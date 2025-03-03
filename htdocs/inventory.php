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
                    <div class="toggle-container">
                        <a href="grid.php" class="grid-button">
                            <img src="images/grid-mode.png" alt="Grid Mode" style="width: 20px; height: 20px;">
                        </a>
                    </div>
                </div>
            </div>
            <div id="tableView" class="view active table-wrapper">
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
                                $imagePath = isset($product['image']) && !empty($product['image'])
                                    ? (file_exists($product['image']) ? $product['image'] : 'images/no-image.png')
                                    : 'images/no-image.png';
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
    <script src="js/inventory.js"></script>
</body>

</html>