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
            if ($this->conn === null) {
                throw new Exception("Database connection was lost before saving.");
            }

            extract($_POST);

            $required_fields = ['firstname', 'lastname', 'address', 'marital_status', 'gender', 'phone_number', 'date_of_birth', 'id_type', 'id_number', 'email', 'firebase_uid'];
            foreach($required_fields as $field){
                if(!isset($$field) || empty($$field)){
                    throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required.");
                }
            }

            $email_for_check = strtolower(trim($email));
            
            if ($this->db_type === 'mysqli') {
                $chk_stmt = $this->conn->prepare("SELECT `id` FROM `accounts` WHERE lower(email) = ?");
                if (!$chk_stmt) { throw new Exception("Failed to prepare statement for email check: " . $this->conn->error); }
                $chk_stmt->bind_param("s", $email_for_check);
                $chk_stmt->execute();
                $result = $chk_stmt->get_result();
                if($result->num_rows > 0){
                    throw new Exception('This email address is already in our records.');
                }
            } elseif ($this->db_type === 'pgsql') {
                $chk_stmt = $this->conn->prepare('SELECT "id" FROM "accounts" WHERE lower("email") = ?');
                if (!$chk_stmt) { throw new Exception("Failed to prepare statement for email check."); }
                $chk_stmt->execute([$email_for_check]);
                $result = $chk_stmt->fetch(PDO::FETCH_ASSOC);
                if($result){
                    throw new Exception('This email address is already in our records.');
                }
            } else {
                throw new Exception("Unsupported database type.");
            }

            $account_number = '';
            while(true){
                $account_number = sprintf("%'.010d", mt_rand(0, 9999999999));
                
                if ($this->db_type === 'mysqli') {
                    $chk_acc_num = $this->conn->query("SELECT `id` FROM `accounts` WHERE `account_number` = '{$account_number}'")->num_rows;
                } elseif ($this->db_type === 'pgsql') {
                    $stmt_check = $this->conn->prepare('SELECT "id" FROM "accounts" WHERE "account_number" = ?');
                    $stmt_check->execute([$account_number]);
                    $chk_acc_num = $stmt_check->rowCount();
                }
                
                if($chk_acc_num <= 0) break;
            }

            $plain_pin = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $sql = "INSERT INTO `accounts` (`firstname`, `lastname`, `address`, `marital_status`, `gender`, `phone_number`, `date_of_birth`, `id_type`, `id_number`, `email`, `firebase_uid`, `account_number`, `balance`, `status`, `login_type`, `first_login_done`, `transaction_pin`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $status = 'Pending';
            $login_type = 2;
            $first_login_done = 0;
            $balance = 0;

            if ($this->db_type === 'mysqli') {
                $stmt_insert = $this->conn->prepare($sql);
                if (!$stmt_insert) { throw new Exception("Failed to prepare insert statement: " . $this->conn->error); }
                $stmt_insert->bind_param("sssssssssssisssis",
                    $firstname, $lastname, $address, $marital_status, $gender, $phone_number, $date_of_birth, $id_type, $id_number, $email, $firebase_uid, $account_number, $balance, $status, $login_type, $first_login_done, $plain_pin
                );
                $save = $stmt_insert->execute();
            } elseif ($this->db_type === 'pgsql') {
                $sql = 'INSERT INTO "accounts" ("firstname", "lastname", "address", "marital_status", "gender", "phone_number", "date_of_birth", "id_type", "id_number", "email", "firebase_uid", "account_number", "balance", "status", "login_type", "first_login_done", "transaction_pin") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
                $stmt_insert = $this->conn->prepare($sql);
                if (!$stmt_insert) { throw new Exception("Failed to prepare insert statement."); }
                $save = $stmt_insert->execute([
                    $firstname, $lastname, $address, $marital_status, $gender, $phone_number, $date_of_birth, $id_type, $id_number, $email, $firebase_uid, $account_number, $balance, $status, $login_type, $first_login_done, $plain_pin
                ]);
            } else {
                throw new Exception("Unsupported database type.");
            }

            if($save){
                return json_encode([
                    'status' => 'success',
                    'msg' => 'Your application has been submitted for review. You will receive an email once your account is approved. Your account number is: ' . $account_number
                ]);
            } else {
                throw new Exception("Failed to save to database.");
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

    public function submit_inquiry(){
        try {
            if ($this->conn === null) {
                throw new Exception("Database connection was lost before saving.");
            }

            extract($_POST);

            $required_fields = ['name', 'email', 'type', 'subject', 'message'];
            foreach($required_fields as $field){
                if(!isset($$field) || empty($$field)){
                    throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required.");
                }
            }

            if ($this->db_type !== 'pgsql') {
                throw new Exception("This action is only supported for PostgreSQL.");
            }

            // Corrected SQL query with the proper column name from the screenshot
            $sql = 'INSERT INTO "inquiries" ("name", "email", "phone", "type", "subject", "message", "date_created") VALUES (?, ?, ?, ?, ?, ?, NOW())';
            
            $stmt_insert = $this->conn->prepare($sql);
            if (!$stmt_insert) {
                throw new Exception("Failed to prepare insert statement.");
            }
            
            $phone = !empty($phone) ? $phone : null;

            $save = $stmt_insert->execute([
                $name,
                $email,
                $phone,
                $type,
                $subject,
                $message
            ]);

            if($save){
                return json_encode([
                    'status' => 'success',
                    'msg' => 'Your message has been sent successfully. We will get back to you shortly.'
                ]);
            } else {
                throw new Exception("Failed to save inquiry to database.");
            }

        } catch (Exception $e) {
            error_log("Error in Account.php::submit_inquiry(): " . $e->getMessage());
            return json_encode([
                'status' => 'failed',
                'msg' => 'Server Error: ' . $e->getMessage()
            ]);
        }
    }
    
    public function get_account_details_for_login() {
        extract($_POST);
        if(!isset($firebase_uid) || empty($firebase_uid)){
            return json_encode(['status' => 'failed', 'msg' => "Firebase User ID is required."]);
        }
        
        try {
            if ($this->db_type === 'mysqli') {
                $stmt = $this->conn->prepare("SELECT id, transaction_pin, first_login_done, status FROM `accounts` WHERE `firebase_uid` = ?");
                if (!$stmt) { throw new Exception("Failed to prepare statement: " . $this->conn->error); }
                $stmt->bind_param("s", $firebase_uid);
                $stmt->execute();
                $result = $stmt->get_result();
                $account_data = $result->fetch_assoc();
            } elseif ($this->db_type === 'pgsql') {
                $stmt = $this->conn->prepare('SELECT "id", "transaction_pin", "first_login_done", "status" FROM "accounts" WHERE "firebase_uid" = ?');
                if (!$stmt) { throw new Exception("Failed to prepare statement."); }
                $stmt->execute([$firebase_uid]);
                $account_data = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                throw new Exception("Unsupported database type.");
            }

            if($account_data) {
                return json_encode(['status' => 'success', 'data' => $account_data]);
            } else {
                return json_encode(['status' => 'failed', 'msg' => 'Account not found or not linked to Firebase UID.']);
            }
        } catch (Exception $e) {
            error_log("Get Account Details Error: " . $e->getMessage());
            return json_encode(['status' => 'error', 'msg' => 'A database error occurred.']);
        }
    }

    public function update_first_login_status() {
        extract($_POST);
        if(!isset($account_id) || empty($account_id)){
            return json_encode(['status' => 'failed', 'msg' => "Account ID is required."]);
        }
        
        try {
            if ($this->db_type === 'mysqli') {
                $stmt = $this->conn->prepare("UPDATE `accounts` SET `first_login_done` = 1 WHERE `id` = ?");
                if (!$stmt) { throw new Exception("Failed to prepare statement: " . $this->conn->error); }
                $stmt->bind_param("i", $account_id);
                $update = $stmt->execute();
            } elseif ($this->db_type === 'pgsql') {
                $stmt = $this->conn->prepare('UPDATE "accounts" SET "first_login_done" = 1 WHERE "id" = ?');
                if (!$stmt) { throw new Exception("Failed to prepare statement."); }
                $update = $stmt->execute([$account_id]);
            } else {
                throw new Exception("Unsupported database type.");
            }

            if($update){
                return json_encode(['status' => 'success', 'msg' => 'First login status updated.']);
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
    case 'submit_inquiry':
        echo $account->submit_inquiry();
        break;
    default:
        echo json_encode(['status' => 'failed', 'msg' => 'Invalid action specified for Account controller.']);
        break;
}
