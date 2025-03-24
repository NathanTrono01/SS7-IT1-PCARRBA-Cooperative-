<?php
session_start();
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['userId'] = $user['userId'];

        // Update login count
        $login_count = $user['login_count'] + 1;
        $stmt = $conn->prepare("UPDATE users SET login_count = ? WHERE userId = ?");
        $stmt->bind_param("ii", $login_count, $user['userId']);
        $stmt->execute();

        // Set welcome message
        if ($login_count == 1) {
            $_SESSION['welcome_message'] = "Welcome to PCARBA Sari Sari Store Inventory System, " . $user['username'] . "!";
        } else {
            $_SESSION['welcome_message'] = "Welcome back to PCARBA Sari Sari Store Inventory System, " . $user['username'] . "!";
        }

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/customcard.css">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        .main-title {
            text-align: center;
            margin-bottom: 20px;
            color: white;
        }

        .main-title h3 {
            font-size: 1.5rem;
            font-weight: bold;
        }

        @media (min-width: 769px) {
            .main-title h3 {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 576px) {
            .main-title h3 {
                font-size: .8rem;
            }
        }

        .alert {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>

<body class="fade-in">
    <div class="d-flex flex-column justify-content-center align-items-center min-vh-100 px-3">
        <div class="main-title mb-4">
            <h3>PCARBA Sari-Sari Store Inventory System</h3>
        </div>
        <div class="custom-card">
            <div class="card-header text-center">
                <h3>Login</h3>
                <hr>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="loginForm">
                    <div class="mb-3">
                        <input type="text" name="username" class="custom-input" placeholder="Username" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="password" class="custom-input" placeholder="Password" required>
                    </div>
                    <?php if (isset($error)) echo "<div class='alert' id='errorAlert'>$error</div>"; ?>
                    <button type="submit" class="custom-button" id="submitButton">Login</button>
                    <div class="loading-indicator" id="loadingIndicator">•••</div>
                </form>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            // replace the login button
            document.getElementById('submitButton').style.display = 'none';
            var loadingIndicator = document.getElementById('loadingIndicator');
            loadingIndicator.style.display = 'block';

            // animation
            var dots = 1;
            var maxDots = 3;
            var interval = setInterval(function() {
                loadingIndicator.textContent = '•'.repeat(dots);
                dots++;
                if (dots > maxDots) {
                    dots = 1;
                }
            }, 500);
        });

        // Hide the error alert after 5 seconds
        setTimeout(function() {
            var errorAlert = document.getElementById('errorAlert');
            if (errorAlert) {
                errorAlert.style.display = 'none';
            }
        }, 5000); // 5000 milliseconds = 5 seconds
    </script>
</body>

</html>