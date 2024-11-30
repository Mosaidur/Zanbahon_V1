<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require 'Connection.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            createRide();
            break;
        case 'GET':
            if (isset($_GET['id'])) {
                getRideByRiderID($_GET['id']);
            } else {
                getRides();
            }
            break;
        case 'PUT':
            updateRide();
            break;
        case 'DELETE':
            if (isset($_GET['id'])) {
                deleteRide($_GET['id']);
            } else {
                echo json_encode(["message" => "Ride ID required"]);
            }
            break;
        default:
            echo json_encode(["message" => "Method not supported"]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(["message" => "An unexpected error occurred", "error" => $e->getMessage()]);
}

function createRide(): void
{
    global $pdo;
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['rider_id'], $data['pickup_location'], $data['drop_location'], $data['total_fare_amount'], $data['total_distance'], $data['approximate_time'])) {
        $riderId = $data['rider_id'];
        $driverId = $data['driver_id'] ?? null;
        $vehicleId = $data['vehicle_id'] ?? null;
        $pickupLocation = $data['pickup_location'];
        $dropLocation = $data['drop_location'];
        $pickupLatitude = $data['pickup_latitude'] ?? null;
        $pickupLongitude = $data['pickup_longitude'] ?? null;
        $dropLatitude = $data['drop_latitude'] ?? null;
        $dropLongitude = $data['drop_longitude'] ?? null;
        $rideStatus = $data['ride_status'] ?? 'Requested';
        $rideCancelledReason = $data['ride_cancelled_reason'] ?? null;
        $totalFareAmount = $data['total_fare_amount']?? null;
        $totalDistance = $data['total_distance'];
        $totalTime = $data['total_time']?? null;
        $approximateTime = $data['approximate_time']?? null;

        try {
            $stmt = $pdo->prepare("INSERT INTO Ride (RiderId, DriverId, VehicleId, PickupLocation, DropLocation, PickupLatitude, PickupLongitude, DropLatitude, DropLongitude, RideStatus, RideCancelledReason, TotalFareAmount, TotalDistance, TotalTime, ApproximateTime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$riderId, $driverId, $vehicleId, $pickupLocation, $dropLocation, $pickupLatitude, $pickupLongitude, $dropLatitude, $dropLongitude, $rideStatus, $rideCancelledReason, $totalFareAmount, $totalDistance, $totalTime, $approximateTime]);
            echo json_encode(["message" => "Ride created successfully", "RideId" => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

function getRideByRiderID($id)
{
    global $pdo;
    try {
        // Prepare the query to fetch all rides for the given RiderId or DriverId
        $stmt = $pdo->prepare("SELECT * FROM Ride WHERE RiderId = ? OR DriverId = ?");
        $stmt->execute([$id, $id]);
        
        // Fetch all matching rows
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rides) {
            echo json_encode(["status" => 200, "rides" => $rides]);
        } else {
            echo json_encode(["status" => 200, "message" => "No rides found for this Rider or Driver"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}


function getRides()
{
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM Ride");
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => 200, "rides" => $rides]);
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}

function updateRide()
{
    global $pdo;
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['ride_id'], $data['ride_status'], $data['ride_cancelled_reason'], $data['ride_accept_time'], $data['ride_start_time'], $data['ride_end_time'], $data['ride_rating'])) {
        $rideId = $data['ride_id'];
        $rideStatus = $data['ride_status'];
        $rideCancelledReason = $data['ride_cancelled_reason'] ?? null;
        $rideAcceptTime = $data['ride_accept_time'] ?? null;
        $rideStartTime = $data['ride_start_time'] ?? null;
        $rideEndTime = $data['ride_end_time'] ?? null;
        $rideRating = $data['ride_rating'] ?? null;

        try {
            $stmt = $pdo->prepare("UPDATE Ride SET RideStatus = ?, RideCancelledReason = ?, RideAcceptTime = ?, RideStartTime = ?, RideEndTime = ?, RideRating = ?, Last_Updated = NOW() WHERE RideId = ?");
            $stmt->execute([$rideStatus, $rideCancelledReason, $rideAcceptTime, $rideStartTime, $rideEndTime, $rideRating, $rideId]);

            echo json_encode(["status" => 200, "message" => "Ride updated successfully"]);
        } catch (PDOException $e) {
            echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

function deleteRide($id)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM Ride WHERE RideId = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount()) {
            echo json_encode(["status" => 200, "message" => "Ride deleted successfully"]);
        } else {
            echo json_encode(["status" => 200, "message" => "Ride not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}
?>
