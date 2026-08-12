<?php
require_once(__DIR__ . '/dbsettings.php');

/**
 * Escape output for safe HTML rendering.
 */
function escapeHtml(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Write a timestamped message to the PHP error log.
 */
function logger(string $message): void {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] admin.php: $message");
}

/**
 * Load named SQL queries from mysqlcommand.txt.
 */
function loadQueries(string $filename): array {
    $content = @file_get_contents($filename);
    if ($content === false) {
        return [];
    }

    $names = [
        'CREATE_TABLE',
        'UPDATE_STATUS',
        'SELECT_BY_REF',
        'SELECT_WITHIN_2HRS',
        'UPDATE_BOOKING',
        'DELETE_BOOKING',
    ];

    $queries = [];
    foreach ($names as $name) {
        if (preg_match('/-- ' . preg_quote($name, '/') . '\s*(.+?);/is', $content, $matches)) {
            $queries[$name] = trim($matches[1]) . ";";
        }
    }

    return $queries;
}

/**
 * Validate the booking reference format.
 */
function isValidReference(string $ref): bool {
    return preg_match('/^BRN\d{5}$/', $ref) === 1;
}

/**
 * Render a single booking row.
 */
function renderBookingRow(array $row): string {
    $ref = escapeHtml($row['ref'] ?? '');
    $cname = escapeHtml($row['cname'] ?? '');
    $phone = escapeHtml($row['phone'] ?? '');
    $sbname = escapeHtml($row['sbname'] ?? '');
    $dsbname = escapeHtml($row['dsbname'] ?? '');
    $pickupDate = escapeHtml($row['pickup_date'] ?? '');
    $pickupTime = escapeHtml($row['pickup_time'] ?? '');
    $status = escapeHtml($row['status'] ?? '');

    $datetime = $pickupDate && $pickupTime ? date('d/m/Y H:i', strtotime("{$pickupDate} {$pickupTime}")) : '';
    $assignButton = $status === 'assigned'
        ? '<button disabled style="background-color: lightgray;">Assign</button>'
        : "<button onclick=\"assign('$ref', event)\">Assign</button>";

    $editLink = 'edit.html?' . http_build_query([
        'ref' => $row['ref'] ?? '',
        'cname' => $row['cname'] ?? '',
        'phone' => $row['phone'] ?? '',
        'unumber' => $row['unumber'] ?? '',
        'snumber' => $row['snumber'] ?? '',
        'stname' => $row['stname'] ?? '',
        'sbname' => $row['sbname'] ?? '',
        'dsbname' => $row['dsbname'] ?? '',
        'pickup_date' => $row['pickup_date'] ?? '',
        'pickup_time' => $row['pickup_time'] ?? '',
    ]);

    $editBtn = "<a href=\"$editLink\"><button>Edit</button></a>";
    $deleteBtn = "<button onclick=\"deleteBooking('$ref', event)\">Delete</button>";

    return "<tr>
        <td>$ref</td>
        <td>$cname</td>
        <td>$phone</td>
        <td>$sbname</td>
        <td>$dsbname</td>
        <td>$datetime</td>
        <td class=\"status-cell\">$status</td>
        <td>$assignButton $editBtn $deleteBtn</td>
    </tr>";
}

function renderTable(array $rows): void {
    if (empty($rows)) {
        echo '<p>No booking history found.</p>';
        return;
    }

    echo '<table><tr>' .
        '<th>Booking Reference Number</th>' .
        '<th>Customer Name</th>' .
        '<th>Phone</th>' .
        '<th>Pickup Suburb</th>' .
        '<th>Destination Suburb</th>' .
        '<th>Pickup Date and Time</th>' .
        '<th>Status</th>' .
        '<th>Assign</th>' .
        '</tr>';

    foreach ($rows as $row) {
        echo renderBookingRow($row);
    }

    echo '</table>';
}

$queries = loadQueries('mysqlcommand.txt');
if (empty($queries['CREATE_TABLE']) || empty($queries['UPDATE_STATUS']) || empty($queries['SELECT_BY_REF']) || empty($queries['UPDATE_BOOKING']) || empty($queries['DELETE_BOOKING'])) {
    logger('Required SQL query blocks are missing from mysqlcommand.txt.');
    http_response_code(500);
    die('<p style="color:red;">Server configuration error. Please contact the administrator.</p>');
}

