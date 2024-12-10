<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
require '../Connection.php';

if (isset($_GET['ride_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE RideId = ?");
        $stmt->execute([$_GET['ride_id']]);
        $ride = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($ride) {
            echo json_encode(["status" => 200, "ride" => $ride]);
        } else {
            echo json_encode(["status" => 404, "message" => "Ride not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => 400, "message" => "Ride ID is required"]);
}
