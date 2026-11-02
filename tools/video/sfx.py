#!/usr/bin/env python3
"""
Quiet sound effects for a course video, synthesised (no stock library is
reachable from the session, and synthesis leaves no licence question), then
mixed under the owner's voice.

    python3 tools/video/sfx.py TIMELINE.json SRC.mov OUT_MIX.wav

The voice is normalised to -16 LUFS / -1.5 dBTP (two-pass loudnorm, linear).
Every effect peaks around -32 dBFS: ~16 dB under speech peaks and 18-22 dB
under its loudness, which is the owner's «not very loud» (2 November 2026).
Which event gets which sound: docs/video-editing.md §6.
"""
import json
import re
import subprocess
import sys
import wave
from pathlib import Path

import numpy as np

SR = 48000
rng = np.random.default_rng(7)


def env(n, attack, tau):
    t = np.arange(n) / SR
    return np.minimum(1, t / attack) * np.exp(-t / tau)


def onepole_lp(x, fc):
    """Time-varying one-pole low-pass; fc is an array (Hz) or a scalar."""
    fc = np.broadcast_to(fc, x.shape)
    a = np.exp(-2 * np.pi * fc / SR)
    y = np.empty_like(x)
    s = 0.0
    for i in range(len(x)):
        s = (1 - a[i]) * x[i] + a[i] * s
        y[i] = s
    return y


def sweep(f0, f1, dur):
    n = int(dur * SR)
    f = np.geomspace(f0, f1, n)
    return np.sin(2 * np.pi * np.cumsum(f) / SR), n


def pop():
    s, n = sweep(1500, 650, 0.07)
    return s * env(n, 0.002, 0.018)


def tick():
    s, n = sweep(2600, 2000, 0.04)
    return 0.7 * s * env(n, 0.001, 0.008)


def chime():
    n = int(0.9 * SR)
    t = np.arange(n) / SR
    tone = sum(a * np.sin(2 * np.pi * f * t) for f, a in ((1318.5, 1.0), (1975.5, 0.55), (2637.0, 0.25)))
    return tone * env(n, 0.004, 0.22) / 1.8


def whoosh(rise=True):
    n = int(0.48 * SR)
    p = np.linspace(0, 1, n)
    shape = np.sin(np.pi * p) ** 2
    fc = 300 + 2200 * (np.sin(np.pi * p) if rise else np.sin(np.pi * p) * 0.7)
    noise = rng.standard_normal(n)
    band = onepole_lp(noise, fc) - onepole_lp(noise, fc * 0.25)
    return band * shape / (np.abs(band).max() + 1e-9)


def swipe():
    n = int(0.33 * SR)
    p = np.linspace(0, 1, n)
    noise = rng.standard_normal(n)
    hi = noise - onepole_lp(noise, 900 + 3000 * p)
    return hi * (np.sin(np.pi * p) ** 1.5) / (np.abs(hi).max() + 1e-9)


SOUNDS = {"pop": pop, "tick": tick, "chime": chime, "whoosh": whoosh, "swipe": swipe}
# Peak level of each, in dBFS. Chimes ring longer, so they sit lower.
LEVEL = {"pop": -31, "tick": -33, "chime": -34, "whoosh": -33, "swipe": -34}
DEFAULT = {
    "name": "pop", "chip": "pop", "navy": "pop", "check": "pop", "big": "pop", "skills": "pop",
    "bubble": "pop", "path": "pop", "url": "tick", "tag": "tick", "eyebrow": "tick",
    "code": "chime", "stat": "chime", "logo": "chime",
}


def events(tl):
    ev = []
    for sp in tl.get("splits", []):
        ev += [(sp["in"], "whoosh"), (sp["out"], "whoosh")]
    for g in tl["groups"]:
        for it in g["items"]:
            name = it.get("sfx", DEFAULT.get(it["kind"]))
            if name and name != "none":
                ev.append((it["at"], name))
            if it.get("strike"):
                ev.append((it["strike"], "swipe"))
            for nd in it.get("nodes", []):
                if "done" in nd:
                    ev.append((nd["done"], "tick"))
                if "goal" in nd:
                    ev.append((nd["goal"], "pop"))
    ev.sort()
    kept, last = [], -9
    for t, s in ev:
        # Never two effects within 0.35 s; a chime or whoosh wins over a pop.
        if t - last < 0.35:
            if s in ("chime", "whoosh") and kept:
                kept[-1] = (kept[-1][0], s)
            continue
        kept.append((t, s))
        last = t
    return kept


def loudnorm_voice(src, out):
    probe = subprocess.run(
        ["ffmpeg", "-hide_banner", "-i", src, "-vn", "-af", "loudnorm=I=-16:TP=-1.5:LRA=11:print_format=json",
         "-f", "null", "-"], capture_output=True, text=True).stderr
    m = json.loads(re.search(r"\{[^{}]*\"input_i\"[^{}]*\}", probe, re.S).group(0))
    af = (f"loudnorm=I=-16:TP=-1.5:LRA=11:measured_I={m['input_i']}:measured_TP={m['input_tp']}:"
          f"measured_LRA={m['input_lra']}:measured_thresh={m['input_thresh']}:offset={m['target_offset']}:linear=true")
    subprocess.run(["ffmpeg", "-v", "error", "-y", "-i", src, "-vn", "-af", af, "-ar", str(SR), "-ac", "2", out], check=True)


def main(tl_path, src, out):
    tl = json.loads(Path(tl_path).read_text(encoding="utf-8"))
    n = int((tl["duration"] + 1) * SR)
    track = np.zeros(n)
    ev = events(tl)
    for t, name in ev:
        s = SOUNDS[name]()
        s = s / (np.abs(s).max() + 1e-9) * 10 ** (LEVEL[name] / 20)
        i = int(t * SR)
        track[i:i + len(s)] += s[: max(0, n - i)]
    voice = str(Path(out).with_suffix(".voice.wav"))
    loudnorm_voice(src, voice)
    with wave.open(voice) as w:
        v = np.frombuffer(w.readframes(w.getnframes()), np.int16).reshape(-1, 2).astype(np.float64) / 32768
    m = min(len(v), n)
    mix = v[:m] + track[:m, None]
    with wave.open(out, "wb") as w:
        w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR)
        w.writeframes((np.clip(mix, -1, 1) * 32767).astype(np.int16).tobytes())
    print(f"{len(ev)} effects:", ", ".join(f"{t:.1f}s {s}" for t, s in ev))


if __name__ == "__main__":
    main(*sys.argv[1:4])
