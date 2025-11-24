<?php
$conn = new mysqli("localhost:3307", "root", "", "nps_elearning");

$name = $_POST['name'];
$email = $_POST['email'];
$message = $_POST['message'];

$stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $message);
$stmt->execute();

$conn->close();

header("Location: thank_you_contact.php");
exit();
?>
