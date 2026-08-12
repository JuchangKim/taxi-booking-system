document.addEventListener('DOMContentLoaded', async () => {
    const container = document.getElementById('formContainer');
    const confirmEl = document.getElementById('confirm');

    const params = new URLSearchParams(window.location.search);
    const data = {
        ref: params.get('ref') || '',
        cname: params.get('cname') || '',
        phone: params.get('phone') || '',
        unumber: params.get('unumber') || '',
        snumber: params.get('snumber') || '',
        stname: params.get('stname') || '',
        sbname: params.get('sbname') || '',
        dsbname: params.get('dsbname') || '',
        pickup_date: params.get('pickup_date') || '',
        pickup_time: params.get('pickup_time') || '',
    };

    const form = document.createElement('form');

    function addField(labelText, name, value, type = 'text', required = false) {
        const wrapper = document.createElement('div');
        wrapper.style.marginBottom = '10px';

        const label = document.createElement('label');
        label.textContent = `${labelText}: `;

        const input = document.createElement('input');
        input.type = type;
        input.name = name;
        input.value = value;
        if (required) input.required = true;
        input.style.width = '100%';
        input.style.padding = '8px';
        input.style.marginTop = '4px';
        input.style.boxSizing = 'border-box';

        label.appendChild(input);
        wrapper.appendChild(label);
        return wrapper;
    }

    form.appendChild(addField('Customer Name', 'cname', data.cname, 'text', true));
    form.appendChild(addField('Phone', 'phone', data.phone, 'text', true));
    form.appendChild(addField('Unit Number', 'unumber', data.unumber, 'text', false));
    form.appendChild(addField('Street Number', 'snumber', data.snumber, 'text', true));
    form.appendChild(addField('Street Name', 'stname', data.stname, 'text', true));
    form.appendChild(addField('Pickup Suburb', 'sbname', data.sbname, 'text', false));
    form.appendChild(addField('Destination Suburb', 'dsbname', data.dsbname, 'text', false));
    form.appendChild(addField('Pickup Date', 'pickup_date', data.pickup_date, 'date', true));
    form.appendChild(addField('Pickup Time', 'pickup_time', data.pickup_time, 'time', true));

    const refInput = document.createElement('input');
    refInput.type = 'hidden';
    refInput.name = 'ref';
    refInput.value = data.ref;
    form.appendChild(refInput);

    const submit = document.createElement('button');
    submit.type = 'submit';
    submit.textContent = 'Update Booking';
    submit.style.padding = '10px 20px';
    submit.style.fontSize = '16px';
    form.appendChild(submit);

    function setConfirmMessage(message, isError = false) {
        confirmEl.textContent = message;
        confirmEl.style.display = message ? 'block' : 'none';
        confirmEl.style.color = isError ? '#b71c1c' : '#1b5e20';
        confirmEl.style.backgroundColor = isError ? '#ffebee' : '#e8f5e9';
        confirmEl.style.border = isError ? '1px solid #f44336' : '1px solid transparent';
    }

    function createBackLink() {
        const link = document.createElement('a');
        link.href = 'admin.html';
        link.textContent = 'Back to Admin Panel';
        link.style.display = 'inline-block';
        link.style.marginTop = '20px';
        return link;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setConfirmMessage('', false);

        const formData = new FormData(form);
        const phone = formData.get('phone');
        const date = formData.get('pickup_date');
        const time = formData.get('pickup_time');

        if (!/^\d{10,12}$/.test(phone)) {
            setConfirmMessage('Phone number must be 10–12 digits.', true);
            return;
        }

        const pickupDateTime = new Date(`${date}T${time}`);
        if (isNaN(pickupDateTime.getTime()) || pickupDateTime < new Date()) {
            setConfirmMessage('Pickup time must not be in the past.', true);
            return;
        }

        formData.append('update', '1');

        try {
            const res = await fetch('admin.php', {
                method: 'POST',
                body: formData,
            });

            const text = await res.text();
            const isError = !res.ok || text.toLowerCase().includes('invalid') || text.toLowerCase().includes('failed');
            setConfirmMessage(text, isError);

            if (!isError) {
                container.innerHTML = '<p>Edit Booking is successful.</p>';
                container.appendChild(createBackLink());
                return;
            }
        } catch (error) {
            setConfirmMessage('Failed to update booking. Please try again later.', true);
        }
    });

    container.appendChild(form);
});
