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

    // Prepare and execute the query for full join
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
    ";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch the results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the results or a message if no matches are found
    echo json_encode($results ? 
        ["status" => 200, "data" => $results] : 
        ["status" => 200, "message" => "No matching data found with the specified status"]
    );
} catch (PDOException $e) {
    // Return an error response if an exception occurs
    echo json_encode([
        "status" => 500,
        "message" => "An error occurred",
        "error" => $e->getMessage()
    ]);
}
?>
