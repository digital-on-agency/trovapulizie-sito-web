#!/usr/bin/env python3
"""Genera gli asset statici della mappa interattiva per CAP (ADR-003, TICKET-01).

Input:  roma_cap.geojson del vault (72 MultiPolygon, dataset Zornade su dati OSM, ODbL)
        zone-quartieri-export.json accanto a questo script (export una tantum di zone_quartieri)
Output: assets/img/zones-map-cap.svg      frammento SVG: un path per CAP + contorno della citta'
        assets/data/cap-centroids.json    centroide dell'anello piu' grande di ogni CAP
        assets/data/cap-names.json        nome, stato e macro zona per CAP (null dove manca)

Uso:
    python3 tools/zones-map/build-svg.py [--geojson <path>] [--tol 0.0004] [--width 1000]

Scelte e trappole (dettaglio in sviluppo/mappa-interattiva-design.md):
- Proiezione equirettangolare con correzione cos(lat) a 41.9 gradi: a questa scala la differenza
  da una proiezione conforme non e' visibile, e non serve nessuna libreria.
- Douglas-Peucker per anello. [ATTENZIONE] su un anello chiuso il primo e l'ultimo punto
  coincidono: chi misura la distanza dalla retta primo-ultimo ottiene 0 per ogni punto e l'anello
  degenera a due punti (memoria del vault reference_osm_svg_map_pipeline). Qui la trappola e'
  disinnescata due volte: distanza_da_segmento tratta il segmento nullo come un punto, e
  semplifica_anello apre comunque l'anello e lo spezza nel punto piu' lontano dal primo, cosi' il
  vertice di chiusura non entra mai nella semplificazione. Il secondo accorgimento serve da solo:
  senza lo spezzamento, meta' anello verrebbe semplificata contro una corda sbagliata.
- La semplificazione e' per anello: due CAP confinanti possono scostarsi di pochi decimi di px
  sul confine comune. Lo stroke bianco di 1px fra i CAP lo copre; il contorno della citta' viene
  ricavato prima di semplificare, dagli spigoli che appartengono a un solo poligono.
"""
import argparse
import json
import math
import os
from collections import Counter, defaultdict

QUI = os.path.dirname(os.path.abspath(__file__))
TEMA = os.path.normpath(os.path.join(QUI, '..', '..'))
GEOJSON_DEFAULT = os.path.normpath(os.path.join(
    TEMA, '..', '..', 'vault-digitalon', 'clients', 'trovapulizie', 'projects',
    'portale-transazionale', 'backlog', 'mappa-quartieri', 'roma_cap.geojson'))
LAT_RIFERIMENTO = 41.9


# ---- Geometria ----

def distanza_da_segmento(p, a, b):
    """Distanza perpendicolare di p dal segmento ab (gradi, piano locale)."""
    (px, py), (ax, ay), (bx, by) = p, a, b
    dx, dy = bx - ax, by - ay
    if dx == 0 and dy == 0:
        return math.hypot(px - ax, py - ay)
    t = ((px - ax) * dx + (py - ay) * dy) / (dx * dx + dy * dy)
    t = max(0.0, min(1.0, t))
    return math.hypot(px - (ax + t * dx), py - (ay + t * dy))


def douglas_peucker(punti, tol):
    """Semplifica una catena aperta (non un anello: vedi semplifica_anello).

    Iterativo con una pila di intervalli: le catene costiere arrivano a migliaia di punti e la
    versione ricorsiva supererebbe il limite di ricorsione di Python nel caso peggiore."""
    n = len(punti)
    if n < 3:
        return list(punti)
    tieni = [False] * n
    tieni[0] = tieni[n - 1] = True
    pila = [(0, n - 1)]
    while pila:
        inizio, fine = pila.pop()
        if fine - inizio < 2:
            continue
        indice, massimo = inizio, -1.0
        for i in range(inizio + 1, fine):
            d = distanza_da_segmento(punti[i], punti[inizio], punti[fine])
            if d > massimo:
                indice, massimo = i, d
        if massimo > tol:
            tieni[indice] = True
            pila.append((inizio, indice))
            pila.append((indice, fine))
    return [p for p, k in zip(punti, tieni) if k]


