<?php
// No require statements at the top, which is correct for our router.

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\InvalidToken;

class Login extends DBConnection {
  private $settings;
  private $firebaseAuth;

  public function __construct(){
    global $_settings;
    $this->settings = $_settings;
    parent::__construct();
    ini_set('display_errors', 0);

    try {
      $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
      if (file_exists($serviceAccountPath)) {
        $factory = (new Factory)->withServiceAccount($serviceAccountPath);
        $this->firebaseAuth = $factory->createAuth();
      }
    } catch (Throwable $e) {
      $this->firebaseAuth = null;
      error_log("Firebase SDK Init Error: " . $e->getMessage());
    }
  }

  public function __destruct(){
    parent::__destruct();
  }

  // =========================== THE FINAL FIX ===========================
  // This is the corrected admin login function. It now uses the modern
  // and secure password_verify() method, which will work with your admin password.
  public function login(){
    extract($_POST);
    $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
    if(!$stmt){
      return json_encode(array('status'=>'failed', 'msg'=>'Database prepare failed.'));
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){
      $data = $result->fetch_assoc();
      // Verify the submitted password against the hash stored in the database
      if(password_verify($password, $data['password'])){
        // Passwords match - Success
        foreach($data as $k => $v){
          if(!is_numeric($k) && $k != 'password'){
            $this->settings->set_userdata($k,$v);
          }
        }
        $this->settings->set_userdata('login_type', 1);
        return json_encode(array('status'=>'success'));
      } else {
        // Password does not match
        return json_encode(array('status'=>'incorrect'));
      }
    } else {
      // Username not found
      return json_encode(array('status'=>'incorrect'));
    }
  }
  // ========================= END OF FIX ==========================

public function logout(){
      if($this->settings->sess_des()){
        // Attempt the PHP redirect first.
        header('location: '.base_url.'?p=admin_login');
        // Added a JavaScript fallback in case the PHP header redirect fails.
        // This makes the redirect robust against "headers already sent" errors.
        echo '<script>window.location.href = "'.base_url.'?p=admin_login";</script>';
        exit; // Ensure script execution stops after the redirect.
      }
    }

    // All other functions for client login/logout are unchanged.
    public function clogout(){
      $_SESSION = array();
      if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
      }
      session_destroy();
      header('location: '.base_url);
      exit;
    }

  public function firebase_login_session() {
    extract($_POST);
    if (!isset($idToken) || empty($idToken)) { return json_encode(['status' => 'failed', 'msg' => 'ID Token missing.']); }
    try {
      $verifiedIdToken = $this->firebaseAuth->verifyIdToken($idToken);
      $uid = $verifiedIdToken->claims()->get('sub');
      $email = $verifiedIdToken->claims()->get('email');
      $stmt = $this->conn->prepare("SELECT *, `status`, `first_login_done`, `pin` FROM `accounts` WHERE firebase_uid = ? OR email = ?");
      if (!$stmt) { return json_encode(['status' => 'error', 'msg' => 'Database error.']);}
      $stmt->bind_param("ss", $uid, $email);
      $stmt->execute();
      $qry = $stmt->get_result();
      $stmt->close();
      if ($qry->num_rows > 0) {
        $userData = $qry->fetch_array();
        if (isset($userData['status']) && strtolower($userData['status']) == 'pending') { return json_encode(['status' => 'pending_approval', 'msg' => 'Your account is pending review.']); }
        if (isset($userData['login_type']) && $userData['login_type'] == 2) {
          foreach ($userData as $k => $v) { if (!is_numeric($k) && $k != 'password' && $k != 'generated_password') { $this->settings->set_userdata($k, $v); } }
          $this->settings->set_userdata('account_id', $userData['id']);
          $this->settings->set_userdata('firebase_uid', $uid);
          $this->settings->set_userdata('login_type', 2);
          $this->settings->set_userdata('first_login_done', $userData['first_login_done']);
          if (empty($userData['firebase_uid'])) {
            $stmt_update_uid = $this->conn->prepare("UPDATE `accounts` SET `firebase_uid` = ? WHERE `id` = ?");
            if ($stmt_update_uid) { $stmt_update_uid->bind_param("si", $uid, $userData['id']); $stmt_update_uid->execute(); $stmt_update_uid->close(); }
          }
          $response = ['status' => 'success', 'first_login_done' => $userData['first_login_done']];
          if ($userData['first_login_done'] == 0 && isset($userData['pin'])) { $response['pin'] = $userData['pin']; }
          return json_encode($response);
        } else { return json_encode(['status' => 'incorrect', 'msg' => 'Account found, but not a client account.']); }
      } else { return json_encode(['status' => 'incorrect', 'msg' => 'Authenticated but account not found in banking system.']);}
    } catch (Throwable $e) {
      error_log("Firebase login error: " . $e->getMessage());
      return json_encode(['status' => 'error', 'msg' => 'An authentication error occurred.']);
    }
  }

  public function update_first_login_status() {
    if (!$this->settings->userdata('id') || $this->settings->userdata('login_type') != 2) { return json_encode(['status' => 'error', 'msg' => 'Unauthorized access.']); }
    $user_id = $this->settings->userdata('id');
    $stmt = $this->conn->prepare("UPDATE `accounts` SET `first_login_done` = 1 WHERE `id` = ?");
    if (!$stmt) { return json_encode(['status' => 'error', 'msg' => 'Database error.']); }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $this->settings->set_userdata('first_login_done', 1);
    return json_encode(['status' => 'success']);
  }
}

// This switch statement correctly routes the action from index.php
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$auth = new Login();
switch ($action) {
  case 'login': echo $auth->login(); break;
  case 'clogin': break; // Deprecated
  case 'logout': echo $auth->logout(); break;
  case 'clogout': echo $auth->clogout(); break;
  case 'firebase_login_session': echo $auth->firebase_login_session(); break;
  case 'update_first_login_status': echo $auth->update_first_login_status(); break;
}