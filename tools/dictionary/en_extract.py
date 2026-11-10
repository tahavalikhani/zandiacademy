# Stream English-Wiktionary English entries (stdin); emit French-Persian pairs that share one English sense's translation table.
import sys, json, collections
out = open(sys.argv[1], 'w', encoding='utf-8')
n = pairs = 0
for line in sys.stdin:
    n += 1
    if '"fa' not in line or '"fr"' not in line: continue
    try: e = json.loads(line)
    except Exception: continue
    if e.get('lang_code') != 'en': continue
    groups = collections.defaultdict(lambda: {'fr': [], 'fa': []})
    for t in e.get('translations', []):
        lc = t.get('lang_code')
        # English Wiktionary files Persian as 'fa-ira' (Iranian Persian) on
        # most entries and 'fa' on older ones; 'prs' (Dari) is left out.
        if lc in ('fa', 'fa-ira'):
            lc = 'fa'
        elif lc != 'fr':
            continue
        key = t.get('sense') or ''
        groups[key][lc].append([t.get('word'), t.get('tags', [])])
    for sense, g in groups.items():
        if g['fr'] and g['fa']:
            out.write(json.dumps({'en': e.get('word'), 'pos': e.get('pos'), 's': sense, 'fr': g['fr'], 'fa': g['fa']}, ensure_ascii=False) + '\n'); pairs += 1
    if n % 250000 == 0: print('en', n, pairs, flush=True)
print('en DONE', n, pairs, flush=True)
