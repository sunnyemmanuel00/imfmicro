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

    // The problematic __destruct function has been removed.

    // Function to handle the contact form submission
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

        // Check if a user is logged in
        $user_id = $this->settings->userdata('id') ?? null;

        // Prepare the SQL statement
        $stmt = $this->conn->prepare("INSERT INTO `inquiries` (name, email, phone, subject, message, type, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if($stmt === false) {
            $resp['msg'] = "Prepare failed: " . $this->conn->error;
            return json_encode($resp);
        }

        // Bind parameters
        $stmt->bind_param("ssssssi", $name, $email, $phone, $subject, $message, $type, $user_id);

        if($stmt->execute()){
            $resp['status'] = 'success';
            $resp['msg'] = "Your message has been sent successfully! We will get back to you as soon as possible.";
        } else {
            $resp['msg'] = "Failed to send message. Please try again later.";
        }

        $stmt->close();
        return json_encode($resp);
    }
}

// This part handles the AJAX request from the contact form
if(isset($_GET['f']) && !empty($_GET['f'])){
    $action = $_GET['f'];
    $app = new Application();
    switch($action){
        case 'submit_inquiry':
            echo $app->submit_inquiry();
            break;
        default:
            // No default action needed
            break;
    }
}