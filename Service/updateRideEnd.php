<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require '../Connection.php';

$data = json_decode(file_get_contents("php://input"), true);

$rideId = $data['rideId'] ?? null;
$endTime = $data['endTime'] ?? null;

global $pdo;
try {
    $stmt = $pdo->prepare("SELECT RideStartTime FROM services WHERE RideId = ?");
    $stmt->execute([$rideId]);
    $ride = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ride) {
        echo json_encode(["status" => 400, "message" => "Ride not found"]);
        return;
    }

    $rideStartTime = $ride['RideStartTime'];
    $startTime = new DateTime($rideStartTime);
    $endTimeObj = new DateTime($endTime);
    $interval = $startTime->diff($endTimeObj);
    $totalTime = $interval->format('%h:%i:%s');

    $stmt = $pdo->prepare("UPDATE services SET RideEndTime = ?, RideStatus = 'Complete', TotalTime = ? WHERE RideId = ?");
    $stmt->execute([$endTime, $totalTime, $rideId]);

    echo json_encode([
        "status" => 200,
        "message" => $stmt->rowCount() > 0 ? "Ride completed successfully" : "Failed to update ride",
        "totalTime" => $totalTime
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
}
