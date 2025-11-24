<?php
require_once "config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = getDBConnection();

// =========================
// 1) TOP 10 LEADERBOARD
// =========================
$leaderboard = [];
$sql = "SELECT id, full_name, xp FROM users ORDER BY xp DESC LIMIT 10";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $leaderboard[] = $row;
    }
}

// Best performer = rank 1
$bestPerformer = !empty($leaderboard) ? $leaderboard[0] : null;

// =========================
// 2) CURRENT USER
// =========================
$currentUserId = $_SESSION['user_id'] ?? null;
$currentUser = null;

if ($currentUserId) {
    $stmt = $conn->prepare("SELECT id, full_name, xp FROM users WHERE id = ?");
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $res = $stmt->get_result();
    $currentUser = $res->fetch_assoc();
    $stmt->close();
}

if (!$currentUser && $bestPerformer) {
    $currentUser = $bestPerformer;
}

$currentUserXp = $currentUser['xp'] ?? 0;
$currentUserName = $currentUser['full_name'] ?? 'Student';

// =========================
// 3) CATEGORY PERFORMANCE
// =========================
$categories = ['english', 'programming', 'science', 'math'];
$categoryXP = array_fill_keys($categories, 0);

if (!empty($currentUser['id'])) {
    $stmtCat = $conn->prepare("
        SELECT category, xp 
        FROM user_category_xp 
        WHERE user_id = ?
    ");
    $stmtCat->bind_param("i", $currentUser['id']);
    $stmtCat->execute();
    $resCat = $stmtCat->get_result();
    while ($row = $resCat->fetch_assoc()) {
        $cat = strtolower($row['category']);
        if (isset($categoryXP[$cat])) {
            $categoryXP[$cat] = (int) $row['xp'];
        }
    }
    $stmtCat->close();
}

$totalCategoryXP = array_sum($categoryXP);

// =========================
// 4) BADGES
// =========================
$userBadges = [];
if (!empty($currentUser['id'])) {
    $stmtBadge = $conn->prepare("
        SELECT badge_name, earned_at 
        FROM user_badges 
        WHERE user_id = ?
        ORDER BY earned_at DESC
        LIMIT 5
    ");
    $stmtBadge->bind_param("i", $currentUser['id']);
    $stmtBadge->execute();
    $resBadge = $stmtBadge->get_result();
    while ($row = $resBadge->fetch_assoc()) {
        $userBadges[] = $row;
    }
    $stmtBadge->close();
}

$badgeIcons = [
    'top performer' => '🥇',
    'fast learner' => '⚡',
    '10-day streak' => '🔥',
    'consistent' => '💪',
    'goal crusher' => '🎯'
];

// =========================
// 5) MOST IMPROVED STUDENTS
// ✅ FIXED TABLE NAME
// =========================
$improvedUsers = [];
$sqlImprovement = "
    SELECT 
        u.id,
        u.full_name,
        MIN(up.xp) AS start_xp,
        MAX(up.xp) AS end_xp,
        (MAX(up.xp) - MIN(up.xp)) AS diff
    FROM users u
    JOIN user_progress_org up ON u.id = up.user_id
    WHERE up.recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY u.id, u.full_name
    HAVING diff > 0
    ORDER BY diff DESC
    LIMIT 3
";

$resImprovement = $conn->query($sqlImprovement);
$improvedUsers = [];

if ($resImprovement && $resImprovement->num_rows > 0) {
    while ($row = $resImprovement->fetch_assoc()) {

        $start = (int) $row['start_xp'];
        $end = (int) $row['end_xp'];
        $diff = (int) $row['diff'];
        $percent = $start > 0 ? round(($diff / $start) * 100) : 0;

        $improvedUsers[] = [
            'full_name' => $row['full_name'],
            'start_xp' => $start,
            'end_xp' => $end,
            'diff' => $diff,
            'percent' => $percent
        ];
    }
}

// $resImprovement = $conn->query($sqlImprovement);
// if ($resImprovement && $resImprovement->num_rows > 0) {
//     while ($row = $resImprovement->fetch_assoc()) {
//         $start = (int)$row['start_xp'];
//         $end = (int)$row['end_xp'];
//         $diff = $end - $start;
//         $percent = $start > 0 ? round(($diff / $start) * 100) : 0;

//         $improvedUsers[] = [
//             'full_name' => $row['full_name'],
//             'start_xp'  => $start,
//             'end_xp'    => $end,
//             'diff'      => $diff,
//             'percent'   => $percent
//         ];
//     }
// }

// =========================
// 6) PROGRESS TIMELINE
// ✅ FIXED TABLE NAME
// =========================
$progressLabels = [];
$progressData = [];

if (!empty($currentUser['id'])) {
    $stmtProg = $conn->prepare("
        SELECT DATE(recorded_at) AS day, MAX(xp) AS xp
        FROM user_progress_org
        WHERE user_id = ?
        GROUP BY DATE(recorded_at)
        ORDER BY day ASC
        LIMIT 10
    ");
    $stmtProg->bind_param("i", $currentUser['id']);
    $stmtProg->execute();
    $resProg = $stmtProg->get_result();
    while ($row = $resProg->fetch_assoc()) {
        $progressLabels[] = $row['day'];
        $progressData[] = (int) $row['xp'];
    }
    $stmtProg->close();
}

// =========================
// 7) NEXT LEVEL GOALS
// =========================
$tiers = [
    ['name' => 'Bronze', 'xp' => 1000, 'color' => '#cd7f32'],
    ['name' => 'Silver', 'xp' => 2000, 'color' => '#c0c0c0'],
    ['name' => 'Gold', 'xp' => 3000, 'color' => '#ffd700'],
    ['name' => 'Platinum', 'xp' => 4000, 'color' => '#e5e4e2']
];

$nextTier = null;
foreach ($tiers as $tier) {
    if ($currentUserXp < $tier['xp']) {
        $nextTier = $tier;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Leaderboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & Chart.js -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .leaderboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
            padding-top: 80px;
        }

        .section-title {
            color: #2c3e50;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 2rem;
            position: relative;
            text-align: center;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            border-radius: 2px;
        }

        .best-performer {
            background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .leaderboard-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .table> :not(caption)>*>* {
            padding: 1rem 1.5rem;
        }

        .table thead th {
            background: linear-gradient(90deg, #2c3e50, #3498db);
            color: white;
            border: none;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.07);
            transition: all 0.2s ease;
        }

        .rank-number {
            font-weight: bold;
            font-size: 1.1rem;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: auto;
        }

        .rank-1 {
            background-color: #ffd700;
            color: #000;
        }

        .rank-2 {
            background-color: #c0c0c0;
            color: #000;
        }

        .rank-3 {
            background-color: #cd7f32;
            color: #fff;
        }

        .xp-points {
            font-weight: bold;
            color: #2ecc71;
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .progress-timeline,
        .category-performance {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .achievement-badge {
            width: 60px;
            height: 60px;
            margin: 0 10px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .badge-title {
            position: absolute;
            bottom: -24px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .improvement-card {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            color: #2c3e50;
            transition: transform 0.25s ease;
        }

        .improvement-card:hover {
            transform: translateY(-5px);
        }

        .category-icon {
            font-size: 24px;
            margin-right: 10px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.25);
        }

        .btn-dashboard {
            margin: 3rem auto 1rem;
            display: block;
            max-width: 260px;
            font-size: 1.05rem;
            border-radius: 999px;
        }
    </style>
</head>

<body>
    <div id="navbar-placeholder"></div>

    <div class="leaderboard-container">

        <h2 class="section-title">Overall Rank</h2>

        <!-- 🏆 Best Performer -->
        <?php if ($bestPerformer): ?>
            <div class="best-performer">
                <h1>🏆</h1>
                <h4>Best Performer</h4>
                <h2 class="mt-2 mb-1">
                    <?php echo htmlspecialchars($bestPerformer['full_name']); ?>
                </h2>
                <p class="mb-0">
                    Total Achievement: <strong><?php echo (int) $bestPerformer['xp']; ?> XP</strong>
                </p>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                No users found yet. Be the first to earn XP!
            </div>
        <?php endif; ?>

        <!-- 🧾 Top 10 Leaderboard Table -->
        <div class="leaderboard-table mt-4">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="text-center">Rank</th>
                        <th>Name</th>
                        <th class="text-end">XP Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($leaderboard)): ?>
                        <?php $rank = 1;
                        foreach ($leaderboard as $user): ?>
                            <?php $rankClass = $rank <= 3 ? "rank-$rank" : ""; ?>
                            <tr>
                                <td class="text-center">
                                    <div class="rank-number <?php echo $rankClass; ?>">
                                        <?php echo $rank; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td class="text-end xp-points"><?php echo (int) $user['xp']; ?> XP</td>
                            </tr>
                            <?php $rank++; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center py-4">
                                No leaderboard data yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 📊 Performance Graph (Top 10) -->
        <div class="chart-container">
            <h4 class="text-center mb-4">Performance Graph (Top 10)</h4>
            <canvas id="leaderboardChart" style="height: 350px;"></canvas>
        </div>

        <!-- 📈 Progress Over Time -->
        <div class="progress-timeline">
            <h4 class="text-center mb-4">Progress Over Time (<?php echo htmlspecialchars($currentUserName); ?>)</h4>
            <?php if (!empty($progressLabels)): ?>
                <canvas id="progressChart" style="height: 320px;"></canvas>
            <?php else: ?>
                <p class="text-center text-muted mb-0">
                    No progress data yet. Complete some quizzes to see your growth!
                </p>
            <?php endif; ?>
        </div>

        <!-- 🕸 Category Performance -->
        <div class="category-performance">
            <h4 class="text-center mb-4">Performance By Category (<?php echo htmlspecialchars($currentUserName); ?>)
            </h4>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <canvas id="categoryChart" style="height: 320px;"></canvas>
                </div>
                <div class="col-md-6 mb-3">
                    <h5 class="mb-3">Category Breakdown</h5>

                    <?php
                    $catIcons = [
                        'english' => '📝 English Mastery',
                        'programming' => '💻 Programming Skills',
                        'science' => '🔬 Science Explorer',
                        'math' => '➗ Math Genius'
                    ];

                    foreach ($categories as $cat):
                        $xp = $categoryXP[$cat];
                        $percent = $totalCategoryXP > 0 ? round(($xp / $totalCategoryXP) * 100) : 0;
                        $title = $catIcons[$cat];
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><?php echo $title; ?></span>
                                <span><?php echo $xp; ?> XP</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $percent; ?>%;">
                                    <?php echo $percent; ?>%
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>

        <!-- 🏅 Achievement Badges (Dynamic) -->
        <div class="chart-container mt-5">
            <h4 class="text-center mb-4">Achievement Badges (<?php echo htmlspecialchars($currentUserName); ?>)</h4>
            <?php if (!empty($userBadges)): ?>
                <div class="d-flex justify-content-center flex-wrap">
                    <?php foreach ($userBadges as $badge):
                        $nameLower = strtolower($badge['badge_name']);
                        $icon = '🏅';
                        foreach ($badgeIcons as $key => $val) {
                            if (str_contains($nameLower, $key)) {
                                $icon = $val;
                                break;
                            }
                        }
                        ?>
                        <div class="text-center mx-2 my-3">
                            <div class="achievement-badge"
                                style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                                <?php echo $icon; ?>
                                <span class="badge-title">
                                    <?php echo htmlspecialchars($badge['badge_name']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-muted mb-0">
                    No badges earned yet. Keep learning to unlock badges!
                </p>
            <?php endif; ?>
        </div>

        <!-- 🚀 Most Improved Students -->
        <div class="mt-5">
            <h4 class="section-title">Most Improved Students (Last 30 days)</h4>
            <div class="row">
                <?php if (!empty($improvedUsers)): ?>
                    <?php
                    $icons = ['🚀', '⭐', '🏃'];
                    $i = 0;
                    foreach ($improvedUsers as $user):
                        $icon = $icons[$i] ?? '📈';
                        $i++;
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="improvement-card text-center">
                                <h1><?php echo $icon; ?></h1>
                                <h5 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                                <p class="mb-1">Start: <?php echo $user['start_xp']; ?> XP</p>
                                <p class="mb-1">Current: <?php echo $user['end_xp']; ?> XP</p>
                                <h3 class="mt-2">+<?php echo $user['diff']; ?> XP (<?php echo $user['percent']; ?>% 📈)</h3>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-muted">
                        No improvement data yet. Once students start gaining XP over time, you'll see them here.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 🎯 Next Level Goals -->
        <div class="chart-container mt-5">
            <h4 class="text-center mb-4">Next Level Goals (<?php echo htmlspecialchars($currentUserName); ?>)</h4>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h5 class="mb-3">Rank Requirements</h5>
                    <?php foreach ($tiers as $tier):
                        $required = $tier['xp'];
                        $name = $tier['name'];
                        $color = $tier['color'];
                        $progress = min(100, round(($currentUserXp / $required) * 100));
                        $completed = $currentUserXp >= $required;
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span><?php echo $name; ?></span>
                            <span><?php echo $required; ?> XP</span>
                        </div>
                        <div class="progress mb-3">
                            <div class="progress-bar" role="progressbar"
                                style="width: <?php echo $completed ? 100 : $progress; ?>%; background-color: <?php echo $color; ?>;"
                                aria-valuenow="<?php echo $completed ? 100 : $progress; ?>" aria-valuemin="0"
                                aria-valuemax="100">
                                <?php echo $completed ? 'Completed' : ($progress . '%'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="col-md-6 mb-3 d-flex flex-column justify-content-center">
                    <div class="text-center mb-3">
                        <?php if ($nextTier): ?>
                            <h5>Next Milestone</h5>
                            <div class="achievement-badge mx-auto my-3"
                                style="background: linear-gradient(135deg, #f9f295 0%, #ffd700 100%); font-size: 30px;">
                                🌟
                            </div>
                            <h4><?php echo $nextTier['name']; ?> Rank</h4>
                            <?php
                            $needed = $nextTier['xp'] - $currentUserXp;
                            if ($needed < 0)
                                $needed = 0;
                            ?>
                            <p class="mt-2">
                                You need <strong><?php echo $needed; ?> XP</strong> to reach this rank.
                            </p>
                            <div class="progress" style="height: 20px;">
                                <?php
                                $progressToNext = min(100, round(($currentUserXp / $nextTier['xp']) * 100));
                                ?>
                                <div class="progress-bar bg-warning" role="progressbar"
                                    style="width: <?php echo $progressToNext; ?>%;"
                                    aria-valuenow="<?php echo $progressToNext; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo $progressToNext; ?>%
                                </div>
                            </div>
                        <?php else: ?>
                            <h5>Max Rank Achieved 🎉</h5>
                            <p>You’ve already passed the highest goal defined. Awesome job!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🔙 Back to Dashboard -->
        <a href="Dashboard.php" class="btn btn-primary btn-dashboard">
            ← Back to Dashboard
        </a>

    </div>

    <!-- JS: jQuery, Bootstrap, Navbar/Footer loader, Charts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Navbar & Footer (if you use them everywhere)
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

    <script>
        // ====== Top 10 Bar Chart ======
        const names = <?php echo json_encode(array_column($leaderboard, 'full_name')); ?>;
        const xpData = <?php echo json_encode(array_map('intval', array_column($leaderboard, 'xp'))); ?>;

        if (names.length > 0) {
            const ctx = document.getElementById('leaderboardChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: names,
                    datasets: [{
                        label: 'XP Points',
                        data: xpData,
                        backgroundColor: 'rgba(52, 152, 219, 0.6)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 2,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        // ====== Progress Timeline Chart ======
        const progressLabels = <?php echo json_encode($progressLabels); ?>;
        const progressData = <?php echo json_encode($progressData); ?>;

        if (progressLabels.length > 0) {
            const pctx = document.getElementById('progressChart').getContext('2d');
            new Chart(pctx, {
                type: 'line',
                data: {
                    labels: progressLabels,
                    datasets: [{
                        label: 'XP Over Time',
                        data: progressData,
                        borderColor: 'rgba(46,204,113,1)',
                        backgroundColor: 'rgba(46,204,113,0.2)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        // ====== Category Radar Chart ======
        // const categoryLabels = <?php echo json_encode(array_map('ucfirst', array_keys($categoryXP))); ?>;
        // const categoryValues = <?php echo json_encode(array_values($categoryXP)); ?>;

        // if (categoryLabels.length > 0) {
        //     const cctx = document.getElementById('categoryChart').getContext('2d');
        //     new Chart(cctx, {
        //         type: 'radar',
        //         data: {
        //             labels: categoryLabels,
        //             datasets: [{
        //                 label: 'Category XP',
        //                 data: categoryValues,
        //                 backgroundColor: 'rgba(52, 152, 219, 0.2)',
        //                 borderColor: 'rgba(52, 152, 219, 1)',
        //                 borderWidth: 2,
        //                 pointBackgroundColor: 'rgba(52,152,219,1)'
        //             }]
        //         },
        //         options: {
        //             responsive: true,
        //             scales: {
        //                 r: {
        //                     suggestedMin: 0,
        //                     suggestedMax: Math.max(...categoryValues, 100)
        //                 }
        //             }
        //         }
        //     });
        // }
        const categoryLabels = ["English", "Programming", "Science", "Math"];
        const categoryValues = <?php echo json_encode(array_values($categoryXP)); ?>;

        const cctx = document.getElementById('categoryChart').getContext('2d');
        new Chart(cctx, {
            type: 'radar',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Category XP',
                    data: categoryValues,
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(52,152,219,1)'
                }]
            }
        });

    </script>
</body>

</html>