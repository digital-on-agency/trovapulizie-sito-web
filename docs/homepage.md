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
| `template-parts/phone-mockup.php` | Bezel CSS del telefono (`.tp-phone`) con schermo = `<video>` o `<img>`; con `lazy` il video esce in `data-src` |
| `template-parts/services-popular.php` | Sezione Servizi popolari (tab + card) |
| `template-parts/reviews-marquee.php` | Sezione Social proof (badge + marquee di recensioni) |
| `template-parts/zones-map.php` | Sezione Dove siamo attivi (mappa raster + overlay + citta' in arrivo) |
| `template-parts/faq-accordion.php` | Sezione FAQ (colonna contatto + accordion + JSON-LD) |
| `template-parts/cta-final.php` | Sezione CTA finale |
| `assets/js/nav-mobile.js` | Menu mobile della navbar (l'unico modulo caricato su tutte le pagine, non solo la Home) |
| `assets/js/hiw-sequence.js` | Sequenza mobile di Come funziona: tre schermate in crossfade guidate dallo scroll |
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
| avvicinamento (anche prima che il trigger A si attivi) | `--hiw-text-fade` sale da 0 a 1 man mano che la cima della sezione sale da 0.85 a 0.1 viewport: titolo e step compaiono mentre il telefono arriva, non sono gia' li' (`entryFade()`) | handler di scroll + trigger A |
| prima del pin (`p < p0`) | handoff: il telefono va dalla posa hero alla posa s1 | trigger A |
| 0 - 3 | video scrubbed; `q = u/3` in 0..1; step attivo = terzo corrente; pose s1 -> s2 -> s3 -> out | trigger A |
| q 0.82 - 0.94 | `--hiw-text-fade` 1 -> 0: titolo e step sfumano mentre il telefono si raddrizza | trigger A |
| 3 - 4 | zoom-through: l'overlay parte dal rettangolo esatto dello schermo (`screenRect()`) e cresce fino al viewport | trigger A (`zoom(z)`) |
| 4 - 4.5 | tenuta a tutto schermo, telefono rilasciato | `syncBeyondPin()` |
| 4.5 - 5.5 | rientro: overlay dal viewport al rettangolo della foto della prima card, velo verde che cresce | `syncBeyondPin()` (`shrink(s)`) |
| >= 5.5 | atterraggio: `cardTakeover(true)`, la card mostra il video in loop (`is-live`), overlay spento | `syncBeyondPin()` + IntersectionObserver sulla card |

[ATTENZIONE] La dissolvenza d'ingresso non puo' dipendere dal trigger del pin: quello parte quando il
telefono della hero ha il centro al centro del viewport, cioe' quando il titolo della sezione e' gia' entrato
da un pezzo. Finche' era legata al trigger, `--hiw-text-fade` restava al valore di default (1) e i testi si
vedevano gia' mentre salivano. Ora `entryFade()` la calcola dalla posizione della sezione e la scrivono sia
l'handler di scroll (prima del trigger) sia `onUpdate` (dentro l'handoff).

Costanti in `home-motion.js`: `VIDEO_VH = 3`, `ZOOM_VH = 1`, `HOLD_VH = 0.5`, `SHRINK_VH = 1`. Se cambiano
va aggiornato anche `height: 550vh` nel CSS.

Snap: durante i 3 viewport del video lo scroll si aggancia ai terzi (0, 1, 2, 3); oltre e' libero.
`?nosnap=1` nell'URL disattiva lo snap (serve al QA per fermarsi a q intermedi).

Perche' due meccanismi: un secondo ScrollTrigger per tenuta e rientro non funziona nei salti (link ad ancora,
`scrollIntoView`, scroll rapido): un trigger mai attivato non chiama ne' `onLeave` ne' `onUpdate`, e
ScrollTrigger non ha un evento `update` globale. Quindi oltre il pin lo stato si ricalcola da `scrollY` a ogni
scroll (listener passivo + rAF) e a ogni `refresh`. Il trigger A, essendo scrubbed, puo' arrivare in ritardo
dopo un salto: per questo in `onUpdate` lo zoom non viene toccato se la card ha gia' preso il video (`cardLive`).

