// QA: node tools/qa-services.js <chrome-bin> <url> [width=1440] [slow=1] — porta la sezione Servizi popolari nel viewport,
// aspetta il reveal, scatta /tmp/cdp-services-{0,1}.png (tab 1 attiva, poi tab 3 dopo il click) e logga geometria di
// indicatore/tab attiva, card attiva e mascotte. In headless le transizioni CSS avanzano solo quando si scatta: con
// letture "a meta'" rilanciare con slow=4.
const { withChrome } = require('./lib/cdp');
const [BIN, URL] = process.argv.slice(2, 4);
const W = parseInt(process.argv[4] || '1440', 10), SLOW = parseFloat(process.argv[5] || '1');

withChrome(BIN, URL, { port: 9336, width: W, mobile: W < 800 }, async ({ ev, shot, sleep }) => {
	await sleep(2500);
	await ev(`document.getElementById('servizi').scrollIntoView({block:'start'}); 'ok'`);
	await sleep(1800 * SLOW);
	const state = () => ev(`(function(){var s=document.querySelector('.services');var t=s.querySelector('.services__tab.is-active');var i=s.querySelector('.services__tab-indicator');var c=s.querySelector('.services__card.is-active');var m=s.querySelector('.services__mascot');var r=e=>{var b=e.getBoundingClientRect();return [Math.round(b.left),Math.round(b.top),Math.round(b.width),Math.round(b.height)]};var io=s.getBoundingClientRect();return JSON.stringify({in:s.classList.contains('is-in'),visible:[Math.round(io.top),Math.round(io.bottom),innerHeight],ready:s.querySelector('.services__tabs').classList.contains('is-ready'),tab:t&&t.textContent.trim(),tabRect:t&&r(t),ind:i&&r(i),card:c&&c.id,cardRect:c&&r(c),cardOp:c&&getComputedStyle(c).opacity,chips:c&&c.querySelectorAll('.tp-chip').length,mascot:m&&r(m),section:r(s)});})()`);
	console.log('S0', await state());
	await shot('/tmp/cdp-services-0.png');
	await ev(`document.querySelectorAll('.services__tab')[2].click(); 'ok'`);
	await sleep(900 * SLOW);
	console.log('S1', await state());
	await shot('/tmp/cdp-services-1.png');
}).catch(e => { console.error('ERR', e); process.exit(1); });
