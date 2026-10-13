import { test, expect } from '@playwright/test';
import { build } from 'esbuild';
import { readFileSync, writeFileSync } from 'node:fs';
import { PNG } from 'pngjs';
import pixelmatch from 'pixelmatch';
import { resolve } from 'node:path';
import { execFileSync } from 'node:child_process';
import { render } from './render.mjs';

test.describe.configure({ mode: 'parallel' });

const autoload = process.env.MAUTIC_VENDOR_AUTOLOAD ?? resolve('../../../../../vendor/autoload.php');
const php = String.raw`require $argv[1]; $minifier = str_ends_with($argv[2], '.js') ? new \MatthiasMullie\Minify\JS($argv[2]) : new \MatthiasMullie\Minify\CSS($argv[2]); echo $minifier->minify();`;
const nativeCss = execFileSync('php', ['-r', php, autoload, resolve('../css/99.animated-checkbox.css')], { encoding: 'utf8' });
const nativeScript = execFileSync('php', ['-r', php, autoload, resolve('../js/99.animated-checkbox.js')], { encoding: 'utf8' });

test('native dependency graph excludes React runtime', async () => {
  const graph = await build({ entryPoints: ['src/animated-checkbox.ts'], bundle: true, write: false, metafile: true });
  expect(Object.keys(graph.metafile.inputs).filter((path) => /node_modules\/(?:react|react-dom)\//.test(path))).toEqual([]);
});

const reference = await build({ entryPoints: ['tests/reference/checkbox.tsx'], bundle: true, write: false, format: 'iife', jsx: 'automatic', alias: { '@': resolve('tests/reference/src') }, define: { 'process.env.NODE_ENV': '"production"' } });
const stylesheet = readFileSync('tests/reference/checkbox.css', 'utf8');
const instant = Date.UTC(2024, 0, 1);

async function scene(page, original, options, dark) {
  await page.evaluate(() => {
    const originalAnimate = Element.prototype.animate;
    window.animationStarts = new WeakMap();
    Element.prototype.animate = function (...argumentsList) {
      const animation = originalAnimate.apply(this, argumentsList);
      let origin = performance.now();
      window.animationStarts.set(animation, origin);
      Object.defineProperty(animation, 'startTime', {
        get: () => origin,
        set: (value) => {
          origin = value ?? performance.now();
          window.animationStarts.set(animation, origin);
          animation.pause();
          animation.currentTime = performance.now() - origin;
        }
      });
      animation.pause();
      return animation;
    };
  });
  if (original) {
    await page.setContent(`<style>${stylesheet}</style><div id="reference"></div>`);
    await page.evaluate((options) => { window.referenceOptions = options; }, options);
    await page.addScriptTag({ content: reference.outputFiles[0].text });
  } else {
    await page.setContent(`<style>${nativeCss}</style>${render('animated-checkbox', { ...options, label: 'Accept' })}`);
    await page.addScriptTag({ content: nativeScript });
    await page.addStyleTag({ content: stylesheet });
  }
  await page.evaluate((dark) => {
    document.documentElement.classList.toggle('dark', dark);
  }, dark);
  await page.addStyleTag({ content: 'body { display:flex; margin:0; padding:64px; } #reference { display:contents; }' });
}

async function action(page, original, options) {
  await page.evaluate(({ original, options }) => {
    window.interactionTime = performance.now();
    const button = document.querySelector('[role="checkbox"]');
    if (options.checked !== undefined) {
      if (original) window.referenceUpdate(options);
      else window.MauticUI.AnimatedCheckbox.get(document.querySelector('[data-component]')).update(options);
    }
    if (options.pointer) button.dispatchEvent(new PointerEvent(options.pointer, { bubbles: true, pointerType: options.touch ? 'touch' : 'mouse', isPrimary: true, button: options.secondary ? 2 : 0 }));
    if (options.key) {
      button.focus();
      button.dispatchEvent(new KeyboardEvent(options.release ? 'keyup' : 'keydown', { bubbles: true, key: options.key }));
    }
    if (options.blur) button.blur();
    if (options.outside) window.dispatchEvent(new PointerEvent('pointerup', { bubbles: true, isPrimary: true }));
  }, { original, options });
}

async function frame(page) {
  return page.getByRole('checkbox').evaluate(async (button) => {
    window.animationStarts ??= new WeakMap();
    getComputedStyle(button).backgroundColor;
    const animations = document.getAnimations();
    for (const animation of animations) {
      if (!window.animationStarts.has(animation)) window.animationStarts.set(animation, animation instanceof CSSTransition ? window.interactionTime : animation.startTime);
      animation.pause();
      animation.currentTime = performance.now() - window.animationStarts.get(animation);
    }
    await Promise.all(animations.map((animation) => animation.ready));
    const shape = button.querySelector('path,line');
    const transform = getComputedStyle(button).transform;
    return {
      buttonStyle: button.getAttribute('style'),
      svg: button.querySelector('svg').getBoundingClientRect().toJSON(),
      attributes: Object.fromEntries([...shape.attributes].map((attribute) => [attribute.name, attribute.value])),
      scale: transform === 'none' ? 1 : new DOMMatrix(transform).a,
      shape: shape.tagName.toLowerCase(),
      background: getComputedStyle(button).backgroundColor,
      color: getComputedStyle(button).color,
      opacity: Number(getComputedStyle(shape).opacity),
      length: Number(shape.getAttribute('stroke-dasharray')?.split(' ')[0] ?? 0),
      offset: Number(shape.getAttribute('stroke-dashoffset') ?? 0)
    };
  });
}

const configurations = [false, true].flatMap((dark) =>
  ['no-preference', 'reduce'].flatMap((reducedMotion) =>
    ['default', 'sm', 'lg'].flatMap((size) =>
      ['default', 'accent'].map((variant) => ({ dark, reducedMotion, size, variant }))
    )
  )
);

for (const { dark, reducedMotion, size, variant } of configurations) {
  test(`original React animation trajectory: ${dark ? 'dark' : 'light'}, ${reducedMotion}, ${size}, ${variant}`, async ({ browser }, testInfo) => {
    test.setTimeout(120000);
    const evidence = [];
    const context = await browser.newContext({ reducedMotion });
    const original = await context.newPage();
    const native = await context.newPage();
    await original.clock.install({ time: new Date(instant) });
    await scene(original, true, { checked: false, size, variant }, dark);
    await scene(native, false, { checked: false, size, variant }, dark);
    await original.clock.pauseAt(new Date(instant + 1000));
    await original.clock.runFor(1000);
    const steps = [
      { pointer: 'pointerenter', frames: [16, 16, 32] }, { pointer: 'pointerdown', frames: [16, 16] }, { pointer: 'pointerleave', frames: [16, 32] }, { outside: true },
      { checked: true }, { checked: false }, { checked: 'indeterminate' }, { checked: true },
      { checked: 'indeterminate' }, { checked: false },
      { key: 'Enter' }, { key: 'Enter', release: true }, { key: 'Enter' }, { blur: true },
      { pointer: 'pointerenter', touch: true }, { pointer: 'pointerdown', secondary: true },
      { checked: true, frames: [16, 32, 192, 16] }, { checked: false, frames: [16, 32] }, { checked: true },
      { checked: 'indeterminate', frames: [16, 32] }, { checked: false }
    ];
    for (const [index, step] of steps.entries()) {
      await action(original, true, step);
      await action(native, false, step);
      let position = 0;
      for (const elapsed of step.frames ?? [16, 16, 32, 64, 64, 64, 128, 256]) {
        await original.clock.runFor(elapsed);
        position += elapsed;
        const expected = await frame(original);
        const actual = await frame(native);
        expect(actual.shape, `step ${index}`).toBe(expected.shape);
        for (const coordinate of ['x', 'y', 'width', 'height']) {
          expect(actual.svg[coordinate], `step ${index}, SVG ${coordinate}`).toBeCloseTo(expected.svg[coordinate], 4);
        }
        expect(Number.isFinite(Number(actual.attributes.opacity)), `step ${index}, valid SVG opacity`).toBe(true);
        expect(actual.background, `step ${index}, background`).toBe(expected.background);
        expect(actual.color, `step ${index}, color`).toBe(expected.color);
        for (const property of ['scale', 'opacity', 'length', 'offset']) {
          expect(actual[property], `step ${index}, ${property}: ${JSON.stringify({ actual, expected })}`).toBeCloseTo(expected[property], 4);
        }
        const clip = { x: 48, y: 48, width: 56, height: 56 };
        const first = await original.screenshot({ clip });
        const second = await native.screenshot({ clip });
        const left = PNG.sync.read(first);
        const right = PNG.sync.read(second);
        const changed = pixelmatch(left.data, right.data, null, left.width, left.height, { threshold: .05 });
        evidence.push({ step: index, position, expected, actual, changed });
        if (changed) console.log(JSON.stringify({ step: index, position, expected, actual, changed }));
        if (changed || position === 16) {
          writeFileSync(testInfo.outputPath(`step-${index}-${position}-react.png`), first);
          writeFileSync(testInfo.outputPath(`step-${index}-${position}-twig.png`), second);
          await testInfo.attach(`step-${index}-${position}-react`, { body: first, contentType: 'image/png' });
          await testInfo.attach(`step-${index}-${position}-twig`, { body: second, contentType: 'image/png' });
        }
        expect(changed, `step ${index}, ${position}ms screenshot`).toBe(0);
      }
    }
    writeFileSync(testInfo.outputPath('trajectory.json'), JSON.stringify(evidence, null, 2));
    expect(evidence.filter(({ step, actual }) => step === 4 && actual.opacity > 0 && actual.opacity < 1).length).toBeGreaterThan(0);
    expect(evidence.filter(({ step, actual }) => step === 6 && actual.length > 0 && actual.length < 1).length).toBeGreaterThan(0);
    await context.close();
  });
}
