<?php

if(!defined('base_url')) define('base_url','http://localhost/banking/');
// We will update these constants to reflect PostgreSQL for local development
// but your code's logic handles it correctly.
if(!defined('DB_SERVER')) define('DB_SERVER','localhost');
if(!defined('DB_USERNAME')) define('DB_USERNAME','root');
if(!defined('DB_PASSWORD')) define('DB_PASSWORD','');
if(!defined('DB_NAME')) define('DB_NAME','banking_db');

class DBConnection {
    private $host;
    private $port;
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
            // This is the code for your live host on Render
            $url = parse_url($db_url);
            $this->host = $url["host"];
            $this->username = $url["user"];
            $this->password = isset($url["pass"]) ? $url["pass"] : null;
            $this->database = substr($url["path"], 1);
            $this->db_type = 'pgsql'; // Assuming PostgreSQL for Render
        } else {
            // Fallback for local development
            // We are now using PostgreSQL for local, so we'll update these settings
            $this->host = 'localhost';
            $this->port = '5432';
            $this->username = 'postgres'; // Your PostgreSQL user
            $this->password = 'Domnic418'; // Your PostgreSQL password
            $this->database = 'banking_db';
            $this->db_type = 'pgsql'; // Now using pgsql locally
        }

        if ($this->db_type === 'mysqli') {
            // This block is no longer needed but we will keep it for now
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            if ($this->conn->connect_error) {
                die("Connection failed: " . $this->conn->connect_error);
            }
        } elseif ($this->db_type === 'pgsql') {
            try {
                // We use PDO for PostgreSQL connections
                $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->database};user={$this->username};password={$this->password}";
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
