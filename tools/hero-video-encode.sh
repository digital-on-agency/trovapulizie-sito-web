#!/usr/bin/env bash
# Codifica il timelapse di sfondo della hero per il web.
# Uso: tools/hero-video-encode.sh <sorgente.mp4> [durata_s=3] [larghezza=1600]
# - accelera la clip sorgente alla durata voluta (timelapse), scarta l'audio
# - ridimensiona alla larghezza data mantenendo 16:9 (la .hero__box e' 1360x759 max, object-fit: cover)
# - h264 crf 26, faststart: ~1 MB per 3 s a 1600px; l'ultimo frame resta a schermo (nessun loop)
set -euo pipefail
SRC="${1:?sorgente mp4}"; DUR="${2:-3}"; W="${3:-1600}"
OUT="$(dirname "$0")/../assets/video/hero-timelapse.mp4"
LEN=$(ffprobe -v error -show_entries format=duration -of csv=p=0 "$SRC")
FACTOR=$(python3 -c "print($DUR/$LEN)")
ffmpeg -y -loglevel error -i "$SRC" -an -vf "setpts=${FACTOR}*PTS,scale=${W}:-2:flags=lanczos,fps=30" \
  -c:v libx264 -preset slow -crf 26 -pix_fmt yuv420p -movflags +faststart "$OUT"
ffprobe -v error -show_entries format=duration:stream=width,height,nb_frames -of default=nw=1 "$OUT"
ls -la "$OUT"
