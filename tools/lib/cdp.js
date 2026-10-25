// Bootstrap condiviso degli script di QA: Chromium headless via CDP (Node 22, WebSocket nativo).
// withChrome(bin, url, opts, fn): apre la pagina, passa { send, ev, shot, sleep } a fn, chiude.
// opts: port (9333), width (1440), height (980), mobile (false), profile (/tmp/cdp-profile-<port>), args ([])
const { spawn } = require('child_process');
const http = require('http');
const fs = require('fs');

const sleep = ms => new Promise(r => setTimeout(r, ms));

async function withChrome(bin, url, opts, fn) {
	const o = Object.assign({ port: 9333, width: 1440, height: 980, mobile: false, args: [] }, opts || {});
	const profile = o.profile || '/tmp/cdp-profile-' + o.port;
	const chrome = spawn(bin, ['--headless=new', '--no-sandbox', '--autoplay-policy=no-user-gesture-required', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist', '--hide-scrollbars', '--window-size=' + o.width + ',' + o.height, '--remote-debugging-port=' + o.port, '--user-data-dir=' + profile].concat(o.args, ['about:blank']), { stdio: 'ignore' });
	const getJson = (p, method) => new Promise((res, rej) => { const req = http.request({ host: '127.0.0.1', port: o.port, path: p, method: method || 'GET' }, r => { let d = ''; r.on('data', c => d += c); r.on('end', () => { try { res(JSON.parse(d)); } catch (e) { rej(d); } }); }); req.on('error', rej); req.end(); });
	try {
		for (let i = 0; i < 40; i++) { try { await getJson('/json/version'); break; } catch (e) { await sleep(300); } }
		const target = await getJson('/json/new?' + encodeURIComponent(url), 'PUT');
		const ws = new WebSocket(target.webSocketDebuggerUrl);
		let id = 0; const pending = {}; const handlers = {};
		// on(method, fn): ascolta un evento CDP (es. Fetch.requestPaused per simulare l'API). Piu' handler per evento.
		const on = (method, fn) => { (handlers[method] = handlers[method] || []).push(fn); };
		ws.onmessage = e => { const m = JSON.parse(e.data); if (m.id && pending[m.id]) { pending[m.id](m); delete pending[m.id]; } else if (m.method && handlers[m.method]) { handlers[m.method].forEach(fn => { try { fn(m.params); } catch (err) { console.log('HANDLER', m.method, err.message); } }); } else if (m.method === 'Runtime.exceptionThrown') console.log('EXC', JSON.stringify(m.params.exceptionDetails).slice(0, 300)); };
		const send = (method, params) => new Promise(r => { const mid = ++id; pending[mid] = r; ws.send(JSON.stringify({ id: mid, method, params: params || {} })); });
		await new Promise(r => ws.onopen = r);
		await send('Page.enable'); await send('Runtime.enable');
		if (o.width !== 1440 || o.mobile) await send('Emulation.setDeviceMetricsOverride', { width: o.width, height: o.height, deviceScaleFactor: 1, mobile: o.mobile });
		const ev = async expr => { const r = await send('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true }); return r.result && r.result.result ? r.result.result.value : JSON.stringify(r); };
		const shot = async (path, clip) => { const r = await send('Page.captureScreenshot', Object.assign({ format: 'png' }, clip ? { clip, captureBeyondViewport: true } : {})); fs.writeFileSync(path, Buffer.from(r.result.data, 'base64')); };
		await fn({ send, ev, shot, sleep, on });
		ws.close();
	} finally {
		chrome.kill();
	}
}

module.exports = { withChrome, sleep };
