<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE");
require 'Connection.php';

if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM services WHERE RideId = ?");
        $stmt->execute([$_GET['id']]);
        echo json_encode(["status" => 200, "message" => "Ride deleted successfully"]);
    } catch (PDOException $e) {
        echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => 400, "message" => "Ride ID is required"]);
}
