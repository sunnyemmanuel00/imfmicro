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
        // Start the response with a default failed status
        $resp = ['status' => 'failed', 'msg' => 'An unknown error occurred.'];

        // Use prepared statements for security and PostgreSQL compatibility.
        // We'll build the column list and a placeholder array to avoid SQL injection.
        
        $columns = [];
        $values = [];
        // MODIFICATION: Added a check for empty values to prevent database errors.
        foreach($_POST as $k => $v){
           if(!in_array($k,['id','password']) && !is_numeric($k) && trim($v) !== ''){
               $columns[] = "\"{$k}\""; // Use double quotes for PostgreSQL column names
               $values[] = $v;
            }
        }

        // The logic for inserting a new account vs. updating an existing one.
        if(empty($_POST['id'])){
            // ========== CREATE NEW CLIENT ACCOUNT ==========
            // Check for required fields for a new account.
            if(empty($_POST['password']) || empty($_POST['email'])){
                return json_encode(['status' => 'failed', 'msg' => 'Email and Password are required for new accounts.']);
            }
            
            // Use a prepared statement to check for existing email
            $stmt = $this->conn->prepare("SELECT id FROM \"accounts\" WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            if($stmt->rowCount() > 0){
                return json_encode(['status' => 'failed', 'msg' => 'This email address is already registered.']);
            }
            $stmt->closeCursor();

            $this->conn->beginTransaction();
            try {
                // Step 1: Create user in Firebase
                if ($this->firebaseAuth === null) throw new Exception("Firebase service is not available. Check configuration.");
                $userProperties = [
                    'email' => $_POST['email'],
                    'emailVerified' => true,
                    'password' => $_POST['password'],
                    'disabled' => false,
                ];
                $createdUser = $this->firebaseAuth->createUser($userProperties);
                
                // Generate and store the 5-digit transaction PIN
                $plain_pin = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT); 

                // Add the new fields to our column and value arrays for insertion
                $columns[] = "\"firebase_uid\"";
                $values[] = $createdUser->uid;
                $columns[] = "\"transaction_pin\"";
                $values[] = $plain_pin;
                $columns[] = "\"password\"";
                $values[] = md5($_POST['password']);
                $columns[] = "\"login_type\"";
                $values[] = 2; // Hardcoding the login_type as 2 for a client
                $columns[] = "\"balance\"";
                $values[] = 0;
                $columns[] = "\"status\"";
                $values[] = 'Pending';
                $columns[] = "\"first_login_done\"";
                $values[] = 0;

                // Generate a unique account number
                $account_number = '';
                while(true){
                    $account_number = sprintf("%'.010d", mt_rand(0, 9999999999));
                    $stmt_chk = $this->conn->prepare("SELECT * FROM \"accounts\" WHERE \"account_number\" = ?");
                    $stmt_chk->execute([$account_number]);
                    if($stmt_chk->rowCount() <= 0) break;
                    $stmt_chk->closeCursor();
                }
                $columns[] = "\"account_number\"";
                $values[] = $account_number;
                
                // Construct and execute the final prepared INSERT statement
                $column_list = implode(', ', $columns);
                $placeholder_list = implode(', ', array_fill(0, count($columns), '?'));
                $sql = "INSERT INTO \"accounts\" ({$column_list}) VALUES ({$placeholder_list})";
                
                $stmt_insert = $this->conn->prepare($sql);
                $save = $stmt_insert->execute($values);
                
                if(!$save) throw new Exception($stmt_insert->errorInfo()[2]);
                $stmt_insert->closeCursor();

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
            $id = $_POST['id'];

            // MODIFICATION: Re-building the columns and values to prevent empty fields from causing errors.
            $update_columns = [];
            $update_values = [];
            foreach($_POST as $k => $v){
                if(!in_array($k,['id','password']) && !is_numeric($k) && trim($v) !== ''){
                    $update_columns[] = "\"{$k}\"";
                    $update_values[] = $v;
                }
            }
            
            if(isset($_POST['password']) && !empty($_POST['password'])){
                $update_columns[] = "\"password\"";
                $update_values[] = md5($_POST['password']);
            }
            
            $update_values[] = $id;

            // Construct the UPDATE statement dynamically
            $update_pairs = [];
            for ($i = 0; $i < count($update_columns); $i++){
                $update_pairs[] = "{$update_columns[$i]} = ?";
            }
            $update_string = implode(', ', $update_pairs);

            $sql = "UPDATE \"accounts\" SET {$update_string} WHERE id = ?";
            
            $stmt_update = $this->conn->prepare($sql);
            $save = $stmt_update->execute($update_values);
            $stmt_update->closeCursor();

            if($save){
                $resp['status'] = 'success';
                $this->settings->set_flashdata('success',"Account details have been updated successfully.");

                if(isset($_POST['password']) && !empty($_POST['password']) && $this->firebaseAuth !== null){
                    $stmt_uid = $this->conn->prepare("SELECT firebase_uid FROM \"accounts\" WHERE id = ?");
                    $stmt_uid->execute([$id]);
                    $firebase_uid = $stmt_uid->fetchColumn();
                    $stmt_uid->closeCursor();
                    
                    if($firebase_uid){
                        try {
                            $this->firebaseAuth->changeUserPassword($firebase_uid, $_POST['password']);
                            $stmt_pwd = $this->conn->prepare("UPDATE \"accounts\" SET password = ? WHERE id = ?");
                            $stmt_pwd->execute([md5($_POST['password']), $id]);
                            $stmt_pwd->closeCursor();
                        } catch (Exception $e) {
                            error_log("Firebase password update failed for UID {$firebase_uid}: " . $e->getMessage());
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

        if(!isset($_settings)){
            require_once(__DIR__ . '/../config.php');
            $_settings = new SystemSettings();
        }

        $sender_account_number = $_settings->userdata('account_number');
        if ($account_number == $sender_account_number) {
            $resp['msg'] = "Cannot transfer to your own account.";
            return json_encode($resp);
        }

        $stmt = $this->conn->prepare("SELECT CONCAT(lastname, ', ', firstname, ' ', COALESCE(middlename,'')) as account_holder_name FROM \"accounts\" WHERE account_number = ?");
        
        if ($stmt === false) {
            $resp['msg'] = "Prepare failed: " . json_encode($this->conn->errorInfo());
            return json_encode($resp);
        }
        
        $stmt->execute([$account_number]);
        
        if ($stmt->rowCount() > 0) {
            $resp['data'] = $stmt->fetch(PDO::FETCH_ASSOC);
            $resp['status'] = 'success';
            $resp['msg'] = 'Account details fetched successfully.';
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'No internal account found with this number.';
        }
        return json_encode($resp);
    }

function check_account_status($account_id) {
    // Assuming a PDO connection is available as $this->conn
    // and an 'accounts' table with an 'id' and 'status' column.
    try {
        $stmt_status = $this->conn->prepare("SELECT status FROM \"accounts\" WHERE id = :id");
        if ($stmt_status === false) {
            throw new Exception("Prepare failed to check account status: " . implode(" ", $this->conn->errorInfo()));
        }
        $stmt_status->bindValue(':id', $account_id, PDO::PARAM_INT);
        $stmt_status->execute();
        $account_status_data = $stmt_status->fetch(PDO::FETCH_ASSOC);

        if ($account_status_data === false) {
            throw new Exception("Sender account not found.");
        }

        if ($account_status_data['status'] !== 'active') {
            return json_encode([
                'status' => 'failed',
                'msg' => "Your account status is currently '{$account_status_data['status']}'. Transfers are not permitted. Please contact support."
            ]);
        }

        // Return a success status if the account is active
        return json_encode(['status' => 'success', 'msg' => 'Account is active.']);

    } catch (Exception $e) {
        return json_encode([
            'status' => 'failed',
            'msg' => "Security check failed: " . $e->getMessage()
        ]);
    }
}

/**
 * Handles internal transfers between accounts.
 * Now includes a check_account_status call at the beginning.
 */
function transfer_internal() {
    // Explicitly define variables from $_POST
    $amount = $_POST['amount'] ?? 0;
    $recipient_account_number = $_POST['recipient_account_number'] ?? '';
    $transaction_pin = $_POST['transaction_pin'] ?? '';
    $narration = $_POST['narration'] ?? '';

    $resp = array('status' => 'failed', 'msg' => '');
    $sender_account_id = $this->settings->userdata('account_id');
    $sender_account_number = $this->settings->userdata('account_number');
    $sender_fullname = $this->settings->userdata('fullname');
    $transfer_amount = floatval($amount);
    $entered_pin = $transaction_pin;

    if ($transfer_amount <= 0) {
        $resp['msg'] = "Transfer amount must be greater than zero.";
        return json_encode($resp);
    }
    if (empty($sender_account_id)) {
        $resp['msg'] = "Sender account ID not found in session. Please re-login.";
        return json_encode($resp);
    }
    if (empty($recipient_account_number)) {
        $resp['msg'] = "Recipient account number is required.";
        return json_encode($resp);
    }
    if ($sender_account_number == $recipient_account_number) {
        $resp['msg'] = "You cannot transfer to your own account.";
        return json_encode($resp);
    }

    $this->conn->beginTransaction();
    try {
        // Fetch sender account data using PDO
        $stmt_sender = $this->conn->prepare("SELECT balance, transaction_pin, firstname, middlename, lastname FROM \"accounts\" WHERE id = :id FOR UPDATE");
        if ($stmt_sender === false) {
            throw new Exception("Prepare failed to fetch sender account: " . implode(" ", $this->conn->errorInfo()));
        }
        $stmt_sender->bindValue(':id', $sender_account_id, PDO::PARAM_INT);
        $stmt_sender->execute();
        $sender_data = $stmt_sender->fetch(PDO::FETCH_ASSOC);

        if ($sender_data === false) {
            throw new Exception("Sender account not found.");
        }
        
        $sender_current_balance = $sender_data['balance'];
        $sender_stored_pin = $sender_data['transaction_pin'];
        $sender_account_holder_name = trim($sender_data['firstname'] . ' ' . (isset($sender_data['middlename']) && !empty($sender_data['middlename']) ? $sender_data['middlename'] . ' ' : '') . $sender_data['lastname']);

        // PIN validation with direct comparison
        if (trim($entered_pin) != trim($sender_stored_pin)) {
            throw new Exception("Invalid Transaction PIN.");
        }

        if ($sender_current_balance < $transfer_amount) {
            throw new Exception("Insufficient funds for transfer.");
        }

        // Fetch receiver account data using PDO
        $stmt_receiver = $this->conn->prepare("SELECT id, balance, firstname, middlename, lastname FROM \"accounts\" WHERE account_number = :account_number FOR UPDATE");
        if ($stmt_receiver === false) {
            throw new Exception("Prepare failed to fetch receiver account: " . implode(" ", $this->conn->errorInfo()));
        }
        $stmt_receiver->bindValue(':account_number', $recipient_account_number);
        $stmt_receiver->execute();
        $receiver_data = $stmt_receiver->fetch(PDO::FETCH_ASSOC);

        if ($receiver_data === false) {
            throw new Exception("Recipient account number does not exist.");
        }
        $receiver_account_id = $receiver_data['id'];
        $receiver_current_balance = $receiver_data['balance'];
        $receiver_account_holder_name = trim($receiver_data['firstname'] . ' ' . (isset($receiver_data['middlename']) && !empty($receiver_data['middlename']) ? $receiver_data['middlename'] . ' ' : '') . $receiver_data['lastname']);

        $new_sender_balance = $sender_current_balance - $transfer_amount;
        $new_receiver_balance = $receiver_current_balance + $transfer_amount;

        // Update sender balance using PDO
        $update_sender_stmt = $this->conn->prepare("UPDATE \"accounts\" SET \"balance\" = :balance WHERE \"id\" = :id");
        if ($update_sender_stmt === false) {
            throw new Exception("Prepare failed to debit sender: " . implode(" ", $this->conn->errorInfo()));
        }
        $update_sender_stmt->bindValue(':balance', $new_sender_balance);
        $update_sender_stmt->bindValue(':id', $sender_account_id, PDO::PARAM_INT);
        if (!$update_sender_stmt->execute()) {
            throw new Exception("Failed to debit sender account: " . implode(" ", $update_sender_stmt->errorInfo()));
        }

        // Update receiver balance using PDO
        $update_receiver_stmt = $this->conn->prepare("UPDATE \"accounts\" SET \"balance\" = :balance WHERE \"id\" = :id");
        if ($update_receiver_stmt === false) {
            throw new Exception("Prepare failed to credit receiver: " . implode(" ", $this->conn->errorInfo()));
        }
        $update_receiver_stmt->bindValue(':balance', $new_receiver_balance);
        $update_receiver_stmt->bindValue(':id', $receiver_account_id, PDO::PARAM_INT);
        if (!$update_receiver_stmt->execute()) {
            throw new Exception("Failed to credit receiver account: " . implode(" ", $update_receiver_stmt->errorInfo()));
        }

        $base_transaction_code = $this->settings->info('short_name') . '-' . date('Ymd-His') . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
        $sender_transaction_code = $base_transaction_code . '-S';
        $receiver_transaction_code = $base_transaction_code . '-R';

        // Record sender transaction using PDO
        $remarks_sender = "Transfer to " . $receiver_account_holder_name . " (Account: " . $recipient_account_number . ")";
        if (!empty($narration)) {
            $remarks_sender .= " - " . $narration;
        }
        $meta_data_sender = json_encode([
            'sender_balance_before' => $sender_current_balance,
            'receiver_account_number' => $recipient_account_number,
            'receiver_account_name' => $receiver_account_holder_name,
            'narration' => $narration
        ]);

        $insert_sender_txn_stmt = $this->conn->prepare("INSERT INTO \"transactions\" (\"account_id\", \"type\", \"amount\", \"remarks\", \"transaction_code\", \"sender_account_number\", \"receiver_account_number\", \"status\", \"transaction_type\", \"meta_data\") VALUES (:account_id, :type, :amount, :remarks, :transaction_code, :sender_account_number, :receiver_account_number, 'completed', :transaction_type, :meta_data)");
        if ($insert_sender_txn_stmt === false) {
            throw new Exception("Prepare failed to record sender transaction: " . implode(" ", $this->conn->errorInfo()));
        }
        $insert_sender_txn_stmt->bindValue(':account_id', $sender_account_id, PDO::PARAM_INT);
        $insert_sender_txn_stmt->bindValue(':type', 3, PDO::PARAM_INT);
        $insert_sender_txn_stmt->bindValue(':amount', $transfer_amount);
        $insert_sender_txn_stmt->bindValue(':remarks', $remarks_sender);
        $insert_sender_txn_stmt->bindValue(':transaction_code', $sender_transaction_code);
        $insert_sender_txn_stmt->bindValue(':sender_account_number', $sender_account_number);
        $insert_sender_txn_stmt->bindValue(':receiver_account_number', $recipient_account_number);
        $insert_sender_txn_stmt->bindValue(':transaction_type', 'internal_transfer_outgoing');
        $insert_sender_txn_stmt->bindValue(':meta_data', $meta_data_sender);
        if (!$insert_sender_txn_stmt->execute()) {
            throw new Exception("Failed to record sender transaction: " . implode(" ", $insert_sender_txn_stmt->errorInfo()));
        }

        // Record receiver transaction using PDO
        $remarks_receiver = "Transfer from " . $sender_account_holder_name . " (Account: " . $sender_account_number . ")";
        if (!empty($narration)) {
            $remarks_receiver .= " - " . $narration;
        }
        $meta_data_receiver = json_encode([
            'receiver_balance_before' => $receiver_current_balance,
            'sender_account_number' => $sender_account_number,
            'sender_account_name' => $sender_account_holder_name,
            'narration' => $narration
        ]);

        $insert_receiver_txn_stmt = $this->conn->prepare("INSERT INTO \"transactions\" (\"account_id\", \"type\", \"amount\", \"remarks\", \"transaction_code\", \"sender_account_number\", \"receiver_account_number\", \"status\", \"transaction_type\", \"meta_data\") VALUES (:account_id, :type, :amount, :remarks, :transaction_code, :sender_account_number, :receiver_account_number, 'completed', :transaction_type, :meta_data)");
        if ($insert_receiver_txn_stmt === false) {
            throw new Exception("Prepare failed to record receiver transaction: " . implode(" ", $this->conn->errorInfo()));
        }
        $insert_receiver_txn_stmt->bindValue(':account_id', $receiver_account_id, PDO::PARAM_INT);
        $insert_receiver_txn_stmt->bindValue(':type', 1, PDO::PARAM_INT);
        $insert_receiver_txn_stmt->bindValue(':amount', $transfer_amount);
        $insert_receiver_txn_stmt->bindValue(':remarks', $remarks_receiver);
        $insert_receiver_txn_stmt->bindValue(':transaction_code', $receiver_transaction_code);
        $insert_receiver_txn_stmt->bindValue(':sender_account_number', $sender_account_number);
        $insert_receiver_txn_stmt->bindValue(':receiver_account_number', $recipient_account_number);
        $insert_receiver_txn_stmt->bindValue(':transaction_type', 'internal_transfer_incoming');
        $insert_receiver_txn_stmt->bindValue(':meta_data', $meta_data_receiver);
        if (!$insert_receiver_txn_stmt->execute()) {
            throw new Exception("Failed to record receiver transaction: " . implode(" ", $insert_receiver_txn_stmt->errorInfo()));
        }

        $this->conn->commit();
        $this->settings->set_userdata('balance', $new_sender_balance);
        $resp['status'] = 'success';
        $resp['msg'] = 'Internal transfer successful. Your new balance: ' . number_format($new_sender_balance, 2);
        $this->settings->set_flashdata('success', 'Internal transfer successful.');

    } catch (Exception $e) {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
        $resp['status'] = 'failed';
        $resp['msg'] = $e->getMessage();
        error_log("Internal transfer failed: " . $e->getMessage());
    }
    return json_encode($resp);
}

function transfer_external() {
    // Explicitly define variables from $_POST
    $amount_external = $_POST['amount_external'] ?? 0;
    $recipient_account_number_external = $_POST['recipient_account_number_external'] ?? '';
    $recipient_account_name_external = $_POST['recipient_account_name_external'] ?? '';
    $recipient_bank_name = $_POST['recipient_bank_name'] ?? '';
    $transaction_pin = $_POST['transaction_pin'] ?? '';
    $swift_bic = $_POST['swift_bic'] ?? '';
    $routing_number = $_POST['routing_number'] ?? '';
    $iban = $_POST['iban'] ?? '';
    $beneficiary_address = $_POST['beneficiary_address'] ?? '';
    $beneficiary_phone = $_POST['beneficiary_phone'] ?? '';
    $narration_external = $_POST['narration_external'] ?? '';

    $resp = array('status' => 'failed', 'msg' => '');
    $sender_account_id = $this->settings->userdata('account_id');
    $sender_account_number = $this->settings->userdata('account_number');
    $sender_fullname = $this->settings->userdata('fullname');
    $transfer_amount = floatval($amount_external);
    $entered_pin = $transaction_pin;

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

    $this->conn->beginTransaction();
    try {
        // Fetch sender account data using PDO
        $stmt_sender = $this->conn->prepare("SELECT balance, transaction_pin FROM \"accounts\" WHERE id = :id FOR UPDATE");
        if ($stmt_sender === false) {
            throw new Exception("Prepare failed to fetch sender account: " . implode(" ", $this->conn->errorInfo()));
        }
        $stmt_sender->bindValue(':id', $sender_account_id, PDO::PARAM_INT);
        $stmt_sender->execute();
        $sender_data = $stmt_sender->fetch(PDO::FETCH_ASSOC);

        if ($sender_data === false) {
            throw new Exception("Sender account not found.");
        }
        
        $sender_current_balance = $sender_data['balance'];
        $sender_stored_pin = $sender_data['transaction_pin'];

        // PIN validation
        if (trim($entered_pin) != trim($sender_stored_pin)) {
            throw new Exception("Invalid Transaction PIN.");
        }

        if ($sender_current_balance < $transfer_amount) {
            throw new Exception("Insufficient funds for external transfer.");
        }
        
        // Debit the sender's account immediately when the transfer is initiated.
        $new_sender_balance = $sender_current_balance - $transfer_amount;
        $update_sender_stmt = $this->conn->prepare("UPDATE \"accounts\" SET \"balance\" = :balance WHERE \"id\" = :id");
        $update_sender_stmt->bindValue(':balance', $new_sender_balance);
        $update_sender_stmt->bindValue(':id', $sender_account_id, PDO::PARAM_INT);
        if (!$update_sender_stmt->execute()) {
             throw new Exception("Failed to debit sender account: " . implode(" ", $update_sender_stmt->errorInfo()));
        }
        $this->settings->set_userdata('balance', $new_sender_balance);
        
        // Insert or update the recipient in the `recipients` table
        $insert_recipient_stmt = $this->conn->prepare("INSERT INTO \"recipients\" (\"user_id\", \"account_number\", \"bank_name\", \"account_name\") VALUES (:user_id, :account_number, :bank_name, :account_name) ON CONFLICT (\"account_number\") DO UPDATE SET account_name = :account_name, bank_name = :bank_name RETURNING id");

        if ($insert_recipient_stmt === false) {
            throw new Exception("Prepare failed to record recipient: " . implode(" ", $this->conn->errorInfo()));
        }
        $insert_recipient_stmt->bindValue(':user_id', $sender_account_id, PDO::PARAM_INT);
        $insert_recipient_stmt->bindValue(':account_number', $recipient_account_number_external);
        $insert_recipient_stmt->bindValue(':bank_name', $recipient_bank_name);
        $insert_recipient_stmt->bindValue(':account_name', $recipient_account_name_external);
        if (!$insert_recipient_stmt->execute()) {
            throw new Exception("Failed to record recipient: " . implode(" ", $insert_recipient_stmt->errorInfo()));
        }
        $recipient_id = $insert_recipient_stmt->fetch(PDO::FETCH_ASSOC)['id'];

        if (!$recipient_id) {
            throw new Exception("Failed to retrieve recipient ID after insertion.");
        }
        
        $description = "External Transfer Request to " . $recipient_account_name_external . " (" . $recipient_bank_name . " BANK, Account: " . $recipient_account_number_external . ")";
        if (!empty($narration_external)) {
            $description .= " - " . $narration_external;
        }

        // **CORRECTION:** This INSERT statement now correctly reflects your `pending_transactions` schema,
        // using the `recipient_id` column instead of the non-existent `recipient_account_number`.
        $insert_pending_stmt = $this->conn->prepare("INSERT INTO \"pending_transactions\" (\"sender_id\", \"amount\", \"description\", \"status\", \"recipient_id\") VALUES (:sender_id, :amount, :description, 'pending', :recipient_id)");
        if ($insert_pending_stmt === false) {
            throw new Exception("Prepare failed to record pending transaction: " . implode(" ", $this->conn->errorInfo()));
        }
        $insert_pending_stmt->bindValue(':sender_id', $sender_account_id, PDO::PARAM_INT);
        $insert_pending_stmt->bindValue(':amount', $transfer_amount);
        $insert_pending_stmt->bindValue(':description', $description);
        $insert_pending_stmt->bindValue(':recipient_id', $recipient_id, PDO::PARAM_INT);

        if (!$insert_pending_stmt->execute()) {
            throw new Exception("Failed to record pending transaction: " . implode(" ", $insert_pending_stmt->errorInfo()));
        }

        $this->conn->commit();

        $resp['status'] = 'success';
        $resp['msg'] = 'External transfer request submitted successfully. It is pending admin approval.';
        $this->settings->set_flashdata('success', 'External transfer request submitted successfully.');
    } catch (Exception $e) {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
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

    try {
        // Using PDO, prepare the SQL statement with a named placeholder.
        // Corrected for PostgreSQL: use double quotes (" ") instead of backticks (``)
        $stmt = $this->conn->prepare("SELECT * FROM \"user_linked_accounts\" WHERE \"user_id\" = :user_id ORDER BY \"account_holder_name\" ASC");
        
        // Bind the user_id parameter to the placeholder and execute.
        $stmt->execute([':user_id' => $user_id]);
        
        // Fetch all results as an associative array.
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($result) {
            $resp['data'] = $result;
            $resp['status'] = 'success';
            $resp['msg'] = 'Linked accounts fetched successfully.';
        } else {
            $resp['status'] = 'success';
            $resp['data'] = [];
            $resp['msg'] = 'No linked accounts found.';
        }

    } catch (PDOException $e) {
        // Handle any database errors.
        $resp['msg'] = "Database Error: " . $e->getMessage();
        error_log("PDOException in get_linked_accounts: " . $e->getMessage());
    }

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
    // Changed to PDO syntax and using double quotes for PostgreSQL
    $check_internal_stmt = $this->conn->prepare("SELECT id, transaction_pin FROM \"accounts\" WHERE account_number = :account_number");
    $check_internal_stmt->bindValue(':account_number', $account_number);
    $check_internal_stmt->execute();
    $internal_account_data = $check_internal_stmt->fetch(PDO::FETCH_ASSOC);

    if ($internal_account_data) {
        // --- THIS IS AN INTERNAL ACCOUNT ---
        $is_internal_bank = 1;
        $bank_name = $this->settings->info('short_name');
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
        // Changed to PDO syntax and using double quotes
        $stmt_user_pin = $this->conn->prepare("SELECT transaction_pin FROM \"accounts\" WHERE id = :id");
        $stmt_user_pin->bindValue(':id', $current_user_account_id, PDO::PARAM_INT);
        $stmt_user_pin->execute();
        $user_pin_data = $stmt_user_pin->fetch(PDO::FETCH_ASSOC);
        
        if (!$user_pin_data) {
            $resp['msg'] = "Your user account could not be found for PIN validation.";
            return json_encode($resp);
        }
        $stored_user_pin = $user_pin_data['transaction_pin'];
        
        if (trim($transaction_pin) !== trim($stored_user_pin)) {
            $resp['msg'] = "Invalid Transaction PIN. Your PIN is required to add an external account.";
            return json_encode($resp);
        }
    }

    if ($is_internal_bank == 1 && $account_number == $this->settings->userdata('account_number')) {
        $resp['msg'] = "You cannot link your own primary account.";
        return json_encode($resp);
    }


    // --- Database Transaction ---
    // Changed to PDO syntax
    $this->conn->beginTransaction();
    try {
        if (empty($id)) { // Insert (Add New)
            // Changed to PDO syntax and using double quotes
            $stmt = $this->conn->prepare("INSERT INTO \"user_linked_accounts\" (\"user_id\", \"account_label\", \"account_number\", \"account_holder_name\", \"bank_name\", \"is_internal_bank\", \"swift_bic\", \"routing_number\", \"iban\", \"beneficiary_address\", \"beneficiary_phone\", \"account_type\", \"link_type\") VALUES (:user_id, :account_label, :account_number, :account_holder_name, :bank_name, :is_internal_bank, :swift_bic, :routing_number, :iban, :beneficiary_address, :beneficiary_phone, :account_type, :link_type)");
            if ($stmt === false) throw new Exception("Prepare failed (insert): " . implode(" ", $this->conn->errorInfo()));
            
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':account_label', $account_label);
            $stmt->bindValue(':account_number', $account_number);
            $stmt->bindValue(':account_holder_name', $account_holder_name);
            $stmt->bindValue(':bank_name', $bank_name);
            $stmt->bindValue(':is_internal_bank', $is_internal_bank, PDO::PARAM_INT);
            $stmt->bindValue(':swift_bic', $swift_bic ?: null);
            $stmt->bindValue(':routing_number', $routing_number ?: null);
            $stmt->bindValue(':iban', $iban ?: null);
            $stmt->bindValue(':beneficiary_address', $beneficiary_address ?: null);
            $stmt->bindValue(':beneficiary_phone', $beneficiary_phone ?: null);
            $stmt->bindValue(':account_type', $account_type);
            $stmt->bindValue(':link_type', $link_type);
            
        } else { // Update (Edit Existing)
            // Changed to PDO syntax and using double quotes
            $sql = "UPDATE \"user_linked_accounts\" SET 
                \"account_label\" = :account_label, \"account_number\" = :account_number, \"account_holder_name\" = :account_holder_name, \"bank_name\" = :bank_name, \"is_internal_bank\" = :is_internal_bank, \"swift_bic\" = :swift_bic, \"routing_number\" = :routing_number, \"iban\" = :iban, \"beneficiary_address\" = :beneficiary_address, \"beneficiary_phone\" = :beneficiary_phone, \"account_type\" = :account_type, \"link_type\" = :link_type 
                WHERE \"id\" = :id AND \"user_id\" = :user_id";
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) throw new Exception("Prepare failed (update): " . implode(" ", $this->conn->errorInfo()));

            $stmt->bindValue(':account_label', $account_label);
            $stmt->bindValue(':account_number', $account_number);
            $stmt->bindValue(':account_holder_name', $account_holder_name);
            $stmt->bindValue(':bank_name', $bank_name);
            $stmt->bindValue(':is_internal_bank', $is_internal_bank, PDO::PARAM_INT);
            $stmt->bindValue(':swift_bic', $swift_bic ?: null);
            $stmt->bindValue(':routing_number', $routing_number ?: null);
            $stmt->bindValue(':iban', $iban ?: null);
            $stmt->bindValue(':beneficiary_address', $beneficiary_address ?: null);
            $stmt->bindValue(':beneficiary_phone', $beneficiary_phone ?: null);
            $stmt->bindValue(':account_type', $account_type);
            $stmt->bindValue(':link_type', $link_type);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        }
        
        $exec_success = $stmt->execute();
        $affected_rows = $stmt->rowCount();

        if ($exec_success) {
            if ($affected_rows > 0) {
                $this->conn->commit();
                $resp['status'] = 'success';
                $resp['msg'] = 'Linked account successfully ' . (empty($id) ? 'added' : 'updated') . '.';
                $this->settings->set_flashdata('success', $resp['msg']);
            } else {
                $this->conn->rollBack();
                $resp['status'] = 'failed';
                $resp['msg'] = "No changes were detected.";
            }
        } else {
            throw new Exception("Failed to save linked account: " . implode(" ", $stmt->errorInfo()));
        }
    } catch (Exception $e) {
        $this->conn->rollBack();
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
    // Corrected for PDO and PostgreSQL syntax
    $stmt_user_pin = $this->conn->prepare("SELECT transaction_pin FROM \"accounts\" WHERE id = :account_id");
    $stmt_user_pin->bindValue(':account_id', $account_id, PDO::PARAM_INT);
    $stmt_user_pin->execute();
    $user_pin_data = $stmt_user_pin->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_pin_data) {
        $resp['msg'] = "User account not found for PIN validation (delete).";
        file_put_contents($log_file, $log_data . "ERROR: " . $resp['msg'] . "\n\n", FILE_APPEND);
        return json_encode($resp);
    }
    $stored_user_pin = $user_pin_data['transaction_pin'];


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
    
    // Begin a transaction using PDO syntax
    $this->conn->beginTransaction();
    try {
        // Corrected for PDO and PostgreSQL syntax
        $stmt = $this->conn->prepare("DELETE FROM \"user_linked_accounts\" WHERE \"id\" = :id AND \"user_id\" = :user_id");
        if ($stmt === false) {
            throw new Exception("Prepare failed (delete linked account): " . implode(" ", $this->conn->errorInfo()));
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);


        $log_data .= "Executing DELETE with id='{$id}' and user_id='{$user_id}'\n";


        if ($stmt->execute()) {
            $affected_rows = $stmt->rowCount(); // PDO way to get affected rows
            $log_data .= "DELETE executed. Rows affected: " . $affected_rows . "\n";
            if ($affected_rows > 0) {
                $this->conn->commit();
                $resp['status'] = 'success';
                $resp['msg'] = 'Linked account successfully deleted.';
                $this->settings->set_flashdata('success', 'Linked account successfully deleted.');
            } else {
                $this->conn->rollBack();
                $resp['status'] = 'failed';
                $resp['msg'] = "Linked account not found or does not belong to you.";
            }
        } else {
            throw new Exception("Failed to delete linked account: " . implode(" ", $stmt->errorInfo()));
        }
    } catch (Exception $e) {
        $this->conn->rollBack();
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
function get_account_details_by_number()
{
    $account_number = $_POST['account_number'] ?? '';

    $resp = array('status' => 'failed', 'msg' => '');

    if (empty($account_number)) {
        $resp['msg'] = "Account number is required.";
        return json_encode($resp);
    }

    // Corrected SQL query for PostgreSQL using '||' for concatenation
    // and 'CASE WHEN' for conditional logic, which is the standard
    // SQL way supported by PostgreSQL.
    $sql = "SELECT CONCAT(firstname, ' ', COALESCE(middlename, ''), CASE WHEN middlename IS NOT NULL AND middlename != '' THEN ' ' ELSE '' END, lastname) as account_holder_name, account_number, :bank_name as bank_name FROM accounts WHERE account_number = :account_number";
    
    $stmt = $this->conn->prepare($sql);
    
    if ($stmt === false) {
        $resp['msg'] = "Prepare failed: " . implode(" ", $this->conn->errorInfo());
        return json_encode($resp);
    }

    $stmt->bindValue(':account_number', $account_number, PDO::PARAM_STR);
    $stmt->bindValue(':bank_name', $this->settings->info('name'), PDO::PARAM_STR);
    
    $stmt->execute();
    
    $row_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row_data) {
        $resp['account_holder_name'] = $row_data['account_holder_name'];
        $resp['bank_name'] = $row_data['bank_name'];
        $resp['status'] = 'success';
        $resp['msg'] = 'Account details fetched successfully.';
    } else {
        $resp['status'] = 'failed';
        $resp['msg'] = 'No internal account found with this number.';
    }

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

    $this->conn->beginTransaction();
    try {
        error_log("Master::deposit_from_linked_account: Transaction started.");

        // Step 1: Validate Primary Account PIN
        // PostgreSQL uses ? for placeholders, and bindValue is used with PDO.
        $stmt_primary_pin = $this->conn->prepare("SELECT transaction_pin FROM accounts WHERE id = ?");
        if ($stmt_primary_pin === false) {
            throw new Exception("Prepare failed to fetch primary account PIN: " . implode(", ", $this->conn->errorInfo()));
        }
        $stmt_primary_pin->bindValue(1, $primary_account_id, PDO::PARAM_INT);
        $stmt_primary_pin->execute();
        $primary_pin_qry = $stmt_primary_pin->fetch(PDO::FETCH_ASSOC);
        if ($primary_pin_qry === false) {
            throw new Exception("Primary account not found for PIN validation.");
        }
        $primary_account_stored_pin = $primary_pin_qry['transaction_pin'];
        $stmt_primary_pin->closeCursor();
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
        $stmt_linked_acc = $this->conn->prepare("SELECT account_number, is_internal_bank, account_holder_name, bank_name FROM user_linked_accounts WHERE id = ? AND user_id = ?");
        if ($stmt_linked_acc === false) {
            throw new Exception("Prepare failed to fetch linked account details: " . implode(", ", $this->conn->errorInfo()));
        }
        $stmt_linked_acc->bindValue(1, $source_linked_account_id, PDO::PARAM_INT);
        $stmt_linked_acc->bindValue(2, $user_id_for_linked, PDO::PARAM_INT);
        $stmt_linked_acc->execute();
        $linked_account_details = $stmt_linked_acc->fetch(PDO::FETCH_ASSOC);
        if ($linked_account_details === false) {
            throw new Exception("Source linked account not found or does not belong to you.");
        }
        $is_internal_linked_account = $linked_account_details['is_internal_bank'];
        $linked_account_number_for_pin_check = $linked_account_details['account_number'];
        $primary_account_number = $this->settings->userdata('account_number'); // Ensure primary_account_number is set for meta_data and insert stmt
        $stmt_linked_acc->closeCursor();
        error_log("Master::deposit_from_linked_account: Linked account details fetched.");

        // Step 3: Fetch Primary Account Balance
        $stmt_primary_balance = $this->conn->prepare("SELECT balance FROM accounts WHERE id = ? FOR UPDATE");
        if ($stmt_primary_balance === false) {
            throw new Exception("Prepare failed to fetch primary account balance: " . implode(", ", $this->conn->errorInfo()));
        }
        $stmt_primary_balance->bindValue(1, $primary_account_id, PDO::PARAM_INT);
        $stmt_primary_balance->execute();
        $primary_account_balance_qry = $stmt_primary_balance->fetch(PDO::FETCH_ASSOC);
        if ($primary_account_balance_qry === false) {
            throw new Exception("Primary account balance could not be retrieved.");
        }
        $primary_account_current_balance = $primary_account_balance_qry['balance'];
        $stmt_primary_balance->closeCursor();
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
            $stmt_source_internal_acc = $this->conn->prepare("SELECT id, balance FROM accounts WHERE account_number = ? FOR UPDATE");
            if ($stmt_source_internal_acc === false) {
                throw new Exception("Prepare failed to fetch internal source account: " . implode(", ", $this->conn->errorInfo()));
            }
            $stmt_source_internal_acc->bindValue(1, $linked_account_number_for_pin_check, PDO::PARAM_STR);
            $stmt_source_internal_acc->execute();
            $source_internal_acc_data = $stmt_source_internal_acc->fetch(PDO::FETCH_ASSOC);
            if ($source_internal_acc_data === false) {
                throw new Exception("Internal source account number does not exist in the bank's system.");
            }
            $source_internal_acc_id = $source_internal_acc_data['id'];
            $source_internal_acc_balance = $source_internal_acc_data['balance'];
            $stmt_source_internal_acc->closeCursor();
            error_log("Master::deposit_from_linked_account: Internal source account balance: {$source_internal_acc_balance}");

            if ($source_internal_acc_balance < $deposit_amount) {
                throw new Exception("Insufficient funds in the linked internal account.");
            }
            
            // Debit internal source account
            $new_source_internal_balance = $source_internal_acc_balance - $deposit_amount;
            $update_source_stmt = $this->conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
            if ($update_source_stmt === false) {
                throw new Exception("Prepare failed to debit internal source account: " . implode(", ", $this->conn->errorInfo()));
            }
            $update_source_stmt->bindValue(1, $new_source_internal_balance, PDO::PARAM_STR); // Use PARAM_STR for float/double
            $update_source_stmt->bindValue(2, $source_internal_acc_id, PDO::PARAM_INT);
            if (!$update_source_stmt->execute()) {
                throw new Exception("Failed to debit internal source account: " . implode(", ", $update_source_stmt->errorInfo()));
            }
            $update_source_stmt->closeCursor();
            error_log("Master::deposit_from_linked_account: Internal source account debited.");

            // Credit primary account
            $update_primary_stmt = $this->conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
            if ($update_primary_stmt === false) {
                throw new Exception("Prepare failed to credit primary account: " . implode(", ", $update_primary_stmt->errorInfo()));
            }
            $update_primary_stmt->bindValue(1, $new_primary_balance, PDO::PARAM_STR);
            $update_primary_stmt->bindValue(2, $primary_account_id, PDO::PARAM_INT);
            if (!$update_primary_stmt->execute()) {
                throw new Exception("Failed to credit primary account: " . implode(", ", $update_primary_stmt->errorInfo()));
            }
            $update_primary_stmt->closeCursor();
            error_log("Master::deposit_from_linked_account: Primary account credited.");

            // Record primary account transaction
            $remarks_primary = "Deposit from Internal Linked Account ({$linked_account_details['account_number']}) - {$linked_account_details['account_holder_name']}";
            $receiver_txn_code = $base_transaction_code . '-R';
            $insert_primary_txn_stmt = $this->conn->prepare("INSERT INTO transactions (account_id, type, amount, remarks, transaction_code, sender_account_number, receiver_account_number, status, transaction_type, linked_account_id, meta_data) VALUES (?, 1, ?, ?, ?, ?, ?, 'completed', 'deposit_internal_completed', ?, ?)");
            if (!$insert_primary_txn_stmt) { throw new Exception("Prepare failed for primary txn: " . implode(", ", $this->conn->errorInfo())); } // Added debug
            $insert_primary_txn_stmt->bindValue(1, $primary_account_id, PDO::PARAM_INT);
            $insert_primary_txn_stmt->bindValue(2, $deposit_amount, PDO::PARAM_STR);
            $insert_primary_txn_stmt->bindValue(3, $remarks_primary, PDO::PARAM_STR);
            $insert_primary_txn_stmt->bindValue(4, $receiver_txn_code, PDO::PARAM_STR);
            $insert_primary_txn_stmt->bindValue(5, $linked_account_details['account_number'], PDO::PARAM_STR);
            $insert_primary_txn_stmt->bindValue(6, $primary_account_number, PDO::PARAM_STR);
            $insert_primary_txn_stmt->bindValue(7, $source_linked_account_id, PDO::PARAM_INT);
            $insert_primary_txn_stmt->bindValue(8, $meta_data, PDO::PARAM_STR);
            if (!$insert_primary_txn_stmt->execute()) { throw new Exception("Failed to record primary account transaction: " . implode(", ", $insert_primary_txn_stmt->errorInfo())); }
            $insert_primary_txn_stmt->closeCursor();
            error_log("Master::deposit_from_linked_account: Primary transaction recorded.");

            // Record source account transaction
            $remarks_source = "Transfer to Linked Account ({$primary_account_number}) - {$this->settings->userdata('fullname')}";
            $sender_txn_code = $base_transaction_code . '-S';
            $insert_source_txn_stmt = $this->conn->prepare("INSERT INTO transactions (account_id, type, amount, remarks, transaction_code, sender_account_number, receiver_account_number, status, transaction_type, linked_account_id, meta_data) VALUES (?, 3, ?, ?, ?, ?, ?, 'completed', 'internal_pull_outgoing', ?, ?)");
            if (!$insert_source_txn_stmt) { throw new Exception("Prepare failed for source txn: " . implode(", ", $this->conn->errorInfo())); } // Added debug
            $insert_source_txn_stmt->bindValue(1, $source_internal_acc_id, PDO::PARAM_INT);
            $insert_source_txn_stmt->bindValue(2, $deposit_amount, PDO::PARAM_STR);
            $insert_source_txn_stmt->bindValue(3, $remarks_source, PDO::PARAM_STR);
            $insert_source_txn_stmt->bindValue(4, $sender_txn_code, PDO::PARAM_STR);
            $insert_source_txn_stmt->bindValue(5, $linked_account_details['account_number'], PDO::PARAM_STR);
            $insert_source_txn_stmt->bindValue(6, $primary_account_number, PDO::PARAM_STR);
            $insert_source_txn_stmt->bindValue(7, $source_linked_account_id, PDO::PARAM_INT);
            $insert_source_txn_stmt->bindValue(8, $meta_data, PDO::PARAM_STR);
            if (!$insert_source_txn_stmt->execute()) { throw new Exception("Failed to record internal source account transaction: " . implode(", ", $insert_source_txn_stmt->errorInfo())); }
            $insert_source_txn_stmt->closeCursor();
            error_log("Master::deposit_from_linked_account: Source transaction recorded.");
            
            $this->settings->set_userdata('balance', $new_primary_balance);
            $this->settings->set_flashdata('success', 'Deposit from internal linked account successful.');
            error_log("Master::deposit_from_linked_account: Internal deposit complete.");
        } else {
            error_log("Master::deposit_from_linked_account: Handling external linked account deposit (pending).");
            // Record external deposit request
            $insert_stmt = $this->conn->prepare("INSERT INTO transactions (account_id, type, amount, remarks, transaction_code, sender_account_number, receiver_account_number, status, transaction_type, linked_account_id, meta_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $remarks = "Deposit request from External Linked Account ({$linked_account_details['account_number']} - {$linked_account_details['bank_name']})";
            $status = 'pending';
            $specific_transaction_type = 'deposit_external_pending';
            if (!$insert_stmt) { throw new Exception("Prepare failed for external deposit txn: " . implode(", ", $this->conn->errorInfo())); } // Added debug
            $insert_stmt->bindValue(1, $primary_account_id, PDO::PARAM_INT);
            $insert_stmt->bindValue(2, 1, PDO::PARAM_INT);
            $insert_stmt->bindValue(3, $deposit_amount, PDO::PARAM_STR);
            $insert_stmt->bindValue(4, $remarks, PDO::PARAM_STR);
            $insert_stmt->bindValue(5, $base_transaction_code, PDO::PARAM_STR);
            $insert_stmt->bindValue(6, $linked_account_details['account_number'], PDO::PARAM_STR);
            $insert_stmt->bindValue(7, $primary_account_number, PDO::PARAM_STR);
            $insert_stmt->bindValue(8, $status, PDO::PARAM_STR);
            $insert_stmt->bindValue(9, $specific_transaction_type, PDO::PARAM_STR);
            $insert_stmt->bindValue(10, $source_linked_account_id, PDO::PARAM_INT);
            $insert_stmt->bindValue(11, $meta_data, PDO::PARAM_STR);
            if (!$insert_stmt->execute()) {
                throw new Exception("Failed to record external deposit request: " . implode(", ", $insert_stmt->errorInfo()));
            }
            $insert_stmt->closeCursor();
            $this->settings->set_flashdata('success', 'Deposit request from external linked account submitted successfully. It is pending admin approval.');
            error_log("Master::deposit_from_linked_account: External deposit request recorded as pending.");
        }
        $this->conn->commit();
        $resp['status'] = 'success';
        $resp['msg'] = 'Operation Successful.';
        error_log("Master::deposit_from_linked_account: Transaction committed successfully.");
    } catch (Exception $e) {
        $this->conn->rollBack();
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
    $this->conn->beginTransaction();
    try {
        $stmt_primary_acc = $this->conn->prepare("SELECT balance, transaction_pin FROM accounts WHERE id = ? FOR UPDATE");
        $stmt_primary_acc->bindValue(1, $primary_account_id, PDO::PARAM_INT);
        $stmt_primary_acc->execute();
        $primary_account_data = $stmt_primary_acc->fetch(PDO::FETCH_ASSOC);
        $primary_account_current_balance = $primary_account_data['balance'];
        $primary_account_stored_pin = $primary_account_data['transaction_pin'];
        $stmt_primary_acc->closeCursor();
        if (trim($entered_transaction_pin) != trim($primary_account_stored_pin)) {
            throw new Exception("Invalid Transaction PIN.");
        }
        if ($primary_account_current_balance < $transfer_amount) {
            throw new Exception("Insufficient funds in your primary account for this transfer.");
        }
        $user_id_for_linked = $this->settings->userdata('id');
        $stmt_linked_acc = $this->conn->prepare("SELECT account_number, is_internal_bank, account_holder_name, bank_name FROM user_linked_accounts WHERE id = ? AND user_id = ?");
        $stmt_linked_acc->bindValue(1, $destination_linked_account_id, PDO::PARAM_INT);
        $stmt_linked_acc->bindValue(2, $user_id_for_linked, PDO::PARAM_INT);
        $stmt_linked_acc->execute();
        $linked_account_details = $stmt_linked_acc->fetch(PDO::FETCH_ASSOC);
        $is_internal_destination_account = $linked_account_details['is_internal_bank'];
        $destination_linked_account_number = $linked_account_details['account_number'];
        $destination_linked_account_holder_name = $linked_account_details['account_holder_name'];
        $destination_linked_bank_name = $linked_account_details['bank_name'];
        $stmt_linked_acc->closeCursor();

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
            $stmt_destination_internal_acc = $this->conn->prepare("SELECT id, balance FROM accounts WHERE account_number = ? FOR UPDATE");
            $stmt_destination_internal_acc->bindValue(1, $destination_linked_account_number, PDO::PARAM_STR);
            $stmt_destination_internal_acc->execute();
            $destination_internal_acc_data = $stmt_destination_internal_acc->fetch(PDO::FETCH_ASSOC);
            $destination_internal_acc_id = $destination_internal_acc_data['id'];
            $destination_internal_acc_balance = $destination_internal_acc_data['balance'];
            $stmt_destination_internal_acc->closeCursor();
            if ($destination_internal_acc_id == $primary_account_id) {
                throw new Exception("You cannot transfer to your own primary account linked as an internal account.");
            }
            $new_destination_internal_balance = $destination_internal_acc_balance + $transfer_amount;
            $update_primary_stmt = $this->conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
            $update_primary_stmt->bindValue(1, $new_primary_balance, PDO::PARAM_STR);
            $update_primary_stmt->bindValue(2, $primary_account_id, PDO::PARAM_INT);
            $update_primary_stmt->execute();
            $update_primary_stmt->closeCursor();
            $update_destination_stmt = $this->conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
            $update_destination_stmt->bindValue(1, $new_destination_internal_balance, PDO::PARAM_STR);
            $update_destination_stmt->bindValue(2, $destination_internal_acc_id, PDO::PARAM_INT);
            $update_destination_stmt->execute();
            $update_destination_stmt->closeCursor();


            $remarks_primary = "Transfer to Internal Linked Account ({$destination_linked_account_number}) - {$destination_linked_account_holder_name}";
            $sender_txn_code = $base_transaction_code . '-S';
            $insert_primary_txn_stmt = $this->conn->prepare("INSERT INTO transactions (account_id, type, amount, remarks, transaction_code, sender_account_number, receiver_account_number, status, transaction_type, linked_account_id, meta_data) VALUES (?, 3, ?, ?, ?, ?, ?, 'completed', 'internal_transfer_outgoing', ?, ?)");
            $insert_primary_txn_stmt->bindValue(1, $primary_account_id, PDO::PARAM_INT);
            $insert_primary_txn_stmt->bindValue(2, $transfer_amount, PDO::PARAM_STR);
            $insert_primary_txn_stmt->bindValue(3, $remarks_primary, PDO::PARAM_STR);
            $insert_primary_txn_stmt->bindValue(4, $sender_txn_code, PDO::PARAM_STR);
            $insert_primary_txn_stmt->bindValue(5, $primary_account_number, PDO::PARAM_STR);
            $insert_primary_txn_stmt->bindValue(6, $destination_linked_account_number, PDO::PARAM_STR);
            $insert_primary_txn_stmt->bindValue(7, $destination_linked_account_id, PDO::PARAM_INT);
            $insert_primary_txn_stmt->bindValue(8, $meta_data, PDO::PARAM_STR);
            if (!$insert_primary_txn_stmt->execute()) { throw new Exception("Failed to record primary account transaction: " . implode(", ", $insert_primary_txn_stmt->errorInfo())); }
            $insert_primary_txn_stmt->closeCursor();


            $remarks_destination = "Transfer from Linked Primary Account ({$primary_account_number}) - {$this->settings->userdata('fullname')}";
            $receiver_txn_code = $base_transaction_code . '-R';
            $insert_destination_txn_stmt = $this->conn->prepare("INSERT INTO transactions (account_id, type, amount, remarks, transaction_code, sender_account_number, receiver_account_number, status, transaction_type, linked_account_id, meta_data) VALUES (?, 1, ?, ?, ?, ?, ?, 'completed', 'internal_transfer_incoming', ?, ?)");
            $insert_destination_txn_stmt->bindValue(1, $destination_internal_acc_id, PDO::PARAM_INT);
            $insert_destination_txn_stmt->bindValue(2, $transfer_amount, PDO::PARAM_STR);
            $insert_destination_txn_stmt->bindValue(3, $remarks_destination, PDO::PARAM_STR);
            $insert_destination_txn_stmt->bindValue(4, $receiver_txn_code, PDO::PARAM_STR);
            $insert_destination_txn_stmt->bindValue(5, $primary_account_number, PDO::PARAM_STR);
            $insert_destination_txn_stmt->bindValue(6, $destination_linked_account_number, PDO::PARAM_STR);
            $insert_destination_txn_stmt->bindValue(7, $destination_linked_account_id, PDO::PARAM_INT);
            $insert_destination_txn_stmt->bindValue(8, $meta_data, PDO::PARAM_STR);
            if (!$insert_destination_txn_stmt->execute()) { throw new Exception("Failed to record internal destination account transaction: " . implode(", ", $insert_destination_txn_stmt->errorInfo())); }
            $insert_destination_txn_stmt->closeCursor();


            $this->settings->set_userdata('balance', $new_primary_balance);
            $this->settings->set_flashdata('success', 'Transfer to internal linked account successful.');
        } else {
            $update_primary_stmt = $this->conn->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
            $update_primary_stmt->bindValue(1, $new_primary_balance, PDO::PARAM_STR);
            $update_primary_stmt->bindValue(2, $primary_account_id, PDO::PARAM_INT);
            $update_primary_stmt->execute();
            $update_primary_stmt->closeCursor();
            $insert_stmt = $this->conn->prepare("INSERT INTO transactions (account_id, type, amount, remarks, transaction_code, sender_account_number, receiver_account_number, status, transaction_type, linked_account_id, meta_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $remarks = "Transfer request to External Linked Account ({$destination_linked_account_number} - {$destination_linked_bank_name})";
            $insert_stmt->bindValue(1, $primary_account_id, PDO::PARAM_INT);
            $insert_stmt->bindValue(2, 3, PDO::PARAM_INT);
            $insert_stmt->bindValue(3, $transfer_amount, PDO::PARAM_STR);
            $insert_stmt->bindValue(4, $remarks, PDO::PARAM_STR);
            $insert_stmt->bindValue(5, $base_transaction_code, PDO::PARAM_STR);
            $insert_stmt->bindValue(6, $primary_account_number, PDO::PARAM_STR);
            $insert_stmt->bindValue(7, $destination_linked_account_number, PDO::PARAM_STR);
            $insert_stmt->bindValue(8, 'pending', PDO::PARAM_STR);
            $insert_stmt->bindValue(9, 'transfer_external_pending', PDO::PARAM_STR);
            $insert_stmt->bindValue(10, $destination_linked_account_id, PDO::PARAM_INT);
            $insert_stmt->bindValue(11, $meta_data, PDO::PARAM_STR);
            $insert_stmt->execute();
            $insert_stmt->closeCursor();
            $this->settings->set_userdata('balance', $new_primary_balance);
            $this->settings->set_flashdata('success', 'Transfer request to external linked account submitted successfully. It is pending admin approval.');
        }
        $this->conn->commit();
        $resp['status'] = 'success';
        $resp['msg'] = 'Operation Successful.';
    } catch (Exception $e) {
        $this->conn->rollBack();
        $resp['status'] = 'failed';
        $resp['msg'] = $e->getMessage();
        error_log("Transfer to linked account failed: " . $e->getMessage());
    }
    return json_encode($resp);
}


function approve_transaction(){
    extract($_POST);
    $resp = ['status' => 'failed', 'msg' => 'An unknown error occurred.'];
    if ($this->settings->userdata('login_type') != 1) {
        $resp['msg'] = "Unauthorized access. Only administrators can approve transactions.";
        return json_encode($resp);
    }
    
    $id_to_process = $transaction_id ?? $id;

    try {
        $this->conn->beginTransaction();

        // Fetch the pending transaction from the `pending_transactions` table
        $stmt_fetch = $this->conn->prepare('SELECT * FROM "pending_transactions" WHERE "id" = ? FOR UPDATE');
        $stmt_fetch->execute([$id_to_process]);
        $pending_transaction_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        if (!$pending_transaction_data) {
            throw new Exception("Pending transaction not found.");
        }
        
        $sender_account_id = $pending_transaction_data['sender_id'];
        $recipient_id = $pending_transaction_data['recipient_id'];
        $transaction_amount = $pending_transaction_data['amount'];
        $description = $pending_transaction_data['description'];
        
        // **CORRECTION:** Fetch the sender's account number from the 'accounts' table
        $stmt_sender_acc = $this->conn->prepare('SELECT account_number FROM "accounts" WHERE id = ?');
        $stmt_sender_acc->execute([$sender_account_id]);
        $sender_account_number = $stmt_sender_acc->fetchColumn();

        // Fetch the recipient's account number using the recipient_id
        $stmt_recipient_acc = $this->conn->prepare('SELECT account_number FROM "accounts" WHERE id = ?');
        $stmt_recipient_acc->execute([$recipient_id]);
        $recipient_account_number = $stmt_recipient_acc->fetchColumn();

        // Check if the recipient account exists internally
        $internal_recipient = $recipient_account_number ? true : false;
        
        $linked_account_id = $recipient_id; // This is the recipient_id from the pending table
        $transaction_type = 'transfer_external_debit';

        // If it's an internal transfer, credit the recipient's balance
        if ($internal_recipient) {
            $update_balance_stmt = $this->conn->prepare('UPDATE "accounts" SET "balance" = "balance" + ? WHERE "account_number" = ?');
            $update_balance_stmt->execute([$transaction_amount, $recipient_account_number]);
            
            $transaction_type = 'transfer_internal_debit';
        } else {
            // For external transfers, we need to find the recipient's ID from the `recipients` table
            $check_recipient_stmt_ext = $this->conn->prepare('SELECT "account_number" FROM "recipients" WHERE "id" = ?');
            $check_recipient_stmt_ext->execute([$recipient_id]);
            $recipient_account_number = $check_recipient_stmt_ext->fetchColumn();
            
            $transaction_type = 'transfer_external_debit';
        }
        
        // Insert into the main `transactions` table with all the correct columns
        $transaction_code = 'IMF-' . date('Ymd') . '-' . substr(md5(uniqid(rand(), true)), 0, 8);
        $insert_stmt = $this->conn->prepare('INSERT INTO "transactions" ("transaction_code", "account_id", "linked_account_id", "transaction_type", "amount", "status", "type", "remarks", "sender_account_number", "receiver_account_number") 
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insert_stmt->execute([
            $transaction_code,
            $sender_account_id,
            $linked_account_id,
            $transaction_type,
            $transaction_amount,
            'completed',
            3, // Type 3 represents an outgoing transaction
            $description,
            $sender_account_number,
            $recipient_account_number,
        ]);
        
        // Delete the transaction from the `pending_transactions` table
        $delete_stmt = $this->conn->prepare('DELETE FROM "pending_transactions" WHERE "id" = ?');
        $delete_stmt->execute([$id_to_process]);

        $this->conn->commit();
        $resp['status'] = 'success';
        $resp['msg'] = 'Transaction successfully approved and balance updated.';
        $this->settings->set_flashdata('success', 'Transaction has been approved successfully.');

    } catch (Exception $e) {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
        $resp['msg'] = $e->getMessage();
        error_log("Transaction approval failed: " . $e->getMessage());
    }
    return json_encode($resp);
}
// --------------------------------------------------


function decline_transaction() {
    extract($_POST);
    $resp = ['status' => 'failed', 'msg' => 'An unknown error occurred.'];
    
    if ($this->settings->userdata('login_type') != 1) {
        $resp['msg'] = "Unauthorized access. Only administrators can decline transactions.";
        return json_encode($resp);
    }
    
    $id_to_process = $transaction_id ?? $id;

    try {
        $this->conn->beginTransaction();

        // Check if it's a pending transaction
        $stmt_pending = $this->conn->prepare('SELECT * FROM "pending_transactions" WHERE "id" = ? FOR UPDATE');
        $stmt_pending->execute([$id_to_process]);
        $pending_transaction_data = $stmt_pending->fetch(PDO::FETCH_ASSOC);

        if ($pending_transaction_data) {
            $sender_account_id = $pending_transaction_data['sender_id'];
            $recipient_id = $pending_transaction_data['recipient_id'];
            $transaction_amount = $pending_transaction_data['amount'];
            $description = $pending_transaction_data['description'];
            
            // Refund the money to the sender's account.
            $update_balance_stmt = $this->conn->prepare('UPDATE "accounts" SET "balance" = "balance" + ? WHERE "id" = ?');
            $update_balance_stmt->execute([$transaction_amount, $sender_account_id]);
            
            // --- NEW CODE ADDED HERE ---
            // Fetch the sender's account number for logging
            $stmt_sender_acc = $this->conn->prepare('SELECT account_number FROM "accounts" WHERE id = ?');
            $stmt_sender_acc->execute([$sender_account_id]);
            $sender_account_number = $stmt_sender_acc->fetchColumn();

            // Fetch the recipient's account number from the accounts or recipients table
            $stmt_recipient_acc = $this->conn->prepare('SELECT account_number FROM "accounts" WHERE id = ?');
            $stmt_recipient_acc->execute([$recipient_id]);
            $recipient_account_number = $stmt_recipient_acc->fetchColumn();

            // If the recipient is external, fetch the account number from the recipients table
            if (!$recipient_account_number) {
                $stmt_ext_acc = $this->conn->prepare('SELECT account_number FROM "recipients" WHERE id = ?');
                $stmt_ext_acc->execute([$recipient_id]);
                $recipient_account_number = $stmt_ext_acc->fetchColumn();
            }

            // Insert a new record into the transactions table with a 'declined' status
            $transaction_code = 'IMF-' . date('Ymd') . '-' . substr(md5(uniqid(rand(), true)), 0, 8);
            $insert_stmt = $this->conn->prepare('INSERT INTO "transactions" ("transaction_code", "account_id", "linked_account_id", "transaction_type", "amount", "status", "type", "remarks", "sender_account_number", "receiver_account_number") 
                                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $insert_stmt->execute([
                $transaction_code,
                $sender_account_id,
                $recipient_id,
                'transfer_external_declined',
                $transaction_amount,
                'declined',
                3, // Type 3 represents an outgoing transaction
                'Declined: ' . $description,
                $sender_account_number,
                $recipient_account_number,
            ]);
            // --- END OF NEW CODE ---
            
            // Delete the transaction from the pending_transactions table.
            $delete_stmt = $this->conn->prepare('DELETE FROM "pending_transactions" WHERE "id" = ?');
            $delete_stmt->execute([$id_to_process]);
            
            $this->conn->commit();
            $resp['status'] = 'success';
            $resp['msg'] = 'Pending transaction successfully declined and funds refunded.';
            $this->settings->set_flashdata('success', 'Pending transaction has been declined successfully.');
        } else {
            // If not found in pending, check if it exists in the main transactions table
            $stmt_main = $this->conn->prepare('SELECT * FROM "transactions" WHERE "id" = ? AND "status" != \'declined\' FOR UPDATE');
            $stmt_main->execute([$id_to_process]);
            $main_transaction_data = $stmt_main->fetch(PDO::FETCH_ASSOC);

            if ($main_transaction_data) {
                // If found, this means we are changing a 'completed' transaction to 'declined'.
                // Refund the money if it was a debit.
                if (strpos($main_transaction_data['transaction_type'], 'debit') !== false || strpos($main_transaction_data['transaction_type'], 'withdraw') !== false) {
                    $account_id = $main_transaction_data['account_id'];
                    $transaction_amount = $main_transaction_data['amount'];
                    
                    $update_balance_stmt = $this->conn->prepare('UPDATE "accounts" SET "balance" = "balance" + ? WHERE "id" = ?');
                    $update_balance_stmt->execute([$transaction_amount, $account_id]);
                    
                    // Update the transaction status to 'declined'
                    $update_status_stmt = $this->conn->prepare('UPDATE "transactions" SET "status" = \'declined\' WHERE "id" = ?');
                    $update_status_stmt->execute([$id_to_process]);
                    
                    $this->conn->commit();
                    $resp['status'] = 'success';
                    $resp['msg'] = 'Completed transaction successfully declined and funds refunded.';
                    $this->settings->set_flashdata('success', 'Completed transaction has been declined successfully.');
                } else {
                    throw new Exception("This type of transaction cannot be declined after completion.");
                }
            } else {
                throw new Exception("Transaction not found or already declined.");
            }
        }
    } catch (Exception $e) {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
        $resp['msg'] = $e->getMessage();
        error_log("Transaction decline failed: " . $e->getMessage());
    }
    return json_encode($resp);
}


// --------------------------------------------------

function delete_transaction(){
    extract($_POST);
    $resp = ['status' => 'failed', 'msg' => 'An unknown error occurred.'];
    if ($this->settings->userdata('login_type') != 1) {
        $resp['msg'] = "Unauthorized access.";
        return json_encode($resp);
    }
    
    try {
        $this->conn->beginTransaction();

        // Try to delete from main transactions table
        $stmt_main = $this->conn->prepare('DELETE FROM "transactions" WHERE "id" = ?');
        $stmt_main->execute([$id]);
        $rows_affected = $stmt_main->rowCount();

        // If not found in main table, try to delete from pending transactions table
        if ($rows_affected == 0) {
            $stmt_pending = $this->conn->prepare('DELETE FROM "pending_transactions" WHERE "id" = ?');
            $stmt_pending->execute([$id]);
            $rows_affected = $stmt_pending->rowCount();
        }

        if ($rows_affected > 0) {
            $this->conn->commit();
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Transaction successfully deleted.");
        } else {
            $this->conn->rollBack();
            $resp['msg'] = "Transaction not found or already deleted.";
        }
    } catch (PDOException $e) {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
        $resp['msg'] = "Database error: " . $e->getMessage();
        error_log("Delete transaction failed: " . $e->getMessage());
    }
    return json_encode($resp);
}

// --------------------------------------------------

function save_transaction(){
    extract($_POST);
    $resp = ['status' => 'failed', 'msg' => 'An unknown error occurred.'];
    if ($this->settings->userdata('login_type') != 1) {
        $resp['msg'] = "Unauthorized access.";
        return json_encode($resp);
    }
    
    if (!isset($id)) {
        $resp['msg'] = "Transaction ID is missing.";
        return json_encode($resp);
    }

    try {
        $this->conn->beginTransaction();

        // Check if the transaction is pending or completed
        $is_pending = isset($_POST['is_pending']) && $_POST['is_pending'] === 'true';

        if ($is_pending) {
            // Logic for updating pending transactions
            $stmt_fetch = $this->conn->prepare('SELECT * FROM "pending_transactions" WHERE "id" = ? FOR UPDATE');
            $stmt_fetch->execute([$id]);
            $original_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

            if (!$original_data) {
                throw new Exception("Pending transaction not found.");
            }
            
            // Refund logic is removed. The amount in the pending transaction is simply updated.
            // The actual debit/credit should only happen when the transaction is approved.
            
            // Build the update query for pending_transactions table
            $data = "";
            $params = [];
            
            if (isset($amount)) {
                $data .= '"amount" = ?, ';
                $params[] = $amount;
            }
            if (isset($remarks)) {
                // Corrected column name to match 'pending_transactions' table
                $data .= '"description" = ?, ';
                $params[] = $remarks;
            }
            
            // Only update the status to completed or declined here.
            if (isset($status) && in_array($status, ['completed', 'declined'])) {
                 $data .= '"status" = ?, ';
                 $params[] = $status;
            }
            
            if (empty($data)) {
                $resp['msg'] = "No fields to update.";
                return json_encode($resp);
            }
            
            $data = rtrim($data, ', ');
            $params[] = $id;
            
            $sql = "UPDATE \"pending_transactions\" SET {$data} WHERE \"id\" = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            // If the status is changed to 'completed' or 'declined', handle it accordingly
            if (isset($status)) {
                if ($status == 'completed') {
                    // Logic to approve and move to the main transactions table
                    $sender_account_id = $original_data['sender_id'];
                    $recipient_account_id = $original_data['recipient_id'];
                    $transaction_amount = $amount ?? $original_data['amount'];
                    
                    // Add amount to recipient's balance
                    $update_balance_stmt_credit = $this->conn->prepare('UPDATE "accounts" SET "balance" = "balance" + ? WHERE "id" = ?');
                    $update_balance_stmt_credit->execute([$transaction_amount, $recipient_account_id]);
                    
                    // Deduct amount from sender's balance (this should have been done when the pending transaction was created)
                    $update_balance_stmt_debit = $this->conn->prepare('UPDATE "accounts" SET "balance" = "balance" - ? WHERE "id" = ?');
                    $update_balance_stmt_debit->execute([$transaction_amount, $sender_account_id]);

                    // Generate a unique transaction code
                    $transaction_code = 'IMF-' . date('Ymd') . '-' . substr(md5(uniqid(rand(), true)), 0, 8);

                    // Insert the debit record into the main transactions table
                    $insert_debit_stmt = $this->conn->prepare('
                        INSERT INTO "transactions" ("transaction_code", "account_id", "linked_account_id", "transaction_type", "type", "amount", "remarks", "status", "date_created") 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ');
                    $insert_debit_stmt->execute([
                        $transaction_code,
                        $sender_account_id,
                        $recipient_account_id,
                        'transfer_external_debit',
                        '2', // '2' for debit
                        $transaction_amount,
                        $remarks ?? $original_data['description'],
                        'completed'
                    ]);

                    // Insert the credit record into the main transactions table
                    $insert_credit_stmt = $this->conn->prepare('
                        INSERT INTO "transactions" ("transaction_code", "account_id", "linked_account_id", "transaction_type", "type", "amount", "remarks", "status", "date_created") 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ');
                    $insert_credit_stmt->execute([
                        $transaction_code,
                        $recipient_account_id,
                        $sender_account_id,
                        'transfer_external_credit',
                        '1', // '1' for credit
                        $transaction_amount,
                        $remarks ?? $original_data['description'],
                        'completed'
                    ]);
                    
                    // Delete the pending transaction
                    $delete_stmt = $this->conn->prepare('DELETE FROM "pending_transactions" WHERE "id" = ?');
                    $delete_stmt->execute([$id]);

                } elseif ($status == 'declined') {
                    // Logic to decline and delete
                    // It's a good idea to refund the debited amount here if it was already deducted when creating the pending transaction.
                    // Assuming it was, we need to refund it.
                    $sender_account_id = $original_data['sender_id'];
                    $amount_to_refund = $amount ?? $original_data['amount'];
                    $update_balance_stmt_refund = $this->conn->prepare('UPDATE "accounts" SET "balance" = "balance" + ? WHERE "id" = ?');
                    $update_balance_stmt_refund->execute([$amount_to_refund, $sender_account_id]);
                    
                    // Delete the pending transaction
                    $delete_stmt = $this->conn->prepare('DELETE FROM "pending_transactions" WHERE "id" = ?');
                    $delete_stmt->execute([$id]);
                }
            }

        } else {
            // Logic for updating main transactions table
            $stmt_fetch = $this->conn->prepare('SELECT * FROM "transactions" WHERE "id" = ? FOR UPDATE');
            $stmt_fetch->execute([$id]);
            $original_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

            if (!$original_data) {
                throw new Exception("Transaction not found.");
            }
            
            // Build the update query for the transactions table
            $data = "";
            $params = [];
            if (isset($amount)) {
                $data .= '"amount" = ?, ';
                $params[] = $amount;
            }
            if (isset($remarks)) {
                $data .= '"remarks" = ?, ';
                $params[] = $remarks;
            }
            if (isset($status)) {
                $data .= '"status" = ?, ';
                $params[] = $status;
            }
            
            if (empty($data)) {
                $resp['msg'] = "No fields to update.";
                return json_encode($resp);
            }
            
            $data = rtrim($data, ', ');
            $params[] = $id;
            
            $sql = "UPDATE \"transactions\" SET {$data} WHERE \"id\" = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
        }
        
        $this->conn->commit();
        $resp['status'] = 'success';
        $this->settings->set_flashdata('success', "Transaction successfully updated.");

    } catch (Exception $e) {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
        $resp['msg'] = "Database error: " . $e->getMessage();
        error_log("Save transaction failed: " . $e->getMessage());
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
    $stmt = $this->conn->prepare("UPDATE \"accounts\" SET \"status\" = 'Active' WHERE id = ?");
    $update = $stmt->execute([$id]);

    if($update){
        $resp['status'] = 'success';
        $resp['msg'] = "Account has been activated successfully.";
        $this->settings->set_flashdata('success', $resp['msg']);
    }else{
        $resp['status'] = 'failed';
        $resp['msg'] = "An error occurred during activation.";
    }
    $stmt->closeCursor();
    return json_encode($resp);
}

    function delete_account(){
        extract($_POST);
        $resp = array('status' => 'failed', 'msg' => '');


        if($this->settings->userdata('login_type') != 1){
            $resp['msg'] = "You are not authorized to perform this action.";
            return json_encode($resp);
        }

        // Corrected query for PostgreSQL: uses double quotes for the table name
        // The id is sanitized with PDO's prepare and execute to prevent SQL injection.
        try {
            $accounts_stmt = $this->conn->prepare('SELECT firebase_uid FROM "accounts" WHERE id = :id');
            $accounts_stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $accounts_stmt->execute();
            $firebase_uid = ($accounts_stmt->rowCount() > 0) ? $accounts_stmt->fetch(PDO::FETCH_ASSOC)['firebase_uid'] : null;

            $this->conn->beginTransaction();

            // Corrected DELETE query for PostgreSQL
            $delete_acc_stmt = $this->conn->prepare('DELETE FROM "accounts" WHERE id = :id');
            $delete_acc_stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $delete_acc = $delete_acc_stmt->execute();
            if(!$delete_acc) throw new Exception("Failed to delete from accounts table.");

            // Corrected DELETE query for PostgreSQL
            $delete_trans_stmt = $this->conn->prepare('DELETE FROM "transactions" WHERE account_id = :id');
            $delete_trans_stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $delete_trans_stmt->execute();
            
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


        } catch (PDOException $e) {
            $this->conn->rollback();
            $resp['msg'] = "An error occurred during database operation: " . $e->getMessage();
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
    try {
        $acc_stmt = $this->conn->prepare('SELECT * FROM "accounts" WHERE id = :id');
        $acc_stmt->execute([':id' => $account_id]);
        if($acc_stmt->rowCount() < 1){
            $resp['msg'] = "Target account does not exist.";
            return json_encode($resp);
        }
        $res = $acc_stmt->fetch(PDO::FETCH_ASSOC);
        $current_balance = $res['balance'];
    } catch (PDOException $e) {
        $resp['msg'] = "Database error during account lookup: " . $e->getMessage();
        return json_encode($resp);
    }
    if($transaction_type == 2 && $current_balance < $amount){
        $resp['msg'] = "Insufficient balance for this debit transaction.";
        return json_encode($resp);
    }
    $new_balance = ($transaction_type == 1) ? $current_balance + $amount : $current_balance - $amount;
    $this->conn->beginTransaction();
    try {
        $update_bal_stmt = $this->conn->prepare('UPDATE "accounts" SET "balance" = :new_balance WHERE id = :account_id');
        $update_bal_stmt->execute([':new_balance' => $new_balance, ':account_id' => $account_id]);
        $sql = 'INSERT INTO "transactions" ("account_id", "type", "amount", "remarks") VALUES (:account_id, :type, :amount, :remarks)';
        $save_trans_stmt = $this->conn->prepare($sql);
        $save_trans_stmt->execute([
            ':account_id' => $account_id,
            ':type' => $transaction_type,
            ':amount' => $amount,
            ':remarks' => $remarks
        ]);
        $this->conn->commit();
        $resp['status'] = 'success';
        $resp['msg'] = "Transaction has been saved successfully.";
        $this->settings->set_flashdata('success', $resp['msg']);
    } catch (PDOException $e) {
        $this->conn->rollBack();
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
        case 'delete_transaction':
            echo $Master->delete_transaction();
            break;
        case 'save_transaction':
            echo $Master->save_transaction();
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