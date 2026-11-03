#!/usr/bin/env python3
"""
A course page's sample-lesson video: a short animated title, then the owner's
lesson clips one after another, each shown WHOLE on a navy ground.

    node    tools/video/preview/render-title.js CONFIG.json $W
    python3 tools/video/preview/make_preview.py CONFIG.json SRC_DIR $W OUT.mp4

The owner's brief (3 November 2026): «it shouldn't feel edited». So nothing
inside a clip is touched. There is no crop of her or the board, no cut, no
caption and no music. Each clip is scaled down to sit on the ground with
rounded corners and a soft shadow, and fades in and out over 0.4 s between
clips. The one thing removed is all-black rows the screen recorder left
under the whole picture (`height` in CONFIG; measured, never a guess). Those
rows hold neither her nor the board. Output follows docs/video-editing.md §2.
The fades are on the video only. Her voice plays every sample.
"""
import json
import subprocess
import sys
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
import sfx as fx  # noqa: E402

W, H, FPS = 1280, 720, 30
BOX_H = 630          # every clip is this tall: 45 px of ground above and below
RADIUS, FADE = 18, 0.4


def run(*a):
    subprocess.run(["ffmpeg", "-v", "error", "-y", *map(str, a)], check=True)


def probe(path, entry):
    return subprocess.run(["ffprobe", "-v", "error", "-select_streams", "v:0", "-show_entries", entry,
                           "-of", "csv=p=0", str(path)], capture_output=True, text=True).stdout.strip()


def mask(w, h, path):
    m = Image.new("L", (w, h), 0)
    ImageDraw.Draw(m).rounded_rectangle((0, 0, w - 1, h - 1), RADIUS, fill=255)
    m.save(path)


def shadow(w, h, path, pad=70):
    s = Image.new("RGBA", (w + 2 * pad, h + 2 * pad), (0, 0, 0, 0))
    ImageDraw.Draw(s).rounded_rectangle((pad, pad + 16, pad + w, pad + h + 16), RADIUS, fill=(6, 16, 36, 150))
    s.filter(ImageFilter.GaussianBlur(26)).save(path)
    return pad


def title_audio(path, dur):
    """Three quiet effects under the title, per docs/video-editing.md §6."""
    import numpy as np  # noqa: PLC0415
    import wave  # noqa: PLC0415
    n = int(dur * fx.SR)
    buf = np.zeros(n)
    for t, s, db in ((.15, fx.pop, -31), (.45, fx.whoosh, -33), (1.4, fx.chime, -34)):
        x = s()
        x = x / (abs(x).max() + 1e-9) * 10 ** (db / 20)
        i = int(t * fx.SR)
        buf[i:i + len(x)] += x[: n - i]
    with wave.open(str(path), "wb") as w:
        w.setnchannels(2); w.setsampwidth(2); w.setframerate(fx.SR)
        w.writeframes((np.repeat(buf[:, None], 2, 1) * 32767).astype(np.int16).tobytes())


def main(cfg_path, src_dir, work, out):
    cfg = json.loads(Path(cfg_path).read_text(encoding="utf-8"))
    work = Path(work)
    ground = work / "ground.png"
    segs = []

    # 1. the title over the ground
    t = cfg["title"]
    title_audio(work / "title.wav", t["duration"])
    seg = work / "seg_00.mkv"
    run("-loop", 1, "-framerate", FPS, "-t", t["duration"], "-i", ground,
        "-framerate", FPS, "-i", work / "t_%05d.png", "-i", work / "title.wav",
        "-filter_complex", "[0:v][1:v]overlay=0:0:eof_action=pass,format=yuv420p[v]",
        "-map", "[v]", "-map", "2:a", "-t", t["duration"],
        "-c:v", "libx264", "-preset", "veryfast", "-crf", 12, "-c:a", "pcm_s16le", seg)
    segs.append(seg)

    # 2. each clip whole, scaled onto the ground
    for i, c in enumerate(cfg["clips"], 1):
        src = Path(src_dir) / c["file"]
        sw, sh = map(int, probe(src, "stream=width,height").split(","))
        ch = c.get("height", sh)
        w = int(round(sw * BOX_H / ch / 2)) * 2
        h = BOX_H
        x, y = (W - w) // 2, (H - h) // 2
        dur = float(probe(src, "format=duration") or subprocess.run(
            ["ffprobe", "-v", "error", "-show_entries", "format=duration", "-of", "csv=p=0", str(src)],
            capture_output=True, text=True).stdout)
        mask(w, h, work / f"mask_{i}.png")
        pad = shadow(w, h, work / f"shadow_{i}.png")
        voice = work / f"voice_{i}.wav"
        fx.loudnorm_voice(str(src), str(voice))
        fo = max(0, dur - FADE)
        seg = work / f"seg_{i:02d}.mkv"
        run("-loop", 1, "-framerate", FPS, "-t", dur, "-i", ground,
            "-i", src,
            "-loop", 1, "-framerate", FPS, "-t", dur, "-i", work / f"shadow_{i}.png",
            "-loop", 1, "-framerate", FPS, "-t", dur, "-i", work / f"mask_{i}.png",
            "-i", voice,
            "-filter_complex",
            f"[1:v]fps={FPS},crop={sw}:{ch}:0:0,scale={w}:{h}:flags=lanczos,format=rgba[c0];"
            f"[3:v]format=gray[m];[c0][m]alphamerge,"
            f"fade=t=in:st=0:d={FADE}:alpha=1,fade=t=out:st={fo}:d={FADE}:alpha=1[c];"
            f"[2:v]format=rgba,fade=t=in:st=0:d={FADE}:alpha=1,fade=t=out:st={fo}:d={FADE}:alpha=1[s];"
            f"[0:v][s]overlay={x - pad}:{y - pad}[b];[b][c]overlay={x}:{y},format=yuv420p[v];"
            f"[4:a]afade=t=in:d=0.03,afade=t=out:st={max(0, dur - .06)}:d=0.06[a]",
            "-map", "[v]", "-map", "[a]", "-t", dur,
            "-c:v", "libx264", "-preset", "veryfast", "-crf", 12, "-c:a", "pcm_s16le", seg)
        segs.append(seg)
        print(f"{c['file']}: {sw}x{sh} → {w}x{h} (kept {ch} rows), {dur:.2f}s")

    # 3. join, then one final encode at the house settings
    lst = work / "list.txt"
    lst.write_text("".join(f"file '{s}'\n" for s in segs))
    joined = work / "joined.mkv"
    run("-f", "concat", "-safe", 0, "-i", lst, "-c", "copy", joined)
    run("-i", joined, "-af", "alimiter=limit=0.84:level=false",
        "-c:v", "libx264", "-preset", "slow", "-crf", 22, "-pix_fmt", "yuv420p", "-profile:v", "high", "-r", FPS,
        "-c:a", "aac", "-b:a", "128k", "-ar", 48000, "-movflags", "+faststart", out)
    print(out)


if __name__ == "__main__":
    main(*sys.argv[1:5])
