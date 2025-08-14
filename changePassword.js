// changePassword.js
const admin = require("firebase-admin");
const readline = require("readline");
const path = require("path");

// Path to your service account JSON file
const serviceAccount = require("C:/xampp/htdocs/banking/classes/firebase-service-account.json");
// Initialize Firebase Admin
admin.initializeApp({
  credential: admin.credential.cert(serviceAccount),
});

// Create readline interface for interactive input
const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout,
});

// Ask for email
rl.question("Enter user's email: ", (email) => {
  // Ask for new password
  rl.question("Enter new password: ", (newPassword) => {
    // Find the user by email
    admin
      .auth()
      .getUserByEmail(email)
      .then((userRecord) => {
        // Update the user's password
        return admin.auth().updateUser(userRecord.uid, {
          password: newPassword,
        });
      })
      .then(() => {
        console.log(`✅ Password updated successfully for ${email}`);
        rl.close();
      })
      .catch((error) => {
        console.error("❌ Error updating password:", error);
        rl.close();
      });
  });
});