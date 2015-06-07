// TinyColor v0.9.15
// https://github.com/bgrins/TinyColor
// 2013-07-04, Brian Grinstead, MIT License
(function (root) {
    function tinycolor(color, opts) {
        if (color = color ? color : "", opts = opts || {}, "object" == typeof color && color.hasOwnProperty("_tc_id"))return color;
        var rgb = inputToRGB(color), r = rgb.r, g = rgb.g, b = rgb.b, a = rgb.a, roundA = mathRound(100 * a) / 100, format = opts.format || rgb.format;
        return 1 > r && (r = mathRound(r)), 1 > g && (g = mathRound(g)), 1 > b && (b = mathRound(b)), {
            ok: rgb.ok,
            format: format,
            _tc_id: tinyCounter++,
            alpha: a,
            toHsv: function () {
                var hsv = rgbToHsv(r, g, b);
                return {h: 360 * hsv.h, s: hsv.s, v: hsv.v, a: a}
            },
            toHsvString: function () {
                var hsv = rgbToHsv(r, g, b), h = mathRound(360 * hsv.h), s = mathRound(100 * hsv.s), v = mathRound(100 * hsv.v);
                return 1 == a ? "hsv(" + h + ", " + s + "%, " + v + "%)" : "hsva(" + h + ", " + s + "%, " + v + "%, " + roundA + ")"
            },
            toHsl: function () {
                var hsl = rgbToHsl(r, g, b);
                return {h: 360 * hsl.h, s: hsl.s, l: hsl.l, a: a}
            },
            toHslString: function () {
                var hsl = rgbToHsl(r, g, b), h = mathRound(360 * hsl.h), s = mathRound(100 * hsl.s), l = mathRound(100 * hsl.l);
                return 1 == a ? "hsl(" + h + ", " + s + "%, " + l + "%)" : "hsla(" + h + ", " + s + "%, " + l + "%, " + roundA + ")"
            },
            toHex: function (allow3Char) {
                return rgbToHex(r, g, b, allow3Char)
            },
            toHexString: function (allow3Char) {
                return "#" + rgbToHex(r, g, b, allow3Char)
            },
            toRgb: function () {
                return {r: mathRound(r), g: mathRound(g), b: mathRound(b), a: a}
            },
            toRgbString: function () {
                return 1 == a ? "rgb(" + mathRound(r) + ", " + mathRound(g) + ", " + mathRound(b) + ")" : "rgba(" + mathRound(r) + ", " + mathRound(g) + ", " + mathRound(b) + ", " + roundA + ")"
            },
            toPercentageRgb: function () {
                return {
                    r: mathRound(100 * bound01(r, 255)) + "%",
                    g: mathRound(100 * bound01(g, 255)) + "%",
                    b: mathRound(100 * bound01(b, 255)) + "%",
                    a: a
                }
            },
            toPercentageRgbString: function () {
                return 1 == a ? "rgb(" + mathRound(100 * bound01(r, 255)) + "%, " + mathRound(100 * bound01(g, 255)) + "%, " + mathRound(100 * bound01(b, 255)) + "%)" : "rgba(" + mathRound(100 * bound01(r, 255)) + "%, " + mathRound(100 * bound01(g, 255)) + "%, " + mathRound(100 * bound01(b, 255)) + "%, " + roundA + ")"
            },
            toName: function () {
                return 0 === a ? "transparent" : hexNames[rgbToHex(r, g, b, !0)] || !1
            },
            toFilter: function (secondColor) {
                var hex = rgbToHex(r, g, b), secondHex = hex, alphaHex = Math.round(255 * parseFloat(a)).toString(16), secondAlphaHex = alphaHex, gradientType = opts && opts.gradientType ? "GradientType = 1, " : "";
                if (secondColor) {
                    var s = tinycolor(secondColor);
                    secondHex = s.toHex(), secondAlphaHex = Math.round(255 * parseFloat(s.alpha)).toString(16)
                }
                return "progid:DXImageTransform.Microsoft.gradient(" + gradientType + "startColorstr=#" + pad2(alphaHex) + hex + ",endColorstr=#" + pad2(secondAlphaHex) + secondHex + ")"
            },
            toString: function (format) {
                var formatSet = !!format;
                format = format || this.format;
                var formattedString = !1, hasAlphaAndFormatNotSet = !formatSet && 1 > a && a > 0, formatWithAlpha = hasAlphaAndFormatNotSet && ("hex" === format || "hex6" === format || "hex3" === format || "name" === format);
                return "rgb" === format && (formattedString = this.toRgbString()), "prgb" === format && (formattedString = this.toPercentageRgbString()), ("hex" === format || "hex6" === format) && (formattedString = this.toHexString()), "hex3" === format && (formattedString = this.toHexString(!0)), "name" === format && (formattedString = this.toName()), "hsl" === format && (formattedString = this.toHslString()), "hsv" === format && (formattedString = this.toHsvString()), formatWithAlpha ? this.toRgbString() : formattedString || this.toHexString()
            }
        }
    }

    function inputToRGB(color) {
        var rgb = {r: 0, g: 0, b: 0}, a = 1, ok = !1, format = !1;
        return "string" == typeof color && (color = stringInputToObject(color)), "object" == typeof color && (color.hasOwnProperty("r") && color.hasOwnProperty("g") && color.hasOwnProperty("b") ? (rgb = rgbToRgb(color.r, color.g, color.b), ok = !0, format = "%" === (color.r + "").substr(-1) ? "prgb" : "rgb") : color.hasOwnProperty("h") && color.hasOwnProperty("s") && color.hasOwnProperty("v") ? (color.s = convertToPercentage(color.s), color.v = convertToPercentage(color.v), rgb = hsvToRgb(color.h, color.s, color.v), ok = !0, format = "hsv") : color.hasOwnProperty("h") && color.hasOwnProperty("s") && color.hasOwnProperty("l") && (color.s = convertToPercentage(color.s), color.l = convertToPercentage(color.l), rgb = hslToRgb(color.h, color.s, color.l), ok = !0, format = "hsl"), color.hasOwnProperty("a") && (a = color.a)), a = parseFloat(a), (isNaN(a) || 0 > a || a > 1) && (a = 1), {
            ok: ok,
            format: color.format || format,
            r: mathMin(255, mathMax(rgb.r, 0)),
            g: mathMin(255, mathMax(rgb.g, 0)),
            b: mathMin(255, mathMax(rgb.b, 0)),
            a: a
        }
    }

    function rgbToRgb(r, g, b) {
        return {r: 255 * bound01(r, 255), g: 255 * bound01(g, 255), b: 255 * bound01(b, 255)}
    }

    function rgbToHsl(r, g, b) {
        r = bound01(r, 255), g = bound01(g, 255), b = bound01(b, 255);
        var h, s, max = mathMax(r, g, b), min = mathMin(r, g, b), l = (max + min) / 2;
        if (max == min)h = s = 0; else {
            var d = max - min;
            switch (s = l > .5 ? d / (2 - max - min) : d / (max + min), max) {
                case r:
                    h = (g - b) / d + (b > g ? 6 : 0);
                    break;
                case g:
                    h = (b - r) / d + 2;
                    break;
                case b:
                    h = (r - g) / d + 4
            }
            h /= 6
        }
        return {h: h, s: s, l: l}
    }

    function hslToRgb(h, s, l) {
        function hue2rgb(p, q, t) {
            return 0 > t && (t += 1), t > 1 && (t -= 1), 1 / 6 > t ? p + 6 * (q - p) * t : .5 > t ? q : 2 / 3 > t ? p + 6 * (q - p) * (2 / 3 - t) : p
        }

        var r, g, b;
        if (h = bound01(h, 360), s = bound01(s, 100), l = bound01(l, 100), 0 === s)r = g = b = l; else {
            var q = .5 > l ? l * (1 + s) : l + s - l * s, p = 2 * l - q;
            r = hue2rgb(p, q, h + 1 / 3), g = hue2rgb(p, q, h), b = hue2rgb(p, q, h - 1 / 3)
        }
        return {r: 255 * r, g: 255 * g, b: 255 * b}
    }

    function rgbToHsv(r, g, b) {
        r = bound01(r, 255), g = bound01(g, 255), b = bound01(b, 255);
        var h, s, max = mathMax(r, g, b), min = mathMin(r, g, b), v = max, d = max - min;
        if (s = 0 === max ? 0 : d / max, max == min)h = 0; else {
            switch (max) {
                case r:
                    h = (g - b) / d + (b > g ? 6 : 0);
                    break;
                case g:
                    h = (b - r) / d + 2;
                    break;
                case b:
                    h = (r - g) / d + 4
            }
            h /= 6
        }
        return {h: h, s: s, v: v}
    }

    function hsvToRgb(h, s, v) {
        h = 6 * bound01(h, 360), s = bound01(s, 100), v = bound01(v, 100);
        var i = math.floor(h), f = h - i, p = v * (1 - s), q = v * (1 - f * s), t = v * (1 - (1 - f) * s), mod = i % 6, r = [v, q, p, p, t, v][mod], g = [t, v, v, q, p, p][mod], b = [p, p, t, v, v, q][mod];
        return {r: 255 * r, g: 255 * g, b: 255 * b}
    }

    function rgbToHex(r, g, b, allow3Char) {
        var hex = [pad2(mathRound(r).toString(16)), pad2(mathRound(g).toString(16)), pad2(mathRound(b).toString(16))];
        return allow3Char && hex[0].charAt(0) == hex[0].charAt(1) && hex[1].charAt(0) == hex[1].charAt(1) && hex[2].charAt(0) == hex[2].charAt(1) ? hex[0].charAt(0) + hex[1].charAt(0) + hex[2].charAt(0) : hex.join("")
    }

    function flip(o) {
        var flipped = {};
        for (var i in o)o.hasOwnProperty(i) && (flipped[o[i]] = i);
        return flipped
    }

    function bound01(n, max) {
        isOnePointZero(n) && (n = "100%");
        var processPercent = isPercentage(n);
        return n = mathMin(max, mathMax(0, parseFloat(n))), processPercent && (n = parseInt(n * max, 10) / 100), 1e-6 > math.abs(n - max) ? 1 : n % max / parseFloat(max)
    }

    function clamp01(val) {
        return mathMin(1, mathMax(0, val))
    }

    function parseHex(val) {
        return parseInt(val, 16)
    }

    function isOnePointZero(n) {
        return "string" == typeof n && -1 != n.indexOf(".") && 1 === parseFloat(n)
    }

    function isPercentage(n) {
        return "string" == typeof n && -1 != n.indexOf("%")
    }

    function pad2(c) {
        return 1 == c.length ? "0" + c : "" + c
    }

    function convertToPercentage(n) {
        return 1 >= n && (n = 100 * n + "%"), n
    }

    function stringInputToObject(color) {
        color = color.replace(trimLeft, "").replace(trimRight, "").toLowerCase();
        var named = !1;
        if (names[color])color = names[color], named = !0; else if ("transparent" == color)return {r: 0, g: 0, b: 0, a: 0, format: "name"};
        var match;
        return (match = matchers.rgb.exec(color)) ? {r: match[1], g: match[2], b: match[3]} : (match = matchers.rgba.exec(color)) ? {
            r: match[1],
            g: match[2],
            b: match[3],
            a: match[4]
        } : (match = matchers.hsl.exec(color)) ? {h: match[1], s: match[2], l: match[3]} : (match = matchers.hsla.exec(color)) ? {
            h: match[1],
            s: match[2],
            l: match[3],
            a: match[4]
        } : (match = matchers.hsv.exec(color)) ? {
            h: match[1],
            s: match[2],
            v: match[3]
        } : (match = matchers.hex6.exec(color)) ? {
            r: parseHex(match[1]),
            g: parseHex(match[2]),
            b: parseHex(match[3]),
            format: named ? "name" : "hex"
        } : (match = matchers.hex3.exec(color)) ? {
            r: parseHex(match[1] + "" + match[1]),
            g: parseHex(match[2] + "" + match[2]),
            b: parseHex(match[3] + "" + match[3]),
            format: named ? "name" : "hex"
        } : !1
    }

    var trimLeft = /^[\s,#]+/, trimRight = /\s+$/, tinyCounter = 0, math = Math, mathRound = math.round, mathMin = math.min, mathMax = math.max, mathRandom = math.random;
    tinycolor.fromRatio = function (color, opts) {
        if ("object" == typeof color) {
            var newColor = {};
            for (var i in color)color.hasOwnProperty(i) && (newColor[i] = "a" === i ? color[i] : convertToPercentage(color[i]));
            color = newColor
        }
        return tinycolor(color, opts)
    }, tinycolor.equals = function (color1, color2) {
        return color1 && color2 ? tinycolor(color1).toRgbString() == tinycolor(color2).toRgbString() : !1
    }, tinycolor.random = function () {
        return tinycolor.fromRatio({r: mathRandom(), g: mathRandom(), b: mathRandom()})
    }, tinycolor.desaturate = function (color, amount) {
        amount = 0 === amount ? 0 : amount || 10;
        var hsl = tinycolor(color).toHsl();
        return hsl.s -= amount / 100, hsl.s = clamp01(hsl.s), tinycolor(hsl)
    }, tinycolor.saturate = function (color, amount) {
        amount = 0 === amount ? 0 : amount || 10;
        var hsl = tinycolor(color).toHsl();
        return hsl.s += amount / 100, hsl.s = clamp01(hsl.s), tinycolor(hsl)
    }, tinycolor.greyscale = function (color) {
        return tinycolor.desaturate(color, 100)
    }, tinycolor.lighten = function (color, amount) {
        amount = 0 === amount ? 0 : amount || 10;
        var hsl = tinycolor(color).toHsl();
        return hsl.l += amount / 100, hsl.l = clamp01(hsl.l), tinycolor(hsl)
    }, tinycolor.darken = function (color, amount) {
        amount = 0 === amount ? 0 : amount || 10;
        var hsl = tinycolor(color).toHsl();
        return hsl.l -= amount / 100, hsl.l = clamp01(hsl.l), tinycolor(hsl)
    }, tinycolor.complement = function (color) {
        var hsl = tinycolor(color).toHsl();
        return hsl.h = (hsl.h + 180) % 360, tinycolor(hsl)
    }, tinycolor.triad = function (color) {
        var hsl = tinycolor(color).toHsl(), h = hsl.h;
        return [tinycolor(color), tinycolor({h: (h + 120) % 360, s: hsl.s, l: hsl.l}), tinycolor({h: (h + 240) % 360, s: hsl.s, l: hsl.l})]
    }, tinycolor.tetrad = function (color) {
        var hsl = tinycolor(color).toHsl(), h = hsl.h;
        return [tinycolor(color), tinycolor({h: (h + 90) % 360, s: hsl.s, l: hsl.l}), tinycolor({
            h: (h + 180) % 360,
            s: hsl.s,
            l: hsl.l
        }), tinycolor({h: (h + 270) % 360, s: hsl.s, l: hsl.l})]
    }, tinycolor.splitcomplement = function (color) {
        var hsl = tinycolor(color).toHsl(), h = hsl.h;
        return [tinycolor(color), tinycolor({h: (h + 72) % 360, s: hsl.s, l: hsl.l}), tinycolor({h: (h + 216) % 360, s: hsl.s, l: hsl.l})]
    }, tinycolor.analogous = function (color, results, slices) {
        results = results || 6, slices = slices || 30;
        var hsl = tinycolor(color).toHsl(), part = 360 / slices, ret = [tinycolor(color)];
        for (hsl.h = (hsl.h - (part * results >> 1) + 720) % 360; --results;)hsl.h = (hsl.h + part) % 360, ret.push(tinycolor(hsl));
        return ret
    }, tinycolor.monochromatic = function (color, results) {
        results = results || 6;
        for (var hsv = tinycolor(color).toHsv(), h = hsv.h, s = hsv.s, v = hsv.v, ret = [], modification = 1 / results; results--;)ret.push(tinycolor({
            h: h,
            s: s,
            v: v
        })), v = (v + modification) % 1;
        return ret
    }, tinycolor.readability = function (color1, color2) {
        var a = tinycolor(color1).toRgb(), b = tinycolor(color2).toRgb(), brightnessA = (299 * a.r + 587 * a.g + 114 * a.b) / 1e3, brightnessB = (299 * b.r + 587 * b.g + 114 * b.b) / 1e3, colorDiff = Math.max(a.r, b.r) - Math.min(a.r, b.r) + Math.max(a.g, b.g) - Math.min(a.g, b.g) + Math.max(a.b, b.b) - Math.min(a.b, b.b);
        return {brightness: Math.abs(brightnessA - brightnessB), color: colorDiff}
    }, tinycolor.readable = function (color1, color2) {
        var readability = tinycolor.readability(color1, color2);
        return readability.brightness > 125 && readability.color > 500
    }, tinycolor.mostReadable = function (baseColor, colorList) {
        for (var bestColor = null, bestScore = 0, bestIsReadable = !1, i = 0; colorList.length > i; i++) {
            var readability = tinycolor.readability(baseColor, colorList[i]), readable = readability.brightness > 125 && readability.color > 500, score = 3 * (readability.brightness / 125) + readability.color / 500;
            (readable && !bestIsReadable || readable && bestIsReadable && score > bestScore || !readable && !bestIsReadable && score > bestScore) && (bestIsReadable = readable, bestScore = score, bestColor = tinycolor(colorList[i]))
        }
        return bestColor
    };
    var names = tinycolor.names = {
        aliceblue: "f0f8ff",
        antiquewhite: "faebd7",
        aqua: "0ff",
        aquamarine: "7fffd4",
        azure: "f0ffff",
        beige: "f5f5dc",
        bisque: "ffe4c4",
        black: "000",
        blanchedalmond: "ffebcd",
        blue: "00f",
        blueviolet: "8a2be2",
        brown: "a52a2a",
        burlywood: "deb887",
        burntsienna: "ea7e5d",
        cadetblue: "5f9ea0",
        chartreuse: "7fff00",
        chocolate: "d2691e",
        coral: "ff7f50",
        cornflowerblue: "6495ed",
        cornsilk: "fff8dc",
        crimson: "dc143c",
        cyan: "0ff",
        darkblue: "00008b",
        darkcyan: "008b8b",
        darkgoldenrod: "b8860b",
        darkgray: "a9a9a9",
        darkgreen: "006400",
        darkgrey: "a9a9a9",
        darkkhaki: "bdb76b",
        darkmagenta: "8b008b",
        darkolivegreen: "556b2f",
        darkorange: "ff8c00",
        darkorchid: "9932cc",
        darkred: "8b0000",
        darksalmon: "e9967a",
        darkseagreen: "8fbc8f",
        darkslateblue: "483d8b",
        darkslategray: "2f4f4f",
        darkslategrey: "2f4f4f",
        darkturquoise: "00ced1",
        darkviolet: "9400d3",
        deeppink: "ff1493",
        deepskyblue: "00bfff",
        dimgray: "696969",
        dimgrey: "696969",
        dodgerblue: "1e90ff",
        firebrick: "b22222",
        floralwhite: "fffaf0",
        forestgreen: "228b22",
        fuchsia: "f0f",
        gainsboro: "dcdcdc",
        ghostwhite: "f8f8ff",
        gold: "ffd700",
        goldenrod: "daa520",
        gray: "808080",
        green: "008000",
        greenyellow: "adff2f",
        grey: "808080",
        honeydew: "f0fff0",
        hotpink: "ff69b4",
        indianred: "cd5c5c",
        indigo: "4b0082",
        ivory: "fffff0",
        khaki: "f0e68c",
        lavender: "e6e6fa",
        lavenderblush: "fff0f5",
        lawngreen: "7cfc00",
        lemonchiffon: "fffacd",
        lightblue: "add8e6",
        lightcoral: "f08080",
        lightcyan: "e0ffff",
        lightgoldenrodyellow: "fafad2",
        lightgray: "d3d3d3",
        lightgreen: "90ee90",
        lightgrey: "d3d3d3",
        lightpink: "ffb6c1",
        lightsalmon: "ffa07a",
        lightseagreen: "20b2aa",
        lightskyblue: "87cefa",
        lightslategray: "789",
        lightslategrey: "789",
        lightsteelblue: "b0c4de",
        lightyellow: "ffffe0",
        lime: "0f0",
        limegreen: "32cd32",
        linen: "faf0e6",
        magenta: "f0f",
        maroon: "800000",
        mediumaquamarine: "66cdaa",
        mediumblue: "0000cd",
        mediumorchid: "ba55d3",
        mediumpurple: "9370db",
        mediumseagreen: "3cb371",
        mediumslateblue: "7b68ee",
        mediumspringgreen: "00fa9a",
        mediumturquoise: "48d1cc",
        mediumvioletred: "c71585",
        midnightblue: "191970",
        mintcream: "f5fffa",
        mistyrose: "ffe4e1",
        moccasin: "ffe4b5",
        navajowhite: "ffdead",
        navy: "000080",
        oldlace: "fdf5e6",
        olive: "808000",
        olivedrab: "6b8e23",
        orange: "ffa500",
        orangered: "ff4500",
        orchid: "da70d6",
        palegoldenrod: "eee8aa",
        palegreen: "98fb98",
        paleturquoise: "afeeee",
        palevioletred: "db7093",
        papayawhip: "ffefd5",
        peachpuff: "ffdab9",
        peru: "cd853f",
        pink: "ffc0cb",
        plum: "dda0dd",
        powderblue: "b0e0e6",
        purple: "800080",
        red: "f00",
        rosybrown: "bc8f8f",
        royalblue: "4169e1",
        saddlebrown: "8b4513",
        salmon: "fa8072",
        sandybrown: "f4a460",
        seagreen: "2e8b57",
        seashell: "fff5ee",
        sienna: "a0522d",
        silver: "c0c0c0",
        skyblue: "87ceeb",
        slateblue: "6a5acd",
        slategray: "708090",
        slategrey: "708090",
        snow: "fffafa",
        springgreen: "00ff7f",
        steelblue: "4682b4",
        tan: "d2b48c",
        teal: "008080",
        thistle: "d8bfd8",
        tomato: "ff6347",
        turquoise: "40e0d0",
        violet: "ee82ee",
        wheat: "f5deb3",
        white: "fff",
        whitesmoke: "f5f5f5",
        yellow: "ff0",
        yellowgreen: "9acd32"
    }, hexNames = tinycolor.hexNames = flip(names), matchers = function () {
        var CSS_INTEGER = "[-\\+]?\\d+%?", CSS_NUMBER = "[-\\+]?\\d*\\.\\d+%?", CSS_UNIT = "(?:" + CSS_NUMBER + ")|(?:" + CSS_INTEGER + ")", PERMISSIVE_MATCH3 = "[\\s|\\(]+(" + CSS_UNIT + ")[,|\\s]+(" + CSS_UNIT + ")[,|\\s]+(" + CSS_UNIT + ")\\s*\\)?", PERMISSIVE_MATCH4 = "[\\s|\\(]+(" + CSS_UNIT + ")[,|\\s]+(" + CSS_UNIT + ")[,|\\s]+(" + CSS_UNIT + ")[,|\\s]+(" + CSS_UNIT + ")\\s*\\)?";
        return {
            rgb: RegExp("rgb" + PERMISSIVE_MATCH3),
            rgba: RegExp("rgba" + PERMISSIVE_MATCH4),
            hsl: RegExp("hsl" + PERMISSIVE_MATCH3),
            hsla: RegExp("hsla" + PERMISSIVE_MATCH4),
            hsv: RegExp("hsv" + PERMISSIVE_MATCH3),
            hex3: /^([0-9a-fA-F]{1})([0-9a-fA-F]{1})([0-9a-fA-F]{1})$/,
            hex6: /^([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/
        }
    }();
    "undefined" != typeof module && module.exports ? module.exports = tinycolor : "undefined" != typeof define ? define(function () {
        return tinycolor
    }) : root.tinycolor = tinycolor
})(this);

/*! Pick-a-Color v1.2.3 | Copyright 2013 Lauren Sperber and Broadstreet Ads https://github.com/lauren/pick-a-color/blob/master/LICENSE | pick-a-color 2014-04-24 */
/*
 * Pick-a-Color JS v1.2.3
 * Copyright 2013 Lauren Sperber and Broadstreet Ads
 * https://github.com/lauren/pick-a-color/blob/master/LICENSE
 */
;
(function ($) {
    "use strict";

    $.fn.pickAColor = function (options) {

        // capabilities

        var supportsTouch = 'ontouchstart' in window,
            smallScreen = (parseInt($(window).width(), 10) < 767) ? true : false,
            supportsLocalStorage = 'localStorage' in window && window.localStorage !== null &&
                typeof JSON === 'object', // don't use LS if JSON is not available
            isIELT10 = document.all && !window.atob, // OH NOES!

            startEvent = supportsTouch ? "touchstart.pickAColor" : "mousedown.pickAColor",
            moveEvent = supportsTouch ? "touchmove.pickAColor" : "mousemove.pickAColor",
            endEvent = supportsTouch ? "touchend.pickAColor" : "mouseup.pickAColor",
            clickEvent = supportsTouch ? "touchend.pickAColor" : "click.pickAColor",
            dragEvent = "dragging.pickAColor",
            endDragEvent = "endDrag.pickAColor";

        // settings

        var settings = $.extend({
            showSpectrum: true,
            showSavedColors: true,
            saveColorsPerElement: false,
            fadeMenuToggle: true,
            showAdvanced: true,
            showBasicColors: true,
            showHexInput: true,
            allowBlank: false,
            inlineDropdown: false,
            basicColors: {
                white: 'fff',
                red: 'f00',
                orange: 'f60',
                yellow: 'ff0',
                green: '008000',
                blue: '00f',
                purple: '800080',
                black: '000'
            }
        }, options);

        // override showBasicColors showAdvanced isn't shown
        if (!settings.showAdvanced && !settings.showBasicColors) {
            settings.showBasicColors = true;
        }

        var useTabs = (settings.showSavedColors && settings.showAdvanced) ||
            (settings.showBasicColors && settings.showSavedColors) ||
            (settings.showBasicColors && settings.showAdvanced);

        // so much markup

        var markupAfterInput = function () {
            var $markup = $("<div>").addClass("input-group-btn"),
                $dropdownButton = $("<button type='button'>").addClass("btn btn-default color-dropdown dropdown-toggle"),
                $dropdownColorPreview = $("<span>").addClass("color-preview current-color"),
                $dropdownCaret = $("<span>").addClass("caret"),
                $dropdownContainer = $("<div>").addClass("color-menu dropdown-menu");
            if (settings.inlineDropdown) {
                $dropdownContainer.addClass("color-menu--inline");
            }
            if (!settings.showHexInput) {
                $dropdownButton.addClass("no-hex");
                $dropdownContainer.addClass("no-hex");
            }
            $markup.append($dropdownButton.append($dropdownColorPreview).append($dropdownCaret));
            if (!useTabs && !settings.showSpectrum) {
                $dropdownContainer.addClass("small");
            }
            if (useTabs) {
                var $tabContainer = $("<div>").addClass("color-menu-tabs"),
                    savedColorsClass = settings.showBasicColors ? "savedColors-tab tab" : "savedColors-tab tab tab-active";
                if (settings.showBasicColors) {
                    $tabContainer.append($("<span>").addClass("basicColors-tab tab tab-active").
                        append($("<a>").text("Basic Colors")));
                }
                if (settings.showSavedColors) {
                    $tabContainer.append($("<span>").addClass(savedColorsClass).append($("<a>").text("Saved Colors")));
                }
                if (settings.showAdvanced) {
                    $tabContainer.append($("<span>").addClass("advanced-tab tab").
                        append($("<a>").text("Advanced")));
                }
                $dropdownContainer.append($tabContainer);
            }

            if (settings.showBasicColors) {
                var $basicColors = $("<div>").addClass("basicColors-content active-content");
                if (settings.showSpectrum) {
                    $basicColors.append($("<h6>").addClass("color-menu-instructions").
                        text("Tap spectrum or drag band to change color"));
                }
                var $listContainer = $("<ul>").addClass("basic-colors-list");
                $.each(settings.basicColors, function (index, value) {
                    var $thisColor = $("<li>").addClass("color-item"),
                        $thisLink = $("<a>").addClass(index + " color-link"),
                        $colorPreview = $("<span>").addClass("color-preview " + index),
                        $colorLabel = $("<span>").addClass("color-label").text(index);

                    $thisLink.append($colorPreview, $colorLabel);
                    $colorPreview.append();
                    if (value[0] !== '#') {
                        value = '#' + value;
                    }
                    $colorPreview.css('background-color', value);

                    if (settings.showSpectrum) {
                        var $thisSpectrum = $("<span>").addClass("color-box spectrum-" + index);
                        if (isIELT10) {
                            $.each([0, 1], function (i) {
                                if (value !== "fff" && index !== "000")
                                    $thisSpectrum.append($("<span>").addClass(index + "-spectrum-" + i +
                                    " ie-spectrum"));
                            });
                        }
                        var $thisHighlightBand = $("<span>").addClass("highlight-band");
                        $.each([0, 1, 2], function () {
                            $thisHighlightBand.append($("<span>").addClass("highlight-band-stripe"));
                        });
                        $thisLink.append($thisSpectrum.append($thisHighlightBand));
                    }
                    $listContainer.append($thisColor.append($thisLink));
                });
                $dropdownContainer.append($basicColors.append($listContainer));
            }

            if (settings.showSavedColors) {
                var savedColorsActiveClass = settings.showBasicColors ? 'inactive-content' : 'active-content',
                    $savedColors = $("<div>").addClass("savedColors-content").addClass(savedColorsActiveClass);
                $savedColors.append($("<p>").addClass("saved-colors-instructions").
                    text("Type in a color or use the spectrums to lighten or darken an existing color."));
                $dropdownContainer.append($savedColors);
            }

            if (settings.showAdvanced) {
                var advancedColorsActiveClass = settings.showBasicColors || settings.showSavedColors ? 'inactive-content' : 'active-content';
                var $advanced = $("<div>").addClass("advanced-content").addClass(advancedColorsActiveClass).
                        append($("<h6>").addClass("advanced-instructions").text("Tap spectrum or drag band to change color")),
                    $advancedList = $("<ul>").addClass("advanced-list"),
                    $hueItem = $("<li>").addClass("hue-item"),
                    $hueContent = $("<span>").addClass("hue-text").
                        text("Hue: ").append($("<span>").addClass("hue-value").text("0"));
                var $hueSpectrum = $("<span>").addClass("color-box spectrum-hue");
                if (isIELT10) {
                    $.each([0, 1, 2, 3, 4, 5, 6], function (i) {
                        $hueSpectrum.append($("<span>").addClass("hue-spectrum-" + i +
                        " ie-spectrum hue"));
                    });
                }
                var $hueHighlightBand = $("<span>").addClass("highlight-band");
                $.each([0, 1, 2], function () {
                    $hueHighlightBand.append($("<span>").addClass("highlight-band-stripe"));
                });
                $advancedList.append($hueItem.append($hueContent).append($hueSpectrum.append($hueHighlightBand)));
                var $lightnessItem = $("<li>").addClass("lightness-item"),
                    $lightnessSpectrum = $("<span>").addClass("color-box spectrum-lightness"),
                    $lightnessContent = $("<span>").addClass("lightness-text").
                        text("Lightness: ").append($("<span>").addClass("lightness-value").text("50%"));
                if (isIELT10) {
                    $.each([0, 1], function (i) {
                        $lightnessSpectrum.append($("<span>").addClass("lightness-spectrum-" + i +
                        " ie-spectrum"));
                    });
                }
                var $lightnessHighlightBand = $("<span>").addClass("highlight-band");
                $.each([0, 1, 2], function () {
                    $lightnessHighlightBand.append($("<span>").addClass("highlight-band-stripe"));
                });
                $advancedList.append($lightnessItem.
                    append($lightnessContent).append($lightnessSpectrum.append($lightnessHighlightBand)));
                var $saturationItem = $("<li>").addClass("saturation-item"),
                    $saturationSpectrum = $("<span>").addClass("color-box spectrum-saturation");
                if (isIELT10) {
                    $.each([0, 1], function (i) {
                        $saturationSpectrum.append($("<span>").addClass("saturation-spectrum-" + i +
                        " ie-spectrum"));
                    });
                }
                var $saturationHighlightBand = $("<span>").addClass("highlight-band");
                $.each([0, 1, 2], function () {
                    $saturationHighlightBand.append($("<span>").addClass("highlight-band-stripe"));
                });
                var $saturationContent = $("<span>").addClass("saturation-text").
                    text("Saturation: ").append($("<span>").addClass("saturation-value").text("100%"));
                $advancedList.append($saturationItem.append($saturationContent).append($saturationSpectrum.
                    append($saturationHighlightBand)));
                var $previewItem = $("<li>").addClass("preview-item").append($("<span>").
                        addClass("preview-text").text("Preview")),
                    $preview = $("<span>").addClass("color-preview advanced").
                        append("<button class='color-select btn btn-mini advanced' type='button'>Select</button>");
                $advancedList.append($previewItem.append($preview));
                $dropdownContainer.append($advanced.append($advancedList));
            }
            $markup.append($dropdownContainer);
            return $markup;
        };

        var myColorVars = {};

        var myStyleVars = {
            rowsInDropdown: 8,
            maxColsInDropdown: 2
        };

        if (settings.showSavedColors) { // if we're saving colors...
            var allSavedColors = []; // make an array for all saved colors
            if (supportsLocalStorage && localStorage.allSavedColors) { // look for them in LS
                allSavedColors = JSON.parse(localStorage.allSavedColors);
                // if there's a saved_colors cookie...
            } else if (document.cookie.match("pickAColorSavedColors-allSavedColors=")) {
                var theseCookies = document.cookie.split(";"); // split cookies into an array...

                $.each(theseCookies, function (index) { // find the savedColors cookie!
                    if (theseCookies[index].match("pickAColorSavedColors-allSavedColors=")) {
                        allSavedColors = theseCookies[index].split("=")[1].split(",");
                    }

                });
            }
        }


        // methods

        var methods = {

            initialize: function (index) {
                var $thisEl = $(this),
                    $thisParent,
                    myId,
                    defaultColor;

                // if there's no name on the input field, create one, then use it as the myID
                if (!$thisEl.attr("name")) {
                    $thisEl.attr("name", "pick-a-color-" + index);
                }
                myId = $thisEl.attr("name");

                // enforce .pick-a-color class on input
                $thisEl.addClass("pick-a-color");

                // convert default color to valid hex value
                if (settings.allowBlank) {
                    // convert to Hex only if the field init value is not blank
                    if (!$thisEl.val().match(/^\s+$|^$/)) {
                        myColorVars.defaultColor = tinycolor($thisEl.val()).toHex();
                        myColorVars.typedColor = myColorVars.defaultColor;
                        $thisEl.val(myColorVars.defaultColor);
                    }
                } else {
                    myColorVars.defaultColor = tinycolor($thisEl.val()).toHex();
                    myColorVars.typedColor = myColorVars.defaultColor;
                    $thisEl.val(myColorVars.defaultColor);
                }

                // wrap initializing input field with unique div and add hex symbol and post-input markup
                $($thisEl).wrap('<div class="input-group pick-a-color-markup" id="' + myId + '">');
                $thisParent = $($thisEl.parent());
                if (settings.showHexInput) {
                    $thisParent.prepend('<span class="hex-pound input-group-addon">#</span>').append(markupAfterInput());
                } else {
                    $thisParent.append(markupAfterInput());
                }

                // hide input for noinput option
                if (!settings.showHexInput) {
                    $thisEl.attr("type", "hidden");
                }
            },

            updatePreview: function ($thisEl) {
                if (!settings.allowBlank) {
                    myColorVars.typedColor = tinycolor($thisEl.val()).toHex();
                    $thisEl.siblings(".input-group-btn").find(".current-color").css("background-color",
                        "#" + myColorVars.typedColor);
                } else {
                    myColorVars.typedColor = $thisEl.val().match(/^\s+$|^$/) ? '' : tinycolor($thisEl.val()).toHex();
                    if (myColorVars.typedColor === '') {
                        $thisEl.siblings(".input-group-btn").find(".current-color").css("background",
                            "none");
                    } else {
                        $thisEl.siblings(".input-group-btn").find(".current-color").css("background-color",
                            "#" + myColorVars.typedColor);
                    }
                }
            },

            // must be called with apply and an arguments array like [{thisEvent}]
            pressPreviewButton: function () {
                var thisEvent = arguments[0].thisEvent;
                thisEvent.stopPropagation();
                methods.toggleDropdown(thisEvent.target);
            },

            openDropdown: function (button, menu) {
                $(".color-menu").each(function () { // check all the other color menus...
                    var $thisEl = $(this);

                    if ($thisEl.css("display") === "block") { // if one is open,
                        // find its color preview button
                        var thisColorPreviewButton = $thisEl.parents(".input-group-btn");
                        methods.closeDropdown(thisColorPreviewButton, $thisEl); // close it
                    }
                });

                if (settings.fadeMenuToggle && !supportsTouch) { //fades look terrible in mobile
                    $(menu).fadeIn("fast");
                } else {
                    $(menu).show();
                }

                $(button).addClass("open");
            },

            closeDropdown: function (button, menu) {
                if (settings.fadeMenuToggle && !supportsTouch) { //fades look terrible in mobile
                    $(menu).fadeOut("fast");
                } else {
                    $(menu).css("display", "none");
                }

                $(button).removeClass("open");
            },

            // can only be called with apply. requires an arguments array like:
            // [{button, menu}]
            closeDropdownIfOpen: function () {
                var button = arguments[0].button,
                    menu = arguments[0].menu;
                if (menu.css("display") === "block") {
                    methods.closeDropdown(button, menu);
                }
            },

            toggleDropdown: function (element) {
                var $container = $(element).parents(".pick-a-color-markup"),
                    $input = $container.find("input"),
                    $button = $container.find(".input-group-btn"),
                    $menu = $container.find(".color-menu");
                if (!$input.is(":disabled") && $menu.css("display") === "none") {
                    methods.openDropdown($button, $menu);
                } else {
                    methods.closeDropdown($button, $menu);
                }
            },

            tabbable: function () {
                var $this_el = $(this),
                    $myContainer = $this_el.parents(".pick-a-color-markup");

                $this_el.click(function () {
                    var $this_el = $(this),
                    // interpret the associated content class from the tab class and get that content div
                        contentClass = $this_el.attr("class").split(" ")[0].split("-")[0] + "-content",
                        myContent = $this_el.parents(".dropdown-menu").find("." + contentClass);

                    if (!$this_el.hasClass("tab-active")) { // make all active tabs inactive
                        $myContainer.find(".tab-active").removeClass("tab-active");
                        // toggle visibility of active content
                        $myContainer.find(".active-content").
                            removeClass("active-content").addClass("inactive-content");
                        $this_el.addClass("tab-active"); // make current tab and content active
                        $(myContent).addClass("active-content").removeClass("inactive-content");
                    }
                });
            },

            // takes a color and the current position of the color band,
            // returns the value by which the color should be multiplied to
            // get the color currently being highlighted by the band

            getColorMultiplier: function (spectrumType, position, tab) {
                // position of the color band as a percentage of the width of the color box
                var spectrumWidth = (tab === "basic") ? parseInt($(".color-box").first().width(), 10) :
                    parseInt($(".advanced-list").find(".color-box").first().width(), 10);
                if (spectrumWidth === 0) { // in case the width isn't set correctly
                    if (tab === "basic") {
                        spectrumWidth = supportsTouch ? 160 : 200;
                    } else {
                        spectrumWidth = supportsTouch ? 160 : 300;
                    }
                }
                var halfSpectrumWidth = spectrumWidth / 2,
                    percentOfBox = position / spectrumWidth;

                // for spectrums that lighten and darken, recalculate percent of box relative
                // to the half of spectrum the highlight band is currently in
                if (spectrumType === "bidirectional") {
                    return (percentOfBox <= 0.5) ?
                    (1 - (position / halfSpectrumWidth)) / 2 :
                    -((position - halfSpectrumWidth) / halfSpectrumWidth) / 2;
                    // now that we're treating each half as an individual spectrum, both are darkenRight
                } else {
                    return (spectrumType === "darkenRight") ? -(percentOfBox / 2) : (percentOfBox / 2);
                }

            },

            // modifyHSLLightness based on ligten/darken in LESS
            // https://github.com/cloudhead/less.js/blob/master/dist/less-1.3.3.js#L1763

            modifyHSLLightness: function (HSL, multiplier) {
                var hsl = HSL;
                hsl.l += multiplier;
                hsl.l = Math.min(Math.max(0, hsl.l), 1);
                return tinycolor(hsl).toHslString();
            },

            // defines the area within which an element can be moved
            getMoveableArea: function ($element) {
                var dimensions = {},
                    $elParent = $element.parent(),
                    myWidth = $element.outerWidth(),
                    parentWidth = $elParent.width(), // don't include borders for parent width
                    parentLocation = $elParent.offset();
                dimensions.minX = parentLocation.left;
                dimensions.maxX = parentWidth - myWidth; //subtract myWidth to avoid pushing out of parent
                return dimensions;
            },

            moveHighlightBand: function ($highlightBand, moveableArea, e) {
                var hbWidth = $(".highlight-band").first().outerWidth(),
                    threeFourthsHBWidth = hbWidth * 0.75,
                    mouseX = supportsTouch ? e.originalEvent.pageX : e.pageX, // find the mouse!
                // mouse position relative to width of highlight-band
                    newPosition = mouseX - moveableArea.minX - threeFourthsHBWidth;

                // don't move beyond moveable area
                newPosition = Math.max(0, (Math.min(newPosition, moveableArea.maxX)));
                $highlightBand.css("position", "absolute");
                $highlightBand.css("left", newPosition);
            },

            horizontallyDraggable: function () {
                $(this).on(startEvent, function (event) {
                    event.preventDefault();
                    var $this_el = $(event.delegateTarget);
                    $this_el.css("cursor", "-webkit-grabbing");
                    $this_el.css("cursor", "-moz-grabbing");
                    var dimensions = methods.getMoveableArea($this_el);

                    $(document).on(moveEvent, function (e) {
                        $this_el.trigger(dragEvent);
                        methods.moveHighlightBand($this_el, dimensions, e);
                    }).on(endEvent, function (event) {
                        $(document).off(moveEvent); // for desktop
                        $(document).off(dragEvent);
                        $this_el.css("cursor", "-webkit-grab");
                        $this_el.css("cursor", "-moz-grab");
                        $this_el.trigger(endDragEvent);
                        $(document).off(endEvent);
                    });
                }).on(endEvent, function (event) {
                    event.stopPropagation();
                    $(document).off(moveEvent); // for mobile
                    $(document).off(dragEvent);
                });
            },

            modifyHighlightBand: function ($highlightBand, colorMultiplier, spectrumType) {
                var darkGrayHSL = {h: 0, s: 0, l: 0.05},
                    bwMidHSL = {h: 0, s: 0, l: 0.5},
                // change to color of band is opposite of change to color of spectrum
                    hbColorMultiplier = -colorMultiplier,
                    hbsColorMultiplier = hbColorMultiplier * 10, // needs to be either black or white
                    $hbStripes = $highlightBand.find(".highlight-band-stripe"),
                    newBandColor = (spectrumType === "lightenRight") ?
                        methods.modifyHSLLightness(bwMidHSL, hbColorMultiplier) :
                        methods.modifyHSLLightness(darkGrayHSL, hbColorMultiplier);
                $highlightBand.css("border-color", newBandColor);
                $hbStripes.css("background-color", newBandColor);
            },

            // must be called with apply and expects an arguments array like
            // [{type: "basic"}] or [{type: "advanced", hsl: {h,s,l}}]
            calculateHighlightedColor: function () {
                var $thisEl = $(this),
                    $thisParent = $thisEl.parent(),
                    hbWidth = $(".highlight-band").first().outerWidth(),
                    halfHBWidth = hbWidth / 2,
                    tab = arguments[0].type,
                    spectrumType,
                    colorHsl,
                    currentHue,
                    currentSaturation,
                    $advancedPreview,
                    $saturationSpectrum,
                    $hueSpectrum,
                    $lightnessValue;

                if (tab === "basic") {
                    // get the class of the parent color box and slice off "spectrum"
                    var colorName = $thisParent.attr("class").split("-")[2],
                        colorHex = settings.basicColors[colorName];
                    colorHsl = tinycolor(colorHex).toHsl();
                    switch (colorHex) {
                        case "fff":
                            spectrumType = "darkenRight";
                            break;
                        case "000":
                            spectrumType = "lightenRight";
                            break;
                        default:
                            spectrumType = "bidirectional";
                    }
                } else {
                    // re-set current L value to 0.5 because the color multiplier ligtens
                    // and darkens against the baseline value
                    var $advancedContainer = $thisEl.parents(".advanced-list");
                    currentSaturation = arguments[0].hsl.s;
                    $hueSpectrum = $advancedContainer.find(".spectrum-hue");
                    currentHue = arguments[0].hsl.h;
                    $saturationSpectrum = $advancedContainer.find(".spectrum-saturation");
                    $lightnessValue = $advancedContainer.find(".lightness-value");
                    $advancedPreview = $advancedContainer.find(".color-preview");
                    colorHsl = {"h": arguments[0].hsl.h, "l": 0.5, "s": arguments[0].hsl.s};
                    spectrumType = "bidirectional";
                }

                // midpoint of the current left position of the color band
                var highlightBandLocation = parseInt($thisEl.css("left"), 10) + halfHBWidth,
                    colorMultiplier = methods.getColorMultiplier(spectrumType, highlightBandLocation, tab),
                    highlightedColor = methods.modifyHSLLightness(colorHsl, colorMultiplier),
                    highlightedHex = "#" + tinycolor(highlightedColor).toHex(),
                    highlightedLightnessString = highlightedColor.split("(")[1].split(")")[0].split(",")[2],
                    highlightedLightness = (parseInt(highlightedLightnessString.split("%")[0], 10)) / 100;

                if (tab === "basic") {
                    $thisParent.siblings(".color-preview").css("background-color", highlightedHex);
                    // replace the color label with a 'select' button
                    $thisParent.prev('.color-label').replaceWith(
                        '<button class="color-select btn btn-mini" type="button">Select</button>');
                    if (spectrumType !== "darkenRight") {
                        methods.modifyHighlightBand($thisEl, colorMultiplier, spectrumType);
                    }
                } else {
                    $advancedPreview.css("background-color", highlightedHex);
                    $lightnessValue.text(highlightedLightnessString);
                    methods.updateSaturationStyles($saturationSpectrum, currentHue, highlightedLightness);
                    methods.updateHueStyles($hueSpectrum, currentSaturation, highlightedLightness);
                    methods.modifyHighlightBand($(".advanced-content .highlight-band"), colorMultiplier, spectrumType);
                }

                return (tab === "basic") ? tinycolor(highlightedColor).toHex() : highlightedLightness;
            },

            updateSavedColorPreview: function (elements) {
                $.each(elements, function (index) {
                    var $this_el = $(elements[index]),
                        thisColor = $this_el.attr("class");
                    $this_el.find(".color-preview").css("background-color", thisColor);
                });
            },

            updateSavedColorMarkup: function ($savedColorsContent, mySavedColors) {
                mySavedColors = mySavedColors ? mySavedColors : allSavedColors;
                if (settings.showSavedColors && mySavedColors.length > 0) {

                    if (!settings.saveColorsPerElement) {
                        $savedColorsContent = $(".savedColors-content");
                        mySavedColors = allSavedColors;
                    }

                    var maxSavedColors = myStyleVars.rowsInDropdown * myStyleVars.maxColsInDropdown;
                    mySavedColors = mySavedColors.slice(0, maxSavedColors);

                    var $col0 = $("<ul>").addClass("saved-color-col 0"),
                        $col1 = $("<ul>").addClass("saved-color-col 1");

                    $.each(mySavedColors, function (index, value) {
                        var $this_li = $("<li>").addClass("color-item"),
                            $this_link = $("<a>").addClass(value);
                        $this_link.append($("<span>").addClass("color-preview"));
                        $this_link.append($("<span>").addClass("color-label").text(value));
                        $this_li.append($this_link);
                        if (index % 2 === 0) {
                            $col0.append($this_li);
                        } else {
                            $col1.append($this_li);
                        }
                    });

                    $savedColorsContent.html($col0);
                    $savedColorsContent.append($col1);

                    var savedColorLinks = $($savedColorsContent).find("a");
                    methods.updateSavedColorPreview(savedColorLinks);

                }
            },

            setSavedColorsCookie: function (savedColors, savedColorsDataAttr) {
                var now = new Date(),
                    tenYearsInMilliseconds = 315360000000,
                    expiresOn = new Date(now.getTime() + tenYearsInMilliseconds);
                expiresOn = expiresOn.toGMTString();

                if (typeof savedColorsDataAttr === "undefined") {
                    document.cookie = "pickAColorSavedColors-allSavedColors=" + savedColors +
                    ";expires=" + expiresOn;
                } else {
                    document.cookie = "pickAColorSavedColors-" + savedColorsDataAttr + "=" +
                    savedColors + "; expires=" + expiresOn;
                }
            },

            saveColorsToLocalStorage: function (savedColors, savedColorsDataAttr) {
                if (supportsLocalStorage) {
                    // if there is no data attribute, save to allSavedColors
                    if (typeof savedColorsDataAttr === "undefined") {
                        try {
                            localStorage.allSavedColors = JSON.stringify(savedColors);
                        }
                        catch (e) {
                            localStorage.clear();
                        }
                    } else { // otherwise save to a data attr-specific item
                        try {
                            localStorage["pickAColorSavedColors-" + savedColorsDataAttr] =
                                JSON.stringify(savedColors);
                        }
                        catch (e) {
                            localStorage.clear();
                        }
                    }
                } else {
                    methods.setSavedColorsCookie(savedColors, savedColorsDataAttr);
                }
            },

            removeFromArray: function (array, item) {
                if ($.inArray(item, array) !== -1) { // make sure it's in there
                    array.splice($.inArray(item, array), 1);
                }
            },

            updateSavedColors: function (color, savedColors, savedColorsDataAttr) {
                methods.removeFromArray(savedColors, color);
                savedColors.unshift(color);
                methods.saveColorsToLocalStorage(savedColors, savedColorsDataAttr);
            },

            // when settings.saveColorsPerElement, colors are saved to both mySavedColors and
            // allSavedColors so they will be avail to color pickers with no savedColorsDataAttr
            addToSavedColors: function (color, mySavedColorsInfo, $mySavedColorsContent) {
                if (settings.showSavedColors && color !== undefined) { // make sure we're saving colors
                    if (color[0] != "#") {
                        color = "#" + color;
                    }
                    methods.updateSavedColors(color, allSavedColors);
                    if (settings.saveColorsPerElement) { // if we're saving colors per element...
                        var mySavedColors = mySavedColorsInfo.colors,
                            dataAttr = mySavedColorsInfo.dataAttr;
                        methods.updateSavedColors(color, mySavedColors, dataAttr);
                        methods.updateSavedColorMarkup($mySavedColorsContent, mySavedColors);
                    } else { // if not saving per element, update markup with allSavedColors
                        methods.updateSavedColorMarkup($mySavedColorsContent, allSavedColors);
                    }
                }
            },

            // handles selecting a color from the basic menu of colors.
            // must be called with apply and relies on an arguments array like:
            // [{els, savedColorsInfo}]
            selectFromBasicColors: function () {
                var selectedColor = $(this).find("span:first").css("background-color"),
                    myElements = arguments[0].els,
                    mySavedColorsInfo = arguments[0].savedColorsInfo;
                selectedColor = tinycolor(selectedColor).toHex();
                $(myElements.thisEl).val(selectedColor);
                $(myElements.thisEl).trigger("change");
                methods.updatePreview(myElements.thisEl);
                methods.addToSavedColors(selectedColor, mySavedColorsInfo, myElements.savedColorsContent);
                methods.closeDropdown(myElements.colorPreviewButton, myElements.colorMenu); // close the dropdown
            },

            // handles user clicking or tapping on spectrum to select a color.
            // must be called with apply and relies on an arguments array like:
            // [{thisEvent, savedColorsInfo, els, mostRecentClick}]
            tapSpectrum: function () {
                var thisEvent = arguments[0].thisEvent,
                    mySavedColorsInfo = arguments[0].savedColorsInfo,
                    myElements = arguments[0].els,
                    mostRecentClick = arguments[0].mostRecentClick;
                thisEvent.stopPropagation(); // stop this click from closing the dropdown
                var $highlightBand = $(this).find(".highlight-band"),
                    dimensions = methods.getMoveableArea($highlightBand);
                if (supportsTouch) {
                    methods.moveHighlightBand($highlightBand, dimensions, mostRecentClick);
                } else {
                    methods.moveHighlightBand($highlightBand, dimensions, thisEvent);
                }
                var highlightedColor = methods.calculateHighlightedColor.apply($highlightBand, [{type: "basic"}]);
                methods.addToSavedColors(highlightedColor, mySavedColorsInfo, myElements.savedColorsContent);
                // update touch instructions
                myElements.touchInstructions.html("Press 'select' to choose this color");
            },

            // bind to mousedown/touchstart, execute provied function if the top of the
            // window has not moved when there is a mouseup/touchend
            // must be called with apply and an arguments array like:
            // [{thisFunction, theseArguments}]
            executeUnlessScrolled: function () {
                var thisFunction = arguments[0].thisFunction,
                    theseArguments = arguments[0].theseArguments,
                    windowTopPosition,
                    mostRecentClick;
                $(this).on(startEvent, function (e) {
                    windowTopPosition = $(window).scrollTop(); // save to see if user is scrolling in mobile
                    mostRecentClick = e;
                }).on(clickEvent, function (event) {
                    var distance = windowTopPosition - $(window).scrollTop();
                    if (supportsTouch && (Math.abs(distance) > 0)) {
                        return false;
                    } else {
                        theseArguments.thisEvent = event; //add the click event to the arguments object
                        theseArguments.mostRecentClick = mostRecentClick; //add start event to the arguments object
                        thisFunction.apply($(this), [theseArguments]);
                    }
                });
            },

            updateSaturationStyles: function (spectrum, hue, lightness) {
                var lightnessString = (lightness * 100).toString() + "%",
                    start = "#" + tinycolor("hsl(" + hue + ",0%," + lightnessString).toHex(),
                    mid = "#" + tinycolor("hsl(" + hue + ",50%," + lightnessString).toHex(),
                    end = "#" + tinycolor("hsl(" + hue + ",100%," + lightnessString).toHex(),
                    fullSpectrumString = "",
                    standard = $.each(["-webkit-linear-gradient", "-o-linear-gradient"], function (index, value) {
                        fullSpectrumString += "background-image: " + value + "(left, " + start + " 0%, " + mid + " 50%, " + end + " 100%);";
                    }),
                    ieSpectrum0 = "progid:DXImageTransform.Microsoft.gradient(startColorstr='" + start + "', endColorstr='" +
                        mid + "', GradientType=1)",
                    ieSpectrum1 = "progid:DXImageTransform.Microsoft.gradient(startColorstr='" + mid + "', endColorstr='" +
                        end + "', GradientType=1)";
                fullSpectrumString =
                    "background-image: -moz-linear-gradient(left center, " + start + " 0%, " + mid + " 50%, " + end + " 100%);" +
                    "background-image: linear-gradient(to right, " + start + " 0%, " + mid + " 50%, " + end + " 100%); " +
                    "background-image: -webkit-gradient(linear, left top, right top," +
                    "color-stop(0, " + start + ")," + "color-stop(0.5, " + mid + ")," + "color-stop(1, " + end + "));" +
                    fullSpectrumString;
                if (isIELT10) {
                    var $spectrum0 = $(spectrum).find(".saturation-spectrum-0");
                    var $spectrum1 = $(spectrum).find(".saturation-spectrum-1");
                    $spectrum0.css("filter", ieSpectrum0);
                    $spectrum1.css("filter", ieSpectrum1);
                } else {
                    spectrum.attr("style", fullSpectrumString);
                }
            },

            updateLightnessStyles: function (spectrum, hue, saturation) {
                var saturationString = (saturation * 100).toString() + "%",
                    start = "#" + tinycolor("hsl(" + hue + "," + saturationString + ",100%)").toHex(),
                    mid = "#" + tinycolor("hsl(" + hue + "," + saturationString + ",50%)").toHex(),
                    end = "#" + tinycolor("hsl(" + hue + "," + saturationString + ",0%)").toHex(),
                    fullSpectrumString = "",
                    standard = $.each(["-webkit-linear-gradient", "-o-linear-gradient"], function (index, value) {
                        fullSpectrumString += "background-image: " + value + "(left, " + start + " 0%, " + mid + " 50%, "
                        + end + " 100%);";
                    }),
                    ieSpectrum0 = "progid:DXImageTransform.Microsoft.gradient(startColorstr='" + start + "', endColorstr='" +
                        mid + "', GradientType=1)",
                    ieSpectrum1 = "progid:DXImageTransform.Microsoft.gradient(startColorstr='" + mid + "', endColorstr='" +
                        end + "', GradientType=1)";
                fullSpectrumString =
                    "background-image: -moz-linear-gradient(left center, " + start + " 0%, " + mid + " 50%, " + end + " 100%); " +
                    "background-image: linear-gradient(to right, " + start + " 0%, " + mid + " 50%, " + end + " 100%); " +
                    "background-image: -webkit-gradient(linear, left top, right top," +
                    " color-stop(0, " + start + ")," + " color-stop(0.5, " + mid + ")," + " color-stop(1, " + end + ")); " +
                    fullSpectrumString;
                if (isIELT10) {
                    var $spectrum0 = $(spectrum).find(".lightness-spectrum-0");
                    var $spectrum1 = $(spectrum).find(".lightness-spectrum-1");
                    $spectrum0.css("filter", ieSpectrum0);
                    $spectrum1.css("filter", ieSpectrum1);
                } else {
                    spectrum.attr("style", fullSpectrumString);
                }
            },

            updateHueStyles: function (spectrum, saturation, lightness) {
                var saturationString = (saturation * 100).toString() + "%",
                    lightnessString = (lightness * 100).toString() + "%",
                    color1 = "#" + tinycolor("hsl(0," + saturationString + "," + lightnessString + ")").toHex(),
                    color2 = "#" + tinycolor("hsl(60," + saturationString + "," + lightnessString + ")").toHex(),
                    color3 = "#" + tinycolor("hsl(120," + saturationString + "," + lightnessString + ")").toHex(),
                    color4 = "#" + tinycolor("hsl(180," + saturationString + "," + lightnessString + ")").toHex(),
                    color5 = "#" + tinycolor("hsl(240," + saturationString + "," + lightnessString + ")").toHex(),
                    color6 = "#" + tinycolor("hsl(300," + saturationString + "," + lightnessString + ")").toHex(),
                    color7 = "#" + tinycolor("hsl(0," + saturationString + "," + lightnessString + ")").toHex(),
                    ieSpectrum0 = "progid:DXImageTransform.Microsoft.gradient(startColorstr='" + color1 + "', endColorstr='" +
                        color2 + "', GradientType=1)",
                    ieSpectrum1 = "progid:DXImageTransform.Microsoft.gradient(startColorstr='" + color2 + "', endColorstr='" +
                        color3 + "', GradientType=1)",
                    ieSpectrum2 = "progid:DXImageTransform.Microsoft.gradient(startColorstr='" + color3 + "', endColorstr='" +
                        color4 + "', GradientType=1)",
                    ieSpectrum3 = "progid:DXImageTransform.Microsoft.gradient(startColorstr='" + color4 + "', endColorstr='" +
                        color5 + "', GradientType=1)",
                    ieSpectrum4 = "progid:DXImageTransform.Microsoft.gradient(startColorstr='" + color5 + "', endColorstr='" +
                        color6 + "', GradientType=1)",
                    ieSpectrum5 = "progid:DXImageTransform.Microsoft.gradient(startColorstr='" + color6 + "', endColorstr='" +
                        color7 + "', GradientType=1)",
                    fullSpectrumString = "",
                    standard = $.each(["-webkit-linear-gradient", "-o-linear-gradient"], function (index, value) {
                        fullSpectrumString += "background-image: " + value + "(left, " + color1 + " 0%, " + color2 + " 17%, " +
                        color3 + " 24%, " + color4 + " 51%, " + color5 + " 68%, " + color6 + " 85%, " + color7 + " 100%);";
                    });
                fullSpectrumString += "background-image: -webkit-gradient(linear, left top, right top," +
                "color-stop(0%, " + color1 + ")," + "color-stop(17%, " + color2 + ")," + "color-stop(34%, " + color3 + ")," +
                "color-stop(51%, " + color4 + ")," + "color-stop(68%, " + color5 + ")," + "color-stop(85%, " + color6 + ")," +
                "color-stop(100%, " + color7 + "));" +
                "background-image: linear-gradient(to right, " + color1 + " 0%, " + color2 + " 17%, " + color3 + " 24%," +
                color4 + " 51%," + color5 + " 68%," + color6 + " 85%," + color7 + " 100%); " +
                "background-image: -moz-linear-gradient(left center, " +
                color1 + " 0%, " + color2 + " 17%, " + color3 + " 24%, " + color4 + " 51%, " + color5 + " 68%, " +
                color6 + " 85%, " + color7 + " 100%);";
                if (isIELT10) {
                    var $spectrum0 = $(spectrum).find(".hue-spectrum-0"),
                        $spectrum1 = $(spectrum).find(".hue-spectrum-1"),
                        $spectrum2 = $(spectrum).find(".hue-spectrum-2"),
                        $spectrum3 = $(spectrum).find(".hue-spectrum-3"),
                        $spectrum4 = $(spectrum).find(".hue-spectrum-4"),
                        $spectrum5 = $(spectrum).find(".hue-spectrum-5");
                    $spectrum0.css("filter", ieSpectrum0);
                    $spectrum1.css("filter", ieSpectrum1);
                    $spectrum2.css("filter", ieSpectrum2);
                    $spectrum3.css("filter", ieSpectrum3);
                    $spectrum4.css("filter", ieSpectrum4);
                    $spectrum5.css("filter", ieSpectrum5);
                } else {
                    spectrum.attr("style", fullSpectrumString);

                }
            },

            // takes the position of a highlight band on the hue spectrum and finds highlighted hue
            // and updates the background of the lightness and saturation spectrums
            // relies on apply and an arguments array like [{h, s, l}]

            getHighlightedHue: function () {
                var $thisEl = $(this),
                    hbWidth = $thisEl.outerWidth(),
                    halfHBWidth = hbWidth / 2,
                    position = parseInt($thisEl.css("left"), 10) + halfHBWidth,
                    $advancedContainer = $thisEl.parents(".advanced-list"),
                    $advancedPreview = $advancedContainer.find(".color-preview"),
                    $lightnessSpectrum = $advancedContainer.find(".spectrum-lightness"),
                    $saturationSpectrum = $advancedContainer.find(".spectrum-saturation"),
                    spectrumWidth = parseInt($advancedContainer.find(".color-box").first().width(), 10),
                    $hueValue = $advancedContainer.find(".hue-value"),
                    currentLightness = arguments[0].l,
                    currentSaturation = arguments[0].s,
                    saturationString = (currentSaturation * 100).toString() + "%",
                    lightnessString = (currentLightness * 100).toString() + "%";

                if (spectrumWidth === 0) { // in case the width isn't set correctly
                    spectrumWidth = supportsTouch ? 160 : 300;
                }

                var hue = Math.floor((position / spectrumWidth) * 360),
                    color = "hsl(" + hue + "," + saturationString + "," + lightnessString + ")";
                color = "#" + tinycolor(color).toHex();

                $advancedPreview.css("background-color", color);
                $hueValue.text(hue);
                methods.updateLightnessStyles($lightnessSpectrum, hue, currentSaturation);
                methods.updateSaturationStyles($saturationSpectrum, hue, currentLightness);
                return hue;
            },

            // relies on apply and an arguments array like [{h, s, l}]

            getHighlightedSaturation: function () {
                var $thisEl = $(this),
                    hbWidth = $thisEl.outerWidth(),
                    halfHBWidth = hbWidth / 2,
                    position = parseInt($thisEl.css("left"), 10) + halfHBWidth,
                    $advancedContainer = $thisEl.parents(".advanced-list"),
                    $advancedPreview = $advancedContainer.find(".color-preview"),
                    $lightnessSpectrum = $advancedContainer.find(".spectrum-lightness"),
                    $hueSpectrum = $advancedContainer.find(".spectrum-hue"),
                    $saturationValue = $advancedContainer.find(".saturation-value"),
                    spectrumWidth = parseInt($advancedContainer.find(".color-box").first().width(), 10),
                    currentLightness = arguments[0].l,
                    lightnessString = (currentLightness * 100).toString() + "%",
                    currentHue = arguments[0].h;

                if (spectrumWidth === 0) { // in case the width isn't set correctly
                    spectrumWidth = supportsTouch ? 160 : 300;
                }

                var saturation = position / spectrumWidth,
                    saturationString = Math.round((saturation * 100)).toString() + "%",
                    color = "hsl(" + currentHue + "," + saturationString + "," + lightnessString + ")";
                color = "#" + tinycolor(color).toHex();

                $advancedPreview.css("background-color", color);
                $saturationValue.text(saturationString);
                methods.updateLightnessStyles($lightnessSpectrum, currentHue, saturation);
                methods.updateHueStyles($hueSpectrum, saturation, currentLightness);
                return saturation;
            },

            updateAdvancedInstructions: function (instructionsEl) {
                instructionsEl.html("Press the color preview to choose this color");
            }

        };

        return this.each(function (index) {

            methods.initialize.apply(this, [index]);

            // commonly used DOM elements for each color picker
            var myElements = {
                thisEl: $(this),
                thisWrapper: $(this).parent(),
                colorTextInput: $(this).find("input"),
                colorMenuLinks: $(this).parent().find(".color-menu li a"),
                colorPreviewButton: $(this).parent().find(".input-group-btn"),
                colorMenu: $(this).parent().find(".color-menu"),
                colorSpectrums: $(this).parent().find(".color-box"),
                basicSpectrums: $(this).parent().find(".basicColors-content .color-box"),
                touchInstructions: $(this).parent().find(".color-menu-instructions"),
                advancedInstructions: $(this).parent().find(".advanced-instructions"),
                highlightBands: $(this).parent().find(".highlight-band"),
                basicHighlightBands: $(this).parent().find(".basicColors-content .highlight-band")
            };

            var mostRecentClick, // for storing click events when needed
                windowTopPosition, // for storing the position of the top of the window when needed
                advancedStatus,
                mySavedColorsInfo;

            if (useTabs) {
                myElements.tabs = myElements.thisWrapper.find(".tab");
            }

            if (settings.showSavedColors) {
                myElements.savedColorsContent = myElements.thisWrapper.find(".savedColors-content");
                if (settings.saveColorsPerElement) { // when saving colors for each color picker...
                    mySavedColorsInfo = {
                        colors: [],
                        dataObj: $(this).data()
                    };
                    $.each(mySavedColorsInfo.dataObj, function (key) {
                        mySavedColorsInfo.dataAttr = key;
                    });

                    // get this picker's colors from local storage if possible
                    if (supportsLocalStorage && localStorage["pickAColorSavedColors-" +
                        mySavedColorsInfo.dataAttr]) {
                        mySavedColorsInfo.colors = JSON.parse(localStorage["pickAColorSavedColors-" +
                        mySavedColorsInfo.dataAttr]);

                        // otherwise, get them from cookies
                    } else if (document.cookie.match("pickAColorSavedColors-" +
                        mySavedColorsInfo.dataAttr)) {
                        var theseCookies = document.cookie.split(";"); // an array of cookies...
                        for (var i = 0; i < theseCookies.length; i++) {
                            if (theseCookies[i].match(mySavedColorsInfo.dataAttr)) {
                                mySavedColorsInfo.colors = theseCookies[i].split("=")[1].split(",");
                            }
                        }

                    } else { // if no data-attr specific colors are in local storage OR cookies...
                        mySavedColorsInfo.colors = allSavedColors; // use mySavedColors
                    }
                }
            }
            if (settings.showAdvanced) {
                advancedStatus = {
                    h: 0,
                    s: 1,
                    l: 0.5
                };

                myElements.advancedSpectrums = myElements.thisWrapper.find(".advanced-list").find(".color-box");
                myElements.advancedHighlightBands = myElements.thisWrapper.find(".advanced-list").find(".highlight-band");
                myElements.hueSpectrum = myElements.thisWrapper.find(".spectrum-hue");
                myElements.lightnessSpectrum = myElements.thisWrapper.find(".spectrum-lightness");
                myElements.saturationSpectrum = myElements.thisWrapper.find(".spectrum-saturation");
                myElements.hueHighlightBand = myElements.thisWrapper.find(".spectrum-hue .highlight-band");
                myElements.lightnessHighlightBand = myElements.thisWrapper.find(".spectrum-lightness .highlight-band");
                myElements.saturationHighlightBand = myElements.thisWrapper.find(".spectrum-saturation .highlight-band");
                myElements.advancedPreview = myElements.thisWrapper.find(".advanced-content .color-preview");
            }

            // add the default color to saved colors
            methods.addToSavedColors(myColorVars.defaultColor, mySavedColorsInfo, myElements.savedColorsContent);
            methods.updatePreview(myElements.thisEl);

            //input field focus: clear content
            // input field blur: update preview, restore previous content if no value entered

            // Prevent blur from not applying color selection
            var menuClicked = false;

            // prevent click/touchend to color-menu or color-text input from closing dropdown
            myElements.colorMenu.on(startEvent, function (e) {
                menuClicked = true;
            });

            myElements.thisEl.focus(function () {
                var $thisEl = $(this);
                myColorVars.typedColor = $thisEl.val(); // update with the current
                if (!settings.allowBlank) {
                    $thisEl.val(""); //clear the field on focus
                }
                methods.toggleDropdown(myElements.colorPreviewButton, myElements.ColorMenu);
            }).blur(function (event) {
                if (menuClicked) {
                    menuClicked = false;
                    return false;
                }

                var $thisEl = $(this);
                myColorVars.newValue = $thisEl.val(); // on blur, check the field's value
                // if the field is empty, put the original value back in the field
                if (myColorVars.newValue.match(/^\s+$|^$/)) {
                    if (!settings.allowBlank) {
                        $thisEl.val(myColorVars.typedColor);
                    }
                } else { // otherwise...
                    myColorVars.newValue = tinycolor(myColorVars.newValue).toHex(); // convert to hex
                    $thisEl.val(myColorVars.newValue); // put the new value in the field
                    // save to saved colors
                    methods.addToSavedColors(myColorVars.newValue, mySavedColorsInfo, myElements.savedColorsContent);
                }
                methods.toggleDropdown(myElements.colorPreviewButton, myElements.ColorMenu);
                methods.updatePreview($thisEl); // update preview
            });

            // toggle visibility of dropdown menu when you click or press the preview button
            methods.executeUnlessScrolled.apply(myElements.colorPreviewButton,
                [{"thisFunction": methods.pressPreviewButton, "theseArguments": {}}]);

            // any touch or click outside of a dropdown should close all dropdowns
            methods.executeUnlessScrolled.apply($(document), [{
                "thisFunction": methods.closeDropdownIfOpen,
                "theseArguments": {"button": myElements.colorPreviewButton, "menu": myElements.colorMenu}
            }]);

            // prevent click/touchend to color-menu or color-text input from closing dropdown
            myElements.colorMenu.on(clickEvent, function (e) {
                e.stopPropagation();
            });

            myElements.thisEl.on(clickEvent, function (e) {
                e.stopPropagation();
            });

            // update field and close menu when selecting from basic dropdown
            methods.executeUnlessScrolled.apply(myElements.colorMenuLinks, [{
                "thisFunction": methods.selectFromBasicColors,
                "theseArguments": {"els": myElements, "savedColorsInfo": mySavedColorsInfo}
            }]);

            if (useTabs) { // make tabs tabbable
                methods.tabbable.apply(myElements.tabs);
            }

            if (settings.showSpectrum || settings.showAdvanced) {
                methods.horizontallyDraggable.apply(myElements.highlightBands);
            }

            // for using the light/dark spectrums

            if (settings.showSpectrum) {

                // move the highlight band when you click on a spectrum

                methods.executeUnlessScrolled.apply(myElements.basicSpectrums, [{
                    "thisFunction": methods.tapSpectrum,
                    "theseArguments": {"savedColorsInfo": mySavedColorsInfo, "els": myElements}
                }]);

                $(myElements.basicHighlightBands).on(dragEvent, function (event) {
                    var $thisEl = event.target;
                    methods.calculateHighlightedColor.apply(this, [{type: "basic"}]);
                }).on(endDragEvent, function (event) {
                    var $thisEl = event.delegateTarget;
                    var finalColor = methods.calculateHighlightedColor.apply($thisEl, [{type: "basic"}]);
                    methods.addToSavedColors(finalColor, mySavedColorsInfo, myElements.savedColorsContent);
                });

            }

            if (settings.showAdvanced) {

                // for dragging advanced sliders


                $(myElements.hueHighlightBand).on(dragEvent, function (event) {
                    advancedStatus.h = methods.getHighlightedHue.apply(this, [advancedStatus]);
                });

                $(myElements.lightnessHighlightBand).on(dragEvent, function () {
                    methods.calculateHighlightedColor.apply(this, [{"type": "advanced", "hsl": advancedStatus}]);
                }).on(endEvent, function () {
                    advancedStatus.l = methods.calculateHighlightedColor.apply(this, [{"type": "advanced", "hsl": advancedStatus}]);
                });

                $(myElements.saturationHighlightBand).on(dragEvent, function () {
                    methods.getHighlightedSaturation.apply(this, [advancedStatus]);
                }).on(endDragEvent, function () {
                    advancedStatus.s = methods.getHighlightedSaturation.apply(this, [advancedStatus]);
                });

                $(myElements.advancedHighlightBand).on(endDragEvent, function () {
                    methods.updateAdvancedInstructions(myElements.advancedInstructions);
                });

                // for clicking/tapping advanced sliders

                $(myElements.lightnessSpectrum).click(function (event) {
                    event.stopPropagation(); // stop this click from closing the dropdown
                    var $highlightBand = $(this).find(".highlight-band"),
                        dimensions = methods.getMoveableArea($highlightBand);
                    methods.moveHighlightBand($highlightBand, dimensions, event);
                    advancedStatus.l = methods.calculateHighlightedColor.apply($highlightBand, [{"type": "advanced", "hsl": advancedStatus}]);
                });

                $(myElements.hueSpectrum).click(function (event) {
                    event.stopPropagation(); // stop this click from closing the dropdown
                    var $highlightBand = $(this).find(".highlight-band"),
                        dimensions = methods.getMoveableArea($highlightBand);
                    methods.moveHighlightBand($highlightBand, dimensions, event);
                    advancedStatus.h = methods.getHighlightedHue.apply($highlightBand, [advancedStatus]);
                });

                $(myElements.saturationSpectrum).click(function (event) {
                    event.stopPropagation(); // stop this click from closing the dropdown
                    var $highlightBand = $(this).find(".highlight-band"),
                        dimensions = methods.getMoveableArea($highlightBand);
                    methods.moveHighlightBand($highlightBand, dimensions, event);
                    advancedStatus.s = methods.getHighlightedSaturation.apply($highlightBand, [advancedStatus]);
                });

                $(myElements.advancedSpectrums).click(function () {
                    methods.updateAdvancedInstructions(myElements.advancedInstructions);
                });

                //for clicking advanced color preview

                $(myElements.advancedPreview).click(function () {
                    var selectedColor = tinycolor($(this).css("background-color")).toHex();
                    $(myElements.thisEl).val(selectedColor);
                    $(myElements.thisEl).trigger("change");
                    methods.updatePreview(myElements.thisEl);
                    methods.addToSavedColors(selectedColor, mySavedColorsInfo, myElements.savedColorsContent);
                    methods.closeDropdown(myElements.colorPreviewButton, myElements.colorMenu); // close the dropdown
                });
            }


            // for using saved colors

            if (settings.showSavedColors) {

                // make the links in saved colors work
                $(myElements.savedColorsContent).click(function (event) {
                    var $thisEl = $(event.target);

                    // make sure click happened on a link or span
                    if ($thisEl.is("SPAN") || $thisEl.is("A")) {
                        //grab the color the link's class or span's parent link's class
                        var selectedColor = $thisEl.is("SPAN") ?
                            $thisEl.parent().attr("class").split("#")[1] :
                            $thisEl.attr("class").split("#")[1];
                        $(myElements.thisEl).val(selectedColor);
                        $(myElements.thisEl).trigger("change");
                        methods.updatePreview(myElements.thisEl);
                        methods.closeDropdown(myElements.colorPreviewButton, myElements.colorMenu);
                        methods.addToSavedColors(selectedColor, mySavedColorsInfo, myElements.savedColorsContent);
                    }
                });

                // update saved color markup with content from localStorage or cookies, if available
                if (!settings.saveColorsPerElement) {
                    methods.updateSavedColorMarkup(myElements.savedColorsContent, allSavedColors);
                } else if (settings.saveColorsPerElement) {
                    methods.updateSavedColorMarkup(myElements.savedColorsContent, mySavedColorsInfo.colors);
                }
            }


        });

    };

})(jQuery);