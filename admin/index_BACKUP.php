<?php require_once(__DIR__ . '/../config.php'); ?>
<?php require_once(__DIR__ . '/auth.php'); ?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
<?php require_once(__DIR__ . '/inc/header.php'); ?>
  <body class="sidebar-mini layout-fixed control-sidebar-slide-open layout-navbar-fixed sidebar-mini-md sidebar-mini-xs" style="height: auto;">
    <div class="wrapper">
     <?php require_once(__DIR__ . '/inc/topBarNav.php'); ?>
     <?php require_once(__DIR__ . '/inc/navigation.php'); ?>
              
     <?php 
        // This determines which page to show. It defaults to 'home' after login.
        $page = isset($_GET['page']) ? $_GET['page'] : 'home';  
     ?>
      <div class="content-wrapper pt-3" style="min-height: 567.854px;">
     
        <!-- Main content -->
        <section class="content  text-dark">
          <div class="container-fluid">
            <?php 
              // =========================== THE FIX ===========================
              // This router now includes the missing page for managing accounts.
              switch($page){
                case 'home':
                    include __DIR__ . '/home.php';
                    break;
                case 'accounts':
                    include __DIR__ . '/accounts/index.php';
                    break;
                case 'accounts/manage_account':
                    include __DIR__ . '/accounts/manage_account.php';
                    break;
                case 'transactions':
                    include __DIR__ . '/transactions/index.php';
                    break;
                case 'inquiries':
                    include __DIR__ . '/inquiries/index.php';
                    break;
                case 'user':
                    if(file_exists(__DIR__ . '/user/index.php')){
                        include __DIR__ . '/user/index.php';
                    } else {
                        include __DIR__ . '/404.html';
                    }
                    break;
                case 'system_info':
                    if(file_exists(__DIR__ . '/system_info/index.php')){
                        include __DIR__ . '/system_info/index.php';
                    } else {
                        include __DIR__ . '/404.html';
                    }
                    break;
                default:
                    include __DIR__ . '/404.html';
                    break;
              }
              // ========================= END OF FIX ==========================
            ?>
          </div>
        </section>
        </div>
      <?php require_once(__DIR__ . '/inc/footer.php'); ?>
      <div class="modal fade" id="confirm_modal" role='dialog'>
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Confirmation</h5></div>
            <div class="modal-body"><div id="delete_content"></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-primary" id='confirm' onclick="">Continue</button><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>