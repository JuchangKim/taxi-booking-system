<?php
// export.php — safe CSV export with proper header handling

require_once(__DIR__ . '/dbsettings.php');

// Disable noisy MySQL warnings
mysqli_report(MYSQLI_REPORT_OFF);

/**
 * Escape HTML safely
 */
function escapeHtml(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Unified error handler
 */
function respondWithError(string $mode, string $message): void {
    if (!headers_sent()) {
        http_response_code(503);

        if ($mode === 'download') {
            header('Content-Type: text/plain; charset=utf-8');
        }
    }

    if ($mode === 'html' || $mode === 'update') {
        echo "<p style='color:red; font-weight:bold;'>$message</p>";
    } else {
        echo $message;
    }

    exit;
}

$mode = $_GET['mode'] ?? 'download';
$filePath = __DIR__ . "/booking_history.csv";   // absolute path

// Connect to DB
$conn = new mysqli($host, $user, $pswd, $dbnm);
if ($conn->connect_error) {
    respondWithError($mode, "Booking history is unavailable right now because the database connection failed.");
}

// Query bookings
$sql = "SELECT ref, cname, phone, sbname, dsbname, pickup_date, pickup_time, status 
        FROM bookings ORDER BY id ASC";

$result = $conn->query($sql);
if (!$result) {
    respondWithError($mode, "Booking history could not be loaded from the database.");
}

// Write CSV file
$csvFile = fopen($filePath, 'w');
if (!$csvFile) {
    respondWithError($mode, "Booking history file could not be written. Check file permissions.");
}

fputcsv($csvFile, [
    'Booking Reference', 'Name', 'Phone', 'Pickup Suburb',
    'Destination', 'Date', 'Time', 'Status'
]);

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
    fputcsv($csvFile, [
        $row['ref'], $row['cname'], $row['phone'], $row['sbname'],
        $row['dsbname'], $row['pickup_date'], $row['pickup_time'], $row['status']
    ]);
}

fclose($csvFile);

// HTML mode: show table
if ($mode === 'html') {
    if (count($rows) === 0) {
        echo "<p>No booking history found.</p>";
        exit;
    }

    echo "<table border='1' cellpadding='5'>";
    echo "<tr>
        <th>Booking Reference</th><th>Name</th><th>Phone</th>
        <th>Pickup Suburb</th><th>Destination</th>
        <th>Date</th><th>Time</th><th>Status</th>
    </tr>";

    foreach ($rows as $row) {
        echo "<tr>
            <td>" . escapeHtml($row['ref']) . "</td>
            <td>" . escapeHtml($row['cname']) . "</td>
            <td>" . escapeHtml($row['phone']) . "</td>
            <td>" . escapeHtml($row['sbname']) . "</td>
            <td>" . escapeHtml($row['dsbname']) . "</td>
            <td>" . escapeHtml($row['pickup_date']) . "</td>
            <td>" . escapeHtml($row['pickup_time']) . "</td>
            <td>" . escapeHtml($row['status']) . "</td>
        </tr>";
    }

    echo "</table>";
    exit;
}

// Update mode: silently succeed
if ($mode === 'update') {
    http_response_code(200);
    exit;
}

// Default: download CSV
if (!headers_sent()) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=booking_history.csv');
}

readfile($filePath);
exit;
