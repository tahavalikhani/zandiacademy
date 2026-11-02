#!/usr/bin/env python3
"""
Composite a course video: camera punch-ins, the split-screen card, the
overlay PNGs from render-overlay.js, and the mixed audio.

    # review a few moments as stills first
    python3 tools/video/compose.py TIMELINE.json SRC.mov OVERLAYDIR OUT --stills 2.5,14.6,40
    # then the whole thing (OUT is the .mp4; AUDIO is from sfx.py)
    python3 tools/video/compose.py TIMELINE.json SRC.mov OVERLAYDIR OUT.mp4 --audio MIX.wav

Encodes H.264 + AAC with +faststart, per docs/video-editing.md §2.
"""
import argparse
import json
import subprocess
from pathlib import Path

import numpy as np
from PIL import Image, ImageDraw, ImageFilter

# Gentle shadow lift: the owner sits against a bright window and reads dark.
# Mild on purpose — the sky must not wash out.
GRADE = "curves=all='0/0 0.18/0.215 0.5/0.535 0.85/0.86 1/1',eq=saturation=1.04"

CARD = (700, 70, 520, 580)  # x, y, w, h of the split-screen video card at 1280×720
CARD_RADIUS = 28
TRANS = 0.5                 # seconds for the frame to fly into / out of the card


def ease(p):
    p = min(1.0, max(0.0, p))
    return 4 * p ** 3 if p < 0.5 else 1 - (-2 * p + 2) ** 3 / 2


def lerp(a, b, s):
    return tuple(x + (y - x) * s for x, y in zip(a, b))


class Camera:
    def __init__(self, tl):
        self.W, self.H = tl["size"]
        c = tl["camera"]
        self.cuts = [0.0] + c["cuts"] + [tl["duration"] + 1]
        self.zooms, self.push, self.center = c["zooms"], c["push"], c["center"]
        self.splits = tl.get("splits", [])
        cw, ch = CARD[2], CARD[3]
        sh = 600.0
        sw = sh * cw / ch
        self.card_src = (self.center[0] - sw / 2, 360 - sh / 2, sw, sh)

    def full_src(self, t):
        i = max(k for k in range(len(self.cuts) - 1) if self.cuts[k] <= t)
        s, e = self.cuts[i], self.cuts[i + 1]
        z = self.zooms[i] * (1 + self.push * (t - s) / (e - s))
        w, h = self.W / z, self.H / z
        x = min(max(self.center[0] - w / 2, 0), self.W - w)
        y = min(max(self.center[1] - h / 2, 0), self.H - h)
        return (x, y, w, h)

    def split(self, t):
        for sp in self.splits:
            if sp["in"] <= t < sp["out"] + TRANS:
                return ease((t - sp["in"]) / TRANS) * (1 - ease((t - sp["out"]) / TRANS))
        return 0.0

    def state(self, t):
        s = self.split(t)
        src = lerp(self.full_src(t), self.card_src, s)
        dst = lerp((0, 0, self.W, self.H), CARD, s)
        return s, src, dst


def rounded_mask(w, h, r):
    m = Image.new("L", (w, h), 0)
    ImageDraw.Draw(m).rounded_rectangle((0, 0, w - 1, h - 1), radius=r, fill=255)
    return m


