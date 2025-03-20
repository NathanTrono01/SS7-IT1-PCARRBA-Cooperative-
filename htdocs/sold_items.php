<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

// Fetch top 5 most sold products (this query is still used somewhere, if needed)
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

// Updated query: fetch product outflow (sold & credit) records
$outflow_sql = "
    SELECT 
        s.dateSold AS dateOut, 
        p.productName, 
        'Sold' AS reason,
        (si.price * si.quantity) AS total, 
        si.quantity, 
        p.unit,
        si.sale_itemId AS item_id
    FROM sale_item si
    JOIN products p ON si.productId = p.productId
    JOIN sales s ON si.saleId = s.saleId
    WHERE si.saleId IS NOT NULL
    
    UNION ALL
    
    SELECT 
        c.transactionDate AS dateOut, 
        p.productName, 
        'Credit' AS reason,
        (si.price * si.quantity) AS total, 
        si.quantity, 
        p.unit,
        si.sale_itemId AS item_id
    FROM sale_item si
    JOIN products p ON si.productId = p.productId
    JOIN credits c ON si.creditId = c.creditId
    WHERE si.creditId IS NOT NULL
    
    ORDER BY dateOut DESC
";

// Execute and check for errors
$outflow_result = $conn->query($outflow_sql);
if (!$outflow_result) {
    echo "<!-- SQL Error: " . $conn->error . " -->";
}

$sale_items = [];
if ($outflow_result && $outflow_result->num_rows > 0) {
    while ($row = $outflow_result->fetch_assoc()) {
        $sale_items[] = $row;
    }
}

// Check if we have any credit items in our results (for debugging)
$credit_count = 0;
$sold_count = 0;
foreach ($sale_items as $item) {
    if ($item['reason'] == 'Credit') {
        $credit_count++;
    } else {
        $sold_count++;
    }
}

// Add these debug lines to see if credits exist in the database
// You can remove them after confirming credits are working
echo "<!-- Debug: Found $credit_count credit items and $sold_count sold items -->";

// Direct query to check sales items with credits (ignore other joins for this test)
$credit_check_sql = "SELECT COUNT(*) as credit_count FROM sale_item WHERE creditId IS NOT NULL";
$credit_result = $conn->query($credit_check_sql);
$credit_only = $credit_result->fetch_assoc();
echo "<!-- Direct credit check: " . $credit_only['credit_count'] . " items with creditId -->";

// Direct query to check sales items with sales (ignore other joins for this test)
$sale_check_sql = "SELECT COUNT(*) as sale_count FROM sale_item WHERE saleId IS NOT NULL";
$sale_result = $conn->query($sale_check_sql);
$sale_only = $sale_result->fetch_assoc();
echo "<!-- Direct sale check: " . $sale_only['sale_count'] . " items with saleId -->";

