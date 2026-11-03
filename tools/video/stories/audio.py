#!/usr/bin/env python3
"""
The soundtrack of one story: an upbeat bed of music, the owner's voice from
each clip at the moment it plays, and quiet effects on the motion.

    python3 tools/video/stories/audio.py PLAN.json CLIPS_DIR OUT.wav

PLAN.json is render.js's «plan» output. The music is synthesised here — the
stock libraries are unreachable from the session, and a synthesised track
leaves no licence question for an Instagram upload. It is a four-bar loop at
120 BPM (C – G – Am – F): four-on-the-floor kick, claps on 2 and 4, offbeat
hats, a pumping bass, syncopated chord plucks and a bell melody.

While she speaks, the bed drops 17 dB and the melody (which sits in the
voice's range) drops out, so the music is felt rather than heard under her.
Her voice is at -16 LUFS (prep.py), and is never the thing that gets quieter.
"""
import json
import sys
import wave
from pathlib import Path

import numpy as np

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))
import sfx as fx  # noqa: E402

SR = 48000
BPM = 120
BEAT = 60 / BPM
rng = np.random.default_rng(11)

midi = lambda m: 440 * 2 ** ((m - 69) / 12)  # noqa: E731
CHORDS = [(60, 64, 67), (59, 62, 67), (57, 60, 64), (57, 60, 65)]  # C G Am F, close voicing
BASS = [36, 43, 45, 41]
# bell melody: (bar, beat, midi, length in beats)
MELODY = [(0, 0, 76, 1), (0, 1, 79, 1), (0, 2, 81, .5), (0, 2.5, 79, 1.5),
          (1, 0, 74, 1), (1, 1.5, 71, .5), (1, 2, 74, 2),
          (2, 0, 72, .5), (2, .5, 76, .5), (2, 1, 81, 1), (2, 2, 79, 2),
          (3, 0, 77, 1), (3, 1, 76, 1), (3, 2, 72, 2)]


def env(n, a, tau):
    t = np.arange(n) / SR
    return np.minimum(1, t / max(a, 1e-4)) * np.exp(-t / tau)


def add(buf, i, x):
    if i >= len(buf):
        return
    x = x[: len(buf) - i]
    buf[i:i + len(x)] += x


def lp(x, fc):
    return fx.onepole_lp(x, float(fc))


def hp(x, fc):
    return x - lp(x, fc)


def saw(f, n, harm=12):
    t = np.arange(n) / SR
    return sum(np.sin(2 * np.pi * f * k * t) / k for k in range(1, harm + 1) if f * k < 9000)


def kick():
    n = int(.32 * SR)
    f = 48 + 110 * np.exp(-np.arange(n) / SR / .035)
    return np.sin(2 * np.pi * np.cumsum(f) / SR) * env(n, .001, .12)


def clap():
    n = int(.22 * SR)
    x = rng.standard_normal(n)
    x = hp(lp(x, 2600), 900)
    e = sum(env(n, .001, .012) * (np.arange(n) >= int(d * SR)) for d in (0, .011, .022)) + env(n, .001, .07) * .6
    return x * e


def hat(open_=False):
    n = int((.12 if open_ else .05) * SR)
    return hp(rng.standard_normal(n), 7000) * env(n, .0005, .04 if open_ else .012)


def pluck(notes, dur=.32):
    n = int(dur * 1.6 * SR)
    x = sum(saw(midi(m) * (1 + d), n, 8) for m in notes for d in (-.003, .003))
    return lp(x, 2400) * env(n, .003, dur * .45)


def bass(m, dur):
    n = int(dur * SR)
    f = midi(m)
    t = np.arange(n) / SR
    x = np.sin(2 * np.pi * f * t) + .35 * np.sin(4 * np.pi * f * t) + .12 * saw(f, n, 6)
    return x * env(n, .004, dur * .7)


def bell(m, dur):
    n = int((dur + .6) * SR)
    f = midi(m)
    t = np.arange(n) / SR
    x = np.sin(2 * np.pi * f * t) + .3 * np.sin(2 * np.pi * 2 * f * t) + .12 * np.sin(2 * np.pi * 3.01 * f * t)
    return x * env(n, .004, .35)


