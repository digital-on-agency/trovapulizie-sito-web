# Landing Sgrosso (page-sgrosso.php)

Cinque pagine su un template: `/roma/sgrosso-casa/` (generica, con tab e calcolatore) e quattro verticali per
ad group (`sgrosso-post-ristrutturazione`, `sgrosso-trasloco`, `sgrosso-fine-affitto`, `sgrosso-prima-pulizia`).
Decisioni, PRD e ticket nel vault: `clients/trovapulizie/projects/sito-web/adr/ADR-004-form-lead-nativo-nel-tema.md`,
`spec/SPEC-landing-sgrosso.md`, `spec/tickets/TICKET-37..50`. Contenuti e misure dal live React
(`front-end/src/pages/marketing/LandingSgrossoSopralluogo.tsx`); Figma `4364:6628` desktop, `4364:7087` mobile.

## File

| File | Ruolo |
|---|---|
| `page-sgrosso.php` | template (Template Name: Landing Sgrosso), legge i campi `sg_*`, compone gli `$args` delle parti nell'ordine del live |
| `inc/sgrosso-variants.php` | testi di default per variante (eyebrow, H1, title, description, H2 di sezione, prima FAQ, immagine hero, scenario) e `tp_sg_text()` |
| `header-landing.php` + `template-parts/head.php` | testata delle landing (promo bar, navbar senza menu, telefono, CTA con testo corto sotto soglia); `head.php` e' condiviso con `header.php` |
| `template-parts/lead-form.php` + `assets/js/lead-form.js` | form in tre passi e form atomico (calcolatore), chiamate dirette all'API, autocomplete HERE |
| `template-parts/trust-bar.php`, `comparison.php`, `scenario-tabs.php` (+ `scenario-tabs.js`), `steps-cards.php`, `sgrosso-estimator.php` (+ `sgrosso-estimator.js`), `coupon-band.php`, `cta-form.php` | sezioni proprie della landing |
| `template-parts/reviews-marquee.php` (`layout: grid`, `card: stars`), `faq-accordion.php`, `sticky-cta.php` (`variant: dual`, `data-watch`) | componenti della Home riusati con nuovi `$args` |
| `page-thank-you.php` + `assets/js/thank-you.js` | thank-you, noindex e fuori sitemap |
| `inc/tracking.php` + `assets/js/tp-track.js` | iubenda, GTM, Clarity, helper dataLayer (tutto il tema) |
| `inc/seo.php` | title/description per variante, JSON-LD Service |

## Campi (scheda "Landing Sgrosso — Sezioni", prefisso `sg_`)

Variante, Testata, Hero, Form, Trust bar, Confrontiamo, Quando serve, Come funziona, Quanto costa, Recensioni e
coupon, FAQ, CTA finale. Regola unica: campo vuoto = default (della variante o del live), campo compilato vince.
Theme Options: Integrazioni (`tp_api_base`, `tp_here_key`), Tracking (GTM, iubenda, Clarity).

## Form lead: contratto

Vedi SPEC §2 "Vincoli verificati" e `SPEC-landing-sgrosso-conversione-mobile` (ADR-004, TICKET-83..85). Ordine dei passi
dalla versione 0.66.0:

1. **La casa**: tipo di intervento (preselezionato dalla variante), metri quadri 10-500, indirizzo. Nessun dato personale,
   nessuna chiamata; evento `preventivo_sgrosso_step {step:1, step_name:'casa'}`.
2. **Contatto**: nome, cellulare, email facoltativa, privacy. In cima la stima indicativa, calcolata con la formula
   unica del calcolatore (`window.tpSgrossoStima`, `sgrosso-estimator.js`; base = 200 + mq x 2,2; min = round10(base +
   extra x 25); max = round10(base x 1,35 + extra x 40)). All'invio: `POST /api/leads` con `contactOnly:true` ->
   `{contattoId, token}`, poi `POST /api/leads/:contattoId/opportunita` con `token` -> `{opportunitaId, token}`. Se il
   primo POST fallisce si resta al passo 2 con il messaggio; se fallisce solo il secondo il Contatto resta e si passa
   comunque al passo 3. Qui scatta `preventivo_sgrosso_submitted {fase:'contatto'}`: **e' la conversione Ads**, non piu'
   l'invio finale.
3. **Dettagli** (facoltativo): bagni, tipo di appartamento, pavimento, sporco, urgenza, foto. "Invia i dettagli" fa
   `PATCH /api/leads/:opportunitaId` con `step:3, final:true`, foto base64 (innesca il PDF) ed emette
   `marketing_form_submit {form:'dettagli'}`; "Salta" porta alla thank-you senza chiamate. Senza Opportunita' (secondo
   POST fallito) il PATCH non parte. Chi salta il passo 3 non riceve il PDF.

Valori delle opzioni e chiavi di `datiPulizia` sono quelli esatti del backend (`typecast:false`): non rinominarli.
Honeypot `website` compilato = risposta ok senza record (usato dai test). CORS: l'allowlist del backend accetta
`www.trovapulizie.it` e `staging.trovapulizie.it`, non lo slot Hostinger.

Percorso verso il prezzo: i pulsanti "Calcola il preventivo gratis" (testata desktop, Confrontiamo, Quando serve) portano
a `#quanto-costa`; il calcolatore ha "Prenota il sopralluogo con questi dati", che precompila il form
(`window.tpLeadFormPrefill(form, {tipo, mq, extra})`), scorre a `#form` (o `#form-bottom`), mette il focus
sull'indirizzo ed emette `marketing_cta_click {cta_position:'calcolatore'}`. Ancore alias per i sitelink Ads: `#prezzi`
(dentro `#quanto-costa`) e `#cosa-include` (dentro `#quando-serve`). Schede di confronto e stelle della hero sono link
(`#quanto-costa`, `#recensioni`); la CTA "Sopralluogo gratuito" della testata mobile, con il form gia' in vista, porta il
focus al primo campo del passo corrente (`data-form-focus`, classe `.is-attention`).

Eventi dataLayer, identici alla SPA: `preventivo_sgrosso_step` `{step, step_name casa|contatto, landing, cta_position,
tipoPulizia}`, `preventivo_sgrosso_submitted` (con `fase:'contatto'`), `marketing_form_submit {form:'calcolatore'|'dettagli'}`,
`marketing_click_to_call/whatsapp/to_form`, `marketing_cta_click`, `marketing_thankyou_create_account_click`. Il parametro
di posizione e' `cta_position`, mai `source`. I pulsanti "Calcola" non puntano piu' a `#form`: non emettono piu'
`marketing_click_to_form`.

## QA

`tools/qa-mobile.js` (6 larghezze, riconosce le landing), `tools/qa-sgrosso.js` (form in tre passi con API simulata via Fetch), `tools/qa-sgrosso-cta.js` (collegamenti interni, calcolatore -> form, ancore),
`tools/qa-lead-contract.js` (rotte vere sullo staging API; `--real <email>` crea record da archiviare),
`tools/qa-tracking.js`, `tools/qa-seo.py`, `tools/qa-fullpage.js`. Dettagli in `tools/README.md`.

## Limiti noti

- La cache LiteSpeed tiene i 404 per un'ora: un URL chiesto prima che la pagina esista resta 404 anche dopo il purge.
- Recensioni e foto prima/dopo sono segnaposto (fittizie/generate): da sostituire prima del cutover.
- Il prefill `?email=` sul signup dell'app richiede una modifica lato portale.
