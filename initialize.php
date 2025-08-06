<?php
$dev_data = array('id'=>'-1','firstname'=>'Developer','lastname'=>'','username'=>'dev_oretnom','password'=>'5da283a2d990e8d8512cf967df5bc0d0','last_login'=>'','date_updated'=>'','date_added'=>'');

// =================== DUAL-ENVIRONMENT BASE URL (LOCAL & LIVE) ===================
// This is the final, correct version that works everywhere.

if(!defined('base_url')){
    // Check if the code is running on the live Render server
    if (getenv('RENDER_EXTERNAL_URL')) {
        // Define the base_url for the LIVE server
        define('base_url', getenv('RENDER_EXTERNAL_URL') . '/');
    } else {
        // Define the base_url for your LOCAL XAMPP server
        define('base_url', 'http://localhost/banking/');
    }
}
// =================================================================================

if(!defined('base_app')) define('base_app', str_replace('\\','/',__DIR__).'/' );

if(!defined('dev_data')) define('dev_data',$dev_data);

// These local DB constants are correct for a default XAMPP setup.
// They will be ignored on the live server because of the logic in DBConnection.php
if(!defined('DB_SERVER')) define('DB_SERVER',"localhost");
if(!defined('DB_USERNAME')) define('DB_USERNAME',"root");
if(!defined('DB_PASSWORD')) define('DB_PASSWORD',""); 
if(!defined('DB_NAME')) define('DB_NAME',"banking_db");

?>