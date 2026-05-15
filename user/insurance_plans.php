<?php
include ('./include/connection.php');
include ('./include/navbar.php');
?>

    <!-- Page Header Start -->
    <div class="container-fluid page-header py-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container text-center py-4">
            <h1 class="display-3 animated slideInDown">All Insurance Plans</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                <ol class="breadcrumb justify-content-center mb-0">
                    <li class="breadcrumb-item"><a href="#!">Home</a></li>
                    <li class="breadcrumb-item"><a href="#!">Pages</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Insurance Plan</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header End -->
    <!-- Insurance Plans Start -->
    <div class="container-fluid py-5">
        <div class="container">
            <div class="text-center mx-auto wow fadeIn" data-wow-delay="0.1s" style="max-width: 500px;">
                <p class="section-title bg-white text-center text-primary px-3">Insurance Plans</p>
                <h1 class="display-6 mb-4">Our Policy Plans</h1>
            </div>

            <div class="row g-4">

            <?php
            $sql = "SELECT * FROM insurance_plan";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
            ?>

                <div class="col-md-6 col-lg-4 wow fadeIn" data-wow-delay="0.1s">
                    <div class="donation-item d-flex h-100 p-4">

                        <div class="donation-detail w-100">

                            <div class="position-relative mb-4">
                                <img class="img-fluid w-100" src="../download (1).jfif" alt="Insurance Plan">

                                <span class="btn btn-sm btn-secondary px-3 position-absolute top-0 end-0">
                                    <?php echo $row['coverage_type']; ?>
                                </span>
                            </div>

                            <h4 class="mb-2">
                                <?php echo $row['plan_name']; ?>
                            </h4>

                            <p class="small text-muted mb-2">
                                <?php echo $row['description']; ?>
                            </p>

                            <ul class="list-unstyled small mb-3">
                                <li><strong>Applicable Crops:</strong> <?php echo $row['applicable_crops']; ?></li>
                                <li><strong>Premium Rate:</strong> <?php echo $row['base_premium_rate']; ?>%</li>
                                <li><strong>Coverage Level:</strong> <?php echo $row['coverage_level']; ?>%</li>
                                <li><strong>Deductible:</strong> <?php echo $row['deductible_rate']; ?>%</li>
                                <li><strong>Unit Structure:</strong> <?php echo $row['unit_structure']; ?></li>
                            </ul>

                            <a href="apply_plan.php?plan_id=<?php echo $row['plan_id']; ?>" 
                            class="btn btn-primary w-100 py-2">
                            <i class="fa fa-plus me-2"></i>Apply Now
                            </a>

                        </div>

                    </div>
                </div>

            <?php
                }
            } else {
                echo "<div class='text-center text-danger'>No Insurance Plans Available</div>";
            }
            ?>

            </div>

        <!-- Parameter Guide Start -->
        <div class="mt-5 pt-4 border-top wow fadeIn" data-wow-delay="0.1s">
            <div class="text-center mx-auto mb-4" style="max-width: 600px;">
                <p class="section-title bg-white text-center text-primary px-3">Plan Guide</p>
                <h2 class="display-6 mb-2">What Do These Terms Mean?</h2>
                <p class="text-muted">Before selecting a plan, understand what each parameter means for your farm.</p>
            </div>

            <div class="row g-4">

                <!-- Premium Rate -->
                <div class="col-md-6 col-lg-3">
                    <div class="h-100 p-4 rounded-3 border bg-white shadow-sm">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:42px;height:42px;flex-shrink:0;">
                                <i class="fa fa-percent"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Premium Rate</h5>
                        </div>
                        <p class="text-muted small mb-3">This is the fee you pay to activate the insurance. It is a percentage of your total insured crop value.</p>
                        <ul class="list-unstyled small mb-0">
                            <li class="py-1 border-bottom"><span class="badge bg-success me-2">Basic</span> 5% — Low, affordable fee</li>
                            <li class="py-1 border-bottom"><span class="badge bg-warning text-dark me-2">Standard</span> 10% — Moderate fee</li>
                            <li class="py-1 pt-2"><span class="badge bg-danger me-2">Premium</span> 15% — Higher fee, max protection</li>
                        </ul>
                    </div>
                </div>

                <!-- Coverage Level -->
                <div class="col-md-6 col-lg-3">
                    <div class="h-100 p-4 rounded-3 border bg-white shadow-sm">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:42px;height:42px;flex-shrink:0;">
                                <i class="fa fa-shield-alt"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Coverage Level</h5>
                        </div>
                        <p class="text-muted small mb-3">This shows how much of your crop value is protected. Higher coverage means more compensation if your crops are damaged.</p>
                        <ul class="list-unstyled small mb-0">
                            <li class="py-1 border-bottom"><span class="badge bg-success me-2">Basic</span> 60% — Covers 60% of crop value</li>
                            <li class="py-1 border-bottom"><span class="badge bg-warning text-dark me-2">Standard</span> 75% — Covers 75% of crop value</li>
                            <li class="py-1 pt-2"><span class="badge bg-danger me-2">Premium</span> 90% — Covers 90% of crop value</li>
                        </ul>
                    </div>
                </div>

                <!-- Deductible -->
                <div class="col-md-6 col-lg-3">
                    <div class="h-100 p-4 rounded-3 border bg-white shadow-sm">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center me-3" style="width:42px;height:42px;flex-shrink:0;">
                                <i class="fa fa-hand-holding-usd"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Deductible</h5>
                        </div>
                        <p class="text-muted small mb-3">This is the portion of loss you bear yourself. The lower this is, the more compensation you receive from the insurance.</p>
                        <ul class="list-unstyled small mb-0">
                            <li class="py-1 border-bottom"><span class="badge bg-success me-2">Basic</span> 40% — Farmer bears more loss</li>
                            <li class="py-1 border-bottom"><span class="badge bg-warning text-dark me-2">Standard</span> 25% — Balanced compensation</li>
                            <li class="py-1 pt-2"><span class="badge bg-danger me-2">Premium</span> 10% — Farmer bears very little</li>
                        </ul>
                    </div>
                </div>

                <!-- Unit Structure -->
                <div class="col-md-6 col-lg-3">
                    <div class="h-100 p-4 rounded-3 border bg-white shadow-sm">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:42px;height:42px;flex-shrink:0;">
                                <i class="fa fa-map-marked-alt"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Unit Structure</h5>
                        </div>
                        <p class="text-muted small mb-3">This defines how broadly the insurance covers your farming land. Larger units mean wider protection across your fields.</p>
                        <ul class="list-unstyled small mb-0">
                            <li class="py-1 border-bottom"><span class="badge bg-success me-2">Basic</span> Basic Unit — Small farm, basic cover</li>
                            <li class="py-1 border-bottom"><span class="badge bg-warning text-dark me-2">Standard</span> Optional Unit — Flexible, selected fields</li>
                            <li class="py-1 pt-2"><span class="badge bg-danger me-2">Premium</span> Enterprise Unit — Large farm, full coverage</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
        <!-- Parameter Guide End -->

        </div>
    </div>
    <!-- Insurance Plans End -->
   

<?php 
include ('./include/footer.php');
?>