<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require '../Connection.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate if the required data is present
    if (isset($data['blood_type'], $data['blood_seeker_name'], $data['blood_seeker_address'], $data['phone'], $data['blood_bank_id'])) {

        // Prepare the SQL query to insert the blood request into the database
        $stmt = $pdo->prepare(
            "INSERT INTO bloodrequest (blood_type, blood_seeker_name, blood_seeker_address, phone, blood_bank_id, request_date, Status) 
             VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?)"
        );

        // Execute the statement with the provided data
        $stmt->execute([
            $data['blood_type'],
            $data['blood_seeker_name'],
            $data['blood_seeker_address'],
            $data['phone'],
            $data['blood_bank_id'],
            $data['status'] ?? 'Waiting' // Default status to 'Waiting'
        ]);

        // Return a success message with the last inserted blood request ID
        echo json_encode([
            "message" => "Blood request created successfully",
            "bloodRequestId" => $pdo->lastInsertId()
        ]);
    } else {
        echo json_encode(["message" => "Invalid input, required fields are missing"]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "message" => "An error occurred",
        "error" => $e->getMessage()
    ]);
}
?>
