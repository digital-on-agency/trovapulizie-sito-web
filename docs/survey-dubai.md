# Survey Dubai v2 (OnField), `/survey-dubai/`

Pagina autonoma del tema che raccoglie le risposte del survey per le imprese di pulizia di Dubai. Sorgente di testi,
domande e diramazioni: vault `clients/trovapulizie/projects/gestionale-saas/survey-dubai-v2.md` (approvata il 30/10/2026).
Marchio OnField, titolare del trattamento Trovapulizie S.R.L. (consenso con link a `/privacy/`). Lingua: inglese.

## File

| File | Cosa fa |
|---|---|
| `page-survey.php` | Template "Survey OnField". Senza navbar ne' footer. Il nome non e' `page-survey-dubai.php` di proposito (WordPress lo applicherebbe da solo allo slug) |
| `inc/survey.php` | Definizione delle domande (`tp_survey_definition()`), tabella, noindex, sitemap, cache, endpoint REST, validazione |
| `inc/survey-admin.php` | Voce di menu "Survey OnField": elenco con filtri, dettaglio, cancellazione, export CSV |
| `assets/js/survey.js` | Costruisce le schermate dalla definizione e invia le risposte |
| `assets/css/survey.css` | Pelle del Figma v1 (`UiW4TPgvqCODW5bhyFV5eK`, pagina `2781:9`) con le correzioni UI del 30/10 |
| `assets/fonts/inter-latin.woff2` | Inter in locale (48 KB), nessuna richiesta a Google |
| `tools/qa-survey.js` | QA: tutte le diramazioni a 390 e 1280 px, validazioni, endpoint, noindex, sitemap, admin e CSV |

## Flusso

Welcome, 10 domande (la sotto-domanda sul software compare solo con "Booking or field-service software"), contatto,
commento libero, grazie. Etichetta "N / 10" (la sotto-domanda software e il commento non contano). Il contatto ha tre
varianti di titolo e CTA per i rami A (vuole l'accesso), B (demo) e C (piu' avanti). Il ramo D ("Not for me") salta il
contatto e nel commento ha il link "Leave your contact anyway".

Le risposte si salvano alla fine del contatto; il commento libero le raggiunge con un secondo invio firmato (una volta
sola, entro due ore). Il ramo D senza contatto salva tutto alla schermata del commento. Dopo il salvataggio il commento
non ha il tasto indietro: tornare al contatto duplicherebbe la risposta.

## Dati

Tabella `{prefix}tp_survey_response`, creata da `tp_survey_install()` (dbDelta) all'attivazione del tema e, dopo un
deploy senza riattivazione, al primo `init` (versione schema in `tp_survey_db_version`). Ogni riga ha `survey_version` (2),
il ramo, i codici delle risposte, i testi liberi "other", contatto, commento, consenso con data e riferimento al testo
(`onfield-survey-v2`), hash dell'IP (HMAC, mai l'IP in chiaro) e user agent. Le risposte della v1 (portale) restano
separate: valori diversi su `business_type`, `team_size` e prezzo.

Admin (`manage_options`): elenco filtrabile per versione, ramo e testo, dettaglio, cancellazione singola e multipla,
export CSV con etichette leggibili, BOM UTF-8 e protezione dall'iniezione di formule (apice davanti a `= + - @`).

## Endpoint

`POST /wp-json/tp/v1/survey` e `POST /wp-json/tp/v1/survey/feedback`.

- Nonce WordPress (`tp_survey`) e tempo firmato (HMAC) incorporati nella pagina. Per questo la pagina non va mai in cache:
  `nocache_headers()`, `DONOTCACHEPAGE` e `litespeed_control_set_nocache`.
- Tempo minimo di 12 secondi dal caricamento (anti-bot), origine uguale al sito, honeypot (`website`: successo finto, nulla salvato).
- Rate limit: 10 salvataggi per ora e per IP (hash), contati solo sui salvataggi riusciti.
- Validazione lato server su tutte le risposte (opzioni dalla stessa definizione del client), consenso obbligatorio quando c'e' un contatto, almeno WhatsApp o email nei rami A/B/C. Nel ramo D il contatto e' facoltativo, ma se c'e' valgono le stesse regole.

[ATTENZIONE] L'IP viene da `REMOTE_ADDR`; solo se e' privato o riservato (proxy) vale il primo `X-Forwarded-For`. Dopo il
primo deploy verificare che due visitatori diversi non finiscano nello stesso hash (colonna `ip_hash`), altrimenti il
rate limit li conta insieme.

## Pagina

`noindex,nofollow` (meta e `X-Robots-Tag`), fuori dalla sitemap di WordPress (filtro in `inc/survey.php`, in AND con quello
della thank-you), nessun link da menu o footer. Senza tracking del sito (GTM, Clarity, iubenda, `tp-track.js`): raccoglie
dati personali e il marchio e' un altro. Titolo e `lang="en"` propri.

## Messa in pubblicazione

1. Deploy dei file del tema (con `functions.php` e `style.css` solo per la riga `require_once` e la `Version:`).
2. Pagina: `wp post create --post_type=page --post_title="Survey OnField" --post_name=survey-dubai --post_status=publish --page_template=page-survey.php`
3. `wp litespeed-purge all` (la pagina non e' in cache, ma `/wp-sitemap.xml` si').
4. Prova: una risposta di prova dal browser, verifica nell'admin, cancellazione della risposta di prova.
5. Cutover checklist N2: la v1 React del portale si dismette (non e' piu' servita da `app.`).

## QA

`node tools/qa-survey.js <chrome> <url-base> <cartella-screenshot> [admin]`. Su WordPress Playground (`@wp-playground/cli`,
tema montato in `wp-content/themes/trovapulizie`) gira per intero, admin compreso (utente `admin` / `password`). Sullo
slot reale le prove di scrittura vanno seguite dalla cancellazione delle risposte di prova.

## Copia nel gestionale interno (Airtable)

`inc/survey-airtable.php` copia ogni risposta nella base Airtable del gestionale interno (`appXiH9eh4Ko9H108`), fuori
dal salvataggio: cron immediato a ogni risposta e giro ogni 15 minuti per le righe rimaste indietro. Token in
`wp-config.php` (`TP_AIRTABLE_SURVEY_TOKEN`), mai nel repo. Manuale: `wp tp-survey sync [<id>] [--all]`.

Per ogni risposta, una volta sola (gli id Airtable restano sulla riga WordPress):

1. **Contatto**, solo se c'e' un recapito: cercato per email o numero, altrimenti creato (Tipo `SaaS Prospect`, Mercato
   `Dubai`, consenso con data e riferimento).
2. **Opportunita'** (dal 31/10/2026, SPEC-lead-onfield del gestionale): per **ogni** risposta con un recapito, di
   qualunque ramo. Servizio `OnField`, Fase OnField `Nuovo`, Campagna `Survey Dubai` (`recfRZJB5mDn6qkZ0`), Stato
   `Da contattare`, note col riepilogo. Fino al 30/10 la creavano solo i rami A e B. Si lavora nella pagina
   `/lead-onfield` del gestionale.
3. **Risposta** nella tabella `Survey OnField`, collegata a Contatto e Opportunita'.

Una risposta cancellata dall'admin WordPress resta su Airtable: si cancella a mano anche li'.
