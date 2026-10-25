#!/usr/bin/env python3
"""QA SEO delle cinque landing Sgrosso (TICKET-48): python3 tools/qa-seo.py <base-url>

Scarica le cinque pagine (con un parametro anti-cache) e verifica title, meta description, canonical,
assenza di noindex, un solo JSON-LD Service con offerta 200-700 EUR, un solo FAQPage, H1 della variante.
I valori attesi sono quelli di VARIANT_CONFIG del live (inc/sgrosso-variants.php). Esce con 1 se fallisce.
"""
import html, json, re, sys, random, urllib.request

BASE = (sys.argv[1] if len(sys.argv) > 1 else 'https://lightgoldenrodyellow-duck-667893.hostingersite.com').rstrip('/')

PAGES = {
    '/roma/sgrosso-casa/': {
        'title': 'Sgrosso casa Roma — Pulizia straordinaria una tantum, sopralluogo gratuito | Trovapulizie',
        'desc': 'Sgrosso casa a Roma: pulizia straordinaria post-cantiere o trasloco con imprese verificate. Sopralluogo gratuito, prezzo fisso, nessuna sorpresa.',
        'h1': 'Sgrosso casa: la tua casa torna come nuova',
    },
    '/roma/sgrosso-post-ristrutturazione/': {
        'title': 'Pulizia post-ristrutturazione Roma — Sopralluogo gratuito | Trovapulizie',
        'desc': 'Pulizia post-ristrutturazione a Roma: via polvere e residui di cantiere con imprese verificate. Sopralluogo gratuito e preventivo a prezzo fisso.',
        'h1': 'Dopo il cantiere, la casa pronta da abitare in un giorno',
    },
    '/roma/sgrosso-trasloco/': {
        'title': 'Pulizia post trasloco Roma — Sopralluogo gratuito | Trovapulizie',
        'desc': 'Pulizia post trasloco a Roma, prima di entrare o dopo aver lasciato casa. Imprese verificate, sopralluogo gratuito, preventivo a prezzo fisso.',
        'h1': 'Prima di entrare o dopo aver lasciato: la casa pulita per il trasloco',
    },
    '/roma/sgrosso-fine-affitto/': {
        'title': 'Pulizia fine affitto Roma — Sopralluogo gratuito | Trovapulizie',
        'desc': "Pulizia fine affitto a Roma per la riconsegna dell'immobile senza contestazioni. Imprese verificate, sopralluogo gratuito, prezzo fisso garantito.",
        'h1': 'Pulizia fine affitto: pronta per la riconsegna',
    },
    '/roma/sgrosso-prima-pulizia/': {
        'title': 'Pulizia straordinaria casa Roma — Sopralluogo gratuito | Trovapulizie',
        'desc': 'Pulizia straordinaria a Roma per case trascurate da tempo: via lo sporco accumulato con imprese verificate. Sopralluogo gratuito, prezzo fisso.',
        'h1': 'Pulizia straordinaria: via lo sporco accumulato nel tempo',
    },
}

def fetch(path):
    req = urllib.request.Request(BASE + path + '?qa=' + str(random.randint(1, 10**9)), headers={'User-Agent': 'Mozilla/5.0 qa-seo', 'Cache-Control': 'no-cache'})
    with urllib.request.urlopen(req, timeout=30) as r:
        return r.status, r.read().decode('utf-8', 'ignore')

def attr(s, pattern):
    m = re.search(pattern, s, re.S)
    return html.unescape(m.group(1)).strip() if m else None

ko = 0
def check(name, ok, detail=''):
    global ko
    print(('OK  ' if ok else 'KO  ') + name + ('  [' + str(detail)[:160] + ']' if detail else ''))
    if not ok: ko += 1

for path, exp in PAGES.items():
    print('---', path)
    status, s = fetch(path)
    check('200', status == 200, status)
    title = attr(s, r'<title>(.*?)</title>')
    check('title', title == exp['title'], title)
    desc = attr(s, r'<meta name="description" content="(.*?)"')
    check('description', desc == exp['desc'], desc)
    canon = attr(s, r'<link rel="canonical" href="(.*?)"')
    check('canonical = URL', canon == BASE + path, canon)
    robots = attr(s, r"<meta name='robots' content='(.*?)'") or attr(s, r'<meta name="robots" content="(.*?)"')
    check('nessun noindex', not robots or 'noindex' not in robots, robots)
    h1 = attr(s, r'<h1[^>]*>(.*?)</h1>')
    check('H1', h1 and re.sub(r'<br\s*/?>', ' ', h1).strip() == exp['h1'], h1)
    blocks = re.findall(r'<script type="application/ld\+json">(.*?)</script>', s, re.S)
    types = []
    for b in blocks:
        try: types.append(json.loads(b).get('@type'))
        except Exception: types.append('INVALID')
    check('un solo Service', types.count('Service') == 1, types)
    check('un solo FAQPage', types.count('FAQPage') == 1, types)
    svc = next((json.loads(b) for b in blocks if '"Service"' in b), None)
    ps = ((svc or {}).get('offers') or {}).get('priceSpecification') or {}
    check('offerta 200-700 EUR', ps.get('minPrice') == 200 and ps.get('maxPrice') == 700 and ps.get('priceCurrency') == 'EUR' and (svc or {}).get('url') == BASE + path, ps)
    check('nessun aggregateRating', 'aggregateRating' not in s, '')

print('ESITO: ' + ('verde' if ko == 0 else f'{ko} controlli falliti'))
sys.exit(1 if ko else 0)
