<?php
// Include the database connection file
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'Connection.php'; // Assuming 'Connection.php' is properly set up with PDO

// Main API Switch Case
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetPaymentsRequest();
        break;
    case 'POST':
        handleCreatePaymentRequest();
        break;
    case 'PUT':
        handleUpdatePaymentRequest();
        break;
    case 'DELETE':
        handleDeletePaymentRequest();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        break;
}

// GET Handler
function handleGetPaymentsRequest()
{
    global $pdo;

    if (isset($_GET['paymentId'])) {
        // Fetch payment by PaymentId
        $paymentId = $_GET['paymentId'];
        $stmt = $pdo->prepare("SELECT * FROM Payments WHERE PaymentId = :paymentId");
        $stmt->bindParam(':paymentId', $paymentId);
    } elseif (isset($_GET['userId'])) {
        // Fetch payments by SenderId or ReceiverId
        $userId = $_GET['userId'];
        $stmt = $pdo->prepare("SELECT * FROM Payments WHERE SenderId = :userId OR ReceiverId = :userId");
        $stmt->bindParam(':userId', $userId);
    } else {
        // Fetch all payments
        $stmt = $pdo->prepare("SELECT * FROM Payments");
    }

    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $payments]);
}

// POST Handler
function handleCreatePaymentRequest()
{
    global $pdo;

    // Get the incoming data
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if all required fields are provided
    if (isset($data['SenderId'], $data['Amount'], $data['PaymentMethod'], $data['TransactionId'])) {
        
        // First, check if the TransactionId already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Payments WHERE TransactionId = :TransactionId");
        $stmt->execute([':TransactionId' => $data['TransactionId']]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            // Return an error message if the TransactionId already exists
            echo json_encode(['success' => false, 'message' => 'Error: Duplicate TransactionId.']);
            return;
        }

        // Proceed with the insert if no duplicate found
        try {
            $stmt = $pdo->prepare("INSERT INTO Payments (SenderId, ReceiverId, ServiceType, ServiceId, Amount, PaymentMethod, PaymentStatus, TransactionId, SenderAccountNumber, ReceiverAccountNumber) 
                VALUES (:SenderId, :ReceiverId, :ServiceType, :ServiceId, :Amount, :PaymentMethod, :PaymentStatus, :TransactionId, :SenderAccountNumber, :ReceiverAccountNumber)");

            $stmt->execute([
                ':SenderId' => $data['SenderId'],
                ':ReceiverId' => $data['ReceiverId'] ?? 1,
                ':ServiceType' => $data['ServiceType'] ?? 'no',
                ':ServiceId' => $data['ServiceId'] ?? null,
                ':Amount' => $data['Amount'],
                ':PaymentMethod' => $data['PaymentMethod'],
                ':PaymentStatus' => $data['PaymentStatus'] ?? 'Paid',
                ':TransactionId' => $data['TransactionId'],
                ':SenderAccountNumber' => $data['SenderAccountNumber'] ?? null,
                ':ReceiverAccountNumber' => $data['ReceiverAccountNumber'] ?? null,
            ]);

            echo json_encode(['success' => true, 'message' => 'Payment added successfully.']);
        } catch (PDOException $e) {
            // Catch any other errors and return the error message
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }

    } else {
        // Return error message for missing required fields
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    }
}


// PUT Handler
function handleUpdatePaymentRequest()
{
    global $pdo;

    // Get the incoming data
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if both PaymentId and PaymentStatus are provided
    if (isset($data['PaymentId'], $data['PaymentStatus'])) {
        
        // Check if the PaymentId exists in the database
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Payments WHERE PaymentId = :PaymentId");
        $stmt->execute([':PaymentId' => $data['PaymentId']]);
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            // Return an error message if the PaymentId doesn't exist
            echo json_encode(['success' => false, 'message' => 'Error: PaymentId not found.']);
            return;
        }

        // Proceed with the update if the PaymentId exists
        try {
            $stmt = $pdo->prepare("UPDATE Payments SET PaymentStatus = :PaymentStatus WHERE PaymentId = :PaymentId");
            $stmt->execute([
                ':PaymentStatus' => $data['PaymentStatus'],
                ':PaymentId' => $data['PaymentId'],
            ]);

            echo json_encode(['success' => true, 'message' => 'Payment updated successfully.']);
        } catch (PDOException $e) {
            // Catch any other errors and return the error message
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }

    } else {
        // Return error message for missing required fields
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    }
}


// DELETE Handler
function handleDeletePaymentRequest()
{
    global $pdo;

    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['PaymentId'])) {
        $stmt = $pdo->prepare("DELETE FROM Payments WHERE PaymentId = :PaymentId");

        $stmt->bindParam(':PaymentId', $data['PaymentId']);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Payment deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing PaymentId.']);
    }
}
?>
