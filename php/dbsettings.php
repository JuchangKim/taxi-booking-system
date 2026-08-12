<!-- dbsettings.php -->
<!-- This file contains database connection settings for the application. -->
<?php
$host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'mysql';
$user = getenv('DB_USER') !== false ? getenv('DB_USER') : 'user';
$pswd = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : 'password';
$dbnm = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'taxi_booking';
?>

