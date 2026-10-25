# Landing Pulizie commerciali B2B (page-landing-b2b.php)

URL `/roma/sgrosso-commerciale/` (post 12, figlia di `/roma/`). Template `page-landing-b2b.php` ("Landing B2B"),
assegnato a mano. Design: ridisegno Figma `6780:895` nella pagina "Sito Web" del file `UiW4TPgvqCODW5bhyFV5eK`
(nota `6783:895`, stati dei tab dei settori `6784:901/951/1001`); testi e claim dal live React
(`front-end/src/pages/marketing/SgrossoCommercialeB2BPage.tsx` su `master`). PRD e ticket nel vault:
`clients/trovapulizie/projects/sito-web/spec/SPEC-landing-sgrosso-commerciale.md` (TICKET-58..62). Mobile senza
Figma: regole nella SPEC, verificate con `tools/qa-mobile.js`.

## Perche' il file non si chiama page-sgrosso-commerciale.php

Col nome dello slug WordPress applica il file da solo ma lascia `_wp_page_template` vuoto: la scheda dei campi
(`->where( 'post_template', ... )`) non compare e `is_page_template()` e' falso, quindi niente script. Stessa trappola
di `page-hub-roma.php` e `page-cleaner-landing.php`.

## File

| File | Ruolo |
|---|---|
| `page-landing-b2b.php` | template, legge i campi `b2b_*` con `tp_b2b()` (default nel template, campo compilato vince), compone gli `$args` delle parti |
| `template-parts/lead-form-b2b.php` + `assets/js/lead-form.js` (`initB2B`, `[data-lead-b2b]`) | form a un passo: chip del tipo di attivita' (Altro apre un campo di testo), superficie facoltativa, zona, telefono con prefisso, consensi, honeypot; una `POST /api/leads` tipo b2b, conferma inline |
| `template-parts/scenario-tabs.php` (+ `scenario-tabs.js`) | Settori: componente "Quando serve" dello Sgrosso con `image`/`image_alt` (foto singola) e `chips` per scenario, `cta_position` |
| `template-parts/works-gallery.php` | Lavori: confronto prima/dopo (`.sg-compare-media`, inizializzato da `scenario-tabs.js` anche fuori dai tab) con card di testo, galleria |
| `template-parts/audience-cards.php` | Formule una-tantum / contratto: card "Per chi e'" di Diventa cleaner con `cta_variant` (ghost sulla chiara, primary sulla scura) |
| `template-parts/value-props.php` | Perche' noi: quattro card con icona (`assets/icons/ic-user-check`, `ic-clock`, `ic-shield-check`, `ic-spray`) |
| `template-parts/reviews-marquee.php` (`layout: grid`, `card: stars`, `avatar_mode: initials`) | recensioni con le iniziali del nome al posto della foto |
| `template-parts/cta-form.php` (`form_part`) | CTA finale con la seconda istanza del form B2B (`#form-bottom`), senza mascotte (`image` vuota) |
| `header-landing.php`, `trust-bar.php`, `steps-cards.php`, `faq-accordion.php`, `sticky-cta.php` (`dual`) | riuso senza modifiche |
| `inc/seo.php` | title e description del live, JSON-LD `Service` senza `offers` |
| `assets/img/b2b/*.webp` | le dieci foto del live convertite (hero con rendition 720, settori, vetrate prima/dopo, tre lavori) |

Scheda Carbon "Landing B2B — Sezioni" (prefisso `b2b_`, `inc/carbon-fields.php`): Testata, Hero, Form, Trust strip,
Settori (complex con foto e chip), Lavori, Formule, Perche' noi, Come funziona, Recensioni, FAQ, CTA finale.

## Sezioni e ancore

hero `#hero` (form `#form`) -> trust strip -> `#settori` -> `#lavori` -> `#formule` -> `#perche-noi` ->
`#come-funziona` -> `#recensioni` -> FAQ -> `#ultimo-passo` (form `#form-bottom`) -> footer globale. Barra fissa
mobile osserva `#form`.

## Form e contratto

Payload (SPEC §2): `{tipo:'b2b', telefono:'+39'+cifre, zona, note:<tipo di attivita'>, superficieMq?,
consentPrivacy:true, consentMarketing, honeypot, pageUrl, utm*, gclid?}`. Telefono per +39: fisso (`0...`) o
cellulare (`3...`), 6-11 cifre, stessi filtri anti-numeri finti dello Sgrosso (`phoneOkAny`); il backend non valida
il formato. Al 201 i campi spariscono e resta la conferma con il telefono; al 400 il messaggio del backend sopra il
bottone; rete/5xx il messaggio generico con "Chiama ora". Nessuna thank-you.

Eventi: `marketing_form_submit {landing:'sgrosso-commerciale', form:'b2b', cta_position:hero|bottom}`;
`marketing_click_to_form {landing, target, cta_position}` dai bottoni verso il form (`settori`, `formule`; da questa
pagina `tp-track.js` porta `cta_position` anche su questo evento), `marketing_click_to_call` dalla testata e dalla
barra fissa.

## Trappole

- Le regole mobile dello Sgrosso danno `order: 1` a ogni `.sg-compare-media` (dentro i tab la foto va prima dei
  chip): nella sezione Lavori la card di testo finiva sopra il confronto. `.b2b-works__media { order: 0 }`.
- Le quattro voci della trust strip sono lunghe: con il padding dello Sgrosso (56px) o della pagina cleaner (32px) la
  quarta andava a capo a 1440. Qui 22px.
- Vantaggi e galleria entrano a cascata (`.is-in` dalla sezione): ombra e bordo degli hover stanno nella stessa
  dichiarazione di transizione, non in una regola separata (vedi `docs/landing-cleaner.md`).

## QA

- `tools/qa-b2b.js <chrome> <url> [larghezza]`: struttura e testi, validazioni, payload della POST (API simulata via
  `Fetch`), conferma inline, 400 e rete caduta, eventi, tab da tastiera, confronto, CTA; sotto 1024 anche barra fissa.
- `tools/qa-mobile.js <chrome> <url>`: riconosce `main.b2b` (sezioni attese e i due form).
- `tools/qa-lead-contract.js [apiBase]`: payload b2b con honeypot verso l'API (`201 skipped`, nessun record).
- `tools/qa-fullpage.js <chrome> <url> 1440 out.png 12000`: confronto col frame Figma `6780:895`.

## Limiti noti

- Recensioni del live, fittizie: da sostituire con recensioni vere o togliere prima del cutover.
- Claim da confermare: "assicurazione RC" (card Tutto in regola, FAQ), "Ti richiamiamo entro 1 ora", "Sempre lo
  stesso referente". Testi dei settori Negozi, Supermercati e Palestre scritti sul live, da rileggere.
- Nessun autocomplete sulla zona (il live non lo ha). La prova del form vero si fa prima del cutover con host
  override su `www.trovapulizie.it`.
