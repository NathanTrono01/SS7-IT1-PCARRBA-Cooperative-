// add_sale.php
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
include 'functions.php'; // Include the file where logAction is defined

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

    // Insert sale into the `sales` table
    $saleSql = "INSERT INTO sales (totalPrice, transactionType, dateSold, userId) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($saleSql);
    $stmt->bind_param("dssi", $totalPrice, $transactionType, $dateSold, $userId);
    $stmt->execute();
    $saleId = $stmt->insert_id;

    // Insert sale items into the `sale_item` table, update batch items, and update inventory totalStock
    foreach ($productIds as $index => $productId) {
        $quantity = $quantities[$index];
        $price = $prices[$index];
        $subTotal = $quantity * $price;
        $creditId = NULL; // Default value for cash transactions

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
            header("Location: sales.php");
            exit();
        }

        while ($batchRow = $batchResult->fetch_assoc()) {
            $batchId = $batchRow['batchId'];
            $batchQuantity = $batchRow['quantity'];

            if ($batchQuantity >= $remainingQuantity) {
                $newBatchQuantity = $batchQuantity - $remainingQuantity;

                // Update the batch quantity
                $updateBatchSql = "UPDATE batchItem SET quantity = ? WHERE batchId = ?";
                $updateBatchStmt = $conn->prepare($updateBatchSql);
                $updateBatchStmt->bind_param("ii", $newBatchQuantity, $batchId);
                $updateBatchStmt->execute();

                // If the new quantity is 0, delete the batch item
                if ($newBatchQuantity == 0) {
                    $deleteBatchSql = "DELETE FROM batchItem WHERE batchId = ?";
                    $deleteBatchStmt = $conn->prepare($deleteBatchSql);
                    $deleteBatchStmt->bind_param("i", $batchId);
                    $deleteBatchStmt->execute();
                }

                // Insert sale item for the batch
                $saleItemSql = "INSERT INTO sale_item (quantity, price, subTotal, productId, saleId, creditId, batchId) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($saleItemSql);
                $stmt->bind_param("iddiiii", $remainingQuantity, $price, $subTotal, $productId, $saleId, $creditId, $batchId);
                $stmt->execute();

                $remainingQuantity = 0;
                break;
            } else {
                $remainingQuantity -= $batchQuantity;

                // Set the batch quantity to 0 and delete the batch item
                $updateBatchSql = "UPDATE batchItem SET quantity = 0 WHERE batchId = ?";
                $updateBatchStmt = $conn->prepare($updateBatchSql);
                $updateBatchStmt->bind_param("i", $batchId);
                $updateBatchStmt->execute();

                // Delete the batch item
                $deleteBatchSql = "DELETE FROM batchItem WHERE batchId = ?";
                $deleteBatchStmt = $conn->prepare($deleteBatchSql);
                $deleteBatchStmt->bind_param("i", $batchId);
                $deleteBatchStmt->execute();

                // Insert sale item for the batch
                $saleItemSql = "INSERT INTO sale_item (quantity, price, subTotal, productId, saleId, creditId, batchId) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($saleItemSql);
                $stmt->bind_param("iddiiii", $batchQuantity, $price, $subTotal, $productId, $saleId, $creditId, $batchId);
                $stmt->execute();
            }
        }

        // If there are remaining quantities that couldn't be fulfilled by the batches
        if ($remainingQuantity > 0) {
            $_SESSION['message'] = "Error: Not enough stock for product ID $productId.";
            $_SESSION['alert_class'] = "alert-danger";
            header("Location: sales.php");
            exit();
        }

        // Update the totalStock in the inventory table
        $updateInventorySql = "UPDATE inventory SET totalStock = totalStock - ? WHERE productId = ?";
        $updateInventoryStmt = $conn->prepare($updateInventorySql);
        $updateInventoryStmt->bind_param("ii", $quantity, $productId);
        $updateInventoryStmt->execute();
    }

    // Log the sale action
    $action = "Sale";
    logAction($action, $productIds, $quantities, $userId, $conn);

    // Redirect to sales page with success message
    $_SESSION['message'] = "Sale recorded successfully!";
    $_SESSION['alert_class'] = "alert-success";
    header("Location: sales.php");
    exit();
}
?>