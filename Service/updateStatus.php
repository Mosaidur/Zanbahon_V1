<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
require '../Connection.php';

// Parse input data
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['ride_id']) && isset($data['ride_status'])) {
    $ride_id = $data['ride_id'];
    $ride_status = $data['ride_status'];

    try {
        $stmt = $pdo->prepare("UPDATE services SET RideStatus = ? WHERE RideId = ?");
        $stmt->execute([$ride_status, $ride_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => 200, "message" => "Ride status updated successfully"]);
        } else {
            echo json_encode(["status" => 404, "message" => "Ride not found or status unchanged"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => 400, "message" => "Ride ID and status are required"]);
}

