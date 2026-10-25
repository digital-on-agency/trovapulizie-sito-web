/**
 * Calcolatore della stima (template-parts/sgrosso-estimator.php, TICKET-46).
 * Legge metri quadri, tipo ed extra, applica la formula del live con i coefficienti scritti nei data-attribute
 * della sezione e aggiorna fascia di prezzo, riga di riepilogo e campo nascosto `note` del form email.
 * Il prezzo "conta" fino al valore in 720 ms (rAF); la prima volta parte da zero quando la card entra nel viewport.
 * Con prefers-reduced-motion il valore cambia di colpo. L'invio del form non e' qui: lo fa il modulo lead
 * (lead-form.js) sui form con data-lead-atomic.
 */
(function () {
	'use strict';

	var DURATION = 720;
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function round10(v) { return Math.round(v / 10) * 10; }

	/** Formula pura: stessa del live (base 200 + 2,2/mq; extra 25/40; massimo x1,35). */
	function stima(mq, extra, c) {
		var base = c.base + mq * c.perMq;
		return {
			min: round10(base + extra * c.extraMin),
			max: round10(base * c.maxMult + extra * c.extraMax)
		};
	}

	function initRoot(root) {
		var c = {
			base: parseFloat(root.getAttribute('data-base')) || 200,
			perMq: parseFloat(root.getAttribute('data-per-mq')) || 2.2,
			extraMin: parseFloat(root.getAttribute('data-extra-min')) || 25,
			extraMax: parseFloat(root.getAttribute('data-extra-max')) || 40,
			maxMult: parseFloat(root.getAttribute('data-max-mult')) || 1.35
		};
		var range = root.querySelector('input[name="mq"]');
		var outMq = root.querySelector('[data-mq-out]');
		var outMin = root.querySelector('[data-price-min]');
		var outMax = root.querySelector('[data-price-max]');
		var metaMq = root.querySelector('[data-meta-mq]');
		var metaTipo = root.querySelector('[data-meta-tipo]');
		var metaExtra = root.querySelector('[data-meta-extra]');
		var note = root.querySelector('[data-calc-note]');
		var result = root.querySelector('.sg-calc__result');
		if (!range || !outMin || !outMax) return;

		var shown = { min: 0, max: 0 };
		var raf = null;

		function animateTo(target) {
			if (raf) cancelAnimationFrame(raf);
			if (reduce) {
				shown = { min: target.min, max: target.max };
				outMin.textContent = target.min;
				outMax.textContent = target.max;
				return;
			}
			var from = { min: shown.min, max: shown.max };
			var t0 = performance.now();
			function step(now) {
				var p = Math.min(1, (now - t0) / DURATION);
				var e = 1 - Math.pow(1 - p, 3);
				shown.min = Math.round(from.min + (target.min - from.min) * e);
				shown.max = Math.round(from.max + (target.max - from.max) * e);
				outMin.textContent = shown.min;
				outMax.textContent = shown.max;
				if (p < 1) raf = requestAnimationFrame(step);
			}
			raf = requestAnimationFrame(step);
		}

		function read() {
			var mq = parseInt(range.value, 10) || 0;
			var tipoEl = root.querySelector('input[name="tipo"]:checked');
			var tipo = tipoEl ? tipoEl.value : '';
			var extras = Array.prototype.map.call(root.querySelectorAll('input[name="extra"]:checked'), function (x) { return x.value; });
			return { mq: mq, tipo: tipo, extras: extras };
		}

		var armed = false; // la prima animazione parte da zero quando la card e' visibile
		function update(animate) {
			var s = read();
			var p = stima(s.mq, s.extras.length, c);
			if (outMq) outMq.textContent = s.mq;
			range.style.setProperty('--fill', ((s.mq - 30) / 270 * 100) + '%');
			if (metaMq) metaMq.textContent = s.mq;
			if (metaTipo) metaTipo.textContent = s.tipo;
			if (metaExtra) metaExtra.textContent = s.extras.length;
			if (note) note.value = 'Calcolatore: ' + s.mq + 'mq · ' + s.tipo + ' · extra: ' + (s.extras.join(', ') || 'nessuno') + ' · stima ' + p.min + '-' + p.max + '€';
			if (animate && armed) animateTo(p);
			else if (!armed) { /* prima della comparsa: valori gia' scritti dal PHP, non toccare */ }
		}

		range.addEventListener('input', function () { update(true); });
		root.querySelectorAll('input[name="tipo"], input[name="extra"]').forEach(function (el) {
			el.addEventListener('change', function () { update(true); });
		});

		if (result && 'IntersectionObserver' in window && !reduce) {
			var io = new IntersectionObserver(function (entries) {
				if (!entries.some(function (e) { return e.isIntersecting; })) return;
				io.disconnect();
				armed = true;
				shown = { min: 0, max: 0 };
				update(true);
			}, { threshold: 0.3 });
			io.observe(result);
		} else {
			armed = true;
			var s0 = read();
			shown = stima(s0.mq, s0.extras.length, c);
		}
		update(false);
	}

	document.querySelectorAll('[data-estimator]').forEach(initRoot);
	window.tpStimaSgrosso = stima; // verificabile dalla console e dai test headless
})();
