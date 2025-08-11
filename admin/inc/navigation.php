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
<aside class="main-sidebar sidebar-dark-primary elevation-4 sidebar-no-expand">
  <a href="<?php echo base_url ?>admin" class="brand-link bg-primary text-sm">
  <img src="<?php echo validate_image($_settings->info('logo'))?>" alt="Store Logo" class="brand-image img-circle elevation-3" style="opacity: .8;width: 2.5rem;height: 2.5rem;max-height: unset">
  <span class="brand-text font-weight-light"><?php echo $_settings->info('short_name') ?></span>
  </a>
  <div class="sidebar os-host os-theme-light os-host-overflow os-host-overflow-y os-host-resize-disabled os-host-transition os-host-scrollbar-horizontal-hidden">
  <div class="os-resize-observer-host observed">
   <div class="os-resize-observer" style="left: 0px; right: auto;"></div>
  </div>
  <div class="os-size-auto-observer observed" style="height: calc(100% + 1px); float: left;">
   <div class="os-resize-observer"></div>
  </div>
  <div class="os-content-glue" style="margin: 0px -8px; width: 249px; height: 646px;"></div>
  <div class="os-padding">
   <div class="os-viewport os-viewport-native-scrollbars-invisible" style="overflow-y: scroll;">
   <div class="os-content" style="padding: 0px 8px; height: 100%; width: 100%;">
    <div class="clearfix"></div>
    <nav class="mt-4">
    <ul class="nav nav-pills nav-sidebar flex-column text-sm nav-compact nav-flat nav-child-indent nav-collapse-hide-child" data-widget="treeview" role="menu" data-accordion="false">
     <li class="nav-item dropdown">
           <a href="<?php echo base_url ?>admin/" class="nav-link nav-home">
      <i class="nav-icon fas fa-tachometer-alt"></i>
      <p>
       Dashboard
      </p>
     </a>
     </li> 
     <li class="nav-item">
     <a href="#" class="nav-link tree-item nav-accounts nav-transactions nav-manage_account">
      <i class="nav-icon fas fa-id-card"></i>
      <p>
       Account Management
      <i class="right fas fa-angle-left"></i>
      </p>
     </a>
     <ul class="nav nav-treeview" style="">
      <li class="nav-item">
             <a href="<?php echo base_url ?>admin/?page=accounts/manage_account" class="nav-link nav-manage_account">
       <i class="far fa-circle nav-icon"></i>
       <p>New Account</p>
      </a>
      </li>
      <li class="nav-item">
             <a href="<?php echo base_url ?>admin/?page=accounts" class="nav-link nav-index">
       <i class="far fa-circle nav-icon"></i>
       <p>Manage Account</p>
      </a>
      </li>
     </ul>
     </li>
     <li class="nav-item">
     <a href="<?php echo base_url ?>admin/?page=transaction" class="nav-link nav-transaction">
      <i class="nav-icon fas fa-th-list"></i>
      <p>
       Transactions
      </p>
     </a>
     </li>
     <li class="nav-item dropdown">
     <a href="<?php echo base_url ?>admin/?page=announcements" class="nav-link nav-announcements">
      <i class="nav-icon fas fa-bullhorn"></i>
      <p>
       Announcements
      </p>
     </a>
     </li>
     
     <li class="nav-item dropdown">
     <a href="<?php echo base_url ?>admin/?page=inquiries" class="nav-link nav-inquiries">
      <i class="nav-icon fas fa-envelope"></i>
      <p>
       Inquiries
      </p>
     </a>
     </li>

     <li class="nav-header">Maintenance</li>
     <li class="nav-item dropdown">
     <a href="<?php echo base_url ?>admin/?page=system_info" class="nav-link nav-system_info">
      <i class="nav-icon fas fa-cogs"></i>
      <p>
       Settings
      </p>
     </a>
     </li>
    </ul>
    </nav>
    </div>
   </div>
  </div>
  <div class="os-scrollbar os-scrollbar-horizontal os-scrollbar-unusable os-scrollbar-auto-hidden">
   <div class="os-scrollbar-track">
   <div class="os-scrollbar-handle" style="width: 100%; transform: translate(0px, 0px);"></div>
   </div>
   </div>
  <div class="os-scrollbar os-scrollbar-vertical os-scrollbar-auto-hidden">
   <div class="os-scrollbar-track">
   <div class="os-scrollbar-handle" style="height: 55.017%; transform: translate(0px, 0px);"></div>
   </div>
  </div>
  <div class="os-scrollbar-corner"></div>
  </div>
  </aside>
 <script>
 $(document).ready(function(){
  var page = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'home' ?>';
  var s = '<?php echo isset($_GET['s']) ? $_GET['s'] : '' ?>';
  page = page.replace(/\//g,'_');
  if($('.nav-link.nav-'+page).length > 0){
   $('.nav-link.nav-'+page).addClass('active')
  if($('.nav-link.nav-'+page).siblings('.nav-treeview').length > 0){
   $('.nav-link.nav-'+page).parent().addClass('menu-open')
  }
  if($('.nav-link.nav-'+page).parents('.nav-item.dropdown').length > 0){
   $('.nav-link.nav-'+page).parents('.nav-item.dropdown').addClass('menu-open')
  }

 }
 
 })
</script>