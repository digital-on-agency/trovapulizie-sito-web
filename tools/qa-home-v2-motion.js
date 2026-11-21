/* QA del movimento della Home v2 (SPEC-homepage-v2, TICKET-129, note Figma 7882:4118).
   Uso: node tools/qa-home-v2-motion.js <chrome> <url-anteprima> [cartella-screenshot] [--modi 1440,390,1440r,390r,1440nojs,390nojs,1440norev]
   <url-anteprima> e' la pagina d'anteprima (es. http://127.0.0.1:9431/anteprima-pagina-iniziale/). Serve Chromium (CDP, Node 22).

   Modi (default: tutti):
     1440, 390          movimento consentito (GSAP solo a 1440);
     1440r, 390r        "riduci movimento" emulato (prefers-reduced-motion: reduce);
     1440nojs, 390nojs  JavaScript disattivato;
     1440norev          reveal.js bloccato (Network.setBlockedURLs): niente classe tp-reveal, niente is-in;
     1440foot, 390foot, 1440footoff  con --footer-url=<pagina qualunque>: footer nuovo (interruttore acceso) o vecchio (spento).

   Controlli (a 1440 con movimento: gli effetti legati allo scroll; negli altri modi: la loro assenza):
   - GSAP e ScrollTrigger richiesti a cdnjs una volta sola a 1440; mai a 390, mai con riduci movimento;
   - parallasse della hero, ken burns della foto aziende e scritta del footer: la trasformazione cambia con lo scroll
     a 1440 (foto: scala > 1; scritta: fuori posto con il footer appena entrato, a posto in fondo alla pagina);
     a 390 e con riduci movimento nessuna trasformazione (none);
   - loop (testo circolare della hero, testo del timbro, raggio del radar, fascia frasi) in pausa fuori viewport e in
     marcia dentro; con riduci movimento nessuna animazione infinita attiva (document.getAnimations());
   - timbro del manifesto: nascosto (opacita' 0) prima di entrare, visibile dopo, con la proprieta' scale (rimbalzo) solo
     con movimento consentito;
   - pillole delle prove, icone dei servizi (stagger 40 ms), avviso e tentacolo della chiusura, foglietti: nascosti
     prima, visibili dopo; senza JavaScript o con reveal.js bloccato sempre visibili (opacita' calcolata 1);
   - FAQ: l'apertura ha una transizione di grid-template-rows (unica proprieta' di layout, richiesta dall'altezza
     della risposta) di 450 ms a 1440 e 360 ms a 390;
   - durate: ingressi 450 ms a 1440, 360 ms a 390 (x0,8), 200 ms con riduci movimento;
   - proprieta' animate (CSSTransition e CSSAnimation raccolte durante una scorsa completa): solo opacity, transform,
     translate, scale, rotate (e grid-template-rows della FAQ, che non parte da sola);
   - CLS durante la scorsa completa < 0,05; long task (>50 ms) contati e riportati (informativo: in headless senza GPU
     non sono rappresentativi).
   Esce con 1 se un controllo fallisce. */
const fs = require('fs');
const path = require('path');
const { withChrome, sleep } = require('./lib/cdp.js');

const [chrome, url, dirArg] = process.argv.slice(2).filter(a => !a.startsWith('--'));
const modiArg = (process.argv.find(a => a.startsWith('--modi=')) || '').slice(7) || (process.argv.includes('--modi') ? process.argv[process.argv.indexOf('--modi') + 1] : '');
const MODI = (modiArg || '1440,390,1440r,390r,1440nojs,390nojs,1440norev').split(',');
if (!chrome || !url) { console.log('Uso: node tools/qa-home-v2-motion.js <chrome> <url> [cartella] [--modi ...]'); process.exit(2); }

let ko = 0;
const ok = (c, msg) => { console.log((c ? 'OK  ' : 'KO  ') + msg); if (!c) ko++; };
const info = msg => console.log('--  ' + msg);

const OBS = `window.__m={cls:0,long:[]};
new PerformanceObserver(l=>{for(const e of l.getEntries()){if(!e.hadRecentInput)window.__m.cls+=e.value}}).observe({type:'layout-shift',buffered:true});
new PerformanceObserver(l=>{for(const e of l.getEntries())window.__m.long.push([Math.round(e.startTime),Math.round(e.duration)])}).observe({type:'longtask',buffered:true});`;

