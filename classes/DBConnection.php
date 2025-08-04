<?php
if(!defined('DB_SERVER')){
    require_once(__DIR__ . "/../initialize.php");
}

class DBConnection{

    public $conn;

    public function __construct(){

        if (!isset($this->conn)) {

            // This allows us to catch connection errors instead of the script dying
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            try {
                // This checks if the code is running on Google App Engine
                if (isset($_SERVER['GAE_ENV']) && $_SERVER['GAE_ENV'] === 'standard') {

                    // --- LIVE GOOGLE CLOUD SQL SETTINGS ---
                    $db_user = 'root'; 
                    $db_pass = 'Dom@418nic'; // Your LIVE database password
                    $db_name = 'banking_db'; 
                    $db_socket = '/cloudsql/imfmicro:us-central1:imfmicro'; // Your instance connection name

                    $this->conn = new mysqli(null, $db_user, $db_pass, $db_name, null, $db_socket);

                } else {

                    // --- LOCAL XAMPP DATABASE SETTINGS ---
                    $db_host = 'localhost';
                    $db_user = 'root';
                    $db_pass = ''; // Your XAMPP password
                    $db_name = 'banking_db';

                    $this->conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
                }
            } catch (Exception $e) {
                // CRITICAL FIX: Instead of die(), throw an exception.
                // This prevents the script from halting with a non-JSON error.
                throw new Exception('Database Connection Failed: ' . $e->getMessage());
            }

            // Turn off strict reporting once connection is successful
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