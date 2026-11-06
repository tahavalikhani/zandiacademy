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

Optional, for recordings that do not have that shape:

  slide_out — [w, h] the card's size when the slide is not 1.316:1. story.html
              reads it from meta.json and lays the card out around it.
  mask      — [[x, y, w, h, colour], …] boxes filled before cropping, in source
              pixels. For a camera that overlaps the slide's corner: fill it
              with the slide's own ground so her picture is not in the card
              twice. Never over text — only where the camera already hid it.
  cam_delogo — [[x, y, w, h], …] boxes ffmpeg's delogo fills from the pixels
              around them, on the camera only. For an app's toolbar label that
              shows through the camera's feathered edge. Keep y ≥ 1: delogo
              refuses a box touching the frame's edge.
  whiten    — [[x, y, w, h], …] boxes on the slide where anything lighter than
              about 85% grey becomes the slide's white. For a camera's feathered
              edge that fades across the slide's own text, where a mask would
              cut letters: black type survives it, the grey haze does not. The
              box loses its colour, so keep it off any coloured highlight.
  slide_clean_until — N: slide frames 1…N are replaced by frame N+1. For an
              app's popup in the opening frames of a static slide. The camera
              and the voice are untouched; nothing is trimmed.
  sharpen   — true for a small share-sheet copy (848 px wide) that the card
              enlarges 1.3× or more: a light unsharp after the Lanczos scale.

Writes, per clip, OUT_DIR/<id>/s_00001.jpg… (slide), c_00001.jpg… (camera),
poster.jpg (thumbnail), voice.wav (loudness-normalised) and meta.json (frame
count, duration, a loudness envelope per frame that drives the «playing» bars).
Everything is 30 fps whatever the source rate. See docs/video-editing.md §8.
"""
import json
import shutil
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
        ow, oh = c.get("slide_out", [1000, 760])
        dur = float(subprocess.run(["ffprobe", "-v", "error", "-show_entries", "format=duration", "-of", "csv=p=0", src],
                                   capture_output=True, text=True).stdout)
        mask = "".join(f"drawbox=x={x}:y={y}:w={w}:h={h}:color={col}:t=fill," for x, y, w, h, col in c.get("mask", []))
        sharp = ",unsharp=5:5:0.6:5:5:0" if c.get("sharpen") else ""
        delogo = "".join(f"delogo=x={x}:y={y}:w={w}:h={h}," for x, y, w, h in c.get("cam_delogo", []))
        wb = c.get("whiten", [])
        lift = "".join(
            f"[a{i}]split[p{i}][q{i}];[q{i}]crop={w}:{h}:{x}:{y},"
            f"lutyuv=y='if(gt(val\\,200)\\,235\\,val)':u=128:v=128[r{i}];[p{i}][r{i}]overlay={x}:{y}[a{i + 1}];"
            for i, (x, y, w, h) in enumerate(wb))
        # One decode, two crops. lanczos for the upscale of the 720p slides.
        run("-i", src, "-filter_complex",
            f"[0:v]fps={FPS},split[a][b];[a]{mask}null[a0];{lift}"
            f"[a{len(wb)}]crop={sw}:{sh}:{sx}:{sy},scale={ow}:{oh}:flags=lanczos{sharp}[s];"
            f"[b]{delogo}crop={cw}:{ch}:{cx}:{cy},scale=400:500:flags=lanczos{sharp}[c]",
            "-map", "[s]", "-q:v", "2", out / "s_%05d.jpg",
            "-map", "[c]", "-q:v", "2", out / "c_%05d.jpg")
        n = c.get("slide_clean_until", 0)
        for i in range(1, n + 1):
            shutil.copyfile(out / f"s_{n + 1:05d}.jpg", out / f"s_{i:05d}.jpg")
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
        (out / "meta.json").write_text(json.dumps({"frames": frames, "duration": dur, "slide": [ow, oh], "level": level}))
        print(f"{cid}: {frames} frames, {dur:.2f}s")


if __name__ == "__main__":
    main(*sys.argv[1:4])