### Impaginazione della sezione (desktop)
Revisione UX del 15/10 notte: **colonna editoriale** (titolo allineato a sinistra, stepper verticale,
segnale di scorrimento) e telefono di fianco. Dal 16/10 il **telefono sta a sinistra e la colonna a destra**
(richiesta di Guido). Il telefono e' pinnato fuori dalla sezione, quindi la colonna non lo contiene:
larghezza della colonna (`min(39%, 560px)`), suo `padding-right` (13%) e `x` delle pose vanno tenute
coerenti **a mano**, sono tre numeri che descrivono una sola composizione. Le `x` negative sono le
precedenti specchiate rispetto al centro del viewport, cosi' i margini esterni sono quelli di prima
scambiati di lato.

**Bilanciamento (16/10).** Prima la colonna partiva da 6,7% e il telefono stava al centro della propria
meta' (`x: 200`): a 1440 restavano 96px di margine a sinistra contro 373 a destra, cioe' il vuoto piu' grande
finiva sul bordo invece che fra i due elementi. Ora testo e telefono sono un blocco unico centrato
(`padding` 13% dal proprio lato, `x: -337`): a 1440 i margini esterni valgono 187 e 226 — i 40px di scarto
sono voluti, Guido ha chiesto il telefono un po' piu' verso il centro e piu' in basso rispetto al
centraggio esatto. Il titolo non e' piu' allineato agli H2 delle altre sezioni (76-80px), ma fra Come funziona e Servizi
c'e' in mezzo lo zoom-through a tutto schermo, quindi quell'allineamento non e' mai visibile.

Le pose hanno `y: 0.035` su tutti gli stati (prima `0.075`, poi `0`): la colonna e' centrata verticalmente nel
viewport e il telefono e' centrato nel suo canvas, quindi la y e' esattamente lo scarto fra i due centri —
0 li allinea, 0,035 lascia il telefono ~29px piu' in basso del testo, come chiesto. Per lo stesso motivo il segnale di scorrimento porta `margin-bottom: -52px`: e' invisibile
fuori dal primo passo ma resta nel flusso, e senza quel margine spostava in alto il centraggio della colonna.

[ATTENZIONE] La `x` delle pose non puo' crescere liberamente: il telefono si muove **dentro** al canvas 3D
(vedi "Telefono 3D"), e oltre il bordo del canvas viene tagliato. Per questa composizione il canvas e' stato
portato da 720 a 1200 di larghezza.

Lo **stepper** mostra tutti e tre i passi con il pallino numerato: quello attivo apre il proprio paragrafo
(`grid-template-rows: 0fr -> 1fr`, l'unico modo di animare un'altezza automatica), i gia' fatti restano come
titoli in `--tp-text-body` con il tratto di collegamento colorato, quelli da fare sono spenti. La linea che
unisce i pallini e' insieme decorazione e indicatore di avanzamento.

