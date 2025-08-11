<?php 
require_once('../config.php'); 
require_once('../classes/DBConnection.php'); // Include the DBConnection class

// This logic correctly handles AJAX requests for modals. IT IS CORRECT.
if (isset($_GET['modal']) && $_GET['modal'] === 'true') {
    $page = isset($_GET['page']) ? $_GET['page'] : '';
    if (!empty($page) && file_exists($page . '.php')) {
        // Include necessary classes for the form to function
        require_once(__DIR__ . '/../classes/Master.php');
        $Master = new Master();
        
        // Include only the requested page content and stop execution
        include $page . '.php';
        exit; // This is crucial. It stops the script from rendering the rest of the page.
    }
}

// Security Gatekeeper to ensure user is logged in.
if (empty($_settings->userdata('account_id'))) {
    header('Location: '.base_url.'index.php');
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once('../classes/Master.php');
$Master = new Master();

$account_id = $_settings->userdata('account_id');
global $conn; 
$stmt_accounts = $conn->prepare('SELECT balance, status, account_number FROM accounts WHERE id = ?');
if (!$stmt_accounts) {
    error_log("PostgreSQL prepare failed: " . implode(" ", $conn->errorInfo()));
}
$stmt_accounts->execute([$account_id]);
$row_accounts = $stmt_accounts->fetch(PDO::FETCH_ASSOC);

if ($row_accounts) {
    $_settings->set_userdata('balance', $row_accounts['balance']);
    $_settings->set_userdata('status', $row_accounts['status']);
    $_settings->set_userdata('account_number', $row_accounts['account_number']);
}
$stmt_accounts->closeCursor();
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
        
            <section class="content text-dark">
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
                <div class="modal-dialog modal-full-height modal-md" role="document">
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