<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

// Fetch audit logs
$query = "SELECT a.logId, a.action, a.details, a.timestamp, u.username 
          FROM audit_logs a 
          JOIN users u ON a.userId = u.userId 
          ORDER BY a.timestamp DESC";
$result = $conn->query($query);

if (!$result) {
    die("Database query failed: " . $conn->error);
}

$logs = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        .flex-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 10px;
        }

        .search-container {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            position: relative;
            width: 100%;
        }

        .header-container {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            flex-direction: row;
            justify-content: space-between;
            position: relative;
            padding: 10px;
        }

        .search-wrapper {
            display: flex;
            align-items: center;
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        .search-wrapper .search-icon {
            position: absolute;
            left: 10px;
            width: 16px;
            height: 16px;
        }

        .search-wrapper .clear-icon {
            position: absolute;
            right: 10px;
            width: 16px;
            height: 16px;
            cursor: pointer;
            display: none;
        }

        .search-wrapper input {
            padding: 8px 8px 8px 30px;
            width: 100%;
            border-radius: 5px;
            border: 1px solid #333942;
            background-color: rgba(33, 34, 39, 255);
            color: #f7f7f8;
        }

        .search-wrapper input:focus {
            outline: none;
            border: 2px solid #335fff;
        }

        .floating-label {
            position: absolute;
            left: 30px;
            top: 8px;
            transform: translateY(0);
            pointer-events: none;
            transition: all 0.3s ease;
            color: rgba(247, 247, 248, 0.64);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .table-wrapper {
            scroll-behavior: smooth;
            height: 70vh;
            /* Ensure the wrapper takes 70% of viewport height */
            overflow-y: auto;
            /* Allow vertical scrolling */
            position: relative;
            padding: 10px;
            padding-top: 0;
        }

        table {
            font-family: Arial, Helvetica, sans-serif;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            table-layout: fixed;
        }

        table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #1f1f1f;
        }

        table tbody tr:nth-child(odd) {
            background-color: #272930;
        }

        table tbody tr:nth-child(even) {
            background-color: rgb(17, 18, 22);
        }

        table th {
            padding: 7px;
            background-color: rgb(17, 18, 22);
            color: rgba(247, 247, 248, 0.9);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 1rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.83);
            padding-bottom: 25px;
        }

        table td {
            padding: 5px 10px;
            font-size: 1rem;
            margin: 0 5px;
        }

        table tr {
            background-color: transparent;
        }

        table tr:hover {
            background-color: rgba(187, 194, 209, 0.17);
            transition: all 0.3s ease;
        }

        .button a {
            background: transparent;
            display: inline-block;
            padding: 8px 12px;
            background-color: rgb(255, 255, 255);
            color: #000;
            text-decoration: none;
            border-radius: 7px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.26);
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        .button a:hover {
            background-color: rgba(255, 255, 255, 0.94);
            color: #000;
            border-radius: 7px;
        }

        .btn-action {
            text-decoration: none;
            padding: 8px 8px;
            font-size: 0.9rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            display: inline-flex;
            justify-content: flex-end;
            min-width: 60px;
            box-sizing: border-box;
        }

        .btn-action img {
            display: none;
        }

        .btn-action span {
            display: inline;
        }

        .btn-open {
            background-color: #335fff !important;
            color: white !important;
        }

        .btn-delete {
            border: 1px solid #ff3d3d !important;
            background-color: transparent !important;
            color: #ff3d3d !important;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        @media (max-width: 1024px) {
            .table-wrapper {
                padding: 10px;
                overflow-x: auto;
            }

            #productTable td {
                padding: 8px 10px;
                font-size: 0.8rem;
                margin: 0 5px;
                width: 20%;
            }

            table th,
            table td {
                font-size: 0.95rem;
            }

            .btn-action {
                padding: 6px 10px;
                font-size: 0.85rem;
            }

            .btn-action span {
                display: none;
            }

            .btn-action img {
                display: inline;
                width: 20px;
                height: 20px;
            }
        }

        @media (max-width: 768px) {
            .table-wrapper {
                padding: 10px;
                overflow-x: auto;
            }

            table {
                width: 100%;
            }

            table th,
            table td {
                font-size: 0.85rem;
            }

            .btn-action {
                padding: 7px 7px;
                font-size: 0.7rem;
                min-width: 30px;
            }

            .btn-action span {
                display: none;
            }

            .btn-action img {
                display: inline;
                width: 20px;
                height: 20px;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 1.5em;
            }

            h2 {
                font-size: 0.8em;
            }

            .table-wrapper {
                margin: 0;
                width: 100%;
            }

            table {
                font-size: 0.8rem;
                width: 100%;
                overflow-x: hidden;
                white-space: wrap;
            }

            table th,
            table td {
                font-size: 0.8rem;
            }

            .btn-action {
                padding: 4px 8px;
                font-size: 0.7rem;
                min-width: 30px;
            }

            .btn-action span {
                display: none;
            }

            .btn-action img {
                display: inline;
                width: 15px;
                height: 15px;
            }
        }

        .alert-success {
            position: fixed;
            margin-top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(40, 167, 70, 0.44);
            border: 1px solid rgb(0, 255, 60);
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }

        .alert-success.show {
            display: block;
        }

        .alert-warning {
            position: fixed;
            margin-top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(167, 99, 40, 0.44);
            border: 1px solid rgb(255, 136, 0);
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }

        .view-details {
            padding: 7px;
            border-radius: 5px;
            color: #335fff;
            text-decoration: none;
            font-weight: bold;
            margin-top: 5px;
        }

        .view-details:hover {
            background-color: rgba(255, 255, 255, 0.07);
            color: rgb(82, 139, 255);
            text-decoration: none;
            font-weight: bold;
            transition: 0.5s;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <div class="main-content fade-in">
        <div class="container">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert-success show <?php echo $_SESSION['alert_class']; ?>" id="alert-success">
                    <span><?php echo $_SESSION['message']; ?></span>
                </div>
                <?php unset($_SESSION['message']);
                unset($_SESSION['alert_class']); ?>
                <script>
                    setTimeout(function() {
                        document.getElementById("alert-success").classList.remove("show");
                    }, 4000);
                </script>
            <?php endif; ?>
            <div class="header-container">
                <h2>Audit Logs</h2>
                <div class="search-container">
                    <div class="search-wrapper">
                        <img src="images/search-icon.png" alt="Search" class="search-icon">
                        <input type="text" id="searchBar" placeholder="Search Action" onkeyup="filterLogs(); toggleClearIcon();">
                        <img src="images/x-circle.png" alt="Clear" class="clear-icon" onclick="clearSearch()">
                    </div>
                </div>
            </div>
            <div class="table-wrapper">
                <table id="logsTable" width="100%">
                    <thead>
                        <tr align="left">
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)) { ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No logs found.</td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($logs as $log) {
                                $date = !empty($log['timestamp'])
                                    ? date("n/j/y", strtotime($log['timestamp'])) . "<br>" . date("g:i A", strtotime($log['timestamp']))
                                    : 'N/A';
                            ?>
                                <tr>
                                    <td><?php echo $date; ?></td>
                                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function filterLogs() {
            const query = document.getElementById('searchBar').value.toLowerCase();
            const rows = document.querySelectorAll('#logsTable tbody tr');

            rows.forEach(row => {
                const action = row.cells[0].textContent.toLowerCase();
                const details = row.cells[1].textContent.toLowerCase();
                const user = row.cells[2].textContent.toLowerCase();
                const timestamp = row.cells[3].textContent.toLowerCase();

                if (action.includes(query) || details.includes(query) || user.includes(query) || timestamp.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function clearSearch() {
            document.getElementById('searchBar').value = '';
            filterLogs();
            toggleClearIcon();
        }

        function toggleClearIcon() {
            const searchBar = document.getElementById('searchBar');
            const clearIcon = document.querySelector('.clear-icon');
            clearIcon.style.display = searchBar.value ? 'block' : 'none';
        }
    </script>
</body>

</html>