Cosa c'era prima e perche' e' cambiato: i tre passi stavano sparsi (1 e 3 a sinistra, 2 a destra, a
31/48/65% dell'altezza), tutti visibili con gli inattivi al 55%, e fra un passo e l'altro l'occhio faceva
quasi 1000px in orizzontale mentre il contenuto da guardare restava fermo al centro; non c'era modo di
sapere quanti passi fossero. Una prima versione intermedia (un riquadro unico piu' barre 1-2-3) risolveva la
lettura ma lasciava tre ancoraggi diversi — titolo centrato sul viewport, testo in alto a sinistra, telefono
a destra — e la composizione galleggiava.

Il colore dei paragrafi e' passato da `--tp-text-placeholder` a `--tp-text-body`: il primo dava 2,5:1 su
bianco, sotto il minimo WCAG AA di 4,5:1.

[ATTENZIONE] La regola base di `.how-it-works__sticky` e' `margin: 0 auto`: nella versione a colonna va
azzerata, altrimenti la colonna stretta finisce al centro, sotto il telefono.

### Pose del telefono
`POSES` in `home-motion.js`: x in px (canvas Figma 1440), y in frazione di viewport, rotazioni in gradi
(convenzione CSS), scala, `k` = quanto la posa si riduce su viewport piu' bassi di 980px. Misurate sui render
Figma degli stati (`3743:3095` hero, `3750:3099` s1, `3798:1087` s2, `3828:1157` s3, `3828:1198` outro).
[ATTENZIONE] Le x/y dei nodi ruotati nei metadata Figma sono pre-rotazione: non coincidono con il bbox.

**Scostamento voluto dal Figma (15/10 notte, Guido)**: le pose del design facevano ballare troppo il
telefono (ry +-24, rz +-12, x fino a -62, y fino a 0.143) e distraevano dal contenuto dello schermo, che e'
il punto della sezione. Oggi le rotazioni sono circa dimezzate (ry +-10, rz +-5) e x e y quasi costanti:
cambia la posa, non la posizione. La scala e' passata da 0.95 a 1.2 e `--hiw-offset` da `navbar + 12px` a
24px, cosi' il titolo sale e lo spazio recuperato va al telefono. Anche la posa finale `out` sta a 1.2:
con 1 il telefono rimpiccioliva di colpo nell'ultimo segmento, subito prima dello zoom.
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
campo WP, schermo = `VideoTexture`, vetro semitrasparente, isola in alto. Canvas 1200x960 centrato sul box del
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
| Navbar | `3564:5`, mobile `4018:965` | `header.php` | - | Altezza `--tp-navbar-h`: 72px desktop, 65px sotto 1024 (il token si ridefinisce, non l'altezza della barra). Sotto soglia: hamburger e pannello |
| Hero | `3564:17` | inline | `hero-video.js` | Box con foto `hero-box.png` + scrim verde (`::after`) + video timelapse con `preload="none"`: lo carica il JS solo senza reduced-motion, `is-playing` lo fa sfumare sopra la foto, resta sull'ultimo frame. Search bar = markup statico (il widget reale e' in ADR-001) |
| Servizi popolari | `3828:1079` | `services-popular.php` | `services-tabs.js` | Tab con `role="tab"`, frecce sinistra/destra, indicatore che scivola (`has-indicator`/`is-ready`), card tutte nella stessa cella grid e solo quella attiva visibile (`is-active`, `is-enter` per il rise). Icone tab = SVG a maschera (`tp_icon_span`, utility `.tp-icon-mask`). Mascotte decorativa che entra da destra. La prima card ospita il video dello zoom (§4) |
| Social proof | `3564:96` | `reviews-marquee.php` | - | Marquee CSS 40 s con il set di card duplicato (`aria-hidden` sul secondo), pausa in hover. Il contenitore non usa `overflow: hidden` per non tagliare ombre e lift. Badge con logo Google (`google-g.svg`) |
| Dove siamo attivi | `3564:114` | `zones-map.php` | - | Mappa raster (niente draw-in). Overlay Centro e Prati in coordinate percentuali con `--zones-op` come opacita' a regime, pulse all'ingresso, alone radar in loop. Citta' come chip senza link |
| FAQ | `6375:1291` | `faq-accordion.php` | `faq-accordion.js` | Layout B2B a due colonne (sotto 1000px una colonna). Una domanda aperta per volta, apertura con `grid-template-rows 0fr -> 1fr`, `aria-expanded`, segno +/- in CSS. JSON-LD `FAQPage` generato dagli stessi item |
| CTA finale | `3564:162` | `cta-final.php` | - | Card a gradiente, illustrazione `mascotte-mocio.png`, `id="registrati"` (ancora del bottone navbar) |
| Footer | `3564:167` | `footer.php` | - | Tutto da Theme Options. Social come icone a maschera; senza URL l'icona e' resa ma non e' un link |

### Dove siamo attivi: mappa per CAP (ADR-003)

`template-parts/zones-interactive.php` + `assets/js/zones-map.js`. L'SVG dei 72 CAP e' incluso **inline**
(`file_get_contents`), non come `<img>`: il CSS deve poter colorare i singoli path. Nome e macro zona di
ogni CAP stanno nei `data-name` / `data-macro` dei path, scritti dal build: sono dati del tema, non
dell'endpoint, e servono anche quando i numeri non arrivano. Il pannello ha quattro stati (`data-state`:
idle / hover / selected / unknown) in una sola cella di griglia, con un'altezza riservata di 400px perche'
il blocco della zona e' vuoto finche' non si sceglie. Con `data-coverage-url` vuoto o irraggiungibile la
sezione resta navigabile senza numeri (`data-network="fallback"`), un solo tentativo, 4 secondi.

