<?php
// =================== FINAL ADMIN PAGE FIX ===================
// This block is added to the top to handle the admin login page request first.
if (isset($_GET['p']) && $_GET['p'] === 'admin_login') {
 // It loads the configuration and then the admin login page directly.
 require_once(__DIR__ . '/config.php');
 require_once(__DIR__ . '/admin/login.php');
 // It stops immediately so the public site does not load by mistake.
 exit();
}

// This second block handles the redirect to the admin dashboard AFTER a successful login.
$request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$path_parts = explode('/', $request_uri);
if (end($path_parts) === 'admin') {
 require_once(__DIR__ . '/config.php');
 require_once(__DIR__ . '/admin/index.php');
 exit();
}
// ========================= END OF FIX ==========================


// =================== YOUR WORKING API ROUTER (MODIFIED) ===================
// This is your original code that handles deposit and other functions. 
if (isset($_GET['f'])) {
 
 // Get the requested action, e.g., 'deposit_from_linked_account'
 $action = $_GET['f'];

 // This is a map that tells the router which file contains which actions.
 $action_map = [
  'Master.php' => [
   // 'login', // <--- MODIFIED: This redundant action is removed from here to prevent conflict.
   'save_account', 'deposit', 'withdraw', 
   'get_internal_account_details_for_transfer', 'transfer_internal', 
   'transfer_external', 'get_linked_accounts', 'save_linked_account', 
   'delete_linked_account', 'get_account_details_by_number', 
   'deposit_from_linked_account', 'transfer_to_linked_account', 
   'approve_transaction', 'decline_transaction', 'save_announcement', 
   'delete_account', 'delete_announcement', 'activate_account', 
   'admin_adjust_balance'
  ],
  'Account.php' => [
   'create_account', // <-- RESTORED: This action must be here.
   'get_account_details_for_login',
   // 'update_first_login_status' // <--- MODIFIED: This redundant action is removed from here.
  ],
  'Login.php' => [
   'login', // <-- This is the correct location for the login action.
   'clogin', 'logout', 'clogout', 
   'firebase_login_session', 'update_first_login_status' // <--- This is the correct location for the action.
  ],
  'Users.php' => [
   'save', 'save_client', 'delete_users'
  ],
  'Application.php' => [
   'submit_inquiry'
  ],
  'SystemSettings.php' => [
   'update_settings'
  ]
 ];

 // Find the correct file for the requested action
 $file_to_run = null;
 foreach ($action_map as $file => $actions) {
  if (in_array($action, $actions)) {
   $file_to_run = $file;
   break;
  }
 }

 if ($file_to_run) {
  // Load the Composer autoloader FIRST.
  require_once(__DIR__ . '/vendor/autoload.php'); // Use __DIR__ for absolute path

  // Load the main configuration file SECOND.
  require_once('config.php'); // config.php will load initialize.php and SystemSettings.php globals.
  
  $file_path = __DIR__ . '/classes/' . $file_to_run;

  if ($file_to_run === 'Master.php') { // Target Master.php specifically
   if (file_exists($file_path)) {
    require_once($file_path); // Load the class definition
    $className = str_replace('.php', '', $file_to_run); // Derive class name (Master)

    if (class_exists($className) && method_exists($className, $action)) {
     try {
      $instance = new $className();
      echo $instance->$action();
      exit; // Exit after successful execution
     } catch (Throwable $e) {
      error_log("API Router Error: Exception in {$className}::{$action}() - " . $e->getMessage() . " in file " . $e->getFile() . " on line " . $e->getLine());
      header('Content-Type: application/json');
      echo json_encode(['status' => 'error', 'msg' => 'Server script error in Master.php: ' . $e->getMessage()]);
      exit;
     }
    }
   }
  } else {
   // For all other classes (Login.php, Account.php, etc.)
   if (file_exists($file_path)) {
    require_once($file_path);
    exit;
   }
  }
 }
}
// =====================================================================================


