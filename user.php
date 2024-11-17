<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With");

require 'Connection.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Fetch a single user by ID
            $stmt = $pdo->prepare("SELECT * FROM User WHERE UserId = ?");
            $stmt->execute([$_GET['id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($user);
        } else {
            // Fetch all users
            $stmt = $pdo->query("SELECT * FROM User");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($users);
        }
        break;

    case 'POST':
        // Create a new user
        $stmt = $pdo->prepare("INSERT INTO User (Name, Email, NID, Phone, Password, RoleId) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $input['Name'],
            $input['Email'],
            $input['NID'],
            $input['Phone'],
            password_hash($input['Password'], PASSWORD_DEFAULT), // Hash the password
            $input['RoleId']
        ]);
        echo json_encode(["status" => $result ? "success" : "error"]);
        break;

    case 'PUT':
        // Update an existing user
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("UPDATE User SET Name = ?, Email = ?, NID = ?, Phone = ?, RoleId = ? WHERE UserId = ?");
            $result = $stmt->execute([
                $input['Name'],
                $input['Email'],
                $input['NID'],
                $input['Phone'],
                $input['RoleId'],
                $_GET['id']
            ]);
            echo json_encode(["status" => $result ? "success" : "error"]);
        } else {
            echo json_encode(["status" => "error", "message" => "User ID required"]);
        }
        break;

    case 'DELETE':
        // Delete a user
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("DELETE FROM User WHERE UserId = ?");
            $result = $stmt->execute([$_GET['id']]);
            echo json_encode(["status" => $result ? "success" : "error"]);
        } else {
            echo json_encode(["status" => "error", "message" => "User ID required"]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Unsupported request method"]);
        break;
}
?>
