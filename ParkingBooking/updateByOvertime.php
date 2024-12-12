<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require '../Connection.php';

try {
    // Fetch all parking bookings
    $stmt = $pdo->prepare("SELECT 
                                ParkingBookingId, ParkingStartTime, ParkingEndTime, RatePerHour, OvertimeRatePerHour,
                                OvertimeParking, OvertimeFee, RegularAmount, OvertimeAmount
                           FROM ParkingBooking");
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if any bookings were found
    if ($bookings) {
        foreach ($bookings as $booking) {
            $parkingStartTime = new DateTime($booking['ParkingStartTime']);
            $parkingEndTime = new DateTime($booking['ParkingEndTime']);
            $ratePerHour = $booking['RatePerHour'];
            $overtimeRatePerHour = $booking['OvertimeRatePerHour'];
            $regularAmount = $booking['RegularAmount'];
            $overtimeAmount = $booking['OvertimeAmount'];

            // Calculate the duration of parking
            $interval = $parkingStartTime->diff($parkingEndTime);
            $durationInHours = $interval->h + ($interval->i / 60);

            // Initialize overtime status and amount
            $overtime = false;
            $totalAmount = $regularAmount;

            // Check if parking duration exceeded the expected time (1 hour is considered regular)
            if ($durationInHours > 1) { // Assuming the regular parking duration is 1 hour
                $overtime = true;
                $overtimeFee = ($durationInHours - 1) * $overtimeRatePerHour; // Calculate overtime fee
                $overtimeAmount = $overtimeFee;
                $totalAmount = $regularAmount + $overtimeAmount;
            } else {
                $overtimeAmount = 0; // No overtime fee
            }

            // Update the overtime status and fee in the database
            $updateStmt = $pdo->prepare("UPDATE ParkingBooking 
                                         SET OvertimeParking = ?, OvertimeFee = ?, OvertimeAmount = ?, TotalAmount = ?
                                         WHERE ParkingBookingId = ?");
            $updateStmt->execute([$overtime, $overtimeFee, $overtimeAmount, $totalAmount, $booking['ParkingBookingId']]);
        }

        // Send success response
        echo json_encode([
            "message" => "Overtime check and updates completed successfully"
        ]);
    } else {
        echo json_encode(["message" => "No parking bookings found"]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "message" => "An error occurred",
        "error" => $e->getMessage()
    ]);
}
?>
