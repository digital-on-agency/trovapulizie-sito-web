/* Bottone di pausa del nastro delle recensioni (WCAG 2.2.2: il movimento che dura piu' di cinque
   secondi deve potersi fermare). La pausa vera la fa il CSS con .is-paused; qui si tiene solo lo
   stato e l'etichetta, cosi' senza JS il bottone non compare come un comando che non funziona. */
(function () {
	'use strict';
	var nastro = document.querySelector('.reviews__marquee');
	if (!nastro) return;
	var bottone = nastro.querySelector('.reviews__pause');
	var etichetta = bottone && bottone.querySelector('.reviews__pause-label');
	if (!bottone || !etichetta) return;

	bottone.addEventListener('click', function () {
		var fermo = nastro.classList.toggle('is-paused');
		bottone.setAttribute('aria-pressed', fermo ? 'true' : 'false');
		etichetta.textContent = fermo ? 'Riprendi' : 'Metti in pausa';
	});
})();
