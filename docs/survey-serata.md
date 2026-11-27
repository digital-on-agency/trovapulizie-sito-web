# Survey della serata 25/11

Pagina `/serata-25-11/` (template "Survey serata 25/11", `page-serata.php`). Survey breve da inviare su WhatsApp, uno per
uno, a chi era alla serata Wine Venture Club del 25/11/2026. Specifica: vault
`clients/trovapulizie/projects/sito-web/spec/SPEC-survey-serata-25-11.md` (approvata da Guido il 26/11).

## File

| File | Cosa fa |
|---|---|
| `inc/serata.php` | Definizione di domande e testi, tabella `{prefix}tp_serata_response`, robots/sitemap/cache/titolo/asset, endpoint `POST /wp-json/tp/v1/serata`, validazione (funzione pura `tp_serata_validate`) |
| `inc/serata-admin.php` | Strumenti > Survey serata 25/11: elenco con etichette leggibili, cancellazione, export CSV |
| `page-serata.php` | Contenitore della pagina, configurazione in `data-config` |
| `assets/js/serata.js` | Schermate costruite dalla definizione, invio unico all'ultima domanda |
| `assets/css/serata.css` | Sopra `survey.css`: verde `#37B299`, firma, scala 1-5 |

Riusa dalla Survey OnField (`inc/survey.php`): `tp_survey_opts`, `tp_survey_guard` (origine, nonce, tempo minimo
12 s firmato), `tp_survey_rate_limited` (bucket `s`, 10 salvataggi per IP e ora), `tp_survey_text`, `tp_survey_ip_hash`,
`tp_survey_csv_cell`. Un cambio a quelle funzioni vale per entrambe le pagine: provare tutte e due.

## Link personali

`https://trovapulizie.it/serata-25-11/?n=Nome%20Cognome`. Il nome si ripulisce lato server (testo semplice, 120
caratteri) e compila la prima domanda, che resta modificabile. Senza `n` il campo e' vuoto e obbligatorio. Nel link va
solo il nome: niente telefono ne' email (finiscono nei log del server e nella cronologia di WhatsApp).

## Comportamento

- Una domanda per schermata, "N / 7"; la 4b (dubbio) compare solo se la 4 non e' "Si', chiaro" e condivide il numero 4.
- Scelte singole e voto avanzano al tocco; l'ultima domanda chiede "Invia". Facoltative vuote: bottone "Salta".
- "No" e "Niente per ora" escludono le altre risposte della stessa domanda (anche lato server).
- "Altro" all'ultima domanda richiede il testo.
- Se l'invio arriva prima dei 12 s anti-bot (risposte a tocchi rapidi), il client riprova da solo fino a tre volte.
- Pagina noindex,nofollow (meta e `X-Robots-Tag`), fuori dalla sitemap, mai in cache, senza GTM, Clarity ne' banner.
  Open Graph resta, per l'anteprima del link su WhatsApp.
- Nessuna notifica. Ogni risposta viene copiata nel gestionale interno (`inc/serata-airtable.php`): nella tabella
  "Survey Serata 25-11", con i voti come numeri, e nella tabella comune "Risposte survey" (`inc/survey-risposte.php`),
  letta dalla pagina `/survey-risposte`. Nessun Contatto nuovo, nessuna Opportunita'. Manuale:
  `wp tp-serata sync [<id>] [--all] [--resync]`; `--resync` riprende anche le righe gia' sincronizzate.

## QA

`node tools/qa-serata.js <chrome> <url-base> <cartella-screenshot> [admin]`: percorso completo a 390 e 1280 px, nome dal
link, 4b condizionata, esclusive, "Altro" obbligatorio, schermata finale, endpoint (honeypot, nonce, tempo minimo,
campi non validi), con `admin` elenco e CSV (login admin/password di WordPress Playground). Sul sito vero le risposte di
prova vanno cancellate subito dall'admin.

## Fine vita

Le risposte si cancellano a fine ricontatti (indicativamente entro 90 giorni) in tre posti: admin di WordPress, tabella
"Survey Serata 25-11" e righe con Survey "Serata 25-11" in "Risposte survey". Una risposta cancellata in WordPress resta
su Airtable. Poi si puo' mettere la pagina in bozza; il codice resta finche' non serve una survey nuova.
