<?php
include('./include/connection.php');
include('./include/navbar.php');
include('./include/carasol.php');
?>

<!-- About Start -->
<div id="about" class="container-fluid py-5">
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

<!-- Team Start -->
<div id="team"></div>
<style>
    .team-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transition: transform 0.3s, box-shadow 0.3s;
        padding: 2rem 1.5rem;
        text-align: center;
    }
    .team-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.14);
    }
    .team-img-wrapper {
        position: relative;
        display: inline-block;
    }
    .team-img-wrapper img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #28a745;
        transition: filter 0.3s;
    }
    .team-img-wrapper:hover img {
        filter: brightness(0.55);
    }
    .team-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .team-img-wrapper:hover .team-overlay {
        opacity: 1;
    }
    .team-overlay a {
        color: #fff;
        font-size: 22px;
        line-height: 1;
        transition: color 0.2s, transform 0.2s;
    }
    .team-overlay a:hover {
        color: #ffc107;
        transform: scale(1.2);
    }
    .team-name {
        font-size: 1.15rem;
        font-weight: 700;
        margin-bottom: 4px;
        color: #1a1a1a;
    }
    .team-role {
        font-size: 0.9rem;
        color: #28a745;
        font-weight: 600;
    }
</style>
<div class="container-fluid py-5">
    <div class="container">
        <div class="text-center mx-auto wow fadeIn" data-wow-delay="0.1s" style="max-width: 500px;">
            <p class="section-title bg-white text-center text-primary px-3">Our Team</p>
            <h1 class="display-6 mb-4">Meet Our Dedicated Team Members</h1>
        </div>
        <div class="row g-4 justify-content-center">

            <div class="col-md-6 col-lg-4 wow fadeIn" data-wow-delay="0.1s">
                <div class="team-card">
                    <div class="team-img-wrapper mb-3">
                        <img src="../profile_user.jpg" alt="Dr. Syed Mushhad Mustuzhar Gilani">
                        <div class="team-overlay">
                            <a href="https://www.linkedin.com/in/mushhad-gilani/" target="_blank" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#!" target="_blank" title="GitHub"><i class="fab fa-github"></i></a>
                            <a href="mailto:#!" title="Email"><i class="fa fa-envelope"></i></a>
                        </div>
                    </div>
                    <div class="team-name">Dr. Mushhad Mustuzhar Gilani</div>
                    <div class="team-role">Supervisor</div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4 wow fadeIn" data-wow-delay="0.3s">
                <div class="team-card">
                    <div class="team-img-wrapper mb-3">
                        <img src="../Izzah.jpeg" alt="Izzah Khursheed">
                        <div class="team-overlay">
                            <a href="https://www.linkedin.com/in/izzah-khursheed" target="_blank" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                            <a href="https://github.com/Izzah-Khursheed" target="_blank" title="GitHub"><i class="fab fa-github"></i></a>
                            <a href="mailto:izzahkhursheed.dev@gmail.com" title="Email"><i class="fa fa-envelope"></i></a>
                        </div>
                    </div>
                    <div class="team-name">Izzah Khursheed</div>
                    <div class="team-role">AI Engineer</div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4 wow fadeIn" data-wow-delay="0.5s">
                <div class="team-card">
                    <div class="team-img-wrapper mb-3">
                        <img src="../Ushba.jpeg" alt="Ushba Zahid">
                        <div class="team-overlay">
                            <a href="https://www.linkedin.com/in/dev-ushba-zahid/" target="_blank" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                            <a href="https://github.com/UshbaZahid" target="_blank" title="GitHub"><i class="fab fa-github"></i></a>
                            <a href="mailto:uzahid14902@gmail.com" title="Email"><i class="fa fa-envelope"></i></a>
                        </div>
                    </div>
                    <div class="team-name">Ushba Zahid</div>
                    <div class="team-role">Website Developer</div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- Team End -->



<?php
include('./include/footer.php');
?>