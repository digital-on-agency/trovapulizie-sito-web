/* Carosello a scorrimento (SPEC-homepage-v2, TICKET-122). Componente generico: template-parts/tp-carousel.php.
   Selettori: [data-tp-carousel] (contenitore), .tp-carousel__track (la lista che scorre), [data-tp-carousel-nav] (indicatore,
   nascosto nel markup con `hidden` finche' questo script non lo toglie), [data-tp-carousel-dot], [data-tp-carousel-prev],
   [data-tp-carousel-next], [data-tp-carousel-count] (testo "N di M" dentro aria-live="polite").
   Evento: `tp:carousel-change` sul contenitore (bubbles), detail { index, total }, a ogni cambio di scheda corrente.
   Scheda corrente = quella il cui centro e' piu' vicino al centro della pista. Tastiera: sulla pista (tabindex 0) frecce
   sinistra e destra, Home e Fine; i puntini e le frecce sono button (Invio e Spazio). Senza questo script resta lo
   scroll-snap CSS e l'indicatore non compare.
   Quando la pista non scorre (il CSS del chiamante la mette in colonna o in griglia, es. Come funziona da 1024 px) lo
   script non cambia nulla: la scheda corrente non si aggiorna e l'indicatore e' nascosto dal CSS; l'attributo
   aria-roledescription="carosello" viene tolto finche' la pista non scorre in orizzontale e rimesso quando scorre.
   "Riduci movimento": gli spostamenti avvengono senza animazione (behavior auto).
   Senza IntersectionObserver non cambia nulla: lo script non lo usa (ascolta solo scroll, resize e click). */
(function () {
	'use strict';

	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');

	function init(root) {
		var track = root.querySelector('.tp-carousel__track');
		var slides = track ? track.children : [];
		if (!track || slides.length < 1) return;
		var nav = root.querySelector('[data-tp-carousel-nav]');
		var dots = root.querySelectorAll('[data-tp-carousel-dot]');
		var count = root.querySelector('[data-tp-carousel-count]');
		var prev = root.querySelector('[data-tp-carousel-prev]');
		var next = root.querySelector('[data-tp-carousel-next]');
		var fmt = count ? (count.getAttribute('data-format') || '%1$d di %2$d') : '';
		var current = -1;
		var raf = 0;

		function scrolls() { return track.scrollWidth > track.clientWidth + 1; }

		function nearest() {
			var mid = track.scrollLeft + track.clientWidth / 2;
			var best = 0, dist = Infinity;
			for (var i = 0; i < slides.length; i++) {
				var d = Math.abs(slides[i].offsetLeft + slides[i].offsetWidth / 2 - mid);
				if (d < dist) { dist = d; best = i; }
			}
			return best;
		}

		function render(i, force) {
			if (i === current && !force) return;
			var changed = i !== current;
			current = i;
			for (var k = 0; k < dots.length; k++) {
				dots[k].classList.toggle('is-active', k === i);
				if (k === i) dots[k].setAttribute('aria-current', 'true'); else dots[k].removeAttribute('aria-current');
			}
			for (var s = 0; s < slides.length; s++) slides[s].classList.toggle('is-active', s === i);
			if (count) count.textContent = fmt.replace('%1$d', i + 1).replace('%2$d', slides.length);
			if (prev) prev.disabled = i === 0;
			if (next) next.disabled = i === slides.length - 1;
			if (changed) root.dispatchEvent(new CustomEvent('tp:carousel-change', { bubbles: true, detail: { index: i, total: slides.length } }));
		}

		function go(i) {
			i = Math.max(0, Math.min(slides.length - 1, i));
			var c = slides[i];
			track.scrollTo({ left: c.offsetLeft - (track.clientWidth - c.offsetWidth) / 2, behavior: reduce && reduce.matches ? 'auto' : 'smooth' });
		}

		function sync() {
			var on = scrolls();
			if (on) root.setAttribute('aria-roledescription', 'carosello'); else root.removeAttribute('aria-roledescription');
			if (on) render(nearest(), true);
		}

		track.addEventListener('scroll', function () {
			if (raf || !scrolls()) return;
			raf = requestAnimationFrame(function () { raf = 0; render(nearest()); });
		}, { passive: true });
		track.addEventListener('keydown', function (e) {
			if (!scrolls()) return;
			var k = e.key, to = -1;
			if (k === 'ArrowRight') to = current + 1;
			else if (k === 'ArrowLeft') to = current - 1;
			else if (k === 'Home') to = 0;
			else if (k === 'End') to = slides.length - 1;
			if (to < 0) return;
			e.preventDefault();
			go(to);
		});
		for (var d = 0; d < dots.length; d++) {
			dots[d].addEventListener('click', (function (btn) { return function () { go(+btn.getAttribute('data-tp-carousel-dot')); }; })(dots[d]));
		}
		if (prev) prev.addEventListener('click', function () { go(current - 1); });
		if (next) next.addEventListener('click', function () { go(current + 1); });
		window.addEventListener('resize', function () { sync(); }, { passive: true });

		if (nav) nav.hidden = false;
		sync();
		if (current < 0) render(0, true);
	}

	var roots = document.querySelectorAll('[data-tp-carousel]');
	for (var r = 0; r < roots.length; r++) init(roots[r]);
})();
