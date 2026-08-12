<?php
// dbsettings.php - load DB settings from environment variables (with safe defaults)
// This avoids committing secrets into source control and fixes parse errors
$host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'mysql';
$user = getenv('DB_USER') !== false ? getenv('DB_USER') : 'user';
$pswd = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : 'password';
$dbnm = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'taxi_booking';
?>
