const { execSync } = require('child_process');
const { defineConfig } = require('@playwright/test');

const hasPhpCli = (() => {
  try {
    execSync('php -v', { stdio: 'ignore' });
    return true;
  } catch (error) {
    return false;
  }
})();

module.exports = defineConfig({
  testDir: './tests/e2e',
  testIgnore: hasPhpCli ? [] : ['**/*.spec.js'],
  timeout: 30000,
  expect: {
    timeout: 10000,
  },
  fullyParallel: false,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    headless: true,
  },
  ...(hasPhpCli
    ? {
        webServer: {
          command: 'php -S 127.0.0.1:8000 -t php',
          url: 'http://127.0.0.1:8000/',
          reuseExistingServer: !process.env.CI,
          timeout: 120000,
        },
      }
    : {}),
});
