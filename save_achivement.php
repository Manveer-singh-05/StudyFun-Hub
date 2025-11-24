<?php
require_once "config.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$challenge_key = $_POST['challenge_key'] ?? null;
$title = $_POST['title'] ?? null;
$description = $_POST['description'] ?? null;

if (!$challenge_key || !$title) {
    echo json_encode(["status" => "error", "message" => "Invalid data"]);
    exit;
}

try {
    $conn = getDBConnection();

    // ✅ Prevent duplicate badges
    $stmtCheck = $conn->prepare("
        SELECT id FROM user_badges 
        WHERE user_id = ? AND badge_name = ?
    ");
    $stmtCheck->bind_param("is", $user_id, $title);
    $stmtCheck->execute();
    $exists = $stmtCheck->get_result()->num_rows > 0;
    $stmtCheck->close();

    if ($exists) {
        echo json_encode(["status" => "exists", "message" => "Badge already earned"]);
        exit;
    }

    // ✅ Insert badge
    $stmt = $conn->prepare("
        INSERT INTO user_badges (user_id, badge_name, earned_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->bind_param("is", $user_id, $title);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["status" => "success", "message" => "Badge earned"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
