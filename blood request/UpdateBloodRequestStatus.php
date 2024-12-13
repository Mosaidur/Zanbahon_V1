<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require '../Connection.php';

try {
    // Get the JSON data from the request body
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if the necessary fields are provided
    if (isset($data['bloodRequestId'], $data['Status'])) {
        
        // Prepare the SQL query to update the status
        $stmt = $pdo->prepare("UPDATE bloodrequest SET Status = :status WHERE bloodRequestId = :bloodRequestId");

        // Bind the values
        $stmt->bindParam(':status', $data['Status']);
        $stmt->bindParam(':bloodRequestId', $data['bloodRequestId']);

        // Execute the query
        $stmt->execute();

        // Check if any rows were updated
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                "message" => "Blood request status updated successfully"
            ]);
        } else {
            echo json_encode([
                "message" => "No blood request found with the provided ID"
            ]);
        }
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

