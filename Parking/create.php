<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require '../Connection.php';

function generateSlotNumber($length = 10) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $slotNumber = '';
    for ($i = 0; $i < $length; $i++) {
        $slotNumber .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $slotNumber;
}

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['user_id'], $data['location'], $data['rate_per_hour'], $data['overtime_rate_per_hour'])) {
        $slotNumber = generateSlotNumber();

        $stmt = $pdo->prepare(
            "INSERT INTO Parking (User_Id, Location, Latitude, Longitude, SlotNumber, SlotType, TotalSlots, VehicleType, RatePerHour, OvertimeRatePerHour, Status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $data['user_id'],
            $data['location'] ,
            $data['latitude']?? null,
            $data['longitude']?? null,
            $slotNumber,
            $data['slot_type'] ?? 'Regular',
            $data['total_slots']?? null,
            $data['vehicle_type'] ?? 'Car',
            $data['rate_per_hour']?? null,
            $data['overtime_rate_per_hour'],
            $data['status'] ?? 'Available'
        ]);

        echo json_encode([
            "message" => "Parking slot created successfully",
            "ParkingId" => $pdo->lastInsertId(),
            "SlotNumber" => $slotNumber
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
