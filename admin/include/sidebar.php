<style>
        /* Sidebar */
        .sidebar {
            width: 240px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #212529;
            padding-top: 70px;
            color: white;
            overflow-y: auto;
        }
        .sidebar a {
            display: block;
            padding: 12px 18px;
            color: white;
            text-decoration: none;
            border-bottom: 1px solid #343a40;
        }
        .sidebar a:hover {
            background: #343a40;
        }
        .sidebar .module-title {
            padding: 10px 18px;
            font-weight: bold;
            background: #343a40;
            margin-top: 5px;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background-color: #495057;
            border-radius: 5px;
        }

        /* Main Content */
        .main-content {
            margin-left: 240px;
            padding: 20px;
            padding-top: 80px;
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 10;
        }
    </style>

<!-- TOP NAVBAR -->
<nav class="navbar navbar-dark bg-primary">
    <div class="container-fluid">
        <a href="dashboard.php" class="navbar-brand fw-bold">Admin Dashboard</a>
        <div>
            <a href="login.php" class="btn btn-light btn-sm">Logout</a>
        </div>
    </div>
</nav>


<!-- SIDEBAR MENU -->
<div class="sidebar">

    <div class="module-title">Farmer Management</div>
    <a href="add_register.php">Add Farmer</a>
    <a href="view_register.php">View Farmers</a>

    <div class="module-title">Insurance Plans</div>
    <a href="add_plan.php">Create Plan</a>
    <a href="view_plan.php">View Plans</a>

    <div class="module-title">Farmer Applications</div>
    <a href="add_insurance_application.php">Application Form</a>
    <a href="view_insurance_application.php">View Application</a>

    <div class="module-title">Claims</div>
    <a href="add_file_claim.php">File Claims</a>
    <a href="view_claims.php">View Claims</a>

    <div class="module-title">Financial</div>
    <a href="manage_rates.php">Crop Market Rates</a>

</div>

<div class="main-content">