<?php
// =================== [ACTIVE] FINAL, ACTION-BASED UNIVERSAL ROUTER ===================
// This is the definitive router. It now includes the Composer autoloader, fixing the
// fatal crash in Master.php and other scripts that use external libraries like Firebase.

if (isset($_GET['f'])) {
    
    // Get the requested action, e.g., 'deposit_from_linked_account'
    $action = $_GET['f'];

    // This is a map that tells the router which file contains which actions.
    $action_map = [
        'Master.php' => [
            'login', 'save_account', 'deposit', 'withdraw', 
            'get_internal_account_details_for_transfer', 'transfer_internal', 
            'transfer_external', 'get_linked_accounts', 'save_linked_account', 
            'delete_linked_account', 'get_account_details_by_number', 
            'deposit_from_linked_account', 'transfer_to_linked_account', 
            'approve_transaction', 'decline_transaction', 'save_announcement', 
            'delete_account', 'delete_announcement', 'activate_account', 
            'admin_adjust_balance'
        ],
        'Account.php' => [
            'save_account', 'get_account_details_for_login', 'update_first_login_status'
        ],
        'Login.php' => [
            'login', 'clogin', 'logout', 'clogout', 
            'firebase_login_session', 'update_first_login_status'
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

    // If we found a file that contains the action, run it.
    if ($file_to_run) {
        
        // --- THE DEFINITIVE FIX ---
        // Load the Composer autoloader FIRST. This makes all libraries (like Firebase)
        // available to every API script and prevents the fatal crash.
        require_once('vendor/autoload.php');

        // Load the main configuration file SECOND.
        require_once('config.php');
        
        $file_path = __DIR__ . '/classes/' . $file_to_run;
        if (file_exists($file_path)) {
            require_once($file_path);
            exit;
        }
    }
}
// =====================================================================================

// NOTE: This config call is still needed for regular page loads.
require_once('config.php');

// =================== FINAL, RESTRUCTURED PAGE ROUTER ===================
// The entire routing logic now runs BEFORE any HTML is outputted.

// 1. Determine the requested page
$page = isset($_GET['p']) ? $_GET['p'] : null;
if (empty($page)) {
    $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    // Check for sub-directories like 'client' or 'admin'
    if (is_dir($path) && file_exists($path . '/index.php')) {
        $page = $path;
    }
}
if (empty($page)) {
    $page = 'home';
}

// 2. Decide how to handle the page
if (is_dir($page)) {
    // --- THIS IS THE FIX FOR SUB-APPLICATIONS ---
    // If the page is a directory (e.g., 'client'), change into that directory,
    // load its index.php, and then exit completely.
    // This prevents the main public template below from ever loading.
    chdir($page);
    require_once('index.php');
    exit;
}

// 3. If we are still here, it means we are loading a regular public page.
// Now, and only now, do we start printing the public website template.
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('inc/header.php') ?>
<body>
    <?php require_once('inc/topBarNav.php') ?>
    <?php 
        // Load the specific content page (e.g., home.php, internet_banking.php)
        if (file_exists($page . ".php")) {
            include $page . '.php';
        } else {
            include '404.html';
        }
    ?>
    <?php require_once('inc/footer.php') ?>
    
    <!-- Your modals and global scripts remain here -->
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
    <script>
        start_loader();
        $(function(){
            end_loader()
        })
    </script>
</body>
</html>
