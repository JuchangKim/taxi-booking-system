<!-- admin.php -->
<?php
// Include database credentials
require_once("dbsettings.php");

// Connect to MySQL database
$conn = new mysqli($host, $user, $pswd, $dbnm);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

/**
 * Load and extract SQL queries from mysqlcommand.txt
 * Each block is prefixed with -- NAME
 */
function loadQueries($filename) {
    $queries = [];
    $sqlScript = file_get_contents($filename);

    // Extract queries individually using preg_match()
    // Create table query
    if (preg_match('/-- CREATE_TABLE\s*(CREATE TABLE .*?);/is', $sqlScript, $match)) {
        $queries['CREATE_TABLE'] = trim($match[1]) . ";";
    }
    // Get max ID query
    if (preg_match('/-- UPDATE_STATUS\s*(UPDATE .*?);/is', $sqlScript, $match)) {
        $queries['UPDATE_STATUS'] = trim($match[1]) . ";";
    }
    // Insert booking query
    if (preg_match('/-- SELECT_BY_REF\s*(SELECT .*?);/is', $sqlScript, $match)) {
        $queries['SELECT_BY_REF'] = trim($match[1]) . ";";
    }
    // Select all unassigned bookings within 2 hours query
    if (preg_match('/-- SELECT_WITHIN_2HRS\s*(SELECT .*?);/is', $sqlScript, $match)) {
        $queries['SELECT_WITHIN_2HRS'] = trim($match[1]) . ";";
    }

    return $queries;
}

// Load the named queries
$queries = loadQueries("mysqlcommand.txt");

// Ensure the bookings table exists (only runs once)
$conn->query($queries["CREATE_TABLE"]);

/**
 * Handle booking assignment via AJAX POST
 * If assign is posted, mark booking as 'assigned' in DB
 */
if (isset($_POST['assign'])) {
    $ref = $_POST['assign'];
    $stmt = $conn->prepare($queries["UPDATE_STATUS"]);
    $stmt->bind_param("s", $ref);
    $stmt->execute();
    echo "<p>Congratulations! Booking request $ref has been assigned!</p>";
} else {
    // Handle booking retrieval request (either by reference or all within 2 hours)
    $ref = $_POST['ref'] ?? null;

    if ($ref !== null && $ref !== "") {
        // Validate reference format
        if (!preg_match('/^BRN\d{5}$/', $ref)) {
            echo "<p style='color:red;'>Invalid booking reference format. Use format: BRN12345</p>";
            exit;
        }

        // Select a specific booking by reference number
        $stmt = $conn->prepare($queries["SELECT_BY_REF"]);
        $stmt->bind_param("s", $ref);
    } else {
        // Select all unassigned bookings within next 2 hours
        $stmt = $conn->prepare($queries["SELECT_WITHIN_2HRS"]);
    }

    // Execute query and display results in a table
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Render table header
        echo "<table><tr>
                <th>Booking Reference Number</th><th>Customer Name</th><th>Phone</th>
                <th>Pickup Suburb</th><th>Destination Suburb</th>
                <th>Pickup Date and Time</th><th>Status</th><th>Assign</th>
              </tr>";
        
        // Loop through results and render each row
        while ($row = $result->fetch_assoc()) {
            $datetime = date("d/m/Y H:i", strtotime($row['pickup_date'] . ' ' . $row['pickup_time']));
            
            // Button is disabled if booking already assigned
            $button = $row['status'] === 'assigned'
                ? '<button disabled style="background-color: lightgray;">Assign</button>'
                : "<button onclick=\"assign('{$row['ref']}', event)\">Assign</button>";
            
            echo "<tr>
                    <td>{$row['ref']}</td>
                    <td>{$row['cname']}</td>
                    <td>{$row['phone']}</td>
                    <td>{$row['sbname']}</td>
                    <td>{$row['dsbname']}</td>
                    <td>$datetime</td>
                    <td>{$row['status']}</td>
                    <td>$button</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No matching records found.</p>";
    }
}
?>
