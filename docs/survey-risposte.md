# Risposte survey: tabella di arrivo comune

Ogni survey scritta su misura nel tema copia le sue risposte nella tabella Airtable "Risposte survey"
(`tblqZTo192SVPN21a`, base "Contabilita' & Marketing" `appXiH9eh4Ko9H108`). La pagina `/survey-risposte` del gestionale
interno legge solo quella tabella: una survey nuova compare li' senza toccare il blocco Softr. Specifica: vault
`clients/trovapulizie/projects/gestionale-interno/spec/SPEC-risposte-survey.md`.

La Survey Dubai (`/survey-dubai/`) non passa da qui: le sue risposte diventano lead OnField e si lavorano in
`/lead-onfield`.

## Funzioni (`inc/survey-risposte.php`)

| Funzione | Cosa fa |
|---|---|
| `tp_risposte_upsert( array $fields )` | Crea o aggiorna la riga con upsert su "Chiave origine". Ritorna `[id, errore]`. `typecast` attivo: un valore nuovo di Survey crea la scelta |
| `tp_risposte_testo( array $pairs )` | Testo leggibile da coppie `[domanda, risposta]`, risposte vuote saltate |

Usa `tp_survey_at_request` e il token `TP_AIRTABLE_SURVEY_TOKEN` della Survey OnField (`inc/survey-airtable.php`).

## Campi

| Campo | Scritto da | Note |
|---|---|---|
| Risposta | tema | titolo, es. "Serata #10 - Nat" |
| Survey | tema | nome della survey, es. "Serata 25-11" |
| Chiave origine | tema | `<survey>:<id WP>`, es. `serata:10`. Chiave dell'upsert: non cambiarla dopo la prima copia |
| Data | tema | ISO 8601 |
| Nome, Azienda, Telefono, Email | tema | Email `null` se vuota |
| Contatto | tema | id del Contatto, solo se esiste (verificarlo prima, un id sbagliato fa fallire la copia) |
| Risposte | tema | da `tp_risposte_testo` |
| Da ricontattare | tema | regola propria di ogni survey |
| Note sincronizzazione | tema | avvisi della copia |
| Ricontattato, Data ricontatto, Nota ricontatto | gestionale | il tema non li manda mai: l'upsert li lascia com'erano |

## Collegare una survey nuova

1. Nel sync della survey verso Airtable, dopo aver salvato l'id della sua riga, chiamare `tp_risposte_upsert()` con i
   campi sopra e una Chiave origine col prefisso della survey.
2. Se la copia fallisce, segnare la riga WordPress in errore con un messaggio che inizia per `risposte survey:`: il giro
   successivo riprova.
3. Nome e recapiti vanno nei loro campi, non nel testo Risposte.
4. Fine vita: le righe della survey in "Risposte survey" si cancellano insieme a quelle in WordPress.

Esempio: `tp_serata_at_sync_row()` e `tp_serata_risposte_pairs()` in `inc/serata-airtable.php`.
