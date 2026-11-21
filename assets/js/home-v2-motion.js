/* Movimento della Home v2 (note Figma 7882:4118, TICKET-120 e TICKET-129). Gli ingressi sono in CSS (style.css: "Movimento
   della hero" e blocco TICKET-129) e li fa scattare reveal.js; qui stanno le parti che richiedono uno script.

   Tabella sezione -> animazione -> durata -> curva (token --tp-dur-* e --tp-ease-* di style.css):
   - Hero: ingresso a cascata (CSS, 450/800 ms, ease-in/stage); testo circolare che ruota piano (60 s, lineare); parallasse
     da 1024 px (GSAP, scrub legato allo scroll, y -90 palco / -36 testo, lineare perche' segue il dito).
   - Manifesto: timbro che compare con rimbalzo e mezzo giro (800 ms, bounce; data-pop in reveal.js) e testo del timbro che
     ruota piano (90 s, lineare); pillole delle prove che entrano 200 ms dopo la loro promessa (450 ms, bounce).
   - Servizi: icone che spuntano con rimbalzo a stagger 40 ms (450 ms, bounce, via --k).
   - Recensioni: foglietti che scendono con rimbalzo (800 ms), stagger 80 ms.
   - Zone: raggio e impulsi del radar (CSS), in pausa fuori schermo (tp-loops.js).
   - Aziende: ken burns della foto da 1024 px (GSAP, scala 1 -> 1,1 legata allo scroll, lineare).
   - FAQ: apertura morbida (altezza 450 ms ease-stage, la risposta scende di 8 px). E' l'unica proprieta' di layout, voluta.
   - Chiusura: telefono che sale (800 ms), avviso "Aggiunta alla schermata Home" che scende con rimbalzo dopo 560 ms e
     battito dell'icona (una volta), tentacoli che entrano da destra (800 ms, solo desktop).
   - Footer nuovo: scritta grande che sale con lo scroll da 1024 px (GSAP, yPercent 55 -> 0, lineare).
   Mobile (sotto 1024 px): durate x0,8 (--hv2-mm), nessun GSAP. Riduci movimento: solo dissolvenze da 200 ms, nessun loop,
   nessun GSAP (blocco unico in style.css).

   Cosa fa questo file:
   1. Testo circolare della hero: IntersectionObserver aggiunge .is-paused a .hv2-hero__orbit fuori schermo.
   2. Da 1024 px con movimento consentito, GSAP + ScrollTrigger da cdnjs (una sola volta, al load o al primo scroll) per tre
      effetti, tutti con solo transform: parallasse della hero, ken burns della foto aziende, scritta del footer.
      Sotto 1024 px o con "riduci movimento" GSAP non viene richiesto.
   Dove viene caricato: sul template page-home-v2.php e, per la scritta del footer, ovunque esca il footer nuovo
   (tp_footer_v2_on(), inc/home-v2.php). Fuori dall'anteprima, con l'interruttore tp_site_v2 acceso, lo script trova solo il
   footer e muove la scritta; con l'interruttore spento non viene caricato.
   Senza JavaScript la pagina resta ferma e completa: nessun contenuto dipende da questo file. */
(function () {
	'use strict';

	var hero = document.querySelector('.hv2-hero');
	var photo = document.querySelector('.hv2-b2b__photo img');
	var footer = document.querySelector('.site-footer-v2');
	var word = footer && footer.querySelector('.site-footer-v2__word-text');
	if (!hero && !photo && !word) return;

	var orbit = hero && hero.querySelector('.hv2-hero__orbit');
	if (orbit && 'IntersectionObserver' in window) {
		new IntersectionObserver(function (entries) {
			orbit.classList.toggle('is-paused', !entries[0].isIntersecting);
		}).observe(orbit);
	}

	var GSAP_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/';
	var QUERY = '(min-width: 1024px) and (prefers-reduced-motion: no-preference)';
	var desktop = window.matchMedia(QUERY);
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
		mm.add(QUERY, function () {
			if (hero) {
				var scroll = { trigger: hero, start: 'top top', end: 'bottom top', scrub: true };
				gsap.to(hero.querySelector('.hv2-hero__stage'), { y: -90, ease: 'none', scrollTrigger: scroll });
				gsap.to(hero.querySelector('.hv2-hero__text'), { y: -36, ease: 'none', scrollTrigger: scroll });
			}
			if (photo) {
				// Ken burns: la foto si avvicina mentre il riquadro attraversa lo schermo. Il riquadro ritaglia (overflow hidden).
				gsap.fromTo(photo, { scale: 1 }, {
					scale: 1.1,
					ease: 'none',
					scrollTrigger: { trigger: photo.closest('.hv2-b2b__box') || photo, start: 'top bottom', end: 'bottom top', scrub: true }
				});
			}
			if (word) {
				// Scritta del footer: sale da sotto il ritaglio mentre il footer entra, a posto quando si arriva in fondo.
				gsap.fromTo(word, { yPercent: 55 }, {
					yPercent: 0,
					ease: 'none',
					scrollTrigger: { trigger: footer, start: 'top bottom', end: 'bottom bottom', scrub: true }
				});
			}
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
