<?php
if(!class_exists('DBConnection')){
    require_once(__DIR__ . '/../config.php');
    require_once(__DIR__ . '/DBConnection.php');
}
class SystemSettings extends DBConnection{
    public function __construct(){
        parent::__construct();
    }
    function check_connection(){
        return($this->conn);
    }
    function load_system_info(){
        if(!isset($_SESSION['system_info'])){
            $sql = "SELECT * FROM system_info";
            $_SESSION['system_info'] = [];

            if (getenv('DATABASE_URL') !== false) {
                // PostgreSQL on Render
                $qry = pg_query($this->conn, $sql);
                while($row = pg_fetch_assoc($qry)){
                    $_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
                }
            } else {
                // MySQL on XAMPP
                $qry = $this->conn->query($sql);
                while($row = $qry->fetch_assoc()){
                    $_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
                }
            }
        }
    }
    function update_system_info(){
        $sql = "SELECT * FROM system_info";
        $_SESSION['system_info'] = [];

        if (getenv('DATABASE_URL') !== false) {
            // PostgreSQL on Render
            $qry = pg_query($this->conn, $sql);
            while($row = pg_fetch_assoc($qry)){
                $_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
            }
        } else {
            // MySQL on XAMPP
            $qry = $this->conn->query($sql);
            while($row = $qry->fetch_assoc()){
                $_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
            }
        }
        return true;
    }
    function update_settings_info(){
        foreach ($_POST as $key => $value) {
            if(!in_array($key, array("about_us", "privacy_policy", "img", "cover"))) {
                if (getenv('DATABASE_URL') !== false) {
                    // PostgreSQL on Render
                    $check_qry = pg_query($this->conn, "SELECT COUNT(*) FROM system_info WHERE meta_field = '".pg_escape_string($this->conn, $key)."'");
                    $exists = pg_fetch_row($check_qry)[0] > 0;
                    if ($exists) {
                        pg_query_params($this->conn, "UPDATE system_info SET meta_value = $1 WHERE meta_field = $2", array($value, $key));
                    } else {
                        pg_query_params($this->conn, "INSERT INTO system_info (meta_value, meta_field) VALUES ($1, $2)", array($value, $key));
                    }
                } else {
                    // MySQL on XAMPP
                    $stmt_update = $this->conn->prepare("UPDATE system_info SET meta_value = ? WHERE meta_field = ?");
                    $stmt_insert = $this->conn->prepare("INSERT INTO system_info (meta_value, meta_field) VALUES (?, ?)");

                    $check_qry = $this->conn->query("SELECT COUNT(*) FROM system_info WHERE meta_field = '{$this->conn->real_escape_string($key)}'");
                    $exists = $check_qry->fetch_row()[0] > 0;

                    if ($exists) {
                        $stmt_update->bind_param("ss", $value, $key);
                        $stmt_update->execute();
                    } else {
                        $stmt_insert->bind_param("ss", $value, $key);
                        $stmt_insert->execute();
                    }
                    if ($stmt_update) $stmt_update->close();
                    if ($stmt_insert) $stmt_insert->close();
                }
            }
        }

        // Handle image uploads (logo)
        if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
            $fname = 'uploads/'.strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
            $target_file = __DIR__ . '/../' . $fname;
            if(move_uploaded_file($_FILES['img']['tmp_name'], $target_file)){
                if(isset($_SESSION['system_info']['logo']) && is_file(__DIR__ . '/../'.$_SESSION['system_info']['logo'])) {
                    unlink(__DIR__ . '/../'.$_SESSION['system_info']['logo']);
                }
                if (getenv('DATABASE_URL') !== false) {
                    pg_query($this->conn, "UPDATE system_info set meta_value = '".pg_escape_string($this->conn, $fname)."' where meta_field = 'logo' ");
                } else {
                    $this->conn->query("UPDATE system_info set meta_value = '{$this->conn->real_escape_string($fname)}' where meta_field = 'logo' ");
                }
            }
        }
        // Handle cover image uploads
        if(isset($_FILES['cover']) && $_FILES['cover']['tmp_name'] != ''){
            $fname = 'uploads/'.strtotime(date('y-m-d H:i')).'_'.$_FILES['cover']['name'];
            $target_file = __DIR__ . '/../' . $fname;
            if(move_uploaded_file($_FILES['cover']['tmp_name'], $target_file)){
                if(isset($_SESSION['system_info']['cover']) && is_file(__DIR__ . '/../'.$_SESSION['system_info']['cover'])) {
                    unlink(__DIR__ . '/../'.$_SESSION['system_info']['cover']);
                }
                if (getenv('DATABASE_URL') !== false) {
                    pg_query($this->conn, "UPDATE system_info set meta_value = '".pg_escape_string($this->conn, $fname)."' where meta_field = 'cover' ");
                } else {
                    $this->conn->query("UPDATE system_info set meta_value = '{$this->conn->real_escape_string($fname)}' where meta_field = 'cover' ");
                }
            }
        }
        $update = $this->update_system_info();
        $flash = $this->set_flashdata('success','System Info Successfully Updated.');
        if($update && $flash){
            return true;
        }
        return false;
    }
    function set_userdata($field='',$value=''){
        if(!empty($field) && $value !== ''){
            $_SESSION['userdata'][$field]= $value;
        }
    }
    function userdata($field = ''){
        if(!empty($field)){
            return isset($_SESSION['userdata'][$field]) ? $_SESSION['userdata'][$field] : null;
        }else{
            return false;
        }
    }
    function set_flashdata($flash='',$value=''){
        if(!empty($flash) && $value !== ''){
            $_SESSION['flashdata'][$flash]= $value;
            return true;
        }
        return false;
    }
    function chk_flashdata($flash = ''){
        return isset($_SESSION['flashdata'][$flash]);
    }
    function flashdata($flash = ''){
        if(!empty($flash) && isset($_SESSION['flashdata'][$flash])){
            $_tmp = $_SESSION['flashdata'][$flash];
            unset($_SESSION['flashdata'][$flash]);
            return $_tmp;
        }else{
            return false;
        }
    }
    function sess_des(){
        if(isset($_SESSION['userdata'])){
            unset($_SESSION['userdata']);
        }
        return true;
    }
    function info($field=''){
        if(!empty($field)){
            return isset($_SESSION['system_info'][$field]) ? $_SESSION['system_info'][$field] : false;
        }else{
            return false;
        }
    }
    function set_info($field='',$value=''){
        if(!empty($field) && $value !== ''){
            $_SESSION['system_info'][$field] = $value;
        }
    }
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$_settings = new SystemSettings();
$_settings->load_system_info();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'update_settings') {
    $status = $_settings->update_settings_info();
    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => $status ? 'success' : 'error']);
    } else {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}
?>