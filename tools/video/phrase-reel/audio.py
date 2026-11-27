#!/usr/bin/env python3
"""
The soundtrack of one phrase reel: a quiet lo-fi bed, her voice on each
card, and small effects on the motion.

    python3 tools/video/phrase-reel/audio.py PLAN.json VOICE_DIR OUT.wav

PLAN.json is render.js's «plan» output; VOICE_DIR is split.py's, or «-» for
no voice (a preview of the bed and the effects alone).

Matched to the two approved reels (docs/video-style/phrase-reel/), measured
off their MP4s: the bed is about 80 BPM near -25 LUFS on its own, her
voice about -10 LUFS, so the bed is felt rather than heard and only dips
4 dB under her. Since 27 November 2026 her voice is about 3 dB louder (split.py, -9 LUFS)
and the bed and the effects sit UNDER = 4 dB below what was measured, with
the bed dipping 6 dB under her: the owner asked for the voice louder. The
effects are still loud, deliberately: in the approved reels
the blips peak near -2 dBFS and the recap and website chords sit near the
voice's level, unlike the quiet effects of the course videos (§6). The
peaks below (dBFS) are those measurements. Each card is a soft rising blip pair as the French
lands, a short ascending shimmer when its key word is highlighted, and a
whoosh as it leaves. The recap rings one rising note per row and a chord
under «ذخیره‌اش کن»; the website card gets the blips and the chord again.
The bed starts drumless and the beat comes in with the first card.

Everything is synthesised here: no stock library is reachable from the
session, and synthesis leaves no licence question on an Instagram upload.
The originals' code was lost, so this is a re-creation of their sound, not
a copy of their samples.
"""
import json
import sys
import wave
from pathlib import Path

import numpy as np

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
import sfx as fx  # noqa: E402  (the course-video effects: whoosh, onepole_lp)

SR = 48000
BPM = 80
BEAT = 60 / BPM
rng = np.random.default_rng(5)
midi = lambda m: 440 * 2 ** ((m - 69) / 12)  # noqa: E731

# Fmaj7 – Em7 – Dm7 – Cmaj7, one bar each, voiced around the bed's 400–600 Hz
CHORDS = [(65, 69, 72, 76), (64, 67, 71, 74), (62, 65, 69, 72), (60, 64, 67, 71)]
BASS = [41, 40, 38, 36]


def env(n, a, tau):
    t = np.arange(n) / SR
    return np.minimum(1, t / max(a, 1e-4)) * np.exp(-t / tau)


def add(buf, i, x):
    if i < 0 or i >= len(buf):
        return
    x = x[: len(buf) - i]
    buf[i:i + len(x)] += x


def tone(f, n, partials=((1, 1),)):
    t = np.arange(n) / SR
    return sum(a * np.sin(2 * np.pi * f * k * t) for k, a in partials)


def keys(notes, dur):
    """A soft electric-piano chord: slow-ish attack, long decay, gentle tremolo."""
    n = int(dur * SR)
    t = np.arange(n) / SR
    x = sum(tone(midi(m) * (1 + d), n, ((1, 1), (2, .28), (3, .06))) for m in notes for d in (-.0015, .0015))
    trem = 1 - .12 * (.5 + .5 * np.sin(2 * np.pi * 4.2 * t))
    return x * trem * np.minimum(1, t / .04) * np.exp(-t / (dur * .75))


def kick():
    n = int(.3 * SR)
    f = 45 + 75 * np.exp(-np.arange(n) / SR / .03)
    return np.sin(2 * np.pi * np.cumsum(f) / SR) * env(n, .002, .11)


def rim():
    n = int(.12 * SR)
    x = fx.onepole_lp(rng.standard_normal(n), 3200.0)
    x = x - fx.onepole_lp(x, 1200.0)
    return (x * 3 + .4 * tone(1700, n)) * env(n, .001, .018)


def hat():
    n = int(.05 * SR)
    x = rng.standard_normal(n)
    return (x - fx.onepole_lp(x, 7500.0)) * env(n, .0005, .011)


def music(total):
    n = int(total * SR)
    bed = np.zeros(n)
    K, R, H = kick(), rim(), hat()
    bar = 4 * BEAT
    for b in range(int(total / bar) + 2):
        t0 = b * bar
        ci = b % 4
        add(bed, int(t0 * SR), keys(CHORDS[ci], bar + .4) * .16)
        add(bed, int(t0 * SR), tone(midi(BASS[ci]), int(bar * SR), ((1, 1), (2, .2))) * env(int(bar * SR), .01, 1.4) * .22)
        if t0 < 3.0:          # the hook: keys only; the beat arrives with the first card
            continue
        for beat in range(4):
            tb = t0 + beat * BEAT
            if beat in (0, 2) or (beat == 3 and b % 2):
                add(bed, int((tb + (BEAT / 2 if beat == 3 else 0)) * SR), K * .9)
            if beat in (1, 3):
                add(bed, int(tb * SR), R * .35)
            for k, sw in ((0, 0), (1, .58)):  # swung eighths
                add(bed, int((tb + sw * BEAT) * SR), H * (.16 if k else .1))
    # vinyl-ish air, very low
    air = rng.standard_normal(n)
    bed += fx.onepole_lp(air, 2500.0) * .004
    return bed


