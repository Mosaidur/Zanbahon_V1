<?php

// Set headers for JSON response and CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

// Read the input JSON
$input = json_decode(file_get_contents("php://input"), true);

// Check if required fields are provided
if (!isset($input['email']) || !isset($input['name']) || !isset($input['Body']) || !isset($input['Subject'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: email, name, Body, or Subject.'
    ]);
    exit;
}

$email = trim($input['email']);
$name = trim($input['name']);
$body = $input['Body'];
$subject = trim($input['Subject']);

// Validate the email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format.'
    ]);
    exit;
}

try {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    // SMTP configuration
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'mosaidurasif.office@gmail.com'; // Replace with your email
    $mail->Password = 'leyznhhaovbpnlye'; // Replace with your app-specific password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    // Email settings
    $mail->setFrom('mosaidurasif.office@gmail.com', $name); // Sender email and name
    $mail->addAddress($email); // Recipient email

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;

    // Send the email
    if ($mail->send()) {
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send email.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Mailer Error: ' . $mail->ErrorInfo
    ]);
}

?>
