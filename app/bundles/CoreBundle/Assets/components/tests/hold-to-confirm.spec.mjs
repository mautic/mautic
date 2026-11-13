import { test, expect } from '@playwright/test';
import { load, render } from './render.mjs';

async function monitor(page) {
  await page.evaluate(() => {
    window.confirms = 0;
    window.callbacks = 0;
    document.addEventListener('mautic:hold-to-confirm:confirm', () => window.confirms++);
    window.MauticUI.HoldToConfirmButton.get(document.querySelector('[data-component]')).update({ onConfirm: () => window.callbacks++ });
  });
}

async function press(page) {
  const box = await page.getByRole('button').boundingBox();
  await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
  await page.mouse.down();
}

test('default contract, translated labels and instructions', async ({ page }) => {
  await load(page, 'hold-to-confirm');
  await expect(page.getByRole('button')).toHaveAccessibleName('Hold to delete. Press and hold for 1.2 seconds to confirm');
  await expect(page.getByRole('button')).toHaveCSS('height', '40px');
  await expect(page.locator('[data-component]')).toHaveAttribute('data-reset-delay', '1500');
});

test('complete fires exactly once while held and resetDelay zero stays confirmed', async ({ page }) => {
  await load(page, 'hold-to-confirm', { duration: 120, resetDelay: 0 });
  await monitor(page);
  await press(page);
  await expect(page.locator('[data-component]')).toHaveAttribute('data-state', 'confirmed');
  await page.waitForTimeout(350);
  await page.mouse.up();
  expect(await page.evaluate(() => [window.confirms, window.callbacks])).toEqual([1, 1]);
  await expect(page.getByRole('status')).toHaveText('Confirmed');
  await expect(page.locator('[data-component]')).toHaveAttribute('data-state', 'confirmed');
});

test('release before completion cancels and unwinds', async ({ page }) => {
  await load(page, 'hold-to-confirm', { duration: 350 });
  await monitor(page);
  await press(page);
  await page.waitForTimeout(80);
  await page.mouse.up();
  await page.waitForTimeout(450);
  expect(await page.evaluate(() => window.confirms)).toBe(0);
  await expect(page.locator('[data-component]')).toHaveAttribute('data-state', 'idle');
  expect(Number(await page.locator('.hold-to-confirm__progress').evaluate((circle) => circle.style.strokeDashoffset))).toBeCloseTo(1, 2);
});

for (const key of ['Space', 'Enter']) {
  test(`keyboard hold with ${key}`, async ({ page }) => {
    await load(page, 'hold-to-confirm', { duration: 120, resetDelay: 0 });
    await monitor(page);
    await page.getByRole('button').focus();
    await page.keyboard.down(key);
    await expect(page.locator('[data-component]')).toHaveAttribute('data-state', 'confirmed');
    await page.keyboard.up(key);
    expect(await page.evaluate(() => window.confirms)).toBe(1);
  });
}

test('pointer and keyboard cooperate without restarting or cancelling each other', async ({ page }) => {
  await load(page, 'hold-to-confirm', { duration: 300, resetDelay: 0 });
  await monitor(page);
  await press(page);
  await page.waitForTimeout(100);
  await page.keyboard.down('Space');
  await page.mouse.up();
  await expect(page.locator('[data-component]')).toHaveAttribute('data-state', 'holding');
  await expect(page.locator('[data-component]')).toHaveAttribute('data-state', 'confirmed');
  await page.keyboard.up('Space');
  expect(await page.evaluate(() => window.confirms)).toBe(1);
});

test('blur and pointer cancellation cancel incomplete holds', async ({ page }) => {
  await load(page, 'hold-to-confirm', { duration: 200 });
  await monitor(page);
  await press(page);
  await page.getByRole('button').dispatchEvent('pointercancel');
  await page.mouse.up();
  await page.getByRole('button').focus();
  await page.keyboard.down('Enter');
  await page.getByRole('button').evaluate((button) => button.blur());
  await page.keyboard.up('Enter');
  await page.waitForTimeout(300);
  expect(await page.evaluate(() => window.confirms)).toBe(0);
});

test('automatic reset permits another independent confirmation', async ({ page }) => {
  await load(page, 'hold-to-confirm', { duration: 90, resetDelay: 150 });
  await monitor(page);
  await press(page);
  await expect(page.locator('[data-component]')).toHaveAttribute('data-state', 'confirmed');
  await page.mouse.up();
  await expect(page.locator('[data-component]')).toHaveAttribute('data-state', 'idle');
  await press(page);
  await expect.poll(() => page.evaluate(() => window.confirms)).toBe(2);
  await page.mouse.up();
});

