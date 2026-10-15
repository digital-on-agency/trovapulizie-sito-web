#!/usr/bin/env bash
# Codifica il video dello zoom-through (schermo telefono -> tutto schermo -> card Servizi) per il web.
# Uso: tools/zoom-video-encode.sh <sorgente.mp4> [larghezza=2560] [crf=19]
# - nessun audio, h264 profilo high, crf basso (il file va a tutto schermo: a crf 24 si vedeva la grana)
# - primo frame esatto salvato in assets/img/come-funziona-zoom-frame.jpg (foto della prima card + fade finale
#   dello storyboard dello schermo): dopo averlo rigenerato, rilanciare tools/screen-storyboard/render.js + ffmpeg
set -euo pipefail
SRC="${1:?sorgente mp4}"; W="${2:-2560}"; CRF="${3:-19}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/assets/video/come-funziona-zoom.mp4"
FRAME="$ROOT/assets/img/come-funziona-zoom-frame.jpg"
ffmpeg -y -loglevel error -i "$SRC" -an -vf "scale=${W}:-2:flags=lanczos" \
  -c:v libx264 -profile:v high -preset slow -crf "$CRF" -pix_fmt yuv420p -movflags +faststart "$OUT"
ffmpeg -y -loglevel error -i "$OUT" -frames:v 1 -vf "scale=1920:-2" -q:v 2 "$FRAME"
ffprobe -v error -show_entries format=duration,bit_rate:stream=width,height,nb_frames -of default=nw=1 "$OUT" | tr '\n' ' '; echo
ls -la "$OUT" "$FRAME"
