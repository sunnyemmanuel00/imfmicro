<?php
// Require Composer's autoloader for Firebase Admin SDK
require_once __DIR__ . '/../vendor/autoload.php'; // Adjust path if vendor is in a different location
require_once __DIR__ . '/../config.php'; // Ensure config.php is correctly loaded, relative to Login.php

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\InvalidToken;

class Login extends DBConnection {
    private $settings;
    private $firebaseAuth; // Property to hold Firebase Auth instance
    private $firebaseDatabase; // Property to hold Firebase Database instance (if needed for RTDB/Firestore later)


public function __construct(){
    global $_settings;
    $this->settings = $_settings;

    parent::__construct();
    ini_set('display_errors', 0); // Hide errors from users in production

    // Initialize Firebase Admin SDK with better error logging for App Engine
    try {
        $serviceAccountPath = __DIR__ . '/firebase-service-account.json';

        // Check if the service account file actually exists at the path
        if (!file_exists($serviceAccountPath)) {
            error_log("FATAL LOGIN ERROR: Firebase Service Account JSON file not found at: " . $serviceAccountPath);
            $this->firebaseAuth = null;
            return; // Stop execution if the file is missing
        }

        $factory = (new Factory)->withServiceAccount($serviceAccountPath);
        $this->firebaseAuth = $factory->createAuth();

    } catch (Throwable $e) {
        // Log the specific reason the SDK failed to initialize
        error_log("FATAL LOGIN ERROR: Firebase Admin SDK Initialization Error: " . $e->getMessage());
        $this->firebaseAuth = null;
    }
}
    public function __destruct(){
        parent::__destruct();
    }

    public function index(){
        echo "<h1>Access Denied</h1> <a href='".base_url."'>Go Back.</a>";
    }

    // Admin Login Function
    public function login(){
        extract($_POST);

        // Using prepared statement for admin login for security
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ? AND password = MD5(?)");
        if (!$stmt) {
            error_log("Admin Login Prepare Failed: " . $this->conn->error);
            return json_encode(array('status' => 'error', 'msg' => 'Database error during login.'));
        }
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $qry = $stmt->get_result();
        $stmt->close();

        if($qry->num_rows > 0){
            $userData = $qry->fetch_array();
            foreach($userData as $k => $v){
                if(!is_numeric($k) && $k != 'password'){
                    $this->settings->set_userdata($k,$v);
                }
            }
            $this->settings->set_userdata('login_type',1); // Set login_type to 1 for admin
            return json_encode(array('status'=>'success'));
        }else{
            return json_encode(array('status'=>'incorrect','msg'=>'Incorrect Username or Password.'));
        }
    }

    public function logout(){
        if($this->settings->sess_des()){
            // You should also consider Firebase sign-out if used in admin panel
            header('location: '.base_url.'admin/login.php'); // Use base_url for proper redirect
            exit;
        }
    }

    // Existing Client Login (clogin) - This is for direct database password checks
    public function clogin(){
        extract($_POST);
        // Using prepared statement for client login for security
        // MODIFIED: Select the 'status' and 'first_login_done' columns
        $stmt = $this->conn->prepare("SELECT *, `status`, `first_login_done` FROM `accounts` WHERE email = ? AND (`password` = MD5(?) OR generated_password = ?)");
        if (!$stmt) {
            error_log("Client Login Prepare Failed: " . $this->conn->error);
            return json_encode(array('status' => 'error', 'msg' => 'Database error during login.'));
        }
        $stmt->bind_param("sss", $email, $password, $password); // Bind password twice for OR condition
        $stmt->execute();
        $qry = $stmt->get_result();
        $stmt->close();

        $resp = [];
        if($qry->num_rows > 0){
            $userData = $qry->fetch_array();

            // --- ADDED LOGIC FOR STATUS CHECK ---
           if (isset($userData['status']) && strtolower($userData['status']) == 'pending') {
    $resp['status'] = 'pending';
    $resp['msg'] = 'Your account is pending review by the administration. Please wait for activation.';
    return json_encode($resp);
}
            // --- END ADDED LOGIC ---

            foreach($userData as $k => $v){
                if(!is_numeric($k) && $k != 'password' && $k != 'generated_password'){
                    $this->settings->set_userdata($k,$v);
                }
            }
            $this->settings->set_userdata('login_type',2); // Set login_type to 2 for client
            
            // Set first_login session flag
            $this->settings->set_userdata('first_login_done', $userData['first_login_done']);

            $resp['status'] = 'success';
        }else{
            $resp['status'] = 'incorrect';
            $resp['msg'] = 'Incorrect Email or Password.'; // More specific message
        }
        if($this->conn->error){
            $resp['status'] = 'failed';
            $resp['_error'] = $this->conn->error;
        }
        return json_encode($resp);
    }

