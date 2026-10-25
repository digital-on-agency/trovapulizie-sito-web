/**
 * Sezione "Quando serve" (template-parts/scenario-tabs.php, TICKET-44).
 * Tab WAI-ARIA (click, frecce, Home/End: una sola tab nel tab order) che mostrano il pannello dello scenario, e il
 * confronto prima/dopo: l'<input type="range"> invisibile copre tutta l'immagine, il suo valore diventa la
 * variabile CSS --pos che taglia l'immagine "prima" e posiziona divisore e maniglia. Al cambio di tab la maniglia
 * torna a meta'. Senza questo file resta il pannello di default con la maniglia ferma.
 */
(function () {
	'use strict';

	function setPos(media, value) {
		media.style.setProperty('--pos', value + '%');
		var range = media.querySelector('input[type="range"]');
		if (range && Number(range.value) !== value) range.value = value;
	}

	function initMedia(media) {
		var range = media.querySelector('input[type="range"]');
		if (!range || media.hasAttribute('data-compare-ready')) return;
		media.setAttribute('data-compare-ready', '');
		range.addEventListener('input', function () { setPos(media, Number(range.value)); });
		range.addEventListener('pointerdown', function () { media.classList.add('is-dragging'); });
		['pointerup', 'pointercancel', 'blur'].forEach(function (ev) {
			range.addEventListener(ev, function () { media.classList.remove('is-dragging'); });
		});
	}

	function initRoot(root) {
		var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
		var panels = Array.prototype.slice.call(root.querySelectorAll('[role="tabpanel"]'));
		root.querySelectorAll('[data-compare]').forEach(initMedia);
		if (!tabs.length) return;

		function activate(key, focus) {
			tabs.forEach(function (t) {
				var on = t.getAttribute('data-key') === key;
				t.setAttribute('aria-selected', on ? 'true' : 'false');
				t.tabIndex = on ? 0 : -1;
				if (on && focus) t.focus();
			});
			panels.forEach(function (p) {
				var on = p.getAttribute('data-key') === key;
				p.hidden = !on;
				if (on) {
					var media = p.querySelector('[data-compare]');
					if (media) setPos(media, 50);
				}
			});
		}

		tabs.forEach(function (tab, i) {
			tab.addEventListener('click', function () { activate(tab.getAttribute('data-key'), false); });
			tab.addEventListener('keydown', function (e) {
				var j = null;
				if (e.key === 'ArrowRight' || e.key === 'ArrowDown') j = (i + 1) % tabs.length;
				else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') j = (i - 1 + tabs.length) % tabs.length;
				else if (e.key === 'Home') j = 0;
				else if (e.key === 'End') j = tabs.length - 1;
				if (j === null) return;
				e.preventDefault();
				activate(tabs[j].getAttribute('data-key'), true);
			});
		});
	}

	document.querySelectorAll('[data-scenario-tabs]').forEach(initRoot);
	// Confronti prima/dopo fuori dai tab (template-parts/works-gallery.php, landing B2B): stesso markup, stessa maniglia.
	document.querySelectorAll('[data-compare]').forEach(function (media) {
		if (!media.closest('[data-scenario-tabs]')) initMedia(media);
	});
})();
