<?php
require_once(__DIR__ . '/../config.php');

// Check if Master class is already loaded
if(!class_exists('Master')){
    require_once(__DIR__ . '/Master.php');
}

class Application extends Master {
    public function __construct(){
        parent::__construct();
    }

    public function submit_inquiry(){
        $resp = ['status' => 'failed', 'msg' => 'An unknown error occurred.'];

        // Extract and sanitize POST data
        $name = strip_tags($_POST['name']);
        $email = strip_tags($_POST['email']);
        $phone = strip_tags($_POST['phone']);
        $subject = strip_tags($_POST['subject']);
        $message = strip_tags($_POST['message']);
        $type = strip_tags($_POST['type']);

        // Basic server-side validation
        if(empty($name) || empty($email) || empty($subject) || empty($message) || empty($type)){
            $resp['msg'] = "Please fill in all required fields.";
            return json_encode($resp);
        }

        $user_id = $this->settings->userdata('id') ?? null;
        
        $sql = DB_TYPE === 'mysql' ? 
            "INSERT INTO `inquiries` (`name`, `email`, `phone`, `subject`, `message`, `type`, `user_id`) VALUES (?, ?, ?, ?, ?, ?, ?)" :
            'INSERT INTO "inquiries" ("name", "email", "phone", "subject", "message", "type", "user_id") VALUES ($1, $2, $3, $4, $5, $6, $7)';

        $save = false;
        if (DB_TYPE === 'mysql') {
            $stmt = $this->conn->prepare($sql);
            if($stmt === false) {
                $resp['msg'] = "Prepare failed: " . $this->conn->error;
                return json_encode($resp);
            }
            $stmt->bind_param("ssssssi", $name, $email, $phone, $subject, $message, $type, $user_id);
            $save = $stmt->execute();
            $stmt->close();
        } else { // pgsql
            $params = array($name, $email, $phone, $subject, $message, $type, $user_id);
            $save = pg_query_params($this->conn, $sql, $params);
        }

        if($save){
            $resp['status'] = 'success';
            $resp['msg'] = "Your message has been sent successfully! We will get back to you as soon as possible.";
        } else {
            $resp['msg'] = "Failed to send message. Please try again later.";
        }

        return json_encode($resp);
    }
}

if(isset($_GET['f']) && !empty($_GET['f'])){
    $action = $_GET['f'];
    $app = null;
    try {
        $app = new Application();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'failed',
            'msg' => 'Server Error on init: ' . $e->getMessage()
        ]);
        exit;
    }
    switch($action){
        case 'submit_inquiry':
            echo $app->submit_inquiry();
            break;
        default:
            // No default action needed
            break;
    }
}
