/* Pila delle schede di Come funziona, solo da 1024 px (SPEC-homepage-v2, TICKET-122; scheda di animazione 7882:4118:
   "niente impilamento delle schede" sotto 1024 px). Le schede sono gia' impilate dal CSS (position: sticky con `top`
   crescente, .hv2-steps__card): qui si aggiunge soltanto l'effetto "la precedente scala a 0,96" mentre la successiva le
   sale sopra. Solo transform; nessuna dipendenza (niente GSAP).
   Selettori: .hv2-steps__carousel .tp-carousel__slide (gli elementi sticky, in ordine) e il .hv2-steps__card dentro ciascuno (quello che
   scala: la trasformazione sull'elemento sticky no, per non toccarne il posizionamento). Per ogni scheda tranne l'ultima, il progresso e'
   la strada fatta dalla scheda successiva fra "ancora in fondo" (a distanza altezza + spazio dalla sua posizione di arresto) e
   "arrivata nella sua posizione di arresto": scala = 1 - 0,04 * progresso, con origine in alto al centro.
   Senza questo script le schede sono impilate lo stesso, senza il rimpicciolimento. Con "riduci movimento" non scala nulla.
   Telefono fermo (24/11): mette .hv2-steps--pin sulla sezione e regola l'opacita' delle schermate di .hv2-steps__pin
   (con "riduci movimento" cambio secco a meta' strada invece della dissolvenza).
   Sotto 1024 px (carosello) non fa nulla e toglie la trasformazione. Un solo gestore di scroll con requestAnimationFrame. */
(function () {
	'use strict';
	var root = document.querySelector('.hv2-steps__carousel');
	if (!root) return;
	var slides = Array.prototype.slice.call(root.querySelectorAll('.tp-carousel__slide'));
	var cards = slides.map(function (s) { return s.querySelector('.hv2-steps__card'); });
	if (cards.length < 2 || cards.some(function (c) { return !c; })) return;
	var bp = getComputedStyle(document.documentElement).getPropertyValue('--tp-bp-desktop').trim() || '1024px';
	var wide = window.matchMedia('(min-width: ' + bp + ')');
	var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
	var MIN = 0.96;
	var raf = 0;
	// Telefono fermo (24/11): un solo telefono sopra la pila; la schermata del passo i+1 compare mentre la scheda i+1
	// sale, con la stessa misura di avanzamento che rimpicciolisce la scheda i.
	var section = root.closest('.hv2-steps');
	var shots = section ? Array.prototype.slice.call(section.querySelectorAll('.hv2-steps__pinshot')) : [];
	var pin = shots.length === cards.length;

	function reset() {
		cards.forEach(function (c) { c.style.transform = ''; });
		if (section) section.classList.remove('hv2-steps--pin');
	}

	function update() {
		raf = 0;
		if (!wide.matches) { reset(); return; }
		if (pin) section.classList.add('hv2-steps--pin');
		for (var i = 0; i < cards.length - 1; i++) {
			var next = slides[i + 1];
			var stick = parseFloat(getComputedStyle(next).top) || 0;
			var travel = next.offsetHeight + 16;
			var p = 1 - (next.getBoundingClientRect().top - stick) / travel;
			p = Math.max(0, Math.min(1, p));
			if (pin) shots[i + 1].style.opacity = (reduce.matches ? (p > 0.5 ? 1 : 0) : p).toFixed(3);
			if (reduce.matches) { cards[i].style.transform = ''; continue; }
			cards[i].style.transform = p > 0 ? 'scale(' + (1 - (1 - MIN) * p).toFixed(4) + ')' : '';
		}
	}

	function schedule() { if (!raf) raf = requestAnimationFrame(update); }

	window.addEventListener('scroll', schedule, { passive: true });
	window.addEventListener('resize', schedule, { passive: true });
	if (wide.addEventListener) { wide.addEventListener('change', schedule); reduce.addEventListener('change', schedule); }
	update();
})();
