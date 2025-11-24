<!DOCTYPE html>
<html>
<head>
<title>Contact Us</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5 col-md-6 bg-white p-4 rounded shadow">

<h3 class="mb-3">Contact Us</h3>

<p>Email: support@studyfunhub.com</p>
<p>Phone: +91 98765-43210</p>

<p>Or send us a message:</p>

<form method="POST" action="send_contact.php">
    <input type="text" name="name" class="form-control mb-2" placeholder="Your Name" required>
    <input type="email" name="email" class="form-control mb-2" placeholder="Your Email" required>
    <textarea name="message" class="form-control mb-2" rows="4" placeholder="Message" required></textarea>

    <button class="btn btn-primary w-100">Send</button>
</form>

</div>
</div>

</body>
</html>
    