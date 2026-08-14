// @ts-check
import { test, expect } from '@playwright/test';
import { getHitsAfter, getMaxHitId, waitForDateLeft } from '../helpers/db.js';

const landingAlias = process.env.MAUTIC_LANDING_ALIAS ?? 'e2e-landing-ai-test';
const dwellWaitMs = Number.parseInt(process.env.DWELL_WAIT_MS ?? '5000', 10);
const minDwellSeconds = Math.max(1, Math.floor(dwellWaitMs / 1000) - 2);
const adminUser = process.env.MAUTIC_ADMIN_USER ?? 'admin';
const adminPass = process.env.MAUTIC_ADMIN_PASS ?? 'Maut1cR0cks!';

test.describe('Page dwell time (date_left)', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('sets date_left on first hit after second landing page visit', async ({ page }) => {
    const baselineId = getMaxHitId();

    await page.goto(`/${landingAlias}`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(dwellWaitMs);
    await page.goto(`/${landingAlias}`, { waitUntil: 'networkidle' });

    const hits = getHitsAfter(baselineId);
    expect(hits.length, 'expected two new page hits').toBeGreaterThanOrEqual(2);

    const [firstHit, secondHit] = hits;
    const resolved = await waitForDateLeft(firstHit.id, minDwellSeconds);

    expect(resolved.dwellSeconds, 'first hit dwell time in seconds').toBeGreaterThanOrEqual(minDwellSeconds);
    expect(secondHit.dateLeft, 'current/last hit should still have NULL date_left').toBeNull();
  });

  test('sets date_left when leaving tracked URL via mtracking.gif (cross-page scenario)', async ({ page }) => {
    const baselineId = getMaxHitId();
    const trackedUrl = `https://external-test.example/dwell-page-${Date.now()}`;
    const otherUrl = `https://external-test.example/other-page-${Date.now()}`;

    await page.goto(
      `/mtracking.gif?page_url=${encodeURIComponent(trackedUrl)}&page_title=DwellTest`,
      { waitUntil: 'networkidle' }
    );
    await page.waitForTimeout(dwellWaitMs);
    await page.goto(
      `/mtracking.gif?page_url=${encodeURIComponent(otherUrl)}&page_title=OtherPage`,
      { waitUntil: 'networkidle' }
    );

    const hits = getHitsAfter(baselineId);
    expect(hits.length, 'expected two tracking pixel hits').toBeGreaterThanOrEqual(2);

    const trackedHit = hits.find((hit) => hit.url.includes('dwell-page-'));
    expect(trackedHit, 'first tracked URL hit should exist').toBeTruthy();

    const resolved = await waitForDateLeft(trackedHit.id, minDwellSeconds);
    expect(resolved.dwellSeconds).toBeGreaterThanOrEqual(minDwellSeconds);
  });
});

test.describe('Contact timeline (admin UI check)', () => {
  test('timeline shows time on page after two landing page visits', async ({ browser }) => {
    const anonymous = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const page = await anonymous.newPage();
    const baselineId = getMaxHitId();

    await page.goto(`/${landingAlias}`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(dwellWaitMs);
    await page.goto(`/${landingAlias}`, { waitUntil: 'networkidle' });
    await anonymous.close();

    const hits = getHitsAfter(baselineId);
    expect(hits.length).toBeGreaterThanOrEqual(2);
    await waitForDateLeft(hits[0].id, minDwellSeconds);

    const leadId = hits[0].leadId;
    expect(leadId, 'tracked contact id').toBeTruthy();

    const admin = await browser.newContext();
    const adminPage = await admin.newPage();
    await adminPage.goto('/s/login');
    await adminPage.fill('#username', adminUser);
    await adminPage.fill('#password', adminPass);
    await adminPage.click('button[type="submit"]');
    await adminPage.waitForURL(/\/(s\/dashboard|s\/contacts)/);

    await adminPage.goto(`/s/contacts/view/${leadId}`);
    await adminPage.waitForLoadState('networkidle');

    // Expand the older page hit (second "Page hit" row — timeline is newest-first).
    const olderHitExpand = adminPage
      .locator('tr.timeline-row')
      .filter({ hasText: 'Page hit' })
      .nth(1)
      .locator('[data-activate-details]');
    const detailsIndex = await olderHitExpand.getAttribute('data-activate-details');
    await olderHitExpand.click();

    const detailsRow = adminPage.locator(`#timeline-details-${detailsIndex}`);
    await expect(detailsRow).not.toHaveClass(/hide/);
    await expect(detailsRow.locator('dt').filter({ hasText: /^Time on page:|^Čas na stránce:/i })).toBeVisible();
    await expect(detailsRow.locator('dd').first()).toHaveText(/\d+s|\d+m \d+s/);
    await expect(detailsRow.locator('dd').first()).not.toHaveText(/unknown|neznám/i);

    await admin.close();
  });
});
