<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

// Fetch summarized sales data
$query = "SELECT saleId, dateSold, transactionType, totalPrice, creditId FROM sales ORDER BY dateSold DESC";
$result = $conn->query($query);

if (!$result) {
    die("Database query failed: " . $conn->error);
}

$sales = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Transactions</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/layer1.css">
    <link rel="stylesheet" href="css/sales.css">
    <style>
        th {
            cursor: pointer;
        }

        th img {
            width: 15px;
            height: 15px;
            margin-right: 10px;
        }

        /* Pagination Container */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
        }

        /* Pagination Buttons */
        .pagination-container button {
            background-color: #335fff;
            color: white;
            border: none;
            padding: 7px 12px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .pagination-container button:hover {
            background-color: #264bcc;
        }

        /* Page Input Field */
        .pagination-container input {
            width: 50px;
            padding: 5px;
            font-size: 14px;
            text-align: center;
            border: 1px solid #335fff;
            border-radius: 5px;
            outline: none;
        }

        /* Page Input Focus Effect */
        .pagination-container input:focus {
            border-color: #264bcc;
            box-shadow: 0 0 5px rgba(51, 95, 255, 0.6);
        }

        .main-content {
            position: relative;
            min-height: 100vh;
            padding-bottom: 60px; /* Height of the pagination container */
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <div class="main-content fade-in">
        <div class="container">
            <div class="table-wrapper">
                <div class="header-container">
                    <h2>Sales</h2>
                    <div class="button">
                        <a href="addSale.php">New Sale</a>
                    </div>
                </div>
                <div class="search-container">
                    <div class="search-wrapper">
                        <img src="images/search-icon.png" alt="Search" class="search-icon">
                        <input type="text" id="searchBar" placeholder="Search Sales by Date" onkeyup="filterSales(); toggleClearIcon();">
                        <img src="images/x-circle.png" alt="Clear" class="clear-icon" onclick="clearSearch()">
                    </div>
                </div>
                <hr>
                <table id="salesTable">
                    <thead>
                        <tr align="left">
                            <th onclick="sortTable(0)">Date <span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(1)">Type <span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(2)">Amount <span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sales)) { ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No sales records found. <a href="addSale.php">Create a new sale</a>.</td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($sales as $sale) { ?>
                                <tr>
                                    <td data-date="<?php echo $sale['dateSold']; ?>">
                                        <?php echo date("n/j/y", strtotime($sale['dateSold'])) . "<br>" . date("g:i A", strtotime($sale['dateSold'])); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($sale['transactionType']); ?></td>
                                    <td>₱ <?php echo htmlspecialchars($sale['totalPrice']); ?></td>
                                    <td>
                                        <?php if ($sale['transactionType'] === 'Credit') { ?>
                                            <a href="credit_details.php?creditId=<?php echo $sale['creditId']; ?>" class="view-details">
                                                <span>View Details</span>
                                            </a>
                                        <?php } else { ?>
                                            <a href="sale_details.php?saleId=<?php echo $sale['saleId']; ?>" class="view-details">
                                                <span>View Details</span>
                                            </a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
                <!-- Pagination -->
                <div class="pagination-container">
                    <button onclick="prevPage()">Previous</button>
                    <span>Page</span>
                    <input type="number" id="pageInput" min="1" onchange="goToPage(this.value)">
                    <span id="totalPages">of 1</span>
                    <button onclick="nextPage()">Next</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        const rowsPerPage = 7;
        const table = document.getElementById('salesTable');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.getElementsByTagName('tr'));
        const totalPages = Math.ceil(rows.length / rowsPerPage);
        document.getElementById("totalPages").textContent = `of ${totalPages}`;

        function displayPage(page) {
            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            rows.forEach((row, index) => {
                row.style.display = (index >= start && index < end) ? '' : 'none';
            });

            document.getElementById("pageInput").value = page;
            currentPage = page;
        }

        function prevPage() {
            if (currentPage > 1) {
                displayPage(currentPage - 1);
            }
        }

        function nextPage() {
            if (currentPage < totalPages) {
                displayPage(currentPage + 1);
            }
        }

        function goToPage(page) {
            page = parseInt(page);
            if (page >= 1 && page <= totalPages) {
                displayPage(page);
            } else {
                document.getElementById("pageInput").value = currentPage;
            }
        }

        function filterSales() {
            const query = document.getElementById('searchBar').value.toLowerCase();
            const rows = document.querySelectorAll('#salesTable tbody tr');

            rows.forEach(row => {
                const dateSold = row.cells[0].textContent.toLowerCase();
                row.style.display = dateSold.includes(query) ? '' : 'none';
            });
        }

        function clearSearch() {
            document.getElementById('searchBar').value = '';
            filterSales();
            toggleClearIcon();
        }

        function toggleClearIcon() {
            const searchBar = document.getElementById('searchBar');
            const clearIcon = document.querySelector('.clear-icon');
            clearIcon.style.display = searchBar.value ? 'block' : 'none';
        }

        function sortTable(columnIndex) {
            const table = document.getElementById('salesTable');
            const rows = Array.from(table.rows).slice(1);
            const isAscending = table.getAttribute('data-sort-order') === 'asc';
            const direction = isAscending ? 1 : -1;

            rows.sort((a, b) => {
                let aText = a.cells[columnIndex].textContent.trim();
                let bText = b.cells[columnIndex].textContent.trim();

                if (columnIndex === 0) { // Date column
                    aText = new Date(a.cells[columnIndex].getAttribute('data-date')).getTime();
                    bText = new Date(b.cells[columnIndex].getAttribute('data-date')).getTime();
                } else if (columnIndex === 2) { // Amount column
                    aText = parseFloat(aText.replace('₱', '').replace(',', ''));
                    bText = parseFloat(bText.replace('₱', '').replace(',', ''));
                }

                if (!isNaN(aText) && !isNaN(bText)) {
                    return direction * (aText - bText);
                }

                return direction * aText.localeCompare(bText);
            });

            const tbody = table.querySelector('tbody');
            rows.forEach(row => tbody.appendChild(row));
            table.setAttribute('data-sort-order', isAscending ? 'desc' : 'asc');
        }

        displayPage(1);
    </script>
</body>

</html>