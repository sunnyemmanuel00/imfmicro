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

// Check if trying to access the admin area (e.g., anything under /admin/)
$isAdminArea = strpos($link, '/admin/') !== false;
// Check if trying to access the client area (e.g., anything under /client/ but not login)
$isClientArea = strpos($link, '/client/') !== false && !strpos($link, 'client/login.php');


// --- Admin Area Authentication ---
if ($isAdminArea) {
    // If not logged in as admin or not logged in at all, redirect to admin login
    if (!isset($_SESSION['userdata']) || (isset($_SESSION['userdata']['login_type']) && $_SESSION['userdata']['login_type'] != 1)) {
        if (!strpos($link, 'admin/login.php')) { // Avoid redirect loop on login page itself
            header('location: ' . base_url . 'admin/login.php');
            exit;
        }
    }
}
// --- Client Area Authentication (if you have specific client-only pages) ---
else if ($isClientArea) {
    // If not logged in as client or not logged in at all, redirect to client login
    if (!isset($_SESSION['userdata']) || (isset($_SESSION['userdata']['login_type']) && $_SESSION['userdata']['login_type'] != 2)) {
        // Assuming your client login is internet_banking.php or client/login.php (if it's a separate client login page)
        if (!strpos($link, 'internet_banking.php') && !strpos($link, 'client/login.php')) { // Avoid redirect loop
            header('location: ' . base_url . '?p=internet_banking'); // Or your actual client login page
            exit;
        }
    }
}

// If already logged in and trying to access a login page, redirect to respective dashboard
if(isset($_SESSION['userdata'])) {
    if (strpos($link, 'admin/login.php')) {
        if (isset($_SESSION['userdata']['login_type']) && $_SESSION['userdata']['login_type'] == 1) {
            header('location: ' . base_url . 'admin');
            exit;
        }
    } else if (strpos($link, '?p=internet_banking') || strpos($link, 'client/login.php')) { // Assuming this is the client login page
        if (isset($_SESSION['userdata']['login_type']) && $_SESSION['userdata']['login_type'] == 2) {
            header('location: ' . base_url . '?p=account_dashboard'); // Or your actual client dashboard page
            exit;
        }
    }
}
?>