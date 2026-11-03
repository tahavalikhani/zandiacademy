#!/usr/bin/env bash
# Build one Instagram story end to end.
#
#   tools/video/stories/make.sh STORY CLIPS_DIR WORK_DIR OUT.mp4 [WORKERS]
#
# CLIPS_DIR comes from prep.py. Frames render in WORKERS parallel ranges
# (default 4), the soundtrack from audio.py, then one H.264 + AAC encode.
set -euo pipefail
story=$1 clips=$2 work=$3 out=$4 jobs=${5:-4}
here=$(cd "$(dirname "$0")" && pwd)
mkdir -p "$work"
node "$here/render.js" "$story" "$clips" "$work" plan
n=$(python3 -c "import json,sys;p=json.load(open(sys.argv[1]));print(round(p['duration']*p['fps']))" "$work/plan.json")
step=$(( (n + jobs - 1) / jobs ))
pids=()
for ((i = 0; i < jobs; i++)); do
  node "$here/render.js" "$story" "$clips" "$work" frames $((i * step)) $(((i + 1) * step)) & pids+=($!)
done
for p in "${pids[@]}"; do wait "$p"; done
python3 "$here/audio.py" "$work/plan.json" "$clips" "$work/mix.wav"
ffmpeg -v error -y -framerate 30 -i "$work/f_%05d.jpg" -i "$work/mix.wav" \
  -c:v libx264 -preset slow -crf 19 -pix_fmt yuv420p -profile:v high -r 30 \
  -af "alimiter=limit=0.89:level=false" -c:a aac -b:a 192k -ar 48000 \
  -shortest -movflags +faststart "$out"
echo "$out"
