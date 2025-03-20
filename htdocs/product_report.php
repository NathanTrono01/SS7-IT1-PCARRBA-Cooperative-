<?php
include 'db.php';

// Check if this is an AJAX request
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $range = isset($_GET['range']) ? (int)$_GET['range'] : 5;
    $sql = "
        SELECT p.productName, SUM(si.quantity) AS quantity_sold
        FROM sale_item si
        JOIN products p ON si.productId = p.productId
        GROUP BY p.productName
        ORDER BY quantity_sold DESC
        LIMIT $range
    ";
    $result = $conn->query($sql);
    $labels = [];
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['productName'];
            $data[] = $row['quantity_sold'];
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['labels' => $labels, 'data' => $data]);
    exit;
}

// Keep the specific queries for this page
$range = isset($_GET['range']) ? (int)$_GET['range'] : 5;
$most_sold_products_sql = "
    SELECT p.productName, SUM(si.quantity) AS quantity_sold
    FROM sale_item si
    JOIN products p ON si.productId = p.productId
    GROUP BY p.productName
    ORDER BY quantity_sold DESC
    LIMIT $range
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

        .dashboard-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 10px;
            width: 100%;
            max-width: 100vw;
            /* Set max width to viewport width */
            overflow-x: hidden;
        }

        .chart-container {
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            height: auto;
            /* Fixed height to prevent infinite expansion */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-wrap: nowrap;
            align-items: center;
        }

        #mostSoldChart {
            width: 100% !important;
            height: auto !important;
            max-height: 300px;
        }

        /* Add these styles to the existing <style> block in product_report.php */
        .range-selector {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: nowrap;
            flex-direction: column;
        }

        .range-selector label {
            color: rgba(224, 224, 224, 0.68);
            font-size: .8rem;
            font-weight: bold;
            margin-bottom: 5px;
            margin-left: 10px;
        }

        .range-selector select {
            background: rgba(43, 45, 49, 0.8);
            border: 1px solid #3a3a3a;
            color: rgba(224, 224, 224, 0.9);
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .range-selector select:hover,
        .range-selector select:focus {
            border-color: #4b73b9;
            color: white;
            box-shadow: 0 0 5px rgba(43, 114, 255, 0.3);
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
            <br>
            <div class="range-selector">
                <label for="rangePicker">Range</label>
                <select id="rangePicker">
                    <option value="5" <?php echo ($range == 5) ? 'selected' : ''; ?>>Top 5</option>
                    <option value="10" <?php echo ($range == 10) ? 'selected' : ''; ?>>Top 10</option>
                    <option value="20" <?php echo ($range == 20) ? 'selected' : ''; ?>>Top 20</option>
                </select>
            </div>
            <div class="dashboard-container">
                <div class="card2 chart-container">
                    <h3>Top <?php echo $range; ?> Most Sold Products</h3>
                    <canvas id="mostSoldChart"></canvas>
                </div>
            </div>

            <br>
            <div>
                <h3>Product Movement</h3>
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
                <div class="pagination">
                    <button id="prevPage" onclick="changePage(-1)"><img src="images/arrow-left.png" alt=""></button>
                    <span>Page</span>
                    <input type="number" id="pageInput" value="1" min="1" onchange="goToPage(this.value)">
                    <span id="pageInfo"></span>
                    <button id="nextPage" onclick="changePage(1)"><img src="images/arrow-right.png" alt=""></button>
                </div>
            </div>
            <script src="js/chart.js"></script>
            <script>
                // Replace initial definitions with reversed order
                const mostSoldProducts = <?php echo json_encode(array_reverse(array_column($most_sold_products, 'productName'))); ?>;
                const soldQuantities = <?php echo json_encode(array_reverse(array_column($most_sold_products, 'quantity_sold'))); ?>;

                const ctx = document.getElementById('mostSoldChart').getContext('2d');
                window.mostSoldChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: mostSoldProducts,
                        datasets: [{
                            label: 'Units Sold',
                            data: soldQuantities,
                            backgroundColor: 'rgba(43, 114, 255, 0.7)',
                            borderColor: 'rgba(43, 114, 255, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            </script>
            <script>
                // When the range picker changes, fetch new data via AJAX
                document.getElementById("rangePicker").addEventListener("change", function() {
                    const range = this.value;
                    // Note: change URL from reports.php to product_report.php
                    fetch("product_report.php?ajax=1&range=" + range)
                        .then(response => response.json())
                        .then(data => {
                            // Update chart title
                            document.querySelector(".card2.chart-container h3").innerText = "Top " + range + " Most Sold Products";
                            // Update the chart data with reversed arrays to display increasing order (left lowest, right highest)
                            window.mostSoldChart.data.labels = data.labels.slice().reverse();
                            window.mostSoldChart.data.datasets[0].data = data.data.slice().reverse();
                            window.mostSoldChart.update();
                        })
                        .catch(err => console.error("AJAX Error:", err));
                    });
            </script>
            <script>
                // Create pagination for the product movement table in product_report.php
                document.addEventListener('DOMContentLoaded', () => {
                    // Only run this code if we're in the product report tab
                    if (document.getElementById('productMovementTable')) {
                        const movementPagination = createPagination("productMovementTable", "movement");
                        movementPagination.updatePagination();
                    }
                });
            </script>
            <br>
        </div>
    </div>
</body>

</html>