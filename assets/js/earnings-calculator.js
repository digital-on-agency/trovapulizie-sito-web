/**
 * Calcolatore "Quanto guadagni" della pagina Diventa cleaner (TICKET-54, template-parts/earnings-calculator.php).
 * stima = arrotonda ai 50 euro (tariffa x ore a settimana x settimane al mese). Nessuna chiamata di rete.
 * Senza questo script i comandi restano [hidden] e la card mostra l'esempio reso dal PHP.
 * window.tpEarnings.stima e' esposta per la QA (tools/qa-cleaner.js).
 */
(function () {
	'use strict';

	function stima(rate, hours, weeks) {
		return Math.round((rate * hours * weeks) / 50) * 50;
	}
	// Separatore delle migliaia col punto, senza dipendere da Intl (alcuni motori non raggruppano a 4 cifre).
	function fmt(n) {
		return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
	}
	window.tpEarnings = { stima: stima, fmt: fmt };

	var root = document.querySelector('[data-earnings]');
	if (!root) return;
	var controls = root.querySelector('[data-earn-controls]');
	var range = root.querySelector('[data-earn-rate]');
	var rateOut = root.querySelector('[data-earn-rate-out]');
	var total = root.querySelector('[data-earn-total]');
	var formula = root.querySelector('[data-earn-formula]');
	var chips = Array.prototype.slice.call(root.querySelectorAll('[data-earn-hours]'));
	if (!controls || !range || !total) return;

	var weeks = parseFloat(root.getAttribute('data-weeks')) || 4.33;
	var note = root.getAttribute('data-note') || '';
	var hours = 0;
	chips.forEach(function (c) { if (c.getAttribute('aria-checked') === 'true') hours = parseInt(c.getAttribute('data-earn-hours'), 10); });

	function paint() {
		var rate = parseInt(range.value, 10);
		var min = parseInt(range.min, 10), max = parseInt(range.max, 10);
		range.style.setProperty('--fill', ((rate - min) / (max - min) * 100) + '%');
		if (rateOut) rateOut.textContent = rate + ' €/h';
		total.textContent = '≈ ' + fmt(stima(rate, hours, weeks)) + ' €';
		if (formula) formula.textContent = '= ' + rate + ' €/h × ' + hours + ' h a settimana · ' + note;
		// Impulso della cifra a ogni cambio (CSS .is-bump, spento con "riduci movimento"); non al primo disegno.
		if (pronto) {
			total.classList.remove('is-bump');
			void total.offsetWidth;
			total.classList.add('is-bump');
		}
	}
	var pronto = false;

	range.addEventListener('input', paint);
	chips.forEach(function (c) {
		c.addEventListener('click', function () {
			hours = parseInt(c.getAttribute('data-earn-hours'), 10);
			chips.forEach(function (o) {
				var on = o === c;
				o.classList.toggle('is-on', on);
				o.setAttribute('aria-checked', on ? 'true' : 'false');
			});
			paint();
		});
	});
	controls.hidden = false;
	paint();
	pronto = true;
})();
