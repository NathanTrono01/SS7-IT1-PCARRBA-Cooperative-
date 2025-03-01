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

// Handle form submission for deleting sale items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_sale_item'])) {
        // Delete sale item
        $sale_itemId = $_POST['sale_itemId'];
        $stmt = $conn->prepare("DELETE FROM sale_item WHERE sale_itemId = ?");
        $stmt->bind_param("i", $sale_itemId);
        $stmt->execute();
        $stmt->close();
        // Refresh the page to reflect the changes
        header("Location: sold_products.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Sold Products</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <div class="fade-in">
        <div class="container">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="popup-alert show <?php echo $_SESSION['alert_class']; ?>" id="popupAlert">
                    <span><?php echo $_SESSION['message']; ?></span>
                </div>
                <?php unset($_SESSION['message']);
                unset($_SESSION['alert_class']); ?>
                <script>
                    setTimeout(function() {
                        document.getElementById("popupAlert").classList.remove("show");
                    }, 4000);
                </script>
            <?php endif; ?>
            <div class="header-container">
                <h2>Sold Products</h2>
                <div class="search-container">
                    <div class="search-wrapper">
                        <img src="images/search-icon.png" alt="Search" class="search-icon">
                        <input type="text" id="searchBar" placeholder="Search Sale Items" onkeyup="filterSaleItems(); toggleClearIcon();">
                        <img src="images/x-circle.png" alt="Clear" class="clear-icon" onclick="clearSearch()">
                    </div>
                </div>
            </div>
            <div class="table-wrapper">
                <table id="saleItemTable" data-sort-order="asc">
                    <thead>
                        <tr align="left">
                            <th onclick="sortTable(4)">Sale/Credit ID<span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(0)">Product Name<span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(1)">Quantity<span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(2)">Price<span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(3)">Subtotal<span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sale_items)) { ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No sale items found.</td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($sale_items as $sale_item) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sale_item['saleId'] ?? $sale_item['creditId']); ?></td>
                                    <td><?php echo htmlspecialchars($sale_item['productName']); ?></td>
                                    <td><?php echo htmlspecialchars($sale_item['quantity']) . (isset($sale_item['unit']) ? ' ' . htmlspecialchars($sale_item['unit']) : ''); ?></td>
                                    <td>₱ <?php echo htmlspecialchars($sale_item['price']); ?></td>
                                    <td>₱ <?php echo htmlspecialchars($sale_item['subTotal']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="saleDetails.php?saleId=<?php echo urlencode($sale_item['saleId'] ?? $sale_item['creditId']); ?>" class="btn-action btn-view">
                                                <span>View Details</span>
                                                <img src="images/view.png" alt="View">
                                            </a>
                                            <form method="post" style="display:inline;" onsubmit="return confirmDelete()">
                                                <input type="hidden" name="sale_itemId" value="<?php echo $sale_item['sale_itemId']; ?>">
                                                <button type="submit" name="delete_sale_item" class="btn-action btn-delete">
                                                    <span>Delete</span>
                                                    <img src="images/delete.png" alt="Delete">
                                                </button>
                                            </form>
                                        </div>
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
        function filterSaleItems() {
            const query = document.getElementById('searchBar').value.toLowerCase();
            const rows = document.querySelectorAll('#saleItemTable tbody tr');

            rows.forEach(row => {
                const productName = row.cells[0].textContent.toLowerCase();
                row.style.display = productName.includes(query) ? '' : 'none';
            });
        }

        function clearSearch() {
            document.getElementById('searchBar').value = '';
            filterSaleItems();
            toggleClearIcon();
        }

        function toggleClearIcon() {
            const searchBar = document.getElementById('searchBar');
            const clearIcon = document.querySelector('.clear-icon');
            clearIcon.style.display = searchBar.value ? 'block' : 'none';
        }

        function confirmDelete() {
            return confirm('Are you sure you want to delete this sale item?');
        }

        function sortTable(columnIndex) {
            const table = document.getElementById('saleItemTable');
            const rows = Array.from(table.rows).slice(1);
            const isAscending = table.getAttribute('data-sort-order') === 'asc';
            const direction = isAscending ? 1 : -1;

            rows.sort((a, b) => {
                const aText = a.cells[columnIndex].textContent.trim();
                const bText = b.cells[columnIndex].textContent.trim();

                if (!isNaN(aText) && !isNaN(bText)) {
                    return direction * (parseFloat(aText) - parseFloat(bText));
                }

                return direction * aText.localeCompare(bText);
            });

            rows.forEach(row => table.tBodies[0].appendChild(row));
            table.setAttribute('data-sort-order', isAscending ? 'desc' : 'asc');

            // Update sort icons
            const headers = table.querySelectorAll('th');
            headers.forEach((header, index) => {
                const icon = header.querySelector('.sort-icon img');
                if (index === columnIndex) {
                    icon.classList.toggle('sort-asc', isAscending);
                    icon.classList.toggle('sort-desc', !isAscending);
                } else {
                    icon.classList.remove('sort-asc', 'sort-desc');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            toggleClearIcon();
        });
    </script>
</body>

</html>