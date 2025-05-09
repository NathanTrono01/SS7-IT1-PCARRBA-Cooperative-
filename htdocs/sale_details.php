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
                 p.productName, p.imagePath AS image, si.quantity, si.subTotal 
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
        .form-container {
            background-color: transparent;
            padding: 10px;
            align-content: center;
        }

        .form-container h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #f7f7f8;
            text-align: center;
        }

        .main-content {
            background-color: transparent;
            padding: 20px;
            color: #f7f7f8;
            width: 100%;
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
            background-color: transparent;
        }

        table th {
            padding: 10px;
            background-color: transparent;
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

        .btn-back-wrapper {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #f7f7f8;
            cursor: pointer;
        }

        .btn-back-wrapper span {
            margin-left: 10px;
            font-size: 16px;
        }

        .btn-back-wrapper img {
            width: 25px;
            height: 25px;
        }

        /* Product thumbnail styling */
        .product-thumb-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-thumbnail {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover; /* This makes the image cover the container */
            object-position: center; /* Centers the image within the container */
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden; /* Ensures the image doesn't spill outside */
        }

        /* Hide thumbnails on small screens */
        @media (max-width: 576px) {
            .product-thumbnail {
                display: none;
            }

            .product-thumb-container {
                gap: 0;
            }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="main-content fade-in">
        <div class="form-container">
            <a href="#" class="btn-back-wrapper" id="back-button">
                <img src="images/back.png" alt="Another Image" class="btn-back" id="another-image">
                <b><span>Back</span></b>
            </a>
            <hr>
            <div class="table-wrapper">
                <h1 align="center">Sale Details</h1>
                <table>
                    <tr>
                        <td><strong>Date Sold:</strong> <?php echo htmlspecialchars($dateSold); ?></td>
                        <td><strong>Sale ID:</strong> <?php echo htmlspecialchars($saleDetails[0]['saleId']); ?></td>
                    </tr>
                    <tr>
                        <td>
                            <strong>Transaction Type:</strong> <?php echo htmlspecialchars($saleDetails[0]['transactionType']); ?>
                        </td>
                        <td>
                            <strong>Total Price: </strong> ₱ <?php echo htmlspecialchars($saleDetails[0]['totalPrice']); ?>
                        </td>
                    </tr>
                </table>
                <br>
                <h3>Product Sold:</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($saleDetails as $item) { 
                            // Check if image exists in the correct path from database
                            // The database uses 'image' field but your products table has 'imagePath'
                            $imagePath = isset($item['image']) && !empty($item['image']) 
                                ? (file_exists($item['image']) ? $item['image'] : 'images/no-image.png') 
                                : 'images/no-image.png';
                        ?>
                            <tr>
                                <td>
                                    <div class="product-thumb-container">
                                        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($item['productName']); ?>" class="product-thumbnail">
                                        <span><?php echo htmlspecialchars($item['productName']); ?> x <?php echo htmlspecialchars($item['quantity']); ?></span>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <script>
                document.getElementById('another-image').addEventListener('mouseover', function() {
                    this.src = 'images/back-hover.png';
                });

                document.getElementById('another-image').addEventListener('mouseout', function() {
                    this.src = 'images/back.png';
                });

                document.addEventListener('DOMContentLoaded', function() {
                    var referrer = document.referrer;
                    var backButton = document.getElementById('back-button');
                    if (referrer.includes('sales.php')) {
                        backButton.href = 'sales.php';
                    } else if (referrer.includes('reports.php')) {
                        backButton.href = 'reports.php?tab=items';
                    } else {
                        backButton.href = 'reports.php?tab=items';
                    }
                });
            </script>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>