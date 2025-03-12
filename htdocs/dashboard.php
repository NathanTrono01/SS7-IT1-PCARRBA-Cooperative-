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
                  FROM inventory 
                  WHERE totalStock < reorderLevel";
$low_stock_result = $conn->query($low_stock_sql);
$low_stock_alerts = $low_stock_result->fetch_assoc()['low_stock_alerts'] ?? 0; // Add 0 if no value

// Fetch low stock products including out-of-stock products
$low_stock_products_sql = "SELECT p.productName, i.totalStock AS quantity, i.reorderLevel 
                           FROM inventory i 
                           JOIN products p ON i.productId = p.productId 
                           WHERE i.totalStock <= i.reorderLevel OR i.totalStock = 0";
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
            touch-action: manipulation; /* Improves touch behavior */
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

        .barchart-container {
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

        #stockBarChart {
            width: 100% !important;
            height: auto !important;
            max-height: 300px;
        }

        .restock-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
            position: relative;
            /* Add this */
        }

        .restock-card {
            background: rgb(31, 32, 36);
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            justify-content: space-between;
            width: 100%;
            margin-top: 10px;
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
            padding: 7px;
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

        .date-range-picker input,
        select {
            background: transparent;
            border: 1px solid #e0e0e0;
            color: rgba(224, 224, 224, 0.68);
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 14px;
            width: 150px;
        }


        .date-range-picker option {
            background: #1f2024;
            border: 1px solid #e0e0e0;
            color: rgba(224, 224, 224, 0.68);
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 14px;
            width: 150px;
        }

        .date-range-picker select:hover {
            color: white;
        }


        .date-range-picker input::placeholder {
            color: rgba(224, 224, 224, 0.68);
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

        .welcome-message {
            font-family: "Builder Sans", Helvetica, Arial, san-serif;
            font-weight: 800;
            font-size: 30px;
            line-height: 135%;
            text-decoration: none;
            font-style: normal;
            color: #ffffff;
            margin-bottom: 1.5rem;
            padding: 0.5rem 0;
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive welcome message styling */
        @media (max-width: 768px) {
            .welcome-message {
                font-size: 24px;
                text-align: center;
                padding: 0.5rem 1rem;
                margin: 0.5rem auto 1.5rem;
            }
        }

        /* Modify existing chart options to enhance dots for mobile */
        canvas {
            max-width: 100%;
            height: auto !important;
            touch-action: manipulation; /* Improves touch behavior */
        }

        /* Custom scrollbar styling */
        .scrollable-restocks::-webkit-scrollbar {
            width: 6px;
            display: block; /* Show scrollbar for better UX */
        }

        .scrollable-restocks::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .scrollable-restocks::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {

            .welcome-message {
                font-family: "Builder Sans", Helvetica, Arial, san-serif;
                font-weight: 800;
                font-size: 20px;
                line-height: 135%;
                text-decoration: none;
                font-style: normal;
                text-align: center;
            }

            .restock-card h4 {
                font-size: 1rem;
                /* Adjust the font size as needed */
            }

            .restock-card p {
                font-size: 0.85rem;
                /* Adjust the font size as needed */
            }

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
            .barchart-container {
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
            position: relative;
            /* Ensures child absolute elements stay inside */
            overflow-y: auto;
            /* Enables scrolling */
            height: 250px;
            /* Define a height for scrolling */
            padding-bottom: 30px;
            /* Prevent content from getting covered by the gradient */
        }

        .scrollable-restocks::-webkit-scrollbar {
            width: 0;
            /* Hide scrollbar */
        }

        .scrollable-restocks.no-blur::after {
            opacity: 0;
        }

        .title-link {
            display: flex;
            font-family: "Builder Sans", Helvetica, Arial, san-serif;
            font-weight: 800;
            font-size: 20px;
            line-height: 120%;
            text-decoration: none;
            font-style: normal;
            align-items: center;
            justify-content: flex-start;
            flex-direction: row;
            flex-wrap: nowrap;
            margin-bottom: 15px;
        }

        .title-link img {
            width: 24px;
            height: 24px;
            margin-left: 5px;
        }

        .title-link img:hover {
            width: 24px;
            height: 24px;
            border-radius: 300px;
            background-color: rgba(187, 188, 190, 0.2);
            transition: background 0.3s, color 0.3s;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/flatpickr.js"></script>
    <script src="js/chart.js"></script>

    <div class="main-content fade-in">
        <div class="dashboard-wrapper">
            <span class="welcome-message"><?php echo $_SESSION['welcome_message']; ?></span>
            <br>
            <div class="title-link">
                <span><b>Overview</b></span>
            </div>
            <div class="status-cards">
                <div class="card1 total-inventory">
                    <i class="fas fa-boxes"></i>
                    <h2>Total Stock</h2>
                    <p><?php echo $total_inventory ?: 'Out of Stock'; ?></p>
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
                <div class="title-link">
                    <span><b>Restock Alert</b></span>
                    <a href="reports.php?tab=product">
                        <img src="images/arrow-right.png" alt="Another Image" class="btn-back" id="another-image">
                    </a>
                </div>
                <div class="restock-container scrollable-restocks" id="lowStockContainer">
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
                                <a href="restock.php?product=<?php echo urlencode($product['productName']); ?>" class="view-product">Restock</a>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <br>
            <div class="title-link">
                <span><b>Snapshot</b></span>
                <a href="reports.php?tab=revenue">
                    <img src="images/arrow-right.png" alt="Another Image" class="btn-back" id="another-image">
                </a>
            </div>
            <div class="dashboard-container">
                <!-- Line Chart Card -->
                <div class="card2 chart-container">
                    <h2>Sales Graph</h2>
                    <!-- Range Selector -->
                    <div class="date-range-picker">
                        <select id="rangeSelector" onchange="handleRangeSelection()">
                            <option value="1">Last Day</option>
                            <option value="7" selected>Last 7 Days</option>
                            <option value="30">Last 30 Days</option>
                            <option value="custom">Custom</option>
                        </select>
                        <div id="customRange" style="display: none;">
                            <input type="text" id="startDate" placeholder="Start Date">
                            <p>to</p>
                            <input type="text" id="endDate" placeholder="End Date">
                        </div>
                        <button onclick="filterSalesData()">Apply</button>
                    </div>
                    <canvas id="salesChart"></canvas>
                </div>

                <!-- Bar Chart Card -->
                <div class="card2 chart-container">
                    <h2>Stock Status</h2>
                    <canvas id="stockBarChart"></canvas>
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

                // Initialize the line chart (Sales Trends) with larger points
                const salesCtx = document.getElementById("salesChart").getContext("2d");
                window.salesChart = new Chart(salesCtx, {
                    type: "line",
                    data: {
                        labels: <?php echo json_encode($sales_dates); ?>.map(date => {
                            const options = {
                                month: 'short',
                                day: 'numeric'
                            };
                            return new Date(date).toLocaleDateString('en-US', options);
                        }),
                        datasets: [{
                            label: "Total Sales",
                            data: <?php echo json_encode($sales_totals); ?>,
                            borderColor: "rgb(43, 114, 255)",
                            backgroundColor: "rgba(43, 114, 255, 0.1)",
                            fill: true,
                            pointBackgroundColor: "rgb(43, 114, 255)",
                            pointBorderColor: "#fff",
                            pointHoverBackgroundColor: "#fff",
                            pointHoverBorderColor: "rgb(43, 114, 255)",
                            pointRadius: 6, // Larger point size
                            pointHoverRadius: 8, // Even larger on hover
                            pointBorderWidth: 2, // Border for better definition
                            tension: 0.3 // Slightly curved line
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false, // Makes it easier to interact with points
                            mode: 'index' // Shows all values at a given x-index
                        },
                        hitRadius: 12, // Larger hit detection area for mobile taps
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: "rgba(255, 255, 255, 0.1)"
                                },
                                ticks: {
                                    color: "rgba(255, 255, 255, 0.7)" // Better visibility
                                }
                            },
                            x: {
                                grid: {
                                    color: "rgba(255, 255, 255, 0.1)"
                                },
                                ticks: {
                                    color: "rgba(255, 255, 255, 0.7)", // Better visibility
                                    maxRotation: 45, // Better readability on mobile
                                    minRotation: 45
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                bodyFont: {
                                    size: 14
                                },
                                callbacks: {
                                    label: function(context) {
                                        return `₱${context.raw.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                                    }
                                }
                            }
                        }
                    }
                });

                // Initialize the bar chart with similar improvements
                const barCtx = document.getElementById("stockBarChart").getContext("2d");
                new Chart(barCtx, {
                    type: "bar",
                    data: {
                        labels: <?php echo json_encode($product_names); ?>,
                        datasets: [{
                            label: "Total Stock",
                            data: <?php echo json_encode($product_stocks); ?>,
                            backgroundColor: <?php echo json_encode($colors); ?>,
                            borderWidth: 1,
                            borderColor: "#ffffff40"
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: "rgba(255, 255, 255, 0.1)"
                                },
                                ticks: {
                                    color: "rgba(255, 255, 255, 0.7)"
                                }
                            },
                            x: {
                                grid: {
                                    color: "rgba(255, 255, 255, 0.1)"
                                },
                                ticks: {
                                    color: "rgba(255, 255, 255, 0.7)",
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12
                            }
                        }
                    }
                });

                // Load initial data for the line chart (Past 7 Days)
                handleRangeSelection();
            });

            // Handle range selection
            function handleRangeSelection() {
                const rangeSelector = document.getElementById("rangeSelector");
                const customRange = document.getElementById("customRange");
                const startDateInput = document.getElementById("startDate");
                const endDateInput = document.getElementById("endDate");

                if (rangeSelector.value === "custom") {
                    customRange.style.display = "flex";
                } else {
                    customRange.style.display = "none";
                    const days = parseInt(rangeSelector.value);
                    const endDate = new Date();
                    const startDate = new Date();
                    startDate.setDate(endDate.getDate() - days);

                    // Set the date inputs
                    startDateInput._flatpickr.setDate(startDate);
                    endDateInput._flatpickr.setDate(endDate);

                    // Automatically apply the filter
                    filterSalesData();
                }
            }

            // Filter sales data based on the selected range
            function filterSalesData() {
                const startDate = document.getElementById("startDate").value;
                const endDate = document.getElementById("endDate").value;

                fetch(`filter_sales.php?startDate=${startDate}&endDate=${endDate}`)
                    .then(response => response.json())
                    .then(data => {
                        // Format dates to "Month, Day / Feb 12"
                        const formattedDates = data.dates.map(date => {
                            const options = {
                                month: 'short',
                                day: 'numeric'
                            };
                            return new Date(date).toLocaleDateString('en-US', options);
                        });

                        // Update the line chart with new data
                        window.salesChart.data.labels = formattedDates;
                        window.salesChart.data.datasets[0].data = data.totals;
                        window.salesChart.update();
                    })
                    .catch(error => console.error("Error fetching sales data:", error));
            }
        </script>
    </div>
</body>

</html>