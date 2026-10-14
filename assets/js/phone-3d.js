/* Telefono 3D (Three.js, ADR-002 rev.): corpo generico procedurale o GLB da campo WP, schermo = VideoTexture.
   Pose con la stessa semantica del ramo CSS di home-motion.js. Render solo su richiesta. */
import * as THREE from 'three';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

// Proporzioni di uno smartphone generico: 71.6 x 147.6 x 7.8 mm, raggio angoli ~11 mm (unita' scena = 100 mm)
const PHONE = { w: 0.716, h: 1.476, d: 0.078, r: 0.11 };

function roundedRect(w, h, r) {
	const s = new THREE.Shape();
	const x = -w / 2, y = -h / 2;
	s.moveTo(x + r, y);
	s.lineTo(x + w - r, y);
	s.quadraticCurveTo(x + w, y, x + w, y + r);
	s.lineTo(x + w, y + h - r);
	s.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
	s.lineTo(x + r, y + h);
	s.quadraticCurveTo(x, y + h, x, y + h - r);
	s.lineTo(x, y + r);
	s.quadraticCurveTo(x, y, x + r, y);
	return s;
}

function plateGeometry(w, h, r) {
	const g = new THREE.ShapeGeometry(roundedRect(w, h, r), 16);
	const pos = g.attributes.position, uv = g.attributes.uv;
	for (let i = 0; i < pos.count; i++) uv.setXY(i, (pos.getX(i) + w / 2) / w, (pos.getY(i) + h / 2) / h);
	return g;
}

