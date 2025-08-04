<?php
// A safe, standalone file to check the PIN in the database.

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>PIN Check</title>";
echo "<style>body { font-family: sans-serif; padding: 20px; } pre { background-color: #f0f0f0; border: 1px solid #ccc; padding: 10px; display: inline-block; white-space: pre-wrap; word-wrap: break-word; } .error { color: red; font-weight: bold; }</style>";
echo "</head><body>";

echo "<h1>Database PIN Check</h1>";

// Include your database configuration just like your other files do.
require_once('config.php');

// !!! IMPORTANT: Set this to the ID of your account from the 'accounts' table !!!
// From your screenshots, the ID for 'Charles Anwurum' is 13.
// If you are testing another account, change the number here.
$your_account_id = 13; 

echo "<h3>Checking PIN for Account ID: {$your_account_id}</h3>";

if (isset($conn) && $conn) {
    $stmt = $conn->prepare("SELECT `transaction_pin` FROM `accounts` WHERE id = ?");
    
    if ($stmt) {
        $stmt->bind_param("i", $your_account_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stored_pin = $row['transaction_pin'];

            echo "<p>The Transaction PIN stored in your database is:</p>";
            echo "<pre>";
            var_dump($stored_pin);
            echo "</pre>";

            echo "<hr>";
            echo "<h4>Analysis:</h4>";
            echo "<ul>";
            echo "<li>The value is shown above inside the quotes.</li>";
            echo "<li><b>string(" . strlen($stored_pin) . ")</b> shows the exact length. If your PIN is 4 digits (e.g., '6727'), it should say `string(4)`. If it says a different number like `string(5)`, it means there is a hidden space.</li>";
            echo "</ul>";

        } else {
            echo "<p class='error'>Error: Could not find an account with ID: {$your_account_id}</p>";
        }
        $stmt->close();
    } else {
        echo "<p class='error'>Error preparing database statement: " . $conn->error . "</p>";
    }
    $conn->close();
} else {
    echo "<p class='error'>Error: Database connection failed. Check your config.php file.</p>";
}

echo "</body></html>";
?>