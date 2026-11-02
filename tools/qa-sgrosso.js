// QA del form lead sgrosso in headless con API simulata (TICKET-40/50, riscritto per il nuovo ordine da
// TICKET-83): node tools/qa-sgrosso.js <chrome-bin> <url> [larghezza=390] [cartella-screenshot]
// Intercetta via CDP (Fetch) ogni chiamata a */api/leads* e risponde come il backend (201 con id e token, 200 al
// PATCH): nessun lead reale, nessun CORS. Il comportamento dell'API simulata si cambia per scenario (`mode`):
// ok, fail1 (500 sul primo POST), bad400 (400 con messaggio), timeout (rete caduta), fail2 (500 sull'opportunita'),
// failpatch (500 sul PATCH), slow (risposta ritardata, per il doppio invio).
// Verifica il form in tre passi: 1 La casa, 2 Contatto (qui nasce il lead), 3 Dettagli facoltativo; la stima del
// passo 2 contro stima() del calcolatore (TICKET-84); il calcolatore atomico. Esce con 1 se un controllo fallisce.
// Con [cartella-screenshot] salva i tre passi del form hero e il calcolatore.
const { withChrome } = require('./lib/cdp');
const fs = require('fs');
const [BIN, BASE_URL, WIDTH_ARG, SHOTS] = process.argv.slice(2, 6);
const WIDTH = parseInt(WIDTH_ARG, 10) || 390;
const sep = BASE_URL.indexOf('?') === -1 ? '?' : '&';
const URL = BASE_URL + sep + 'utm_source=google&utm_medium=cpc&utm_campaign=sgrosso&gclid=TESTGCLID';

const calls = [];
const checks = [];
let mode = 'ok';
function check(name, ok, detail) { checks.push([name, ok]); console.log((ok ? 'OK  ' : 'KO  ') + name + (detail ? '  [' + String(detail).slice(0, 260) + ']' : '')); }
const real = () => calls.filter(c => c.method !== 'OPTIONS');
const contactCalls = () => real().filter(c => c.method === 'POST' && /\/api\/leads$/.test(c.url));
const oppCalls = () => real().filter(c => /\/opportunita$/.test(c.url));
const patchCalls = () => real().filter(c => c.method === 'PATCH');

