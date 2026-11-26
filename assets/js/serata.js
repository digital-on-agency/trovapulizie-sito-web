/**
 * Survey della serata 25/11 (page-serata.php, inc/serata.php). Costruisce le schermate dalla definizione in data-config
 * (stessa fonte della validazione lato server) e invia tutto in una volta a POST /wp-json/tp/v1/serata all'ultima
 * domanda. Nessuna libreria, nessun tracking. Classi e pelle della Survey OnField (assets/css/survey.css).
 *
 * Flusso: benvenuto -> nome (gia' compilato dal link) -> voto -> ricordo -> chiarezza -> dubbio (solo se il modello
 * non era chiaro) -> servizi -> approfondire -> "E adesso?" con Invia -> grazie. Etichetta "N / 7": il dubbio condivide
 * il numero della domanda 4. Le scelte singole avanzano al tocco, tranne l'ultima, che chiede Invia.
 * Il video della serata compare nella schermata finale solo a chi ha scelto "Rivedere il video" nella domanda 6;
 * preload="none": il file si scarica solo quando si preme play.
 */
(function () {
	'use strict';

	var root = document.querySelector('[data-serata]');
	if (!root) return;
	var cfg;
	try { cfg = JSON.parse(root.getAttribute('data-config')); } catch (e) { return; }
	var def = cfg.def;
	var ui = def.ui;
	var screenEl = root.querySelector('[data-sv-screen]');
	var topEl = root.querySelector('[data-sv-top]');
	var backEl = root.querySelector('[data-sv-back]');
	var countEl = root.querySelector('[data-sv-count]');
	var fillEl = root.querySelector('[data-sv-fill]');

	var ids = def.steps.map(function (s) { return s.id; });
	var stepById = {};
	def.steps.forEach(function (s) { stepById[s.id] = s; });

	var ans = { name: cfg.name || '' };
	var hist = [];
	var busy = false;
	var honey = null;
	var retries = 0;

	function el(tag, cls, text) {
		var n = document.createElement(tag);
		if (cls) n.className = cls;
		if (text !== undefined) n.textContent = text;
		return n;
	}
	function cta(label, onClick, extra) {
		var b = el('button', 'sv__cta' + (extra ? ' ' + extra : ''), label);
		b.type = 'button';
		b.addEventListener('click', onClick);
		return b;
	}
	function errorBox() {
		var m = el('p', 'sv__error');
		m.setAttribute('role', 'alert');
		m.hidden = true;
		return m;
	}
	function show(msg, text) { msg.textContent = text; msg.hidden = false; }

	/* ---- Navigazione ---- */
	function applies(step) {
		return !step.when || step.when[1].indexOf(ans[step.when[0]] || '') !== -1;
	}
	function nextAfter(key) {
		var i = key === 'welcome' ? -1 : ids.indexOf(key);
		for (var j = i + 1; j < ids.length; j++) {
			if (applies(stepById[ids[j]])) return ids[j];
		}
		return 'thanks';
	}
	function go(key) { hist.push(key); render(key); }
	function advance(from) { go(nextAfter(from)); }
	function back() {
		if (busy || hist.length < 2) return;
		hist.pop();
		render(hist[hist.length - 1]);
	}

	function chrome(key) {
		var showTop = key !== 'welcome' && key !== 'thanks';
		topEl.hidden = !showTop;
		var n = stepById[key] ? stepById[key].count : 0;
		countEl.textContent = n ? n + ' / ' + def.total : '';
		fillEl.style.width = (n / def.total) * 100 + '%';
		backEl.hidden = !(showTop && hist.length > 1);
	}

	function render(key) {
		screenEl.textContent = '';
		chrome(key);
		if (key === 'welcome') welcome();
		else if (key === 'thanks') thanks();
		else question(stepById[key]);
		window.scrollTo(0, 0);
		screenEl.focus({ preventScroll: true });
	}

	/* ---- Schermate ---- */
	function logo() {
		var p = el('p', 'sv__logo sv__logo--img');
		var img = el('img');
		img.src = cfg.logo; img.alt = def.brand; img.width = 1356; img.height = 212;
		p.appendChild(img);
		return p;
	}

	function welcome() {
		var w = def.welcome;
		screenEl.appendChild(logo());
		screenEl.appendChild(el('h1', 'sv__title', w.title));
		screenEl.appendChild(el('p', 'sv__body', w.body));
		screenEl.appendChild(el('p', 'sv__sign', w.sign));
		screenEl.appendChild(cta(w.cta, function () { advance('welcome'); }));
	}

	function thanks() {
		var t = def.thanks;
		var name = (ans.name || '').trim().split(/\s+/)[0] || '';
		var icon = el('div', 'sv__ok');
		icon.setAttribute('aria-hidden', 'true');
		icon.appendChild(el('span', '', '✓'));
		screenEl.appendChild(icon);
		screenEl.appendChild(el('h1', 'sv__title', t.title.replace('%s', name ? ', ' + name : '')));
		screenEl.appendChild(el('p', 'sv__body', t.body));
		screenEl.appendChild(el('p', 'sv__sign', t.sign));
		if (cfg.video && (ans.approfondire || []).indexOf('video') !== -1) {
			var box = el('div', 'sv__video');
			box.appendChild(el('h2', 'sv__video-title', t.video));
			var v = el('video');
			v.src = cfg.video; v.controls = true; v.preload = 'none'; v.setAttribute('playsinline', '');
			if (cfg.poster) v.poster = cfg.poster;
			v.setAttribute('aria-label', t.video);
			box.appendChild(v);
			screenEl.appendChild(box);
		}
	}

	function question(step) {
		screenEl.appendChild(el('h1', 'sv__title', step.title));
		if (step.hint) screenEl.appendChild(el('p', 'sv__hint', step.hint));
		var msg = errorBox();
		if (step.type === 'text' || step.type === 'textarea') textQ(step, msg);
		else if (step.type === 'scale') scaleQ(step, msg);
		else choiceQ(step, msg);
	}

	// Avanti/Invia comune: controllo dell'obbligatorio, poi avanza o invia.
	function footer(step, msg, ok) {
		screenEl.appendChild(msg);
		var label = step.last ? ui.send : (step.required || hasAnswer(step) ? ui.next : ui.skip);
		var btn = cta(label, function () {
			var err = ok();
			if (err) { show(msg, err); return; }
			msg.hidden = true;
			if (step.last) submit(btn, msg); else advance(step.id);
		}, 'sv__cta--wide');
		screenEl.appendChild(btn);
		if (step.last) privacyNote();
		return btn;
	}
	function hasAnswer(step) {
		var v = ans[step.id];
		return Array.isArray(v) ? v.length > 0 : !!(v && String(v).trim());
	}
	function relabel(btn, step) {
		if (!step.last && !step.required) btn.textContent = hasAnswer(step) ? ui.next : ui.skip;
	}

	function textQ(step, msg) {
		var area = step.type === 'textarea';
		var inp = el(area ? 'textarea' : 'input', 'sv__input' + (area ? ' sv__textarea' : ''));
		if (area) inp.rows = 4; else { inp.type = 'text'; inp.autocomplete = 'name'; }
		inp.maxLength = step.max;
		inp.placeholder = step.placeholder || '';
		inp.setAttribute('aria-label', step.title);
		inp.value = ans[step.id] || '';
		screenEl.appendChild(inp);
		var btn = footer(step, msg, function () {
			return step.required && !inp.value.trim() ? ui.required : '';
		});
		inp.addEventListener('input', function () {
			ans[step.id] = inp.value;
			inp.removeAttribute('aria-invalid');
			msg.hidden = true;
			relabel(btn, step);
		});
		if (!area) inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); btn.click(); } });
	}

	function scaleQ(step, msg) {
		var row = el('div', 'sv__scale');
		row.setAttribute('role', 'radiogroup');
		row.setAttribute('aria-label', step.title);
		for (var v = step.min; v <= step.max; v++) {
			(function (val) {
				var b = el('button', 'sv__scale-opt', String(val));
				b.type = 'button';
				b.setAttribute('role', 'radio');
				b.setAttribute('aria-checked', ans[step.id] === val ? 'true' : 'false');
				if (ans[step.id] === val) b.classList.add('is-on');
				b.addEventListener('click', function () {
					ans[step.id] = val;
					Array.prototype.forEach.call(row.children, function (c) {
						var on = c === b;
						c.classList.toggle('is-on', on);
						c.setAttribute('aria-checked', on ? 'true' : 'false');
					});
					msg.hidden = true;
					window.setTimeout(function () { advance(step.id); }, 170);
				});
				row.appendChild(b);
			})(v);
		}
		screenEl.appendChild(row);
		var legend = el('div', 'sv__scale-legend');
		legend.appendChild(el('span', '', step.low));
		legend.appendChild(el('span', '', step.high));
		screenEl.appendChild(legend);
		// Il voto avanza al tocco: il bottone serve solo a chi torna indietro e vuole confermare.
		if (ans[step.id]) footer(step, msg, function () { return ''; });
		else screenEl.appendChild(msg);
	}

	function choiceQ(step, msg) {
		var multi = step.type === 'multi';
		var cur = ans[step.id];
		var sel = multi ? (cur || []).slice() : (cur ? [cur] : []);
		var otherField = step.other_field;
		var list = el('div', 'sv__opts');
		list.setAttribute('role', multi ? 'group' : 'radiogroup');
		var otherWrap = null, otherInput = null, btn = null;

		function otherSelected() {
			return step.options.some(function (o) { return o.other && sel.indexOf(o.v) !== -1; });
		}
		function sync() {
			Array.prototype.forEach.call(list.children, function (b) {
				var on = sel.indexOf(b.getAttribute('data-v')) !== -1;
				b.classList.toggle('is-on', on);
				b.setAttribute('aria-checked', on ? 'true' : 'false');
			});
			if (otherWrap) otherWrap.hidden = !otherSelected();
		}
		function commit() {
			ans[step.id] = multi ? sel.slice() : (sel[0] || '');
			if (otherField) ans[otherField] = otherSelected() && otherInput ? otherInput.value : '';
			if (btn) relabel(btn, step);
		}

		step.options.forEach(function (o) {
			var b = el('button', 'sv__opt' + (multi ? ' sv__opt--multi' : ''));
			b.type = 'button';
			b.setAttribute('data-v', o.v);
			b.setAttribute('role', multi ? 'checkbox' : 'radio');
			b.appendChild(el('span', 'sv__opt-label', o.l));
			if (multi) b.appendChild(el('span', 'sv__opt-box'));
			b.addEventListener('click', function () {
				var i = sel.indexOf(o.v);
				if (multi) {
					if (i !== -1) sel.splice(i, 1);
					// L'opzione esclusiva ("No", "Niente per ora") toglie le altre, e viceversa.
					else if (o.v === step.exclusive) sel = [o.v];
					else { sel = sel.filter(function (v) { return v !== step.exclusive; }); sel.push(o.v); }
				} else sel = [o.v];
				sync();
				commit();
				msg.hidden = true;
				if (!multi && !o.other && !step.last) {
					// Tocco su una risposta singola: avanza dopo un attimo, il tempo di vedere la selezione.
					window.setTimeout(function () { advance(step.id); }, 170);
				} else if (o.other && otherInput && !otherWrap.hidden) {
					otherInput.focus();
				}
			});
			list.appendChild(b);
		});
		screenEl.appendChild(list);

		if (otherField) {
			otherWrap = el('div', 'sv__other');
			otherInput = el('input', 'sv__input');
			otherInput.type = 'text';
			otherInput.maxLength = 255;
			otherInput.placeholder = ui.other;
			otherInput.setAttribute('aria-label', ui.other);
			otherInput.value = ans[otherField] || '';
			otherInput.addEventListener('input', function () { ans[otherField] = otherInput.value; otherInput.removeAttribute('aria-invalid'); msg.hidden = true; });
			otherWrap.appendChild(otherInput);
			screenEl.appendChild(otherWrap);
		}
		sync();

		// Bottone solo dove serve: domande multiple, risposte singole con "Altro", ultima domanda.
		if (multi || otherField || step.last) {
			btn = footer(step, msg, function () {
				if (step.required && !sel.length) return ui.required;
				if (otherSelected() && !(otherInput.value || '').trim()) { otherInput.setAttribute('aria-invalid', 'true'); otherInput.focus(); return ui.other_required; }
				return '';
			});
			if (otherInput) otherInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); btn.click(); } });
		} else {
			screenEl.appendChild(msg);
		}
	}

	function privacyNote() {
		var p = el('p', 'sv__note', ui.privacy);
		var a = el('a', '', ui.privacy_link);
		a.href = cfg.privacy; a.target = '_blank'; a.rel = 'noopener';
		p.appendChild(a);
		screenEl.appendChild(p);
		// Honeypot: invisibile all'utente e alla tastiera, un bot lo compila.
		var hp = el('div', 'sv__hp');
		hp.setAttribute('aria-hidden', 'true');
		honey = el('input');
		honey.type = 'text'; honey.name = 'website'; honey.tabIndex = -1; honey.autocomplete = 'off';
		hp.appendChild(honey);
		screenEl.appendChild(hp);
	}

	/* ---- Invio ---- */
	function payload() {
		var p = { nonce: cfg.nonce, ts: cfg.ts, sig: cfg.sig, contact: cfg.contact || '', website: honey ? honey.value : '' };
		ids.forEach(function (k) {
			var st = stepById[k];
			var v = ans[k];
			if (!applies(st)) v = st.type === 'multi' ? [] : '';
			p[k] = st.type === 'multi' ? (v || []) : (v === undefined ? '' : v);
			if (st.other_field) p[st.other_field] = ans[st.other_field] || '';
		});
		return p;
	}

	function submit(btn, msg, keepLabel) {
		if (busy) return;
		busy = true;
		var label = keepLabel || btn.textContent;
		btn.disabled = true;
		btn.textContent = ui.sending;
		fetch(cfg.endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload()), credentials: 'omit' })
			.then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { status: r.status, body: j }; }); })
			.then(function (res) {
				busy = false;
				btn.disabled = false;
				btn.textContent = label;
				if (res.status === 201 || res.status === 200) { hist = []; go('thanks'); return; }
				// Invio prima del tempo minimo anti-bot (chi risponde a tocchi rapidi ci arriva): si riprova da soli.
				if (res.status === 429 && res.body && res.body.code === 'tp_survey_fast' && retries < 3) {
					retries++;
					btn.disabled = true;
					btn.textContent = ui.sending;
					window.setTimeout(function () { btn.disabled = false; submit(btn, msg, label); }, 4000);
					return;
				}
				var text = ui.error;
				if (res.status === 400 && res.body && res.body.data && res.body.data.fields) {
					// Un campo obbligatorio di una schermata precedente: si torna li'.
					var first = res.body.data.fields[0] === 'adesso_altro' ? 'adesso' : res.body.data.fields[0];
					if (first !== 'adesso' && stepById[first]) { go(first); return; }
					text = first === 'adesso' && res.body.data.fields.indexOf('adesso_altro') !== -1 ? ui.other_required : ui.required;
				} else if (res.status === 403) text = ui.expired;
				else if (res.status === 429 && res.body && res.body.code === 'tp_survey_limit') text = ui.limit;
				show(msg, text);
			})
			.catch(function () { busy = false; btn.disabled = false; btn.textContent = label; show(msg, ui.error); });
	}

	backEl.addEventListener('click', back);
	go('welcome');
})();
