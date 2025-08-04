<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/DBConnection.php');

Class Account extends DBConnection {
  private $settings;
  public function __construct(){
    global $_settings;
    $this->settings = $_settings;
    parent::__construct();
  }
  public function __destruct(){
    parent::__destruct();
  }

  public function save_account(){
    try {
      if ($this->conn->connect_error) {
        throw new Exception("Database connection was lost before saving.");
      }

      extract($_POST);

      $required_fields = ['firstname', 'lastname', 'address', 'marital_status', 'gender', 'phone_number', 'date_of_birth', 'id_type', 'id_number', 'email'];
      foreach($required_fields as $field){
        if(!isset($$field) || empty($$field)){
          throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required.");
        }
      }
      
      if(!isset($firebase_uid) || empty($firebase_uid)){
        throw new Exception("Firebase User ID is required.");
      }

      $email_escaped = $this->conn->real_escape_string($email);
      $chk_stmt = $this->conn->prepare("SELECT `id` FROM `accounts` WHERE `email` = ?");
      $chk_stmt->bind_param("s", $email_escaped);
      $chk_stmt->execute();
      $result = $chk_stmt->get_result();
      if($result->num_rows > 0){
        throw new Exception('This email address is already in our records.');
      }
      $chk_stmt->close();

      $account_number = '';
      while(true){
        $account_number = sprintf("%'.010d", mt_rand(0, 9999999999));
        $chk_acc_num = $this->conn->query("SELECT `id` FROM `accounts` WHERE `account_number` = '{$account_number}'")->num_rows;
        if($chk_acc_num <= 0) break;
      }

      $data = "";
      foreach($_POST as $k => $v){
        if(!in_array($k, array('password', 'confirm_password'))){
          if(!empty($data)) $data .= ", ";
          $data .= " `{$k}` = '". $this->conn->real_escape_string($v) ."' ";
        }
      }

      $data .= ", `account_number` = '{$account_number}', `balance` = 0, `status` = 'Pending', `login_type` = 2, `first_login_done` = 0 ";
      
      // =========================== THE FIX ===========================
      // Corrected column name to `transaction_pin` to match verification logic.
      $plain_pin = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
      $data .= ", `transaction_pin` = '". $this->conn->real_escape_string($plain_pin) ."' ";
      // ========================= END OF FIX ==========================


      $sql = "INSERT INTO `accounts` SET {$data}";
      $save = $this->conn->query($sql);

      if($save){
        $account_id = $this->conn->insert_id;
        return json_encode([
          'status' => 'success', 
          'msg' => 'Your application has been submitted for review. You will receive an email once your account is approved. Your account number is: ' . $account_number
        ]);
      } else {
        throw new Exception("Failed to save to database: " . $this->conn->error);
      }

    } catch (Exception $e) {
      error_log("Error in Account.php::save_account(): " . $e->getMessage());
      header('Content-Type: application/json');
      echo json_encode([
        'status' => 'failed',
        'msg' => 'Server Error: ' . $e->getMessage()
      ]);
      exit;
    }
  }
  
  public function get_account_details_for_login() {
    extract($_POST);
    if(!isset($firebase_uid) || empty($firebase_uid)){
      return json_encode(['status' => 'failed', 'msg' => "Firebase User ID is required."]);
    }
    $firebase_uid = $this->conn->real_escape_string($firebase_uid);
    // MODIFIED: Changed the select column to match the save logic.
    $sql = "SELECT id, transaction_pin, first_login_done, status FROM `accounts` WHERE `firebase_uid` = '{$firebase_uid}'";
    $result = $this->conn->query($sql);

    if($result && $result->num_rows > 0) {
      $account_data = $result->fetch_assoc();
      return json_encode(['status' => 'success', 'data' => $account_data]);
    } else {
      return json_encode(['status' => 'failed', 'msg' => 'Account not found or not linked to Firebase UID.']);
    }
  }

  public function update_first_login_status() {
    extract($_POST);
    if(!isset($account_id) || empty($account_id)){
      return json_encode(['status' => 'failed', 'msg' => "Account ID is required."]);
    }

    $account_id = $this->conn->real_escape_string($account_id);
    $sql = "UPDATE `accounts` SET `first_login_done` = 1 WHERE `id` = '{$account_id}'";
    $update = $this->conn->query($sql);

    if($update){
      return json_encode(['status' => 'success', 'msg' => 'First login status updated.']);
    } else {
      return json_encode(['status' => 'failed', 'msg' => 'Failed to update first login status: ' . $this->conn->error]);
    }
  }
}

$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$account = null;
try {
  $account = new Account();
} catch (Exception $e) {
  header('Content-Type: application/json');
  echo json_encode([
    'status' => 'failed',
    'msg' => 'Server Error on init: ' . $e->getMessage()
  ]);
  exit;
}

switch ($action) {
  case 'create_account':
    echo $account->save_account();
    break;
  case 'get_account_details_for_login':
    echo $account->get_account_details_for_login();
    break;
  case 'update_first_login_status':
    echo $account->update_first_login_status();
    break;
  default:
    echo json_encode(['status' => 'failed', 'msg' => 'Invalid action specified for Account controller.']);
    break;
}