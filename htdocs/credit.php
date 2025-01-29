<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['accountLevel'] !== 'Admin') {
    header("Location: index.php");
    exit();
}
?>

<head>
    <title>Credit</title>
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
            z-index: 1030;
            /* Ensures navbar stays above other elements */
            border-bottom: 2px solid #ffffff;
            /* White bottom border */
        }

        /* Sidebar Styling */
        .sidebar {
            position: fixed;
            top: 56px;
            /* Height of the navbar */
            left: 0;
            height: calc(100vh - 56px);
            /* Full viewport height minus navbar height */
            width: 200px;
            background-color: #222;
            padding-top: 20px;
            z-index: 1020;
            /* Below navbar but above main content */
            border-right: 2px solid #ffffff;
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
            margin-left: 200px;
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

<body class="bg-dark text-white">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h1>Credit</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>.</p>
            <!-- Inventory Management Content Goes Here -->
            <a href="adminDashboard.php" class="btn btn-primary">Back to Dashboard</a>
            <a href="logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>


</body>

</html>