# Home — documentazione del codice

Riferimento tecnico della pagina Home del tema `trovapulizie` (template `page-home.php`). Spiega come
la pagina viene costruita, dove vive ogni comportamento e cosa toccare quando cambia qualcosa.
Le decisioni e il loro perche' stanno negli ADR del vault (`clients/trovapulizie/projects/sito-web/adr/`);
qui c'e' il come.

Fonti di design: Figma `UiW4TPgvqCODW5bhyFV5eK`, frame desktop `3564:4` (1440x9965), frame mobile
`4016:965` (390x6888). Token e valori estratti: `docs/brand-kit.md`. Spec motion (animazioni per
sezione, token di durata/easing): `../portale-transazionale/docs/motion-spec-homepage-redesign.md` nel vault.

Stato al 15/10/2026: desktop completo (ver. tema 0.17.0). Mobile e tablet non ancora adattati
(vedi "Limiti noti").

## 1. Mappa dei file

| File | Ruolo |
|---|---|
| `style.css` | Unico foglio di stile. L'header `Version:` e' anche il cache-buster di tutti gli asset (vedi §8) |
| `functions.php` | Helper condivisi, enqueue di stili e script (script solo sulla Home), importmap Three.js, boot Carbon Fields |
| `inc/carbon-fields.php` | Tutti i campi editoriali: container `post_meta` della Home (tab per sezione) e container `theme_options` del Footer |
| `header.php` | `<head>` + navbar (markup statico, link ad ancore di sezione) |
| `footer.php` | Footer da Theme Options + `wp_footer()` |
| `page-home.php` | Template Home: legge i campi, prepara gli array `$..._args`, renderizza Hero e Come funziona inline, le altre sezioni via `get_template_part` |
| `index.php` | Template di fallback per le altre pagine (contenuto WP nudo) |
| `template-parts/phone-mockup.php` | Bezel CSS del telefono (`.tp-phone`) con schermo = `<video>` o `<img>` |
| `template-parts/services-popular.php` | Sezione Servizi popolari (tab + card) |
| `template-parts/reviews-marquee.php` | Sezione Social proof (badge + marquee di recensioni) |
| `template-parts/zones-map.php` | Sezione Dove siamo attivi (mappa raster + overlay + citta' in arrivo) |
| `template-parts/faq-accordion.php` | Sezione FAQ (colonna contatto + accordion + JSON-LD) |
| `template-parts/cta-final.php` | Sezione CTA finale |
| `assets/js/*.js` | Un modulo per comportamento, nessun bundler, nessuna dipendenza fra moduli tranne `phone-3d.js` -> `screen-timeline.js` (vedi §4) |
| `assets/img/`, `assets/icons/`, `assets/video/` | Asset del tema usati come fallback quando il campo WP e' vuoto (vedi §7) |
| `tools/` | Script di QA headless, pipeline del video dello schermo, encoding video. Non vanno sul server (vedi `tools/README.md`) |

## 2. Flusso di rendering

1. `page-home.php` legge ogni campo con `carbon_get_the_post_meta()` e costruisce un array di argomenti per
   sezione (`$services_args`, `$reviews_args`, `$zones_args`, `$faq_args`, `$cta_args`).
2. Hero e Come funziona sono inline nel template perche' condividono il telefono (`#hiw-phone-pin`, vedi §4).
3. Le altre sezioni sono template-part con `wp_parse_args` e default vuoti: ogni parte e' riutilizzabile in
   altre pagine (landing, quartieri) passando gli stessi argomenti.
4. Ordine nel DOM: navbar, hero, come-funziona, overlay `#hiw-zoom` (solo se c'e' il video zoom),
   servizi, recensioni, zone, faq, cta, footer.
5. Condizioni di render: Come funziona solo con almeno uno step; Servizi, Recensioni e FAQ solo con
   righe nel campo complex; Zone e CTA sempre (la CTA torna vuota senza titolo ne' bottone).
   Da loggato con editor, se Hero/step/servizi sono tutti vuoti compare un avviso giallo in cima.

Convenzioni di markup: BEM (`blocco__elemento--modificatore`), classi di stato `is-*`, variabile CSS
`--i` sugli elementi in stagger (il CSS la moltiplica per il ritardo), attributo `data-reveal` sulle
sezioni che entrano con il reveal (§5). Niente inline style tranne `--i` e la geometria dell'overlay.

## 3. Campi editoriali (Carbon Fields)

Container `post_meta` "Home — Sezioni", visibile solo sulle pagine con template `page-home.php`
(Pagine > Home > Modifica, sotto l'editor). Un tab per sezione.

| Tab | Campi | Note |
|---|---|---|
| Hero | `hero_title` (textarea: un invio = una riga), `hero_subtitle`, `hero_trust_badge`, `hero_video` (file mp4) | Il video e' il timelapse disordine -> ordine (§6.1). Senza video resta `hero-box.png` |
| Come funziona | `how_it_works_title`, `how_it_works_subtitle`, `how_it_works_video` (mp4 all-intra), `how_it_works_poster` (immagine), `how_it_works_zoom_video` (mp4), `how_it_works_steps` (complex max 3: `title`, `description`, `image`) | `image` dello step e' lo schermo statico per mobile e reduced-motion; vuoto = `screen-stepN-*.png` del tema |
| Telefono 3D | `phone_model_url` (GLB opzionale), `phone_model_credit` | Vuoto = corpo procedurale Three.js. Il credito finisce nel footer accanto al copyright |
| Servizi Popolari | `services_title`, `services_subtitle`, `services_proof_avatar`, `services_proof_title`, `services_proof_sub`, `services_proof_rating`, `popular_services` (complex max 6: `tab_label`, `tab_icon` select, `includes` textarea una voce per riga, `card_title`, `card_description`, `highlight`, `card_image`, `visual_label`, `link`, `link_text`) | `tab_icon` deve esistere come `assets/icons/tab-<valore>.svg`; valore sconosciuto = `casa` |
| Social Proof | `reviews_badge`, `reviews_badge_source` (select `google`/`none`), `reviews_badge_link`, `reviews_title`, `reviews` (complex: `author_name`, `author_city`, `avatar`, `review_text`) | Desktop: tutte in marquee; mobile: le prime 3 |
| Dove Siamo Attivi | `zones_title`, `zones_description`, `zones_map_image`, `zones_coming_label`, `zones_coming_cities` (textarea una per riga) | Con una mappa caricata nel campo gli overlay del tema (disegnati per Roma) non vengono resi |
| FAQ | `faq_eyebrow`, `faq_title`, `faq_subtitle`, `faq_contact_*` (people, role, avatar_1, avatar_2, title, text, cta_label, cta_link), `faq_items` (complex: `question`, `answer`) | Prima domanda aperta all'avvio |
| CTA Finale | `cta_title`, `cta_subtitle`, `cta_button_text`, `cta_button_link`, `cta_note`, `cta_image` | |

Container `theme_options` "Footer" (menu di wp-admin "Footer", page file `tp-footer`): `footer_brand_text`,
`footer_tagline`, `footer_social` (complex: `network` select, `url`), `footer_columns` (complex: `title`,
`links` textarea "Etichetta | URL" per riga), `footer_cta_text`, `footer_cta_label`, `footer_cta_link`,
`footer_copyright`, `footer_legal` (textarea "Etichetta | URL" per riga).

Helper usati dai template per leggere i textarea multi-riga: `tp_lines()` (una voce per riga) e
`tp_link_lines()` ("Etichetta | URL" -> `[label, url]`, URL mancante = `#`).

[ATTENZIONE] Entrambi i container devono stare dentro il callback di `carbon_fields_register_fields`.
Un `Container::make()` fuori dal hook manda in fatal tutto il sito (successo il 15/10 su staging).

## 4. Come funziona: telefono, video, zoom-through (desktop)

Decisione e vincoli: ADR-002 (rev. 3). Qui il meccanismo.

### Elementi
- `.hero__phone#hiw-phone-pin`: contenitore del telefono, nasce nella hero. Dentro: il bezel CSS
  `#hiw-phone` (`phone-mockup.php` con il video dello schermo) e `.hero__phone-3d`, host del canvas Three.js.
- `.how-it-works` (`#come-funziona`): sezione con `.how-it-works__sticky` (titolo, sottotitolo, 3 step).
- `#hiw-zoom`: overlay `position: fixed` con il video dello zoom-through e un velo verde (`.hiw-zoom__veil`).
- `.services__card .services__visual`: la foto della prima card di Servizi popolari, sotto cui sta il
  `<video class="services__video">` con lo stesso file dello zoom (atterraggio).

### Gating
`home-motion.js` parte solo con `(min-width: 768px) and (prefers-reduced-motion: no-preference)`; in quel
caso mette `motion-ok` su `<html>`. Tutto il CSS della coreografia e' sotto `.motion-ok`. Senza la classe la
pagina e' statica: telefono fermo nella hero, step in griglia con lo schermo statico (`how-it-works__phone-static`).

GSAP 3.13 + ScrollTrigger si caricano da cdnjs solo quando la sezione entra a meta' viewport di distanza
(IntersectionObserver `rootMargin: 50%`). Three.js r186 arriva via importmap (in `functions.php`) e
`phone-3d.js` viene importato dinamicamente solo con WebGL2; se fallisce resta il bezel CSS.

### Unita' di scroll
`.motion-ok .how-it-works` e' alta 550vh. Il telefono viene pinnato (ScrollTrigger, `pinSpacing: false`,
`scrub: 0.7`) da quando il suo centro raggiunge il centro del viewport fino a `sectionTop + 4vh`:

| Intervallo (u = viewport scrollati da `sectionTop`) | Cosa succede | Chi lo guida |
|---|---|---|
| prima del pin (`p < p0`) | handoff: il telefono va dalla posa hero alla posa s1 | trigger A |
| 0 - 3 | video scrubbed; `q = u/3` in 0..1; step attivo = terzo corrente; pose s1 -> s2 -> s3 -> out | trigger A |
| q 0.82 - 0.94 | `--hiw-text-fade` 1 -> 0: titolo e step sfumano mentre il telefono si raddrizza | trigger A |
| 3 - 4 | zoom-through: l'overlay parte dal rettangolo esatto dello schermo (`screenRect()`) e cresce fino al viewport | trigger A (`zoom(z)`) |
| 4 - 4.5 | tenuta a tutto schermo, telefono rilasciato | `syncBeyondPin()` |
| 4.5 - 5.5 | rientro: overlay dal viewport al rettangolo della foto della prima card, velo verde che cresce | `syncBeyondPin()` (`shrink(s)`) |
| >= 5.5 | atterraggio: `cardTakeover(true)`, la card mostra il video in loop (`is-live`), overlay spento | `syncBeyondPin()` + IntersectionObserver sulla card |

Costanti in `home-motion.js`: `VIDEO_VH = 3`, `ZOOM_VH = 1`, `HOLD_VH = 0.5`, `SHRINK_VH = 1`. Se cambiano
va aggiornato anche `height: 550vh` nel CSS.

Snap: durante i 3 viewport del video lo scroll si aggancia ai terzi (0, 1, 2, 3); oltre e' libero.
`?nosnap=1` nell'URL disattiva lo snap (serve al QA per fermarsi a q intermedi).

Perche' due meccanismi: un secondo ScrollTrigger per tenuta e rientro non funziona nei salti (link ad ancora,
`scrollIntoView`, scroll rapido): un trigger mai attivato non chiama ne' `onLeave` ne' `onUpdate`, e
ScrollTrigger non ha un evento `update` globale. Quindi oltre il pin lo stato si ricalcola da `scrollY` a ogni
scroll (listener passivo + rAF) e a ogni `refresh`. Il trigger A, essendo scrubbed, puo' arrivare in ritardo
dopo un salto: per questo in `onUpdate` lo zoom non viene toccato se la card ha gia' preso il video (`cardLive`).

### Pose del telefono
`POSES` in `home-motion.js`: x in px (canvas Figma 1440), y in frazione di viewport, rotazioni in gradi
(convenzione CSS), scala, `k` = quanto la posa si riduce su viewport piu' bassi di 980px. Misurate sui render
Figma degli stati (`3743:3095` hero, `3750:3099` s1, `3798:1087` s2, `3828:1157` s3, `3828:1198` outro).
[ATTENZIONE] Le x/y dei nodi ruotati nei metadata Figma sono pre-rotazione: non coincidono con il bbox.
Le stesse pose valgono per il ramo CSS (`transform` sul bezel) e per il ramo 3D (`setPose` in `phone-3d.js`).

### Schermo del telefono
- Il video dello schermo (`how_it_works_video`) e' codificato all-intra (ogni frame keyframe) perche' lo scrub
  fa un seek per frame. `seek()` tiene un solo seek in volo (`pump()` su `seeked`): accodare `currentTime`
  durante un seek fa scattare il video.
- Timeline condivisa `screen-timeline.js` (`T.total = 12.6` s, tempi per segmento, posizioni UV di tile e
  card): la usano lo storyboard che genera il video (tools/screen-storyboard) e l'overlay 3D che solleva tile
  e card dallo schermo (`phone-3d.js`, `setProgress(q)`). Cambiare un tempo qui e non rigenerare il video
  rompe il sincronismo.
- Vincoli dei file: primo frame del video schermo = screen Home della hero; ultimo frame del video schermo =
  primo frame del video zoom = foto della prima card di Servizi (`come-funziona-zoom-frame.jpg`).

### Telefono 3D (`phone-3d.js`)
Corpo procedurale (profilo arrotondato estruso, cornice frontale nera opaca, fianchi metallici) oppure GLB dal
campo WP, schermo = `VideoTexture`, vetro semitrasparente, isola in alto. Canvas 720x960 centrato sul box del
phone (`.hero__phone-3d`), render solo su richiesta (`requestRender`). Espone `setPose`, `setProgress`,
`screenRect` (proiezione dei 4 angoli dello schermo: da qui parte l'overlay dello zoom). Con `.is-3d` sul
contenitore il bezel CSS resta nel DOM ma invisibile.

### Layering (z-index)
navbar 10 (sticky), overlay zoom 9 (fixed), telefono pinnato 5, contenuto hero e card 1. L'overlay sta sotto la
navbar di proposito.

## 5. Le altre sezioni

Comportamento comune: `reveal.js` osserva ogni `[data-reveal]` e aggiunge `.is-in` una volta (threshold
0.15). Lo stagger e le trasformazioni sono nel CSS di ciascuna sezione (variabile `--i`). Riduci movimento:
blocco unico in coda a `style.css` che azzera transizioni e animazioni di tutte le sezioni.

| Sezione | Figma | Template | JS | Note di implementazione |
|---|---|---|---|---|
| Navbar | `3564:5` | `header.php` | - | Altezza `--tp-navbar-h` (72px); da questo token derivano l'altezza del box hero e `--hiw-offset`. Link nascosti sotto 960px, nessun menu mobile |
| Hero | `3564:17` | inline | `hero-video.js` | Box con foto `hero-box.png` + scrim verde (`::after`) + video timelapse con `preload="none"`: lo carica il JS solo senza reduced-motion, `is-playing` lo fa sfumare sopra la foto, resta sull'ultimo frame. Search bar = markup statico (il widget reale e' in ADR-001) |
| Servizi popolari | `3828:1079` | `services-popular.php` | `services-tabs.js` | Tab con `role="tab"`, frecce sinistra/destra, indicatore che scivola (`has-indicator`/`is-ready`), card tutte nella stessa cella grid e solo quella attiva visibile (`is-active`, `is-enter` per il rise). Icone tab = SVG a maschera (`tp_icon_span`, utility `.tp-icon-mask`). Mascotte decorativa che entra da destra. La prima card ospita il video dello zoom (§4) |
| Social proof | `3564:96` | `reviews-marquee.php` | - | Marquee CSS 40 s con il set di card duplicato (`aria-hidden` sul secondo), pausa in hover. Il contenitore non usa `overflow: hidden` per non tagliare ombre e lift. Badge con logo Google (`google-g.svg`) |
| Dove siamo attivi | `3564:114` | `zones-map.php` | - | Mappa raster (niente draw-in). Overlay Centro e Prati in coordinate percentuali con `--zones-op` come opacita' a regime, pulse all'ingresso, alone radar in loop. Citta' come chip senza link |
| FAQ | `6375:1291` | `faq-accordion.php` | `faq-accordion.js` | Layout B2B a due colonne (sotto 1000px una colonna). Una domanda aperta per volta, apertura con `grid-template-rows 0fr -> 1fr`, `aria-expanded`, segno +/- in CSS. JSON-LD `FAQPage` generato dagli stessi item |
| CTA finale | `3564:162` | `cta-final.php` | - | Card a gradiente, illustrazione `mascotte-mocio.png`, `id="registrati"` (ancora del bottone navbar) |
| Footer | `3564:167` | `footer.php` | - | Tutto da Theme Options. Social come icone a maschera; senza URL l'icona e' resa ma non e' un link |

## 6. Asset video

| File | Campo | Uso | Produzione |
|---|---|---|---|
| `assets/video/hero-timelapse.mp4` (1 MB, 3 s) | `hero_video` | sfondo hero | `tools/hero-video-encode.sh <sorgente>` (accelera a 3 s, 1600px, crf 26) |
| `assets/video/come-funziona.mp4` (5 MB, 12,6 s, all-intra) | `how_it_works_video` | schermo del telefono, scrubbed | `tools/screen-storyboard/render.js` (frame PNG via Chromium) + ffmpeg con `keyint=1` (comando nell'header di render.js) |
| `assets/video/come-funziona-zoom.mp4` (6 MB, 2560px) | `how_it_works_zoom_video` | zoom-through e card Servizi | `tools/zoom-video-encode.sh <sorgente>` (salva anche il primo frame in `come-funziona-zoom-frame.jpg`) |
| `assets/video/placeholder-dev.mp4` | - | non referenziato (residuo del 14/10) | - |

Sullo staging i tre campi puntano ai file del tema (URL `wp-content/themes/trovapulizie/assets/video/...`),
non alla libreria media: cosi' il `?ver=` del tema li invalida.

Texture dell'overlay 3D: `assets/img/tiles/` (tile dei servizi, `card-laura.png`). La card viene ritagliata dallo
screen Figma con `tools/screen-storyboard/cut-card.py`, che stampa anche il box da riportare in
`screen-timeline.js` (`CARD`) e nello storyboard.

## 7. Asset di fallback del tema

Quando il campo WP e' vuoto il template usa l'asset del tema (sempre via `tp_theme_asset()`, quindi versionato):

| Campo vuoto | Asset |
|---|---|
| `how_it_works_poster` | `assets/img/hero-phone-screen.png` (schermo nella hero) |
| step `image` | `assets/img/screen-step1-servizio.png`, `screen-step2-cleaner.png`, `screen-step3-conferma.png` |
| `card_image` | `assets/img/services-visual-pulizia-casa.jpg` |
| `services_proof_avatar` | `assets/img/services-proof-avatar.jpg` |
| recensione `avatar` | `assets/img/review-avatar-1..3.png` (a rotazione) |
| `zones_map_image` | `assets/img/zones-map-roma.jpg` + overlay `zones-overlay-centro.png`, `zones-overlay-prati.jpg` |
| `faq_contact_avatar_1/2` | `assets/img/founder-guido.png`, `founder-christian.png` |
| `cta_image` | `assets/img/mascotte-mocio.png` |
| mascotte Servizi | `assets/img/mascotte.png` (sempre dal tema) |

Icone: `assets/icons/tab-*.svg` (tab servizi), `social-*.svg` (footer), `ic-*.svg` e `chevron.svg` (search bar),
`google-g.svg` (badge recensioni). Le icone monocromatiche sono rese come maschera CSS in `currentColor`
(`tp_icon_span()` -> `<span class="... tp-icon-mask" style="--icon:url(...)">`): il colore lo decide il CSS,
il file deve avere il disegno pieno (nessun colore proprio necessario).

## 8. Cache-busting e versione del tema

Hostinger (LiteSpeed) cacha HTML e asset per 7 giorni. Regola:
- ogni asset del tema passa da `tp_theme_asset()` / `tp_versioned_url()` che aggiungono `?ver=<Version>`; anche
  i video e le texture dell'overlay 3D (`texOf` in `phone-3d.js` riusa la query string del modulo);
- quindi **ogni deploy che cambia CSS, JS, immagini o video richiede il bump di `Version:` in `style.css`**
  (minor per funzionalita', patch per rifiniture), altrimenti il browser serve il file vecchio;
- dopo la copia sul server: `wp litespeed-purge all` nel docroot (vedi README, Deploy);
- per il QA headless cancellare il profilo Chromium (`/tmp/cdp-profile*`) oppure si vede l'HTML cachato.

## 9. Dipendenze esterne

| Cosa | Da dove | Quando |
|---|---|---|
| Poppins | Google Fonts (`wp_enqueue_style`) | sempre |
| GSAP 3.13.0 + ScrollTrigger | cdnjs, caricati da `home-motion.js` | Home, solo con `motion-ok`, quando la sezione si avvicina |
| Three.js 0.186.1 + addons | jsdelivr via importmap in `wp_head` | Home, import dinamico da `home-motion.js` solo con WebGL2 |
| Carbon Fields | Composer (`vendor/`, non tracciato) | sempre (`composer install` in locale; sul server `vendor/` e' gia' presente) |

## 10. Breakpoint e gating motion

Soglia unica a **1024px** (ADR-002 rev. 4), dichiarata una volta sola nel token `--tp-bp-desktop` su
`:root`. Le media query di sezione usano `max-width: 1023px` (il CSS non accetta `var()` dentro `@media`,
quindi il valore e' ripetuto a mano: cambiandolo, cambiarlo ovunque) e `home-motion.js` costruisce il
proprio `matchMedia` leggendo quel token, cosi' CSS e JS non possono divergere.

- fino a 1023px: layout impilato, nessun pin, nessun GSAP, nessun Three.js;
- da 1024px: coreografia desktop completa.

Restano le soglie fluide desktop (960, 1000, 1100, 1240px) che riducono spaziature e colonne senza
cambiare comportamento. I tablet in orizzontale (1024-1279) restano sulla coreografia desktop, per scelta.

Il telefono e' parametrico: `--tp-phone-w`, `--tp-phone-h`, `--tp-phone-radius`,
`--tp-phone-screen-radius` (default desktop 294x608). Le varianti mobile del Figma (150x300 nella hero,
158x320 negli step) si ottengono ridefinendo i token, senza `transform: scale()`.

## 11. QA

Script in `tools/` (Node 22, Chromium di Playwright in cache, CDP; dettagli in `tools/README.md`):
- `qa-scroll-shots.js`: stati 00..16 della coreografia (hero, handoff, terzi, zoom, tenuta, rientro,
  atterraggio) con screenshot e log di posa, `currentTime`, step, 3D, overlay, card;
- `qa-section.js`: una sezione a scelta, larghezza a scelta, click opzionale;
- `qa-services.js`: tab e card di Servizi popolari;
- `qa-hero-video.js`: autoplay e fine del timelapse.

Letture: in headless le transizioni CSS avanzano solo quando si scatta uno screenshot; con valori "a meta'"
rilanciare con attese piu' lunghe (`slow`). I numeri loggati vanno confrontati con il run precedente: un
refactor senza cambi di comportamento deve dare gli stessi valori.

## 12. Limiti noti (15/10/2026, ver 0.18.0)

- Mobile e tablet adattati solo nella base (TICKET-10): niente piu' scroll orizzontale e niente piu'
  coreografia sotto 1024px, ma il layout delle sezioni non e' ancora quello del Figma mobile
  (`4016:965`). Da fare: navbar senza menu, hero, Come funziona impilata animata, le altre sezioni.
  Ticket TICKET-11..21 in `spec/tickets/` nel vault.
- `reviews__badge` sporge dal viewport a 360px (clippato, non genera scroll): lo chiude TICKET-15.
- `screen-step1-servizio.png` viene scaricato due volte (poster del video senza `?ver`, `<img>` con
  `?ver`): lo chiude TICKET-20.
- Contenuti segnaposto: recensioni, badge Google, FAQ 2-5, servizi Uffici/Condomini, URL social, link
  Prezzi/Accedi/Registrati, pagine linkate dal footer non esistenti.
- Search bar della hero statica: il widget di ricerca reale e' il widget JS di ADR-001.
- `placeholder-dev.mp4` tracciato ma non usato.
