<?php
// Set default date range to last 7 days
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-7 days'));

if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
}

// Fetch total sales revenue
$total_sales_sql = "SELECT SUM(totalPrice) AS total_sales_revenue FROM sales WHERE DATE(dateSold) BETWEEN '$start_date' AND '$end_date'";
$total_sales_result = $conn->query($total_sales_sql);
$total_sales_revenue = $total_sales_result->fetch_assoc()['total_sales_revenue'] ?? 0;

// Fetch total transactions
$total_transactions_sql = "SELECT COUNT(*) AS total_transactions FROM sales WHERE DATE(dateSold) BETWEEN '$start_date' AND '$end_date'";
$total_transactions_result = $conn->query($total_transactions_sql);
$total_transactions = $total_transactions_result->fetch_assoc()['total_transactions'] ?? 0;

// Fetch total items sold
$total_items_sold_sql = "SELECT SUM(si.quantity) AS total_items_sold 
                         FROM sale_item si 
                         LEFT JOIN sales s ON si.saleId = s.saleId 
                         WHERE (s.saleId IS NULL OR DATE(s.dateSold) BETWEEN '$start_date' AND '$end_date')";
$total_items_sold_result = $conn->query($total_items_sold_sql);
$total_items_sold = $total_items_sold_result->fetch_assoc()['total_items_sold'] ?? 0;

// Calculate average sale value
$average_sale_value = $total_transactions > 0 ? $total_sales_revenue / $total_transactions : 0;

// CORRECTED: Fetch total cost of goods sold (COGS) based on items actually sold in the period
// This joins sale_item to get sold quantities and matches with the average cost price of those products
$total_cogs_sql = "
    SELECT SUM(si.quantity * IFNULL(avg_cost.avg_cost_price, 0)) AS total_cogs
    FROM sale_item si
    JOIN sales s ON si.saleId = s.saleId
    LEFT JOIN (
        SELECT 
            productId, 
            AVG(costPrice) as avg_cost_price
        FROM 
            batchItem
        GROUP BY 
            productId
    ) avg_cost ON si.productId = avg_cost.productId
    WHERE DATE(s.dateSold) BETWEEN '$start_date' AND '$end_date'
";
$total_cogs_result = $conn->query($total_cogs_sql);
$total_cogs = $total_cogs_result->fetch_assoc()['total_cogs'] ?? 0;

// Calculate gross profit
$gross_profit = $total_sales_revenue - $total_cogs;

// Assume expenses are stored in a variable (if any)
$expenses = 0; // Replace with actual expenses if available

// Calculate net profit
$net_profit = $gross_profit - $expenses;

$data = [
    'total_sales_revenue' => $total_sales_revenue,
    'total_transactions' => $total_transactions,
    'total_items_sold' => $total_items_sold,
    'average_sale_value' => $average_sale_value,
    'total_cogs' => $total_cogs,
    'gross_profit' => $gross_profit,
    'net_profit' => $net_profit,
];

