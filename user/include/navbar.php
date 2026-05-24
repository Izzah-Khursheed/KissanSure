<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>KissanSure</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="/KissanSure/logo.png" rel="icon" type="image/png">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@600;700&family=Open+Sans&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">

    <script>
    function showFlash(msg, type) {
        var icons = {success:'bi-check-circle-fill',danger:'bi-x-circle-fill',warning:'bi-exclamation-triangle-fill',info:'bi-info-circle-fill'};
        var c = document.getElementById('_fc');
        if (!c) {
            c = document.createElement('div');
            c.id = '_fc';
            c.style.cssText = 'position:fixed;top:80px;right:20px;z-index:99999;width:340px;';
            document.body.appendChild(c);
        }
        var d = document.createElement('div');
        d.style.marginBottom = '8px';
        d.className = 'alert alert-'+(type||'info')+' shadow d-flex align-items-start gap-2 p-3';
        d.style.borderRadius = '10px';
        d.innerHTML = '<i class="bi '+(icons[type]||'bi-info-circle-fill')+' flex-shrink-0 mt-1"></i>'
                    + '<div class="flex-grow-1 small">'+msg+'</div>'
                    + '<button type="button" onclick="this.closest(\'.alert\').remove()" style="background:none;border:none;padding:2px 6px;opacity:.6;font-size:1.2rem;line-height:1;cursor:pointer;">&times;</button>';
        c.appendChild(d);
        setTimeout(function(){
            d.style.cssText += 'transition:opacity .4s;opacity:0;';
            setTimeout(function(){ if(d.parentNode) d.parentNode.removeChild(d); }, 420);
        }, 5000);
    }
    </script>
