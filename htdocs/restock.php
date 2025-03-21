<?php
session_start();
include('db.php');
include 'functions.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Check if userId is set in the session
if (!isset($_SESSION['userId'])) {
    die("User ID is not set in the session.");
}


// Get the userId from the session
$userId = $_SESSION['userId'];

// Fetch all products with their stock levels
$sql = "SELECT p.productId, p.productName, COALESCE(SUM(b.quantity), 0) AS stockLevel 
        FROM products p 
        LEFT JOIN batchItem b ON p.productId = b.productId 
        GROUP BY p.productId, p.productName";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error fetching products: " . mysqli_error($conn));
}

// Check if a product is pre-selected
$preselectedProduct = null;
if (isset($_GET['product'])) {
    $preselectedProduct = $_GET['product'];
}

if (isset($_POST['restock_items'])) {
    $productIds = $_POST['productId'];
    $quantities = $_POST['quantity'];
    $costPrices = $_POST['cost_price'];

    foreach ($productIds as $index => $productId) {
        $additionalQuantity = $quantities[$index];
        $costPrice = $costPrices[$index];

        // Validate quantity input
        if ($additionalQuantity < 1) {
            $additionalQuantity = 1;
        }

        // Insert new batch item
        $insert_batch_sql = "INSERT INTO batchItem (quantity, costPrice, productId, userId) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_batch_sql);
        $stmt->bind_param("idii", $additionalQuantity, $costPrice, $productId, $userId);

        if (!$stmt->execute()) {
            $_SESSION['message'] = "Error restocking item: " . $stmt->error;
            $_SESSION['alert_class'] = "alert-danger";
            break;
        }

        // Calculate the new total stock
        $total_stock_sql = "SELECT COALESCE(SUM(quantity), 0) AS totalStock FROM batchItem WHERE productId = ?";
        $stmt = $conn->prepare($total_stock_sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $totalStock = $row['totalStock'];

        // Update the total stock in the inventory table
        $update_inventory_sql = "UPDATE inventory SET totalStock = ? WHERE productId = ?";
        $stmt = $conn->prepare($update_inventory_sql);
        $stmt->bind_param("ii", $totalStock, $productId);

        if (!$stmt->execute()) {
            $_SESSION['message'] = "Error updating inventory: " . $stmt->error;
            $_SESSION['alert_class'] = "alert-danger";
            break;
        }

        // Log the restock action
        logAction('Restock', [$productId], [$additionalQuantity], $userId, $conn);
    }

    if (!isset($_SESSION['message'])) {
        $_SESSION['message'] = "Product/s restocked successfully!";
        $_SESSION['alert_class'] = "alert-success";
        header("Location: inventory.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restock Products</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        .form-container {
            align-content: center;
            background-color: rgb(17, 18, 22, 0.7);
            padding: 10px;
            border-radius: 12px;
            width: 100%;
            max-width: 100%;
        }

        .form-container h1,
        .form-container h3 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            color: whitesmoke;
            text-align: center;
        }

        /* Input Fields Styling */
        .form-c,
        .form-control1,
        .form-control2 {
            color: white;
            padding: 12px;
            font-size: 1rem;
            border-radius: 8px;
            width: 100%;
            border: 1px solid rgba(208, 217, 251, 0.12);
            background-color: rgba(208, 217, 251, 0.08);
            transition: all 0.3s ease;
        }

        .form-c:focus,
        .form-control1:focus,
        .form-control2:focus {
            border: 2px solid #335fff;
            color: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(51, 95, 255, 0.2);
        }

        /* Placeholder Styling */
        ::placeholder {
            color: rgba(247, 247, 248, 0.5);
            opacity: 1;
        }

        /* Button Styling */
        .btn-primary {
            color: rgb(255, 255, 255);
            background-color: rgb(42, 56, 255);
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }

        .btn-primary:hover {
            background-color: rgba(85, 119, 255, 0.9);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(51, 95, 255, 0.3);
        }

        /* Clear Button */
        .btn-clear {
            position: absolute;
            top: 8px;
            right: 8px;
            color: rgb(255, 58, 58);
            background-color: transparent;
            border: none;
            width: 30px;
            height: 30px;
            padding: 5px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-clear:hover {
            color: #ff0000;
            background-color: rgba(255, 0, 0, 0.1);
            transform: scale(1.1);
        }

        /* Alert Styling */
        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease-out;
            margin-bottom: 20px;
            position: relative;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.9);
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.9);
            border-left: 4px solid #dc3545;
        }

        .alert-warning {
            background-color: rgba(255, 152, 0, 0.9);
            border-left: 4px solid #ff9800;
        }

        .alert .fa-times {
            cursor: pointer;
            margin-left: auto;
        }

        /* Product List Styling */
        .product-search-container {
            position: relative;
            margin-bottom: 20px;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 40px;
            width: 16px;
            height: 16px;
            opacity: 0.6;
        }

        #productSearch {
            padding-left: 36px;
            /* Make room for the search icon */
        }

        .product-list {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 8px;
            border-radius: 8px;
            background-color: rgba(23, 25, 30, 0.95);
            border: 1px solid rgba(51, 57, 66, 0.8);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            position: absolute;
            width: 100%;
            backdrop-filter: blur(4px);
            display: none;
            scrollbar-width: thin;
            scrollbar-color: #335fff #1e2028;
        }

        .product-list::-webkit-scrollbar {
            width: 8px;
        }

        .product-list::-webkit-scrollbar-track {
            background: #1e2028;
            border-radius: 8px;
        }

        .product-list::-webkit-scrollbar-thumb {
            background-color: #335fff;
            border-radius: 8px;
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid rgba(51, 57, 66, 0.5);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-item:hover {
            background-color: rgba(51, 95, 255, 0.1);
        }

        .product-item.selected {
            display: none;
        }

        .product-item label {
            flex: 1;
            color: #f7f7f8;
            cursor: pointer;
            margin: 0;
            font-weight: normal;
        }

        .stock-level {
            color: #94a3b8;
            font-size: 0.85em;
            margin-left: 5px;
        }

        /* Selected Products Styling */
        .selected-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .selected-product {
            display: flex;
            flex-direction: column;
            padding: 15px;
            border-radius: 8px;
            background-color: rgba(39, 41, 48, 0.8);
            position: relative;
            border: 1px solid rgba(51, 57, 66, 0.5);
            transition: all 0.3s ease;
            animation: fadeIn 0.3s ease-out;
        }

        .selected-product:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            border-color: rgba(51, 95, 255, 0.5);
        }

        .selected-product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            position: relative;
        }

        .selected-product-name {
            color: #f7f7f8;
            font-weight: 500;
            margin-bottom: 5px;
            padding-right: 30px;
            /* Make room for the X button */
            word-break: break-word;
        }

        .selected-product-stock {
            color: #94a3b8;
            font-size: 0.85em;
            display: block;
            margin-top: 3px;
        }

        .selected-product input {
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .selected-product input[type="number"],
        .selected-product input[type="text"] {
            background-color: rgba(17, 18, 22, 0.7);
            border: 1px solid rgba(51, 57, 66, 0.5);
            color: #f7f7f8;
            padding: 8px 12px;
            border-radius: 6px;
        }

        .selected-product input:focus {
            border-color: #335fff;
            box-shadow: 0 0 0 2px rgba(51, 95, 255, 0.2);
        }

        .input-label {
            color: #94a3b8;
            font-size: 0.85em;
            margin-bottom: 4px;
            display: block;
        }

        /* Back Button Styling */
        .btn-back-wrapper {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #f7f7f8;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .btn-back-wrapper:hover {
            transform: translateX(-3px);
        }

        .btn-back-wrapper span {
            margin-left: 10px;
            font-size: 16px;
        }

        .btn-back-wrapper img {
            width: 25px;
            height: 25px;
            transition: all 0.3s ease;
        }

        /* Empty State */
        .empty-state {
            display: none;
            text-align: center;
            padding: 30px 20px;
            color: #94a3b8;
        }

        .empty-state.visible {
            display: block;
            animation: fadeIn 0.5s ease-out;
        }

        .empty-state-icon {
            font-size: 40px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .form-container {
                padding: 5px;
                width: 100%;
            }

            .form-c,
            .form-control1,
            .form-control2 {
                padding: 10px;
            }

            .selected-products {
                grid-template-columns: 1fr;
            }

            .selected-product {
                padding: 12px;
            }
        }

        hr {
            height: 1px;
            border: 0;
            background-color: rgba(255, 255, 255, 0.2);
            margin: 20px 0;
        }

        /* Unit Selection */
        .input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .input-group>div {
            flex: 1;
            position: relative;
        }

        .input-group-addon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .no-results {
            padding: 15px;
            color: #94a3b8;
            text-align: center;
            font-style: italic;
            display: none;
        }

        /* Tooltip styles */
        .tooltip-trigger {
            position: relative;
            display: inline-block;
            margin-left: 5px;
            width: 16px;
            height: 16px;
            background-color: rgba(148, 163, 184, 0.2);
            color: #94a3b8;
            border-radius: 50%;
            text-align: center;
            line-height: 16px;
            font-size: 10px;
            font-weight: bold;
            cursor: help;
        }

        .tooltip-content {
            visibility: hidden;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            background-color: rgba(23, 25, 30, 0.95);
            color: #f7f7f8;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            font-weight: normal;
            border: 1px solid rgba(51, 57, 66, 0.5);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .tooltip-content::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: rgba(23, 25, 30, 0.95) transparent transparent transparent;
        }

        .tooltip-trigger:hover .tooltip-content {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>
    <div class="main-content fade-in">
        <div class="form-container">
            <div class="container">
                <a href="#" class="btn-back-wrapper" id="back-button">
                    <img src="images/back.png" alt="Back" class="btn-back" id="another-image">
                    <b><span>Back</span></b>
                </a>
                <br>

                <h3 class="text-center flex-grow-1 m-0">Restock Products</h3>
                <hr>

                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert <?php echo $_SESSION['alert_class']; ?>" id="alert">
                        <i class="fas <?php echo $_SESSION['alert_class'] === 'alert-success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <span><?php echo $_SESSION['message']; ?></span>
                        <i class="fas fa-times" onclick="closeAlert()"></i>
                    </div>

                    <script>
                        setTimeout(function() {
                            document.getElementById("alert").style.display = "none";
                        }, 4000);
                        <?php unset($_SESSION['message']);
                        unset($_SESSION['alert_class']); ?>
                    </script>
                <?php endif; ?>

                <form method="POST" id="restockForm">
                    <div class="product-search-container">
                        <label for="productSearch" class="form-label">Select Products to Restock:</label>
                        <img src="images/search-icon.png" class="search-icon" alt="Search">
                        <input type="text" id="productSearch" class="form-control1" placeholder="Search for products..." autocomplete="off">
                        <div class="product-list">
                            <!-- Products will be loaded here -->
                            <?php
                            // Reset the result pointer
                            mysqli_data_seek($result, 0);
                            $hasProducts = false;
                            while ($row = mysqli_fetch_assoc($result)) {
                                $hasProducts = true;
                            ?>
                                <div class="product-item" data-product-id="<?= $row['productId'] ?>" data-product-name="<?= $row['productName'] ?>" onclick="selectProduct('<?= $row['productId'] ?>', '<?= htmlspecialchars($row['productName'], ENT_QUOTES) ?>', '<?= number_format($row['stockLevel'] ?? 0) ?>')">
                                    <label>
                                        <?= htmlspecialchars($row['productName']) ?>
                                        <span class="stock-level">(Current stock: <?= number_format($row['stockLevel'] ?? 0) ?>)</span>
                                    </label>
                                </div>
                            <?php } ?>
                            <div class="no-results">No products found matching your search</div>
                        </div>
                    </div>

                    <div class="selected-products" id="selectedProductsContainer"></div>

                    <div class="empty-state" id="emptyState">
                        <div class="empty-state-icon">📦</div>
                        <p>No products selected for restocking yet</p>
                        <p>Search and select products above to begin</p>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="restock_items" class="btn-primary" id="restockButton" disabled>Restock Selected Products</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Enhanced filtering of products
        function filterProducts() {
            const query = document.getElementById('productSearch').value.toLowerCase();
            const productList = document.querySelector('.product-list');
            const noResults = document.querySelector('.no-results');
            let hasResults = false;

            document.querySelectorAll('.product-item').forEach(item => {
                const productName = item.querySelector('label').textContent.toLowerCase();
                if (productName.includes(query) && !item.classList.contains('selected')) {
                    item.style.display = 'flex';
                    hasResults = true;
                } else {
                    item.style.display = 'none';
                }
            });

            // Show/hide the product list based on query and results
            if (query.length > 0) {
                productList.style.display = 'block';
                noResults.style.display = hasResults ? 'none' : 'block';
            } else {
                productList.style.display = 'none';
            }

            return hasResults;
        }

        // Enhanced product selection
        function selectProduct(productId, productName, stockLevel) {
            const selectedProducts = document.querySelector('.selected-products');
            const productItem = document.createElement('div');
            productItem.className = 'selected-product';
            productItem.setAttribute('data-product-id', productId);

            productItem.innerHTML = `
                <div class="selected-product-header">
                    <div>
                        <span class="selected-product-name">${productName}</span>
                        <span class="selected-product-stock">Current stock: ${stockLevel}</span>
                    </div>
                    <button type="button" class="btn-clear" onclick="removeProduct(this, '${productId}')">✕</button>
                </div>
                
                <label class="input-label">Quantity to add 
                    <span class="tooltip-trigger">?
                        <span class="tooltip-content">Enter the number of units to add to inventory</span>
                    </span>
                </label>
                <input type="number" name="quantity[]" min="1" value="1" class="form-control2" required>
                
                <label class="input-label">Cost price per unit (₱) 
                    <span class="tooltip-trigger">?
                        <span class="tooltip-content">Enter the cost price per unit (how much you paid for each item)</span>
                    </span>
                </label>
                <input type="number" name="cost_price[]" placeholder="Enter cost price" class="form-control2" required>
                
                <input type="hidden" name="productId[]" value="${productId}">
            `;

            selectedProducts.appendChild(productItem);
            document.getElementById('productSearch').value = '';
            document.querySelector(`.product-item[data-product-id="${productId}"]`).classList.add('selected');
            document.querySelector('.product-list').style.display = 'none';

            updateEmptyState();
            updateSubmitButton();

            // Add animation class
            setTimeout(() => productItem.classList.add('animated'), 10);
        }

        function removeProduct(button, productId) {
            const productItem = button.closest('.selected-product');

            // Add fade-out animation
            productItem.style.opacity = '0';
            productItem.style.transform = 'scale(0.9)';

            setTimeout(() => {
                productItem.remove();
                document.querySelector(`.product-item[data-product-id="${productId}"]`).classList.remove('selected');
                updateEmptyState();
                updateSubmitButton();
            }, 300);
        }

        function updateEmptyState() {
            const selectedProducts = document.querySelectorAll('.selected-product');
            const emptyState = document.getElementById('emptyState');

            if (selectedProducts.length === 0) {
                emptyState.classList.add('visible');
            } else {
                emptyState.classList.remove('visible');
            }
        }

        function updateSubmitButton() {
            const selectedProducts = document.querySelectorAll('.selected-product');
            const restockButton = document.getElementById('restockButton');

            restockButton.disabled = selectedProducts.length === 0;
        }

        function closeAlert() {
            document.getElementById("alert").style.display = "none";
        }

        // Click outside to close dropdown
        document.addEventListener('click', function(event) {
            const productSearch = document.getElementById('productSearch');
            const productList = document.querySelector('.product-list');

            // If click is outside the search and dropdown
            if (!productSearch.contains(event.target) && !productList.contains(event.target)) {
                productList.style.display = 'none';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const productSearch = document.getElementById('productSearch');
            const productList = document.querySelector('.product-list');

            // Show dropdown when clicking on search field
            productSearch.addEventListener('focus', function() {
                if (this.value.length > 0) {
                    filterProducts();
                }
            });

            // Filter products as user types
            productSearch.addEventListener('input', filterProducts);

            // Handle back button dynamically
            var referrer = document.referrer;
            var backButton = document.getElementById('back-button');
            if (referrer.includes('inventory.php')) {
                backButton.href = 'inventory.php';
            } else if (referrer.includes('dashboard.php')) {
                backButton.href = 'dashboard.php';
            } else if (referrer.includes('grid.php')) {
                backButton.href = 'grid.php';
            } else {
                backButton.href = 'inventory.php'; // Default fallback
            }

            // Button hover effect
            document.getElementById('another-image').addEventListener('mouseover', function() {
                this.src = 'images/back-hover.png';
            });

            document.getElementById('another-image').addEventListener('mouseout', function() {
                this.src = 'images/back.png';
            });

            // Pre-select the product if provided
            const preselectedProduct = "<?php echo $preselectedProduct; ?>";
            if (preselectedProduct) {
                const productItems = document.querySelectorAll('.product-item');
                for (let item of productItems) {
                    if (item.dataset.productName === preselectedProduct) {
                        item.click();
                        break;
                    }
                }
            }

            // Initialize empty state
            updateEmptyState();
            updateSubmitButton();

            // Validate form on submit
            document.getElementById('restockForm').addEventListener('submit', function(e) {
                const selectedProducts = document.querySelectorAll('.selected-product');
                let isValid = true;

                selectedProducts.forEach(product => {
                    const quantityInput = product.querySelector('input[name="quantity[]"]');
                    const costInput = product.querySelector('input[name="cost_price[]"]');

                    if (!quantityInput.value || quantityInput.value < 1) {
                        quantityInput.style.borderColor = '#dc3545';
                        isValid = false;
                    } else {
                        quantityInput.style.borderColor = '';
                    }

                    if (!costInput.value || isNaN(parseFloat(costInput.value))) {
                        costInput.style.borderColor = '#dc3545';
                        isValid = false;
                    } else {
                        costInput.style.borderColor = '';
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields with valid values.');
                }
            });
        });
    </script>
</body>

</html>