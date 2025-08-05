<?php
if(!defined('DB_SERVER')){
    require_once(__DIR__ . "/../initialize.php");
}

class DBConnection{

    public $conn;
    public $db_type;

    public function __construct(){

        if (!isset($this->conn)) {

            // Attempt to connect to PostgreSQL first (for Render deployment)
            $database_url = getenv('DATABASE_URL');
            if ($database_url) {
                try {
                    $url = parse_url($database_url);
                    $dsn = "pgsql:host={$url['host']};port={$url['port']};dbname={$url['path']};user={$url['user']};password={$url['pass']}";
                    $this->conn = new PDO($dsn);
                    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->db_type = 'pgsql';
                    return;
                } catch (PDOException $e) {
                    error_log("PostgreSQL Connection Failed: " . $e->getMessage());
                    // Fall through to MySQL if PostgreSQL fails
                }
            }

            // Fallback to Local MySQL (for development)
            try {
                $db_host = 'localhost';
                $db_user = 'root';
                $db_pass = '';
                $db_name = 'banking_db';

                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

                $this->conn = new mysqli(DB_SERVER, $db_user, $db_pass, $db_name);
                
                if ($this->conn->connect_error) {
                    throw new Exception("Connection failed: " . $this->conn->connect_error);
                }

                mysqli_report(MYSQLI_REPORT_OFF);
                $this->db_type = 'mysqli';

            } catch (Exception $e) {
                throw new Exception('Database Connection Failed: ' . $e->getMessage());
            }
        }
    }
    
    public function __destruct(){
        if ($this->conn && $this->db_type === 'mysqli' && $this->conn->ping()) {
            $this->conn->close();
        }
    }
}
?>