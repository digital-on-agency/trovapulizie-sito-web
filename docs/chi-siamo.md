# Pagina Chi siamo (`/chi-siamo/`)

Chi c'e' dietro Trovapulizie, come lavora, il team, la storia e i dati della societa'. Design: Figma `6795:895`
(pagina "Sito Web") con la hero rifatta il 27/10 (`6814:895`, manifesto chiaro con mappa); note `6791:1054`.
PRD e ticket nel vault: `clients/trovapulizie/projects/sito-web/spec/SPEC-pagina-chi-siamo.md` (TICKET-66..69).

## File

| File | Ruolo |
|---|---|
| `page-about.php` | template "Chi siamo": default dei testi, ordine delle sezioni, posizioni degli eventi |
| `template-parts/about-hero.php` | hero a manifesto e mappa dei CAP colorata lato server |
| `template-parts/about-why.php` | Perche' esistiamo: testo a sinistra, card a destra |
| `template-parts/audience-cards.php` | Cosa facciamo (riuso di Diventa cleaner, badge resi come eyebrow) |
| `template-parts/about-rules.php` | Come lavoriamo: quattro card menta |
| `template-parts/about-team.php` | Il team: con foto card grande, senza foto card compatta con l'iniziale |
| `template-parts/about-timeline.php` | La nostra storia: tappe su una linea, l'ultima piena |
| `template-parts/company-contacts.php` | Dati aziendali e contatti (blocco di Contatti con `order => 'company'`) |
| `template-parts/cta-final.php` | CTA finale con la mascotte |
| `inc/carbon-fields.php` | scheda "Chi siamo — Sezioni" (prefisso `ab_`, otto tab) |
| `inc/seo.php` | title, description, `Organization` condiviso con Contatti piu' `foundingDate` e `founder` |
| `assets/img/about/` | foto dei fondatori, WebP 440x528 dal documento investitori |
| `style.css` | blocco 16, classi `ab-*` |

[ATTENZIONE] Il template si chiama `page-about.php`, non `page-chi-siamo.php` (trappola dello slug). Sulla pagina
(post 52 sullo slot) il template e' assegnato a mano.

## Mappa della hero

L'SVG dei CAP del tema (`assets/img/zones-map-cap.svg`) e' incluso inline e colorato in PHP: i CAP con stato
`active` o `future` in `assets/data/coverage.fixture.json` (la stessa copertura della Home) diventano
`data-state="covered"`. Il viewBox si restringe sulle zone coperte; `preserveAspectRatio` e' `meet` perche' le zone
arrivano fino a Ostia e un ritaglio le taglierebbe. Nessun JavaScript.

Trappole:
- I CAP con un nome hanno `data-name` fra `data-cap` e `data-state`: la regex deve tollerare attributi intermedi,
  altrimenti salta proprio i CAP coperti e la mappa esce grigia senza errori.
- Il contorno della citta' (`.zones-map__outline`) e' disegnato sopra i CAP: un selettore generico `path` lo riempie
  e copre le zone. Il colore va solo su `.zones-map__caps path`.

Quando arrivera' l'endpoint reale della copertura (ADR-003) la hero va collegata alla stessa fonte della Home.

## Contenuti

Ogni testo ha un default nel template; un campo compilato vince (`tp_ab()`). Le liste sono textarea "Titolo | testo"
(storia: "Data | Titolo | testo"); il team e' un campo complesso con foto opzionale. Numero WhatsApp ed email nel
blocco contatti si leggono dalla pagina Contatti (`ct_whatsapp`, `ct_email`), come il `ContactPoint`: un solo posto
da aggiornare.

## Tracking

`data-landing="chi-siamo"`. Posizioni: `hero`, `hero_contatti`, `cosa_facciamo_cerca`, `cosa_facciamo_partner`,
`chi_siamo_contatti` (+ `_numero`), `final`.

## QA

```
CDP_PROFILE=<cartella vuota> node tools/qa-about.js <chrome> <url>/chi-siamo/ [1440|1023|390] [out.png]
node tools/qa-mobile.js <chrome> <url>/chi-siamo/
```

Sullo slot l'HTML senza parametri puo' arrivare dalla cache anche dopo il purge: per la QA subito dopo un deploy
aggiungere `?v=<numero>` all'URL.

## Aperti

Cognomi, foto e consenso di Niccolo' e Mattia; rilettura di bio, claim e della tappa di agosto; voce nel menu.
