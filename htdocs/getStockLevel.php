<?php
include 'db.php';

if (isset($_GET['item_id'])) {
    $productId = $_GET['item_id'];
    $sql = "SELECT stockLevel FROM products WHERE productId = '$productId'";
    $result = mysqli_query($conn, $sql);
    $item = mysqli_fetch_assoc($result);

    if ($item) {
        echo $item['stockLevel'];
    } else {
        echo "Item not found.";
    }
}
?>