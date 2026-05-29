<?php
// Reads from environment variables (Railway/production) or falls back to XAMPP defaults
$conn = mysqli_connect(
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASS') ?: '',
    getenv('DB_NAME') ?: 'crop_insurance_one'
);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>