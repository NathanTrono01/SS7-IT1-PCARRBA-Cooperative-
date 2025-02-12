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

// Fetch all products with sales count
$query = "
    SELECT p.*, COALESCE(SUM(si.quantity), 0) AS salesCount
    FROM products p
    LEFT JOIN sale_item si ON p.productId = si.productId
    GROUP BY p.productId
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

        .search-wrapper input::placeholder {
            color: rgba(247, 247, 248, 0.64);
            font-weight: 200;
        }

        table {
            font-family: Arial, Helvetica, sans-serif;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #1f1f1f;
        }

        table th {
            padding: 7px;
            background-color: #0c0c0f;
            color: rgba(247, 247, 248, 0.9);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 1rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.83);
            border-top: 2px solid #333942;
            border-bottom: 2px solid #333942;
            width: 20%;
            /* Set each column to take up 20% of the table width */
        }

        table th:first-child {
            border-left: 2px solid #333942;
            border-top: 2px solid #333942;
            border-bottom: 2px solid #333942;
        }

        table th:last-child {
            border-right: 2px solid #333942;
            border-top: 2px solid #333942;
            border-bottom: 2px solid #333942;
            width: 250px;
            /* Adjusted width for the Actions column */
        }

        table td {
            padding: 5px 10px;
            /* Add padding to the sides of the td elements */
            font-size: 1rem;
            color: #eee;
            margin: 0 5px;
            width: 20%;
            /* Set each column to take up 20% of the table width */
        }

        table td:last-child {
            width: 250px;
            /* Adjusted width for the Actions column */
        }

        table tr {
            background-color: transparent;
        }

        table tr:hover {
            background-color: rgba(187, 194, 209, 0.17);
            transition: all 0.3s ease;
        }

        table tr td:first-child {
            border-top-left-radius: 7px;
            border-bottom-left-radius: 7px;
        }

        table tr td:last-child {
            border-top-right-radius: 7px;
            border-bottom-right-radius: 7px;
        }

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
            background: transparent;
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

        .btn-restock {
            background-color: #28a745 !important;
            color: white !important;
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
                padding: 6px 8px;
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
                padding: 6px 8px;
                font-size: 0.75rem;
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
            }

            table {
                font-size: 0.8rem;
                display: block;
                width: 100%;
                overflow-x: auto;
                white-space: nowrap;
            }

            table th,
            table td {
                width: 100%;
                font-size: 0.8rem;
                padding: 5px;
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
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <div class="main-content">
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
                <table id="productTable">
                    <thead>
                        <tr align="left">
                            <th>Name</th>
                            <th>Category</th>
                            <th>Sales</th>
                            <th>Stock</th>
                            <th>Price (₱)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)) { ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No products found. <a href="insertProduct.php">Stock a product</a>.</td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($products as $product) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['productName']); ?></td>
                                    <td><?php echo htmlspecialchars($product['productCategory']); ?></td>
                                    <td><?php echo htmlspecialchars($product['salesCount']); ?></td>
                                    <td><?php echo htmlspecialchars($product['stockLevel']); ?></td>
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

        document.addEventListener('DOMContentLoaded', toggleClearIcon);
    </script>
</body>

</html>