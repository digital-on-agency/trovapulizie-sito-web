/**
 * Movimento della pagina Diventa cleaner (page-cleaner-landing.php).
 *
 * - Ingressi: aggiunge .dc--motion a main.dc (il CSS nasconde i blocchi solo con questa classe: senza JavaScript la
 *   pagina e' tutta visibile) e marca con .dc-rv i blocchi da far entrare, con --d = posizione fra i fratelli per la
 *   cascata. A fine ingresso toglie .dc-rv: da li' valgono le transizioni di hover del blocco, senza il ritardo
 *   della cascata.
 * - Hero: telefono e card flottanti entrano dopo il testo; su puntatore fine il telefono si inclina seguendo il mouse
 *   e le card si muovono in parallasse (variabili --rx --ry --px --py sullo stage).
 * - Stima dei guadagni: conta da zero quando la card entra in vista; si ferma se l'utente tocca i comandi.
 *
 * Niente di tutto questo con "riduci movimento". Nessuna libreria.
 */
(function () {
	'use strict';

	var main = document.querySelector('main.dc');
	if (!main || !('IntersectionObserver' in window)) return;
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
	main.classList.add('dc--motion');

	var GRUPPI = [
		'.dc .sg-trust__bar > li',
		'.dc .sg-section-head',
		'.sg-steps--screens .sg-step',
		'.dc-tile',
		'.dc-earn__head',
		'.dc-calc',
		'.dc-estimate',
		'.dc-who__card',
		'.dc-req__card',
		'.dc-partners__list > li'
	];
	var blocchi = [];
	GRUPPI.forEach(function (sel) {
		Array.prototype.forEach.call(document.querySelectorAll(sel), function (el) {
			var fratelli = Array.prototype.filter.call(el.parentNode.children, function (c) { return c.matches(sel); });
			el.style.setProperty('--d', String(Math.max(0, fratelli.indexOf(el))));
			el.classList.add('dc-rv');
			blocchi.push(el);
		});
	});

	// A fine ingresso toglie la classe; il timer copre i casi in cui transitionend non arriva (scheda in background,
	// transizione sostituita da un'altra regola).
	function fine(el) {
		var fatto = false;
		function pulisci() {
			if (fatto) return;
			fatto = true;
			el.removeEventListener('transitionend', handler);
			el.classList.remove('dc-rv', 'is-in');
			el.style.removeProperty('--d');
		}
		function handler(e) { if (e.propertyName === 'opacity') pulisci(); }
		el.addEventListener('transitionend', handler);
		var d = parseInt(el.style.getPropertyValue('--d'), 10) || 0;
		setTimeout(pulisci, 900 + d * 80);
	}

	var io = new IntersectionObserver(function (voci) {
		voci.forEach(function (v) {
			if (!v.isIntersecting) return;
			io.unobserve(v.target);
			fine(v.target);
			v.target.classList.add('is-in');
			if (v.target.classList.contains('dc-estimate')) conta(v.target);
		});
	}, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
	blocchi.forEach(function (el) { io.observe(el); });

	// Hero: ingresso al caricamento, non allo scroll.
	var hero = document.querySelector('.dc-hero');
	if (hero) requestAnimationFrame(function () { requestAnimationFrame(function () { hero.classList.add('is-in'); }); });

	// Parallasse della hero, solo con mouse.
	var stage = document.querySelector('.dc-hero__stage');
	var panel = document.querySelector('.dc-hero__panel');
	if (stage && panel && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
		var raf = 0, nx = 0, ny = 0;
		panel.addEventListener('mousemove', function (e) {
			var r = panel.getBoundingClientRect();
			nx = (e.clientX - r.left) / r.width - 0.5;
			ny = (e.clientY - r.top) / r.height - 0.5;
			if (!raf) raf = requestAnimationFrame(applica);
		});
		panel.addEventListener('mouseleave', function () { nx = 0; ny = 0; if (!raf) raf = requestAnimationFrame(applica); });
		function applica() {
			raf = 0;
			stage.style.setProperty('--ry', (nx * 10).toFixed(2) + 'deg');
			stage.style.setProperty('--rx', (-ny * 6).toFixed(2) + 'deg');
			stage.style.setProperty('--px', (nx * -14).toFixed(1) + 'px');
			stage.style.setProperty('--py', (ny * -10).toFixed(1) + 'px');
		}
	}

	// Conteggio della stima: da 0 al valore mostrato, 800 ms, si interrompe se l'utente usa i comandi.
	function conta(card) {
		var out = card.querySelector('[data-earn-total]');
		var fmt = window.tpEarnings && window.tpEarnings.fmt;
		if (!out || !fmt) return;
		var target = parseInt(out.textContent.replace(/\D/g, ''), 10);
		if (!target) return;
		var stop = false;
		var ctrl = document.querySelector('[data-earn-controls]');
		if (ctrl) ['input', 'click'].forEach(function (t) { ctrl.addEventListener(t, function () { stop = true; }, { once: true }); });
		var t0 = performance.now();
		(function passo(t) {
			if (stop) return;
			var k = Math.min(1, (t - t0) / 800);
			var e = 1 - Math.pow(1 - k, 3);
			out.textContent = '≈ ' + fmt(Math.round(target * e / 10) * 10) + ' €';
			if (k < 1) requestAnimationFrame(passo);
			else out.textContent = '≈ ' + fmt(target) + ' €';
		})(t0);
	}
})();
