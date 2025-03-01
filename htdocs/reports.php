<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
include 'db.php';
include 'datetime.php';

// Fetch all sale items with sale ID or credit ID
$query = "
    SELECT 
        si.sale_itemId, 
        si.quantity, 
        si.price, 
        si.subTotal, 
        si.saleId, 
        si.creditId,
        p.productName, 
        p.unit
    FROM 
        sale_item si
    LEFT JOIN 
        products p ON si.productId = p.productId
";
$result = $conn->query($query);
$sale_items = $result->fetch_all(MYSQLI_ASSOC);

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
    <title>Reports</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/reports.css">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #181818;
            color: white;
            text-align: center;
            margin: 0;
            padding: 0;
        }

        .tabs-container {
            overflow-x: auto;
            white-space: nowrap;
            width: 100%;
        }

        .tabs-container::-webkit-scrollbar {
            height: 8px;
        }

        .tabs-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .tabs-container::-webkit-scrollbar-thumb {
            background: transparent;
            border-radius: 4px;
        }

        .tabs-container::-webkit-scrollbar-thumb:hover {
            background: transparent;
        }

        .tabs {
            display: inline-flex;
            border-bottom: 2px solid #555;
            width: 100%;
        }

        .tab {
            padding: 7px 25px;
            cursor: pointer;
            color: #aaa;
            text-align: center;
            white-space: nowrap;
            gap: 10px;
        }

        .tab.active {
            color: white;
            border-bottom: 2px solid white;
        }

        .content {
            display: none;
        }

        .content.active {
            display: block;
        }

        .table-wrapper {
            overflow-x: auto;
            margin: 20px 0;
        }

        .table-wrapper::-webkit-scrollbar {
            height: 8px;
        }

        .table-wrapper::-webkit-scrollbar-track {
            background: transparent;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background: transparent;
            border-radius: 4px;
        }

        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: transparent;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #333;
            color: white;
        }

        tr:nth-child(odd) {
            background-color: #272930;
        }

        tr:nth-child(even) {
            background-color: rgb(17, 18, 22);
        }

        @media (max-width: 1024px) {
            .content h2 {
                text-align: center;
            }

            .main-content {
                padding: 10px;
            }

            .tab {
                flex: 1 1 auto;
            }

            th,
            td {
                padding: 5px;
            }

            .container {
                padding: 0 20px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 10px;
            }

            .tab {
                flex: 1 1 auto;
            }

            th,
            td {
                padding: 5px;
            }

            .container {
                padding: 0 20px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 10px;
            }

            .tab {
                flex: 1 1 auto;
            }

            th,
            td {
                padding: 3px;
                font-size: 0.9rem;
            }

            .container {
                padding: 0 20px;
            }
        }

        table a {
            text-decoration: none;
        }

        /* FOR DASHBOARDS */

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
            color: green;
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

        table th {
            background-color: rgb(17, 18, 22);
            border-bottom: 1px solid #333;
            padding-bottom: 20px;
        }

        table td {
            padding: 10px;
            text-align: left;
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

        /* Responsive Design */
        @media (max-width: 768px) {

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
            height: 500px;
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

        .welcome-message {
            font-family: "Builder Sans", Helvetica, Arial, san-serif;
            font-weight: 800;
            font-size: 30px;
            line-height: 135%;
            text-decoration: none;
            font-style: normal;
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination button {
            background: rgb(43, 114, 255);
            border: none;
            color: #fff;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .pagination button:hover {
            background: rgb(82, 139, 255);
        }

        .pagination input {
            width: 50px;
            text-align: center;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #fff;
            color: #000;
        }

        .pagination span {
            font-size: 14px;
            color: #fff;
        }
    </style>
</head>


<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>
    <div class="main-content fade-in">
        <div class="container">
            <h1>Reports</h1>

            <!-- Navigation Tabs -->
            <div class="tabs-container">
                <div class="tabs">
                    <div class="tab" onclick="showTab('sales')">Sales Report</div>
                    <div class="tab" onclick="showTab('product')">Product Report</div>
                    <div class="tab" onclick="showTab('revenue')">Revenue</div>
                    <div class="tab" onclick="showTab('items')">Sold Items Table</div>
                    <div class="tab" onclick="showTab('download')">Download</div>
                </div>
            </div>

            <!-- Tab Contents -->

            <div id="sales" class="content active">
                <div class="main-content">
                    <h2>Sales Report</h2>
                    <p>Here is the Sales information...</p>
                </div>
            </div>

            <div id="product" class="content">
                <div class="main-content">
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
                                <p>₱ <?php echo array_sum(array_column($sale_items, 'subTotal')); ?></p>
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
                                <button id="prevPage" onclick="changePage(-1)">&#9664;</button>
                                <span>Page</span>
                                <input type="number" id="pageInput" value="1" min="1" onchange="goToPage(this.value)">
                                <span id="pageInfo"></span>
                                <button id="nextPage" onclick="changePage(1)">&#9654;</button>
                            </div>
                        </div>
                        <br>
                        <div>
                            <h3>Low Stock Alert</h3>
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
                    </div>
                </div>
            </div>

            <div id="revenue" class="content">
                <div class="main-content">
                    <h2>Revenue</h2>
                    <p>Here is the Revenue information...</p>
                </div>

            </div>

            <div id="items" class="content">
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

            <div id="download" class="content">
                <div class="main-content">
                    <h2>Download</h2>
                    <p>Here is the download information...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabId) {
            // Hide all content
            document.querySelectorAll('.content').forEach(c => c.classList.remove('active'));
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            // Show selected content
            document.getElementById(tabId).classList.add('active');
            // Highlight selected tab
            document.querySelector(`.tab[onclick="showTab('${tabId}')"]`).classList.add('active');
        }

        function groupRowsById(tableId, dataAttribute) {
            const table = document.getElementById(tableId);
            const rows = table.querySelectorAll('tbody tr');
            const colors = ['rgb(17, 18, 22)', '#272930'];
            let currentColorIndex = 0;
            let lastId = null;

            rows.forEach(row => {
                const id = row.getAttribute(dataAttribute);
                if (id !== lastId) {
                    currentColorIndex = (currentColorIndex + 1) % colors.length;
                    lastId = id;
                }
                row.style.backgroundColor = colors[currentColorIndex];
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab') || 'sales';
            showTab(activeTab);
            groupRowsById('saleItemTable', 'data-sale-id');
            groupRowsById('creditItemTable', 'data-credit-id');
        });
    </script>
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

            // Initialize the line chart (Sales Trends)
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
                        backgroundColor: "transparent",
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true, // Ensure the y-axis starts at 0
                            grid: {
                                color: "rgba(255, 255, 255, 0.1)" // Change grid line color
                            }
                        },
                        x: {
                            grid: {
                                color: "rgba(255, 255, 255, 0.1)" // Change grid line color
                            }
                        }
                    }
                }
            });

            // Initialize the bar chart (Stock Status Breakdown)
            const barCtx = document.getElementById("stockBarChart").getContext("2d");
            new Chart(barCtx, {
                type: "bar",
                data: {
                    labels: <?php echo json_encode($product_names); ?>,
                    datasets: [{
                        label: "Total Stock",
                        data: <?php echo json_encode($product_stocks); ?>,
                        backgroundColor: <?php echo json_encode($colors); ?>
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true, // Ensure the y-axis starts at 0
                            grid: {
                                color: "rgba(255, 255, 255, 0.1)" // Change grid line color
                            }
                        },
                        x: {
                            grid: {
                                color: "rgba(255, 255, 255, 0.1)" // Change grid line color
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
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

        const rowsPerPage = 5;
        let currentPage = 1;

        function paginateTable(tableId) {
            const table = document.getElementById(tableId);
            const rows = table.querySelectorAll('tbody tr');
            const totalRows = rows.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage);

            rows.forEach((row, index) => {
                row.style.display = (index >= (currentPage - 1) * rowsPerPage && index < currentPage * rowsPerPage) ? '' : 'none';
            });

            document.getElementById('pageInfo').textContent = `of ${totalPages}`;
            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage === totalPages;
            document.getElementById('pageInput').value = currentPage;
        }

        function changePage(direction) {
            currentPage += direction;
            paginateTable('productMovementTable');
        }

        function goToPage(page) {
            const totalPages = Math.ceil(document.querySelectorAll('#productMovementTable tbody tr').length / rowsPerPage);
            if (page >= 1 && page <= totalPages) {
                currentPage = parseInt(page);
                paginateTable('productMovementTable');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            paginateTable('productMovementTable');
        });
    </script>
</body>

</html>