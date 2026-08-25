// admin.js

const currentDocument = () => globalThis.document || document;
const currentWindow = () => globalThis.window || window;
let adminInitializationRan = false;

function setConfirmMessage(message, isError = false) {
    const confirmBox = currentDocument().getElementById('confirm');
    if (!confirmBox) {
        return;
    }

    confirmBox.innerHTML = message;
    confirmBox.style.display = message ? 'block' : 'none';
    confirmBox.style.color = isError ? 'red' : 'green';
    confirmBox.style.backgroundColor = isError ? '#fbe9e7' : '#e0f7e9';
    confirmBox.style.borderColor = isError ? '#d32f2f' : 'transparent';
}

async function fetchBookings(ref = '', showAll = false) {
    const content = currentDocument().getElementById('content');
    if (!content) {
        return;
    }

    content.innerHTML = '<p>Loading bookings...</p>';

    try {
        const formData = new FormData();
        // If no reference is provided, request the full booking list from admin.php.
        // Otherwise, send the reference number to search for a specific booking.
        if (showAll || ref === '') {
            formData.append('all', '1');
        } else {
            formData.append('ref', ref);
        }

        const response = await fetch('admin.php', { method: 'POST', body: formData });
        const html = await response.text();

        if (!response.ok) {
            setConfirmMessage(html || 'Unable to load bookings.', true);
            content.innerHTML = '';
            return;
        }

        content.innerHTML = html;
        setConfirmMessage('', false);
    } catch (error) {
        setConfirmMessage('Unable to load bookings. Please try again.', true);
        content.innerHTML = '';
    }
}

currentWindow().assign = async (ref, event) => {
    const buttonEl = event.target;
    buttonEl.disabled = true;

    const row = buttonEl.closest('tr');
    const statusCell = row ? row.querySelector('.status-cell') : null;

    try {
        const formData = new FormData();
        formData.append('assign', ref);
        const response = await fetch('admin.php', { method: 'POST', body: formData });
        const text = await response.text();

        if (!response.ok) {
            setConfirmMessage(text || 'Failed to assign booking.', true);
            if (buttonEl) buttonEl.disabled = false;
            return;
        }

        if (statusCell) {
            statusCell.textContent = 'assigned';
        }
        setConfirmMessage(text, false);
    } catch (error) {
        setConfirmMessage('Failed to assign booking. Please try again.', true);
        if (buttonEl) buttonEl.disabled = false;
    }
};

currentWindow().deleteBooking = async (ref, event) => {
    if (!currentWindow().confirm(`Are you sure you want to delete booking ${ref}?`)) {
        return;
    }

    const row = event.target.closest('tr');

    try {
        const formData = new FormData();
        formData.append('delete', ref);
        const response = await fetch('admin.php', { method: 'POST', body: formData });
        const text = await response.text();

        if (!response.ok) {
            setConfirmMessage(text || 'Failed to delete booking.', true);
            return;
        }

        if (row) {
            row.remove();
        }
        setConfirmMessage(text, false);
    } catch (error) {
        setConfirmMessage('Failed to delete booking. Please try again.', true);
    }
};

currentDocument().addEventListener('DOMContentLoaded', () => {
    if (adminInitializationRan) {
        return;
    }
    adminInitializationRan = true;

    const button = currentDocument().getElementById('sbutton');
    const search = currentDocument().getElementById('bsearch');
    const allButton = currentDocument().getElementById('allbutton');

    if (!button || !search || !allButton) {
        return;
    }

    button.addEventListener('click', () => {
        const ref = search.value.trim();
        if (ref && !/^BRN\d{5}$/.test(ref)) {
            setConfirmMessage('Invalid reference. Format: BRN12345', true);
            currentDocument().getElementById('content').innerHTML = '';
            return;
        }

        setConfirmMessage('', false);
        fetchBookings(ref, false);
    });

    allButton.addEventListener('click', () => {
        search.value = '';
        setConfirmMessage('', false);
        fetchBookings('', true);
    });

    // Pressing Enter in the search box should act like clicking Search.
    // If the search box is empty, Enter loads all bookings.
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            button.click();
        }
    });

    fetchBookings('');
});

if (typeof globalThis !== 'undefined') {
    globalThis.setConfirmMessage = setConfirmMessage;
    globalThis.fetchBookings = fetchBookings;
}