def semplifica_anello(anello, tol):
    """DP su un anello chiuso: lo apre, lo spezza nel punto piu' lontano dal primo e lo richiude."""
    punti = list(anello)
    if len(punti) > 1 and punti[0] == punti[-1]:
        punti = punti[:-1]
    if len(punti) < 4:
        return None
    primo = punti[0]
    lontano = max(range(1, len(punti)), key=lambda i: math.hypot(punti[i][0] - primo[0], punti[i][1] - primo[1]))
    catena_a = douglas_peucker(punti[:lontano + 1], tol)
    catena_b = douglas_peucker(punti[lontano:] + [primo], tol)
    fuso = catena_a[:-1] + catena_b[:-1]
    if len(fuso) < 3:
        return None
    return fuso + [fuso[0]]


def area_firmata(anello):
    """Area con il segno (shoelace). Serve per scegliere l'anello piu' grande e per il centroide."""
    somma = 0.0
    for (x1, y1), (x2, y2) in zip(anello, anello[1:]):
        somma += x1 * y2 - x2 * y1
    return somma / 2.0


def centroide(anello):
    """Centroide d'area dell'anello (non la media dei vertici: su forme allungate sbaglia)."""
    a = area_firmata(anello)
    if abs(a) < 1e-12:
        n = len(anello) - 1
        return (sum(p[0] for p in anello[:n]) / n, sum(p[1] for p in anello[:n]) / n)
    cx = cy = 0.0
    for (x1, y1), (x2, y2) in zip(anello, anello[1:]):
        f = x1 * y2 - x2 * y1
        cx += (x1 + x2) * f
        cy += (y1 + y2) * f
    return (cx / (6.0 * a), cy / (6.0 * a))


def anelli_di_confine(features):
    """Contorno della citta': gli spigoli che compaiono una volta sola appartengono al bordo esterno
    (il dataset e' una tassellazione, i confini interni sono condivisi da due CAP). Gli spigoli
    rimasti vengono ricuciti in anelli seguendo i vertici."""
    conteggio = Counter()
    for f in features:
        for poligono in f['geometry']['coordinates']:
            for anello in poligono:
                punti = [tuple(p) for p in anello]
                for a, b in zip(punti, punti[1:]):
                    conteggio[frozenset((a, b))] += 1
    vicini = defaultdict(list)
    for spigolo, quante in conteggio.items():
        if quante == 1:
            a, b = tuple(spigolo)
            vicini[a].append(b)
            vicini[b].append(a)
    anelli, visti = [], set()
    for partenza in list(vicini):
        if partenza in visti:
            continue
        catena, corrente, precedente = [partenza], partenza, None
        visti.add(partenza)
        while True:
            avanti = [v for v in vicini[corrente] if v != precedente and v not in visti]
            if not avanti:
                break
            precedente, corrente = corrente, avanti[0]
            visti.add(corrente)
            catena.append(corrente)
        if len(catena) >= 4:
            anelli.append(catena + [catena[0]])
    return anelli


# ---- Proiezione e serializzazione ----

class Proiezione:
    def __init__(self, lon_min, lat_max, scala):
        self.lon_min, self.lat_max, self.scala = lon_min, lat_max, scala
        self.kx = math.cos(math.radians(LAT_RIFERIMENTO))

    def __call__(self, lon, lat):
        return ((lon - self.lon_min) * self.kx * self.scala, (self.lat_max - lat) * self.scala)


def path_d(anelli, proietta):
    """Un solo attributo d con un sottotracciato per anello, coordinate a un decimale."""
    pezzi = []
    for anello in anelli:
        coordinate = [proietta(lon, lat) for lon, lat in anello[:-1]]
        if len(coordinate) < 3:
            continue
        pezzi.append('M' + 'L'.join('%.1f %.1f' % c for c in coordinate) + 'Z')
    return ''.join(pezzi)


