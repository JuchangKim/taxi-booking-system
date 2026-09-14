const { test, expect } = require('@playwright/test');

// Helper to mock the booking backend so the browser test exercises the real user flow without requiring MySQL to be running.
async function mockBookingSubmission(page) {
  await page.route('**/booking.php', async (route) => {
    const formData = route.request().postData();

    // The booking page sends a regular form POST with customer and pickup details. We validate the form by checking the request payload.
    if (!formData || !formData.includes('cname=Alice+Jones')) {
      await route.fulfill({
        status: 400,
        contentType: 'text/plain',
        body: 'Booking failed. Please complete all required fields.',
      });
      return;
    }

    await route.fulfill({
      status: 200,
      contentType: 'text/html',
      body: '<div class="success">Booking confirmed. Reference: BRN12345</div>',
    });
  });
}

// Helper to mock the admin actions so the search, assign, and delete flows can be validated in a browser context.
async function mockAdminOperations(page) {
  await page.route('**/admin.php', async (route) => {
    const formData = route.request().postData() || '';

    if (formData.includes('all=1')) {
      await route.fulfill({
        status: 200,
        contentType: 'text/html',
        body: `
          <table>
            <tbody>
              <tr>
                <td>BRN12345</td>
                <td>pending</td>
                <td><button type="button" data-ref="BRN12345" onclick="window.assign('BRN12345', event)">Assign</button></td>
                <td><button type="button" data-ref="BRN12345" onclick="window.deleteBooking('BRN12345', event)">Delete</button></td>
              </tr>
            </tbody>
          </table>
        `,
      });
      return;
    }

    if (formData.includes('ref=BRN12345')) {
      await route.fulfill({
        status: 200,
        contentType: 'text/html',
        body: `
          <table>
            <tbody>
              <tr>
                <td>BRN12345</td>
                <td class="status-cell">pending</td>
                <td><button type="button" data-ref="BRN12345" onclick="window.assign('BRN12345', event)">Assign</button></td>
              </tr>
            </tbody>
          </table>
        `,
      });
      return;
    }

    if (formData.includes('assign=BRN12345')) {
      await route.fulfill({
        status: 200,
        contentType: 'text/plain',
        body: 'Booking BRN12345 assigned successfully.',
      });
      return;
    }

    if (formData.includes('delete=BRN12345')) {
      await route.fulfill({
        status: 200,
        contentType: 'text/plain',
        body: 'Booking BRN12345 deleted successfully.',
      });
      return;
    }

    await route.fulfill({
      status: 200,
      contentType: 'text/plain',
      body: 'No booking found.',
    });
  });
}

// Helper to mock the history page refresh and chatbot API so the dashboard flow can be validated without a running Docker stack.
async function mockHistoryAndChatbot(page) {
  await page.route('**/export.php?mode=update', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'text/plain',
      body: 'CSV refreshed successfully.',
    });
  });

  await page.route('**/export.php?mode=html', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'text/html',
      body: `
        <table>
          <thead><tr><th>Reference</th><th>Status</th></tr></thead>
          <tbody><tr><td>BRN12345</td><td>assigned</td></tr></tbody>
        </table>
      `,
    });
  });

  await page.route('http://54.79.89.195:8000/ask', async (route) => {
    const body = route.request().postData() || '';

    // The chatbot receives a question from the UI and responds with a text answer based on the refreshed booking history.
    expect(body).toContain('assigned');

    await route.fulfill({
      status: 200,
      contentType: 'text/plain',
      body: 'There are 1 assigned bookings in the current history.',
    });
  });

  await page.route('**/export.php', async (route) => {
    const url = route.request().url();
    const isCsvDownload = !url.includes('mode=');

    if (isCsvDownload) {
      await route.fulfill({
        status: 200,
        headers: {
          'Content-Type': 'text/csv',
          'Content-Disposition': 'attachment; filename="booking_history.csv"',
        },
        body: 'reference,status\nBRN12345,assigned\n',
      });
      return;
    }

    await route.continue();
  });
}

test.describe('Taxi booking system end-to-end user journeys', () => {
  test('E2E-001: customer can complete a taxi booking flow', async ({ page }) => {
    // This scenario follows the real customer journey: the user fills in all required fields, submits the form, and sees a booking reference.
    await mockBookingSubmission(page);

    await page.goto('/booking.html');

    await page.locator('input[name="cname"]').fill('Alice Jones');
    await page.locator('input[name="phone"]').fill('0401234567');
    await page.locator('input[name="unumber"]').fill('12');
    await page.locator('input[name="snumber"]').fill('24');
    await page.locator('input[name="stname"]').fill('Main Street');
    await page.locator('input[name="sbname"]').fill('Parramatta');
    await page.locator('input[name="dsbname"]').fill('Sydney CBD');
    await page.locator('input[name="date"]').fill('2030-12-31');
    await page.locator('input[name="time"]').fill('15:30');

    await page.locator('button[type="submit"]').click();

    await expect(page.locator('#reference')).toContainText('BRN12345');
  });

  test('E2E-002: admin can search and assign a booking', async ({ page }) => {
    // The admin journey starts with a reference lookup, then assigns the booking and confirms the status change in the UI.
    await mockAdminOperations(page);
    await page.goto('/admin.html');

    await page.locator('#bsearch').fill('BRN12345');
    await page.locator('#sbutton').click();

    await expect(page.locator('#content')).toContainText('BRN12345');

    await page.getByRole('button', { name: 'Assign' }).click();

    await expect(page.locator('#confirm')).toContainText('assigned successfully');
    await expect(page.locator('.status-cell')).toHaveText('assigned');
  });

  test('E2E-003: customer can export booking history as CSV', async ({ page }) => {
    // This flow validates the Export CSV action and ensures the browser receives a downloadable CSV file from the application.
    await mockHistoryAndChatbot(page);

    await page.goto('/history.html');
    await page.waitForLoadState('networkidle');

    const [download] = await Promise.all([
      page.waitForEvent('download'),
      page.locator('.btn-export').click(),
    ]);

    await expect(download.suggestedFilename()).toContain('booking_history.csv');
  });

  test('E2E-004: chatbot refreshes data from CSV and answers booking questions', async ({ page }) => {
    // The final scenario validates the automatic CSV refresh and the chatbot question flow that relies on the refreshed booking history.
    await mockHistoryAndChatbot(page);

    await page.goto('/history.html');

    await page.waitForRequest((request) => request.url().includes('export.php?mode=update'));
    await page.locator('#question').fill('How many bookings are assigned?');
    await page.locator('button:has-text("Ask")').click();

    await expect(page.locator('#response')).toContainText('1 assigned bookings');
  });
});
