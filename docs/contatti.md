# Pagina Contatti (`/contatti/`)

Smistamento di chi cerca un contatto verso la pagina giusta, piu' i canali diretti per chi ha davvero bisogno del
team. Nessun form (decisione Guido 27/10). Design: Figma `6803:895` (pagina "Sito Web"), note `6803:1068`.
PRD e ticket nel vault: `clients/trovapulizie/projects/sito-web/spec/SPEC-pagina-contatti.md` (TICKET-63..65).

## File

| File | Ruolo |
|---|---|
| `page-contact.php` | template "Contatti": default dei testi, mappa icona -> `cta_position`, ordine delle sezioni |
| `template-parts/route-cards.php` | testa con l'H1 e le quattro card di smistamento |
| `template-parts/company-contacts.php` | card dei canali (WhatsApp, email, orari, bottone) e card dei dati aziendali |
| `template-parts/faq-accordion.php` | FAQ della Home, qui senza card di contatto |
| `inc/carbon-fields.php` | scheda "Contatti — Sezioni" (prefisso `ct_`, tab Smistamento, Canali, Dati aziendali, FAQ) |
| `inc/seo.php` | title, description, JSON-LD `Organization` con `ContactPoint` |
| `functions.php` | `TP_CT_WHATSAPP`, `TP_CT_EMAIL`, `tp_whatsapp_parts()`, script `reveal` e `faq-accordion` sulla pagina |
| `style.css` | blocco 15, classi `ct-*` |

[ATTENZIONE] Il template si chiama `page-contact.php`, non `page-contatti.php`: col nome dello slug WordPress lo
applicherebbe da solo senza scheda dei campi ne' script. Sulla pagina (post 51 sullo slot) il template e'
assegnato a mano.

## Contenuti

Ogni testo ha un default nel template; un campo compilato vince (`tp_ct()`). Le card sono un campo complesso:
vuoto = le quattro del Figma. L'icona della card decide anche `cta_position` negli eventi
(`contatti_card_casa`, `_azienda`, `_lavora`, `_prenotato`), cosi' riordinare o riscrivere le card non rompe i report.

Il numero WhatsApp passa sempre da `tp_whatsapp_parts()`: accetta qualunque formato e restituisce il testo da
mostrare, il link `wa.me` e il formato E.164. Lo stesso campo `ct_whatsapp` alimenta la card "Hai gia' prenotato"
(con testo precompilato), il blocco dei canali e il `ContactPoint` del JSON-LD. Svuotando il numero spariscono
riga, bottone e `telephone`; la card scura sparisce perche' resta senza link.

## Tracking

`tp-track.js`, nessun codice nuovo: i link hanno `data-cta-position` e il `main` ha `data-landing="contatti"`.
Card interne -> `marketing_cta_click`; link `wa.me` -> `marketing_click_whatsapp`. Il bottone dei canali e' `contatti_canali`,
il numero `contatti_canali_numero`. L'email (`mailto:`) non e' tracciata.

## SEO

Title "Contatti — Trovapulizie", description della SPEC. `Organization` con indirizzo solo a livello di citta',
come il provider del `Service` delle landing: lo `streetAddress` resta fuori finche' non si chiarisce se il profilo
Google e' un'attivita' con area di servizio. La sede legale completa e' comunque visibile in pagina.

## Responsive

Card su quattro colonne da 1024px, due fino a 640px, una sotto. Canali sopra i dati aziendali sotto 1024px. L'area
cliccabile delle card e' l'intera card (`::after` del link), per questo l'avviso "bersagli sotto 44px" di
`qa-mobile.js` sui link delle card non e' un problema reale.

## QA

```
CDP_PROFILE=<cartella vuota> node tools/qa-contact.js <chrome> <url>/contatti/ [1440|1023|390] [out.png]
node tools/qa-mobile.js <chrome> <url>/contatti/
```

## Aperti

Telefono fisso, fasce orarie, risposte FAQ 2-4 da rileggere col team, voce Contatti nella navbar: vedi nota Figma
`6803:1068` e SPEC §6.
