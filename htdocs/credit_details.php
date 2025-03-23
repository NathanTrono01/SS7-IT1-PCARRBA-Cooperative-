<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';
include 'datetime.php';

if (!isset($_GET['creditId'])) {
    header("Location: credits.php");
    exit();
}

$creditId = $_GET['creditId'];

// Fetch credit details
$query = "SELECT c.creditId, c.transactionDate, c.paymentStatus, cr.customerName, cr.phoneNumber, cr.creditBalance, cr.amountPaid,
               p.productName, p.imagePath AS image, si.quantity, si.subTotal, cr.creditorId, c.userId, c.lastUpdated
        FROM credits c
        JOIN sale_item si ON c.creditId = si.creditId
        JOIN products p ON si.productId = p.productId
        JOIN creditor cr ON c.creditorId = cr.creditorId
        WHERE c.creditId = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $creditId);
$stmt->execute();
$result = $stmt->get_result();
$creditDetails = $result->fetch_all(MYSQLI_ASSOC);

// Check if credit exists
if (empty($creditDetails)) {
    echo "<p>Credit record not found.</p>";
    exit();
}

// Format the transactionDate
$transactionDate = !empty($creditDetails[0]['transactionDate']) ? date("M d, Y -- g:i A", strtotime($creditDetails[0]['transactionDate'])) : 'N/A';

// Handle form submission for updating amount 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amountPaid = $_POST['amountPaid'];
    if ($amountPaid > $creditDetails[0]['creditBalance']) {
        echo "<p>Amount paid cannot exceed the credit balance.</p>";
        exit();
    }
    $newAmountPaid = $creditDetails[0]['amountPaid'] + $amountPaid;
    $newCreditBalance = $creditDetails[0]['creditBalance'] - $amountPaid;
    if ($newCreditBalance <= 0) {
        $paymentStatus = 'Paid';
    } elseif ($newCreditBalance < $creditDetails[0]['creditBalance']) {
        $paymentStatus = 'Partially Paid';
    } else {
        $paymentStatus = 'Unpaid';
    }

    // Update creditor's amount paid and credit balance
    $updateCreditorSql = "UPDATE creditor SET amountPaid = ?, creditBalance = ? WHERE creditorId = ?";
    $stmt = $conn->prepare($updateCreditorSql);
    $stmt->bind_param("dii", $newAmountPaid, $newCreditBalance, $creditDetails[0]['creditorId']);
    $stmt->execute();

    // Update credit's payment status and lastUpdated
    $dateUpdated = getCurrentDateTime();
    $updateCreditSql = "UPDATE credits SET paymentStatus = ?, lastUpdated = ? WHERE creditId = ?";
    $stmt = $conn->prepare($updateCreditSql);
    $stmt->bind_param("ssi", $paymentStatus, $dateUpdated, $creditId);
    $stmt->execute();

    // If payment status is 'Paid', insert into sales table
    if ($paymentStatus === 'Paid') {
        $userId = $creditDetails[0]['userId'];
        $insertSaleSql = "INSERT INTO sales (dateSold, transactionType, totalPrice, userId, creditId) VALUES (?, 'Credit', ?, ?, ?)";
        $stmt = $conn->prepare($insertSaleSql);
        $stmt->bind_param("sdii", $dateUpdated, $newAmountPaid, $userId, $creditId);
        $stmt->execute();
    }

    // Redirect to the same page to reflect changes
    header("Location: credit_details.php?creditId=$creditId");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Details</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        .form-container {
            background-color: transparent;
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
            object-fit: cover;
            /* This makes the image cover the container */
            object-position: center;
            /* Centers the image within the container */
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
            /* Ensures the image doesn't spill outside */
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

        /* Enhanced payment form styles */
        .payment-form-container {
            background-color: #242529;
            padding: 20px;
            margin-top: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .payment-form-container h3 {
            color: #f7f7f8;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 600;
        }

        .payment-summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px;
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 6px;
        }

        .payment-info {
            padding: 10px;
            font-size: 16px;
            display: flex;
            gap: 20px;
        }

        .payment-info div {
            padding: 8px 12px;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.05);
        }

        .label-highlight {
            color: #a4cfff;
            font-weight: 600;
        }

        .amount-paid {
            color: #64dd17;
        }

        .balance-due {
            color: #ff9800;
        }

        .form-control {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #f7f7f8;
            padding: 10px 15px;
            height: 45px;
        }

        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.15);
            color: #f7f7f8;
            border-color: #a4cfff;
        }

        .form-control::placeholder {
            color: rgba(247, 247, 248, 0.6);
        }

        .btn-primary {
            background-color: #0d6efd;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
        }

        /* Product thumbnail styling - already present but ensuring it's optimized */
        .product-thumbnail {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
            object-position: center;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
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
            <?php if ($creditDetails[0]['paymentStatus'] !== 'Paid') { ?>
                <div class="payment-form-container">
                    <h3>Update Payment</h3>
                    <div class="payment-summary">
                        <div class="payment-info">
                            <div>
                                <span class="label-highlight">Amount Paid:</span>
                                <span class="amount-paid">₱ <?php echo number_format($creditDetails[0]['amountPaid'], 2); ?></span>
                            </div>
                            <div>
                                <span class="label-highlight">Balance:</span>
                                <span class="balance-due">₱ <?php echo number_format($creditDetails[0]['creditBalance'], 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="amountPaid" class="form-label">Payment Amount:</label>
                            <input type="number"
                                id="amountPaid"
                                name="amountPaid"
                                class="form-control"
                                step="0.01"
                                min="0.01"
                                max="<?php echo htmlspecialchars($creditDetails[0]['creditBalance']); ?>"
                                placeholder="Enter amount (₱)"
                                required>
                        </div>
                        <input type="submit" value="Update Payment" class="btn btn-primary">
                    </form>
                </div>
            <?php } else { ?>
                <div class="payment-form-container">
                    <h3>Payment Status</h3>
                    <div class="payment-info">
                        <div>
                            <span class="label-highlight">Status:</span>
                            <span class="amount-paid">Fully Paid</span>
                        </div>
                        <div>
                            <span class="label-highlight">Total Amount Paid:</span>
                            <span class="amount-paid">₱ <?php echo number_format($creditDetails[0]['amountPaid'], 2); ?></span>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
        <hr>
        <div class="table-wrapper">
            <h1 align="center">Credit Details</h1>
            <table>
                <tr>
                    <td>
                        <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($creditDetails[0]['customerName']); ?></p>
                    </td>
                    <td>
                        <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($creditDetails[0]['phoneNumber']); ?></p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p><strong>Credit ID:</strong> <?php echo htmlspecialchars($creditDetails[0]['creditId']); ?></p>
                    </td>
                    <td>
                        <p><strong>Transaction Date:</strong> <?php echo htmlspecialchars($transactionDate); ?></p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p><strong>Payment Status: </strong> <?php echo htmlspecialchars($creditDetails[0]['paymentStatus']); ?></p>
                    </td>
                </tr>
            </table>
            <hr>
            <h3>Bought on Credit:</h3>
            <table>
                <thead>
                    <tr>
                        <th>PRODUCT DETAILS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($creditDetails as $item) {
                        // Check if image exists in the correct path from database
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
                if (referrer.includes('credit.php')) {
                    backButton.href = 'credit.php';
                } else if (referrer.includes('sales.php')) {
                    backButton.href = 'sales.php';
                } else {
                    backButton.href = 'reports.php?tab=items';
                }
            });
        </script>
        <script src="js/bootstrap.bundle.min.js"></script>
    </div>
</body>

</html>