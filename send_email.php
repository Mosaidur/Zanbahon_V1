<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Include the database connection file
require 'Connection.php'; // Ensure 'Connection.php' uses PDO

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

if (isset($_GET['email'])) {
    // Sanitize and retrieve the email address
    $email = trim($_GET['email']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email format.',
        ]);
        exit;
    }

    // Generate a unique OTP
    $verificationCode = rand(10000, 99999); // Random 5-digit code
    
    // Get current time for Created_At
    $createdAt = date("Y-m-d H:i:s");

    // Calculate expiry time (10 minutes after the created time)
    $expiryTime = date("Y-m-d H:i:s", strtotime($createdAt . " +10 minutes"));

    try {
        // Prepare and execute SQL query to insert OTP data
        $stmt = $pdo->prepare("INSERT INTO otp_table (OTP_Code, Email, Expiry_Time, Created_At) 
                               VALUES (:otp, :email, :expiry, :created_at)");
        $stmt->execute([
            ':otp' => $verificationCode,
            ':email' => $email,
            ':expiry' => $expiryTime,
            ':created_at' => $createdAt, // Ensure Created_At is inserted
        ]);

        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'mosaidurasif.office@gmail.com';
            $mail->Password = 'leyznhhaovbpnlye'; // App password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('mosaidurasif.office@gmail.com', 'Zanbahon V1');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Email Verification';
            $mail->Body = "
                <h3>Email Verification for Zanbahon</h3>
                <p>Your OTP is:</p>
                <h1>$verificationCode</h1>
                <p>This OTP is valid for 10 minutes.</p>
            ";

            $mail->send();

            echo json_encode([
                'success' => true,
                'message' => 'Verification email sent successfully. Please check your inbox!',
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error sending email: ' . $mail->ErrorInfo,
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save OTP in the database: ' . $e->getMessage(),
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Email address is required.',
    ]);
}

?>
