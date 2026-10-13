import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  use: { browserName: 'chromium', headless: true },
  workers: 2,
  reporter: 'list'
});
