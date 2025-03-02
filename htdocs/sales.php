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


        .alert-success {
            position: fixed;
            margin-top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #d4edda;
            border: 1px solid green;
            color: green;
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

        th {
            cursor: pointer;
        }

        th img {
            width: 15px;
            height: 15px;
            margin-right: 10px;
        }

        .table-wrapper {
            height: 70vh;
            /* Ensure the wrapper takes 70% of viewport height */
            overflow-y: auto;
            /* Allow vertical scrolling */
            position: relative;
            padding: 10px;
            padding-top: 0;
        }


        #saleTable {
            width: 100%;
            border-collapse: collapse;
        }

        /* Sticky Header */
        #saleTable thead {
            position: sticky;
            top: 0;
            background: rgb(17, 18, 22);
            z-index: 10;
        }


        /* Styling for the headers */
        #saleTable th {
            background: rgb(17, 18, 22);
            /* Dark background */
            color: white;
            border-bottom: 2px solid #333942;
            text-align: left;
        }

        /* Making tbody scrollable while keeping alignment */
        #saleTable tbody {
            display: block;
            max-height: 65vh;
            /* Adjust height dynamically */
        }

        /* Ensuring rows and cells align properly */
        #saleTable tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        #saleTable td {
            padding: 10px 10px;
            font-size: 1rem;
            margin: 0 5px;
            width: 20%;
        }

        /* Scrollbar for tbody */
        #saleTable .table-wrapper::-webkit-scrollbar {
            width: 6px;
            transition: opacity 0.3s ease-in-out;
        }

        /* Track (background of the scrollbar) */
        #saleTable .table-wrapper::-webkit-scrollbar-track {
            background: transparent;
        }

        /* Thumb (scrollable part) */
        #saleTable .table-wrapper::-webkit-scrollbar-thumb {
            background-color: #555;
            background: grey;
            border-radius: 10rem;
            opacity: 0;
            /* Initially hidden */
        }

        /* Show scrollbar on hover */
        #saleTable .table-wrapper:hover::-webkit-scrollbar-thumb {
            opacity: 1;
            /* Visible when hovering */
        }

        /* Hover effect for better UX */
        #saleTable .table-wrapper::-webkit-scrollbar-thumb:hover {
            background-color: #777;
        }


        @media (max-width: 1024px) {
            .table-wrapper {
                /* Responsive height based on viewport */
                overflow-y: auto;
                /* Scrollable body */
                position: relative;
                overflow-x: hidden;
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

        .main-content {
            padding: 10px;
            height: 100vh;
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
                <h2>Sales</h2>
                <div class="button">
                    <a href="addSale.php">New Sale</a>
                </div>
                <div class="search-container">
                    <div class="search-wrapper">
                        <img src="images/search-icon.png" alt="Search" class="search-icon">
                        <input type="text" id="searchBar" placeholder="Search (Date/Type)" onkeyup="filterSales(); toggleClearIcon();">
                        <img src="images/x-circle.png" alt="Clear" class="clear-icon" onclick="clearSearch()">
                    </div>
                </div>
            </div>
            <div class="table-wrapper">
                <table id="salesTable">
                    <thead>
                        <tr>
                            <th onclick="sortTable(0)">Date <span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(1)">Type <span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(2)">Amount <span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sales)) { ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No sales records found. <a href="addSale.php">Create a new sale</a>.</td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($sales as $sale) { ?>
                                <tr>
                                    <td data-date="<?php echo $sale['dateSold']; ?>">
                                        <?php echo date("n/j/y", strtotime($sale['dateSold'])) . "<br>" . date("g:i A", strtotime($sale['dateSold'])); ?>
                                    </td>
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
        function createRipple(event) {
            const button = event.currentTarget;
            const ripple = document.createElement("span");
            ripple.classList.add("ripple");

            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height) * 2.5; // Bigger ripple
            ripple.style.width = ripple.style.height = `${size}px`;

            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;

            button.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 2000); // Matches animation duration
        }
    </script>
</body>

</html>