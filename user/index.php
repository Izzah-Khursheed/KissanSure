<?php
include('./include/connection.php');
include('./include/navbar.php');
include('./include/carasol.php');
?>

<!-- About Start -->
<div class="container-fluid py-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 wow fadeIn" data-wow-delay="0.2s">
                <div class="about-img">
                    <img class="img-fluid w-100" src="../3.jfif" alt="Image">
                </div>
            </div>
            <div class="col-lg-6">
                <p class="section-title bg-white text-start text-primary pe-3">About Us</p>
                <h1 class="display-6 mb-4 wow fadeIn" data-wow-delay="0.2s">Protecting Your Harvest, Securing Your Future</h1>
                <p class="mb-4 wow fadeIn" data-wow-delay="0.3s">Our platform is dedicated to empowering the backbone of our economy, the farmers. We aim to bridge the gap between traditional agriculture and modern financial security through digital innovation.</p>

                <div class="row g-4 pt-2">
                    <div class="col-sm-6 wow fadeIn" data-wow-delay="0.4s">
                        <div class="h-100">
                            <h3>Our Mission</h3>
                            <p>To provide a seamless, transparent, and accessible insurance ecosystem that shields farmers against climate risks and crop failures.</p>

                            <p class="text-dark"><i class="fa fa-check text-primary me-2"></i>Secure Financial Stability</p>
                            <p class="text-dark"><i class="fa fa-check text-primary me-2"></i>Fast & Transparent Claims</p>
                            <p class="text-dark mb-0"><i class="fa fa-check text-primary me-2"></i>Data-Driven Protection</p>
                        </div>
                    </div>

                    <div class="col-sm-6 wow fadeIn" data-wow-delay="0.5s">
                        <div class="h-100 bg-primary p-4 text-center">
                            <p class="fs-5 text-white">Ensure your peace of mind by exploring our customized crop insurance plans today.</p>
                            <a class="btn btn-secondary py-2 px-4" href="insurance_plans.php">View Insurance Plans</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- About End -->

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
    </div>
</div>
<!-- Insurance Plans End -->

<!-- Service Start -->
<!-- <div class="container-fluid py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-md-12 col-lg-4 col-xl-3 wow fadeIn" data-wow-delay="0.1s">
                <div class="service-title">
                    <h1 class="display-6 mb-4">What We Do for Those in Need.</h1>
                    <p class="fs-5 mb-0">We work to bring smiles, hope, and a brighter future to those in need.</p>
                </div>
            </div>
            <div class="col-md-12 col-lg-8 col-xl-9">
                <div class="row g-5">
                    <div class="col-sm-6 col-md-4 wow fadeIn" data-wow-delay="0.1s">
                        <div class="service-item h-100">
                            <div class="btn-square bg-light mb-4">
                                <i class="fa fa-droplet fa-2x text-secondary"></i>
                            </div>
                            <h3>Pure Water</h3>
                            <p class="mb-2">We’re creating programs that address urgent needs while fostering
                                long-term solutions for sustainable change.</p>
                            <a href="#!">Read More</a>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 wow fadeIn" data-wow-delay="0.3s">
                        <div class="service-item h-100">
                            <div class="btn-square bg-light mb-4">
                                <i class="fa fa-hospital fa-2x text-secondary"></i>
                            </div>
                            <h3>Health Care</h3>
                            <p class="mb-2">We’re creating programs that address urgent needs while fostering
                                long-term solutions for sustainable change.</p>
                            <a href="#!">Read More</a>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 wow fadeIn" data-wow-delay="0.5s">
                        <div class="service-item h-100">
                            <div class="btn-square bg-light mb-4">
                                <i class="fa fa-hands-holding-child fa-2x text-secondary"></i>
                            </div>
                            <h3>Social Care</h3>
                            <p class="mb-2">We’re creating programs that address urgent needs while fostering
                                long-term solutions for sustainable change.</p>
                            <a href="#!">Read More</a>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 wow fadeIn" data-wow-delay="0.1s">
                        <div class="service-item h-100">
                            <div class="btn-square bg-light mb-4">
                                <i class="fa fa-bowl-food fa-2x text-secondary"></i>
                            </div>
                            <h3>Healthy Food</h3>
                            <p class="mb-2">We’re creating programs that address urgent needs while fostering
                                long-term solutions for sustainable change.</p>
                            <a href="#!">Read More</a>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 wow fadeIn" data-wow-delay="0.3s">
                        <div class="service-item h-100">
                            <div class="btn-square bg-light mb-4">
                                <i class="fa fa-school-flag fa-2x text-secondary"></i>
                            </div>
                            <h3>Primary Education</h3>
                            <p class="mb-2">We’re creating programs that address urgent needs while fostering
                                long-term solutions for sustainable change.</p>
                            <a href="#!">Read More</a>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4 wow fadeIn" data-wow-delay="0.5s">
                        <div class="service-item h-100">
                            <div class="btn-square bg-light mb-4">
                                <i class="fa fa-home fa-2x text-secondary"></i>
                            </div>
                            <h3>Residence Facilities</h3>
                            <p class="mb-2">We’re creating programs that address urgent needs while fostering
                                long-term solutions for sustainable change.</p>
                            <a href="#!">Read More</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> -->
