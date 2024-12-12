<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require '../Connection.php';


//Here you create ride and

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['rider_id'], $data['pickup_location'], $data['drop_location'], $data['total_fare_amount'], $data['total_distance'], $data['approximate_time'])) {
        $stmt = $pdo->prepare("INSERT INTO services (RiderId, DriverId, VehicleId, PickupLocation, DropLocation, PickupLatitude, PickupLongitude, DropLatitude, DropLongitude, RideStatus, RideCancelledReason, TotalFareAmount, TotalDistance, TotalTime, ApproximateTime, ServiceName) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['rider_id'], 
            $data['driver_id'] ?? null, 
            $data['vehicle_id'] ?? null, 
            $data['pickup_location'], 
            $data['drop_location'], 
            $data['pickup_latitude'] ?? null, 
            $data['pickup_longitude'] ?? null, 
            $data['drop_latitude'] ?? null, 
            $data['drop_longitude'] ?? null, 
            $data['ride_status'] ?? 'Requested', 
            $data['ride_cancelled_reason'] ?? null, 
            $data['total_fare_amount'], 
            $data['total_distance'], 
            $data['total_time'] ?? null, 
            $data['approximate_time'], 
            $data['service_name'] ?? 'Ride Share'
        ]);
        echo json_encode(["message" => "Service created successfully", "ServiceId" => $pdo->lastInsertId()]);
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
} catch (PDOException $e) {
    echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
}
