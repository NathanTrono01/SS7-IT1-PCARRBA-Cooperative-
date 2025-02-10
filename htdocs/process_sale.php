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
    $amountPaid = $_POST['amountPaid'] ?? 0;
    $userId = $_SESSION['userId'];


    // Calculate total price
    $totalPrice = 0;
    foreach ($productIds as $index => $productId) {
        $totalPrice += $quantities[$index] * $prices[$index];
    }

    $dateSold = getCurrentDateTime();

    // Insert sale into the `sale` table
    $saleSql = "INSERT INTO sales (totalPrice, transactionType, dateSold, userId) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($saleSql);
    $stmt->bind_param("dssi", $totalPrice, $transactionType, $dateSold, $userId);
    $stmt->execute();
    $saleId = $stmt->insert_id;

    // Insert sale items into the `sale_item` table
    foreach ($productIds as $index => $productId) {
        $quantity = $quantities[$index];
        $price = $prices[$index];
        $subTotal = $quantity * $price;

        $saleItemSql = "INSERT INTO sale_item (quantity, price, subTotal, productId, saleId) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($saleItemSql);
        $stmt->bind_param("iddii", $quantity, $price, $subTotal, $productId, $saleId);
        $stmt->execute();

        // Update product stock
        $updateStockSql = "UPDATE products SET stockLevel = stockLevel - ? WHERE productId = ?";
        $stmt = $conn->prepare($updateStockSql);
        $stmt->bind_param("ii", $quantity, $productId);
        $stmt->execute();
    }

    // Redirect to sales page with success message
    $_SESSION['message'] = "Sale recorded successfully!";
    $_SESSION['alert_class'] = "alert-success";
    header("Location: sales.php");
    exit();
}