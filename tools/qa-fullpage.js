// Screenshot dell'intera pagina: node tools/qa-fullpage.js <chrome-bin> <url> [width=1440] [out=/tmp/cdp-fullpage.png] [maxHeight=6000]
// Carica la pagina, aspetta reveal e immagini (3 s), porta il viewport all'altezza del documento (cosi' navbar
// sticky e barra fissa non coprono nulla) e scatta un PNG unico. Serve a vedere le sezioni una sotto l'altra
// dopo un deploy; per lo stato di una singola sezione con scroll reale usare qa-section.js.
const { withChrome } = require('./lib/cdp');
const [BIN, URL] = process.argv.slice(2, 4);
const W = parseInt(process.argv[4] || '1440', 10), OUT = process.argv[5] || '/tmp/cdp-fullpage.png', MAX = parseInt(process.argv[6] || '6000', 10);

withChrome(BIN, URL, { port: 9341, width: W, mobile: W < 800 }, async ({ send, ev, shot, sleep }) => {
	await sleep(3000);
	const h = Math.min(MAX, parseInt(await ev('document.documentElement.scrollHeight'), 10) || 2000);
	await send('Emulation.setDeviceMetricsOverride', { width: W, height: h, deviceScaleFactor: 1, mobile: W < 800 });
	await sleep(1500);
	await shot(OUT, { x: 0, y: 0, width: W, height: h, scale: 1 });
	console.log('FULLPAGE', JSON.stringify({ width: W, height: h, out: OUT }));
}).catch(e => { console.error('ERR', e); process.exit(1); });
