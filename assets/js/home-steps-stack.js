/* Pila delle schede di Come funziona, solo da 1024 px (SPEC-homepage-v2, TICKET-122; scheda di animazione 7882:4118:
   "niente impilamento delle schede" sotto 1024 px). Le schede sono gia' impilate dal CSS (position: sticky con `top`
   crescente, .hv2-steps__card): qui si aggiunge soltanto l'effetto "la precedente scala a 0,96" mentre la successiva le
   sale sopra. Solo transform; nessuna dipendenza (niente GSAP).
   Selettori: .hv2-steps__carousel .tp-carousel__slide (gli elementi sticky, in ordine) e il .hv2-steps__card dentro ciascuno (quello che
   scala: la trasformazione sull'elemento sticky no, per non toccarne il posizionamento). Per ogni scheda tranne l'ultima, il progresso e'
   la strada fatta dalla scheda successiva fra "ancora in fondo" (a distanza altezza + spazio dalla sua posizione di arresto) e
   "arrivata nella sua posizione di arresto": scala = 1 - 0,04 * progresso, con origine in alto al centro.
   Senza questo script le schede sono impilate lo stesso, senza il rimpicciolimento. Con "riduci movimento" non scala nulla.
   Telefono fermo (24/11): mette .hv2-steps--pin sulla sezione e scopre la schermata del passo successivo a tendina, seguendo
   il bordo superiore della scheda che sale sul telefono (niente dissolvenza: due schermate trasparenti si sovrappongono);
   con "riduci movimento" cambio secco a meta' schermo.
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
	// Telefono fermo (24/11): un solo telefono sopra la pila; la schermata del passo i+1 si scopre dall'alto verso il basso
	// in pari col bordo della scheda i+1 che attraversa lo schermo.
	var section = root.closest('.hv2-steps');
	var shots = section ? Array.prototype.slice.call(section.querySelectorAll('.hv2-steps__pinshot')) : [];
	var screen = section ? section.querySelector('.hv2-steps__phone--pin .hv2-steps__screen') : null;
	var pin = !!screen && shots.length === cards.length;

	function reset() {
		cards.forEach(function (c) { c.style.transform = ''; });
		if (section) section.classList.remove('hv2-steps--pin');
	}

	function update() {
		raf = 0;
		if (!wide.matches) { reset(); return; }
		if (pin) section.classList.add('hv2-steps--pin');
		var sr = pin ? screen.getBoundingClientRect() : null;
		for (var i = 0; i < cards.length - 1; i++) {
			var next = slides[i + 1];
			var stick = parseFloat(getComputedStyle(next).top) || 0;
			var travel = next.offsetHeight + 16;
			var p = 1 - (next.getBoundingClientRect().top - stick) / travel;
			p = Math.max(0, Math.min(1, p));
			if (pin) {
				var cut = (cards[i + 1].getBoundingClientRect().top - sr.top) / sr.height;
				cut = Math.max(0, Math.min(1, cut));
				if (reduce.matches) cut = cut < 0.5 ? 0 : 1;
				shots[i + 1].style.clipPath = 'inset(' + (cut * 100).toFixed(2) + '% 0 0 0)';
			}
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
