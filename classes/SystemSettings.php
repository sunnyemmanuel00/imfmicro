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
			$_SESSION['system_info'] = [];
			
			if ($this->db_type === 'pgsql') {
				// Use PDO for PostgreSQL
				try {
					$stmt = $this->conn->prepare("SELECT * FROM system_info");
					$stmt->execute();
					$qry = $stmt->fetchAll(PDO::FETCH_ASSOC);
					foreach($qry as $row){
						$_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
					}
				} catch (PDOException $e) {
					error_log("PostgreSQL query error: " . $e->getMessage());
				}
			} else {
				// Use MySQLi for XAMPP
				$qry = $this->conn->query("SELECT * FROM system_info");
				while($row = $qry->fetch_assoc()){
					$_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
				}
			}
		}
	}
	function update_system_info(){
		$_SESSION['system_info'] = [];
		if ($this->db_type === 'pgsql') {
			// Use PDO for PostgreSQL
			try {
				$stmt = $this->conn->prepare("SELECT * FROM system_info");
				$stmt->execute();
				$qry = $stmt->fetchAll(PDO::FETCH_ASSOC);
				foreach($qry as $row){
					$_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
				}
			} catch (PDOException $e) {
				error_log("PostgreSQL query error: " . $e->getMessage());
			}
		} else {
			// Use MySQLi for XAMPP
			$qry = $this->conn->query("SELECT * FROM system_info");
			while($row = $qry->fetch_assoc()){
				$_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
			}
		}
		return true;
	}
	function update_settings_info(){
		foreach ($_POST as $key => $value) {
			if(!in_array($key, array("about_us", "privacy_policy", "img", "cover"))) {
				if ($this->db_type === 'pgsql') {
					// Use PDO for PostgreSQL
					try {
						$stmt = $this->conn->prepare("SELECT COUNT(*) FROM system_info WHERE meta_field = ?");
						$stmt->execute([$key]);
						$exists = $stmt->fetchColumn() > 0;
						if ($exists) {
							$stmt = $this->conn->prepare("UPDATE system_info SET meta_value = ? WHERE meta_field = ?");
							$stmt->execute([$value, $key]);
						} else {
							$stmt = $this->conn->prepare("INSERT INTO system_info (meta_value, meta_field) VALUES (?, ?)");
							$stmt->execute([$value, $key]);
						}
					} catch (PDOException $e) {
						error_log("PostgreSQL update error: " . $e->getMessage());
					}
				} else {
					// Use MySQLi for XAMPP
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
				if ($this->db_type === 'pgsql') {
					try {
						$stmt = $this->conn->prepare("UPDATE system_info SET meta_value = ? WHERE meta_field = 'logo'");
						$stmt->execute([$fname]);
					} catch (PDOException $e) {
						error_log("PostgreSQL logo update error: " . $e->getMessage());
					}
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
				if ($this->db_type === 'pgsql') {
					try {
						$stmt = $this->conn->prepare("UPDATE system_info SET meta_value = ? WHERE meta_field = 'cover'");
						$stmt->execute([$fname]);
					} catch (PDOException $e) {
						error_log("PostgreSQL cover update error: " . $e->getMessage());
					}
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