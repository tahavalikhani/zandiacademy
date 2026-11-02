#!/usr/bin/env python3
"""
Draft Persian transcript of a course video, with timestamps.

NOT part of the theme and never run by WordPress. A tool for the video edit
described in docs/video-editing.md. Its output is a DRAFT: Whisper mishears
Persian (it wrote «زبط» for «ضبط» and «سفتنا» for «ثبت‌نام» on the A1 intro),
so every line is corrected by hand and then confirmed by the owner before a
single caption is drawn. See docs/video-editing.md §3.

Setup (once per session; both files come from GitHub, which the session's
proxy allows — huggingface.co is blocked):

    pip install sherpa-onnx soundfile
    M=$SCRATCH/models; mkdir -p $M && cd $M
    curl -sL https://github.com/k2-fsa/sherpa-onnx/releases/download/asr-models/sherpa-onnx-whisper-turbo.tar.bz2 | tar xj

Run:

    ffmpeg -i input.mov -ac 1 -ar 16000 audio.wav
    python3 tools/video/transcribe.py $M audio.wav > transcript.txt

Segments are kept short (max 7 s) on purpose: Whisper silently DROPS words
from a long segment, which is how a 27-second chunk of the A1 intro lost most
of a sentence on the first try. A chunk boundary can still split a word:
so it runs two passes offset by half a chunk; read both together.
"""
import sys

import numpy as np
import sherpa_onnx
import soundfile as sf

MODELS, WAV = sys.argv[1], sys.argv[2]
W = f"{MODELS}/sherpa-onnx-whisper-turbo"

rec = sherpa_onnx.OfflineRecognizer.from_whisper(
    encoder=f"{W}/turbo-encoder.int8.onnx",
    decoder=f"{W}/turbo-decoder.int8.onnx",
    tokens=f"{W}/turbo-tokens.txt",
    language="fa",
    task="transcribe",
    num_threads=4,
)

audio, sr = sf.read(WAV, dtype="float32")
if sr != 16000 or audio.ndim != 1:
    sys.exit("Expected 16 kHz mono: ffmpeg -i in.mov -ac 1 -ar 16000 audio.wav")

# Chunk at the quietest moments, never longer than MAX seconds. Silero VAD
# was tried first and does not split a talker who never pauses: it returned
# 17-second segments and Whisper dropped half of each.
MAX, MIN, HOP = 7.0, 2.0, 800  # seconds, seconds, samples (50 ms)
env = np.array([np.sqrt(np.mean(audio[i : i + HOP] ** 2)) for i in range(0, len(audio), HOP)])
cuts, pos = [0], 0
while (len(audio) - pos) / 16000 > MAX:
    lo = pos + int(MIN * 16000) // HOP * HOP
    hi = pos + int(MAX * 16000) // HOP * HOP
    win = env[lo // HOP : hi // HOP]
    pos = lo + int(np.argmin(win)) * HOP
    cuts.append(pos)
cuts.append(len(audio))

pad = np.zeros(3200, dtype=np.float32)  # 0.2 s lead-in
tail = np.zeros(16000, dtype=np.float32)  # 1 s: without it the last words of every chunk vanish


def decode(a, b):
    chunk = audio[a:b]
    if np.sqrt(np.mean(chunk**2)) < 0.003:
        return None  # silence
    s = rec.create_stream()
    s.accept_waveform(16000, np.concatenate([pad, chunk, tail]))
    rec.decode_stream(s)
    return s.result.text.strip()


# Pass A: the chunks. Pass B: the same audio cut at the MIDDLE of each chunk,
# so every word that pass A split across a boundary is heard whole in pass B.
# Even so Whisper drops words at chunk edges; read both passes together.
print("# Pass A")
for a, b in zip(cuts, cuts[1:]):
    t = decode(a, b)
    if t is not None:
        print(f"[{a/16000:7.2f} - {b/16000:7.2f}]  {t}", flush=True)

mids = [0] + [(a + b) // 2 for a, b in zip(cuts, cuts[1:])] + [len(audio)]
print("\n# Pass B (offset by half a chunk)")
for a, b in zip(mids, mids[1:]):
    if b - a < 8000:
        continue
    t = decode(a, b)
    if t is not None:
        print(f"[{a/16000:7.2f} - {b/16000:7.2f}]  {t}", flush=True)
