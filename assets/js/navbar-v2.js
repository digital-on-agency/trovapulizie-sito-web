/* Barra chiara della Home v2 (TICKET-120): in cima alla pagina e' trasparente, cosi' la sfumatura della hero continua
   dietro di lei (Figma 7903:5261); appena si scorre torna velata, perche' i contenuti non si leggano attraverso.
   Selettore: header.navbar--v2. Aggiunge/toglie la classe .is-top (soglia 8 px, un solo listener passivo, throttle con
   requestAnimationFrame). Senza JavaScript la barra resta velata (CSS di base): nessuna funzione dipende da questo
   file. Con prefers-reduced-motion nessuna transizione (il CSS le azzera). */
(function () {
	'use strict';

	var bar = document.querySelector('header.navbar--v2');
	if (!bar) return;
	var ticking = false;

	function update() {
		ticking = false;
		bar.classList.toggle('is-top', (window.pageYOffset || document.documentElement.scrollTop) < 8);
	}

	window.addEventListener('scroll', function () {
		if (!ticking) {
			ticking = true;
			window.requestAnimationFrame(update);
		}
	}, { passive: true });
	update();

	/* Menu mobile aperto: la pagina sotto non scorre (revisione giro 1, voce 21). nav-mobile.js e' condiviso con l'header
	   vecchio e non si tocca: qui si osserva aria-expanded dell'hamburger e si mette una classe su <html>, che solo il
	   CSS della v2 (html.tp-nav-lock) usa. */
	var burger = document.getElementById('nav-burger');
	if (burger && window.MutationObserver) {
		var root = document.documentElement;
		var sync = function () {
			root.classList.toggle('tp-nav-lock', burger.getAttribute('aria-expanded') === 'true');
		};
		new MutationObserver(sync).observe(burger, { attributes: true, attributeFilter: ['aria-expanded'] });
		sync();
	}
})();
