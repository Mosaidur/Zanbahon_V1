<?php
// Include the database connection file
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'Connection.php'; // Assuming 'Connection.php' is properly set up with PDO

// Include PHPMailer classes and use the correct namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer files
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

// Check if 'email' and 'otp' are provided
if (isset($_GET['email']) && isset($_GET['otp'])) {
    // Sanitize and retrieve the email and OTP
    $email = trim($_GET['email']);
    $otp = trim($_GET['otp']);

    // Get the current date and time in 'Y-m-d H:i:s' format
    $currentDateTime = date('Y-m-d H:i:s');

    // Prepare the SQL query to check OTP and expiry time
    // Assuming Expiry_Time is stored as a timestamp
    $sql = "SELECT * FROM otp_table WHERE Email = :email AND OTP_Code = :otp AND Expiry_Time > :currentDateTime";
    $stmt = $pdo->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':otp', $otp);
    $stmt->bindParam(':currentDateTime', $currentDateTime);

    // Execute the query
    $stmt->execute();

    // Check if any row is returned
    if ($stmt->rowCount() > 0) {
        // OTP is valid, now set 'verify' to TRUE
        $updateSql = "UPDATE otp_table SET verify = TRUE WHERE Email = :email AND OTP_Code = :otp";
        $updateStmt = $pdo->prepare($updateSql);
        
        // Bind parameters
        $updateStmt->bindParam(':email', $email);
        $updateStmt->bindParam(':otp', $otp);

        // Execute the update query
        $updateStmt->execute();

        // OTP verified and updated successfully
        echo json_encode([
            'success' => true,
            'message' => 'OTP verification successful.',
        ]);
    } else {
        // OTP verification failed
        echo json_encode([
            'success' => false,
            'message' => 'Invalid OTP or OTP has expired.',
        ]);
    }
} else {
    // Missing email or OTP
    echo json_encode([
        'success' => false,
        'message' => 'Email and OTP are required.',
    ]);
}
?>
