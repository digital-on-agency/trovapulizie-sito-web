/**
 * Calcolatore della stima (template-parts/sgrosso-estimator.php, TICKET-46).
 * Legge metri quadri, tipo ed extra, applica la formula del live con i coefficienti scritti nei data-attribute
 * della sezione e aggiorna fascia di prezzo, riga di riepilogo e campo nascosto `note` del form email.
 * Il prezzo "conta" fino al valore in 720 ms (rAF); la prima volta parte da zero quando la card entra nel viewport.
 * Con prefers-reduced-motion il valore cambia di colpo. L'invio del form non e' qui: lo fa il modulo lead
 * (lead-form.js) sui form con data-lead-atomic.
 *
 * Modulo condiviso della formula (TICKET-84): window.tpStimaSgrosso(mq, extra, coef) e' la formula pura;
 * window.tpSgrossoStima(mq, extra) la applica ai coefficienti della sezione [data-estimator] della pagina e
 * restituisce {min, max}, oppure null se la sezione manca o i metri quadri non sono validi. Il passo Contatto del
 * form (lead-form.js) usa questa: nessuna seconda copia della formula. Va caricato prima di lead-form.js.
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

	/** Coefficienti dai data-attribute della sezione (default del live se mancano). */
	function readCoef(root) {
		return {
			base: parseFloat(root.getAttribute('data-base')) || 200,
			perMq: parseFloat(root.getAttribute('data-per-mq')) || 2.2,
			extraMin: parseFloat(root.getAttribute('data-extra-min')) || 25,
			extraMax: parseFloat(root.getAttribute('data-extra-max')) || 40,
			maxMult: parseFloat(root.getAttribute('data-max-mult')) || 1.35
		};
	}

	/** Stima della pagina per mq ed extra dati, con i coefficienti del calcolatore; null se non calcolabile. */
	function stimaPagina(mq, extra) {
		var root = document.querySelector('[data-estimator]');
		var n = Number(mq);
		if (!root || !isFinite(n) || n <= 0) return null;
		return stima(n, Number(extra) || 0, readCoef(root));
	}

	function initRoot(root) {
		var c = readCoef(root);
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

		// "Prenota il sopralluogo con questi dati" (TICKET-85): precompila il form della pagina (tipo, metri quadri, extra
		// per la stima del passo 2) tramite window.tpLeadFormPrefill di lead-form.js, scorre al form e porta il focus
		// sull'indirizzo. Il form e' #form (hero) oppure, se manca, #form-bottom; se mancano entrambi il pulsante resta nascosto.
		var book = root.querySelector('[data-calc-book]');
		function formBox() { return document.getElementById('form') || document.getElementById('form-bottom'); }
		if (book && formBox()) {
			book.hidden = false;
			book.addEventListener('click', function () {
				var box = formBox();
				if (!box) return;
				var s = read();
				var form = box.querySelector('[data-lead-form]');
				var prefilled = false;
				if (form && typeof window.tpLeadFormPrefill === 'function') prefilled = window.tpLeadFormPrefill(form, { tipo: s.tipo, mq: s.mq, extra: s.extras.length });
				if (window.tpTrack) window.tpTrack('marketing_cta_click', { landing: window.tpLanding ? window.tpLanding() : '', cta_position: 'calcolatore' });
				box.scrollIntoView(); // lo scorrimento morbido o istantaneo lo decide il CSS (prefers-reduced-motion)
				if (prefilled) { var addr = form.querySelector('input[name="indirizzo"]'); if (addr) addr.focus({ preventScroll: true }); }
				if (window.history && history.replaceState) history.replaceState(null, '', '#' + box.id);
			});
		}

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
	window.tpStimaSgrosso = stima; // formula pura, verificabile dalla console e dai test headless
	window.tpSgrossoStima = stimaPagina; // formula con i coefficienti della pagina, usata dal form
})();
