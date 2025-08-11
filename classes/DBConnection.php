<?php

if(!defined('base_url')) define('base_url','http://localhost/banking/');

class DBConnection {
  private $host;
  private $port;
  private $username;
  private $password;
  private $database;
  public $conn;
  public $db_type;

  public function __construct(){
    // Check for the DATABASE_URL environment variable used by Render
    $db_url = getenv('DATABASE_URL');
    if (!empty($db_url)) {
      // This is the code for your live host on Render
      $url = parse_url($db_url);
      $this->host = $url["host"];
      $this->username = $url["user"];
      $this->password = isset($url["pass"]) ? $url["pass"] : null;
      $this->database = substr($url["path"], 1);
      $this->port = isset($url["port"]) ? $url["port"] : 5432;
      $this->db_type = 'pgsql';
    } else {
      // Fallback for local development using PostgreSQL
      $this->host = 'localhost';
      $this->port = '5432';
      $this->username = 'postgres';
      $this->password = 'Domnic418';
      $this->database = 'banking_db';
      $this->db_type = 'pgsql';
    }

    if ($this->db_type === 'pgsql') {
      try {
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
    // PDO connections close automatically when the script ends.
  }
}
?>