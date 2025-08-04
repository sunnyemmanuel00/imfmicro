<?php require_once('config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('inc/header.php') ?>
<body>
<?php require_once('inc/topBarNav.php') ?>

<header class="bg-dark py-5" id="main-header">
    <div class="container px-4 px-lg-5 my-5">
        <div class="text-center text-white">
            <h1 class="display-4 fw-bolder">About <?php echo $_settings->info('name') ?></h1>
        </div>
    </div>
</header>
<section class="py-5">
    <div class="container px-4 px-lg-5">
        <div class="row gx-4 gx-lg-5 align-items-center">
            <div class="col-lg-6">
             <img class="img-fluid rounded mb-4 mb-lg-0" src="<?php echo base_url ?>uploads/history.jpg" alt="IMF Micro Finance Bank History" />
            </div>
            <div class="col-lg-6">
                <h2 class="fw-bolder">Our Journey Since 2009</h2>
                <p class="lead">IMF Micro Finance Bank was established in 2009 with a clear vision: to empower individuals and small businesses through accessible and reliable financial services. From our humble beginnings, we have grown steadily, driven by a commitment to fostering economic growth and financial inclusion.</p>
                <p>Our head office is proudly located in the United Kingdom, serving as the central hub for our operations and strategic development. Over the years, we've expanded our reach and refined our offerings, always keeping our clients' needs at the forefront.</p>
                <p>We believe in building lasting relationships based on trust, transparency, and mutual respect. Our journey is defined by the success stories of our clients, and we are dedicated to continuing that legacy for many years to come.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container px-4 px-lg-5">
        <h2 class="fw-bolder text-center mb-4">Our Mission & Values</h2>
        <div class="row gx-4 gx-lg-5 justify-content-center">
            <div class="col-lg-10">
                <p class="lead text-center">Our mission is to provide innovative and responsible financial solutions that contribute to the prosperity of our clients and the communities we serve. We are guided by a set of core values:</p>
                <div class="row mt-4">
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 text-center p-3">
                            <div class="card-body">
                                <h5 class="fw-bolder">Integrity</h5>
                                <p class="card-text">Upholding the highest ethical standards in all our dealings.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 text-center p-3">
                            <div class="card-body">
                                <h5 class="fw-bolder">Customer Focus</h5>
                                <p class="card-text">Prioritizing the needs and satisfaction of our clients above all else.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 text-center p-3">
                            <div class="card-body">
                                <h5 class="fw-bolder">Innovation</h5>
                                <p class="card-text">Continuously seeking new and better ways to serve our customers.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container px-4 px-lg-5">
        <h2 class="fw-bolder text-center mb-4">Why Choose IMF Micro Finance Bank?</h2>
        <div class="row gx-4 gx-lg-5 justify-content-center">
            <div class="col-lg-8 text-center">
                <p class="lead">We offer a unique blend of traditional banking security with modern, accessible financial tools. Our dedicated team is committed to understanding your unique financial landscape and providing tailored advice and solutions.</p>
                <p>From secure online banking to personalized loan services and expert investment guidance, we are your trusted partner on your financial journey. Join the growing family of satisfied clients who have chosen IMF Micro Finance Bank for their financial needs.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once('inc/footer.php') ?>
</body>
</html>