if (isset($_GET['ajax'])) {
    echo json_encode($data);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Sale Summary</title>
    <script src="js/flatpickr.js"></script>
    <link rel="stylesheet" href="css/flatpickr.min.css">
    <style>
        body {
            background-color: #0d0d0d;
            color: white;
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: auto;
        }

        .summary {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }

        .header {
            font-weight: bold;
            border-bottom: 2px solid gray;
            padding-top: 5px;
        }

        .total {
            font-weight: bold;
            padding-top: 5px;
        }

        .date-header {
            color: rgba(224, 224, 224, 0.68);
            font-size: .8rem;
            font-weight: bold;
            margin-bottom: 5px;
            margin-left: 10px;
        }

        .date-range-picker {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
            flex-direction: row;
            justify-content: start;
            align-content: center;
            flex-wrap: nowrap;
        }

        .date-range-picker input,
        select {
            background: transparent;
            border: 1px solid #e0e0e0;
            color: rgba(224, 224, 224, 0.68);
            padding: 8px 12px;
            border-radius: 10px;
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

        .date-range-picker:hover .date-header,
        .date-range-picker:hover select,
        .date-range-picker:hover select~.date-header {
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

        /* Add or modify styles for custom range container */
        #customRange {
            display: none;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
    </style>
</head>

<body>
    <div class="main-content fade-in">
        <div class="container">
            <span class="date-header">Date Range</span>
            <div class="date-range-picker">
                <select id="rangeSelector" onchange="handleRangeSelection()">
                    <option value="today" selected>Today</option>
                    <option value="1">Last Day</option>
                    <option value="7">Last 7 Days</option>
                    <option value="30">Last 30 Days</option>
                    <option value="custom">Custom</option>
                </select>
                <div id="customRange" style="display: none;">
                    <input type="text" id="startDate" placeholder="Start Date">
                    <p>to</p>
                    <input type="text" id="endDate" placeholder="End Date">
                </div>
                <button onclick="applyDateRange()">Apply</button>
            </div>

            <div class="summary header"><span>Sales Summary</span><span>Amount</span></div>

            <div class="summary"><span>Total Sales Revenue</span><span id="total_sales_revenue"><?php echo number_format($total_sales_revenue, 2); ?> PHP</span></div>
            <div class="summary"><span>Total Transactions</span><span id="total_transactions"><?php echo $total_transactions; ?></span></div>
            <div class="summary"><span>Total Items Sold</span><span id="total_items_sold"><?php echo $total_items_sold; ?></span></div>
            <div class="summary"><span>Average Sale Value</span><span id="average_sale_value"><?php echo number_format($average_sale_value, 2); ?> PHP</span></div>
            <br>
            <div class="summary header"><span>Profit & Loss Summary</span><span>Amount</span></div>

            <div class="summary"><span>Total Cost of Goods Sold (COGS)</span><span id="total_cogs"><?php echo number_format($total_cogs, 2); ?> PHP</span></div>
            <div class="summary"><span>Gross Profit</span><span id="gross_profit"><?php echo number_format($gross_profit, 2); ?> PHP</span></div>
            <div class="summary"><span>Net Profit</span><span id="net_profit"><?php echo number_format($net_profit, 2); ?> PHP</span></div>
        </div>
    </div>

    <script src="js/flatpickr.js"></script>
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

            handleRangeSelection();
        });

        function handleRangeSelection() {
            const rangeSelector = document.getElementById("rangeSelector");
            const customRange = document.getElementById("customRange");

            if (rangeSelector.value === "custom") {
                customRange.style.display = "flex";
            } else {
                customRange.style.display = "none";
                applyDateRange();
            }
        }

        function applyDateRange() {
            const rangeSelector = document.getElementById("rangeSelector");
            let startDate, endDate;

            if (rangeSelector.value === "custom") {
                startDate = document.getElementById("startDate").value;
                endDate = document.getElementById("endDate").value;
            } else if (rangeSelector.value === "today") {
                // Both start and end date are set to today
                const today = new Date();
                startDate = today.toISOString().split('T')[0];
                endDate = today.toISOString().split('T')[0];
            } else {
                endDate = new Date();
                startDate = new Date();
                switch (rangeSelector.value) {
                    case "1":
                        startDate.setDate(endDate.getDate() - 1);
                        break;
                    case "7":
                        startDate.setDate(endDate.getDate() - 7);
                        break;
                    case "30":
                        startDate.setDate(endDate.getDate() - 30);
                        break;
                }
                startDate = startDate.toISOString().split('T')[0];
                endDate = endDate.toISOString().split('T')[0];
            }

            fetch(`sales_report.php?start_date=${startDate}&end_date=${endDate}&ajax=1`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('total_sales_revenue').innerText = `${parseFloat(data.total_sales_revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} PHP`;
                    document.getElementById('total_transactions').innerText = data.total_transactions;
                    document.getElementById('total_items_sold').innerText = data.total_items_sold;
                    document.getElementById('average_sale_value').innerText = `${parseFloat(data.average_sale_value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} PHP`;
                    document.getElementById('total_cogs').innerText = `${parseFloat(data.total_cogs).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} PHP`;
                    document.getElementById('gross_profit').innerText = `${parseFloat(data.gross_profit).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} PHP`;
                    document.getElementById('net_profit').innerText = `${parseFloat(data.net_profit).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} PHP`;
                });
        }
    </script>
</body>

</html>