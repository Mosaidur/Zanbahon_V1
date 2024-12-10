<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require '../Connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['ride_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE services SET 
                               RideStatus = ?, RideCancelledReason = ?, RideAcceptTime = ?, 
                               RideStartTime = ?, RideEndTime = ?, RideRating = ?, Last_Updated = NOW() 
                               WHERE RideId = ?");
        $stmt->execute([
            $data['ride_status'] ?? null, 
            $data['ride_cancelled_reason'] ?? null, 
            $data['ride_accept_time'] ?? null, 
            $data['ride_start_time'] ?? null, 
            $data['ride_end_time'] ?? null, 
            $data['ride_rating'] ?? null, 
            $data['ride_id']
        ]);
        echo json_encode(["status" => 200, "message" => "Ride updated successfully"]);
    } catch (PDOException $e) {
        echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => 400, "message" => "Ride ID is required"]);
}
