<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

// Fetch summarized sales data
$query = "SELECT saleId, dateSold, transactionType, totalPrice, creditId FROM sales ORDER BY dateSold DESC";
$result = $conn->query($query);

if (!$result) {
    die("Database query failed: " . $conn->error);
}

$sales = $result->fetch_all(MYSQLI_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Transactions</title>
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

        .search-wrapper input:focus {
            outline: none;
            border: 2px solid #335fff;
        }

        .floating-label {
            position: absolute;
            left: 30px;
            top: 8px;
            /* Adjust this value to align with the input's border */
            transform: translateY(0);
            pointer-events: none;
            transition: all 0.3s ease;
            color: rgba(247, 247, 248, 0.64);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            font-family: Arial, Helvetica, sans-serif;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            table-layout: fixed;
            /* Ensure even spacing of columns */
        }

        table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #1f1f1f;
        }

        table tbody tr:nth-child(odd) {
            background-color: #272930;
            /* Darker background for odd rows */
        }

        table tbody tr:nth-child(even) {
            background-color: rgb(17, 18, 22);
            /* Lighter background for even rows */
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
            /* Add padding to the sides of the td elements */
            font-size: 1rem;
            color: #eee;
            margin: 0 5px;
            white-space: normal;
            overflow: hidden;
            text-overflow: ellipsis;
            word-wrap: break-word;
        }

        table tr {
            background-color: transparent;
        }

        table tr:hover {
            background-color: rgba(187, 194, 209, 0.17);
            transition: all 0.3s ease;
        }

        /* table tr td:first-child {
            border-top-left-radius: 5px;
            border-bottom-left-radius: 5px;
        }

        table tr td:last-child {
            border-top-right-radius: 5px;
            border-bottom-right-radius: 5px;
        } */

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
            <div class="table-wrapper">
                <div class="header-container">
                    <h2>Sales</h2>
                    <div class="button">
                        <a href="addSale.php">New Sale</a>
                    </div>
                </div>
                <div class="search-container">
                    <div class="search-wrapper">
                        <img src="images/search-icon.png" alt="Search" class="search-icon">
                        <input type="text" id="searchBar" placeholder="Search Sales" onkeyup="filterSales(); toggleClearIcon();">
                        <img src="images/x-circle.png" alt="Clear" class="clear-icon" onclick="clearSearch()">
                    </div>
                </div>
                <hr style="height: 1px; border: none; color: rgb(187, 188, 190); background-color: rgb(187, 188, 190);">
                <table id="salesTable" width="100%">
                    <thead>
                        <tr align="left">
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sales)) { ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No sales records found. <a href="addSale.php">Create a new sale</a>.</td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($sales as $sale) {
                                $dateSold = !empty($sale['dateSold'])
                                    ? date("n/j/y", strtotime($sale['dateSold'])) . "<br>" . date("g:i A", strtotime($sale['dateSold']))
                                    : 'N/A';
                            ?>
                                <tr>
                                    <td><?php echo $dateSold; ?></td>
                                    <td><?php echo htmlspecialchars($sale['transactionType']); ?></td>
                                    <td>₱ <?php echo htmlspecialchars($sale['totalPrice']); ?></td>
                                    <td>
                                        <?php if ($sale['transactionType'] === 'Credit') { ?>
                                            <a href="credit_details.php?creditId=<?php echo $sale['creditId']; ?>" class="view-details">
                                                <span>View Details</span>
                                            </a>
                                        <?php } else { ?>
                                            <a href="sale_details.php?saleId=<?php echo $sale['saleId']; ?>" class="view-details">
                                                <span>View Details</span>
                                            </a>
                                        <?php } ?>
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
        function filterSales() {
            const query = document.getElementById('searchBar').value.toLowerCase();
            const rows = document.querySelectorAll('#salesTable tbody tr');

            rows.forEach(row => {
                const transactionType = row.cells[0].textContent.toLowerCase();
                row.style.display = transactionType.includes(query) ? '' : 'none';
            });
        }

        function clearSearch() {
            document.getElementById('searchBar').value = '';
            filterSales();
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