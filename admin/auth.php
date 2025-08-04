<?php
/**
 * Admin Authentication and Authorization Gatekeeper
 * This file protects the admin area.
 */

// Check if a user is logged in and if their login_type is NOT 1 (which is the type for Admin).
if(isset($_SESSION['userdata']['login_type']) && $_SESSION['userdata']['login_type'] != 1){
    // If the logged-in user is a client (or any other type),
    // destroy their session to be safe and redirect them to the main homepage.
    session_destroy();
    header('Location: '.base_url);
    exit;
}

// If no one is logged in at all, redirect them to the admin login page.
if(!isset($_SESSION['userdata']['id'])){
    header('Location: '.base_url.'admin/login.php');
    exit;
}
?>