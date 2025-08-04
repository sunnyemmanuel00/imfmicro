<?php
// Ensure Composer's autoloader is available at the root level (handled by index.php router)
// For standalone testing or direct access, it might be needed, but for router-based access, index.php handles it.
// require_once(__DIR__ . '/../vendor/autoload.php'); 

// Ensure DBConnection and config.php are loaded.
// DBConnection implicitly loads initialize.php which defines base_url and DB constants.
require_once(__DIR__ . '/DBConnection.php'); 
require_once(__DIR__ . '/../config.php'); // For $_settings object

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\InvalidToken;

// FIX: Master class MUST extend DBConnection to inherit the $conn object.
class Master extends DBConnection { 
    // The $conn property is inherited from DBConnection, no need to declare it here.
    public $settings;
    private $firebaseAuth;

    public function __construct() {
        // Call the parent (DBConnection) constructor first to establish $this->conn
        parent::__construct(); 

        global $_settings;
        $this->settings = $_settings;

        // Initialize Firebase Admin SDK
        try {
            $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
            if (!file_exists($serviceAccountPath)) {
                error_log("FATAL MASTER ERROR: Firebase Service Account JSON file not found at: " . $serviceAccountPath);
                $this->firebaseAuth = null;
                return; 
            }
            $factory = (new Factory)->withServiceAccount($serviceAccountPath);
            $this->firebaseAuth = $factory->createAuth();
        } catch (Throwable $e) {
            error_log("Firebase SDK Init Error in Master.php: " . $e->getMessage());
            $this->firebaseAuth = null;
        }
    }

    // =========================== MODIFICATION ===========================
    // This login() function is removed. It was duplicated and its logic
    // was confusing, and it is not needed anymore since client logins
    // are handled by Firebase.
    //
    // The new login logic for admins is in Login.php, and the router
    // will now correctly direct the API call there.
    // ========================= END OF MODIFICATION ======================