def compose(frame, t, cam, under, overlay):
    s, (sx, sy, sw, sh), (dx, dy, dw, dh) = cam.state(t)
    src = Image.fromarray(frame)
    if s <= 0.0005:
        out = src.resize((cam.W, cam.H), Image.BICUBIC, box=(sx, sy, sx + sw, sy + sh)).convert("RGBA")
    else:
        out = under.copy()
        w, h = max(2, round(dw)), max(2, round(dh))
        x, y = round(dx), round(dy)
        r = round(CARD_RADIUS * s)
        content = src.resize((w, h), Image.BICUBIC, box=(sx, sy, sx + sw, sy + sh))
        # Soft navy shadow under the card.
        sm = Image.new("L", (cam.W // 4, cam.H // 4), 0)
        ImageDraw.Draw(sm).rounded_rectangle(
            (x // 4, (y + 14) // 4, (x + w) // 4, (y + h + 14) // 4), radius=max(1, r // 4), fill=int(120 * s))
        sm = sm.filter(ImageFilter.GaussianBlur(6)).resize((cam.W, cam.H), Image.BILINEAR)
        out.paste(Image.new("RGBA", (cam.W, cam.H), (8, 18, 38, 255)), (0, 0), sm)
        out.paste(content, (x, y), rounded_mask(w, h, r) if r > 0 else None)
        if s > 0.02:
            bd = Image.new("RGBA", (cam.W, cam.H), (0, 0, 0, 0))
            ImageDraw.Draw(bd).rounded_rectangle(
                (x - 1, y - 1, x + w, y + h), radius=r + 1, outline=(255, 255, 255, int(255 * s)), width=3)
            out.alpha_composite(bd)
    if overlay is not None:
        out.alpha_composite(overlay)
    return out.convert("RGB")


def frames(src, W, H, fps):
    p = subprocess.Popen(
        ["ffmpeg", "-v", "error", "-i", src, "-vf", f"fps={fps},scale={W}:{H},{GRADE}",
         "-f", "rawvideo", "-pix_fmt", "rgb24", "-"], stdout=subprocess.PIPE)
    n = W * H * 3
    while True:
        b = p.stdout.read(n)
        if len(b) < n:
            break
        yield np.frombuffer(b, np.uint8).reshape(H, W, 3)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("timeline"); ap.add_argument("src"); ap.add_argument("overlays"); ap.add_argument("out")
    ap.add_argument("--stills"); ap.add_argument("--audio")
    a = ap.parse_args()
    tl = json.loads(Path(a.timeline).read_text(encoding="utf-8"))
    cam, fps = Camera(tl), tl["fps"]
    od = Path(a.overlays)
    under = Image.open(od / "under.png").convert("RGBA")

    if a.stills:
        Path(a.out).mkdir(parents=True, exist_ok=True)
        for t in [float(x) for x in a.stills.split(",")]:
            fr = subprocess.run(
                ["ffmpeg", "-v", "error", "-ss", str(t), "-i", a.src, "-frames:v", "1", "-vf",
                 f"scale={cam.W}:{cam.H},{GRADE}", "-f", "rawvideo", "-pix_fmt", "rgb24", "-"],
                stdout=subprocess.PIPE, check=True).stdout
            frame = np.frombuffer(fr, np.uint8).reshape(cam.H, cam.W, 3)
            ov = od / f"still_{t:g}.png"
            img = compose(frame, t, cam, under, Image.open(ov).convert("RGBA") if ov.exists() else None)
            img.save(Path(a.out) / f"c_{t:g}.png")
        return

    manifest = json.loads((od / "manifest.json").read_text())
    enc = subprocess.Popen(
        ["ffmpeg", "-v", "error", "-y", "-f", "rawvideo", "-pix_fmt", "rgb24", "-s", f"{cam.W}x{cam.H}",
         "-r", str(fps), "-i", "-", "-i", a.audio, "-map", "0:v", "-map", "1:a",
         "-c:v", "libx264", "-preset", "slow", "-crf", "22", "-pix_fmt", "yuv420p", "-profile:v", "high",
         "-c:a", "aac", "-b:a", "128k", "-shortest", "-movflags", "+faststart", a.out], stdin=subprocess.PIPE)
    cache = {}
    for i, fr in enumerate(frames(a.src, cam.W, cam.H, fps)):
        if i >= len(manifest):
            break
        name = manifest[i]
        ov = None
        if name:
            if name not in cache:
                cache.clear()
                cache[name] = Image.open(od / name).convert("RGBA")
            ov = cache[name]
        enc.stdin.write(compose(fr, i / fps, cam, under, ov).tobytes())
        if i % 240 == 0:
            print(f"frame {i}/{len(manifest)}", flush=True)
    enc.stdin.close()
    enc.wait()


if __name__ == "__main__":
    main()
