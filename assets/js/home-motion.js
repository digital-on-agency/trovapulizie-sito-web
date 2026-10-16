/* Come funziona (desktop): phone unico pinnato dalla hero, pose Figma, video scrubbed dallo scroll,
   zoom-through e atterraggio nella card Servizi (ADR-002 rev. 3). Mappa completa: docs/homepage.md §4.

   Parte solo con viewport >= --tp-bp-desktop (1024px) e senza "riduci movimento" (classe motion-ok su <html>); carica GSAP +
   ScrollTrigger da CDN quando la sezione si avvicina e, con WebGL2, importa phone-3d.js (altrimenti resta il
   bezel CSS con transform). Unita' di scroll dalla cima della sezione (viewport): 0-3 video (q = u/3, step
   a terzi), 3-4 zoom-through, 4-4.5 tenuta, 4.5-5.5 rientro nella card, >= 5.5 card live. Il CSS
   (.motion-ok .how-it-works { height: 550vh }) deve restare allineato alle costanti *_VH qui sotto.
   Trigger A (ScrollTrigger, pin + scrub) guida 0-4; oltre il pin lo stato si ricalcola da scrollY in
   syncBeyondPin() perche' un trigger mai attivato in un salto non chiama nessuna callback. */
(function () {
	'use strict';

	// Soglia unica con il CSS (ADR-002 rev. 4): il token --tp-bp-desktop su :root e' la sola fonte di verita'.
	var bp = getComputedStyle(document.documentElement).getPropertyValue('--tp-bp-desktop').trim() || '1024px';
	var mq = window.matchMedia('(min-width: ' + bp + ') and (prefers-reduced-motion: no-preference)');
	if (!mq.matches) return;

	var pinEl = document.getElementById('hiw-phone-pin');
	var phone = document.getElementById('hiw-phone');
	var section = document.getElementById('come-funziona');
	if (!pinEl || !phone || !section) return;

	document.documentElement.classList.add('motion-ok');

	var video = phone.querySelector('video');
	var steps = section.querySelectorAll('[data-step]');
	// Zoom-through (ADR-002 punto 5, rev. 3): dopo i 3 terzi del video, 1 viewport di scroll in cui il video
	// esce dal rettangolo esatto dello schermo 3D fino a tutto il viewport (trigger A, col pin); poi, senza pin
	// (trigger B), 0.5 viewport di tenuta a tutto schermo e 1 viewport di rientro nel rettangolo della foto della
	// prima card di Servizi popolari, dove il video resta in loop al posto della foto.
	var zoomEl = document.getElementById('hiw-zoom');
	var zoomVideo = zoomEl ? zoomEl.querySelector('.hiw-zoom__video') : null;
	var zoomVeil = zoomEl ? zoomEl.querySelector('.hiw-zoom__veil') : null;
	var servicesEl = document.querySelector('.services');
	var cardVisual = servicesEl ? servicesEl.querySelector('.services__card .services__visual') : null;
	var cardVideo = cardVisual ? cardVisual.querySelector('.services__video') : null;
	var VIDEO_VH = 3, ZOOM_VH = 1, HOLD_VH = 0.5, SHRINK_VH = 1;
	var GSAP_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/';

	// Centro del phone rispetto al centro viewport: x in px (canvas 1440), y in frazione di viewport (canvas 980).
	// Posizioni misurate sui render Figma degli stati (le x/y dei nodi ruotati nei metadata sono pre-rotazione, non il bbox).
	// Fonte: 3743:3095 (hero), 3750:3099 (S1), 3798:1087 (S2), 3828:1157 (S3), 3828:1198 (outro).
	// Rotazioni in prospettiva vera (ry/rx/rz) accordate a occhio sulle silhouette Figma: il Figma e' piatto (rotate+skew), qui il volume e' reale.
	// k = quanto la posa segue il canvas Figma di 980px (le pose pinnate si riducono su viewport piu' bassi, la hero no).
	var POSES = {
		hero: { x: 0, y: 0, ry: 0, rx: 0, rz: 0, s: 1, k: 0 },
		// 15/10 notte (richiesta di Guido): telefono piu' grande e oscillazione ridotta. Le pose del Figma
		// (ry +-24, rz +-12, x fino a -62, y fino a 0.143) facevano ballare il telefono a ogni cambio di stato
		// e distraevano dal contenuto dello schermo, che e' il punto della sezione. Qui le rotazioni sono
		// circa dimezzate, x e y quasi costanti: cambia la posa, non la posizione.
		// x positiva: col testo raccolto in un solo blocco a sinistra, il telefono si sposta a destra per
		// bilanciare la composizione (prima restavano ~570px vuoti a destra).
		// 16/10: x da 200 a 383 e y a 0. Con x 200 il telefono restava al centro della propria meta' e la
		// sezione aveva 96px di margine a sinistra contro 373 a destra; ora testo e telefono sono un blocco
		// unico centrato (va insieme al padding 13% di .how-it-works__sticky). Con y 0.075 il telefono stava
		// 63px piu' in basso del testo, che e' centrato sul viewport: a 0 sono centrati entrambi, a qualunque
		// altezza di finestra.
		s1: { x: 383, y: 0, ry: -10, rx: 3, rz: -5, s: 1.2, k: 1 },
		s2: { x: 383, y: 0, ry: 10, rx: 3, rz: 5, s: 1.2, k: 1 },
		s3: { x: 373, y: 0, ry: -5, rx: 2, rz: -3, s: 1.22, k: 1 },
		// La posa finale (raddrizzata, da cui parte lo zoom-through) tiene la stessa scala degli stati:
		// con s: 1 il telefono rimpiccioliva di colpo nell'ultimo segmento, subito prima dello zoom.
		out: { x: 383, y: 0, ry: 0, rx: 0, rz: 0, s: 1.2, k: 1 }
	};
	var SEQ = [POSES.s1, POSES.s2, POSES.s3, POSES.out];
	var KEYS = ['x', 'y', 'ry', 'rx', 'rz', 's', 'k'];

	function lerp(a, b, t) { return a + (b - a) * t; }
	function mix(a, b, t) {
		var o = {};
		for (var i = 0; i < KEYS.length; i++) o[KEYS[i]] = lerp(a[KEYS[i]], b[KEYS[i]], t);
		return o;
	}
	var phone3d = null;
	var lastRaw = null;
	var lastPose = null;
	function apply(p) {
		var sx = Math.min(1, window.innerWidth / 1440);
		var kv = Math.min(1, window.innerHeight / 980);
		var s = p.s * (1 + (kv - 1) * p.k);
		lastRaw = p;
		lastPose = { x: p.x, y: p.y, ry: p.ry, rx: p.rx, rz: p.rz, s: s };
		if (phone3d) { phone3d.setPose(lastPose, sx, window.innerHeight); return; }
		phone.style.transform = 'translate(' + (p.x * sx) + 'px,' + (p.y * window.innerHeight) + 'px) rotateY(' + p.ry + 'deg) rotateX(' + p.rx + 'deg) rotateZ(' + p.rz + 'deg) scale(' + s + ')';
	}

	function hasWebGL2() {
		try { var c = document.createElement('canvas'); return !!(window.WebGL2RenderingContext && c.getContext('webgl2')); } catch (e) { return false; }
	}
	function boot3d() {
		var host = pinEl.querySelector('.hero__phone-3d');
		if (!host || !video || !hasWebGL2() || !pinEl.dataset.theme) return;
		import(pinEl.dataset.theme + '/assets/js/phone-3d.js?ver=' + (pinEl.dataset.ver || '0')).then(function (mod) {
			return mod.createPhone3D({ container: host, video: video, modelUrl: pinEl.dataset.model || '', phonePx: 608, width: 1200, height: 960 });
		}).then(function (api) {
			phone3d = api;
			pinEl.classList.add('is-3d');
			phone.style.transform = '';
			apply(lastRaw || POSES.hero);
			if (video.readyState >= 2) video.currentTime = Math.max(video.currentTime, 0.001);
		}).catch(function (e) { console.warn('phone-3d non disponibile, resta il phone CSS', e); });
	}
	// Comparsa di titolo e step: dipende da dove sta la sezione nel viewport, non dal trigger del pin.
	// Il trigger A parte quando il telefono della hero e' a meta' strada, e prima di quel momento nessuno
	// scriveva --hiw-text-fade: i testi erano gia' li' mentre salivano. 0 quando la cima della sezione e'
	// ancora in fondo al viewport, 1 poco prima che si incolli.
	function entryFade() {
		var vh = window.innerHeight;
		var d = sectionTop() - window.scrollY; // distanza della cima della sezione dal bordo alto del viewport
		return easeInOut(Math.max(0, Math.min(1, (0.85 * vh - d) / (0.75 * vh))));
	}
	var lastFade = null;
	function setTextFade(v) {
		var f = Math.round(v * 1000) / 1000;
		if (f === lastFade) return;
		lastFade = f;
		section.style.setProperty('--hiw-text-fade', String(f));
	}
	function setStep(q) {
		var active = q < 1 / 3 ? 0 : q < 2 / 3 ? 1 : 2;
		for (var i = 0; i < steps.length; i++) {
			steps[i].classList.toggle('is-active', i === active);
			steps[i].classList.toggle('is-done', i < active);
		}
		section.classList.toggle('is-state-2', active === 1);
		// L'indicatore 1-2-3 e il segnale di scorrimento si accendono da qui, via CSS
		section.setAttribute('data-step', String(active + 1));
	}

	// Un seek alla volta: impostare currentTime mentre un seek e' in corso lo accoda e il video scatta
	var duration = 0;
	var targetTime = null;
	function pump() {
		if (targetTime === null || video.seeking) return;
		var t = targetTime;
		targetTime = null;
		if (Math.abs(video.currentTime - t) > 0.012) video.currentTime = t;
	}
	function seek(q) {
		if (phone3d && phone3d.setProgress) phone3d.setProgress(q);
		if (!duration) return;
		var t = Math.min(q * duration, duration - 0.04);
		targetTime = Math.round(t * 24) / 24;
		pump();
	}

	// Posizione della sezione misurata una volta per refresh/resize, non a ogni frame di scroll
	var secTop = 0;
	function measureSection() { secTop = section.getBoundingClientRect().top + window.scrollY; }
	function sectionTop() { return secTop; }
	// Scroll pinnato dopo p0, in viewport: video + zoom (trigger A); tenuta e rientro stanno nel trigger B
	function pinUnits() { return VIDEO_VH + ZOOM_VH; }
	function easeInOut(v) { return v < 0.5 ? 4 * v * v * v : 1 - Math.pow(-2 * v + 2, 3) / 2; }
	function playSafe(v) { if (!v) return; var p = v.play(); if (p && p.catch) p.catch(function () {}); }
	function screenRect() {
		if (phone3d && phone3d.screenRect) return phone3d.screenRect();
		var el = phone.querySelector('.tp-phone__screen') || phone;
		var r = el.getBoundingClientRect();
		return { left: r.left, top: r.top, width: r.width, height: r.height, radius: parseFloat(getComputedStyle(el).borderRadius) || 0 };
	}
	var zoomOn = false, cardLive = false;
	function showOverlay(on) {
		if (!zoomEl || zoomOn === on) return;
		zoomOn = on;
		zoomEl.classList.toggle('is-on', on);
		if (on) playSafe(zoomVideo); else if (zoomVideo) zoomVideo.pause();
	}
	var lastBox = '';
	function setBox(r, radius, veil, opacity) {
		var key = [r.left, r.top, r.width, r.height, radius, opacity, veil].map(function (n) { return Math.round(n * 100); }).join(',');
		if (key === lastBox) return;
		lastBox = key;
		zoomEl.style.left = r.left + 'px';
		zoomEl.style.top = r.top + 'px';
		zoomEl.style.width = r.width + 'px';
		zoomEl.style.height = r.height + 'px';
		zoomEl.style.borderRadius = radius + 'px';
		zoomEl.style.opacity = opacity.toFixed(3);
		if (zoomVeil) zoomVeil.style.opacity = veil.toFixed(3);
	}
	function viewportRect() { return { left: 0, top: 0, width: window.innerWidth, height: window.innerHeight }; }
	function mixRect(a, b, e) { return { left: lerp(a.left, b.left, e), top: lerp(a.top, b.top, e), width: lerp(a.width, b.width, e), height: lerp(a.height, b.height, e) }; }
	var cardRadius = null; // il raggio della card non cambia durante lo scroll: letto una volta
	function cardRect() {
		var b = cardVisual.getBoundingClientRect();
		if (cardRadius === null) cardRadius = parseFloat(getComputedStyle(cardVisual).borderRadius) || 16;
		return { left: b.left, top: b.top, width: b.width, height: b.height, radius: cardRadius };
	}
	// z in 0..1: 0 = overlay sovrapposto allo schermo (crossfade breve), 1 = tutto il viewport
	function zoom(z) {
		if (!zoomEl) return;
		if (z <= 0) { showOverlay(false); return; }
		showOverlay(true);
		var r = screenRect(), e = easeInOut(z);
		setBox(mixRect(r, viewportRect(), e), lerp(r.radius, 0, e), 0, Math.min(1, z / 0.08));
	}
	// s in 0..1: 0 = tutto il viewport, 1 = rettangolo (vivo, scorre con la pagina) della foto della prima card; velo verde che sfuma
	function shrink(s) {
		if (!zoomEl) return;
		if (!cardVisual) { zoom(1); return; }
		showOverlay(true);
		if (servicesEl) servicesEl.classList.add('is-in'); // il reveal della sezione parte subito: la card deve essere ferma all'atterraggio
		var r = cardRect(), e = easeInOut(s);
		setBox(mixRect(viewportRect(), r, e), lerp(0, r.radius, e), e, 1);
	}
	// Atterraggio: la foto della prima card diventa il video (stesso file, stesso istante); tornando indietro il contrario
	function cardTakeover(on) {
		if (!cardVideo || cardLive === on) return;
		cardLive = on;
		cardVisual.classList.toggle('is-live', on);
		if (on) {
			cardVideo.preload = 'auto'; // stesso file dell'overlay (6 MB): si scarica solo quando serve davvero
			if (zoomVideo && zoomVideo.readyState >= 1) { try { cardVideo.currentTime = zoomVideo.currentTime; } catch (e) {} }
			playSafe(cardVideo);
			showOverlay(false);
		} else {
			shrink(1);
			if (zoomVideo && cardVideo.readyState >= 1) { try { zoomVideo.currentTime = cardVideo.currentTime; } catch (e) {} }
			cardVideo.pause();
		}
	}

	function init() {
		gsap.registerPlugin(ScrollTrigger);

		if (video) {
			video.pause();
			video.addEventListener('loadedmetadata', function () { duration = video.duration; });
			video.addEventListener('seeked', pump);
			video.preload = 'auto';
			video.load();
		}

		boot3d();
		// Il video dello zoom-through (pesante) si scarica solo quando la sezione si avvicina, come il video dello schermo
		if (zoomVideo) { zoomVideo.preload = 'auto'; zoomVideo.load(); }

		measureSection();
		ScrollTrigger.addEventListener('refreshInit', measureSection);

		var p0 = 0;
		ScrollTrigger.create({
			trigger: pinEl,
			start: 'center center',
			end: function () { return sectionTop() + pinUnits() * window.innerHeight; },
			pin: pinEl,
			pinSpacing: false,
			scrub: 0.7,
			invalidateOnRefresh: true,
			onRefresh: function (self) { p0 = Math.max(0, Math.min(1, (sectionTop() - self.start) / (self.end - self.start))); },
			snap: /[?&]nosnap/.test(location.search) ? undefined : {
				snapTo: function (p) {
					if (p < p0) return p;
					var n = pinUnits(), u = (p - p0) / (1 - p0) * n;
					if (u > VIDEO_VH + 0.02) return p; // zoom-through e tenuta: scroll libero
					var k = Math.round(u);
					return p0 + (1 - p0) * k / n;
				},
				duration: { min: 0.1, max: 0.3 }
			},
			onUpdate: function (self) {
				var p = self.progress;
				if (p <= p0) {
					var h = p0 ? p / p0 : 1;
					apply(mix(POSES.hero, POSES.s1, h));
					seek(0);
					setStep(0);
					setTextFade(entryFade());
					return;
				}
				var u = (p - p0) / (1 - p0) * pinUnits();
				var q = Math.max(0, Math.min(1, u / VIDEO_VH));
				var seg = Math.min(2, Math.floor(q * 3));
				apply(mix(SEQ[seg], SEQ[seg + 1], q * 3 - seg));
				seek(q);
				setStep(q);
				// Ultimo 12% del terzo segmento: il phone si raddrizza e sale verso il centro; titolo e step sfumano via
				setTextFade(1 - Math.max(0, Math.min(1, (q - 0.82) / 0.12)));
				// A e' scrubbed (0.7 s di ritardo): dopo un salto oltre la sezione il suo onUpdate arriva quando la card ha
				// gia' preso il video; in quel caso l'overlay non si tocca
				if (!cardLive) zoom(Math.max(0, Math.min(1, (u - VIDEO_VH) / ZOOM_VH)));
			}
		});
		// Oltre il pin (tenuta, rientro, atterraggio) lo stato si ricava dalla posizione di scroll a ogni update:
		// un secondo ScrollTrigger non basta, perche' saltando oltre il suo intervallo (link, scrollIntoView, scroll
		// rapido) non si attiva mai e non chiama ne' onLeave ne' onUpdate, lasciando l'overlay a tutto schermo.
		function syncBeyondPin() {
			var u = (window.scrollY - sectionTop()) / window.innerHeight;
			// Prima che il trigger A si attivi nessuno scriverebbe la dissolvenza d'ingresso: la scrive qui
			if (u < 0) setTextFade(entryFade());
			if (u < pinUnits() && !cardLive) return; // sotto il pin l'overlay lo guida il trigger A (scrubbed)
			var landed = pinUnits() + HOLD_VH + SHRINK_VH;
			if (u >= landed) { cardTakeover(true); return; }
			if (cardLive) cardTakeover(false);
			if (u >= pinUnits() + HOLD_VH) shrink(Math.min(1, (u - pinUnits() - HOLD_VH) / SHRINK_VH));
			else if (u >= pinUnits()) zoom(1);
		}
		var syncQueued = false;
		function onScroll() {
			if (syncQueued) return;
			syncQueued = true;
			requestAnimationFrame(function () { syncQueued = false; syncBeyondPin(); });
		}
		window.addEventListener('scroll', onScroll, { passive: true });
		window.addEventListener('resize', onScroll);
		ScrollTrigger.addEventListener('refresh', syncBeyondPin);
		syncBeyondPin();
		// Prima card: il video gira solo quando la foto e' visibile (dopo l'atterraggio o arrivandoci in altro modo)
		if (cardVideo && 'IntersectionObserver' in window) {
			new IntersectionObserver(function (entries) {
				entries.forEach(function (en) {
					if (en.isIntersecting && !zoomOn) cardTakeover(true);
					else if (!en.isIntersecting && cardLive) cardVideo.pause();
				});
			}, { threshold: 0.05 }).observe(cardVisual);
		}
	}

	function loadScript(src, cb) {
		var s = document.createElement('script');
		s.src = src;
		s.async = true;
		s.onload = cb;
		document.head.appendChild(s);
	}
	function boot() {
		if (window.gsap && window.ScrollTrigger) return init();
		loadScript(GSAP_CDN + 'gsap.min.js', function () {
			loadScript(GSAP_CDN + 'ScrollTrigger.min.js', init);
		});
	}

	if ('IntersectionObserver' in window) {
		var io = new IntersectionObserver(function (entries) {
			if (entries.some(function (e) { return e.isIntersecting; })) { io.disconnect(); boot(); }
		}, { rootMargin: '50% 0px' });
		io.observe(section);
	} else {
		boot();
	}
})();