test('disabled prevents confirmation and updates cancel an active hold', async ({ page }) => {
  await load(page, 'hold-to-confirm', { duration: 250, disabled: true });
  await monitor(page);
  await expect(page.getByRole('button')).toBeDisabled();
  await page.evaluate(() => window.MauticUI.HoldToConfirmButton.get(document.querySelector('[data-component]')).update({ disabled: false }));
  await press(page);
  await page.evaluate(() => window.MauticUI.HoldToConfirmButton.get(document.querySelector('[data-component]')).update({ disabled: true }));
  await page.mouse.up();
  await page.waitForTimeout(350);
  expect(await page.evaluate(() => window.confirms)).toBe(0);
});

test('removal cancels timers and callbacks', async ({ page }) => {
  await load(page, 'hold-to-confirm', { duration: 220 });
  await monitor(page);
  await press(page);
  await page.locator('[data-component]').evaluate((element) => element.remove());
  await page.mouse.up();
  await page.waitForTimeout(320);
  expect(await page.evaluate(() => window.confirms)).toBe(0);
});

test('beam theme, strength, active and disabled keep their distinct contracts', async ({ page }) => {
  await load(page, 'hold-to-confirm', { variant: 'beam', theme: 'dark', colorVariant: 'ocean', strength: .4, active: false });
  await expect(page.locator('[data-beam]')).toHaveCSS('--beam-strength', '0.4');
  expect(await page.locator('[data-beam]').evaluate((element) => element.hasAttribute('data-active'))).toBe(false);
  await page.evaluate(() => window.MauticUI.HoldToConfirmButton.get(document.querySelector('[data-component]')).update({ active: true }));
  expect(await page.locator('[data-beam]').evaluate((element) => element.hasAttribute('data-active'))).toBe(true);
  await page.evaluate(() => window.MauticUI.HoldToConfirmButton.get(document.querySelector('[data-component]')).update({ active: false }));
  await expect.poll(() => page.locator('[data-beam]').evaluate((element) => element.hasAttribute('data-active'))).toBe(false);
  await page.locator('[data-component]').evaluate((element) => element.remove());
  await expect(page.locator('head style[data-beam-style]')).toHaveCount(0);
});

test('reduced motion preserves duration and events', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await load(page, 'hold-to-confirm', { duration: 150, resetDelay: 0 });
  await monitor(page);
  await press(page);
  await expect(page.locator('[data-component]')).toHaveAttribute('data-state', 'holding');
  await expect(page.locator('[data-component]')).toHaveAttribute('data-state', 'confirmed');
  expect(await page.evaluate(() => window.confirms)).toBe(1);
  await page.mouse.up();
});

for (const [size, height] of [['sm', 32], ['md', 40], ['lg', 48]]) {
  test(`size ${size}`, async ({ page }) => {
    await load(page, 'hold-to-confirm', { size });
    await expect(page.getByRole('button')).toHaveCSS('height', `${height}px`);
    const geometry = await page.locator('[data-component]').evaluate((element) => {
      const button = element.querySelector('button');
      const symbol = element.querySelector('.hold-to-confirm__symbol').getBoundingClientRect();
      const label = element.querySelector('.hold-to-confirm__label').getBoundingClientRect();
      const status = element.querySelector('[role="status"]');
      return {gap: label.left - symbol.right, labelHeight: label.height, centerOffset: Math.abs(label.y + label.height / 2 - symbol.y - symbol.height / 2), statusWidth: status.getBoundingClientRect().width, statusInsideButton: button.contains(status)};
    });
    expect(geometry.gap).toBeCloseTo(8, 1);
    expect(geometry.labelHeight).toBeLessThan(height - 4);
    expect(geometry.centerOffset).toBeLessThan(1);
    expect(geometry.statusWidth).toBe(1);
    expect(geometry.statusInsideButton).toBe(false);
  });
}

for (const variant of ['neutral', 'beam']) {
  test(`${variant} confirmation retains success colors while hovered`, async ({ page }) => {
    await load(page, 'hold-to-confirm', { variant, duration: 100, resetDelay: 0 });
    await press(page);
    await expect(page.locator('[data-component]')).toHaveAttribute('data-state', 'confirmed');
    await page.mouse.up();
    await expect(page.getByRole('button')).toHaveCSS('background-color', 'rgb(255, 255, 255)');
    await expect(page.getByRole('button')).toHaveCSS('color', 'color(srgb 0.119216 0.413333 0.203922)');
  });
}