$conn = new mysqli($host, $user, $pswd, $dbnm);
if ($conn->connect_error) {
    logger('Database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    die('<p style="color:red;">Connection failed. Please try again later.</p>');
}

if (!$conn->query($queries['CREATE_TABLE'])) {
    logger('Failed to ensure booking table exists: ' . $conn->error);
    http_response_code(500);
    die('<p style="color:red;">Server error while initializing the booking database.</p>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign'])) {
        $ref = trim($_POST['assign']);
        if (!isValidReference($ref)) {
            http_response_code(400);
            echo '<p style="color:red;">Invalid booking reference.</p>';
            exit;
        }

        $stmt = $conn->prepare($queries['UPDATE_STATUS']);
        if (!$stmt) {
            logger('Failed to prepare assign statement: ' . $conn->error);
            http_response_code(500);
            echo '<p style="color:red;">Failed to update booking status.</p>';
            exit;
        }

        $stmt->bind_param('s', $ref);
        if (!$stmt->execute()) {
            logger('Failed to assign booking ' . $ref . ': ' . $stmt->error);
            http_response_code(500);
            echo '<p style="color:red;">Failed to assign booking.</p>';
            exit;
        }

        echo '<p>Congratulations! Booking request ' . escapeHtml($ref) . ' has been assigned!</p>';
        exit;
    }

    if (isset($_POST['delete'])) {
        $ref = trim($_POST['delete']);
        if (!isValidReference($ref)) {
            http_response_code(400);
            echo '<p style="color:red;">Invalid booking reference.</p>';
            exit;
        }

        $stmt = $conn->prepare($queries['DELETE_BOOKING']);
        if (!$stmt) {
            logger('Failed to prepare delete statement: ' . $conn->error);
            http_response_code(500);
            echo '<p style="color:red;">Failed to delete booking.</p>';
            exit;
        }

        $stmt->bind_param('s', $ref);
        if (!$stmt->execute()) {
            logger('Failed to delete booking ' . $ref . ': ' . $stmt->error);
            http_response_code(500);
            echo '<p style="color:red;">Failed to delete booking.</p>';
            exit;
        }

        echo '<p>Booking ' . escapeHtml($ref) . ' has been deleted.</p>';
        exit;
    }

    if (isset($_POST['update'])) {
        $ref = trim($_POST['ref'] ?? '');
        $cname = trim($_POST['cname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $unumber = trim($_POST['unumber'] ?? '');
        $snumber = trim($_POST['snumber'] ?? '');
        $stname = trim($_POST['stname'] ?? '');
        $sbname = trim($_POST['sbname'] ?? '');
        $dsbname = trim($_POST['dsbname'] ?? '');
        $pickupDate = trim($_POST['pickup_date'] ?? '');
        $pickupTime = trim($_POST['pickup_time'] ?? '');

        if (!isValidReference($ref)) {
            http_response_code(400);
            echo '<p style="color:red;">Invalid booking reference.</p>';
            exit;
        }

        if ($cname === '' || $phone === '' || $snumber === '' || $stname === '' || $pickupDate === '' || $pickupTime === '') {
            http_response_code(400);
            echo '<p style="color:red;">Missing required booking fields.</p>';
            exit;
        }

        if (!preg_match('/^\d{10,12}$/', $phone)) {
            http_response_code(400);
            echo '<p style="color:red;">Phone number must be 10 to 12 digits.</p>';
            exit;
        }

        $pickup = DateTime::createFromFormat('Y-m-d H:i', $pickupDate . ' ' . $pickupTime);
        if (!$pickup || $pickup < new DateTime('now')) {
            http_response_code(400);
            echo '<p style="color:red;">Pickup date/time must not be in the past.</p>';
            exit;
        }

        $stmt = $conn->prepare($queries['UPDATE_BOOKING']);
        if (!$stmt) {
            logger('Failed to prepare update statement: ' . $conn->error);
            http_response_code(500);
            echo '<p style="color:red;">Failed to update booking.</p>';
            exit;
        }

        $stmt->bind_param(
            'ssssssssss',
            $cname,
            $phone,
            $unumber,
            $snumber,
            $stname,
            $sbname,
            $dsbname,
            $pickupDate,
            $pickupTime,
            $ref
        );

        if (!$stmt->execute()) {
            logger('Failed to update booking ' . $ref . ': ' . $stmt->error);
            http_response_code(500);
            echo '<p style="color:red;">Failed to update booking.</p>';
            exit;
        }

        echo '<p>Booking ' . escapeHtml($ref) . ' has been updated.</p>';
        exit;
    }

    if (isset($_POST['all'])) {
        $result = $conn->query('SELECT * FROM bookings ORDER BY pickup_date, pickup_time');
        if ($result && $result->num_rows > 0) {
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            renderTable($rows);
        } else {
            echo '<p>No booking history found.</p>';
        }
        exit;
    }

    $ref = trim($_POST['ref'] ?? '');
    if ($ref !== '') {
        if (!isValidReference($ref)) {
            http_response_code(400);
            echo '<p style="color:red;">Invalid booking reference format. Use format: BRN12345</p>';
            exit;
        }

        $stmt = $conn->prepare($queries['SELECT_BY_REF']);
        if (!$stmt) {
            logger('Failed to prepare select-by-ref statement: ' . $conn->error);
            http_response_code(500);
            echo '<p style="color:red;">Server error while searching for booking.</p>';
            exit;
        }

        $stmt->bind_param('s', $ref);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        renderTable($rows);
        exit;
    }

    $defaultQuery = $queries['SELECT_WITHIN_2HRS'] ?? 'SELECT * FROM bookings ORDER BY pickup_date, pickup_time';
    $result = $conn->query($defaultQuery);
    if ($result && $result->num_rows > 0) {
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        renderTable($rows);
    } else {
        echo '<p>No booking history found.</p>';
    }
    exit;
}

http_response_code(405);
echo '<p style="color:red;">Unsupported request method.</p>';
?>
