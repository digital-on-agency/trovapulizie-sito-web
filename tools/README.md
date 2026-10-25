# tools/

Script di lavoro per il tema. Non vanno copiati sul server. Richiedono Node 22 (WebSocket nativo), Python 3
con Pillow e numpy, ffmpeg, e il Chromium di Playwright (`~/.cache/ms-playwright/chromium-*/chrome-linux64/chrome`).

## QA headless (CDP)

Tutti gli script di QA usano `lib/cdp.js`: avvia Chromium headless con SwiftShader (WebGL senza GPU) e
autoplay consentito, apre l'URL e passa `send`, `ev` (valuta JS nella pagina), `shot` (screenshot) e `sleep`.
Ogni script usa una porta di debug diversa, quindi possono girare in parallelo.

| Script | Uso | Output |
|---|---|---|
| `qa-scroll-shots.js <chrome> <url>` | stati della coreografia Come funziona (00 hero ... 16 atterraggio); aggiungere `?nosnap=1` all'URL per fermarsi a q intermedi | `/tmp/cdp-*.png` + log per stato |
| `qa-section.js <chrome> <url> <selettore> [width=1440] [out] [selettore-click]` | una sezione a una larghezza, con click opzionale | screenshot + `STATE` (rect, `is-in`, animazione, overlay) |
| `qa-services.js <chrome> <url> [width] [slow]` | tab e card di Servizi popolari | `/tmp/cdp-services-{0,1}.png` + geometria |
| `qa-hero-video.js <chrome> <url>` | autoplay e fine del timelapse hero | `/tmp/cdp-hero-{0,1,2}.png` + stato video |
| `qa-zones-map.js <chrome> <url> [larghezza]` | mappa interattiva per CAP: livelli, passaggio del mouse, click, select, CTA con il CAP, stato di ripiego con la copertura bloccata via CDP | esito per controllo, codice di uscita 1 se fallisce |
| `qa-fullpage.js <chrome> <url> [width] [out] [maxHeight]` | screenshot dell'intera pagina (viewport portato all'altezza del documento) | un PNG |
| `qa-sgrosso.js <chrome> <url>` | form lead in tre passi e calcolatore della landing Sgrosso con l'API simulata via `Fetch` (nessun lead reale): payload, eventi dataLayer, navigazione alla thank-you | esito per controllo, codice di uscita 1 se fallisce |
| `qa-lead-contract.js [apiBase] [--real email]` | contratto delle rotte `/api/leads` dal terminale: 400 del backend e honeypot; con `--real` giro completo che crea record su Airtable (da archiviare) | esito per controllo |
| `qa-tracking.js <chrome> <url>` | gtm.js una volta, consent default negato, `tp_gclid` e UTM dall'URL, eventi `marketing_*` dai click su tel: e #form | esito per controllo |
| `qa-seo.py [base]` (Python) | title, description, canonical, robots, H1, JSON-LD Service e FAQPage delle cinque landing Sgrosso | esito per controllo |
| `qa-cleaner.js <chrome> <url> [larghezza]` | pagina Diventa cleaner: formula del calcolatore, slider e chip, eventi `marketing_*` delle CTA con `cta_position` (navigazione bloccata), signup e WhatsApp, partner assenti col campo vuoto, un solo FAQPage e un solo H1 | esito per controllo, codice di uscita 1 se fallisce |
| `qa-home-search.js <chrome> <url> [larghezza] [out.png]` | barra di ricerca della Home: invio intercettato con `Fetch` verso app.trovapulizie.com; privati -> `/home` o `/results` con address e service, aziende -> `/richiedi-preventivo?step=0`, indirizzo vuoto -> nessun invio, menu con i servizi dell'API | esito per controllo |
| `qa-cleaner-motion.js <chrome> <url> [cartella]` | movimento della pagina Diventa cleaner: ingressi a cascata, nessun blocco rimasto nascosto dopo lo scroll, conteggio della stima, hover reali (`Input.dispatchMouseEvent`) su card, tile e bottone, parallasse della hero, "riduci movimento" emulato. Avvia Chromium con `--blink-settings` da mouse: in headless `hover` e `pointer` valgono `none` | esito per controllo + screenshot degli stati |
| `qa-mobile.js <chrome> <url> [larghezze]` | sweep mobile e tablet: scroll orizzontale, gate motion, navbar, hero, sequenza di Come funziona, risorse CDN, peso (budget 2560 KB sotto soglia, rendition `-720` dei video, nessuno screen a piena risoluzione, nessun file chiesto con due URL) | esito per larghezza con le sei risorse piu' pesanti, codice di uscita 1 se fallisce. Il peso e' attendibile solo con profilo pulito (`rm -rf /tmp/cdp-profile*`); il peso di un video arriva da piu' richieste Range sullo stesso URL, lo script le somma |

Prima di un run dopo un deploy: cancellare `/tmp/cdp-profile*` (l'HTML e' cachato 7 giorni) e attendere qualche
secondo dopo `wp litespeed-purge all`. In headless le transizioni CSS avanzano solo allo scatto: letture a meta'
transizione si risolvono con attese piu' lunghe, non sono bug.

## Video dello schermo (Come funziona)

`screen-storyboard/`:
- `index.html`: storyboard 1170x2532 con gli screen Figma dei tre step come layer e gli elementi animati
  (indirizzo digitato, pressioni, evidenziazione card, thank-you, fade finale). Espone `seek(t)`.
- `render.js <chrome> --out <dir> [--fps 24] [--total 12.6] [--times ...]`: scatta un PNG per frame. Poi ffmpeg
  all-intra (comando nell'header del file) -> `assets/video/come-funziona.mp4`.
- `cut-card.py`: ritaglia la card del cleaner dallo screen del passo 2 in `assets/img/tiles/card-laura.png` e
  stampa il box da riportare in `assets/js/screen-timeline.js` (`CARD`) e nello storyboard.

I tempi dello storyboard e degli overlay 3D vengono da `assets/js/screen-timeline.js`: cambiarli richiede di
rigenerare il video.

## Encoding

- `hero-video-encode.sh <sorgente.mp4> [durata=3] [larghezza=1600]` -> `assets/video/hero-timelapse.mp4`
- `step-posters-export.sh` -> le tre schermate della sequenza mobile
  `assets/img/come-funziona-step{1,2,3}-poster.jpg`, estratte dal video desktop sui tempi di `screen-timeline.js`
- `zoom-video-encode.sh <sorgente.mp4> [larghezza=2560] [crf=19]` -> `assets/video/come-funziona-zoom.mp4` +
  primo frame in `assets/img/come-funziona-zoom-frame.jpg`

Dopo ogni nuovo video: bump di `Version:` in `style.css`, deploy, purge (vedi README principale).

## Mappa per CAP

`zones-map/`:
- `build-svg.py`: dai 72 MultiPolygon di `roma_cap.geojson` (vault, dataset Zornade su dati OSM, ODbL)
  genera `assets/img/zones-map-cap.svg`, `assets/data/cap-centroids.json` e `assets/data/cap-names.json`.
  Va rieseguito solo quando cambia la geometria o l'export dei nomi. I nomi vengono da
  `zone-quartieri-export.json` accanto allo script (export una tantum di `zone_quartieri`, 26 CAP su 72).
- `verify.py`: i criteri di TICKET-01 in 16 controlli, compresa la trappola del Douglas-Peucker sugli
  anelli chiusi.
