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
	var GSAP_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/';

	// Centro del phone rispetto al centro viewport: x in px (canvas 1440), y in frazione di viewport (canvas 980).
	// Posizioni misurate sui render Figma degli stati (le x/y dei nodi ruotati nei metadata sono pre-rotazione, non il bbox).
	// Fonte: 3743:3095 (hero), 3750:3099 (S1), 3798:1087 (S2), 3828:1157 (S3), 3828:1198 (outro).
	// Rotazioni in prospettiva vera (ry/rx/rz) accordate a occhio sulle silhouette Figma: il Figma e' piatto (rotate+skew), qui il volume e' reale.
	// k = quanto la posa segue il canvas Figma di 980px (le pose pinnate si riducono su viewport piu' bassi, la hero no).
	var POSES = {
		hero: { x: 0, y: 0, ry: 0, rx: 0, rz: 0, s: 1, k: 0 },
		s1: { x: -15, y: 0.054, ry: -24, rx: 6, rz: -12, s: 1, k: 1 },
		s2: { x: -15, y: 0.047, ry: 24, rx: 6, rz: 12, s: 1, k: 1 },
		s3: { x: -62, y: 0.088, ry: -12, rx: 4, rz: -6, s: 1.02, k: 1 },
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

	var duration = 0;
	function seek(q) {
		if (phone3d && phone3d.setProgress) phone3d.setProgress(q);
		if (!duration) return;
		var t = Math.min(q * duration, duration - 0.04);
		if (Math.abs(video.currentTime - t) > 0.02) video.currentTime = t;
	}

	function sectionTop() { return section.getBoundingClientRect().top + window.scrollY; }

	function init() {
		gsap.registerPlugin(ScrollTrigger);

		if (video) {
			video.pause();
			video.addEventListener('loadedmetadata', function () { duration = video.duration; });
			video.preload = 'auto';
			video.load();
		}

		boot3d();

		var p0 = 0;
		ScrollTrigger.create({
			trigger: pinEl,
			start: 'center center',
			end: function () { return sectionTop() + section.offsetHeight - window.innerHeight; },
			pin: pinEl,
			pinSpacing: false,
			scrub: 0.5,
			invalidateOnRefresh: true,
			onRefresh: function (self) { p0 = Math.max(0, Math.min(1, (sectionTop() - self.start) / (self.end - self.start))); },
			snap: /[?&]nosnap/.test(location.search) ? undefined : {
				snapTo: function (p) {
					if (p < p0) return p;
					var k = Math.round((p - p0) / (1 - p0) * 3);
					return p0 + (1 - p0) * k / 3;
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
				var q = (p - p0) / (1 - p0);
				var seg = Math.min(2, Math.floor(q * 3));
				apply(mix(SEQ[seg], SEQ[seg + 1], q * 3 - seg));
				seek(q);
				setStep(q);
			}
		});
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
