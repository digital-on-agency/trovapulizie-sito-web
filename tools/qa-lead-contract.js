// Test di contratto del form lead (ADR-004, TICKET-40): node tools/qa-lead-contract.js [apiBase] [--real email]
// Esercita le rotte /api/leads dal terminale (nessun CORS: non c'e' un'origine) per verificare che i nomi dei
// campi e le risposte siano quelli attesi dal tema (assets/js/lead-form.js):
//   - validazioni 400 con i messaggi del backend (tipo, zona, consenso privacy)
//   - honeypot compilato -> 201 {ok:true, skipped:'honeypot'} senza record
// Con --real <email> fa anche il giro completo dei tre passi con un lead marcato "TEST QA": crea record veri su
// Airtable, da archiviare subito dopo (base appXiH9eh4Ko9H108). L'email deve ricevere posta: il backend fa il
// lookup MX. Default apiBase: https://staging.api.trovapulizie.com. Esce con 1 se un controllo fallisce.
const args = process.argv.slice(2);
const API = (args.find(a => a.startsWith('http')) || 'https://staging.api.trovapulizie.com').replace(/\/+$/, '');
const realIdx = args.indexOf('--real');
const REAL_EMAIL = realIdx !== -1 ? args[realIdx + 1] : '';
const PAGE = 'https://www.trovapulizie.it/roma/sgrosso-casa/';
const PAGE_B2B = 'https://www.trovapulizie.it/roma/sgrosso-commerciale/';

async function call(method, path, body) {
	const res = await fetch(API + path, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
	let json = {};
	try { json = await res.json(); } catch (e) {}
	return { status: res.status, json };
}

const checks = [];
function check(name, ok, detail) { checks.push([name, ok, detail]); console.log((ok ? 'OK  ' : 'KO  ') + name + '  [' + detail + ']'); }

(async () => {
	console.log('API', API);
	let r = await call('POST', '/api/leads', {});
	check('400 senza tipo', r.status === 400 && /tipo/i.test(r.json.message || ''), r.status + ' ' + r.json.message);

	r = await call('POST', '/api/leads', { tipo: 'b2c', email: 'qa@example.com', consentPrivacy: true, contactOnly: true, pageUrl: PAGE });
	check('400 senza zona', r.status === 400 && /zona/i.test(r.json.message || ''), r.status + ' ' + r.json.message);

	r = await call('POST', '/api/leads', { tipo: 'b2c', email: 'qa@example.com', zona: 'Roma', consentPrivacy: false, contactOnly: true, pageUrl: PAGE });
	check('400 senza consenso privacy', r.status === 400 && /privacy/i.test(r.json.message || ''), r.status + ' ' + r.json.message);

	r = await call('POST', '/api/leads', { tipo: 'b2c', nome: 'TEST QA honeypot', email: 'qa-honeypot@example.com', telefono: '+393331234567', zona: 'Via di prova 1, Roma', consentPrivacy: true, consentMarketing: false, honeypot: 'bot', contactOnly: true, pageUrl: PAGE });
	check('honeypot -> 201 skipped senza record', r.status === 201 && r.json.ok === true && r.json.skipped === 'honeypot' && !r.json.contattoId, r.status + ' ' + JSON.stringify(r.json));

	r = await call('POST', '/api/leads', { tipo: 'b2c', email: 'qa@example.com', zona: 'Non specificata (calcolatore)', note: 'Calcolatore: 100mq · Post-ristrutturazione · extra: nessuno · stima 420-570€', consentPrivacy: true, consentMarketing: false, honeypot: 'bot', pageUrl: PAGE });
	check('calcolatore atomico con honeypot -> 201 skipped', r.status === 201 && r.json.skipped === 'honeypot', r.status + ' ' + JSON.stringify(r.json));

	// Landing B2B (SPEC-landing-sgrosso-commerciale §2): una sola POST tipo b2b, note = tipo di attivita'.
	r = await call('POST', '/api/leads', { tipo: 'b2b', telefono: '+390691516075', zona: 'Prati', note: 'Palestra', superficieMq: '200', consentPrivacy: true, consentMarketing: false, honeypot: 'bot', pageUrl: PAGE_B2B });
	check('b2b con honeypot -> 201 skipped senza record', r.status === 201 && r.json.ok === true && r.json.skipped === 'honeypot', r.status + ' ' + JSON.stringify(r.json));

	if (REAL_EMAIL) {
		console.log('--- giro reale (crea record su Airtable, da archiviare)');
		const s1 = await call('POST', '/api/leads', { tipo: 'b2c', nome: 'TEST QA', email: REAL_EMAIL, telefono: '+393471234568', zona: 'Via Tuscolana 118, Roma', consentPrivacy: true, consentMarketing: false, honeypot: '', contactOnly: true, pageUrl: PAGE, utmSource: 'qa', utmMedium: 'contract', utmCampaign: 'sgrosso' });
		check('passo 1 -> 201 contattoId+token', s1.status === 201 && !!s1.json.contattoId && !!s1.json.token, s1.status + ' ' + JSON.stringify(s1.json));
		if (s1.json.contattoId && s1.json.token) {
			const s2 = await call('POST', '/api/leads/' + encodeURIComponent(s1.json.contattoId) + '/opportunita', { token: s1.json.token, tipo: 'b2c', zona: 'Via Tuscolana 118, Roma', note: 'Trasloco · Trilocale · 85mq (form non completato)', superficieMq: '85', datiPulizia: { tipoPulizia: 'trasloco', tipoAppartamento: 'Trilocale', mq: '85', indirizzo: 'Via Tuscolana 118, Roma', pavimento: 'Parquet', bagni: '2', arredata: 'In parte', livello_sporco: 'pesante', _step: 2, _completo: false }, pageUrl: PAGE });
			check('passo 2 -> 201 opportunitaId+token', s2.status === 201 && !!s2.json.opportunitaId && !!s2.json.token, s2.status + ' ' + JSON.stringify(s2.json));
			if (s2.json.opportunitaId && s2.json.token) {
				const s3 = await call('PATCH', '/api/leads/' + encodeURIComponent(s2.json.opportunitaId), { token: s2.json.token, step: 3, final: true, datiPulizia: { tipoPulizia: 'trasloco', tipoAppartamento: 'Trilocale', mq: '85', indirizzo: 'Via Tuscolana 118, Roma', pavimento: 'Parquet', bagni: '2', arredata: 'In parte', livello_sporco: 'pesante', urgenza: 'Entro 48h', _step: 3, _completo: true }, note: 'Trasloco · Trilocale · 85mq' });
				check('passo 3 -> 200', s3.status === 200, s3.status + ' ' + JSON.stringify(s3.json));
				console.log('DA ARCHIVIARE su Airtable: contatto ' + s1.json.contattoId + ', opportunita ' + s2.json.opportunitaId);
			}
		}
	} else {
		console.log('(giro reale saltato: passare --real <email> per eseguirlo)');
	}

	const ko = checks.filter(c => !c[1]).length;
	console.log(ko ? 'ESITO: ' + ko + ' controlli falliti' : 'ESITO: verde');
	process.exitCode = ko ? 1 : 0;
})().catch(e => { console.error('ERR', e); process.exit(1); });
