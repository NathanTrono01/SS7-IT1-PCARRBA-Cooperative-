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

// Function to check if a product is referenced in the sale_item table
function isProductReferencedInSaleItem($conn, $productId)
{
    $query = "SELECT COUNT(*) as count FROM sale_item WHERE productId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

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
            overflow-x: hidden;
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
            background-color: rgb(42, 56, 255) !important;
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

        .btn-delete:disabled {
            border: 1px solid #ff9999 !important;
            color: #ff9999 !important;
            cursor: not-allowed;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .popup-alert {
            position: fixed;
            margin-top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #d4edda;
            border: 1px solid green;
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
            color: green;
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

        th {
            cursor: pointer;
        }

        th img {
            width: 15px;
            height: 15px;
            margin-right: 10px;
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

        /* Ensuring the table takes full width */
        #productTable {
            width: 100%;
            border-collapse: collapse;
        }

        /* Sticky Header */
        #productTable thead {
            position: sticky;
            top: 0;
            background: rgb(17, 18, 22);
            z-index: 10;
        }


        /* Styling for the headers */
        #productTable th {
            background: rgb(17, 18, 22);
            /* Dark background */
            border-bottom: 2px solid #333942;
            text-align: left;
        }

        /* Making tbody scrollable while keeping alignment */
        #productTable tbody {
            display: block;
            max-height: 65vh;
            /* Adjust height dynamically */
        }

        /* Ensuring rows and cells align properly */
        #productTable tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        #productTable td {
            padding: 10px 10px;
            font-size: 1rem;
            margin: 0 5px;
            width: 20%;
        }

        /* Scrollbar for tbody */
        #productTable .table-wrapper::-webkit-scrollbar {
            width: 6px;
            transition: opacity 0.3s ease-in-out;
        }

        /* Track (background of the scrollbar) */
        #productTable .table-wrapper::-webkit-scrollbar-track {
            background: transparent;
        }

        /* Thumb (scrollable part) */
        #productTable .table-wrapper::-webkit-scrollbar-thumb {
            background-color: #555;
            background: grey;
            border-radius: 10rem;
            opacity: 0;
            /* Initially hidden */
        }

        /* Show scrollbar on hover */
        #productTable .table-wrapper:hover::-webkit-scrollbar-thumb {
            opacity: 1;
            /* Visible when hovering */
        }

        /* Hover effect for better UX */
        #productTable .table-wrapper::-webkit-scrollbar-thumb:hover {
            background-color: #777;
        }

        @media (max-width: 1024px) {

            #productTable td {
                padding: 8px 10px;
                font-size: 0.8rem;
                margin: 0 5px;
                width: 20%;
            }

            .table-wrapper {
                /* Responsive height based on viewport */
                overflow-y: auto;
                /* Scrollable body */
                position: relative;
                overflow-x: hidden;
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
                font-size: 0.7em;
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

        .stock-out {
            color: red;
        }

        .stock-low {
            color: orange;
        }

        .stock-good {
            color: limegreen;
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
            <div class="header-container">
                <h2>Products</h2>
                <div class="button">
                    <a href="restock.php" class="restock-button">Restock</a>
                    <a href="insertProduct.php" class="button-product">Add Product</a>
                </div>
                <div class="search-container">
                    <div class="search-wrapper">
                        <img src="images/search-icon.png" alt="Search" class="search-icon">
                        <input type="text" id="searchBar" placeholder="Search Product/s" onkeyup="filterProducts(); toggleClearIcon();">
                        <img src="images/x-circle.png" alt="Clear" class="clear-icon" onclick="clearSearch()">
                    </div>
                </div>
            </div>
            <div class="table-wrapper">
                <table id="productTable" data-sort-order="asc">
                    <thead>
                        <tr align="left">
                            <th onclick="sortTable(0)">Name<span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(1)">Category<span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(2)">Stock<span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
                            <th onclick="sortTable(3)">Price<span class="sort-icon"><img src="images/sort.png" alt="sort"></span></th>
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
                                $isReferenced = isProductReferencedInSaleItem($conn, $product['productId']);
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['productName']); ?></td>
                                    <td><?php echo htmlspecialchars($product['productCategory']); ?></td>
                                    <td class="<?php echo $stockClass; ?>"><?php echo $product['totalStock'] == 0 ? 'Out of Stock' : htmlspecialchars($product['totalStock']) . (isset($product['unit']) ? ' ' . htmlspecialchars($product['unit']) : ''); ?></td>
                                    <td>₱ <?php echo number_format($product['unitPrice'], 2); ?></td>
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
                                                <button type="submit" name="delete_product" class="btn-action btn-delete" <?php echo $isReferenced ? 'disabled' : ''; ?>>
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
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const isAscending = table.getAttribute('data-sort-order') === 'asc';
            const direction = isAscending ? 1 : -1;

            rows.sort((a, b) => {
                const aText = a.cells[columnIndex].textContent.trim();
                const bText = b.cells[columnIndex].textContent.trim();

                if (columnIndex === 2 || columnIndex === 3) { // Stock or Price column
                    const aValue = parseFloat(aText.replace(/[^\d.-]/g, '')) || 0;
                    const bValue = parseFloat(bText.replace(/[^\d.-]/g, '')) || 0;
                    return direction * (aValue - bValue);
                }

                return direction * aText.localeCompare(bText);
            });

            rows.forEach(row => table.querySelector('tbody').appendChild(row));
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