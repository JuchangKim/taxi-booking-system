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
