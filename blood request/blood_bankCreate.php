<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require '../Connection.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate if required data is provided
    if (isset($data['CenterName'], $data['Location'], $data['ContactNumbers'], $data['Availability'])) {

        // Prepare the SQL query to insert a new blood donation center
        $stmt = $pdo->prepare(
            "INSERT INTO BloodDonationCenters (CenterName, Location, ContactNumbers, Availability) 
             VALUES (?, ?, ?, ?)"
        );

        // Execute the query with the provided data
        $stmt->execute([
            $data['CenterName'],
            $data['Location'],
            $data['ContactNumbers'],
            $data['Availability']
        ]);

        // Return a success message with the last inserted blood bank ID
        echo json_encode([
            "message" => "Blood Donation Center created successfully",
            "blood_bank_id" => $pdo->lastInsertId()
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
