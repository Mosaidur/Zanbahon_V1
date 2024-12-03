<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require 'Connection.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($requestMethod) {
        case 'POST':
            createService();
            break;
        case 'GET':
            if (isset($_GET['id'])) {
                getRidesByRiderId($_GET['id']);
            } elseif (isset($_GET['status'])) {
                getRidesByStatus($_GET['status']);
            } else {
                getAllRides();
            }
            break;
        case 'PUT':
            // Update ride details
            if (isset($_PUT['rideId'], $_PUT['rideStatus'], $_PUT['cancelReason'], $_PUT['acceptTime'], $_PUT['startTime'], $_PUT['endTime'], $_PUT['rating'])) {
                updateRideDetails();
            }
            // Update ride from driver's side (includes driverId, vehicleId, and ride accept time)
            elseif (isset($_PUT['rideId'], $_PUT['driverId'], $_PUT['vehicleId'], $_PUT['rideStatus'], $_PUT['acceptTime'])) {
                updateRideFromDriver($rideId, $driverId, $vehicleId, $rideStatus, $acceptTime);
            }
            // Start the ride
            elseif (isset($_PUT['rideId'], $_PUT['startTime'])) {
                startRide($rideId, $startTime);
            }
            // End the ride
            elseif (isset($_PUT['rideId'], $_PUT['endTime'])) {
                completeRide($rideId, $endTime);
            }
            // Cancel the ride
            elseif (isset($_PUT['rideId'], $_PUT['cancelReason'])) {
                cancelRide($rideId, $cancelReason);
            } 
            else {
                echo json_encode(["status" => 400, "message" => "Invalid input for PUT request"]);
            }
            break;
        case 'DELETE':
            if (isset($_GET['id'])) {
                deleteRide($rideId);
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

function createService(): void
{
    global $pdo;
    $inputData = json_decode(file_get_contents("php://input"), true);

    if (isset($inputData['riderId'], $inputData['pickupLocation'], $inputData['dropLocation'], $inputData['fareAmount'], $inputData['distance'], $inputData['approximateTime'])) {
        $riderId = $inputData['riderId'];
        $driverId = $inputData['driverId'] ?? null;
        $vehicleId = $inputData['vehicleId'] ?? null;
        $pickupLocation = $inputData['pickupLocation'];
        $dropLocation = $inputData['dropLocation'];
        $pickupLatitude = $inputData['pickupLatitude'] ?? null;
        $pickupLongitude = $inputData['pickupLongitude'] ?? null;
        $dropLatitude = $inputData['dropLatitude'] ?? null;
        $dropLongitude = $inputData['dropLongitude'] ?? null;
        $rideStatus = $inputData['rideStatus'] ?? 'Requested';
        $cancelReason = $inputData['cancelReason'] ?? null;
        $totalFare = $inputData['fareAmount'] ?? null;
        $totalDistance = $inputData['distance'];
        $totalTime = $inputData['totalTime'] ?? null;
        $approximateTime = $inputData['approximateTime'] ?? null;
        $serviceName = $inputData['serviceName'] ?? 'Ride Share';

        try {
            $stmt = $pdo->prepare("INSERT INTO Services (RiderId, DriverId, VehicleId, PickupLocation, DropLocation, PickupLatitude, PickupLongitude, DropLatitude, DropLongitude, RideStatus, CancelReason, FareAmount, TotalDistance, TotalTime, ApproximateTime, ServiceName) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$riderId, $driverId, $vehicleId, $pickupLocation, $dropLocation, $pickupLatitude, $pickupLongitude, $dropLatitude, $dropLongitude, $rideStatus, $cancelReason, $totalFare, $totalDistance, $totalTime, $approximateTime, $serviceName]);
            echo json_encode(["message" => "Service created successfully", "serviceId" => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

function getRidesByRiderId($id)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM Services WHERE RiderId = ? OR DriverId = ?");
        $stmt->execute([$id, $id]);
        
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

function getAllRides()
{
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM Services");
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => 200, "rides" => $rides]);
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}

function getRidesByStatus($status)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM Services WHERE RideStatus = ?");
        $stmt->execute([$status]);
        
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rides) {
            echo json_encode(["status" => 200, "rides" => $rides]);
        } else {
            echo json_encode(["status" => 200, "message" => "No rides found with the specified status"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}

function updateRideDetails()
{
    global $pdo;
    $inputData = json_decode(file_get_contents("php://input"), true);

    if (isset($inputData['rideId'], $inputData['rideStatus'], $inputData['cancelReason'], $inputData['acceptTime'], $inputData['startTime'], $inputData['endTime'], $inputData['rating'])) {
        $rideId = $inputData['rideId'];
        $rideStatus = $inputData['rideStatus'];
        $cancelReason = $inputData['cancelReason'] ?? null;
        $acceptTime = $inputData['acceptTime'] ?? null;
        $startTime = $inputData['startTime'] ?? null;
        $endTime = $inputData['endTime'] ?? null;
        $rating = $inputData['rating'] ?? null;

        try {
            $stmt = $pdo->prepare("UPDATE Services SET RideStatus = ?, CancelReason = ?, RideAcceptTime = ?, RideStartTime = ?, RideEndTime = ?, RideRating = ?, LastUpdated = NOW() WHERE RideId = ?");
            $stmt->execute([$rideStatus, $cancelReason, $acceptTime, $startTime, $endTime, $rating, $rideId]);

            echo json_encode(["status" => 200, "message" => "Ride updated successfully"]);
        } catch (PDOException $e) {
            echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

function updateRideFromDriver($rideId, $driverId, $vehicleId, $rideStatus, $acceptTime)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE Ride SET DriverId = ?, VehicleId = ?, RideStatus = ?, RideAcceptTime = ? WHERE RideId = ?");
        $stmt->execute([$driverId, $vehicleId, $rideStatus, $acceptTime, $rideId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => 200, "message" => "Ride updated successfully"]);
        } else {
            echo json_encode(["status" => 400, "message" => "Failed to update ride or ride not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}
function startRide($rideId, $startTime)
{
    global $pdo;
    try {
        // Update the ride status to 'In Progress' and set the start time
        $query = $pdo->prepare("UPDATE Services 
                                SET RideStatus = 'In Progress', RideStartTime = ? 
                                WHERE RideId = ?");
        $query->execute([$startTime, $rideId]);

        if ($query->rowCount() > 0) {
            echo json_encode(["status" => 200, "message" => "Ride started successfully"]);
        } else {
            echo json_encode(["status" => 400, "message" => "Failed to start ride or ride not found"]);
        }
    } catch (PDOException $exception) {
        echo json_encode(["message" => "An error occurred", "error" => $exception->getMessage()]);
    }
}

function completeRide($rideId, $endTime)
{
    global $pdo;
    try {
        // Fetch the start time of the ride
        $query = $pdo->prepare("SELECT RideStartTime FROM Services WHERE RideId = ?");
        $query->execute([$rideId]);
        $ride = $query->fetch(PDO::FETCH_ASSOC);

        if (!$ride) {
            echo json_encode(["status" => 400, "message" => "Ride not found"]);
            return;
        }

        $rideStartTime = $ride['RideStartTime'];

        // Calculate total time
        $startTime = new DateTime($rideStartTime);
        $endTimeObj = new DateTime($endTime);
        $interval = $startTime->diff($endTimeObj);
        $totalTime = $interval->format('%h:%i:%s'); // Format time as hours:minutes:seconds

        // Update the ride details
        $query = $pdo->prepare("UPDATE Services
                                SET RideEndTime = ?, RideStatus = 'Completed', TotalTime = ?
                                WHERE RideId = ?");
        $query->execute([$endTime, $totalTime, $rideId]);

        if ($query->rowCount() > 0) {
            echo json_encode(["status" => 200, "message" => "Ride completed successfully", "totalTime" => $totalTime]);
        } else {
            echo json_encode(["status" => 400, "message" => "Failed to complete ride or ride not found"]);
        }
    } catch (PDOException $exception) {
        echo json_encode(["message" => "An error occurred", "error" => $exception->getMessage()]);
    }
}

function cancelRide($rideId, $cancelReason)
{
    global $pdo;
    try {
        // Check if the ride exists
        $query = $pdo->prepare("SELECT * FROM Services WHERE RideId = ?");
        $query->execute([$rideId]);
        $ride = $query->fetch(PDO::FETCH_ASSOC);

        if (!$ride) {
            echo json_encode(["status" => 400, "message" => "Ride not found"]);
            return;
        }

        // Update the ride status to 'Cancelled' and set the cancellation reason
        $query = $pdo->prepare("UPDATE Services 
                                SET RideStatus = 'Cancelled', CancellationReason = ?
                                WHERE RideId = ?");
        $query->execute([$cancelReason, $rideId]);

        if ($query->rowCount() > 0) {
            echo json_encode(["status" => 200, "message" => "Ride cancelled successfully"]);
        } else {
            echo json_encode(["status" => 400, "message" => "Failed to cancel ride"]);
        }
    } catch (PDOException $exception) {
        echo json_encode(["message" => "An error occurred", "error" => $exception->getMessage()]);
    }
}

function deleteRide($rideId)
{
    global $pdo;
    try {
        $query = $pdo->prepare("DELETE FROM Services WHERE RideId = ?");
        $query->execute([$rideId]);

        if ($query->rowCount() > 0) {
            echo json_encode(["status" => 200, "message" => "Ride deleted successfully"]);
        } else {
            echo json_encode(["status" => 400, "message" => "Ride not found"]);
        }
    } catch (PDOException $exception) {
        echo json_encode(["message" => "An error occurred", "error" => $exception->getMessage()]);
    }
}

?>
