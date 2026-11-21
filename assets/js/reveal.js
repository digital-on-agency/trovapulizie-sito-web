/* Reveal generico all'ingresso nel viewport: ogni elemento con [data-reveal] riceve .is-in (una volta).
   Il come (stagger, trasformazioni) lo decide il CSS della sezione. */
(function () {
	'use strict';
	var els = document.querySelectorAll('[data-reveal]');
	if (!els.length) return;
	if (!('IntersectionObserver' in window)) { els.forEach(function (el) { el.classList.add('is-in'); }); return; }
	// Home v2: l'opacita' 0 degli ingressi vale solo sotto questa classe, cosi' senza questo script (o senza
	// IntersectionObserver, ramo sopra) il contenuto resta visibile. Nessun altro CSS del tema la usa.
	document.documentElement.classList.add('tp-reveal');
	var io = new IntersectionObserver(function (entries) {
		entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
	}, { threshold: 0.15 });
	els.forEach(function (el) { io.observe(el); });
})();
