# Stream English-Wiktionary English entries (stdin); emit each English word's
# Persian translations, table by table, in the order Wiktionary lists the senses.
# Used only as the LAST source (build.py, «gloss bridge»).
import sys, json, collections
out = open(sys.argv[1], 'w', encoding='utf-8')
n = kept = 0
for line in sys.stdin:
    n += 1
    if '"fa' not in line: continue
    try: e = json.loads(line)
    except Exception: continue
    if e.get('lang_code') != 'en': continue
    tables = collections.OrderedDict()
    for t in e.get('translations', []):
        if t.get('lang_code') not in ('fa', 'fa-ira') or not t.get('word'): continue
        tables.setdefault(t.get('sense') or '', []).append([t['word'], t.get('tags', [])])
    if tables:
        out.write(json.dumps({'en': e.get('word'), 'pos': e.get('pos'), 't': list(tables.items())}, ensure_ascii=False) + '\n'); kept += 1
print('en_fa DONE', n, kept, flush=True)
