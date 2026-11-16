# Hub /roma/

Pagina 6, template `page-hub-roma.php`, campi nella scheda "Hub Roma - Sezioni" (`roma_*`). Origine: SPEC-pagina-hub-roma
(TICKET-22..36) e, dal tema 0.70.0, SPEC-crescita-organica-fase-b US-03 (TICKET-98).

## Obiettivo SEO

L'hub risponde alla ricerca "impresa di pulizie a Roma": title, H1, description e introduzione portano la keyword, e una
sezione "Servizi a Roma" linka ogni pagina di servizio pubblicata.

## Ordine delle sezioni

hero con ricerca (`#cerca`), introduzione (`#cosa-facciamo`), elenco "Servizi a Roma" (`#servizi-roma`), zone per CAP
(`#dove-siamo`), servizi a tab, verificati, come funziona, recensioni (solo se compilate), FAQ, CTA finale, barra fissa
mobile. Il blocco "Altri servizi a Roma" non c'e' piu' sull'hub: lo sostituisce l'elenco.

## Testi: default nel codice, campi che vincono

I default stanno in `inc/hub-roma.php` (`tp_hub_roma_defaults()`). Il template li legge con `tp_hub_roma_testi()`: un
campo compilato sulla pagina vince, un campo vuoto lascia il default.

| Testo | Campo che lo sovrascrive |
|---|---|
| pillola sopra l'H1 | `roma_hero_eyebrow` |
| H1 | `roma_hero_title` |
| sottotitolo | `roma_hero_subtitle` |
| pillola dell'introduzione | `roma_intro_eyebrow` |
| testo dell'introduzione (paragrafi separati da una riga vuota) | `roma_intro_text` |
| title | `seo_title` (scheda SEO) |
| meta description | `seo_description` (scheda SEO) |

I titoli delle due sezioni nuove ("Cosa fa Trovapulizie", "Servizi a Roma"), il testo sotto "Servizi a Roma", i titoli dei
gruppi e le righe di descrizione dei servizi non hanno campo: si cambiano in `inc/hub-roma.php`.

[ATTENZIONE] Prima di un rilascio si legge cosa c'e' nel database di produzione (`wp post meta get <ID> _roma_hero_title`
e gli altri): un campo compilato nasconde il testo nuovo senza errori. In produzione al 16/11/2026 i campi dell'hero,
dell'introduzione e `seo_description` sono compilati con i testi del 20/10 (cleaner, pagamento sicuro, prenoti online).

## Elenco dei servizi

`tp_hub_roma_servizi()` legge `tp_service_pages()` (inc/seo.php: unica fonte di percorsi e anchor), tiene solo le pagine
con stato `publish` (`tp_service_pages_published()`, `get_page_by_path`) e le dispone nei gruppi di
`tp_hub_roma_gruppi()`. Una pagina in bozza, privata, cestinata o non creata non compare: nessun link a una 404. Una pagina
nuova aggiunta a `tp_service_pages()` esce da sola nel gruppo "Altri servizi"; per darle una riga di descrizione si aggiunge
la voce in `tp_hub_roma_descrizioni()`. [ATTENZIONE] Il footer e il blocco "Altri servizi" delle altre pagine non filtrano
sullo stato: linkano ancora tutte le voci di `tp_service_pages()`.

## Vincoli sui testi (SPEC-crescita-organica-fase-b §2)

Nessun numero di ditte, clienti, recensioni o zone; nessuna promessa nuova (tempi, garanzie, certificazioni); "verificate"
solo dove il sito lo dice gia'; nessun prezzo per uffici e condomini; nessuna immagine nuova. `tools/qa-hub-roma.js` li
controlla sul testo delle sezioni nuove.

## QA

`node tools/qa-hub-roma.js <chrome> <url> [larghezza]` (vedi `tools/README.md`), `tools/qa-mobile.js` sulle sei larghezze,
`python3 tools/qa-seo-rifiniture.py` (title e description di default dal codice), `python3 tools/qa-sitemap-titles.py`
sul sito vivo.
