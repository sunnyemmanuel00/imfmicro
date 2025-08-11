<?php
// ===========================================================================
// Updated Client Top Navigation Bar - `client/inc/topBarNav.php`
//
// This file has been updated to correctly display the logged-in user's
// full name in the top right corner. The previous change failed because the
// session was not active. This version now starts the session at the
// beginning of the file to ensure the user data is accessible.
// ===========================================================================

// IMPORTANT: Start the session to ensure $_SESSION variables are available.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<style>
    .user-img{
        position: absolute;
        height: 27px;
        width: 27px;
        object-fit: cover;
        left: -7%;
        top: -12%;
    }
    .btn-rounded{
        border-radius: 50px;
    }
</style>
<nav class="main-header navbar navbar-expand navbar-dark border border-light border-top-0 border-left-0 border-right-0 navbar-light text-sm">
    <ul class="navbar-nav">
        <li class="nav-item d-none d-sm-inline-block">
            <span class="nav-link"><?php echo (!isMobileDevice()) ? $_settings->info('name'):$_settings->info('short_name'); ?></span>
        </li>
    </ul>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <div class="btn-group nav-link">
                <button type="button" class="btn btn-rounded badge badge-light dropdown-toggle dropdown-icon" data-toggle="dropdown">
                    <span><i class="img-circle elevation-2 user-img fa fa-user text-mute d-flex justify-content-center align-items-center"></i></span>
                    <span class="ml-3">
                        <?php
                            // Check if the 'userdata' session array and the 'firstname' and 'lastname' keys exist.
                            // If they do, display the user's name. Otherwise, display "Guest".
                            if(isset($_SESSION['userdata']['firstname']) && isset($_SESSION['userdata']['lastname'])) {
                                echo ucwords($_SESSION['userdata']['firstname'] . ' ' . $_SESSION['userdata']['lastname']);
                            } else {
                                echo "Guest";
                            }
                        ?>
                    </span>
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu" role="menu">
                    <a class="dropdown-item" href="<?php echo base_url.'client/?page=user' ?>"><span class="fa fa-user"></span> My Account</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo base_url.'?f=clogout' ?>"><span class="fas fa-sign-out-alt"></span> Logout</a>
                </div>
            </div>
        </li>
        <li class="nav-item">
            
        </li>
    </ul>
</nav>
