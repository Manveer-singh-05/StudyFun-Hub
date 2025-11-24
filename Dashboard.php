<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

function calculateLevelFromXP($xp)
{
    // Simple numeric level: every 200 XP = +1 level
    $xp = (int) $xp;
    $level = floor($xp / 200) + 1;
    return max(1, $level);
}

try {
    $conn = getDBConnection();

    // 1) Ensure badges table exists (safe, only creates if missing)
    $createBadgesTable = "
        CREATE TABLE IF NOT EXISTS badges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            badge_key VARCHAR(50) NOT NULL,
            badge_name VARCHAR(100) NOT NULL,
            description TEXT,
            date_earned TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_badge (user_id, badge_key)
        )
    ";
    $conn->query($createBadgesTable);

    // 2) Fetch user data (including XP)
    $stmt = $conn->prepare("SELECT id, full_name, email, profile_picture, xp FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Error preparing user statement: " . $conn->error);
    }

    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        throw new Exception("User not found");
    }

    $userId = (int) $user['id'];
    $total_xp = isset($user['xp']) ? (int) $user['xp'] : 0;
    $user_level = calculateLevelFromXP($total_xp);

    // Default profile image if not set
    if (empty($user['profile_picture']) || !file_exists($user['profile_picture'])) {
        $user['profile_picture'] = 'IMG/image.png';
    }

    // 3) Count completed challenges (from achievements)
    $total_challenges = 12; // English + Math + Science + Programming (Easy/Medium/Hard)

    $stmtChallenges = $conn->prepare("
        SELECT COUNT(*) AS completed 
        FROM achievements 
        WHERE user_id = ?
    ");
    if (!$stmtChallenges) {
        throw new Exception("Error preparing challenges statement: " . $conn->error);
    }
    $stmtChallenges->bind_param("i", $userId);
    $stmtChallenges->execute();
    $chRes = $stmtChallenges->get_result()->fetch_assoc();
    $completed_challenges = (int) ($chRes['completed'] ?? 0);

    $challenge_percent = $total_challenges > 0
        ? min(100, ($completed_challenges / $total_challenges) * 100)
        : 0;

    // 4) Fetch recent achievements
    $stmtAch = $conn->prepare("
        SELECT title, description, achieved_at 
        FROM achievements 
        WHERE user_id = ?
        ORDER BY achieved_at DESC
        LIMIT 5
    ");
    if (!$stmtAch) {
        throw new Exception("Error preparing achievements statement: " . $conn->error);
    }
    $stmtAch->bind_param("i", $userId);
    $stmtAch->execute();
    $achRes = $stmtAch->get_result();
    $achievements = [];
    while ($row = $achRes->fetch_assoc()) {
        $achievements[] = $row;
    }

    // 5) Badge rules based on XP + challenges
    $badgeDefinitions = [
        'bronze_learner' => [
            'name' => 'Bronze Learner',
            'description' => 'Earn at least 500 XP.',
            'unlocked' => $total_xp >= 500
        ],
        'silver_achiever' => [
            'name' => 'Silver Achiever',
            'description' => 'Complete at least 5 challenges.',
            'unlocked' => $completed_challenges >= 5
        ],
        'gold_master' => [
            'name' => 'Gold Master',
            'description' => 'Earn at least 2000 XP.',
            'unlocked' => $total_xp >= 2000
        ]
    ];

    // 6) Sync unlocked badges to database
    foreach ($badgeDefinitions as $key => $badge) {
        if (!$badge['unlocked'])
            continue;

        // Check if already exists
        $stmtCheck = $conn->prepare("SELECT id FROM badges WHERE user_id = ? AND badge_key = ? LIMIT 1");
        if ($stmtCheck) {
            $stmtCheck->bind_param("is", $userId, $key);
            $stmtCheck->execute();
            $checkRes = $stmtCheck->get_result();
            if ($checkRes->num_rows === 0) {
                // Insert new badge
                $stmtInsert = $conn->prepare("
                    INSERT INTO badges (user_id, badge_key, badge_name, description) 
                    VALUES (?, ?, ?, ?)
                ");
                if ($stmtInsert) {
                    $stmtInsert->bind_param("isss", $userId, $key, $badge['name'], $badge['description']);
                    $stmtInsert->execute();
                    $stmtInsert->close();
                }
            }
            $stmtCheck->close();
        }
    }

    // 7) Fetch all badges for display
    $stmtBadges = $conn->prepare("
        SELECT badge_key, badge_name, description, date_earned 
        FROM badges 
        WHERE user_id = ?
        ORDER BY date_earned
    ");
    if (!$stmtBadges) {
        throw new Exception("Error preparing badges statement: " . $conn->error);
    }
    $stmtBadges->bind_param("i", $userId);
    $stmtBadges->execute();
    $badgeRes = $stmtBadges->get_result();
    $badges = [];
    while ($row = $badgeRes->fetch_assoc()) {
        $badges[] = $row;
    }

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
} finally {
    if (isset($stmt))
        $stmt->close();
    if (isset($stmtChallenges))
        $stmtChallenges->close();
    if (isset($stmtAch))
        $stmtAch->close();
    if (isset($stmtBadges))
        $stmtBadges->close();
    if (isset($conn))
        $conn->close();
}

// Badge icons for display
$badgeIcons = [
    'bronze_learner' => '🥉',
    'silver_achiever' => '🥈',
    'gold_master' => '🥇'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NPS eLearning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="Dashboard.css" rel="stylesheet">
    <style>
        .level-badge {
            background-color: #ffd700;
            color: #000;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 10px;
            display: inline-block;
        }

        .achievement-item {
            border-left: 4px solid #2940D3;
            padding-left: 15px;
            margin-bottom: 20px;
        }

        .progress {
            height: 20px;
            border-radius: 10px;
        }

        .progress-bar {
            background-color: #2940D3;
        }

        .card {
            border: none;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
        }

        .card-header {
            background-color: transparent;
            border-bottom: 2px solid #f0f0f0;
            padding: 20px;
        }

        .profile-card {
            text-align: center;
            padding: 20px;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 20px;
            border: 4px solid #2940D3;
            background-color: #f8f9fa;
        }

        .btn-action {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 1.05em;
            margin-top: 15px;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .badge-pill {
            border-radius: 50rem;
        }
    </style>
</head>

<body>
    <div id="navbar-placeholder"></div>

    <div class="container mt-5 pt-5">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="mb-3">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
            </div>
        </div>

        <div class="row">
            <!-- Profile & XP Section -->
            <div class="col-md-4 mb-4">
                <div class="card profile-card">
                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" class="profile-image mx-auto"
                        alt="Profile Picture" onerror="this.onerror=null; this.src='IMG/default-profile.jpg'">
                    <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>

                    <div class="mb-2">
                        <span class="badge bg-primary badge-pill me-1">
                            ⭐ XP: <?php echo $total_xp; ?>
                        </span>
                        <div class="level-badge">
                            Level <?php echo $user_level; ?>
                        </div>
                    </div>

                    <p class="text-muted mt-2 mb-1">
                        Challenges completed:
                        <strong><?php echo $completed_challenges; ?>/<?php echo $total_challenges; ?></strong>
                    </p>

                    <a href="profile-settings.php" class="btn btn-primary btn-action">
                        <i class="bi bi-person-circle me-2"></i>Profile
                    </a>
                </div>
            </div>

            <!-- Progress, Achievements, Badges -->
            <div class="col-md-8">
                <!-- Challenge Progress -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Challenge Progress</h5>
                        <span class="text-muted">
                            <?php echo $completed_challenges; ?>/<?php echo $total_challenges; ?> completed
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="mb-1">Overall Progress</p>
                        <div class="progress mb-2">
                            <div class="progress-bar" role="progressbar"
                                style="width: <?php echo $challenge_percent; ?>%"
                                aria-valuenow="<?php echo $challenge_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php echo round($challenge_percent); ?>% of all challenges completed
                        </small>
                    </div>
                </div>

                <!-- Achievements -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Achievements</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($achievements)): ?>
                            <p class="text-muted">Complete challenges to earn achievements!</p>
                        <?php else: ?>
                            <?php foreach ($achievements as $achievement): ?>
                                <div class="achievement-item">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($achievement['title']); ?></h6>
                                    <p class="text-muted mb-1"><?php echo htmlspecialchars($achievement['description']); ?></p>
                                    <small class="text-muted">
                                        Earned on: <?php echo date('F j, Y', strtotime($achievement['achieved_at'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Badges -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Badges</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($badges)): ?>
                            <p class="text-muted">No badges yet. Keep completing challenges and earning XP!</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($badges as $badge):
                                    $key = $badge['badge_key'];
                                    $icon = isset($badgeIcons[$key]) ? $badgeIcons[$key] : '🏅';
                                    ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="p-3 border rounded h-100">
                                            <div class="fs-3"><?php echo $icon; ?></div>
                                            <h6 class="mt-2 mb-1">
                                                <?php echo htmlspecialchars($badge['badge_name']); ?>
                                            </h6>
                                            <p class="small text-muted mb-1">
                                                <?php echo htmlspecialchars($badge['description']); ?>
                                            </p>
                                            <small class="text-muted">
                                                Earned: <?php echo date('M j, Y', strtotime($badge['date_earned'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- Challenge Shortcuts -->
        <div class="row mt-4">
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-book display-4 text-primary mb-3"></i>
                        <h5 class="card-title">English Challenge</h5>
                        <p class="card-text">Test your English language skills</p>
                        <a href="english.php" class="btn btn-outline-primary w-100">Start Challenge</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-calculator display-4 text-primary mb-3"></i>
                        <h5 class="card-title">Mathematics Challenge</h5>
                        <p class="card-text">Solve mathematical problems</p>
                        <a href="math.php" class="btn btn-outline-primary w-100">Start Challenge</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-bezier display-4 text-primary mb-3"></i>
                        <h5 class="card-title">Science Challenge</h5>
                        <p class="card-text">Explore scientific concepts</p>
                        <a href="science.php" class="btn btn-outline-primary w-100">Start Challenge</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bi bi-code-slash display-4 text-primary mb-3"></i>
                        <h5 class="card-title">Programming Challenge</h5>
                        <p class="card-text">Practice coding fundamentals</p>
                        <a href="programming.php" class="btn btn-outline-primary w-100">Start Challenge</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Navbar & Footer Loading Script -->
    <script>
        $(function () {
            $.get("check_auth.php")
                .done(function (isLoggedIn) {
                    const navbarFile = isLoggedIn === "true" ? "navbar-loggedin.php" : "navbar-guest.php";
                    $("#navbar-placeholder").load(navbarFile);
                })
                .fail(function () {
                    $("#navbar-placeholder").load("navbar-guest.php");
                });

            $("#footer-placeholder").load("footer.html");
        });
    </script>

    <div id="footer-placeholder"></div>
</body>

</html>