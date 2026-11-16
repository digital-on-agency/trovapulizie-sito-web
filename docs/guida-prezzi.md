# Guida prezzi (`page-guida-prezzi.php`)

Pagina `/roma/prezzi-pulizie/` (figlia di `/roma/`): prezzo delle pulizie straordinarie a Roma, con tabella delle fasce per
50, 80, 100 e 120 mq, fattori che fanno cambiare il prezzo, calcolatore, form lead e FAQ. TICKET-96 della
`SPEC-crescita-organica-fase-b` (US-01) nel vault: `clients/trovapulizie/projects/sito-web/spec/`. Una sola pagina per tutti
i servizi sgrosso (decisione di Guido del 16/11).

## File

| File | Ruolo |
|---|---|
| `page-guida-prezzi.php` | template (Template Name: Guida prezzi). Testi nel template, senza campi Carbon: cambiano con un deploy |
| `inc/prezzi.php` | formula unica lato PHP (`tp_sgrosso_stima()`, `tp_sgrosso_coef()`), righe e metrature della tabella |
| `template-parts/price-guide-table.php` | tabella: una riga per servizio (link alla landing), una colonna per metratura |
| `template-parts/sgrosso-estimator.php`, `lead-form.php`, `value-props.php`, `faq-accordion.php`, `cta-form.php`, `related-services.php`, `sticky-cta.php` | componenti delle landing, riusati senza copie |
| `inc/seo.php` | title, description, Service (offerta 200-700 EUR come le landing), voce in `tp_service_pages()` (blocco Altri servizi, footer, BreadcrumbList) |
| `tools/qa-guida-prezzi.js` | QA della pagina; il flusso del form e' di `tools/qa-sgrosso.js` con `EXPECT_LANDING` / `EXPECT_PATH` |

## Formula unica

`assets/js/sgrosso-estimator.js` (`stima`) e `tp_sgrosso_stima()` implementano la stessa formula: `base = 200 + mq x 2,2`,
`min = round10(base + extra x 25)`, `max = round10(base x 1,35 + extra x 40)`. Le cifre della tabella non sono scritte in
nessun punto: le calcola `tp_guida_prezzi_righe()` e ogni cella porta `data-mq`, `data-min`, `data-max`. I coefficienti di
default stanno in `tp_sgrosso_coef()` e sono quelli che `sgrosso-estimator.php` scrive nei data-attribute letti dal JS (e che
`page-sgrosso.php` usa come ripiego dei campi `sg_calc_*`).

[ATTENZIONE] Cambiare la formula in un posto solo la rompe: serve cambiarla in `sgrosso-estimator.js` **e** in
`tp_sgrosso_stima()`/`tp_sgrosso_coef()`. `tools/qa-guida-prezzi.js` confronta ogni cella con `window.tpStimaSgrosso` nel
browser e fallisce se divergono. Con `COMPARE_URL=<landing viva>` confronta anche la tabella con il calcolatore della landing
(utile se qualcuno compila a mano i campi `sg_calc_*` di una landing: la guida usa sempre i default).

La formula non dipende dal tipo di servizio, quindi le cinque righe della tabella sono uguali. La pagina lo dice (nota
`pg-table__note--same`, mostrata solo se `tp_guida_prezzi_uguali()` e' vero) invece di inventare differenze.

## Assegnazione del template

Il file NON si chiama `page-prezzi-pulizie.php`: col nome dello slug WordPress lo applicherebbe da solo lasciando
`_wp_page_template` vuoto, e `is_page_template()` (script, title, JSON-LD) non partirebbe in silenzio. Il template va
assegnato a mano: `wp post create --post_type=page --post_title="Prezzi pulizie" --post_name=prezzi-pulizie
--post_parent=<ID di /roma/> --post_status=publish --page_template=page-guida-prezzi.php`. La sitemap nativa di WordPress la
include da sola (solo la thank-you e' esclusa).

## Contenuti e vincoli

Solo fatti gia' pubblicati sulle landing: sopralluogo gratuito e senza impegno, prezzo fisso concordato prima di iniziare,
prodotti professionali inclusi. Nessun numero di clienti, ditte o recensioni, nessuna promessa di tempi o garanzie, nessuna
immagine nuova (hero e mascotte delle landing Sgrosso), nessun coupon. Eventi dataLayer con `landing: 'prezzi-pulizie'`;
tipo di intervento preselezionato quello di sgrosso casa.

Senza JavaScript: tabella, testi e link sono HTML lato server; un `<noscript><style>` scoperto mostra fattori e risposte
delle FAQ (altrimenti il CSS dei componenti li tiene a opacita' 0 finche' `reveal.js` non gira).

## QA

`node tools/qa-guida-prezzi.js <chrome> <url> [larghezza]` (32 controlli: title, H1, tabella contro la formula del browser,
calcolatore, form, FAQPage/Service/BreadcrumbList, link interni, contenuti vietati, layout, JavaScript spento) e
`EXPECT_LANDING=prezzi-pulizie EXPECT_PATH=/roma/prezzi-pulizie/ EXPECT_TIPO=post-ristrutturazione node tools/qa-sgrosso.js
<chrome> <url> [larghezza]` (form in tre passi con API simulata, nessun lead reale). `tools/qa-mobile.js` riconosce la pagina
(`main.pg`). Dopo il deploy: `python3 tools/qa-sitemap-titles.py` deve restare verde (title <= 60, un solo `<title>`).
