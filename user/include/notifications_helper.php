<?php
function _notif_ensure_table($conn) {
    static $done = false;
    if ($done) return;
    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS notifications (
            notif_id   INT AUTO_INCREMENT PRIMARY KEY,
            farmer_id  INT NOT NULL,
            type       VARCHAR(50)  NOT NULL,
            title      VARCHAR(255) NOT NULL,
            message    TEXT         NOT NULL,
            link       VARCHAR(255) DEFAULT NULL,
            is_read    TINYINT(1)   DEFAULT 0,
            created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_farmer (farmer_id),
            INDEX idx_read   (is_read)
        )
    ");
    $done = true;
}

function add_notification($conn, $farmer_id, $type, $title, $message, $link = null) {
    _notif_ensure_table($conn);
    $farmer_id = (int)$farmer_id;
    $type    = mysqli_real_escape_string($conn, $type);
    $title   = mysqli_real_escape_string($conn, $title);
    $message = mysqli_real_escape_string($conn, $message);
    $link_sql = $link ? "'" . mysqli_real_escape_string($conn, $link) . "'" : "NULL";
    mysqli_query($conn, "
        INSERT INTO notifications (farmer_id, type, title, message, link)
        VALUES ($farmer_id, '$type', '$title', '$message', $link_sql)
    ");
}

function notif_unread_count($conn, $farmer_id) {
    _notif_ensure_table($conn);
    $r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM notifications WHERE farmer_id = " . (int)$farmer_id . " AND is_read = 0");
    return (int)(mysqli_fetch_assoc($r)['c'] ?? 0);
}

function notif_list($conn, $farmer_id, $limit = 8) {
    _notif_ensure_table($conn);
    $r = mysqli_query($conn, "SELECT * FROM notifications WHERE farmer_id = " . (int)$farmer_id . " ORDER BY created_at DESC LIMIT " . (int)$limit);
    $rows = [];
    while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
    return $rows;
}

function notif_check_premium_reminder($conn, $farmer_id) {
    _notif_ensure_table($conn);
    $fid = (int)$farmer_id;
    $r = mysqli_query($conn, "SELECT application_id FROM farmer_applications WHERE farmer_id = $fid AND status = 'Pending Payment' LIMIT 1");
    if (!$r || mysqli_num_rows($r) === 0) return;
    $app = mysqli_fetch_assoc($r);
    // Only remind once every 3 days to avoid spam
    $r2 = mysqli_query($conn, "SELECT notif_id FROM notifications WHERE farmer_id = $fid AND type = 'premium_reminder' AND created_at > DATE_SUB(NOW(), INTERVAL 3 DAY) LIMIT 1");
    if ($r2 && mysqli_num_rows($r2) > 0) return;
    add_notification($conn, $fid, 'premium_reminder',
        'Premium Payment Pending',
        'Your insurance plan is not yet active. Pay your premium to activate your coverage.',
        '/KissanSure/user/view_application_invoice.php?id=' . $app['application_id']
    );
}

function notif_icon($type) {
    return match($type) {
        'plan_active'      => '<i class="bi bi-patch-check-fill text-success"></i>',
        'payment_rejected' => '<i class="bi bi-x-circle-fill text-danger"></i>',
        'claim_approved'   => '<i class="bi bi-shield-check text-success"></i>',
        'claim_rejected'   => '<i class="bi bi-shield-x text-danger"></i>',
        'payout_sent'      => '<i class="bi bi-cash-coin text-success"></i>',
        'premium_reminder' => '<i class="bi bi-exclamation-circle-fill text-warning"></i>',
        default            => '<i class="bi bi-bell-fill text-primary"></i>',
    };
}

function notif_time_ago($dt) {
    $diff = time() - strtotime($dt);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . ' min ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('d M Y', strtotime($dt));
}
