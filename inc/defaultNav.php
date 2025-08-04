<nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
    <div class="container">
        <a href="<?php echo base_url ?>" class="navbar-brand">
            <img src="<?php echo validate_image($_settings->info('logo'))?>" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light"><?php echo $_settings->info('short_name') ?></span>
        </a>

        <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse order-3" id="navbarCollapse">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="./" class="nav-link">Home</a>
                </li>
                <li class="nav-item">
                    <a href="./?p=about" class="nav-link">About</a>
                </li>
                <li class="nav-item">
                    <a href="./?p=contact_us" class="nav-link">Contact</a>
                </li>
                </ul>

            <form class="form-inline ml-0 ml-md-3" id="search-form-default-nav">
                <div class="input-group input-group-sm">
                    <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search" name="search">
                    <div class="input-group-append">
                        <button class="btn btn-navbar" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
            <?php if(!isset($_SESSION['userdata'])): ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo base_url ?>client/login.php">
                    Login
                </a>
            </li>
            <?php else: ?>
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false">
                    <span class="mr-2"><?php echo ucwords($_settings->userdata('firstname').' '.$_settings->userdata('lastname')) ?></span>
                    <img src="<?php echo validate_image($_settings->userdata('avatar')) ?>" class="img-circle elevation-2" alt="User Image" style="height: 2rem;width: 2rem;object-fit: cover;max-height: unset">
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <a href="<?php echo base_url ?>admin/?page=user" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> Account
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo base_url ?>classes/Login.php?f=logout" class="dropdown-item">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<script>
    $(function(){
        $('#search-form-default-nav').submit(function(e){
            e.preventDefault();
            var sTxt = $(this).find('[name="search"]').val();
            if(sTxt != '')
                location.href = './?p=products&search='+sTxt;
        });
    });
</script>