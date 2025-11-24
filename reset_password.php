<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// DB Connection
$conn = new mysqli("localhost:3307", "root", "", "nps_elearning");
if ($conn->connect_error) {
    die("DB Error: " . $conn->connect_error);
}

$message = "";

// ✅ 1. Check if token exists in URL
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid or missing token");
}

$token = $_GET['token'];

// ✅ 2. Validate token in database
$stmt = $conn->prepare("SELECT email, expires_at FROM password_reset_tokens WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid or expired token");
}

$row = $result->fetch_assoc();

// ✅ 3. Check expiration
if (strtotime($row['expires_at']) < time()) {
    die("Token has expired. Request a new reset link.");
}

$email = $row['email'];

// ✅ 4. If user submits new password
if (isset($_POST['update'])) {
    $newPass = $_POST['password'];
    $confirmPass = $_POST['confirm'];

    if ($newPass !== $confirmPass) {
        $message = "<div class='alert alert-danger'>Passwords do not match.</div>";
    } else {
        // ✅ Hash password
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);

        // ✅ Update user password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
        $stmt->execute();

        // ✅ Delete used token
        $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();

        $message = "<div class='alert alert-success'>Password updated successfully! <a href='index.php'>Login Now</a></div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="col-md-6 mx-auto bg-white p-4 rounded shadow">

        <h3 class="text-center mb-3">Reset Your Password</h3>

        <?php echo $message; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm" class="form-control" required>
            </div>

            <button type="submit" name="update" class="btn btn-primary w-100">
                Update Password
            </button>
        </form>

    </div>
</div>

</body>
</html>
