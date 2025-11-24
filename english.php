

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>English Challenges</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .challenge-card:hover {
            transform: scale(1.05);
            transition: 0.3s;
        }

        .pagination {
            justify-content: center;
            margin-top: 30px;
        }
    </style>
</head>

<body class="pt-5">
    <!-- Navbar -->

    <div id="navbar-placeholder"></div>

    <!-- Navbar -->
    <div class="container mt-5">
        <h1 class="text-center">English Challenges</h1>
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card challenge-card p-3 shadow-lg">
                    <h5>Vocabulary Quiz</h5>
                    <p>Difficulty: Easy</p>
                    <span class="badge bg-warning text-dark">Earn 100 XP</span>
                    <a href="english_easy_quiz.html" class="btn btn-primary m-1 w-100">Start Challenge</a>

                </div>
            </div>
            <div class="col-md-4">
                <div class="card challenge-card p-3 shadow-lg">
                    <h5>Grammar Test</h5>
                    <p>Difficulty: Medium</p>
                    <span class="badge bg-warning text-dark">Earn 200 XP</span>
                    <a href="english_medium_quiz.html" class="btn btn-primary m-1 w-100">Start Challenge</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card challenge-card p-3 shadow-lg">
                    <h5>Essay Writing</h5>
                    <p>Difficulty: Hard</p>
                    <span class="badge bg-warning text-dark">Earn 300 XP</span>
                    <a href="english_hard_quiz.html" class="btn btn-primary m-1 w-100">Start Challenge</a>
                </div>
            </div>
        </div>

        <h2 class="mt-5 text-center">Leaderboard</h2>
        <table class="table table-striped mt-3">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Name</th>
                    <th>XP</th>
                </tr>
            </thead>
            <!-- <tbody>
                <tr>
                    <td>1</td>
                    <td>Rahul Mehta</td>
                    <td>1600</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Priya Singh</td>
                    <td>1300</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Aditya Roy</td>
                    <td>1100</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>Neha Sharma</td>
                    <td>900</td>
                </tr>
            </tbody> -->
            <?php
            require_once "config.php";
            $conn = getDBConnection();

            $sql = "SELECT full_name, xp FROM users ORDER BY xp DESC LIMIT 10";
            $result = $conn->query($sql);

            $rank = 1;

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                <td>$rank</td>
                <td>{$row['full_name']}</td>
                <td>{$row['xp']}</td>
              </tr>";
                    $rank++;
                }
            } else {
                echo "<tr><td colspan='3' class='text-center'>No users yet</td></tr>";
            }
            ?>

        </table>
    </div>

    <!-- Footer -->
    <!-- Footer Placeholder -->
    <div id="footer-placeholder"></div>

    <script src="index1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(function () {
            // Load appropriate navbar based on PHP session
            $.get("check_auth.php", function (isLoggedIn) {
                const navbarFile = isLoggedIn === "true" ? "navbar-loggedin.php" : "navbar-guest.php";
                $("#navbar-placeholder").load(navbarFile);
            });

            // Load footer
            $("#footer-placeholder").load("footer.html");
        });
    </script>
</body>

</html>