#!/usr/bin/env python3
"""Crawl dei link interni (cutover-checklist A8): python3 tools/qa-links.py [base-url]

Parte dalla sitemap di WordPress (/wp-sitemap.xml e sotto-sitemap), apre ogni pagina, raccoglie i link verso lo
stesso host (ancore comprese: la pagina di destinazione deve esistere e l'id deve comparire nell'HTML) e controlla
ogni destinazione senza seguire i redirect. Segnala: 4xx/5xx, redirect a catena (un redirect che porta a un altro
redirect), ancore che non esistono sulla pagina di arrivo. Esce con 1 se trova problemi.
Solo libreria standard. Cache dell'HTML: aggiunge ?qa=<timestamp> alle pagine aperte.
"""
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

BASE = (sys.argv[1] if len(sys.argv) > 1 else 'https://lightgoldenrodyellow-duck-667893.hostingersite.com').rstrip('/')
HOST = urllib.parse.urlparse(BASE).netloc
STAMP = str(int(time.time()))


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k):
        return None


opener = urllib.request.build_opener(NoRedirect)


def fetch(url, follow=False):
    """(status, location, body) senza seguire i redirect."""
    req = urllib.request.Request(url, headers={'User-Agent': 'tp-qa-links'})
    try:
        r = (urllib.request.urlopen if follow else opener.open)(req, timeout=30)
        return r.status, r.headers.get('Location'), r.read().decode('utf-8', 'replace')
    except urllib.error.HTTPError as e:
        return e.code, e.headers.get('Location'), ''
    except Exception as e:  # noqa: BLE001
        return 0, None, str(e)


def bust(url):
    return url + ('&' if '?' in url else '?') + 'qa=' + STAMP


def sitemap_pages():
    _, _, idx = fetch(bust(BASE + '/wp-sitemap.xml'), follow=True)
    pages = []
    for sm in re.findall(r'<loc>([^<]+)</loc>', idx):
        _, _, body = fetch(bust(sm), follow=True)
        pages += re.findall(r'<loc>([^<]+)</loc>', body)
    return sorted(set(pages))


def main():
    pages = sitemap_pages()
    print('pagine in sitemap:', len(pages))
    html = {}
    links = {}  # destinazione -> pagine che la linkano
    for p in pages:
        status, _, body = fetch(bust(p))
        html[p.split('#')[0]] = body
        if status != 200:
            print('KO sitemap', status, p)
        for href in re.findall(r'href="([^"]+)"', body):
            href = href.replace('&amp;', '&')
            u = urllib.parse.urljoin(p, href)
            pu = urllib.parse.urlparse(u)
            if pu.netloc != HOST or pu.path.startswith(('/wp-content', '/wp-includes', '/wp-json', '/xmlrpc')):
                continue
            if re.search(r'\.(css|js|png|jpe?g|webp|svg|ico|woff2?|mp4|xml|txt)$', pu.path):
                continue
            links.setdefault(u, set()).add(p)
    problems = 0
    for u in sorted(links):
        page = u.split('#')[0]
        frag = urllib.parse.urlparse(u).fragment
        status, loc, body = fetch(bust(page))
        if status in (301, 302, 307, 308):
            s2, _, _ = fetch(urllib.parse.urljoin(page, loc))
            if s2 in (301, 302, 307, 308):
                problems += 1
                print('KO catena', status, page, '->', loc, '->', s2, '| da', sorted(links[u])[0])
            continue
        if status != 200:
            problems += 1
            print('KO', status, u, '| da', ', '.join(sorted(links[u])[:3]))
            continue
        if frag and ('id="%s"' % frag) not in body:
            problems += 1
            print('KO ancora mancante', u, '| da', sorted(links[u])[0])
    print(('TUTTO OK' if not problems else 'KO %d' % problems), 'su', len(links), 'link interni')
    sys.exit(1 if problems else 0)


if __name__ == '__main__':
    main()
