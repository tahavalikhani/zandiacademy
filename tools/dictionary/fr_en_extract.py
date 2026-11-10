# Stream English-Wiktionary FRENCH entries (stdin); keep each French word's
# part of speech and its first English glosses. Used only by the gloss bridge.
import sys, json
out = open(sys.argv[1], 'w', encoding='utf-8')
n = kept = 0
for line in sys.stdin:
    n += 1
    try: e = json.loads(line)
    except Exception: continue
    if e.get('lang_code') != 'fr' or not e.get('word'): continue
    gl = []
    for s in e.get('senses', []):
        if s.get('form_of') or s.get('alt_of'): continue
        g = (s.get('glosses') or [''])[-1]
        if g: gl.append(g)
        if len(gl) >= 3: break
    if gl:
        out.write(json.dumps({'w': e['word'], 'p': e.get('pos'), 'g': gl}, ensure_ascii=False) + '\n'); kept += 1
print('fr_en DONE', n, kept, flush=True)
