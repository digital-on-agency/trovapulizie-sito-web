# Landing zanzare (`/roma/zanzare-disinfestazione/`)

Template `page-landing-zanzare.php` (post 13, figlia di `/roma/`). Design: Figma `4849:884`; testi, claim, prezzi,
recensioni e foto dal live React (`ZanzareDisinfestazionePage.tsx` su master), portati as-is (decisione Guido 27/10).
PRD e ticket nel vault: `clients/trovapulizie/projects/sito-web/spec/SPEC-landing-zanzare.md` (TICKET-73..76).

## Composizione

`main.sgrosso.b2b.zz`: eredita gli stili di Sgrosso e B2B. Sezioni: hero con il form, `trust-bar` (5 voci),
`value-props` (Autorita', quattro colonne solo qui), `zanzare-tigre.php` (nuova), `scenario-tabs` (Per chi, foto
singola, CTA e nota per tab), `service-cards.php` (nuova, con banner condomini), `steps-cards`, `reviews-marquee`
(griglia a stelle con le foto del live), `pricing-tiers.php` (nuova), `faq-accordion` con la card dei fondatori,
`cta-form` col secondo form, `sticky-cta` doppia. CSS nel blocco 19.

## Form

`lead-form-b2b.php` + `initB2B` con le opzioni nuove (i default lasciano la B2B com'era): "Chi sei" con
`chooser_types` (Privato = `b2c`, Condominio e Attivita' = `b2b`, come `toZanzareLead`), `note_prefix`
"Disinfestazione zanzare · Chi sei: ", email facoltativa, niente superficie ne' consenso marketing, coupon come solo
testo, bottoni Chiama e WhatsApp, riga di urgenza, conferma in pagina. Le CTA con `data-preselect` (tab Per chi,
banner) scelgono la voce nel form di arrivo (`lead-form.js`, listener in fondo al file).

## Contenuti

Scheda "Landing zanzare — Sezioni" (prefisso `zz_`): promo bar (fuori stagione conviene cambiarla), telefono, hero,
trust, form, autorita', zanzara tigre, banner condomini, i due prezzi, FAQ. Il resto e' nel template.
Claim sanitari e prezzi del live (patentino, prodotti registrati, garanzia 15 giorni, da €119 / da €449, "4,9 su
Google · 32 recensioni") restano da verificare contro i dati reali (memoria `project_tp_landing_zanzare`).

## QA

```
CDP_PROFILE=<cartella vuota> node tools/qa-zanzare.js <chrome> <url>/roma/zanzare-disinfestazione/ [1440|390]
node tools/qa-mobile.js <chrome> <url>/roma/zanzare-disinfestazione/
```

API simulata con `Fetch`: nessun lead reale. Con la macchina carica (load average oltre 10 su 2 core, 27/10) le
QA headless danno falsi rossi diversi a ogni giro (DOM letto a meta', navigazioni sovrapposte): ripetere a carico
basso prima di cercare un bug.
