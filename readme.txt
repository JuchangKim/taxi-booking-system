=========================================
Assignment 2: CabsOnline Booking System
Student Name: Juchang Kim
Student ID: 22180242
=========================================

FILES INCLUDED:
- booking.html         → Form for customers to book a taxi.
- booking.js           → JavaScript for validating booking inputs and sending via Fetch API.
- booking.php          → PHP script to insert validated booking into the MySQL database.
- admin.html           → Admin interface to search and assign bookings.
- admin.js             → JavaScript to handle search, assign button clicks, and DOM updates.
- admin.php            → PHP backend for processing admin requests (search + assign).
- style.css            → Unified CSS styling for both pages including responsive background image.
- mysqlcommand.txt     → SQL statements for table creation, data insert, and selection queries.
- dbsettings.php       → Contains database connection credentials.
- images/Background.jpg → Background image used for both booking and admin UI.

USAGE INSTRUCTIONS:

1. Launch `booking.html` in the browser.
2. Fill out the booking form. JavaScript (`booking.js`) ensures input validity.
3. On submit, booking data is sent via Fetch to `booking.php`, which inserts the record into MySQL.
4. Launch `admin.html` to manage bookings.
5. Admin can search by booking reference (e.g., BRN00001) or leave it empty to list all unassigned bookings within 2 hours.
6. Click “Assign” to mark a booking as assigned. The status is updated in both the UI and the database.
7. The confirmation message is shown below the search bar, and assignment buttons are disabled after use.

NOTES:
- The site includes a fixed background image loaded from `images/Background.jpg`.
- All communication between frontend and backend uses Fetch API.
- Assignment status is visually updated without reloading the entire table.