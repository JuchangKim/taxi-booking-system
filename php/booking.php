<!-- booking.php -->
<?php
require_once(__DIR__ . '/dbsettings.php');

/**
 * Safe HTML encoding for user data displayed in the browser.
 */
function escapeHtml(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Write a timestamped message to the PHP error log.
 */
function logger(string $message): void {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] booking.php: $message");
}

/**
 * Load named SQL statements from the external mysqlcommand.txt file.
 */
function loadQueries(string $filename): array {
    $content = @file_get_contents($filename);
    if ($content === false) {
        return [];
    }

    $queries = [];
    $blocks = [
        'CREATE_TABLE' => '/-- CREATE_TABLE\s*(CREATE TABLE .*?);/is',
        'GET_MAX_ID' => '/-- GET_MAX_ID\s*(SELECT .*?);/is',
        'INSERT_BOOKING' => '/-- INSERT_BOOKING\s*(INSERT INTO .*?);/is',
    ];

    foreach ($blocks as $name => $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $queries[$name] = trim($matches[1]) . ";";
        }
    }

    return $queries;
}

/**
 * Validate booking form input and return an array of cleaned values.
 */
function validateBookingInput(array $posted, array &$errors): array {
    $errors = [];
    $data = [];

    $data['cname'] = trim($posted['cname'] ?? '');
    $data['phone'] = trim($posted['phone'] ?? '');
    $data['unumber'] = trim($posted['unumber'] ?? '');
    $data['snumber'] = trim($posted['snumber'] ?? '');
    $data['stname'] = trim($posted['stname'] ?? '');
    $data['sbname'] = trim($posted['sbname'] ?? '');
    $data['dsbname'] = trim($posted['dsbname'] ?? '');
    $data['date'] = trim($posted['date'] ?? '');
    $data['time'] = trim($posted['time'] ?? '');

    if ($data['cname'] === '') {
        $errors[] = 'Customer name is required.';
    }

    if (!preg_match('/^\d{10,12}$/', $data['phone'])) {
        $errors[] = 'Phone number must be 10 to 12 digits.';
    }

    if ($data['snumber'] === '') {
        $errors[] = 'Street number is required.';
    }

    if ($data['stname'] === '') {
        $errors[] = 'Street name is required.';
    }

    if ($data['date'] === '' || $data['time'] === '') {
        $errors[] = 'Pickup date and time are required.';
    } else {
        $pickup = DateTime::createFromFormat('Y-m-d H:i', $data['date'] . ' ' . $data['time']);
        $now = new DateTime('now');
        if (!$pickup) {
            $errors[] = 'Pickup date/time format is invalid.';
        } elseif ($pickup < $now) {
            $errors[] = 'Pickup date/time must not be in the past.';
        }
    }

    if (count($errors) > 0) {
        return $data;
    }

    return $data;
}

$queries = loadQueries("mysqlcommand.txt");
if (empty($queries['CREATE_TABLE']) || empty($queries['GET_MAX_ID']) || empty($queries['INSERT_BOOKING'])) {
    logger('Required SQL queries are missing from mysqlcommand.txt.');
    http_response_code(500);
    die("<p style='color:white;'>Server configuration error. Please contact the site administrator.</p>");
}

$conn = new mysqli($host, $user, $pswd, $dbnm);
if ($conn->connect_error) {
    logger('Database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    die("<p style='color:white;'>Connection failed. Please try again later.</p>");
}

if (!$conn->query($queries['CREATE_TABLE'])) {
    logger('Unable to create booking table: ' . $conn->error);
    http_response_code(500);
    die("<p style='color:white;'>Server error while initializing booking database.</p>");
}

$validationErrors = [];
$input = validateBookingInput($_POST, $validationErrors);
if (!empty($validationErrors)) {
    logger('Validation failed: ' . implode(' | ', $validationErrors));
    $errorText = escapeHtml(implode(' ', $validationErrors));
    die("<p style='color:white;'>$errorText</p>");
}

$nextId = 1;
$result = $conn->query($queries['GET_MAX_ID']);
if ($result) {
    $row = $result->fetch_assoc();
    $maxId = isset($row['max_id']) && $row['max_id'] !== null ? intval($row['max_id']) : 0;
    $nextId = $maxId + 1;
}

$ref = 'BRN' . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
$created = date('Y-m-d H:i:s');
$status = 'unassigned';

$stmt = $conn->prepare($queries['INSERT_BOOKING']);
if (!$stmt) {
    logger('Error preparing insert statement: ' . $conn->error);
    http_response_code(500);
    die("<p style='color:white;'>Failed to save booking. Please try again later.</p>");
}

if (!$stmt->bind_param(
    'ssssssssssss',
    $ref,
    $input['cname'],
    $input['phone'],
    $input['unumber'],
    $input['snumber'],
    $input['stname'],
    $input['sbname'],
    $input['dsbname'],
    $input['date'],
    $input['time'],
    $created,
    $status
)) {
    logger('Error binding parameters: ' . $stmt->error);
    http_response_code(500);
    die("<p style='color:white;'>Failed to save booking. Please try again later.</p>");
}

if (!$stmt->execute()) {
    logger('Failed to insert booking: ' . $stmt->error . ' values: ' . json_encode($input));
    http_response_code(500);
    die("<p style='color:white;'>Failed to save booking. Please try again later.</p>");
}

$formattedDate = escapeHtml(date('d/m/Y', strtotime($input['date'])));
$formattedTime = escapeHtml($input['time']);
$reference = escapeHtml($ref);

logger("Created booking $reference for {$input['cname']}.");

echo "<p>Thank you for your booking!<br><br>Booking reference number: $reference<br>Pickup time: $formattedTime<br>Pickup date: $formattedDate</p>";
?>
