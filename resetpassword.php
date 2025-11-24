<?php
session_start();

// Database connection
$host = 'localhost:3307';
$user = 'root';
$pass = '';
$dbname = 'nps_elearning';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

// Check token
if (!isset($_GET['token'])) {
    die("Invalid or missing token.");
}

$token = $_GET['token'];

// Validate token
$stmt = $conn->prepare("SELECT email, expires_at FROM password_reset_tokens WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Invalid or expired reset link.");
}

$data = $result->fetch_assoc();
$email = $data['email'];
$expires = strtotime($data['expires_at']);

if ($expires < time()) {
    die("Reset link has expired.");
}

// If user submits new password
if (isset($_POST['change'])) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $message = "<div class='alert alert-danger'>Passwords do not match.</div>";
    } elseif (strlen($password) < 6) {
        $message = "<div class='alert alert-danger'>Password must be at least 6 characters.</div>";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Update users table
        $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update->bind_param("ss", $hashed, $email);
        $update->execute();

        // Delete used token from new table
        $delete = $conn->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
        $delete->bind_param("s", $token);
        $delete->execute();

        $message = "<div class='alert alert-success'>Password changed successfully! <a href='index.php'>Login</a></div>";
    }
}
?>
