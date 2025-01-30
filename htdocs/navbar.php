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
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            width: 100%;
            height: 60px;
            z-index: 1030;
            border-bottom: 0.25px solid rgba(187, 188, 190, 0.25);
            padding-left: 20px;
            padding-right: 20px;
            background-color: rgb(17, 18, 22);
        }

        .navbar1 {
            display: grid;
            grid-template-columns: 1fr;
        }

        .navbar2 {
            display: grid;
            grid-template-columns: auto;
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
            background-color: rgb(17, 18, 22);
            padding-top: 20px;
            z-index: 1020;
            border-right: 0.25px solid rgba(187, 188, 190, 0.25);
            overflow-y: auto;
        }

        .sidebar-record-sale a[href*="sell.php"] {
            font-size: 1.2rem;
            padding: 20px 25px;
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

        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.06);
            color: #fff;
        }
        .sidebar a.active {
            background-color: rgba(187, 194, 209, 0.17);
            font-size: 1.05rem;
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

    <body>
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark navbar-fixed-top">
            <div class="navbar1">
                <a class="navbar-brand" href="dashboard.php">PCARBA Sari-Sari Store</a>
            </div>
            <div class="navbar2">
                <div class="ms-auto">
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle" href="" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($_SESSION['accountLevel'] === 'Admin'): ?>
                                <li><a href="adminPanel.php" class="dropdown-item">Settings</a></li>
                            <?php else: ?>
                                <li><a href="generalSettings.php" class="dropdown-item">Settings</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Sidebar -->
        <div class="sidebar">
            <a href="sell.php" class="sidebar-link sidebar-record-sale <?php echo $current_page == 'sell.php' ? 'active' : ''; ?>">Record Sale</a>
            <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="inventory.php" class="<?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>">Inventory</a>
            <a href="revenue.php" class="<?php echo $current_page == 'revenue.php' ? 'active' : ''; ?>">Revenue</a>
            <a href="credit.php" class="<?php echo $current_page == 'credit.php' ? 'active' : ''; ?>">Credit</a>
        </div>
    </body>

</html>