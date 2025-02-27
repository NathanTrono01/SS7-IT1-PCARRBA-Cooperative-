<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

// Fetch data for the dashboard
$current_date = date('Y-m-d');

// Fetch sales data for the chart
$sales_data_sql = "SELECT DATE(dateSold) as sale_date, SUM(totalPrice) as total_sales FROM sales GROUP BY DATE(dateSold) ORDER BY DATE(dateSold)";
$sales_data_result = $conn->query($sales_data_sql);

$sales_dates = [];
$sales_totals = [];

while ($row = $sales_data_result->fetch_assoc()) {
    $sales_dates[] = $row['sale_date'];
    $sales_totals[] = $row['total_sales'] ?? 0; // Add 0 if no sales
}

// Ensure there is at least one data point for the sales chart
if (empty($sales_dates)) {
    $sales_dates[] = $current_date;
    $sales_totals[] = 0;
}

// Fetch total inventory items
$total_inventory_sql = "SELECT SUM(quantity) AS total_inventory FROM batchItem";
$total_inventory_result = $conn->query($total_inventory_sql);
$total_inventory = $total_inventory_result->fetch_assoc()['total_inventory'] ?? 0; // Add 0 if no value

// Fetch total sales today
$total_sales_sql = "SELECT SUM(totalPrice) AS total_sales_today FROM sales WHERE DATE(dateSold) = ?";
$total_sales_stmt = $conn->prepare($total_sales_sql);
$total_sales_stmt->bind_param("s", $current_date);
$total_sales_stmt->execute();
$total_sales_result = $total_sales_stmt->get_result();
$total_sales_today = $total_sales_result->fetch_assoc()['total_sales_today'] ?? 0; // Add 0 if no value

// Fetch low stock alerts
$low_stock_sql = "SELECT COUNT(*) AS low_stock_alerts 
                  FROM inventory i 
                  JOIN batchItem b ON i.productId = b.productId 
                  WHERE b.quantity < i.reorderLevel";
$low_stock_result = $conn->query($low_stock_sql);
$low_stock_alerts = $low_stock_result->fetch_assoc()['low_stock_alerts'] ?? 0; // Add 0 if no value

// Fetch low stock products
$low_stock_products_sql = "SELECT p.productName, b.quantity, i.reorderLevel 
                           FROM inventory i 
                           JOIN batchItem b ON i.productId = b.productId 
                           JOIN products p ON i.productId = p.productId 
                           WHERE b.quantity < i.reorderLevel";
$low_stock_products_result = $conn->query($low_stock_products_sql);

$low_stock_products = [];
while ($row = $low_stock_products_result->fetch_assoc()) {
    $low_stock_products[] = $row;
}

// Fetch pending credits
$pending_credits_sql = "SELECT COUNT(*) AS pending_credits FROM credits WHERE paymentStatus = 'Unpaid'";
$pending_credits_result = $conn->query($pending_credits_sql);
$pending_credits = $pending_credits_result->fetch_assoc()['pending_credits'] ?? 0; // Add 0 if no value

// Fetch out-of-stock items
$out_of_stock_sql = "SELECT COUNT(*) AS out_of_stock FROM inventory WHERE totalStock = 0";
$out_of_stock_result = $conn->query($out_of_stock_sql);
$out_of_stock = $out_of_stock_result->fetch_assoc()['out_of_stock'] ?? 0; // Add 0 if no value

// Fetch stock levels for each product
$product_stock_sql = "SELECT p.productName, COALESCE(SUM(b.quantity), 0) AS totalStock 
                      FROM products p 
                      LEFT JOIN batchItem b ON p.productId = b.productId 
                      GROUP BY p.productName";
$product_stock_result = $conn->query($product_stock_sql);

$product_names = [];
$product_stocks = [];
$colors = [];

