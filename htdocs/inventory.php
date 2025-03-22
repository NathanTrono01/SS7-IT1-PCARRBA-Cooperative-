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
        p.unit,
        p.imagePath as image
    FROM 
        products p
    LEFT JOIN 
        batchItem b ON p.productId = b.productId
    LEFT JOIN 
        categories c ON p.categoryId = c.categoryId
    GROUP BY 
        p.productId, p.productName, c.categoryName, p.unitPrice, p.unit, p.imagePath
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
    <link rel="stylesheet" href="css/inventory.css">
    <style>
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }

        .grid-item {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .grid-button {
            display: inline-block;
            padding: 8px 12px;
            background: transparent;
            color: rgb(187, 188, 190);
            text-decoration: none;
            border-radius: 7px;
            border: 0.5px solid rgba(187, 188, 190, 0.5);
            transition: border-color 0.3s, color 0.3s;
        }

        .grid-button:hover {
            background-color: rgba(255, 255, 255, 0.06);
            border: 1.5px solid rgb(187, 188, 190);
            color: #fff;
            border-radius: 7px;
        }

        /* New styles for product images */
        .product-image {
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .product-image img:hover {
            transform: scale(1.05);
        }

        .grid-actions {
            margin-top: 10px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        /* Updated product grid styles with flexbox layout */
        .product-grid {
            display: flex !important;
            flex-wrap: wrap;
            gap: 15px;
            padding: 10px 0;
            flex-direction: row;
            justify-content: flex-start;
            /* Center-aligned */
        }

        .product-card {
            background-color: #1e293b;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease-in-out;
            width: 180px;
            /* Fixed width */
            margin: 0;
            /* Remove auto margins */
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        /* Product image - keep same height but ensure consistent width */
        .product-image {
            height: 140px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.05);
            width: 100%;
        }

        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        /* Product info - tighter spacing */
        .product-info {
            padding: 10px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        /* Detail items - tighter spacing */
        .product-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
            align-items: center;
        }

        .detail-label {
            color: #94a3b8;
            min-width: 55px;
        }

        /* Make product name smaller too */
        .product-name {
            font-size: 15px;
            font-weight: 600;
            margin: 0 0 6px 0;
            line-height: 1.2;
            height: 36px;
            overflow: hidden;
        }

        /* Improved button styles */
        .card-actions {
            display: flex;
            gap: 5px;
            margin-top: 8px;
        }

        .action-button {
            padding: 6px 0;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            border: none;
            text-align: center;
            flex: 1;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .edit-button {
            background-color: #0284c7;
            color: white;
        }

        .edit-button:hover {
            background-color: #0369a1;
        }

        .delete-button {
            background-color: #b91c1c;
            color: white;
        }

        .delete-button:hover {
            background-color: #991b1b;
        }

        .delete-button[disabled] {
            background-color: #7f1d1d;
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Responsive adjustments */
        @media (max-width: 1400px) {
            .product-card {
                width: 185px;
            }
        }

        @media (max-width: 992px) {
            .product-card {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .product-grid {
                justify-content: center;
            }

            .product-card {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .product-grid {
                gap: 10px;
            }

            .product-card {
                width: 100%;
            }

            .product-image {
                height: 110px;
            }

            .action-button {
                padding: 5px 0;
                font-size: 12px;
            }
        }

        /* Stock level badges */
        .stock-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
            text-align: center;
            min-width: 35px;
            width: 70%;
        }

        .stock-out {
            background-color: rgba(235, 87, 87, 0.15);
            color: #EB5757;
            border: 1px solid rgba(235, 87, 87, 0.3);
        }

        .stock-low {
            background-color: rgba(242, 153, 74, 0.15);
            color: #F39C12;
            border: 1px solid rgba(242, 153, 74, 0.3);
        }

        .stock-good {
            background-color: rgba(39, 174, 96, 0.15);
            color: #27AE60;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }

        /* Sort indicators for table headers */
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
        }

        /* Hover effect for all table headers */
        table th:hover {
            background-color: rgba(51, 95, 255, 0.1);
            border-bottom: 2px solid #335fff;
        }

        /* Product table specific styling */
        #productTable th {
            padding: 12px 15px;
            border-bottom: 2px solid #333942;
            font-weight: 600;
        }

        #productTable th:hover {
            background-color: rgba(51, 95, 255, 0.1);
            border-bottom: 2px solid #335fff;
        }

        /* Additional table styling */
        #productTable {
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
        }

        #productTable td {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(51, 57, 66, 0.2);
        }

        table th:hover {
            background-color: rgba(51, 95, 255, 0.1);
        }

        .search-results-count {
            display: none;
            font-size: 14px;
            color: #94a3b8;
            margin-left: 15px;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .stock-badge {
                min-width: unset;
                /* Remove minimum width */
                width: auto;
                /* Let it be as wide as needed */
                padding: 4px;
            }
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
                <h2>Product Management</h2>
                <div class="button">
                    <a title="Restock product" href="restock.php" class="restock-button">Restock</a>
                    <a title="New product" href="insertProduct.php" class="button-product">Add Product</a>
                </div>
                <div class="search-container">
                    <div class="search-wrapper">
                        <img src="images/search-icon.png" alt="Search" class="search-icon">
                        <input type="text" id="searchBar" placeholder="Search Product/s" onkeyup="filterProducts(); toggleClearIcon();">
                        <img src="images/x-circle.png" alt="Clear" class="clear-icon" onclick="clearSearch()">
                    </div>
                    <div class="toggle-container">
                        <a title="Grid View" href="grid.php" class="grid-button">
                            <img src="images/grid-mode.png" alt="Grid Mode" style="width: 20px; height: 20px;">
                        </a>
                    </div>
                </div>
                <span class="search-results-count" id="searchResultsCount"></span>
            </div>
            <div id="tableView" class="view active table-wrapper">
                <table id="productTable" data-sort-order="asc" data-sort-column="0">
                    <thead>
                        <tr align="left">
                            <th onclick="sortTable(0)">Name <span class="sort-indicator active-sort">▼</span></th>
                            <th onclick="sortTable(1)">Category <span class="sort-indicator">◆</span></th>
                            <th onclick="sortTable(2)">Stock <span class="sort-indicator">◆</span></th>
                            <th onclick="sortTable(3)">Price <span class="sort-indicator">◆</span></th>
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
                                $imagePath = isset($product['image']) && !empty($product['image'])
                                    ? (file_exists($product['image']) ? $product['image'] : 'images/no-image.png')
                                    : 'images/no-image.png';
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['productName']); ?></td>
                                    <td><?php echo htmlspecialchars($product['productCategory']); ?></td>
                                    <td>
                                        <span class="stock-badge <?php echo $stockClass; ?>">
                                            <?php echo $product['totalStock'] == 0 ? 'Out of Stock' : htmlspecialchars($product['totalStock']) . (isset($product['unit']) ? ' ' . htmlspecialchars($product['unit']) : ''); ?>
                                        </span>
                                    </td>
                                    <td>₱ <?php echo number_format($product['unitPrice'], 2); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="productId" value="<?php echo $product['productId']; ?>">
                                                <a title="Edit product" href="editProduct.php?productName=<?php echo urlencode($product['productName']); ?>" class="btn-action btn-edit">
                                                    <span>Edit</span>
                                                    <img src="images/white-pencil.png" alt="Edit">
                                                </a>
                                            </form>
                                            <form method="post" style="display:inline;" onsubmit="return confirmDelete()">
                                                <input type="hidden" name="productId" value="<?php echo $product['productId']; ?>">
                                                <button title="Delete product" type="submit" name="delete_product" class="btn-action btn-delete" <?php echo $isReferenced ? 'disabled' : ''; ?>>
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
    <script src="js/inventory.js"></script>
    <script>
        // Enhanced table sorter function
        function sortTable(columnIndex) {
            const table = document.getElementById("productTable");
            const tbody = table.querySelector("tbody");
            const rows = Array.from(tbody.querySelectorAll("tr"));
            const isNumericColumn = (columnIndex === 2 || columnIndex === 3); // Stock and Price columns

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
                let cellA = rowA.cells[columnIndex].innerText.trim();
                let cellB = rowB.cells[columnIndex].innerText.trim();

                // Special handling for price column
                if (columnIndex === 3) { // Price column
                    const numA = parseFloat(cellA.replace(/[^\d.-]/g, ""));
                    const numB = parseFloat(cellB.replace(/[^\d.-]/g, ""));
                    return sortOrder === "asc" ? numA - numB : numB - numA;
                }

                // Special handling for stock column
                if (columnIndex === 2) { // Stock column
                    if (cellA === "Out of Stock") cellA = "0";
                    if (cellB === "Out of Stock") cellB = "0";

                    const numA = parseInt(cellA.replace(/[^\d.-]/g, "")) || 0;
                    const numB = parseInt(cellB.replace(/[^\d.-]/g, "")) || 0;
                    return sortOrder === "asc" ? numA - numB : numB - numA;
                }

                // Text comparison for other columns
                return sortOrder === "asc" ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
            });

            // Reorder rows
            rows.forEach(row => {
                tbody.appendChild(row);
            });
        }

        // Initialize sort on page load
        document.addEventListener('DOMContentLoaded', () => {
            // Initial sort on name column
            sortTable(0);
        });
    </script>
    <!-- Update the filterProducts function to show search result counts -->
    <script>
        function filterProducts() {
            const searchInput = document.getElementById('searchBar');
            const filter = searchInput.value.toLowerCase();
            const table = document.getElementById('productTable');
            const tr = table.getElementsByTagName('tr');
            let visibleCount = 0;

            // Loop through all table rows, and hide those who don't match the search query
            for (let i = 1; i < tr.length; i++) { // Start at 1 to skip header row
                const nameCell = tr[i].getElementsByTagName('td')[0];
                const categoryCell = tr[i].getElementsByTagName('td')[1];
                if (nameCell && categoryCell) {
                    const nameText = nameCell.textContent || nameCell.innerText;
                    const categoryText = categoryCell.textContent || categoryCell.innerText;

                    if (nameText.toLowerCase().indexOf(filter) > -1 ||
                        categoryText.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                        visibleCount++;
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }

            // Update search results count
            const resultsCounter = document.getElementById('searchResultsCount');
            if (filter.length > 0) {
                resultsCounter.textContent = `${visibleCount} product${visibleCount !== 1 ? 's' : ''} found`;
                resultsCounter.style.display = 'inline-block';
            } else {
                resultsCounter.style.display = 'none';
            }
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

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            toggleClearIcon();
            sortTable(0);
        });
    </script>
</body>

</html>