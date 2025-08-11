<?php
// C:\xampp\htdocs\banking\classes\Users.php
require_once('../config.php');
Class Users extends DBConnection {
    private $settings;
    public function __construct(){
        global $_settings;
        $this->settings = $_settings;
        parent::__construct();
    }
    public function __destruct(){
        parent::__destruct();
    }
    public function save_users(){
        extract($_POST);

        // --- FIX: Ensure the 'id' field is a valid integer or 0 ---
        // This is the direct fix for the error you are seeing.
        // It checks if 'id' is set, not empty, and is a number. Otherwise, it defaults to 0.
        $id_to_check = (isset($id) && trim($id) !== '' && is_numeric($id)) ? (int)$id : 0;
        
        // --- FIX: Ensure the 'type' field is a valid integer or 1 ---
        if (!isset($type) || trim($type) === '' || !is_numeric($type)) {
            $type = 1;
        }

        $data = [];
        $params = [];

        // Explicitly check for fields to ensure they are not empty, and build the query parts
        if(isset($firstname) && trim($firstname) !== '') {
            $data[] = "firstname = ?";
            $params[] = $firstname;
        }
        if(isset($lastname) && trim($lastname) !== '') {
            $data[] = "lastname = ?";
            $params[] = $lastname;
        }
        if(isset($username) && trim($username) !== '') {
            $data[] = "username = ?";
            $params[] = $username;
        }
        
        // The 'type' field is now always a valid integer due to our check above.
        $data[] = "type = ?";
        $params[] = $type;
        
        // Handle password update
        if(!empty($password)){
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $data[] = "password = ?";
            $params[] = $hashed_password;
        }

        // Handle avatar image upload
        if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
            $fname = 'uploads/'.strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
            $move = move_uploaded_file($_FILES['img']['tmp_name'],'../'. $fname);
            if($move){
                $data[] = "avatar = ?";
                $params[] = $fname;
                if(isset($_SESSION['userdata']['avatar']) && is_file('../'.$_SESSION['userdata']['avatar'])) {
                    unlink('../'.$_SESSION['userdata']['avatar']);
                }
            }
        }
        
        // Check if required fields are missing
        if(empty($data)){
            return 3;
        }

        $set_clause = implode(', ', $data);

        // Check if username already exists, using the now-safe $id_to_check
        $check_stmt = $this->conn->prepare("SELECT COUNT(id) FROM users WHERE username = ? AND id != ?");
        $check_stmt->execute([$username, $id_to_check]);
        $username_count = $check_stmt->fetchColumn();

        if ($username_count > 0) {
            return 2; // Username already exists
        }

        if(empty($id_to_check)){
            // Insert new user logic using correct PostgreSQL syntax
            $sql_columns = implode(', ', array_map(function($item) {
                return explode(' ', $item)[0];
            }, $data));
            $sql_placeholders = implode(', ', array_fill(0, count($data), '?'));

            $sql = "INSERT INTO users ({$sql_columns}) VALUES ({$sql_placeholders})";
            $qry = $this->conn->prepare($sql);
            $result = $qry->execute($params);
            
            if($result){
                $this->settings->set_flashdata('success','User Details successfully saved.');
                foreach($_POST as $k => $v){
                    if($k != 'id'){
                        $this->settings->set_userdata($k,$v);
                    }
                }
                return 1;
            } else {
                return 3;
            }
        } else {
            // Update existing user logic using correct PostgreSQL syntax
            $sql = "UPDATE users SET {$set_clause} WHERE id = ?";
            $params[] = $id_to_check;
            
            $update_stmt = $this->conn->prepare($sql);
            $update = $update_stmt->execute($params);

            if($update){
                $this->settings->set_flashdata('success','User Details successfully updated.');
                // Update session data
                foreach($_POST as $k => $v){
                    if($k != 'id'){
                        $this->settings->set_userdata($k,$v);
                    }
                }
                if(isset($fname)) {
                    $this->settings->set_userdata('avatar',$fname);
                }
                return 1;
            } else {
                return "UPDATE users SET {$set_clause} WHERE id = {$id_to_check}";
            }
        }
    }

    public function delete_users(){
        extract($_POST);
        $qry = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $result = $qry->execute([$id]);
        if($result){
            $this->settings->set_flashdata('success','User Details successfully deleted.');
            return 1;
        }else{
            return false;
        }
    }

    public function save_client(){
        extract($_POST);
        $data = [];
        $params = [];
        
        // Prepare data for the update statement, filtering out empty values
        foreach($_POST as $k => $v){
            // Ensure the value is not empty before adding to the query
            if(!in_array($k, array('id','password')) && trim($v) !== ''){
                $data[] = "{$k} = ?";
                $params[] = $v;
            }
        }
        
        if(isset($password) && !empty($password)){
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $data[] = "password = ?";
            $params[] = $hashed_password;
        }
        
        $data[] = "generated_password = ''";
        
        $set_clause = implode(', ', $data);
        $sql = "UPDATE accounts SET {$set_clause} WHERE id = ?";
        $params[] = $id;
        
        $stmt = $this->conn->prepare($sql);
        $save = $stmt->execute($params);

        if($save){
            $this->settings->set_flashdata('success','User Details successfully updated.');
            foreach($_POST as $k => $v){
                if(!in_array($k,array('id','password'))){
                    $this->settings->set_userdata($k,$v);
                }
            }
            return 1;
        }else{
            $resp['error'] = $sql;
            return json_encode($resp);
        }
    } 
}

$users = new Users();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
switch ($action) {
    case 'save':
        echo $users->save_users();
    break;
    case 'save_client':
        echo $users->save_client();
    break;
    case 'delete':
        echo $users->delete_users();
    break;
    default:
        // echo $sysset->index();
        break;
}
?>
