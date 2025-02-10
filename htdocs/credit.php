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
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <div class="main-content">
        <div class="container">
            <h2>Credits</h2>
            <table>
                <thead>
                    <tr>
                        <th>Transaction Date</th>
                        <th>Creditor</th>
                        <th>Total Amount</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                   
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>