    // REPLACE WITH THIS FUNCTION
public function clogout(){
    // This new code manually and completely destroys the session.
    
    // Unset all of the session variables.
    $_SESSION = array();

    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, not just the session data!
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finally, destroy the session.
    session_destroy();

    // Redirect to the homepage after logout
    header('location: '.base_url);
    exit;
}
    /**
     * Handles Firebase ID Token verification and creates a PHP session for CLIENTS.
     */
    public function firebase_login_session() {
        extract($_POST); // Expects 'idToken' and 'login_type'

        if (!isset($idToken) || empty($idToken)) {
            return json_encode(['status' => 'failed', 'msg' => 'ID Token missing.']);
        }

        try {
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($idToken);
            $uid = $verifiedIdToken->claims()->get('sub'); // Firebase User ID
            $email = $verifiedIdToken->claims()->get('email'); // Email from Firebase

            error_log("Firebase Login Session: Received ID Token, UID: {$uid}, Email: {$email}");

            // Fetch user data from your MySQL 'accounts' table for CLIENTS
            // MODIFIED: Select the 'status', 'first_login_done', and 'transaction_pin' columns
            // Although SELECT * gets all, it's good to explicitly list if you target specific ones.
            $stmt = $this->conn->prepare("SELECT *, `status`, `first_login_done`, `transaction_pin` FROM `accounts` WHERE firebase_uid = ? OR email = ?");
            if (!$stmt) {
                error_log("Firebase Login Session Prepare Failed: " . $this->conn->error);
                return json_encode(['status' => 'error', 'msg' => 'Database error during Firebase login.']);
            }
            $stmt->bind_param("ss", $uid, $email);
            $stmt->execute();
            $qry = $stmt->get_result();
            $stmt->close();


            if ($qry->num_rows > 0) {
                $userData = $qry->fetch_array();

                // --- ADDED LOGIC FOR STATUS CHECK ---
                // UPDATED: Now checks for the text 'Pending'
if (isset($userData['status']) && strtolower($userData['status']) == 'pending') {
    error_log("Firebase Login Session: User (UID: {$uid}, Email: {$email}) account is pending.");
    return json_encode(['status' => 'pending_approval', 'msg' => 'Your account is pending review by the administration. Please wait for activation.']);
}
                // --- END ADDED LOGIC ---

                // Only allow login if this is a client account (login_type = 2)
                if (isset($userData['login_type']) && $userData['login_type'] == 2) {
                    foreach ($userData as $k => $v) {
                        if (!is_numeric($k) && $k != 'password' && $k != 'generated_password') {
                            $this->settings->set_userdata($k, $v);
                        }
                    }

                    // ================= START: THE FIX =================
                    // Master.php needs 'account_id' in the session for transactions.
                    // Here, we explicitly set it using the 'id' from the accounts table.
                    $this->settings->set_userdata('account_id', $userData['id']);
                    // =================  END: THE FIX  =================

                    $this->settings->set_userdata('firebase_uid', $uid);
                    $this->settings->set_userdata('login_type', 2); // Explicitly set to 2 for client
                    
                    // Set first_login session flag
                    $this->settings->set_userdata('first_login_done', $userData['first_login_done']);

                    error_log("Firebase Login Session: User Found. Session Data after set_userdata: " . print_r($_SESSION, true));

                    if (empty($userData['firebase_uid'])) {
                        // Use prepared statement for update
                        $stmt_update_uid = $this->conn->prepare("UPDATE `accounts` SET `firebase_uid` = ? WHERE `id` = ?");
                        if ($stmt_update_uid) {
                            $stmt_update_uid->bind_param("si", $uid, $userData['id']);
                            $stmt_update_uid->execute();
                            $stmt_update_uid->close();
                            error_log("Firebase Login Session: Updated firebase_uid for account ID {$userData['id']}");
                        } else {
                            error_log("Firebase Login Session: Failed to prepare firebase_uid update: " . $this->conn->error);
                        }
                    }
                    
                    $this->settings->set_userdata('login_success_message', 'You have successfully logged in!'); 
                    
                    // Prepare the response array
                    $response = ['status' => 'success', 'first_login_done' => $userData['first_login_done']];

                    // If it's the first login, include the transaction_pin in the response
                    if ($userData['first_login_done'] == 0) {
                        if (isset($userData['transaction_pin'])) {
                            $response['transaction_pin'] = $userData['transaction_pin'];
                        } else {
                            // Log a warning if the PIN is unexpectedly missing for a first-time login
                            error_log("Warning: 'transaction_pin' not found in userData for first login (UID: {$uid}). Check database schema.");
                        }
                    }
                    
                    return json_encode($response);
                } else {
                    error_log("Firebase Login Session: User (UID: {$uid}, Email: {$email}) found but is not a client (login_type is not 2).");
                    return json_encode(['status' => 'incorrect', 'msg' => 'Account found, but not a client account.']);
                }

            } else {
                error_log("Firebase Login Session: Authenticated Firebase user (UID: {$uid}, Email: {$email}) NOT found in local 'accounts' table.");
                return json_encode(['status' => 'incorrect', 'msg' => 'Authenticated but account not found in banking system.']);
            }

        } catch (InvalidToken $e) {
            error_log("Firebase ID Token verification failed: " . $e->getMessage());
            return json_encode(['status' => 'incorrect', 'msg' => 'Invalid or expired authentication token.']);
        } catch (Throwable $e) {
            error_log("Server-side Firebase login error: " . $e->getMessage());
            return json_encode(['status' => 'error', 'msg' => 'An authentication error occurred on the server.']);
        }
    }

