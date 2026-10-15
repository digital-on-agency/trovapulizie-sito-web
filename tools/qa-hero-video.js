// QA: node tools/qa-hero-video.js <chrome-bin> <url> — carica la home, verifica che il timelapse della hero
// parta da solo (muto), sfumi (classe is-playing) e si fermi sull'ultimo frame; scatta /tmp/cdp-hero-{0,1,2}.png
const { withChrome } = require('./lib/cdp');
const [BIN, URL] = process.argv.slice(2, 4);

withChrome(BIN, URL, { port: 9335 }, async ({ ev, shot, sleep }) => {
	const state = () => ev(`(function(){var v=document.querySelector('.hero__video'); if(!v) return 'NO VIDEO'; return JSON.stringify({src:v.currentSrc.split('/').pop(), ready:v.readyState, t:+v.currentTime.toFixed(2), dur:+v.duration.toFixed(2), paused:v.paused, ended:v.ended, playing:v.classList.contains('is-playing'), opacity:getComputedStyle(v).opacity});})()`);
	for (const [i, wait] of [[0, 800], [1, 1500], [2, 3000]]) {
		await sleep(wait);
		console.log('t+' + i, await state());
		await shot('/tmp/cdp-hero-' + i + '.png');
	}
}).catch(e => { console.error('ERR', e); process.exit(1); });
