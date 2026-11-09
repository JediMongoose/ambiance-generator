<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Simon Hall</title>
<style>
:root{--bg:#05040c;--fg:#e9e4ff;--glass:rgba(255,255,255,.08);--edge:rgba(255,255,255,.22);color-scheme:dark;box-sizing:border-box;padding-top:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px)}
html,body{height:100%;margin:0;background:var(--bg);overflow:hidden;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;color:var(--fg)}
canvas{position:fixed;inset:0;width:100%;height:100%;display:block}
#ui{position:fixed;left:0;right:0;bottom:calc(36px + env(safe-area-inset-bottom,0px));display:flex;flex-direction:column;align-items:center;gap:14px;transition:opacity 1.6s ease;pointer-events:none}
#ui.hide{opacity:0}
h1{font-weight:200;letter-spacing:.7em;font-size:13px;margin:0;opacity:.7;text-indent:.7em}
button{pointer-events:auto;cursor:pointer;color:var(--fg);background:var(--glass);border:1px solid var(--edge);border-radius:999px;padding:8px 18px;font-size:11px;letter-spacing:.2em;text-transform:uppercase;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);transition:background .6s,box-shadow .6s,transform .6s;display:flex;align-items:center;gap:9px}
button:hover{background:rgba(255,255,255,.16);box-shadow:0 0 40px rgba(160,140,255,.35)}
button:focus-visible{outline:2px solid #b9a8ff;outline-offset:3px}
.hint{margin:0;font-size:11px;letter-spacing:.2em;opacity:.45;font-weight:300}
.dot{width:6px;height:6px;border-radius:50%;background:var(--fg);opacity:.5}
button.on .dot{opacity:1;animation:pulse 4s ease-in-out infinite;box-shadow:0 0 12px #b9a8ff}
@keyframes pulse{50%{transform:scale(1.8);opacity:.5}}
</style>
</head>
<body>
<canvas id="c"></canvas>
<div id="ui">
  <button id="btn" aria-pressed="false"><span class="dot"></span><span id="lbl">Enable sound</span></button>
</div>
<script>
(function(){
const cv=document.getElementById('c'),g=cv.getContext('2d');
let W,H,DPR,t=0,mx=.5,my=.5,tx=.5,ty=.5,px=0,py=0,pact=false,pdown=false,lx=0,ly=0,ln=0;
const ripples=[],comets=[],rays=[],sparks=[],bangs=[],cool={};
const fx={zoom:0,rot:0,mirror:0,fs:3.1,v:1.1,hs:1,sp:22,sat:80,lobes:3,mv:0};
const tg=Object.assign({},fx);
let hueAcc=0;
const R_=(a,b)=>a+Math.random()*(b-a);
function reshuffle(){
  tg.zoom=Math.random()<.5?0:R_(-.0007,.001);
  tg.rot=Math.random()<.5?0:R_(-.0006,.0006);
  tg.mirror=0;
  tg.mv=Math.random()<.5?1:0;
  tg.fs=R_(2.4,3.8);tg.v=R_(.7,1.3);tg.hs=R_(.4,1.4);tg.sp=R_(15,60);tg.sat=R_(65,85);
  tg.lobes=Math.floor(R_(3,6));
  setTimeout(reshuffle,R_(14000,28000));
}
function burst(){
  const r=Math.random();
  if(r<.5){const n=Math.floor(R_(3,5)),h=Math.random()*360;for(let i=0;i<n;i++)setTimeout(()=>ripple(.5,.5,(h+i*25)%360),i*320);try{pluck(Math.random(),.4)}catch(e){}}
  else if(r<1.01){const n=Math.floor(R_(1,3));for(let i=0;i<n;i++)comets.push({a:Math.random()*6.28,r:R_(.1,.45),w:R_(.25,.6)*(Math.random()<.5?-1:1),life:1,h:Math.random()*360,g:R_(.02,.06)})}
  else{rays.push({a:Math.random()*6.28,n:Math.floor(R_(8,24)),life:1,h:Math.random()*360,w:R_(-.2,.2)})}
  setTimeout(burst,R_(14000,28000));
}
setTimeout(reshuffle,9000);setTimeout(burst,6000);
function size(){DPR=Math.min(window.devicePixelRatio||1,2);W=cv.width=innerWidth*DPR;H=cv.height=innerHeight*DPR;g.fillStyle='#05040c';g.fillRect(0,0,W,H)}
addEventListener('resize',size);size();

// particles drifting through a slow flow field
const N=Math.round(Math.min(380,innerWidth*innerHeight/4500));
const P=Array.from({length:N},()=>({x:Math.random()*W,y:Math.random()*H,l:Math.random()*300}));
function field(x,y,t){
  const a=x/W*fx.fs,b=y/H*fx.fs;
  return Math.sin(a+t*.11)*2+Math.cos(b*1.3-t*.09)*2+Math.sin((a+b)*.8+t*.07)*1.5;
}
function frame(){
  t+=.016;
  for(const k in fx)fx[k]+=(tg[k]-fx[k])*.006;
  mx+=(tx-mx)*.02;my+=(ty-my)*.02;
  g.globalCompositeOperation='source-over';
  if(Math.abs(fx.zoom)>.0002||Math.abs(fx.rot)>.0002){g.save();g.globalAlpha=.97;g.translate(W/2,H/2);g.rotate(fx.rot);const s=1+fx.zoom;g.scale(s,s);g.translate(-W/2,-H/2);g.drawImage(cv,0,0);g.restore()}
  if(fx.mirror>.03){g.save();g.globalAlpha=fx.mirror;if(fx.mv>.5){g.translate(0,H);g.scale(1,-1);g.drawImage(cv,0,0,W,H/2,0,0,W,H/2)}else{g.translate(W,0);g.scale(-1,1);g.drawImage(cv,0,0,W/2,H,0,0,W/2,H)}g.restore()}
  g.fillStyle='rgba(5,4,12,.09)';g.fillRect(0,0,W,H);
  g.globalCompositeOperation='lighter';
  hueAcc+=.1*fx.hs;const hue0=hueAcc%360;
  g.lineWidth=1.2*DPR;
  for(const p of P){
    const a=field(p.x,p.y,t);
    let vx=Math.cos(a)*fx.v*DPR,vy=Math.sin(a)*fx.v*DPR;
    if(pact){const dx=px-p.x,dy=py-p.y,d2=dx*dx+dy*dy,rr=(300*DPR)*(300*DPR);
      if(d2<rr){const d=Math.sqrt(d2)+1,f=(1-d2/rr)*(pdown?2.8:1.4)*DPR;
        vx+=(-dy/d)*f+(pdown?dx/d*f*1.3:0);vy+=(dx/d)*f+(pdown?dy/d*f*1.3:0);}}
    const nx=p.x+vx,ny=p.y+vy;
    g.strokeStyle='hsla('+((hue0+a*fx.sp+p.x/W*fx.sp*3.6)%360)+','+fx.sat+'%,62%,.16)';
    g.beginPath();g.moveTo(p.x,p.y);g.lineTo(nx,ny);g.stroke();
    p.x=nx;p.y=ny;p.l--;
    if(p.l<0||nx<0||nx>W||ny<0||ny>H){p.x=Math.random()*W;p.y=Math.random()*H;p.l=200+Math.random()*300}
  }
  // breathing mandala of rotating rings
  const cx=W*(.5+(mx-.5)*.08),cy=H*(.5+(my-.5)*.08),R=Math.min(W,H)*.34;
  const breath=1+Math.sin(t*.35)*.08;
  g.lineWidth=1.4*DPR;
  const ringR=(k,th,ph)=>{const L=Math.floor(fx.lobes),w=fx.lobes-L,m=Math.sin(t*.2+k);
    return R*breath*(.35+k*.09)*(1+.22*m*((1-w)*Math.sin(th*(L+k%3)+ph*2)+w*Math.sin(th*(L+1+k%3)+ph*2)))};
  for(let k=0;k<4;k++){
    const ph=t*.12*(k%2?1:-1)+k*.9;
    g.strokeStyle='hsla('+((hue0+k*38+180)%360)+',85%,68%,.10)';
    g.beginPath();
    for(let i=0;i<=240;i++){
      const th=i/240*Math.PI*2;
      const r=ringR(k,th,ph);
      const x=cx+Math.cos(th+ph)*r,y=cy+Math.sin(th+ph)*r;
      i?g.lineTo(x,y):g.moveTo(x,y);
    }
    g.closePath();g.stroke();
  }
  // orbs travelling along the rings
  const orbs=[];
  for(let k=0;k<4;k++){
    const ph=t*.12*(k%2?1:-1)+k*.9;
    for(let j=0;j<(k<2?1:2);j++){
      const th=t*(.18+k*.05)*(k%2?-1:1)+j*Math.PI+k*1.7,r=ringR(k,th,ph);
      const x=cx+Math.cos(th+ph)*r,y=cy+Math.sin(th+ph)*r,h=(hue0+k*38+180)%360,rad=(1.3+k*.25)*DPR;
      orbs.push({x,y,h,id:k*2+j});
      const og=g.createRadialGradient(x,y,0,x,y,rad*4);
      og.addColorStop(0,'hsla('+h+',90%,85%,.9)');og.addColorStop(.3,'hsla('+h+',90%,70%,.35)');og.addColorStop(1,'rgba(0,0,0,0)');
      g.fillStyle=og;g.fillRect(x-rad*4,y-rad*4,rad*8,rad*8);
    }
  }
  // orb collisions: small burst of sparks plus a soft chime
  for(let i=0;i<orbs.length;i++)for(let j=i+1;j<orbs.length;j++){
    const A=orbs[i],B=orbs[j];
    if(Math.hypot(A.x-B.x,A.y-B.y)<20*DPR){
      const key=A.id*10+B.id;
      if(!(cool[key]>t)){cool[key]=t+4;
        const bx=(A.x+B.x)/2,by=(A.y+B.y)/2,bh=(A.h+B.h)/2;
        for(let n=0;n<22;n++){const an=Math.random()*6.283,sv=R_(.4,1.8)*DPR;sparks.push({x:bx,y:by,vx:Math.cos(an)*sv,vy:Math.sin(an)*sv,life:1,h:bh+R_(-30,30)})}
        bangs.push({x:bx,y:by,r:0,a:1,h:bh});
        const xn=Math.min(.98,Math.max(.02,bx/W));
        try{pluck(xn,.75);setTimeout(()=>{try{pluck(Math.min(.98,xn+.18),.8)}catch(e){}},140)}catch(e){}
      }
    }
  }
  for(let i=sparks.length-1;i>=0;i--){const s=sparks[i];s.x+=s.vx;s.y+=s.vy;s.vx*=.96;s.vy*=.96;s.life-=.022;if(s.life<=0){sparks.splice(i,1);continue}
    g.fillStyle='hsla('+s.h+',95%,78%,'+s.life*.9+')';g.beginPath();g.arc(s.x,s.y,(1+s.life*1.4)*DPR,0,7);g.fill()}
  for(let i=bangs.length-1;i>=0;i--){const b=bangs[i];b.r+=1.1*DPR;b.a-=.03;if(b.a<=0){bangs.splice(i,1);continue}
    g.lineWidth=1.5*DPR;g.strokeStyle='hsla('+b.h+',95%,80%,'+b.a*.6+')';g.beginPath();g.arc(b.x,b.y,b.r,0,7);g.stroke()}
  // soft glowing core
  const gr=g.createRadialGradient(cx,cy,0,cx,cy,R*.5*breath);
  gr.addColorStop(0,'hsla('+((hue0+260)%360)+',90%,70%,.10)');gr.addColorStop(1,'rgba(0,0,0,0)');
  g.fillStyle=gr;g.fillRect(cx-R,cy-R,R*2,R*2);
  if(pact){const hr=(pdown?95:55)*DPR*(1+Math.sin(t*3)*.08);const hg=g.createRadialGradient(px,py,0,px,py,hr);hg.addColorStop(0,'hsla('+((hue0+120)%360)+',90%,75%,'+(pdown?.32:.16)+')');hg.addColorStop(1,'rgba(0,0,0,0)');g.fillStyle=hg;g.fillRect(px-hr,py-hr,hr*2,hr*2)}
  for(let i=rays.length-1;i>=0;i--){const q=rays[i];q.life-=.004;q.a+=q.w*.01;if(q.life<=0){rays.splice(i,1);continue}
    g.lineWidth=1.2*DPR;g.strokeStyle='hsla('+q.h+',90%,70%,'+(Math.sin(q.life*Math.PI)*.35)+')';
    const Rm=Math.hypot(W,H)*.6*(1-q.life*.3);g.beginPath();
    for(let j=0;j<q.n;j++){const an=q.a+j/q.n*6.283;g.moveTo(cx,cy);g.lineTo(cx+Math.cos(an)*Rm,cy+Math.sin(an)*Rm)}
    g.stroke()}
  for(let i=comets.length-1;i>=0;i--){const c=comets[i];c.life-=.0025;c.a+=c.w*.02;c.r+=c.g*.002;if(c.life<=0){comets.splice(i,1);continue}
    const m=Math.min(W,H),x=cx+Math.cos(c.a)*c.r*m*(1+Math.sin(t)*.05),y=cy+Math.sin(c.a)*c.r*m;
    const al=Math.sin(c.life*Math.PI),cr=(1.2+al*1.6)*DPR;
    const cg=g.createRadialGradient(x,y,0,x,y,cr*3);cg.addColorStop(0,'hsla('+c.h+',95%,80%,'+al*.8+')');cg.addColorStop(1,'rgba(0,0,0,0)');
    g.fillStyle=cg;g.fillRect(x-cr*3,y-cr*3,cr*6,cr*6)}
  // ripples (born from bell notes and taps)
  for(let i=ripples.length-1;i>=0;i--){
    const r=ripples[i];r.r+=r.s*DPR;r.a-=.0035;
    if(r.a<=0){ripples.splice(i,1);continue}
    g.lineWidth=2*DPR;
    g.strokeStyle='hsla('+r.h+',90%,72%,'+(r.a*.5)+')';
    g.beginPath();g.arc(r.x,r.y,r.r,0,7);g.stroke();
    g.strokeStyle='hsla('+((r.h+40)%360)+',90%,72%,'+(r.a*.25)+')';
    g.beginPath();g.arc(r.x,r.y,r.r*.7,0,7);g.stroke();
  }
  requestAnimationFrame(frame);
}
frame();
function ripple(x,y,h){ripples.push({x:x*W,y:y*H,r:0,s:.9+Math.random()*.5,a:1,h:h==null?(t*6+Math.random()*80)%360:h})}
addEventListener('pointermove',e=>{
  tx=e.clientX/innerWidth;ty=e.clientY/innerHeight;px=e.clientX*DPR;py=e.clientY*DPR;pact=true;
  if(pdown){const now=performance.now();
    if(Math.hypot(e.clientX-lx,e.clientY-ly)>80&&now-ln>110){lx=e.clientX;ly=e.clientY;ln=now;const h=pluck(tx,ty);ripple(tx,ty,h)}}
  wake()});
addEventListener('pointerdown',e=>{
  if(e.target.closest('button'))return;
  const xn=e.clientX/innerWidth,yn=e.clientY/innerHeight;
  px=e.clientX*DPR;py=e.clientY*DPR;pact=true;pdown=true;lx=e.clientX;ly=e.clientY;ln=performance.now();
  const h=pluck(xn,yn);ripple(xn,yn,h);wake()});
function up(e){pdown=false;if(e.pointerType==='touch')pact=false}
addEventListener('pointerup',up);addEventListener('pointercancel',up);
document.documentElement.addEventListener('pointerleave',()=>{pact=false;pdown=false});

// UI fade when idle
const ui=document.getElementById('ui');let idle;
function wake(){ui.classList.remove('hide');clearTimeout(idle);idle=setTimeout(()=>ui.classList.add('hide'),5000)}
wake();

// ---------- generative audio ----------
let ctx,master,verb,dly,running=false,timers=[],ci=0;
const chords=[[45,57,60,64,67,71],[41,57,60,64,69,72],[48,55,59,64,67,71],[43,55,62,64,69,74]];
const bells=[69,72,74,76,79,81,84,88];
const mf=m=>440*Math.pow(2,(m-69)/12);
function impulse(sec){
  const n=ctx.sampleRate*sec,b=ctx.createBuffer(2,n,ctx.sampleRate);
  for(let c=0;c<2;c++){const d=b.getChannelData(c);for(let i=0;i<n;i++)d[i]=(Math.random()*2-1)*Math.pow(1-i/n,3.2)}
  return b;
}
function build(){
  ctx=new (window.AudioContext||window.webkitAudioContext)();
  master=ctx.createGain();master.gain.value=0;
  const comp=ctx.createDynamicsCompressor();
  master.connect(comp);comp.connect(ctx.destination);
  verb=ctx.createConvolver();verb.buffer=impulse(7);
  const vg=ctx.createGain();vg.gain.value=.8;verb.connect(vg);vg.connect(master);
  dly=ctx.createDelay(2);dly.delayTime.value=.62;
  const fb=ctx.createGain();fb.gain.value=.45;
  const dl=ctx.createBiquadFilter();dl.type='lowpass';dl.frequency.value=1800;
  dly.connect(dl);dl.connect(fb);fb.connect(dly);dl.connect(verb);
}
function pad(m,when,dur){
  const out=ctx.createGain(),f=ctx.createBiquadFilter();
  f.type='lowpass';f.frequency.value=500+Math.random()*300;f.Q.value=.7;
  const lfo=ctx.createOscillator(),lg=ctx.createGain();
  lfo.frequency.value=.04+Math.random()*.08;lg.gain.value=260;
  lfo.connect(lg);lg.connect(f.frequency);lfo.start(when);lfo.stop(when+dur+2);
  const peak=m<48?.16:.07,att=dur*.35,rel=dur*.4;
  out.gain.setValueAtTime(0,when);
  out.gain.linearRampToValueAtTime(peak,when+att);
  out.gain.setValueAtTime(peak,when+dur-rel);
  out.gain.linearRampToValueAtTime(0,when+dur);
  [-7,0,6].forEach((det,i)=>{
    const o=ctx.createOscillator();o.type=i==1?'sine':'triangle';
    o.frequency.value=mf(m);o.detune.value=det;
    o.connect(f);o.start(when);o.stop(when+dur+.1);
  });
  f.connect(out);out.connect(master);out.connect(verb);
}
function bell(){
  if(!running)return;
  const m=bells[Math.floor(Math.random()*bells.length)],now=ctx.currentTime+.05;
  const o=ctx.createOscillator(),o2=ctx.createOscillator(),gn=ctx.createGain(),pan=ctx.createStereoPanner();
  o.type='sine';o2.type='sine';o.frequency.value=mf(m);o2.frequency.value=mf(m)*2.01;
  const v=.05+Math.random()*.04;
  gn.gain.setValueAtTime(0,now);gn.gain.linearRampToValueAtTime(v,now+.02);
  gn.gain.exponentialRampToValueAtTime(.0001,now+5);
  const g2=ctx.createGain();g2.gain.value=.25;
  const px=Math.random();pan.pan.value=px*2-1;
  o.connect(gn);o2.connect(g2);g2.connect(gn);gn.connect(pan);
  pan.connect(master);pan.connect(dly);pan.connect(verb);
  o.start(now);o2.start(now);o.stop(now+5.2);o2.stop(now+5.2);
  setTimeout(()=>ripple(px,.25+Math.random()*.5,((m-60)*9+t*6)%360),50);
  timers.push(setTimeout(bell,1800+Math.random()*4200));
}
const scale=[57,60,62,64,67,69,72,74,76,79,81,84,88];
function pluck(xn,yn){
  if(!running)return;
  const m=scale[Math.min(scale.length-1,Math.floor(xn*scale.length))],now=ctx.currentTime+.02;
  const o=ctx.createOscillator(),o2=ctx.createOscillator(),gn=ctx.createGain(),g2=ctx.createGain(),pan=ctx.createStereoPanner();
  o.type='sine';o2.type='triangle';o.frequency.value=mf(m);o2.frequency.value=mf(m)*2;g2.gain.value=.2;
  const v=.05+(1-yn)*.09;
  gn.gain.setValueAtTime(0,now);gn.gain.linearRampToValueAtTime(v,now+.015);gn.gain.exponentialRampToValueAtTime(.0001,now+3.5);
  pan.pan.value=xn*2-1;
  o.connect(gn);o2.connect(g2);g2.connect(gn);gn.connect(pan);pan.connect(master);pan.connect(dly);pan.connect(verb);
  o.start(now);o2.start(now);o.stop(now+3.6);o2.stop(now+3.6);
  return ((m-57)*8+t*6)%360;
}
function chord(){
  if(!running)return;
  const c=chords[ci++%chords.length],now=ctx.currentTime+.05,dur=16;
  c.forEach((m,i)=>pad(m,now+i*.4,dur));
  timers.push(setTimeout(chord,11500));
}
async function start(){
  if(!ctx)build();
  await ctx.resume();running=true;
  master.gain.cancelScheduledValues(ctx.currentTime);
  master.gain.setValueAtTime(master.gain.value,ctx.currentTime);
  master.gain.linearRampToValueAtTime(.8,ctx.currentTime+4);
  chord();timers.push(setTimeout(bell,3000));
}
function stop(){
  running=false;timers.forEach(clearTimeout);timers=[];
  const n=ctx.currentTime;
  master.gain.cancelScheduledValues(n);master.gain.setValueAtTime(master.gain.value,n);
  master.gain.linearRampToValueAtTime(0,n+2.5);
  setTimeout(()=>{if(!running)ctx.suspend()},2700);
}
const btn=document.getElementById('btn'),lbl=document.getElementById('lbl');
btn.addEventListener('click',async()=>{
  if(!running){await start();btn.classList.add('on');btn.setAttribute('aria-pressed','true');lbl.textContent='Disable sound'}
  else{stop();btn.classList.remove('on');btn.setAttribute('aria-pressed','false');lbl.textContent='Enable sound'}
  wake();
});
})();
</script>
</body>
</html>