// Your original, working public page loader is unchanged below.
require_once('config.php');

// =================== FINAL, RESTRUCTURED PAGE ROUTER ===================
// 1. Determine the requested page
$page = isset($_GET['p']) ? $_GET['p'] : null;
if (empty($page)) {
  $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
  if (is_dir($path) && file_exists($path . '/index.php')) {
    $page = $path;
  }
}
if (empty($page)) {
  $page = 'home';
}

// 2. Decide how to handle the page
if (is_dir($page)) {
  chdir($page);
  require_once('index.php');
  exit;
}

// 3. If we are still here, it means we are loading a regular public page.
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('inc/header.php') ?>
<body>
  <script>
    // Define _base_url_ globally for all public pages
    window._base_url_ = <?php echo json_encode(base_url); ?>;

    // Initialize Firebase. This block should ensure `window.auth` is set.
    try {
      const firebaseConfig = {
       apiKey: "AIzaSyAkHD7A-HnZYakoiV5YxIVJamEwMe2r86w",
       authDomain: "usbmicro-ca116.firebaseapp.com",
       projectId: "usbmicro-ca116",
       storageBucket: "usbmicro-ca116.firebasestorage.app",
       messagingSenderId: "774331717251",
       appId: "1:774331717251:web:986a6350209aea275bedb6"
      };
      
      // Initialize app only if it hasn't been already
      if (typeof firebase !== 'undefined' && firebase.apps.length === 0) {
        firebase.initializeApp(firebaseConfig);
        window.auth = firebase.auth(); // Expose the auth instance globally
        console.log("Firebase initialized globally (non-module).");
      } else if (typeof firebase !== 'undefined' && firebase.apps.length > 0) {
        window.auth = firebase.auth(); // Expose the auth instance if already initialized
        console.log("Firebase auth instance exposed globally.");
      } else {
        console.warn("Firebase global object 'firebase' not found. Ensure SDK is loaded correctly for global access.");
      }
      
    } catch (e) {
      console.error("Firebase global init script failed:", e);
    }

    // Define global loader stubs if script.js (where they are defined) loads later
    window.start_loader = window.start_loader || function() { console.log('start_loader called'); /* Actual implementation in script.js */ };
    window.end_loader = window.end_loader || function() { console.log('end_loader called'); /* Actual implementation in script.js */ };
    
    start_loader(); // Start loader immediately on page load
  </script>

  <?php require_once('inc/topBarNav.php') ?>
  <?php 
    // Load the specific content page (e.g., home.php, internet_banking.php)
    // These files should NOT contain their own <header> or full HTML structure.
    // MODIFIED: This now simply includes the home.php partial without a redundant header.
    if ($page === 'home'): ?>
      <?php include 'home.php'; ?>
    <?php elseif ($page === 'internet_banking'): ?>
      <header class="py-5" id="main-header">
        <div class="container px-4 px-lg-5 my-5">
          <div class="text-center text-white">
            <h1 class="display-4 fw-bolder">Internet Banking</h1>
          </div>
        </div>
      </header>
      <?php include $page . '.php'; ?>
    <?php elseif ($page === 'open_account'): ?>
      <?php // MODIFIED: Removed redundant <header> tag for this case. ?>
      <?php include $page . '.php'; ?>
    <?php elseif ($page === 'reset_admin_pass'): // ADDED: Direct page route for the reset script. ?>
      <?php include 'reset_admin_pass.php'; ?>
    <?php elseif (file_exists($page . ".php")): ?>
      <?php include $page . '.php'; ?>
    <?php else: ?>
      <?php include '404.html'; ?>
    <?php endif; ?>

  <?php require_once('inc/footer.php') ?>
  
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
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" id='submit' onclick="$('#uni_modal form').submit()">Save</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
  <script>
    // Call end_loader when DOM is ready
    $(function(){
      end_loader(); 
    })
  </script>
</body>
</html>