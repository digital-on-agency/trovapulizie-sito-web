# Pagine legali (`/privacy/`, `/terms/`, cookie policy)

Template `page-legal.php` ("Pagina legale"): titolo della pagina, testo dall'editor di WordPress in una colonna da
820px e, se il campo "Policy iubenda" e' compilato, la policy incorporata in un iframe come sulla SPA di produzione.
CSS nel blocco 17 di `style.css`. Title "<titolo> — Trovapulizie" da `inc/seo.php`; description dal campo SEO.

| Pagina | Post sullo slot | Contenuto |
|---|---|---|
| `/privacy/` | 37 (la bozza "Privacy" dello scheletro del 12/10) | solo l'iframe della policy iubenda 55307359: il testo lo mantiene iubenda |
| `/terms/` | 54 | testo copiato dall'app (`front-end/src/content/terms.ts`, versione italiana del 14/06/2026, uguale su `master` e `staging` il 27/10), convertito in HTML nel contenuto della pagina |
| cookie policy | nessuna | il footer porta a `https://www.iubenda.com/privacy-policy/55307359/cookie-policy`, come il footer dell'app |

URL uguali alla produzione (`/privacy`, `/terms`). I link legali del footer stanno in Theme Options > Footer >
"Link legali"; il 27/10 sono stati corretti da `/termini/` e `/cookie/`, che non sono mai esistiti.

[ATTENZIONE] I termini vivono in due posti finche' l'app non punta a questa pagina: ogni modifica va fatta sia qui
sia in `terms.ts`, oppure si sceglie un'unica fonte con Niccolo'. Il testo cita ancora "Sito web: trovapulizie.com"
come nell'app: e' testo legale, non va ritoccato da qui.

Trappole viste il 27/10:
- Esisteva gia' una bozza con slug `privacy` (scheletro del 12/10) che `wp post list --name=privacy
  --post_status=any` non ha mostrato: la pagina nuova e' nata come `privacy-2`. Prima di creare una pagina
  controllare con una query su `post_name`.
- La cache oggetti di LiteSpeed (attiva sul web, `object-life` 360 s) non e' raggiungibile da WP-CLI
  (`wp_using_ext_object_cache()` falso, `wp cache flush` fallisce): dopo una modifica da CLI il web puo' vedere lo
  stato vecchio del post fino a 6 minuti (qui: bozza -> redirect a `?page_id=37`).
