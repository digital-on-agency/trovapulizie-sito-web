/**
 * Pagina thank-you (page-thank-you.php, TICKET-42). Legge sessionStorage.tp_thankyou scritto da lead-form.js
 * ({zona, tipoCasa, email, coupon}), riempie le righe del riepilogo (le righe senza dato restano nascoste) e
 * precompila l'email del signup. "Crea account" spara marketing_thankyou_create_account_click
 * {cta_position:'thank-you'} e porta al signup dell'app con ?email=; Google va al signup senza prefill.
 * Senza dati (apertura diretta, ricarica dopo un'altra pagina) la pagina resta valida con la sola riga Coupon.
 */
(function () {
	'use strict';

	var main = document.querySelector('main.ty');
	if (!main) return;
	var signup = main.getAttribute('data-signup-url') || '';
	var data = {};
	try { data = JSON.parse(sessionStorage.getItem('tp_thankyou') || '{}') || {}; } catch (e) { data = {}; }

	['zona', 'tipoCasa', 'email'].forEach(function (k) {
		var row = main.querySelector('[data-row="' + k + '"]');
		var val = main.querySelector('[data-val="' + k + '"]');
		if (row && val && data[k]) { val.textContent = data[k]; row.hidden = false; }
	});
	if (data.coupon) { var c = main.querySelector('[data-val="coupon"]'); if (c) c.textContent = data.coupon; }

	var form = main.querySelector('[data-ty-form]');
	var input = form ? form.querySelector('input[name="email"]') : null;
	var verified = main.querySelector('[data-verified]');
	function syncVerified() { if (verified && input) verified.hidden = !(data.email && input.value.trim() === data.email); }
	if (input && data.email) { input.value = data.email; }
	if (input) { input.addEventListener('input', syncVerified); syncVerified(); }

	if (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var email = input ? input.value.trim() : '';
			if (window.tpTrack) window.tpTrack('marketing_thankyou_create_account_click', { cta_position: 'thank-you' });
			window.location.href = signup + (email ? (signup.indexOf('?') === -1 ? '?' : '&') + 'email=' + encodeURIComponent(email) : '');
		});
	}
})();
