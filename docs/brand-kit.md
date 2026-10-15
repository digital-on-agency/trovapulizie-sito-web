# Brand Kit — sito marketing Trovapulizie

Fonte: Figma `UiW4TPgvqCODW5bhyFV5eK`, frame Home "Homepage — Desktop - redesign" (`3564:4`).
Valori estratti via `get_design_context` sui nodi reali (Navbar `3564:5`, Hero Text `3564:208`,
Card servizio `3828:1130`) — non dedotti, non inventati. Sostituiscono i placeholder del primo
giro di CSS (segnalati come inventati da Guido, vedi `CLAUDE.md` § Ora 12/10).

**Discrepanza risolta**: il Figma usa **Poppins** per tutto il testo, non Inter (che resta il
font dell'app React, vedi `design-system.md` del cliente — quello è il design system dell'app
mobile/portale, non di questo sito).

## Colori

| Token | Hex | Uso osservato |
|---|---|---|
| `--tp-primary` | `#00C896` | CTA, link, bordo bottone Accedi, stelle rating |
| `--tp-text` | `#191919` | H1 Hero |
| `--tp-text-heading` | `#1F2937` | Titoli card, dropdown servizio |
| `--tp-text-body` | `#6B7280` | Sottotitolo Hero, caption trust badge |
| `--tp-text-muted` | `#8C94A1` | Descrizione card servizio |
| `--tp-text-placeholder` | `#9CA3AF` | Placeholder input indirizzo |
| `--tp-text-chip` | `#474F5C` | Testo chip "Cosa include" |
| `--tp-border` | `#EBEDF0` | Bordo search bar, divider |
| `--tp-border-card` | `#E5E8EB` | Bordo card servizio, bordo chip |
| `--tp-bg-soft` | `#F8FBFA` | Pannelli interni card (Cosa include/Descrizione) |
| `--tp-white` | `#FFFFFF` | Background base |

Nota: non sono gli stessi hex esatti della palette `design-system.md` dell'app (quella usa
`neutral-400`=`#6B7280` coincidente, ma `neutral-200`=`#D1D5DB` mentre qui si osservano
`#9CA3AF`/`#8C94A1`/`#474F5C` — token web distinti, non un refactor della palette app).

## Font

- Famiglia: **Poppins** (Google Fonts), pesi osservati: Regular 400, Medium 500, SemiBold 600,
  Bold 700, ExtraBold 800.
- H1 Hero: 48px / line-height 56px / ExtraBold (800).
- Sottotitolo Hero: 18px / line-height 28px / Regular.
- Nav link: 15px / Regular.
- Bottone (Accedi/Registrati/Cerca/Prenota): 14-16px / SemiBold o Bold a seconda del CTA.
- Titolo card servizio: 22px / Bold.
- Descrizione card: 14px / Regular.
- Chip: 13px / Medium.

## Radius (non uniforme — dipende dal componente, non un solo token come nel primo giro)

| Token | Valore | Uso |
|---|---|---|
| `--tp-radius-sm` | 8px | Bottoni Navbar (Accedi/Registrati) |
| `--tp-radius-md` | 10px | Chip |
| `--tp-radius-lg` | 12px | Elementi search bar, bottone Prenota |
| `--tp-radius-xl` | 16px | Box search bar, pannelli card, Visual card |
| `--tp-radius-2xl` | 24px | Card servizio (contenitore esterno) |
| `--tp-radius-pill` | 999px | Badge pillola (trust badge, se tondo) |

## Ombre

- `--tp-shadow-card`: `0 10px 28px -6px rgba(15,23,41,0.1)` — osservata sulla search bar Hero.

## Componenti base implementati

Vedi `style.css` sezione "Componenti base":

- `.tp-btn` / `.tp-btn--primary` / `.tp-btn--secondary` — bottoni CTA (Navbar + sezioni)
- `.tp-chip` — chip "Cosa include"
- `.tp-card` — card servizio (bordo + radius 24px, contenuto libero)

## Sezione Servizi popolari (`3828:1079`, 15/10)

- Sfondo sezione: `--tp-bg-soft` full-bleed; contenuto 1248px (96px di margine a 1440).
- Titolo sezione: 36px / Bold / `--tp-text-heading`; sottotitolo 17px / Regular / `#9CA3AF`.
- Social proof: avatar 40px tondo; riga 1 13px SemiBold; riga 2 12px `#9CA3AF`; divisore 1x34 `--tp-border-card`;
  stella `#FFB826` 16px Bold; "4,9 valutazione media" 14px SemiBold.
- Tab a pillola: raggio **22px**, padding 10/18/10/16, gap 8, icona 18px (`assets/icons/tab-*.svg`, tratto
  `#6B7380`, resa come mask in `currentColor`); attiva = `--tp-primary` + testo bianco SemiBold; inattiva =
  bianco + bordo `--tp-border-card` + testo `--tp-text-chip` Medium 14px.
- Card servizio 1248x360: `.tp-card` (raggio 24, padding 20, gap 20); pannelli `.tp-card__panel` da 320px
  (raggio 16, padding 18); Visual con velo `rgba(0,200,150,0.5)` ed etichetta 15px SemiBold bianca in basso a
  sinistra (20/20); titolo card 22px Bold; descrizione 14px `--tp-text-muted`; riga in evidenza 13px Medium
  `--tp-primary`; CTA larghezza piena, raggio 12, padding 13, 15px SemiBold.
- Mascotte decorativa `4522:4705`: `assets/img/mascotte.png` 218px ruotata 17.42deg in basso a destra, tagliata
  dal bordo della sezione.

## Come aggiornare questo file

Ogni volta che si fetcha un nuovo nodo Figma con valori di colore/font/radius non ancora
documentati qui, aggiungerli con il node-id di provenienza. Non inventare valori per sezioni
non ancora fetchate — lasciarle fuori finché non verificate.