while ($row = $product_stock_result->fetch_assoc()) {
    $product_names[] = $row['productName'];
    $product_stocks[] = $row['totalStock'];
    $colors[] = sprintf('#%06X', mt_rand(0, 0xFFFFFF)); // Generate random color
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/flatpickr.min.css">
    <style>
        html,
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: #e0e0e0;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        .card1 {
            color: rgb(187, 188, 190);
            background: transparent;
            border: solid 1px rgb(67, 67, 67);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: 0.3s;
        }

        .card1:hover {
            border: solid 1px white;
            background: rgba(255, 255, 255, 0.04);
        }

        .card1 h2 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .card1 p {
            font-size: 2rem;
            font-weight: bold;
        }

        .card1 i {
            font-size: 3rem;
            margin-bottom: 10px;
            color: #e0e0e0;
        }

        .card1.total-inventory p {
            color: rgb(0, 72, 197);
        }

        .card1.total-sales p {
            color: rgb(44, 195, 49);
        }

        .card1.low-stock p {
            color: #F44336;
        }

        .card1.pending-credits p {
            color: rgb(231, 175, 55);
        }

        .status-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .status-cards .card1 {
            flex: 1 1 calc(25% - 20px);
        }

        .card2 {
            background: rgb(31, 32, 36);
            border: none;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: 0.3s;
        }

        .recent-restocks {
            width: 100%;
            max-width: 1200px;
            margin: 20px auto;
            background: transparent;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-height: 300px;
            /* Fixed height */
            overflow-y: auto;
            /* Scrollable */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 10px;
            border-bottom: 1px solid #333;
            text-align: left;
        }

        table th {
            background: #333;
        }

        canvas {
            max-width: 100%;
            height: auto !important;
        }

        .dashboard-wrapper {
            display: grid;
            gap: 10px;
            box-sizing: border-box;
            padding: 20px;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 10px;
            max-width: 1200px;
            overflow-x: hidden;
        }

        .chart-container {
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            height: auto;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-wrap: wrap;
            align-items: center;
        }

        #salesChart {
            width: 100% !important;
            height: auto !important;
            max-height: 300px;
        }

        .piechart-container {
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            height: auto;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-wrap: wrap;
            align-items: center;
        }

        #stockPieChart {
            width: 100% !important;
            height: auto !important;
            max-height: 300px;
        }

        .restock-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .restock-card {
            background: rgb(31, 32, 36);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            justify-content: space-between;
            width: 100%;
        }

        .restock-header {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-start;
        }

        .restock-date {
            font-size: 0.85rem;
            color: #bbb;
        }

        .restock-footer {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-end;
        }

        .view-product {
            padding: 4px;
            border-radius: 7px;
            color: rgb(43, 114, 255);
            text-decoration: none;
            font-weight: bold;
            margin-top: 5px;
        }

        .view-product:hover {
            background-color: rgba(255, 255, 255, 0.07);
            color: rgb(82, 139, 255);
            text-decoration: none;
            font-weight: bold;
            transition: 0.5s;
        }

        .dismiss-btn {
            background: transparent;
            border: none;
            color: red;
            font-size: 25px;
            cursor: pointer;
            margin-top: 5px;
        }

        /* Date Range Picker */
        .date-range-picker {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
            flex-direction: row;
            justify-content: center;
            align-content: center;
            flex-wrap: wrap;
        }

        .date-range-picker input {
            background: transparent;
            border: 1px solid #e0e0e0;
            color: #e0e0e0;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 14px;
            width: 150px;
        }

        .date-range-picker input::placeholder {
            color: #bbb;
        }

        .date-range-picker button {
            background: rgb(43, 114, 255);
            border: none;
            color: #fff;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .date-range-picker button:hover {
            background: rgb(82, 139, 255);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-wrapper {
                padding: 10px;
            }

            .status-cards,
            .dashboard-container {
                gap: 20px;
            }

            .dashboard-container {
                grid-template-columns: 1fr;
            }

            .chart-container,
            .piechart-container,
            .calendar-container {
                width: 100%;
            }

            .status-cards .card1 {
                flex: 1 1 100%;
            }

            .date-range-picker {
                flex-direction: column;
                align-items: stretch;
            }

            .date-range-picker input {
                width: 100%;
            }

            .date-range-picker button {
                width: 100%;
            }
        }

        .scrollable-restocks {
            max-height: 200px;
            /* Adjust the height as needed */
            overflow-y: auto;
            position: relative;
            padding-right: 15px;
            /* Space for scrollbar */
        }

        .scrollable-restocks::-webkit-scrollbar {
            width: 0;
            /* Hide scrollbar */
        }

        .scrollable-restocks::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            /* Adjust height as needed */
            background: linear-gradient(to bottom, rgba(17, 18, 22, 0), rgb(17, 18, 22, 1));
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .scrollable-restocks.no-blur::after {
            opacity: 0;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/flatpickr.js"></script>
    <script src="js/chart.js"></script>

    <div class="main-content">
        <div class="dashboard-wrapper">
            <h1>Overview</h1>
            <div class="status-cards">
                <div class="card1 total-inventory">
                    <i class="fas fa-boxes"></i>
                    <h2>Total Product/s</h2>
                    <p><?php echo $total_inventory ?: 'No Stock'; ?></p>
                </div>
                <div class="card1 total-sales">
                    <i class="fas fa-dollar-sign"></i>
                    <h2>Total Sales Today</h2>
                    <p><?php echo number_format($total_sales_today, 2) ?: '0.00'; ?> PHP</p>
                </div>
                <div class="card1 low-stock">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h2>Low Stock Product/s</h2>
                    <p><?php echo $low_stock_alerts; ?></p>
                </div>
                <div class="card1 pending-credits">
                    <i class="fas fa-credit-card"></i>
                    <h2>Unpaid Credits</h2>
                    <p><?php echo $pending_credits ?: 'None'; ?></p>
                </div>
            </div>

            <br>
            <div>
                <h2>Low Stock Products</h2>
                <div class="restock-container scrollable-restocks" id="lowStockContainer">
                    <?php foreach ($low_stock_products as $product) { ?>
                        <div class="restock-card">
                            <div class="restock-header">
                                <h4><?php echo $product['productName']; ?></h4>
                                <p>Quantity: <?php echo $product['quantity']; ?></p>
                                <p>Reorder Level: <?php echo $product['reorderLevel']; ?></p>
                                <a href="restock.php?product=<?php echo urlencode($product['productName']); ?>" class="view-product">Restock</a>
                            </div>
                            <div class="restock-footer">
                                <button class="dismiss-btn" onclick="dismissLowStock(this)">×</button>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <br>
            <h2>Snapshot</h2>
            <div class="dashboard-container">
                <!-- Line Chart Card -->
                <div class="card2 chart-container">
                    <h2>Sales Graph</h2>
                    <div class="date-range-picker">
                        <input type="text" id="startDate" placeholder="Start Date">
                        <p>to</p>
                        <input type="text" id="endDate" placeholder="End Date">
                        <button onclick="filterSalesData()">Apply</button>
                    </div>
                    <canvas id="salesChart"></canvas>
                </div>

                <!-- Pie Chart Card -->
                <div class="card2 piechart-container">
                    <h2>Stock Status</h2>
                    <canvas id="stockPieChart"></canvas>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Initialize Flatpickr for date range picker
                flatpickr("#startDate", {
                    dateFormat: "Y-m-d",
                    defaultDate: "<?php echo date('Y-m-d', strtotime('-7 days')); ?>"
                });

                flatpickr("#endDate", {
                    dateFormat: "Y-m-d",
                    defaultDate: "<?php echo date('Y-m-d'); ?>"
                });

                // Format dates to "Month, Day / Feb 12"
                const salesDates = <?php echo json_encode($sales_dates); ?>.map(date => {
                    const options = {
                        month: 'short',
                        day: 'numeric'
                    };
                    return new Date(date).toLocaleDateString('en-US', options);
                });

                // Line Chart (Sales Trends)
                let ctx = document.getElementById("salesChart").getContext("2d");
                const salesChart = new Chart(ctx, {
                    type: "line",
                    data: {
                        labels: salesDates,
                        datasets: [{
                            label: "Total Sales",
                            data: <?php echo json_encode($sales_totals); ?>,
                            borderColor: "rgb(43, 114, 255)",
                            backgroundColor: "transparent",
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true // Ensure the y-axis starts at 0
                            }
                        }
                    }
                });

                // Pie Chart (Stock Status Breakdown)
                let pieCtx = document.getElementById("stockPieChart").getContext("2d");
                new Chart(pieCtx, {
                    type: "pie",
                    data: {
                        labels: <?php echo json_encode($product_names); ?>,
                        datasets: [{
                            data: <?php echo json_encode($product_stocks); ?>,
                            backgroundColor: <?php echo json_encode($colors); ?>
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'left'
                            }
                        }
                    }
                });
            });

            function dismissLowStock(button) {
                button.closest('.low-stock-card').remove();
            }

            function expandLowStock() {
                const container = document.getElementById('lowStockContainer');
                container.style.maxHeight = container.style.maxHeight === 'none' ? '200px' : 'none';
            }

            function filterSalesData() {
                const startDate = document.getElementById("startDate").value;
                const endDate = document.getElementById("endDate").value;

                fetch(`filter_sales.php?startDate=${startDate}&endDate=${endDate}`)
                    .then(response => response.json())
                    .then(data => {
                        // Update the chart with new data
                        salesChart.data.labels = data.dates;
                        salesChart.data.datasets[0].data = data.totals;
                        salesChart.update();
                    });
            }
        </script>
    </div>
</body>

</html>