// admin.js

// Wait for the HTML DOM to be fully loaded
document.addEventListener("DOMContentLoaded", () => {

  // Get references to important DOM elements
  const button = document.getElementById("sbutton");       // Search button
  const search = document.getElementById("bsearch");       // Input field for booking reference
  const confirm = document.getElementById("confirm");      // Confirmation message box
  const content = document.getElementById("content");      // Table container for booking results

  // Handle click event on "Search" button
  button.addEventListener("click", () => {
      const ref = search.value.trim(); // Get and trim the search input

      // Validate booking reference format (e.g., BRN00001)
      if (ref && !/^BRN\d{5}$/.test(ref)) {
          confirm.innerHTML = "<span style='color:red;'>Invalid reference. Format: BRN12345</span>";
          confirm.style.display = "block";
          content.innerHTML = ""; // Clear previous results
          return;
      }

      // Clear confirmation box if valid input
      confirm.innerHTML = "";
      confirm.style.display = "none";

      // Fetch bookings from server
      fetchBookings(ref);
  });

  // Function to assign a booking (triggered when "Assign" button is clicked)
  window.assign = async (ref, event) => {
      const buttonEl = event.target;
      // prevent double clicks
      buttonEl.disabled = true; 

      // Update booking status visually in the table
      const row = buttonEl.closest("tr");
      const statusCell = row.querySelector("td:nth-child(7)"); // Status is in 7th cell
      statusCell.textContent = "assigned";

      // Prepare request to update status in database
      const formData = new FormData();
      formData.append("assign", ref);

      // Send update request to server
      const res = await fetch("admin.php", { method: "POST", body: formData });
      const text = await res.text();

      // Show confirmation message
      document.getElementById("confirm").innerHTML = text;
      confirm.style.display = text.trim() ? "block" : "none";
  };

    // Delete a booking
  window.deleteBooking = function deleteBooking(ref, event) {
    if (!window.confirm(`Are you sure you want to delete booking ${ref}?`)) return;

    const formData = new FormData();
    formData.append("delete", ref);

    fetch("admin.php", { method: "POST", body: formData })
        .then(r => r.text())
        .then(msg => {
            const row = event.target.closest("tr");
            if (row) row.remove(); // Remove row from table
            document.getElementById("confirm").innerHTML = msg;
            document.getElementById("confirm").style.display = "block";
        });
    }

    // Edit a booking
  window.editBooking = function editBooking(dataJson) {
        const data = JSON.parse(dataJson);

        const form = document.createElement("form");
        form.innerHTML = `
            <h3>Edit Booking: ${data.ref}</h3>
            <input type="hidden" name="ref" value="${data.ref}">
            Name: <input name="cname" value="${data.cname}"><br>
            Phone: <input name="phone" value="${data.phone}"><br>
            Unit No: <input name="unumber" value=""><br>
            Street No: <input name="snumber" value=""><br>
            Street Name: <input name="stname" value=""><br>
            Pickup Suburb: <input name="sbname" value="${data.sbname}"><br>
            Destination: <input name="dsbname" value="${data.dsbname}"><br>
            Date: <input type="date" name="pickup_date" value="${data.pickup_date}"><br>
            Time: <input type="time" name="pickup_time" value="${data.pickup_time}"><br>
            <button type="submit">Update Booking</button>
        `;

        form.onsubmit = (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            formData.append("update", "1");

            fetch("admin.php", { method: "POST", body: formData })
                .then(r => r.text())
                .then(msg => {
                    document.getElementById("confirm").innerHTML = msg;
                    fetchBookings(""); // refresh list
                });
        };

        document.getElementById("content").innerHTML = "";
        document.getElementById("content").appendChild(form);
    }

    // Function to fetch bookings from the server (based on booking reference or all unassigned within 2 hours)
    function fetchBookings(ref) {
        const formData = new FormData();
        formData.append("ref", ref);

        // Send search request to server
        fetch("admin.php", { method: "POST", body: formData })
            .then((r) => r.text())
            .then((html) => {
                // Render returned booking table
                content.innerHTML = html; 
            });
    }

    });
