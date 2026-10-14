// QA: node tools/qa-scroll-shots.js <chrome-bin> <url> — scatta /tmp/cdp-*.png agli stati del pin Come funziona e logga posa/currentTime/step.
const { spawn } = require('child_process');
const http = require('http');
const fs = require('fs');
const BIN = process.argv[2], URL = process.argv[3];
const chrome = spawn(BIN, ['--headless=new','--no-sandbox','--disable-gpu','--hide-scrollbars','--window-size=1440,980','--remote-debugging-port=9333','--user-data-dir=/tmp/cdp-profile','about:blank'], {stdio:'ignore'});
const sleep = ms => new Promise(r=>setTimeout(r,ms));
function getJson(path, method){ return new Promise((res,rej)=>{ const req=http.request({host:'127.0.0.1',port:9333,path,method:method||'GET'},r=>{let d='';r.on('data',c=>d+=c);r.on('end',()=>{try{res(JSON.parse(d))}catch(e){rej(d)}})}); req.on('error',rej); req.end(); }); }
(async()=>{
  for (let i=0;i<40;i++){ try{ await getJson('/json/version'); break;}catch(e){ await sleep(300);} }
  const target = await getJson('/json/new?'+encodeURIComponent(URL),'PUT');
  const ws = new WebSocket(target.webSocketDebuggerUrl);
  let id=0; const pending={};
  ws.onmessage = e => { const m=JSON.parse(e.data); if(m.id&&pending[m.id]){pending[m.id](m);delete pending[m.id];} };
  const send=(method,params)=>new Promise(r=>{ const mid=++id; pending[mid]=r; ws.send(JSON.stringify({id:mid,method,params:params||{}})); });
  await new Promise(r=>ws.onopen=r);
  await send('Page.enable'); await send('Runtime.enable');
  const ev = async (expr) => { const r = await send('Runtime.evaluate',{expression:expr,awaitPromise:true,returnByValue:true}); return r.result && r.result.result ? r.result.result.value : JSON.stringify(r); };
  await sleep(4000);
  for (let i=0;i<40;i++){ const ok = await ev(`!!(window.ScrollTrigger) && (function(v){return !!v && v.readyState>=2})(document.querySelector('#hiw-phone video'))`); if(ok===true) break; await sleep(500); }
  const info = await ev(`(function(){var s=document.getElementById('come-funziona').getBoundingClientRect().top+scrollY; var p=document.getElementById('hiw-phone-pin').getBoundingClientRect(); var v=document.querySelector('#hiw-phone video'); return JSON.stringify({sectionTop:Math.round(s), phoneStart:Math.round(p.top+scrollY+p.height/2-innerHeight/2), vh:innerHeight, st: !!window.ScrollTrigger, rs: v&&v.readyState, dur: v&&v.duration, motion: document.documentElement.className})})()`);
  console.log('INFO', info);
  const {sectionTop, phoneStart, vh} = JSON.parse(info);
  const shots = {'00-hero':0, '01-handoff': Math.round((phoneStart+sectionTop)/2), '02-q0': sectionTop, '03-q033': sectionTop+vh, '04-q066': sectionTop+2*vh, '05-q1': sectionTop+3*vh, '06-after': sectionTop+3*vh+600};
  for (const [name,y] of Object.entries(shots)){
    await ev(`window.scrollTo(0, ${y}); 'ok'`); await sleep(500); await ev(`window.scrollTo(0, ${y}); 'ok'`); await sleep(1800);
    const dbg = await ev(`(function(){var v=document.querySelector('#hiw-phone video');var ph=document.getElementById('hiw-phone');var a=document.querySelector('[data-step].is-active');var r=ph.getBoundingClientRect();var sec=document.getElementById('come-funziona').getBoundingClientRect().top, stk=document.querySelector('.how-it-works__sticky').getBoundingClientRect().top, ttl=document.querySelector('.how-it-works__title').getBoundingClientRect().top, pe=document.getElementById('hiw-phone-pin'); return JSON.stringify({y:scrollY,sec:Math.round(sec),stk:Math.round(stk),ttl:Math.round(ttl),pos:getComputedStyle(pe).position,st2:document.getElementById('come-funziona').classList.contains('is-state-2'),t:v&&v.currentTime.toFixed(2),tr:ph.style.transform,step:a&&a.dataset.step,phoneBox:[Math.round(r.left),Math.round(r.top),Math.round(r.width),Math.round(r.height)]})})()`);
    const shot = await send('Page.captureScreenshot',{format:'png'});
    fs.writeFileSync('/tmp/cdp-'+name+'.png', Buffer.from(shot.result.data,'base64'));
    console.log(name, y, dbg);
  }
  ws.close(); chrome.kill(); process.exit(0);
})().catch(e=>{console.error('ERR',e); chrome.kill(); process.exit(1);});
