<?php
require_once(__DIR__ . '/../config.php'); // Adjust path if necessary
require_once(__DIR__ . '/DBConnection.php'); // Corrected path for DBConnection in the same directory

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
        extract($_POST);

        // Required fields from the form
        $required_fields = ['firstname', 'lastname', 'address', 'marital_status', 'gender', 'phone_number', 'date_of_birth', 'id_type', 'id_number', 'email'];
        foreach($required_fields as $field){
            if(!isset($$field) || empty($$field)){
                return json_encode(['status' => 'failed', 'msg' => ucfirst(str_replace('_', ' ', $field)) . " is required."]);
            }
        }
        
        // Ensure firebase_uid is present if using Firebase registration
        if(!isset($firebase_uid) || empty($firebase_uid)){
            return json_encode(['status' => 'failed', 'msg' => "Firebase User ID is required."]);
        }

        // Check if email already exists in accounts table (for your local data)
        $chk = $this->conn->query("SELECT * FROM `accounts` WHERE `email` = '{$email}'")->num_rows;
        if($chk > 0){
            return json_encode(['status' => 'failed', 'msg' => 'Email already exists in our records.']);
        }

        // Generate a unique account number
        $account_number = '';
        while(true){
            $account_number = sprintf("%'.010d", mt_rand(0, 9999999999));
            $chk = $this->conn->query("SELECT * FROM `accounts` WHERE `account_number` = '{$account_number}'")->num_rows;
            if($chk <= 0) break;
        }

        $data = "";
        foreach($_POST as $k => $v){
            // Do NOT save the plain password or confirm_password in MySQL, Firebase handles it.
            // We are using firebase_uid for linking
            if(!in_array($k, array('password', 'confirm_password'))){
                if(!empty($data)) $data .= ", ";
                $data .= " `{$k}` = '". $this->conn->real_escape_string($v) ."' ";
            }
        }

        // Add account_number and set default balance/status
        if(!empty($data)) $data .= ", ";
        $data .= " `account_number` = '{$account_number}' ";
        
        if(!empty($data)) $data .= ", ";
        $data .= " `balance` = 0 "; // Default balance
        
        if(!empty($data)) $data .= ", ";
       $data .= " `status` = 'Pending' "; // Set status to 'Pending' for new accounts
        
        if(!empty($data)) $data .= ", ";
        $data .= " `login_type` = 2 "; // Set login_type to 2 for client accounts

        // Generate and store the 5-digit transaction PIN in PLAIN TEXT
        $plain_pin = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT); // Generate a 5-digit number
        if(!empty($data)) $data .= ", ";
        $data .= " `transaction_pin` = '". $this->conn->real_escape_string($plain_pin) ."' ";

        // Set first_login_done to 0 for new accounts.
        // This flag will trigger the *actual PIN display* on the first successful login to an activated account.
        if(!empty($data)) $data .= ", ";
        $data .= " `first_login_done` = 0 ";


        $sql = "INSERT INTO `accounts` SET {$data}";
        $save = $this->conn->query($sql);

        if($save){
            $account_id = $this->conn->insert_id; // Get the ID of the newly inserted account
            return json_encode(array(
                'status' => 'success', 
                'msg' => 'Your application has been submitted for review. You will receive an email once your account is approved. Your account number is: ' . $account_number,
                // 'transaction_pin' => $plain_pin, // <--- REMOVED: Do NOT return PIN to client at registration
                'account_id' => $account_id // Keep account ID for updating first_login_done later
            ));
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to submit account application to database: ' . $this->conn->error;
            error_log("DB Error in save_account: " . $this->conn->error);
            return json_encode($resp);
        }
    }

    /**
     * Fetches user account details, including first_login_done and transaction_pin.
     * This will be called by internet_banking.php.
     */
    public function get_account_details_for_login() {
        extract($_POST);
        if(!isset($firebase_uid) || empty($firebase_uid)){
            return json_encode(['status' => 'failed', 'msg' => "Firebase User ID is required."]);
        }
        $firebase_uid = $this->conn->real_escape_string($firebase_uid);
        $sql = "SELECT id, transaction_pin, first_login_done, status FROM `accounts` WHERE `firebase_uid` = '{$firebase_uid}'";
        $result = $this->conn->query($sql);

        if($result && $result->num_rows > 0) {
            $account_data = $result->fetch_assoc();
            return json_encode(['status' => 'success', 'data' => $account_data]);
        } else {
            return json_encode(['status' => 'failed', 'msg' => 'Account not found or not linked to Firebase UID.']);
        }
    }

    /**
     * Updates the first_login_done status for a given account.
     * This will be called by internet_banking.php after PIN acknowledgment.
     */
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
$account = new Account(); // Instantiate the Account class
switch ($action) {
    case 'save_account':
        echo $account->save_account();
        break;
    case 'get_account_details_for_login': // NEW CASE: To fetch account details on login
        echo $account->get_account_details_for_login();
        break;
    case 'update_first_login_status':
        echo $account->update_first_login_status();
        break;
    default:
        echo "Invalid Action"; // Or some default response
        break;
}