def main():
    ap = argparse.ArgumentParser(description='Genera SVG, centroidi e nomi dei CAP di Roma.')
    ap.add_argument('--geojson', default=GEOJSON_DEFAULT)
    ap.add_argument('--tol', type=float, default=0.0004, help='tolleranza DP in gradi (0.0004 = circa 40 m)')
    ap.add_argument('--width', type=float, default=1000.0)
    args = ap.parse_args()

    with open(args.geojson, encoding='utf-8') as fh:
        dati = json.load(fh)
    features = dati['features']

    contorno = anelli_di_confine(features)

    # Semplificazione (in gradi) prima di proiettare: la tolleranza resta leggibile come distanza reale.
    per_cap, saltati = {}, []
    for f in features:
        cap = f['properties']['cap']
        anelli = []
        for poligono in f['geometry']['coordinates']:
            for anello in poligono:
                semplificato = semplifica_anello([tuple(p) for p in anello], args.tol)
                if semplificato:
                    anelli.append(semplificato)
                else:
                    saltati.append(cap)
        if anelli:
            per_cap[cap] = anelli
    contorno = [a for a in (semplifica_anello(c, args.tol) for c in contorno) if a]

    tutti = [p for anelli in per_cap.values() for anello in anelli for p in anello]
    lon_min = min(p[0] for p in tutti)
    lon_max = max(p[0] for p in tutti)
    lat_min = min(p[1] for p in tutti)
    lat_max = max(p[1] for p in tutti)
    kx = math.cos(math.radians(LAT_RIFERIMENTO))
    scala = args.width / ((lon_max - lon_min) * kx)
    altezza = (lat_max - lat_min) * scala
    proietta = Proiezione(lon_min, lat_max, scala)

    nomi_db = {}
    percorso_export = os.path.join(QUI, 'zone-quartieri-export.json')
    if os.path.exists(percorso_export):
        with open(percorso_export, encoding='utf-8') as fh:
            for riga in json.load(fh):
                nomi_db[riga['cap']] = {
                    'name': riga.get('quartiere'),
                    'status': riga.get('stato'),
                    'macro': riga.get('macro_zona'),
                }

    # SVG: ordine per CAP crescente, cosi' due build dello stesso input danno file identici.
    righe = ['<svg viewBox="0 0 %d %d" xmlns="http://www.w3.org/2000/svg" class="zones-map__svg" role="img" aria-label="Mappa dei CAP di Roma">' % (round(args.width), round(altezza))]
    righe.append('<g class="zones-map__caps">')
    for cap in sorted(per_cap):
        meta = nomi_db.get(cap) or {}
        # name e macro vengono dal tema, non dall'endpoint: il contratto della copertura non li porta
        # (name e' facoltativo li', macro non c'e' proprio) e la mappa deve saper scrivere il nome
        # della zona anche quando i dati non arrivano.
        attributi = ''.join(
            ' data-%s="%s"' % (chiave, str(valore).replace('&', '&amp;').replace('"', '&quot;'))
            for chiave, valore in (('name', meta.get('name')), ('macro', meta.get('macro')))
            if valore
        )
        righe.append('<path data-cap="%s"%s data-level="0" d="%s"/>' % (cap, attributi, path_d(per_cap[cap], proietta)))
    righe.append('</g>')
    righe.append('<path class="zones-map__outline" d="%s"/>' % path_d(contorno, proietta))
    righe.append('</svg>')
    svg = '\n'.join(righe) + '\n'

    centroidi = []
    for cap in sorted(per_cap):
        maggiore = max(per_cap[cap], key=lambda a: abs(area_firmata(a)))
        lon, lat = centroide(maggiore)
        centroidi.append({'cap': cap, 'lat': round(lat, 5), 'lon': round(lon, 5)})

    nomi = {}
    for cap in sorted(per_cap):
        nomi[cap] = nomi_db.get(cap) or {'name': None, 'status': None, 'macro': None}

    scrivi(os.path.join(TEMA, 'assets', 'img', 'zones-map-cap.svg'), svg)
    scrivi(os.path.join(TEMA, 'assets', 'data', 'cap-centroids.json'), json.dumps(centroidi, ensure_ascii=False, indent=1) + '\n')
    scrivi(os.path.join(TEMA, 'assets', 'data', 'cap-names.json'), json.dumps(nomi, ensure_ascii=False, indent=1) + '\n')

    print('CAP: %d   anelli scartati: %d   anelli di contorno: %d' % (len(per_cap), len(saltati), len(contorno)))
    print('viewBox 0 0 %d %d   scala %.1f px/grado' % (round(args.width), round(altezza), scala))
    print('SVG: %.1f KB   nomi dal DB: %d su %d' % (len(svg.encode('utf-8')) / 1024, sum(1 for v in nomi.values() if v['name']), len(nomi)))


def scrivi(percorso, contenuto):
    os.makedirs(os.path.dirname(percorso), exist_ok=True)
    with open(percorso, 'w', encoding='utf-8') as fh:
        fh.write(contenuto)


if __name__ == '__main__':
    main()
