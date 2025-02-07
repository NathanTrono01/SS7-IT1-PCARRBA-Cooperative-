<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
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
        .navbar-fixed-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            width: 100%;
            height: 60px;
            padding: 0 20px;
            background-color: rgb(17, 18, 22);
            border-bottom: 0.25px solid rgba(187, 188, 190, 0.25);
            z-index: 1030;
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
            top: 56px;
            left: 0;
            height: 100vh;
            width: 260px;
            background-color: rgb(17, 18, 22);
            padding-top: 20px;
            z-index: 1020;
            border-right: 0.25px solid rgba(187, 188, 190, 0.25);
            overflow-y: auto;
            transition: transform 0.3s ease-in-out;
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
            font-size: 1.05rem;
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

        .main-content {
            margin-top: 10px;
            margin-left: 250px;
            padding: 15px;
            padding-top: 20px;
            transition: margin-left 0.3s;
        }

        /* Mobile Devices (e.g., up to 768px) */
        @media (max-width: 768px) {
            .sidebar {
                width: 80%;
                /* 80% width for mobile devices */
                transform: translateX(-100%);
            }

            .sidebar-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(92, 92, 92, 0.06);
                backdrop-filter: blur(1px);
                z-index: 1010;
            }

            .overlay-open {
                display: block;
            }
        }

        /* Tablet Devices (e.g., 768px to 1024px) */
        @media (max-width: 1023px) and (min-width: 766px) {
            .sidebar {
                width: 40%;
                transform: translateX(-100%);
            }

            .sidebar-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
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
                z-index: 1010;
            }

            .overlay-open {
                display: block;
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

        @media (min-width: 769px) {
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
            margin: 10px 20px; /* Adjusted margin for left and right gap */
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

    <div class="sidebar">
        <a href="addSale.php" class="sidebar-link sidebar-record-sale <?php echo $current_page == 'addSale.php' ? 'active' : ''; ?>">+ New Sale</a>
        <hr>
        <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <img src="images/<?php echo $current_page == 'dashboard.php' ? 'dashboard_active.png' : 'dashboard.png'; ?>" alt="Home">&nbsp;Home
        </a>
        <a href="inventory.php" class="<?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>">
            <img src="images/<?php echo $current_page == 'inventory.php' ? 'cabinet_active.png' : 'cabinet.png'; ?>" alt="Inventory">&nbsp;Inventory
        </a>
        <a href="sales.php" class="<?php echo $current_page == 'sales.php' ? 'active' : ''; ?>">
            <img src="images/<?php echo $current_page == 'sales.php' ? 'barsales_active.png' : 'barsales.png'; ?>" alt="Sales">&nbsp;Sales
        </a>
        <a href="credit.php" class="<?php echo $current_page == 'credit.php' ? 'active' : ''; ?>">
            <img src="images/<?php echo $current_page == 'credit.php' ? 'credits_active.png' : 'credits.png'; ?>" alt="Credits">&nbsp;Credits
        </a>
    </div>

    <div class="overlay" onclick="toggleSidebar()"></div>

    <div class="main-content">
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.overlay');
            sidebar.classList.toggle('sidebar-open');
            overlay.classList.toggle('overlay-open');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = "<?php echo $current_page; ?>";
            const pageNames = {
                'addSale.php': 'Add Sale',
                'dashboard.php': 'Dashboard',
                'inventory.php': 'Inventory',
                'sales.php': 'Sales',
                'credit.php': 'Credit'
            };

            const navbarBrandMobile = document.querySelector('.navbar-brand-mobile');
            if (navbarBrandMobile && pageNames[currentPage]) {
                navbarBrandMobile.textContent = pageNames[currentPage];
            }
        });
    </script>
</body>

</html>