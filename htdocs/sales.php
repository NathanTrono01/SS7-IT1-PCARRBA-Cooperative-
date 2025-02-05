<?php
session_start();
include('db.php');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

if (isset($_SESSION['accountLevel'])) {
    $accountLevel = $_SESSION['accountLevel'];
} else {
    $accountLevel = ''; // Set role to empty if not logged in
}

// Get sales data
$sql = "SELECT sales.saleId, products.productName, sales.quantitySold, sales.totalPrice, sales.saleDate 
        FROM sales 
        JOIN products ON sales.productId = products.productId";
$result = mysqli_query($conn, $sql);
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
        .table-wrapper {
            background-color: transparent;
            max-height: 600px;
            overflow-y: auto;
            width: 100%;
            max-width: none;
            margin: 25px auto;
            position: relative;
            padding: 0 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-wrapper::-webkit-scrollbar {
            width: 5px;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .table-wrapper:hover::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2);
        }

        table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #1f1f1f;
            border-bottom: 0.25px solid rgba(187, 188, 190, 0.25);
            border-radius: 10px 10px 0 0;
        }

        table th {
            padding: 7.5px;
            background-color: rgb(17, 18, 22);
            color: #fff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 1rem;
            border-radius: 10px 10px 0 0;
        }

        table td {
            padding: 10px;
            font-size: 1rem;
            color: #eee;
        }

        table tr {
            background-color: rgb(17, 18, 22);
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
            padding: 5px 10px;
            font-size: 0.875rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            opacity: 0.8;
        }

        @media (max-width: 1024px) {
            .table-wrapper {
                width: 95%;
            }

            table th,
            table td {
                font-size: 0.95rem;
                padding: 6px;
            }
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 1.8em;
            }

            .table-wrapper {
                width: 100%;
            }

            table th,
            table td {
                font-size: 0.85rem;
                padding: 6px;
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
                padding: 0 5px;
                width: 100%;
            }

            table {
                font-size: 0.8rem;
            }

            table th,
            table td {
                font-size: 0.8rem;
                padding: 5px;
            }
        }
    </style>
</head>
<?php include 'navbar.php'; ?>
<script src="js/bootstrap.bundle.min.js"></script>

<body>

    <div class="container main-content">
        <h1>Sales Transactions</h1>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Item Name</th>
                        <th>Quantity Sold</th>
                        <th>Total Price (₱)</th>
                        <th>Date Sold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['saleId']; ?></td>
                            <td><?php echo htmlspecialchars($row['productName']); ?></td>
                            <td><?php echo $row['quantitySold']; ?></td>
                            <td>₱ <?php echo $row['totalPrice']; ?></td>
                            <td><?php echo date("F j, Y, g:i a", strtotime($row['saleDate'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>