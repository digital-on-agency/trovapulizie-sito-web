/* Menu mobile della navbar (TICKET-11). L'hamburger compare sotto --tp-bp-desktop e apre il pannello
   #nav-panel, che contiene le voci di menu e Accedi (nella barra resta il solo Registrati, Figma 4018:965).
   Chiusura: tocco fuori, Esc, scelta di una voce. Il focus entra nel pannello all'apertura e torna
   all'hamburger alla chiusura. Senza JS il pannello resta chiuso e la barra mostra comunque logo e
   Registrati: nessuna voce diventa irraggiungibile, le stesse ancore sono nel footer. */
(function () {
	'use strict';

	var burger = document.getElementById('nav-burger');
	var panel = document.getElementById('nav-panel');
	if (!burger || !panel) return;

	function open() {
		panel.hidden = false;
		burger.setAttribute('aria-expanded', 'true');
		burger.setAttribute('aria-label', 'Chiudi il menu');
		document.addEventListener('click', onDocClick);
		document.addEventListener('keydown', onKey);
		var first = panel.querySelector('a');
		if (first) first.focus();
	}

	function close(restoreFocus) {
		panel.hidden = true;
		burger.setAttribute('aria-expanded', 'false');
		burger.setAttribute('aria-label', 'Apri il menu');
		document.removeEventListener('click', onDocClick);
		document.removeEventListener('keydown', onKey);
		if (restoreFocus) burger.focus();
	}

	function onDocClick(e) {
		if (!panel.contains(e.target)) close(false);
	}

	function onKey(e) {
		if (e.key === 'Escape') close(true);
	}

	burger.addEventListener('click', function (e) {
		// senza questo il listener sul documento, appena agganciato, richiuderebbe subito il pannello
		e.stopPropagation();
		if (panel.hidden) open(); else close(true);
	});

	panel.addEventListener('click', function (e) {
		if (e.target.closest('a')) close(false);
	});

	// Tornando sopra la soglia desktop il pannello non deve restare aperto sopra il layout della barra.
	var bp = getComputedStyle(document.documentElement).getPropertyValue('--tp-bp-desktop').trim() || '1024px';
	var mq = window.matchMedia('(min-width: ' + bp + ')');
	(mq.addEventListener ? mq.addEventListener.bind(mq, 'change') : mq.addListener.bind(mq))(function (e) {
		if (e.matches && !panel.hidden) close(false);
	});
})();
