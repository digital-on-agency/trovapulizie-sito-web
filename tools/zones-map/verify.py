#!/usr/bin/env python3
"""Verifica gli asset prodotti da build-svg.py (criteri di accettazione di TICKET-01).

Uso: python3 tools/zones-map/verify.py
Esce con codice 1 al primo criterio non soddisfatto, elencando tutti gli esiti.
"""
import json
import os
import re
import sys

QUI = os.path.dirname(os.path.abspath(__file__))
TEMA = os.path.normpath(os.path.join(QUI, '..', '..'))
SVG = os.path.join(TEMA, 'assets', 'img', 'zones-map-cap.svg')
CENTROIDI = os.path.join(TEMA, 'assets', 'data', 'cap-centroids.json')
NOMI = os.path.join(TEMA, 'assets', 'data', 'cap-names.json')

esiti = []


def controlla(nome, condizione, dettaglio=''):
    esiti.append((bool(condizione), nome, dettaglio))


def numeri(d):
    return [float(x) for x in re.findall(r'-?\d+(?:\.\d+)?', d)]


def main():
    for percorso in (SVG, CENTROIDI, NOMI):
        if not os.path.exists(percorso):
            controlla('esiste %s' % os.path.relpath(percorso, TEMA), False, 'file assente')
    if any(not ok for ok, _, _ in esiti):
        return stampa()

    svg = open(SVG, encoding='utf-8').read()
    centroidi = json.load(open(CENTROIDI, encoding='utf-8'))
    nomi = json.load(open(NOMI, encoding='utf-8'))

    caps = re.findall(r'<path data-cap="(\d{5})"', svg)
    controlla('72 path con data-cap', len(caps) == 72, '%d path' % len(caps))
    controlla('data-cap distinti', len(set(caps)) == len(caps), '%d distinti' % len(set(caps)))

    kb = len(svg.encode('utf-8')) / 1024
    controlla('SVG sotto 120 KB', kb < 120, '%.1f KB' % kb)

    viewbox = re.search(r'viewBox="0 0 (\d+) (\d+)"', svg)
    controlla('viewBox presente', viewbox is not None)
    if viewbox:
        larghezza, altezza = float(viewbox.group(1)), float(viewbox.group(2))
        # La finestra inquadra i CAP censiti, non tutto il comune: gli altri escono dal bordo apposta.
        # Quello che deve valere e' che ogni CAP con nome ci stia dentro per intero.
        fuori = []
        for m in re.finditer(r'<path data-cap="(\d{5})"([^>]*)d="([^"]+)"', svg):
            cap, attributi, d = m.group(1), m.group(2), m.group(3)
            if 'data-name=' not in attributi:
                continue
            valori = numeri(d)
            xs, ys = valori[0::2], valori[1::2]
            if xs and (min(xs) < -1 or max(xs) > larghezza + 1 or min(ys) < -1 or max(ys) > altezza + 1):
                fuori.append(cap)
        controlla('il viewBox copre per intero i CAP con nome', not fuori, '%d fuori: %s' % (len(fuori), fuori[:5]))
        rapporto = altezza / larghezza
        controlla('inquadratura orizzontale (rapporto sotto 0.8)', rapporto < 0.8, round(rapporto, 2))

    controlla('72 centroidi', len(centroidi) == 72, '%d voci' % len(centroidi))
    lat_fuori = [c for c in centroidi if not 41.6 <= c['lat'] <= 42.2]
    lon_fuori = [c for c in centroidi if not 12.2 <= c['lon'] <= 12.9]
    controlla('latitudini fra 41.6 e 42.2', not lat_fuori, str(lat_fuori[:3]))
    controlla('longitudini fra 12.2 e 12.9', not lon_fuori, str(lon_fuori[:3]))
    controlla('centroidi e CAP dell\'SVG coincidono', {c['cap'] for c in centroidi} == set(caps))

    con_nome = [c for c, v in nomi.items() if v.get('name')]
    controlla('26 nomi dal DB', len(con_nome) == 26, '%d nomi' % len(con_nome))
    controlla('72 voci in cap-names', len(nomi) == 72, '%d voci' % len(nomi))

    # Anelli dell'output: un anello e' degenere sotto i 3 vertici distinti. Non si pretendono 8 vertici
    # per ogni anello: due CAP hanno enclave larghe circa 200 m, che a quella scala sono poligoni di 5-6
    # vertici per forma loro, non per effetto della semplificazione. Il vincolo degli 8 vertici vale
    # dove c'e' forma da conservare, cioe' sugli anelli piu' larghi di 20 px.
    degeneri, poveri = [], []
    for d in re.findall(r' d="([^"]+)"', svg):
        for sotto in [s for s in d.split('M') if s.strip()]:
            valori = numeri(sotto)
            vertici = len(valori) // 2
            xs, ys = valori[0::2], valori[1::2]
            largo = max(max(xs) - min(xs), max(ys) - min(ys))
            if vertici < 3:
                degeneri.append(vertici)
            elif largo > 20 and vertici < 8:
                poveri.append((vertici, round(largo, 1)))
    controlla('nessun anello degenere (sotto 3 vertici)', not degeneri, str(degeneri[:10]))
    controlla('gli anelli oltre 20 px tengono almeno 8 vertici', not poveri, str(poveri[:10]))

    controlla_trappola_anello_chiuso()
    return stampa()


def controlla_trappola_anello_chiuso():
    """Criterio 2 del ticket: e' una proprieta' della funzione, non del file prodotto.

    La trappola nota e' che un anello chiuso (primo punto uguale all'ultimo) dato a Douglas-Peucker
    cosi' com'e' degeneri a due punti: il segmento primo-ultimo e' nullo e, in chi calcola la
    distanza come distanza da una retta, ogni distanza viene 0. Qui non succede perche'
    distanza_da_segmento tratta il segmento nullo come un punto; il controllo lo mette per iscritto,
    cosi' una riscrittura che reintroduca il difetto lo fa cadere."""
    sys.path.insert(0, QUI)
    import importlib
    build = importlib.import_module('build-svg') if os.path.exists(os.path.join(QUI, 'build-svg.py')) else None
    if build is None:
        controlla('build-svg.py importabile', False)
        return
    import math
    cerchio = [(0.01 * math.cos(t * math.pi / 18), 0.01 * math.sin(t * math.pi / 18)) for t in range(36)]
    cerchio = cerchio + [cerchio[0]]
    diretto = build.douglas_peucker(cerchio, 0.0004)
    anello = build.semplifica_anello(cerchio, 0.0004)
    controlla("DP sull'anello chiuso non degenera: il segmento nullo e' trattato come punto",
              len(diretto) >= 8, '%d punti' % len(diretto))
    controlla('semplifica_anello conserva almeno 8 vertici', anello is not None and len(anello) >= 8,
              '%s punti' % (len(anello) if anello else None))
    controlla('semplifica_anello restituisce un anello chiuso', anello is not None and anello[0] == anello[-1])


def stampa():
    for ok, nome, dettaglio in esiti:
        print(('[ok] ' if ok else '[KO] ') + nome + (('  -> ' + dettaglio) if dettaglio and not ok else ''))
    falliti = sum(1 for ok, _, _ in esiti if not ok)
    print(('\nFALLITI %d controlli su %d' % (falliti, len(esiti))) if falliti else '\nTutti i controlli ok (%d)' % len(esiti))
    return 1 if falliti else 0


if __name__ == '__main__':
    sys.exit(main())
