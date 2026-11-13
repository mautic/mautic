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

(() => {
    if (window.MauticUI && window.MauticUI.HoldToConfirmButton) return;

    const spring = (time, stiffness, damping) => {
        const decay = damping / 2;
        const frequency = Math.sqrt(stiffness - decay * decay);
        return 1 - Math.exp(-decay * time) * (Math.cos(frequency * time) + decay / frequency * Math.sin(frequency * time));
    };

    const animate = (target, destination, options = {}) => {
        const duration = options.type === 'spring' ? 600 : (options.duration === undefined ? 0.3 : options.duration) * 1000;
        const delay = (options.delay || 0) * 1000;
        const easing = options.ease === 'linear' ? 'linear' : 'cubic-bezier(.22, 1, .36, 1)';
        if (typeof target === 'number') {
            let frame;
            const start = performance.now();
            const tick = (now) => {
                const elapsed = Math.max(0, now - start - delay);
                const fraction = duration ? Math.min(1, elapsed / duration) : 1;
                const amount = options.type === 'spring' ? spring(elapsed / 1000, options.stiffness, options.damping) : options.ease === 'linear' ? fraction : 1 - Math.pow(1 - fraction, 3);
                options.onUpdate(fraction === 1 ? destination : target + (destination - target) * amount);
                if (fraction < 1) frame = requestAnimationFrame(tick);
                else if (options.onComplete) options.onComplete();
            };
            frame = requestAnimationFrame(tick);
            return {stop: () => cancelAnimationFrame(frame)};
        }
        const computed = getComputedStyle(target);
        const matrix = new DOMMatrixReadOnly(computed.transform === 'none' ? undefined : computed.transform);
        const from = {};
        const to = {};
        Object.entries(destination).forEach(([key, value]) => {
            from[key] = Array.isArray(value) ? value[0] : key === 'opacity' ? Number(computed.opacity) : key === 'scale' ? matrix.a : matrix.f;
            to[key] = Array.isArray(value) ? value[value.length - 1] : value;
        });
        const styles = (fraction) => {
            const values = {};
            Object.keys(to).forEach((key) => values[key] = from[key] + (to[key] - from[key]) * fraction);
            const result = {};
            if ('opacity' in values) result.opacity = values.opacity;
            if ('scale' in values) result.transform = `scale(${values.scale})`;
            if ('y' in values) result.transform = `translateY(${values.y}px)`;
            return result;
        };
        const frames = options.type === 'spring' ? Array.from({length: 61}, (_, index) => styles(index === 60 ? 1 : spring(index / 100, options.stiffness, options.damping))) : [styles(0), styles(1)];
        const animation = target.animate(frames, {duration, delay, fill: 'both', easing: options.type === 'spring' ? 'linear' : easing});
        animation.onfinish = () => {
            Object.assign(target.style, styles(1));
            animation.cancel();
            if (options.onComplete) options.onComplete();
        };
        return {stop: () => {
            const current = getComputedStyle(target);
            if ('opacity' in to) target.style.opacity = current.opacity;
            if ('scale' in to || 'y' in to) {
                target.style.transform = current.transform;
            }
            animation.cancel();
        }};
    };

    const {beamCSS, beamThemes} = window.MauticUI.HoldBeam;
    const reducedMotion = () => window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    function emit(element, name, detail) {
        element.dispatchEvent(new CustomEvent(name, { bubbles: true, detail }));
    }
    function register(name, selector, create) {
        const controllers = new Map();
        const mount = (root = document) => {
            const candidates = Array.from(root.querySelectorAll(selector));
            if (root instanceof HTMLElement && root.matches(selector)) candidates.unshift(root);
            for (const element of candidates) {
                if (!controllers.has(element)) controllers.set(element, create(element));
            }
        };
        const start = () => {
            mount();
            new MutationObserver((mutations) => {
                for (const mutation of mutations) {
                    for (const node of mutation.addedNodes) {
                        if (node instanceof HTMLElement) mount(node);
                    }
                }
                for (const [element, controller] of controllers) {
                    if (element.isConnected) continue;
                    controller.destroy();
                    controllers.delete(element);
                }
            }).observe(document.documentElement, { childList: true, subtree: true });
        };
        window.MauticUI = window.MauticUI || {};
        window.MauticUI[name] = { get: (element) => controllers.get(element), mount };
        if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", start, { once: true });
        else start();
    }
    let beamCounter = 0;
    const snappy = { type: "spring", stiffness: 500, damping: 30 };
    const easeOut = [0.22, 1, 0.36, 1];
    class HoldToConfirmButton {
        constructor(element) {
            this.element = element;
            this.events = new AbortController();
            this.sources = new Set();
            this.animations = new Map();
            this.colorScheme = window.matchMedia("(prefers-color-scheme: dark)");
            this.beamId = `mautic-hold-${++beamCounter}`;
            this.progress = 0;
            this.confirmed = false;
            this.button = element.querySelector("button");
            this.ring = element.querySelector(".hold-to-confirm__ring");
            this.progressCircle = element.querySelector(".hold-to-confirm__progress");
            this.icon = element.querySelector(".hold-to-confirm__icon");
            this.defaultIcon = this.icon.cloneNode(true);
            this.check = element.querySelector(".hold-to-confirm__check");
            this.idleLabel = element.querySelector("[data-idle-label]");
            this.confirmedLabel = element.querySelector("[data-confirmed-label]");
            this.status = element.querySelector('[role="status"]');
            this.duration = Number.isFinite(Number(element.dataset.duration)) ? Math.max(0, Number(element.dataset.duration)) : 1200;
            this.resetDelay = Number.isFinite(Number(element.dataset.resetDelay)) ? Math.max(0, Number(element.dataset.resetDelay)) : 1500;
            const options = { signal: this.events.signal };
            this.button.addEventListener("pointerdown", (event) => {
                try {
                    this.button.setPointerCapture(event.pointerId);
                } catch (error) {
                    if (!(error instanceof DOMException)) throw error;
                }
                if (event.button === 0 && event.isPrimary) this.start("pointer");
            }, options);
            for (const name of ["pointerup", "pointerleave", "pointercancel", "lostpointercapture"]) {
                this.button.addEventListener(name, () => this.cancel("pointer"), options);
            }
            this.button.addEventListener("keydown", (event) => {
                if (event.key !== " " && event.key !== "Enter") return;
                event.preventDefault();
                if (!event.repeat) this.start("keyboard");
            }, options);
            this.button.addEventListener("keyup", (event) => {
                if (event.key !== " " && event.key !== "Enter") return;
                event.preventDefault();
                this.cancel("keyboard");
            }, options);
            this.button.addEventListener("blur", () => this.cancel(), options);
            this.button.addEventListener("contextmenu", (event) => event.preventDefault(), options);
            window.addEventListener("blur", () => this.cancel(), options);
            document.addEventListener("visibilitychange", () => {
                if (document.hidden) this.cancel();
            }, options);
            this.colorScheme.addEventListener("change", () => this.renderBeam(), options);
            element.addEventListener("animationend", (event) => {
                if (event.target !== element || !event.animationName.includes("fade-out")) return;
                element.removeAttribute("data-fading");
                element.removeAttribute("data-active");
            }, options);
            this.renderBeam();
        }
        update(options) {
            if (options.className !== void 0) this.button.className = `btn btn-tertiary btn-nospin hold-to-confirm__button ${options.className}`;
            if (options.beamClassName !== void 0) this.element.className = `hold-to-confirm ${options.beamClassName}`;
            if ("icon" in options) {
                this.icon.replaceChildren(...options.icon ? [options.icon] : Array.from(this.defaultIcon.childNodes).map((node) => node.cloneNode(true)));
            }
            if (options.size !== void 0) {
                this.element.dataset.size = options.size;
                this.button.setAttribute('size', options.size);
                const dimensions = { sm: { icon: 12, ring: 20, stroke: 2 }, md: { icon: 14, ring: 24, stroke: 2 }, lg: { icon: 17, ring: 30, stroke: 2.5 } }[options.size];
                const symbol = this.element.querySelector(".hold-to-confirm__symbol");
                symbol.style.width = `${dimensions.ring}px`;
                symbol.style.height = `${dimensions.ring}px`;
                this.ring.setAttribute("viewBox", `0 0 ${dimensions.ring} ${dimensions.ring}`);
                this.ring.setAttribute("width", String(dimensions.ring));
                this.ring.setAttribute("height", String(dimensions.ring));
                for (const circle of this.ring.querySelectorAll("circle")) {
                    circle.setAttribute("cx", String(dimensions.ring / 2));
                    circle.setAttribute("cy", String(dimensions.ring / 2));
                    circle.setAttribute("r", String((dimensions.ring - dimensions.stroke) / 2));
                    circle.setAttribute("stroke-width", String(dimensions.stroke));
                }
                for (const svg of this.element.querySelectorAll("[data-default-icon], .hold-to-confirm__check svg")) {
                    svg.setAttribute("width", String(dimensions.icon));
                    svg.setAttribute("height", String(dimensions.icon));
                }
            }
            if (options.duration !== void 0 && Number.isFinite(options.duration)) {
                this.cancel();
                this.duration = Math.max(0, options.duration);
            }
            if (options.resetDelay !== void 0 && Number.isFinite(options.resetDelay)) this.resetDelay = Math.max(0, options.resetDelay);
            if ("onConfirm" in options) this.onConfirm = options.onConfirm;
            if (options.disabled !== void 0) {
                this.button.disabled = options.disabled;
                if (options.disabled) this.cancel();
            }
            if (options.label !== void 0) this.idleLabel.textContent = options.label;
            if (options.confirmedLabel !== void 0) this.confirmedLabel.textContent = options.confirmedLabel;
            for (const key of ["variant", "theme", "colorVariant", "strength", "active"]) {
                if (options[key] !== void 0) this.element.dataset[key === "active" ? "beamEnabled" : key] = key === "variant" && options[key] === "destructive" ? "danger" : String(options[key]);
            }
            this.button.setAttribute("aria-label", this.element.dataset.instructions.replace("%label%", this.idleLabel.textContent || "").replace("%seconds%", (this.duration / 1e3).toFixed(1).replace(/\.0$/, "")));
            this.button.classList.toggle('btn-danger', this.element.dataset.variant === 'danger');
            this.renderBeam();
        }
        motion(element, target, options = snappy) {
            if (this.animations.get(element)) this.animations.get(element).stop();
            this.animations.set(element, animate(element, target, reducedMotion() ? { duration: 0 } : options));
        }
        setProgress(value) {
            this.progress = value;
            this.progressCircle.style.strokeDashoffset = String(1 - value);
            this.ring.style.opacity = String(Math.max(0, Math.min(1, value / 0.04)));
        }
        start(source) {
            if (!this.element.isConnected || this.button.disabled || this.confirmed) return;
            const alreadyHolding = this.sources.size > 0;
            this.sources.add(source);
            if (alreadyHolding) return;
            this.element.dataset.state = "holding";
            this.motion(this.button, { scale: reducedMotion() ? 1 : 0.97 }, { duration: 0.2, ease: easeOut });
            if (this.fill) this.fill.stop();
            this.fill = animate(this.progress, 1, {
                duration: Math.max(0, this.duration * (1 - this.progress) / 1e3),
                ease: "linear",
                onUpdate: (value) => this.setProgress(value),
                onComplete: () => this.complete()
            });
        }
        cancel(source) {
            if (source) this.sources.delete(source);
            else this.sources.clear();
            if (this.sources.size > 0) return;
            this.motion(this.button, { scale: 1 });
            if (this.confirmed) return;
            this.element.dataset.state = "idle";
            if (this.fill) this.fill.stop();
            this.fill = animate(this.progress, 0, {
                ...reducedMotion() ? { duration: 0.15, ease: "linear" } : snappy,
                onUpdate: (value) => this.setProgress(value)
            });
        }
        swapLabel(confirmed) {
            const outgoing = confirmed ? this.idleLabel : this.confirmedLabel;
            const incoming = confirmed ? this.confirmedLabel : this.idleLabel;
            const ghost = outgoing.cloneNode(true);
            ghost.style.position = "absolute";
            ghost.setAttribute("aria-hidden", "true");
            outgoing.parentElement.append(ghost);
            outgoing.hidden = true;
            incoming.hidden = false;
            const transition = { type: "spring", stiffness: 400, damping: 30 };
            this.animations.set(ghost, animate(ghost, { y: reducedMotion() ? 0 : -6, opacity: 0 }, {
                ...reducedMotion() ? { duration: 0 } : transition,
                onComplete: () => { this.animations.delete(ghost); ghost.remove(); }
            }));
            this.motion(incoming, { y: reducedMotion() ? 0 : [6, 0], opacity: [0, 1] }, { ...transition, delay: confirmed ? 0.17 : 0 });
        }
        complete() {
            if (this.confirmed || !this.element.isConnected || this.button.disabled || document.hidden || this.sources.size === 0) return;
            this.confirmed = true;
            this.sources.clear();
            this.setProgress(1);
            this.element.dataset.state = "confirmed";
            this.motion(this.button, { scale: 1 });
            this.motion(this.icon, { scale: 0, opacity: 0 });
            this.motion(this.check, { scale: 1, opacity: 1 }, { type: "spring", stiffness: 500, damping: 22, delay: 0.05 });
            this.swapLabel(true);
            this.status.textContent = this.confirmedLabel.textContent;
            this.button.setAttribute("aria-label", this.confirmedLabel.textContent || "");
            if (this.onConfirm) this.onConfirm();
            emit(this.element, "mautic:hold-to-confirm:confirm", {});
            if (this.resetDelay > 0) this.resetTimer = setTimeout(() => this.reset(), this.resetDelay);
        }
        reset() {
            const wasConfirmed = this.confirmed;
            this.confirmed = false;
            this.sources.clear();
            clearTimeout(this.resetTimer);
            this.element.dataset.state = "idle";
            this.motion(this.button, { scale: 1 });
            this.motion(this.icon, { scale: 1, opacity: 1 });
            this.motion(this.check, { scale: 0, opacity: 0 });
            if (wasConfirmed) this.swapLabel(false);
            this.status.textContent = "";
            this.update({});
            if (this.fill) this.fill.stop();
            this.fill = animate(this.progress, 0, { duration: reducedMotion() ? 0.1 : 0.3, ease: "easeOut", onUpdate: (value) => this.setProgress(value) });
        }
        renderBeam() {
            if (this.element.dataset.variant !== "beam") {
                if (this.beamStyle) this.beamStyle.remove();
                this.beamStyle = void 0;
                this.element.removeAttribute("data-beam");
                if (this.element.querySelector("[data-beam-bloom]")) this.element.querySelector("[data-beam-bloom]").remove();
                if (this.intersection) this.intersection.disconnect();
                this.intersection = void 0;
                return;
            }
            const colorVariant = ["colorful", "mono", "ocean", "sunset"].includes(this.element.dataset.colorVariant || "") ? this.element.dataset.colorVariant : "sunset";
            const theme = ['light', 'dark'].includes(this.element.dataset.theme) ? this.element.dataset.theme : this.colorScheme.matches ? 'dark' : 'light';
            if (!this.beamStyle) this.beamStyle = document.createElement("style");
            this.beamStyle.dataset.beamStyle = this.beamId;
            this.beamStyle.textContent = beamCSS({
                ...beamThemes.md[theme],
                id: this.beamId,
                borderRadius: parseFloat(getComputedStyle(this.button).borderTopLeftRadius),
                borderWidth: 1,
                duration: 1.96,
                colorVariant,
                staticColors: colorVariant === "mono",
                brightness: 1.3,
                hueRange: 30,
                theme
            });
            if (!this.beamStyle.isConnected) document.head.append(this.beamStyle);
            this.element.dataset.beam = this.beamId;
            this.element.style.setProperty("--beam-strength", String(Math.max(0, Math.min(1, Number(this.element.dataset.strength)))));
            if (!this.element.querySelector("[data-beam-bloom]")) {
                const bloom = document.createElement("div");
                bloom.setAttribute("data-beam-bloom", "");
                this.element.append(bloom);
            }
            const active = this.element.dataset.beamEnabled !== "false" && !this.button.disabled;
            if (active) {
                this.element.setAttribute("data-active", "");
                this.element.removeAttribute("data-fading");
            } else if (this.element.hasAttribute("data-active")) {
                this.element.setAttribute("data-fading", "");
            } else this.element.removeAttribute("data-active");
            if (!this.intersection) {
                this.intersection = new IntersectionObserver((entries) => {
                    for (const entry of entries) this.element.toggleAttribute("data-paused", !entry.isIntersecting);
                }, { rootMargin: "256px" });
                this.intersection.observe(this.element);
            }
        }
        destroy() {
            this.events.abort();
            if (this.fill) this.fill.stop();
            clearTimeout(this.resetTimer);
            for (const animation of this.animations.values()) animation.stop();
            if (this.beamStyle) this.beamStyle.remove();
            if (this.intersection) this.intersection.disconnect();
        }
    }
    register("HoldToConfirmButton", '[data-component="hold-to-confirm"]', (element) => new HoldToConfirmButton(element));
})();
