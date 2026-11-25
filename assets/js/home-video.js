/* Sezione video della Home v2 (TICKET-157, template-parts/home-video-v2.php).
   Il <video> non e' nell'HTML iniziale: nessun download prima del clic. Senza JavaScript il tasto e' un link al file mp4.
   Con JavaScript: il link diventa un <button> (stesse classi e contenuto) e al clic nasce il <video> con src, controls,
   playsinline, preload="metadata"; parte con audio (il clic e' il gesto dell'utente), il fuoco passa al video e al primo
   avvio parte una sola volta tpTrack('video_play', { video: 'una-luce' }). Nessun autoplay. */
(function () {
	'use strict';

	function init(frame) {
		var link = frame.querySelector('a.hv2-video__play');
		if (!link) return;
		var src = frame.getAttribute('data-video-src') || link.getAttribute('href');
		if (!src) return;

		// Il link diventa un <button>: stesse classi, stesso contenuto, stessa etichetta per i lettori di schermo.
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = link.className;
		btn.setAttribute('aria-label', link.getAttribute('aria-label') || '');
		while (link.firstChild) btn.appendChild(link.firstChild);
		link.parentNode.replaceChild(btn, link);

		var tracked = false;
		btn.addEventListener('click', function () {
			var video = document.createElement('video');
			video.className = 'hv2-video__video';
			video.controls = true;
			video.setAttribute('playsinline', '');
			video.preload = 'metadata';
			video.setAttribute('aria-label', 'Una luce, il video di Trovapulizie');
			var poster = frame.querySelector('.hv2-video__poster');
			if (poster && poster.currentSrc) video.poster = poster.currentSrc;
			video.src = src;
			video.addEventListener('play', function () {
				if (tracked) return;
				tracked = true;
				try {
					window.tpTrack && window.tpTrack('video_play', { video: 'una-luce' });
				} catch (e) { /* il tracciamento non deve mai fermare il video */ }
			});
			frame.appendChild(video);
			frame.classList.add('is-playing');
			if (poster) poster.hidden = true;
			btn.remove();
			var p = video.play();
			if (p && p.catch) p.catch(function () { /* audio bloccato dal browser: restano i comandi per avviarlo a mano */ });
			video.focus();
		});
	}

	function start() {
		var frames = document.querySelectorAll('.hv2-video__frame');
		for (var i = 0; i < frames.length; i++) init(frames[i]);
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
	else start();
})();
