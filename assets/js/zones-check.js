/* Controllo del CAP della Home v2 (TICKET-125, SPEC-homepage-v2 US-04). Nessuna richiesta di rete: la lista dei CAP
   attivi e' nel markup, in data-caps del form .hv2-zones__check come JSON { "00186": "Centro Storico (...)", ... }.
   Il form e' stampato con l'attributo hidden e lo togliamo qui: senza JavaScript non compare (mappa e chip restano).

   Esiti, scritti nel contenitore #hv2-zones-esito (aria-live="polite"):
   - CAP in lista     -> "<CAP> · Ci siamo: sei in <quartiere>" e il link "Apri l'app" (data-cta-position="zones_check");
                         il CAP si evidenzia sulla mappa (path[data-cap] riceve .is-selected, la mappa si sposta sulla zona);
                         se il CAP e' in lista ma non ha un poligono (00194) l'esito e' lo stesso, senza evidenziazione.
   - CAP di Roma fuori lista (00118-00199) -> "<CAP> · Non ancora da te. Scrivici su WhatsApp per sapere quando arriviamo." e il
                         link "Scrivici su WhatsApp" (data-cta-position="zones_whatsapp", testo precompilato "Ciao, il mio CAP e'
                         <CAP>: quando arrivate da me?"). Nessun campo email.
   - altro (non 5 cifre, o fuori da 00118-00199) -> "Scrivi un CAP di Roma (5 cifre)": il campo resta compilato e prende il focus.
   Stato sul form: data-state = "ok" | "no" | "errore" | assente. Dopo "Controlla" l'esito scende di 8 px e appare (CSS). */
(function () {
	'use strict';
	var form = document.querySelector('.hv2-zones__check');
	if (!form) return;
	var caps;
	try { caps = JSON.parse(form.getAttribute('data-caps') || '{}'); } catch (e) { return; }
	var input = form.querySelector('.hv2-zones__input');
	var out = form.querySelector('.hv2-zones__result');
	var section = form.closest('.hv2-zones');
	var stage = section ? section.querySelector('.hv2-zones__stage') : null;
	var appUrl = form.getAttribute('data-app-url') || '';
	var waUrl = form.getAttribute('data-wa-url') || '';
	form.hidden = false;

	function el(tag, cls, text) {
		var n = document.createElement(tag);
		if (cls) n.className = cls;
		if (text) n.textContent = text;
		return n;
	}
	function link(href, text, pos) {
		var a = el('a', 'hv2-zones__cta', text);
		a.href = href;
		a.setAttribute('data-cta-position', pos);
		return a;
	}
	function isRoma(cap) { return /^00(11[89]|1[2-9]\d)$/.test(cap); }

	function resetMap() {
		if (!section) return;
		var old = section.querySelectorAll('.hv2-zones__svg path.is-selected');
		for (var i = 0; i < old.length; i++) old[i].classList.remove('is-selected');
		if (stage) { stage.style.removeProperty('--zs'); stage.style.removeProperty('--zx'); stage.style.removeProperty('--zy'); }
		section.classList.remove('is-ping');
	}
	function showOnMap(cap) {
		resetMap();
		var p = section && section.querySelector('.hv2-zones__svg path[data-cap="' + cap + '"]');
		if (!p) return;
		p.classList.add('is-selected');
		if (!stage || !p.getBBox) return;
		var vb = p.ownerSVGElement.viewBox.baseVal;
		var b = p.getBBox();
		var cx = (b.x + b.width / 2 - vb.x) / vb.width;
		var cy = (b.y + b.height / 2 - vb.y) / vb.height;
		var s = 1.5;
		// con il disco fermo, il centro della zona va al centro del disco; limite perche' lo stage resti pieno
		var lim = function (v) { return Math.max(0, Math.min(1, v)); };
		var tx = Math.max(1 - s, Math.min(0, 0.5 - s * lim(cx)));
		var ty = Math.max(1 - s, Math.min(0, 0.5 - s * lim(cy)));
		stage.style.setProperty('--zs', String(s));
		stage.style.setProperty('--zx', (tx * 100).toFixed(2) + '%');
		stage.style.setProperty('--zy', (ty * 100).toFixed(2) + '%');
		void section.offsetWidth;
		section.classList.add('is-ping');
	}
	function show(state, nodes) {
		form.setAttribute('data-state', state);
		out.textContent = '';
		out.classList.remove('is-in');
		nodes.forEach(function (n) { out.appendChild(n); });
		void out.offsetWidth;
		out.classList.add('is-in');
	}

	form.addEventListener('submit', function (ev) {
		ev.preventDefault();
		var v = (input.value || '').replace(/\s+/g, '');
		if (!/^\d{5}$/.test(v) || !isRoma(v)) {
			resetMap();
			show('errore', [el('p', 'hv2-zones__msg', 'Scrivi un CAP di Roma (5 cifre)')]);
			input.setAttribute('aria-invalid', 'true');
			input.focus();
			return;
		}
		input.removeAttribute('aria-invalid');
		if (Object.prototype.hasOwnProperty.call(caps, v)) {
			var p = el('p', 'hv2-zones__msg');
			p.appendChild(el('span', 'hv2-zones__tick', '✓'));
			p.appendChild(el('span', '', v + ' · Ci siamo: sei in ' + caps[v]));
			show('ok', [p, link(appUrl, 'Apri l’app', 'zones_check')]);
			showOnMap(v);
		} else {
			resetMap();
			var msg = el('p', 'hv2-zones__msg hv2-zones__msg--no', v + ' · Non ancora da te. Scrivici su WhatsApp per sapere quando arriviamo.');
			var nodes = [msg];
			if (waUrl) {
				var t = 'Ciao, il mio CAP è ' + v + ': quando arrivate da me?';
				nodes.push(link(waUrl + (waUrl.indexOf('?') < 0 ? '?' : '&') + 'text=' + encodeURIComponent(t), 'Scrivici su WhatsApp', 'zones_whatsapp'));
			}
			show('no', nodes);
		}
	});
	input.addEventListener('input', function () {
		if (form.getAttribute('data-state') === 'errore') input.removeAttribute('aria-invalid');
	});
})();
