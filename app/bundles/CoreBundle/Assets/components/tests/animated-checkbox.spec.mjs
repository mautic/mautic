import { test, expect } from '@playwright/test';
import { load, render } from './render.mjs';

for (const [options, expected] of [[{}, 'false'], [{ defaultChecked: true }, 'true'], [{ defaultChecked: 'indeterminate' }, 'mixed'], [{ checked: false, defaultChecked: true }, 'false']]) {
  test(`initial state ${JSON.stringify(options)}`, async ({ page }) => {
    await load(page, 'animated-checkbox', { label: 'Accept', ...options });
    await expect(page.getByRole('checkbox', { name: 'Accept' })).toHaveAttribute('aria-checked', expected);
  });
}

test('pointer and Space toggle, Enter does not; form data and reset follow state', async ({ page }) => {
  const markup = render('animated-checkbox', { label: 'Accept', name: 'accepted', value: 'yes' });
  await load(page, 'animated-checkbox', { label: 'Other' }, `<form id="test-form">${markup}<button type="reset">Reset</button></form>`);
  const checkbox = page.getByRole('checkbox', { name: 'Accept' });
  await checkbox.click();
  await expect(checkbox).toHaveAttribute('aria-checked', 'true');
  expect(await page.evaluate(() => new FormData(document.querySelector('#test-form')).get('accepted'))).toBe('yes');
  await checkbox.press('Enter');
  await expect(checkbox).toHaveAttribute('aria-checked', 'true');
  await checkbox.press('Space');
  await expect(checkbox).toHaveAttribute('aria-checked', 'false');
  await checkbox.click();
  await page.getByText('Reset', { exact: true }).click();
  await expect(checkbox).toHaveAttribute('aria-checked', 'false');
});

test('controlled state emits next state without changing until updated', async ({ page }) => {
  await load(page, 'animated-checkbox', { label: 'Accept', checked: false });
  await page.evaluate(() => {
    window.changes = [];
    document.addEventListener('mautic:animated-checkbox:change', (event) => window.changes.push(event.detail.checked));
  });
  const checkbox = page.getByRole('checkbox');
  await checkbox.click();
  await expect(checkbox).toHaveAttribute('aria-checked', 'false');
  expect(await page.evaluate(() => window.changes)).toEqual([true]);
  await page.evaluate(() => window.MauticUI.AnimatedCheckbox.get(document.querySelector('[data-component]')).update({ checked: 'indeterminate' }));
  await expect(checkbox).toHaveAttribute('aria-checked', 'mixed');
  await checkbox.click();
  expect(await page.evaluate(() => window.changes)).toEqual([true, true]);
});

test('disabled prevents interaction and required validity follows native input', async ({ page }) => {
  await load(page, 'animated-checkbox', { label: 'Accept', disabled: true, required: true });
  await expect(page.getByRole('checkbox')).toBeDisabled();
  await page.evaluate(() => window.MauticUI.AnimatedCheckbox.get(document.querySelector('[data-component]')).update({ disabled: false }));
  expect(await page.locator('input').evaluate((input) => input.checkValidity())).toBe(false);
  await page.getByRole('checkbox').click();
  expect(await page.locator('input').evaluate((input) => input.checkValidity())).toBe(true);
});

for (const [size, width] of [['sm', 18], ['default', 20], ['lg', 24]]) {
  test(`size ${size} and accent style`, async ({ page }) => {
    await load(page, 'animated-checkbox', { label: 'Accept', size, variant: 'accent' });
    await expect(page.getByRole('checkbox')).toHaveCSS('width', `${width}px`);
    await expect(page.getByRole('checkbox')).toHaveCSS('border-top-width', '0px');
  });
}

test('AJAX insertion initializes once and removal disposes controller', async ({ page }) => {
  await load(page, 'animated-checkbox', { label: 'Accept' });
  const markup = render('animated-checkbox', { label: 'Added' });
  await page.evaluate((markup) => document.body.insertAdjacentHTML('beforeend', markup), markup);
  await page.getByRole('checkbox', { name: 'Added' }).click();
  await expect(page.getByRole('checkbox', { name: 'Added' })).toHaveAttribute('aria-checked', 'true');
  await page.evaluate(() => {
    window.removed = document.querySelector('[data-component]');
    window.removed.remove();
  });
  await expect.poll(() => page.evaluate(() => window.MauticUI.AnimatedCheckbox.get(window.removed) === undefined)).toBe(true);
});

test('translated options are escaped', () => {
  const html = render('animated-checkbox', { label: '<script>alert(1)</script>', value: '" onfocus="bad', disabled: false, required: false });
  expect(html).not.toContain('<script>');
  expect(html).not.toContain(' disabled');
  expect(html).not.toContain(' required');
});
