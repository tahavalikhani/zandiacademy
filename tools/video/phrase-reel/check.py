#!/usr/bin/env python3
"""
Check a phrase reel's text before anything is rendered. make.sh runs it
first and stops on a failure.

    python3 tools/video/phrase-reel/check.py REEL.json

The French and the Persian are the owner's, copied verbatim from what she
sent; this only catches what a copy can break. Persian: no Arabic ي/ك, no
Latin digits, «می»/«نمی» joined by ZWNJ. French: every *highlight* closed,
no Persian letters. Markup: «*…*» italic + highlighter (French), «[…]» red
(hook and website card), «\\n» a line break on the card.
"""
import json
import re
import sys
from pathlib import Path

ZWNJ = "‌"


def persian(t, where, errors):
    if re.search(r"[يك]", t):
        errors.append(f"{where}: Arabic ي/ك in «{t}»")
    if re.search(r"[0-9]", re.sub(r"[A-Za-z][A-Za-z0-9.]*", "", t)):
        errors.append(f"{where}: Latin digit in «{t}»")
    if re.search(r"(^|\s)(ن?می)(\s|[؀-ۿ])", t.replace(ZWNJ, "\u0000")):
        errors.append(f"{where}: «می» without ZWNJ in «{t}»")
    if t.count("[") != t.count("]"):
        errors.append(f"{where}: unbalanced [ ] in «{t}»")


def main(path):
    r = json.loads(Path(path).read_text(encoding="utf-8"))
    errors = []
    for k in ("slug", "title_fr", "title_fa", "hook", "phrases"):
        if k not in r:
            errors.append(f"missing «{k}»")
    if len(r.get("hook", [])) != 2:
        errors.append("hook must be two lines")
    if not 3 <= len(r.get("phrases", [])) <= 8:
        errors.append("3 to 8 phrases fit the recap list and the dots")
    persian(r.get("title_fa", ""), "title_fa", errors)
    for i, h in enumerate(r.get("hook", [])):
        persian(h, f"hook {i + 1}", errors)
    for k in ("cta", "save", "bio"):
        for t in ([r[k]] if isinstance(r.get(k), str) else r.get(k, [])):
            persian(t, k, errors)
    for i, p in enumerate(r.get("phrases", [])):
        persian(p["fa"], f"phrase {i + 1} fa", errors)
        if p["fr"].count("*") % 2:
            errors.append(f"phrase {i + 1}: unclosed * in «{p['fr']}»")
        if re.search(r"[؀-ۿ]", p["fr"]):
            errors.append(f"phrase {i + 1}: Persian letters in the French line")
        if p["fr"].count("\n") > 1:
            errors.append(f"phrase {i + 1}: at most two lines on a card")
    for e in errors:
        print("FAIL", e)
    print(f"{path}: {len(r.get('phrases', []))} phrases, {len(errors)} failures")
    return 1 if errors else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1]))
