# Blog (`/blog/`, `/blog/categoria/<slug>/`, `/blog/<slug>/`)

Articoli = post nativi di WordPress. Design: Figma `6824:903` (indice), `6827:937` (articolo), card `6824:895`,
note `6824:1056`. PRD e ticket nel vault: `clients/trovapulizie/projects/sito-web/spec/SPEC-blog.md` (TICKET-70..72).

## Impostazioni WordPress (fatte con WP-CLI sullo slot il 27/10)

| Impostazione | Valore |
|---|---|
| Pagina degli articoli (`page_for_posts`) | pagina "Blog", slug `blog` (post 58) |
| Struttura permalink | `/blog/%postname%/` (tocca solo gli articoli; pagine e landing invariate, verificato con un confronto degli URL prima e dopo) |
| `category_base` | `blog/categoria` (WordPress non antepone `/blog/` alla base delle categorie da solo) |
| Categorie | Casa `casa`, Sgrosso `sgrosso`, Aziende e condomini `aziende-condomini`, Prezzi `prezzi`, Per i professionisti `professionisti`; quella di default rinominata Varie `varie`, esclusa dai chip |

## File

| File | Ruolo |
|---|---|
| `home.php`, `category.php` | indice e categorie, entrambi con `template-parts/blog-index.php` |
| `template-parts/blog-index.php` | hero, chip delle categorie con almeno un articolo, evidenza, griglia, paginazione numerata, stato vuoto, CTA finale |
| `template-parts/post-card.php` | card dell'articolo (anche "Leggi anche"); senza immagine in evidenza mostra la mascotte |
| `single.php` | articolo: testata, copertina, indice dagli H2, testo, box servizio, autore, Leggi anche |
| `inc/blog.php` | servizi collegabili, indice automatico, minuti di lettura, `[tp_servizio]`, 7 articoli per pagina, niente pagine autore ne' commenti, SEO e JSON-LD (`Blog`, `BlogPosting`, `BreadcrumbList`) |
| `inc/carbon-fields.php` | scheda "Articolo" (campo "Servizio collegato"); la scheda SEO vale anche per gli articoli |
| `style.css` | blocco 18, classi `bl-*` |

## Scrivere un articolo

- **Titolo**, **Riassunto** (estratto: e' il sommario sotto il titolo e il testo delle card) e **Immagine in evidenza**
  (copertina; consigliato almeno 1600px di larghezza).
- **Categoria**: una sola, fra le cinque.
- **Titoli di sezione** in H2: con almeno 3 H2 compare l'indice a sinistra, con le ancore automatiche.
- **Checklist**: un Gruppo con classe CSS aggiuntiva `tp-checklist` (pannello Avanzate del blocco), dentro sottotitoli
  H3 ed elenchi puntati; le voci prendono la spunta verde.
- **Consiglio**: un Gruppo con classe `tp-consiglio`, dentro un paragrafo che inizia con una parola in grassetto
  ("Consiglio").
- **Box del servizio**: scegli il servizio nella scheda "Articolo" a destra; il box va dove scrivi `[tp_servizio]`
  (blocco Shortcode), altrimenti in fondo al testo. "Nessuno" = niente box.
- **FAQ**: sezione H2 "Domande frequenti" con H3 per domanda e un paragrafo per risposta.
- Autore unico: "Redazione Trovapulizie" con la mascotte, non serve impostarlo.

## Tracking

`data-landing="blog"` su indice e categorie, `data-landing="articolo"` sull'articolo. Box servizio
`marketing_cta_click` con `cta_position` `servizio`; stato vuoto `blog_vuoto`; CTA finale `final`.

## QA

```
CDP_PROFILE=<cartella vuota> node tools/qa-blog.js <chrome> <base-url> <slug-articolo> [1440|1023|390] [cartella-png]
```

Servono 7 articoli pubblicati. Il 27/10 sono stati creati esempi temporanei marcati con il meta `_tp_qa_esempio`,
pubblicati solo per la QA e poi eliminati con le loro immagini.

## Trappola: cache oggetti

Le modifiche fatte da WP-CLI (stato di un post, opzioni, permalink) non arrivano al web finche' la cache oggetti di
LiteSpeed (memcached, non raggiungibile dalla CLI) tiene le copie vecchie: vedi `docs/pagine-legali.md`. Dopo il
cambio dei permalink le categorie rispondevano ancora sul vecchio indirizzo finche' non sono state invalidate le
chiavi `alloptions`, `rewrite_rules`, `category_base` dal contesto web. Non usare `flush_all`: il memcached e'
condiviso con gli altri siti dell'account.

## Stato

Un articolo in bozza (post 59, "Pulizia di fine affitto: la checklist...", servizio sgrosso fine affitto, copertina
`sgrosso-hero-fine-affitto`), in attesa della rilettura di Guido. Senza articoli pubblicati `/blog/` mostra lo stato
vuoto.
