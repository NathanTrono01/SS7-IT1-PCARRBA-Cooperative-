<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';
include 'datetime.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productIds = $_POST['productId'];
    $quantities = $_POST['quantity'];
    $prices = $_POST['price'];
    $transactionType = $_POST['transactionType'];
    $customerName = $_POST['customerName'];
    $phoneNumber = $_POST['phoneNumber'];
    $userId = $_SESSION['userId'];

    // Validate inputs
    if (empty($productIds) || empty($quantities) || empty($prices) || empty($customerName) || empty($phoneNumber)) {
        $_SESSION['message'] = "All fields are required.";
        $_SESSION['alert_class'] = "alert-danger";
        header("Location: addSale.php");
        exit();
    }

    // Calculate total price
    $totalPrice = 0;
    foreach ($productIds as $index => $productId) {
        $totalPrice += $quantities[$index] * $prices[$index];
    }

    $dateSold = getCurrentDateTime();

    // Insert creditor into the `creditor` table
    $creditorSql = "INSERT INTO creditor (customerName, phoneNumber, creditBalance) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($creditorSql);
    $stmt->bind_param("ssd", $customerName, $phoneNumber, $totalPrice);
    $stmt->execute();
    $creditorId = $stmt->insert_id;

    // Insert credit into the `credits` table
    $creditSql = "INSERT INTO credits (creditorId, paymentStatus, dateCreated, userId) VALUES (?, 'Unpaid', ?, ?)";
    $stmt = $conn->prepare($creditSql);
    $stmt->bind_param("iss", $creditorId, $dateSold, $userId);
    $stmt->execute();
    $creditId = $stmt->insert_id;

    // Insert sale items into the `sale_item` table
    foreach ($productIds as $index => $productId) {
        $quantity = $quantities[$index];
        $price = $prices[$index];
        $subTotal = $quantity * $price;

        $saleItemSql = "INSERT INTO sale_item (quantity, price, subTotal, productId, creditId) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($saleItemSql);
        $stmt->bind_param("iddii", $quantity, $price, $subTotal, $productId, $creditId);
        $stmt->execute();

        // Update product stock
        $updateStockSql = "UPDATE products SET stockLevel = stockLevel - ? WHERE productId = ?";
        $stmt = $conn->prepare($updateStockSql);
        $stmt->bind_param("ii", $quantity, $productId);
        $stmt->execute();
    }

    // Redirect to credits page with success message
    $_SESSION['message'] = "Credit recorded successfully!";
    $_SESSION['alert_class'] = "alert-success";
    header("Location: credits.php");
    exit();
}
?>