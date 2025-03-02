<?php
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
// Include database connection
include 'db.php';

// Fetch top 5 most sold products
$most_sold_products_sql = "
    SELECT p.productName, SUM(si.quantity) AS quantity_sold
    FROM sale_item si
    JOIN products p ON si.productId = p.productId
    GROUP BY p.productName
    ORDER BY quantity_sold DESC
    LIMIT 5
";
$most_sold_products_result = $conn->query($most_sold_products_sql);
$most_sold_products = [];
if ($most_sold_products_result->num_rows > 0) {
    while ($row = $most_sold_products_result->fetch_assoc()) {
        $most_sold_products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Product Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <link rel="stylesheet" href="css/rep.css">
    <style>
        .most-sold-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            padding: 10px;
        }

        .most-sold-card {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 5px;
            background-color: rgb(31, 32, 36);
            text-align: center;
            width: 200px;
        }
    </style>
</head>

<body>
    <div class="main-content fade-in">
        <br>
        <h2>Sale Items Data Tables</h2>
        <div class="table-wrapper">
            <table id="saleItemTable">
                <thead>
                    <h3 align="center"> SOLD ITEMS </h3>
                    <tr>
                        <th>Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sale_items)) { ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No sale items found.</td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($sale_items as $sale_item) { ?>
                            <?php if (!empty($sale_item['saleId'])) { ?>
                                <tr data-sale-id="<?php echo htmlspecialchars($sale_item['saleId']); ?>">
                                    <td><?php echo htmlspecialchars($sale_item['productName']); ?></td>
                                    <td><?php echo htmlspecialchars($sale_item['quantity']) . (isset($sale_item['unit']) ? ' ' . htmlspecialchars($sale_item['unit']) : ''); ?></td>
                                    <td>₱ <?php echo htmlspecialchars($sale_item['price']); ?></td>
                                    <td>₱ <?php echo htmlspecialchars($sale_item['subTotal']); ?></td>
                                    <td><a href="sale_details.php?saleId=<?php echo htmlspecialchars($sale_item['saleId']); ?>&tab=items">View Details</a></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
            <br>
            <table id="creditItemTable">
                <h3 align="center"> CREDIT ITEMS </h3>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sale_items)) { ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No credit items found.</td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($sale_items as $sale_item) { ?>
                            <?php if (!empty($sale_item['creditId'])) { ?>
                                <tr data-credit-id="<?php echo htmlspecialchars($sale_item['creditId']); ?>">
                                    <td><?php echo htmlspecialchars($sale_item['productName']); ?></td>
                                    <td><?php echo htmlspecialchars($sale_item['quantity']) . (isset($sale_item['unit']) ? ' ' . htmlspecialchars($sale_item['unit']) : ''); ?></td>
                                    <td>₱ <?php echo htmlspecialchars($sale_item['price']); ?></td>
                                    <td>₱ <?php echo htmlspecialchars($sale_item['subTotal']); ?></td>
                                    <td><a href="credit_details.php?creditId=<?php echo htmlspecialchars($sale_item['creditId']); ?>&tab=items">View Details</a></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>