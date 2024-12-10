<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Include database connection
require '../Connection.php';

try {
    // Retrieve the 'status' parameter from the query string
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';

    // Validate 'status' parameter
    if (empty($status)) {
        echo json_encode([
            "status" => 400,
            "message" => "Invalid or empty 'status' parameter"
        ]);
        exit;
    }

    // Prepare and execute the query to fetch rides by status
    $stmt = $pdo->prepare("SELECT * FROM services WHERE RideStatus = :status");
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch the results
    $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the results or a message if no rides are found
    echo json_encode($rides ? 
        ["status" => 200, "rides" => $rides] : 
        ["status" => 200, "message" => "No rides found with the specified status"]
    );
} catch (PDOException $e) {
    // Return an error response if an exception occurs
    echo json_encode([
        "status" => 500,
        "message" => "An error occurred",
        "error" => $e->getMessage()
    ]);
}
