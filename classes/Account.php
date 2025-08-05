<?php
// ===========================================================================
// Account.php - Dual Database Compatibility
// This file has been updated to work with both MySQL and PostgreSQL.
// The database type is determined by the DB_TYPE constant in config.php.
// ===========================================================================

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
            if (DB_TYPE === 'mysql') {
                if ($this->conn->connect_error) {
                    throw new Exception("Database connection was lost before saving.");
                }
            } elseif (DB_TYPE === 'pgsql') {
                if (pg_connection_status($this->conn) !== PGSQL_CONNECTION_OK) {
                    throw new Exception("Database connection was lost before saving.");
                }
            }

            extract($_POST);

            // Validate required fields
            $required_fields = ['firstname', 'lastname', 'address', 'marital_status', 'gender', 'phone_number', 'date_of_birth', 'id_type', 'id_number', 'email'];
            foreach($required_fields as $field){
                if(!isset($$field) || empty($$field)){
                    throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required.");
                }
            }
            
            if(!isset($firebase_uid) || empty($firebase_uid)){
                throw new Exception("Firebase User ID is required. Registration failed.");
            }

            // Check if email already exists
            $check_email_sql = DB_TYPE === 'mysql' ? "SELECT `id` FROM `accounts` WHERE `email` = ?" : 'SELECT "id" FROM "accounts" WHERE "email" = $1';
            $check_email_params = [DB_TYPE === 'mysql' ? 's' : null, $email];
            
            $stmt = null;
            if (DB_TYPE === 'mysql') {
                $stmt = $this->conn->prepare($check_email_sql);
                $stmt->bind_param(...$check_email_params);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows > 0) {
                    throw new Exception('This email address is already in our records.');
                }
                $stmt->close();
            } elseif (DB_TYPE === 'pgsql') {
                $result = pg_query_params($this->conn, $check_email_sql, array($email));
                if ($result && pg_num_rows($result) > 0) {
                    throw new Exception('This email address is already in our records.');
                }
            }

            // Generate a unique account number
            $account_number = '';
            do {
                $account_number = sprintf("%'.010d", mt_rand(0, 9999999999));
                $check_acc_num_sql = DB_TYPE === 'mysql' ? "SELECT `id` FROM `accounts` WHERE `account_number` = ?" : 'SELECT "id" FROM "accounts" WHERE "account_number" = $1';
                if (DB_TYPE === 'mysql') {
                    $stmt = $this->conn->prepare($check_acc_num_sql);
                    $stmt->bind_param('s', $account_number);
                    $stmt->execute();
                    $chk_acc_num = $stmt->get_result()->num_rows;
                    $stmt->close();
                } else { // pgsql
                    $result = pg_query_params($this->conn, $check_acc_num_sql, array($account_number));
                    $chk_acc_num = pg_num_rows($result);
                }
            } while ($chk_acc_num > 0);

            // Prepare the data for insertion
            $columns = [];
            $placeholders = [];
            $values = [];
            $param_index = 1;

            foreach($_POST as $k => $v){
                if(!in_array($k, ['password', 'confirm_password', 'action'])){
                    $columns[] = DB_TYPE === 'mysql' ? "`{$k}`" : "\"{$k}\"";
                    $placeholders[] = DB_TYPE === 'mysql' ? '?' : '$' . $param_index++;
                    $values[] = $v;
                }
            }
            
            // Add fixed fields
            $columns[] = DB_TYPE === 'mysql' ? "`firebase_uid`" : "\"firebase_uid\"";
            $placeholders[] = DB_TYPE === 'mysql' ? '?' : '$' . $param_index++;
            $values[] = $firebase_uid;

            $columns[] = DB_TYPE === 'mysql' ? "`account_number`" : "\"account_number\"";
            $placeholders[] = DB_TYPE === 'mysql' ? '?' : '$' . $param_index++;
            $values[] = $account_number;

            $columns[] = DB_TYPE === 'mysql' ? "`balance`" : "\"balance\"";
            $placeholders[] = DB_TYPE === 'mysql' ? '?' : '$' . $param_index++;
            $values[] = 0;
            
            $columns[] = DB_TYPE === 'mysql' ? "`status`" : "\"status\"";
            $placeholders[] = DB_TYPE === 'mysql' ? '?' : '$' . $param_index++;
            $values[] = 'Pending';
            
            $columns[] = DB_TYPE === 'mysql' ? "`login_type`" : "\"login_type\"";
            $placeholders[] = DB_TYPE === 'mysql' ? '?' : '$' . $param_index++;
            $values[] = 2;
            
            $columns[] = DB_TYPE === 'mysql' ? "`first_login_done`" : "\"first_login_done\"";
            $placeholders[] = DB_TYPE === 'mysql' ? '?' : '$' . $param_index++;
            $values[] = 0;
            
            $columns[] = DB_TYPE === 'mysql' ? "`transaction_pin`" : "\"transaction_pin\"";
            $placeholders[] = DB_TYPE === 'mysql' ? '?' : '$' . $param_index++;
            $plain_pin = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $values[] = $plain_pin;

            $sql = 'INSERT INTO ' . (DB_TYPE === 'mysql' ? '`accounts`' : '"accounts"') . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $save = false;

            if (DB_TYPE === 'mysql') {
                $stmt = $this->conn->prepare($sql);
                $types = str_repeat('s', count($values)); // Assuming all are strings for simplicity
                $stmt->bind_param($types, ...$values);
                $save = $stmt->execute();
                $stmt->close();
            } else { // pgsql
                $save = pg_query_params($this->conn, $sql, $values);
            }

            if($save){
                return json_encode([
                    'status' => 'success', 
                    'msg' => 'Your application has been submitted for review. Your account number is: ' . $account_number
                ]);
            } else {
                $error = DB_TYPE === 'mysql' ? $this->conn->error : pg_last_error($this->conn);
                throw new Exception("Failed to save to database: " . $error);
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
        try {
            if (DB_TYPE === 'mysql') {
                if ($this->conn->connect_error) {
                    throw new Exception("Database connection lost.");
                }
            } else {
                if (pg_connection_status($this->conn) !== PGSQL_CONNECTION_OK) {
                    throw new Exception("Database connection lost.");
                }
            }

            extract($_POST);
            if(!isset($firebase_uid) || empty($firebase_uid)){
                return json_encode(['status' => 'failed', 'msg' => "Firebase User ID is required."]);
            }
            
            // Use prepared statement to prevent SQL injection
            $sql = DB_TYPE === 'mysql' ? "SELECT `id`, `transaction_pin`, `first_login_done`, `status` FROM `accounts` WHERE `firebase_uid` = ?" : 'SELECT "id", "transaction_pin", "first_login_done", "status" FROM "accounts" WHERE "firebase_uid" = $1';
            $account_data = null;

            if (DB_TYPE === 'mysql') {
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('s', $firebase_uid);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result && $result->num_rows > 0) {
                    $account_data = $result->fetch_assoc();
                }
                $stmt->close();
            } else { // pgsql
                $result = pg_query_params($this->conn, $sql, array($firebase_uid));
                if($result && pg_num_rows($result) > 0) {
                    $account_data = pg_fetch_assoc($result);
                }
            }

            if($account_data) {
                return json_encode(['status' => 'success', 'data' => $account_data]);
            } else {
                return json_encode(['status' => 'failed', 'msg' => 'Account not found or not linked to Firebase UID.']);
            }
        } catch (Exception $e) {
            error_log("Error in Account.php::get_account_details_for_login(): " . $e->getMessage());
            return json_encode(['status' => 'failed', 'msg' => 'Server Error: ' . $e->getMessage()]);
        }
    }

    public function update_first_login_status() {
        try {
            if (DB_TYPE === 'mysql') {
                if ($this->conn->connect_error) {
                    throw new Exception("Database connection lost.");
                }
            } else {
                if (pg_connection_status($this->conn) !== PGSQL_CONNECTION_OK) {
                    throw new Exception("Database connection lost.");
                }
            }
            
            extract($_POST);
            if(!isset($account_id) || empty($account_id)){
                return json_encode(['status' => 'failed', 'msg' => "Account ID is required."]);
            }

            $sql = DB_TYPE === 'mysql' ? "UPDATE `accounts` SET `first_login_done` = 1 WHERE `id` = ?" : 'UPDATE "accounts" SET "first_login_done" = 1 WHERE "id" = $1';
            $update = false;

            if (DB_TYPE === 'mysql') {
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('i', $account_id);
                $update = $stmt->execute();
                $stmt->close();
            } else { // pgsql
                $update = pg_query_params($this->conn, $sql, array($account_id));
            }

            if($update){
                return json_encode(['status' => 'success', 'msg' => 'First login status updated.']);
            } else {
                $error = DB_TYPE === 'mysql' ? $this->conn->error : pg_last_error($this->conn);
                throw new Exception("Failed to update first login status: " . $error);
            }
        } catch (Exception $e) {
            error_log("Error in Account.php::update_first_login_status(): " . $e->getMessage());
            return json_encode(['status' => 'failed', 'msg' => 'Server Error: ' . $e->getMessage()]);
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
