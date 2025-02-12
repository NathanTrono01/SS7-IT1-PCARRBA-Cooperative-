<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

if (!isset($_GET['creditId'])) {
    header("Location: credits.php");
    exit();
}

$creditId = $_GET['creditId'];

// Fetch credit details
$query = "SELECT c.creditId, c.transactionDate, c.paymentStatus, cr.customerName, cr.phoneNumber, cr.creditBalance, cr.amountPaid,
                 p.productName, si.quantity, si.subTotal, cr.creditorId
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

// Handle form submission for updating amount paid
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amountPaid = $_POST['amountPaid'];
    $newAmountPaid = $creditDetails[0]['amountPaid'] + $amountPaid;
    $newCreditBalance = $creditDetails[0]['creditBalance'] - $amountPaid;
    $paymentStatus = $newCreditBalance <= 0 ? 'Paid' : 'Unpaid';

    // Update creditor's amount paid and credit balance
    $updateCreditorSql = "UPDATE creditor SET amountPaid = ?, creditBalance = ? WHERE creditorId = ?";
    $stmt = $conn->prepare($updateCreditorSql);
    $stmt->bind_param("dii", $newAmountPaid, $newCreditBalance, $creditDetails[0]['creditorId']);
    $stmt->execute();

    // Update credit's payment status
    $updateCreditSql = "UPDATE credits SET paymentStatus = ? WHERE creditId = ?";
    $stmt = $conn->prepare($updateCreditSql);
    $stmt->bind_param("si", $paymentStatus, $creditId);
    $stmt->execute();

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
    <div class="c h-100 d-flex align-items-center justify-content-center">
        <div class="main-content">
            <h1>Items Taken:</h1>
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
                        <?php foreach ($creditDetails as $item) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($item['productName']); ?></td>
                                <td>₱ <?php echo htmlspecialchars($item['subTotal']); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <h1>Credit Details</h1>
            <p><strong>Credit ID:</strong> <?php echo htmlspecialchars($creditDetails[0]['creditId']); ?></p>
            <p><strong>Transaction Date:</strong> <?php echo htmlspecialchars($transactionDate); ?></p>
            <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($creditDetails[0]['customerName']); ?></p>
            <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($creditDetails[0]['phoneNumber']); ?></p>
            <p><strong>Credit Balance: </strong> ₱ <?php echo htmlspecialchars($creditDetails[0]['creditBalance']); ?></p>
            <p><strong>Amount Paid: </strong> ₱ <?php echo htmlspecialchars($creditDetails[0]['amountPaid']); ?></p>
            <p><strong>Payment Status: </strong> <?php echo htmlspecialchars($creditDetails[0]['paymentStatus']); ?></p>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="amountPaid" class="form-label">Amount Paid:</label>
                    <input type="number" id="amountPaid" name="amountPaid" class="form-control" min="0" max="<?php echo htmlspecialchars($creditDetails[0]['creditBalance']); ?>" required>
                </div>
                <input type="submit" value="Update Payment" class="btn btn-primary">
            </form>
            <a href="credit.php" class="btn btn-secondary mt-3">Back to Credits</a>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>