// Modi "foot" e "footoff": una pagina qualunque del sito (--footer-url=<url>) con l'interruttore tp_site_v2 acceso ("foot": il
// footer nuovo c'e', lo script del movimento e' caricato e la scritta segue lo scroll da 1024 px) o spento ("footoff": il
// footer vecchio, nessuno script del movimento, nessuna richiesta a cdnjs).
async function modoFooter(m, idx, pageUrl) {
	const width = parseInt(m, 10);
	const mobile = width < 1024;
	const off = /footoff$/.test(m);
	console.log('\n== modo ' + m + ' ' + pageUrl);
	await withChrome(chrome, 'about:blank', { port: 9340 + idx, width, height: mobile ? 844 : 900, mobile }, async ({ send, ev, on }) => {
		const reqs = [];
		await send('Network.enable');
		on('Network.requestWillBeSent', p => reqs.push(p.request.url));
		await send('Network.setCacheDisabled', { cacheDisabled: true });
		await send('Page.navigate', { url: pageUrl });
		await sleep(5500);
		await ev("document.documentElement.style.scrollBehavior='auto';0");
		const motion = reqs.filter(u => /assets\/js\/home-v2-motion\.js/.test(u)).length;
		const gsapReq = reqs.filter(u => /cdnjs\.cloudflare\.com\/ajax\/libs\/gsap/.test(u)).length;
		const nf = await ev("document.querySelectorAll('footer.site-footer-v2').length+'/'+document.querySelectorAll('footer.site-footer').length");
		if (off) {
			ok(motion === 0 && gsapReq === 0, 'interruttore spento: nessun home-v2-motion.js (' + motion + ') e nessuna richiesta a cdnjs (' + gsapReq + ')');
			ok(nf === '0/1', 'interruttore spento: footer vecchio (v2/vecchio ' + nf + ')');
			return;
		}
		ok(nf === '1/0', 'interruttore acceso: footer nuovo (v2/vecchio ' + nf + ')');
		ok(motion === 1, 'home-v2-motion.js caricato una volta (' + motion + ')');
		ok(mobile ? gsapReq === 0 : gsapReq === 2, 'GSAP ' + (mobile ? 'non richiesto sotto 1024 px' : 'richiesto una volta') + ' (' + gsapReq + ' richieste)');
		const get = () => ev("var t=getComputedStyle(document.querySelector('.site-footer-v2__word-text')).transform;t==='none'?0:parseFloat(t.replace('matrix(','').split(',')[5])");
		const H = await ev('document.documentElement.scrollHeight');
		await ev("var f=document.querySelector('.site-footer-v2');window.scrollTo(0,f.getBoundingClientRect().top+window.scrollY-window.innerHeight+120);0");
		let a = 0; for (let i = 0; i < 20; i++) { a = await get(); if (mobile || a > 8) break; await sleep(250); }
		await ev('window.scrollTo(0,' + H + ');0');
		let b = 99; for (let i = 0; i < 20; i++) { b = await get(); if (mobile || Math.abs(b) < 1) break; await sleep(250); }
		if (mobile) ok(a === 0 && b === 0, 'scritta del footer ferma sotto 1024 px (' + a + ', ' + b + ')');
		else ok(a > 8 && Math.abs(b) < 1, 'scritta del footer segue lo scroll anche fuori dall\'anteprima: ' + a.toFixed(1) + ' px -> ' + b.toFixed(1) + ' px');
	});
}

