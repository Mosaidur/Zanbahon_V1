<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
require '../Connection.php';

try {
    $stmt = $pdo->query("SELECT * FROM services");
    $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rides ? ["status" => 200, "rides" => $rides] : ["status" => 200, "message" => "No rides found"]);
} catch (PDOException $e) {
    echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
}
