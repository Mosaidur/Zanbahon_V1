<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
require '../Connection.php';

if (isset($_GET['riderid'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE RiderId = ? OR DriverId = ?");
        $stmt->execute([$_GET['riderid'], $_GET['riderid']]);
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rides ? ["status" => 200, "rides" => $rides] : ["status" => 200, "message" => "No rides found"]);
    } catch (PDOException $e) {
        echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => 400, "message" => "Rider ID is required"]);
}
