#!/usr/bin/env python3
"""
Builds the dictionary's data files from three free sources.

NEVER RUN BY WORDPRESS. This is the offline half of /dictionary/: it reads the
raw downloads (see tools/dictionary/README.md for the exact URLs and the two
extract scripts that shrink them first), joins them, and writes the small JSON
shards the theme reads at request time. The output is committed; the inputs are
not, because together they are several gigabytes.

    python3 -I tools/dictionary/build.py <work-dir> <theme-dir>

<work-dir> must hold:
    fr_lemmas.jsonl     fr_extract.py's output (French Wiktionary, French words)
    en_pairs.jsonl      en_extract.py's output (English Wiktionary translation
                        tables that list both French and Persian)
    fra_sentences.tsv   Tatoeba, French
    pes_sentences.tsv   Tatoeba, Persian
    eng_sentences.tsv   Tatoeba, English (only as a bridge, never shown)
    links.csv           Tatoeba, which sentence translates which

WHERE A PERSIAN MEANING COMES FROM, BEST FIRST

1. French Wiktionary's own «Traductions» table for the French word. Written by
   a person, against one French sense, so it carries that sense's French
   definition with it.
2. English Wiktionary, where one English sense's translation table lists both
   a French and a Persian word: «house — human abode» lists maison and خانه,
   so maison→خانه. Every pair is still a human translation; the only guess is
   that the two translators meant the same sense, which is what the table is
   for. Tables listing more than MAX_TABLE words in either language are
   skipped: those are the catch-alls («get», «make») where the guess fails.
   A pair is attached to a French entry only if the parts of speech agree, and
   for a noun only if the gender agrees when both sides state one.

Nothing in this file translates, guesses or generates text.

Example sentences come from Tatoeba: French sentences with a Persian
translation, directly or through an English translation of the French one.

Frequency — used only to ORDER things (suggestions, the equivalents on a
Persian page, the homepage lists) and never shown as a number — is how often a
word occurs in Tatoeba's 730,000 French sentences.
"""

import collections
import json
import os
import re
import shutil
import sys
import time

MAX_TABLE = 8          # skip translation tables wider than this in either language
MAX_GROUPS = 6         # Persian meaning groups per French entry
MAX_PER_GROUP = 5      # Persian words per group
MAX_EXAMPLES = 3
MAX_DEFS = 4

KEEP_POS = {
    'noun', 'verb', 'adj', 'adv', 'prep', 'pron', 'conj', 'intj', 'phrase',
    'det', 'article', 'num', 'particle', 'proverb',
}
EN_POS = {'noun': 'noun', 'verb': 'verb', 'adj': 'adj', 'adv': 'adv', 'prep': 'prep',
          'pron': 'pron', 'conj': 'conj', 'intj': 'intj', 'phrase': 'phrase', 'num': 'num',
          'det': 'det', 'article': 'article', 'particle': 'particle', 'proverb': 'proverb'}
STOP = set('''le la les l un une des du de d au aux et ou mais donc or ni car que qu qui
quoi dont où ce c cet cette ces se s je j tu il elle on nous vous ils elles me m te t lui
leur leurs y en ne n pas plus à dans par pour sur sous avec sans chez vers son sa ses mon
ma mes ton ta tes notre nos votre vos est sont suis es sommes êtes a as ai ont avons avez'''.split())
SKIP_FA_TAGS = {'archaic', 'obsolete', 'dated', 'dialectal', 'rare', 'Dari', 'Tajik',
                'poetic', 'regional', 'derogatory', 'vulgar', 'slang', 'literally', 'Classical'}

# ---------------------------------------------------------------- normalisation
# MUST match zandi_dict_norm_fr() / zandi_dict_norm_fa() in inc/dictionary.php
# character for character. tests/test-dictionary.php checks the PHP side against
# keys this script wrote.

