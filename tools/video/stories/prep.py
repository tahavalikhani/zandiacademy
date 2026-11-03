#!/usr/bin/env python3
"""
Cut the owner's screen-recorded lesson clips into the pieces a story is built
from. Each clip is her camera on the left, a slide on the right and a black
band under the slide; a 9:16 story re-lays them out, so they are split here.

    python3 tools/video/stories/prep.py CLIPS.json SRC_DIR OUT_DIR

CLIPS.json maps a clip id to its crop rectangles in SOURCE pixels:

    "2928": {"file": "IMG_2928.mov", "slide": [x, y, w, h], "cam": [x, y, w, h],
             "poster_at": 5.0, "band": 898}

  slide  — 1.316:1 (shown at 1000×760). Pad into the slide's own margin rather
           than cut a line of text; a story is read on a phone.
  cam    — 4:5 (shown at 400×500). Never wider than the camera column.
  band   — the y where the black band starts; the thumbnail is cut above it.

Writes, per clip, OUT_DIR/<id>/s_00001.jpg… (slide), c_00001.jpg… (camera),
poster.jpg (thumbnail), voice.wav (loudness-normalised) and meta.json (frame
count, duration, a loudness envelope per frame that drives the «playing» bars).
Everything is 30 fps whatever the source rate. See docs/video-editing.md §8.
"""
import json
import subprocess
import sys
import wave
from pathlib import Path

import numpy as np

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
from sfx import loudnorm_voice  # noqa: E402

FPS = 30


def run(*a):
    subprocess.run(["ffmpeg", "-v", "error", "-y", *map(str, a)], check=True)


def main(cfg_path, src_dir, out_dir):
    cfg = json.loads(Path(cfg_path).read_text(encoding="utf-8"))
    for cid, c in cfg.items():
        src = Path(src_dir) / c["file"]
        out = Path(out_dir) / cid
        out.mkdir(parents=True, exist_ok=True)
        sx, sy, sw, sh = c["slide"]
        cx, cy, cw, ch = c["cam"]
        dur = float(subprocess.run(["ffprobe", "-v", "error", "-show_entries", "format=duration", "-of", "csv=p=0", src],
                                   capture_output=True, text=True).stdout)
        # One decode, two crops. lanczos for the upscale of the 720p slides.
        run("-i", src, "-filter_complex",
            f"[0:v]fps={FPS},split[a][b];"
            f"[a]crop={sw}:{sh}:{sx}:{sy},scale=1000:760:flags=lanczos[s];"
            f"[b]crop={cw}:{ch}:{cx}:{cy},scale=400:500:flags=lanczos[c]",
            "-map", "[s]", "-q:v", "2", out / "s_%05d.jpg",
            "-map", "[c]", "-q:v", "2", out / "c_%05d.jpg")
        run("-ss", c.get("poster_at", 3), "-i", src, "-frames:v", "1",
            "-vf", f"crop=iw:{c['band']}:0:0,scale=560:-2:flags=lanczos", "-q:v", "2", out / "poster.jpg")
        loudnorm_voice(str(src), str(out / "voice.wav"))
        with wave.open(str(out / "voice.wav")) as w:
            sr = w.getframerate()
            v = np.frombuffer(w.readframes(w.getnframes()), np.int16).reshape(-1, 2).mean(1) / 32768
        frames = len(list(out.glob("s_*.jpg")))
        hop = sr // FPS
        rms = [float(np.sqrt(np.mean(v[i * hop:(i + 1) * hop] ** 2) + 1e-12)) for i in range(frames)]
        peak = np.percentile(rms, 95) or 1
        level = [round(min(1.0, r / peak), 3) for r in rms]
        (out / "meta.json").write_text(json.dumps({"frames": frames, "duration": dur, "level": level}))
        print(f"{cid}: {frames} frames, {dur:.2f}s")


if __name__ == "__main__":
    main(*sys.argv[1:4])
