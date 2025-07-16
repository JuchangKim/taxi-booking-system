// booking.js

// Wait for the DOM to be fully loaded before running any script
document.addEventListener("DOMContentLoaded", () => {
    // Get references to the form and input fields
    const form = document.getElementById("bookingForm");
    const dateInput = document.getElementById("date");
    const timeInput = document.getElementById("time");

    // Set default values for date and time fields to current date/time
    const now = new Date();
    dateInput.value = now.toISOString().split("T")[0]; // Format: YYYY-MM-DD
    timeInput.value = now.toTimeString().slice(0, 5);  // Format: HH:MM (24-hour)

    // Handle form submission
    form.addEventListener("submit", async (e) => {
        e.preventDefault(); // Prevent default form submission behavior

        const formData = new FormData(form); // Gather form inputs
        const phone = formData.get("phone"); // Extract phone value

        // Validate phone number: must be all digits, 10–12 characters long
        if (!/^\d{10,12}$/.test(phone)) {
            alert("Phone number must be 10-12 digits.");
            return;
        }

        // Validate pickup date/time must not be earlier than current time
        const pickupDateTime = new Date(`${formData.get("date")}T${formData.get("time")}`);
        if (pickupDateTime < new Date()) {
            alert("Pickup date/time must not be in the past.");
            return;
        }

        // Send data to server using Fetch API (POST to booking.php)
        const response = await fetch("booking.php", {
            method: "POST",
            body: formData
        });

        // Get response text from the server (e.g., confirmation message)
        const result = await response.text();

        const reference = document.getElementById("reference");
        reference.innerHTML = result;

        // Remove old classes
        reference.classList.remove("success", "error");

        // Determine the message type
        if (result.trim()) {
            // If the message looks like an error
            if (result.toLowerCase().includes("connection failed") || result.toLowerCase().includes("error") || result.toLowerCase().includes("denied")) {
                reference.classList.add("error");
            } else {
                reference.classList.add("success");
            }
        } else {
            reference.style.display = "none";
        }
    });
});
