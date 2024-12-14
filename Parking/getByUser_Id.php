<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require '../Connection.php';

try {
    // Check if the 'User_Id' parameter is provided in the query string
    if (isset($_GET['User_Id'])) {
        $userId = $_GET['User_Id'];

        // Prepare SQL query to fetch parking spots by User_Id
        $stmt = $pdo->prepare("SELECT ParkingId, Location, Latitude, Longitude, SlotNumber, SlotType, TotalSlots, VehicleType, RatePerHour, OvertimeRatePerHour, Status, Created_At 
                               FROM Parking 
                               WHERE User_Id = ?");
        $stmt->execute([$userId]);

        // Fetch all matching records
        $parkingSpots = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if any records were found
        if ($parkingSpots) {
            echo json_encode(["message" => "Parking spots retrieved successfully", "data" => $parkingSpots]);
        } else {
            echo json_encode(["message" => "No parking spots found for the given User_Id"]);
        }
    } else {
        echo json_encode(["message" => "User_Id parameter is required"]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "message" => "An error occurred",
        "error" => $e->getMessage()
    ]);
}
?>
