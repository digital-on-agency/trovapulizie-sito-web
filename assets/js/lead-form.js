/**
 * Modulo lead delle landing (ADR-004, TICKET-40/41/46). Nessuna libreria.
 *
 * Form in tre passi [data-lead-form] (template-parts/lead-form.php), stesso contratto della SPA
 * (front-end/src/lib/marketing/leads.ts, SPEC-landing-sgrosso §2):
 *   passo 1  POST  {api}/api/leads                          contactOnly:true  -> {contattoId, token}
 *   passo 2  POST  {api}/api/leads/:contattoId/opportunita  token             -> {opportunitaId, token}
 *   passo 3  PATCH {api}/api/leads/:opportunitaId           token, step:3, final:true, foto
 * Form atomico [data-lead-atomic] (calcolatore): POST {api}/api/leads con email, zona e note gia' nei campi.
 * Eventi dataLayer via tpTrack (tp-track.js): preventivo_sgrosso_step {step, step_name, landing, cta_position,
 * tipoPulizia}, preventivo_sgrosso_submitted, marketing_form_submit {landing, form}. Poi sessionStorage
 * tp_thankyou e navigazione a /thank-you/.
 *
 * Base URL dell'API in data-api sul form (Theme Options > Integrazioni); chiave HERE in data-here-key sull'input
 * indirizzo: vuota = campo di testo semplice.
 *
 * [ATTENZIONE] Valori delle opzioni e chiavi di datiPulizia sono quelli esatti del backend (typecast:false):
 * li legge dal DOM, non li riscrive. Il campo honeypot `website` compilato fa rispondere ok senza creare nulla.
 */
