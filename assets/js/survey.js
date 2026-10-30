/**
 * Survey OnField v2 (page-survey.php, inc/survey.php). Costruisce le schermate dalla definizione in data-config
 * (domande, testi, diramazioni: una sola fonte, la stessa della validazione lato server) e invia le risposte a
 * POST /wp-json/tp/v1/survey. Nessuna libreria, nessun tracking.
 *
 * Flusso: welcome -> 10 domande (software_names solo se "Booking or field-service software") -> contatto (rami A/B/C)
 * -> commento libero -> grazie. Il ramo D (interest = no) salta il contatto e ha il link "Leave your contact anyway".
 * Le risposte si salvano alla fine del contatto (A/B/C o D con contatto) e il commento libero le raggiunge con un
 * secondo invio firmato: chi chiude la pagina all'ultima schermata non perde il lead. Il ramo D senza contatto
 * invia tutto alla schermata del commento.
 *
 * Etichetta di avanzamento "N / 10" (S5b e commento non contano), tasto indietro su tutte le schermate tranne la
 * prima e l'ultima, e tranne il commento dopo il salvataggio (la risposta c'e' gia': tornare al contatto la duplicherebbe).
 */
(function () {
	'use strict';

	var root = document.querySelector('[data-survey]');
	if (!root) return;
	var cfg;
	try { cfg = JSON.parse(root.getAttribute('data-config')); } catch (e) { return; }
	var def = cfg.def;
	var screenEl = root.querySelector('[data-sv-screen]');
	var topEl = root.querySelector('[data-sv-top]');
	var backEl = root.querySelector('[data-sv-back]');
	var countEl = root.querySelector('[data-sv-count]');
	var fillEl = root.querySelector('[data-sv-fill]');
	var TOTAL = 10;

	var stepById = {};
	def.steps.forEach(function (s) { stepById[s.id] = s; });

	var ans = {};          // risposte per campo (single: stringa, multi: array)
	var contact = { name: '', whatsapp: '', email: '', company: '', consent: false };
	var hist = [];         // pila delle schermate visitate
	var saved = null;      // {id,key} dopo il salvataggio
	var fbSent = false;
	var busy = false;

	function el(tag, cls, text) {
		var n = document.createElement(tag);
		if (cls) n.className = cls;
		if (text !== undefined) n.textContent = text;
		return n;
	}
	function branch() { return def.branches[ans.interest] || ''; }
	function hasContact() { return !!(contact.name.trim() || contact.whatsapp.trim() || contact.email.trim()); }

	/* ---- Navigazione ---- */
	function nextAfter(key) {
		if (key === 'welcome') return 'role';
		if (key === 'contact') return 'feedback';
		if (key === 'feedback') return 'thanks';
		var ids = def.steps.map(function (s) { return s.id; });
		var i = ids.indexOf(key);
		var j = i + 1;
		var st = stepById[ids[j]];
		if (st && st.when && (ans[st.when[0]] || []).indexOf(st.when[1]) === -1) j++;
		if (j >= ids.length) return branch() === 'D' ? 'feedback' : 'contact';
		return ids[j];
	}
	function go(key, push) {
		if (push !== false) hist.push(key);
		render(key);
	}
	function back() {
		if (busy || hist.length < 2) return;
		hist.pop();
		render(hist[hist.length - 1]);
	}
	function advance(from) { go(nextAfter(from)); }

	function chrome(key) {
		var showTop = key !== 'welcome' && key !== 'thanks';
		topEl.hidden = !showTop;
		var step = stepById[key];
		var n = key === 'contact' ? def.contact.count : (step ? step.count : 0);
		countEl.textContent = n ? n + ' / ' + TOTAL : '';
		fillEl.style.width = (key === 'feedback' ? 100 : (n / TOTAL) * 100) + '%';
		backEl.hidden = !(showTop && hist.length > 1 && !(key === 'feedback' && saved));
	}

	function render(key) {
		screenEl.textContent = '';
		chrome(key);
		if (key === 'welcome') welcome();
		else if (key === 'contact') contactScreen();
		else if (key === 'feedback') feedbackScreen();
		else if (key === 'thanks') thanks();
		else choice(stepById[key]);
		screenEl.scrollTop = 0;
		window.scrollTo(0, 0);
		screenEl.focus({ preventScroll: true });
	}

	function cta(label, onClick, extra) {
		var b = el('button', 'sv__cta' + (extra ? ' ' + extra : ''), label);
		b.type = 'button';
		b.addEventListener('click', onClick);
		return b;
	}
	function title(text) { var h = el('h1', 'sv__title', text); return h; }
	function hint(text) { return el('p', 'sv__hint', text); }

	/* ---- Schermate ---- */
	function welcome() {
		var w = def.welcome;
		screenEl.appendChild(el('p', 'sv__logo', def.brand));
		screenEl.appendChild(title(w.title));
		screenEl.appendChild(el('p', 'sv__body', w.body));
		screenEl.appendChild(cta(w.cta, function () { advance('welcome'); }));
		screenEl.appendChild(el('p', 'sv__note', w.note));
	}

	function choice(step) {
		var multi = step.type === 'multi';
		var cur = ans[step.id];
		var sel = multi ? (cur || []).slice() : (cur ? [cur] : []);
		var otherField = step.other_field;
		screenEl.appendChild(title(step.title));
		if (step.hint) screenEl.appendChild(hint(step.hint));
		var list = el('div', 'sv__opts');
		list.setAttribute('role', multi ? 'group' : 'radiogroup');
		var otherWrap = null, otherInput = null, nextBtn = null;

		function otherSelected() {
			return step.options.some(function (o) { return o.other && sel.indexOf(o.v) !== -1; });
		}
		function sync() {
			Array.prototype.forEach.call(list.children, function (b) {
				var on = sel.indexOf(b.getAttribute('data-v')) !== -1;
				b.classList.toggle('is-on', on);
				b.setAttribute(multi ? 'aria-checked' : 'aria-checked', on ? 'true' : 'false');
			});
			if (otherWrap) otherWrap.hidden = !otherSelected();
			if (nextBtn) nextBtn.disabled = sel.length === 0;
		}
		function commit() {
			if (multi) ans[step.id] = sel.slice(); else ans[step.id] = sel[0] || '';
			if (otherField) ans[otherField] = otherSelected() && otherInput ? otherInput.value : '';
			// Se cambia "tools" e il software non e' piu' scelto, la sotto-domanda non vale piu'.
			if (step.id === 'tools' && sel.indexOf('software') === -1) { delete ans.software_names; delete ans.software_other; }
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
				if (multi) { if (i === -1) sel.push(o.v); else sel.splice(i, 1); }
				else sel = [o.v];
				sync();
				commit();
				if (!multi && !o.other) {
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
			otherInput.maxLength = 200;
			otherInput.placeholder = def.ui.other;
			otherInput.setAttribute('aria-label', def.ui.other);
			otherInput.value = ans[otherField] || '';
			otherInput.addEventListener('input', function () { ans[otherField] = otherInput.value; });
			otherInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); if (nextBtn && !nextBtn.disabled) nextBtn.click(); } });
			otherWrap.appendChild(otherInput);
			screenEl.appendChild(otherWrap);
		}
		// Next solo dove serve: domande multiple e risposte singole con campo "altro".
		if (multi || otherField) {
			nextBtn = cta(def.ui.next, function () { commit(); advance(step.id); }, 'sv__cta--wide');
			screenEl.appendChild(nextBtn);
		}
		sync();
	}

	function field(name, label, opts) {
		var wrap = el('div', 'sv__field');
		var id = 'sv-' + name;
		var l = el('label', 'sv__label', label);
		l.setAttribute('for', id);
		var inp = el('input', 'sv__input');
		inp.id = id; inp.name = name;
		inp.type = opts.type || 'text';
		inp.placeholder = opts.placeholder || '';
		if (opts.autocomplete) inp.autocomplete = opts.autocomplete;
		if (opts.inputmode) inp.setAttribute('inputmode', opts.inputmode);
		inp.maxLength = opts.max || 190;
		inp.value = contact[name] || '';
		inp.addEventListener('input', function () { contact[name] = inp.value; inp.removeAttribute('aria-invalid'); });
		wrap.appendChild(l); wrap.appendChild(inp);
		if (opts.helper) { var h = el('p', 'sv__helper', opts.helper); h.id = id + '-h'; inp.setAttribute('aria-describedby', h.id); wrap.appendChild(h); }
		return wrap;
	}

	function contactScreen() {
		var c = def.contact;
		var b = branch();
		var t = c.titles[b === 'D' ? 'C' : b] || c.titles.C;
		screenEl.appendChild(title(t[0]));
		var form = el('form', 'sv__form');
		form.noValidate = true;
		var f = c.fields;
		form.appendChild(field('name', f.name[0], { placeholder: f.name[1], autocomplete: 'name', max: 120 }));
		form.appendChild(field('whatsapp', f.whatsapp[0], { placeholder: f.whatsapp[1], autocomplete: 'tel', inputmode: 'tel', max: 40, type: 'tel' }));
		form.appendChild(field('email', f.email[0], { placeholder: f.email[1], autocomplete: 'email', inputmode: 'email', max: 190, type: 'email' }));
		form.appendChild(el('p', 'sv__helper sv__helper--pair', c.helper));
		form.appendChild(field('company', f.company[0], { placeholder: f.company[1], autocomplete: 'organization', max: 160 }));

		var cl = el('label', 'sv__consent');
		var cb = el('input'); cb.type = 'checkbox'; cb.name = 'consent'; cb.checked = !!contact.consent;
		cb.addEventListener('change', function () { contact.consent = cb.checked; cl.classList.remove('is-error'); });
		var ct = el('span', 'sv__consent-text', c.consent + ' ');
		var a = el('a', '', c.privacy); a.href = cfg.privacy; a.target = '_blank'; a.rel = 'noopener';
		ct.appendChild(a);
		cl.appendChild(cb); cl.appendChild(ct);
		form.appendChild(cl);

		// Honeypot: invisibile all'utente e alla tastiera, un bot lo compila.
		var hp = el('div', 'sv__hp'); hp.setAttribute('aria-hidden', 'true');
		var hpi = el('input'); hpi.type = 'text'; hpi.name = 'website'; hpi.tabIndex = -1; hpi.autocomplete = 'off';
		hp.appendChild(hpi); form.appendChild(hp);

		var msg = el('p', 'sv__error'); msg.setAttribute('role', 'alert'); msg.hidden = true;
		form.appendChild(msg);
		form.addEventListener('input', function () { msg.hidden = true; });
		form.addEventListener('change', function () { msg.hidden = true; });
		var submit = el('button', 'sv__cta sv__cta--wide', t[1]);
		submit.type = 'submit';
		form.appendChild(submit);
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (busy) return;
			var bad = [];
			if (!contact.name.trim()) bad.push('name');
			var wa = contact.whatsapp.trim(), em = contact.email.trim();
			var digits = wa.replace(/\D+/g, '');
			if (wa && (digits.length < 7 || digits.length > 15 || /[^\d\s+()\-.]/.test(wa))) bad.push('whatsapp');
			if (em && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(em)) bad.push('email');
			if (!wa && !em) { bad.push('whatsapp'); bad.push('email'); }
			if (!contact.consent) bad.push('consent');
			mark(form, bad);
			if (bad.length) { msg.textContent = def.ui.invalid; msg.hidden = false; focusFirst(form, bad); return; }
			msg.hidden = true;
			submitAll(hpi.value, submit, msg, form);
		});
		screenEl.appendChild(form);
	}

	function mark(form, bad) {
		['name', 'whatsapp', 'email', 'company'].forEach(function (n) {
			var i = form.querySelector('[name="' + n + '"]');
			if (!i) return;
			if (bad.indexOf(n) !== -1) i.setAttribute('aria-invalid', 'true'); else i.removeAttribute('aria-invalid');
		});
		var cl = form.querySelector('.sv__consent');
		if (cl) cl.classList.toggle('is-error', bad.indexOf('consent') !== -1);
	}
	function focusFirst(form, bad) {
		var n = bad.indexOf('consent') !== -1 && bad.length === 1 ? 'consent' : bad[0];
		var i = form.querySelector('[name="' + n + '"]');
		if (i) i.focus();
	}

	function feedbackScreen() {
		var f = def.feedback;
		screenEl.appendChild(title(f.title));
		screenEl.appendChild(hint(f.hint));
		var ta = el('textarea', 'sv__input sv__textarea');
		ta.rows = 4; ta.maxLength = 2000; ta.placeholder = f.placeholder;
		ta.setAttribute('aria-label', f.title);
		ta.value = ans.feedback || '';
		ta.addEventListener('input', function () { ans.feedback = ta.value; });
		screenEl.appendChild(ta);
		var msg = el('p', 'sv__error'); msg.setAttribute('role', 'alert'); msg.hidden = true;
		screenEl.appendChild(msg);
		var send = cta(f.send, function () { finish(send, msg); }, 'sv__cta--wide');
		screenEl.appendChild(send);
		var skip = el('button', 'sv__link', f.skip);
		skip.type = 'button';
		skip.addEventListener('click', function () { ans.feedback = ''; ta.value = ''; finish(send, msg); });
		screenEl.appendChild(skip);
		if (branch() === 'D' && !saved) {
			var alt = el('button', 'sv__link sv__link--soft', def.contact.skip);
			alt.type = 'button';
			alt.addEventListener('click', function () { go('contact'); });
			screenEl.appendChild(alt);
		}
	}

	function thanks() {
		var t = def.thanks[branch()] || def.thanks.C;
		var icon = el('div', 'sv__ok'); icon.appendChild(el('span', '', '✓')); icon.setAttribute('aria-hidden', 'true');
		screenEl.appendChild(icon);
		screenEl.appendChild(title(t[0]));
		screenEl.appendChild(el('p', 'sv__body', t[1]));
	}

	/* ---- Invio ---- */
	function payload(extra) {
		var p = {
			nonce: cfg.nonce, ts: cfg.ts, sig: cfg.sig,
			role: ans.role, business_type: ans.business_type, team_size: ans.team_size, weekly_jobs: ans.weekly_jobs,
			tools: ans.tools || [], tools_other: ans.tools_other || '',
			software_names: ans.software_names || [], software_other: ans.software_other || '',
			challenge: ans.challenge, challenge_other: ans.challenge_other || '',
			software_spend: ans.software_spend, price_expectation: ans.price_expectation, interest: ans.interest,
			name: contact.name, whatsapp: contact.whatsapp, email: contact.email, company: contact.company,
			consent: !!contact.consent, feedback: ans.feedback || ''
		};
		for (var k in extra) p[k] = extra[k];
		return p;
	}
	function post(url, body) {
		return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body), credentials: 'omit' })
			.then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { status: r.status, body: j }; }); });
	}
	function explain(res, msg, form) {
		var code = res.body && res.body.code, text = def.ui.error;
		if (res.status === 400 && res.body && res.body.data && res.body.data.fields) { text = def.ui.invalid; if (form) mark(form, res.body.data.fields); }
		else if (res.status === 403) text = def.ui.expired;
		else if (res.status === 429) text = def.ui.limit;
		msg.textContent = text; msg.hidden = false;
		return code;
	}

	// Salva la risposta (alla fine del contatto). In caso di successo passa al commento libero.
	function submitAll(honey, btn, msg, form) {
		busy = true;
		var label = btn.textContent; btn.disabled = true; btn.textContent = def.ui.sending;
		post(cfg.endpoint, payload({ website: honey })).then(function (res) {
			busy = false; btn.disabled = false; btn.textContent = label;
			if (res.status === 201 || res.status === 200) {
				saved = { id: res.body.id || 0, key: res.body.key || '' };
				if (ans.feedback) fbSent = true; // il commento scritto prima del contatto viaggia gia' nel primo invio
				go('feedback');
			} else explain(res, msg, form);
		}).catch(function () { busy = false; btn.disabled = false; btn.textContent = label; msg.textContent = def.ui.error; msg.hidden = false; });
	}

	// Fine del commento libero: o aggiorna la risposta salvata, o (ramo D senza contatto) la salva ora.
	function finish(btn, msg) {
		if (busy) return;
		var text = (ans.feedback || '').trim();
		if (saved) {
			if (!text || fbSent || !saved.id) { go('thanks'); return; }
			busy = true; btn.disabled = true;
			post(cfg.endpoint + '/feedback', { nonce: cfg.nonce, id: saved.id, key: saved.key, feedback: text }).then(function () {
				busy = false; fbSent = true; go('thanks');
			}).catch(function () { busy = false; btn.disabled = false; msg.textContent = def.ui.error; msg.hidden = false; });
			return;
		}
		busy = true;
		var label = btn.textContent; btn.disabled = true; btn.textContent = def.ui.sending;
		post(cfg.endpoint, payload({ website: '' })).then(function (res) {
			busy = false; btn.disabled = false; btn.textContent = label;
			if (res.status === 201 || res.status === 200) { saved = { id: res.body.id || 0, key: res.body.key || '' }; fbSent = true; go('thanks'); }
			else explain(res, msg, null);
		}).catch(function () { busy = false; btn.disabled = false; btn.textContent = label; msg.textContent = def.ui.error; msg.hidden = false; });
	}

	backEl.addEventListener('click', back);
	go('welcome');
})();
