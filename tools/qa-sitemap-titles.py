#!/usr/bin/env python3
"""QA della sitemap e dei title (TICKET-95): python3 tools/qa-sitemap-titles.py [base-url]

Legge wp-sitemap.xml e le sue sotto-sitemap e verifica: nessuna sitemap degli autori, nessun URL /author/,
ogni URL risponde 200 senza redirect, un solo <title> per pagina e lungo al massimo 60 caratteri dopo la
decodifica delle entita HTML. Esce con 1 se un controllo fallisce.
"""
import html, random, re, sys, urllib.error, urllib.request

BASE = (sys.argv[1] if len(sys.argv) > 1 else 'https://trovapulizie.it').rstrip('/')
MAX_TITLE = 60
fails = 0


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


opener = urllib.request.build_opener(NoRedirect)


def get(url):
    sep = '&' if '?' in url else '?'
    req = urllib.request.Request(url + sep + 'qa=' + str(random.randint(1, 10**9)), headers={'User-Agent': 'Mozilla/5.0 qa-sitemap'})
    try:
        r = opener.open(req, timeout=30)
        return r.status, r.read().decode('utf-8', 'ignore')
    except urllib.error.HTTPError as e:
        return e.code, ''


def check(name, ok, detail=''):
    global fails
    print(('OK  ' if ok else 'KO  ') + name + ('  [' + str(detail)[:200] + ']' if detail else ''))
    if not ok:
        fails += 1


status, index = get(BASE + '/wp-sitemap.xml')
check('sitemap indice 200', status == 200, status)
subs = re.findall(r'<loc>([^<]+)</loc>', index)
check('nessuna sitemap degli autori', not any('sitemap-users' in s for s in subs), [s for s in subs if 'users' in s])

urls = []
for s in subs:
    _, body = get(s)
    urls += re.findall(r'<loc>([^<]+)</loc>', body)
check('nessun URL /author/ in sitemap', not any('/author/' in u for u in urls), [u for u in urls if '/author/' in u])

for u in urls:
    status, body = get(u)
    titles = re.findall(r'<title>(.*?)</title>', body, re.S)
    title = html.unescape(titles[0].strip()) if titles else ''
    path = u.replace(BASE, '') or '/'
    check(path + ' 200 senza redirect', status == 200, status)
    check(path + ' un solo title <= %d' % MAX_TITLE, len(titles) == 1 and 0 < len(title) <= MAX_TITLE, '%d: %s' % (len(title), title))

print('ESITO: ' + ('verde (%d URL)' % len(urls) if not fails else '%d controlli falliti' % fails))
sys.exit(1 if fails else 0)