(function () {
	'use strict';

	var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	var KNOWN_FAKE = { '3123456789': 1, '3123456780': 1, '3216549870': 1, '3000000000': 1 };
	var MSG_GENERIC = 'Invio non riuscito, riprova o chiamaci direttamente.';
	var MSG_PHOTOS = 'Le foto sono troppo pesanti: prova a rimuoverne qualcuna o riducine il numero.';
	var MAX_PHOTOS = 5;
	var MAX_PHOTO_BYTES = 5 * 1024 * 1024;
	var THANK_YOU = '/thank-you/';

	function track(name, params) { if (window.tpTrack) window.tpTrack(name, params); }
	function pageUrl() { return window.location.origin + window.location.pathname; }
	function utm() { return window.tpGetUtm ? window.tpGetUtm() : {}; }
	function gclid() { return window.tpGetGclid ? window.tpGetGclid() : undefined; }
	function phoneHref() { var a = document.querySelector('a[href^="tel:"]'); return a ? a.getAttribute('href') : ''; }

	/* ---- API ------------------------------------------------------------------------------------- */
	function apiBase(form) { return (form.getAttribute('data-api') || 'https://api.trovapulizie.com').replace(/\/+$/, ''); }

	function call(form, method, path, body) {
		return fetch(apiBase(form) + path, {
			method: method,
			cache: 'no-store',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(body)
		}).then(function (res) {
			return res.json().catch(function () { return {}; }).then(function (json) {
				if (res.ok) return json;
				var err = new Error(json && json.message ? json.message : 'HTTP ' + res.status);
				err.status = res.status;
				err.shown = res.status === 400 ? err.message : (res.status === 413 ? MSG_PHOTOS : MSG_GENERIC);
				throw err;
			});
		}, function () {
			var err = new Error('network'); err.shown = MSG_GENERIC; throw err;
		});
	}

	/* ---- Validazioni ---------------------------------------------------------------------------- */
	function digits(s) { return (s || '').replace(/\D+/g, ''); }
	function isRepeated(d) { return /^(\d)\1+$/.test(d); }
	function isSequential(d) {
		var asc = true, desc = true;
		for (var i = 1; i < d.length; i++) {
			var a = +d[i - 1], b = +d[i];
			if (b !== (a + 1) % 10) asc = false;
			if (b !== (a + 9) % 10) desc = false;
		}
		return asc || desc;
	}
	function phoneOk(prefix, local) {
		var d = digits(local);
		if (prefix === '+39') {
			if (!/^3\d{8,9}$/.test(d)) return false; // solo mobile: il canale e' WhatsApp
			return !(isRepeated(d) || isSequential(d) || KNOWN_FAKE[d]);
		}
		return d.length >= 6 && d.length <= 15;
	}

	/* ---- Errori a video ------------------------------------------------------------------------- */
	function setError(form, name, msg) {
		var box = form.querySelector('[data-error-for="' + name + '"]');
		if (box) box.textContent = msg || '';
		var field = form.querySelector('[data-field="' + name + '"]');
		if (field) field.classList.toggle('is-invalid', !!msg);
	}
	function clearErrors(form) {
		form.querySelectorAll('[data-error-for]').forEach(function (b) { b.textContent = ''; });
		form.querySelectorAll('.is-invalid').forEach(function (f) { f.classList.remove('is-invalid'); });
		showApiError(form, '');
	}
	function showApiError(form, msg, withPhone) {
		var box = form.querySelector('.lead-form__api-error');
		if (!box) return;
		box.textContent = '';
		if (!msg) { box.hidden = true; return; }
		box.appendChild(document.createTextNode(msg));
		var tel = phoneHref();
		if (withPhone && tel) {
			box.appendChild(document.createTextNode(' '));
			var a = document.createElement('a'); a.href = tel; a.textContent = 'Chiama ora'; box.appendChild(a);
		}
		box.hidden = false;
	}
	function focusFirstInvalid(form) {
		var f = form.querySelector('.is-invalid input, .is-invalid select');
		if (f) f.focus();
	}
	function val(form, name) { var el = form.querySelector('[name="' + name + '"]'); return el ? el.value.trim() : ''; }
	function checked(form, name) { var el = form.querySelector('input[name="' + name + '"]:checked'); return el ? el.value : ''; }
	function isChecked(form, name) { var el = form.querySelector('input[name="' + name + '"]'); return !!(el && el.checked); }

	function busy(form, on) {
		var b = form.querySelector('[data-submit]');
		if (b) b.disabled = !!on;
		form.classList.toggle('is-busy', !!on);
	}

	function gotoThankYou(data) {
		try { sessionStorage.setItem('tp_thankyou', JSON.stringify(data)); } catch (e) {}
		window.location.href = THANK_YOU;
	}

	/* ---- Foto: compressione lato client ---------------------------------------------------------- */
	function fileToPhoto(file) {
		return new Promise(function (resolve) {
			var done = function (blob, type, name) {
				var r = new FileReader();
				r.onload = function () { var s = String(r.result); resolve({ filename: name, contentType: type, base64: s.slice(s.indexOf(',') + 1) }); };
				r.onerror = function () { resolve(null); };
				r.readAsDataURL(blob);
			};
			if (!('createImageBitmap' in window)) { done(file, file.type, file.name); return; }
			createImageBitmap(file).then(function (bmp) {
				var max = 1600, w = bmp.width, h = bmp.height;
				var scale = Math.min(1, max / Math.max(w, h));
				var c = document.createElement('canvas');
				c.width = Math.round(w * scale); c.height = Math.round(h * scale);
				c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
				c.toBlob(function (blob) {
					if (!blob || blob.size >= file.size) done(file, file.type, file.name);
					else done(blob, 'image/jpeg', file.name.replace(/\.[^.]+$/, '') + '.jpg');
				}, 'image/jpeg', 0.8);
			}).catch(function () { done(file, file.type, file.name); });
		});
	}

	/* ---- Autocomplete indirizzo (HERE Autosuggest, come la SPA) ---------------------------------- */
	function initAutocomplete(input) {
		var key = input.getAttribute('data-here-key');
		var list = document.getElementById(input.getAttribute('aria-controls') || '');
		if (!key || !list) return;
		var timer = null, ctrl = null, items = [], active = -1;

		function close() { list.hidden = true; list.innerHTML = ''; items = []; active = -1; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); }
		function render() {
			list.innerHTML = '';
			items.forEach(function (label, i) {
				var li = document.createElement('li');
				li.id = list.id + '-' + i; li.setAttribute('role', 'option'); li.textContent = label;
				li.addEventListener('mousedown', function (e) { e.preventDefault(); choose(i); });
				list.appendChild(li);
			});
			list.hidden = items.length === 0;
			input.setAttribute('aria-expanded', items.length ? 'true' : 'false');
		}
		function highlight(i) {
			active = i;
			Array.prototype.forEach.call(list.children, function (li, j) {
				li.setAttribute('aria-selected', j === i ? 'true' : 'false');
				if (j === i) input.setAttribute('aria-activedescendant', li.id);
			});
		}
		function choose(i) { if (items[i]) { input.value = items[i]; input.dispatchEvent(new Event('input', { bubbles: true })); } close(); }
		function search(q) {
			if (ctrl) ctrl.abort();
			ctrl = new AbortController();
			var url = 'https://autosuggest.search.hereapi.com/v1/autosuggest?q=' + encodeURIComponent(q) + '&apiKey=' + encodeURIComponent(key) + '&in=bbox:6.6,35.5,18.5,47.1&limit=6&lang=it';
			fetch(url, { signal: ctrl.signal }).then(function (r) { return r.ok ? r.json() : { items: [] }; }).then(function (data) {
				items = (data.items || []).map(function (it) { return it.address && it.address.label ? it.address.label : (it.title || ''); }).filter(Boolean);
				render();
			}).catch(function () { /* rete o abort: il campo resta testo libero */ });
		}
		input.addEventListener('input', function () {
			var q = input.value.trim();
			clearTimeout(timer);
			if (q.length < 3) { close(); return; }
			timer = setTimeout(function () { search(q); }, 300);
		});
		input.addEventListener('keydown', function (e) {
			if (list.hidden) return;
			if (e.key === 'ArrowDown') { e.preventDefault(); highlight((active + 1) % items.length); }
			else if (e.key === 'ArrowUp') { e.preventDefault(); highlight((active - 1 + items.length) % items.length); }
			else if (e.key === 'Enter') { if (active >= 0) { e.preventDefault(); choose(active); } }
			else if (e.key === 'Escape') { close(); }
		});
		input.addEventListener('blur', function () { setTimeout(close, 120); });
	}

	/* ---- Form in tre passi ---------------------------------------------------------------------- */
	function initMultiStep(form) {
		var landing = form.getAttribute('data-landing') || '';
		var ctaPosition = form.getAttribute('data-cta-position') || 'hero';
		var steps = Array.prototype.slice.call(form.querySelectorAll('[data-step]'));
		var submitBtn = form.querySelector('[data-submit]');
		var backBtn = form.querySelector('[data-back]');
		var labels = []; try { labels = JSON.parse(submitBtn.getAttribute('data-labels') || '[]'); } catch (e) {}
		var stepNames = ['contatto', 'abitazione', 'servizio'];
		var stepLabels = ['Contatto', 'Abitazione', 'Servizio'];
		var state = { step: 1, contattoId: '', contattoToken: '', opportunitaId: '', opportunitaToken: '', photos: [] };

		var levels = {};
		try { levels = JSON.parse((form.querySelector('[data-sporco-levels]') || {}).textContent || '{}'); } catch (e) {}
		var levelKeys = Object.keys(levels);
		var range = form.querySelector('input[name="livelloSporcoIdx"]');
		var hiddenLevel = form.querySelector('input[name="livelloSporco"]');
		if (range && levelKeys.length) {
			var syncLevel = function () {
				var k = levelKeys[Math.min(levelKeys.length - 1, Math.max(0, parseInt(range.value, 10) || 0))];
				if (hiddenLevel) hiddenLevel.value = k;
				var t = form.querySelector('[data-sporco-title]'), s = form.querySelector('[data-sporco-sub]');
				if (t) t.textContent = levels[k][0];
				if (s) s.textContent = levels[k][1];
				range.setAttribute('aria-valuetext', levels[k][0]);
			};
			range.addEventListener('input', syncLevel);
			syncLevel();
		}

		var addrInput = form.querySelector('input[name="indirizzo"]');
		if (addrInput) initAutocomplete(addrInput);

		var fileInput = form.querySelector('input[name="foto"]');
		var photoList = form.querySelector('[data-photo-list]');
		function renderPhotos() {
			if (!photoList) return;
			photoList.innerHTML = '';
			state.photos.forEach(function (p, i) {
				var li = document.createElement('li');
				var img = document.createElement('img'); img.src = 'data:' + p.contentType + ';base64,' + p.base64; img.alt = p.filename;
				var rm = document.createElement('button'); rm.type = 'button'; rm.className = 'lead-form__photo-remove'; rm.setAttribute('aria-label', 'Rimuovi ' + p.filename); rm.textContent = '×';
				rm.addEventListener('click', function () { state.photos.splice(i, 1); renderPhotos(); });
				li.appendChild(img); li.appendChild(rm); photoList.appendChild(li);
			});
		}
		if (fileInput) {
			fileInput.addEventListener('change', function () {
				var files = Array.prototype.slice.call(fileInput.files || []);
				fileInput.value = '';
				setError(form, 'foto', '');
				var accepted = [];
				for (var i = 0; i < files.length; i++) {
					var f = files[i];
					if (state.photos.length + accepted.length >= MAX_PHOTOS) { setError(form, 'foto', 'Massimo ' + MAX_PHOTOS + ' foto.'); break; }
					if (f.type.indexOf('image/') !== 0) { setError(form, 'foto', 'Solo immagini.'); continue; }
					if (f.size > MAX_PHOTO_BYTES) { setError(form, 'foto', '"' + f.name + '" supera i 5MB.'); continue; }
					accepted.push(f);
				}
				if (!accepted.length) return;
				form.classList.add('is-compressing');
				Promise.all(accepted.map(fileToPhoto)).then(function (photos) {
					photos.forEach(function (p) { if (p) state.photos.push(p); });
					renderPhotos();
				}).finally(function () { form.classList.remove('is-compressing'); });
			});
		}

		function tipoPulizia() { return checked(form, 'tipoPulizia'); }
		function tipoPuliziaLabel() { var el = form.querySelector('input[name="tipoPulizia"]:checked'); return el ? (el.getAttribute('data-label') || el.value) : ''; }
		function telefono() { return val(form, 'prefisso') + digits(val(form, 'cellulare')); }

		function datiPulizia(step, completo) {
			var d = {
				tipoPulizia: tipoPulizia(),
				tipoAppartamento: checked(form, 'tipoAppartamento') || undefined,
				mq: val(form, 'mq') || undefined,
				indirizzo: val(form, 'indirizzo'),
				pavimento: checked(form, 'pavimento') || undefined,
				bagni: val(form, 'bagni') || undefined,
				arredata: checked(form, 'arredata') || undefined,
				livello_sporco: hiddenLevel ? hiddenLevel.value || undefined : undefined,
				urgenza: checked(form, 'urgenza') || undefined,
				_step: step,
				_completo: completo
			};
			Object.keys(d).forEach(function (k) { if (d[k] === undefined) delete d[k]; });
			return d;
		}
		function note(completo) {
			var mq = val(form, 'mq');
			var parts = [tipoPuliziaLabel(), checked(form, 'tipoAppartamento'), mq ? mq + 'mq' : ''].filter(Boolean);
			var base = parts.join(' · ') || 'Sgrosso casa';
			return completo ? base : base + ' (form non completato)';
		}

		function show(step) {
			state.step = step;
			steps.forEach(function (fs) { fs.hidden = parseInt(fs.getAttribute('data-step'), 10) !== step; });
			var n = form.querySelector('[data-step-n]'); if (n) n.textContent = step;
			var nm = form.querySelector('[data-step-name]'); if (nm) nm.textContent = stepLabels[step - 1];
			Array.prototype.forEach.call(form.querySelectorAll('.lead-form__bars i'), function (bar, i) { bar.classList.toggle('is-done', i < step); });
			if (submitBtn && labels[step - 1]) submitBtn.textContent = labels[step - 1];
			if (backBtn) backBtn.hidden = step === 1;
			clearErrors(form);
			var first = form.querySelector('[data-step="' + step + '"] input:not([type="hidden"]):not([type="radio"]):not([type="checkbox"]), [data-step="' + step + '"] input[type="radio"]');
			if (first && step > 1) first.focus();
		}

		function validateStep1() {
			var ok = true;
			if (!val(form, 'nome')) { setError(form, 'nome', 'Nome obbligatorio'); ok = false; }
			if (!val(form, 'cellulare')) { setError(form, 'cellulare', 'Numero obbligatorio'); ok = false; }
			else if (!phoneOk(val(form, 'prefisso'), val(form, 'cellulare'))) { setError(form, 'cellulare', 'Numero non valido'); ok = false; }
			if (!val(form, 'email')) { setError(form, 'email', 'Email obbligatoria'); ok = false; }
			else if (!EMAIL_RE.test(val(form, 'email'))) { setError(form, 'email', 'Email non valida'); ok = false; }
			if (!val(form, 'indirizzo')) { setError(form, 'indirizzo', 'Indirizzo obbligatorio'); ok = false; }
			if (!isChecked(form, 'consentPrivacy')) { setError(form, 'consentPrivacy', 'Devi accettare la Privacy Policy per continuare'); ok = false; }
			return ok;
		}
		function validateStep2() {
			var ok = true;
			if (!val(form, 'mq')) { setError(form, 'mq', 'Metri quadri obbligatori'); ok = false; }
			if (!val(form, 'bagni')) { setError(form, 'bagni', 'Numero di bagni obbligatorio'); ok = false; }
			if (!checked(form, 'arredata')) { setError(form, 'arredata', 'Indica se la casa è arredata'); ok = false; }
			return ok;
		}

		function fireStep(n) { track('preventivo_sgrosso_step', { step: n, step_name: stepNames[n - 1], landing: landing, cta_position: ctaPosition, tipoPulizia: tipoPulizia() || undefined }); }

		function submitStep1() {
			var p = Promise.resolve();
			if (!state.contattoId) {
				var body = Object.assign({
					tipo: 'b2c', nome: val(form, 'nome'), email: val(form, 'email'), telefono: telefono(), zona: val(form, 'indirizzo'),
					consentPrivacy: true, consentMarketing: isChecked(form, 'consentMarketing'), honeypot: val(form, 'website'),
					contactOnly: true, pageUrl: pageUrl()
				}, utm());
				p = call(form, 'POST', '/api/leads', body).then(function (r) {
					if (r && r.contattoId && r.token) { state.contattoId = r.contattoId; state.contattoToken = r.token; }
				});
			}
			return p.then(function () { fireStep(1); show(2); });
		}
		function submitStep2() {
			var p = Promise.resolve();
			if (state.contattoId && state.contattoToken && !state.opportunitaId) {
				var body = {
					token: state.contattoToken, tipo: 'b2c', zona: val(form, 'indirizzo'), note: note(false),
					superficieMq: val(form, 'mq') || undefined, datiPulizia: datiPulizia(2, false), gclid: gclid(), pageUrl: pageUrl()
				};
				p = call(form, 'POST', '/api/leads/' + encodeURIComponent(state.contattoId) + '/opportunita', body).then(function (r) {
					if (r && r.opportunitaId && r.token) { state.opportunitaId = r.opportunitaId; state.opportunitaToken = r.token; }
				});
			}
			return p.then(function () { fireStep(2); show(3); });
		}
		function submitStep3() {
			var p = Promise.resolve();
			if (state.opportunitaId && state.opportunitaToken) {
				var body = { token: state.opportunitaToken, step: 3, final: true, datiPulizia: datiPulizia(3, true), note: note(true) };
				if (state.photos.length) body.foto = state.photos;
				p = call(form, 'PATCH', '/api/leads/' + encodeURIComponent(state.opportunitaId), body);
			}
			return p.then(function () {
				track('preventivo_sgrosso_submitted', { landing: landing, cta_position: ctaPosition, tipoPulizia: tipoPulizia() });
				gotoThankYou({ zona: val(form, 'indirizzo'), tipoCasa: tipoPuliziaLabel(), email: val(form, 'email'), coupon: '-30€ sul primo lavoro' });
			});
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (form.classList.contains('is-busy')) return;
			clearErrors(form);
			var run;
			if (state.step === 1) { if (!validateStep1()) { focusFirstInvalid(form); return; } run = submitStep1; }
			else if (state.step === 2) { if (!validateStep2()) { focusFirstInvalid(form); return; } run = submitStep2; }
			else run = submitStep3;
			busy(form, true);
			run().catch(function (err) { showApiError(form, err.shown || MSG_GENERIC, err.status !== 400 && err.status !== 413); }).finally(function () { busy(form, false); });
		});
		if (backBtn) backBtn.addEventListener('click', function () { if (state.step > 1) show(state.step - 1); });
		show(1);
	}

	/* ---- Form atomico (calcolatore) -------------------------------------------------------------- */
	function initAtomic(form) {
		var landing = form.getAttribute('data-landing') || '';
		var formName = form.getAttribute('data-form') || 'calcolatore';
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (form.classList.contains('is-busy')) return;
			clearErrors(form);
			var ok = true;
			if (!val(form, 'email')) { setError(form, 'email', 'Email obbligatoria'); ok = false; }
			else if (!EMAIL_RE.test(val(form, 'email'))) { setError(form, 'email', 'Email non valida'); ok = false; }
			if (!isChecked(form, 'consentPrivacy')) { setError(form, 'consentPrivacy', 'Devi accettare la Privacy Policy per continuare'); ok = false; }
			if (!ok) { focusFirstInvalid(form); return; }
			var body = Object.assign({
				tipo: 'b2c', email: val(form, 'email'), zona: val(form, 'zona') || 'Non specificata (calcolatore)', note: val(form, 'note'),
				consentPrivacy: true, consentMarketing: false, honeypot: val(form, 'website'), pageUrl: pageUrl(), gclid: gclid()
			}, utm());
			busy(form, true);
			call(form, 'POST', '/api/leads', body).then(function () {
				track('marketing_form_submit', { landing: landing, form: formName });
				gotoThankYou({ email: val(form, 'email'), coupon: '-30€ sul primo lavoro' });
			}).catch(function (err) { showApiError(form, err.shown || MSG_GENERIC, err.status !== 400 && err.status !== 413); }).finally(function () { busy(form, false); });
		});
	}

	document.querySelectorAll('[data-lead-form]').forEach(initMultiStep);
	document.querySelectorAll('[data-lead-atomic]').forEach(initAtomic);
})();
