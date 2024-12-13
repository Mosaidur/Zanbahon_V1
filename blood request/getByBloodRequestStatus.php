<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
require '../Connection.php';

try {
    // Prepare the SQL query to fetch all blood requests with status 'Waiting'
    $stmt = $pdo->prepare("SELECT * FROM bloodrequest WHERE Status = 'Waiting'");

    // Execute the query
    $stmt->execute();

    // Fetch all the rows
    $bloodRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if there are any records
    if ($bloodRequests) {
        echo json_encode([
            "message" => "Blood requests with status 'Waiting' fetched successfully",
            "data" => $bloodRequests
        ]);
    } else {
        echo json_encode([
            "message" => "No blood requests with status 'Waiting' found"
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "message" => "An error occurred",
        "error" => $e->getMessage()
    ]);
}
?>
