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
        .main-content {
            max-height: 93vh;
        }

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
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            position: relative;
            /* Ensures the image is positioned relative to it */
            overflow: hidden;
            /* Prevents scrolling while allowing overflow */
            width: 100%;
            /* Adjust as needed */
            height: 100vh;
            /* Adjust to fit viewport */
        }


        .product-image img {
            width: 120%;
            height: auto;
            top: 50%;
            left: 50%;
            position: absolute;
            object-fit: contain;
            transform: translate(-50%, -50%);
            transition: transform 0.3s ease;
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
            justify-content: center;
            align-items: stretch;
            /* Ensures all cards align properly */
        }

        .product-card {
            background-color: #1f2024;
            border-radius: 3px;
            overflow: hidden;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.35);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease-in-out;
            flex: 1 1 250px;
            /* Ensures cards expand but do not shrink too much */
            max-width: 285px;
            min-width: 200px;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-info {
            padding: 15px !important;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        /* Ensure image consistency */
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

        .stock-out {
            border-radius: 5px;
            padding: 4px 10px;
            background-color: rgba(255, 17, 17, 0.15);
        }

        .stock-low {
            border-radius: 5px;
            padding: 4px 10px;
            background-color: rgba(255, 116, 17, 0.15);
        }

        .stock-good {
            border-radius: 5px;
            padding: 4px 10px;
            background-color: rgba(41, 255, 17, 0.15);
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
                        <a href="inventory.php" class="grid-button">
                            <img src="images/table-mode.png" alt="Table Mode" style="width: 20px; height: 20px;">
                        </a>
                    </div>
                </div>
            </div>
            <hr>
            <!-- Modified grid view HTML -->
            <div class="view product-grid">
                <?php foreach ($products as $product) {
                    $stockClass = '';
                    $stockText = '';

                    if ($product['totalStock'] == 0) {
                        $stockClass = 'stock-out';
                        $stockText = 'Out of Stock';
                    } elseif ($product['totalStock'] < 5) {
                        $stockClass = 'stock-low';
                        $stockText = htmlspecialchars($product['totalStock']) . (isset($product['unit']) ? ' ' . htmlspecialchars($product['unit']) : '');
                    } else {
                        $stockClass = 'stock-good';
                        $stockText = htmlspecialchars($product['totalStock']) . (isset($product['unit']) ? ' ' . htmlspecialchars($product['unit']) : '');
                    }

                    // Image path handling
                    $imagePath = 'images/no-image.png'; // Default image
                    if (isset($product['image']) && !empty($product['image'])) {
                        $imagePath = $product['image'];
                        if (!file_exists($imagePath)) {
                            $imagePath = 'images/no-image.png';
                        }
                    }

                    $isReferenced = isProductReferencedInSaleItem($conn, $product['productId']);
                ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['productName']); ?>" onerror="this.src='images/no-image.png'">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['productName']); ?></h3>

                            <div class="product-detail">
                                <span class="detail-label">Category:</span>
                                <span><?php echo htmlspecialchars($product['productCategory']); ?></span>
                            </div>

                            <div class="product-detail">
                                <span class="detail-label">Stock:</span>
                                <span class="<?php echo $stockClass; ?>"><?php echo $stockText; ?></span>
                            </div>

                            <div class="product-detail">
                                <span class="detail-label">Price:</span>
                                <span class="product-price">₱<?php echo number_format($product['unitPrice'], 2); ?></span>
                            </div>

                            <div class="card-actions">
                                <a href="editProduct.php?productName=<?php echo urlencode($product['productName']); ?>" class="action-button edit-button">Edit</a>
                                <form method="post" style="display:inline; flex: 1;" onsubmit="return confirmDelete()">
                                    <input type="hidden" name="productId" value="<?php echo $product['productId']; ?>">
                                    <button type="submit" name="delete_product" class="action-button delete-button" <?php echo $isReferenced ? 'disabled' : ''; ?> style="width: 100%;">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <script>
        // Search functionality for grid view only
        function filterProducts() {
            const searchTerm = document.getElementById('searchBar').value.toLowerCase();

            // Filter grid view only (since we're on grid.php)
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach(card => {
                const productName = card.querySelector('.product-name').textContent.toLowerCase();
                const productCategory = card.querySelector('.product-detail:nth-child(2) span:last-child').textContent.toLowerCase();

                if (productName.includes(searchTerm) || productCategory.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function clearSearch() {
            document.getElementById('searchBar').value = '';
            filterProducts();
            document.querySelector('.clear-icon').style.display = 'none';
        }

        function toggleClearIcon() {
            const searchBar = document.getElementById('searchBar');
            const clearIcon = document.querySelector('.clear-icon');

            if (searchBar.value) {
                clearIcon.style.display = 'block';
            } else {
                clearIcon.style.display = 'none';
            }
        }

        // Initialize when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Set up the search icon clear button initial state
            toggleClearIcon();
        });

        function confirmDelete() {
            return confirm("Are you sure you want to delete this product?");
        }
    </script>
</body>

</html>