/**
 * Barra di ricerca della Home (template-parts/search-bar.php con services): i servizi solo aziende (option
 * data-b2b) non vanno ai risultati ma al preventivo B2B dell'app, con step=0, come MarketingHome.handleSearch
 * della SPA. Senza questo script il form invia comunque address e service ai risultati.
 */
(function () {
	'use strict';
	Array.prototype.forEach.call(document.querySelectorAll('form[data-search-form]'), function (form) {
		var base = form.getAttribute('action');
		var b2b = form.getAttribute('data-b2b-action');
		var select = form.querySelector('select[name="service"]');
		if (!select || !b2b) return;
		form.addEventListener('submit', function () {
			var opt = select.options[select.selectedIndex];
			var aziende = opt && opt.hasAttribute('data-b2b');
			form.setAttribute('action', aziende ? b2b : base);
			var step = form.querySelector('input[name="step"]');
			if (aziende && !step) {
				step = document.createElement('input');
				step.type = 'hidden';
				step.name = 'step';
				step.value = '0';
				form.insertBefore(step, form.firstChild);
			} else if (!aziende && step) {
				step.parentNode.removeChild(step);
			}
		});
	});
})();
