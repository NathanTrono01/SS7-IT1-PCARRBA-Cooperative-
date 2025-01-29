<?php
include 'db.php';
session_start();

if (!isset($_SESSION['username']) || $_SESSION['accountLevel'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $accountLevel = $_POST['accountLevel'];

    // Check if username is unique
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password, accountLevel) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $password, $accountLevel);
            if ($stmt->execute()) {
                $success = "Registration successful!";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    } else {
        $error = "Database error: Unable to prepare statement.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        /* Fixed Navbar Styling */
        .navbar-fixed-top {
            position: fixed;
            top: 0;
            width: 100%;
            height: 70px;
            /* Adjusted height */
            z-index: 1030;
            /* Ensures navbar stays above other elements */
            border-bottom: 2px solid rgb(114, 114, 114);
            /* White bottom border */
            padding-left: 20px;
            padding-right: 20px;
            background-color: #232527;
            display: flex;
            align-items: center;
            /* Vertically center content */
        }

        .dropdown {
            margin-left: 20px;
            padding: 10px;
        }

        .dropdown-menu {
            background-color: #444;
        }

        .dropdown-item {
            color: hsla(0, 0%, 100%, 0.7);
        }

        .dropdown-item:hover {
            background: transparent;
            color: #fff;
        }

        /* Sidebar Styling */
        .sidebar {
            position: fixed;
            top: 56px;
            /* Height of the navbar */
            left: 0;
            height: 100vh;
            /* Full viewport height minus navbar height */
            width: 250px;
            background-color: #232527;
            padding-top: 20px;
            z-index: 1020;
            /* Below navbar but above main content */
            border-right: 2px solid rgb(114, 114, 114);
            /* White right border */
            overflow-y: auto;
            /* Enable vertical scroll if content overflows */
        }

        .sidebar a {
            color: #ddd;
            padding: 15px 20px;
            text-decoration: none;
            display: block;
            transition: background 0.3s, color 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #575757;
            color: #fff;
        }

        /* Main Content Styling */
        .main-content {
            margin-top: 56px;
            /* Height of the navbar */
            margin-left: 250px;
            /* Width of the sidebar */
            padding: 20px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 2px solid #ffffff;
            }

            .main-content {
                margin-left: 0;
            }
        }

        /* Custom Card Styling */
        .custom-card {
            width: 100%;
            max-width: 500px;
            /* Maintain a reasonable max-width */
            background-color: #333;
            /* Grey / black */
            color: white;
            padding: 20px;
            box-sizing: border-box;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-top: 20px;
        }

        .custom-input {
            background-color: rgba(255, 255, 255, 0.1);
            /* Semi-transparent input */
            color: white;
            border: 1px solid hsla(0, 0%, 100%, 0.2);
            padding: 10px;
            font-size: 1rem;
            border-radius: 4px;
            width: 100%;
        }

        .custom-input::placeholder {
            color: #bdbebe;
        }

        .custom-input:focus {
            border-color: white;
            color: white;
            outline: none;
            background-color: rgba(255, 255, 255, 0.15);
        }

        .custom-button {
            background: transparent;
            border: 1px solid #bdbebe;
            color: hsla(0, 0%, 100%, 0.7);
            transition: border-color 0.3s, color 0.3s;
            padding: 10px;
            font-size: 1rem;
            border-radius: 4px;
            width: 100%;
            margin-top: 10px;
        }

        .custom-button:hover {
            border-color: white;
            background: transparent;
            color: white;
        }

        .alert {
            border: 1px solid red;
            background-color: #f8d7da;
            color: red;
            font-size: 0.8rem;
            /* Small font size */
            padding: 0.4rem 0.8rem;
            /* Reduced padding */
            border-radius: 4px;
            margin-top: 10px;
        }

        .alert-success {
            border: 1px solid green;
            background-color: #d4edda;
            color: green;
        }

        .loading-indicator {
            display: none;
            color: white;
            font-size: 1.5rem;
            /* Size increased */
            text-align: center;
            margin-top: 15px;
        }

        /* Navbar Title Styling */
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            margin-right: 20px;

        }

        /* Scrollbar Styling for Sidebar */
        .sidebar::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #2c2c2c;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: #555;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark  navbar-fixed-top">
        <a class="navbar-brand" href="adminDashboard.php">Admin Panel</a>
        <div class="ms-auto">
            <div class="dropdown">
                <a class="nav-link dropdown-toggle" href="" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo $_SESSION['username']; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <a href="adminDashboard.php" class="active">Admin Dashboard</a>
        <a href="inventory.php">Inventory</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h1>Admin Panel</h1>
            <p>Welcome, <?php echo $_SESSION['username']; ?>. You have administrative privileges.</p>
            <a href="adminDashboard.php" class="btn btn-primary">Back to Dashboard</a>
            <a href="logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="card mx-auto custom-card">
            <div class="card-header text-center">
                <h2>Register a User</h2>
            </div>
            <div class="card-body">
                <?php if (isset($error)) echo "<div class='alert'>$error</div>"; ?>
                <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" class="custom-input" placeholder="Username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" class="custom-input" placeholder="Password" required>
                    </div>
                    <div class="mb-3">
                        <label for="accountLevel" class="form-label">Account Level</label>
                        <select name="accountLevel" class="form-select custom-input" required>
                            <option value="nonAdmin">Non-Admin</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
            </div>
        </div>

        <!-- Bootstrap JS Bundle -->
        <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>