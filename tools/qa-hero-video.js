// QA: node tools/qa-hero-video.js <chrome-bin> <url> — carica la home, verifica che il timelapse della hero
// parta da solo (muto), sfumi (classe is-playing) e si fermi sull'ultimo frame; scatta /tmp/cdp-hero-{0,1,2}.png
const { spawn } = require('child_process');
const http = require('http');
const fs = require('fs');
const BIN = process.argv[2], URL = process.argv[3];
const chrome = spawn(BIN, ['--headless=new', '--no-sandbox', '--autoplay-policy=no-user-gesture-required', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist', '--hide-scrollbars', '--window-size=1440,980', '--remote-debugging-port=9335', '--user-data-dir=/tmp/cdp-hero-profile', 'about:blank'], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
function getJson(p, method) { return new Promise((res, rej) => { const req = http.request({ host: '127.0.0.1', port: 9335, path: p, method: method || 'GET' }, r => { let d = ''; r.on('data', c => d += c); r.on('end', () => { try { res(JSON.parse(d)); } catch (e) { rej(d); } }); }); req.on('error', rej); req.end(); }); }
(async () => {
	for (let i = 0; i < 40; i++) { try { await getJson('/json/version'); break; } catch (e) { await sleep(300); } }
	const target = await getJson('/json/new?' + encodeURIComponent(URL), 'PUT');
	const ws = new WebSocket(target.webSocketDebuggerUrl);
	let id = 0; const pending = {};
	ws.onmessage = e => { const m = JSON.parse(e.data); if (m.id && pending[m.id]) { pending[m.id](m); delete pending[m.id]; } else if (m.method === 'Runtime.exceptionThrown') console.log('EXC', JSON.stringify(m.params.exceptionDetails).slice(0, 300)); };
	const send = (method, params) => new Promise(r => { const mid = ++id; pending[mid] = r; ws.send(JSON.stringify({ id: mid, method, params: params || {} })); });
	await new Promise(r => ws.onopen = r);
	await send('Page.enable'); await send('Runtime.enable');
	const ev = async expr => { const r = await send('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true }); return r.result && r.result.result ? r.result.result.value : JSON.stringify(r); };
	const state = () => ev(`(function(){var v=document.querySelector('.hero__video'); if(!v) return 'NO VIDEO'; return JSON.stringify({src:v.currentSrc.split('/').pop(), ready:v.readyState, t:+v.currentTime.toFixed(2), dur:+v.duration.toFixed(2), paused:v.paused, ended:v.ended, playing:v.classList.contains('is-playing'), opacity:getComputedStyle(v).opacity});})()`);
	for (const [i, wait] of [[0, 800], [1, 1500], [2, 3000]]) {
		await sleep(wait);
		console.log('t+' + i, await state());
		const shot = await send('Page.captureScreenshot', { format: 'png' });
		fs.writeFileSync('/tmp/cdp-hero-' + i + '.png', Buffer.from(shot.result.data, 'base64'));
	}
	ws.close(); chrome.kill(); process.exit(0);
})().catch(e => { console.error('ERR', e); chrome.kill(); process.exit(1); });
