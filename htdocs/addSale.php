<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

// Fetch all products with their stock levels
$sql = "SELECT p.productId, p.productName, p.unitPrice, COALESCE(SUM(b.quantity), 0) AS stockLevel 
        FROM products p 
        LEFT JOIN batchItem b ON p.productId = b.productId 
        WHERE b.quantity > 0 
        GROUP BY p.productId, p.productName, p.unitPrice";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error fetching products: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record a Sale</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .form-container {
            align-content: center;
            background-color: rgb(17, 18, 22, 0.7);
            padding: 10px;
            border-radius: 12px;
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

        /* Enhanced Input Fields */
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

        .form-control1:focus,
        .form-control2:focus {
            border: 2px solid #335fff;
            color: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(51, 95, 255, 0.2);
        }

        /* Improved Placeholder */
        ::placeholder {
            color: rgba(247, 247, 248, 0.5);
            opacity: 1;
        }

        /* Enhanced Buttons */
        .btn-primary,
        .btn-success {
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

        .btn-primary:hover,
        .btn-success:hover {
            background-color: rgba(85, 119, 255, 0.9);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(51, 95, 255, 0.3);
        }

        .btn-success {
            background-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-success:disabled {
            background-color: rgba(108, 117, 125, 0.8);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Improved Clear Button */
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

        /* Enhanced Alerts */
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

        .alert .fa-times {
            cursor: pointer;
            margin-left: auto;
        }

        /* Enhanced Product Search */
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

        .stock-level,
        .price-level {
            color: #94a3b8;
            font-size: 0.85em;
            margin-left: 5px;
        }

        /* Enhanced Selected Products Grid */
        .selected-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            max-height: 50vh;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #335fff #1e2028;
        }

        .selected-products::-webkit-scrollbar {
            width: 8px;
        }

        .selected-products::-webkit-scrollbar-track {
            background: #1e2028;
            border-radius: 8px;
        }

        .selected-products::-webkit-scrollbar-thumb {
            background-color: #335fff;
            border-radius: 8px;
        }

        /* Styled Cards for Selected Products */
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
            word-break: break-word;
        }

        .selected-product-info {
            color: #94a3b8;
            font-size: 0.85em;
            display: block;
            margin-top: 3px;
        }

        .selected-product input {
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .selected-product input[type="number"] {
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

        /* Enhanced Back Button */
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

        /* Item-specific Subtotal */
        .item-subtotal {
            font-weight: bold;
            color: #f7f7f8;
            margin-top: 8px;
            padding-top: 5px;
            border-top: 1px solid rgba(51, 57, 66, 0.5);
            text-align: right;
        }

        /* Sale Summary Box */
        .sale-summary {
            background-color: rgba(39, 41, 48, 0.8);
            border: 1px solid rgba(51, 57, 66, 0.5);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .sale-summary:hover {
            border-color: rgba(51, 95, 255, 0.5);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            align-items: center;
        }

        .summary-label {
            font-weight: 500;
            color: #94a3b8;
        }

        .summary-value {
            font-weight: 600;
            color: #f7f7f8;
            font-size: 1.1em;
        }

        .total-price {
            font-size: 1.5em;
            color: #f7f7f8;
            font-weight: 600;
        }

        .change-amount {
            font-size: 1.2em;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        #changeRow {
            transition: all 0.3s ease;
            opacity: 0;
            height: 0;
            overflow: hidden;
            margin: 0;
        }

        #changeRow.visible {
            opacity: 1;
            height: auto;
            margin-bottom: 10px;
            padding-top: 10px;
        }

        .amount-paid-input {
            max-width: 200px;
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

        /* No Results */
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

        /* Required indicator */
        .required {
            color: #dc3545;
            margin-left: 3px;
        }

        /* Responsive Styling */
        @media (max-width: 768px) {
            .form-container {
                padding: 5px;
                width: 100%;
            }

            .form-control1, .form-control2 {
                padding: 10px;
            }

            .selected-products {
                grid-template-columns: 1fr;
                max-height: 40vh;
            }

            .selected-product {
                padding: 12px;
            }
            
            .total-price, .change-amount {
                font-size: 1.2em;
            }
        }

        hr {
            height: 1px;
            border: 0;
            background-color: rgba(255, 255, 255, 0.2);
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <div class="main-content fade-in">
        <div class="form-container">
            <a href="#" class="btn-back-wrapper" id="back-button">
                <img src="images/back.png" alt="Back" class="btn-back" id="another-image">
                <b><span>Back</span></b>
            </a>
            <br>
            
            <h3 class="text-center flex-grow-1 m-0">Record a Sale</h3>
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
                    <?php unset($_SESSION['message']); unset($_SESSION['alert_class']); ?>
                </script>
            <?php endif; ?>
            
            <form method="POST" action="process_sale.php" id="saleForm">
                <div class="product-search-container">
                    <label for="productSearch" class="form-label">Select Products: <span class="required">*</span></label>
                    <img src="images/search-icon.png" class="search-icon" alt="Search">
                    <input type="text" id="productSearch" class="form-control1" placeholder="Search by product name" autocomplete="off">
                    <div class="product-list">
                        <?php 
                            mysqli_data_seek($result, 0);
                            while ($row = mysqli_fetch_assoc($result)) { 
                                if ($row['stockLevel'] > 0) { // Only show products with stock
                        ?>
                            <div class="product-item" data-product-id="<?= $row['productId'] ?>" onclick="selectProduct(<?= $row['productId'] ?>, '<?= htmlspecialchars($row['productName'], ENT_QUOTES) ?>', <?= $row['unitPrice'] ?>, <?= $row['stockLevel'] ?>)">
                                <label>
                                    <?= htmlspecialchars($row['productName']) ?>
                                    <span class="price-level">(₱ <?= number_format($row['unitPrice'], 2) ?>)</span>
                                    <span class="stock-level">Stock: <?= number_format($row['stockLevel']) ?></span>
                                </label>
                            </div>
                        <?php 
                                }
                            } 
                        ?>
                        <div class="no-results">No products found matching your search</div>
                    </div>
                </div>

                <div class="selected-products" id="selectedProductsContainer"></div>
                
                <div class="empty-state" id="emptyState">
                    <div class="empty-state-icon">🛒</div>
                    <p>No items in cart yet</p>
                    <p>Search and select products above to begin</p>
                </div>
                
                <div class="sale-summary">
                    <div class="summary-row">
                        <span class="summary-label">Total Items:</span>
                        <span class="summary-value" id="totalItems">0</span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Total Amount:</span>
                        <span class="summary-value total-price">₱ <span id="totalPrice">0.00</span></span>
                    </div>
                    
                    <hr>
                    
                    <div class="summary-row">
                        <span class="summary-label">
                            Amount Paid:
                            <span class="required">*</span>
                            <span class="tooltip-trigger">?
                                <span class="tooltip-content">Enter the amount paid by the customer</span>
                            </span>
                        </span>
                        <input type="number" id="amountPaid" name="amountPaid" step="0.01" min="0" class="form-control2 amount-paid-input" placeholder="Enter amount" required>
                    </div>
                    
                    <div class="summary-row" id="changeRow">
                        <span class="summary-label">Change:</span>
                        <span class="summary-value change-amount" id="change">₱ 0.00</span>
                    </div>
                </div>

                <input type="hidden" name="transactionType" value="Cash">
                <button type="submit" name="submit" id="processSaleBtn" class="btn-success" disabled>Process Sale</button>
            </form>
        </div>
    </div>

    <script>
        // Enhanced product filtering
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

        // Enhanced product selection with modern card UI
        function selectProduct(productId, productName, unitPrice, stockLevel) {
            const selectedProducts = document.getElementById('selectedProductsContainer');
            const productItem = document.createElement('div');
            productItem.className = 'selected-product';
            productItem.setAttribute('data-product-id', productId);
            productItem.setAttribute('data-price', unitPrice);
            
            productItem.innerHTML = `
                <div class="selected-product-header">
                    <div>
                        <span class="selected-product-name">${productName}</span>
                        <span class="selected-product-info">Price: ₱${unitPrice.toFixed(2)} | Stock: ${stockLevel}</span>
                    </div>
                    <button type="button" class="btn-clear" onclick="removeProduct(this, ${productId})">✕</button>
                </div>
                
                <label class="input-label">
                    Quantity
                    <span class="tooltip-trigger">?
                        <span class="tooltip-content">Maximum quantity: ${stockLevel}</span>
                    </span>
                </label>
                <input type="number" name="quantity[]" min="1" max="${stockLevel}" value="1" class="form-control2 quantity-input" onchange="updateItemSubtotal(this); calculateTotal();" required>
                
                <div class="item-subtotal">
                    Subtotal: ₱<span class="item-price">${unitPrice.toFixed(2)}</span>
                </div>
                
                <input type="hidden" name="productId[]" value="${productId}">
                <input type="hidden" name="price[]" value="${unitPrice.toFixed(2)}">
            `;
            
            selectedProducts.appendChild(productItem);
            document.getElementById('productSearch').value = '';
            
            // Mark this product as selected
            const productItemElement = document.querySelector(`.product-item[data-product-id="${productId}"]`);
            if (productItemElement) {
                productItemElement.classList.add('selected');
            }
            
            document.querySelector('.product-list').style.display = 'none';
            
            updateEmptyState();
            calculateTotal();
            
            // Add animation class
            setTimeout(() => productItem.classList.add('animated'), 10);
        }

        // Update subtotal for a specific item
        function updateItemSubtotal(quantityInput) {
            const productCard = quantityInput.closest('.selected-product');
            const unitPrice = parseFloat(productCard.getAttribute('data-price'));
            const quantity = parseInt(quantityInput.value) || 0;
            const subtotal = unitPrice * quantity;
            
            productCard.querySelector('.item-price').textContent = subtotal.toFixed(2);
        }

        // Enhanced total calculation
        function calculateTotal() {
            let total = 0;
            let totalItems = 0;
            
            document.querySelectorAll('.selected-product').forEach(product => {
                const quantity = parseInt(product.querySelector('input[name="quantity[]"]').value) || 0;
                const price = parseFloat(product.getAttribute('data-price')) || 0;
                
                total += quantity * price;
                totalItems += quantity;
            });
            
            document.getElementById('totalPrice').textContent = total.toFixed(2);
            document.getElementById('totalItems').textContent = totalItems;
            
            calculateChange();
            updateSubmitButton();
        }

        // Improved change calculation function with better visibility control
        function calculateChange() {
            const total = parseFloat(document.getElementById('totalPrice').textContent) || 0;
            const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
            const change = amountPaid - total;
            const changeElement = document.getElementById('change');
            const changeRow = document.getElementById('changeRow');
            
            // Use AJAX-style updates to ensure DOM changes are applied
            if (amountPaid > 0) {
                // Format and update the change text
                changeElement.textContent = '₱' + change.toFixed(2);
                
                // Add/remove classes rather than directly setting style
                changeRow.classList.add('visible');
                
                // Set color based on positive/negative change
                if (change >= 0) {
                    changeElement.style.color = '#28a745'; // Green for positive/zero change
                } else {
                    changeElement.style.color = '#dc3545'; // Red for negative change
                }
                
                // Log for debugging
                console.log("Change displayed:", change.toFixed(2));
            } else {
                changeRow.classList.remove('visible');
                console.log("Change hidden (no amount entered)");
            }
        }

        // Remove product with animation
        function removeProduct(button, productId) {
            const productItem = button.closest('.selected-product');
            
            // Add fade-out animation
            productItem.style.opacity = '0';
            productItem.style.transform = 'scale(0.9)';
            
            setTimeout(() => {
                productItem.remove();
                
                // Re-enable the product in the dropdown
                const productListItem = document.querySelector(`.product-item[data-product-id="${productId}"]`);
                if (productListItem) {
                    productListItem.classList.remove('selected');
                }
                
                updateEmptyState();
                calculateTotal();
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
            const total = parseFloat(document.getElementById('totalPrice').textContent) || 0;
            const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
            const processSaleButton = document.getElementById('processSaleBtn');
            
            // Enable button only if products selected AND sufficient payment
            processSaleButton.disabled = (selectedProducts.length === 0 || amountPaid < total);
        }

        function closeAlert() {
            document.getElementById("alert").style.display = "none";
        }
        
        // Enhanced form validation and submission
        function confirmProcessSale(event) {
            const total = parseFloat(document.getElementById('totalPrice').textContent) || 0;
            const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
            
            // First validate payment amount
            if (amountPaid < total) {
                event.preventDefault();
                alert('Amount paid must be greater than or equal to the total price.');
                return;
            }
            
            // Check if any products are selected
            if (document.querySelectorAll('.selected-product').length === 0) {
                event.preventDefault();
                alert('Please select at least one product.');
                return;
            }
            
            // Check for valid quantities
            let allQuantitiesValid = true;
            document.querySelectorAll('.quantity-input').forEach(input => {
                const val = parseInt(input.value);
                const max = parseInt(input.max);
                
                if (isNaN(val) || val <= 0 || val > max) {
                    allQuantitiesValid = false;
                    input.style.borderColor = '#dc3545';
                } else {
                    input.style.borderColor = '';
                }
            });
            
            if (!allQuantitiesValid) {
                event.preventDefault();
                alert('Please enter valid quantities within the available stock limits.');
                return;
            }
            
            // Final confirmation
            if (!confirm('Are you sure you want to process this sale?')) {
                event.preventDefault();
            }
        }

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
            
            // Handle form submission with validation
            document.getElementById('saleForm').addEventListener('submit', confirmProcessSale);
            
            // Update totals when amount paid changes
            const amountPaidInput = document.getElementById('amountPaid');
        
            // Add multiple event listeners to catch all possible changes
            ['input', 'change', 'keyup', 'blur'].forEach(function(event) {
                amountPaidInput.addEventListener(event, function() {
                    calculateChange();
                    updateSubmitButton();
                });
            });
            
            // Force an initial calculation
            setTimeout(calculateChange, 100);
            
            // Click outside to close dropdown
            document.addEventListener('click', function(event) {
                const productSearch = document.getElementById('productSearch');
                const productList = document.querySelector('.product-list');
                
                if (!productSearch.contains(event.target) && !productList.contains(event.target)) {
                    productList.style.display = 'none';
                }
            });
            
            // Handle back button
            const backButton = document.getElementById('back-button');
            backButton.addEventListener('click', function(e) {
                e.preventDefault();
                window.history.back();
            });
            
            // Button hover effect
            document.getElementById('another-image').addEventListener('mouseover', function() {
                this.src = 'images/back-hover.png';
            });

            document.getElementById('another-image').addEventListener('mouseout', function() {
                this.src = 'images/back.png';
            });
            
            // Initialize empty state
            updateEmptyState();
        });
    </script>
</body>

</html>