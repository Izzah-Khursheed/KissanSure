<?php
include('./include/connection.php');

header('Content-Type: application/json');

$name  = trim(mysqli_real_escape_string($conn, $_POST['name']  ?? ''));
$email = trim(mysqli_real_escape_string($conn, $_POST['email'] ?? ''));
$phone = trim(mysqli_real_escape_string($conn, $_POST['phone'] ?? ''));
$query = trim(mysqli_real_escape_string($conn, $_POST['query'] ?? ''));

if (!$name || !$email || !$query) {
    echo json_encode(['success' => false, 'message' => 'Name, email and query are required.']);
    exit;
}

// Create table if it doesn't exist
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    query TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Store in database
$sql = "INSERT INTO contact_messages (name, email, phone, query) VALUES ('$name', '$email', '$phone', '$query')";
if (!mysqli_query($conn, $sql)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save your message. Please try again.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Your message has been received! We will get back to you soon.']);