for (const variant of ['danger', 'neutral', 'beam']) {
  test(`${variant} success focus, hover and disabled states stay consistent`, async ({ page }) => {
    await load(page, 'hold-to-confirm', { variant, duration: 0, resetDelay: 0 });
    await page.addStyleTag({content: '.btn:focus,.btn.focus {outline:2px solid var(--focus);box-shadow:inset 0 0 0 1px var(--focus);} .btn:hover,.btn:active,.btn.active {background:red;color:white;border-color:red;} .btn:disabled {background:gray;color:rgb(80,80,80);border-color:gray;}'});
    const button = page.getByRole('button');
    await button.focus();
    await expect(button).toHaveCSS('outline-color', 'rgb(15, 98, 254)');
    await press(page);
    await expect(page.locator('[data-component]')).toHaveAttribute('data-state', 'confirmed');
    await expect(button).toHaveCSS('outline-color', 'color(srgb 0.119216 0.413333 0.203922)');
    await expect(button).toHaveCSS('border-color', 'color(srgb 0.119216 0.413333 0.203922)');
    await expect(button).toHaveCSS('color', 'color(srgb 0.119216 0.413333 0.203922)');
    await page.mouse.up();
    await button.evaluate(element => element.classList.add('active', 'focus'));
    await expect(button).toHaveCSS('background-color', 'rgb(255, 255, 255)');
    await expect(button).toHaveCSS('box-shadow', 'color(srgb 0.119216 0.413333 0.203922) 0px 0px 0px 1px inset');
    await page.evaluate(() => window.MauticUI.HoldToConfirmButton.get(document.querySelector('[data-component]')).update({ disabled: true }));
    await expect(button).toHaveCSS('color', 'rgb(80, 80, 80)');
    await expect(button).toBeDisabled();
    await button.evaluate(element => element.classList.remove('active', 'focus'));
    await page.evaluate(() => {
      const controller = window.MauticUI.HoldToConfirmButton.get(document.querySelector('[data-component]'));
      controller.update({ disabled: false });
      controller.reset();
    });
    await button.focus();
    await expect(button).toHaveCSS('outline-color', 'rgb(15, 98, 254)');
  });
}

test('right click and a short native click do not confirm', async ({ page }) => {
  await load(page, 'hold-to-confirm', { duration: 150, resetDelay: 0 });
  await monitor(page);
  await page.getByRole('button').click({ button: 'right' });
  await page.waitForTimeout(200);
  await page.getByRole('button').click();
  await page.waitForTimeout(200);
  expect(await page.evaluate(() => window.confirms)).toBe(0);
});

test('native button appearance and spinner opt-out survive updates', async ({ page }) => {
  await load(page, 'hold-to-confirm', { size: 'sm' });
  const button = page.getByRole('button');
  await expect(button).toHaveClass(/btn-nospin/);
  await expect(button).toHaveAttribute('size', 'sm');
  await page.addStyleTag({ content: '.btn {border-radius:3px;font-weight:400;padding:0 16px;gap:24px;font-size:14px;}' });
  await expect(button).toHaveCSS('border-radius', '3px');
  await expect(button).toHaveCSS('font-weight', '400');
  await expect(button).toHaveCSS('font-size', '14px');
  await expect(button).toHaveCSS('padding-left', '16px');
  await expect(button).toHaveCSS('gap', '8px');
  await page.evaluate(() => window.MauticUI.HoldToConfirmButton.get(document.querySelector('[data-component]')).update({ className: 'custom', size: 'lg' }));
  await expect(button).toHaveClass(/btn-nospin/);
  await expect(button).toHaveClass(/custom/);
  await expect(button).toHaveAttribute('size', 'lg');
  await expect(button).toHaveCSS('height', '48px');
});

test('window blur cancels a keyboard hold', async ({ page }) => {
  await load(page, 'hold-to-confirm', { duration: 200 });
  await monitor(page);
  await page.getByRole('button').focus();
  await page.keyboard.down('Enter');
  await page.evaluate(() => window.dispatchEvent(new Event('blur')));
  await page.waitForTimeout(250);
  await page.keyboard.up('Enter');
  expect(await page.evaluate(() => window.confirms)).toBe(0);
});

