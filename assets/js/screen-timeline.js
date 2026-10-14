/* Timeline condivisa dello schermo del telefono: la usano sia il video base (tools/screen-storyboard)
   sia l'overlay 3D (phone-3d.js). Tempi in secondi sul video; i terzi del pin cadono a total/3 e 2*total/3. */
export const T = {
	total: 12.6,
	s1: { tilesStart: 0.4, tilesEnd: 2.4, select: 2.4, typeStart: 2.6, typeEnd: 3.6, press: 3.7 },
	s2: { start: 4.0, slide: 0.3, cardStart: 5.0, cardEnd: 6.6, highlight: 6.5, press: 7.0 },
	s3: { start: 8.0, slide: 0.3, press: 9.3, thanks: 9.9, bandStart: 11.4, bandEnd: 12.0 }
};

export const ADDRESS = 'Via Tuscolana 118, Roma';
export const TILE_ORDER = ['casa', 'commerciale', 'condominio', 'affitti-brevi', 'sanitario', 'cantiere', 'casa'];

// Centri in UV (0..1, origine alto-sinistra) sullo schermo 1170x2532 e dimensione delle tile (192px con ombra)
export const TILES = {
	casa: [0.1838, 0.34], commerciale: [0.5, 0.34], condominio: [0.8162, 0.34],
	'affitti-brevi': [0.1838, 0.483], sanitario: [0.5, 0.483], cantiere: [0.8162, 0.483]
};
export const TILE_SIZE = [192 / 1170, 192 / 2532];
// Card "Laura Bianchi" nei risultati: box 51..1127 x 683..1262 (bottone incluso), texture con margine 18
export const CARD = { x: 33 / 1170, y: 665 / 2532, w: 1112 / 1170, h: 615 / 2532 };

export const clamp01 = v => Math.max(0, Math.min(1, v));
export const easeOut = v => 1 - Math.pow(1 - v, 3);
export const easeInOut = v => (v < 0.5 ? 4 * v * v * v : 1 - Math.pow(-2 * v + 2, 3) / 2);
export const ramp = (t, a, b) => clamp01((t - a) / (b - a));
export const bump = (t, a, b) => Math.sin(ramp(t, a, b) * Math.PI);

// Sollevamento (0..1) della tile `name` al tempo t: sequenza alternata, l'ultima (casa) resta su e scende prima del tap cerca
export function tileLift(name, t) {
	const { tilesStart, tilesEnd, press } = T.s1;
	const n = TILE_ORDER.length, step = (tilesEnd - tilesStart) / n, win = step * 1.8;
	let lift = 0;
	TILE_ORDER.forEach((tile, i) => {
		if (tile !== name) return;
		const a = tilesStart + i * step;
		if (i === n - 1) lift = Math.max(lift, ramp(t, a, a + step) * (1 - ramp(t, tilesEnd + 0.6, press)));
		else lift = Math.max(lift, bump(t, a, a + win));
	});
	return easeInOut(lift);
}

// Sollevamento (0..1) della card cleaner nel segmento 2
export function cardLift(t) {
	const { cardStart, cardEnd } = T.s2;
	return easeInOut(Math.min(ramp(t, cardStart, cardStart + 0.5), 1 - ramp(t, cardEnd - 0.5, cardEnd)));
}
