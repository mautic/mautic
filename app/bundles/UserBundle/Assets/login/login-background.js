(() => {
const box = document.getElementById('login-background');
const cv = box.querySelector('canvas');
const gl = cv.getContext('webgl2', { antialias: false, depth: false });
if (!gl) return;
const mq = matchMedia('(prefers-reduced-motion: reduce)');
let RM = mq.matches;

const VS = `#version 300 es
void main(){vec2 p=vec2((gl_VertexID<<1)&2,gl_VertexID&2);gl_Position=vec4(p*2.-1.,0,1);}`;

const CELL = `#version 300 es
precision highp float;
uniform vec2 R,O,D,M;uniform float T,P,I,K,Q,Y;uniform vec3 C[10];uniform sampler2D S;
float sq(float x){return x*x;}
out vec4 o;
float h(float n){return fract(sin(n)*43758.5453);}
float vn(vec3 p){
  vec3 i=floor(p),f=fract(p),w=f*f*f*(f*(f*6.-15.)+10.);
  float n=dot(i,vec3(1,57,113));
  return mix(mix(mix(h(n),h(n+1.),w.x),mix(h(n+57.),h(n+58.),w.x),w.y),
             mix(mix(h(n+113.),h(n+114.),w.x),mix(h(n+170.),h(n+171.),w.x),w.y),w.z)*2.-1.;
}
void main(){
  float c=8.*P;
  vec2 cc=(floor(gl_FragCoord.xy)+O)*c,uv=cc/R.y;
  vec3 p=vec3((uv+D)*2.,T*.05);
  float f=0.,q=1.;
  for(int i=0;i<5;i++){f+=vn(p*q);q*=1.25;}
  f*=.25;
  for(int i=0;i<10;i++){
    vec3 k=C[i];if(k.x<0.)continue;
    float t=max(k.z,0.),r=distance(uv,(k.xy-R*.5-c*.5)/R.y);
    float v=exp(-sq((r-.3*t)/.1))*exp(-t)*exp(-10.*r);
    if(v>.02)f=max(f,v);
  }
  if(I<1.){
    float fr=(1.-pow(1.-I,2.4))*(length(vec2(R.x/R.y,1))*.5+.2),r=distance(uv,vec2(-c*.5/R.y));
    f=mix(-1.,f,1.-smoothstep(fr-.06,fr+.04,r));
    float v=exp(-sq((r-fr)/.07))*(1.-I)*1.4;
    if(v>.02)f=max(f,v);
  }
  f=max(f,0.);
  vec2 d=(cc+c*.5+R*.5-M)/K;
  float tr=max(exp(-dot(d,d))*Q,max(texelFetch(S,ivec2(gl_FragCoord.xy),0).a*Y-1./255.,0.));
  f+=tr*.9;
  o=vec4(clamp((f+.5)*.5,0.,1.),0,0,tr);
}`;

const MAIN = `#version 300 es
precision highp float;
uniform vec2 R,O,N;uniform float P,W,Z;uniform sampler2D S;uniform vec3 B,L;
out vec4 o;
float b(vec2 v){v=floor(v);return fract(v.x*.5+v.y*v.y*.75);}
vec2 s5(vec2 x){return x*x*x*(x*(x*6.-15.)+10.);}
void main(){
  vec2 fc=gl_FragCoord.xy-R*.5;
  float f=texture(S,(fc/(8.*P)-O)/N).r*2.-.5;
  if(f<-.072){o=vec4(0);return;}
  vec2 id=floor(fc/P),uv=fc/P-id,m=mod(id,8.);
  float by=(b(m/4.)*.25+b(m/2.))*.25+b(m)-.5,
        bw=smoothstep(-.072,.072,f+by-.5),
        hh=fract(sin(dot(id,vec2(127.1,311.7)))*43758.5453),
        ht=clamp((f-1.)*2.5,0.,1.)*.4;
  vec2 q=abs(uv-.5)-.5*sqrt(clamp(bw*(1.+ht*.35),0.,1.));
  float a=.5/P,Mk=1.-smoothstep(-a,a,max(q.x,q.y));
  float w=.5+.5*sin(Z*(1.5+3.*fract(hh*7.31))+hh*37.);
  Mk*=1.-W*.55*w*w;
  float dn=clamp(f,0.,1.),bl=smoothstep(.2,1.,dn),g=bl*bl*.024;
  vec2 n=gl_FragCoord.xy/R,e=s5(clamp(n*2.,0.,1.))*s5(clamp((1.-n)*2.,0.,1.));
  float fd=e.x*e.y;Mk*=fd;g*=fd;
  vec3 col=mix(B,L,max(smoothstep(.3,1.,dn)*.85,ht));
  o=vec4(col*(1.-.35*ht)*Mk+mix(col,vec3(1),.35)*g*(1.-Mk),Mk+g*(1.-Mk));
}`;

const prog = fs => {
  const p = gl.createProgram(), u = {};
  for (const [t, s] of [[gl.VERTEX_SHADER, VS], [gl.FRAGMENT_SHADER, fs]]) {
    const x = gl.createShader(t); gl.shaderSource(x, s); gl.compileShader(x); gl.attachShader(p, x);
  }
  gl.linkProgram(p);
  if (!gl.getProgramParameter(p, gl.LINK_STATUS)) return null;
  for (let i = gl.getProgramParameter(p, gl.ACTIVE_UNIFORMS); i--;) {
    const n = gl.getActiveUniform(p, i).name.replace('[0]', '');
    u[n] = gl.getUniformLocation(p, n);
  }
  return [p, u];
};
const cell = prog(CELL), main = prog(MAIN);
if (!cell || !main) return;
const [cp, cu] = cell, [mp, mu] = main;
const rgb = v => {
  const h = /^#([0-9a-f]{6})$/i.exec(v);
  if (h) { const n = parseInt(h[1], 16); return [n >> 16 & 255, n >> 8 & 255, n & 255]; }
  const m = v.match(/\d+(\.\d+)?/g);
  return m && m.length >= 3 ? m.slice(0, 3).map(Number) : [78, 93, 157];
};
const base = rgb(getComputedStyle(box).getPropertyValue('--login-background-color').trim()).map(c => c / 255);
const light = base.map(c => c + (1 - c) * 0.4);

const T2 = gl.TEXTURE_2D;
const tg = [0, 1].map(() => {
  const t = gl.createTexture();
  gl.bindTexture(T2, t);
  for (const [k, v] of [[gl.TEXTURE_MIN_FILTER, gl.LINEAR], [gl.TEXTURE_MAG_FILTER, gl.LINEAR],
    [gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE], [gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE]]) gl.texParameteri(T2, k, v);
  return { t, f: gl.createFramebuffer() };
});

let W, H, r, vh, cx, cy, cw, ch, mx, my;
let cur = 0, el = 0, clk = 0, pr = 0, dx = 0, dy = 0, it = RM ? 1 : 0, last = 0;
let px = 0, py = 0, inside = false, ci = 0;
const T0 = Math.random() * 1000;
const clicks = new Float32Array(30).fill(-1), at = new Float32Array(10);

const resize = () => {
  r = Math.min(devicePixelRatio || 1, 2);
  vh = box.clientHeight;
  W = cv.width = Math.max(1, Math.round(box.clientWidth * r));
  H = cv.height = Math.max(1, Math.round(vh * r));
  if (mx === undefined) { mx = W / 2; my = H / 2; }
  const c = 32 * r;
  cx = Math.floor((-W / 2 - c) / c); cy = Math.floor((-H / 2 - c) / c);
  cw = Math.min(2048, Math.floor((W / 2 + c) / c) - cx + 1);
  ch = Math.min(2048, Math.floor((H / 2 + c) / c) - cy + 1);
  for (const { t, f } of tg) {
    gl.bindTexture(T2, t);
    gl.texImage2D(T2, 0, gl.RGBA8, cw, ch, 0, gl.RGBA, gl.UNSIGNED_BYTE, null);
    gl.bindFramebuffer(gl.FRAMEBUFFER, f);
    gl.framebufferTexture2D(gl.FRAMEBUFFER, gl.COLOR_ATTACHMENT0, T2, t, 0);
  }
  if (RM) { settle = 2; schedule(); }
};

let lost = false, queued = false, settle = 2;
const schedule = () => { if (!queued && !lost) { queued = true; requestAnimationFrame(frame); } };
cv.addEventListener('webglcontextlost', () => { lost = true; });

const frame = now => {
  queued = false;
  if (lost) return;
  if (!RM || --settle > 0) schedule();
  const dt = last ? Math.min(Math.max((now - last) / 1000, 0), 0.05) : 0;
  last = now;
  clk += dt;
  if (!RM) { el += dt; dx += 0.004330127 * dt; dy += 0.0025 * dt; }
  for (let k = 0; k < 10; k++) clicks[k * 3 + 2] = (clk - at[k]) * 0.5;
  const fl = 1 - Math.exp(-dt / 0.08);
  if (inside) { mx += (px * r - mx) * fl; my += ((vh - py) * r - my) * fl; }
  pr += ((inside ? 1 : 0) - pr) * (1 - Math.exp(-dt / 0.25));

  gl.useProgram(cp);
  gl.bindFramebuffer(gl.FRAMEBUFFER, tg[1 - cur].f);
  gl.viewport(0, 0, cw, ch);
  gl.bindTexture(T2, tg[cur].t);
  gl.uniform2f(cu.R, W, H); gl.uniform2f(cu.O, cx, cy);
  gl.uniform2f(cu.D, dx, dy); gl.uniform2f(cu.M, mx, my);
  gl.uniform1f(cu.T, T0 + el * 0.3); gl.uniform1f(cu.P, 4 * r);
  gl.uniform1f(cu.I, it); gl.uniform1f(cu.K, Math.max(140 * r, 1));
  gl.uniform1f(cu.Q, pr); gl.uniform1f(cu.Y, Math.exp(-dt / 0.45));
  gl.uniform3fv(cu.C, clicks);
  gl.drawArrays(gl.TRIANGLES, 0, 3);
  cur = 1 - cur;

  gl.useProgram(mp);
  gl.bindFramebuffer(gl.FRAMEBUFFER, null);
  gl.viewport(0, 0, W, H);
  gl.bindTexture(T2, tg[cur].t);
  gl.uniform2f(mu.R, W, H); gl.uniform2f(mu.O, cx, cy); gl.uniform2f(mu.N, cw, ch);
  gl.uniform1f(mu.P, 4 * r); gl.uniform1f(mu.W, RM ? 0 : 0.2); gl.uniform1f(mu.Z, clk);
  gl.uniform3fv(mu.B, base); gl.uniform3fv(mu.L, light);
  gl.drawArrays(gl.TRIANGLES, 0, 3);

  if (it < 1) it = Math.min(1, it + dt / 1.8);
};

const loc = e => {
  const b = box.getBoundingClientRect(), x = e.clientX - b.left, y = e.clientY - b.top;
  return [x, y, x >= 0 && y >= 0 && x <= b.width && y <= b.height];
};
addEventListener('pointermove', e => {
  if (RM) return;
  const [x, y, i] = loc(e);
  if (i) { if (!inside) { mx = x * r; my = (vh - y) * r; } px = x; py = y; }
  inside = i;
}, { passive: true });
addEventListener('pointerdown', e => {
  const [x, y, i] = loc(e);
  if (!i || RM) return;
  clicks[ci * 3] = x * r; clicks[ci * 3 + 1] = (vh - y) * r; at[ci] = clk;
  ci = (ci + 1) % 10;
}, { passive: true });
document.documentElement.addEventListener('pointerleave', () => { inside = false; });

mq.addEventListener('change', e => { RM = e.matches; if (RM) { it = 1; inside = false; settle = 2; } schedule(); });

resize();
new ResizeObserver(resize).observe(box);
schedule();
})();
