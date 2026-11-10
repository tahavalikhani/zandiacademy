# tools/dictionary — rebuilding /dictionary/'s data

**Never run by WordPress.** These scripts turn free downloads into the small
JSON files the theme reads. Their output is committed:

```
inc/data/dictionary/fr/{xx}.json     French words, filed by their first two letters
inc/data/dictionary/fa/{hhhh}.json   Persian words, filed by their first letter's code point
inc/data/dictionary/conj/{xx}.json   full conjugation tables
inc/data/dictionary/meta.json        counts, build date, the homepage lists
assets/dictionary/{fr|fa}-*.json     suggestion lists the BROWSER fetches while typing
```

The inputs are not committed — together they are about 8 GB.

## The sources, exactly

| File | URL | Licence |
| --- | --- | --- |
| French Wiktionary, French words (3.2 GB) | `https://kaikki.org/frwiktionary/Fran%C3%A7ais/kaikki.org-dictionary-Fran%C3%A7ais.jsonl` | CC BY-SA + GFDL |
| English Wiktionary, English words (3.4 GB) | `https://kaikki.org/dictionary/English/kaikki.org-dictionary-English.jsonl` | CC BY-SA + GFDL |
| Tatoeba, French sentences | `https://downloads.tatoeba.org/exports/per_language/fra/fra_sentences.tsv.bz2` | CC BY 2.0 FR |
| Tatoeba, Persian sentences | `https://downloads.tatoeba.org/exports/per_language/pes/pes_sentences.tsv.bz2` | CC BY 2.0 FR |
| Tatoeba, English sentences | `https://downloads.tatoeba.org/exports/per_language/eng/eng_sentences.tsv.bz2` | CC BY 2.0 FR |
| Tatoeba, translation links | `https://downloads.tatoeba.org/exports/links.tar.bz2` | CC BY 2.0 FR |

kaikki.org is Wiktionary extracted by [wiktextract](https://github.com/tatuylonen/wiktextract);
it re-extracts every few weeks. Tatoeba's Persian is `pes`, not `fa`. English
Wiktionary files Iranian Persian as `fa-ira` on most entries and `fa` on older
ones — the extractors take both, and missing `fa-ira` once cut the matches by
half without any error.

Both licences require credit on every page that uses the data, and CC BY-SA
requires saying the adapted data stays CC BY-SA. That is
`template-parts/dictionary/credit.php`. **It is not optional.**

## Rebuilding

From an empty work directory (`W`), with the theme at `T`:

```
cd $W
curl -sS "https://kaikki.org/frwiktionary/Fran%C3%A7ais/kaikki.org-dictionary-Fran%C3%A7ais.jsonl" \
  | python3 -I $T/tools/dictionary/fr_extract.py fr_lemmas.jsonl
curl -sS -C - -o English.jsonl "https://kaikki.org/dictionary/English/kaikki.org-dictionary-English.jsonl"
python3 -I $T/tools/dictionary/en_extract.py    en_pairs.jsonl < English.jsonl
rm English.jsonl
for l in fra pes eng; do
  curl -sS https://downloads.tatoeba.org/exports/per_language/$l/${l}_sentences.tsv.bz2 | bunzip2 > ${l}_sentences.tsv
done
curl -sS https://downloads.tatoeba.org/exports/links.tar.bz2 | tar -xjO > links.csv

python3 -I $T/tools/dictionary/build.py $W $T
php $T/tests/test-dictionary.php
```

Download big files with `-C -` to disk rather than piping when the connection
is unreliable: a pipe that drops mid-file ends the stream cleanly, and the
extractor reports DONE on half the data. The first English extraction here did
exactly that.

`build.py` deletes and rewrites both output folders, so a rebuild cannot leave
a stale shard behind. Then run the test — it checks every key the build filed
against the PHP that looks them up.

## Where a Persian meaning comes from, best first

1. **French Wiktionary's own translation table** for the French word — written
   against one French sense, so the page shows that sense's French definition
   under it.
2. **English Wiktionary, one sense's table listing both languages** — «house:
   human abode» lists maison and خانه. Tables wider than 8 words in either
   language are skipped (the catch-alls), parts of speech must agree, and a
   noun's gender must agree when both sides state one.
**A third source was tried and removed (10 November 2026): the gloss
bridge** — a French word's English gloss («très: very») and that English
word's first Persian translation. It added ~1,270 words and got the sense wrong
too often to keep: «devoir» came out as ورزش (through «exercise»), «bon» as خب.
A missing word is a gap; a wrong one is a lie a learner memorises. Do not
bring it back without a way to check the sense.

Nothing in the pipeline writes, translates or generates a word. Every Persian
string on the site is one somebody typed into Wiktionary.

## Known limits

- One meaning per sense, and fewer senses than a paid dictionary. A common
  word can still be missing; the not-found page offers the nearest spellings.
- Example sentences exist for the words Tatoeba's ~7,000 French–Persian pairs
  happen to contain (direct, or through an English translation).
- Frequency (used only for ORDER, never shown) is counted in Tatoeba's French
  sentences; function words are not counted.
