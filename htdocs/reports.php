<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
include 'db.php';

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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Reports</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <link rel="stylesheet" href="css/sales.css">
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
            padding: 10px;
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
        }

        table a {
            text-decoration: none;
        }
    </style>
</head>

<?php include 'navbar.php'; ?>
<script src="js/bootstrap.bundle.min.js"></script>

<body>
    <div class="main-content fade-in">
        <div class="container">
            <h1>Reports</h1>

            <!-- Navigation Tabs -->
            <div class="tabs-container">
                <div class="tabs">
                    <div class="tab" onclick="showTab('sales')">Sales Report</div>
                    <div class="tab" onclick="showTab('inventory')">Inventory Report</div>
                    <div class="tab" onclick="showTab('revenue')">Revenue</div>
                    <div class="tab" onclick="showTab('items')">Sold Items Table</div>
                    <div class="tab" onclick="showTab('download')">Download</div>
                </div>
            </div>

            <!-- Tab Contents -->

            <div id="sales" class="content">
            </div>

            <div id="inventory" class="content">
            </div>

            <div id="revenue" class="content">
                <h2>Credits</h2>
                <p>Here is the credits information...</p>
            </div>

            <div id="items" class="content">
                <br>
                <h2>Sale Items Data Tables</h2>
                <div class="table-wrapper">
                    <table id="saleItemTable">
                        <thead>
                            <h3 align="center"> SOLD ITEMS </h3>
                            <tr>
                                <th>Sale ID</th>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
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
                                            <td><?php echo htmlspecialchars($sale_item['saleId']); ?></td>
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
                                <th>Credit ID</th>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
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
                                            <td><?php echo htmlspecialchars($sale_item['creditId']); ?></td>
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
                <h2>Revenue</h2>
                <p>Here is the revenue information...</p>
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

</body>

</html>