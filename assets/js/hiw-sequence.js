/* Come funziona sotto --tp-bp-desktop: sequenza di tre schermate guidata dallo scroll (ADR-002 rev. 5).
   Il contenuto della sezione resta sticky per circa due viewport; dentro un unico telefono le tre
   schermate si alternano in crossfade e i tre paragrafi compaiono e scompaiono in sincrono.
   Niente video: solo opacita', quindi funziona anche in Low Power Mode e su Safari iOS, dove autoplay e
   seek non sono affidabili. Senza JavaScript o con "riduci movimento" la classe hiw-seq-ok non viene
   messa e restano i tre blocchi impilati del Figma 4020:965, che sono il fallback corretto.
   Su desktop non fa nulla: li' il telefono e' quello pinnato della hero (home-motion.js). */
(function () {
	'use strict';

	var root = document.documentElement;
	var bp = getComputedStyle(root).getPropertyValue('--tp-bp-desktop').trim() || '1024px';
	if (window.matchMedia('(min-width: ' + bp + ')').matches) return;
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

	var section = document.getElementById('come-funziona');
	var sticky = section && section.querySelector('.how-it-works__sticky');
	var screens = section ? section.querySelectorAll('.hiw-seq__screen') : [];
	var steps = section ? section.querySelectorAll('.how-it-works__step') : [];
	if (!section || !sticky || screens.length !== 3 || steps.length !== 3) return;

	root.classList.add('hiw-seq-ok');

	// Ogni passaggio fra due schermate dura FADE della distanza fra l'una e l'altra; il resto e' tenuta,
	// cosi' ogni schermata resta ferma e leggibile invece di stare sempre a meta' dissolvenza.
	var FADE = 0.3;

	function weights(u) {
		// u in 0..1 sull'intera sequenza -> peso di ciascuna delle tre schermate
		var pos = u * 2; // 0 = prima schermata, 1 = seconda, 2 = terza
		var i = Math.min(1, Math.floor(pos)); // coppia corrente: i e i+1
		var frac = pos - i;
		// la dissolvenza sta al centro dell'intervallo: tenuta, crossfade, tenuta
		var t = Math.max(0, Math.min(1, (frac - (0.5 - FADE / 2)) / FADE));
		var w = [0, 0, 0];
		w[i] = 1 - t;
		w[i + 1] = t;
		return w;
	}

	var ticking = false;

	function update() {
		ticking = false;
		var rect = section.getBoundingClientRect();
		var span = section.offsetHeight - sticky.offsetHeight;
		if (span <= 0) return;
		var u = Math.max(0, Math.min(1, -rect.top / span));
		var w = weights(u);
		// Il passo corrente e' quello con il peso maggiore: sotto 1024px i tre passi restano tutti
		// visibili come stepper (fatto / in corso / da fare, come su desktop) e cambia solo lo stato,
		// quindi qui non si tocca piu' l'opacita' dei testi. Le schermate invece si incrociano.
		var cur = w[0] >= w[1] && w[0] >= w[2] ? 0 : (w[1] >= w[2] ? 1 : 2);
		for (var i = 0; i < 3; i++) {
			screens[i].style.opacity = w[i];
			steps[i].classList.toggle('is-active', i === cur);
			steps[i].classList.toggle('is-done', i < cur);
		}
	}

	function onScroll() {
		if (ticking) return;
		ticking = true;
		window.requestAnimationFrame(update);
	}

	window.addEventListener('scroll', onScroll, { passive: true });
	window.addEventListener('resize', onScroll);
	update();
})();
