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
        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            /* Prevent horizontal scrolling */
        }

        body {
            display: grid;
            grid-template-rows: 60px 1fr;
            /* Top bar and main content */
            grid-template-columns: 260px 1fr;
            /* Sidebar and main content */
            height: 100vh;
            overflow: hidden;
            margin: 0;
        }

        .navbar-fixed-top {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 60px;
            padding: 0 20px;
            background-color: rgb(17, 18, 22);
            border-bottom: 0.25px solid rgba(187, 188, 190, 0.25);
            z-index: 1000;
        }

        .navbar1 {
            display: flex;
            align-items: center;
        }

        .navbar2 {
            display: flex;
            align-items: center;
        }

        .dropdown {
            margin-left: 20px;
            padding: 10px;
        }

        .dropdown-menu {
            color: #b6b7be;
            padding: 10px;
            background-color: #1f2024;
            border: transparent;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.17);
        }

        .dropdown-item {
            padding: 7.5px;
            display: flex;
            align-content: center;
            border-radius: 5px;
            color: #b6b7be;
            transition: background 0.3s, color 0.3s;
        }

        .dropdown-item:hover {
            background: #3a3b3f;
            color: #fff;
        }

        .sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            bottom: 0;
            width: 260px;
            background-color: rgb(17, 18, 22);
            padding-top: 20px;
            border-right: 0.25px solid rgba(187, 188, 190, 0.25);
            overflow-y: auto;
            transition: transform 0.3s ease-in-out;
            z-index: 999;
        }

        .sidebar-collapsed {
            transform: translateX(-100%);
        }

        .sidebar a {
            margin-left: 20px;
            margin-right: 20px;
            display: flex;
            align-items: center;
            padding: 13px 20px;
            padding-top: 10px;
            color: rgba(255, 255, 255, 0.92);
            text-decoration: none;
            border-radius: 7.5px;
            transition: background 0.3s, color 0.3s;
        }

        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.06);
            color: #fff;
        }

        .sidebar a.active {
            background-color: rgba(187, 194, 209, 0.17);
            color: #fff;
        }

        .sidebar-record-sale {
            font-size: 1.2rem;
            padding: 15px 25px;
            background: transparent;
            border: 0.5px solid rgba(187, 188, 190, 0.5);
            transition: border-color 0.3s, color 0.3s;
            margin-bottom: 16px;
        }

        .sidebar-record-sale:hover {
            background: transparent;
            border: 1.5px solid rgb(187, 188, 190);
            color: #fff;
        }

        .sidebar-record-sale.active {
            color: #fff;
        }

        .sidebar-restock {
            display: block;
            font-size: 1.2rem;
            padding: 15px 25px;
            background: transparent;
            border: 0.5px solid rgba(187, 188, 190, 0.5);
            transition: border-color 0.3s, color 0.3s;
            margin-bottom: 16px;
        }

        .sidebar-restock:hover {
            background: transparent;
            border: 1.5px solid rgb(187, 188, 190);
            color: #fff;
        }

        .sidebar-restock.active {
            color: #fff;
        }

        .main-content {
            grid-row: 2 / -1;
            grid-column: 2 / -1;
            padding: 24px 24px;
            overflow-y: auto;
            transition: margin-left 0.3s;
            margin: 0;
        }

        .container {
            width: 100%;
            padding: 0;
            margin: 0 auto;
        }

        /* Mobile Devices (e.g., up to 768px) */
        @media (max-width: 768px) {
            body {
                grid-template-columns: 1fr;
                /* Single column layout */
            }

            .sidebar {
                display: none;
                /* Hide the sidebar */
            }

            .main-content {
                grid-column: 1 / -1;
                /* Main content takes full width */
                margin-left: 0;
                padding: 10px;
            }

            .sidebar-open {
                display: block;
                position: fixed;
                top: 60px;
                left: 0;
                width: 80%;
                height: calc(100% - 60px);
                background-color: rgb(17, 18, 22);
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }

            .sidebar-open.sidebar-open-visible {
                transform: translateX(0);
                /* Show sidebar when toggled */
            }

            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.34);
                backdrop-filter: blur(1px);
                z-index: 101;
            }

            .overlay-open {
                display: block;
                /* Show overlay when sidebar is open */
            }
        }

        /* Tablet Devices (e.g., 768px to 1024px) */
        @media (min-width: 768px) and (max-width: 1024px) {
            body {
                grid-template-columns: 1fr;
                /* Single column layout */
            }

            .sidebar {
                display: none;
                /* Hide the sidebar */
            }

            .main-content {
                grid-column: 1 / -1;
                /* Main content takes full width */
                margin-left: 0;
                padding: 10px;
            }

            .sidebar-open {
                display: block;
                position: fixed;
                top: 60px;
                left: 0;
                width: 40%;
                height: calc(100% - 60px);
                background-color: rgb(17, 18, 22);
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }

            .sidebar-open.sidebar-open-visible {
                transform: translateX(0);
                /* Show sidebar when toggled */
            }

            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.10);
                backdrop-filter: blur(1px);
                z-index: 101;
            }

            .overlay-open {
                display: block;
                /* Show overlay when sidebar is open */
            }
        }

        .navbar-brand {
            display: none !important;
        }

        .navbar-brand-mobile {
            display: block;
            font-size: 1.5rem;
            font-weight: bold;
            margin-left: 10px;
        }

        .minimize-btn {
            display: block;
            margin-left: 10px;
        }

        .navbar1 {
            display: flex;
            align-items: center;
        }

        @media (min-width: 1025px) {
            .sidebar {
                display: block !important;
                transform: translateX(0) !important;
            }

            .minimize-btn {
                display: none !important;
            }

            .navbar-brand-mobile {
                display: none !important;
            }

            .navbar-brand {
                display: block !important;
            }
        }

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

        .minimize-btn {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
        }

        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 1.5rem;
            margin-right: 20px;
        }

        .sidebar hr {
            border: 1px solid rgb(255, 255, 255);
            margin: 10px 20px;
        }

        .sidebar a img {
            width: 24px;
            /* Adjust the width as needed */
            height: 24px;
            /* Adjust the height as needed */
            margin-right: 10px;
            /* Space between image and text */
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-fixed-top">
        <div class="navbar1">
            <button class="minimize-btn" onclick="toggleSidebar()">☰</button>
            <a class="navbar-brand" href="dashboard.php">PCARBA Sari-Sari Store</a>
            <span class="navbar-brand-mobile">Dashboard</span>
        </div>
        <div class="navbar2">
            <div class="ms-auto">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle" href="" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo $_SESSION['username']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a href="generalSettings.php" class="dropdown-item">Settings</a></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="sidebar sidebar-open">
        <a href="transaction.php" class="sidebar-link sidebar-record-sale <?php echo in_array($current_page, ['addSale.php', 'addCredit.php', 'transaction.php']) ? 'active' : ''; ?>">New Transaction</a>
        <a href="restock.php" class="sidebar-link sidebar-restock <?php echo in_array($current_page, ['restock.php']) ? 'active' : ''; ?>">Restock</a>
        <hr>
        <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <img src="images/<?php echo $current_page == 'dashboard.php' ? 'DB.png' : 'DB.png'; ?>" alt="Dashboard">&nbsp;Dashboard
        </a>
        <a href="inventory.php" class="<?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>">
            <img src="images/<?php echo $current_page == 'inventory.php' ? 'cabinet_active.png' : 'cabinet.png'; ?>" alt="Inventory">&nbsp;Inventory
        </a>
        <a href="sales.php" class="<?php echo $current_page == 'sales.php' ? 'active' : ''; ?>">
            <img src="images/<?php echo $current_page == 'sales.php' ? 'barsales_active.png' : 'barsales.png'; ?>" alt="Sales">&nbsp;Sales
        </a>
        <a href="credit.php" class="<?php echo $current_page == 'credit.php' ? 'active' : ''; ?>">
            <img src="images/<?php echo $current_page == 'credit.php' ? 'credits-active.png' : 'credits.png'; ?>" alt="Credits">&nbsp;Credits
        </a>
        <a href="audit_logs.php" class="<?php echo $current_page == 'audit_logs.php' ? 'active' : ''; ?>">
            <img src="images/<?php echo $current_page == 'audit_logs.php' ? 'auditlogs-active.png' : 'auditlogs.png'; ?>" alt="Audit Logs">&nbsp;Audit Logs
        </a>
        <a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <img src="images/<?php echo $current_page == 'reports.php' ? 'reports.png' : 'reports.png'; ?>" alt="reports">&nbsp;Reports
        </a>
    </div>

    <div class="overlay" onclick="toggleSidebar()"></div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar-open');
            const overlay = document.querySelector('.overlay');
            const body = document.body;

            sidebar.classList.toggle('sidebar-open-visible');
            overlay.classList.toggle('overlay-open');

            // Adjust grid layout for tablets
            if (window.innerWidth >= 768 && window.innerWidth <= 1024) {
                if (sidebar.classList.contains('sidebar-open-visible')) {
                    body.style.gridTemplateColumns = '40% 1fr'; /* Show sidebar */
                } else {
                    body.style.gridTemplateColumns = '1fr'; /* Hide sidebar */
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = "<?php echo $current_page; ?>";
            const pageNames = {
                'restock.php': 'Restock',
                'editProduct.php': 'Product',
                'insertProduct.php': 'Product',
                'transaction.php': 'Transaction',
                'addCredit.php': 'Transaction',
                'addSale.php': 'Transaction',
                'dashboard.php': 'Dashboard',
                'inventory.php': 'Inventory',
                'sales.php': 'Sales',
                'credit.php': 'Credit',
                'audit_logs.php': 'Audit Logs',
                'reports.php': 'Reports',
            };

            const navbarBrandMobile = document.querySelector('.navbar-brand-mobile');
            if (navbarBrandMobile && pageNames[currentPage]) {
                navbarBrandMobile.textContent = pageNames[currentPage];
            }
        });
    </script>
</body>

</html>