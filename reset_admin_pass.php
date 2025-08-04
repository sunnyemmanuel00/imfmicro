<?php
// This is a temporary tool to reset the admin password.
// MODIFIED: Added a try-catch block to display database connection errors on the page.

// === HTML Header for consistent output ===
echo "<!DOCTYPE html><html><head><title>Admin Password Reset</title>";
echo "<style>body{font-family: sans-serif; padding: 2em; line-height: 1.6;} code{background: #eee; padding: 3px 5px; border-radius: 4px;}</style>";
echo "</head><body>";
echo "<h1>Admin Password Reset Tool</h1>";

try {
    // This is where the database connection is established via config.php.
    // An exception will be thrown here if the connection fails.
    require_once('config.php');

    // This code block will only execute if the database connection is successful.
    $new_password = 'Domnic418'; // <--- CHANGE THIS IF YOU WANT A DIFFERENT PASSWORD
    $admin_username = 'admin';

    echo "<p>Attempting to reset password for user: '<strong>" . htmlspecialchars($admin_username) . "</strong>'...</p>";

    if (!isset($conn) || !$conn) {
        // This condition should not be reached if config.php throws an exception on failure,
        // but it's a good safety check.
        throw new Exception("Database connection object (\$conn) is not available after including config.php.");
    }

    // Hash the new password securely using a modern algorithm.
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Prepare the UPDATE statement using a prepared statement for safety.
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $hashed_password, $admin_username);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "<h3 style='color:green;'>SUCCESS!</h3>";
                echo "<p>The password for '<strong>" . htmlspecialchars($admin_username) . "</strong>' has been reset.</p>";
                echo "<p>Your new temporary password is: <strong><code>" . $new_password . "</code></strong></p>";
                echo "<p>You can now go to the admin login page and use these credentials.</p>";
                echo "<p style='color:red; font-weight:bold;'>IMPORTANT: For security, please delete this file (reset_admin_pass.php) from your project folder immediately and deploy again.</p>";
            } else {
                echo "<h3 style='color:orange;'>NOTICE:</h3>";
                echo "<p>The query ran, but no user was found with the username '<strong>" . htmlspecialchars($admin_username) . "</strong>'. Please check your `users` table on your database to ensure the admin user exists.</p>";
            }
        } else {
            echo "<h3 style='color:red;'>ERROR:</h3>";
            echo "<p>Could not execute the database update. Error: " . htmlspecialchars($stmt->error) . "</p>";
        }
        $stmt->close();
    } else {
        echo "<h3 style='color:red;'>ERROR:</h3>";
        echo "<p>Could not prepare the database statement. Error: " . htmlspecialchars($conn->error) . "</p>";
    }
    // MODIFIED: Removed the manual $conn->close(); call here to prevent the "object already closed" error.
    // The connection will now be automatically closed by the DBConnection destructor.

} catch (Throwable $e) {
    // This block catches any fatal errors or exceptions (like a failed database connection)
    echo "<h3 style='color:red;'>CRITICAL CONNECTION ERROR:</h3>";
    echo "<p>The script could not establish a database connection.</p>";
    echo "<p>Please verify your database credentials and socket path in your `DBConnection.php` file for the live server environment.</p>";
    echo "<p>Detailed Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>