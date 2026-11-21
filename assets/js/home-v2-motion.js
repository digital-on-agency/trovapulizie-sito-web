/* Movimento della Home v2 (note Figma 7882:4118). Gli ingressi della hero sono in CSS (style.css, "Movimento della
   hero"); qui stanno solo le parti che richiedono uno script:
   1. Loop del testo circolare fermo fuori schermo: IntersectionObserver aggiunge .is-paused a .hv2-hero__orbit.
   2. Parallasse della hero da desktop (>= 1024 px, movimento consentito): GSAP + ScrollTrigger da cdnjs, caricati al
      load della pagina o al primo scroll. Sposta solo transform di .hv2-hero__stage e .hv2-hero__text, scrub legato
      allo scroll della hero. Sotto 1024 px o con "riduci movimento" GSAP non viene richiesto.
   Senza JavaScript la hero resta ferma e completa: nessun contenuto dipende da questo file. */
(function () {
	'use strict';

	var hero = document.querySelector('.hv2-hero');
	if (!hero) return;

	var orbit = hero.querySelector('.hv2-hero__orbit');
	if (orbit && 'IntersectionObserver' in window) {
		new IntersectionObserver(function (entries) {
			orbit.classList.toggle('is-paused', !entries[0].isIntersecting);
		}).observe(orbit);
	}

	var GSAP_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/';
	var desktop = window.matchMedia('(min-width: 1024px) and (prefers-reduced-motion: no-preference)');
	if (!desktop.matches) return;

	function loadScript(src, done) {
		var s = document.createElement('script');
		s.src = src;
		s.async = true;
		s.onload = done;
		document.head.appendChild(s);
	}

	function init() {
		var gsap = window.gsap;
		gsap.registerPlugin(window.ScrollTrigger);
		var mm = gsap.matchMedia();
		mm.add('(min-width: 1024px) and (prefers-reduced-motion: no-preference)', function () {
			var scroll = { trigger: hero, start: 'top top', end: 'bottom top', scrub: true };
			gsap.to(hero.querySelector('.hv2-hero__stage'), { y: -90, ease: 'none', scrollTrigger: scroll });
			gsap.to(hero.querySelector('.hv2-hero__text'), { y: -36, ease: 'none', scrollTrigger: scroll });
		});
	}

	var started = false;
	function start() {
		if (started) return;
		started = true;
		window.removeEventListener('scroll', start);
		if (window.gsap && window.ScrollTrigger) return init();
		loadScript(GSAP_CDN + 'gsap.min.js', function () {
			loadScript(GSAP_CDN + 'ScrollTrigger.min.js', init);
		});
	}

	window.addEventListener('scroll', start, { passive: true });
	if (document.readyState === 'complete') start();
	else window.addEventListener('load', start);
})();