def bell(f, dur=1.2, a=.003, dec=.3):
    n = int(dur * SR)
    return tone(f, n, ((1, 1), (2.0, .25), (3.01, .08))) * env(n, a, dur * dec)


def blip(f0, f1):
    n = int(.13 * SR)
    p = np.arange(n) / SR
    f = f1 + (f0 - f1) * np.exp(-p / .018)          # a quick rise that settles
    return np.sin(2 * np.pi * np.cumsum(f) / SR) * env(n, .002, .045)


def shimmer():
    out = np.zeros(int(1.6 * SR))
    for k, f in enumerate((1760, 2093, 2349, 2637, 3136)):
        add(out, int(k * .045 * SR), bell(f, 1.3) * (1 - k * .08))
    return out


def chord():
    out = np.zeros(int(2.7 * SR))
    for k, m in enumerate((72, 76, 79, 84)):
        add(out, int(k * .012 * SR), bell(midi(m), 2.6, dec=.5))
    return out


# Everything that is not her voice sits this many dB below the levels measured
# off the first two reels: the owner asked for her voice louder (27 Nov 2026).
UNDER = -4

ARP = [72, 74, 76, 79, 81, 84, 86, 88, 91, 93]  # C major pentatonic, rising


def effect(name):
    if name == "blip":
        return blip(1300, 1650), -2
    if name == "blip_hi":
        return blip(1600, 2000), -3
    if name == "blip2":
        x = np.zeros(int(.4 * SR))
        add(x, 0, blip(1300, 1650))
        add(x, int(.2 * SR), blip(1600, 2000))
        return x, -3.8
    if name == "shimmer":
        return shimmer(), -9.5
    if name == "whoosh":
        return fx.whoosh(), -11
    if name == "chord":
        return chord(), -5.5
    if name.startswith("arp"):
        return bell(midi(ARP[int(name[3:]) % len(ARP)]), .9), -6.7
    raise ValueError(name)


def read(path):
    with wave.open(str(path)) as w:
        x = np.frombuffer(w.readframes(w.getnframes()), np.int16).reshape(-1, w.getnchannels()).astype(np.float64) / 32768
    return x if x.shape[1] == 2 else np.repeat(x, 2, 1)


def ramp(n, windows, low, pre=.25, post=.4):
    t = np.arange(n) / SR
    g = np.ones(n)
    for a, b in windows:
        x = np.clip(np.minimum((t - (a - pre)) / pre, ((b + post) - t) / post), 0, 1)
        g = np.minimum(g, 1 - (1 - low) * (.5 - .5 * np.cos(np.pi * x)))
    return g


def limit(x, thr, block=240, release=.08):
    """A look-ahead peak limiter: only the loud moments come down, never the
    whole mix (a blip landing on her voice would otherwise turn every phrase
    down with it)."""
    peak = np.abs(x).max()
    nb = -(-len(x) // block)
    pad = np.zeros((nb * block, 2)); pad[:len(x)] = x
    bp = np.abs(pad).reshape(nb, block, 2).max(axis=(1, 2))
    need = np.minimum(1, thr / np.maximum(np.maximum(bp, np.roll(bp, -1)), 1e-9))
    g, cur, up = np.empty(nb), 1.0, np.exp(-block / SR / release)
    for i, v in enumerate(need):
        cur = v if v < cur else v + (cur - v) * up
        g[i] = cur
    gs = np.interp(np.arange(nb * block), np.arange(nb) * block + block / 2, g)[:len(x)]
    return x * gs[:, None], peak


def main(plan_path, voice_dir, out):
    plan = json.loads(Path(plan_path).read_text())
    total = plan["duration"]
    n = int(round(total * SR))
    clips = []
    if voice_dir != "-":
        meta = json.loads((Path(voice_dir) / "voice.json").read_text())["clips"]
        for v in plan["voice"]:
            clips.append((v["start"], read(Path(voice_dir) / meta[v["i"]]["file"])))

    bed = music(total + 1)[:n]
    bed *= 10 ** ((-28.4 + UNDER) / 20) / (np.sqrt(np.mean(bed ** 2)) + 1e-9)   # ≈ -25 LUFS on its own, as measured
    bed *= ramp(n, [(s, s + len(x) / SR) for s, x in clips], 10 ** (-6 / 20))
    bed *= np.clip(np.arange(n) / SR / .3, 0, 1) * np.clip((total - np.arange(n) / SR) / 1.2, 0, 1)
    mix = np.stack([bed, bed], 1)

    for s, x in clips:
        i = int(round(s * SR))
        x = x[: max(0, n - i)]
        mix[i:i + len(x)] += x

    fxbuf = np.zeros(n)
    for t, name in plan["sfx"]:
        x, db = effect(name)
        add(fxbuf, int(round(t * SR)), x / (np.abs(x).max() + 1e-9) * 10 ** ((db + UNDER) / 20))
    mix += fxbuf[:, None]

    mix, peak = limit(mix, .89)
    with wave.open(out, "wb") as w:
        w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR)
        w.writeframes((np.clip(mix, -1, 1) * 32767).astype(np.int16).tobytes())
    print(f"{out}: {total:.2f}s, {len(clips)} voice clips, peak {20 * np.log10(peak + 1e-9):.1f} dBFS")


if __name__ == "__main__":
    main(*sys.argv[1:4])
