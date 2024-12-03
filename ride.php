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
            createService();
            break;
        case 'GET':
            if (isset($_GET['id'])) {
                getRideByRiderID($_GET['id']);
            } elseif(isset($_GET['id'])){
                getRideByRideStatus($status);
            }
            else {
                getRides();
            }
            break;
            
            
            case 'PUT':
                // Update ride details
                if (isset($_PUT['ride_id'], $_PUT['ride_status'], $_PUT['ride_cancelled_reason'], $_PUT['ride_accept_time'], $_PUT['ride_start_time'], $_PUT['ride_end_time'], $_PUT['ride_rating'])) {
                    updateRide();
                }
                // Update ride from driver's side (includes driverId, vehicleId, and ride accept time)
                elseif (isset($_PUT['rideId'], $_PUT['driverId'], $_PUT['vehicleId'], $_PUT['rideStatus'], $_PUT['rideAcceptTime'])) {
                    updateRideFromDriverSide(
                        $_PUT['rideId'],
                        $_PUT['driverId'],
                        $_PUT['vehicleId'],
                        $_PUT['rideStatus'],
                        $_PUT['rideAcceptTime']
                    );
                }
                // Start the ride
                elseif (isset($_PUT['rideId'], $_PUT['rideStartTime'])) {
                    updateRideStart($_PUT['rideId'], $_PUT['rideStartTime']);
                }
                // End the ride
                elseif (isset($_PUT['rideId'], $_PUT['rideEndTime'])) {
                    updateRideEnd($_PUT['rideId'], $_PUT['rideEndTime']);
                }
                // Cancel the ride
                elseif (isset($_PUT['rideId'], $_PUT['cancelReason'])) {
                    updateRideCancel($_PUT['rideId'], $_PUT['cancelReason']);
                } 
                else {
                    echo json_encode(["status" => 400, "message" => "Invalid input for PUT request"]);
                }
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

function createService(): void
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
        $totalFareAmount = $data['total_fare_amount'] ?? null;
        $totalDistance = $data['total_distance'];
        $totalTime = $data['total_time'] ?? null;
        $approximateTime = $data['approximate_time'] ?? null;
        $serviceName = $data['service_name'] ?? 'Ride Share';

        try {
            $stmt = $pdo->prepare("INSERT INTO Services (RiderId, DriverId, VehicleId, PickupLocation, DropLocation, PickupLatitude, PickupLongitude, DropLatitude, DropLongitude, RideStatus, RideCancelledReason, TotalFareAmount, TotalDistance, TotalTime, ApproximateTime, ServiceName) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$riderId, $driverId, $vehicleId, $pickupLocation, $dropLocation, $pickupLatitude, $pickupLongitude, $dropLatitude, $dropLongitude, $rideStatus, $rideCancelledReason, $totalFareAmount, $totalDistance, $totalTime, $approximateTime, $serviceName]);
            echo json_encode(["message" => "Service created successfully", "ServiceId" => $pdo->lastInsertId()]);
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
        $stmt = $pdo->prepare("SELECT * FROM Services WHERE RiderId = ? OR DriverId = ?");
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
        $stmt = $pdo->query("SELECT * FROM Services");
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => 200, "rides" => $rides]);
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}

function getRideByRideStatus($status)
{
    global $pdo;
    try {
        // Prepare the query to fetch rides with the given RideStatus
        $stmt = $pdo->prepare("SELECT * FROM Services WHERE RideStatus = ?");
        $stmt->execute([$status]);
        
        // Fetch all matching rows
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
            $stmt = $pdo->prepare("UPDATE Services SET RideStatus = ?, RideCancelledReason = ?, RideAcceptTime = ?, RideStartTime = ?, RideEndTime = ?, RideRating = ?, Last_Updated = NOW() WHERE RideId = ?");
            $stmt->execute([$rideStatus, $rideCancelledReason, $rideAcceptTime, $rideStartTime, $rideEndTime, $rideRating, $rideId]);

            echo json_encode(["status" => 200, "message" => "Ride updated successfully"]);
        } catch (PDOException $e) {
            echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}

function updateRideFromDriverSide($rideId, $driverId, $vehicleId, $rideStatus, $rideAcceptTime)
{
    global $pdo;
    try {
        // Prepare the SQL query to update the ride information
        $stmt = $pdo->prepare("UPDATE Ride 
                               SET DriverId = ?, VehicleId = ?, RideStatus = ?, RideAcceptTime = ? 
                               WHERE RideId = ?");
        
        // Execute the query with the provided parameters
        $stmt->execute([$driverId, $vehicleId, $rideStatus, $rideAcceptTime, $rideId]);

        // Check if any row was affected (i.e., if the ride was updated)
        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => 200, "message" => "Ride updated successfully"]);
        } else {
            echo json_encode(["status" => 400, "message" => "Failed to update ride or ride not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}

function updateRideStart($rideId, $startTime)
{
    global $pdo;
    try {
        // Update the RideStatus to 'In Progress' and set the RideStartTime
        $stmt = $pdo->prepare("UPDATE Services 
                               SET RideStatus = 'Started', RideStartTime = ? 
                               WHERE RideId = ?");
        $stmt->execute([$startTime, $rideId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => 200, "message" => "Ride started successfully"]);
        } else {
            echo json_encode(["status" => 400, "message" => "Failed to update ride or ride not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}

function updateRideEnd($rideId, $endTime)
{
    global $pdo;
    try {
        // Step 1: Fetch the RideStartTime from the database for the ride
        $stmt = $pdo->prepare("SELECT RideStartTime FROM Services WHERE RideId = ?");
        $stmt->execute([$rideId]);
        $ride = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ride) {
            echo json_encode(["status" => 400, "message" => "Ride not found"]);
            return;
        }

        $rideStartTime = $ride['RideStartTime'];
        
        // Step 2: Calculate the total time by getting the difference between the start and end times
        $startTime = new DateTime($rideStartTime);
        $endTime = new DateTime($endTime);
        $interval = $startTime->diff($endTime);
        $totalTime = $interval->format('%h:%i:%s'); // Format the time as hours:minutes:seconds

        // Step 3: Update the RideEndTime, RideStatus, and TotalTime
        $stmt = $pdo->prepare("UPDATE Ride
                               SET RideEndTime = ?, RideStatus = 'Complete', TotalTime = ?
                               WHERE RideId = ?");
        $stmt->execute([$endTime, $totalTime, $rideId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => 200, "message" => "Ride completed successfully", "totalTime" => $totalTime]);
        } else {
            echo json_encode(["status" => 400, "message" => "Failed to update ride or ride not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}

function updateRideCancel($rideId, $cancelReason)
{
    global $pdo;
    try {
        // Step 1: Check if the ride exists
        $stmt = $pdo->prepare("SELECT * FROM Services WHERE RideId = ?");
        $stmt->execute([$rideId]);
        $ride = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ride) {
            echo json_encode(["status" => 400, "message" => "Ride not found"]);
            return;
        }

        // Step 2: Update the RideStatus to 'Cancelled' and set the cancellation reason
        $stmt = $pdo->prepare("UPDATE Ride 
                               SET RideStatus = 'Cancelled', RideCancelledReason = ?
                               WHERE RideId = ?");
        $stmt->execute([$cancelReason, $rideId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => 200, "message" => "Ride cancelled successfully"]);
        } else {
            echo json_encode(["status" => 400, "message" => "Failed to cancel the ride"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}


function deleteRide($id)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM Services WHERE RideId = ?");
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
