# Pagina Diventa cleaner

URL `/diventa-cleaner/` (post 35). Template `page-cleaner-landing.php` ("Diventa cleaner"), assegnato a mano.
Design: Figma `6752:895`, pagina "Sito Web" del file `UiW4TPgvqCODW5bhyFV5eK`. PRD e ticket nel vault:
`clients/trovapulizie/projects/sito-web/spec/SPEC-pagina-diventa-cleaner.md` (TICKET-51..57). Mobile senza Figma:
regole nella SPEC, verificate con `tools/qa-mobile.js`.

## Perche' il file non si chiama page-diventa-cleaner.php

Col nome dello slug WordPress applica il file da solo, ma lascia `_wp_page_template` a `default`: la scheda dei
campi (`->where( 'post_template', ... )`) non compare e `is_page_template()` e' falso, quindi niente script.
Stessa trappola di `page-hub-roma.php`.

## Sezioni, template-part e campi

Scheda "Diventa cleaner — Sezioni" (prefisso `dc_`, `inc/carbon-fields.php`). Ogni testo ha il default nel
template; un campo compilato vince (`tp_dc()`, numeri con `tp_dc_num()`).

| Sezione | Ancora | Template-part | Campi |
|---|---|---|---|
| Hero con telefono e due card flottanti | `#hero` | nel template + `phone-mockup.php` | `dc_hero_*`, `dc_float*` |
| Trust strip | - | `trust-bar.php` | `dc_trust_items` |
| Come funziona con schermate | `#come-funziona` | `steps-cards.php` (arg `screen` per passo, modificatore `sg-steps--screens`) | `dc_hiw_*` |
| Bento dell'app | `#app` | `app-bento.php` | `dc_app_*` |
| Quanto guadagni | `#guadagni` | `earnings-calculator.php` + `assets/js/earnings-calculator.js` | `dc_calc_*` |
| Per chi e' | `#per-chi` | `audience-cards.php` | `dc_who_*` |
| Requisiti | `#requisiti` | `requirements.php` | `dc_req_*` |
| Partner (solo se compilato) | - | `partners-strip.php` | `dc_partners*` |
| FAQ | `#faq` | `faq-accordion.php` | `dc_faq_*` |
| CTA finale | `#registrati` | `cta-final.php` | `dc_cta_*` |
| Barra fissa mobile | - | `sticky-cta.php` (osserva `.dc-hero`) | - |

Navbar: `header.php` con `links` e `cta_*` passati da `get_header( '', $args )`: voci verso le ancore locali,
"Registrati gratis" verso il signup cleaner. Le altre pagine passano niente e restano come prima.

Le liste "Titolo | testo" (requisiti, righe della stima) si leggono con `tp_pipe_lines()` (`functions.php`).

## Destinazioni e tracking

- Signup: `tp_app_url( 'cleaner-signup' )` (stessa rotta del live React).
- Imprese e card di contatto delle FAQ: `https://wa.me/393200980144` con testo precompilato, nuova scheda.
- `main.dc[data-landing="diventa-cleaner"]`. I link con `data-cta-position` emettono `marketing_cta_click
  {landing, cta_position, target}` (tp-track.js); i link wa.me `marketing_click_whatsapp` con la stessa posizione.
  Posizioni: `navbar`, `hero`, `guadagni`, `per-chi`, `final`, `sticky`.
- Title e description: quelli del live (`inc/seo.php`), sovrascrivibili dalla scheda SEO.

## Calcolatore

`stima = arrotonda ai 50 euro (tariffa x ore a settimana x 4,33)`, lorda, senza percentuale di commissione.
Valori iniziali resi dal PHP (sono anche quelli visibili senza JavaScript); slider e chip restano `[hidden]` finche'
lo script non li attiva. Range e ore sono campi: e' un esempio, non un dato di piattaforma.

## Schermate dell'app

`assets/img/cleaner/*.webp`, gia' ritagliate sulla parte da mostrare (8-33 KB l'una):
`dashboard-hero` (540x1168, fascia verde di 88px sotto la Dynamic Island), `step-*` (480x615), `tile-*`.
Sorgente: pagina Figma "Ditta — Mobile" (serie D, design corrente dell'app), esportate a 2x con "NettaPro Roma" al
posto del segnaposto "{Ragione Sociale}" e poi ripristinate sui mock. Prima del cutover vanno confrontate con l'app
vera: se l'app live ha un aspetto diverso, la pagina promette un'interfaccia che al signup non c'e'.

## QA

- `tools/qa-mobile.js <chrome> <url>`: riconosce `main.dc` (sezioni attese, barra fissa nascosta al caricamento,
  card flottanti solo su desktop, calcolatore attivato).
- `tools/qa-cleaner.js <chrome> <url> [larghezza]`: formula, slider e chip, eventi delle CTA, link, partner, FAQPage.
- `tools/qa-fullpage.js <chrome> <url> 1440 out.png 14000`: screenshot per il confronto col Figma.

## Limiti noti

- Testi delle FAQ 2-6 scritti come default: da rileggere (pagamenti, annullamenti).
- Claim da confermare: "profilo in pochi minuti", "attivazione via email", "referente sui lavori grandi".
- Sezione partner nascosta finche' i partner non danno l'ok ai nomi.
