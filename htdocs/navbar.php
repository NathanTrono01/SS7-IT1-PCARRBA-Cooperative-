<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>navbar</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        /* Fixed Navbar Styling */
        .navbar-fixed-top {
            position: fixed;
            top: 0;
            width: 100%;
            height: 70px;
            z-index: 1030;
            border-bottom: 2px solid rgb(114, 114, 114);
            padding-left: 20px;
            padding-right: 20px;
            background-color: #232527;
            display: flex;
            align-items: center;
        }

        .dropdown {
            margin-left: 20px;
            padding: 10px;
        }

        .dropdown-menu {
            background-color: #444;
            border: transparent;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
            left: 0;
            height: 100vh;
            width: 250px;
            background-color: #232527;
            padding-top: 20px;
            z-index: 1020;
            border-right: 2px solid rgb(114, 114, 114);
            overflow-y: auto;
        }

        .sidebar a {
            border-radius: 10px;
            color: #ddd;
            margin-left: 10px;
            margin-right: 10px;
            padding: 15px 20px;
            text-decoration: none;
            display: block;
            transition: background 0.3s, color 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: rgba(0,0,0,0.5);
            color: #fff;
        }

        /* Main Content Styling */
        .main-content {
            margin-top: 56px;
            margin-left: 250px;
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
        <a class="navbar-brand" href="dashboard.php">PCARBA Sari-Sari Store</a>
        <div class="ms-auto">
            <div class="dropdown">
                <a class="nav-link dropdown-toggle" href="" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo $_SESSION['username']; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php if ($_SESSION['accountLevel'] === 'Admin'): ?>
                        <li><a href="adminPanel.php" class="dropdown-item">Settings</a></li>
                    <?php else: ?>
                        <li><a href="generalSettings" class="dropdown-item">Settings</a></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">

        <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
        <a href="inventory.php" class="<?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>">Inventory</a>
        <a href="revenue.php" class="<?php echo $current_page == 'revenue.php' ? 'active' : ''; ?>">Revenue</a>
        <a href="credit.php" class="<?php echo $current_page == 'credit.php' ? 'active' : ''; ?>">Credit</a>
    </div>
</body>

</html>