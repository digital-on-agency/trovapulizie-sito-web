/* Come funziona: phone unico pinnato dalla hero, pose Figma, video scrubbed dallo scroll (ADR-002). */
(function () {
	'use strict';

	var mq = window.matchMedia('(min-width: 768px) and (prefers-reduced-motion: no-preference)');
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
		// 15/10: +0.035 di y sulle pose pinnate: il testo scende di 72px sotto la navbar (CSS --hiw-offset) e il
		// telefono lo segue in parte, cosi' lo spazio sopra il titolo e sotto il telefono si equivalgono
		// 15/10 sera: telefono al 95% nelle pose pinnate e +0.02 di y, per piu' aria fra sottotitolo e telefono
		s1: { x: -15, y: 0.109, ry: -24, rx: 6, rz: -12, s: 0.95, k: 1 },
		s2: { x: -15, y: 0.102, ry: 24, rx: 6, rz: 12, s: 0.95, k: 1 },
		s3: { x: -62, y: 0.143, ry: -12, rx: 4, rz: -6, s: 0.97, k: 1 },
		out: { x: 0, y: 0, ry: 0, rx: 0, rz: 0, s: 1, k: 1 }
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
			return mod.createPhone3D({ container: host, video: video, modelUrl: pinEl.dataset.model || '', phonePx: 608, width: 720, height: 960 });
		}).then(function (api) {
			phone3d = api;
			pinEl.classList.add('is-3d');
			phone.style.transform = '';
			apply(lastRaw || POSES.hero);
			if (video.readyState >= 2) video.currentTime = Math.max(video.currentTime, 0.001);
		}).catch(function (e) { console.warn('phone-3d non disponibile, resta il phone CSS', e); });
	}
	function setStep(q) {
		var active = q < 1 / 3 ? 0 : q < 2 / 3 ? 1 : 2;
		for (var i = 0; i < steps.length; i++) steps[i].classList.toggle('is-active', i === active);
		section.classList.toggle('is-state-2', active === 1);
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

	function sectionTop() { return section.getBoundingClientRect().top + window.scrollY; }
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
	function setBox(r, radius, veil, opacity) {
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
	function cardRect() {
		var b = cardVisual.getBoundingClientRect();
		return { left: b.left, top: b.top, width: b.width, height: b.height, radius: parseFloat(getComputedStyle(cardVisual).borderRadius) || 16 };
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
					apply(mix(POSES.hero, POSES.s1, p0 ? p / p0 : 1));
					seek(0);
					setStep(0);
					return;
				}
				var u = (p - p0) / (1 - p0) * pinUnits();
				var q = Math.max(0, Math.min(1, u / VIDEO_VH));
				var seg = Math.min(2, Math.floor(q * 3));
				apply(mix(SEQ[seg], SEQ[seg + 1], q * 3 - seg));
				seek(q);
				setStep(q);
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
			var landed = pinUnits() + HOLD_VH + SHRINK_VH;
			if (u >= landed) { cardTakeover(true); return; }
			if (cardLive) cardTakeover(false);
			if (u >= pinUnits() + HOLD_VH) shrink(Math.min(1, (u - pinUnits() - HOLD_VH) / SHRINK_VH));
			else if (u >= pinUnits()) zoom(1);
			// sotto il pin l'overlay lo guida il trigger A (scrubbed)
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
			cardVideo.preload = 'auto';
			new IntersectionObserver(function (entries) {
				entries.forEach(function (en) {
					if (en.isIntersecting && !zoomOn) { cardVisual.classList.add('is-live'); cardLive = true; playSafe(cardVideo); }
					else if (!en.isIntersecting && cardLive) { cardVideo.pause(); }
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
