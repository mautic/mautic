(() => {
let O = { sm: { dark: { strokeOpacity: 0.46, innerOpacity: 0.24, bloomOpacity: 0.38, innerShadow: "rgba(255, 255, 255, 0.3)", saturation: 1.2 }, light: { strokeOpacity: 0.12, innerOpacity: 0.3, bloomOpacity: 0.16, innerShadow: "rgba(0, 0, 0, 0.14)", saturation: 1.8 } }, md: { dark: { strokeOpacity: 0.26, innerOpacity: 0.42, bloomOpacity: 0.24, innerShadow: "rgba(255, 255, 255, 0.27)", saturation: 1.2 }, light: { strokeOpacity: 0.12, innerOpacity: 0.26, bloomOpacity: 0.34, innerShadow: "rgba(0, 0, 0, 0.14)", saturation: 1.5 } }, line: { dark: { strokeOpacity: 1.14, innerOpacity: 0.7, bloomOpacity: 0.8, innerShadow: "rgba(255, 255, 255, 0.1)", saturation: 1.2 }, light: { strokeOpacity: 0.16, innerOpacity: 0.32, bloomOpacity: 0.3, innerShadow: "rgba(0, 0, 0, 0.14)", saturation: 1.95 } }, "pulse-outside": { dark: { strokeOpacity: 0.94, innerOpacity: 0.34, bloomOpacity: 0.3, innerShadow: "transparent", saturation: 1.2, brightness: 1.9, hairlineOpacity: 0 }, light: { strokeOpacity: 1.96, innerOpacity: 1.04, bloomOpacity: 0.42, innerShadow: "transparent", saturation: 0.6, brightness: 1.7, hairlineOpacity: 0 } }, "pulse-inner": { dark: { strokeOpacity: 1.54, innerOpacity: 0.44, bloomOpacity: 0.66, innerShadow: "transparent", saturation: 1.2, brightness: 0.75 }, light: { strokeOpacity: 0.32, innerOpacity: 0.4, bloomOpacity: 0.8, innerShadow: "transparent", saturation: 0.75, brightness: 1.3 } } }, I = { dark: { ...O.md.dark }, light: { ...O.md.light } }, S = { colorful: { border: [{ color: "rgb(255, 50, 100)", pos: "33% -7.4%", size: "70px 40px" }, { color: "rgb(40, 140, 255)", pos: "12% -5%", size: "60px 35px" }, { color: "rgb(50, 200, 80)", pos: "2.1% 68.3%", size: "40px 70px" }, { color: "rgb(30, 185, 170)", pos: "2.1% 68.3%", size: "20px 35px" }, { color: "rgb(100, 70, 255)", pos: "74.4% 100%", size: "180px 32px" }, { color: "rgb(40, 140, 255)", pos: "55% 100%", size: "85px 26px" }, { color: "rgb(255, 120, 40)", pos: "93.9% 0%", size: "74px 32px" }, { color: "rgb(240, 50, 180)", pos: "100% 27.1%", size: "26px 42px" }, { color: "rgb(180, 40, 240)", pos: "100% 27.1%", size: "52px 48px" }], spike: { primary: "rgb(255, 60, 80)", secondary: "rgba(40, 190, 180, 0.98)" }, spikeLt: { primary: "rgb(200, 30, 60)", secondary: "rgb(20, 150, 140)" } }, mono: { border: [{ color: "rgb(180, 180, 180)", pos: "33% -7.4%", size: "70px 40px" }, { color: "rgb(140, 140, 140)", pos: "12% -5%", size: "60px 35px" }, { color: "rgb(160, 160, 160)", pos: "2.1% 68.3%", size: "40px 70px" }, { color: "rgb(130, 130, 130)", pos: "2.1% 68.3%", size: "20px 35px" }, { color: "rgb(170, 170, 170)", pos: "74.4% 100%", size: "180px 32px" }, { color: "rgb(150, 150, 150)", pos: "55% 100%", size: "85px 26px" }, { color: "rgb(190, 190, 190)", pos: "93.9% 0%", size: "74px 32px" }, { color: "rgb(145, 145, 145)", pos: "100% 27.1%", size: "26px 42px" }, { color: "rgb(165, 165, 165)", pos: "100% 27.1%", size: "52px 48px" }], spike: { primary: "rgb(200, 200, 200)", secondary: "rgb(170, 170, 170)" }, spikeLt: { primary: "rgb(80, 80, 80)", secondary: "rgb(120, 120, 120)" } }, ocean: { border: [{ color: "rgb(100, 80, 220)", pos: "33% -7.4%", size: "70px 40px" }, { color: "rgb(60, 120, 255)", pos: "12% -5%", size: "60px 35px" }, { color: "rgb(80, 100, 200)", pos: "2.1% 68.3%", size: "40px 70px" }, { color: "rgb(50, 140, 220)", pos: "2.1% 68.3%", size: "20px 35px" }, { color: "rgb(120, 80, 255)", pos: "74.4% 100%", size: "180px 32px" }, { color: "rgb(70, 130, 255)", pos: "55% 100%", size: "85px 26px" }, { color: "rgb(140, 100, 240)", pos: "93.9% 0%", size: "74px 32px" }, { color: "rgb(90, 110, 230)", pos: "100% 27.1%", size: "26px 42px" }, { color: "rgb(130, 70, 255)", pos: "100% 27.1%", size: "52px 48px" }], spike: { primary: "rgb(100, 120, 255)", secondary: "rgba(130, 100, 220, 0.98)" }, spikeLt: { primary: "rgb(60, 60, 180)", secondary: "rgb(80, 100, 200)" } }, sunset: { border: [{ color: "rgb(255, 80, 50)", pos: "33% -7.4%", size: "70px 40px" }, { color: "rgb(255, 160, 40)", pos: "12% -5%", size: "60px 35px" }, { color: "rgb(255, 120, 60)", pos: "2.1% 68.3%", size: "40px 70px" }, { color: "rgb(255, 200, 50)", pos: "2.1% 68.3%", size: "20px 35px" }, { color: "rgb(255, 100, 80)", pos: "74.4% 100%", size: "180px 32px" }, { color: "rgb(255, 180, 60)", pos: "55% 100%", size: "85px 26px" }, { color: "rgb(255, 60, 60)", pos: "93.9% 0%", size: "74px 32px" }, { color: "rgb(255, 140, 50)", pos: "100% 27.1%", size: "26px 42px" }, { color: "rgb(255, 90, 70)", pos: "100% 27.1%", size: "52px 48px" }], spike: { primary: "rgb(255, 140, 80)", secondary: "rgba(255, 100, 60, 0.98)" }, spikeLt: { primary: "rgb(200, 80, 40)", secondary: "rgb(220, 120, 30)" } } };
function R(i, e) {
  let a = S[i];
  return e ? a.spike : a.spikeLt;
}
let j = { colorful: { dark: [{ color: "rgb(255, 50, 100)", sizeW: 36, sizeH: 36, offsetX: 0, offsetY: 2 }, { color: "rgb(40, 180, 220)", sizeW: 30, sizeH: 32, offsetX: 39, offsetY: 0 }, { color: "rgb(50, 200, 80)", sizeW: 33, sizeH: 28, offsetX: -36, offsetY: 2 }, { color: "rgb(180, 40, 240)", sizeW: 29, sizeH: 34, offsetX: -54, offsetY: 0 }, { color: "rgb(255, 160, 30)", sizeW: 27, sizeH: 30, offsetX: 51, offsetY: -1 }, { color: "rgb(100, 70, 255)", sizeW: 36, sizeH: 24, offsetX: 21, offsetY: 1 }, { color: "rgb(40, 140, 255)", sizeW: 30, sizeH: 22, offsetX: -21, offsetY: 0 }, { color: "rgb(240, 50, 180)", sizeW: 25, sizeH: 28, offsetX: 66, offsetY: 1 }, { color: "rgb(30, 185, 170)", sizeW: 23, sizeH: 30, offsetX: -66, offsetY: -1 }], light: [{ color: "rgb(255, 50, 100)", sizeW: 45, sizeH: 36, offsetX: 0, offsetY: 2 }, { color: "rgb(40, 140, 255)", sizeW: 35, sizeH: 32, offsetX: 65, offsetY: 0 }, { color: "rgb(50, 200, 80)", sizeW: 40, sizeH: 28, offsetX: -60, offsetY: 2 }, { color: "rgb(180, 40, 240)", sizeW: 35, sizeH: 34, offsetX: -90, offsetY: 0 }, { color: "rgb(30, 185, 170)", sizeW: 38, sizeH: 30, offsetX: 85, offsetY: -1 }, { color: "rgb(100, 70, 255)", sizeW: 50, sizeH: 24, offsetX: 35, offsetY: 1 }, { color: "rgb(40, 140, 255)", sizeW: 40, sizeH: 22, offsetX: -35, offsetY: 0 }, { color: "rgb(255, 120, 40)", sizeW: 35, sizeH: 28, offsetX: 110, offsetY: 1 }, { color: "rgb(240, 50, 180)", sizeW: 30, sizeH: 30, offsetX: -110, offsetY: -1 }] }, mono: { dark: [{ color: "rgb(200, 200, 200)", sizeW: 36, sizeH: 36, offsetX: 0, offsetY: 2 }, { color: "rgb(170, 170, 170)", sizeW: 30, sizeH: 32, offsetX: 39, offsetY: 0 }, { color: "rgb(155, 155, 155)", sizeW: 33, sizeH: 28, offsetX: -36, offsetY: 2 }, { color: "rgb(185, 185, 185)", sizeW: 29, sizeH: 34, offsetX: -54, offsetY: 0 }, { color: "rgb(165, 165, 165)", sizeW: 27, sizeH: 30, offsetX: 51, offsetY: -1 }, { color: "rgb(180, 180, 180)", sizeW: 36, sizeH: 24, offsetX: 21, offsetY: 1 }, { color: "rgb(160, 160, 160)", sizeW: 30, sizeH: 22, offsetX: -21, offsetY: 0 }, { color: "rgb(175, 175, 175)", sizeW: 25, sizeH: 28, offsetX: 66, offsetY: 1 }, { color: "rgb(190, 190, 190)", sizeW: 23, sizeH: 30, offsetX: -66, offsetY: -1 }], light: [{ color: "rgb(100, 100, 100)", sizeW: 45, sizeH: 36, offsetX: 0, offsetY: 2 }, { color: "rgb(80, 80, 80)", sizeW: 35, sizeH: 32, offsetX: 65, offsetY: 0 }, { color: "rgb(90, 90, 90)", sizeW: 40, sizeH: 28, offsetX: -60, offsetY: 2 }, { color: "rgb(70, 70, 70)", sizeW: 35, sizeH: 34, offsetX: -90, offsetY: 0 }, { color: "rgb(85, 85, 85)", sizeW: 38, sizeH: 30, offsetX: 85, offsetY: -1 }, { color: "rgb(95, 95, 95)", sizeW: 50, sizeH: 24, offsetX: 35, offsetY: 1 }, { color: "rgb(75, 75, 75)", sizeW: 40, sizeH: 22, offsetX: -35, offsetY: 0 }, { color: "rgb(105, 105, 105)", sizeW: 35, sizeH: 28, offsetX: 110, offsetY: 1 }, { color: "rgb(65, 65, 65)", sizeW: 30, sizeH: 30, offsetX: -110, offsetY: -1 }] }, ocean: { dark: [{ color: "rgb(100, 80, 220)", sizeW: 36, sizeH: 36, offsetX: 0, offsetY: 2 }, { color: "rgb(60, 120, 255)", sizeW: 30, sizeH: 32, offsetX: 39, offsetY: 0 }, { color: "rgb(80, 100, 200)", sizeW: 33, sizeH: 28, offsetX: -36, offsetY: 2 }, { color: "rgb(130, 70, 255)", sizeW: 29, sizeH: 34, offsetX: -54, offsetY: 0 }, { color: "rgb(70, 130, 255)", sizeW: 27, sizeH: 30, offsetX: 51, offsetY: -1 }, { color: "rgb(120, 80, 255)", sizeW: 36, sizeH: 24, offsetX: 21, offsetY: 1 }, { color: "rgb(90, 110, 230)", sizeW: 30, sizeH: 22, offsetX: -21, offsetY: 0 }, { color: "rgb(110, 90, 240)", sizeW: 25, sizeH: 28, offsetX: 66, offsetY: 1 }, { color: "rgb(140, 100, 255)", sizeW: 23, sizeH: 30, offsetX: -66, offsetY: -1 }], light: [{ color: "rgb(80, 60, 200)", sizeW: 45, sizeH: 36, offsetX: 0, offsetY: 2 }, { color: "rgb(50, 100, 220)", sizeW: 35, sizeH: 32, offsetX: 65, offsetY: 0 }, { color: "rgb(70, 90, 190)", sizeW: 40, sizeH: 28, offsetX: -60, offsetY: 2 }, { color: "rgb(110, 60, 220)", sizeW: 35, sizeH: 34, offsetX: -90, offsetY: 0 }, { color: "rgb(60, 110, 230)", sizeW: 38, sizeH: 30, offsetX: 85, offsetY: -1 }, { color: "rgb(100, 70, 240)", sizeW: 50, sizeH: 24, offsetX: 35, offsetY: 1 }, { color: "rgb(80, 100, 210)", sizeW: 40, sizeH: 22, offsetX: -35, offsetY: 0 }, { color: "rgb(90, 80, 225)", sizeW: 35, sizeH: 28, offsetX: 110, offsetY: 1 }, { color: "rgb(120, 90, 245)", sizeW: 30, sizeH: 30, offsetX: -110, offsetY: -1 }] }, sunset: { dark: [{ color: "rgb(255, 100, 60)", sizeW: 36, sizeH: 36, offsetX: 0, offsetY: 2 }, { color: "rgb(255, 180, 50)", sizeW: 30, sizeH: 32, offsetX: 39, offsetY: 0 }, { color: "rgb(255, 140, 70)", sizeW: 33, sizeH: 28, offsetX: -36, offsetY: 2 }, { color: "rgb(255, 80, 80)", sizeW: 29, sizeH: 34, offsetX: -54, offsetY: 0 }, { color: "rgb(255, 200, 60)", sizeW: 27, sizeH: 30, offsetX: 51, offsetY: -1 }, { color: "rgb(255, 120, 50)", sizeW: 36, sizeH: 24, offsetX: 21, offsetY: 1 }, { color: "rgb(255, 160, 80)", sizeW: 30, sizeH: 22, offsetX: -21, offsetY: 0 }, { color: "rgb(255, 90, 60)", sizeW: 25, sizeH: 28, offsetX: 66, offsetY: 1 }, { color: "rgb(255, 70, 70)", sizeW: 23, sizeH: 30, offsetX: -66, offsetY: -1 }], light: [{ color: "rgb(220, 80, 40)", sizeW: 45, sizeH: 36, offsetX: 0, offsetY: 2 }, { color: "rgb(230, 150, 30)", sizeW: 35, sizeH: 32, offsetX: 65, offsetY: 0 }, { color: "rgb(210, 110, 50)", sizeW: 40, sizeH: 28, offsetX: -60, offsetY: 2 }, { color: "rgb(200, 60, 60)", sizeW: 35, sizeH: 34, offsetX: -90, offsetY: 0 }, { color: "rgb(220, 170, 40)", sizeW: 38, sizeH: 30, offsetX: 85, offsetY: -1 }, { color: "rgb(210, 100, 30)", sizeW: 50, sizeH: 24, offsetX: 35, offsetY: 1 }, { color: "rgb(230, 130, 60)", sizeW: 40, sizeH: 22, offsetX: -35, offsetY: 0 }, { color: "rgb(190, 70, 50)", sizeW: 35, sizeH: 28, offsetX: 110, offsetY: 1 }, { color: "rgb(180, 50, 50)", sizeW: 30, sizeH: 30, offsetX: -110, offsetY: -1 }] } };
function M(i, e, a) {
  return j[i][e ? "dark" : "light"].map((r) => {
    let s = r.offsetX === 0 ? "" : r.offsetX > 0 ? ` + ${r.offsetX}px` : ` - ${Math.abs(r.offsetX)}px`, o = r.offsetY === 0 ? "" : r.offsetY > 0 ? ` + ${r.offsetY}px` : ` - ${Math.abs(r.offsetY)}px`;
    return `radial-gradient(ellipse calc(${r.sizeW}px * let(--beam-w-${a})) calc(${r.sizeH}px * let(--beam-h-${a})) at calc(let(--beam-x-${a}) * 100%${s}) calc(100%${o}), ${r.color}, transparent)`;
  }).join(`,
       `);
}
let C = { colorful: [{ color: "rgba(255, 50, 100, 0.48)", sizeW: 33, sizeH: 30, offsetX: 0, offsetY: 0 }, { color: "rgba(40, 180, 220, 0.42)", sizeW: 24, sizeH: 26, offsetX: 39, offsetY: -3 }, { color: "rgba(50, 200, 80, 0.48)", sizeW: 27, sizeH: 24, offsetX: -36, offsetY: 0 }, { color: "rgba(180, 40, 240, 0.42)", sizeW: 23, sizeH: 28, offsetX: -54, offsetY: -2 }, { color: "rgba(255, 160, 30, 0.50)", sizeW: 24, sizeH: 24, offsetX: 51, offsetY: -1 }, { color: "rgba(100, 70, 255, 0.45)", sizeW: 30, sizeH: 20, offsetX: 21, offsetY: 0 }, { color: "rgba(40, 140, 255, 0.40)", sizeW: 25, sizeH: 18, offsetX: -21, offsetY: -2 }, { color: "rgba(240, 50, 180, 0.45)", sizeW: 21, sizeH: 24, offsetX: 66, offsetY: 0 }, { color: "rgba(30, 185, 170, 0.52)", sizeW: 18, sizeH: 26, offsetX: -66, offsetY: -1 }], mono: [{ color: "rgba(200, 200, 200, 0.48)", sizeW: 33, sizeH: 30, offsetX: 0, offsetY: 0 }, { color: "rgba(170, 170, 170, 0.42)", sizeW: 24, sizeH: 26, offsetX: 39, offsetY: -3 }, { color: "rgba(155, 155, 155, 0.48)", sizeW: 27, sizeH: 24, offsetX: -36, offsetY: 0 }, { color: "rgba(185, 185, 185, 0.42)", sizeW: 23, sizeH: 28, offsetX: -54, offsetY: -2 }, { color: "rgba(165, 165, 165, 0.50)", sizeW: 24, sizeH: 24, offsetX: 51, offsetY: -1 }, { color: "rgba(180, 180, 180, 0.45)", sizeW: 30, sizeH: 20, offsetX: 21, offsetY: 0 }, { color: "rgba(160, 160, 160, 0.40)", sizeW: 25, sizeH: 18, offsetX: -21, offsetY: -2 }, { color: "rgba(175, 175, 175, 0.45)", sizeW: 21, sizeH: 24, offsetX: 66, offsetY: 0 }, { color: "rgba(190, 190, 190, 0.52)", sizeW: 18, sizeH: 26, offsetX: -66, offsetY: -1 }], ocean: [{ color: "rgba(100, 80, 220, 0.48)", sizeW: 33, sizeH: 30, offsetX: 0, offsetY: 0 }, { color: "rgba(60, 120, 255, 0.42)", sizeW: 24, sizeH: 26, offsetX: 39, offsetY: -3 }, { color: "rgba(80, 100, 200, 0.48)", sizeW: 27, sizeH: 24, offsetX: -36, offsetY: 0 }, { color: "rgba(130, 70, 255, 0.42)", sizeW: 23, sizeH: 28, offsetX: -54, offsetY: -2 }, { color: "rgba(70, 130, 255, 0.50)", sizeW: 24, sizeH: 24, offsetX: 51, offsetY: -1 }, { color: "rgba(120, 80, 255, 0.45)", sizeW: 30, sizeH: 20, offsetX: 21, offsetY: 0 }, { color: "rgba(90, 110, 230, 0.40)", sizeW: 25, sizeH: 18, offsetX: -21, offsetY: -2 }, { color: "rgba(110, 90, 240, 0.45)", sizeW: 21, sizeH: 24, offsetX: 66, offsetY: 0 }, { color: "rgba(140, 100, 255, 0.52)", sizeW: 18, sizeH: 26, offsetX: -66, offsetY: -1 }], sunset: [{ color: "rgba(255, 100, 60, 0.48)", sizeW: 33, sizeH: 30, offsetX: 0, offsetY: 0 }, { color: "rgba(255, 180, 50, 0.42)", sizeW: 24, sizeH: 26, offsetX: 39, offsetY: -3 }, { color: "rgba(255, 140, 70, 0.48)", sizeW: 27, sizeH: 24, offsetX: -36, offsetY: 0 }, { color: "rgba(255, 80, 80, 0.42)", sizeW: 23, sizeH: 28, offsetX: -54, offsetY: -2 }, { color: "rgba(255, 200, 60, 0.50)", sizeW: 24, sizeH: 24, offsetX: 51, offsetY: -1 }, { color: "rgba(255, 120, 50, 0.45)", sizeW: 30, sizeH: 20, offsetX: 21, offsetY: 0 }, { color: "rgba(255, 160, 80, 0.40)", sizeW: 25, sizeH: 18, offsetX: -21, offsetY: -2 }, { color: "rgba(255, 90, 60, 0.45)", sizeW: 21, sizeH: 24, offsetX: 66, offsetY: 0 }, { color: "rgba(255, 70, 70, 0.52)", sizeW: 18, sizeH: 26, offsetX: -66, offsetY: -1 }] };
function L(i, e) {
  return C[i].map((a) => {
    let r = a.offsetX === 0 ? "" : a.offsetX > 0 ? ` + ${a.offsetX}px` : ` - ${Math.abs(a.offsetX)}px`, s = a.offsetY === 0 ? "" : ` - ${Math.abs(a.offsetY)}px`;
    return `radial-gradient(ellipse calc(${a.sizeW}px * let(--beam-w-${e})) calc(${a.sizeH}px * let(--beam-h-${e})) at calc(let(--beam-x-${e}) * 100%${r}) calc(100%${s}), ${a.color}, transparent)`;
  }).join(`,
    `);
}
let V = { colorful: { dark: { spikes: [{ color1: "rgb(100, 70, 255)", color2: "rgba(100, 70, 255, 1)" }, { color1: "rgba(255, 170, 40, 0.59)", color2: "rgba(255, 170, 40, 0.29)" }, { color1: "rgb(50, 200, 100)", color2: "rgba(50, 200, 100, 1)" }, { color1: "rgba(200, 50, 240, 0.91)", color2: "rgba(200, 50, 240, 0.45)" }, { color1: "rgb(40, 140, 255)", color2: "rgba(40, 140, 255, 1)" }] }, light: { spikes: [{ color1: "rgb(80, 50, 200)", color2: "rgba(80, 50, 200, 0.8)" }, { color1: "rgba(210, 130, 0, 0.7)", color2: "rgba(210, 130, 0, 0.46)" }, { color1: "rgb(30, 160, 70)", color2: "rgba(30, 160, 70, 0.82)" }, { color1: "rgb(160, 30, 190)", color2: "rgba(160, 30, 190, 0.7)" }, { color1: "rgb(30, 100, 200)", color2: "rgba(30, 100, 200, 0.78)" }] } }, mono: { dark: { spikes: [{ color1: "rgb(200, 200, 200)", color2: "rgba(200, 200, 200, 1)" }, { color1: "rgba(180, 180, 180, 0.59)", color2: "rgba(180, 180, 180, 0.29)" }, { color1: "rgb(190, 190, 190)", color2: "rgba(190, 190, 190, 1)" }, { color1: "rgba(170, 170, 170, 0.91)", color2: "rgba(170, 170, 170, 0.45)" }, { color1: "rgb(185, 185, 185)", color2: "rgba(185, 185, 185, 1)" }] }, light: { spikes: [{ color1: "rgb(80, 80, 80)", color2: "rgba(80, 80, 80, 0.8)" }, { color1: "rgba(100, 100, 100, 0.7)", color2: "rgba(100, 100, 100, 0.46)" }, { color1: "rgb(70, 70, 70)", color2: "rgba(70, 70, 70, 0.82)" }, { color1: "rgb(90, 90, 90)", color2: "rgba(90, 90, 90, 0.7)" }, { color1: "rgb(85, 85, 85)", color2: "rgba(85, 85, 85, 0.78)" }] } }, ocean: { dark: { spikes: [{ color1: "rgb(100, 80, 255)", color2: "rgb(100, 80, 255)" }, { color1: "rgba(80, 130, 220, 0.59)", color2: "rgba(80, 130, 220, 0.29)" }, { color1: "rgb(60, 100, 255)", color2: "rgb(60, 100, 255)" }, { color1: "rgba(90, 120, 200, 0.91)", color2: "rgba(90, 120, 200, 0.45)" }, { color1: "rgb(120, 90, 255)", color2: "rgb(120, 90, 255)" }] }, light: { spikes: [{ color1: "rgb(50, 40, 180)", color2: "rgba(50, 40, 180, 0.8)" }, { color1: "rgba(40, 80, 200, 0.7)", color2: "rgba(40, 80, 200, 0.46)" }, { color1: "rgb(30, 50, 190)", color2: "rgba(30, 50, 190, 0.82)" }, { color1: "rgb(60, 90, 180)", color2: "rgba(60, 90, 180, 0.7)" }, { color1: "rgb(70, 60, 200)", color2: "rgba(70, 60, 200, 0.78)" }] } }, sunset: { dark: { spikes: [{ color1: "rgb(255, 100, 80)", color2: "rgb(255, 100, 80)" }, { color1: "rgba(255, 150, 80, 0.59)", color2: "rgba(255, 150, 80, 0.29)" }, { color1: "rgb(255, 80, 60)", color2: "rgb(255, 80, 60)" }, { color1: "rgba(255, 120, 50, 0.91)", color2: "rgba(255, 120, 50, 0.45)" }, { color1: "rgb(255, 140, 70)", color2: "rgb(255, 140, 70)" }] }, light: { spikes: [{ color1: "rgb(200, 60, 30)", color2: "rgba(200, 60, 30, 0.8)" }, { color1: "rgba(220, 100, 20, 0.7)", color2: "rgba(220, 100, 20, 0.46)" }, { color1: "rgb(180, 40, 20)", color2: "rgba(180, 40, 20, 0.82)" }, { color1: "rgb(210, 80, 10)", color2: "rgba(210, 80, 10, 0.7)" }, { color1: "rgb(190, 70, 30)", color2: "rgba(190, 70, 30, 0.78)" }] } } };
function v(i, e) {
  let a = i.match(/^rgba\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*[\d.]+\s*\)$/);
  if (a) return `rgba(${a[1]}, ${a[2]}, ${a[3]}, ${e})`;
  let r = i.match(/^rgb\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*\)$/);
  return r ? `rgba(${r[1]}, ${r[2]}, ${r[3]}, ${e})` : i;
}
function l(i, e) {
  let a = i.match(/^rgba\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*\)$/);
  if (a) return `rgba(${a[1]}, ${a[2]}, ${a[3]}, ${(parseFloat(a[4]) * e).toFixed(2)})`;
  let r = i.match(/^rgb\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*\)$/);
  return r ? `rgba(${r[1]}, ${r[2]}, ${r[3]}, ${e.toFixed(2)})` : i;
}
function P(i, e, a) {
  let r = R(i, e), s = V[i][e ? "dark" : "light"], o = i === "mono", f = o ? 0.14 : 1, $ = o ? l(r.primary, 0.14) : r.primary, k = o ? l(r.primary, 0.09) : r.primary, p = o ? l(r.secondary, 0.12) : r.secondary, g = o ? v(r.secondary, 0.06) : v(r.secondary, 0.49), t = s.spikes.map((c) => o ? { color1: l(c.color1, f), color2: l(c.color2, f * 0.7) } : c), n = o ? "12px" : "0.8px", b = o ? "14px" : "2px", m = o ? "12px" : "1.2px", x = o ? "10px" : "0.6px", d = o ? "42px" : "92px", u = o ? "38px" : "72px", h = o ? "40px" : "85px", z = o ? "32px" : "60px", y = o ? "12px" : "1px", w = o ? "rgba(255, 255, 255, 0.5)" : "rgba(255, 255, 255, 1)", W = o ? "rgba(255, 255, 255, 0.45)" : "rgba(255, 255, 255, 0.9)", X = o ? "rgba(255, 255, 255, 0.25)" : "rgba(255, 255, 255, 0.5)", Y = o ? "rgba(255, 255, 255, 0.15)" : "rgba(255, 255, 255, 0.3)", H = o ? "rgba(255, 255, 255, 0.06)" : "rgba(255, 255, 255, 0.12)", F = o ? "rgba(255, 255, 255, 0.015)" : "rgba(255, 255, 255, 0.03)";
  if (e) return `radial-gradient(ellipse calc(${n} * let(--beam-spike-${a})) calc(${d} * let(--beam-h-${a})) at 8% calc(100% - 2px), ${$}, ${k} 30%, transparent 88%),
       radial-gradient(ellipse calc(10px * let(--beam-spike2-${a})) calc(35px * let(--beam-h-${a})) at 22% calc(100% - 4px), ${p}, ${g} 50%, transparent 95%),
       radial-gradient(ellipse calc(${b} * (2 - let(--beam-spike-${a}))) calc(${u} * let(--beam-h-${a})) at 36% calc(100% - 3px), ${t[0].color1}, ${t[0].color2} 40%, transparent 90%),
       radial-gradient(ellipse calc(14px * let(--beam-spike2-${a})) calc(28px * let(--beam-h-${a})) at 50% calc(100% - 2px), ${t[1].color1}, ${t[1].color2} 55%, transparent 96%),
       radial-gradient(ellipse calc(${m} * (2 - let(--beam-spike2-${a}))) calc(${h} * let(--beam-h-${a})) at 64% calc(100% - 4px), ${t[2].color1}, ${t[2].color2} 35%, transparent 89%),
       radial-gradient(ellipse calc(7px * let(--beam-spike-${a})) calc(45px * let(--beam-h-${a})) at 78% calc(100% - 2px), ${t[3].color1}, ${t[3].color2} 48%, transparent 94%),
       radial-gradient(ellipse calc(${x} * (2 - let(--beam-spike-${a}))) calc(${z} * let(--beam-h-${a})) at 92% calc(100% - 3px), ${t[4].color1}, ${t[4].color2} 42%, transparent 91%),
       radial-gradient(ellipse calc(21px * let(--beam-spike-${a})) calc(15px * let(--beam-spike2-${a})) at calc(let(--beam-x-${a}) * 100%) calc(100% + 1px), ${w} 0%, ${W} 20%, ${X} 50%, transparent 100%),
       radial-gradient(ellipse calc(42px * let(--beam-w-${a})) calc(40px * let(--beam-h-${a})) at calc(let(--beam-x-${a}) * 100%) 100%, ${Y} 0%, ${H} 25%, ${F} 55%, transparent 80%)`;
  {
    let c = o ? l(r.primary, 0.11) : v(r.primary, 0.85), q = o ? l(r.secondary, 0.09) : v(r.secondary, 0.7);
    return `radial-gradient(ellipse calc(${n} * let(--beam-spike-${a})) calc(${d} * let(--beam-h-${a})) at 8% calc(100% - 2px), ${$}, ${c} 30%, transparent 88%),
       radial-gradient(ellipse calc(10px * let(--beam-spike2-${a})) calc(35px * let(--beam-h-${a})) at 22% calc(100% - 4px), ${p}, ${q} 50%, transparent 95%),
       radial-gradient(ellipse calc(${b} * (2 - let(--beam-spike-${a}))) calc(${u} * let(--beam-h-${a})) at 36% calc(100% - 3px), ${t[0].color1}, ${t[0].color2} 40%, transparent 90%),
       radial-gradient(ellipse calc(14px * let(--beam-spike2-${a})) calc(28px * let(--beam-h-${a})) at 50% calc(100% - 2px), ${t[1].color1}, ${t[1].color2} 55%, transparent 96%),
       radial-gradient(ellipse calc(${m} * (2 - let(--beam-spike2-${a}))) calc(${h} * let(--beam-h-${a})) at 64% calc(100% - 4px), ${t[2].color1}, ${t[2].color2} 35%, transparent 89%),
       radial-gradient(ellipse calc(7px * let(--beam-spike-${a})) calc(45px * let(--beam-h-${a})) at 78% calc(100% - 2px), ${t[3].color1}, ${t[3].color2} 48%, transparent 94%),
       radial-gradient(ellipse calc(${y} * (2 - let(--beam-spike-${a}))) calc(${z} * let(--beam-h-${a})) at 92% calc(100% - 3px), ${t[4].color1}, ${t[4].color2} 42%, transparent 91%),
       radial-gradient(ellipse calc(50px * let(--beam-w-${a})) calc(32px * let(--beam-h-${a})) at calc(let(--beam-x-${a}) * 100%) calc(100%), rgba(0, 0, 0, 0.5) 0%, rgba(0, 0, 0, 0.18) 30%, rgba(0, 0, 0, 0.03) 60%, transparent 85%)`;
  }
}
function A(i) {
  return `
[data-beam="${i}"][data-paused],
[data-beam="${i}"][data-paused]::after,
[data-beam="${i}"][data-paused]::before,
[data-beam="${i}"][data-paused] [data-beam-bloom] {
  animation-play-state: paused !important;
}`;
}
function J(i) {
  let { id: e, borderRadius: a, borderWidth: r, duration: s, strokeOpacity: o, innerOpacity: f, bloomOpacity: $, innerShadow: k, colorVariant: p, staticColors: g, brightness: t, saturation: n, hueRange: b, theme: m } = i, x = Math.max(0, a - r), d = m === "dark", u = o, h = f, z = $, y = g ? "" : `animation: beam-hue-shift-${e} 12s ease-in-out infinite;`, w = g ? "" : `animation: beam-hue-shift-bloom-${e} 8s ease-in-out infinite;`, W = g ? "" : `
@keyframes beam-hue-shift-${e} {
  0% { filter: hue-rotate(calc(let(--beam-hue-base, 0deg) - ${b}deg)) brightness(${t.toFixed(2)}) saturate(${n.toFixed(2)}); }
  50% { filter: hue-rotate(calc(let(--beam-hue-base, 0deg) + ${b}deg)) brightness(${t.toFixed(2)}) saturate(${n.toFixed(2)}); }
  100% { filter: hue-rotate(calc(let(--beam-hue-base, 0deg) - ${b}deg)) brightness(${t.toFixed(2)}) saturate(${n.toFixed(2)}); }
}

@keyframes beam-hue-shift-bloom-${e} {
  0% { filter: blur(8px) hue-rotate(calc(let(--beam-hue-base, 0deg) - ${b + 10}deg)) brightness(${t.toFixed(2)}) saturate(${n.toFixed(2)}); }
  50% { filter: blur(8px) hue-rotate(calc(let(--beam-hue-base, 0deg) + ${b + 10}deg)) brightness(${t.toFixed(2)}) saturate(${n.toFixed(2)}); }
  100% { filter: blur(8px) hue-rotate(calc(let(--beam-hue-base, 0deg) - ${b + 10}deg)) brightness(${t.toFixed(2)}) saturate(${n.toFixed(2)}); }
}`, X = d ? `radial-gradient(
        ellipse calc(24px * let(--beam-w-${e})) calc(28px * let(--beam-h-${e})) at calc(let(--beam-x-${e}) * 100%) calc(100% + 2px),
        rgba(255, 255, 255, 0.38) 0%,
        rgba(255, 255, 255, 0.12) 30%,
        transparent 65%
      )` : `radial-gradient(
        ellipse calc(35px * let(--beam-w-${e})) calc(28px * let(--beam-h-${e})) at calc(let(--beam-x-${e}) * 100%) calc(100% + 2px),
        rgba(0, 0, 0, 0.6) 0%,
        rgba(0, 0, 0, 0.25) 35%,
        transparent 70%
      )`, Y = M(p, d, e), H = L(p, e), F = P(p, d, e), c = p === "mono" ? "filter: blur(6px);" : "";
  return `
@property --beam-x-${e} {
  syntax: "<number>";
  initial-value: 0;
  inherits: true;
}

@property --beam-w-${e} {
  syntax: "<number>";
  initial-value: 1;
  inherits: true;
}

@property --beam-h-${e} {
  syntax: "<number>";
  initial-value: 1;
  inherits: true;
}

@property --beam-spike-${e} {
  syntax: "<number>";
  initial-value: 1;
  inherits: true;
}

@property --beam-spike2-${e} {
  syntax: "<number>";
  initial-value: 1;
  inherits: true;
}

@property --beam-edge-${e} {
  syntax: "<number>";
  initial-value: 1;
  inherits: true;
}

@property --beam-opacity-${e} {
  syntax: "<number>";
  initial-value: 0;
  inherits: true;
}

[data-beam="${e}"] {
  position: relative;
  border-radius: ${a}px;
  overflow: hidden;
}

[data-beam="${e}"][data-active] {
  animation:
    beam-travel-${e} ${s}s linear infinite,
    beam-edge-fade-${e} ${s}s linear infinite,
    beam-breathe-${e} ${(s * 1.3).toFixed(1)}s ease-in-out infinite,
    beam-spike-${e} ${(s * 1.33).toFixed(1)}s ease-in-out infinite,
    beam-spike2-${e} ${(s * 1.7).toFixed(1)}s ease-in-out infinite,
    beam-fade-in-${e} 0.6s ease forwards;
}

[data-beam="${e}"][data-fading] {
  animation:
    beam-travel-${e} ${s}s linear infinite,
    beam-edge-fade-${e} ${s}s linear infinite,
    beam-breathe-${e} ${(s * 1.3).toFixed(1)}s ease-in-out infinite,
    beam-spike-${e} ${(s * 1.33).toFixed(1)}s ease-in-out infinite,
    beam-spike2-${e} ${(s * 1.7).toFixed(1)}s ease-in-out infinite,
    beam-fade-out-${e} 0.5s ease forwards;
}

[data-beam="${e}"][data-active]::after,
[data-beam="${e}"][data-fading]::after {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: ${x}px;
  padding: ${r}px;
  clip-path: inset(0 round ${a}px);
  background: ${X}, ${Y};
  -webkit-mask:
    radial-gradient(
      ellipse calc(78px * let(--beam-w-${e})) calc(60px * let(--beam-h-${e})) at calc(let(--beam-x-${e}) * 100%) 100%,
      white 0%, rgba(255, 255, 255, 0.5) 45%, transparent 100%
    ),
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
  -webkit-mask-composite: source-in, xor;
  mask:
    radial-gradient(
      ellipse calc(78px * let(--beam-w-${e})) calc(60px * let(--beam-h-${e})) at calc(let(--beam-x-${e}) * 100%) 100%,
      white 0%, rgba(255, 255, 255, 0.5) 45%, transparent 100%
    ),
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
  mask-composite: intersect, exclude;
  pointer-events: none;
  z-index: 2;
  opacity: calc(let(--beam-opacity-${e}) * let(--beam-edge-${e}) * ${u.toFixed(2)} * let(--beam-stroke-opacity, 1) * let(--beam-strength, 1));
  ${y}
}

[data-beam="${e}"][data-active]::before,
[data-beam="${e}"][data-fading]::before {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: ${a}px;
  background: ${H};
  box-shadow: inset 0 0 9px 1px ${k};
  -webkit-mask-image:
    radial-gradient(
      ellipse calc(78px * let(--beam-w-${e})) calc(60px * let(--beam-h-${e})) at calc(let(--beam-x-${e}) * 100%) 100%,
      white 0%, rgba(255, 255, 255, 0.5) 45%, transparent 100%
    ),
    linear-gradient(white, transparent 28px, transparent calc(100% - 28px), white),
    linear-gradient(to right, white, transparent 28px, transparent calc(100% - 28px), white);
  -webkit-mask-composite: source-in, source-over;
  mask-image:
    radial-gradient(
      ellipse calc(78px * let(--beam-w-${e})) calc(60px * let(--beam-h-${e})) at calc(let(--beam-x-${e}) * 100%) 100%,
      white 0%, rgba(255, 255, 255, 0.5) 45%, transparent 100%
    ),
    linear-gradient(white, transparent 28px, transparent calc(100% - 28px), white),
    linear-gradient(to right, white, transparent 28px, transparent calc(100% - 28px), white);
  mask-composite: intersect, add;
  pointer-events: none;
  z-index: 1;
  opacity: calc(let(--beam-opacity-${e}) * let(--beam-edge-${e}) * ${h.toFixed(2)} * let(--beam-inner-opacity, 1) * let(--beam-strength, 1));
  clip-path: inset(0 round ${a}px);
  ${y}
}

[data-beam="${e}"] [data-beam-bloom] {
  display: none;
  position: absolute;
  inset: 0;
  border-radius: ${x}px;
  clip-path: inset(0 round ${a}px);
  padding: 0;
  -webkit-mask: radial-gradient(
    ellipse calc(84px * let(--beam-w-${e})) calc(110px * let(--beam-h-${e})) at calc(let(--beam-x-${e}) * 100%) 100%,
    white 0%, rgba(255, 255, 255, 0.5) 35%, transparent 100%
  );
  -webkit-mask-composite: source-over;
  mask: radial-gradient(
    ellipse calc(84px * let(--beam-w-${e})) calc(110px * let(--beam-h-${e})) at calc(let(--beam-x-${e}) * 100%) 100%,
    white 0%, rgba(255, 255, 255, 0.5) 35%, transparent 100%
  );
  mask-composite: add;
  background: ${F};
  ${c}
  pointer-events: none;
  z-index: 3;
  opacity: 0;
}

[data-beam="${e}"][data-active] [data-beam-bloom],
[data-beam="${e}"][data-fading] [data-beam-bloom] {
  display: block;
  opacity: calc(let(--beam-opacity-${e}) * let(--beam-edge-${e}) * ${z.toFixed(2)} * let(--beam-bloom-opacity, 1) * let(--beam-strength, 1));
  ${w}
}

@keyframes beam-travel-${e} {
  0%   { --beam-x-${e}: 0.06;  --beam-w-${e}: 0.5; }
  10%  { --beam-x-${e}: 0.15;  --beam-w-${e}: 0.8; }
  20%  { --beam-x-${e}: 0.25;  --beam-w-${e}: 1.1; }
  30%  { --beam-x-${e}: 0.35;  --beam-w-${e}: 1.3; }
  40%  { --beam-x-${e}: 0.44;  --beam-w-${e}: 1.45; }
  50%  { --beam-x-${e}: 0.5;   --beam-w-${e}: 1.5; }
  60%  { --beam-x-${e}: 0.56;  --beam-w-${e}: 1.45; }
  70%  { --beam-x-${e}: 0.65;  --beam-w-${e}: 1.3; }
  80%  { --beam-x-${e}: 0.75;  --beam-w-${e}: 1.1; }
  90%  { --beam-x-${e}: 0.85;  --beam-w-${e}: 0.8; }
  100% { --beam-x-${e}: 0.94;  --beam-w-${e}: 0.5; }
}

@keyframes beam-edge-fade-${e} {
  0%    { --beam-edge-${e}: 0; }
  12.5% { --beam-edge-${e}: 0; }
  32.5% { --beam-edge-${e}: 1; }
  67.5% { --beam-edge-${e}: 1; }
  87.5% { --beam-edge-${e}: 0; }
  100%  { --beam-edge-${e}: 0; }
}

@keyframes beam-breathe-${e} {
  0%, 100% { --beam-h-${e}: 0.8; }
  25%      { --beam-h-${e}: 1.25; }
  55%      { --beam-h-${e}: 0.85; }
  80%      { --beam-h-${e}: 1.3; }
}

@keyframes beam-spike-${e} {
  0%   { --beam-spike-${e}: 0.8; }
  25%  { --beam-spike-${e}: 1.3; }
  50%  { --beam-spike-${e}: 0.9; }
  75%  { --beam-spike-${e}: 1.4; }
  100% { --beam-spike-${e}: 0.8; }
}

@keyframes beam-spike2-${e} {
  0%   { --beam-spike2-${e}: 1.2; }
  25%  { --beam-spike2-${e}: 0.7; }
  50%  { --beam-spike2-${e}: 1.4; }
  75%  { --beam-spike2-${e}: 0.8; }
  100% { --beam-spike2-${e}: 1.2; }
}

@keyframes beam-fade-in-${e} {
  to { --beam-opacity-${e}: 1; }
}

@keyframes beam-fade-out-${e} {
  from { --beam-opacity-${e}: 1; }
  to { --beam-opacity-${e}: 0; }
}
${W}
${A(e)}
`;
}
window.MauticUI = window.MauticUI || {};
window.MauticUI.HoldBeam = { beamCSS: J, beamThemes: O };

})();
