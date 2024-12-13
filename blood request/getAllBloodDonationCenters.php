<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require '../Connection.php';

try {
    // Prepare the SQL query to fetch all blood donation centers
    $stmt = $pdo->prepare("SELECT * FROM BloodDonationCenters");

    // Execute the query
    $stmt->execute();

    // Fetch all the rows
    $bloodDonationCenters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if there are any records
    if ($bloodDonationCenters) {
        echo json_encode([
            "message" => "Blood Donation Centers fetched successfully",
            "data" => $bloodDonationCenters
        ]);
    } else {
        echo json_encode([
            "message" => "No Blood Donation Centers found"
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "message" => "An error occurred",
        "error" => $e->getMessage()
    ]);
}
?>
