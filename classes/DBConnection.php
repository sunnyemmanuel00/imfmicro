<?php
if(!defined('DB_SERVER')){
    require_once(__DIR__ . "/../initialize.php");
}

class DBConnection{

    public $conn;

    public function __construct(){

        if (!isset($this->conn)) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            try {
                // Check if environment variables are set for Render deployment
                if (getenv('DB_HOST') !== false) {
                    // --- RENDER DEPLOYMENT SETTINGS ---
                    $db_host = getenv('DB_HOST');
                    $db_user = getenv('DB_USER');
                    $db_pass = getenv('DB_PASS');
                    $db_name = getenv('DB_NAME');
                    
                    $this->conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
                } else {
                    // --- LOCAL XAMPP DATABASE SETTINGS ---
                    $db_host = 'localhost';
                    $db_user = 'root';
                    $db_pass = '';
                    $db_name = 'banking_db';

                    $this->conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
                }
            } catch (Exception $e) {
                throw new Exception('Database Connection Failed: ' . $e->getMessage());
            }

            mysqli_report(MYSQLI_REPORT_OFF);
        }
    }

    public function __destruct(){
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>