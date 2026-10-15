#!/usr/bin/env bash
# Le tre schermate della sequenza mobile di Come funziona (TICKET-13 rev. 2, ADR-002 rev. 5).
# Sono fotogrammi di assets/video/come-funziona.mp4 presi dentro ciascun passo, secondo T in
# assets/js/screen-timeline.js, mezzo secondo dopo l'inizio del passo per non cogliere la transizione:
#   step 1  0,5 s   home con i servizi e l'indirizzo digitato
#   step 2  4,5 s   risultati della ricerca
#   step 3  8,5 s   conferma della prenotazione
# Scala 360x780: lo schermo mobile e' largo 146 CSS px.
# Su mobile non c'e' video (ne' autoplay ne' seek sono affidabili su iOS): queste immagini sono la
# sequenza, non un poster di ripiego. Cambiando i tempi del video desktop vanno rigenerate.
# Uso: bash tools/step-posters-export.sh
set -e
cd "$(dirname "$0")/.."
SRC=assets/video/come-funziona.mp4
TIMES=(0.5 4.5 8.5)
for i in 0 1 2; do
  n=$((i + 1))
  ffmpeg -y -loglevel error -ss "${TIMES[$i]}" -i "$SRC" -frames:v 1 \
    -vf "scale=360:780:flags=lanczos" -q:v 6 \
    "assets/img/come-funziona-step$n-poster.jpg"
done
ls -l assets/img/come-funziona-step*-poster.jpg
