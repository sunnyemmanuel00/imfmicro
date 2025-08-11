<?php require_once(__DIR__ . '/../config.php'); ?>
<?php require_once(__DIR__ . '/auth.php'); ?>
<?php 
// ====================== START: CRITICAL ROUTER FIX ======================
// This block ensures that only the requested page content is returned for AJAX calls.
// It prevents the entire HTML template from being loaded into the modal.
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $page = isset($_GET['page']) ? $_GET['page'] : 'home';
    switch($page){
        case 'accounts/manage_account':
        case 'accounts/manage_balance':
        case 'accounts/view_transactions':
        case 'inquiries/view_inquiry':
        // ADDED: Cases for transactions modals to ensure they load correctly.
        case 'transaction/view_transaction':
        case 'transaction/manage_transaction':
            // Directly include the modal content page without the surrounding template.
            include __DIR__ . '/' . $page . '.php';
            break;
        default:
            // For other AJAX calls, handle as needed or return an error.
            http_response_code(404);
            echo "Page Not Found";
            break;
    }
    // Stop script execution here to prevent loading the full page template.
    exit;
}
// ====================== END: CRITICAL ROUTER FIX ======================
?>
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
  
    <section class="content text-dark">
   <div class="container-fluid">
   <?php 
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
     case 'accounts/manage_balance': // Case for balance modal
      include __DIR__ . '/accounts/manage_balance.php';
      break;
     case 'accounts/view_transactions': // Case for transactions modal
      include __DIR__ . '/accounts/view_transactions.php';
      break;
     // CORRECTED: The new case to handle the main transactions page.
     // It uses the correct directory name 'transaction' instead of 'transactions'.
     case 'transaction':
      include __DIR__ . '/transaction/index.php';
      break;
     case 'inquiries':
      include __DIR__ . '/inquiries/index.php';
      break;
     case 'inquiries/view_inquiry': // Case for inquiries modal
      include __DIR__ . '/inquiries/view_inquiry.php';
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
    ?>
   </div>
    </section>
    </div>
   <?php require_once(__DIR__ . '/inc/footer.php'); ?>
            <div class="modal fade" id="uni_modal" role='dialog'>
    <div class="modal-dialog rounded-0 modal-md modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"></h5>
        </div>
        <div class="modal-body">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" id='submit' onclick="$('#uni_modal form').submit()">Save</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
   </div>
   <div class="modal fade" id="uni_modal_right" role='dialog'>
    <div class="modal-dialog rounded-0 modal-full-height modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span class="fa fa-arrow-right"></span>
        </div>
        <div class="modal-body">
        </div>
      </div>
    </div>
   </div>
   <div class="modal fade" id="viewer_modal" role='dialog'>
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <button type="button" class="btn-close" data-dismiss="modal"><span class="fa fa-times"></span></button>
        <img src="" alt="">
      </div>
    </div>
   </div>
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