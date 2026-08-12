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
    // Default to 1 if the query is missing or returns NULL (empty table)
    $nextId = 1;
    if (!empty($queries["GET_MAX_ID"] ) && is_string($queries["GET_MAX_ID"])) {
        $result = $conn->query($queries["GET_MAX_ID"]);
        if ($result) {
            $row = $result->fetch_assoc();
            $maxId = (isset($row['max_id']) && $row['max_id'] !== null) ? intval($row['max_id']) : 0;
            $nextId = $maxId + 1;
        }
    }

    // Generate a unique booking reference in the format BRN00001
    // Cast to string before using str_pad to avoid deprecation warnings when passing non-strings
    $ref = "BRN" . str_pad((string)$nextId, 5, "0", STR_PAD_LEFT);

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
        // Log server-side, then show a generic error to the user
        error_log("booking.php: Parameter binding failed: " . $stmt->error);
        die("<p style='color:white;'>Parameter binding failed: " . $stmt->error . "</p>");
    }
    
    // Defensive: ensure created timestamp is set (bind_param binds by reference so this updates the bound value)
    if (empty($created)) {
        $created = date("Y-m-d H:i:s");
    }

    // Prepare array snapshot of bound variables for validation/logging (do not expose to users)
    $boundVars = [$ref, $cname, $phone, $unumber, $snumber, $stname, $sbname, $dsbname, $date, $time, $created, $status];

    // Detect any empty required values and log them for debugging
    $missing = [];
    foreach ($boundVars as $i => $v) {
        if ($v === null || $v === '') {
            $missing[] = $i; // record index of missing value
        }
    }
    if (!empty($missing)) {
        error_log("booking.php: warning - empty fields at indexes: " . implode(',', $missing) . " values: " . json_encode($boundVars));
    }

    // Execute the prepared statement with error handling and logging
    if (!$stmt->execute()) {
        error_log("booking.php: Failed to insert booking: " . $stmt->error . " boundValues: " . json_encode($boundVars));
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