withChrome(BIN, URL, { port: 9347, width: WIDTH, mobile: WIDTH < 800, height: WIDTH < 800 ? 844 : 980 }, async ({ send, ev, shot, sleep, on }) => {
	on('Fetch.requestPaused', async (p) => {
		const req = p.request;
		let body = {};
		try { body = req.postData ? JSON.parse(req.postData) : {}; } catch (e) {}
		calls.push({ method: req.method, url: req.url, body });
		const headers = [
			{ name: 'Content-Type', value: 'application/json' },
			{ name: 'Access-Control-Allow-Origin', value: '*' },
			{ name: 'Access-Control-Allow-Methods', value: 'GET,POST,PATCH,OPTIONS' },
			{ name: 'Access-Control-Allow-Headers', value: 'Content-Type' }
		];
		const reply = (status, json) => send('Fetch.fulfillRequest', { requestId: p.requestId, responseCode: status, responseHeaders: headers, body: Buffer.from(JSON.stringify(json)).toString('base64') });
		if (req.method === 'OPTIONS') return reply(204, {});
		const isOpp = /\/api\/leads\/[^/]+\/opportunita$/.test(req.url);
		const isPatch = req.method === 'PATCH';
		const isContact = /\/api\/leads$/.test(req.url) && !isOpp;
		if (mode === 'timeout' && isContact) return send('Fetch.failRequest', { requestId: p.requestId, errorReason: 'TimedOut' });
		if (mode === 'slow') await sleep(1500);
		if (mode === 'fail1' && isContact) return reply(500, { message: 'Errore interno' });
		if (mode === 'bad400' && isContact) return reply(400, { message: 'Numero di telefono non valido' });
		if (mode === 'fail2' && isOpp) return reply(500, { message: 'Errore interno' });
		if (mode === 'failpatch' && isPatch) return reply(500, { message: 'Errore interno' });
		if (isOpp) return reply(201, { opportunitaId: 'recOPP1', token: 'tok-opp' });
		if (isPatch) return reply(200, { ok: true });
		if (isContact) return reply(201, body.contactOnly ? { contattoId: 'recCONT1', token: 'tok-cont' } : { ok: true, opportunitaId: 'recATOM1' });
		return reply(200, {});
	});
	await send('Fetch.enable', { patterns: [{ urlPattern: '*/api/leads*', requestStage: 'Request' }] });
	// Copia ogni push del dataLayer in localStorage.__dl: gli eventi si leggono anche dopo la navigazione a /thank-you/.
	await send('Page.addScriptToEvaluateOnNewDocument', { source: "(function(){ var dl = window.dataLayer = window.dataLayer || []; var op = dl.push.bind(dl); dl.push = function(){ try { var a = JSON.parse(localStorage.getItem('__dl') || '[]'); Array.prototype.forEach.call(arguments, function(x){ a.push(x); }); localStorage.setItem('__dl', JSON.stringify(a)); } catch (e) {} return op.apply(null, arguments); }; })();" });

	async function load(m) {
		mode = m || 'ok';
		calls.length = 0;
		await send('Page.navigate', { url: URL });
		await sleep(2800);
		await ev("localStorage.removeItem('__dl')");
	}
	// set(form, name, valore) imposta un campo; click su radio/checkbox con checked.
	const F = "document.getElementById('lead-hero')";
	const setf = (n, v) => ev(`(function(){ var f=${F}; var e=f.querySelector('[name="${n}"]'); e.value=${JSON.stringify(v)}; e.dispatchEvent(new Event('input',{bubbles:true})); return e.value; })()`);
	const submit = () => ev(`(function(){ ${F}.requestSubmit(); return 'ok'; })()`);
	const stepN = () => ev(`(function(){ var s=${F}.querySelector('[data-step]:not([hidden])'); return s ? s.getAttribute('data-step') : 'nessuno'; })()`);
	const events = () => ev(`JSON.stringify(JSON.parse(localStorage.getItem('__dl')||'[]').filter(function(x){ return x && /^(preventivo_|marketing_form)/.test(x.event||''); }))`).then(JSON.parse);
	const err = (n) => ev(`${F}.querySelector('[data-error-for="${n}"]').textContent`);
	const focused = () => ev(`document.activeElement && document.activeElement.name || ''`);
	const apiErr = () => ev(`(function(){ var b=${F}.querySelector('.lead-form__api-error'); return JSON.stringify({ hidden: b.hidden, text: b.textContent, tel: !!b.querySelector('a[href^="tel:"]') }); })()`).then(JSON.parse);
	const clipOf = (sel) => ev(`(function(){ var r=document.querySelector(${JSON.stringify(sel)}).getBoundingClientRect(); return JSON.stringify({ x: r.left + scrollX, y: r.top + scrollY, width: r.width, height: r.height, scale: 1 }); })()`).then(JSON.parse);
	const fillStep1 = async (mq) => { await setf('mq', mq || '80'); await setf('indirizzo', 'Via Tuscolana 118, Roma'); };
	const fillStep2 = async (tel) => { await setf('nome', 'TEST QA'); await setf('cellulare', tel || '347 123 4568'); await ev(`${F}.querySelector('input[name=consentPrivacy]').checked = true`); };
	const shotForm = async (name) => { if (!SHOTS) return; fs.mkdirSync(SHOTS, { recursive: true }); await ev(`document.querySelector('#form').scrollIntoView()`); await sleep(500); await shot(SHOTS + '/' + name + '.png', await clipOf('#form .lead-form')); };

	// ---- Struttura del passo 1, hero e fondo pagina -----------------------------------------------------
	await load();
	for (const id of ['lead-hero', 'lead-bottom']) {
		const s = JSON.parse(await ev(`(function(){ var f=document.getElementById('${id}'); var st=f.querySelector('[data-step="1"]');
			var names=Array.prototype.map.call(st.querySelectorAll('input,select,textarea'),function(e){return e.name;}).filter(function(n,i,a){return a.indexOf(n)===i;});
			var checked=f.querySelector('input[name=tipoPulizia]:checked');
			return JSON.stringify({ names: names, hidden: st.hidden, n: f.querySelector('[data-step-n]').textContent, name: f.querySelector('[data-step-name]').textContent, btn: f.querySelector('[data-submit]').textContent.trim(), checked: checked ? checked.value : '', personal: ['nome','cellulare','email','consentPrivacy'].filter(function(n){ return st.querySelector('[name="'+n+'"]'); }) }); })()`));
		check(id + ': passo 1 "La casa" con tipo, mq, indirizzo e nessun dato personale', !s.hidden && s.names.slice().sort().join() === 'indirizzo,mq,tipoPulizia' && s.personal.length === 0 && s.n === '1' && s.name === 'La casa' && s.btn === 'Vedi la stima', JSON.stringify(s));
		if (process.env.EXPECT_TIPO) check(id + ': tipo preselezionato dalla variante', s.checked === process.env.EXPECT_TIPO, s.checked);
		else check(id + ': un tipo di intervento e preselezionato', !!s.checked, s.checked);
	}
	await shotForm('form-passo-1');

	// ---- Validazione del passo 1 ---------------------------------------------------------------------------
	await submit(); await sleep(400);
	check('passo 1 vuoto: nessuna chiamata, errori su mq e indirizzo, focus su mq', real().length === 0 && (await err('mq')) !== '' && (await err('indirizzo')) !== '' && (await focused()) === 'mq' && (await stepN()) === '1', JSON.stringify({ mq: await err('mq'), ind: await err('indirizzo'), foc: await focused() }));
	for (const bad of ['5', '501']) {
		await setf('mq', bad); await setf('indirizzo', 'Via Roma 1'); await submit(); await sleep(300);
		check('passo 1 con mq ' + bad + ': errore su mq, nessuna chiamata, dati intatti', real().length === 0 && (await err('mq')) !== '' && (await err('indirizzo')) === '' && (await ev(`${F}.querySelector('[name=indirizzo]').value`)) === 'Via Roma 1' && (await stepN()) === '1', await err('mq'));
	}
	await setf('mq', '80'); await setf('indirizzo', ''); await submit(); await sleep(300);
	check('passo 1 con indirizzo vuoto: errore e focus sull indirizzo', real().length === 0 && (await err('indirizzo')) !== '' && (await focused()) === 'indirizzo' && (await ev(`${F}.querySelector('[name=mq]').value`)) === '80', await focused());

	// ---- Passo 1 valido -> passo 2, nessuna chiamata -------------------------------------------------------
	await fillStep1('80'); await submit(); await sleep(600);
	let evs = await events();
	check('passo 1 valido: nessuna chiamata a /api/leads, passo 2 visibile', real().length === 0 && (await stepN()) === '2', real().length);
	check('evento preventivo_sgrosso_step {step:1, step_name:casa}', evs.length === 1 && evs[0].event === 'preventivo_sgrosso_step' && evs[0].step === 1 && evs[0].step_name === 'casa' && evs[0].landing === 'sgrosso-casa' && evs[0].cta_position === 'hero' && evs[0].tipoPulizia === 'post-ristrutturazione', JSON.stringify(evs));
	const sec2 = JSON.parse(await ev(`(function(){ var f=${F}; var st=f.querySelector('[data-step="2"]'); return JSON.stringify({ n: f.querySelector('[data-step-n]').textContent, name: f.querySelector('[data-step-name]').textContent, btn: f.querySelector('[data-submit]').textContent.trim(), emailReq: f.querySelector('[name=email]').required, back: !f.querySelector('[data-back]').hidden }); })()`));
	check('passo 2 "Contatto": bottone di invio, email non obbligatoria, Indietro presente', sec2.n === '2' && sec2.name === 'Contatto' && sec2.btn === 'Prenota il sopralluogo gratuito' && !sec2.emailReq && sec2.back, JSON.stringify(sec2));

	// @T84-begin
	// ---- TICKET-84: stima nel passo 2 ---------------------------------------------------------------------------
	const stimaOf = (mq) => ev(`(function(){ var root=document.querySelector('[data-estimator]'); var c={ base: parseFloat(root.getAttribute('data-base')), perMq: parseFloat(root.getAttribute('data-per-mq')), extraMin: parseFloat(root.getAttribute('data-extra-min')), extraMax: parseFloat(root.getAttribute('data-extra-max')), maxMult: parseFloat(root.getAttribute('data-max-mult')) }; var s=window.tpStimaSgrosso(${mq}, 0, c); return s.min + '-' + s.max; })()`);
	const line = () => ev(`(function(){ var e=${F}.querySelector('[data-estimate]'); var l=${F}.querySelector('[data-estimate-line]'); return JSON.stringify({ hidden: e ? e.hidden : 'assente', line: l ? l.textContent : '', note: e ? e.textContent : '' }); })()`).then(JSON.parse);
	let ln = await line();
	check('stima 80 mq: "Stima indicativa: 380-510 euro" e nota sul sopralluogo', !ln.hidden && ln.line === 'Stima indicativa: 380-510 euro' && /Il prezzo definitivo dopo il sopralluogo gratuito/.test(ln.note), JSON.stringify(ln));
	await shotForm('form-passo-2');
	for (const mq of ['30', '120', '250']) {
		await ev(`${F}.querySelector('[data-back]').click()`); await sleep(200);
		await setf('mq', mq); await submit(); await sleep(400);
		ln = await line();
		const exp = await stimaOf(mq);
		check('stima ' + mq + ' mq uguale a stima() del calcolatore (' + exp + '), ricalcolata al ritorno', ln.line === 'Stima indicativa: ' + exp + ' euro', ln.line);
	}
	// @T84-end
	await ev(`${F}.querySelector('[data-back]').click()`); await sleep(200);
	check('Indietro dal passo 2 riporta al passo 1 con i dati intatti', (await stepN()) === '1' && (await ev(`${F}.querySelector('[name=indirizzo]').value`)) === 'Via Tuscolana 118, Roma', await stepN());
	await setf('mq', '80'); await submit(); await sleep(400);

	// ---- Validazione del passo 2 -----------------------------------------------------------------------------------
	await submit(); await sleep(300);
	check('passo 2 vuoto: nessuna chiamata, errori su nome, cellulare, privacy, focus su nome', real().length === 0 && (await err('nome')) !== '' && (await err('cellulare')) !== '' && (await err('consentPrivacy')) !== '' && (await focused()) === 'nome', JSON.stringify([await err('nome'), await err('cellulare'), await err('consentPrivacy'), await focused()]));
	await setf('nome', 'TEST QA'); await setf('cellulare', '333 333 3333'); await submit(); await sleep(300);
	check('numero ripetuto bloccato lato client', real().length === 0 && (await err('cellulare')) === 'Numero non valido' && (await focused()) === 'cellulare', await err('cellulare'));
	await setf('cellulare', '347 123 4568'); await setf('email', 'non-una-email'); await ev(`${F}.querySelector('input[name=consentPrivacy]').checked = true`); await submit(); await sleep(300);
	check('email compilata ma non valida: errore, nessuna chiamata', real().length === 0 && (await err('email')) === 'Email non valida', await err('email'));
	await setf('email', ''); await ev(`${F}.querySelector('input[name=consentPrivacy]').checked = false`); await submit(); await sleep(300);
	check('privacy non accettata: errore e nessuna chiamata, dati intatti', real().length === 0 && (await err('consentPrivacy')) !== '' && (await ev(`${F}.querySelector('[name=nome]').value`)) === 'TEST QA', await err('consentPrivacy'));

	// ---- Passo 2 valido: nascono Contatto e Opportunita', scatta la conversione -----------------------------------
	await ev(`${F}.querySelector('input[name=consentPrivacy]').checked = true`); await submit(); await sleep(1500);
	const c1 = contactCalls()[0], c2 = oppCalls()[0];
	check('passo 2: POST /api/leads contactOnly con i dati del contatto', !!c1 && c1.body.contactOnly === true && c1.body.tipo === 'b2c' && c1.body.nome === 'TEST QA' && c1.body.telefono === '+393471234568' && c1.body.zona === 'Via Tuscolana 118, Roma' && c1.body.consentPrivacy === true && c1.body.consentMarketing === false && !('email' in c1.body) && c1.body.utmSource === 'google' && c1.body.utmMedium === 'cpc' && c1.body.utmCampaign === 'sgrosso' && typeof c1.body.pageUrl === 'string' && c1.body.pageUrl.indexOf('?') === -1, JSON.stringify(c1 && c1.body));
	const d2 = c2 && c2.body.datiPulizia;
	check('passo 2: POST opportunita con token, superficie, datiPulizia, gclid', !!c2 && /\/api\/leads\/recCONT1\/opportunita$/.test(c2.url) && c2.body.token === 'tok-cont' && c2.body.tipo === 'b2c' && c2.body.zona === 'Via Tuscolana 118, Roma' && c2.body.superficieMq === '80' && c2.body.note === 'Post-ristrutturazione · 80mq (form non completato)' && c2.body.gclid === 'TESTGCLID' && c2.body.pageUrl.indexOf('?') === -1 && d2 && d2.tipoPulizia === 'post-ristrutturazione' && d2._step === 2 && d2._completo === false && d2.mq === '80', JSON.stringify(c2 && c2.body));
	check('passo 2: una sola POST di contatto e una di opportunita, nessun PATCH', contactCalls().length === 1 && oppCalls().length === 1 && patchCalls().length === 0, contactCalls().length + '/' + oppCalls().length);
	evs = await events();
	const names = evs.map(e => e.event + (e.step ? ':' + e.step : ''));
	const sub = evs.find(e => e.event === 'preventivo_sgrosso_submitted');
	check('eventi: step 2 contatto e submitted {fase:contatto}, in questo ordine', names.join() === 'preventivo_sgrosso_step:1,preventivo_sgrosso_step:2,preventivo_sgrosso_submitted' && evs[1].step_name === 'contatto' && !!sub && sub.fase === 'contatto' && sub.landing === 'sgrosso-casa' && sub.cta_position === 'hero' && sub.tipoPulizia === 'post-ristrutturazione', JSON.stringify(names));
	const s3 = JSON.parse(await ev(`(function(){ var f=${F}; return JSON.stringify({ step: f.querySelector('[data-step]:not([hidden])').getAttribute('data-step'), title: f.querySelector('[data-step="3"] legend').textContent, btn: f.querySelector('[data-submit]').textContent.trim(), skip: !f.querySelector('[data-skip]').hidden, back: !f.querySelector('[data-back]').hidden, path: location.pathname }); })()`));
	check('passo 3 "Richiesta ricevuta" con Invia i dettagli e Salta, senza Indietro, ancora sulla landing', s3.step === '3' && s3.title === 'Richiesta ricevuta' && s3.btn === 'Invia i dettagli' && s3.skip && !s3.back && s3.path === '/roma/sgrosso-casa/', JSON.stringify(s3));
	await shotForm('form-passo-3');

	// ---- Passo 3: dettagli e foto -> PATCH finale ---------------------------------------------------------------------
	await setf('bagni', '2');
	await ev(`(function(){ var f=${F}; f.querySelector('input[name=tipoAppartamento][value=Trilocale]').checked=true; f.querySelector('input[name=pavimento][value=Parquet]').checked=true; var r=f.querySelector('input[name=livelloSporcoIdx]'); r.value=3; r.dispatchEvent(new Event('input',{bubbles:true})); return 'ok'; })()`);
	await ev(`(async function(){ var c=document.createElement('canvas'); c.width=8; c.height=8; var blob=await new Promise(function(r){c.toBlob(r,'image/png');}); var dt=new DataTransfer(); dt.items.add(new File([blob],'prova.png',{type:'image/png'})); var i=${F}.querySelector('input[name=foto]'); i.files=dt.files; i.dispatchEvent(new Event('change',{bubbles:true})); return 'ok'; })()`);
	await sleep(1200);
	await submit(); await sleep(2000);
	const c3 = patchCalls()[0], d3 = c3 && c3.body.datiPulizia;
	check('passo 3: PATCH finale con token, datiPulizia, foto, note', !!c3 && /\/api\/leads\/recOPP1$/.test(c3.url) && c3.body.token === 'tok-opp' && c3.body.step === 3 && c3.body.final === true && c3.body.note === 'Post-ristrutturazione · Trilocale · 80mq' && d3 && d3.bagni === '2' && d3.tipoAppartamento === 'Trilocale' && d3.pavimento === 'Parquet' && d3.livello_sporco === 'pesante' && d3.mq === '80' && d3._step === 3 && d3._completo === true && Array.isArray(c3.body.foto) && c3.body.foto.length === 1 && c3.body.foto[0].filename === 'prova.png', JSON.stringify(c3 && Object.assign({}, c3.body, { foto: c3.body.foto && c3.body.foto.map(f => f.filename) })));
	const nav = JSON.parse(await ev(`JSON.stringify({ path: location.pathname, ty: sessionStorage.getItem('tp_thankyou') })`));
	check('passo 3 inviato: navigazione a /thank-you/ con riepilogo', nav.path === '/thank-you/' && !!nav.ty && JSON.parse(nav.ty).tipoCasa === 'Post-ristrutturazione', JSON.stringify(nav));

	// ---- "Salta" -------------------------------------------------------------------------------------------------------------
	await load();
	await fillStep1('80'); await submit(); await sleep(400); await fillStep2(); await submit(); await sleep(1500);
	const before = real().length;
	await ev(`${F}.querySelector('[data-skip]').click()`); await sleep(800);
	check('Salta: nessuna chiamata, navigazione a /thank-you/', real().length === before && patchCalls().length === 0 && (await ev('location.pathname')) === '/thank-you/', real().length + '/' + before);

	// ---- PATCH finale: evento marketing_form_submit {form:dettagli} -------------------------------------------------------------
	await load();
	await fillStep1('80'); await submit(); await sleep(400); await fillStep2(); await submit(); await sleep(1500);
	await submit(); await sleep(1500);
	const fe = (await events()).filter(e => e.event === 'marketing_form_submit');
	check('evento marketing_form_submit {landing, form:dettagli}', fe.length === 1 && fe[0].landing === 'sgrosso-casa' && fe[0].form === 'dettagli', JSON.stringify(fe));

	// ---- Errori del primo POST ---------------------------------------------------------------------------------------------------
	await load('fail1');
	await fillStep1('80'); await submit(); await sleep(400); await fillStep2(); await submit(); await sleep(1200);
	let ae = await apiErr();
	evs = await events();
	check('primo POST in errore: messaggio generico col link al telefono, resto al passo 2, submitted non scatta', !ae.hidden && /Invio non riuscito/.test(ae.text) && ae.tel && (await stepN()) === '2' && !evs.some(e => e.event === 'preventivo_sgrosso_submitted') && oppCalls().length === 0 && (await ev(`${F}.querySelector('[name=nome]').value`)) === 'TEST QA' && !(await ev(`${F}.querySelector('[data-submit]').disabled`)), JSON.stringify(ae));
	mode = 'ok'; await submit(); await sleep(1500);
	check('dopo l errore, un nuovo invio riesce e porta al passo 3', (await stepN()) === '3' && contactCalls().length === 2 && oppCalls().length === 1, contactCalls().length + '/' + oppCalls().length);

	await load('bad400');
	await fillStep1('80'); await submit(); await sleep(400); await fillStep2(); await submit(); await sleep(1200);
	ae = await apiErr();
	check('primo POST 400: messaggio del server senza link, resto al passo 2', !ae.hidden && ae.text === 'Numero di telefono non valido' && !ae.tel && (await stepN()) === '2', JSON.stringify(ae));

	await load('timeout');
	await fillStep1('80'); await submit(); await sleep(400); await fillStep2(); await submit(); await sleep(1500);
	ae = await apiErr();
	evs = await events();
	check('rete caduta sul primo POST: messaggio generico col telefono, resto al passo 2, submitted non scatta', !ae.hidden && /Invio non riuscito/.test(ae.text) && ae.tel && (await stepN()) === '2' && !evs.some(e => e.event === 'preventivo_sgrosso_submitted'), JSON.stringify(ae));

	// ---- Primo POST ok, opportunita' in errore ----------------------------------------------------------------------------------
	await load('fail2');
	await fillStep1('80'); await submit(); await sleep(400); await fillStep2(); await submit(); await sleep(1500);
	evs = await events();
	const sub2 = evs.find(e => e.event === 'preventivo_sgrosso_submitted');
	check('opportunita in errore: submitted {fase:contatto} scatta comunque, passo 3 visibile', !!sub2 && sub2.fase === 'contatto' && (await stepN()) === '3' && contactCalls().length === 1 && oppCalls().length === 1, JSON.stringify(evs.map(e => e.event)));
	const prima = real().length;
	await submit(); await sleep(1200);
	check('opportunita assente: Invia i dettagli porta a /thank-you/ senza PATCH', patchCalls().length === 0 && real().length === prima && (await ev('location.pathname')) === '/thank-you/', patchCalls().length);

	// ---- PATCH in errore ---------------------------------------------------------------------------------------------------------------
	await load('failpatch');
	await fillStep1('80'); await submit(); await sleep(400); await fillStep2(); await submit(); await sleep(1500);
	await submit(); await sleep(1200);
	ae = await apiErr();
	check('PATCH in errore: messaggio, resto al passo 3, Salta disponibile', !ae.hidden && /Invio non riuscito/.test(ae.text) && (await stepN()) === '3' && (await ev(`!${F}.querySelector('[data-skip]').hidden`)) && (await ev('location.pathname')) === '/roma/sgrosso-casa/', JSON.stringify(ae));
	await ev(`${F}.querySelector('[data-skip]').click()`); await sleep(600);
	check('dopo il PATCH in errore, Salta porta a /thank-you/', (await ev('location.pathname')) === '/thank-you/', await ev('location.pathname'));

	// ---- Doppio invio -------------------------------------------------------------------------------------------------------------------------
	await load('slow');
	await fillStep1('80'); await submit(); await sleep(400); await fillStep2(); await submit(); await sleep(150);
	const dis = await ev(`${F}.querySelector('[data-submit]').disabled`);
	await submit(); await ev(`${F}.querySelector('[data-submit]').click()`); await sleep(300);
	check('passo 2 in corso: bottone disabilitato e nessuna seconda chiamata', dis === true && contactCalls().length === 1, 'disabled=' + dis + ' contatti=' + contactCalls().length);
	await sleep(4000);
	check('dopo la risposta lenta: un solo contatto e una sola opportunita', contactCalls().length === 1 && oppCalls().length === 1 && (await stepN()) === '3', contactCalls().length + '/' + oppCalls().length);
	const dis3 = (async () => { await submit(); await sleep(150); return ev(`${F}.querySelector('[data-submit]').disabled`); })();
	await submit(); await sleep(150);
	check('passo 3 in corso: bottone disabilitato e un solo PATCH', (await dis3) === true && patchCalls().length === 1, 'patch=' + patchCalls().length);

	// ---- Calcolatore (form atomico, invariato) ---------------------------------------------------------------------------------------------
	await load();
	await ev(`(function(){ var f=document.querySelector('[data-lead-atomic]'); var e=f.querySelector('[name=email]'); e.value='qa-calc@example.com'; e.dispatchEvent(new Event('input',{bubbles:true})); f.querySelector('input[name=consentPrivacy]').checked=true; f.requestSubmit(); return 'ok'; })()`);
	await sleep(1500);
	const ca = real().find(c => c.method === 'POST');
	check('calcolatore: POST atomico', !!ca && ca.body.tipo === 'b2c' && ca.body.email === 'qa-calc@example.com' && ca.body.zona === 'Non specificata (calcolatore)' && /^Calcolatore: \d+mq · .+ · extra: nessuno · stima \d+-\d+€$/.test(ca.body.note) && ca.body.consentPrivacy === true && !ca.body.contactOnly, JSON.stringify(ca && ca.body));
	check('calcolatore: navigazione a /thank-you/', (await ev('location.pathname')) === '/thank-you/', await ev('location.pathname'));

	// @T84-begin
	// ---- Formula non disponibile: la riga della stima non compare, il passo 2 funziona -----------------------------------------------------
	await send('Page.addScriptToEvaluateOnNewDocument', { source: "Object.defineProperty(window,'tpSgrossoStima',{get:function(){return undefined;},set:function(){},configurable:true}); Object.defineProperty(window,'tpStimaSgrosso',{get:function(){return undefined;},set:function(){},configurable:true});" });
	await load();
	await fillStep1('80'); await submit(); await sleep(500);
	ln = await line();
	check('formula assente: la riga della stima non compare e il passo 2 e usabile', (await stepN()) === '2' && (ln.hidden === true || ln.line === ''), JSON.stringify(ln));
	await fillStep2(); await submit(); await sleep(1500);
	check('formula assente: l invio del passo 2 funziona comunque', (await stepN()) === '3' && contactCalls().length === 1, contactCalls().length);

	// @T84-end

	const ko = checks.filter(c => !c[1]).length;
	console.log(ko ? 'ESITO: ' + ko + ' controlli falliti su ' + checks.length : 'ESITO: verde (' + checks.length + ' controlli)');
	process.exitCode = ko ? 1 : 0;
}).catch(e => { console.error('ERR', e); process.exit(1); });