    function save_account(){
        extract($_POST);
        $resp = ['status' => 'failed', 'msg' => 'An unknown error occurred.'];
        
        $account_data_parts = [];
        foreach($_POST as $k =>$v){
           if(!in_array($k,['id','password']) && !is_numeric($k)){
                if(!empty($account_data_parts)) $account_data_parts[] = ",";
                $account_data_parts[] = " `{$k}`='{$this->conn->real_escape_string(trim($v))}' ";
            }
        }
        $account_data = implode('', $account_data_parts);

        if(empty($id)){
            // ========== CREATE NEW CLIENT ACCOUNT ==========
            if(empty($password) || empty($email)){ // Removed transaction_pin from required for Firebase only
                return json_encode(['status' => 'failed', 'msg' => 'Email and Password are required for new accounts.']);
            }
            if($this->conn->query("SELECT id FROM `accounts` WHERE email = '{$this->conn->real_escape_string($email)}'")->num_rows > 0){
                return json_encode(['status' => 'failed', 'msg' => 'This email address is already registered.']);
            }

            $this->conn->begin_transaction();
            try {
                // Step 1: Create user in Firebase
                if ($this->firebaseAuth === null) throw new Exception("Firebase service is not available. Check configuration.");
                $userProperties = [
                    'email' => $email,
                    'emailVerified' => true,
                    'password' => $password,
                    'disabled' => false,
                ];
                $createdUser = $this->firebaseAuth->createUser($userProperties);
                
                // Generate and store the 5-digit transaction PIN in PLAIN TEXT
                $plain_pin = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT); // Generate a 5-digit number

                // Step 2: Insert into local `accounts` table
                $account_data .= ", `firebase_uid` = '{$createdUser->uid}'";
                $account_data .= ", `transaction_pin` = '{$this->conn->real_escape_string($plain_pin)}'"; // Add transaction_pin here
                $account_data .= ", `password` = '".md5($password)."'";
                $account_data .= ", `login_type` = 2 ";
                $account_data .= ", `balance` = 0 "; // Default balance
                $account_data .= ", `status` = 'Pending' "; // Set status to 'Pending' for new accounts
                $account_data .= ", `first_login_done` = 0 "; // Set first_login_done to 0

                // Generate a unique account number
                $account_number = '';
                while(true){
                    $account_number = sprintf("%'.010d", mt_rand(0, 9999999999));
                    $chk = $this->conn->query("SELECT * FROM `accounts` WHERE `account_number` = '{$account_number}'")->num_rows;
                    if($chk <= 0) break;
                }
                $account_data .= ", `account_number` = '{$account_number}' ";

                $sql = "INSERT INTO `accounts` set {$account_data} ";
                $save = $this->conn->query($sql);
                if(!$save) throw new Exception($this->conn->error);

                $this->conn->commit();
                $resp['status'] = 'success';
                $this->settings->set_flashdata('success',"New Account has been created successfully.");
            } catch (Exception $e) {
                $this->conn->rollback();
                $resp['msg'] = "Error: " . $e->getMessage();
                error_log("Master::save_account failed: " . $e->getMessage()); 
            }
        } else {
            // ========== UPDATE EXISTING CLIENT ACCOUNT ==========
            $sql = "UPDATE `accounts` set {$account_data} where id = '{$id}' ";
            $save = $this->conn->query($sql);
            if($save){
                $resp['status'] = 'success';
                $this->settings->set_flashdata('success',"Account details have been updated successfully.");

                if(isset($password) && !empty($password) && $this->firebaseAuth !== null){
                    $uid_qry = $this->conn->query("SELECT firebase_uid FROM `accounts` WHERE id = '{$id}'");
                    if($uid_qry->num_rows > 0){
                        $firebase_uid = $uid_qry->fetch_array()[0];
                        if(!empty($firebase_uid)){
                            try {
                                $this->firebaseAuth->changeUserPassword($firebase_uid, $password);
                                $this->conn->query("UPDATE `accounts` set `password` = '".md5($password)."' where id = '{$id}'");
                            } catch (Exception $e) {
                                error_log("Firebase password update failed for UID {$firebase_uid}: " . $e->getMessage());
                            }
                        }
                    }
                }
            } else {
                $resp['status'] = 'failed';
                $resp['msg'] = "An error occurred while saving the details.";
            }
        }
        return json_encode($resp);
    }
    
    function deposit() {
        extract($_POST);
        $resp = array('status' => 'failed', 'msg' => '');

        $account_id = $this->settings->userdata('account_id');
        $amount = floatval($amount);
        $entered_pin = $transaction_pin;

        if ($amount <= 0) {
            $resp['msg'] = "Deposit amount must be greater than zero.";
            return json_encode($resp);
        }
        if (empty($account_id)) {
            $resp['msg'] = "Account ID not found in session. Please re-login.";
            return json_encode($resp);
        }

        $this->conn->begin_transaction();
        try {
            $stmt_acc = $this->conn->prepare("SELECT balance, transaction_pin, account_number FROM `accounts` WHERE id = ? FOR UPDATE");
            if ($stmt_acc === false) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt_acc->bind_param("i", $account_id);
            $stmt_acc->execute();
            $acc_qry = $stmt_acc->get_result();

            if ($acc_qry->num_rows === 0) {
                throw new Exception("Account not found.");
            }
            $account_data = $acc_qry->fetch_assoc();
            $current_balance = $account_data['balance'];
            $stored_pin = $account_data['transaction_pin'];
            $account_number = $account_data['account_number'];
            $stmt_acc->close();

            if (trim($entered_pin) != trim($stored_pin)) {
                throw new Exception("Invalid Transaction PIN.");
            }

            $new_balance = $current_balance + $amount;
            $update_stmt = $this->conn->prepare("UPDATE `accounts` SET `balance` = ? WHERE `id` = ?");
            if ($update_stmt === false) {
                throw new Exception("Prepare failed to update account balance: " . $this->conn->error);
            }
            $update_stmt->bind_param("di", $new_balance, $account_id);
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update account balance: " . $update_stmt->error);
            }
            $update_stmt->close();

            $transaction_code = $this->settings->info('short_name') . '-' . date('Ymd-His') . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
            $remarks = "Cash Deposit";
            $status = "completed";
            $type = 1;
            $specific_transaction_type = 'cash_deposit';
            $meta_data = json_encode(['balance_before' => $current_balance]);

            $insert_stmt = $this->conn->prepare("INSERT INTO `transactions` (`account_id`, `type`, `amount`, `remarks`, `transaction_code`, `sender_account_number`, `receiver_account_number`, `status`, `transaction_type`, `meta_data`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($insert_stmt === false) {
                throw new Exception("Prepare failed to record transaction: " . $this->conn->error);
            }
            $insert_stmt->bind_param("iissssssis", $account_id, $type, $amount, $remarks, $transaction_code, $account_number, $account_number, $status, $specific_transaction_type, $meta_data);
            if (!$insert_stmt->execute()) {
                throw new Exception("Failed to record transaction: " . $insert_stmt->error);
            }
            $insert_stmt->close();

            $this->conn->commit();
            $this->settings->set_userdata('balance', $new_balance);
            $resp['status'] = 'success';
            $resp['msg'] = 'Deposit successful. New balance: ' . number_format($new_balance, 2);
            $this->settings->set_flashdata('success', 'Deposit successful.');

        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            error_log("Deposit failed: " . $e->getMessage());
        }
        return json_encode($resp);
    }

    function withdraw() {
        extract($_POST);
        $resp = array('status' => 'failed', 'msg' => '');

        $account_id = $this->settings->userdata('account_id');
        $amount = floatval($amount);
        $entered_pin = $transaction_pin;

        if ($amount <= 0) {
            $resp['msg'] = "Withdrawal amount must be greater than zero.";
            return json_encode($resp);
        }
        if (empty($account_id)) {
            $resp['msg'] = "Account ID not found in session. Please re-login.";
            return json_encode($resp);
        }

        $this->conn->begin_transaction();
        try {
            $stmt_acc = $this->conn->prepare("SELECT balance, transaction_pin, account_number FROM `accounts` WHERE id = ? FOR UPDATE");
            if ($stmt_acc === false) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt_acc->bind_param("i", $account_id);
            $stmt_acc->execute();
            $acc_qry = $stmt_acc->get_result();
            if ($acc_qry->num_rows === 0) {
                throw new Exception("Account not found.");
            }
            $account_data = $acc_qry->fetch_assoc();
            $current_balance = $account_data['balance'];
            $stored_pin = $account_data['transaction_pin'];
            $account_number = $account_data['account_number'];
            $stmt_acc->close();

            if (trim($entered_pin) != trim($stored_pin)) {
                throw new Exception("Invalid Transaction PIN.");
            }

            if ($current_balance < $amount) {
                throw new Exception("Insufficient funds.");
            }

            $new_balance = $current_balance - $amount;
            $update_stmt = $this->conn->prepare("UPDATE `accounts` SET `balance` = ? WHERE `id` = ?");
            if ($update_stmt === false) {
                throw new Exception("Prepare failed to update account balance: " . $this->conn->error);
            }
            $update_stmt->bind_param("di", $new_balance, $account_id);
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update account balance: " . $update_stmt->error);
            }
            $update_stmt->close();

            $transaction_code = $this->settings->info('short_name') . '-' . date('Ymd-His') . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
            $remarks = "Cash Withdrawal";
            $status = "completed";
            $type = 2;
            $specific_transaction_type = 'cash_withdrawal';
            $meta_data = json_encode(['balance_before' => $current_balance]);

            $insert_stmt = $this->conn->prepare("INSERT INTO `transactions` (`account_id`, `type`, `amount`, `remarks`, `transaction_code`, `sender_account_number`, `receiver_account_number`, `status`, `transaction_type`, `meta_data`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($insert_stmt === false) {
                throw new Exception("Prepare failed to record transaction: " . $this->conn->error);
            }
            $insert_stmt->bind_param("iissssssis", $account_id, $type, $amount, $remarks, $transaction_code, $account_number, $account_number, $status, $specific_transaction_type, $meta_data);
            if (!$insert_stmt->execute()) {
                throw new Exception("Failed to record transaction: " . $insert_stmt->error);
            }
            $insert_stmt->close();

            $this->conn->commit();
            $this->settings->set_userdata('balance', $new_balance);
            $resp['status'] = 'success';
            $resp['msg'] = 'Withdrawal successful. New balance: ' . number_format($new_balance, 2);
            $this->settings->set_flashdata('success', 'Withdrawal successful.');

        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            error_log("Withdrawal failed: " . $e->getMessage());
        }
        return json_encode($resp);
    }

    function get_internal_account_details_for_transfer() {
        extract($_POST);
        $resp = array('status' => 'failed', 'msg' => '', 'data' => null);

        $sender_account_id = $this->settings->userdata('account_id');

        if (empty($account_number)) {
            $resp['msg'] = "Account number is required.";
            return json_encode($resp);
        }

        $sender_account_number = $this->settings->userdata('account_number');
        if ($account_number == $sender_account_number) {
            $resp['msg'] = "Cannot transfer to your own account.";
            return json_encode($resp);
        }

        $stmt = $this->conn->prepare("SELECT CONCAT(firstname, ' ', COALESCE(middlename, ''), IF(middlename IS NOT NULL AND middlename != '', ' ', ''), lastname) as account_holder_name, account_number FROM `accounts` WHERE account_number = ?");
        if ($stmt === false) {
            $resp['msg'] = "Prepare failed: " . $this->conn->error;
            return json_encode($resp);
        }
        $stmt->bind_param("s", $account_number);
        $stmt->execute();
        $result = $stmt->get_result();


        if ($result->num_rows > 0) {
            $resp['data'] = $result->fetch_assoc();
            $resp['status'] = 'success';
            $resp['msg'] = 'Account details fetched successfully.';
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'No internal account found with this number.';
        }
        $stmt->close();
        return json_encode($resp);
    }

    function transfer_internal() {
        extract($_POST);
        $resp = array('status' => 'failed', 'msg' => '');

        $sender_account_id = $this->settings->userdata('account_id');
        $sender_account_number = $this->settings->userdata('account_number');
        $sender_fullname = $this->settings->userdata('fullname');
        $transfer_amount = floatval($amount);
        $entered_pin = $transaction_pin;
        $recipient_account_number_str = $recipient_account_number;
        $narration = $narration ?? '';

        if ($transfer_amount <= 0) {
            $resp['msg'] = "Transfer amount must be greater than zero.";
            return json_encode($resp);
        }
        if (empty($sender_account_id)) {
            $resp['msg'] = "Sender account ID not found in session. Please re-login.";
            return json_encode($resp);
        }
        if (empty($recipient_account_number_str)) {
            $resp['msg'] = "Recipient account number is required.";
            return json_encode($resp);
        }
        if ($sender_account_number == $recipient_account_number_str) {
            $resp['msg'] = "You cannot transfer to your own account.";
            return json_encode($resp);
        }

        $this->conn->begin_transaction();
        try {
            $stmt_sender = $this->conn->prepare("SELECT balance, transaction_pin, firstname, middlename, lastname FROM `accounts` WHERE id = ? FOR UPDATE");
            if ($stmt_sender === false) {
                throw new Exception("Prepare failed to fetch sender account: " . $this->conn->error);
            }
            $stmt_sender->bind_param("i", $sender_account_id);
            $stmt_sender->execute();
            $sender_qry = $stmt_sender->get_result();


            if ($sender_qry->num_rows === 0) {
                throw new Exception("Sender account not found.");
            }
            $sender_data = $sender_qry->fetch_assoc();
            $sender_current_balance = $sender_data['balance'];
            $sender_stored_pin = $sender_data['transaction_pin'];
            $sender_account_holder_name = trim($sender_data['firstname'] . ' ' . (isset($sender_data['middlename']) && !empty($sender_data['middlename']) ? $sender_data['middlename'] . ' ' : '') . $sender_data['lastname']);
            $stmt_sender->close();

            if (trim($entered_pin) != trim($sender_stored_pin)) {
                throw new Exception("Invalid Transaction PIN.");
            }


            if ($sender_current_balance < $transfer_amount) {
                throw new Exception("Insufficient funds for transfer.");
            }


            $stmt_receiver = $this->conn->prepare("SELECT id, balance, firstname, middlename, lastname FROM `accounts` WHERE account_number = ? FOR UPDATE");
            if ($stmt_receiver === false) {
                throw new Exception("Prepare failed to fetch receiver account: " . $this->conn->error);
            }
            $stmt_receiver->bind_param("s", $recipient_account_number_str);
            $stmt_receiver->execute();
            $receiver_qry = $stmt_receiver->get_result();


            if ($receiver_qry->num_rows === 0) {
                throw new Exception("Recipient account number does not exist.");
            }
            $receiver_data = $receiver_qry->fetch_assoc();
            $receiver_account_id = $receiver_data['id'];
            $receiver_current_balance = $receiver_data['balance'];
            $receiver_account_holder_name = trim($receiver_data['firstname'] . ' ' . (isset($receiver_data['middlename']) && !empty($receiver_data['middlename']) ? $receiver_data['middlename'] . ' ' : '') . $receiver_data['lastname']);
            $stmt_receiver->close();


            $new_sender_balance = $sender_current_balance - $transfer_amount;
            $new_receiver_balance = $receiver_current_balance + $transfer_amount;


            $update_sender_stmt = $this->conn->prepare("UPDATE `accounts` SET `balance` = ? WHERE `id` = ?");
            if ($update_sender_stmt === false) {
                throw new Exception("Prepare failed to debit sender: " . $this->conn->error);
            }
            $update_sender_stmt->bind_param("di", $new_sender_balance, $sender_account_id);
            if (!$update_sender_stmt->execute()) {
                throw new Exception("Failed to debit sender account: " . $update_sender_stmt->error);
            }
            $update_sender_stmt->close();


            $update_receiver_stmt = $this->conn->prepare("UPDATE `accounts` SET `balance` = ? WHERE `id` = ?");
            if ($update_receiver_stmt === false) {
                throw new Exception("Prepare failed to credit receiver: " . $this->conn->error);
            }
            $update_receiver_stmt->bind_param("di", $new_receiver_balance, $receiver_account_id);
            if (!$update_receiver_stmt->execute()) {
                throw new Exception("Failed to credit receiver account: " . $update_receiver_stmt->error);
            }
            $update_receiver_stmt->close();


            $base_transaction_code = $this->settings->info('short_name') . '-' . date('Ymd-His') . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
            $sender_transaction_code = $base_transaction_code . '-S';
            $receiver_transaction_code = $base_transaction_code . '-R';


            $remarks_sender = "Transfer to " . $receiver_account_holder_name . " (Account: " . $recipient_account_number_str . ")";
            if (!empty($narration)) {
                $remarks_sender .= " - " . $narration;
            }
            $type_sender = 3;
            $specific_transaction_type_sender = 'internal_transfer_outgoing';
            $meta_data_sender = json_encode([
                'sender_balance_before' => $sender_current_balance,
                'receiver_account_number' => $recipient_account_number_str,
                'receiver_account_name' => $receiver_account_holder_name,
                'narration' => $narration
            ]);


            $insert_sender_txn_stmt = $this->conn->prepare("INSERT INTO `transactions` (`account_id`, `type`, `amount`, `remarks`, `transaction_code`, `sender_account_number`, `receiver_account_number`, `status`, `transaction_type`, `meta_data`) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', ?, ?)");
            if ($insert_sender_txn_stmt === false) {
                throw new Exception("Prepare failed to record sender transaction: " . $this->conn->error);
            }
            $insert_sender_txn_stmt->bind_param("iisssssis", $sender_account_id, $type_sender, $transfer_amount, $remarks_sender, $sender_transaction_code, $sender_account_number, $recipient_account_number_str, $specific_transaction_type_sender, $meta_data_sender);
            if (!$insert_sender_txn_stmt->execute()) {
                throw new Exception("Failed to record sender transaction: " . $insert_sender_txn_stmt->error);
            }
            $insert_sender_txn_stmt->close();


            $remarks_receiver = "Transfer from " . $sender_account_holder_name . " (Account: " . $sender_account_number . ")";
            if (!empty($narration)) {
                $remarks_receiver .= " - " . $narration;
            }
            $type_receiver = 1;
            $specific_transaction_type_receiver = 'internal_transfer_incoming';
            $meta_data_receiver = json_encode([
                'receiver_balance_before' => $receiver_current_balance,
                'sender_account_number' => $sender_account_number,
                'sender_account_name' => $sender_account_holder_name,
                'narration' => $narration
            ]);


            $insert_receiver_txn_stmt = $this->conn->prepare("INSERT INTO `transactions` (`account_id`, `type`, `amount`, `remarks`, `transaction_code`, `sender_account_number`, `receiver_account_number`, `status`, `transaction_type`, `meta_data`) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', ?, ?)");
            if ($insert_receiver_txn_stmt === false) {
                throw new Exception("Prepare failed to record receiver transaction: " . $this->conn->error);
            }
            $insert_receiver_txn_stmt->bind_param("iisssssis", $receiver_account_id, $type_receiver, $transfer_amount, $remarks_receiver, $receiver_transaction_code, $sender_account_number, $recipient_account_number_str, $specific_transaction_type_receiver, $meta_data_receiver);
            if (!$insert_receiver_txn_stmt->execute()) {
                throw new Exception("Failed to record receiver transaction: " . $insert_receiver_txn_stmt->error);
            }
            $insert_receiver_txn_stmt->close();


            $this->conn->commit();
            $this->settings->set_userdata('balance', $new_sender_balance);
            $resp['status'] = 'success';
            $resp['msg'] = 'Internal transfer successful. Your new balance: ' . number_format($new_sender_balance, 2);
            $this->settings->set_flashdata('success', 'Internal transfer successful.');


        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            error_log("Internal transfer failed: " . $e->getMessage());
        }
        return json_encode($resp);
    }

    function transfer_external() {
        extract($_POST);
        $resp = array('status' => 'failed', 'msg' => '');

        $sender_account_id = $this->settings->userdata('account_id');
        $sender_account_number = $this->settings->userdata('account_number');
        $sender_fullname = $this->settings->userdata('fullname');
        $transfer_amount = floatval($amount_external);
        $entered_pin = $transaction_pin;
        $recipient_bank_name = $recipient_bank_name;
        $recipient_account_number_external = $recipient_account_number_external;
        $recipient_account_name_external = $recipient_account_name_external;
        $swift_bic = $swift_bic ?? '';
        $routing_number = $routing_number ?? '';
        $iban = $iban ?? '';
        $beneficiary_address = $beneficiary_address ?? '';
        $beneficiary_phone = $beneficiary_phone ?? '';
        $narration_external = $narration_external ?? '';


        if ($transfer_amount <= 0) {
            $resp['msg'] = "Transfer amount must be greater than zero.";
            return json_encode($resp);
        }
        if (empty($sender_account_id)) {
            $resp['msg'] = "Sender account ID not found in session. Please re-login.";
            return json_encode($resp);
        }
        if (empty($recipient_bank_name) || empty($recipient_account_number_external) || empty($recipient_account_name_external)) {
            $resp['msg'] = "Recipient Bank Name, Account Number, and Account Name are required for external transfers.";
            return json_encode($resp);
        }


        $this->conn->begin_transaction();
        try {
            $stmt_sender = $this->conn->prepare("SELECT balance, transaction_pin FROM `accounts` WHERE id = ? FOR UPDATE");
            if ($stmt_sender === false) {
                throw new Exception("Prepare failed to fetch sender account: " . $this->conn->error);
            }
            $stmt_sender->bind_param("i", $sender_account_id);
            $stmt_sender->execute();
            $sender_qry = $stmt_sender->get_result();


            if ($sender_qry->num_rows === 0) {
                throw new Exception("Sender account not found.");
            }
            $sender_data = $sender_qry->fetch_assoc();
            $sender_current_balance = $sender_data['balance'];
            $sender_stored_pin = $sender_data['transaction_pin'];
            $stmt_sender->close();


            if (trim($entered_pin) != trim($sender_stored_pin)) {
                throw new Exception("Invalid Transaction PIN.");
            }


            if ($sender_current_balance < $transfer_amount) {
                throw new Exception("Insufficient funds for external transfer.");
            }


            $new_sender_balance = $sender_current_balance - $transfer_amount;
            $update_sender_stmt = $this->conn->prepare("UPDATE `accounts` SET `balance` = ? WHERE `id` = ?");
            if ($update_sender_stmt === false) {
                throw new Exception("Prepare failed to debit sender: " . $this->conn->error);
            }
            $update_sender_stmt->bind_param("di", $new_sender_balance, $sender_account_id);
            if (!$update_sender_stmt->execute()) {
                throw new Exception("Failed to debit sender account: " . $update_sender_stmt->error);
            }
            $update_sender_stmt->close();


            $transaction_code = $this->settings->info('short_name') . '-' . date('Ymd-His') . '-EXT-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);


            $remarks = "External Transfer Request to " . $recipient_account_name_external . " (" . $recipient_bank_name . ", Account: " . $recipient_account_number_external . ")";
            if (!empty($narration_external)) {
                $remarks .= " - " . $narration_external;
            }


            $meta_data = json_encode([
                'sender_account_number' => $sender_account_number,
                'sender_account_name' => $sender_fullname,
                'sender_balance_before' => $sender_current_balance,
                'recipient_bank_name' => $recipient_bank_name,
                'recipient_account_number' => $recipient_account_number_external,
                'recipient_account_name' => $recipient_account_name_external,
                'swift_bic' => $swift_bic,
                'routing_number' => $routing_number,
                'iban' => $iban,
                'beneficiary_address' => $beneficiary_address,
                'beneficiary_phone' => $beneficiary_phone,
                'narration' => $narration_external
            ]);


            $insert_txn_stmt = $this->conn->prepare("INSERT INTO `transactions` (`account_id`, `type`, `amount`, `remarks`, `transaction_code`, `sender_account_number`, `receiver_account_number`, `status`, `transaction_type`, `meta_data`) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'transfer_external_pending', ?)");
            if ($insert_txn_stmt === false) {
                throw new Exception("Prepare failed to record external transaction: " . $this->conn->error);
            }
            $type = 3;
            $insert_txn_stmt->bind_param("iissssss",
                $sender_account_id,
                $type,
                $transfer_amount,
                $remarks,
                $transaction_code,
                $sender_account_number,
                $recipient_account_number_external,
                $meta_data
            );
            if (!$insert_txn_stmt->execute()) {
                throw new Exception("Failed to record external transaction: " . $insert_txn_stmt->error);
            }
            $insert_txn_stmt->close();


            $this->conn->commit();
            $this->settings->set_userdata('balance', $new_sender_balance);
            $resp['status'] = 'success';
            $resp['msg'] = 'External transfer request submitted successfully. It is pending admin approval. Your new balance: ' . number_format($new_sender_balance, 2);
            $this->settings->set_flashdata('success', 'External transfer request submitted successfully.');


        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            error_log("External transfer failed: " . $e->getMessage());
        }
        return json_encode($resp);
    }


    function get_linked_accounts($user_id) {
        $resp = array('status' => 'failed', 'msg' => '', 'data' => []);
        if (empty($user_id)) {
            $resp['msg'] = "User ID is required to fetch linked accounts.";
            return json_encode($resp);
        }
        $stmt = $this->conn->prepare("SELECT * FROM `user_linked_accounts` WHERE user_id = ? ORDER BY `account_holder_name` ASC");
        if ($stmt === false) {
            $resp['msg'] = "Prepare failed: " . $this->conn->error;
            return json_encode($resp);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $resp['data'][] = $row;
            }
            $resp['status'] = 'success';
            $resp['msg'] = 'Linked accounts fetched successfully.';
        } else {
            $resp['status'] = 'success';
            $resp['msg'] = 'No linked accounts found.';
        }
        $stmt->close();
        return json_encode($resp);
    }
    
    function save_linked_account() {
        // Explicitly define variables from $_POST
        $id = $_POST['id'] ?? null;
        $account_label = $_POST['account_label'] ?? '';
        $account_number = $_POST['account_number'] ?? '';
        $account_holder_name = $_POST['account_holder_name'] ?? '';
        $bank_name = $_POST['bank_name'] ?? '';
        $swift_bic = $_POST['swift_bic'] ?? '';
        $routing_number = $_POST['routing_number'] ?? '';
        $iban = $_POST['iban'] ?? '';
        $beneficiary_address = $_POST['beneficiary_address'] ?? '';
        $beneficiary_phone = $_POST['beneficiary_phone'] ?? '';
        $account_type = $_POST['account_type'] ?? '';
        $link_type = $_POST['link_type'] ?? '';
        $transaction_pin = $_POST['transaction_pin'] ?? '';

        $resp = array('status' => 'failed', 'msg' => '');
        $user_id = $this->settings->userdata('id');
        $current_user_account_id = $this->settings->userdata('account_id');

        // --- Basic Validation ---
        if (empty($user_id)) {
            $resp['msg'] = "User not logged in. Please re-login.";
            return json_encode($resp);
        }
        if (empty($transaction_pin)) {
            $resp['msg'] = "The Transaction PIN is required.";
            return json_encode($resp);
        }
        if (empty($account_number) || empty($account_holder_name) || !isset($bank_name)) {
            $resp['msg'] = "All required fields must be filled.";
            return json_encode($resp);
        }


        // --- NEW SECURITY: PIN & Account Validation ---
        $check_internal_stmt = $this->conn->prepare("SELECT id, transaction_pin FROM `accounts` WHERE account_number = ?");
        $check_internal_stmt->bind_param("s", $account_number);
        $check_internal_stmt->execute();
        $internal_account_result = $check_internal_stmt->get_result();
        
        if ($internal_account_result->num_rows > 0) {
            // --- THIS IS AN INTERNAL ACCOUNT ---
            $is_internal_bank = 1;
            $bank_name = $this->settings->info('short_name'); # Corrected to use short_name for internal
            $internal_account_data = $internal_account_result->fetch_assoc();
            $owner_stored_pin = $internal_account_data['transaction_pin'];


            // Validate against the ACCOUNT OWNER'S PIN
            if (trim($transaction_pin) !== trim($owner_stored_pin)) {
                $resp['msg'] = "Invalid Account Owner's Transaction PIN.";
                return json_encode($resp);
            }
        } else {
            // --- THIS IS AN EXTERNAL ACCOUNT ---
            $is_internal_bank = 0;
            if(empty($bank_name) || trim($bank_name) == '0'){
                $resp['msg'] = "A valid external Bank Name is required.";
                return json_encode($resp);
            }


            // Validate against the LOGGED-IN USER'S PIN to authorize the action
            $stmt_user_pin = $this->conn->prepare("SELECT transaction_pin FROM `accounts` WHERE id = ?");
            $stmt_user_pin->bind_param("i", $current_user_account_id);
            $stmt_user_pin->execute();
            $user_pin_qry = $stmt_user_pin->get_result();
            if ($user_pin_qry->num_rows === 0) {
                $resp['msg'] = "Your user account could not be found for PIN validation.";
                return json_encode($resp);
            }
            $stored_user_pin = $user_pin_qry->fetch_assoc()['transaction_pin'];
            $stmt_user_pin->close();


            if (trim($transaction_pin) !== trim($stored_user_pin)) {
                $resp['msg'] = "Invalid Transaction PIN. Your PIN is required to add an external account.";
                return json_encode($resp);
            }
        }
        $check_internal_stmt->close();


        if ($is_internal_bank == 1 && $account_number == $this->settings->userdata('account_number')) {
            $resp['msg'] = "You cannot link your own primary account.";
            return json_encode($resp);
        }


        // --- Database Transaction ---
        $this->conn->begin_transaction();
        try {
            if (empty($id)) { // Insert (Add New)
                $stmt = $this->conn->prepare("INSERT INTO `user_linked_accounts` (`user_id`, `account_label`, `account_number`, `account_holder_name`, `bank_name`, `is_internal_bank`, `swift_bic`, `routing_number`, `iban`, `beneficiary_address`, `beneficiary_phone`, `account_type`, `link_type`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt === false) throw new Exception("Prepare failed (insert): " . $this->conn->error);
                $stmt->bind_param("issssisssssss", $user_id, $account_label, $account_number, $account_holder_name, $bank_name, $is_internal_bank, $swift_bic, $routing_number, $iban, $beneficiary_address, $beneficiary_phone, $account_type, $link_type);
            } else { // Update (Edit Existing)
                $stmt = $this->conn->prepare("UPDATE `user_linked_accounts` SET `account_label` = ?, `account_number` = ?, `account_holder_name` = ?, `bank_name` = ?, `is_internal_bank` = ?, `swift_bic` = ?, `routing_number` = ?, `iban` = ?, `beneficiary_address` = ?, `beneficiary_phone` = ?, `account_type` = ?, `link_type` = ? WHERE `id` = ? AND `user_id` = ?");
                if ($stmt === false) throw new Exception("Prepare failed (update): " . $this->conn->error);
                $stmt->bind_param("ssssisssssssii", $account_label, $account_number, $account_holder_name, $bank_name, $is_internal_bank, $swift_bic, $routing_number, $iban, $beneficiary_address, $beneficiary_phone, $account_type, $link_type, $id, $user_id);
            }
            
            $exec_success = $stmt->execute();
            $affected_rows = $stmt->affected_rows;


            if ($exec_success) {
                if ($affected_rows > 0) {
                    $this->conn->commit();
                    $resp['status'] = 'success';
                    $resp['msg'] = 'Linked account successfully ' . (empty($id) ? 'added' : 'updated') . '.';
                    $this->settings->set_flashdata('success', $resp['msg']);
                } else {
                    $this->conn->rollback();
                    $resp['status'] = 'failed';
                    $resp['msg'] = "No changes were detected.";
                }
            } else {
                throw new Exception("Failed to save linked account: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            error_log("Save linked account failed: " . $e->getMessage());
        }
        return json_encode($resp);
    }

    function delete_linked_account() {
        // --- START DEBUG LOGGING ---
        $log_file = __DIR__ . '/debug_log.txt';
        $log_data = "--- " . date('Y-m-d H:i:s') . " --- DELETE LINKED ACCOUNT ---\n";
        $log_data .= "RECEIVED POST Data: " . print_r($_POST, true) . "\n";
        // ---


        extract($_POST);
        $resp = array('status' => 'failed', 'msg' => '');
        $user_id = $this->settings->userdata('id');
        $account_id = $this->settings->userdata('account_id');


        // Log session data
        $log_data .= "Session Data: user_id='{$user_id}', account_id='{$account_id}'\n";
        
        if (empty($user_id)) {
            $resp['msg'] = "User not logged in. Please re-login.";
            file_put_contents($log_file, $log_data . "ERROR: " . $resp['msg'] . "\n\n", FILE_APPEND);
            return json_encode($resp);
        }
        if (empty($transaction_pin)) {
            $resp['msg'] = "Transaction PIN is required.";
            file_put_contents($log_file, $log_data . "ERROR: " . $resp['msg'] . "\n\n", FILE_APPEND);
            return json_encode($resp);
        }


        // Validate user's transaction PIN
        $stmt_user_pin = $this->conn->prepare("SELECT transaction_pin FROM `accounts` WHERE id = ?");
        $stmt_user_pin->bind_param("i", $account_id);
        $stmt_user_pin->execute();
        $user_pin_qry = $stmt_user_pin->get_result();
        
        if ($user_pin_qry->num_rows === 0) {
            $resp['msg'] = "User account not found for PIN validation (delete).";
            file_put_contents($log_file, $log_data . "ERROR: " . $resp['msg'] . "\n\n", FILE_APPEND);
            return json_encode($resp);
        }
        $stored_user_pin = $user_pin_qry->fetch_assoc()['transaction_pin'];
        $stmt_user_pin->close();


        $log_data .= "PIN Check: Entered='{$transaction_pin}', Stored='{$stored_user_pin}'\n";


        if (trim($transaction_pin) !== trim($stored_user_pin)) {
            $resp['msg'] = "Invalid Transaction PIN.";
            file_put_contents($log_file, $log_data . "ERROR: " . $resp['msg'] . "\n\n", FILE_APPEND);
            return json_encode($resp);
        }


        if (empty($id)) {
            $resp['msg'] = "Linked account ID is required.";
            file_put_contents($log_file, $log_data . "ERROR: " . $resp['msg'] . "\n\n", FILE_APPEND);
            return json_encode($resp);
        }
        
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("DELETE FROM `user_linked_accounts` WHERE id = ? AND user_id = ?");
            if ($stmt === false) {
                throw new Exception("Prepare failed (delete linked account): " . $this->conn->error);
            }
            $stmt->bind_param("ii", $id, $user_id);


            $log_data .= "Executing DELETE with id='{$id}' and user_id='{$user_id}'\n";


            if ($stmt->execute()) {
                $affected_rows = $stmt->affected_rows;
                $log_data .= "DELETE executed. Rows affected: " . $affected_rows . "\n";
                if ($affected_rows > 0) {
                    $this->conn->commit();
                    $resp['status'] = 'success';
                    $resp['msg'] = 'Linked account successfully deleted.';
                    $this->settings->set_flashdata('success', 'Linked account successfully deleted.');
                } else {
                    $this->conn->rollback();
                    $resp['status'] = 'failed';
                    $resp['msg'] = "Linked account not found or does not belong to you.";
                }
            } else {
                throw new Exception("Failed to delete linked account: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            $log_data .= "EXCEPTION CAUGHT: " . $e->getMessage() . "\n";
        }


        // --- Final Logging Step ---
        $log_data .= "Final Response: " . print_r($resp, true) . "\n\n";
        file_put_contents($log_file, $log_data, FILE_APPEND);
        // ---
        
        return json_encode($resp);
    }

    function get_account_details_by_number() {
    // Explicitly define variables from $_POST to avoid notices.
    $account_number = $_POST['account_number'] ?? '';


    $resp = array('status' => 'failed', 'msg' => '');


    if (empty($account_number)) {
        $resp['msg'] = "Account number is required.";
        return json_encode($resp);
    }


    // Select firstname, middlename, lastname and concatenate them, use system's bank name.
    $stmt = $this->conn->prepare("SELECT CONCAT(firstname, ' ', COALESCE(middlename, ''), IF(middlename IS NOT NULL AND middlename != '', ' ', ''), lastname) as account_holder_name, account_number, '" . $this->settings->info('name') . "' as bank_name FROM `accounts` WHERE account_number = ?");
    if ($stmt === false) {
        $resp['msg'] = "Prepare failed: " . $this->conn->error;
        return json_encode($resp);
    }
    $stmt->bind_param("s", $account_number);
    $stmt->execute();
    $result = $stmt->get_result();


    if ($result->num_rows > 0) {
        $row_data = $result->fetch_assoc();
        // Assign data directly to $resp keys, not nested in 'data'
        $resp['account_holder_name'] = $row_data['account_holder_name'];
        $resp['bank_name'] = $row_data['bank_name']; 
        $resp['status'] = 'success';
        $resp['msg'] = 'Account details fetched successfully.';
    } else {
        $resp['status'] = 'failed';
        $resp['msg'] = 'No internal account found with this number.';
    }
    $stmt->close();
    return json_encode($resp);
}

function deposit_from_linked_account() {
        error_log("Master::deposit_from_linked_account: Function called.");
        extract($_POST);
        $resp = array('status' => 'failed', 'msg' => '');

        $primary_account_id = $this->settings->userdata('account_id');
        $deposit_amount = floatval($amount_deposit);
        $entered_transaction_pin = $transaction_pin;
        $source_linked_account_id = $_POST['source_linked_account_id'] ?? null; // Ensure this is extracted

        error_log("Master::deposit_from_linked_account: primary_account_id: {$primary_account_id}, deposit_amount: {$deposit_amount}, source_linked_account_id: {$source_linked_account_id}");

        if ($deposit_amount <= 0) {
            $resp['msg'] = "Deposit amount must be greater than zero.";
            error_log("Master::deposit_from_linked_account: Invalid deposit amount.");
            return json_encode($resp);
        }
        if (empty($primary_account_id)) {
            $resp['msg'] = "Primary account details not found in session. Please re-login.";
            error_log("Master::deposit_from_linked_account: Primary account ID missing from session.");
            return json_encode($resp);
        }
        if (empty($source_linked_account_id)) { // Added check for linked account ID
            $resp['msg'] = "Source linked account not selected.";
            error_log("Master::deposit_from_linked_account: Source linked account ID missing.");
            return json_encode($resp);
        }

        $this->conn->begin_transaction();
        try {
            error_log("Master::deposit_from_linked_account: Transaction started.");

            // Step 1: Validate Primary Account PIN
            $stmt_primary_pin = $this->conn->prepare("SELECT transaction_pin FROM `accounts` WHERE id = ?");
            if ($stmt_primary_pin === false) {
                throw new Exception("Prepare failed to fetch primary account PIN: " . $this->conn->error);
            }
            $stmt_primary_pin->bind_param("i", $primary_account_id);
            $stmt_primary_pin->execute();
            $primary_pin_qry = $stmt_primary_pin->get_result();
            if ($primary_pin_qry->num_rows === 0) {
                throw new Exception("Primary account not found for PIN validation.");
            }
            $primary_account_stored_pin = $primary_pin_qry->fetch_assoc()['transaction_pin'];
            $stmt_primary_pin->close();
            error_log("Master::deposit_from_linked_account: Primary PIN fetched. Stored: {$primary_account_stored_pin}, Entered: {$entered_transaction_pin}");
            
            if (trim($entered_transaction_pin) != trim($primary_account_stored_pin)) {
                throw new Exception("Invalid Transaction PIN.");
            }
            error_log("Master::deposit_from_linked_account: PIN validated successfully.");

            // Step 2: Fetch Linked Account Details
            $user_id_for_linked = $this->settings->userdata('id');
            if (empty($user_id_for_linked)) {
                throw new Exception("User ID not found in session for linked account validation.");
            }
            $stmt_linked_acc = $this->conn->prepare("SELECT account_number, is_internal_bank, account_holder_name, bank_name FROM `user_linked_accounts` WHERE id = ? AND user_id = ?");
            if ($stmt_linked_acc === false) {
                throw new Exception("Prepare failed to fetch linked account details: " . $this->conn->error);
            }
            $stmt_linked_acc->bind_param("ii", $source_linked_account_id, $user_id_for_linked);
            $stmt_linked_acc->execute();
            $linked_acc_qry = $stmt_linked_acc->get_result();
            if ($linked_acc_qry->num_rows === 0) {
                throw new Exception("Source linked account not found or does not belong to you.");
            }
            $linked_account_details = $linked_acc_qry->fetch_assoc();
            $is_internal_linked_account = $linked_account_details['is_internal_bank'];
            $linked_account_number_for_pin_check = $linked_account_details['account_number'];
            $primary_account_number = $this->settings->userdata('account_number'); // Ensure primary_account_number is set for meta_data and insert stmt
            $stmt_linked_acc->close();
            error_log("Master::deposit_from_linked_account: Linked account details fetched.");

            // Step 3: Fetch Primary Account Balance
            $stmt_primary_balance = $this->conn->prepare("SELECT balance FROM `accounts` WHERE id = ? FOR UPDATE");
            if ($stmt_primary_balance === false) {
                throw new Exception("Prepare failed to fetch primary account balance: " . $this->conn->error);
            }
            $stmt_primary_balance->bind_param("i", $primary_account_id);
            $stmt_primary_balance->execute();
            $primary_account_balance_qry = $stmt_primary_balance->get_result();
            if ($primary_account_balance_qry->num_rows === 0) {
                throw new Exception("Primary account balance could not be retrieved.");
            }
            $primary_account_current_balance = $primary_account_balance_qry->fetch_assoc()['balance'];
            $stmt_primary_balance->close();
            error_log("Master::deposit_from_linked_account: Primary account balance fetched: {$primary_account_current_balance}.");
            
            $base_transaction_code = $this->settings->info('short_name') . '-' . date('Ymd-His') . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
            $new_primary_balance = $primary_account_current_balance + $deposit_amount;
            
            $meta_data = json_encode([
                'source_linked_account_id' => $source_linked_account_id,
                'source_account_number_linked' => $linked_account_details['account_number'],
                'source_account_holder_name_linked' => $linked_account_details['account_holder_name'],
                'source_bank_name_linked' => $linked_account_details['bank_name'],
                'primary_account_balance_before' => $primary_account_current_balance
            ]);
            error_log("Master::deposit_from_linked_account: Meta data prepared: " . $meta_data);

            if ($is_internal_linked_account == 1) {
                error_log("Master::deposit_from_linked_account: Handling internal linked account deposit.");
                // Fetch internal source account balance
                $stmt_source_internal_acc = $this->conn->prepare("SELECT id, balance FROM `accounts` WHERE account_number = ? FOR UPDATE");
                if ($stmt_source_internal_acc === false) {
                    throw new Exception("Prepare failed to fetch internal source account: " . $this->conn->error);
                }
                $stmt_source_internal_acc->bind_param("s", $linked_account_number_for_pin_check);
                $stmt_source_internal_acc->execute();
                $source_internal_acc_qry = $stmt_source_internal_acc->get_result();
                if ($source_internal_acc_qry->num_rows === 0) {
                    throw new Exception("Internal source account number does not exist in the bank's system.");
                }
                $source_internal_acc_data = $source_internal_acc_qry->fetch_assoc();
                $source_internal_acc_id = $source_internal_acc_data['id'];
                $source_internal_acc_balance = $source_internal_acc_data['balance'];
                $stmt_source_internal_acc->close();
                error_log("Master::deposit_from_linked_account: Internal source account balance: {$source_internal_acc_balance}");

                if ($source_internal_acc_balance < $deposit_amount) {
                    throw new Exception("Insufficient funds in the linked internal account.");
                }
                
                // Debit internal source account
                $new_source_internal_balance = $source_internal_acc_balance - $deposit_amount;
                $update_source_stmt = $this->conn->prepare("UPDATE `accounts` SET `balance` = ? WHERE `id` = ?");
                if ($update_source_stmt === false) {
                    throw new Exception("Prepare failed to debit internal source account: " . $this->conn->error);
                }
                $update_source_stmt->bind_param("di", $new_source_internal_balance, $source_internal_acc_id);
                if (!$update_source_stmt->execute()) {
                    throw new Exception("Failed to debit internal source account: " . $update_source_stmt->error);
                }
                $update_source_stmt->close();
                error_log("Master::deposit_from_linked_account: Internal source account debited.");

                // Credit primary account
                $update_primary_stmt = $this->conn->prepare("UPDATE `accounts` SET `balance` = ? WHERE `id` = ?");
                if ($update_primary_stmt === false) {
                    throw new Exception("Prepare failed to credit primary account: " . $this->conn->error);
                }
                $update_primary_stmt->bind_param("di", $new_primary_balance, $primary_account_id);
                if (!$update_primary_stmt->execute()) {
                    throw new Exception("Failed to credit primary account: " . $update_primary_stmt->error);
                }
                $update_primary_stmt->close();
                error_log("Master::deposit_from_linked_account: Primary account credited.");

                // Record primary account transaction
                $remarks_primary = "Deposit from Internal Linked Account ({$linked_account_details['account_number']}) - {$linked_account_details['account_holder_name']}";
                $receiver_txn_code = $base_transaction_code . '-R';
                $insert_primary_txn_stmt = $this->conn->prepare("INSERT INTO `transactions` (`account_id`, `type`, `amount`, `remarks`, `transaction_code`, `sender_account_number`, `receiver_account_number`, `status`, `transaction_type`, `linked_account_id`, `meta_data`) VALUES (?, 1, ?, ?, ?, ?, ?, 'completed', 'deposit_internal_completed', ?, ?)");
                if (!$insert_primary_txn_stmt) { throw new Exception("Prepare failed for primary txn: " . $this->conn->error); } // Added debug
                $insert_primary_txn_stmt->bind_param("idssssis", $primary_account_id, $deposit_amount, $remarks_primary, $receiver_txn_code, $linked_account_details['account_number'], $primary_account_number, $source_linked_account_id, $meta_data);
                if (!$insert_primary_txn_stmt->execute()) { throw new Exception("Failed to record primary account transaction: " . $insert_primary_txn_stmt->error); }
                $insert_primary_txn_stmt->close();
                error_log("Master::deposit_from_linked_account: Primary transaction recorded.");

                // Record source account transaction
                $remarks_source = "Transfer to Linked Account ({$primary_account_number}) - {$this->settings->userdata('fullname')}";
                $sender_txn_code = $base_transaction_code . '-S';
                $insert_source_txn_stmt = $this->conn->prepare("INSERT INTO `transactions` (`account_id`, `type`, `amount`, `remarks`, `transaction_code`, `sender_account_number`, `receiver_account_number`, `status`, `transaction_type`, `linked_account_id`, `meta_data`) VALUES (?, 3, ?, ?, ?, ?, ?, 'completed', 'internal_pull_outgoing', ?, ?)");
                if (!$insert_source_txn_stmt) { throw new Exception("Prepare failed for source txn: " . $this->conn->error); } // Added debug
                $insert_source_txn_stmt->bind_param("idssssis", $source_internal_acc_id, $deposit_amount, $remarks_source, $sender_txn_code, $linked_account_details['account_number'], $primary_account_number, $source_linked_account_id, $meta_data);
                if (!$insert_source_txn_stmt->execute()) { throw new Exception("Failed to record internal source account transaction: " . $insert_source_txn_stmt->error); }
                $insert_source_txn_stmt->close();
                error_log("Master::deposit_from_linked_account: Source transaction recorded.");
                
                $this->settings->set_userdata('balance', $new_primary_balance);
                $this->settings->set_flashdata('success', 'Deposit from internal linked account successful.');
                error_log("Master::deposit_from_linked_account: Internal deposit complete.");
            } else {
                error_log("Master::deposit_from_linked_account: Handling external linked account deposit (pending).");
                // Record external deposit request
                $insert_stmt = $this->conn->prepare("INSERT INTO `transactions` (`account_id`, `type`, `amount`, `remarks`, `transaction_code`, `sender_account_number`, `receiver_account_number`, `status`, `transaction_type`, `linked_account_id`, `meta_data`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $remarks = "Deposit request from External Linked Account ({$linked_account_details['account_number']} - {$linked_account_details['bank_name']})";
                $status = 'pending';
                $specific_transaction_type = 'deposit_external_pending';
                if (!$insert_stmt) { throw new Exception("Prepare failed for external deposit txn: " . $this->conn->error); } // Added debug
                $insert_stmt->bind_param("iissssssiss", $primary_account_id, 1, $deposit_amount, $remarks, $base_transaction_code, $linked_account_details['account_number'], $primary_account_number, $status, $specific_transaction_type, $source_linked_account_id, $meta_data);
                if (!$insert_stmt->execute()) {
                    throw new Exception("Failed to record external deposit request: " . $insert_stmt->error);
                }
                $insert_stmt->close();
                $this->settings->set_flashdata('success', 'Deposit request from external linked account submitted successfully. It is pending admin approval.');
                error_log("Master::deposit_from_linked_account: External deposit request recorded as pending.");
            }
            $this->conn->commit();
            $resp['status'] = 'success';
            $resp['msg'] = 'Operation Successful.';
            error_log("Master::deposit_from_linked_account: Transaction committed successfully.");
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            error_log("Master::deposit_from_linked_account failed: " . $e->getMessage() . " (Rollback performed)");
        }
        return json_encode($resp);
    }


    function transfer_to_linked_account(){
        extract($_POST);
        $resp = array('status' => 'failed', 'msg' => '');
        $primary_account_id = $this->settings->userdata('account_id');
        $primary_account_number = $this->settings->userdata('account_number');
        $primary_account_fullname = $this->settings->userdata('fullname');
        $transfer_amount = floatval($amount_transfer);
        $entered_transaction_pin = $transaction_pin;
        if ($transfer_amount <= 0) {
            $resp['msg'] = "Transfer amount must be greater than zero.";
            return json_encode($resp);
        }
        if (empty($primary_account_id)) {
            $resp['msg'] = "Primary account details not found in session. Please re-login.";
            return json_encode($resp);
        }
        $this->conn->begin_transaction();
        try {
            $stmt_primary_acc = $this->conn->prepare("SELECT balance, transaction_pin FROM `accounts` WHERE id = ? FOR UPDATE");
            $stmt_primary_acc->bind_param("i", $primary_account_id);
            $stmt_primary_acc->execute();
            $primary_account_data = $stmt_primary_acc->get_result()->fetch_assoc();
            $primary_account_current_balance = $primary_account_data['balance'];
            $primary_account_stored_pin = $primary_account_data['transaction_pin'];
            $stmt_primary_acc->close();
            if (trim($entered_transaction_pin) != trim($primary_account_stored_pin)) {
                throw new Exception("Invalid Transaction PIN.");
            }
            if ($primary_account_current_balance < $transfer_amount) {
                throw new Exception("Insufficient funds in your primary account for this transfer.");
            }
            $user_id_for_linked = $this->settings->userdata('id');
            $stmt_linked_acc = $this->conn->prepare("SELECT account_number, is_internal_bank, account_holder_name, bank_name FROM `user_linked_accounts` WHERE id = ? AND user_id = ?");
            $stmt_linked_acc->bind_param("ii", $destination_linked_account_id, $user_id_for_linked);
            $stmt_linked_acc->execute();
            $linked_account_details = $stmt_linked_acc->get_result()->fetch_assoc();
            $is_internal_destination_account = $linked_account_details['is_internal_bank'];
            $destination_linked_account_number = $linked_account_details['account_number'];
            $destination_linked_account_holder_name = $linked_account_details['account_holder_name'];
            $destination_linked_bank_name = $linked_account_details['bank_name'];
            $stmt_linked_acc->close();

            $base_transaction_code = $this->settings->info('short_name') . '-' . date('Ymd-His') . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
            $new_primary_balance = $primary_account_current_balance - $transfer_amount;

            $meta_data = json_encode([
                'destination_linked_account_id' => $destination_linked_account_id,
                'destination_account_number_linked' => $destination_linked_account_number,
                'destination_account_holder_name_linked' => $destination_linked_account_holder_name,
                'destination_bank_name_linked' => $destination_linked_bank_name,
                'primary_account_balance_before' => $primary_account_current_balance
            ]);

            if ($is_internal_destination_account == 1) {
                $stmt_destination_internal_acc = $this->conn->prepare("SELECT id, balance FROM `accounts` WHERE account_number = ? FOR UPDATE");
                $stmt_destination_internal_acc->bind_param("s", $destination_linked_account_number);
                $stmt_destination_internal_acc->execute();
                $destination_internal_acc_data = $stmt_destination_internal_acc->get_result()->fetch_assoc();
                $destination_internal_acc_id = $destination_internal_acc_data['id'];
                $destination_internal_acc_balance = $destination_internal_acc_data['balance'];
                $stmt_destination_internal_acc->close();
                if ($destination_internal_acc_id == $primary_account_id) {
                    throw new Exception("You cannot transfer to your own primary account linked as an internal account.");
                }
                $new_destination_internal_balance = $destination_internal_acc_balance + $transfer_amount;
                $update_primary_stmt = $this->conn->prepare("UPDATE `accounts` SET `balance` = ? WHERE `id` = ?");
                $update_primary_stmt->bind_param("di", $new_primary_balance, $primary_account_id);
                $update_primary_stmt->execute();
                $update_primary_stmt->close();
                $update_destination_stmt = $this->conn->prepare("UPDATE `accounts` SET `balance` = ? WHERE `id` = ?");
                $update_destination_stmt->bind_param("di", $new_destination_internal_balance, $destination_internal_acc_id);
                $update_destination_stmt->execute();
                $update_destination_stmt->close();


                $remarks_primary = "Transfer to Internal Linked Account ({$destination_linked_account_number}) - {$destination_linked_account_holder_name}";
                $sender_txn_code = $base_transaction_code . '-S';
                $insert_primary_txn_stmt = $this->conn->prepare("INSERT INTO `transactions` (`account_id`, `type`, `amount`, `remarks`, `transaction_code`, `sender_account_number`, `receiver_account_number`, `status`, `transaction_type`, `linked_account_id`, `meta_data`) VALUES (?, 3, ?, ?, ?, ?, ?, 'completed', 'internal_transfer_outgoing', ?, ?)");
                $insert_primary_txn_stmt->bind_param("idssssis", $primary_account_id, $transfer_amount, $remarks_primary, $sender_txn_code, $primary_account_number, $destination_linked_account_number, $destination_linked_account_id, $meta_data);
                if (!$insert_primary_txn_stmt->execute()) { throw new Exception("Failed to record primary account transaction: " . $insert_primary_txn_stmt->error); }
                $insert_primary_txn_stmt->close();


                $remarks_destination = "Transfer from Linked Primary Account ({$primary_account_number}) - {$this->settings->userdata('fullname')}";
                $receiver_txn_code = $base_transaction_code . '-R';
                $insert_destination_txn_stmt = $this->conn->prepare("INSERT INTO `transactions` (`account_id`, `type`, `amount`, `remarks`, `transaction_code`, `sender_account_number`, `receiver_account_number`, `status`, `transaction_type`, `linked_account_id`, `meta_data`) VALUES (?, 1, ?, ?, ?, ?, ?, 'completed', 'internal_transfer_incoming', ?, ?)");
                $insert_destination_txn_stmt->bind_param("idssssis", $destination_internal_acc_id, $transfer_amount, $remarks_destination, $receiver_txn_code, $primary_account_number, $destination_linked_account_number, $destination_linked_account_id, $meta_data);
                if (!$insert_destination_txn_stmt->execute()) { throw new Exception("Failed to record internal destination account transaction: " . $insert_destination_txn_stmt->error); }
                $insert_destination_txn_stmt->close();


                $this->settings->set_userdata('balance', $new_primary_balance);
                $this->settings->set_flashdata('success', 'Transfer to internal linked account successful.');
            } else {
                $update_primary_stmt = $this->conn->prepare("UPDATE `accounts` SET `balance` = ? WHERE `id` = ?");
                $update_primary_stmt->bind_param("di", $new_primary_balance, $primary_account_id);
                $update_primary_stmt->execute();
                $update_primary_stmt->close();
                $insert_stmt = $this->conn->prepare("INSERT INTO `transactions` (`account_id`, `type`, `amount`, `remarks`, `transaction_code`, `sender_account_number`, `receiver_account_number`, `status`, `transaction_type`, `linked_account_id`, `meta_data`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $remarks = "Transfer request to External Linked Account ({$destination_linked_account_number} - {$destination_linked_bank_name})";
                $insert_stmt->bind_param("iissssssiss", $primary_account_id, 3, $transfer_amount, $remarks, $base_transaction_code, $primary_account_number, $destination_linked_account_number, 'pending', 'transfer_external_pending', $destination_linked_account_id, $meta_data);
                $insert_stmt->execute();
                $insert_stmt->close();
                $this->settings->set_userdata('balance', $new_primary_balance);
                $this->settings->set_flashdata('success', 'Transfer request to external linked account submitted successfully. It is pending admin approval.');
            }
            $this->conn->commit();
            $resp['status'] = 'success';
            $resp['msg'] = 'Operation Successful.';
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            error_log("Transfer to linked account failed: " . $e->getMessage());
        }
        return json_encode($resp);
    }

    function approve_transaction(){
        extract($_POST);
        $resp = array('status' => 'failed', 'msg' => '');
        if ($this->settings->userdata('login_type') != 1) {
            $resp['msg'] = "Unauthorized access. Only administrators can approve transactions.";
            return json_encode($resp);
        }
        $transaction_id = $transaction_id;
        $this->conn->begin_transaction();
        try {
            $stmt_txn = $this->conn->prepare("SELECT * FROM `transactions` WHERE id = ? FOR UPDATE");
            if ($stmt_txn === false) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt_txn->bind_param("i", $transaction_id);
            $stmt_txn->execute();
            $txn_qry = $stmt_txn->get_result();
            if ($txn_qry->num_rows === 0) {
                throw new Exception("Transaction not found.");
            }
            $transaction_data = $txn_qry->fetch_assoc();
            $stmt_txn->close();


            if ($transaction_data['status'] !== 'pending' || !in_array($transaction_data['transaction_type'], ['deposit_external_pending', 'transfer_external_pending'])) {
                throw new Exception("Transaction is not a pending external deposit/transfer or has already been processed.");
            }


            $target_account_id = $transaction_data['account_id'];
            $transaction_amount = $transaction_data['amount'];
            $original_transaction_type = $transaction_data['transaction_type'];


            $new_specific_transaction_type = '';


            if ($original_transaction_type === 'deposit_external_pending') {
                $stmt_account_balance = $this->conn->prepare("SELECT balance FROM `accounts` WHERE id = ? FOR UPDATE");
                if ($stmt_account_balance === false) {
                    throw new Exception("Prepare failed to fetch account balance: " . $this->conn->error);
                }
                $stmt_account_balance->bind_param("i", $target_account_id);
                $stmt_account_balance->execute();
                $account_balance_qry = $stmt_account_balance->get_result();
                if ($account_balance_qry->num_rows === 0) {
                    throw new Exception("Target account not found for transaction ID: {$transaction_id}.");
                }
                $account_data = $account_balance_qry->fetch_assoc();
                $current_account_balance = $account_data['balance'];
                $stmt_account_balance->close();


                $new_account_balance = $current_account_balance + $transaction_amount;
                $update_account_stmt = $this->conn->prepare("UPDATE `accounts` SET `balance` = ? WHERE `id` = ?");
                if ($update_account_stmt === false) {
                    throw new Exception("Prepare failed to update account balance: " . $this->conn->error);
                }
                $update_account_stmt->bind_param("di", $new_account_balance, $target_account_id);
                if (!$update_account_stmt->execute()) {
                    throw new Exception("Failed to update account balance: " . $update_account_stmt->error);
                }
                $update_account_stmt->close();
                $new_specific_transaction_type = 'deposit_external_completed';


            } elseif ($original_transaction_type === 'transfer_external_pending') {
                $new_specific_transaction_type = 'transfer_external_completed';
            } else {
                throw new Exception("Unsupported pending transaction type for approval: " . $original_transaction_type);
            }


            $update_txn_stmt = $this->conn->prepare("UPDATE `transactions` SET `status` = 'completed', `transaction_type` = ? WHERE `id` = ?");
            if ($update_txn_stmt === false) {
                throw new Exception("Prepare failed to update transaction status: " . $this->conn->error);
            }
            $update_txn_stmt->bind_param("si", $new_specific_transaction_type, $transaction_id);
            if (!$update_txn_stmt->execute()) {
                throw new Exception("Failed to update transaction status: " . $update_txn_stmt->error);
            }
            $update_txn_stmt->close();
            $this->conn->commit();
            $resp['status'] = 'success';
            $resp['msg'] = 'Transaction successfully approved and account credited/marked as completed.';
            $this->settings->set_flashdata('success', 'Transaction successfully approved.');
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            error_log("Transaction approval failed: " . $e->getMessage());
        }
        return json_encode($resp);
    }


    function decline_transaction(){
        extract($_POST);
        $resp = array('status' => 'failed', 'msg' => '');
        if ($this->settings->userdata('login_type') != 1) {
            $resp['msg'] = "Unauthorized access. Only administrators can decline transactions.";
            return json_encode($resp);
        }
        $transaction_id = $transaction_id;
        $reason = $reason ?? 'No reason provided.';
        $this->conn->begin_transaction();
        try {
            $stmt_txn = $this->conn->prepare("SELECT * FROM `transactions` WHERE id = ? FOR UPDATE");
            if ($stmt_txn === false) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt_txn->bind_param("i", $transaction_id);
            $stmt_txn->execute();
            $txn_qry = $stmt_txn->get_result();
            if ($txn_qry->num_rows === 0) {
                throw new Exception("Transaction not found.");
            }
            $transaction_data = $txn_qry->fetch_assoc();
            $stmt_txn->close();


            if ($transaction_data['status'] !== 'pending' || !in_array($transaction_data['transaction_type'], ['deposit_external_pending', 'transfer_external_pending'])) {
                throw new Exception("Transaction is not a pending external deposit/transfer or has already been processed.");
            }


            $target_account_id = $transaction_data['account_id'];
            $transaction_amount = $transaction_data['amount'];
            $original_transaction_type = $transaction_data['transaction_type'];


            $new_specific_transaction_type = '';


            if ($original_transaction_type === 'transfer_external_pending') {
                $stmt_account_balance = $this->conn->prepare("SELECT balance FROM `accounts` WHERE id = ? FOR UPDATE");
                if ($stmt_account_balance === false) {
                    throw new Exception("Prepare failed to fetch account balance for refund: " . $this->conn->error);
                }
                $stmt_account_balance->bind_param("i", $target_account_id);
                $stmt_account_balance->execute();
                $account_balance_qry = $stmt_account_balance->get_result();
                if ($account_balance_qry->num_rows === 0) {
                    throw new Exception("Target account not found for refund during decline: {$target_account_id}.");
                }
                $account_data = $account_balance_qry->fetch_assoc();
                $current_account_balance = $account_data['balance'];
                $stmt_account_balance->close();


                $new_account_balance = $current_account_balance + $transaction_amount;
                $update_account_stmt = $this->conn->prepare("UPDATE `accounts` SET `balance` = ? WHERE `id` = ?");
                if ($update_account_stmt === false) {
                    throw new Exception("Prepare failed to refund account balance: " . $this->conn->error);
                }
                $update_account_stmt->bind_param("di", $new_account_balance, $target_account_id);
                if (!$update_account_stmt->execute()) {
                    throw new Exception("Failed to refund account balance: " . $update_account_stmt->error);
                }
                $update_account_stmt->close();


                if ($target_account_id == $this->settings->userdata('account_id')) {
                    $this->settings->set_userdata('balance', $new_account_balance);
                }
                $new_specific_transaction_type = 'transfer_external_declined';


            } elseif ($original_transaction_type === 'deposit_external_pending') {
                $new_specific_transaction_type = 'deposit_external_declined';
            } else {
                throw new Exception("Unsupported pending transaction type for decline: " . $original_transaction_type);
            }


            $new_remarks = $transaction_data['remarks'] . " (Declined by Admin. Reason: " . $reason . ")";
            $update_txn_stmt = $this->conn->prepare("UPDATE `transactions` SET `status` = 'declined', `remarks` = ?, `transaction_type` = ? WHERE `id` = ?");
            if ($update_txn_stmt === false) {
                throw new Exception("Prepare failed to update transaction status to declined: " . $this->conn->error);
            }
            $update_txn_stmt->bind_param("ssi", $new_remarks, $new_specific_transaction_type, $transaction_id);
            if (!$update_txn_stmt->execute()) {
                throw new Exception("Failed to update transaction status to declined: " . $update_txn_stmt->error);
            }
            $update_txn_stmt->close();
            $this->conn->commit();
            $resp['status'] = 'success';
            $resp['msg'] = 'Transaction successfully declined.';
            $this->settings->set_flashdata('success', 'Transaction successfully declined.');
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            error_log("Transaction declining failed: " . $e->getMessage());
        }
        return json_encode($resp);
    }


    function save_announcement(){
        extract($_POST);
        $announcement_html_entities = htmlentities($announcement);
        if(empty($id)){
            $sql = "INSERT INTO `announcements` set `title` = ?, `announcement` = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                return json_encode(array('status'=>'failed', 'msg'=>"Prepare failed: " . $this->conn->error));
            }
            $stmt->bind_param("ss", $title, $announcement_html_entities);
        }else{
            $sql = "UPDATE `announcements` set `title` = ?, `announcement` = ? where id = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                return json_encode(array('status'=>'failed', 'msg'=>"Prepare failed: " . $this->conn->error));
            }
            $stmt->bind_param("ssi", $title, $announcement_html_entities, $id);
        }
        if($stmt->execute()){
            $resp['status'] ='success';
            $this->settings->set_flashdata('success', 'Announcement successfully saved.');
        }else{
            $resp['status'] = 'failed';
            $resp['msg'] = "Execute failed: " . $stmt->error;
            error_log("Save announcement failed: " . $stmt->error);
        }
        $stmt->close();
        return json_encode($resp);
    }
    
    function activate_account(){
        extract($_POST);
        $stmt = $this->conn->prepare("UPDATE `accounts` set `status` = 'Active' where id = ?");
        $stmt->bind_param("i", $id);
        $update = $stmt->execute();


        if($update){
            $resp['status'] = 'success';
            $resp['msg'] = "Account has been activated successfully.";
            $this->settings->set_flashdata('success', $resp['msg']);
        }else{
            $resp['status'] = 'failed';
            $resp['msg'] = "An error occurred during activation.";
            $resp['err'] = $this->conn->error;
        }
        $stmt->close();
        return json_encode($resp);
    }


    function delete_account(){
        extract($_POST);
        $resp = array('status' => 'failed', 'msg' => '');


        if($this->settings->userdata('login_type') != 1){
            $resp['msg'] = "You are not authorized to perform this action.";
            return json_encode($resp);
        }


        $qry = $this->conn->query("SELECT firebase_uid FROM `accounts` where id = '{$id}'");
        $firebase_uid = ($qry->num_rows > 0) ? $qry->fetch_array()[0] : null;


        $this->conn->begin_transaction();
        try {
            $delete_acc = $this->conn->query("DELETE FROM `accounts` where id = '{$id}'");
            if(!$delete_acc) throw new Exception("Failed to delete from accounts table.");


            $this->conn->query("DELETE FROM `transactions` where account_id = '{$id}'");
            
            if(!empty($firebase_uid) && $this->firebaseAuth !== null){
                try {
                    $this->firebaseAuth->deleteUser($firebase_uid);
                } catch (Exception $e) {
                    error_log("Could not delete user from Firebase (UID: {$firebase_uid}): " . $e->getMessage());
                }
            }
            
            $this->conn->commit();
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success','Account has been deleted successfully.');


        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['msg'] = "An error occurred: " . $e->getMessage();
        }
        
        return json_encode($resp);
    }
    
    function delete_announcement(){
        extract($_POST);
        $stmt = $this->conn->prepare("DELETE FROM `announcements` where id = ?");
        if ($stmt === false) {
            return json_encode(array('status'=>'failed', 'msg'=>"Prepare failed: " . $this->conn->error));
        }
        $stmt->bind_param("i", $id);
        if($stmt->execute()){
            $resp['status'] ='success';
            $this->settings->set_flashdata('success', 'Announcement successfully deleted.');
        }else{
            $resp['status'] = 'failed';
            $resp['msg'] = "Execute failed: " . $stmt->error;
            error_log("Delete announcement failed: " . $stmt->error);
        }
        $stmt->close();
        return json_encode($resp);
    }
    
    public function admin_adjust_balance(){
        extract($_POST);
        $resp = ['status' => 'failed', 'msg' => 'An unknown error occurred.'];


        if($this->settings->userdata('login_type') != 1){
            $resp['msg'] = "You are not authorized to perform this action.";
            return json_encode($resp);
        }
        if(empty($account_id) || empty($transaction_type) || !isset($amount) || empty($remarks)){
            $resp['msg'] = "All fields are required.";
            return json_encode($resp);
        }
        $amount = floatval($amount);
        if($amount <= 0){
            $resp['msg'] = "Amount must be a positive number.";
            return json_encode($resp);
        }
        
        $acc_qry = $this->conn->query("SELECT * FROM `accounts` where id = '{$account_id}'");
        if($acc_qry->num_rows < 1){
            $resp['msg'] = "Target account does not exist.";
            return json_encode($resp);
        }
        $res = $acc_qry->fetch_array();
        $current_balance = $res['balance'];
        
        if($transaction_type == 2 && $current_balance < $amount){ // 2 = Debit
            $resp['msg'] = "Insufficient balance for this debit transaction.";
            return json_encode($resp);
        }
        
        $new_balance = ($transaction_type == 1) ? $current_balance + $amount : $current_balance - $amount; // 1 = Credit


        $this->conn->begin_transaction();
        try {
            $update_bal = $this->conn->query("UPDATE `accounts` set `balance` = '{$new_balance}' where id = '{$account_id}'");
            if(!$update_bal) throw new Exception("Failed to update account balance.");


            // THIS IS THE FIX: The 'balance' column is removed from the query
            $type = $transaction_type;
            $remarks_sanitized = $this->conn->real_escape_string($remarks);
            $sql = "INSERT INTO `transactions` set `account_id` = '{$account_id}', `type` = '{$type}', `amount` = '{$amount}', `remarks` = '{$remarks_sanitized}'";
            
            $save_trans = $this->conn->query($sql);
            if(!$save_trans) throw new Exception($this->conn->error);


            $this->conn->commit();
            $resp['status'] = 'success';
            $resp['msg'] = "Transaction has been saved successfully.";
            $this->settings->set_flashdata('success',$resp['msg']);


        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = "An error occurred: " . $e->getMessage();
        }
        
        return json_encode($resp);
    }
}


if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    // This block executes if Master.php is accessed directly (e.g., via /classes/Master.php?f=action)
    // The router in index.php will now also explicitly trigger this block by requiring the file.
    $Master = new Master(); // Instantiate the Master class
    $action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
    switch ($action) {
        // The `login` case is left in place to avoid breaking other functionalities that might be using it.
        // The admin login will be routed to a different, dedicated action.
        case 'login': 
            echo $Master->login(); 
            break;
        case 'save_account': // Client Registration/Update
            echo $Master->save_account();
            break;
        case 'deposit':
            echo $Master->deposit();
            break;
        case 'withdraw':
            echo $Master->withdraw();
            break;
        case 'get_internal_account_details_for_transfer':
            echo $Master->get_internal_account_details_for_transfer();
            break;
        case 'transfer_internal':
            echo $Master->transfer_internal();
            break;
        case 'transfer_external':
            echo $Master->transfer_external();
            break;
        case 'get_linked_accounts':
            echo $Master->get_linked_accounts($Master->settings->userdata('id'));
            break;
        case 'save_linked_account':
            echo $Master->save_linked_account();
            break;
        case 'delete_linked_account':
            echo $Master->delete_linked_account();
            break;
        case 'get_account_details_by_number':
            echo $Master->get_account_details_by_number();
            break;
        case 'deposit_from_linked_account':
            echo $Master->deposit_from_linked_account();
            break;
        case 'transfer_to_linked_account':
            echo $Master->transfer_to_linked_account();
            break;
        case 'approve_transaction':
            echo $Master->approve_transaction();
            break;
        case 'decline_transaction':
            echo $Master->decline_transaction();
            break;
        case 'save_announcement':
            echo $Master->save_announcement();
            break;
        case 'delete_account':
            echo $Master->delete_account();
            break;
        case 'delete_announcement':
            echo $Master->delete_announcement();
            break;
        case 'activate_account':
            echo $Master->activate_account();
            break;
        case 'admin_adjust_balance':
            echo $Master->admin_adjust_balance();
            break;
        default:
            echo json_encode(array('status'=>'failed','msg'=>'Invalid action.'));
            break;
    }
}
?>