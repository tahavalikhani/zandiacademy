#!/usr/bin/env python3
"""
Cut the owner's recording into one voice clip per phrase.

    python3 tools/video/phrase-reel/split.py REEL.json RECORDING OUT_DIR [--cuts 3.1,7.9,...]

RECORDING is either one audio/video file with every phrase read in order,
pauses between, or a folder holding one file per phrase (sorted by name).
For one file, the pauses are found with ffmpeg's silencedetect; the
threshold is walked until exactly one segment per phrase comes out. If no
threshold gives the right count, it prints what it found and stops: then
listen, and pass the start of every phrase with --cuts (seconds).

The whole recording is loudness-normalised ONCE (two-pass, linear, so her
own rise and fall between phrases is kept) and then cut, with a little air
either side and 15 ms fades. OUT_DIR gets pNN.wav (48 kHz stereo) and
voice.json, which render.js and audio.py read. Nothing is re-ordered,
sped up or trimmed inside a phrase.
"""
import json
import re
import subprocess
import sys
from pathlib import Path

SR = 48000
TARGET = -11.5    # LUFS of her speech; the approved reels sit near -10 under the bed
PRE, POST = 0.06, 0.14


def run(cmd):
    return subprocess.run(cmd, capture_output=True, text=True)


def duration(path):
    return float(run(["ffprobe", "-v", "error", "-show_entries", "format=duration", "-of", "csv=p=0", str(path)]).stdout)


def normalise(src, out):
    probe = run(["ffmpeg", "-hide_banner", "-i", str(src), "-vn", "-af",
                 f"highpass=f=70,loudnorm=I={TARGET}:TP=-1:LRA=11:print_format=json", "-f", "null", "-"]).stderr
    m = json.loads(re.search(r"\{[^{}]*\"input_i\"[^{}]*\}", probe, re.S).group(0))
    af = (f"highpass=f=70,loudnorm=I={TARGET}:TP=-1:LRA=11:measured_I={m['input_i']}:measured_TP={m['input_tp']}:"
          f"measured_LRA={m['input_lra']}:measured_thresh={m['input_thresh']}:offset={m['target_offset']}:linear=true")
    subprocess.run(["ffmpeg", "-v", "error", "-y", "-i", str(src), "-vn", "-af", af, "-ar", str(SR), "-ac", "2", str(out)], check=True)


def speech(path, noise, gap):
    err = run(["ffmpeg", "-hide_banner", "-i", str(path), "-af", f"silencedetect=n={noise}dB:d={gap}", "-f", "null", "-"]).stderr
    total = duration(path)
    starts = [float(x) for x in re.findall(r"silence_start: ([\d.]+)", err)]
    ends = [float(x) for x in re.findall(r"silence_end: ([\d.]+)", err)]
    segs, cur = [], 0.0
    for s, e in zip(starts, ends + [total] * (len(starts) - len(ends))):
        if s - cur > 0.15:
            segs.append((cur, s))
        cur = e
    if total - cur > 0.15:
        segs.append((cur, total))
    return [(a, b) for a, b in segs if b - a > 0.25]


def cut(src, a, b, out):
    d = b - a
    subprocess.run(["ffmpeg", "-v", "error", "-y", "-ss", f"{a:.3f}", "-t", f"{d:.3f}", "-i", str(src),
                    "-af", f"afade=t=in:d=0.015,afade=t=out:st={max(0, d - 0.015):.3f}:d=0.015",
                    "-ar", str(SR), "-ac", "2", str(out)], check=True)


def main():
    args = sys.argv[1:]
    cuts = None
    if "--cuts" in args:
        i = args.index("--cuts")
        cuts = [float(x) for x in args[i + 1].split(",")]
        del args[i:i + 2]
    reel, rec, out = Path(args[0]), Path(args[1]), Path(args[2])
    n = len(json.loads(reel.read_text(encoding="utf-8"))["phrases"])
    out.mkdir(parents=True, exist_ok=True)
    clips = []

    if rec.is_dir():
        files = sorted(p for p in rec.iterdir() if p.is_file() and not p.name.startswith("."))
        if len(files) != n:
            sys.exit(f"{len(files)} files in {rec} for {n} phrases")
        for i, f in enumerate(files):
            norm = out / f"_norm{i:02d}.wav"
            normalise(f, norm)
            segs = speech(norm, -40, 0.25)
            a, b = (segs[0][0], segs[-1][1]) if segs else (0, duration(norm))
            a, b = max(0, a - PRE), min(duration(norm), b + POST)
            cut(norm, a, b, out / f"p{i + 1:02d}.wav")
            norm.unlink()
            clips.append({"file": f"p{i + 1:02d}.wav", "dur": round(b - a, 3), "source": f.name, "from": round(a, 3), "to": round(b, 3)})
    else:
        norm = out / "_norm.wav"
        normalise(rec, norm)
        total = duration(norm)
        if cuts:
            if len(cuts) != n:
                sys.exit(f"--cuts has {len(cuts)} starts for {n} phrases")
            bounds = cuts + [total]
            segs = []
            for a, nxt in zip(bounds, bounds[1:]):
                inner = [s for s in speech(norm, -40, 0.25) if a - 0.05 <= s[0] < nxt]
                segs.append((inner[0][0], inner[-1][1]) if inner else (a, nxt))
        else:
            segs = None
            for gap in (0.6, 0.5, 0.45, 0.4, 0.35, 0.3, 0.7, 0.8, 1.0):
                for noise in (-40, -38, -36, -34, -42, -45, -32, -30):
                    s = speech(norm, noise, gap)
                    if len(s) == n:
                        segs = s
                        break
                if segs:
                    break
            if not segs:
                print(f"Could not find exactly {n} phrases. At -40 dB / 0.4 s it hears:")
                for a, b in speech(norm, -40, 0.4):
                    print(f"  {a:7.2f} - {b:7.2f}  ({b - a:.2f} s)")
                sys.exit("Listen, then pass the start of every phrase: --cuts 0.4,3.9,...")
        for i, (a, b) in enumerate(segs):
            a, b = max(0, a - PRE), min(total, b + POST)
            cut(norm, a, b, out / f"p{i + 1:02d}.wav")
            clips.append({"file": f"p{i + 1:02d}.wav", "dur": round(b - a, 3), "from": round(a, 3), "to": round(b, 3)})
        norm.unlink()

    (out / "voice.json").write_text(json.dumps({"recording": rec.name, "lufs": TARGET, "clips": clips}, ensure_ascii=False, indent=1))
    for i, c in enumerate(clips):
        print(f"phrase {i + 1}: {c['from']:7.2f} - {c['to']:7.2f}  ({c['dur']:.2f} s)")


if __name__ == "__main__":
    main()