FR_MAP = str.maketrans({
    'à': 'a', 'â': 'a', 'ä': 'a', 'á': 'a', 'ã': 'a', 'å': 'a',
    'ç': 'c', 'é': 'e', 'è': 'e', 'ê': 'e', 'ë': 'e',
    'í': 'i', 'ì': 'i', 'î': 'i', 'ï': 'i', 'ñ': 'n',
    'ó': 'o', 'ò': 'o', 'ô': 'o', 'ö': 'o', 'õ': 'o',
    'ú': 'u', 'ù': 'u', 'û': 'u', 'ü': 'u', 'ý': 'y', 'ÿ': 'y',
    '’': "'", '‘': "'", 'ʼ': "'",
})


def norm_fr(s):
    s = s.strip().lower().replace('œ', 'oe').replace('æ', 'ae')
    s = s.translate(FR_MAP)
    return re.sub(r'\s+', ' ', s)


FA_DIACRITICS = re.compile('[ً-ٰٟـ]')


def clean_fa(s):
    """Display form: no harakat, Persian ی/ک, no notes in brackets."""
    if not s:
        return ''
    s = FA_DIACRITICS.sub('', s)
    s = s.replace('ي', 'ی').replace('ك', 'ک').replace('ى', 'ی')
    s = re.sub(r'\([^)]*\)', '', s)
    s = re.sub(r'[\s ]+', ' ', s).strip(' ،,;؛-')
    return s


def split_fa(s):
    """One source string can hold several meanings: «رد شدن -عبور کردن»."""
    out = []
    for part in re.split(r'[،,;؛/]|\s-\s?|\s?-\s', clean_fa(s)):
        part = part.strip(' -')
        if good_fa(part) and part not in out:
            out.append(part)
    return out


def norm_fa(s):
    s = clean_fa(s)
    s = s.replace('أ', 'ا').replace('إ', 'ا').replace('ة', 'ه').replace('ۀ', 'ه')
    s = s.replace('‌', '').replace(' ', '')
    return s


def fr_shard(key):
    two = (key + '__')[:2]
    return ''.join(c if 'a' <= c <= 'z' else '_' for c in two)


def fa_shard(key):
    return '%04x' % ord(key[0]) if key else '0000'


def good_fa(s):
    return bool(s) and 2 <= len(s) <= 40 and not re.search(r'[A-Za-z0-9/<>\[\]{}=]', s) \
        and re.search('[؀-ۿ]', s) is not None


# ---------------------------------------------------------------- conjugation
TENSES = [
    ('present', ('indicative', 'present')),
    ('passe_compose', ('indicative', 'past', 'multiword-construction')),
    ('imparfait', ('indicative', 'imperfect')),
    ('futur', ('indicative', 'future')),
    ('conditionnel', ('conditional', 'present')),
    ('subjonctif', ('subjunctive', 'present')),
    ('imperatif', ('imperative', 'present')),
    ('plus_que_parfait', ('indicative', 'pluperfect')),
    ('futur_anterieur', ('indicative', 'future', 'perfect')),
    ('conditionnel_passe', ('conditional', 'past')),
    ('subjonctif_passe', ('subjunctive', 'past')),
    ('passe_simple', ('indicative', 'past')),
    ('passe_anterieur', ('indicative', 'past', 'anterior')),
    ('subjonctif_imparfait', ('subjunctive', 'imperfect')),
    ('subjonctif_plus_que_parfait', ('subjunctive', 'pluperfect')),
    ('imperatif_passe', ('imperative', 'past')),
]
TAGS_TO_TENSE = {tags: name for name, tags in TENSES}
SIMPLE_TENSES = {'present', 'imparfait', 'futur', 'conditionnel', 'subjonctif', 'imperatif',
                 'passe_simple', 'subjonctif_imparfait'}
PRONOUN = re.compile(
    r"^((?:que |qu’)?(?:je |j’|tu |il/elle/on |nous |vous |ils/elles )"
    r"(?:me |m’|te |t’|se |s’|nous |vous )?)"
)


