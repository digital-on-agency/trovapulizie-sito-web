#!/usr/bin/env bash
# Tre clip mobile della sezione Come funziona (TICKET-13, ADR-002 rev. 4).
# Taglia assets/video/come-funziona.mp4 sui confini semantici di T in assets/js/screen-timeline.js
# (non ai terzi aritmetici: a 4,2 e 8,4 i tagli cadevano dentro la transizione gia' iniziata):
#   step 1  0    -> 4,0   (s2.start)      digitazione indirizzo, tile, selezione, tap
#   step 2  4,0  -> 8,0   (s3.start)      risultati, card cleaner, Prenota
#   step 3  8,0  -> 11,4  (s3.bandStart)  conferma, pagamento, messaggio finale
# L'ultimo clip si ferma prima della dissolvenza sulla foto (bandStart): su mobile non c'e' banda
# ne' zoom-through (ADR-002 rev. 4), quel finale appartiene solo al desktop.
# Scala a 360x780 (lo schermo mobile e' largo 146 CSS px). Il poster si prende a +0,5 s dall'inizio
# del clip, non al primo frame: i clip 2 e 3 iniziano a transizione in corso e il primo frame mostrerebbe
# due schermate sovrapposte come fallback statico.
# Non all-intra: questi clip si riproducono, non si cercano frame per frame.
# Uso: bash tools/step-clips-encode.sh
set -e
cd "$(dirname "$0")/.."
SRC=assets/video/come-funziona.mp4
CUTS=(0 4.0 8.0)
ENDS=(4.0 8.0 11.4)
for i in 0 1 2; do
  n=$((i + 1))
  ss=${CUTS[$i]}
  to=$(awk -v a="${ENDS[$i]}" -v b="${CUTS[$i]}" 'BEGIN{printf "%.3f", a - b}')
  ffmpeg -y -loglevel error -ss "$ss" -t "$to" -i "$SRC" \
    -vf "scale=360:780:flags=lanczos" -an \
    -c:v libx264 -preset slow -crf 28 -pix_fmt yuv420p -movflags +faststart \
    "assets/video/come-funziona-step$n.mp4"
  ffmpeg -y -loglevel error -ss "$(awk -v a="$ss" 'BEGIN{printf "%.3f", a + 0.5}')" -i "$SRC" -frames:v 1 \
    -vf "scale=360:780:flags=lanczos" -q:v 6 \
    "assets/img/come-funziona-step$n-poster.jpg"
done
ls -l assets/video/come-funziona-step*.mp4 assets/img/come-funziona-step*-poster.jpg