Come ci si muove nella sezione (revisione del 17/10, ADR-003 rev. 2):
- **barra di ricerca** `template-parts/search-bar.php`, la stessa della hero: indirizzo o CAP. Riconosce
  un CAP di cinque cifre anche dentro un indirizzo incollato, una voce della datalist "Nome - CAP" e un
  nome di quartiere anche parziale. Senza corrispondenza il pannello passa a `unknown` e lo dice.
- **chip dei quartieri**, nel blocco di riposo del pannello: sono i 26 nomi veri da `zone_quartieri`,
  nel markup anche senza JS (quindi indicizzabili) e sono i bottoni che insieme alla barra sostituiscono
  il select; dalla zona scelta si torna all'elenco con "Tutte le zone".
- **nessun numero di professionisti a schermo** (decisione del 17/10): con l'offerta di oggi, "2 persone
  e 2 imprese" racconta la debolezza invece della copertura. Il pannello risponde a parole ("Siamo
  attivi in questa zona" / "Non siamo ancora attivi qui") e in evidenza resta il solo numero di zone
  attive. Il dato continua ad arrivare dall'endpoint e decide lo stato della zona: per rimetterlo a
  schermo basta `'show_counts' => true` sul template-part.
- **tre stati sulla mappa** (`data-state` sui path): attiva, in arrivo, non coperta. Non quattro livelli
  di intensita': la copertura reale va da 1 a 4 profili e una gradazione mostrerebbe sempre lo stesso
  colore. I numeri veri stanno nel pannello.

Revisione UI/UX del 17/10, con i numeri del rilievo (contrasti calcolati, non scelti a occhio):
- **contrasti**: testo dei chip da 3,2:1 a **4,99:1** (`#0A7A4D` su `#EAFAF4`), suggerimenti e didascalie
  da 2,9 a **5,75:1** (`#5B6472`), attribuzione da 3,1 a **5,16:1** (`#656E7B`). Il minimo AA e' 4,5;
  `--tp-text-muted` non lo raggiunge su questi fondi e qui non va usato.
- **bersagli da dito**: chip da 36 a 44px e campo di ricerca da 23 a 46px di altezza utile. L'area dei
  chip e del link "Tutte le zone" cresce con `::before`, non con il padding, cosi' la forma non cambia.
- **tocco sulla mappa**: sotto la soglia desktop **18 zone attive su 26 sono piu' piccole di 24px**
  (San Giovanni e' 25x19 anche a 1440). Su puntatore grossolano il click non cerca piu' il poligono
  esatto: prende la zona attiva **piu' vicina** al punto toccato, e porta il pannello in vista.
- **elenco dei quartieri**: sotto la soglia si fermava a 1633px di sezione, ora ne mostra 12 con un
  bottone per gli altri (il taglio lo fa il JS: senza JS restano tutti nel markup). Sezione a 1380px.
- **focus da tastiera**: contorno visibile di 3px su chip, bottoni e link; il campo lo segnala con
  l'anello interno su `:focus-within`.

[ATTENZIONE] La barra e' la stessa in due sezioni: in QA o in console va sempre ristretta alla sezione
(`.zones-map .tp-search__submit`), altrimenti si aziona quella della hero, che e' statica, e la ricerca
sembra non rispondere.

L'SVG non inquadra tutto il comune: la finestra si stringe sui 26 CAP censiti (`build-svg.py`, opzione
`--tutto` per tornare indietro). Tutto il comune ha un rapporto 1,05, quasi quadrato, e meta' della figura
sarebbe agro a nord dove non operiamo; sui CAP censiti il rapporto scende a 0,66. I CAP fuori finestra
restano disegnati e proseguono oltre il bordo.

