// QA generica di una sezione: node tools/qa-section.js <chrome-bin> <url> <selettore> [width=1440] [out=/tmp/cdp-section.png] [selettore-da-cliccare]
// Porta la sezione in cima al viewport, aspetta reveal/animazioni (4 s), scatta lo screenshot e logga rect, classe is-in,
// la prima animazione CSS trovata e lo stato dell'overlay dello zoom-through (#hiw-zoom). Con il selettore da cliccare
// scatta un secondo screenshot "-click" e logga aria-expanded / is-open dell'elemento cliccato.
const { withChrome } = require('./lib/cdp');
const [BIN, URL, SEL] = process.argv.slice(2, 5);
const W = parseInt(process.argv[5] || '1440', 10), OUT = process.argv[6] || '/tmp/cdp-section.png', CLICK = process.argv[7] || '';

withChrome(BIN, URL, { port: 9337, width: W, mobile: W < 800 }, async ({ ev, shot, sleep }) => {
	await sleep(2500);
	await ev(`document.querySelector(${JSON.stringify(SEL)}).scrollIntoView({block:'start'}); 'ok'`);
	await sleep(4000);
	console.log('STATE', await ev(`(function(){var s=document.querySelector(${JSON.stringify(SEL)}); var b=s.getBoundingClientRect(); var an=null; var all=s.querySelectorAll('*'); for (var i=0;i<all.length;i++){ var cs=getComputedStyle(all[i]); if(cs.animationName && cs.animationName!=='none'){ an={el:all[i].className, name:cs.animationName, state:cs.animationPlayState, dur:cs.animationDuration}; break; } } var z=document.getElementById('hiw-zoom'); return JSON.stringify({rect:[Math.round(b.left),Math.round(b.top),Math.round(b.width),Math.round(b.height)], isIn:s.classList.contains('is-in'), anim:an, overlayOn: z? z.classList.contains('is-on') : null});})()`));
	await shot(OUT);
	if (CLICK) {
		await ev(`document.querySelector(${JSON.stringify(CLICK)}).click(); 'ok'`);
		await sleep(1500);
		await shot(OUT.replace(/\.png$/, '-click.png'));
		console.log('CLICK', CLICK, await ev(`(function(){var e=document.querySelector(${JSON.stringify(CLICK)}); return JSON.stringify({expanded:e.getAttribute('aria-expanded'), open: !!e.closest('.is-open')});})()`));
	}
}).catch(e => { console.error('ERR', e); process.exit(1); });
