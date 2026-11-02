#!/usr/bin/env python3
"""
Check every on-screen string in a video timeline before anything is rendered.

    python3 tools/video/check_text.py tools/video/timelines/course-b1-intro.json

The transcript is read from the .transcript.txt beside the timeline. Exits
non-zero on any failure, and render.py runs it first and refuses to render
on a failure. This is the guard against the mistake the owner reported: Persian
on screen that was not what she said, misspelled. See docs/video-editing.md §3.

- A QUOTE ("quote": true) must occur in the transcript, after normalising
  ZWNJ, spaces, punctuation and a trailing «…».
- EVERY Persian string: no Arabic ي/ك, no Latin digits, «می»/«نمی» joined by
  ZWNJ (never a space, never glued).
"""
import json
import re
import sys
from pathlib import Path

ZWNJ = "‌"


def norm(s):
    s = s.replace(ZWNJ, "").replace("…", "")
    s = re.sub(r"[«»،,.!؟?:;\[\]]", " ", s)
    return re.sub(r"\s+", " ", s).strip()


def strings(timeline):
    for g in timeline["groups"]:
        for it in g["items"]:
            if it.get("text"):
                yield it
            for n in it.get("nodes", []):
                yield {"text": n["code"], "kind": "code"}


def main(path):
    tl = json.loads(Path(path).read_text(encoding="utf-8"))
    tpath = Path(path).with_suffix(".transcript.txt")
    lines = [l for l in tpath.read_text(encoding="utf-8").splitlines() if not l.startswith("#")]
    transcript = norm(" ".join(re.sub(r"^\[[\d.]+\]", "", l) for l in lines).replace("[؟]", ""))

    errors = []
    for it in strings(tl):
        t = it["text"]
        persian = re.search(r"[؀-ۿ]", t)
        if it.get("quote") and norm(t) not in transcript:
            errors.append(f"QUOTE not in transcript: «{t}»")
        if persian:
            if re.search(r"[يك]", t):
                errors.append(f"Arabic ي/ك: «{t}»")
            if re.search(r"[0-9]", re.sub(r"[A-Za-z][A-Za-z0-9.]*", "", t)):
                errors.append(f"Latin digit in Persian text: «{t}»")
            if re.search(r"(^|\s)(ن?می)(\s|[؀-ۿ])", t.replace(ZWNJ, "\u0000")):
                errors.append(f"«می» without ZWNJ: «{t}»")
    for e in errors:
        print("FAIL", e)
    print(f"{len(list(strings(tl)))} strings checked, {len(errors)} failures")
    return 1 if errors else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1]))
