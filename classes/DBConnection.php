<?php

if(!defined('base_url')) define('base_url','http://localhost/banking/');
if(!defined('DB_SERVER')) define('DB_SERVER','localhost');
if(!defined('DB_USERNAME')) define('DB_USERNAME','root');
if(!defined('DB_PASSWORD')) define('DB_PASSWORD','');
if(!defined('DB_NAME')) define('DB_NAME','banking_db');

class DBConnection {
    private $host;
    private $username;
    private $password;
    private $database;
    public $conn;
    public $db_type;

    public function __construct(){
        // CRITICAL FIX: Check for the DATABASE_URL environment variable first,
        // as this is used by Render and other cloud providers.
        $db_url = getenv('DATABASE_URL');
        if (!empty($db_url)) {
            $url = parse_url($db_url);
            $this->host = $url["host"];
            $this->username = $url["user"];
            $this->password = isset($url["pass"]) ? $url["pass"] : null;
            $this->database = substr($url["path"], 1);
            $this->db_type = 'pgsql'; // Assuming PostgreSQL for Render
        } else {
            // Fallback for local development
            $this->host = DB_SERVER;
            $this->username = DB_USERNAME;
            $this->password = DB_PASSWORD;
            $this->database = DB_NAME;
            $this->db_type = 'mysqli'; // Default for local XAMPP/MySQL
        }

        if ($this->db_type === 'mysqli') {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            if ($this->conn->connect_error) {
                die("Connection failed: " . $this->conn->connect_error);
            }
        } elseif ($this->db_type === 'pgsql') {
            try {
                $dsn = "pgsql:host={$this->host};dbname={$this->database};user={$this->username};password={$this->password}";
                $this->conn = new PDO($dsn);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $e) {
                error_log("PostgreSQL Connection Failed: " . $e->getMessage());
                die("Database Connection Failed: " . $e->getMessage());
            }
        } else {
            die("Unsupported database type.");
        }
    }

    function __destruct(){
        if ($this->db_type === 'mysqli' && $this->conn) {
            $this->conn->close();
        }
        // PDO connections close automatically when the script ends.
    }
}
?>