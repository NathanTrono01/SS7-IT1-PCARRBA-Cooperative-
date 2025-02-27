<?php
function logAction($action, $productIds, $quantities, $userId, $conn)
{
    // Fetch product names and units from the database
    $productDetails = [];
    foreach ($productIds as $index => $productId) {
        $sql = "SELECT productName, unit FROM products WHERE productId = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $productName = $row['productName'];
            $unit = $row['unit'];
            $quantity = $quantities[$index];
            $productDetails[] = "$quantity $productName ($unit)";
        }
    }

    // Format the details based on the action
    switch ($action) {
        case 'Restock':
            $details = "Restocked " . implode(", ", $productDetails);
            break;
        case 'Sold':
            $details = "Sold " . implode(", ", $productDetails);
            break;
        case 'New Product':
            $details = "Added new product: " . implode(", ", $productDetails);
            break;
        case 'Credit':
            $details = "Recorded credit for " . implode(", ", $productDetails);
            break;
        case 'Edit Product':
            $details = "Edited product: " . implode(", ", $productDetails);
            break;
        default:
            $details = "Performed action: $action on " . implode(", ", $productDetails);
            break;
    }

    // Insert into audit_logs
    $timestamp = date('Y-m-d H:i:s'); // Current timestamp
    $sql = "INSERT INTO audit_logs (action, details, userId, timestamp) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("ssis", $action, $details, $userId, $timestamp);
    if (!$stmt->execute()) {
        die("Error executing statement: " . $stmt->error);
    }
}
?>