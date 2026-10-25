# trovapulizie-sito-web

Tema WordPress custom (PHP + Carbon Fields, nessun page builder, nessun bundler) per il sito marketing di
Trovapulizie, scorporato dall'app transazionale React (`portale-transazionale`). Decisione architetturale:
`ADR-001` nel vault, `clients/trovapulizie/projects/sito-web/adr/`.

## Documentazione

- `docs/homepage.md`: come e' costruita la Home (file, campi editoriali, sezioni, coreografia del telefono,
  asset, cache-busting, QA, limiti noti). Da leggere prima di toccare `page-home.php`, `home-motion.js` o `style.css`.
- `docs/brand-kit.md`: token di design estratti dal Figma (colori, font, raggi, ombre) con i node-id di provenienza.
- `docs/landing-sgrosso.md`: landing Sgrosso (cinque pagine su un template, form lead con chiamate dirette all'API,
  tracking, SEO, QA).
- `tools/README.md`: script di QA, pipeline del video dello schermo, encoding.
- Decisioni (ADR), spec e stato del progetto: vault `clients/trovapulizie/projects/sito-web/`.

## Struttura

```
style.css                 foglio unico; l'header Version: e' il cache-buster di tutti gli asset
functions.php             helper, enqueue, importmap Three.js, boot Carbon Fields
inc/carbon-fields.php     campi editoriali (Home per tab + Theme Options Footer)
header.php / footer.php   navbar e footer
page-home.php             template Home
page-hub-roma.php         template hub di citta' /roma/
page-sgrosso.php          template landing Sgrosso (cinque pagine, campo variante)
page-thank-you.php        thank-you dopo un lead (noindex)
header-landing.php        testata delle landing (promo bar, navbar senza menu); head condiviso in template-parts/head.php
inc/sgrosso-variants.php  testi di default per variante della landing Sgrosso
inc/tracking.php          iubenda, GTM, Clarity da Theme Options
inc/seo.php               title, description e JSON-LD per pagina
index.php                 fallback per le altre pagine
template-parts/           sezioni riutilizzabili (args via get_template_part) + phone-mockup
assets/js/                un modulo per comportamento
assets/img|icons|video/   asset del tema (fallback dei campi WP)
docs/                     documentazione
tools/                    QA e pipeline video (non vanno sul server)
```

## Setup locale

```
composer install
```

Non esiste un ambiente WordPress locale: il codice si verifica sullo staging dopo il deploy. `php -l` si esegue
sul server (in locale non c'e' PHP).

## Deploy

Nessuna pipeline. Il repo e' la copia versionata; il sito gira sul docroot di Hostinger (account condiviso Digital
On, credenziali nella memoria `infra_hostinger_ssh` del vault). Procedura usata, nell'ordine:

1. Bump di `Version:` in `style.css` se sono cambiati CSS, JS, immagini o video (gli asset sono cachati 7 giorni
   e vengono invalidati solo dal `?ver=`).
2. Copia dei file modificati in una cartella di appoggio sul server e lint PHP prima di toccare il tema:

```
FILES="style.css functions.php page-home.php ..."
tar czf /tmp/tp-deploy.tgz $FILES
scp -P $PORT -i $KEY /tmp/tp-deploy.tgz $HOST:~/tp-deploy.tgz
ssh -p $PORT -i $KEY $HOST 'set -e
  D=domains/<slot>.hostingersite.com/public_html/wp-content/themes/trovapulizie
  rm -rf ~/tp-stage && mkdir ~/tp-stage && tar xzf ~/tp-deploy.tgz -C ~/tp-stage
  cd ~/tp-stage && for f in $(find . -name "*.php"); do php -l $f; done
  cp -r ~/tp-stage/. ~/$D/ && cd ~/$D/../../.. && wp litespeed-purge all
  rm -rf ~/tp-stage ~/tp-deploy.tgz'
```

3. QA sullo staging con gli script in `tools/` (profilo Chromium cancellato prima del run).
4. Commit e push. `vendor/` non e' tracciato (`composer install` sul server e' gia' stato fatto).

Un errore PHP nel tema manda in fatal tutto il sito: per questo il lint precede sempre la copia.

## Convenzioni

- CSS: BEM, token in `:root`, classi di stato `is-*`, `--i` per lo stagger, un solo blocco `prefers-reduced-motion` in coda.
- PHP: i template-part ricevono tutto via `$args` con default vuoti; nessun accesso diretto ai campi dentro le parti.
- JS: IIFE senza dipendenze; librerie esterne caricate solo quando servono (vedi `docs/homepage.md` §9).
- Commit: un argomento per commit, prefisso `feat` / `fix` / `refactor` / `docs` / `chore`.
- Niente emoji nel codice, nei commenti e nei contenuti di default.

## Stato

Home desktop e mobile, hub /roma/ e cinque landing Sgrosso con form lead funzionante (ver 0.50.x). Stato e prossimi passi nel vault,
`clients/trovapulizie/projects/sito-web/CLAUDE.md`.
