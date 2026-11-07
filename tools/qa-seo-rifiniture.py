#!/usr/bin/env python3
"""Controllo statico dei title (TICKET-88) e del canonical di blog e categorie (TICKET-89), senza WordPress.

Uso: python3 tools/qa-seo-rifiniture.py   (dalla radice del tema). Esce con 1 se un controllo fallisce.

Legge i literal in inc/sgrosso-variants.php (page_title delle cinque varianti) e in inc/seo.php (Home, B2B,
zanzare), li confronta con i valori della SPEC-seo-rifiniture-2026-11-07 e verifica la lunghezza dopo la
decodifica delle entita HTML (limite 60). Verifica anche che il canonical venga stampato su indice del blog e
categorie con tp_seo_canonical_url().
"""
import html, os, re, sys

RADICE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
LIMITE = 60

ATTESI = {
    'home': 'Trovapulizie: pulizie professionali a Roma',
    'sgrosso-casa': 'Sgrosso casa Roma, sopralluogo gratuito | Trovapulizie',
    'sgrosso-post-ristrutturazione': 'Pulizie post ristrutturazione Roma | Trovapulizie',
    'sgrosso-prima-pulizia': 'Pulizia straordinaria casa Roma | Trovapulizie',
    'sgrosso-trasloco': 'Pulizie post trasloco Roma | Trovapulizie',
    'sgrosso-fine-affitto': 'Pulizia fine affitto Roma, sopralluogo gratis | Trovapulizie',
    'zanzare': 'Disinfestazione zanzare Roma | Trovapulizie',
    'sgrosso-commerciale': 'Pulizie commerciali Roma: uffici e negozi | Trovapulizie',
}

def leggi(rel):
    with open(os.path.join(RADICE, rel), encoding='utf-8') as f:
        return f.read()

def php_literal(s):
    return html.unescape(s.replace("\\'", "'"))

ko = 0
def check(nome, ok, dettaglio=''):
    global ko
    print(('OK  ' if ok else 'KO  ') + nome + ('  [' + str(dettaglio)[:160] + ']' if dettaglio else ''))
    if not ok:
        ko += 1

varianti = leggi('inc/sgrosso-variants.php')
seo = leggi('inc/seo.php')

trovati = {}
# Cinque varianti: la chiave del blocco in VARIANT_CONFIG non coincide sempre con lo slug (es. prima-pulizia-a-fondo).
CHIAVI = {
    'sgrosso-casa': 'sgrosso-casa',
    'post-ristrutturazione': 'sgrosso-post-ristrutturazione',
    'trasloco': 'sgrosso-trasloco',
    'fine-affitto': 'sgrosso-fine-affitto',
    'prima-pulizia-a-fondo': 'sgrosso-prima-pulizia',
}
for m in re.finditer(r"\t\t'([a-z-]+)'\s*=>\s*array\((.*?)'page_title'\s*=>\s*'((?:[^'\\]|\\.)*)'", varianti, re.S):
    if m.group(1) in CHIAVI:
        trovati[CHIAVI[m.group(1)]] = php_literal(m.group(3))

def blocco_template(template):
    m = re.search(r"is_page_template\(\s*'" + re.escape(template) + r"'\s*\)\s*\)\s*\{\s*return\s+'((?:[^'\\]|\\.)*)'", seo)
    return php_literal(m.group(1)) if m else None

m = re.search(r"is_front_page\(\)\s*\)\s*\{\s*return\s+'((?:[^'\\]|\\.)*)'", seo)
trovati['home'] = php_literal(m.group(1)) if m else None
trovati['sgrosso-commerciale'] = blocco_template('page-landing-b2b.php')
trovati['zanzare'] = blocco_template('page-landing-zanzare.php')

print('--- TICKET-88: title')
for chiave, atteso in ATTESI.items():
    t = trovati.get(chiave)
    check(chiave + ': valore', t == atteso, t)
    check(chiave + ': lunghezza <= ' + str(LIMITE), t is not None and len(t) <= LIMITE, None if t is None else len(t))

print('--- TICKET-89: canonical')
check('tp_seo_canonical_url gestisce la paginazione con get_pagenum_link', 'get_pagenum_link' in seo)
check('canonical stampato su is_home e is_category', bool(re.search(r"rel=\"canonical\"", seo)) and bool(re.search(r"is_home\(\)\s*\|\|\s*is_category\(\)", seo)))
check('nessuna seconda funzione che calcola il canonical',
      len(re.findall(r"function\s+tp_[a-z_]*canonical[a-z_]*\s*\(", seo)) == 1)

print('ESITO: ' + ('verde' if ko == 0 else str(ko) + ' controlli falliti'))
sys.exit(1 if ko else 0)
