document.addEventListener("DOMContentLoaded", () => {
  fetch("admin.php", {
    method: "POST",
    body: new URLSearchParams({ all: "1" }) // send flag to get all bookings
  })
    .then(r => r.text())
    .then(html => {
      document.getElementById("historyContent").innerHTML = html;
    });
});
