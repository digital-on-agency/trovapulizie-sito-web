/* Mette in pausa i loop CSS fuori dal viewport (Home v2, TICKET-121). Un solo IntersectionObserver condiviso per tutti
   gli elementi con [data-tp-loop] (oggi la fascia frasi .tp-marquee): quando un elemento esce dallo schermo riceve la
   classe .is-paused, che il CSS del componente traduce in animation-play-state: paused; quando rientra la perde.
   Nessuna dipendenza. Senza IntersectionObserver o senza JavaScript i loop scorrono sempre (nessuna classe messa).
   Con "riduci movimento" non fa nulla: il CSS ferma gia' i loop. Caricato solo sul template page-home-v2.php
   (inc/home-v2.php). */
(function () {
	'use strict';
	var els = document.querySelectorAll('[data-tp-loop]');
	if (!els.length || !('IntersectionObserver' in window)) return;
	if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
	var io = new IntersectionObserver(function (entries) {
		entries.forEach(function (en) { en.target.classList.toggle('is-paused', !en.isIntersecting); });
	}, { threshold: 0 });
	els.forEach(function (el) { io.observe(el); });
})();
