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
        // Update booking details
        if (preg_match('/-- UPDATE_BOOKING\s*(UPDATE .*?);/is', $sqlScript, $match)) {
            $queries['UPDATE_BOOKING'] = trim($match[1]) . ";";
        }

        // Delete booking
        if (preg_match('/-- DELETE_BOOKING\s*(DELETE .*?);/is', $sqlScript, $match)) {
            $queries['DELETE_BOOKING'] = trim($match[1]) . ";";
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
    } 
    // Handle delete
    else if (isset($_POST['delete'])) {
        $ref = $_POST['delete'];
        $stmt = $conn->prepare($queries["DELETE_BOOKING"]);
        $stmt->bind_param("s", $ref);
        $stmt->execute();
        echo "<p>Booking $ref has been deleted.</p>";
        exit;
    }

    // Handle update
    else if (isset($_POST['update'])) {
        $stmt = $conn->prepare($queries["UPDATE_BOOKING"]);
        $stmt->bind_param("ssssssssss",
            $_POST['cname'], $_POST['phone'], $_POST['unumber'], $_POST['snumber'],
            $_POST['stname'], $_POST['sbname'], $_POST['dsbname'],
            $_POST['pickup_date'], $_POST['pickup_time'], $_POST['ref']
        );
        $stmt->execute();
        echo "<p>Booking {$_POST['ref']} has been updated.</p>";
        exit;
    } else if (isset($_POST['all'])) {
        $result = $conn->query("SELECT * FROM bookings ORDER BY pickup_date, pickup_time");
        if ($result->num_rows > 0) {
            echo "<table><tr>
                    <th>Booking Reference</th><th>Name</th><th>Phone</th>
                    <th>Pickup Suburb</th><th>Destination</th>
                    <th>Date</th><th>Time</th><th>Status</th>
                </tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['ref']}</td><td>{$row['cname']}</td><td>{$row['phone']}</td>
                        <td>{$row['sbname']}</td><td>{$row['dsbname']}</td>
                        <td>{$row['pickup_date']}</td><td>{$row['pickup_time']}</td><td>{$row['status']}</td>
                    </tr>";
            }
            echo "</table>";
    } else {
        echo "<p>No booking history found.</p>";
    }
    exit;
} 
    
    else {
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
            // Show the full booking list on first load and whenever the search box is empty.
            $result = $conn->query("SELECT * FROM bookings ORDER BY pickup_date, pickup_time");

            if ($result->num_rows > 0) {
                echo "<table><tr>
                        <th>Booking Reference Number</th><th>Customer Name</th><th>Phone</th>
                        <th>Pickup Suburb</th><th>Destination Suburb</th>
                        <th>Pickup Date and Time</th><th>Status</th><th>Assign</th>
                    </tr>";

                while ($row = $result->fetch_assoc()) {
                    $datetime = date("d/m/Y H:i", strtotime($row['pickup_date'] . ' ' . $row['pickup_time']));
                    $button = $row['status'] === 'assigned'
                        ? '<button disabled style="background-color: lightgray;">Assign</button>'
                        : "<button onclick=\"assign('{$row['ref']}', event)\">Assign</button>";
                    $editLink = "edit.html?" . http_build_query([
                        "ref" => $row['ref'],
                        "cname" => $row['cname'],
                        "phone" => $row['phone'],
                        "sbname" => $row['sbname'],
                        "dsbname" => $row['dsbname'],
                        "pickup_date" => $row['pickup_date'],
                        "pickup_time" => $row['pickup_time']
                    ]);
                    $editBtn = "<a href=\"$editLink\"><button>Edit</button></a>";
                    $deleteBtn = "<button onclick=\"deleteBooking('{$row['ref']}', event)\">Delete</button>";

                    echo "<tr>
                            <td>{$row['ref']}</td>
                            <td>{$row['cname']}</td>
                            <td>{$row['phone']}</td>
                            <td>{$row['sbname']}</td>
                            <td>{$row['dsbname']}</td>
                            <td>$datetime</td>
                            <td>{$row['status']}</td>
                            <td>$button $editBtn $deleteBtn</td>
                        </tr>";
                }

                echo "</table>";
            } else {
                echo "<p>No booking history found.</p>";
            }
            exit;
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
                // Edit and delete buttons
                $editLink = "edit.html?" . http_build_query([
                    "ref" => $row['ref'],
                    "cname" => $row['cname'],
                    "phone" => $row['phone'],
                    "sbname" => $row['sbname'],
                    "dsbname" => $row['dsbname'],
                    "pickup_date" => $row['pickup_date'],
                    "pickup_time" => $row['pickup_time']
                ]);
                $editBtn = "<a href=\"$editLink\"><button>Edit</button></a>";
                $deleteBtn = "<button onclick=\"deleteBooking('{$row['ref']}', event)\">Delete</button>";
                echo "<tr>
                        <td>{$row['ref']}</td>
                        <td>{$row['cname']}</td>
                        <td>{$row['phone']}</td>
                        <td>{$row['sbname']}</td>
                        <td>{$row['dsbname']}</td>
                        <td>$datetime</td>
                        <td>{$row['status']}</td>
                        <td>$button $editBtn $deleteBtn</td>
                    </tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No matching records found.</p>";
        }
    }
?>
