<?php
if(!defined('DB_SERVER')){
    require_once(__DIR__ . "/../initialize.php");
}

class DBConnection{

    public $conn;

    public function __construct(){

        if (!isset($this->conn)) {

            try {
                // Check for Render's DATABASE_URL for PostgreSQL connection
                if (getenv('DATABASE_URL') !== false) {
                    // --- RENDER DEPLOYMENT SETTINGS (PostgreSQL) ---
                    $database_url = getenv('DATABASE_URL');
                    $this->conn = pg_connect($database_url);

                    if (!$this->conn) {
                        throw new Exception('PostgreSQL Connection Failed: Could not connect to the database.');
                    }

                } else {
                    // --- LOCAL XAMPP DATABASE SETTINGS (MySQL) ---
                    $db_host = 'localhost';
                    $db_user = 'root';
                    $db_pass = '';
                    $db_name = 'banking_db';

                    // This allows us to catch connection errors instead of the script dying
                    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                    // Use DB_SERVER constant for the host to avoid socket issues
                    $this->conn = new mysqli(DB_SERVER, $db_user, $db_pass, $db_name);
                    mysqli_report(MYSQLI_REPORT_OFF);
                }

            } catch (Exception $e) {
                // CRITICAL FIX: Instead of die(), throw an exception.
                throw new Exception('Database Connection Failed: ' . $e->getMessage());
            }
        }
    }
    
    // The destructor has been commented out to prevent premature connection closure.
    // PHP will automatically close the connection at the end of the script.
    /*
    public function __destruct(){
        if ($this->conn) {
            // Close the connection based on which one was used
            if (getenv('DATABASE_URL') !== false) {
                pg_close($this->conn);
            } else {
                $this->conn->close();
            }
        }
    }
    */
}
?>
