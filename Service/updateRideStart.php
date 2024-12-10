<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require '../Connection.php';

$data = json_decode(file_get_contents("php://input"), true);

$rideId = $data['rideId'] ?? null;
$startTime = $data['startTime'] ?? null;

global $pdo;
try {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE RideId = ?");
    $stmt->execute([$rideId]);
    $ride = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ride) {
        echo json_encode(["status" => 400, "message" => "Ride not found"]);
        return;
    }

    $startTimeObj = new DateTime($startTime);

    $stmt = $pdo->prepare("UPDATE services SET RideStatus = 'Started', RideStartTime = ? WHERE RideId = ?");
    $stmt->execute([$startTime, $rideId]);

    echo json_encode(["status" => 200, "message" => $stmt->rowCount() > 0 ? "Ride started successfully" : "Failed to update ride"]);
} catch (PDOException $e) {
    echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
}