    /**
     * Updates the first_login_done status for the logged-in user.
     */
    public function update_first_login_status() {
        // Ensure user is logged in and is a client
        if (!$this->settings->userdata('id') || $this->settings->userdata('login_type') != 2) {
            return json_encode(['status' => 'error', 'msg' => 'Unauthorized access.']);
        }

        $user_id = $this->settings->userdata('id');

        try {
            $stmt = $this->conn->prepare("UPDATE `accounts` SET `first_login_done` = 1 WHERE `id` = ?");
            if (!$stmt) {
                error_log("Update first_login_done prepare failed: " . $this->conn->error);
                return json_encode(['status' => 'error', 'msg' => 'Database error during status update.']);
            }
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                // Update session variable as well
                $this->settings->set_userdata('first_login_done', 1);
                return json_encode(['status' => 'success', 'msg' => 'First login status updated.']);
            } else {
                // This might happen if the status was already 1 or user_id not found (shouldn't happen if user is logged in)
                error_log("Update first_login_done: No rows affected for user ID: {$user_id}");
                return json_encode(['status' => 'success', 'msg' => 'Status already updated or no changes needed.']);
            }
            $stmt->close();
        } catch (Throwable $e) {
            error_log("Exception updating first_login_done: " . $e->getMessage());
            return json_encode(['status' => 'error', 'msg' => 'An error occurred updating login status.']);
        }
    }
}

$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$auth = new Login();
switch ($action) {
    case 'login': // Admin login
        echo $auth->login();
        break;
    case 'clogin': // Old client login (direct DB password)
        echo $auth->clogin();
        break;
    case 'logout': // Admin logout
        echo $auth->logout();
        break;
    case 'clogout': // Client logout
        echo $auth->clogout();
        break;
    case 'firebase_login_session': // New client login (Firebase)
        echo $auth->firebase_login_session();
        break;
    case 'update_first_login_status': // NEW: Update first_login_done status
        echo $auth->update_first_login_status();
        break;
    default:
        echo $auth->index();
        break;
}