def music(total):
    n = int(total * SR)
    drums, inst, lead = np.zeros(n), np.zeros(n), np.zeros(n)
    bars = int(total / (4 * BEAT)) + 2
    K, C, Hc, Ho = kick(), clap(), hat(), hat(True)
    P = [pluck(c) for c in CHORDS]  # filtered once, reused every bar
    pump = np.ones(n)
    for b in range(bars):
        t0 = b * 4 * BEAT
        ci = b % 4
        intro = b < 1  # first bar: no kick, so the story opens on the title
        for beat in range(4):
            tb = t0 + beat * BEAT
            if not intro:
                add(drums, int(tb * SR), K)
                i = int(tb * SR)
                m = min(n - i, int(.25 * SR))
                if m > 0:
                    pump[i:i + m] = np.minimum(pump[i:i + m], 1 - .55 * np.exp(-np.arange(m) / SR / .07))
            if beat in (1, 3):
                add(drums, int(tb * SR), C * .55)
            add(drums, int((tb + BEAT / 2) * SR), (Ho if beat == 3 else Hc) * .35)
            add(drums, int((tb + BEAT / 4) * SR), Hc * .12)
            add(drums, int((tb + 3 * BEAT / 4) * SR), Hc * .12)
        for k, pos in enumerate((0, .75, 1.5, 2, 2.75, 3.5)):
            add(inst, int((t0 + pos * BEAT) * SR), P[ci] * (.22 if k in (0, 3) else .16))
        for k in range(8):
            if k % 2 == 1 or k == 0:
                add(inst, int((t0 + k * BEAT / 2) * SR), bass(BASS[ci], BEAT / 2) * (.5 if k == 0 else .42))
        for bar, beat, m, ln in MELODY:
            if bar == ci:
                add(lead, int((t0 + beat * BEAT) * SR), bell(m, ln * BEAT) * .18)
    inst *= pump
    # a little room: one quiet 3/8-beat echo on the lead and plucks
    d = int(.375 * BEAT * SR)
    for x in (lead, inst):
        x[d:] += .22 * x[:-d]
    return drums + inst, lead


def sfx_track(events, n):
    out = np.zeros(n)
    snip = lambda: np.concatenate([fx.tick(), np.zeros(int(.03 * SR)), fx.tick()]) + 0  # noqa: E731
    sounds = {"pop": fx.pop, "tick": fx.tick, "chime": fx.chime, "whoosh": fx.whoosh, "swipe": fx.swipe, "snip": snip}
    level = {"pop": -22, "tick": -27, "chime": -24, "whoosh": -25, "swipe": -26, "snip": -23}
    last = -9
    for t, name in events:
        if name in ("pop", "tick") and t - last < .1:
            continue
        s = sounds[name]()
        s = s / (np.abs(s).max() + 1e-9) * 10 ** (level[name] / 20)
        add(out, int(t * SR), s)
        last = t
    return out


def read(path):
    with wave.open(str(path)) as w:
        return np.frombuffer(w.readframes(w.getnframes()), np.int16).reshape(-1, 2).astype(np.float64) / 32768


def ramp_gain(n, windows, low, pre=.35, post=.35):
    """1.0 outside the windows, `low` inside, with smooth ramps either side."""
    t = np.arange(n) / SR
    g = np.ones(n)
    for a, b in windows:
        x = np.clip(np.minimum((t - (a - pre)) / pre, ((b + post) - t) / post), 0, 1)
        x = .5 - .5 * np.cos(np.pi * x)
        g = np.minimum(g, 1 - (1 - low) * x)
    return g


def main(plan_path, clips_dir, out):
    plan = json.loads(Path(plan_path).read_text())
    total = plan["duration"]
    n = int(round(total * SR))
    bed, lead = music(total + 1)
    bed, lead = bed[:n], lead[:n]
    # level the music: bed RMS about -19 dBFS at full
    r = np.sqrt(np.mean((bed + lead) ** 2)) + 1e-9
    bed, lead = bed / r * 10 ** (-19 / 20), lead / r * 10 ** (-19 / 20)
    win = [(c["start"], c["end"]) for c in plan["clips"]]
    m = bed * ramp_gain(n, win, 10 ** (-17 / 20)) + lead * ramp_gain(n, win, 10 ** (-40 / 20))
    fade = np.clip((total - np.arange(n) / SR) / .8, 0, 1)
    m *= fade
    mix = np.stack([m, m], 1)
    mix[:, 0] *= 1.0
    for c in plan["clips"]:
        v = read(Path(clips_dir) / c["id"] / "voice.wav")
        i = int(round(c["start"] * SR))
        if i < 0:
            continue
        v = v[: max(0, n - i)]
        mix[i:i + len(v)] += v
    s = sfx_track(plan["sfx"], n)
    mix += s[:, None]
    peak = np.abs(mix).max()
    if peak > .89:  # never clip; the encode's limiter does the fine work
        mix *= .89 / peak
    with wave.open(out, "wb") as w:
        w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR)
        w.writeframes((np.clip(mix, -1, 1) * 32767).astype(np.int16).tobytes())
    print(f"{out}: {total:.2f}s, peak {20 * np.log10(peak + 1e-9):.1f} dBFS")


if __name__ == "__main__":
    main(*sys.argv[1:4])