export async function createPhone3D({ container, video, modelUrl, phonePx, width, height }) {
	const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
	renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
	renderer.setSize(width, height);
	renderer.outputColorSpace = THREE.SRGBColorSpace;
	renderer.toneMapping = THREE.ACESFilmicToneMapping;
	renderer.toneMappingExposure = 1.05;
	container.appendChild(renderer.domElement);

	const scene = new THREE.Scene();
	const pmrem = new THREE.PMREMGenerator(renderer);
	scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
	pmrem.dispose();

	const fov = 28;
	const camera = new THREE.PerspectiveCamera(fov, width / height, 0.1, 50);
	camera.position.z = (PHONE.h * height) / (phonePx * 2 * Math.tan(THREE.MathUtils.degToRad(fov / 2)));

	const key = new THREE.DirectionalLight(0xffffff, 1.4);
	key.position.set(-2, 3, 4);
	scene.add(key, new THREE.AmbientLight(0xffffff, 0.06));

	const group = new THREE.Group();
	group.rotation.order = 'YXZ';
	scene.add(group);

	let frontZ = PHONE.d / 2;
	if (modelUrl) {
		try {
			const gltf = await new GLTFLoader().loadAsync(modelUrl);
			const m = gltf.scene;
			const box = new THREE.Box3().setFromObject(m);
			const size = box.getSize(new THREE.Vector3());
			m.position.sub(box.getCenter(new THREE.Vector3()));
			const s = PHONE.h / size.y;
			m.scale.setScalar(s);
			const holder = new THREE.Group();
			holder.add(m);
			group.add(holder);
			frontZ = (size.z * s) / 2;
		} catch (e) {
			console.warn('phone-3d: modello non caricato, corpo procedurale', e);
		}
	}
	if (!group.children.length) {
		// Profilo arrotondato (raggio angoli 11 mm) estruso per lo spessore, bevel 1.2 mm sugli spigoli: le proporzioni di un telefono reale
		const bevel = 0.012;
		const geo = new THREE.ExtrudeGeometry(roundedRect(PHONE.w - bevel * 2, PHONE.h - bevel * 2, PHONE.r - bevel), {
			depth: PHONE.d - bevel * 2, bevelEnabled: true, bevelThickness: bevel, bevelSize: bevel, bevelSegments: 4, curveSegments: 24
		});
		geo.translate(0, 0, -(PHONE.d - bevel * 2) / 2);
		// Gruppo 0 = facce frontale/posteriore (cornice attorno allo schermo): nero opaco, niente riflesso grigio da fermo.
		// Gruppo 1 = fianchi + bevel: metallo con riflesso ambiente.
		const body = new THREE.Mesh(geo, [
			new THREE.MeshPhysicalMaterial({ color: 0x08090c, metalness: 0.25, roughness: 0.6, envMapIntensity: 0.35 }),
			new THREE.MeshPhysicalMaterial({ color: 0x1a1c21, metalness: 0.75, roughness: 0.3, clearcoat: 0.6, clearcoatRoughness: 0.2 })
		]);
		group.add(body);
	}

	const bezel = PHONE.w * 0.04;
	const sw = PHONE.w - bezel * 2, sh = PHONE.h - bezel * 2, sr = PHONE.r - bezel;
	const tex = new THREE.VideoTexture(video);
	tex.colorSpace = THREE.SRGBColorSpace;
	tex.minFilter = THREE.LinearFilter;
	tex.generateMipmaps = false;
	const screen = new THREE.Mesh(plateGeometry(sw, sh, sr), new THREE.MeshBasicMaterial({ map: tex, toneMapped: false }));
	screen.position.z = frontZ + 0.002;
	const glass = new THREE.Mesh(
		plateGeometry(sw, sh, sr),
		new THREE.MeshPhysicalMaterial({ color: 0xffffff, metalness: 0, roughness: 0.06, transparent: true, opacity: 0.1, envMapIntensity: 1.3, depthWrite: false })
	);
	glass.position.z = frontZ + 0.004;
	const island = new THREE.Mesh(plateGeometry(PHONE.w * 0.31, PHONE.h * 0.043, PHONE.h * 0.0215), new THREE.MeshBasicMaterial({ color: 0x0d0f14 }));
	island.position.set(0, sh / 2 - PHONE.h * 0.045, frontZ + 0.003);
	group.add(screen, glass, island);

	// Overlay che escono dallo schermo (tile servizi, card cleaner): piani sopra il vetro, sollevati lungo Z dalla timeline condivisa
	const qs = new URL(import.meta.url).search;
	const tl = await import('./screen-timeline.js' + qs);
	const base = new URL('../../', import.meta.url).href.replace(/\/$/, '');
	const loader = new THREE.TextureLoader();
	const texOf = file => { const t = loader.load(base + '/assets/img/tiles/' + file); t.colorSpace = THREE.SRGBColorSpace; t.anisotropy = 4; return t; };
	const shadowTex = (() => {
		const c = document.createElement('canvas'); c.width = c.height = 128;
		const g = c.getContext('2d'); const grd = g.createRadialGradient(64, 64, 8, 64, 64, 64);
		grd.addColorStop(0, 'rgba(10,20,30,0.9)'); grd.addColorStop(1, 'rgba(10,20,30,0)');
		g.fillStyle = grd; g.fillRect(0, 0, 128, 128);
		return new THREE.CanvasTexture(c);
	})();
	const LIFT = 0.14, Z0 = frontZ + 0.0045;
	function overlay(file, u, v, w, h, r) {
		const m = new THREE.Mesh(plateGeometry(w, h, r), new THREE.MeshBasicMaterial({ map: texOf(file), transparent: true, toneMapped: false, depthWrite: false }));
		const s = new THREE.Mesh(new THREE.PlaneGeometry(w * 1.6, h * 1.6), new THREE.MeshBasicMaterial({ map: shadowTex, transparent: true, opacity: 0, depthWrite: false, toneMapped: false }));
		const x = (u - 0.5) * sw, y = (0.5 - v) * sh;
		m.position.set(x, y, Z0); s.position.set(x, y - h * 0.1, Z0 - 0.0005);
		m.renderOrder = 3; s.renderOrder = 2; m.visible = s.visible = false;
		group.add(s, m);
		return { m, s };
	}
	function setLift(o, lift) {
		const on = lift > 0.001;
		o.m.visible = o.s.visible = on;
		if (!on) return;
		o.m.position.z = Z0 + lift * LIFT;
		o.m.scale.setScalar(1 + 0.1 * lift);
		o.s.material.opacity = 0.45 * lift;
		o.s.scale.setScalar(1 + 0.35 * lift);
	}
	const tw = tl.TILE_SIZE[0] * sw, th = tl.TILE_SIZE[1] * sh;
	const tiles = {};
	Object.keys(tl.TILES).forEach(name => { tiles[name] = overlay('tile-' + name + '.png', tl.TILES[name][0], tl.TILES[name][1], tw, th, tw * 0.19); });
	const casaNeutral = texOf('tile-casa-neutral.png'), casaSelected = tiles.casa.m.material.map;
	const card = overlay('card-laura.png', tl.CARD.x + tl.CARD.w / 2, tl.CARD.y + tl.CARD.h / 2, tl.CARD.w * sw, tl.CARD.h * sh, 0.02);

	// q = progress del pin (0..1) -> tempo del video sulla timeline condivisa
	function setProgress(q) {
		const t = Math.max(0, Math.min(1, q)) * tl.T.total;
		Object.keys(tiles).forEach(name => setLift(tiles[name], t < tl.T.s2.start ? tl.tileLift(name, t) : 0));
		const map = t < tl.T.s1.select ? casaNeutral : casaSelected;
		if (tiles.casa.m.material.map !== map) { tiles.casa.m.material.map = map; tiles.casa.m.material.needsUpdate = true; }
		setLift(card, t >= tl.T.s2.start && t < tl.T.s3.start ? tl.cardLift(t) : 0);
		requestRender();
	}

	const unitPerPx = PHONE.h / phonePx;
	let pending = false;
	function render() { pending = false; renderer.render(scene, camera); }
	function requestRender() { if (!pending) { pending = true; requestAnimationFrame(render); } }
	// Il primo frame arriva alla texture solo dopo un seek: a 0 esatto Chrome e Safari lasciano lo schermo nero
	function primeFrame() { if (video.currentTime < 0.001) video.currentTime = 0.001; else { tex.needsUpdate = true; requestRender(); } }
	video.addEventListener('seeked', function () { tex.needsUpdate = true; requestRender(); });
	video.addEventListener('loadeddata', primeFrame);
	if (video.readyState >= 2) primeFrame();

	// p: { x px, y frazione viewport, ry/rx/rz gradi (convenzione CSS), s scala }
	function setPose(p, sx, vh) {
		group.rotation.set(THREE.MathUtils.degToRad(-p.rx), THREE.MathUtils.degToRad(p.ry), THREE.MathUtils.degToRad(-p.rz));
		group.position.set(p.x * sx * unitPerPx, -p.y * vh * unitPerPx, 0);
		group.scale.setScalar(p.s);
		requestRender();
	}

	requestRender();
	return { setPose, setProgress, render: requestRender };
}
