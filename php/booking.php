<!-- booking.php -->
<?php
    // Load database connection settings
    require_once("dbsettings.php");

    // Create a new MySQLi connection
    $conn = new mysqli($host, $user, $pswd, $dbnm);

    // Check for connection error
    if ($conn->connect_error) die("<p style='color:white;'>Connection failed: " . $conn->connect_error . "</p>");

    /*
    * Function to load SQL queries from the mysqlcommand.txt file
    * It extracts specific SQL statements by matching comment markers
    */
    function loadQueries($file) {
        $q = file_get_contents($file);  // Read the entire SQL file
        $queries = [];

        // Match each query using regular expressions
        preg_match('/-- CREATE_TABLE\s*(CREATE TABLE .*?);/is', $q, $m1);
        preg_match('/-- GET_MAX_ID\s*(SELECT .*?);/is', $q, $m2);
        preg_match('/-- INSERT_BOOKING\s*(INSERT INTO .*?);/is', $q, $m3);

        // Store matched queries into array
        $queries["CREATE_BOOKING_TABLE"] = $m1[1] ?? null;
        $queries["GET_MAX_ID"] = $m2[1] ?? 0; // Default to 0 if not found
        $queries["INSERT_BOOKING"] = $m3[1] ?? null;

        return $queries;
    }

    // Load queries from external file
    $queries = loadQueries("mysqlcommand.txt");

    // Run table creation SQL if needed
    if ($queries["CREATE_BOOKING_TABLE"]) {
        $conn->query($queries["CREATE_BOOKING_TABLE"]);
    }

    // Collect form data sent via POST request
    $cname = $_POST['cname'];
    $phone = $_POST['phone'];
    $unumber = $_POST['unumber'];
    $snumber = $_POST['snumber'];
    $stname = $_POST['stname'];
    $sbname = $_POST['sbname'];
    $dsbname = $_POST['dsbname'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    // Get the highest ID value to calculate the next booking reference number
    $result = $conn->query($queries["GET_MAX_ID"]);
    $row = $result->fetch_assoc();
    $nextId = $row['max_id'] + 1;

    // Generate a unique booking reference in the format BRN00001
    $ref = "BRN" . str_pad($nextId, 5, "0", STR_PAD_LEFT);

    // Store the current timestamp for the booking record
    $created = date("Y-m-d H:i:s");

    // Set initial booking status
    $status = "unassigned";

    // Prepare the insert query with error handling
    if (!$stmt = $conn->prepare($queries["INSERT_BOOKING"])) {
        die("<p style='color:white;'>Error preparing SQL: " . $conn->error . "</p>");
    }
    
    // Bind parameters to the prepared statement with error handling
    if (!$stmt->bind_param("ssssssssssss", $ref, $cname, $phone, $unumber, $snumber, $stname, $sbname, $dsbname, $date, $time, $created, $status)) {
        die("<p style='color:white;'>Parameter binding failed: " . $stmt->error . "</p>");
    }

    // Execute the prepared statement with error handling
    if (!$stmt->execute()) {
        die("<p style='color:white;'>Failed to insert booking: " . $stmt->error . "</p>");
    }

    // Format date for confirmation display
    $formattedDate = date("d/m/Y", strtotime($date));

    // Output booking confirmation message to user
    echo "<p>Thank you for your booking!<br>
        <br>
        Booking reference number: $ref<br>
        Pickup time: $time<br>
        Pickup date: $formattedDate</p>";
?>
