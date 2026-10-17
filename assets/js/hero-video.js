/* Timelapse di sfondo della hero: parte solo senza "riduci movimento", sfuma sopra la foto
   quando il primo frame e' davvero in riproduzione e si ferma sull'ultimo frame (stanza in ordine).
   Se l'autoplay viene negato resta la foto statica: nessun errore in console.

   Sotto --tp-bp-desktop il box della hero e' alto 270px: il file desktop (1600px di larghezza, ~1 MB)
   e' fuori misura, quindi si chiede la rendition ridotta per convenzione <nome>-720.mp4 (TICKET-20).
   Se quella rendition non c'e' sul server resta la foto: il file desktop non viene mai chiesto da
   mobile, nemmeno come ripiego. */
(function () {
	var v = document.querySelector('.hero__video');
	if (!v) return;
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

	// Soglia unica con il CSS: il token --tp-bp-desktop su :root e' la sola fonte di verita'.
	var bp = getComputedStyle(document.documentElement).getPropertyValue('--tp-bp-desktop').trim() || '1024px';
	if (!window.matchMedia('(min-width: ' + bp + ')').matches) {
		var ridotta = v.src.replace(/\.mp4(\?|$)/, '-720.mp4$1');
		if (ridotta === v.src) return; // sorgente non riconosciuta: meglio la foto che 1 MB
		// La rendition ridotta manca sul server: resta la foto, niente ripiego sul file desktop.
		v.addEventListener('error', function () { v.removeAttribute('src'); }, { once: true });
		v.src = ridotta;
	}

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
