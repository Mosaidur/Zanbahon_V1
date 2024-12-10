<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
require '../Connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['riderid'])) {
        $riderId = $_GET['riderid'];

        if (isset($_GET['realtime']) && $_GET['realtime'] === 'true') {
            header("Content-Type: text/event-stream");
            header("Cache-Control: no-cache");
            header("Connection: keep-alive");
            header("X-Accel-Buffering: no"); // Prevent buffering issues with some servers

            $lastEventId = isset($_SERVER['HTTP_LAST_EVENT_ID']) ? intval($_SERVER['HTTP_LAST_EVENT_ID']) : 0;

            while (true) {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM services WHERE (RiderId = :riderId OR DriverId = :riderId) AND id > :lastId ORDER BY id ASC");
                    $stmt->execute(['riderId' => $riderId, 'lastId' => $lastEventId]);
                    $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($rides)) {
                        foreach ($rides as $ride) {
                            echo "id: " . $ride['id'] . "\n";
                            echo "data: " . json_encode($ride) . "\n\n";
                            $lastEventId = $ride['id'];
                        }
                        ob_flush();
                        flush();
                    }
                } catch (PDOException $e) {
                    echo "event: error\n";
                    echo "data: {\"message\": \"An error occurred\", \"error\": \"" . addslashes($e->getMessage()) . "\"}\n\n";
                    ob_flush();
                    flush();
                    break;
                }

                usleep(500000); // Sleep for 0.5 seconds
            }
            exit;
        }
    } else {
        header("Content-Type: application/json");
        echo json_encode(["status" => 400, "message" => "Rider ID is required"]);
    }
} else {
    header("Content-Type: application/json");
    echo json_encode(["status" => 405, "message" => "Method not allowed"]);
}
?>
