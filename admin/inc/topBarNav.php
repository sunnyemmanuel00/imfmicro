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
     <li class="nav-item">
     <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
     </li>
     <li class="nav-item d-none d-sm-inline-block">
      <a href="<?php echo base_url ?>" class="nav-link"><?php echo (!isMobileDevice()) ? $_settings->info('name'):$_settings->info('short_name'); ?> - Admin</a>
     </li>
    </ul>
    <ul class="navbar-nav ml-auto">
     <li class="nav-item">
      <div class="btn-group nav-link">
          <button type="button" class="btn btn-rounded badge badge-light dropdown-toggle dropdown-icon" data-toggle="dropdown">
          <!-- The following line has been commented out to remove the user image -->
          <!-- <span><img src="<?php echo validate_image($_settings->userdata('avatar')) ?>" class="img-circle elevation-2 user-img" alt="User Image"></span> -->
          <span class="ml-3"><?php echo ucwords($_settings->userdata('firstname').' '.$_settings->userdata('lastname')) ?></span>
          <span class="sr-only">Toggle Dropdown</span>
          </button>
          <div class="dropdown-menu" role="menu">
          <a class="dropdown-item" href="<?php echo base_url.'admin/?page=user' ?>"><span class="fa fa-user"></span> My Account</a>
          <div class="dropdown-divider"></div>
                     <a class="dropdown-item" href="<?php echo base_url.'?f=logout' ?>"><span class="fas fa-sign-out-alt"></span> Logout</a>
          </div>
        </div>
      </li>
      <li class="nav-item">
      
      </li>
    </ul>
   </nav>