def split_pronoun(form):
    m = PRONOUN.match(form)
    if not m:
        return ['', form]
    return [m.group(1).rstrip(), form[m.end():]]


def conjugation(entry):
    tenses = collections.OrderedDict()
    extra = {}
    for form, tags in entry.get('f', []):
        t = tuple(tags)
        if t == ('participle', 'present'):
            extra.setdefault('ppr', form)
        elif t == ('participle', 'past'):
            extra.setdefault('pp', form)
        elif t == ('gerund', 'present'):
            extra.setdefault('ger', form)
        name = TAGS_TO_TENSE.get(t)
        if not name:
            continue
        rows = tenses.setdefault(name, [])
        limit = 3 if name.startswith('imperatif') else 6
        if len(rows) < limit:
            rows.append(split_pronoun(form))
    if 'present' not in tenses:
        return None
    out = {'t': {name: tenses[name] for name, _ in TENSES if name in tenses}}
    out.update(extra)
    pc = tenses.get('passe_compose')
    if pc and pc[0][1]:
        aux = pc[0][1].split(' ')[0]
        out['aux'] = 'être' if aux in ('suis', 'me', 'm’') or aux.startswith('suis') else 'avoir'
        if pc[0][1].startswith('suis'):
            out['aux'] = 'être'
    return out


def simple_forms(conj):
    """Single-word forms a student might type: prends, prenions, pris."""
    out = set()
    for name, rows in conj['t'].items():
        if name not in SIMPLE_TENSES:
            continue
        for _, rest in rows:
            w = rest.split(' ')[-1]
            if w and ' ' not in w:
                out.add(w)
    for k in ('pp', 'ppr'):
        if conj.get(k):
            out.add(conj[k])
    return out


# ---------------------------------------------------------------- helpers

def gloss_head(gloss, pos):
    """«to put, to place (something)» → 'put'; anything not a bare word → ''."""
    g = re.sub(r'\([^)]*\)', '', gloss).lower()
    first = re.split(r'[;,:]', g)[0].strip()
    if pos == 'verb':
        if not first.startswith('to '):
            return ''
        first = first[3:]
    first = re.sub(r'^(a|an|the) ', '', first).strip(' .')
    if re.fullmatch(r"[a-z][a-z'\-]*( [a-z][a-z'\-]*)?", first):
        return first
    return ''


def tokens_fr(s):
    return [t for t in re.split(r"[^a-zàâäáãåçéèêëíìîïñóòôöõúùûüýÿœæ\-]+", s.lower().replace('’', "'")) if t]


def tokens_fa(s):
    s = clean_fa(s)
    return [norm_fa(t) for t in re.split(r'[\s‌،؛.!؟?:«»"\'()]+', s) if t]


def log(*a):
    print(time.strftime('%H:%M:%S'), *a, flush=True)


