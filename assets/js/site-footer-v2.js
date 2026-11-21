/* Footer nuovo (SPEC-homepage-v2, TICKET-128): fisarmonica delle colonne sotto 1024 px.
   Markup: footer.site-footer-v2 con details.site-footer-v2__group (template-parts/site-footer-v2.php), stampati con
   l'attributo open: senza questo script restano tutti aperti e leggibili.
   Comportamento: sotto 1024 px all'avvio i gruppi sono chiusi e se ne apre uno per volta (aprirne uno chiude gli altri);
   da 1024 px in su sono sempre aperti e il titolo non e' interattivo (click annullato, fuori dall'ordine di tabulazione).
   Al cambio di larghezza si riallinea. Con "riduci movimento" l'apertura resta istantanea per CSS (nessuna logica qui).
   Nessuna dipendenza, nessun IntersectionObserver. */
(function () {
	'use strict';
	var footer = document.querySelector('.site-footer-v2');
	if (!footer) { return; }
	var groups = Array.prototype.slice.call(footer.querySelectorAll('details.site-footer-v2__group'));
	var mq = window.matchMedia('(max-width: 1023px)');
	var busy = false;

	function set(open) {
		busy = true;
		groups.forEach(function (g) { g.open = open; });
		busy = false;
	}
	function sync() {
		set(!mq.matches);
		groups.forEach(function (g) {
			var s = g.querySelector('summary');
			if (!s) { return; }
			if (mq.matches) { s.removeAttribute('tabindex'); } else { s.setAttribute('tabindex', '-1'); }
		});
	}
	groups.forEach(function (g) {
		var s = g.querySelector('summary');
		if (s) {
			s.addEventListener('click', function (e) { if (!mq.matches) { e.preventDefault(); } });
		}
		g.addEventListener('toggle', function () {
			if (busy || !mq.matches || !g.open) { return; }
			busy = true;
			groups.forEach(function (o) { if (o !== g) { o.open = false; } });
			busy = false;
		});
	});
	if (mq.addEventListener) { mq.addEventListener('change', sync); } else if (mq.addListener) { mq.addListener(sync); }
	sync();
})();
