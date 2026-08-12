<?php
require_once(__DIR__ . '/dbsettings.php');

mysqli_report(MYSQLI_REPORT_OFF);

function escapeHtml(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function respondWithError(string $mode, string $message): void {
    http_response_code(503);

    if ($mode === 'html' || $mode === 'update') {
        echo "<p style='color:red; font-weight:bold;'>$message</p>";
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
    }

    exit;
}

$filePath = "booking_history.csv";
$mode = $_GET['mode'] ?? 'download';

$conn = new mysqli($host, $user, $pswd, $dbnm);
if ($conn->connect_error) {
    respondWithError($mode, "Booking history is unavailable right now because the database connection failed.");
}

$result = $conn->query("SELECT ref, cname, phone, sbname, dsbname, pickup_date, pickup_time, status FROM bookings ORDER BY id ASC");
if (!$result) {
    respondWithError($mode, "Booking history could not be loaded from the database.");
}

$csvFile = fopen($filePath, 'w');
if (!$csvFile) {
    respondWithError($mode, "Booking history file could not be written.");
}

fputcsv($csvFile, ['Booking Reference', 'Name', 'Phone', 'Pickup Suburb', 'Destination', 'Date', 'Time', 'Status']);


$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
    fputcsv($csvFile, [
        $row['ref'], $row['cname'], $row['phone'], $row['sbname'],
        $row['dsbname'], $row['pickup_date'], $row['pickup_time'], $row['status']
    ]);
}
fclose($csvFile);

if ($mode === 'html') {
    if (count($rows) > 0) {
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
    } else {
        echo "<p>No booking history found.</p>";
    }
}
else if ($mode === 'update') {
    // Do nothing, just update the file
    http_response_code(200);
}
else {
    // Default: download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=booking_history.csv');
    readfile($filePath);
}

$conn->close();
?>
