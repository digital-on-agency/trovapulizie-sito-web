/*
 * Come funziona della Home v2: telefono fermo e video della prenotazione legato allo scroll (da 1024 px, movimento
 * consentito). Markup in template-parts/home-steps-v2.php (.hv2-scrub), CSS nel blocco .hv2-scrub di style.css.
 *
 * - Acceso: mette .hv2-scrub-on su <html>; il CSS mostra il blocco alto (sticky) e nasconde il carosello delle schede.
 *   Sotto 1024 px, con "riduci movimento" o senza JavaScript resta il carosello di sempre.
 * - Video: data-src -> src solo quando la sezione si avvicina (5 MB, all-intra: ogni fotogramma e' un keyframe).
 * - Fluidita': il tempo del video insegue quello dato dallo scroll con un'interpolazione per fotogramma (niente salti
 *   quando la rotella scatta) e un solo seek alla volta (il successivo parte dopo 'seeked').
 * - Passi: .is-active sul passo in corso e .is-done sui precedenti, dai tempi data-t (secondi sul video).
 */
(function () {
	'use strict';
	var block = document.querySelector('[data-hv2-scrub]');
	if (!block) return;
	var video = block.querySelector('video');
	var steps = Array.prototype.slice.call(block.querySelectorAll('.hv2-scrub__step'));
	var starts = steps.map(function (s) { return parseFloat(s.getAttribute('data-t')) || 0; });
	var mqDesk = window.matchMedia('(min-width: 1024px)');
	var mqCalm = window.matchMedia('(prefers-reduced-motion: reduce)');
	var root = document.documentElement;
	var on = false, inView = false, raf = 0, shown = 0, seeking = false, active = -1, io = null;

	function duration() {
		return video && video.duration && isFinite(video.duration) ? video.duration : 12.6;
	}
	function target() {
		var r = block.getBoundingClientRect();
		var run = r.height - window.innerHeight;
		var p = run > 0 ? Math.min(1, Math.max(0, -r.top / run)) : 0;
		return p * (duration() - 0.05);
	}
	function setStep(t) {
		var i = 0;
		for (var k = 0; k < starts.length; k++) if (t >= starts[k] - 0.01) i = k;
		if (i === active) return;
		active = i;
		steps.forEach(function (s, k) {
			s.classList.toggle('is-active', k === i);
			s.classList.toggle('is-done', k < i);
		});
	}
	function seek() {
		if (!video || seeking || video.readyState < 1) return;
		if (Math.abs(video.currentTime - shown) < 1 / 60) return;
		seeking = true;
		video.currentTime = shown;
	}
	function frame() {
		raf = 0;
		if (!on || !inView) return;
		var t = target();
		var d = t - shown;
		shown = Math.abs(d) < 0.004 ? t : shown + d * 0.14;
		setStep(shown);
		seek();
		if (Math.abs(t - shown) > 0.004 || seeking) raf = requestAnimationFrame(frame);
	}
	function kick() {
		if (!raf && on && inView) raf = requestAnimationFrame(frame);
	}
	function loadVideo() {
		if (!video || video.getAttribute('src')) return;
		var src = video.getAttribute('data-src');
		if (!src) return;
		video.preload = 'auto';
		video.src = src;
		video.load();
	}
	function enable() {
		if (on) return;
		on = true;
		root.classList.add('hv2-scrub-on');
		if (video) video.pause();
		io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				inView = e.isIntersecting;
				if (inView) { loadVideo(); kick(); }
			});
		}, { rootMargin: '100% 0px' });
		io.observe(block);
		window.addEventListener('scroll', kick, { passive: true });
		window.addEventListener('resize', kick);
		shown = target();
		setStep(shown);
	}
	function disable() {
		if (!on) return;
		on = false;
		root.classList.remove('hv2-scrub-on');
		if (io) io.disconnect();
		window.removeEventListener('scroll', kick);
		window.removeEventListener('resize', kick);
	}
	function update() {
		if (mqDesk.matches && !mqCalm.matches && 'IntersectionObserver' in window) enable(); else disable();
	}
	if (video) {
		video.addEventListener('seeked', function () { seeking = false; kick(); });
		video.addEventListener('loadedmetadata', kick);
	}
	[mqDesk, mqCalm].forEach(function (mq) {
		if (mq.addEventListener) mq.addEventListener('change', update); else if (mq.addListener) mq.addListener(update);
	});
	update();
})();
