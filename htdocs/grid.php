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
            padding: 0 0 25px 0;
        }

        /* Updated product grid styles with improved layout */
        .product-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
            padding: 10px 5px;
            transition: all 0.3s ease;
        }

        .product-card {
            background: linear-gradient(145deg, #23242a, #1c1e22);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.05);
            height: 100%;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.3);
        }

        /* Improved image container */
        .product-image {
            height: 160px;
            width: 100%;
            position: relative;
            overflow: hidden;
            background: rgba(15, 16, 19, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Add subtle gradient overlay at bottom */
        .product-image::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(to top, rgba(15, 16, 19, 0.7), transparent);
            pointer-events: none;
            z-index: 1;
        }

        /* Improved image sizing and positioning */
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
            transform-origin: center;
            position: absolute;
            top: 0;
            left: 0;
        }

        /* For product images that should maintain aspect ratio (like logos) */
        .product-image.contain-image img {
            object-fit: contain;
            max-height: 90%;
            max-width: 90%;
            width: auto;
            height: auto;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            transition: transform 0.5s ease;
        }


        /* Enhanced product info styling */
        .product-info {
            padding: 16px !important;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            gap: 8px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .product-name {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 6px 0;
            line-height: 1.3;
            max-height: 42px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            color: rgba(255, 255, 255, 0.95);
        }

        .product-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 13px;
            align-items: center;
            padding: 2px 0;
        }

        .detail-label {
            color: #94a3b8;
            font-weight: 500;
            min-width: 70px;
            flex-shrink: 0;
        }

        /* Stock status indicators with improved styling */
        .stock-out {
            border-radius: 20px;
            padding: 3px 10px;
            background-color: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            font-weight: 500;
            font-size: 12px;
            backdrop-filter: blur(5px);
            display: inline-block;
        }

        .stock-low {
            border-radius: 20px;
            padding: 3px 10px;
            background-color: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            font-weight: 500;
            font-size: 12px;
            backdrop-filter: blur(5px);
            display: inline-block;
        }

        .stock-good {
            border-radius: 20px;
            padding: 3px 10px;
            background-color: rgba(34, 197, 94, 0.15);
            color: #22c55e;
            font-weight: 500;
            font-size: 12px;
            backdrop-filter: blur(5px);
            display: inline-block;
        }

        /* Enhanced card actions */
        .card-actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .action-button {
            padding: 8px 0;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            border: none;
            text-align: center;
            flex: 1;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .edit-button {
            background-color: #0891b2;
            color: white;
        }

        .edit-button:hover {
            background-color: #0e7490;
            box-shadow: 0 2px 8px rgba(8, 145, 178, 0.4);
        }

        .delete-button {
            background-color: #b91c1c;
            color: white;
        }

        .delete-button:hover {
            background-color: #991b1b;
            box-shadow: 0 2px 8px rgba(185, 28, 28, 0.4);
        }

        .delete-button[disabled] {
            background-color: #7f1d1d;
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* Product price with enhanced styling */
        .product-price {
            font-weight: 600;
            color: #f8fafc;
        }

        /* Improved responsive behavior */
        @media (max-width: 992px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }

            .product-image {
                height: 140px;
            }
        }

        @media (max-width: 480px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 12px;
            }

            .product-image {
                height: 120px;
            }

            .product-info {
                padding: 12px !important;
            }

            .product-name {
                font-size: 14px;
            }

            .product-detail {
                font-size: 12px;
            }

            .action-button {
                padding: 6px 0;
                font-size: 12px;
            }
        }

        /* Animation for cards appearing */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .product-card {
            animation: fadeInUp 0.4s ease-out forwards;
        }

        /* Staggered animation delay for cards */
        .product-grid .product-card:nth-child(1) {
            animation-delay: 0.05s;
        }

        .product-grid .product-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .product-grid .product-card:nth-child(3) {
            animation-delay: 0.15s;
        }

        .product-grid .product-card:nth-child(4) {
            animation-delay: 0.2s;
        }

        .product-grid .product-card:nth-child(5) {
            animation-delay: 0.25s;
        }

        .product-grid .product-card:nth-child(n+6) {
            animation-delay: 0.3s;
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
            <div class="view product-grid wwfade-in">
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
                                <span class="product-price">₱ <?php echo number_format($product['unitPrice'], 2); ?></span>
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