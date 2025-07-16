document.addEventListener("DOMContentLoaded", async () => {
    const container = document.getElementById("formContainer");
    const confrim = document.getElementById("confrim");

    const params = new URLSearchParams(window.location.search);
    const ref = params.get("ref");
    const cname = params.get("cname") || "";
    const phone = params.get("phone") || "";
    const unumber = params.get("unumber") || "";
    const snumber = params.get("snumber") || "";
    const stname = params.get("stname") || "";
    const sbname = params.get("sbname") || "";
    const dsbname = params.get("dsbname") || "";
    const pickup_date = params.get("pickup_date") || "";
    const pickup_time = params.get("pickup_time") || "";

    const form = document.createElement("form");
    form.innerHTML = `
        <input type="hidden" name="ref" value="${ref}">
        <label>Customer Name: <input type="text" name="cname" value="${cname}" required /></label><br>
        <label>Phone: <input type="text" name="phone" value="${phone}" required /></label><br>
        <label>Unit Number: <input type="text" name="unumber" value="${unumber}" /></label><br>
        <label>Street Number: <input type="text" name="snumber" value="${snumber}" required /></label><br>
        <label>Street Name: <input type="text" name="stname" value="${stname}" required /></label><br>
        <label>Pickup Suburb: <input type="text" name="sbname" value="${sbname}" required /></label><br>
        <label>Destination Suburb: <input type="text" name="dsbname" value="${dsbname}" required /></label><br>
        <label>Pickup Date: <input type="date" name="pickup_date" value="${pickup_date}" required /></label><br>
        <label>Pickup Time: <input type="time" name="pickup_time" value="${pickup_time}" required /></label><br>
        <button type="submit">Update Booking</button>
    `;

    function backLink() {
        const link = document.createElement("a");
        link.href = "admin.html";
        link.textContent = "Back to Admin Panel";
        link.style.display = "inline-block";
        link.style.marginTop = "20px";
        return link;
    }

    form.onsubmit = async (e) => {
        e.preventDefault();
        confrim.innerHTML = "";
        confrim.classList.remove("error", "success");

        const formData = new FormData(form);
        const phone = formData.get("phone");
        const date = formData.get("pickup_date");
        const time = formData.get("pickup_time");

        // Phone number validation
        if (!/^\d{10,12}$/.test(phone)) {
            confrim.innerHTML = "Phone number must be 10–12 digits.";
            confrim.classList.add("error");
            return;
        }

        // Pickup date/time validation
        const pickupDateTime = new Date(`${date}T${time}`);
        if (pickupDateTime < new Date()) {
            confrim.innerHTML = "Pickup time must not be in the past.";
            confrim.classList.add("error");
            return;
        }

        // Send update request
        formData.append("update", "1");
        const res = await fetch("admin.php", {
            method: "POST",
            body: formData,
        });

        const text = await res.text();
        confrim.innerHTML = text;
        confrim.classList.add(text.toLowerCase().includes("error") ? "error" : "success");

        if (!text.toLowerCase().includes("error")) {
            form.remove(); // hide form after success
            container.innerHTML = "Edit Booking is Successful <br>";
        }
        
        container.appendChild(backLink()); // Always add back link
    };

    container.appendChild(form);
});
