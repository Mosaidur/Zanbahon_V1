<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Include database connection
require '../Connection.php';

try {
    // Retrieve the 'status' and 'servicename' parameters from the query string
    $status = isset($_GET['status']) ? trim($_GET['status']) : null;
    $servicename = isset($_GET['servicename']) ? trim($_GET['servicename']) : null;

    // Validate 'status' and 'servicename' parameters
    if (!$status || !$servicename) {
        echo json_encode([
            "status" => 400,
            "message" => "Invalid or empty 'status' or 'servicename' parameter"
        ]);
        exit;
    }

    // Prepare the SQL query for full join with additional conditions
    $query = "
        SELECT 
            s.*, 
            p.* 
        FROM 
            services s
        LEFT JOIN 
            Payments p
        ON 
            s.RideId = p.TransactionId
        WHERE 
            s.RideStatus = :status
            AND s.ServiceName = :servicename
        UNION
        SELECT 
            s.*, 
            p.* 
        FROM 
            services s
        RIGHT JOIN 
            Payments p
        ON 
            s.RideId = p.TransactionId
        WHERE 
            p.PaymentStatus = :status
            AND s.ServiceName = :servicename
    ";

    // Prepare and execute the query
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->bindParam(':servicename', $servicename, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch the results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the results or a message if no matches are found
    if ($results) {
        echo json_encode([
            "status" => 200,
            "data" => $results
        ]);
    } else {
        echo json_encode([
            "status" => 200,
            "message" => "No matching data found with the specified parameters"
        ]);
    }
} catch (PDOException $e) {
    // Return an error response if an exception occurs
    echo json_encode([
        "status" => 500,
        "message" => "An error occurred",
        "error" => $e->getMessage()
    ]);
}
?>
