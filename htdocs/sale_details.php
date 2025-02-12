<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

if (!isset($_GET['saleId'])) {
    header("Location: sales.php");
    exit();
}

$saleId = $_GET['saleId'];

// Fetch sale details
$query = "SELECT s.saleId, s.dateSold, s.transactionType, s.totalPrice, 
                 p.productName, si.quantity, si.subTotal 
          FROM sales s
          JOIN sale_item si ON s.saleId = si.saleId
          JOIN products p ON si.productId = p.productId
          WHERE s.saleId = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $saleId);
$stmt->execute();
$result = $stmt->get_result();
$saleDetails = $result->fetch_all(MYSQLI_ASSOC);

// Check if sale exists
if (empty($saleDetails)) {
    echo "<p>Sale record not found.</p>";
    exit();
}

// Format the dateSold
$dateSold = !empty($saleDetails[0]['dateSold']) ? date("M d, Y -- g:i A", strtotime($saleDetails[0]['dateSold'])) : 'N/A';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Details</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        .main-content {
            background-color: transparent;
            padding: 20px;
            color: #f7f7f8;
            width: 100%;
            max-width: 1200px;
        }

        .main-content h1,
        .main-content h3 {
            color: #f7f7f8;
        }

        .main-content p {
            color: #f7f7f8;
        }

        .table-wrapper {
            background-color: #191a1f;
            width: 100%;
            margin: 25px auto;
            position: relative;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.58);
        }

        table {
            font-family: Arial, Helvetica, sans-serif;
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #1f1f1f;
        }

        table th {
            padding: 10px;
            background-color: #0c0c0f;
            color: rgba(247, 247, 248, 0.9);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 1rem;
            border: 2px solid transparent;
        }

        table td {
            padding: 10px;
            font-size: 1rem;
            color: #eee;
            border: 2px solid transparent;
        }

        table tr {
            background-color: transparent;
        }

        table tr:hover {
            background-color: rgba(187, 194, 209, 0.17);
            transition: all 0.3s ease;
        }

        .btn-secondary {
            background-color: #6c757d;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="container-fluid h-100 d-flex align-items-center justify-content-center">
        <div class="main-content">
            <h1>Items Sold:</h1>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Quantity</th>
                            <th>Product</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($saleDetails as $item) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($item['productName']); ?></td>
                                <td>₱ <?php echo htmlspecialchars($item['subTotal']); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <h1>Sale Details</h1>
            <p><strong>Sale ID:</strong> <?php echo htmlspecialchars($saleDetails[0]['saleId']); ?></p>
            <p><strong>Date Sold:</strong> <?php echo htmlspecialchars($dateSold); ?></p>
            <p><strong>Transaction Type:</strong> <?php echo htmlspecialchars($saleDetails[0]['transactionType']); ?></p>
            <p><strong>Total Price: </strong> ₱ <?php echo htmlspecialchars($saleDetails[0]['totalPrice']); ?></p>
            <a href="sales.php" class="btn btn-secondary mt-3">Back to Sales</a>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>