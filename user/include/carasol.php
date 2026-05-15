 <!-- Carousel Start -->
    <div class="container-fluid p-0 wow fadeIn" data-wow-delay="0.1s">
        <div class="owl-carousel header-carousel py-5">
            <div class="container py-5">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6">
                        <div class="carousel-text">
                            <h1 class="display-1 text-uppercase mb-3">Your Hard Work Deserves Reliable Protection</h1>
                            <p class="fs-5 mb-5">Climate change and unpredictable weather shouldn't dictate your success. Our platform provides data-driven insurance solutions to keep your farm thriving, no matter what the season brings.</p>
                            <div class="d-flex">
                                <?php if (!isset($_SESSION['farmer_id'])): ?>
                                <a class="btn btn-primary py-3 px-4 me-3" href="farmer_register.php">Register Farm</a>
                                <a class="btn btn-secondary py-3 px-4" href="login.php">Login</a>
                                <?php else: ?>
                                <a class="btn btn-primary py-3 px-4 me-3" href="apply_claim.php">Apply for Claim</a>
                                <a class="btn btn-secondary py-3 px-4" href="insurance_plans.php">View Plans</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="carousel-img">
                            <img class="w-100" src="img/download (2) (1).jpg" alt="Image">
                        </div>
                    </div>
                </div>
            </div>
            <div class="container py-5">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6">
                        <div class="carousel-text">
                            <h1 class="display-1 text-uppercase mb-3">Your Hard Work Deserves Reliable Protection</h1>
                            <p class="fs-5 mb-5">Climate change and unpredictable weather shouldn't dictate your success. Our platform provides data-driven insurance solutions to keep your farm thriving, no matter what the season brings.</p>
                            <div class="d-flex mt-4">
                                <?php if (!isset($_SESSION['farmer_id'])): ?>
                                <a class="btn btn-primary py-3 px-4 me-3" href="farmer_register.php">Register Farm</a>
                                <a class="btn btn-secondary py-3 px-4" href="login.php">Login</a>
                                <?php else: ?>
                                <a class="btn btn-primary py-3 px-4 me-3" href="apply_claim.php">Apply for Claim</a>
                                <a class="btn btn-secondary py-3 px-4" href="insurance_plans.php">View Plans</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="carousel-img">
                            <img class="w-100" src="img/Donna Jos Sia.jpg" alt="Image">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Carousel End -->