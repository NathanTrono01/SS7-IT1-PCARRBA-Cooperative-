<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db.php';

// Fetch all products with total stock level and category name
$query = "
    SELECT 
        p.productId, 
        p.productName, 
        c.categoryName AS productCategory, 
        p.unitPrice, 
        COALESCE(SUM(b.quantity), 0) AS totalStock,
        p.unit
    FROM 
        products p
    LEFT JOIN 
        batchItem b ON p.productId = b.productId
    LEFT JOIN 
        categories c ON p.categoryId = c.categoryId
    GROUP BY 
        p.productId, p.productName, c.categoryName, p.unitPrice, p.unit
";
$result = $conn->query($query);
$products = $result->fetch_all(MYSQLI_ASSOC);

// Handle form submission for editing or deleting products
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_product'])) {
        // Delete product
        $productId = $_POST['productId'];
        $stmt = $conn->prepare("DELETE FROM products WHERE productId = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $stmt->close();
        // Refresh the page to reflect the changes
        header("Location: inventory.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Inventory</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <!-- <link rel="stylesheet" href="css/inventory.css"> -->
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
            gap: 10px;
            position: relative;
            width: 100%;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            /* Adjust padding to make space for the search icon */
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

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            font-family: Arial, Helvetica, sans-serif;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            table-layout: auto;
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

        table th {
            padding: 7px;
            background-color: rgb(17, 18, 22);
            color: rgba(247, 247, 248, 0.9);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 1rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.83);
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

        .restock-button {
            display: inline-block;
            padding: 8px 12px;
            background: transparent;
            color: rgb(187, 188, 190);
            text-decoration: none;
            border-radius: 7px;
            border: 0.5px solid rgba(187, 188, 190, 0.5);
            transition: border-color 0.3s, color 0.3s;
            margin-bottom: 10px;
        }

        .restock-button:hover {
            background-color: rgba(255, 255, 255, 0.06);
            border: 1.5px solid rgb(187, 188, 190);
            color: #fff;
            border-radius: 7px;
        }

        .button-product {
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

        .button-product:hover {
            background-color: rgba(255, 255, 255, 0.94);
            color: #000;
            border-radius: 7px;
        }

        .btn-action {
            text-decoration: none;
            padding: 6px 8px;
            font-size: 0.9rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 60px;
            text-align: center;
            box-sizing: border-box;
        }

        .btn-action img {
            display: none;
        }

        .btn-action span {
            display: inline;
        }

        .btn-edit {
            background-color: #335fff !important;
            color: white !important;
        }

        .btn-delete {
            border: 1px solid #ff3d3d !important;
            background-color: transparent !important;
            color: #ff3d3d !important;
        }

        .btn-delete:hover {
            border: 1px solid rgb(255, 0, 0) !important;
            color: rgb(255, 0, 0) !important;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        @media (max-width: 1024px) {
            .table-wrapper {
                padding: 10px;
                overflow-x: auto;
            }

            table {
                width: 100%;
                table-layout: auto;
            }

            table th,
            table td {
                font-size: 0.95rem;
                padding: 6px;
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
                padding: 10px;
                overflow-x: auto;
            }

            table {
                width: 100%;
                table-layout: auto;
            }

            table th,
            table td {
                font-size: 0.85rem;
                padding: 6px;
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
                width: 100%;
                overflow-x: auto;
            }

            table {
                font-size: 0.8rem;
                width: 100%;
                overflow-x: hidden;
                white-space: nowrap;
            }

            table th,
            table td {
                width: 100%;
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

        .popup-alert {
            position: fixed;
            margin-top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(40, 167, 70, 0.44);
            border: 1px solid rgb(0, 255, 60);
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }

        .popup-alert.show {
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

        .stock-out {
            color: red;
        }

        .stock-low {
            color: orange;
        }

        .stock-good {
            color: limegreen;
        }

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
            <div class="table-wrapper">
                <div class="header-container">
                    <h2>Products</h2>
                    <div class="button">
                        <a href="restock.php" class="restock-button">Restock</a>
                        <a href="insertProduct.php" class="button-product">Add Product</a>
                    </div>
                </div>
                <div class="search-container">
                    <div class="search-wrapper">
                        <img src="images/search-icon.png" alt="Search" class="search-icon">
                        <input type="text" id="searchBar" placeholder="Search Product/s" onkeyup="filterProducts(); toggleClearIcon();">
                        <img src="images/x-circle.png" alt="Clear" class="clear-icon" onclick="clearSearch()">
                    </div>
                </div>
                <hr style="height: 1px; border: none; color: rgb(187, 188, 190); background-color: rgb(187, 188, 190);">
                <table id="productTable" data-sort-order="asc">
                    <thead>
                        <tr align="left">
                            <th onclick="sortTable(0)">Name <span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(1)">Category <span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(2)">Stock <span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(3)">Price (₱) <span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)) { ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No products found. <a href="insertProduct.php">Stock a product</a>.</td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($products as $product) {
                                $stockClass = '';
                                if ($product['totalStock'] == 0) {
                                    $stockClass = 'stock-out';
                                } elseif ($product['totalStock'] < 5) {
                                    $stockClass = 'stock-low';
                                } else {
                                    $stockClass = 'stock-good';
                                }
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['productName']); ?></td>
                                    <td><?php echo htmlspecialchars($product['productCategory']); ?></td>
                                    <td class="<?php echo $stockClass; ?>"><?php echo $product['totalStock'] == 0 ? 'Out of Stock' : htmlspecialchars($product['totalStock']) . (isset($product['unit']) ? ' ' . htmlspecialchars($product['unit']) : ''); ?></td>
                                    <td>₱ <?php echo htmlspecialchars($product['unitPrice']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="productId" value="<?php echo $product['productId']; ?>">
                                                <a href="editProduct.php?productName=<?php echo urlencode($product['productName']); ?>" class="btn-action btn-edit">
                                                    <span>Edit</span>
                                                    <img src="images/white-pencil.png" alt="Edit">
                                                </a>
                                            </form>
                                            <form method="post" style="display:inline;" onsubmit="return confirmDelete()">
                                                <input type="hidden" name="productId" value="<?php echo $product['productId']; ?>">
                                                <button type="submit" name="delete_product" class="btn-action btn-delete">
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
    </div>

    <script>
        function filterProducts() {
            const query = document.getElementById('searchBar').value.toLowerCase();
            const rows = document.querySelectorAll('#productTable tbody tr');

            rows.forEach(row => {
                const productName = row.cells[0].textContent.toLowerCase();
                row.style.display = productName.includes(query) ? '' : 'none';
            });
        }

        function clearSearch() {
            document.getElementById('searchBar').value = '';
            filterProducts();
            toggleClearIcon();
        }

        function toggleClearIcon() {
            const searchBar = document.getElementById('searchBar');
            const clearIcon = document.querySelector('.clear-icon');
            clearIcon.style.display = searchBar.value ? 'block' : 'none';
        }

        function confirmDelete() {
            return confirm('Are you sure you want to delete this item?');
        }

        function closeAlert() {
            document.getElementById("alert").style.display = "none";
        }

        document.addEventListener('DOMContentLoaded', () => {
            toggleClearIcon();
        });

        function sortTable(columnIndex) {
            const table = document.getElementById('productTable');
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
    </script>
</body>

</html>