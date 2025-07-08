<?php
    require_once("dbsettings.php");

    $conn = new mysqli($host, $user, $pswd, $dbnm);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Set headers to prompt download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=booking_history.csv');

    // Output stream
    $output = fopen('php://output', 'w');

    // Column headers
    fputcsv($output, ['Booking Reference', 'Name', 'Phone', 'Pickup Suburb', 'Destination', 'Date', 'Time', 'Status']);

    // Query all booking records
    $result = $conn->query("SELECT ref, cname, phone, sbname, dsbname, pickup_date, pickup_time, status FROM bookings ORDER BY id ASC");

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['ref'],
            $row['cname'],
            $row['phone'],
            $row['sbname'],
            $row['dsbname'],
            $row['pickup_date'],
            $row['pickup_time'],
            $row['status']
        ]);
    }

    fclose($output);
    $conn->close();
    exit;
?>
