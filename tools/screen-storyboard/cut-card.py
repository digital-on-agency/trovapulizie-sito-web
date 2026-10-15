#!/usr/bin/env python3
"""Ritaglia la card "Laura Bianchi" dallo screen Figma del passo 2 e produce la texture con alpha
per l'overlay 3D (assets/img/tiles/card-laura.png).

Uso: python3 tools/screen-storyboard/cut-card.py

Il box della card viene rilevato sul PNG sorgente (card bianca su sfondo grigio #f7f8fa) cercando il
primo e l'ultimo pixel bianco nella zona dei risultati; il raggio degli angoli dal primo pixel bianco
della riga superiore. Intorno al box resta un margine trasparente (MARGIN) cosi' il bordo lo definisce
l'alpha della texture e non il piano Three.js.

Lo screen sorgente e' 1179x2556 ma nel video e' mostrato a 1170x2532: lo script stampa i box gia'
riscalati nello spazio 1170x2532 da riportare in assets/js/screen-timeline.js (CARD) e in
tools/screen-storyboard/index.html (#cardHi, cardPatch, #prenotaPress).
"""
import os
import numpy as np
from PIL import Image, ImageDraw

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.abspath(os.path.join(HERE, '..', '..'))
SRC = os.path.join(ROOT, 'assets', 'img', 'screen-step2-cleaner.png')
OUT = os.path.join(ROOT, 'assets', 'img', 'tiles', 'card-laura.png')
VIDEO_W, VIDEO_H = 1170, 2532
MARGIN = 12          # px sorgente di trasparenza intorno alla card
ZONE = (40, 730, 1140, 1300)   # x0, y0, x1, y1 sorgente: zona in cui cercare la prima card
SS = 4               # supersampling della maschera per angoli antialiasati

src = Image.open(SRC).convert('RGB')
W, H = src.size
sx, sy = VIDEO_W / W, VIDEO_H / H
a = np.array(src).astype(int)
white = (a[:, :, 0] >= 250) & (a[:, :, 1] >= 250) & (a[:, :, 2] >= 250)

x0, y0, x1, y1 = ZONE
sub = white[y0:y1, x0:x1]
rows = np.where(sub.sum(axis=1) > 0)[0]
cols = np.where(sub.sum(axis=0) > 0)[0]
top, bot = y0 + rows[0], y0 + rows[-1]
left, right = x0 + cols[0], x0 + cols[-1]
radius = int(np.where(white[top, :])[0][0] - left)
cw, ch = right - left + 1, bot - top + 1

# Bottone "Prenota ora" (verde brand #00C896) dentro la card
green = (np.abs(a[:, :, 0]) < 25) & (np.abs(a[:, :, 1] - 200) < 25) & (np.abs(a[:, :, 2] - 150) < 25)
g = green[top:bot + 1, left:right + 1]
gr = np.where(g.sum(axis=1) > cw * 0.5)[0]
gc = np.where(g[gr].sum(axis=0) > 0)[0]
btn = (left + gc[0], top + gr[0], left + gc[-1] + 1, top + gr[-1] + 1)

# Texture: crop con margine, alpha = rettangolo arrotondato della card
bx0, by0, bx1, by1 = left - MARGIN, top - MARGIN, right + 1 + MARGIN, bot + 1 + MARGIN
tex = src.crop((bx0, by0, bx1, by1)).convert('RGBA')
tw, th = tex.size
mask_ss = Image.new('L', (tw * SS, th * SS), 0)
ImageDraw.Draw(mask_ss).rounded_rectangle(
    (MARGIN * SS, MARGIN * SS, (MARGIN + cw) * SS - 1, (MARGIN + ch) * SS - 1), radius=radius * SS, fill=255)
mask = mask_ss.resize((tw, th), Image.LANCZOS)
rgb = np.array(tex)[:, :, :3]
alpha = np.array(mask)
# fuori dalla card il colore resta bianco: niente frangia scura sui bordi antialiasati
rgb = np.where(alpha[:, :, None] == 0, 255, rgb).astype(np.uint8)
out = np.dstack([rgb, alpha]).astype(np.uint8)
Image.fromarray(out, 'RGBA').save(OUT, optimize=True)

def box_video(x0, y0, x1, y1):
    return (x0 * sx, y0 * sy, (x1 - x0) * sx, (y1 - y0) * sy)

print('sorgente %dx%d, scala video sx=%.6f sy=%.6f' % (W, H, sx, sy))
print('card sorgente px: x %d..%d y %d..%d (w %d h %d) raggio %d' % (left, right, top, bot, cw, ch, radius))
print('texture %s: %dx%d (margine %d)' % (os.path.relpath(OUT, ROOT), tw, th, MARGIN))
cx, cy, cww, chh = box_video(bx0, by0, bx1, by1)
print('CARD (screen-timeline.js, spazio 1170x2532, texture con margine): x %.1f y %.1f w %.1f h %.1f' % (cx, cy, cww, chh))
hx, hy, hw, hh = box_video(left, top, right + 1, bot + 1)
print('#cardHi (storyboard): left %.0f top %.0f width %.0f height %.0f radius %.0f' % (hx, hy, hw, hh, radius * sx))
print('cardPatch (storyboard): left %.0f top %.0f width %.0f height %.0f' % (round(cx), round(cy), round(cww), round(chh)))
px, py, pw, ph = box_video(*btn)
print('#prenotaPress (storyboard): left %.0f top %.0f width %.0f height %.0f radius %.0f' % (px, py, pw, ph, ph / 2))
