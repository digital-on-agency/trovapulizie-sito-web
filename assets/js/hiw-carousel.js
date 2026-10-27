/* Come funziona sotto --tp-bp-desktop: navigazione del carosello (decisione Guido 27/10, sostituisce la sequenza
   guidata dallo scroll di ADR-002 rev. 5). La fila dei passi scorre gia' da sola col dito (scroll-snap nel CSS):
   qui si aggiungono puntini, frecce e "1 di 3", che restano nascosti finche' lo script non mette .hiw-car-ok.
   Il passo corrente e' quello col centro piu' vicino al centro della fila. Su desktop non fa nulla: li' il telefono
   e' quello pinnato della hero (home-motion.js). */
(function () {
	'use strict';

	var root = document.documentElement;
	var bp = getComputedStyle(root).getPropertyValue('--tp-bp-desktop').trim() || '1024px';
	if (window.matchMedia('(min-width: ' + bp + ')').matches) return;

	var section = document.getElementById('come-funziona');
	var track = section && section.querySelector('.how-it-works__steps');
	var cards = track ? track.querySelectorAll('.how-it-works__step') : [];
	var nav = section && section.querySelector('.hiw-nav');
	if (!track || !nav || cards.length < 2) return;

	var dots = nav.querySelectorAll('[data-hiw-dot]');
	var count = nav.querySelector('[data-hiw-count]');
	var prev = nav.querySelector('[data-hiw-prev]');
	var next = nav.querySelector('[data-hiw-next]');
	var smooth = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
	var current = -1;

	function nearest() {
		var mid = track.scrollLeft + track.clientWidth / 2;
		var best = 0, dist = Infinity;
		cards.forEach(function (c, i) {
			var d = Math.abs(c.offsetLeft + c.offsetWidth / 2 - mid);
			if (d < dist) { dist = d; best = i; }
		});
		return best;
	}

	function render(i) {
		if (i === current) return;
		current = i;
		dots.forEach(function (d, k) {
			d.classList.toggle('is-active', k === i);
			if (k === i) d.setAttribute('aria-current', 'step'); else d.removeAttribute('aria-current');
		});
		cards.forEach(function (c, k) { c.classList.toggle('is-active', k === i); });
		if (count) count.textContent = (i + 1) + ' di ' + cards.length;
		if (prev) prev.disabled = i === 0;
		if (next) next.disabled = i === cards.length - 1;
	}

	function go(i) {
		var c = cards[Math.max(0, Math.min(cards.length - 1, i))];
		track.scrollTo({ left: c.offsetLeft - (track.clientWidth - c.offsetWidth) / 2, behavior: smooth });
	}

	var raf = 0;
	track.addEventListener('scroll', function () {
		if (raf) return;
		raf = requestAnimationFrame(function () { raf = 0; render(nearest()); });
	}, { passive: true });
	dots.forEach(function (d) { d.addEventListener('click', function () { go(+d.getAttribute('data-hiw-dot')); }); });
	if (prev) prev.addEventListener('click', function () { go(current - 1); });
	if (next) next.addEventListener('click', function () { go(current + 1); });

	root.classList.add('hiw-car-ok');
	render(nearest());
})();
