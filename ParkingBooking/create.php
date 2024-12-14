<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require '../Connection.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['user_id'], $data['vehicle_id'], $data['parking_id'], $data['parking_start_time'], $data['parking_end_time'])) {
        $userId = $data['user_id'];
        $vehicleId = $data['vehicle_id'];
        $parkingId = $data['parking_id'];
        $startTime = $data['parking_start_time'];
        $endTime = $data['parking_end_time'];
        $ServiceProviderId = $data['ServiceProviderId'];

        // Check parking availability
        $availabilityStmt = $pdo->prepare("SELECT Status, RatePerHour, OvertimeRatePerHour FROM Parking WHERE ParkingId = ?");
        $availabilityStmt->execute([$parkingId]);
        $parking = $availabilityStmt->fetch(PDO::FETCH_ASSOC);

        if (!$parking || $parking['Status'] !== 'Available') {
            echo json_encode(["message" => "Parking is not available"]);
            exit;
        }

        // Calculate total time and fare
        $startTimeObj = new DateTime($startTime);
        $endTimeObj = new DateTime($endTime);
        $interval = $startTimeObj->diff($endTimeObj);
        $totalHours = $interval->h + ($interval->days * 24) + ($interval->i > 0 ? 1 : 0);

        $regularHours = min($totalHours, 1); // Assuming 1 hour is regular
        $overtimeHours = max($totalHours - 1, 0);

        $regularAmount = $regularHours * $parking['RatePerHour'];
        $overtimeAmount = $overtimeHours * $parking['OvertimeRatePerHour'];
        $totalAmount = $regularAmount + $overtimeAmount;

        // Insert booking request
        $bookingStmt = $pdo->prepare(
            "INSERT INTO ParkingBooking (User_Id, VehicleId, ParkingId, ParkingStartTime, ParkingEndTime, RegularAmount, OvertimeAmount, TotalAmount, BookingStatus, PaymentStatus,ServiceProviderId) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Accepted', 'Pending',?)"
        );

        $bookingStmt->execute([
            $userId,
            $vehicleId,
            $parkingId,
            $startTime,
            $endTime,
            $regularAmount,
            $overtimeAmount,
            $totalAmount,
            $ServiceProviderId
        ]);

        // Update parking status to Unavailable
        $updateStatusStmt = $pdo->prepare("UPDATE Parking SET Status = 'Booked' WHERE ParkingId = ?");
        $updateStatusStmt->execute([$parkingId]);

        echo json_encode([
            "message" => "Booking created successfully",
            "BookingId" => $pdo->lastInsertId(),
            "TotalAmount" => $totalAmount
        ]);
    } else {
        echo json_encode(["message" => "Invalid input"]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "message" => "An error occurred",
        "error" => $e->getMessage()
    ]);
}
?>
