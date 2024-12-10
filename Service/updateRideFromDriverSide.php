<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require '../Connection.php';

$data = json_decode(file_get_contents("php://input"), true);

$rideId = $data['rideId'] ?? null;
$driverId = $data['driverId'] ?? null;
$vehicleId = $data['vehicleId'] ?? null;
$rideStatus = $data['rideStatus'] ?? null;
$rideAcceptTime = $data['rideAcceptTime'] ?? null;

global $pdo;
try {
    if (empty($rideId) || empty($driverId) || empty($vehicleId) || empty($rideStatus) || empty($rideAcceptTime)) {
        echo json_encode(["status" => 400, "message" => "All fields are required"]);
        return;
    }

    $rideAcceptTimeObj = new DateTime($rideAcceptTime);

    $stmt = $pdo->prepare("SELECT * FROM services WHERE RideId = ?");
    $stmt->execute([$rideId]);
    $ride = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ride) {
        echo json_encode(["status" => 400, "message" => "Ride not found"]);
        return;
    }

    $stmt = $pdo->prepare("UPDATE services SET DriverId = ?, VehicleId = ?, RideStatus = ?, RideAcceptTime = ? WHERE RideId = ?");
    $stmt->execute([$driverId, $vehicleId, $rideStatus, $rideAcceptTime, $rideId]);

    echo json_encode(["status" => 200, "message" => $stmt->rowCount() > 0 ? "Ride updated successfully" : "Failed to update ride"]);
} catch (PDOException $e) {
    echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
}
