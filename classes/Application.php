<?php
require_once(__DIR__ . '/../config.php');

if(!class_exists('Master')){
    require_once(__DIR__ . '/Master.php');
}

class Application extends Master {
    public function __construct(){
        parent::__construct();
    }

    public function submit_inquiry(){
        $resp = ['status' => 'failed', 'msg' => 'An unknown error occurred.'];

        $name = strip_tags($_POST['name']);
        $email = strip_tags($_POST['email']);
        $phone = strip_tags($_POST['phone']);
        $subject = strip_tags($_POST['subject']);
        $message = strip_tags($_POST['message']);
        $type = strip_tags($_POST['type']);

        if(empty($name) || empty($email) || empty($subject) || empty($message) || empty($type)){
            $resp['msg'] = "Please fill in all required fields.";
            return json_encode($resp);
        }

        $user_id = $this->settings->userdata('id') ?? null;
        
        try {
            $stmt = $this->conn->prepare("INSERT INTO `inquiries` (name, email, phone, subject, message, type, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) { throw new Exception("Failed to prepare statement."); }
            $stmt->bind_param("ssssssi", $name, $email, $phone, $subject, $message, $type, $user_id);
            
            if($stmt->execute()){
                $resp['status'] = 'success';
                $resp['msg'] = "Your message has been sent successfully! We will get back to you as soon as possible.";
            } else {
                $resp['msg'] = "Failed to send message. Please try again later.";
            }
        } catch (Exception $e) {
            error_log("Inquiry Submission MySQLi Error: " . $e->getMessage());
            $resp['msg'] = "A database error occurred.";
        }
        
        return json_encode($resp);
    }
}

if(isset($_GET['f']) && !empty($_GET['f'])){
    $action = $_GET['f'];
    $app = new Application();
    switch($action){
        case 'submit_inquiry':
            echo $app->submit_inquiry();
            break;
        default:
            break;
    }
}