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

// Email is now handled by EmailJS on the frontend.
// PHPMailer code kept below for future use (e.g. when site goes live with a verified domain).

/*
$emailSent = false;
$phpmailerPath = __DIR__ . '/PHPMailer/src/';

if (is_dir($phpmailerPath)) {
    require $phpmailerPath . 'Exception.php';
    require $phpmailerPath . 'PHPMailer.php';
    require $phpmailerPath . 'SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = BREVO_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = BREVO_USERNAME;
        $mail->Password   = BREVO_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = BREVO_PORT;

        $mail->setFrom(BREVO_USERNAME, 'KissanSure');
        $mail->addAddress(MAIL_TO);
        $mail->addReplyTo($email, $name);

        $mail->isHTML(true);
        $mail->Subject = 'New Contact Message from ' . $name;
        $mail->Body    = "
            <h3>New message from KissanSure contact form</h3>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Phone:</strong> $phone</p>
            <p><strong>Query:</strong><br>$query</p>
        ";

        $mail->send();
        $emailSent = true;
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Mail error: ' . $e->getMessage()]);
        exit;
    }
}
*/

echo json_encode(['success' => true, 'message' => 'Your message has been received! We will get back to you soon.']);
