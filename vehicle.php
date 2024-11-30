<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require 'Connection.php'; // Database connection

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            createVehicle();
            break;
        case 'GET':
            if (isset($_GET['id'])) {
                getVehicle($_GET['id']);
            } else {
                getVehicles();
            }
            break;
        case 'PUT':
            updateVehicle();
            break;
        case 'DELETE':
            if (isset($_GET['id'])) {
                deleteVehicle($_GET['id']);
            } else {
                echo json_encode(["message" => "Vehicle ID required"]);
            }
            break;
        default:
            echo json_encode(["message" => "Method not supported"]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(["message" => "An unexpected error occurred", "error" => $e->getMessage()]);
}

function createVehicle(): void
{
    global $pdo;
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if the required fields are present in the request data
    if (isset($data['UserId'], $data['VehicleNumber'], $data['VehicleType'], $data['Capacity'], $data['OwnerName'], $data['OwnerContact'], $data['perKMRate'])) {
        
        // Check if vehicle_number is present and not empty
        if (empty($data['VehicleNumber'])) {
            echo json_encode(["message" => "Vehicle number is required"]);
            return;
        }

        // Check if perKMRate is valid and not null
        if (empty($data['perKMRate']) || !is_numeric($data['perKMRate'])) {
            echo json_encode(["message" => "perKMRate is required and must be a number"]);
            return;
        }

        $userId = $data['UserId'] ?? null;
        $vehicleNumber = $data['VehicleNumber'];  // Correct field name
        $vehicleType = $data['VehicleType'] ?? 'Car';  // Defaulting to 'Car' if not provided
        $capacity = $data['Capacity'] ?? 4;  // Defaulting to 4 if not provided
        $ownerName = $data['OwnerName'] ?? null;
        $ownerContact = $data['OwnerContact'] ?? null;
        $vehicleInfo = $data['VehicleInfo'] ?? null;  // Optional BLOB field
        $vehicleVerification = $data['VehicleVerification'] ?? false;  // Default false if not provided
        $perKMRate = $data['perKMRate'];  // perKMRate is required and must be numeric

        try {
            // Check if the vehicle number already exists
            $stmt = $pdo->prepare("SELECT * FROM Vehicle WHERE VehicleNumber = ?");
            $stmt->execute([$vehicleNumber]);
            if ($stmt->fetch()) {
                echo json_encode(["status" => 200, "message" => "Vehicle number already exists"]);
                return;
            }

            // Insert the new vehicle into the database
            $stmt = $pdo->prepare("INSERT INTO Vehicle (UserId, VehicleNumber, VehicleType, Capacity, OwnerName, OwnerContact, VehicleInfo, Vehicle_verification, perKMRate)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            // If vehicleInfo is binary data, ensure it is correctly inserted (use PDO::PARAM_LOB for BLOB)
            $stmt->execute([
                $userId,
                $vehicleNumber,
                $vehicleType,
                $capacity,
                $ownerName,
                $ownerContact,
                $vehicleInfo,  // This should be the binary data, passed as is
                $vehicleVerification,
                $perKMRate  // Insert the perKMRate value
            ]);
            echo json_encode(["status" => 200, "message" => "Vehicle created successfully", "VehicleId" => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}



function getVehicle($id)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM Vehicle WHERE VehicleId = ?");
        $stmt->execute([$id]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($vehicle) {
            echo json_encode(["status" => 200, "vehicle" => $vehicle]);
        } else {
            echo json_encode(["status" => 404, "message" => "Vehicle not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}

function getVehicles()
{
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM Vehicle");
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => 200, "vehicles" => $vehicles]);
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}

function updateVehicle()
{
    global $pdo;
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['VehicleId'], $data['UserId'], $data['VehicleNumber'], $data['VehicleType'], $data['Capacity'], $data['OwnerName'], $data['OwnerContact'], $data['perKMRate'])) {
        $vehicleId = $data['VehicleId'];
        $userId = $data['UserId'];
        $vehicleNumber = $data['VehicleNumber'];
        $vehicleType = $data['VehicleType'] ?? 'Car'; // Default to 'Car' if not provided
        $capacity = $data['Capacity'] ?? 4; // Default to 4 if not provided
        $ownerName = $data['OwnerName'];
        $ownerContact = $data['OwnerContact'];
        $vehicleInfo = isset($data['VehicleInfo']) ? $data['VehicleInfo'] : null; // Optional BLOB field
        $vehicleVerification = isset($data['VehicleVerification']) ? $data['VehicleVerification'] : false; // Default false if not provided
        $perKMRate = $data['perKMRate']; // perKMRate is required and must be a number

        // Check if perKMRate is valid and not null
        if (empty($perKMRate) || !is_numeric($perKMRate)) {
            echo json_encode(["message" => "perKMRate is required and must be a number"]);
            return;
        }

        try {
            // Update vehicle information in the database
            $stmt = $pdo->prepare("UPDATE Vehicle SET 
                                   UserId = ?, 
                                   VehicleNumber = ?, 
                                   VehicleType = ?, 
                                   Capacity = ?, 
                                   OwnerName = ?, 
                                   OwnerContact = ?, 
                                   VehicleInfo = ?, 
                                   Vehicle_verification = ?, 
                                   perKMRate = ?, 
                                   Last_Updated = NOW() 
                                   WHERE VehicleId = ?");
            $stmt->execute([
                $userId,
                $vehicleNumber,
                $vehicleType,
                $capacity,
                $ownerName,
                $ownerContact,
                $vehicleInfo, // This can be binary data
                $vehicleVerification,
                $perKMRate, // Update perKMRate
                $vehicleId
            ]);

            echo json_encode(["status" => 200, "message" => "Vehicle updated successfully"]);
        } catch (PDOException $e) {
            echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
}


function deleteVehicle($id)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM Vehicle WHERE VehicleId = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount()) {
            echo json_encode(["status" => 200, "message" => "Vehicle deleted successfully"]);
        } else {
            echo json_encode(["status" => 404, "message" => "Vehicle not found"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["message" => "An error occurred", "error" => $e->getMessage()]);
    }
}
?>
