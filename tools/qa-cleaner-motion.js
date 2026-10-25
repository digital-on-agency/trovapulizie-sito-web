// QA del movimento della pagina Diventa cleaner (assets/js/cleaner-motion.js):
// node tools/qa-cleaner-motion.js <chrome-bin> <url> [cartella-screenshot]
// 1. Ingressi: .dc--motion su main, hero in ingresso, blocchi sotto la piega nascosti finche' non entrano, nessun
//    .dc-rv rimasto dopo aver scorso tutta la pagina, stima che finisce a "≈ 1.900 €" dopo il conteggio.
// 2. Hover reali via Input.dispatchMouseEvent: card dei passi e tile (-4px, schermo -28px), bottone (-2px),
//    parallasse della hero (--ry diverso da 0). Screenshot degli stati nella cartella indicata.
// 3. "Riduci movimento" emulato: niente .dc--motion e tutto visibile.
// Chromium headless dichiara hover: none e pointer: none: le media query degli hover non scatterebbero mai. Si avvia
// con le impostazioni Blink di un mouse (primaryHoverType=2, primaryPointerType=4).
// Esce con 1 se un controllo fallisce.
const { withChrome } = require('./lib/cdp');
const [BIN, URL, OUT] = process.argv.slice(2, 5);
const out = OUT || '/tmp';
const MOUSE = '--blink-settings=primaryHoverType=2,availableHoverTypes=2,primaryPointerType=4,availablePointerTypes=4';
const checks = [];
const check = (n, ok, v) => checks.push([n, !!ok, v]);

(async () => {
	await withChrome(BIN, URL, { port: 9361, width: 1440, height: 900, args: [MOUSE] }, async ({ send, ev, shot, sleep }) => {
		await sleep(3500);
		const s0 = JSON.parse(await ev(`(function(){
			var tile = document.querySelector('.dc-tile');
			return JSON.stringify({
				motion: document.querySelector('main.dc').classList.contains('dc--motion'),
				heroIn: document.querySelector('.dc-hero').classList.contains('is-in'),
				float: getComputedStyle(document.querySelector('.dc-float--1')).opacity,
				tileRv: tile.classList.contains('dc-rv'), tileOp: getComputedStyle(tile).opacity
			});
		})()`));
		check('dc--motion su main', s0.motion, s0.motion);
		check('hero entrata al caricamento', s0.heroIn, s0.heroIn);
		check('card flottante visibile dopo l\'ingresso', parseFloat(s0.float) > 0.9, s0.float);
		check('tile sotto la piega nascosta prima dello scroll', s0.tileRv && s0.tileOp === '0', s0);
		await shot(out + '/motion-hero.png');

		// Parallasse: mouse sul lato destro della hero
		await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: 1300, y: 300 });
		await sleep(600);
		const ry = await ev(`getComputedStyle(document.querySelector('.dc-hero__stage')).getPropertyValue('--ry').trim()`);
		check('parallasse: telefono inclinato col mouse', ry && ry !== '0deg' && parseFloat(ry) > 2, ry);
		await shot(out + '/motion-parallax.png');

		// Scorre tutta la pagina a passi
		const H = await ev(`document.documentElement.scrollHeight`);
		for (let y = 0; y < H; y += 450) { await ev(`window.scrollTo(0, ${y})`); await sleep(260); }
		await sleep(1500);
		const s1 = JSON.parse(await ev(`JSON.stringify({ rimasti: document.querySelectorAll('.dc-rv').length, stima: document.querySelector('[data-earn-total]').textContent.trim() })`));
		check('nessun blocco rimasto nascosto dopo lo scroll', s1.rimasti === 0, s1.rimasti);
		check('stima dopo il conteggio', s1.stima === '≈ 1.900 €', s1.stima);

		// Hover su card dei passi, tile e bottone hero
		async function hover(sel, name) {
			await ev(`document.querySelector('${sel}').scrollIntoView({ block: 'center' })`);
			await sleep(400);
			const b = JSON.parse(await ev(`JSON.stringify(document.querySelector('${sel}').getBoundingClientRect())`));
			await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: Math.round(b.x + b.width / 2), y: Math.round(b.y + b.height / 2) });
			await sleep(1100);
			const r = JSON.parse(await ev(`(function(){ var el = document.querySelector('${sel}'); var img = el.querySelector('.dc-peek img');
				return JSON.stringify({ t: getComputedStyle(el).transform, img: img ? getComputedStyle(img).transform : null }); })()`));
			await shot(out + '/motion-hover-' + name + '.png');
			await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: 5, y: 5 });
			await sleep(400);
			return r;
		}
		const ty = m => (m && m.indexOf('matrix') === 0) ? parseFloat(m.split(',')[5]) : 0;
		const st = await hover('.sg-steps--screens .sg-step', 'step');
		check('hover card passo: -4px', Math.round(ty(st.t)) === -4, st.t);
		check('hover card passo: schermo -28px', Math.round(ty(st.img)) === -28, st.img);
		const ti = await hover('.dc-tile:nth-child(2)', 'tile');
		check('hover tile: -4px e schermo -28px', Math.round(ty(ti.t)) === -4 && Math.round(ty(ti.img)) === -28, ti);
		const bt = await hover('.dc-hero__ctas .tp-btn--primary', 'button');
		check('hover bottone hero: -2px', Math.round(ty(bt.t)) === -2, bt.t);
	});

	// Riduci movimento
	await withChrome(BIN, URL, { port: 9362, width: 1440, height: 900 }, async ({ send, ev, sleep }) => {
		await send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
		await send('Page.reload', { ignoreCache: true });
		await sleep(3500);
		const r = JSON.parse(await ev(`JSON.stringify({ motion: document.querySelector('main.dc').classList.contains('dc--motion'), op: getComputedStyle(document.querySelector('.dc-tile')).opacity, anim: getComputedStyle(document.querySelector('.dc-float--1')).animationName })`));
		check('riduci movimento: nessun ingresso, tutto visibile', !r.motion && r.op === '1', r);
		check('riduci movimento: card ferme', r.anim === 'none', r.anim);
	});

	let ko = 0;
	checks.forEach(([n, ok, v]) => { if (!ok) ko++; console.log((ok ? 'OK  ' : 'KO  ') + n + '  ' + JSON.stringify(v)); });
	console.log(ko ? 'ESITO: ' + ko + ' controlli falliti' : 'ESITO: verde');
	process.exit(ko ? 1 : 0);
})().catch(e => { console.error(e); process.exit(1); });
