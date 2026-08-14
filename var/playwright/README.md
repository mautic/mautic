# Dwell time E2E (Playwright)

Verifies `date_left` / „Time on page“ after the PageModel fix on branch
`fix-visits-url-point-action-based-on-total-time-spent-is-not-triggered-properly-m5`.

## Prerequisites

- DDEV running (`ddev start`)
- Published landing page (default alias: `e2e-landing-ai-test`)
- Node.js 18+

## Run automated tests

```bash
cd var/playwright
npm install
npm run install:browsers
npm test
```

Optional env vars:

| Variable | Default | Description |
|----------|---------|-------------|
| `MAUTIC_BASE_URL` | `https://leuchtfeuer-mautic.ddev.site` | Mautic base URL |
| `MAUTIC_LANDING_ALIAS` | `e2e-landing-ai-test` | Landing page alias |
| `DWELL_WAIT_MS` | `5000` | Wait between visits (ms) |
| `MAUTIC_ADMIN_USER` | `admin` | Admin username for timeline UI test |
| `MAUTIC_ADMIN_PASS` | `Maut1cR0cks!` (DDEV default) | Admin password for timeline UI test |

Headed mode (see the browser):

```bash
npm run test:headed
```
