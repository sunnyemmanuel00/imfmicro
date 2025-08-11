<?php
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
   // CRITICAL FIX: The code was looking for 'FIREBASE_SERVICE_ACCOUNT'
   // but the variable on Render is named 'FIREBASE_CREDENTIALS'.
   
   // MODIFICATION START:
   // Use a more robust method to get environment variables.
   // getenv() can fail in certain server configurations.
   // We will now check $_SERVER as a fallback.
   $firebaseConfigJson = getenv('FIREBASE_CREDENTIALS');
   if ($firebaseConfigJson === false) {
    $firebaseConfigJson = $_SERVER['FIREBASE_CREDENTIALS'] ?? false;
   }
   // MODIFICATION END

   if ($firebaseConfigJson !== false && !empty($firebaseConfigJson)) {
    $factory = (new Factory)->withServiceAccountJson($firebaseConfigJson);
    $this->firebaseAuth = $factory->createAuth();
   } else {
    $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
    if (file_exists($serviceAccountPath)) {
     $factory = (new Factory)->withServiceAccount($serviceAccountPath);
     $this->firebaseAuth = $factory->createAuth();
    } else {
     throw new Exception("Firebase service account configuration not found. Please ensure the FIREBASE_CREDENTIALS environment variable is set or the firebase-service-account.json file exists.");
    }
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
  
  try {
   if ($this->db_type === 'mysqli') {
    $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
    if (!$stmt) { throw new Exception("MySQLi Prepare failed: " . $this->conn->error); }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result && $result->num_rows > 0){
     $data = $result->fetch_assoc();
     if(password_verify($password, $data['password'])){
      foreach($data as $k => $v){
       if(!is_numeric($k) && $k != 'password'){ $this->settings->set_userdata($k,$v); }
      }
      $this->settings->set_userdata('login_type', 1);
      return json_encode(array('status'=>'success'));
     } else { return json_encode(array('status'=>'incorrect', 'msg' => 'Incorrect username or password.')); }
    } else { return json_encode(array('status'=>'incorrect', 'msg' => 'Incorrect username or password.')); }
   } elseif ($this->db_type === 'pgsql') {
    $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
    if (!$stmt) { throw new Exception("PostgreSQL Prepare failed."); }
    $stmt->execute([$username]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if($data){
     if(password_verify($password, $data['password'])){
      foreach($data as $k => $v){
       if(!is_numeric($k) && $k != 'password'){ $this->settings->set_userdata($k,$v); }
      }
      $this->settings->set_userdata('login_type', 1);
      return json_encode(array('status'=>'success'));
     } else { return json_encode(array('status'=>'incorrect', 'msg' => 'Incorrect username or password.')); }
    } else { return json_encode(array('status'=>'incorrect', 'msg' => 'Incorrect username or password.')); }
   } else {
    throw new Exception("Unsupported database type.");
   }
  } catch (Exception $e) {
   error_log("Admin Login Error: " . $e->getMessage());
   return json_encode(array('status'=>'error', 'msg'=>'A database error occurred.'));
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
   if ($this->firebaseAuth === null) {
    error_log("Firebase SDK was not initialized correctly. Check service account configuration.");
    return json_encode(['status' => 'error', 'msg' => 'Firebase authentication is not configured correctly on the server.']);
   }
   
   $verifiedIdToken = $this->firebaseAuth->verifyIdToken($idToken);
   $uid = $verifiedIdToken->claims()->get('sub');
   $email = $verifiedIdToken->claims()->get('email');
   $email_for_query = trim(strtolower($email));
   
   if ($this->db_type === 'mysqli') {
    // Correcting SQL syntax for mysqli with backticks
    $stmt = $this->conn->prepare("SELECT *, `status`, `first_login_done`, `transaction_pin` AS `pin` FROM `accounts` WHERE `firebase_uid` = ? OR lower(email) = ?");
    if (!$stmt) { throw new Exception("Failed to prepare statement: " . $this->conn->error); }
    $stmt->bind_param("ss", $uid, $email_for_query);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
   } elseif ($this->db_type === 'pgsql') {
    // Correcting SQL syntax for pgsql with double quotes
    $stmt = $this->conn->prepare('SELECT *, "status", "first_login_done", "transaction_pin" AS "pin" FROM "accounts" WHERE "firebase_uid" = ? OR lower(email) = ?');
    if (!$stmt) { throw new Exception("Failed to prepare statement."); }
    $stmt->execute([$uid, $email_for_query]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
   } else {
    throw new Exception("Unsupported database type.");
   }

   if ($userData) {
    if (isset($userData['status']) && strtolower($userData['status']) == 'pending') { 
     return json_encode(['status' => 'pending_approval', 'msg' => 'Your account is pending review.']); 
    }
    
    if (isset($userData['login_type']) && $userData['login_type'] == 2) {
     foreach ($userData as $k => $v) { 
      if (!is_numeric($k) && $k != 'password' && $k != 'generated_password') { 
       $this->settings->set_userdata($k, $v); 
      } 
     }
     $this->settings->set_userdata('account_id', $userData['id']);
     $this->settings->set_userdata('firebase_uid', $uid);
     $this->settings->set_userdata('login_type', 2);
     // *** MODIFICATION START ***
     // Use the fetched boolean value directly for the session
     $this->settings->set_userdata('first_login_done', $userData['first_login_done']);
     // *** MODIFICATION END ***
     
     if (empty($userData['firebase_uid'])) {
      if ($this->db_type === 'mysqli') {
       $stmt_update_uid = $this->conn->prepare("UPDATE `accounts` SET `firebase_uid` = ? WHERE `id` = ?");
       if ($stmt_update_uid) { 
        $stmt_update_uid->bind_param("si", $uid, $userData['id']); 
        $stmt_update_uid->execute();
       }
      } elseif ($this->db_type === 'pgsql') {
       $stmt_update_uid = $this->conn->prepare("UPDATE `accounts` SET `firebase_uid` = ? WHERE `id` = ?");
       if ($stmt_update_uid) { 
        $stmt_update_uid->execute([$uid, $userData['id']]);
       }
      }
     }
     
     $response = ['status' => 'success', 'first_login_done' => $userData['first_login_done']];

     // *** MODIFICATION START ***
     // Check for the boolean value `false` instead of the numeric `0`
     if ($userData['first_login_done'] === false && isset($userData['pin'])) { 
      $this->update_first_login_status_immediate($userData['id']);
      $response['pin'] = $userData['pin']; 
     }
     // *** MODIFICATION END ***
     return json_encode($response);
    } else { 
     return json_encode(['status' => 'incorrect', 'msg' => 'Account found, but not a client account.']); 
    }
   } else { 
    return json_encode(['status' => 'incorrect', 'msg' => 'Authenticated but account not found in banking system.']);
   }
  } catch (Throwable $e) {
   error_log("Firebase login error: " . $e->getMessage());
   return json_encode(['status' => 'error', 'msg' => 'An authentication error occurred: ' . $e->getMessage()]);
  }
 }

 public function update_first_login_status_immediate($user_id) {
  try {
   if ($this->db_type === 'mysqli') {
    $stmt = $this->conn->prepare("UPDATE `accounts` SET `first_login_done` = 1 WHERE `id` = ?");
    if (!$stmt) { throw new Exception("Failed to prepare statement: " . $this->conn->error); }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
   } elseif ($this->db_type === 'pgsql') {
    // *** MODIFICATION START ***
    // Use the boolean value `true` in the update query
    $stmt = $this->conn->prepare('UPDATE "accounts" SET "first_login_done" = true WHERE "id" = ?');
    if (!$stmt) { throw new Exception("Failed to prepare statement."); }
    $stmt->execute([$user_id]);
    // *** MODIFICATION END ***
   }
   // *** MODIFICATION START ***
   // Update session variable with the boolean value `true`
   $this->settings->set_userdata('first_login_done', true);
   // *** MODIFICATION END ***
  } catch (Exception $e) {
   error_log("Immediate Update First Login Status Error: " . $e->getMessage());
  }
 }

 public function update_first_login_status() {
  if (!$this->settings->userdata('account_id') || $this->settings->userdata('login_type') != 2) { 
   return json_encode(['status' => 'error', 'msg' => 'Unauthorized access.']); 
  }
  $user_id = $this->settings->userdata('account_id');
  
  try {
   if ($this->db_type === 'mysqli') {
    $stmt = $this->conn->prepare("UPDATE `accounts` SET `first_login_done` = 1 WHERE `id` = ?");
    if (!$stmt) { throw new Exception("Failed to prepare statement: " . $this->conn->error); }
    $stmt->bind_param("i", $user_id);
    $update = $stmt->execute();
   } elseif ($this->db_type === 'pgsql') {
    // *** MODIFICATION START ***
    // Use the boolean value `true` in the update query
    $stmt = $this->conn->prepare('UPDATE "accounts" SET "first_login_done" = true WHERE "id" = ?');
    if (!$stmt) { throw new Exception("Failed to prepare statement."); }
    $update = $stmt->execute([$user_id]);
    // *** MODIFICATION END ***
   } else {
    throw new Exception("Unsupported database type.");
   }

   if($update){
    // *** MODIFICATION START ***
    // Update session variable with the boolean value `true`
    $this->settings->set_userdata('first_login_done', true);
    // *** MODIFICATION END ***
    return json_encode(['status' => 'success']);
   } else {
    return json_encode(['status' => 'failed', 'msg' => 'Failed to update first login status.']);
   }
  } catch (Exception $e) {
   error_log("Update First Login Status Error: " . $e->getMessage());
   return json_encode(['status' => 'error', 'msg' => 'A database error occurred.']);
  }
 }
}

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
?>
