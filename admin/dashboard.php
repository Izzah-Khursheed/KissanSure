<?php
include("./include/connection.php");
include("./include/header.php");
include("./include/sidebar.php");
// Total Farmers
$farmers = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total FROM register_farmer"));

// Total Policies / Applications
$policies = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total FROM farmer_applications"));

// Pending Claims
$pending_claims = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total FROM insurance_claims WHERE claim_status='Pending'"));

// Approved Claims
$approved_claims = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) AS total FROM insurance_claims WHERE claim_status='Approved'"));
?>
<style>
.main-content {
    margin-left: 120px !important;
    padding: 20px;
}

.stats-container {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 30px;
}

.stat-card {
    position: relative;
    flex: 1 1 22%;
    min-width: 220px;
    height: 150px;
    border-radius: 20px;
    overflow: hidden;
    color: white;
    cursor: pointer;
    background: var(--card-color);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-card:hover {
    transform: translateY(-10px) scale(1.05);
    box-shadow: 0 15px 30px rgba(0,0,0,0.25);
}

.stat-card .card-bg {
    position: absolute;
    inset: 0;
    background-image: var(--bg-img);
    background-size: cover;
    background-position: center;
    opacity: 0.2;
    filter: brightness(0.7);
}

.stat-card .card-content {
    position: relative;
    z-index: 2;
    text-align: center;
}

.stat-card h6 {
    font-size: 14px;
    opacity: 0.9;
    margin: 0;
}

.stat-card h2 {
    font-size: 32px;
    font-weight: bold;
    margin: 5px 0 0 0;
}

@media(max-width: 900px){
    .stat-card {
        flex: 1 1 45%;
        margin-bottom: 20px;
    }
}

@media(max-width: 600px){
    .stat-card {
        flex: 1 1 100%;
    }
}
</style>


<div class="main-content">
    <div class="stats-container">

        <!-- Farmers -->
        <div class="stat-card" style="--card-color:#4A90E2; --bg-img:url('../3.jfif')">
            <div class="card-bg"></div>
            <div class="card-content">
                <h6>Total Farmers</h6>
                <h2><?= $farmers['total']; ?></h2>
            </div>
        </div>

        <!-- Policies -->
        <div class="stat-card" style="--card-color:#50E3C2; --bg-img:url('../download.jfif')">
            <div class="card-bg"></div>
            <div class="card-content">
                <h6>Total Policies</h6>
                <h2><?= $policies['total']; ?></h2>
            </div>
        </div>

        <!-- Pending Claims -->
        <div class="stat-card" style="--card-color:#F5A623; --bg-img:url('../3.jfif')">
            <div class="card-bg"></div>
            <div class="card-content">
                <h6>Pending Claims</h6>
                <h2><?= $pending_claims['total']; ?></h2>
            </div>
        </div>

        <!-- Approved Claims -->
        <div class="stat-card" style="--card-color:#7ED321; --bg-img:url('../download (1).jfif')">
            <div class="card-bg"></div>
            <div class="card-content">
                <h6>Approved Claims</h6>
                <h2><?= $approved_claims['total']; ?></h2>
            </div>
        </div>

    </div>
</div>


<?php include("./include/footer.php"); ?>

</body>
</html>
