<?php
require_once 'config.php';
session_start();

// ✅ 1) User must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

// ✅ 2) XP must be provided
if (!isset($_POST['xp'])) {
    echo json_encode(["status" => "error", "message" => "XP not provided"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$xpEarned = intval($_POST['xp']);
$category = $_POST['category'] ?? 'general'; // Default category if not sent

try {
    $conn = getDBConnection();
    $conn->begin_transaction();

    // ✅ 3) Update total XP in users table
    $stmt = $conn->prepare("UPDATE users SET xp = xp + ? WHERE id = ?");
    $stmt->bind_param("ii", $xpEarned, $user_id);
    $stmt->execute();
    $stmt->close();

    // ✅ 4) Get updated XP after adding
    $stmt2 = $conn->prepare("SELECT xp FROM users WHERE id = ?");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $result = $stmt2->get_result();
    $row = $result->fetch_assoc();
    $updatedXP = intval($row['xp']);
    $stmt2->close();

    // ✅ 5) Log XP history (Important for Dashboard Graph & Improved Users)
    $stmt3 = $conn->prepare("
        INSERT INTO user_progress_org (user_id, xp, recorded_at)
        VALUES (?, ?, NOW())
    ");
    $stmt3->bind_param("ii", $user_id, $updatedXP);
    $stmt3->execute();
    $stmt3->close();

    // ✅ 6) Update XP by category (Leaderboard category section)
    $stmt4 = $conn->prepare("
        INSERT INTO user_category_xp (user_id, category, xp)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE xp = xp + VALUES(xp)
    ");
    $stmt4->bind_param("isi", $user_id, $category, $xpEarned);
    $stmt4->execute();
    $stmt4->close();

    $conn->commit();

    // ✅ Response back to frontend
    echo json_encode([
        "status" => "success",
        "earned" => $xpEarned,
        "total_xp" => $updatedXP
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
