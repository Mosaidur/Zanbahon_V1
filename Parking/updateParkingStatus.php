<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require '../Connection.php';

try {
    // Fetch all parking bookings where the parking end time is earlier than now and the booking status is not 'Completed'
    $stmt = $pdo->prepare("
        SELECT 
            pb.ParkingBookingId, 
            pb.ParkingStartTime, 
            pb.ParkingEndTime, 
            pb.RegularAmount,
            pb.ParkingId,
            p.OvertimeRatePerHour
        FROM ParkingBooking pb
        INNER JOIN Parking p ON pb.ParkingId = p.ParkingId
        WHERE pb.ParkingEndTime < NOW() AND pb.BookingStatus != 'Completed'
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($bookings) {
        foreach ($bookings as $booking) {
            $parkingEndTime = new DateTime($booking['ParkingEndTime']);
            $currentTime = new DateTime();

            $regularAmount = $booking['RegularAmount'];
            $overtimeRatePerHour = $booking['OvertimeRatePerHour'];
            $overtimeAmount = 0;
            $totalAmount = $regularAmount;
            $overtime = false;

            // Check if the current time exceeds the parking end time to calculate overtime
            if ($parkingEndTime < $currentTime) {
                $interval = $parkingEndTime->diff($currentTime);

                // Calculate overtime hours
                $totalOvertimeHours = $interval->h + ($interval->days * 24);
                $totalOvertimeMinutes = $interval->i;
                $overtimeDurationInHours = $totalOvertimeHours + ($totalOvertimeMinutes / 60);

                // Calculate overtime amount
                $overtimeAmount = $overtimeDurationInHours * $overtimeRatePerHour;
                $totalAmount += $overtimeAmount;
                $overtime = true;
            }

            // Update the booking record with calculated values
            $updateStmt = $pdo->prepare("
                UPDATE ParkingBooking 
                SET 
                    OvertimeParking = ?, 
                    OvertimeFee = ?, 
                    OvertimeAmount = ?, 
                    TotalAmount = ?, 
                    PaymentStatus = 'Paid', 
                    BookingStatus = 'Completed', 
                    ParkingEndTime = NOW()
                WHERE ParkingBookingId = ?
            ");
            $updateStmt->execute([$overtime, $overtimeAmount, $overtimeAmount, $totalAmount, $booking['ParkingBookingId']]);

            // Free up the parking slot by updating its status to 'Available'
            $updateParkingStmt = $pdo->prepare("
                UPDATE Parking 
                SET Status = 'Available'
                WHERE ParkingId = ?
            ");
            $updateParkingStmt->execute([$booking['ParkingId']]);
        }

        // Respond with a success message
        echo json_encode(["message" => "Overtime check and updates completed successfully"]);
    } else {
        echo json_encode(["message" => "No parking bookings found that require updates"]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "message" => "An error occurred",
        "error" => $e->getMessage()
    ]);
}
?>
