<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

// Fetch summarized credit data
$query = "SELECT c.creditId, c.transactionDate, c.paymentStatus, cr.creditBalance, cr.customerName 
          FROM credits c
          JOIN creditor cr ON c.creditorId = cr.creditorId
          ORDER BY c.lastUpdated DESC";
$result = $conn->query($query);

if (!$result) {
    die("Database query failed: " . $conn->error);
}

$credits = $result->fetch_all(MYSQLI_ASSOC);

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteCreditId'])) {
    $deleteCreditId = $_POST['deleteCreditId'];
    $deleteQuery = "DELETE FROM credits WHERE creditId = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $deleteCreditId);
    if ($stmt->execute()) {
        header("Location: credit.php");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credits Transaction</title>
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
            justify-content: space-between;
            align-items: center;
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
            /* Adjust padding to make space for the search icon */
            width: 100%;
            border-radius: 5px;
            border: 1px solid #333942;
            background-color: rgba(33, 34, 39, 255);
            color: #f7f7f8;
        }

        .search-wrapper input::placeholder {
            color: rgba(247, 247, 248, 0.64);
            font-weight: 200;
        }

        table {
            font-family: Arial, Helvetica, sans-serif;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #1f1f1f;
        }

        table th {
            padding: 7px;
            background-color: #0c0c0f;
            color: rgba(247, 247, 248, 0.9);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 1rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.83);
            border-top: 2px solid #333942;
            border-bottom: 2px solid #333942;
            width: 20%;
            /* Set each column to take up 20% of the table width */
        }

        table th:first-child {
            border-left: 2px solid #333942;
            border-top: 2px solid #333942;
            border-bottom: 2px solid #333942;
        }

        table th:last-child {
            border-right: 2px solid #333942;
            border-top: 2px solid #333942;
            border-bottom: 2px solid #333942;
            width: 150px;
            /* Fixed width for the Actions column */
        }

        table td {
            padding: 5px 10px;
            /* Add padding to the sides of the td elements */
            font-size: 1rem;
            color: #eee;
            margin: 0 5px;
            width: 20%;
            /* Set each column to take up 20% of the table width */
        }

        table td:last-child {
            width: 150px;
            /* Fixed width for the Actions column */
        }

        table tr {
            background-color: transparent;
        }

        table tr:hover {
            background-color: rgba(187, 194, 209, 0.17);
            transition: all 0.3s ease;
        }

        table tr td:first-child {
            border-top-left-radius: 7px;
            border-bottom-left-radius: 7px;
        }

        table tr td:last-child {
            border-top-right-radius: 7px;
            border-bottom-right-radius: 7px;
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
            padding: 5px 10px;
            font-size: 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 75px;
            text-align: center;
            box-sizing: border-box;
        }

        .btn-action img {
            display: none;
        }

        .btn-action span {
            display: inline;
        }

        .btn-edit {
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

            table {
                width: 100%;
                table-layout: auto;
            }

            table th,
            table td {
                font-size: 0.95rem;
                padding: 6px;
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
                table-layout: auto;
            }

            table th,
            table td {
                font-size: 0.85rem;
                padding: 6px;
            }

            .btn-action {
                padding: 6px 10px;
                font-size: 0.75rem;
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
                display: block;
                width: 100%;
                overflow-x: auto;
                white-space: nowrap;
            }

            table th,
            table td {
                width: 100%;
                font-size: 0.8rem;
                padding: 5px;
            }

            .btn-action {
                padding: 4px 10px;
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
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <div class="main-content">
        <div class="container">
            <div class="table-wrapper">
                <div class="header-container">
                    <h2>Credit Transaction</h2>
                    <div class="button">
                        <a href="addCredit.php">New Credit</a>
                    </div>
                </div>
                <div class="search-container">
                    <div class="search-wrapper">
                        <img src="images/search-icon.png" alt="Search" class="search-icon">
                        <input type="text" id="searchBar" placeholder="Search Creditor/s" onkeyup="filterCredits(); toggleClearIcon();">
                        <img src="images/x-circle.png" alt="Clear" class="clear-icon" onclick="clearSearch()">
                    </div>
                </div>
                <hr style="height: 1px; border: none; color: rgb(187, 188, 190); background-color: rgb(187, 188, 190);">
                <table id="creditsTable">
                    <thead>
                        <tr align="left">
                            <th>Date</th>
                            <th>Creditor</th>
                            <th>Total Balance</th>
                            <th>Payment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($credits)) { ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No credit records found. <a href="addCredit.php">Create a new credit</a>.</td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($credits as $credit) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date("M d, Y -- g:i A", strtotime($credit['transactionDate']))); ?></td>
                                    <td><?php echo htmlspecialchars($credit['customerName']); ?></td>
                                    <td>₱ <?php echo htmlspecialchars($credit['creditBalance']); ?></td>
                                    <td><?php echo htmlspecialchars($credit['paymentStatus']); ?></td>
                                    <td>
                                        <a href="credit_details.php?creditId=<?php echo $credit['creditId']; ?>" class="btn btn-primary btn-sm btn-action">
                                            <span>View Details</span>
                                            <img src="images/open.png" alt="View Details">
                                        </a>
                                        <form method="POST" action="credit.php" style="display:inline;" onsubmit="return confirmDelete();">
                                            <input type="hidden" name="deleteCreditId" value="<?php echo $credit['creditId']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm btn-action">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function filterCredits() {
            const query = document.getElementById('searchBar').value.toLowerCase();
            const rows = document.querySelectorAll('#creditsTable tbody tr');

            rows.forEach(row => {
                const creditorName = row.cells[1].textContent.toLowerCase();
                row.style.display = creditorName.includes(query) ? '' : 'none';
            });
        }

        function clearSearch() {
            document.getElementById('searchBar').value = '';
            filterCredits();
            toggleClearIcon();
        }

        function toggleClearIcon() {
            const searchBar = document.getElementById('searchBar');
            const clearIcon = document.querySelector('.clear-icon');
            clearIcon.style.display = searchBar.value ? 'block' : 'none';
        }

        function confirmDelete() {
            return confirm('Are you sure you want to delete this credit record?');
        }

        document.addEventListener('DOMContentLoaded', toggleClearIcon);
    </script>
</body>