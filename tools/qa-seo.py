#!/usr/bin/env python3
"""QA SEO delle cinque landing Sgrosso (TICKET-48): python3 tools/qa-seo.py <base-url> [--home <percorso-anteprima> si|no]

Scarica le cinque pagine (con un parametro anti-cache) e verifica title, meta description, canonical,
assenza di noindex, un solo JSON-LD Service con offerta 200-700 EUR, un solo FAQPage, H1 della variante.
I valori attesi sono quelli di VARIANT_CONFIG del live (inc/sgrosso-variants.php). Esce con 1 se fallisce.

Caso pagina iniziale (TICKET-130): con --home <percorso> si/no si controllano la radice e la pagina d'anteprima della
Home v2 (es. /anteprima-pagina-iniziale/). Secondo argomento: "si" se l'anteprima e' gia' la pagina iniziale, "no" se la
pagina iniziale e' ancora la Home attuale. Radice: un solo Organization e un solo WebSite, nessun AggregateRating, title
col nome del sito, nessun noindex. Anteprima non iniziale: nessun Organization ne' WebSite, noindex presente, un solo H1,
al piu' un FAQPage, nessun AggregateRating. Anteprima iniziale: risponde con un redirect alla radice (o, se servita, ha
Organization e WebSite e nessun noindex).
Con --solo-home si salta il controllo delle cinque landing (utile su un ambiente di prova senza le landing compilate).
"""
import html, json, re, sys, random, urllib.request

HOME = sys.argv.index('--home') if '--home' in sys.argv else -1
BASE = (sys.argv[1] if len(sys.argv) > 1 else 'https://lightgoldenrodyellow-duck-667893.hostingersite.com').rstrip('/')

PAGES = {
    '/roma/sgrosso-casa/': {
        'title': 'Sgrosso casa Roma, sopralluogo gratuito | Trovapulizie',
        'desc': 'Sgrosso casa a Roma: pulizia straordinaria post-cantiere o trasloco con imprese verificate. Sopralluogo gratuito, prezzo fisso, nessuna sorpresa.',
        'h1': 'Sgrosso casa: la tua casa torna come nuova',
    },
    '/roma/sgrosso-post-ristrutturazione/': {
        'title': 'Pulizie post ristrutturazione Roma | Trovapulizie',
        'desc': 'Pulizia post-ristrutturazione a Roma: via polvere e residui di cantiere con imprese verificate. Sopralluogo gratuito e preventivo a prezzo fisso.',
        'h1': 'Dopo il cantiere, la casa pronta da abitare in un giorno',
    },
    '/roma/sgrosso-trasloco/': {
        'title': 'Pulizie post trasloco Roma | Trovapulizie',
        'desc': 'Pulizia post trasloco a Roma, prima di entrare o dopo aver lasciato casa. Imprese verificate, sopralluogo gratuito, preventivo a prezzo fisso.',
        'h1': 'Prima di entrare o dopo aver lasciato: la casa pulita per il trasloco',
    },
    '/roma/sgrosso-fine-affitto/': {
        'title': 'Pulizia fine affitto Roma, sopralluogo gratis | Trovapulizie',
        'desc': "Pulizia fine affitto a Roma per la riconsegna dell'immobile senza contestazioni. Imprese verificate, sopralluogo gratuito, prezzo fisso garantito.",
        'h1': 'Pulizia fine affitto: pronta per la riconsegna',
    },
    '/roma/sgrosso-prima-pulizia/': {
        'title': 'Pulizia approfondita casa Roma | Trovapulizie',
        'desc': 'Pulizia approfondita a Roma per case trascurate da tempo: via lo sporco accumulato con imprese verificate. Sopralluogo gratuito, prezzo fisso.',
        'h1': 'Pulizia approfondita della casa: via lo sporco accumulato nel tempo',
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

for path, exp in ([] if '--solo-home' in sys.argv else PAGES.items()):
    print('---', path)
    status, s = fetch(path)
    check('200', status == 200, status)
    title = attr(s, r'<title>(.*?)</title>')
    check('title', title == exp['title'], title)
    check('title <= 60 caratteri', title is not None and len(title) <= 60, len(title or ''))
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

def ld_types(s):
    out = []
    for b in re.findall(r'<script type="application/ld\+json">(.*?)</script>', s, re.S):
        try: out.append(json.loads(b).get('@type'))
        except Exception: out.append('INVALID')
    return out

def no_redirect_fetch(path):
    class NoRedirect(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, *a, **k): return None
    opener = urllib.request.build_opener(NoRedirect)
    req = urllib.request.Request(BASE + path + '?qa=' + str(random.randint(1, 10**9)), headers={'User-Agent': 'Mozilla/5.0 qa-seo', 'Cache-Control': 'no-cache'})
    try:
        r = opener.open(req, timeout=30)
        return r.status, r.read().decode('utf-8', 'ignore'), r.headers
    except urllib.error.HTTPError as e:
        return e.code, '', e.headers

if HOME != -1:
    prev_path, prev_front = sys.argv[HOME + 1], sys.argv[HOME + 2] == 'si'
    print('--- pagina iniziale (radice) e anteprima', prev_path, '(iniziale: ' + ('si' if prev_front else 'no') + ')')
    status, s = fetch('/')
    t = ld_types(s)
    check('radice 200', status == 200, status)
    check('radice: un solo Organization e un solo WebSite', t.count('Organization') == 1 and t.count('WebSite') == 1, t)
    check('radice: nessun aggregateRating', 'aggregateRating' not in s.lower(), '')
    check('radice: title del sito', (attr(s, r'<title>(.*?)</title>') or '').startswith('Trovapulizie'), attr(s, r'<title>(.*?)</title>'))
    robots = attr(s, r"<meta name='robots' content='(.*?)'") or ''
    check('radice: nessun noindex', 'noindex' not in robots, robots)
    check('radice: un solo FAQPage al piu\'', t.count('FAQPage') <= 1, t)
    status, s2, h = no_redirect_fetch(prev_path)
    if prev_front:
        if status in (301, 302):
            check('anteprima iniziale: redirect alla radice', (h.get('Location') or '').split('?')[0].rstrip('/') == BASE.rstrip('/'), h.get('Location'))
        else:
            t2 = ld_types(s2)
            check('anteprima iniziale servita: Organization e WebSite, nessun noindex', t2.count('Organization') == 1 and t2.count('WebSite') == 1 and 'noindex' not in (attr(s2, r"<meta name='robots' content='(.*?)'") or ''), t2)
    else:
        t2 = ld_types(s2)
        robots2 = attr(s2, r"<meta name='robots' content='(.*?)'") or ''
        check('anteprima 200', status == 200, status)
        check('anteprima: nessun Organization ne\' WebSite (non e\' la pagina iniziale)', 'Organization' not in t2 and 'WebSite' not in t2, t2)
        check('anteprima: noindex', 'noindex' in robots2 and 'noindex' in (h.get('X-Robots-Tag') or ''), robots2 + ' / ' + str(h.get('X-Robots-Tag')))
        check('anteprima: un solo H1', len(re.findall(r'<h1[\s>]', s2)) == 1, len(re.findall(r'<h1[\s>]', s2)))
        check('anteprima: al piu\' un FAQPage', t2.count('FAQPage') <= 1, t2)
        check('anteprima: nessun aggregateRating', 'aggregaterating' not in s2.lower(), '')

print('ESITO: ' + ('verde' if ko == 0 else f'{ko} controlli falliti'))
sys.exit(1 if ko else 0)
