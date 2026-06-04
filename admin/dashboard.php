<?php
// Admin dashboard — shows key system stats and interactive charts.
// All numbers are queried live from the database on each page load.
include("./include/connection.php");
include("./include/header.php");
include("./include/sidebar.php");

// ── STAT CARD QUERIES ──────────────────────────────────────────────────────────
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

// ── CHART DATA ─────────────────────────────────────────────────────────────────
// Last 6 month labels
$labels = [];
for ($i = 5; $i >= 0; $i--) {
    $labels[] = date('M Y', strtotime("-$i months"));
}

// Monthly Applications by application_date
$app_rows = [];
$r = mysqli_query($conn, "
    SELECT DATE_FORMAT(application_date, '%b %Y') AS month, COUNT(*) AS total
    FROM farmer_applications
    WHERE application_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(application_date, '%Y-%m')
    ORDER BY MIN(application_date) ASC
");
while ($row = mysqli_fetch_assoc($r)) $app_rows[$row['month']] = (int)$row['total'];

// Monthly Claims by loss_date
$claim_rows_chart = [];
$r2 = mysqli_query($conn, "
    SELECT DATE_FORMAT(loss_date, '%b %Y') AS month, COUNT(*) AS total
    FROM insurance_claims
    WHERE loss_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(loss_date, '%Y-%m')
    ORDER BY MIN(loss_date) ASC
");
while ($row = mysqli_fetch_assoc($r2)) $claim_rows_chart[$row['month']] = (int)$row['total'];

$apps_data   = array_map(fn($l) => $app_rows[$l]        ?? 0, $labels);
$claims_data = array_map(fn($l) => $claim_rows_chart[$l] ?? 0, $labels);

// Plan Distribution
$plan_labels = [];
$plan_data   = [];
$r3 = mysqli_query($conn, "
    SELECT ip.plan_name, COUNT(fa.application_id) AS total
    FROM farmer_applications fa
    JOIN insurance_plan ip ON fa.plan_id = ip.plan_id
    GROUP BY ip.plan_name
    ORDER BY total DESC
");
while ($row = mysqli_fetch_assoc($r3)) {
    $plan_labels[] = $row['plan_name'];
    $plan_data[]   = (int)$row['total'];
}

// ── SECOND ROW STAT CARDS ──────────────────────────────────────────────────────
// --- Extra Stat Cards ---
$active_policies = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM farmer_applications WHERE status = 'Active'"));

$total_premium = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(final_premium), 0) AS total FROM farmer_applications WHERE status = 'Active'"));

$total_payouts = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(final_payout), 0) AS total FROM insurance_claims WHERE payout_status IN ('Sent','Completed')"));

$under_review = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM insurance_claims WHERE claim_status IN ('AI Analyzed','Under Review')"));

// Breakdown of claims grouped by their current status (for the doughnut chart)
$claim_status_labels = [];
$claim_status_data   = [];
$r4 = mysqli_query($conn, "SELECT claim_status, COUNT(*) AS total FROM insurance_claims GROUP BY claim_status ORDER BY total DESC");
while ($row = mysqli_fetch_assoc($r4)) {
    $claim_status_labels[] = $row['claim_status'];
    $claim_status_data[]   = (int)$row['total'];
}

// Recent Activity — last 5 applications
$recent_apps = [];
$r5 = mysqli_query($conn, "
    SELECT fa.full_name, ip.plan_name, fa.status, fa.application_date
    FROM farmer_applications fa
    JOIN insurance_plan ip ON fa.plan_id = ip.plan_id
    ORDER BY fa.application_id DESC LIMIT 5
");
while ($row = mysqli_fetch_assoc($r5)) $recent_apps[] = $row;

// Recent Activity — last 5 claims
$recent_claims = [];
$r6 = mysqli_query($conn, "
    SELECT fa.full_name, ip.plan_name, ic.claim_status, ic.loss_date
    FROM insurance_claims ic
    JOIN farmer_applications fa ON ic.application_id = fa.application_id
    JOIN insurance_plan ip ON fa.plan_id = ip.plan_id
    ORDER BY ic.claim_id DESC LIMIT 5
");
while ($row = mysqli_fetch_assoc($r6)) $recent_claims[] = $row;
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

    <!-- Second Stats Row -->
    <div class="stats-container" style="margin-top:16px;">

        <div class="stat-card" style="--card-color:#6f42c1; --bg-img:url('../3.jfif')">
            <div class="card-bg"></div>
            <div class="card-content">
                <h6>Active Policies</h6>
                <h2><?= $active_policies['total']; ?></h2>
            </div>
        </div>

        <div class="stat-card" style="--card-color:#e83e8c; --bg-img:url('../download.jfif')">
            <div class="card-bg"></div>
            <div class="card-content">
                <h6>Premium Collected</h6>
                <h2 style="font-size:22px;">PKR <?= number_format($total_premium['total'], 0); ?></h2>
            </div>
        </div>

        <div class="stat-card" style="--card-color:#20c997; --bg-img:url('../download (1).jfif')">
            <div class="card-bg"></div>
            <div class="card-content">
                <h6>Total Payouts Issued</h6>
                <h2 style="font-size:22px;">PKR <?= number_format($total_payouts['total'], 0); ?></h2>
            </div>
        </div>

        <div class="stat-card" style="--card-color:#dc3545; --bg-img:url('../3.jfif')">
            <div class="card-bg"></div>
            <div class="card-content">
                <h6>Under Review</h6>
                <h2><?= $under_review['total']; ?></h2>
            </div>
        </div>

    </div>

    <!-- Charts Row -->
    <div style="display:flex; gap:20px; margin-top:30px; flex-wrap:wrap;">

        <!-- Monthly Bar Chart -->
        <div style="flex:2; min-width:320px; background:#fff; border-radius:16px; box-shadow:0 4px 18px rgba(0,0,0,0.08); padding:24px;">
            <h6 style="font-weight:600; margin-bottom:16px; color:#333;">Monthly Applications &amp; Claims <span style="font-size:12px; color:#999; font-weight:400;">(last 6 months)</span></h6>
            <canvas id="monthlyChart"></canvas>
        </div>

        <!-- Plan Distribution Doughnut -->
        <div style="flex:1; min-width:260px; background:#fff; border-radius:16px; box-shadow:0 4px 18px rgba(0,0,0,0.08); padding:24px; display:flex; flex-direction:column; align-items:center;">
            <h6 style="font-weight:600; margin-bottom:16px; color:#333; align-self:flex-start;">Applications by Plan</h6>
            <canvas id="planChart" style="max-width:240px;"></canvas>
        </div>

    </div>

    <!-- Second Charts Row -->
    <div style="display:flex; gap:20px; margin-top:24px; flex-wrap:wrap;">

        <!-- Claims by Status Doughnut -->
        <div style="flex:1; min-width:260px; background:#fff; border-radius:16px; box-shadow:0 4px 18px rgba(0,0,0,0.08); padding:24px; display:flex; flex-direction:column; align-items:center;">
            <h6 style="font-weight:600; margin-bottom:16px; color:#333; align-self:flex-start;">Claims by Status</h6>
            <canvas id="claimStatusChart" style="max-width:240px;"></canvas>
        </div>

        <!-- Recent Activity -->
        <div style="flex:3; min-width:320px; background:#fff; border-radius:16px; box-shadow:0 4px 18px rgba(0,0,0,0.08); padding:24px;">
            <h6 style="font-weight:600; margin-bottom:16px; color:#333;">Recent Activity</h6>
            <div style="display:flex; gap:20px; flex-wrap:wrap;">

                <!-- Recent Applications -->
                <div style="flex:1; min-width:220px;">
                    <p style="font-size:13px; font-weight:600; color:#4A90E2; margin-bottom:8px;">Latest Applications</p>
                    <table style="width:100%; font-size:13px; border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:1px solid #eee;">
                                <th style="padding:4px 6px; text-align:left; color:#999; font-weight:500;">Farmer</th>
                                <th style="padding:4px 6px; text-align:left; color:#999; font-weight:500;">Plan</th>
                                <th style="padding:4px 6px; text-align:left; color:#999; font-weight:500;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_apps as $a): ?>
                            <tr style="border-bottom:1px solid #f5f5f5;">
                                <td style="padding:5px 6px;"><?= htmlspecialchars($a['full_name']); ?></td>
                                <td style="padding:5px 6px; color:#555;"><?= htmlspecialchars($a['plan_name']); ?></td>
                                <td style="padding:5px 6px;">
                                    <?php
                                    $sc = match($a['status']) {
                                        'Active'          => '#20c997',
                                        'Pending Payment' => '#F5A623',
                                        'Rejected'        => '#dc3545',
                                        default           => '#6c757d'
                                    };
                                    ?>
                                    <span style="background:<?= $sc ?>22; color:<?= $sc ?>; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:600;"><?= $a['status']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_apps)): ?>
                            <tr><td colspan="3" style="padding:10px 6px; color:#999; text-align:center;">No applications yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Claims -->
                <div style="flex:1; min-width:220px;">
                    <p style="font-size:13px; font-weight:600; color:#F5A623; margin-bottom:8px;">Latest Claims</p>
                    <table style="width:100%; font-size:13px; border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:1px solid #eee;">
                                <th style="padding:4px 6px; text-align:left; color:#999; font-weight:500;">Farmer</th>
                                <th style="padding:4px 6px; text-align:left; color:#999; font-weight:500;">Plan</th>
                                <th style="padding:4px 6px; text-align:left; color:#999; font-weight:500;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_claims as $c): ?>
                            <tr style="border-bottom:1px solid #f5f5f5;">
                                <td style="padding:5px 6px;"><?= htmlspecialchars($c['full_name']); ?></td>
                                <td style="padding:5px 6px; color:#555;"><?= htmlspecialchars($c['plan_name']); ?></td>
                                <td style="padding:5px 6px;">
                                    <?php
                                    $cc = match($c['claim_status']) {
                                        'Approved','Payout Sent','Completed' => '#20c997',
                                        'Rejected'                           => '#dc3545',
                                        'AI Analyzed','Under Review'         => '#F5A623',
                                        default                              => '#6c757d'
                                    };
                                    ?>
                                    <span style="background:<?= $cc ?>22; color:<?= $cc ?>; padding:2px 8px; border-radius:20px; font-size:11px; font-weight:600;"><?= $c['claim_status']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_claims)): ?>
                            <tr><td colspan="3" style="padding:10px 6px; color:#999; text-align:center;">No claims yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const monthlyLabels = <?= json_encode($labels) ?>;
