<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

// Fetch summarized credit data
$query = "SELECT c.creditId, c.transactionDate, c.paymentStatus, cr.creditBalance, cr.customerName 
          FROM credits c
          JOIN creditor cr ON c.creditorId = cr.creditorId
          ORDER BY c.transactionDate DESC";
$result = $conn->query($query);

if (!$result) {
    die("Database query failed: " . $conn->error);
}

$credits = $result->fetch_all(MYSQLI_ASSOC);

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteCreditId'])) {
    $deleteCreditId = $_POST['deleteCreditId'];
    $deleteQuery = "DELETE FROM credits WHERE creditId = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $deleteCreditId);
    if ($stmt->execute()) {
        header("Location: credit.php");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credits Transaction</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
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

        .floating-label {
            position: absolute;
            left: 30px;
            top: 8px;
            /* Adjust this value to align with the input's border */
            transform: translateY(0);
            pointer-events: none;
            transition: all 0.3s ease;
            color: rgba(247, 247, 248, 0.64);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .table-wrapper {
            height: 70vh;
            /* Ensure the wrapper takes 70% of viewport height */
            overflow-y: auto;
            /* Allow vertical scrolling */
            position: relative;
            padding: 10px;
            padding-top: 0;
        }

        table {
            font-family: Arial, Helvetica, sans-serif;
            width: 90%;
            border-collapse: separate;
            border-spacing: 0 10px;
            table-layout: fixed;
            /* Ensure even spacing of columns */
        }

        table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #1f1f1f;
        }

        table tbody tr:nth-child(odd) {
            background-color: #272930;
            /* Darker background for odd rows */
        }

        table tbody tr:nth-child(even) {
            background-color: rgb(17, 18, 22);
            /* Lighter background for even rows */
        }

        table th,
        table td {
            text-wrap: auto;
        }

        table th {
            padding: 7px;
            background-color: rgb(17, 18, 22);
            color: rgba(247, 247, 248, 0.9);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 1rem;
            padding-bottom: 25px;
        }

        table td {
            padding: 5px 10px;
            /* Add padding to the sides of the td elements */
            font-size: 1rem;
            color: #eee;
            margin: 0 5px;
            white-space: normal;
            overflow: hidden;
            text-overflow: ellipsis;
            word-wrap: break-word;
        }

        table tr {
            background-color: transparent;
        }

        table tr:hover {
            background-color: rgba(187, 194, 209, 0.17);
            transition: all 0.3s ease;
        }

        /* table tr td:first-child {
            border-top-left-radius: 5px;
            border-bottom-left-radius: 5px;
            }
            
            table tr td:last-child {
                border-top-right-radius: 5px;
                border-bottom-right-radius: 5px;
                } */

        /* Ensuring the table takes full width */
        #creditsTable {
            width: 100%;
            border-collapse: collapse;
        }

        /* Sticky Header */
        #creditsTable thead {
            position: sticky;
            top: 0;
            background: rgb(17, 18, 22);
            z-index: 10;
        }


        /* Styling for the headers */
        #creditsTable th {
            background: rgb(17, 18, 22);
            /* Dark background */
            color: white;
            border-bottom: 2px solid #333942;
            text-align: left;
        }

        /* Making tbody scrollable while keeping alignment */
        #creditsTable tbody {
            display: block;
            max-height: 65vh;
            /* Adjust height dynamically */
        }

        /* Ensuring rows and cells align properly */
        #creditsTable tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        #creditsTable td {
            padding: 10px 10px;
            font-size: 1rem;
            margin: 0 5px;
            width: 20%;
        }

        /* Scrollbar for tbody */
        #creditsTable .table-wrapper::-webkit-scrollbar {
            width: 6px;
            transition: opacity 0.3s ease-in-out;
        }

        /* Track (background of the scrollbar) */
        #creditsTable .table-wrapper::-webkit-scrollbar-track {
            background: transparent;
        }

        /* Thumb (scrollable part) */
        #creditsTable .table-wrapper::-webkit-scrollbar-thumb {
            background-color: #555;
            background: grey;
            border-radius: 10rem;
            opacity: 0;
            /* Initially hidden */
        }

        /* Show scrollbar on hover */
        #creditsTable .table-wrapper:hover::-webkit-scrollbar-thumb {
            opacity: 1;
            /* Visible when hovering */
        }

        /* Hover effect for better UX */
        #creditsTable .table-wrapper::-webkit-scrollbar-thumb:hover {
            background-color: #777;
        }

        .button a {
            background: transparent;
            display: inline-block;
            padding: 8px 12px;
            background-color: rgb(255, 255, 255);
            color: #000;
            text-decoration: none;
            border-radius: 7px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.26);
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        .button a:hover {
            background-color: rgba(255, 255, 255, 0.94);
            color: #000;
            border-radius: 7px;
        }

        .btn-action {
            text-decoration: none;
            padding: 8px 8px;
            font-size: 0.9rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            display: inline-flex;
            justify-content: flex-end;
            min-width: 60px;
            box-sizing: border-box;
        }

        .btn-action img {
            display: none;
        }

        .btn-action span {
            display: inline;
        }

        .btn-delete {
            margin-left: 5px;
            border: none;
            background: transparent;
            padding: 5px;
            border-radius: 5px;
            color: rgb(255, 51, 51);
            text-decoration: none;
            font-weight: bold;
            margin-top: 5px;
        }

        .btn-delete:hover {
            background-color: rgba(255, 255, 255, 0.07);
            color: rgb(255, 82, 82);
            text-decoration: none;
            font-weight: bold;
            transition: 0.5s;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .alert-success {
            position: fixed;
            top: 100px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #d4edda;
            border: 1px solid green;
            color: green;
            padding: 15px 30px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }

        .alert-success.show {
            display: block;
        }

        .alert-warning {
            position: fixed;
            margin-top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(167, 99, 40, 0.44);
            border: 1px solid rgb(255, 136, 0);
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }

        .view-details {
            padding: 7px;
            border-radius: 5px;
            color: #335fff;
            text-decoration: none;
            font-weight: bold;
            margin-top: 5px;
        }

        .view-details:hover {
            background-color: rgba(255, 255, 255, 0.07);
            color: rgb(82, 139, 255);
            text-decoration: none;
            font-weight: bold;
            transition: 0.5s;
        }

        th {
            cursor: pointer;
        }

        th img {
            width: 15px;
            height: 15px;
            margin-right: 10px;
        }

        @media (max-width: 1024px) {
            .table-wrapper {
                /* Responsive height based on viewport */
                overflow-y: auto;
                /* Scrollable body */
                position: relative;
                overflow-x: hidden;
            }

            #creditsTable td {
                padding: 5px 10px;
                font-size: 0.8rem;
                margin: 0 5px;
                width: 20%;
            }

            #creditsTable th {
                font-size: 0.6rem !important;
            }

            th img {
                width: 11px;
                height: 11px;
                margin-right: 5px;
            }

            table th,
            table td {
                font-size: 0.95rem;
            }

            .btn-action {
                padding: 6px 10px;
                font-size: 0.85rem;
            }

            .btn-action span {
                display: none;
            }

            .btn-action img {
                display: inline;
                width: 20px;
                height: 20px;
            }
        }

        @media (max-width: 768px) {
            .table-wrapper {
                overflow-x: hidden;
            }

            table {
                width: 100%;
            }

            table th,
            table td {
                font-size: 0.85rem;
            }

            .btn-action {
                padding: 7px 7px;
                font-size: 0.7rem;
                min-width: 30px;
            }

            .btn-action span {
                display: none;
            }

            .btn-action img {
                display: inline;
                width: 20px;
                height: 20px;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 1.5em;
            }

            h2 {
                font-size: 0.8em;
            }

            .table-wrapper {
                margin: 0;
            }

            table {
                font-size: 0.8rem;
                width: 100%;
                overflow-x: hidden;
                white-space: wrap;
            }

            table th,
            table td {

                font-size: 0.8rem;
            }

            .btn-action {
                padding: 4px 8px;
                font-size: 0.7rem;
                min-width: 30px;
            }

            .btn-action span {
                display: none;
            }

            .btn-action img {
                display: inline;
                width: 15px;
                height: 15px;
            }
        }

        .main-content {
            padding: 10px;
            height: 100vh;
        }

        .unpaid {
            color: red;
            font-size: 1rem;
            white-space: wrap;
        }

        .partial {
            color: orange;
            font-size: 1rem;
            white-space: wrap;
        }

        .paid {
            color: limegreen;
            font-size: 1rem;
            white-space: wrap;
        }

        @media (max-width: 768px) {

            .unpaid,
            .partial,
            .paid {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {

            .unpaid,
            .partial,
            .paid {
                font-size: 0.75rem;
            }
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
            text-align: center;
            min-width: 35px;
            width: 70%;
        }

        .status-unpaid {
            background-color: rgba(235, 87, 87, 0.15);
            color: #EB5757;
            border: 1px solid rgba(235, 87, 87, 0.3);
        }

        .status-partial {
            background-color: rgba(242, 153, 74, 0.15);
            color: #F39C12;
            border: 1px solid rgba(242, 153, 74, 0.3);
        }

        .status-paid {
            background-color: rgba(39, 174, 96, 0.15);
            color: #27AE60;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }

        /* Sort indicators */
        .sort-indicator {
            display: inline-block;
            margin-left: 5px;
            opacity: 0.6;
        }

        .active-sort {
            opacity: 1;
            color: #335fff;
        }

        /* Enhanced table header styling */
        table th {
            position: relative;
            cursor: pointer;
            transition: background-color 0.2s;
            padding: 7px;
            background-color: rgb(17, 18, 22);
            color: rgba(247, 247, 248, 0.9);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 1rem;
            padding-bottom: 25px;
        }

        table th:hover {
            background-color: rgba(51, 95, 255, 0.1);
        }

        /* Specific hover styling for creditsTable headers */
        #creditsTable th:hover {
            background-color: rgba(51, 95, 255, 0.1);
            border-bottom: 2px solid #335fff;
        }

        /* Search results count */
        .search-results-count {
            display: none;
            font-size: 14px;
            color: #94a3b8;
            margin-left: 15px;
            margin-top: 5px;
        }

        /* Remove old status styles */
        .unpaid, .partial, .paid {
            color: inherit;
            font-size: inherit;
            white-space: inherit;
        }

        /* Status badges with responsive design */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
            text-align: center;
            min-width: 80px;
            justify-content: center;
        }

        .status-unpaid {
            background-color: rgba(235, 87, 87, 0.15);
            color: #EB5757;
            border: 1px solid rgba(235, 87, 87, 0.3);
        }

        .status-partial {
            background-color: rgba(242, 153, 74, 0.15);
            color: #F39C12;
            border: 1px solid rgba(242, 153, 74, 0.3);
        }

        .status-paid {
            background-color: rgba(39, 174, 96, 0.15);
            color: #27AE60;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }

        /* Status indicator dot for mobile */
        .status-indicator {
            display: none; /* Hidden by default, shown only on mobile */
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 6px;
        }

        .status-unpaid .status-indicator {
            background-color: #EB5757;
            box-shadow: 0 0 0 2px rgba(235, 87, 87, 0.2);
        }

        .status-partial .status-indicator {
            background-color: #F39C12;
            box-shadow: 0 0 0 2px rgba(242, 153, 74, 0.2);
        }
        
        .status-paid .status-indicator {
            background-color: #27AE60;
            box-shadow: 0 0 0 2px rgba(39, 174, 96, 0.2);
        }

        /* Status text (hidden on mobile) */
        .status-text {
            display: inline;
        }

        /* Mobile-specific adjustments */
        @media (max-width: 768px) {
            .status-badge {
                min-width: unset; /* Remove minimum width */
                width: auto; /* Let it be as wide as needed */
                padding: 4px;
            }
            
            .status-indicator {
                display: inline-block; /* Show the dot on mobile */
                margin-right: 0; /* No margin needed when text is hidden */
            }
            
            .status-text {
                display: none; /* Hide the text on mobile */
            }
            
            #creditsTable td {
                padding: 8px 5px; /* Reduce padding on mobile */
            }
        }
        
        /* Extra small screens */
        @media (max-width: 480px) {
            .status-badge {
                padding: 3px;
            }
            
            .status-indicator {
                width: 10px;
                height: 10px;
            }
        }

        /* Update #creditsTable styling to match inventory.php */
        #creditsTable {
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
        }

        #creditsTable th {
            padding: 12px 15px;
            border-bottom: 2px solid #333942;
            font-weight: 600;
            background-color: rgb(17, 18, 22);
            color: rgba(247, 247, 248, 0.9);
            text-transform: uppercase;
        }

        #creditsTable td {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(51, 57, 66, 0.2);
            font-size: 1rem;
            color: #eee;
            width: 20%;
        }

        /* Enhanced table header styling with consistent hover effect */
        table th {
            position: relative;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        table th:hover {
            background-color: rgba(51, 95, 255, 0.1);
            border-bottom: 2px solid #335fff;
        }

        /* Specific hover styling for creditsTable headers */
        #creditsTable th:hover {
            background-color: rgba(51, 95, 255, 0.1);
            border-bottom: 2px solid #335fff;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <div class="main-content fade-in">
        <div class="container">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert-success show <?php echo $_SESSION['alert_class']; ?>" id="alert-success">
                    <span><?php echo $_SESSION['message']; ?></span>
                </div>
                <?php unset($_SESSION['message']);
                unset($_SESSION['alert_class']); ?>
                <script>
                    setTimeout(function() {
                        document.getElementById("alert-success").classList.remove("show");
                    }, 4000);
                </script>
            <?php endif; ?>
            <div class="header-container">
                <h2>Credit</h2>
                <div class="button">
                    <a href="addCredit.php">New Credit</a>
                </div>
                <div class="search-container">
                    <div class="search-wrapper">
                        <img src="images/search-icon.png" alt="Search" class="search-icon">
                        <input type="text" id="searchBar" placeholder="Search Date/Status/Creditor" onkeyup="filterCredits(); toggleClearIcon();">
                        <img src="images/x-circle.png" alt="Clear" class="clear-icon" onclick="clearSearch()">
                    </div>
                </div>
                <span class="search-results-count" id="searchResultsCount"></span>
            </div>
            <div class="table-wrapper">
                <table id="creditsTable" data-sort-order="asc" data-sort-column="0">
                    <thead>
                        <tr align="left">
                            <th onclick="sortTable(0)">Date <span class="sort-indicator active-sort">▼</span></th>
                            <th onclick="sortTable(1)">Creditor <span class="sort-indicator">◆</span></th>
                            <th onclick="sortTable(2)">Status <span class="sort-indicator">◆</span></th>
                            <th onclick="sortTable(3)">Balance <span class="sort-indicator">◆</span></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($credits)) { ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No credit records found. <a href="addCredit.php">Create a new credit</a>.</td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($credits as $credit) {
                                $statusClass = '';
                                if ($credit['paymentStatus'] == "Unpaid") {
                                    $statusClass = 'status-unpaid';
                                } elseif ($credit['paymentStatus'] == "Partially Paid") {
                                    $statusClass = 'status-partial';
                                } else {
                                    $statusClass = 'status-paid';
                                }
                                $date = !empty($credit['transactionDate'])
                                    ? date("n/j/y", strtotime($credit['transactionDate'])) . "<br>" . date("g:i A", strtotime($credit['transactionDate']))
                                    : 'N/A';
                            ?>
                                <tr>
                                    <td data-date="<?php echo $credit['transactionDate']; ?>"><?php echo $date; ?></td>
                                    <td><?php echo htmlspecialchars($credit['customerName']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <span class="status-indicator"></span>
                                            <span class="status-text"><?php echo htmlspecialchars($credit['paymentStatus']); ?></span>
                                        </span>
                                    </td>
                                    <td>₱ <?php echo number_format(floatval($credit['creditBalance']), 2); ?></td>
                                    <td>
                                        <a href="credit_details.php?creditId=<?php echo $credit['creditId']; ?>" class="view-details">
                                            <span>View Details</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function filterCredits() {
            const query = document.getElementById('searchBar').value.toLowerCase();
            const rows = document.querySelectorAll('#creditsTable tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                // Skip header row if present in selection
                if (row.querySelector('th')) return;
                
                // Skip the "No records found" row
                if (row.cells.length === 1 && row.cells[0].colSpan > 1) return;

                const date = row.cells[0].textContent.toLowerCase();
                const creditorName = row.cells[1].textContent.toLowerCase();
                const status = row.cells[2].textContent.toLowerCase();
                const balance = row.cells[3].textContent.toLowerCase();

                const isVisible = date.includes(query) || 
                                creditorName.includes(query) || 
                                status.includes(query) || 
                                balance.includes(query);
                
                row.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });
            
            // Update search results count
            const resultsCounter = document.getElementById('searchResultsCount');
            if (query.length > 0) {
                resultsCounter.textContent = `${visibleCount} record${visibleCount !== 1 ? 's' : ''} found`;
                resultsCounter.style.display = 'inline-block';
            } else {
                resultsCounter.style.display = 'none';
            }
        }

        function clearSearch() {
            document.getElementById('searchBar').value = '';
            filterCredits();
            toggleClearIcon();
        }

        function toggleClearIcon() {
            const searchBar = document.getElementById('searchBar');
            const clearIcon = document.querySelector('.clear-icon');
            clearIcon.style.display = searchBar.value ? 'block' : 'none';
        }

        function sortTable(columnIndex) {
            const table = document.getElementById('creditsTable');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Skip if only "No records found" row exists
            if (rows.length === 1 && rows[0].cells.length === 1 && rows[0].cells[0].colSpan > 1) {
                return;
            }
            
            // Update sort order
            let sortOrder = "asc";
            if (table.dataset.sortColumn === columnIndex.toString()) {
                sortOrder = table.dataset.sortOrder === "asc" ? "desc" : "asc";
            }
            
            table.dataset.sortOrder = sortOrder;
            table.dataset.sortColumn = columnIndex;
            
            // Update sort indicators
            const indicators = document.querySelectorAll('.sort-indicator');
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
                
                let cellA = rowA.cells[columnIndex].textContent.trim();
                let cellB = rowB.cells[columnIndex].textContent.trim();

                if (columnIndex === 0) { // Date column
                    const dateA = new Date(rowA.cells[columnIndex].getAttribute('data-date'));
                    const dateB = new Date(rowB.cells[columnIndex].getAttribute('data-date'));
                    return sortOrder === "asc" ? dateA - dateB : dateB - dateA;
                } else if (columnIndex === 3) { // Balance column
                    const numA = parseFloat(cellA.replace(/[^\d.-]/g, ""));
                    const numB = parseFloat(cellB.replace(/[^\d.-]/g, ""));
                    return sortOrder === "asc" ? numA - numB : numB - numA;
                }

                // Default text comparison
                return sortOrder === "asc" ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
            });

            // Reapply the sorted rows
            rows.forEach(row => {
                tbody.appendChild(row);
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleClearIcon();
            sortTable(0); // Initial sort on date column
        });
    </script>
</body>

</html>