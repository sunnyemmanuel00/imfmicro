<?php 
require_once('../config.php'); 

// ======================================================
// SECURITY GATEKEEPER
// ======================================================

// 1. Check if a user account_id exists in the session. If not, it redirects to the homepage and stops the script.
if (empty($_settings->userdata('account_id'))) {
    header('Location: '.base_url.'index.php');
    exit;
}

// 2. This block tells the browser not to save a cached version of secure pages.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// --- End of Security Gatekeeper ---


// Now that we know the user is logged in, we prepare the necessary objects and session data.
require_once('../classes/Master.php');
$Master = new Master();

// Use the account_id from the session, which corresponds to the 'id' column in the accounts table
$account_id = $_settings->userdata('account_id');
global $conn; 

// This query securely fetches the latest balance and status for the logged-in user.
$stmt = $conn->prepare("SELECT balance, status FROM `accounts` WHERE id = ?");
$stmt->bind_param("i", $account_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0){
    $row = $result->fetch_assoc();
    $_settings->set_userdata('balance', $row['balance']);
    $_settings->set_userdata('status', $row['status']); 
}
?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
<?php require_once('inc/header.php') ?>
  <body class="layout-fixed layout-navbar-fixed" style="height: auto;">
      
    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
    
    <div class="wrapper">
     <?php require_once('inc/topBarNav.php') ?>
     <?php require_once('inc/navigation.php') ?>
           
     <?php $page = isset($_GET['page']) ? $_GET['page'] : 'home';  ?>
      <div class="content-wrapper pt-3" style="min-height: 567.854px;">
      
        <section class="content   text-dark">
          <div class="container-fluid">
            <?php 
              if(!file_exists($page.".php") && !is_dir($page)){
                  include '404.html';
              }else{
                if(is_dir($page))
                  include $page.'/index.php';
                else
                  include $page.'.php';

              }
            ?>
          </div>
        </section>
        <div class="modal fade" id="confirm_modal" role='dialog'>
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title">Confirmation</h5>
      </div>
      <div class="modal-body">
        <div id="delete_content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id='confirm' onclick="">Continue</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="uni_modal" role='dialog'>
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
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
    <div class="modal-dialog modal-full-height   modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span class="fa fa-arrow-right"></span>
        </button>
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
      </div>
      <?php require_once('inc/footer.php') ?>
  </body>
</html>