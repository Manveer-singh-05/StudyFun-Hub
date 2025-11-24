<?php
session_start();

$conn = new mysqli("localhost:3307", "root", "", "nps_elearning");

if ($conn->connect_error) {
    die("DB Error: " . $conn->connect_error);
}

$message = $_POST['message'];
$email = $_SESSION['user_email'] ?? null;

$stmt = $conn->prepare("INSERT INTO feedback (user_email, message) VALUES (?, ?)");
$stmt->bind_param("ss", $email, $message);
$stmt->execute();

$stmt->close();
$conn->close();

header("Location: thanks_feedback.php");
exit();
?>
