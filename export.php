<?php
require_once("dbsettings.php");

$conn = new mysqli($host, $user, $pswd, $dbnm);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$filePath = "booking_history.csv";
$csvFile = fopen($filePath, 'w');
fputcsv($csvFile, ['Booking Reference', 'Name', 'Phone', 'Pickup Suburb', 'Destination', 'Date', 'Time', 'Status']);

$result = $conn->query("SELECT ref, cname, phone, sbname, dsbname, pickup_date, pickup_time, status FROM bookings ORDER BY id ASC");

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
    fputcsv($csvFile, [
        $row['ref'], $row['cname'], $row['phone'], $row['sbname'],
        $row['dsbname'], $row['pickup_date'], $row['pickup_time'], $row['status']
    ]);
}
fclose($csvFile);

$mode = $_GET['mode'] ?? 'download';

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
                <td>{$row['ref']}</td>
                <td>{$row['cname']}</td>
                <td>{$row['phone']}</td>
                <td>{$row['sbname']}</td>
                <td>{$row['dsbname']}</td>
                <td>{$row['pickup_date']}</td>
                <td>{$row['pickup_time']}</td>
                <td>{$row['status']}</td>
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