test('danger alias, dynamic variant and custom class keep native button styles', async ({ page }) => {
  await load(page, 'hold-to-confirm', { variant: 'destructive' });
  await expect(page.locator('[data-component]')).toHaveAttribute('data-variant', 'danger');
  await expect(page.getByRole('button')).toHaveClass(/btn-danger/);
  await page.evaluate(() => window.MauticUI.HoldToConfirmButton.get(document.querySelector('[data-component]')).update({ variant: 'neutral', className: 'custom' }));
  await expect(page.getByRole('button')).toHaveClass(/btn-tertiary/);
  await expect(page.getByRole('button')).not.toHaveClass(/btn-danger/);
  await expect(page.getByRole('button')).toHaveClass(/custom/);
  await page.evaluate(() => window.MauticUI.HoldToConfirmButton.get(document.querySelector('[data-component]')).update({ variant: 'danger' }));
  await expect(page.getByRole('button')).toHaveClass(/btn-danger/);
});

test('updated instructions and reset restore the accessible name', async ({ page }) => {
  await load(page, 'hold-to-confirm', { duration: 100, resetDelay: 0 });
  await page.evaluate(() => window.MauticUI.HoldToConfirmButton.get(document.querySelector('[data-component]')).update({ label: 'Remove contact', confirmedLabel: 'Confirmed' }));
  await press(page);
  await expect(page.getByRole('button')).toHaveAccessibleName('Confirmed');
  await page.mouse.up();
  await page.evaluate(() => window.MauticUI.HoldToConfirmButton.get(document.querySelector('[data-component]')).reset());
  await expect(page.getByRole('button')).toHaveAccessibleName('Remove contact. Press and hold for 0.1 seconds to confirm');
});

test('reduced motion freezes the beam and removes decorative transforms', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await load(page, 'hold-to-confirm', { variant: 'beam', duration: 200, resetDelay: 0 });
  await press(page);
  await page.waitForTimeout(50);
  await expect(page.getByRole('button')).toHaveCSS('transform', 'matrix(1, 0, 0, 1, 0, 0)');
  expect(await page.locator('[data-component]').evaluate((element) => getComputedStyle(element, '::after').animationName)).toBe('none');
  await page.mouse.up();
});

test('zero duration confirms once without leaving an active hold', async ({ page }) => {
  await load(page, 'hold-to-confirm', { duration: 0, resetDelay: 0 });
  await monitor(page);
  await press(page);
  await expect(page.locator('[data-component]')).toHaveAttribute('data-state', 'confirmed');
  await page.mouse.up();
  expect(await page.evaluate(() => window.confirms)).toBe(1);
});

test('success check stays centered under inherited Mautic button rules', async ({ page }) => {
  await load(page, 'hold-to-confirm', { duration: 0, resetDelay: 0 });
  await press(page);
  await expect(page.locator('[data-component]')).toHaveAttribute('data-state', 'confirmed');
  await page.mouse.up();
  await page.waitForTimeout(700);
  const offset = await page.locator('.hold-to-confirm__symbol').evaluate((element) => {
    const symbol = element.getBoundingClientRect();
    const check = element.querySelector('.hold-to-confirm__check svg').getBoundingClientRect();
    return [Math.abs(symbol.x + symbol.width / 2 - check.x - check.width / 2), Math.abs(symbol.y + symbol.height / 2 - check.y - check.height / 2)];
  });
  expect(offset[0]).toBeLessThan(1);
  expect(offset[1]).toBeLessThan(1);
});

test('production PHP minifier keeps confirmation and beam working', async ({ page }) => {
  await load(page, 'hold-to-confirm', { variant: 'beam', duration: 100, resetDelay: 0 }, '', true);
  await monitor(page);
  await press(page);
  await expect(page.locator('[data-component]')).toHaveAttribute('data-state', 'confirmed');
  await page.mouse.up();
  expect(await page.evaluate(() => window.confirms)).toBe(1);
  await expect(page.locator('[data-beam]')).toHaveCount(1);
});

test('values zero and false are preserved; custom labels are escaped' , () => {
  const html = render('hold-to-confirm', { duration: 0, resetDelay: 0, disabled: false, active: false, strength: 0, label: '<script>bad</script>' });
  expect(html).toContain('data-duration="0"');
  expect(html).toContain('data-reset-delay="0"');
  expect(html).toContain('data-strength="0"');
  expect(html).not.toContain(' disabled');
  expect(html).not.toContain('<script>');
});
