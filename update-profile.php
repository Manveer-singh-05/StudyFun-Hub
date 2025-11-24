<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("
    SELECT 
        full_name, email, phone, bio, profile_picture, last_login
    FROM users 
    WHERE id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}

$message = "";

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $bio = trim($_POST['bio']);

    // Use old picture unless replaced
    $profile_picture = $user['profile_picture'];

    // Picture upload
    if (!empty($_FILES['profile_picture']['name'])) {
        
        if ($_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            
            $tmp = $_FILES['profile_picture']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $allowed = ["jpg", "jpeg", "png", "gif", "webp"];

            if (in_array($ext, $allowed)) {

                $newName = "profile_" . $user_id . "_" . time() . "." . $ext;
                $dest = "IMG/" . $newName;

                if (move_uploaded_file($tmp, $dest)) {
                    $profile_picture = $dest;
                }

            } else {
                $message = "Invalid image file.";
            }
        }
    }

    if ($message === "") {

        $stmt = $conn->prepare("
            UPDATE users 
            SET full_name = ?, email = ?, phone = ?, bio = ?, profile_picture = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssssi", $name, $email, $phone, $bio, $profile_picture, $user_id);

        if ($stmt->execute()) {
            // $message = "Profile updated successfully!";
            // $user = array_merge($user, [
            //     "full_name" => $name,
            //     "email" => $email,
            //     "phone" => $phone,
            //     "bio" => $bio,
            //     "profile_picture" => $profile_picture
            // ]);
              $_SESSION['message'] = "Profile updated successfully!";
    header("Location: profile-settings.php");
    exit;
        } else {
            $message = "Update failed.";
        }
        $stmt->close();
    }
}

$conn->close();

// Resolve profile image path
$profilePic = (!empty($user['profile_picture']) && file_exists(__DIR__ . "/" . $user['profile_picture']))
              ? $user['profile_picture']
              : "IMG/image.png";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f8f9fa;
            padding-top: 80px;
        }

        .profile-card {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .profile-picture-container {
            position: relative;
            width: 130px;
            height: 130px;
            margin: auto;
        }

        .profile-picture {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .upload-btn {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 38px;
            height: 38px;
            background: #0d6efd;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(0,0,0,0.15);
        }
    </style>
</head>

<body>
    <?php include 'navbar-loggedin.php'; ?>

    <div class="container">
        <div class="col-md-8 mx-auto">
            <div class="profile-card">

                <div class="text-center mb-4">
                    <div class="profile-picture-container">
                        <img src="<?= htmlspecialchars($profilePic) ?>" class="profile-picture">
                        <div class="upload-btn" onclick="document.getElementById('upload').click()">
                            <i class="bi bi-camera-fill"></i>
                        </div>
                    </div>
                    <h3 class="mt-3"><?= htmlspecialchars($user['full_name']) ?></h3>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-info"><?= $message ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <input type="file" id="upload" name="profile_picture" class="d-none">

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control"
                            value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                            value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control"
                            value="<?= htmlspecialchars($user['phone']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bio</label>
                        <textarea name="bio" class="form-control" rows="4"><?= htmlspecialchars($user['bio']) ?></textarea>
                    </div>

                    <button class="btn btn-primary w-100 mb-2">Update Profile</button>
                    <a href="profile-settings.php" class="btn btn-outline-secondary w-100">Cancel</a>

                </form>
            </div>
        </div>
    </div>

</body>
</html>