const appsData      = <?= json_encode($apps_data) ?>;
const claimsData    = <?= json_encode($claims_data) ?>;
const planLabels    = <?= json_encode($plan_labels) ?>;
const planData      = <?= json_encode($plan_data) ?>;

// Bar chart
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: monthlyLabels,
        datasets: [
            {
                label: 'Applications',
                data: appsData,
                backgroundColor: 'rgba(74,144,226,0.75)',
                borderColor: 'rgba(74,144,226,1)',
                borderWidth: 1,
                borderRadius: 5
            },
            {
                label: 'Claims',
                data: claimsData,
                backgroundColor: 'rgba(245,166,35,0.75)',
                borderColor: 'rgba(245,166,35,1)',
                borderWidth: 1,
                borderRadius: 5
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } }
        }
    }
});

// Plan Distribution Doughnut
new Chart(document.getElementById('planChart'), {
    type: 'doughnut',
    data: {
        labels: planLabels.length ? planLabels : ['No Data'],
        datasets: [{
            data: planData.length ? planData : [1],
            backgroundColor: ['#4A90E2','#50E3C2','#F5A623','#7ED321','#BD10E0'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

// Claims by Status Doughnut
const claimStatusLabels = <?= json_encode($claim_status_labels) ?>;
const claimStatusData   = <?= json_encode($claim_status_data) ?>;
const statusColors = {
    'AI Analyzed':  '#F5A623',
    'Under Review': '#f0ad4e',
    'Approved':     '#20c997',
    'Rejected':     '#dc3545',
    'Payout Sent':  '#4A90E2',
    'Completed':    '#7ED321',
    'Pending':      '#6c757d'
};
const claimColors = claimStatusLabels.map(l => statusColors[l] || '#aaa');

new Chart(document.getElementById('claimStatusChart'), {
    type: 'doughnut',
    data: {
        labels: claimStatusLabels.length ? claimStatusLabels : ['No Data'],
        datasets: [{
            data: claimStatusData.length ? claimStatusData : [1],
            backgroundColor: claimStatusLabels.length ? claimColors : ['#eee'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

<?php include("./include/footer.php"); ?>

</body>
</html>
