/* Come funziona sotto --tp-bp-desktop: tre blocchi impilati, ognuno col proprio clip (ADR-002 rev. 4).
   Il video di ogni passo esce dal PHP con data-src e senza sorgente: qui viene assegnata la sorgente e
   avviato il clip la prima volta che il blocco entra nel viewport, poi il blocco non viene piu' osservato.
   Cosi' i clip dei passi non ancora visti non vengono richiesti affatto.
   Con "riduci movimento", senza IntersectionObserver o se l'autoplay viene negato resta il poster, che e'
   uno stato corretto e non un degrado: la sezione deve leggersi comunque come il Figma mobile 4020:965.
   Su desktop non fa nulla: il telefono e' quello pinnato della hero (home-motion.js). */
(function () {
	'use strict';

	var bp = getComputedStyle(document.documentElement).getPropertyValue('--tp-bp-desktop').trim() || '1024px';
	if (window.matchMedia('(min-width: ' + bp + ')').matches) return;
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
	if (!('IntersectionObserver' in window)) return;

	var videos = document.querySelectorAll('.how-it-works__step .tp-phone__video[data-src]');
	if (!videos.length) return;

	function start(video) {
		if (!video.src) {
			video.src = video.getAttribute('data-src');
			video.removeAttribute('data-src');
		}
		var p = video.play();
		// Low Power Mode e Data Saver rifiutano l'autoplay: il poster resta, nessun errore in console.
		if (p && typeof p.catch === 'function') p.catch(function () {});
	}

	var io = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (!entry.isIntersecting) return;
			io.unobserve(entry.target);
			start(entry.target);
		});
	}, { threshold: 0.35 });

	Array.prototype.forEach.call(videos, function (v) { io.observe(v); });
})();
