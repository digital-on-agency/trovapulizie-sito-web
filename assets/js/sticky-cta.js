/**
 * Barra CTA fissa su mobile (TICKET-31).
 * Compare quando la hero esce dallo schermo, sparisce quando rientra. Sopra --tp-bp-desktop
 * non si attiva affatto: la barra non esiste su desktop.
 *
 * La soglia si legge dal token, non si riscrive qui: CSS e JS non devono poter divergere.
 */
(function () {
	'use strict';

	var barra = document.querySelector('[data-sticky-cta]');
	// L'elemento osservato lo dice il markup (data-watch): la hero dell'hub o il form della landing.
	var hero = document.querySelector(barra ? (barra.getAttribute('data-watch') || '.hub-hero') : '.hub-hero');
	if (!barra || !hero || !('IntersectionObserver' in window)) return;

	var soglia = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--tp-bp-desktop'), 10) || 1024;
	var mq = window.matchMedia('(max-width: ' + (soglia - 1) + 'px)');

	var osservatore = new IntersectionObserver(function (voci) {
		var heroVisibile = voci.some(function (v) { return v.isIntersecting; });
		barra.hidden = heroVisibile || !mq.matches;
	}, { threshold: 0 });

	function applica() {
		if (mq.matches) {
			osservatore.observe(hero);
		} else {
			osservatore.unobserve(hero);
			barra.hidden = true;
		}
	}
	applica();
	if (mq.addEventListener) mq.addEventListener('change', applica);
	else if (mq.addListener) mq.addListener(applica);
})();
