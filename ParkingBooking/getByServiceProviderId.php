<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require '../Connection.php';

try {
    // Check if the 'service_provider_id' parameter is provided in the query string
    if (isset($_GET['service_provider_id'])) {
        $serviceProviderId = $_GET['service_provider_id'];

        // Prepare SQL query to fetch parking bookings by ServiceProviderId
        $stmt = $pdo->prepare(
            "SELECT ParkingBookingId, ServiceName, User_Id, VehicleId, ParkingId, ServiceProviderId, BookingTime, 
                    ParkingStartTime, ParkingEndTime, OvertimeParking, PaymentStatus, BookingStatus, TotalAmount, 
                    OvertimeFee, RegularAmount, OvertimeAmount, BookingLatitude, BookingLongitude, Created_At
             FROM ParkingBooking
             WHERE ServiceProviderId = ?"
        );
        $stmt->execute([$serviceProviderId]);

        // Fetch all matching records
        $parkingBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if any records were found
        if ($parkingBookings) {
            echo json_encode(["message" => "Parking bookings retrieved successfully", "data" => $parkingBookings]);
        } else {
            echo json_encode(["message" => "No parking bookings found for the given service provider"]);
        }
    } else {
        echo json_encode(["message" => "ServiceProviderId parameter is required"]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "message" => "An error occurred",
        "error" => $e->getMessage()
    ]);
}
?>