</head>
<style>
    .navbar .nav-link img {
        border: 2px solid #ffc107;
        transition: 0.3s;
    }
    .navbar .nav-link img:hover {
        transform: scale(1.1);
    }

    /* Form labels and filled input text: black. Placeholders stay grey. */
    .form-label,
    .form-check-label {
        color: #000 !important;
    }
    .form-control,
    .form-select {
        color: #000 !important;
    }
    .form-control::placeholder {
        color: #6c757d !important;
        opacity: 1;
    }

    /* Notification bell styles */
    #notifBell {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255,255,255,0.13);
        display: flex !important;
        align-items: center;
        justify-content: center;
        padding: 0 !important;
        transition: background 0.2s;
    }
    #notifBell:hover, #notifBell:focus { background: rgba(255,255,255,0.25); }
    .notif-unread { background-color: #eff6ff; }
    .notif-read   { background-color: #ffffff; }
    .notif-item:hover { background-color: #dbeafe !important; }
    .notif-item { transition: background-color 0.15s; color: inherit; }
    #notifBadge { font-size: 0.6rem; padding: 3px 5px; min-width: 16px; }
</style>
<body>
    <!-- Spinner Start -->
    <div id="spinner"
        class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
    </div>
    <!-- Spinner End -->


    <!-- Topbar Start -->
    <div class="container-fluid bg-secondary top-bar wow fadeIn" data-wow-delay="0.1s">
        <div class="row align-items-center h-100">
            <div class="col-lg-4 text-center text-lg-start">
                <a href="index.php">
                    <h1 class="display-5 text-primary m-0">KissanSure</h1>
                </a>
            </div>
        </div>
    </div>
    <!-- Topbar End -->


    <!-- Navbar Start -->
    <div class="container-fluid bg-secondary px-0 wow fadeIn" data-wow-delay="0.1s">
        <div class="nav-bar">
            <nav class="navbar navbar-expand-lg bg-primary navbar-dark px-4 py-lg-0">
                <h4 class="d-lg-none m-0">Menu</h4>
                <button type="button" class="navbar-toggler me-0" data-bs-toggle="collapse"
                    data-bs-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav me-auto">
                        <a href="index.php" class="nav-item nav-link">Home</a>
                        <?php if (!isset($_SESSION['farmer_id'])): ?>
                        <a href="farmer_register.php" class="nav-item nav-link">Farmer Registration</a>
                        <?php endif; ?>
                        <a href="insurance_plans.php" class="nav-item nav-link">Insurance Plans</a>
                        <a href="view_application_plan.php" class="nav-item nav-link">My Application</a>
                        <a href="apply_claim.php" class="nav-item nav-link">Apply for Claim</a>
                        <a href="view_application_claim.php" class="nav-item nav-link">My Claims</a>
                    </div>
                   <?php if (isset($_SESSION['farmer_id'])): ?>
                    <?php
                        if (isset($conn)) {
                            include_once __DIR__ . '/notifications_helper.php';
                            notif_check_premium_reminder($conn, $_SESSION['farmer_id']);
                            $notif_count = notif_unread_count($conn, $_SESSION['farmer_id']);
                            $notif_items = notif_list($conn, $_SESSION['farmer_id'], 8);
                            // Fetch farmer's profile photo
                            $navPhotoRow = mysqli_fetch_assoc(mysqli_query($conn,
                                "SELECT profile_photo FROM register_farmer WHERE farmerid = " . (int)$_SESSION['farmer_id']
                            ));
                            $navPhoto = !empty($navPhotoRow['profile_photo'])
                                ? '/KissanSure/user/uploads/profiles/' . htmlspecialchars($navPhotoRow['profile_photo'])
                                : '/KissanSure/profile_user.jpg';
                        } else {
                            $notif_count = 0;
                            $notif_items = [];
                            $navPhoto    = '/KissanSure/profile_user.jpg';
                        }
                    ?>
                    <div class="ms-auto">
                        <ul class="navbar-nav align-items-center">

                            <!-- Notification Bell -->
                            <li class="nav-item dropdown me-2">
                                <a class="nav-link position-relative" href="#" id="notifBell"
                                   data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                                    <i class="bi bi-bell-fill fs-5 text-white"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger<?= $notif_count === 0 ? ' d-none' : ''; ?>"
                                          id="notifBadge">
                                        <?= $notif_count > 99 ? '99+' : $notif_count; ?>
                                    </span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end p-0 shadow"
                                     style="min-width:300px;max-width:360px;border-radius:10px;overflow:hidden;">
                                    <div class="d-flex justify-content-between align-items-center px-3 py-2 bg-primary text-white">
                                        <span class="fw-bold small">Notifications</span>
                                        <a href="#" id="markAllReadBtn"
                                           class="text-white text-decoration-none small<?= $notif_count === 0 ? ' d-none' : ''; ?>">
                                            Mark all read
                                        </a>
                                    </div>
                                    <div id="notifScrollArea" style="max-height:340px;overflow-y:auto;">
                                        <?php if (empty($notif_items)): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="bi bi-bell-slash d-block fs-3 mb-1 opacity-50"></i>
                                            <small>No notifications yet</small>
                                        </div>
                                        <?php else: ?>
                                            <?php foreach ($notif_items as $n): ?>
                                            <a href="<?= htmlspecialchars($n['link'] ?? '#'); ?>"
                                               class="d-flex align-items-start gap-2 px-3 py-2 border-bottom text-decoration-none notif-item<?= $n['is_read'] ? ' notif-read' : ' notif-unread'; ?>"
                                               data-id="<?= (int)$n['notif_id']; ?>">
                                                <span class="flex-shrink-0 mt-1"><?= notif_icon($n['type']); ?></span>
                                                <div style="min-width:0;flex:1;">
                                                    <div class="small fw-semibold <?= $n['is_read'] ? 'text-muted' : 'text-dark'; ?>">
                                                        <?= htmlspecialchars($n['title']); ?>
                                                    </div>
                                                    <div class="small text-muted" style="white-space:normal;line-height:1.3;">
                                                        <?= htmlspecialchars($n['message']); ?>
                                                    </div>
                                                    <div class="text-muted mt-1" style="font-size:0.68rem;">
                                                        <?= notif_time_ago($n['created_at']); ?>
                                                    </div>
                                                </div>
                                                <?php if (!$n['is_read']): ?>
                                                <span class="flex-shrink-0 bg-primary rounded-circle mt-2"
                                                      style="width:7px;height:7px;min-width:7px;"></span>
                                                <?php endif; ?>
                                            </a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>

                            <!-- Profile Dropdown -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                                    <img src="<?= $navPhoto; ?>"
                                        alt="User"
                                        class="rounded-circle"
                                        width="35" height="35"
                                        style="object-fit:cover;margin-right:8px;">
                                    <span><?php echo ucwords($_SESSION['farmer_name']); ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="farmer_profile.php">My Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                                </ul>
                            </li>

                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="ms-auto">
                        <a href="login.php" class="btn btn-warning btn-sm me-2">Login</a>
                        <a href="farmer_register.php" class="btn btn-outline-light btn-sm">Register</a>
                    </div>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </div>
    <!-- Navbar End -->
     