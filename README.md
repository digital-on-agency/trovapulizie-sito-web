# trovapulizie-sito-web

Tema WordPress custom (PHP + Carbon Fields) per il sito marketing di Trovapulizie — scorporato dall'app transazionale React (`portale-transazionale`). Decisione architetturale completa: vedi `ADR-001-marketing-su-wordpress-separato-da-app.md` nel vault, `clients/trovapulizie/projects/sito-web/`.

## Stack

- WordPress, tema custom (nessun page builder)
- [Carbon Fields](https://carbonfields.net/) via Composer per i campi editoriali — standard agenzia, non ACF
- Hosting: Hostinger, account condiviso Digital On

## Setup locale

```
composer install
```

## Deploy

Nessuna pipeline CI: le modifiche si applicano direttamente sul server via SSH/SCP (account condiviso Digital On, vedi memoria `infra_hostinger_ssh`). Questo repo è la copia versionata di backup/history, non un deploy target automatico — dopo ogni modifica, sincronizzare manualmente verso il docroot del sito (staging o produzione).

## Stato

In sviluppo. Vedi `clients/trovapulizie/projects/sito-web/CLAUDE.md` nel vault per stato e prossimi passi.