async function modo(m, idx) {
	const width = parseInt(m, 10);
	const mobile = width < 1024;
	const reduce = /r$/.test(m);
	const nojs = /nojs$/.test(m);
	const norev = /norev$/.test(m);
	const desk = !mobile && !reduce;
	console.log('\n== modo ' + m + (mobile ? ' (mobile)' : '') + (reduce ? ' riduci movimento' : '') + (nojs ? ' senza JavaScript' : '') + (norev ? ' reveal.js bloccato' : ''));
	await withChrome(chrome, 'about:blank', { port: 9340 + idx, width, height: mobile ? 844 : 900, mobile }, async ({ send, ev, shot, on }) => {
		const reqs = [];
		await send('Network.enable');
		on('Network.requestWillBeSent', p => reqs.push(p.request.url));
		await send('Network.setCacheDisabled', { cacheDisabled: true });
		if (reduce) await send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
		if (norev) await send('Network.setBlockedURLs', { urls: ['*assets/js/reveal.js*'] });
		if (nojs) await send('Network.setBlockedURLs', { urls: ['*.js', '*.js?*'] }); // equivale a JavaScript spento: nessuno script parte, la valutazione CDP resta possibile
		await send('Page.addScriptToEvaluateOnNewDocument', { source: OBS });
		await send('Page.navigate', { url });
		await sleep(5500);
		await ev("document.documentElement.style.scrollBehavior='auto';0");

		const js = async expr => { const v = await ev('JSON.stringify((function(){' + expr + '})())'); try { return JSON.parse(v); } catch (e) { return v; } };
		const cs = (sel, prop) => js(`var e=document.querySelector(${JSON.stringify(sel)});return e?getComputedStyle(e)[${JSON.stringify(prop)}]:null`);
		const view = sel => js(`var e=document.querySelector(${JSON.stringify(sel)});if(!e)return null;e.scrollIntoView({block:'center'});return 1`);
		const scrollY = y => ev('window.scrollTo(0,' + y + ');0');
		const sc = (n, x) => ev(`window.scrollTo(0,${x});0`);
		const poll = async (get, pred) => { let v; for (let i = 0; i < 20; i++) { v = await get(); if (pred(v)) return v; await sleep(250); } return v; };

		const has = await js("return {stamp:!!document.querySelector('.hv2-man__stamp'),svc:document.querySelectorAll('.hv2-services__tile').length,cta:!!document.querySelector('.hv2-cta__notif'),faq:!!document.querySelector('.faq--v2 .faq__a'),photo:!!document.querySelector('.hv2-b2b__photo img'),word:!!document.querySelector('.site-footer-v2__word-text')}");
		ok(has.stamp && has.svc >= 4 && has.cta && has.faq && has.photo && has.word, 'markup presente: timbro, ' + has.svc + ' icone, avviso, FAQ, foto aziende, scritta footer');

		const gsapReq = reqs.filter(u => /cdnjs\.cloudflare\.com\/ajax\/libs\/gsap/.test(u));
		if (desk && !nojs) {
			// GSAP parte al load o al primo scroll: lo scroll di questa funzione e' dopo, quindi qui deve essere gia' partito
			ok(gsapReq.length === 2, 'GSAP richiesto una volta (gsap.min.js e ScrollTrigger.min.js): ' + gsapReq.length + ' richieste');
		} else {
			ok(gsapReq.length === 0, 'nessuna richiesta a cdnjs (GSAP): ' + gsapReq.length);
		}

		// ---- stato prima dell'ingresso (tutto sotto la piega) ----
		await scrollY(0); await sleep(300);
		const hidden = sel => cs(sel, 'opacity');
		const readPre = async () => ({
			stamp: await hidden('.hv2-man__stamp'),
			proof: await hidden('.hv2-man__item .hv2-man__proof'),
			tile: await hidden('.hv2-services__tile'),
			notif: await hidden('.hv2-cta__notif'),
			faqa: await hidden('.faq--v2 .faq__item')
		});
		// la dissolvenza verso lo stato nascosto parte quando reveal.js mette tp-reveal: si aspetta che finisca
		const pre = await poll(readPre, v => (nojs || norev) ? Object.values(v).every(x => x === '1') : (v.stamp === '0' && v.proof === '0' && v.tile === '0' && v.notif === '0'));
		if (nojs || norev) {
			ok(Object.values(pre).every(v => v === '1'), 'senza ' + (nojs ? 'JavaScript' : 'reveal.js') + ' timbro, pillole, icone, avviso e FAQ sono visibili (opacita\' ' + JSON.stringify(pre) + ')');
		} else {
			ok(pre.stamp === '0' && pre.proof === '0' && pre.tile === '0' && pre.notif === '0', 'prima dell\'ingresso timbro, pillole, icone e avviso sono nascosti (' + JSON.stringify(pre) + ')');
		}
		const orbitOutIdx = await js("var e=document.querySelector('.hv2-hero__orbit');return e?1:0");
		void orbitOutIdx;

		// ---- durate ----
		const dur = String(await cs('.hv2 [data-reveal]', 'transitionDuration')).split(',')[0].trim();
		if (!nojs && !norev) {
			const want = reduce ? '0.2s' : mobile ? '0.36s' : '0.45s';
			ok(dur === want, 'durata degli ingressi ' + want + ' (letta: ' + dur + ')');
		}
		const faqDur = await cs('.faq--v2 .faq__a', 'transitionDuration');
		const faqProp = await cs('.faq--v2 .faq__a', 'transitionProperty');
		if (!reduce) {
			const want = mobile ? '0.36s' : '0.45s';
			ok(/grid-template-rows/.test(faqProp) && faqDur.split(',')[0].trim() === want, 'FAQ: apertura morbida su grid-template-rows ' + want + ' (' + faqProp + ' / ' + faqDur + ')');
		}

		// ---- scorsa completa: proprieta' animate, CLS, long task ----
		await js("window.__props={};return 1");
		const H = await js('return document.documentElement.scrollHeight');
		const vh = mobile ? 844 : 900;
		const lt0 = (await js('return window.__m.long.length'));
		for (let y = 0; y <= H; y += Math.round(vh * 0.6)) {
			await scrollY(y); await sleep(260);
			await js(`document.getAnimations().forEach(function(a){var e=a.effect,p=[];if(e&&e.target&&e.target.closest&&e.target.closest('header'))return;if(a.transitionProperty){p=[a.transitionProperty]}else if(e&&e.getKeyframes){e.getKeyframes().forEach(function(k){Object.keys(k).forEach(function(n){if(['offset','easing','composite','computedOffset'].indexOf(n)<0)p.push(n)})})}p.forEach(function(n){window.__props[n]=(window.__props[n]||0)+1})});return 1`);
		}
		await sleep(800);
		const props = await js('return window.__props');
		const okProps = ['opacity', 'transform', 'translate', 'scale', 'rotate', 'grid-template-rows'];
		const bad = Object.keys(props).filter(p => okProps.indexOf(p) < 0);
		ok(bad.length === 0, 'proprieta\' animate durante la scorsa: ' + Object.keys(props).join(', ') + (bad.length ? ' (NON ammesse: ' + bad.join(', ') + ')' : ''));
		const cls = await js('return window.__m.cls');
		ok(cls < 0.05, 'CLS durante caricamento e scorsa completa ' + cls.toFixed(4) + ' (< 0,05)');
		const longs = await js('return window.__m.long');
		info('long task > 50 ms: ' + longs.length + ' (di cui dopo il load, durante la scorsa: ' + Math.max(0, longs.length - lt0) + '), massimo ' + Math.max(0, ...longs.map(l => l[1])) + ' ms');

		if (nojs) return;

		// ---- effetti legati allo scroll ----
		// In headless senza GPU gli aggiornamenti legati allo scroll (rAF, IntersectionObserver) possono tardare: si
		// rilegge lo stato fino a quando soddisfa l'atteso, al massimo 5 s.
		const mat = t => (t && t !== 'none') ? t.replace(/^matrix\(/, '').replace(/\)$/, '').split(',').map(Number) : [1, 0, 0, 1, 0, 0];
		await scrollY(0);
		const stage0 = await poll(() => cs('.hv2-hero__stage', 'transform'), v => mat(v)[5] === 0 || v === 'none');
		await scrollY(vh * 0.6);
		const stage1 = await poll(() => cs('.hv2-hero__stage', 'transform'), v => v !== stage0);
		if (desk) ok(stage0 !== stage1 && stage1 !== 'none', 'parallasse hero: ' + stage0 + ' -> ' + stage1);
		else ok(stage1 === 'none' || stage1 === stage0, 'nessuna parallasse hero (' + stage1 + ')');

		await view('.hv2-b2b__box');
		const k1 = await poll(() => cs('.hv2-b2b__photo img', 'transform'), v => !desk || (v && v !== 'none'));
		await js("var b=document.querySelector('.hv2-b2b__box').getBoundingClientRect();window.scrollBy(0,Math.round(b.height*0.5));return 1");
		const k2 = await poll(() => cs('.hv2-b2b__photo img', 'transform'), v => !desk || (v && v !== k1));
		const sc1 = mat(k1)[0];
		const sc2 = mat(k2)[0];
		if (desk) ok(sc1 >= 1 && sc2 > sc1 && sc2 <= 1.2, 'ken burns foto aziende: scala ' + sc1.toFixed(3) + ' -> ' + sc2.toFixed(3) + ' (1 <= s <= 1,2)');
		else ok(k1 === 'none' && k2 === 'none', 'nessun ken burns (' + k1 + ', ' + k2 + ')');

		await js("var f=document.querySelector('.site-footer-v2');window.scrollTo(0,f.getBoundingClientRect().top+window.scrollY-window.innerHeight+120);return 1");
		const w1 = await poll(() => cs('.site-footer-v2__word-text', 'transform'), v => !desk || mat(v)[5] > 8);
		await scrollY(H);
		const w2 = await poll(() => cs('.site-footer-v2__word-text', 'transform'), v => !desk || Math.abs(mat(v)[5]) < 1);
		if (desk) ok(mat(w1)[5] > 8 && Math.abs(mat(w2)[5]) < 1, 'scritta footer legata allo scroll: ty ' + mat(w1)[5].toFixed(1) + ' px a footer appena entrato -> ' + mat(w2)[5].toFixed(1) + ' px in fondo');
		else ok(w1 === 'none' && w2 === 'none', 'scritta footer statica (' + w1 + ', ' + w2 + ')');

		// ---- loop in pausa fuori viewport ----
		const loops = [['.hv2-hero__orbit', 'testo circolare hero', '.hv2-hero__orbit'], ['.hv2-man__stamp', 'testo del timbro', '.hv2-man__orbit']];
		const loopState = (sel, animSel) => js(`var e=document.querySelector(${JSON.stringify(sel)});var a=document.querySelector(${JSON.stringify(animSel)});var cs=getComputedStyle(a);return {paused:e.classList.contains('is-paused'),name:cs.animationName,state:cs.animationPlayState}`);
		for (const [sel, nome, animSel] of loops) {
			await view(sel);
			const inView = await poll(() => loopState(sel, animSel), v => reduce || !v.paused);
			await scrollY(sel === '.hv2-hero__orbit' ? H : 0);
			const outView = await poll(() => loopState(sel, animSel), v => reduce || v.paused);
			if (reduce) ok(!/spin|orbit|rotate/.test(inView.name) || inView.name === 'hv2-fade', nome + ': nessun giro con riduci movimento (animationName ' + inView.name + ')');
			else if (mobile && sel === '.hv2-man__stamp') info(nome + ': su mobile il testo del timbro e\' nascosto (display none)');
			else ok(!inView.paused && /running/.test(inView.state.split(',').pop()) && outView.paused && /paused/.test(outView.state.split(',').pop()), nome + ': in marcia in viewport (' + inView.state + '), in pausa fuori (' + outView.state + ')');
		}
		const sweep = await js("var e=document.querySelector('.hv2-zones__sweep');if(!e)return null;e.scrollIntoView({block:'center'});return 1");
		if (sweep) {
			const sw = () => js("return document.querySelector('.hv2-zones__sweep').classList.contains('is-paused')");
			const a = await poll(sw, v => reduce || v === false);
			await scrollY(0);
			const b = await poll(sw, v => reduce || v === true);
			if (!reduce) ok(a === false && b === true, 'raggio del radar: in marcia in viewport, in pausa fuori (' + JSON.stringify([a, b]) + ')');
		}
		const inf = await js("return document.getAnimations().filter(function(a){var t=a.effect&&a.effect.getComputedTiming();return a.playState==='running'&&t&&t.iterations===Infinity}).map(function(a){return a.animationName})");
		if (reduce) ok(inf.length === 0, 'riduci movimento: nessuna animazione infinita in corso (' + inf.join(',') + ')');
		else info('animazioni infinite in corso nella posizione attuale: ' + inf.join(', '));

		// ---- ingressi (richiedono reveal.js) ----
		if (norev) return;
		await scrollY(0); await sleep(300);
		await view('.hv2-man__stamp'); await sleep(1500);
		const stamp = await js("var e=document.querySelector('.hv2-man__stamp');var c=getComputedStyle(e);return {op:c.opacity,scale:c.scale,isin:e.classList.contains('is-in'),dur:c.transitionDuration,tf:c.transitionProperty}");
		ok(stamp.isin && stamp.op === '1', 'timbro visibile dopo l\'ingresso (opacita\' ' + stamp.op + ', scale ' + stamp.scale + ', durate ' + stamp.dur + ')');
		const proof = await js("var e=document.querySelector('.hv2-man__item .hv2-man__proof');return getComputedStyle(e).opacity");
		ok(proof === '1', 'pillola della prova visibile dopo l\'ingresso');
		await view('.hv2-services__panel'); await sleep(1500);
		const readTiles = () => js("return Array.prototype.map.call(document.querySelectorAll('.hv2-services__tile'),function(t){var c=getComputedStyle(t);var d=c.transitionDelay.split(',').map(function(x){return parseFloat(x)});return {op:c.opacity,d:Math.max.apply(null,d),s:c.scale}})");
		const tiles = await poll(readTiles, v => v.every(t => t.op === '1'));
		ok(tiles.every(t => t.op === '1'), 'icone dei servizi visibili dopo l\'ingresso (' + tiles.length + ')');
		if (!reduce) {
			const step = tiles.slice(1).map((t, i) => Math.round((t.d - tiles[i].d) * 1000));
			ok(step.every(s => s === 40), 'stagger delle icone 40 ms (differenze ' + step.join(',') + ' ms)');
			ok(tiles[tiles.length - 1].d - tiles[0].d < 0.5, 'stagger totale sotto 500 ms (' + Math.round((tiles[tiles.length - 1].d - tiles[0].d) * 1000) + ' ms)');
		} else {
			ok(tiles.every(t => t.d === 0), 'riduci movimento: nessun ritardo sulle icone');
		}
		await view('.hv2-cta__stage'); await sleep(2200);
		const cta = await js("var n=getComputedStyle(document.querySelector('.hv2-cta__notif'));var t=document.querySelector('.hv2-cta__tent--back');var tc=t?getComputedStyle(t):null;return {n:n.opacity,nt:n.transform,t:tc&&tc.opacity,tt:tc&&tc.transform,td:tc&&tc.display}");
		ok(cta.n === '1' && cta.nt === 'none', 'avviso della chiusura visibile e a riposo dopo l\'ingresso (opacita\' ' + cta.n + ', transform ' + cta.nt + ')');
		if (!mobile) ok(cta.t === '1' && cta.tt === 'none', 'tentacolo a riposo dopo l\'ingresso (opacita\' ' + cta.t + ', transform ' + cta.tt + ')');
		// FAQ: apri una domanda; la transizione si legge con le API delle animazioni (in headless senza GPU i fotogrammi
		// non scorrono in tempo reale): si ferma, si porta a 1/3 della durata e si legge l'altezza, poi si porta alla fine.
		await view('.faq--v2 .faq__list');
		await sleep(900);
		const out = JSON.parse(await ev(`(function(){var it=document.querySelectorAll('.faq--v2 .faq__item')[1];var a=it.querySelector('.faq__a');var q=it.querySelector('.faq__q');q.click();var h=function(){return Math.round(a.getBoundingClientRect().height*10)/10};var an=document.getAnimations().filter(function(x){return x.effect&&x.effect.target===a&&x.transitionProperty==='grid-template-rows'});var r={open:it.classList.contains('is-open'),n:an.length,dur:an.length?an[0].effect.getTiming().duration:null,full:a.firstElementChild.scrollHeight};if(an.length){an[0].pause();an[0].currentTime=r.dur/3;r.third=h();an[0].finish();}r.end=h();return JSON.stringify(r)})()`));
		ok(reduce || (out.n === 1 && Math.round(out.dur) === (mobile ? 360 : 450)), 'FAQ: una transizione di grid-template-rows di ' + Math.round(out.dur) + ' ms all\'apertura');
		ok(out.open && out.end >= out.full - 1, 'FAQ: la risposta si apre (altezza finale ' + out.end + ' px, piena ' + out.full + ')');
		if (!reduce) ok(out.third > 1 && out.third < out.end - 1, 'FAQ: a un terzo della durata l\'altezza e\' intermedia (' + out.third + ' px su ' + out.end + ')');
	});
}

(async () => {
	const footerUrl = (process.argv.find(a => a.startsWith('--footer-url=')) || '').slice(13);
	for (let i = 0; i < MODI.length; i++) {
		if (/foot(off)?$/.test(MODI[i])) await modoFooter(MODI[i], i, footerUrl);
		else await modo(MODI[i], i);
	}
	console.log('\n' + (ko ? ko + ' controlli falliti' : 'tutti i controlli passati'));
	process.exit(ko ? 1 : 0);
})();
