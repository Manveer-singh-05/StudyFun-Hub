<?php
$conn = new mysqli("localhost:3307", "root", "", "nps_elearning");

if ($conn->connect_error) {
    die("DB Error: " . $conn->connect_error);
}

$email = $_POST['email'];

// ✅ Check if already subscribed
$stmt = $conn->prepare("SELECT id FROM subscribers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // ✅ Already subscribed
    header("Location: already_subscribed.php");
    exit();
}

// ✅ Insert new subscriber
$stmt = $conn->prepare("INSERT INTO subscribers (email) VALUES (?)");
$stmt->bind_param("s", $email);
$stmt->execute();

$stmt->close();
$conn->close();

header("Location: subscribed_success.php");
exit();
?>
