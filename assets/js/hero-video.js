/* Timelapse di sfondo della hero: parte solo senza "riduci movimento", sfuma sopra la foto
   quando il primo frame e' davvero in riproduzione e si ferma sull'ultimo frame (stanza in ordine).
   Se l'autoplay viene negato resta la foto statica: nessun errore in console. */
(function () {
	var v = document.querySelector('.hero__video');
	if (!v) return;
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
	// preload="none" nel markup: il file si scarica solo da qui, dopo il controllo sul movimento ridotto
	v.preload = 'auto';
	v.load();
	v.addEventListener('playing', function () { v.classList.add('is-playing'); }, { once: true });
	var start = function () {
		var p = v.play();
		if (p && p.catch) p.catch(function () {});
	};
	if (v.readyState >= 3) start(); else v.addEventListener('canplay', start, { once: true });
})();
