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
	var POSES = {
		hero: { x: 0, y: 0, rot: 0, skew: 0, sy: 1 },
		s1: { x: -15, y: 0.054, rot: -14.75, skew: 10.04, sy: 0.98 },
		s2: { x: -15, y: 0.047, rot: 14.75, skew: -10.04, sy: 0.98 },
		s3: { x: -62, y: 0.088, rot: -8.14, skew: 6.61, sy: 0.99 },
		out: { x: 0, y: 0, rot: 0, skew: 0, sy: 1 }
	};
	var SEQ = [POSES.s1, POSES.s2, POSES.s3, POSES.out];

	function lerp(a, b, t) { return a + (b - a) * t; }
	function mix(a, b, t) {
		return { x: lerp(a.x, b.x, t), y: lerp(a.y, b.y, t), rot: lerp(a.rot, b.rot, t), skew: lerp(a.skew, b.skew, t), sy: lerp(a.sy, b.sy, t) };
	}
	function apply(p) {
		var sx = Math.min(1, window.innerWidth / 1440);
		phone.style.transform = 'translate(' + (p.x * sx) + 'px,' + (p.y * window.innerHeight) + 'px) rotate(' + p.rot + 'deg) skewX(' + p.skew + 'deg) scaleY(' + p.sy + ')';
	}
	function setStep(q) {
		var active = q < 1 / 3 ? 0 : q < 2 / 3 ? 1 : 2;
		for (var i = 0; i < steps.length; i++) steps[i].classList.toggle('is-active', i === active);
		section.classList.toggle('is-state-2', active === 1);
	}

	var duration = 0;
	function seek(q) {
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
			snap: {
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
