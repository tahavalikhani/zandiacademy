#!/usr/bin/env bash
# Build one phrase reel end to end.
#
#   tools/video/phrase-reel/make.sh REEL.json VOICE_DIR WORK_DIR OUT.mp4 [WORKERS]
#
# VOICE_DIR comes from split.py («-» renders without a voice). The text is
# checked first, frames render in WORKERS parallel ranges (default 4), then
# the soundtrack, then one H.264 + AAC encode. docs/video-editing.md §10.
set -euo pipefail
reel=$1 voice=$2 work=$3 out=$4 jobs=${5:-4}
here=$(cd "$(dirname "$0")" && pwd)
python3 "$here/check.py" "$reel"
mkdir -p "$work"
rm -f "$work"/f_*.jpg
node "$here/render.js" "$reel" "$voice" "$work" plan
n=$(python3 -c "import json,sys;p=json.load(open(sys.argv[1]));print(round(p['duration']*p['fps']))" "$work/plan.json")
step=$(( (n + jobs - 1) / jobs ))
pids=()
for ((i = 0; i < jobs; i++)); do
  node "$here/render.js" "$reel" "$voice" "$work" frames $((i * step)) $(((i + 1) * step)) & pids+=($!)
done
for p in "${pids[@]}"; do wait "$p"; done
python3 "$here/audio.py" "$work/plan.json" "$voice" "$work/mix.wav"
ffmpeg -v error -y -framerate 30 -i "$work/f_%05d.jpg" -i "$work/mix.wav" \
  -c:v libx264 -preset slow -crf 20 -pix_fmt yuv420p -profile:v high -r 30 \
  -af "alimiter=limit=0.89:level=false" -c:a aac -b:a 192k -ar 48000 \
  -shortest -movflags +faststart "$out"
echo "$out"