// A more detailed debug to see what's in $sale_items
echo "<!-- Sale items dump: ";
foreach ($sale_items as $index => $item) {
    echo "\n Item $index: reason=" . $item['reason'] . 
         ", product=" . $item['productName'] .
         ", date=" . $item['dateOut'];
}
echo " -->";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Product Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        /* Common UI elements */
        .flex-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 10px;
        }

        .search-container {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            position: relative;
            width: 100%;
        }

        .header-container {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            flex-direction: row;
            justify-content: space-between;
            position: relative;
            padding: 10px;
        }

        .search-wrapper {
            display: flex;
            align-items: center;
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        .search-wrapper .search-icon {
            position: absolute;
            left: 10px;
            width: 16px;
            height: 16px;
        }

        .search-wrapper .clear-icon {
            position: absolute;
            right: 10px;
            width: 16px;
            height: 16px;
            cursor: pointer;
            display: none;
        }

        .search-wrapper input {
            padding: 8px 8px 8px 30px;
            width: 100%;
            border-radius: 5px;
            border: 1px solid #333942;
            background-color: rgba(33, 34, 39, 255);
            color: #f7f7f8;
        }

        .search-wrapper input:focus {
            outline: none;
            border: 2px solid #335fff;
        }

        /* Search results count styling - consistent with other pages */
        .search-results-count {
            display: none;
            font-size: 14px;
            color: #94a3b8;
            margin-left: 15px;
            margin-top: 5px;
        }

        /* Table styling - consistent with inventory.php */
        #outflowTable {
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
        }

        #outflowTable th {
            position: relative;
            padding: 12px 15px;
            border-bottom: 2px solid #333942;
            font-weight: 600;
            background-color: rgb(17, 18, 22);
            color: rgba(247, 247, 248, 0.9);
            text-transform: uppercase;
            transition: background-color 0.2s;
        }

        #outflowTable td {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(51, 57, 66, 0.2);
            font-size: 1rem;
            color: #eee;
        }

        /* Table header hover effect - consistent with other pages */
        #outflowTable th:hover {
            background-color: rgba(51, 95, 255, 0.1);
            border-bottom: 2px solid #335fff;
            cursor: pointer;
        }

        /* Sort indicators - consistent with other pages */
        .sort-indicator {
            display: inline-block;
            margin-left: 5px;
            opacity: 0.6;
        }

        .active-sort {
            opacity: 1;
            color: #335fff;
        }

        /* Reason badges - styled consistently with status badges in credit.php */
        .reason-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
            text-align: center;
            min-width: 80px;
            justify-content: center;
        }

        .reason-sold {
            background-color: rgba(39, 174, 96, 0.15);
            color: #27AE60;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }

        .reason-credit {
            background-color: rgba(242, 153, 74, 0.15);
            color: #F39C12;
            border: 1px solid rgba(242, 153, 74, 0.3);
        }

        /* Mobile-friendly reason badges - consistent with credit.php */
        .reason-indicator {
            display: none; /* Hidden by default, shown only on mobile */
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 6px;
        }

        .reason-sold .reason-indicator {
            background-color: #27AE60;
            box-shadow: 0 0 0 2px rgba(39, 174, 96, 0.2);
        }

        .reason-credit .reason-indicator {
            background-color: #F39C12;
            box-shadow: 0 0 0 2px rgba(242, 153, 74, 0.2);
        }

        .reason-text {
            display: inline;
        }

        /* Mobile-specific adjustments */
        @media (max-width: 768px) {
            .reason-badge {
                min-width: unset; /* Remove minimum width */
                width: auto; /* Let it be as wide as needed */
                padding: 4px;
            }
            
            .reason-indicator {
                display: inline-block; /* Show the dot on mobile */
                margin-right: 0; /* No margin needed when text is hidden */
            }
            
            .reason-text {
                display: none; /* Hide the text on mobile */
            }
            
            #outflowTable td {
                padding: 8px 5px; /* Reduce padding on mobile */
            }
        }

        /* Extra small screens */
        @media (max-width: 480px) {
            .reason-badge {
                padding: 3px;
            }
            
            .reason-indicator {
                width: 10px;
                height: 10px;
            }
        }

        /* Keep your existing media queries for responsiveness */
        @media (max-width: 1024px) {
            /* ...existing styles... */
        }

        @media (max-width: 768px) {
            /* ...existing styles... */
        }

        @media (max-width: 480px) {
            /* ...existing styles... */
        }

        /* Animation for new rows - keep this unique feature */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .new-row {
            animation: fadeIn 0.5s ease-out forwards;
        }

        /* Fix positioning and styling for table wrapper */
        .table-wrapper {
            height: 70vh;
            overflow-y: auto;
            position: relative;
            padding: 10px;
            padding-top: 0;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="main-content fade-in">
        <div class="container">
            <div class="header-container">
                <h2>Product Outflow Report</h2>
                <div class="search-container">
                    <div class="search-wrapper">
                        <img src="images/search-icon.png" alt="Search" class="search-icon">
                        <input type="text" id="searchBar" placeholder="Search (Date/Product/Reason)" onkeyup="filterOutflow(); toggleClearIcon();">
                        <img src="images/x-circle.png" alt="Clear" class="clear-icon" onclick="clearSearch()">
                    </div>
                </div>
                <span class="search-results-count" id="searchResultsCount"></span>
            </div>

            <!-- Responsive wrapper -->
            <div class="table-wrapper">
                <table id="outflowTable" data-sort-order="desc" data-sort-column="0">
                    <thead>
                        <tr>
                            <th onclick="sortTable(0, 'outflow')">Date Out <span class="sort-indicator active-sort">▼</span></th>
                            <th onclick="sortTable(1, 'outflow')">Product <span class="sort-indicator">◆</span></th>
                            <th onclick="sortTable(2, 'outflow')">Reason <span class="sort-indicator">◆</span></th>
                            <th onclick="sortTable(3, 'outflow')">Total Value <span class="sort-indicator">◆</span></th>
                            <th onclick="sortTable(4, 'outflow')">Quantity <span class="sort-indicator">◆</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sale_items)) { ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No outflow records found.</td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($sale_items as $sale_item) { ?>
                                <tr data-id="<?php echo htmlspecialchars($sale_item['dateOut']); ?>">
                                    <td data-date="<?php echo $sale_item['dateOut']; ?>">
                                        <?php
                                        echo date("n/j/y", strtotime($sale_item['dateOut'])) . "<br>" .
                                            date("g:i A", strtotime($sale_item['dateOut']));
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($sale_item['productName']); ?></td>
                                    <td>
                                        <span class="reason-badge reason-<?php echo strtolower($sale_item['reason']); ?>">
                                            <span class="reason-indicator"></span>
                                            <span class="reason-text"><?php echo htmlspecialchars($sale_item['reason']); ?></span>
                                        </span>
                                    </td>
                                    <td>₱ <?php echo number_format($sale_item['total'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($sale_item['quantity']) . ' ' . htmlspecialchars($sale_item['unit']); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- Add this after the table -->
            <div class="pagination" id="outflowPagination">
                <button id="outflowPrevPage" onclick="outflowPagination.changePage(-1)"><img src="images/arrow-left.png" alt=""></button>
                <span>Page</span>
                <input type="number" id="outflowPageInput" value="1" min="1" onchange="outflowPagination.goToPage(this.value)">
                <span id="outflowPageInfo"></span>
                <button id="outflowNextPage" onclick="outflowPagination.changePage(1)"><img src="images/arrow-right.png" alt=""></button>
            </div>
        </div>
    </div>

    <script>
        // Enhanced filter function with search result count
        function filterOutflow() {
            const query = document.getElementById('searchBar').value.toLowerCase();
            const rows = document.querySelectorAll('#outflowTable tbody tr');
            let visibleCount = 0;

            // First, reset any pagination hiding
            rows.forEach(row => {
                // Clear pagination hidden flag so we only filter by search term
                delete row.dataset.paginationHidden;
            });

            rows.forEach(row => {
                // Skip the "No outflow records found" row
                if (row.cells.length === 1 && row.cells[0].colSpan > 1) return;
                
                const dateText = row.cells[0].textContent.toLowerCase();
                const productText = row.cells[1].textContent.toLowerCase();
                const reasonText = row.cells[2].textContent.toLowerCase();
                const amountText = row.cells[3].textContent.toLowerCase();
                const quantityText = row.cells[4].textContent.toLowerCase();
                
                // Check if any field matches query
                const shouldShow = query === '' || 
                                 dateText.includes(query) || 
                                 productText.includes(query) || 
                                 reasonText.includes(query) ||
                                 amountText.includes(query) ||
                                 quantityText.includes(query);

                // Update display and count matching rows
                // We're using a dataset attribute for search filtering to distinguish from pagination hiding
                row.dataset.searchHidden = shouldShow ? 'false' : 'true';
                
                // Don't set display property here - let pagination handle final visibility
                if (shouldShow) visibleCount++;
            });

            // Update search results count display
            const resultsCounter = document.getElementById('searchResultsCount');
            if (query.length > 0) {
                resultsCounter.textContent = `${visibleCount} record${visibleCount !== 1 ? 's' : ''} found`;
                resultsCounter.style.display = 'inline-block';
            } else {
                resultsCounter.style.display = 'none';
            }

            // Reset to first page when filtering and update pagination
            document.getElementById('outflowPageInput').value = 1;
            currentPage = 1;
            outflowPagination.updatePagination();
            
            console.log(`Search results: ${visibleCount} matching rows`);
        }

        function clearSearch() {
            document.getElementById('searchBar').value = '';
            filterOutflow();
            toggleClearIcon();
        }

        function toggleClearIcon() {
            const searchBar = document.getElementById('searchBar');
            const clearIcon = document.querySelector('.clear-icon');
            clearIcon.style.display = searchBar.value ? 'block' : 'none';
        }

        // Enhanced table sorter function - consistent with other pages
        function sortTable(columnIndex, tablePrefix) {
            let tableId = (tablePrefix === 'movement') ? "productMovementTable" : "outflowTable";
            const table = document.getElementById(tableId);
            const tbody = table.querySelector("tbody");
            const rows = Array.from(tbody.querySelectorAll("tr"));
            
            // Skip if only "No records found" row exists
            if (rows.length === 1 && rows[0].cells.length === 1 && rows[0].cells[0].colSpan > 1) {
                return;
            }
            
            const isNumericColumn = (columnIndex === 3 || columnIndex === 4);
            const isDateColumn = columnIndex === 0;
            
            // Update sort order
            let sortOrder = "asc";
            if (table.dataset.sortColumn === columnIndex.toString()) {
                sortOrder = table.dataset.sortOrder === "asc" ? "desc" : "asc";
            }
            
            table.dataset.sortOrder = sortOrder;
            table.dataset.sortColumn = columnIndex;
            
            // Update sort indicators
            const indicators = table.querySelectorAll('.sort-indicator');
            indicators.forEach(ind => {
                ind.textContent = '◆';
                ind.classList.remove('active-sort');
            });
            
            const activeIndicator = indicators[columnIndex];
            activeIndicator.textContent = sortOrder === 'asc' ? '▲' : '▼';
            activeIndicator.classList.add('active-sort');

            rows.sort((rowA, rowB) => {
                // Skip rows with colspan (like "no records" message)
                if (rowA.cells.length === 1 || rowB.cells.length === 1) return 0;
                
                if (isDateColumn) {
                    const dateA = new Date(rowA.cells[columnIndex].dataset.date);
                    const dateB = new Date(rowB.cells[columnIndex].dataset.date);
                    return sortOrder === "asc" ? dateA - dateB : dateB - dateA;
                }
                
                if (isNumericColumn) {
                    const numA = parseFloat(rowA.cells[columnIndex].innerText.replace(/[^0-9.-]+/g, ""));
                    const numB = parseFloat(rowB.cells[columnIndex].innerText.replace(/[^0-9.-]+/g, ""));
                    return sortOrder === "asc" ? numA - numB : numB - numA;
                }
                
                const cellA = rowA.cells[columnIndex].innerText.trim().toLowerCase();
                const cellB = rowB.cells[columnIndex].innerText.trim().toLowerCase();
                return sortOrder === "asc" ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
            });

            // Add animation classes to rows
            rows.forEach((row, index) => {
                tbody.appendChild(row);
                // Add animation only to visible rows
                setTimeout(() => {
                    row.classList.add('new-row');
                    // Remove class after animation completes
                    setTimeout(() => row.classList.remove('new-row'), 500);
                }, index * 20);
            });
            
            // After sorting, reset pagination for this table.
            if(tablePrefix === 'movement'){
                if (typeof movementPagination !== 'undefined' && movementPagination) {
                    movementPagination.updatePagination();
                }
            } else {
                outflowPagination.updatePagination();
            }
        }

        // Generic pagination factory function:
        function createPagination(tableId, prefix) {
            const rowsPerPage = 10;
            let currentPage = 1;
            let totalPages = 1;
            const table = document.getElementById(tableId);
            const tbody = table.querySelector("tbody");
            
            function updatePagination() {
                console.log(`Updating pagination for ${prefix}`);
                
                // Get all normal rows (excluding special message rows)
                const allRows = Array.from(tbody.querySelectorAll("tr")).filter(row => 
                    !(row.cells.length === 1 && row.cells[0].colSpan > 1)
                );
                
                // Filter only rows that aren't hidden by search
                const filteredRows = allRows.filter(row => 
                    row.dataset.searchHidden !== 'true'
                );
                
                console.log(`Total rows: ${allRows.length}, Filtered rows: ${filteredRows.length}`);
                
                totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
                document.getElementById(prefix + "PageInfo").textContent = `of ${totalPages}`;
                
                const pageInput = document.getElementById(prefix + "PageInput");
                pageInput.max = totalPages;
                
                if (currentPage > totalPages) {
                    currentPage = totalPages;
                    pageInput.value = currentPage;
                }
                
                // First hide all rows
                allRows.forEach(row => {
                    row.style.display = 'none';
                });
                
                // Then show only the rows for current page that aren't filtered out by search
                filteredRows.forEach((row, index) => {
                    const shouldShowOnPage = (index >= (currentPage - 1) * rowsPerPage && 
                                          index < currentPage * rowsPerPage);
                    
                    if (shouldShowOnPage) {
                        row.style.display = ''; // Show row
                    }
                });
                
                document.getElementById(prefix + "PrevPage").disabled = (currentPage === 1);
                document.getElementById(prefix + "NextPage").disabled = (currentPage === totalPages);
                
                // Debug: count how many rows are actually visible
                const visibleRows = Array.from(tbody.querySelectorAll("tr")).filter(
                    row => row.style.display !== 'none'
                );
                console.log(`Visible rows after pagination: ${visibleRows.length}`);
            }
            
            function changePage(direction) {
                const newPage = currentPage + direction;
                if(newPage >= 1 && newPage <= totalPages){
                    currentPage = newPage;
                    document.getElementById(prefix + "PageInput").value = currentPage;
                    updatePagination();
                }
            }
            
            function goToPage(pageNum) {
                pageNum = parseInt(pageNum);
                if(pageNum >= 1 && pageNum <= totalPages){
                    currentPage = pageNum;
                    updatePagination();
                } else {
                    document.getElementById(prefix + "PageInput").value = currentPage;
                }
            }
            
            return {
                updatePagination,
                changePage,
                goToPage
            };
        }
        
        // Initialize pagination objects for each table, but only if the tables exist
        const outflowPagination = createPagination("outflowTable", "outflow");

        // Only create movementPagination if productMovementTable exists
        let movementPagination = null;
        if (document.getElementById("productMovementTable")) {
            movementPagination = createPagination("productMovementTable", "movement");
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            console.log("DOM loaded");
            
            // Debug: check how many rows we have initially
            const initialRows = document.querySelectorAll('#outflowTable tbody tr');
            console.log("Initial row count:", initialRows.length);
            
            // Debug: check how many have each reason
            const soldBadges = document.querySelectorAll('.reason-sold');
            const creditBadges = document.querySelectorAll('.reason-credit');
            console.log("Sold badges:", soldBadges.length);
            console.log("Credit badges:", creditBadges.length);
            
            // Set all rows as not filtered by search initially
            initialRows.forEach(row => {
                row.dataset.searchHidden = 'false';
            });
            
            // Initialize without sorting first - the sort might be hiding rows
            toggleClearIcon();
            
            // Initialize pagination
            const outflowPagination = window.outflowPagination = createPagination("outflowTable", "outflow");
            outflowPagination.updatePagination();
            
            // If we need to sort, do it after everything else is ready
            setTimeout(() => {
                sortTable(0, 'outflow');
            }, 100);
        });
    </script>
</body>

</html>