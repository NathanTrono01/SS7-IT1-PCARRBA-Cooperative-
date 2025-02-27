<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    if (empty($productIds) || empty($quantities) || empty($prices) || empty($customerName)) {
        $_SESSION['message'] = "All fields are required.";
        $_SESSION['alert_class'] = "alert-danger";
        header("Location: addCredit.php");
        exit();
    }

    // Calculate total price
    $totalPrice = 0;
    foreach ($productIds as $index => $productId) {
        $totalPrice += $quantities[$index] * $prices[$index];
    }

    $dateCreated = getCurrentDateTime();

    // Insert creditor into the `creditor` table
    $creditorSql = "INSERT INTO creditor (customerName, phoneNumber, creditBalance, amountPaid) VALUES (?, ?, ?, 0)";
    $stmt = $conn->prepare($creditorSql);
    $stmt->bind_param("ssd", $customerName, $phoneNumber, $totalPrice);
    $stmt->execute();
    $creditorId = $stmt->insert_id;

    // Insert credit into the `credits` table
    $creditSql = "INSERT INTO credits (creditorId, paymentStatus, transactionDate, userId) VALUES (?, 'Unpaid', ?, ?)";
    $stmt = $conn->prepare($creditSql);
    $stmt->bind_param("iss", $creditorId, $dateCreated, $userId);
    $stmt->execute();
    $creditId = $stmt->insert_id;

    // Insert credit items into the `sale_item` table and update batch items
    foreach ($productIds as $index => $productId) {
        $quantity = $quantities[$index];
        $price = $prices[$index];
        $subTotal = $quantity * $price;

        // Update batch items and get the batchId
        $remainingQuantity = $quantity;
        $batchSelectSql = "SELECT batchId, quantity FROM batchItem WHERE productId = ? ORDER BY dateAdded ASC";
        $batchStmt = $conn->prepare($batchSelectSql);
        $batchStmt->bind_param("i", $productId);
        $batchStmt->execute();
        $batchResult = $batchStmt->get_result();

        if ($batchResult->num_rows === 0) {
            // No batches found for the product
            $_SESSION['message'] = "Error: No batches found for product ID $productId.";
            $_SESSION['alert_class'] = "alert-danger";
            header("Location: addCredit.php");
            exit();
        }

        while ($batchRow = $batchResult->fetch_assoc()) {
            $batchId = $batchRow['batchId'];
            $batchQuantity = $batchRow['quantity'];

            if ($batchQuantity >= $remainingQuantity) {
                $newBatchQuantity = $batchQuantity - $remainingQuantity;
                $updateBatchSql = "UPDATE batchItem SET quantity = ? WHERE batchId = ?";
                $updateBatchStmt = $conn->prepare($updateBatchSql);
                $updateBatchStmt->bind_param("ii", $newBatchQuantity, $batchId);
                $updateBatchStmt->execute();

                // Insert credit item for the batch
                $saleItemSql = "INSERT INTO sale_item (quantity, price, subTotal, productId, creditId, batchId) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($saleItemSql);
                $stmt->bind_param("iddiii", $remainingQuantity, $price, $subTotal, $productId, $creditId, $batchId);
                $stmt->execute();

                $remainingQuantity = 0;
                break;
            } else {
                $remainingQuantity -= $batchQuantity;
                $updateBatchSql = "UPDATE batchItem SET quantity = 0 WHERE batchId = ?";
                $updateBatchStmt = $conn->prepare($updateBatchSql);
                $updateBatchStmt->bind_param("i", $batchId);
                $updateBatchStmt->execute();

                // Insert credit item for the batch
                $saleItemSql = "INSERT INTO sale_item (quantity, price, subTotal, productId, creditId, batchId) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($saleItemSql);
                $stmt->bind_param("iddiii", $batchQuantity, $price, $subTotal, $productId, $creditId, $batchId);
                $stmt->execute();
            }
        }

        // If there are remaining quantities that couldn't be fulfilled by the batches
        if ($remainingQuantity > 0) {
            $_SESSION['message'] = "Error: Not enough stock for product ID $productId.";
            $_SESSION['alert_class'] = "alert-danger";
            header("Location: addCredit.php");
            exit();
        }
    }

    // Redirect to credits page with success message
    $_SESSION['message'] = "Credit recorded successfully!";
    $_SESSION['alert_class'] = "alert-success";
    header("Location: credit.php");
    exit();
}
?>