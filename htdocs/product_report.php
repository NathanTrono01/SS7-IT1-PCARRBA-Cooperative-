<?php
// Keep the specific queries for this page
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

// Calculate overall total cost of inventory using batchItem
$total_cost_query = "SELECT SUM(quantity * costPrice) AS total_cost FROM batchItem";
$total_cost_result = $conn->query($total_cost_query);
$total_cost = $total_cost_result->fetch_assoc()['total_cost'] ?? 0;
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
        
        /* Add these new styles */
        .scrollable-restocks {
            min-height: 100px;
            max-height: 300px;
            transition: min-height 0.3s ease;
        }
        
        .scrollable-restocks:empty {
            min-height: auto;
        }
        
        .restock-card:only-child {
            margin-bottom: 0;
        }
    </style>
</head>

<body>
    <div class="main-content fade-in">
        <div class="dashboard-wrapper">
            <h2>Overview</h2>
            <div class="button">
                <a href="restock.php" class="restock-button">Restock</a>
                <a href="insertProduct.php" class="button-product">Add Product</a>
            </div>
            <hr>
            <div class="status-cards">
                <div class="card1 total-inventory">
                    <i class="fas fa-boxes"></i>
                    <h2>Total Stock</h2>
                    <p><?php echo $total_inventory ?: 'Out of Stock'; ?></p>
                </div>
                <div class="card1 total-sales">
                    <i class="fas fa-dollar-sign"></i>
                    <h2>Total Sold Products</h2>
                    <p><?php echo array_sum(array_column($sale_items, 'quantity')); ?></p>
                </div>
                <div class="card1 low-stock">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h2>Total Low Stock</h2>
                    <p><?php echo $low_stock_alerts; ?></p>
                </div>
                <div class="card1 pending-credits">
                    <i class="fas fa-credit-card"></i>
                    <h2>Total Cost</h2>
                    <p><?php echo number_format($total_cost, 2); ?> PHP</p>
                </div>
            </div>
            <br>
            <div>
                <h3>Product Movement</h3>
                <div class="table-wrapper">
                    <table id="productMovementTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch product movements from audit logs
                            $product_movements_sql = "
                                SELECT a.timestamp, p.productName, a.action, a.details
                                FROM audit_logs a
                                JOIN products p ON a.details LIKE CONCAT('%', p.productName, '%')
                                ORDER BY a.timestamp DESC
                            ";
                            $product_movements_result = $conn->query($product_movements_sql);


                            if ($product_movements_result->num_rows > 0) {
                                while ($movement = $product_movements_result->fetch_assoc()) {
                                    $date = !empty($movement['timestamp'])
                                        ? date("n/j/y", strtotime($movement['timestamp'])) . "<br>" . date("g:i A", strtotime($movement['timestamp']))
                                        : 'N/A';

                                    echo "<tr>";
                                    echo "<td>" . $date . "</td>";
                                    echo "<td>" . htmlspecialchars($movement['productName']) . "</td>";
                                    echo "<td>" . htmlspecialchars($movement['action']) . "</td>";
                                    echo "<td>" . htmlspecialchars($movement['details']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' style='text-align: center;'>No product movements found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="pagination">
                    <button id="prevPage" onclick="changePage(-1)"><img src="images/arrow-left.png" alt=""></button>
                    <span>Page</span>
                    <input type="number" id="pageInput" value="1" min="1" onchange="goToPage(this.value)">
                    <span id="pageInfo"></span>
                    <button id="nextPage" onclick="changePage(1)"><img src="images/arrow-right.png" alt=""></button>
                </div>
            </div>

            <br>
            <div>
                <h3>Top 5 Most Sold Products</h3>
                <div class="restock-container scrollable-restocks" style="<?php echo count($most_sold_products) === 0 ? 'min-height: auto;' : ''; ?>">
                    <?php if (count($most_sold_products) > 0) { ?>
                        <?php foreach ($most_sold_products as $product) { ?>
                            <div class="restock-card">
                                <div class="restock-header">
                                    <p><img src="images/bestseller.png" alt="" style="width: 30px; height: 30px;"></p>
                                    <h4><?php echo htmlspecialchars($product['productName']); ?></h4>
                                    <p>Total Sold: <strong><?php echo $product['quantity_sold']; ?> units</strong></p>
                                </div>
                        </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="restock-card" style="margin-bottom: 0;">
                            <div class="restock-header">
                                <p>No sales data available yet.</p>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <br>
            <div>
                <h3>Stock Alert</h3>
                <div class="restock-container scrollable-restocks" id="lowStockContainer" style="<?php echo empty($low_stock_products) ? 'min-height: auto;' : ''; ?>">
                    <?php if (!empty($low_stock_products)) { ?>
                        <?php foreach ($low_stock_products as $product) { ?>
                            <div class="restock-card">
                                <div class="restock-header">
                                    <p><img src="images/alert.png" alt="" style="width: 30px; height: 30px;"></p>
                                    <h4>
                                        <?php if ($product['quantity'] == 0) { ?>
                                            Your "<?php echo $product['productName']; ?>" is out of stock!
                                        <?php } else { ?>
                                            Your "<?php echo $product['productName']; ?>" is low stock!
                                        <?php } ?>
                                    </h4>
                                    <p>Current Stock: <?php echo $product['quantity']; ?></p>
                                </div>
                                <div class="restock-footer">
                                    <td><a href="restock.php?product=<?php echo urlencode($product['productName']); ?>&tab=product" class="view-product">Restock</a></td>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="restock-card" style="margin-bottom: 0;">
                            <div class="restock-header">
                                <p>No low stock alerts at this time.</p>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>