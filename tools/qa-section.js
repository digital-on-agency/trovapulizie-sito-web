// QA generica di una sezione: node tools/qa-section.js <chrome-bin> <url> <selettore> [width=1440] [out=/tmp/cdp-section.png]
// Porta la sezione in cima al viewport, aspetta reveal/animazioni (4 s), scatta lo screenshot e logga rect, classe is-in
// e lo stato di eventuali animazioni CSS (animation-name/play-state del primo discendente animato).
const { spawn } = require('child_process');
const http = require('http');
const fs = require('fs');
const [BIN, URL, SEL] = process.argv.slice(2, 5);
const W = parseInt(process.argv[5] || '1440', 10), OUT = process.argv[6] || '/tmp/cdp-section.png';
const chrome = spawn(BIN, ['--headless=new', '--no-sandbox', '--autoplay-policy=no-user-gesture-required', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist', '--hide-scrollbars', '--window-size=' + W + ',980', '--remote-debugging-port=9337', '--user-data-dir=/tmp/cdp-section-profile-' + W + '', 'about:blank'], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
function getJson(p, method) { return new Promise((res, rej) => { const req = http.request({ host: '127.0.0.1', port: 9337, path: p, method: method || 'GET' }, r => { let d = ''; r.on('data', c => d += c); r.on('end', () => { try { res(JSON.parse(d)); } catch (e) { rej(d); } }); }); req.on('error', rej); req.end(); }); }
(async () => {
	for (let i = 0; i < 40; i++) { try { await getJson('/json/version'); break; } catch (e) { await sleep(300); } }
	const target = await getJson('/json/new?' + encodeURIComponent(URL), 'PUT');
	const ws = new WebSocket(target.webSocketDebuggerUrl);
	let id = 0; const pending = {};
	ws.onmessage = e => { const m = JSON.parse(e.data); if (m.id && pending[m.id]) { pending[m.id](m); delete pending[m.id]; } else if (m.method === 'Runtime.exceptionThrown') console.log('EXC', JSON.stringify(m.params.exceptionDetails).slice(0, 300)); };
	const send = (method, params) => new Promise(r => { const mid = ++id; pending[mid] = r; ws.send(JSON.stringify({ id: mid, method, params: params || {} })); });
	await new Promise(r => ws.onopen = r);
	await send('Page.enable'); await send('Runtime.enable');
	await send('Emulation.setDeviceMetricsOverride', { width: W, height: 980, deviceScaleFactor: 1, mobile: W < 800 });
	const ev = async expr => { const r = await send('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true }); return r.result && r.result.result ? r.result.result.value : JSON.stringify(r); };
	await sleep(2500);
	await ev(`document.querySelector(${JSON.stringify(SEL)}).scrollIntoView({block:'start'}); 'ok'`);
	await sleep(4000);
	console.log('STATE', await ev(`(function(){var s=document.querySelector(${JSON.stringify(SEL)}); var b=s.getBoundingClientRect(); var an=null; s.querySelectorAll('*').forEach(function(el){ if(an) return; var cs=getComputedStyle(el); if(cs.animationName && cs.animationName!=='none') an={el:el.className, name:cs.animationName, state:cs.animationPlayState, dur:cs.animationDuration, tf:cs.transform.slice(0,40)}; }); var z=document.getElementById('hiw-zoom'); return JSON.stringify({rect:[Math.round(b.left),Math.round(b.top),Math.round(b.width),Math.round(b.height)], isIn:s.classList.contains('is-in'), anim:an, overlayOn: z? z.classList.contains('is-on') : null});})()`));
	const shot = await send('Page.captureScreenshot', { format: 'png' });
	fs.writeFileSync(OUT, Buffer.from(shot.result.data, 'base64'));
	ws.close(); chrome.kill(); process.exit(0);
})().catch(e => { console.error('ERR', e); chrome.kill(); process.exit(1); });
