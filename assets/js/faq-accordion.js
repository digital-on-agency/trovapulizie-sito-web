/* FAQ: una domanda aperta per volta; apertura/chiusura via classe (il CSS anima grid-template-rows e il chevron).
   Funziona anche senza JS: la prima risposta e' visibile, le altre restano chiuse. */
(function () {
	'use strict';
	document.querySelectorAll('.faq__list').forEach(function (list) {
		var items = Array.prototype.slice.call(list.querySelectorAll('.faq__item'));
		items.forEach(function (item) {
			var btn = item.querySelector('.faq__q');
			if (!btn) return;
			btn.addEventListener('click', function () {
				var open = item.classList.contains('is-open');
				items.forEach(function (o) {
					var on = o === item && !open;
					o.classList.toggle('is-open', on);
					var b = o.querySelector('.faq__q');
					if (b) b.setAttribute('aria-expanded', on ? 'true' : 'false');
				});
			});
		});
	});
})();
