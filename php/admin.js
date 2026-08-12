// admin.js

function setConfirmMessage(message, isError = false) {
    const confirmBox = document.getElementById('confirm');
    confirmBox.innerHTML = message;
    confirmBox.style.display = message ? 'block' : 'none';
    confirmBox.style.color = isError ? 'red' : 'green';
    confirmBox.style.backgroundColor = isError ? '#fbe9e7' : '#e0f7e9';
    confirmBox.style.borderColor = isError ? '#d32f2f' : 'transparent';
}

async function fetchBookings(ref = '', showAll = false) {
    const content = document.getElementById('content');
    content.innerHTML = '<p>Loading bookings...</p>';

    try {
        const formData = new FormData();
        // If showAll is true, request the full booking list from admin.php.
        // Otherwise, send the reference number to search for a specific booking.
        if (showAll) {
            formData.append('all', '1');
        } else if (ref !== '') {
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

window.assign = async (ref, event) => {
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

window.deleteBooking = async (ref, event) => {
    if (!window.confirm(`Are you sure you want to delete booking ${ref}?`)) {
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

document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('sbutton');
    const search = document.getElementById('bsearch');

    button.addEventListener('click', () => {
        const ref = search.value.trim();
        if (ref && !/^BRN\d{5}$/.test(ref)) {
            setConfirmMessage('Invalid reference. Format: BRN12345', true);
            document.getElementById('content').innerHTML = '';
            return;
        }

        setConfirmMessage('', false);
        const showAll = ref === '';
        fetchBookings(ref, showAll);
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
