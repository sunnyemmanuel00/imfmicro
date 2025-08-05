<?php
// ===========================================================================
// Login.php - Dual Database Compatibility
// This file has been updated to work with both MySQL and PostgreSQL.
// The database type is determined by the DB_TYPE constant in config.php.
// ===========================================================================

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

  public function login(){
    extract($_POST);
    
    $sql = DB_TYPE === 'mysql' ? "SELECT * FROM `users` WHERE `username` = ?" : 'SELECT * FROM "users" WHERE "username" = $1';
    
    $data = null;
    if (DB_TYPE === 'mysql') {
        $stmt = $this->conn->prepare($sql);
        if(!$stmt){
            return json_encode(array('status'=>'failed', 'msg'=>'Database prepare failed.'));
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0){
            $data = $result->fetch_assoc();
        }
        $stmt->close();
    } else { // pgsql
        $result = pg_query_params($this->conn, $sql, array($username));
        if ($result && pg_num_rows($result) > 0) {
            $data = pg_fetch_assoc($result);
        }
    }

    if($data){
      if(password_verify($password, $data['password'])){
        foreach($data as $k => $v){
          if(!is_numeric($k) && $k != 'password'){
            $this->settings->set_userdata($k,$v);
          }
        }
        $this->settings->set_userdata('login_type', 1);
        return json_encode(array('status'=>'success'));
      } else {
        return json_encode(array('status'=>'incorrect'));
      }
    } else {
      return json_encode(array('status'=>'incorrect'));
    }
  }

  public function logout(){
    if($this->settings->sess_des()){
      header('location: '.base_url.'?p=admin_login');
      echo '<script>window.location.href = "'.base_url.'?p=admin_login";</script>';
      exit;
    }
  }

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
      
      $email_for_query = trim(strtolower($email));
      
      $sql = DB_TYPE === 'mysql' ? "SELECT `id`, `status`, `first_login_done`, `transaction_pin` FROM `accounts` WHERE `firebase_uid` = ? OR lower(`email`) = ?" : 'SELECT "id", "status", "first_login_done", "transaction_pin" FROM "accounts" WHERE "firebase_uid" = $1 OR lower("email") = $2';
      
      $account_data = null;
      if (DB_TYPE === 'mysql') {
          $stmt = $this->conn->prepare($sql);
          if (!$stmt) { return json_encode(['status' => 'error', 'msg' => 'Database error during prepare.']); }
          $stmt->bind_param("ss", $uid, $email_for_query);
          $stmt->execute();
          $result = $stmt->get_result();
          if ($result->num_rows > 0) {
              $account_data = $result->fetch_assoc();
          }
          $stmt->close();
      } else { // pgsql
          $result = pg_query_params($this->conn, $sql, array($uid, $email_for_query));
          if ($result && pg_num_rows($result) > 0) {
              $account_data = pg_fetch_assoc($result);
          }
      }

      if ($account_data) {
        if (isset($account_data['status']) && strtolower($account_data['status']) == 'pending') { return json_encode(['status' => 'pending_approval', 'msg' => 'Your account is pending review.']); }
        
        $sql_update_uid = DB_TYPE === 'mysql' ? "UPDATE `accounts` SET `firebase_uid` = ? WHERE `id` = ?" : 'UPDATE "accounts" SET "firebase_uid" = $1 WHERE "id" = $2';
        if (empty($account_data['firebase_uid'])) {
            if (DB_TYPE === 'mysql') {
                $stmt_update_uid = $this->conn->prepare($sql_update_uid);
                if ($stmt_update_uid) { $stmt_update_uid->bind_param("si", $uid, $account_data['id']); $stmt_update_uid->execute(); $stmt_update_uid->close(); }
            } else { // pgsql
                pg_query_params($this->conn, $sql_update_uid, array($uid, $account_data['id']));
            }
        }
        
        foreach ($account_data as $k => $v) { 
            if (!is_numeric($k) && $k != 'password' && $k != 'generated_password') { 
                $this->settings->set_userdata($k, $v); 
            } 
        }

        $this->settings->set_userdata('account_id', $account_data['id']);
        $this->settings->set_userdata('firebase_uid', $uid);
        $this->settings->set_userdata('login_type', 2);
        $this->settings->set_userdata('first_login_done', $account_data['first_login_done']);
        
        $response = ['status' => 'success', 'first_login_done' => $account_data['first_login_done']];
        if ($account_data['first_login_done'] == 0 && isset($account_data['transaction_pin'])) { 
            $response['pin'] = $account_data['transaction_pin']; 
        }
        return json_encode($response);
      } else { 
        return json_encode(['status' => 'incorrect', 'msg' => 'Authenticated but account not found in banking system.']);
      }
    } catch (Throwable $e) {
      error_log("Firebase login error: " . $e->getMessage());
      return json_encode(['status' => 'error', 'msg' => 'An authentication error occurred.']);
    }
  }

  public function update_first_login_status() {
    if (!$this->settings->userdata('id') || $this->settings->userdata('login_type') != 2) { 
        return json_encode(['status' => 'error', 'msg' => 'Unauthorized access.']); 
    }
    $user_id = $this->settings->userdata('id');
    
    $sql = DB_TYPE === 'mysql' ? "UPDATE `accounts` SET `first_login_done` = 1 WHERE `id` = ?" : 'UPDATE "accounts" SET "first_login_done" = 1 WHERE "id" = $1';
    
    $update = false;
    if (DB_TYPE === 'mysql') {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) { return json_encode(['status' => 'error', 'msg' => 'Database error.']); }
        $stmt->bind_param("i", $user_id);
        $update = $stmt->execute();
        $stmt->close();
    } else { // pgsql
        $update = pg_query_params($this->conn, $sql, array($user_id));
    }
    
    if ($update) {
        $this->settings->set_userdata('first_login_done', 1);
        return json_encode(['status' => 'success']);
    } else {
        $error = DB_TYPE === 'mysql' ? $this->conn->error : pg_last_error($this->conn);
        return json_encode(['status' => 'error', 'msg' => "Failed to update first login status: " . $error]);
    }
  }
}

$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$auth = null;
try {
    $auth = new Login();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'failed',
        'msg' => 'Server Error on init: ' . $e->getMessage()
    ]);
    exit;
}
switch ($action) {
  case 'login': echo $auth->login(); break;
  case 'clogin': break;
  case 'logout': echo $auth->logout(); break;
  case 'clogout': echo $auth->clogout(); break;
  case 'firebase_login_session': echo $auth->firebase_login_session(); break;
  case 'update_first_login_status': echo $auth->update_first_login_status(); break;
  default: echo json_encode(['status' => 'failed', 'msg' => 'Invalid action specified for Login controller.']); break;
}