def main():
    work, theme = sys.argv[1], sys.argv[2]
    data_dir = os.path.join(theme, 'inc', 'data', 'dictionary')
    sug_dir = os.path.join(theme, 'assets', 'dictionary')

    # 1. French entries ------------------------------------------------------
    entries = collections.defaultdict(list)          # word -> [entry]
    for line in open(os.path.join(work, 'fr_lemmas.jsonl'), encoding='utf-8'):
        e = json.loads(line)
        if e['p'] not in KEEP_POS or 'alt-of' in e['t']:
            continue
        e['groups'] = []                              # [{'fa': [...], 'd': gloss, 'score': n}]
        entries[e['w']].append(e)
    log('french words', len(entries))
    all_pos = {w: list(es) for w, es in entries.items()}

    # 2. Direct translations (French Wiktionary) ------------------------------
    for word, es in entries.items():
        for e in es:
            by_sense = collections.OrderedDict()
            for fa, sense, idx in e['fa']:
                for part in split_fa(fa):
                    by_sense.setdefault((idx, sense), []).append(part)
            for (idx, sense), words in by_sense.items():
                e['groups'].append({'fa': words, 'd': sense.rstrip('.') if sense else '', 'score': 100})

    # 3. Triangulated through English ----------------------------------------
    support = collections.Counter()
    tables = []
    for line in open(os.path.join(work, 'en_pairs.jsonl'), encoding='utf-8'):
        t = json.loads(line)
        if len(t['fr']) > MAX_TABLE or len(t['fa']) > MAX_TABLE:
            continue
        fa = []
        for w, tags in t['fa']:
            if SKIP_FA_TAGS.intersection(tags):
                continue
            for part in split_fa(w):
                if part not in fa:
                    fa.append(part)
        if not fa:
            continue
        t['fa'] = fa[:MAX_PER_GROUP]
        tables.append(t)
        t['fr'] = [[w, tags] for w, tags in t['fr'] if w]
        for frw, _ in t['fr']:
            for w in t['fa']:
                support[(frw, w)] += 1
    log('usable english tables', len(tables))

    attached = 0
    for t in tables:
        pos = EN_POS.get(t['pos'])
        if not pos:
            continue
        for frw, tags in t['fr']:
            cands = [e for e in entries.get(frw, []) if e['p'] == pos]
            if not cands:
                continue
            if pos == 'noun':
                g = 'masculine' if 'masculine' in tags else 'feminine' if 'feminine' in tags else None
                if g:
                    same = [e for e in cands if g in e['t']]
                    if same:
                        cands = same
                    elif any(('masculine' in e['t'] or 'feminine' in e['t']) for e in cands):
                        continue
            e = cands[0]
            score = sum(support[(frw, w)] for w in t['fa'])
            e['groups'].append({'fa': list(t['fa']), 'd': '', 'score': score})
            attached += 1
    log('triangulated groups attached', attached)

    # 3b. The gloss bridge — LAST, and only to fill a gap ----------------------
    # English Wiktionary's French entries gloss each word in English («très» —
    # «very»); its English entries list Persian translations sense by sense.
    # For a French entry the first two sources left with fewer than two
    # meanings, take its first two glosses, reduce each to a bare English
    # headword of the same part of speech, and borrow the Persian of that
    # word's FIRST translation table — the sense Wiktionary lists first. The
    # words borrowed are still human translations; what is inferred is that
    # the gloss and the first sense agree, which is why this ranks last and
    # never adds to a word that is already well covered. Optional: the build
    # runs without these two files.
    fr_en_path = os.path.join(work, 'fr_en.jsonl')
    en_fa_path = os.path.join(work, 'en_fa.jsonl')
    if os.path.exists(fr_en_path) and os.path.exists(en_fa_path):
        en_fa = {}
        for line in open(en_fa_path, encoding='utf-8'):
            r = json.loads(line)
            key = (r['en'].lower(), r['pos'])
            if key in en_fa:
                continue
            for _, words in r['t']:
                fa = []
                for w, tags in words:
                    if SKIP_FA_TAGS.intersection(tags):
                        continue
                    for part in split_fa(w):
                        if part not in fa:
                            fa.append(part)
                if fa:
                    en_fa[key] = fa[:4]
                    break
        bridged = 0
        for line in open(fr_en_path, encoding='utf-8'):
            r = json.loads(line)
            pos = EN_POS.get(r['p'])
            cands = [e for e in entries.get(r['w'], []) if e['p'] == pos]
            if not pos or not cands or len(cands[0]['groups']) >= 2:
                continue
            e = cands[0]
            for gloss in r['g'][:2]:
                head = gloss_head(gloss, pos)
                fa = en_fa.get((head, r['p'])) if head else None
                if fa:
                    e['groups'].append({'fa': list(fa), 'd': '', 'score': 0})
                    bridged += 1
        log('gloss-bridge groups added', bridged)

    # Tidy groups: dedupe words across groups, merge, cap.
    keep = {}
    for word, es in entries.items():
        kept = []
        for e in es:
            seen = set()
            groups = sorted(e['groups'], key=lambda g: -g['score'])
            out = []
            for g in groups:
                ws = [w for w in g['fa'] if norm_fa(w) not in seen]
                for w in ws:
                    seen.add(norm_fa(w))
                if ws:
                    out.append({'fa': ws[:MAX_PER_GROUP], 'd': g['d']})
                if len(out) >= MAX_GROUPS:
                    break
            if out:
                e['groups'] = out
                kept.append(e)
        if kept:
            keep[word] = kept
    log('french words with a persian meaning', len(keep))

    # 4. Conjugations and the form index -------------------------------------
    conj = {}
    forms = collections.defaultdict(set)   # form key -> lemma words
    for word, es in keep.items():
        for e in es:
            if e['p'] == 'verb' and word not in conj:
                c = conjugation(e)
                if c:
                    conj[word] = c
                    for f in simple_forms(c):
                        if f != word:
                            forms[norm_fr(f)].add(word)
            elif e['p'] in ('noun', 'adj'):
                for f, tags in e.get('f', []):
                    if f and f != word and ' ' not in f and ('plural' in tags or 'feminine' in tags):
                        forms[norm_fr(f)].add(word)
    log('conjugated verbs', len(conj), 'typed forms', len(forms))

    # 5. Frequency from Tatoeba ------------------------------------------------
    def lemmas_of(tok):
        k = norm_fr(tok)
        out = set()
        if tok in keep:
            out.add(tok)
        out.update(forms.get(k, ()))
        return out

    fra, pes, eng = {}, {}, set()
    for line in open(os.path.join(work, 'fra_sentences.tsv'), encoding='utf-8'):
        i, _, txt = line.rstrip('\n').split('\t', 2)
        fra[int(i)] = txt
    for line in open(os.path.join(work, 'pes_sentences.tsv'), encoding='utf-8'):
        i, _, txt = line.rstrip('\n').split('\t', 2)
        pes[int(i)] = txt
    for line in open(os.path.join(work, 'eng_sentences.tsv'), encoding='utf-8'):
        eng.add(int(line.split('\t', 1)[0]))
    log('tatoeba fra/pes/eng', len(fra), len(pes), len(eng))

    # Two passes, because a typed form can belong to several words: «suis» is
    # être and suivre, «est» is être and the noun «est», «des» is the article
    # and the plural of «dé». Pass one counts only unambiguous tokens; pass two
    # gives each ambiguous token to whichever candidate pass one found commoner.
    # Function words are not counted at all — they would only crowd the
    # content words this ordering exists for.
    tok_cache = {}
    tok_count = collections.Counter()
    for txt in fra.values():
        tok_count.update(tokens_fr(txt))
    freq = collections.Counter()
    ambiguous = {}
    for tok, n in tok_count.items():
        if tok in STOP or len(tok) < 2:
            tok_cache[tok] = set()
            continue
        ls = tok_cache[tok] = lemmas_of(tok)
        if len(ls) == 1:
            freq[next(iter(ls))] += n
        elif ls:
            ambiguous[tok] = (ls, n)
    for tok, (ls, n) in ambiguous.items():
        best = max(sorted(ls), key=lambda w: freq[w])
        freq[best] += n
        tok_cache[tok] = {best}
    rank = {w: i for i, w in enumerate(sorted(keep, key=lambda w: (-freq[w], w)))}

    # 6. French–Persian sentence pairs ----------------------------------------
    fr_pes = collections.defaultdict(set)
    fr_eng = collections.defaultdict(set)
    eng_pes = collections.defaultdict(set)
    with open(os.path.join(work, 'links.csv'), encoding='utf-8') as fh:
        for line in fh:
            a, b = line.split()
            a, b = int(a), int(b)
            if a in fra:
                if b in pes:
                    fr_pes[a].add(b)
                elif b in eng:
                    fr_eng[a].add(b)
            elif a in eng and b in pes:
                eng_pes[a].add(b)
    pairs = {}
    for f, ps in fr_pes.items():
        pairs[f] = (min(ps), True)
    for f, es in fr_eng.items():
        if f in pairs:
            continue
        for e in sorted(es):
            if eng_pes.get(e):
                pairs[f] = (min(eng_pes[e]), False)
                break
    log('french sentences with a persian translation', len(pairs),
        'direct', sum(1 for v in pairs.values() if v[1]))

    ex_fr = collections.defaultdict(list)
    ex_fa = collections.defaultdict(list)
    fa_heads = set()
    for es in keep.values():
        for e in es:
            for g in e['groups']:
                for w in g['fa']:
                    fa_heads.add(norm_fa(w))
    for f, (p, direct) in pairs.items():
        ft, pt = fra[f], pes[p]
        if not (8 <= len(ft) <= 90) or len(pt) > 120:
            continue
        item = (0 if direct else 1, len(ft), ft, pt)
        for lemma in {l for tok in tokens_fr(ft) for l in (tok_cache[tok] if tok in tok_cache else lemmas_of(tok))}:
            ex_fr[lemma].append(item)
        for k in set(tokens_fa(pt)):
            if k in fa_heads:
                ex_fa[k].append(item)

    def pick(items):
        items.sort()
        out, seen = [], set()
        for _, _, ft, pt in items:
            if ft in seen:
                continue
            seen.add(ft)
            out.append([ft, pt])
            if len(out) >= MAX_EXAMPLES:
                break
        return out

    # 7. Assemble French pages --------------------------------------------------
    def pos_code(e):
        g = ''
        if 'masculine' in e['t'] and 'feminine' in e['t']:
            g = 'mf'
        elif 'masculine' in e['t']:
            g = 'm'
        elif 'feminine' in e['t']:
            g = 'f'
        return g

    fr_pages = {}
    for word, es in keep.items():
        blocks = []
        for e in es:
            b = {'p': e['p'], 'm': [[g['fa'], g['d']] for g in e['groups']]}
            g = pos_code(e)
            if g:
                b['g'] = g
            if e['i']:
                b['i'] = e['i']
            defs = [d for d, tags in e['g'] if d][:MAX_DEFS]
            if defs:
                b['d'] = defs
            if e['p'] in ('noun', 'adj'):
                fl = [[f, 'pl' if 'plural' in tags else 'f'] for f, tags in e.get('f', [])
                      if f and f != word and ('plural' in tags or 'feminine' in tags)][:3]
                if fl:
                    b['f'] = fl
            blocks.append(b)
        page = {'w': word, 'b': blocks, 'r': rank[word]}
        if word in conj:
            page['c'] = {'present': conj[word]['t']['present'], 'aux': conj[word].get('aux', ''),
                         'pp': conj[word].get('pp', '')}
        x = pick(ex_fr.get(word, []))
        if x:
            page['x'] = x
        fr_pages[word] = page

    # 8. Persian pages ----------------------------------------------------------
    fa_pages = {}
    for word in sorted(keep, key=lambda w: rank[w]):
        for b in fr_pages[word]['b']:
            context = [w for g in b['m'] for w in g[0]]
            for gi, (fa_words, _) in enumerate(b['m']):
                for w in fa_words:
                    k = norm_fa(w)
                    if not k:
                        continue
                    p = fa_pages.setdefault(k, {'w': w, 'fr': [], 'seen': set()})
                    if (word, b['p']) in p['seen']:
                        continue
                    p['seen'].add((word, b['p']))
                    ctx = [c for c in context if norm_fa(c) != k][:4]
                    item = [word, b['p'], b.get('g', ''), ctx]
                    p['fr'].append((gi, rank[word], item))
    for k, p in fa_pages.items():
        p['fr'] = [it for _, _, it in sorted(p['fr'], key=lambda t: (t[0], t[1]))][:12]
        p.pop('seen')
        x = pick(ex_fa.get(k, []))
        if x:
            p['x'] = x
    log('persian headwords', len(fa_pages))

    # 9. Write shards -------------------------------------------------------------
    for d in (data_dir, sug_dir):
        if os.path.isdir(d):
            shutil.rmtree(d)
    for sub in ('fr', 'fa', 'conj'):
        os.makedirs(os.path.join(data_dir, sub))
    os.makedirs(sug_dir)

    def dump(path, obj):
        with open(path, 'w', encoding='utf-8') as fh:
            json.dump(obj, fh, ensure_ascii=False, separators=(',', ':'), sort_keys=True)

    fr_sh = collections.defaultdict(lambda: {'e': {}, 'f': {}})
    for word, page in fr_pages.items():
        fr_sh[fr_shard(norm_fr(word))]['e'].setdefault(norm_fr(word), []).append(page)
    for k, lemmas in forms.items():
        fr_sh[fr_shard(k)]['f'][k] = sorted(lemmas, key=lambda w: rank[w])
    for name, obj in fr_sh.items():
        dump(os.path.join(data_dir, 'fr', name + '.json'), obj)

    conj_sh = collections.defaultdict(dict)
    for word, c in conj.items():
        conj_sh[fr_shard(norm_fr(word))][word] = c
    for name, obj in conj_sh.items():
        dump(os.path.join(data_dir, 'conj', name + '.json'), obj)

    fa_sh = collections.defaultdict(dict)
    for k, p in fa_pages.items():
        fa_sh[fa_shard(k)][k] = p
    for name, obj in fa_sh.items():
        dump(os.path.join(data_dir, 'fa', name + '.json'), obj)

    # Suggestions: [display, gloss, lemma-or-empty], most frequent first.
    # Single words before phrases, so «pren» offers prendre before «prendre la mort».
    sug = collections.defaultdict(list)
    for word in sorted(keep, key=lambda w: (' ' in w, rank[w])):
        first = fr_pages[word]['b'][0]['m'][0][0][0]
        sug['fr-' + fr_shard(norm_fr(word))].append([word, first, ''])
    for k, lemmas in sorted(forms.items(), key=lambda kv: min(rank[w] for w in kv[1])):
        if k in {norm_fr(w) for w in lemmas}:
            continue
        for lemma in sorted(lemmas, key=lambda w: rank[w])[:2]:
            sug['fr-' + fr_shard(k)].append([k, '', lemma])
    fa_order = sorted(fa_pages.items(), key=lambda kv: rank[kv[1]['fr'][0][0]])
    for k, p in fa_order:
        sug['fa-' + fa_shard(k)].append([p['w'], p['fr'][0][0], ''])
    for name, rows in sug.items():
        dump(os.path.join(sug_dir, name + '.json'), rows)

    # Homepage lists and provenance.
    # The homepage list shows one gloss per word, so it only takes words that
    # have ONE part of speech anywhere in French Wiktionary — with or without
    # a Persian meaning. «bien» is mostly the adverb «well», but only its noun
    # «goods» has Persian; listing it as «متاع» would be true and misleading.
    def one_pos(w):
        kinds = {e['p'] for e in all_pos.get(w, [])}
        return len(kinds) == 1 and kinds <= {'noun', 'verb', 'adj'}
    common = [w for w in sorted(keep, key=lambda w: rank[w]) if one_pos(w)][:24]
    verbs = [w for w in sorted(conj, key=lambda w: rank[w])][:12]
    meta = {
        'built': time.strftime('%Y-%m-%d'),
        'counts': {'fr': len(fr_pages), 'fa': len(fa_pages), 'verbs': len(conj),
                   'examples': len(pairs)},
        'common': [[w, fr_pages[w]['b'][0]['m'][0][0][0]] for w in common],
        'verbs': [[w, fr_pages[w]['b'][0]['m'][0][0][0]] for w in verbs],
    }
    dump(os.path.join(data_dir, 'meta.json'), meta)
    log('done', meta['counts'])


if __name__ == '__main__':
    main()
