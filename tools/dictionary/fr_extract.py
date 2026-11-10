# Stream French-Wiktionary (French words) JSONL from stdin; keep lemma entries compactly.
import sys, json
out = open(sys.argv[1], 'w', encoding='utf-8')
n = kept = 0
for line in sys.stdin:
    n += 1
    try: e = json.loads(line)
    except Exception: continue
    pt = e.get('pos_title', '') or ''
    if pt.startswith('Forme') or e.get('pos') in ('name', 'symbol', 'character', 'prefix', 'suffix', 'infix', 'interfix', 'abbrev', 'romanization'):
        continue
    w = e.get('word')
    if not w or ' ' in w and len(w) > 40: continue
    ipa = ''
    for s in e.get('sounds', []):
        if s.get('ipa', '').startswith('\\'):
            ipa = s['ipa'].strip('\\'); break
    gl = []
    for s in e.get('senses', []):
        g = (s.get('glosses') or [''])[-1]
        if g and len(g) < 220 and 'form_of' not in s: gl.append([g, s.get('tags', [])])
        if len(gl) >= 6: break
    fa = [[t.get('word'), t.get('sense', ''), t.get('sense_index', 0)] for t in e.get('translations', []) if t.get('lang_code') in ('fa', 'pes') and t.get('word')]
    forms = []
    for f in e.get('forms', []):
        if e.get('pos') == 'verb' and 'Conjugaison' not in (f.get('source') or ''): continue
        forms.append([f.get('form'), f.get('tags', [])])
    rec = {'w': w, 'p': e.get('pos'), 'pt': pt, 't': e.get('tags', []), 'i': ipa, 'g': gl, 'fa': fa, 'f': forms}
    out.write(json.dumps(rec, ensure_ascii=False) + '\n'); kept += 1
    if n % 250000 == 0: print('fr', n, kept, flush=True)
print('fr DONE', n, kept, flush=True)
