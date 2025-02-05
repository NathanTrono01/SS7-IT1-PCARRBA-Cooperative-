<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include('db.php');

// Fetch total credit
$totalCreditQuery = "SELECT SUM(totalAmount) as totalCredit FROM credits WHERE userId = ?";
$stmt = $conn->prepare($totalCreditQuery);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();
$totalCredit = $result->fetch_assoc()['totalCredit'] ?? 0;

// Fetch credits
$creditsQuery = "SELECT products.productName, credits.quantity, credits.totalAmount, credits.transactionDate, credits.paymentStatus 
                 FROM credits 
                 JOIN products ON credits.productId = products.productId 
                 WHERE credits.userId = ?";
$stmt = $conn->prepare($creditsQuery);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$credits = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch products
$productsQuery = "SELECT productId, productName, unitPrice FROM products";
$products = $conn->query($productsQuery)->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['productId'];
    $quantity = $_POST['quantity'];
    $totalAmount = $quantity * $products[array_search($productId, array_column($products, 'productId'))]['unitPrice'];
    $transactionDate = date('Y-m-d H:i:s');
    $paymentStatus = 'Pending';

    $insertCreditQuery = "INSERT INTO credits (userId, productId, quantity, totalAmount, transactionDate, paymentStatus) 
                          VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertCreditQuery);
    $stmt->bind_param("iiidss", $_SESSION['userId'], $productId, $quantity, $totalAmount, $transactionDate, $paymentStatus);
    $stmt->execute();

    header("Location: credit.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Credit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        body {
            background-color: #121212;
            color: #e0e0e0;
            font-family: Arial, sans-serif;
        }

        .main-content {
            padding: 20px;
        }

        .container {
            background-color: #1e1e1e;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            padding: 20px;
            margin-top: 20px;
        }

        h1, h2, h3 {
            color: #ff9800;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table thead {
            background-color: #ff9800;
            color: #121212;
        }

        table th, table td {
            padding: 10px;
            border: 1px solid #333;
        }

        table tr:nth-child(even) {
            background-color: #2c2c2c;
        }

        .btn-primary {
            background-color: #ff9800;
            border-color: #ff9800;
            color: #121212;
        }

        .btn-secondary {
            background-color: #03dac6;
            border-color: #03dac6;
            color: #121212;
        }

        .form-label {
            font-weight: bold;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }

        .form-select, .form-control {
            background-color: #2c2c2c;
            color: #e0e0e0;
            border: 1px solid #333;
        }

        .form-select option {
            background-color: #2c2c2c;
            color: #e0e0e0;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <div class="main-content">
        <div class="container">
            <h1>Credit</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>.</p>
            <h3>Your Total Credit: ₱<?php echo number_format($totalCredit, 2); ?></h3>

            <h2>Credits</h2>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Total Amount</th>
                        <th>Transaction Date</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($credits)): ?>
                        <tr><td colspan="5">No credits added yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($credits as $credit): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($credit['productName']); ?></td>
                                <td><?php echo $credit['quantity']; ?></td>
                                <td>₱<?php echo number_format($credit['totalAmount'], 2); ?></td>
                                <td><?php echo $credit['transactionDate']; ?></td>
                                <td><?php echo htmlspecialchars($credit['paymentStatus']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2>Add a New Credit</h2>
            <form method="POST" action="credit.php">
                <div class="mb-3">
                    <label for="product" class="form-label">Product</label>
                    <select class="form-select" name="productId" required>
                        <option value="" disabled selected>Select a product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['productId']; ?>"><?php echo htmlspecialchars($product['productName']); ?> - ₱<?php echo number_format($product['unitPrice'], 2); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" required min="1">
                </div>
                <button type="submit" class="btn btn-primary">Add Credit</button>
            </form>

            <br>
            <a href="logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>
</body>
</html>