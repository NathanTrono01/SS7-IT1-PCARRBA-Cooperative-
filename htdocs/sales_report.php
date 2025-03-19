<?php

include 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default date range to last 7 days
$end_date   = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-7 days'));

if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date   = $_GET['end_date'];
}

// Total Sales Revenue:
//  -- Cash sales from sales table AND
//  -- Credit sales (only if paid) from sales joined with credits
$total_sales_sql = "SELECT 
    (
      (SELECT COALESCE(SUM(totalPrice), 0) 
         FROM sales 
         WHERE DATE(dateSold) BETWEEN '$start_date' AND '$end_date'
           AND transactionType = 'Cash'
      )
      +
      (SELECT COALESCE(SUM(s.totalPrice), 0)
         FROM sales s
         JOIN credits c ON s.creditId = c.creditId
         WHERE DATE(s.dateSold) BETWEEN '$start_date' AND '$end_date'
           AND c.paymentStatus = 'Paid'
      )
    ) AS total_sales_revenue";
$total_sales_result = $conn->query($total_sales_sql);
$total_sales_revenue = $total_sales_result->fetch_assoc()['total_sales_revenue'] ?? 0;

// Total Transactions
$total_transactions_sql = "SELECT 
    (
      (SELECT COUNT(*) 
         FROM sales 
         WHERE DATE(dateSold) BETWEEN '$start_date' AND '$end_date'
           AND transactionType = 'Cash'
      )
      +
      (SELECT COUNT(*) 
         FROM sales s
         JOIN credits c ON s.creditId = c.creditId
         WHERE DATE(s.dateSold) BETWEEN '$start_date' AND '$end_date'
           AND c.paymentStatus = 'Paid'
      )
    ) AS total_transactions";
$total_transactions_result = $conn->query($total_transactions_sql);
$total_transactions = $total_transactions_result->fetch_assoc()['total_transactions'] ?? 0;

// Total Items Sold:
//  -- For cash sales: join sale_item with sales (using saleId)
//  -- For credit sales: join sale_item with credits (using creditId) filtering on paid credits
$total_items_sold_sql = "SELECT 
    COALESCE(SUM(total_qty), 0) AS total_items_sold
    FROM (
      SELECT SUM(si.quantity) AS total_qty
      FROM sale_item si 
      JOIN sales s ON si.saleId = s.saleId 
      WHERE DATE(s.dateSold) BETWEEN '$start_date' AND '$end_date'
        AND s.transactionType = 'Cash'
      UNION ALL
      SELECT SUM(si.quantity) AS total_qty
      FROM sale_item si
      JOIN credits c ON si.creditId = c.creditId
      WHERE DATE(c.transactionDate) BETWEEN '$start_date' AND '$end_date'
        AND c.paymentStatus = 'Paid'
    ) AS all_items";
$total_items_sold_result = $conn->query($total_items_sold_sql);
$total_items_sold = $total_items_sold_result->fetch_assoc()['total_items_sold'] ?? 0;

// Calculate average sale value
$average_sale_value = $total_transactions > 0 ? $total_sales_revenue / $total_transactions : 0;

// Updated COGS Calculation:
// Use a subquery to fetch the average (or single) cost price per product to avoid duplicate rows from batchItem
$total_cogs_sql = "
    SELECT COALESCE(SUM(total_item_cost), 0) AS total_cogs
    FROM (
      -- Cash sales COGS
      SELECT SUM(si.quantity * (
                  SELECT IFNULL(AVG(costPrice), 0)
                  FROM batchItem
                  WHERE productId = si.productId
               )) AS total_item_cost
      FROM sale_item si
      JOIN sales s ON si.saleId = s.saleId
      WHERE DATE(s.dateSold) BETWEEN '$start_date' AND '$end_date'
            AND s.transactionType = 'Cash'
      UNION ALL
      -- Credit sales COGS
      SELECT SUM(si.quantity * (
                  SELECT IFNULL(AVG(costPrice), 0)
                  FROM batchItem
                  WHERE productId = si.productId
               )) AS total_item_cost
      FROM sale_item si
      JOIN credits c ON si.creditId = c.creditId
      WHERE DATE(c.transactionDate) BETWEEN '$start_date' AND '$end_date'
            AND c.paymentStatus = 'Paid'
    ) AS combined_costs
";
$total_cogs_result = $conn->query($total_cogs_sql);
$total_cogs = $total_cogs_result->fetch_assoc()['total_cogs'] ?? 0;

// Gross Profit and Net Profit
$gross_profit = $total_sales_revenue - $total_cogs;
$expenses = 0; // Replace with actual expenses if available
$net_profit = $gross_profit - $expenses;

$data = [
    'total_sales_revenue' => $total_sales_revenue,
    'total_transactions'  => $total_transactions,
    'total_items_sold'     => $total_items_sold,
    'average_sale_value'   => $average_sale_value,
    'total_cogs'           => $total_cogs,
    'gross_profit'         => $gross_profit,
    'net_profit'           => $net_profit,
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
            flex-direction: row;
            justify-content: flex-start;
            gap: 15px;
            margin-bottom: 25px;
            width: 100%;
        }

        .date-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .date-range-picker select {
            background: rgba(43, 45, 49, 0.8);
            border: 1px solid #3a3a3a;
            color: rgba(224, 224, 224, 0.9);
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            width: 180px;
            transition: all 0.2s ease;
        }

        .date-range-picker select:hover,
        .date-range-picker select:focus {
            border-color: #4b73b9;
            color: white;
            box-shadow: 0 0 5px rgba(43, 114, 255, 0.3);
        }

        /* Custom Range Container Styles */
        #customRange {
            display: none;
            flex-wrap: nowrap;
            gap: 15px;
            align-items: center;
            justify-content: flex-start;
            padding: 12px 15px;
            background: rgba(31, 32, 36, 0.6);
            border-radius: 8px;
            border: 1px solid #333;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            margin-top: 5px;
            width: 100%;
        }

        #customRange input {
            background: rgba(43, 45, 49, 0.8);
            border: 1px solid #3a3a3a;
            color: rgba(224, 224, 224, 0.9);
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            width: 140px;
            transition: all 0.2s ease;
        }

        #customRange input:hover,
        #customRange input:focus {
            border-color: #4b73b9;
            color: white;
            outline: none;
            box-shadow: 0 0 5px rgba(43, 114, 255, 0.3);
        }

        #customRange p {
            color: rgba(224, 224, 224, 0.7);
            margin: 0 5px;
        }

        .date-range-picker button {
            background: rgb(43, 114, 255);
            border: none;
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .date-range-picker button:hover {
            background: rgb(82, 139, 255);
            box-shadow: 0 0 8px rgba(43, 114, 255, 0.5);
            transform: translateY(-1px);
        }
        
        /* Responsive Styles */
        @media (max-width: 600px) {
            #customRange {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            #customRange input {
                width: 100%;
            }
            
            .date-controls {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .date-range-picker select,
            .date-range-picker button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="main-content fade-in">
        <div class="container">
            <span class="date-header">Date Range</span>
            <div class="date-range-picker">
                <div class="date-controls">
                    <select id="rangeSelector" onchange="handleRangeSelection()">
                        <option value="today" selected>Today</option>
                        <option value="1">Last Day</option>
                        <option value="7">Last 7 Days</option>
                        <option value="30">Last 30 Days</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <button onclick="applyDateRange()">Apply</button>
                <div id="customRange">
                    <input type="text" id="startDate" placeholder="Start Date">
                    <p>to</p>
                    <input type="text" id="endDate" placeholder="End Date">
                </div>
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