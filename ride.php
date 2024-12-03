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
            } elseif(isset($_GET['status'])){
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
            $stmt = $pdo->prepare("INSERT INTO services (RiderId, DriverId, VehicleId, PickupLocation, DropLocation, PickupLatitude, PickupLongitude, DropLatitude, DropLongitude, RideStatus, RideCancelledReason, TotalFareAmount, TotalDistance, TotalTime, ApproximateTime, ServiceName) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
        $stmt = $pdo->prepare("SELECT * FROM services WHERE RiderId = ? OR DriverId = ?");
        $stmt->execute([$id, $id]);
        
        // Fetch all matching rows
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rides) {
            echo json_encode(["status" => 200, "rides" => $rides]);
        } else {
            echo json_encode(["status" => 200, "message" => "No rides found for the provided Rider or Driver ID"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
    }
}



function getRides()
{
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM services");
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rides) {
            echo json_encode(["status" => 200, "rides" => $rides]);
        } else {
            echo json_encode(["status" => 200, "message" => "No rides found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
    }
}


function getRideByRideStatus($status)
{
    global $pdo;

    // Validate that the status is not empty
    if (empty($status)) {
        echo json_encode(["status" => 400, "message" => "Invalid or empty status parameter"]);
        return;
    }

    try {
        // Prepare the query to fetch rides with the given RideStatus
        $stmt = $pdo->prepare("SELECT * FROM services WHERE RideStatus = ?");
        $stmt->execute([$status]);
        
        // Fetch all matching rows
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rides) {
            echo json_encode(["status" => 200, "rides" => $rides]);
        } else {
            echo json_encode(["status" => 200, "message" => "No rides found with the specified status"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
    }
}



function updateRide()
{
    global $pdo;
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if the ride_id is set, as it's a mandatory field
    if (!isset($data['ride_id'])) {
        echo json_encode(["status" => 400, "message" => "Missing required field: ride_id"]);
        return;
    }

    // Validate ride_rating if provided
    if (isset($data['ride_rating']) && !is_numeric($data['ride_rating'])) {
        echo json_encode(["status" => 400, "message" => "Invalid rating value. It should be a number."]);
        return;
    }

    // Validate if ride_id exists in the database
    try {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE RideId = ?");
        $stmt->execute([$data['ride_id']]);
        $ride = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ride) {
            echo json_encode(["status" => 400, "message" => "Ride not found"]);
            return;
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => 500, "message" => "An error occurred while checking the ride", "error" => $e->getMessage()]);
        return;
    }

    // Proceed with updating if ride exists
    $rideId = $data['ride_id'];
    $rideStatus = $data['ride_status'] ?? null;
    $rideCancelledReason = $data['ride_cancelled_reason'] ?? null;
    $rideAcceptTime = $data['ride_accept_time'] ?? null;
    $rideStartTime = $data['ride_start_time'] ?? null;
    $rideEndTime = $data['ride_end_time'] ?? null;
    $rideRating = $data['ride_rating'] ?? null;

    try {
        $stmt = $pdo->prepare("UPDATE services SET 
                               RideStatus = ?, RideCancelledReason = ?, RideAcceptTime = ?, 
                               RideStartTime = ?, RideEndTime = ?, RideRating = ?, Last_Updated = NOW() 
                               WHERE RideId = ?");
        $stmt->execute([$rideStatus, $rideCancelledReason, $rideAcceptTime, 
                        $rideStartTime, $rideEndTime, $rideRating, $rideId]);

        echo json_encode(["status" => 200, "message" => "Ride updated successfully"]);
    } catch (PDOException $e) {
        echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
    }
}


function updateRideFromDriverSide($rideId, $driverId, $vehicleId, $rideStatus, $rideAcceptTime)
{
    global $pdo;
    try {
        // Step 1: Validate the input parameters
        if (empty($rideId) || empty($driverId) || empty($vehicleId) || empty($rideStatus) || empty($rideAcceptTime)) {
            echo json_encode(["status" => 400, "message" => "All fields are required"]);
            return;
        }

        // Step 2: Validate the rideAcceptTime format
        try {
            $rideAcceptTimeObj = new DateTime($rideAcceptTime); // Validate date format
        } catch (Exception $e) {
            echo json_encode(["status" => 400, "message" => "Invalid rideAcceptTime format"]);
            return;
        }

        // Step 3: Check if the ride exists
        $stmt = $pdo->prepare("SELECT * FROM services WHERE RideId = ?");
        $stmt->execute([$rideId]);
        $ride = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ride) {
            echo json_encode(["status" => 400, "message" => "Ride not found"]);
            return;
        }

        // Step 4: Prepare the SQL query to update the ride information
        $stmt = $pdo->prepare("UPDATE services 
                               SET DriverId = ?, VehicleId = ?, RideStatus = ?, RideAcceptTime = ? 
                               WHERE RideId = ?");

        // Execute the query with the provided parameters
        $stmt->execute([$driverId, $vehicleId, $rideStatus, $rideAcceptTime, $rideId]);

        // Step 5: Check if any row was affected (i.e., if the ride was updated)
        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => 200, "message" => "Ride updated successfully"]);
        } else {
            echo json_encode(["status" => 400, "message" => "Failed to update ride or ride not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
    }
}


function updateRideStart($rideId, $startTime)
{
    global $pdo;
    try {
        // Step 1: Check if the ride exists
        $stmt = $pdo->prepare("SELECT * FROM services WHERE RideId = ?");
        $stmt->execute([$rideId]);
        $ride = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ride) {
            echo json_encode(["status" => 400, "message" => "Ride not found"]);
            return;
        }

        // Step 2: Validate the start time format
        try {
            $startTimeObj = new DateTime($startTime); // Check if the start time is in a valid format
        } catch (Exception $e) {
            echo json_encode(["status" => 400, "message" => "Invalid start time format"]);
            return;
        }

        // Step 3: Update the RideStatus to 'Started' and set the RideStartTime
        $stmt = $pdo->prepare("UPDATE services 
                               SET RideStatus = 'Started', RideStartTime = ? 
                               WHERE RideId = ?");
        $stmt->execute([$startTime, $rideId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => 200, "message" => "Ride started successfully"]);
        } else {
            echo json_encode(["status" => 400, "message" => "Failed to update ride"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
    }
}


function updateRideEnd($rideId, $endTime)
{
    global $pdo;
    try {
        // Step 1: Fetch the RideStartTime from the database for the ride
        $stmt = $pdo->prepare("SELECT RideStartTime FROM services WHERE RideId = ?");
        $stmt->execute([$rideId]);
        $ride = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ride) {
            echo json_encode(["status" => 400, "message" => "Ride not found"]);
            return;
        }

        $rideStartTime = $ride['RideStartTime'];

        // Step 2: Validate end time format and calculate the total time
        try {
            $startTime = new DateTime($rideStartTime);
            $endTimeObj = new DateTime($endTime);
            $interval = $startTime->diff($endTimeObj);
            $totalTime = $interval->format('%h:%i:%s'); // Format the time as hours:minutes:seconds
        } catch (Exception $e) {
            echo json_encode(["status" => 400, "message" => "Invalid end time format"]);
            return;
        }

        // Step 3: Update the RideEndTime, RideStatus, and TotalTime
        $stmt = $pdo->prepare("UPDATE services
                               SET RideEndTime = ?, RideStatus = 'Complete', TotalTime = ?
                               WHERE RideId = ?");
        $stmt->execute([$endTime, $totalTime, $rideId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => 200, "message" => "Ride completed successfully", "totalTime" => $totalTime]);
        } else {
            echo json_encode(["status" => 400, "message" => "Failed to update ride or ride not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
    }
}


function updateRideCancel($rideId, $cancelReason)
{
    if (empty($rideId) || empty($cancelReason)) {
        echo json_encode(["status" => 400, "message" => "Missing required parameters: rideId or cancelReason"]);
        return;
    }

    global $pdo;
    try {
        // Step 1: Check if the ride exists
        $stmt = $pdo->prepare("SELECT * FROM services WHERE RideId = ?");
        $stmt->execute([$rideId]);
        $ride = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ride) {
            echo json_encode(["status" => 400, "message" => "Ride not found"]);
            return;
        }

        // Step 2: Update the RideStatus to 'Cancelled' and set the cancellation reason
        $stmt = $pdo->prepare("UPDATE services 
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
    if (empty($id)) {
        echo json_encode(["status" => 400, "message" => "Missing ride ID"]);
        return;
    }

    global $pdo;
    try {
        // Attempt to delete the ride from the database
        $stmt = $pdo->prepare("DELETE FROM services WHERE RideId = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount()) {
            echo json_encode(["status" => 200, "message" => "Ride deleted successfully"]);
        } else {
            echo json_encode(["status" => 404, "message" => "Ride not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => 500, "message" => "An error occurred", "error" => $e->getMessage()]);
    }
}

?>
