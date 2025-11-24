<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
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

// When user submits email
if (isset($_POST['reset'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        $message = "<div class='alert alert-danger'>Please enter your email.</div>";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", time() + 1800); // 30 minutes

            // Save token into NEW TABLE
            $stmt = $conn->prepare("INSERT INTO password_reset_tokens (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $token, $expires);
            $stmt->execute();

            // Reset Link
            $resetLink = "http://localhost/manveer/preshan/reset_password.php?token=" . urlencode($token);

            // Email content
            $subject = "Password Reset - StudyFun Hub";
            $body = "Hello,\n\nClick this link to reset your password:\n$resetLink\n\nThis link will expire in 30 minutes.";
            
            $headers = "From: StudyFun Hub <no-reply@localhost>\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/plain; charset=UTF-8\r\n";

            mail($email, $subject, $body, $headers);

            $message = "<div class='alert alert-success'>Reset link sent to <b>$email</b>!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Email not found.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="col-md-6 mx-auto bg-white p-4 rounded shadow">

        <h3 class="text-center mb-3">Forgot Password</h3>

        <?php echo $message; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Enter your email</label>
                <input type="email" name="email" class="form-control" placeholder="example@gmail.com">
            </div>

            <button type="submit" name="reset" class="btn btn-primary w-100">
                Send Reset Link
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="index.php">Back to Login</a>
        </div>

    </div>
</div>

</body>
</html>

