// QA: node tools/qa-services.js <chrome-bin> <url> [width=1440] — porta la sezione Servizi popolari nel viewport,
// aspetta il reveal, scatta /tmp/cdp-services-{0,1}.png (tab 1 attiva, poi tab 3 dopo il click) e logga geometria
// di indicatore/tab attiva, card attiva e mascotte.
const { spawn } = require('child_process');
const http = require('http');
const fs = require('fs');
const BIN = process.argv[2], URL = process.argv[3], W = parseInt(process.argv[4] || '1440', 10), SLOW = parseFloat(process.argv[5] || '1');
const chrome = spawn(BIN, ['--headless=new', '--no-sandbox', '--autoplay-policy=no-user-gesture-required', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist', '--hide-scrollbars', '--window-size=' + W + ',980', '--remote-debugging-port=9336', '--user-data-dir=/tmp/cdp-services-profile', 'about:blank'], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
function getJson(p, method) { return new Promise((res, rej) => { const req = http.request({ host: '127.0.0.1', port: 9336, path: p, method: method || 'GET' }, r => { let d = ''; r.on('data', c => d += c); r.on('end', () => { try { res(JSON.parse(d)); } catch (e) { rej(d); } }); }); req.on('error', rej); req.end(); }); }
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
	await ev(`document.getElementById('servizi').scrollIntoView({block:'start'}); 'ok'`);
	await sleep(1800 * SLOW);
	const state = () => ev(`(function(){var s=document.querySelector('.services');var t=s.querySelector('.services__tab.is-active');var i=s.querySelector('.services__tab-indicator');var c=s.querySelector('.services__card.is-active');var m=s.querySelector('.services__mascot');var r=e=>{var b=e.getBoundingClientRect();return [Math.round(b.left),Math.round(b.top),Math.round(b.width),Math.round(b.height)]};var io=s.getBoundingClientRect();return JSON.stringify({in:s.classList.contains('is-in'),visible:[Math.round(io.top),Math.round(io.bottom),innerHeight],ready:s.querySelector('.services__tabs').classList.contains('is-ready'),tab:t&&t.textContent.trim(),tabRect:t&&r(t),ind:i&&r(i),card:c&&c.id,cardRect:c&&r(c),cardOp:c&&getComputedStyle(c).opacity,chips:c&&c.querySelectorAll('.tp-chip').length,mascot:m&&r(m),section:r(s)});})()`);
	console.log('S0', await state());
	let shot = await send('Page.captureScreenshot', { format: 'png' });
	fs.writeFileSync('/tmp/cdp-services-0.png', Buffer.from(shot.result.data, 'base64'));
	await ev(`document.querySelectorAll('.services__tab')[2].click(); 'ok'`);
	await sleep(900 * SLOW);
	console.log('S1', await state());
	shot = await send('Page.captureScreenshot', { format: 'png' });
	fs.writeFileSync('/tmp/cdp-services-1.png', Buffer.from(shot.result.data, 'base64'));
	ws.close(); chrome.kill(); process.exit(0);
})().catch(e => { console.error('ERR', e); chrome.kill(); process.exit(1); });
