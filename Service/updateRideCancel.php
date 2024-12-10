<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require '../Connection.php';

$data = json_decode(file_get_contents("php://input"), true);

$rideId = $data['rideId'] ?? null;
$cancelReason = $data['cancelReason'] ?? null;

global $pdo;
try {
    if (empty($rideId) || empty($cancelReason)) {
        echo json_encode(["status" => 400, "message" => "Missing required parameters: rideId or cancelReason"]);
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM services WHERE RideId = ?");
    $stmt->execute([$rideId]);
    $ride = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ride) {
        echo json_encode(["status" => 400, "message" => "Ride not found"]);
        return;
    }

    $stmt = $pdo->prepare("UPDATE services SET RideStatus = 'Cancelled', RideCancelledReason = ? WHERE RideId = ?");
    $stmt->execute([$cancelReason, $rideId]);

    echo json_encode(["status" => 200, "message" => $stmt->rowCount() > 0 ? "Ride cancelled successfully" : "Failed to cancel the ride"]);
} catch (PDOException $e) {
    echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
}
