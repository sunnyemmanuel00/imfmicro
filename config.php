<?php
ob_start();
ini_set('date.timezone','Asia/Manila');
date_default_timezone_set('Asia/Manila');
session_start();

// ====================================================================
// FIX: Hardcoded Base URL for specific environments.
// This new logic detects the environment and sets the correct base URL.
// The DB_TYPE constant has been removed as it is now handled directly by the DBConnection class.
// ====================================================================
if (!defined('base_url')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $local_path = '/banking/'; // This is the subdirectory for your local XAMPP setup

    if ($host === 'imfpayments.online' || $host === 'www.imfpayments.online') {
        // If the custom domain is detected, use it.
        define('base_url', 'https://imfpayments.online/');
    } elseif (strpos($host, 'imfmicros.onrender.com') !== false) {
        // If the Render domain is detected, use it.
        define('base_url', 'https://imfmicros.onrender.com/');
    } else {
        // Fallback for your local development environment (XAMPP).
        define('base_url', $protocol . $host . $local_path);
    }
}

// =============================================================
// FIX: Define base_app dynamically to fix local paths on different servers.
// =============================================================
if (!defined('base_app')) {
    define('base_app', __DIR__ . '/');
}

require_once(__DIR__ . '/initialize.php');
require_once(__DIR__ . '/classes/DBConnection.php');
require_once(__DIR__ . '/classes/SystemSettings.php');
$db = new DBConnection;
$conn = $db->conn;

function redirect($url=''){
    if(!empty($url))
    echo '<script>location.href="'.base_url .$url.'"</script>';
}
function validate_image($file){
    if(!empty($file)){
        if(is_file(base_app.$file)){
            return base_url.$file;
        }else{
            return base_url.'dist/img/no-image-available.png';
        }
    }else{
        return base_url.'dist/img/no-image-available.png';
    }
}
function isMobileDevice(){
    $aMobileUA = array(
        '/iphone/i' => 'iPhone', 
        '/ipod/i' => 'iPod', 
        '/ipad/i' => 'iPad', 
        '/android/i' => 'Android', 
        '/blackberry/i' => 'BlackBerry', 
        '/webos/i' => 'Mobile'
    );
    foreach($aMobileUA as $sMobileKey => $sMobileOS){
        if(preg_match($sMobileKey, $_SERVER['HTTP_USER_AGENT'])){
            return true;
        }
    }
    return false;
}
function randomPassword() {
    $chars =  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.
    '0123456789`-=~!@#$%^&*()_+,./<>?;:[]{}\|';
    $str = '';
    $max = strlen($chars) - 1;
    for ($i=0; $i < 8; $i++)
    $str .= $chars[mt_rand(0, $max)];
    return $str;
}
ob_end_flush();
?>
