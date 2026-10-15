/* Servizi popolari: indicatore che scivola fra le tab, card in crossfade+rise (chip in stagger via CSS),
   frecce sinistra/destra fra le tab, reveal della sezione all'ingresso nel viewport. Niente dipendenze. */
(function () {
	'use strict';
	var root = document.querySelector('.services');
	if (!root) return;
	var list = root.querySelector('.services__tabs');
	var ind = root.querySelector('.services__tab-indicator');
	var tabs = Array.prototype.slice.call(root.querySelectorAll('.services__tab'));
	var cards = Array.prototype.slice.call(root.querySelectorAll('.services__card'));
	if (!tabs.length) return;
	var active = Math.max(0, tabs.map(function (t) { return t.classList.contains('is-active'); }).indexOf(true));

	function placeIndicator() {
		var t = tabs[active];
		if (!t || !ind) return;
		ind.style.width = t.offsetWidth + 'px';
		ind.style.height = t.offsetHeight + 'px';
		ind.style.transform = 'translate(' + t.offsetLeft + 'px,' + t.offsetTop + 'px)';
		if (!list.classList.contains('has-indicator')) {
			list.classList.add('has-indicator');
			// la prima posa non si anima
			requestAnimationFrame(function () { requestAnimationFrame(function () { list.classList.add('is-ready'); }); });
		}
	}
	function activate(i, focus) {
		if (i === active || !tabs[i]) return;
		active = i;
		tabs.forEach(function (t, k) {
			var on = k === i;
			t.classList.toggle('is-active', on);
			t.setAttribute('aria-selected', on ? 'true' : 'false');
			t.tabIndex = on ? 0 : -1;
		});
		cards.forEach(function (c, k) {
			if (k === i) {
				c.classList.add('is-enter');
				void c.offsetWidth;
				c.classList.remove('is-enter');
				c.classList.add('is-active');
				c.removeAttribute('aria-hidden');
			} else {
				c.classList.remove('is-active');
				c.setAttribute('aria-hidden', 'true');
			}
		});
		placeIndicator();
		if (focus) tabs[i].focus();
	}
	tabs.forEach(function (t, i) {
		t.addEventListener('click', function () { activate(i); });
		t.addEventListener('keydown', function (e) {
			var d = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
			if (!d) return;
			e.preventDefault();
			activate((active + d + tabs.length) % tabs.length, true);
		});
	});
	window.addEventListener('resize', placeIndicator);
	window.addEventListener('load', placeIndicator);
	if (document.fonts && document.fonts.ready) document.fonts.ready.then(placeIndicator);
	placeIndicator();

	if ('IntersectionObserver' in window) {
		var io = new IntersectionObserver(function (entries) {
			if (entries.some(function (e) { return e.isIntersecting; })) { root.classList.add('is-in'); io.disconnect(); }
		}, { threshold: 0.15 });
		io.observe(root);
	} else {
		root.classList.add('is-in');
	}
})();
