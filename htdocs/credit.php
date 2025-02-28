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
          ORDER BY c.lastUpdated DESC";
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
    <link rel="stylesheet" href="css/credit.css">
    <style>
        th {
            cursor: pointer;
        }

        th img {
            width: 15px;
            height: 15px;
            margin-right: 10px;
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
            <div class="table-wrapper">
                <div class="header-container">
                    <h2>Credit</h2>
                    <div class="button">
                        <a href="addCredit.php">New Credit</a>
                    </div>
                </div>
                <div class="search-container">
                    <div class="search-wrapper">
                        <img src="images/search-icon.png" alt="Search" class="search-icon">
                        <input type="text" id="searchBar" placeholder="Search Creditor/s" onkeyup="filterCredits(); toggleClearIcon();">
                        <img src="images/x-circle.png" alt="Clear" class="clear-icon" onclick="clearSearch()">
                    </div>
                </div>
                <hr style="height: 1px; border: none; color: rgb(187, 188, 190); background-color: rgb(187, 188, 190);">
                <table id="creditsTable" data-sort-order="asc">
                    <thead>
                        <tr align="left">
                            <th onclick="sortTable(0)">Date <span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(1)">Status <span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(2)">Creditor <span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(3)">Balance <span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
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
                                $date = !empty($credit['transactionDate'])
                                    ? date("n/j/y", strtotime($credit['transactionDate'])) . "<br>" . date("g:i A", strtotime($credit['transactionDate']))
                                    : 'N/A';
                            ?>
                                <tr>
                                    <td data-date="<?php echo $credit['transactionDate']; ?>"><?php echo $date; ?></td>
                                    <td><?php echo htmlspecialchars($credit['paymentStatus']); ?></td>
                                    <td><?php echo htmlspecialchars($credit['customerName']); ?></td>
                                    <td>₱ <?php echo htmlspecialchars($credit['creditBalance']); ?></td>
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

            rows.forEach(row => {
                const creditorName = row.cells[2].textContent.toLowerCase();
                row.style.display = creditorName.includes(query) ? '' : 'none';
            });
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
            const rows = Array.from(table.rows).slice(1);
            const isAscending = table.getAttribute('data-sort-order') === 'asc';
            const direction = isAscending ? 1 : -1;

            rows.sort((a, b) => {
                let aText = a.cells[columnIndex].textContent.trim();
                let bText = b.cells[columnIndex].textContent.trim();

                if (columnIndex === 0) { // Date column
                    aText = new Date(a.cells[columnIndex].getAttribute('data-date')).getTime();
                    bText = new Date(b.cells[columnIndex].getAttribute('data-date')).getTime();
                } else if (columnIndex === 3) { // Balance column
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

        document.addEventListener('DOMContentLoaded', toggleClearIcon);
    </script>
</body>

</html>