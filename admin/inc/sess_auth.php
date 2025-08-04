<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
    $link = "https"; 
else
    $link = "http"; 
$link .= "://"; 
$link .= $_SERVER['HTTP_HOST']; 
$link .= $_SERVER['REQUEST_URI'];

// Check if 'userdata' is not set in the session AND the current page is NOT 'login.php'
if(!isset($_SESSION['userdata']) && !strpos($link, 'login.php')){
    // Redirect to login.php
    header('location: login.php');
    exit; // Always exit after a header redirect
}

// Check if 'userdata' is set in the session AND the current page IS 'login.php'
if(isset($_SESSION['userdata']) && strpos($link, 'login.php')){
    // Redirect to index.php
    header('location: index.php');
    exit; // Always exit after a header redirect
}
?>