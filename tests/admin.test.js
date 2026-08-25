const fs = require('fs');
const vm = require('vm');
const { TextEncoder, TextDecoder } = require('util');

global.TextEncoder = TextEncoder;
global.TextDecoder = TextDecoder;

const { JSDOM } = require('jsdom');
const adminScript = fs.readFileSync(require.resolve('../php/admin.js'), 'utf8');

function loadAdminPage() {
  const dom = new JSDOM(`
    <div id="confirm"></div>
    <div id="content"></div>
    <input type="text" id="bsearch" value="" />
    <input type="button" id="sbutton" value="Search" />
    <input type="button" id="allbutton" value="Show all bookings" />
  `, { url: 'http://localhost/', runScripts: 'dangerously' });

  const context = dom.getInternalVMContext ? dom.getInternalVMContext() : dom.window;
  const page = dom.window;

  context.window = page;
  context.document = page.document;
  context.globalThis = page;
  context.FormData = page.FormData;
  page.fetch = jest.fn();
  context.fetch = page.fetch;
  page.confirm = jest.fn(() => true);
  context.confirm = page.confirm;

  vm.runInContext(adminScript, context);
  return dom;
}

/**
 * Admin feature tests for the taxi booking system.
 * These tests cover the search, assignment, deletion, and confirmation behaviours used by the admin interface.
 */
describe('Admin booking feature tests', () => {
  let dom;

  // This setup provides a fresh jsdom page for each test so the admin page and event listeners remain isolated.
  beforeEach(async () => {
    dom = loadAdminPage();
    await new Promise((resolve) => setTimeout(resolve, 0));
    dom.window.fetch.mockClear();
  });

  // Test case: the confirmation panel should render an error message and the red styling used by the admin screen.
  test('setConfirmMessage shows an error message with the expected admin error styles', () => {
    const confirmBox = dom.window.document.getElementById('confirm');

    dom.window.setConfirmMessage('Invalid booking reference.', true);

    expect(confirmBox.innerHTML).toBe('Invalid booking reference.');
    expect(confirmBox.style.display).toBe('block');
    expect(confirmBox.style.color).toBe('red');
    expect(confirmBox.style.backgroundColor).toBe('rgb(251, 233, 231)');
    expect(confirmBox.style.borderColor).toBe('#d32f2f');
  });

  // Test case: the admin list request should send a search-by-reference form payload when a booking ref is entered.
  test('fetchBookings sends a booking reference search request when a ref is provided', async () => {
    dom.window.fetch.mockResolvedValue({
      ok: true,
      text: async () => '<table><tr><td>BRN00001</td></tr></table>'
    });

    dom.window.document.dispatchEvent(new dom.window.Event('DOMContentLoaded'));
    dom.window.fetch.mockClear();

    await dom.window.fetchBookings('BRN00001', false);

    expect(dom.window.fetch).toHaveBeenCalledTimes(1);
    const fetchOptions = dom.window.fetch.mock.calls[0][1];
    const body = fetchOptions.body;
    expect(body.get('ref')).toBe('BRN00001');
    expect(body.has('all')).toBe(false);
  });

  // Test case: the admin list request should ask the backend for all bookings when the user wants the full table.
  test('fetchBookings requests the full booking table when the admin chooses Show all bookings', async () => {
    dom.window.fetch.mockResolvedValue({
      ok: true,
      text: async () => '<table><tr><td>BRN00001</td></tr></table>'
    });

    await dom.window.fetchBookings('', true);

    expect(dom.window.fetch).toHaveBeenCalledTimes(1);
    const fetchOptions = dom.window.fetch.mock.calls[0][1];
    const body = fetchOptions.body;
    expect(body.get('all')).toBe('1');
    expect(body.has('ref')).toBe(false);
  });

  // Test case: the admin assignment action should send the booking ref to the backend and update the row status to assigned.
  test('window.assign updates the booking status after a successful assignment request', async () => {
    const row = dom.window.document.createElement('tr');
    const statusCell = dom.window.document.createElement('td');
    statusCell.className = 'status-cell';
    row.appendChild(statusCell);

    const button = dom.window.document.createElement('button');
    button.textContent = 'Assign';
    button.closest = jest.fn(() => row);
    row.appendChild(button);

    dom.window.fetch.mockResolvedValue({
      ok: true,
      text: async () => '<p>Booking BRN00001 has been assigned.</p>'
    });

    await dom.window.assign('BRN00001', { target: button });

    expect(dom.window.fetch).toHaveBeenCalledTimes(1);
    const body = dom.window.fetch.mock.calls[0][1].body;
    expect(body.get('assign')).toBe('BRN00001');
    expect(statusCell.textContent).toBe('assigned');
    expect(dom.window.document.getElementById('confirm').innerHTML).toContain('Booking BRN00001 has been assigned.');
  });

  // Test case: deleting a booking should confirm the action, remove the row, and display a success message.
  test('window.deleteBooking removes the row after the user confirms deletion', async () => {
    const row = dom.window.document.createElement('tr');
    row.innerHTML = '<td>BRN00001</td>';
    dom.window.document.body.appendChild(row);

    dom.window.confirm = jest.fn(() => true);
    dom.window.fetch.mockResolvedValue({
      ok: true,
      text: async () => '<p>Booking BRN00001 has been deleted.</p>'
    });

    await dom.window.deleteBooking('BRN00001', { target: row });

    expect(dom.window.confirm).toHaveBeenCalledWith('Are you sure you want to delete booking BRN00001?');
    expect(dom.window.document.body.contains(row)).toBe(false);
    expect(dom.window.document.getElementById('confirm').innerHTML).toContain('Booking BRN00001 has been deleted.');
  });

  // Test case: the DOMContentLoaded listener should wire the Search and Show all button events to the booking fetch flow.
  test('DOMContentLoaded binds the search and all-bookings actions to the admin controls', async () => {
    dom.window.fetch.mockResolvedValue({
      ok: true,
      text: async () => '<table><tr><td>BRN00001</td></tr></table>'
    });

    dom.window.document.dispatchEvent(new dom.window.Event('DOMContentLoaded'));
    await new Promise((resolve) => setTimeout(resolve, 0));
    const searchInput = dom.window.document.getElementById('bsearch');
    const searchButton = dom.window.document.getElementById('sbutton');
    const allButton = dom.window.document.getElementById('allbutton');

    dom.window.fetch.mockClear();
    searchInput.value = 'BRN00001';
    searchButton.click();

    expect(dom.window.fetch).toHaveBeenCalledTimes(1);
    const firstBody = dom.window.fetch.mock.calls[0][1].body;
    expect(firstBody.get('ref')).toBe('BRN00001');

    dom.window.fetch.mockClear();
    allButton.click();

    expect(dom.window.fetch).toHaveBeenCalledTimes(1);
    const allBody = dom.window.fetch.mock.calls[0][1].body;
    expect(allBody.get('all')).toBe('1');
  });
});
