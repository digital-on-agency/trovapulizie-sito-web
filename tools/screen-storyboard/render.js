// Render dello storyboard schermo frame per frame con Chromium headless via CDP.
// Uso: node render.js <chrome-bin> --out <dir> [--fps 24] [--times 0.2,2.4,...] [--total 12.6]
// Poi: ffmpeg -framerate 24 -i <dir>/f%04d.png -vf scale=540:1170 -c:v libx264 -x264-params keyint=1:min-keyint=1:scenecut=0 -crf 24 -pix_fmt yuv420p -movflags +faststart out.mp4
const { spawn } = require('child_process');
const http = require('http');
const fs = require('fs');
const path = require('path');

const args = process.argv.slice(2);
const BIN = args[0];
const opt = (k, d) => { const i = args.indexOf(k); return i > -1 ? args[i + 1] : d; };
const OUT = opt('--out', '/tmp/sb-frames');
const FPS = parseFloat(opt('--fps', '24'));
const TOTAL = parseFloat(opt('--total', '12.6'));
const TIMES = opt('--times', null);
const PAGE = 'file://' + path.resolve(__dirname, 'index.html');
fs.mkdirSync(OUT, { recursive: true });

const chrome = spawn(BIN, ['--headless=new', '--no-sandbox', '--allow-file-access-from-files', '--hide-scrollbars', '--force-device-scale-factor=1', '--window-size=1170,2532', '--remote-debugging-port=9334', '--user-data-dir=/tmp/sb-profile', 'about:blank'], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
function getJson(p, method) { return new Promise((res, rej) => { const req = http.request({ host: '127.0.0.1', port: 9334, path: p, method: method || 'GET' }, r => { let d = ''; r.on('data', c => d += c); r.on('end', () => { try { res(JSON.parse(d)); } catch (e) { rej(d); } }); }); req.on('error', rej); req.end(); }); }

(async () => {
	for (let i = 0; i < 40; i++) { try { await getJson('/json/version'); break; } catch (e) { await sleep(300); } }
	const target = await getJson('/json/new?' + encodeURIComponent(PAGE), 'PUT');
	const ws = new WebSocket(target.webSocketDebuggerUrl);
	let id = 0; const pending = {};
	ws.onmessage = e => { const m = JSON.parse(e.data); if (m.id && pending[m.id]) { pending[m.id](m); delete pending[m.id]; } else if (m.method === 'Runtime.exceptionThrown') console.log('EXC', JSON.stringify(m.params.exceptionDetails).slice(0, 300)); };
	const send = (method, params) => new Promise(r => { const mid = ++id; pending[mid] = r; ws.send(JSON.stringify({ id: mid, method, params: params || {} })); });
	await new Promise(r => ws.onopen = r);
	await send('Page.enable'); await send('Runtime.enable');
	await send('Emulation.setDeviceMetricsOverride', { width: 1170, height: 2532, deviceScaleFactor: 1, mobile: false });
	const ev = async expr => { const r = await send('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true }); return r.result && r.result.result ? r.result.result.value : undefined; };
	for (let i = 0; i < 60; i++) { const ok = await ev(`!!window.storyboardReady && Array.from(document.images).every(im => im.complete && im.naturalWidth > 0) && document.fonts.status === 'loaded'`); if (ok === true) break; await sleep(250); }
	await ev('document.fonts.ready.then(() => true)');

	const times = TIMES ? TIMES.split(',').map(Number) : Array.from({ length: Math.round(TOTAL * FPS) + 1 }, (_, i) => i / FPS);
	for (let i = 0; i < times.length; i++) {
		const t = times[i];
		await ev(`window.seek(${t}); 'ok'`);
		await send('Runtime.evaluate', { expression: 'new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)))', awaitPromise: true });
		const shot = await send('Page.captureScreenshot', { format: 'png', clip: { x: 0, y: 0, width: 1170, height: 2532, scale: 1 }, captureBeyondViewport: true });
		const name = TIMES ? ('t' + t.toFixed(2).replace('.', '_') + '.png') : ('f' + String(i).padStart(4, '0') + '.png');
		fs.writeFileSync(path.join(OUT, name), Buffer.from(shot.result.data, 'base64'));
		if (i % 24 === 0 || TIMES) console.log('frame', i, 't=' + t.toFixed(2));
	}
	ws.close(); chrome.kill(); process.exit(0);
})().catch(e => { console.error('ERR', e); chrome.kill(); process.exit(1); });
