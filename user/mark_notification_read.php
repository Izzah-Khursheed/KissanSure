<?php
session_start();
require_once __DIR__ . '/include/connection.php';
require_once __DIR__ . '/include/notifications_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['farmer_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false]);
    exit();
}

$fid  = (int)$_SESSION['farmer_id'];
$mode = $_POST['mode'] ?? 'all';

_notif_ensure_table($conn);

if ($mode === 'all') {
    mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE farmer_id = $fid");
} elseif ($mode === 'one' && isset($_POST['id'])) {
    $nid = (int)$_POST['id'];
    mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE notif_id = $nid AND farmer_id = $fid");
}

echo json_encode(['ok' => true]);
