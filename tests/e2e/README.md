# End-to-End (E2E) Test Setup

Last updated: 2026-09-04

This repository includes browser-level end-to-end scenario tests for the taxi booking system. The E2E suite checks the major user journeys for the booking flow, admin operations, CSV export, and chatbot behavior.

## Scope

The current E2E suite covers these user scenarios:

- E2E-001: Customer booking flow
- E2E-002: Admin assignment flow
- E2E-003: CSV export flow
- E2E-004: Chatbot + CSV refresh flow

## Prerequisites

Before running the E2E tests locally, ensure the following tools are installed:

- Node.js 20+
- npm
- PHP CLI (required because the Playwright config starts a local PHP web server)
- Chromium or Playwright-managed browser runtime

## Installation

From the project root, run:

```bash
npm install
npx playwright install --with-deps chromium
```

If PHP is not installed on your machine, install it first:

- Windows: install PHP from a PHP distribution such as XAMPP or the official PHP Windows build
- macOS: `brew install php`
- Ubuntu/Debian: `sudo apt install php-cli`

## How to Run the Tests

Run the E2E suite:

```bash
npx playwright test tests/e2e/taxi-booking.spec.js
```

Run in headed mode to watch the browser automation:

```bash
npx playwright test tests/e2e/taxi-booking.spec.js --headed
```

Open the Playwright UI:

```bash
npx playwright test --ui
```

Generate and view the HTML report:

```bash
npx playwright show-report
```

## Project Configuration

The test configuration is defined in:

- `playwright.config.js`

Important notes:

- The suite uses the local PHP server from the `php` folder.
- The default base URL is `http://127.0.0.1:8000`.
- The configuration is set up for CI execution as well as local runs.

## Test Result Summary

| Date | Status | Details |
| --- | --- | --- |
| 2026-09-04 | Ready for CI / local environment requires PHP | E2E test suite is created and configured. The Playwright scenario tests are implemented and ready to execute when PHP is available. |

## Current Execution Status

As of 2026-09-04:

- E2E scenarios are implemented in `tests/e2e/taxi-booking.spec.js`
- Jest/unit coverage is also in place
- CI is configured to run the E2E checks through GitHub Actions
- Local execution may fail in environments without PHP CLI installed, because the test server depends on it

## CI Execution

The repository CI workflow runs the E2E tests automatically on push and pull request, using PHP setup before test execution.

```bash
npm install
npx playwright install --with-deps chromium
npm run test:e2e
```

## Notes

These tests are scenario-based and simulate the main business user journeys rather than only checking DOM or function-level logic. This makes them suitable for validating critical flows such as:

- booking creation
- admin search and assignment
- CSV export used for reporting/history
- chatbot refresh and response generation

If a browser issue or environment issue occurs, confirm that PHP is installed and that all Node dependencies have been installed before re-running the suite.