[ATTENZIONE] I figli della griglia hanno `min-width: 0`: senza, un figlio largo quanto il suo contenuto
piu' lungo (era la select, con l'opzione "Casal Palocco / Axa / Dragona - 00126") allarga la sezione a
431px dentro un viewport da 360.

La vecchia sezione raster (`template-parts/zones-map.php`, ADR-002 rev. 3) e' ancora nel tema ma non e'
piu' renderizzata: la toglie TICKET-08 insieme ai suoi campi.

## 6. Asset video

| File | Campo | Uso | Produzione |
|---|---|---|---|
| `assets/video/hero-timelapse.mp4` (1 MB, 3 s, 1600px) | `hero_video` | sfondo hero da --tp-bp-desktop in su | `tools/hero-video-encode.sh <sorgente>` (accelera a 3 s, 1600px, crf 26) |
| `assets/video/hero-timelapse-720.mp4` (155 KB, 720px) | - (per convenzione) | sfondo hero sotto la soglia desktop | `ffmpeg -i hero-timelapse.mp4 -an -vf scale=720:-2:flags=lanczos -c:v libx264 -preset slow -crf 28 -movflags +faststart` |
| `assets/video/come-funziona.mp4` (5 MB, 12,6 s, all-intra) | `how_it_works_video` | schermo del telefono, scrubbed | `tools/screen-storyboard/render.js` (frame PNG via Chromium) + ffmpeg con `keyint=1` (comando nell'header di render.js) |
| `assets/video/come-funziona-zoom.mp4` (6 MB, 2560px) | `how_it_works_zoom_video` | zoom-through e card Servizi | `tools/zoom-video-encode.sh <sorgente>` (salva anche il primo frame in `come-funziona-zoom-frame.jpg`) |
| `assets/video/placeholder-dev.mp4` | - | non referenziato (residuo del 14/10) | - |

Sullo staging i tre campi puntano ai file del tema (URL `wp-content/themes/trovapulizie/assets/video/...`),
non alla libreria media: cosi' il `?ver=` del tema li invalida.

### Rendition per mobile (TICKET-20)

Sotto `--tp-bp-desktop` il box della hero e' alto 270px e il telefono e' largo 150: i file a misura desktop
sono fuori scala. Due meccanismi, diversi perche' diversa e' la sorgente:

- **foto del box**: `assets/img/hero-box-720.jpg` scelta dal CSS nel blocco sotto soglia (media query, nessun JS);
- **video del box**: `hero-video.js` legge `--tp-bp-desktop` e, sotto soglia, sostituisce nell'URL del campo WP
  `.mp4` con `-720.mp4`. **La convenzione e' il contratto**: non c'e' un campo WP per la rendition mobile, il
  nome del file la deve rispettare. Se la rendition manca, il listener sull'evento `error` toglie `src` e resta
  la foto: da mobile il file desktop non viene chiesto **in nessun caso**, nemmeno come ripiego.

[ATTENZIONE] Il poster di un `<video>` e l'`<img>` che mostra lo stesso file devono avere **lo stesso URL**,
query di versione inclusa: `?ver=` su uno solo dei due significa due richieste dello stesso file. Per questo
ogni URL che arriva da un campo WP passa da `tp_versioned_url()`, non solo gli asset del tema.

Texture dell'overlay 3D: `assets/img/tiles/` (tile dei servizi, `card-laura.png`). La card viene ritagliata dallo
screen Figma con `tools/screen-storyboard/cut-card.py`, che stampa anche il box da riportare in
`screen-timeline.js` (`CARD`) e nello storyboard.

## 7. Asset di fallback del tema

Quando il campo WP e' vuoto il template usa l'asset del tema (sempre via `tp_theme_asset()`, quindi versionato):

| Campo vuoto | Asset |
|---|---|
| `how_it_works_poster` | `assets/img/come-funziona-step1-poster.jpg` (360x780, 24 KB: lo schermo del telefono e' largo 146px su mobile e 294 su desktop) |
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

**Navbar sotto 1024px** (Figma `4018:965`): la barra scende a 65px ridefinendo il token `--tp-navbar-h`
(non l'altezza della barra: cosi' seguono box della hero, `scroll-margin-top` delle sezioni e posizione del
pannello). Restano logo, Registrati e l'hamburger; le voci di menu e Accedi stanno in `#nav-panel`, aperto
da `assets/js/nav-mobile.js` (chiusura con tocco fuori, Esc o scelta di una voce; il focus entra nel
pannello e torna all'hamburger). Le voci hanno una sola sorgente in `header.php` (`$tp_nav_links`).

**Hero sotto 1024px** (Figma `4018:973`): struttura diversa dal desktop. Li' il testo sta sopra la foto a
tutta sezione; qui il testo e' su bianco, la foto e' un box alto 270 in flusso sotto, e il telefono
(150x300) sporge dal bordo inferiore del box con un `margin-top` negativo di 168 (= 270 - 102 del Figma).
La search bar va su tre righe con il divisore orizzontale e Cerca a tutta larghezza.

**Come funziona sotto 1024px** (ADR-002 rev. 5): il contenuto della sezione resta sticky per circa due
viewport (`height: 300dvh`, non `vh`: la barra di Safari che entra e esce cambia `vh` e sfaserebbe il pin)
e dentro un unico telefono le tre schermate si alternano in crossfade, con i tre paragrafi in sincrono.
Niente video: solo opacita', quindi funziona anche in Low Power Mode e su Safari iOS, dove autoplay e seek
non sono affidabili. Le tre schermate stanno sovrapposte in `.hiw-seq` e si incrociano fra loro: la
cornice del telefono e' una sola, perche' due bezel scuri a meta' opacita' lascerebbero passare lo sfondo.

`assets/js/hiw-sequence.js` calcola il progresso da `scrollY` (nessuna libreria), tiene ferma ogni
schermata e dissolve solo nella parte centrale di ogni intervallo (`FADE`), e mette `hiw-seq-ok` su
`<html>`. Senza JavaScript o con `prefers-reduced-motion` la classe non arriva e restano i **tre blocchi
impilati** del Figma `4020:965` (badge 44, testo 300, telefono 158x320 per blocco): e' il fallback, ed e'
fedele al design.

Le tre schermate vengono da `tools/step-posters-export.sh`, che le estrae dal video desktop sui tempi di
`T` in `assets/js/screen-timeline.js` (0,5 / 4,5 / 8,5 s, mezzo secondo dentro ogni passo per non cogliere
la transizione). Cambiando i tempi del video desktop vanno rigenerate.

**Le altre sezioni sotto 1024px** (16/10, TICKET-14..19). Tutte in `style.css`, blocco `max-width: 1023px`
della rispettiva sezione; i numeri vengono dai frame mobile del Figma.

- **Servizi popolari** (`4021:965`): i sei tab vanno a capo su tre righe; nella card l'ordine diventa
  titolo, testo, visual, "Cosa include" con i chip, pill e CTA a tutta larghezza. I due pannelli
  (`.services__includes`, `.services__desc`) diventano `display: contents`, cosi' i loro figli sono figli
  diretti della card e si riordinano con `order` senza duplicare markup; cosi' spariscono anche i riquadri
  grigi, che nel Figma mobile non ci sono. Distanze tutte a 14px. Due trappole chiuse: il rapporto del
  visual sta sull'immagine e non sul contenitore (con `height: 100%` dentro un contenitore di altezza
  automatica la percentuale non si risolve e l'immagine impone la propria altezza), e il visual prende
  `flex: 0 0 auto` perche' le card stanno tutte nella stessa cella di griglia e la riga e' alta quanto la
  piu' alta: col `flex: 1 1 auto` del desktop il visual si prendeva tutto lo spazio in eccesso.
  [ATTENZIONE] L'ordine del DOM resta quello desktop: a schermo l'ordine e' quello del Figma, ma uno
  screen reader legge ancora prima i chip e poi il titolo. Per sistemarlo servirebbe spezzare il pannello
  descrizione in due, e su desktop i due pezzi diventerebbero due riquadri grigi separati.
- **Social proof** (`4023:1023`): niente marquee (animazione `none`, secondo set nascosto), tre card in
  colonna larghe 350. Dentro la card la citazione sta sopra e avatar + autore sotto sulla stessa riga: il
  corpo diventa `display: contents` e la card una griglia a due aree. Il contatore "01 / 06" appartiene al
  marquee desktop e qui e' nascosto.
- **Dove siamo attivi** (`4023:1007`): il riquadro della mappa e' 350x220, rapporto diverso da quello del
  raster (1280x714). Gli overlay sono in percentuale, quindi al cambio di rapporto si sarebbero staccati
  dalla mappa: per questo mappa e overlay stanno dentro `.zones__map-stage`, che tiene sempre il rapporto
  dell'immagine ed e' largo 112,7% (= 220 x 1280/714 / 350), cioe' un `object-fit: cover` applicato a
  tutto il gruppo. Su desktop i due rapporti coincidono e lo stage e' grande quanto il riquadro.
- **FAQ** (`4024:1007`): titolo centrato e lista accordion; la card di contatto resta sotto la lista
  (decisione del 15/10, nel Figma mobile non c'e'). Per ottenerlo senza invertire l'ordine di lettura la
  card e' stata spostata **dopo** la lista nel markup e `.faq__inner` e' passato da flex a **griglia**:
  su desktop la card torna in colonna 1, riga 2, sotto il sottotitolo, e la lista occupa la colonna 2 su
  entrambe le righe.
- **CTA finale** (`4025:1007`): card 350x322 con la mascotte (`4025:1015`) in posizione assoluta che
  sporge a sinistra oltre il bordo della card e del viewport. La sporgenza e' tagliata da `overflow-x:
  clip` sulla sezione: a differenza di `overflow: hidden` non taglia l'ombra della card in verticale, e
  non genera scroll orizzontale.
- **Footer** (`4026:1007`): marchio a tutta larghezza, le tre colonne su una riga sola (prendono la
  larghezza del contenuto con `flex: 0 1 auto`, mentre marchio e blocco Cleaner CTA occupano l'intera
  riga), divisore, copyright e link legali. Sotto i 390px il corpo scende a 13px e il passo fra le colonne
  a 12: e' quanto basta a tenere le tre colonne su una riga a 360 senza troncare "Come guadagnare".

**Come funziona sotto 1024px: lo stepper** (16/10). Prima i tre testi si alternavano nello stesso riquadro,
uno per volta e centrati: non si capiva a che punto si fosse ne' quanti passi ci fossero. Ora sotto 1024px
c'e' lo stesso stepper del desktop — i tre passi tutti visibili, numero di fianco al titolo, allineati a
sinistra, linea di collegamento che si colora fino al passo corrente, e solo il passo in corso che apre il
proprio paragrafo. `hiw-sequence.js` non tocca piu' l'opacita' dei testi: calcola il passo corrente dal peso
maggiore e mette `is-active` / `is-done`; le schermate dentro al telefono continuano a incrociarsi.
Il riquadro dei passi tiene un'altezza fissa (236px) perche' il telefono sotto non salti quando cambia la
lunghezza del paragrafo, e titolo e sottotitolo sono allineati a sinistra come lo stepper.

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

## 12. Limiti noti (17/10/2026, ver 0.32.1)

- Mobile e tablet: la Home e' completa (TICKET-10..20). Resta TICKET-21: prova su iPhone reale (Low Power Mode
  e barra dinamica di Safari non sono riproducibili in headless). Ticket in `spec/tickets/` nel vault.
- Gli screen PNG a piena risoluzione non sono piu' caricati dalla pagina (restano per lo storyboard).
  Peso trasferito a 390px: 1508 KB (15/10) -> 1331 (16/10) -> **306 KB** (17/10, TICKET-20).
- Il peso **desktop** non e' stato ottimizzato (682 KB, di cui il video hero a piena misura): fuori dal
  perimetro di TICKET-20.
- Contenuti segnaposto: recensioni, badge Google, FAQ 2-5, servizi Uffici/Condomini, URL social, link
  Prezzi/Accedi/Registrati, pagine linkate dal footer non esistenti.
- Search bar della hero statica: il widget di ricerca reale e' il widget JS di ADR-001.
- `placeholder-dev.mp4` tracciato ma non usato.