<!-- Service End -->

<!-- Team Start -->
<div class="container-fluid py-5">
    <div class="container">
        <div class="text-center mx-auto wow fadeIn" data-wow-delay="0.1s" style="max-width: 500px;">
            <p class="section-title bg-white text-center text-primary px-3">Our Team</p>
            <h1 class="display-6 mb-4">Meet Our Dedicated Team Members</h1>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4 wow fadeIn" data-wow-delay="0.1s">
                <div class="team-item d-flex h-100 p-4">
                    <div class="team-detail pe-4">
                        <img class="img-fluid mb-4" src="../profile_user.jpg" alt="">
                        <h3>Sir Mushhadd Ghillani</h3>
                        <span>Supervisor</span>
                    </div>
                    <div class="team-social bg-light d-flex flex-column justify-content-center flex-shrink-0 p-4">
                        <a class="btn btn-square btn-primary my-2" href="#!"><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-square btn-primary my-2" href="#!"><i class="fab fa-x-twitter"></i></a>
                        <a class="btn btn-square btn-primary my-2" href="#!"><i class="fab fa-instagram"></i></a>
                        <a class="btn btn-square btn-primary my-2" href="#!"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 wow fadeIn" data-wow-delay="0.3s">
                <div class="team-item d-flex h-100 p-4">
                    <div class="team-detail pe-4">
                        <img class="img-fluid mb-4" src="../profile_user.jpg" alt="">
                        <h3>Izzah Khursheed</h3>
                        <span>Project AI Specialist</span>
                    </div>
                    <div class="team-social bg-light d-flex flex-column justify-content-center flex-shrink-0 p-4">
                        <a class="btn btn-square btn-primary my-2" href="#!"><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-square btn-primary my-2" href="#!"><i class="fab fa-x-twitter"></i></a>
                        <a class="btn btn-square btn-primary my-2" href="#!"><i class="fab fa-instagram"></i></a>
                        <a class="btn btn-square btn-primary my-2" href="#!"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 wow fadeIn" data-wow-delay="0.5s">
                <div class="team-item d-flex h-100 p-4">
                    <div class="team-detail pe-4">
                        <img class="img-fluid mb-4" src="../profile_user.jpg" alt="">
                        <h3>Ushba Zahid</h3>
                        <span>Project Website Developer</span>
                    </div>
                    <div class="team-social bg-light d-flex flex-column justify-content-center flex-shrink-0 p-4">
                        <a class="btn btn-square btn-primary my-2" href="#!"><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-square btn-primary my-2" href="#!"><i class="fab fa-x-twitter"></i></a>
                        <a class="btn btn-square btn-primary my-2" href="#!"><i class="fab fa-instagram"></i></a>
                        <a class="btn btn-square btn-primary my-2" href="#!"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Team End -->



<?php
include('./include/footer.php');
?>