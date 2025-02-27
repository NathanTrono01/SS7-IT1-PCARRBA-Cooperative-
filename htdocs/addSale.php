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
        .form-container {
            background-color: transparent;
            padding: 10px;
            align-content: center;
        }

        .form-container h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #f7f7f8;
            text-align: center;
        }

        .form-label {
            font-weight: 500;
            color: #f7f7f8;
        }

        .form-label .required {
            color: red;
        }

        .form-control1 {
            border-radius: 8px;
            padding: 10px;
            border: 1px solid rgba(208, 217, 251, .12);
            background-color: rgba(208, 217, 251, .08);
            color: #f7f7f8;
            transition: border-color 0.3s ease, background-color 0.3s ease;
        }

        .form-control2 {
            border-radius: 5px;
            padding: 7px;
            border: 1px solid rgba(208, 217, 251, .12);
            background-color: rgba(208, 217, 251, .08);
            color: #f7f7f8;
            transition: border-color 0.3s ease, background-color 0.3s ease;
        }

        .form-control1:focus {
            border: 2px solid;
            border-color: #335fff;
            color: white;
            outline: none;
        }

        .btn-primary {
            color: whitesmoke;
            background-color: #335fff;
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .btn-clear {
            color: red;
            background-color: transparent;
            border: 1px solid red;
            width: 30px;
            padding: 5px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .btn-clear:hover {
            background-color: rgba(255, 0, 0, 0.24);
        }

        .btn-primary:hover {
            background-color: rgb(0, 75, 156);
        }

        .btn-back img {
            background-color: transparent;
            color: red;
            padding: 10px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            text-align: center;
            transition: background-color 0.3s ease;
        }

        .btn-back:hover img {
            content: url('images/back-hover.png');
        }

        .btn-success {
            color: whitesmoke;
            background-color: #28a745;
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-success:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }

        .alert {
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease-out;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #28a745;
        }

        .alert-danger {
            background-color: #dc3545;
        }

        .alert .fa-times {
            cursor: pointer;
            margin-left: auto;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-100%);
            }

            to {
                transform: translateY(0);
            }
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .form-row .form-group {
            flex: 1;
            min-width: 200px;
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
            }

            .form-container h1 {
                font-size: 20px;
            }

            .form-row {
                flex-direction: column;
            }

            .alert {
                width: 100%;
            }

            .selected-products {
                max-height: 300px;
                overflow-y: auto;
            }
        }

        @media (min-width: 769px) {
            .selected-products {
                max-height: 240px;
                overflow-y: auto;
            }
        }

        .product-list {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
            display: none;
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
        }

        .product-item label {
            flex: 1;
            color: #f7f7f8;
        }

        .selected-products {
            margin-bottom: 20px;
        }

        .selected-product {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .selected-product label {
            flex: 1;
            color: #f7f7f8;
        }

        .selected-product input[type="number"] {
            width: 80px;
        }
    </style>
    <script>
        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('.selected-product input[name="quantity[]"]').forEach((input, index) => {
                const quantity = parseInt(input.value) || 0;
                const price = parseFloat(document.querySelectorAll('.selected-product input[name="price[]"]')[index].value) || 0;
                total += quantity * price;
            });
            document.getElementById('totalPrice').textContent = total.toFixed(2);
            calculateChange();
            validateAmountPaid();
        }

        function calculateChange() {
            const total = parseFloat(document.getElementById('totalPrice').textContent) || 0;
            const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
            const change = amountPaid - total;
            const changeElement = document.getElementById('change');
            if (isNaN(change) || amountPaid === 0) {
                changeElement.style.display = 'none';
            } else {
                changeElement.style.display = 'inline';
                changeElement.textContent = change.toFixed(2);
            }
        }

        function validateAmountPaid() {
            const total = parseFloat(document.getElementById('totalPrice').textContent) || 0;
            const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
            const processSaleButton = document.querySelector('input[name="submit"]');
            if (amountPaid >= total) {
                processSaleButton.disabled = false;
            } else {
                processSaleButton.disabled = true;
            }
        }

        function filterProducts() {
            const query = document.getElementById('productSearch').value.toLowerCase();
            const productList = document.querySelector('.product-list');
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

            productList.style.display = hasResults ? 'block' : 'none';
        }

        function selectProduct(productId, productName, unitPrice, stockLevel) {
            const selectedProducts = document.querySelector('.selected-products');
            const productItem = document.createElement('div');
            productItem.className = 'selected-product';
            productItem.innerHTML = `
                <label>${productName} (PHP${unitPrice} | Stock: ${stockLevel})</label>
                <input type="number" name="quantity[]" min="1" max="${stockLevel}" value="1" class="form-control2" style="" onchange="calculateTotal()" required>
                <input type="hidden" name="productId[]" value="${productId}">
                <input type="hidden" name="price[]" value="${unitPrice}">
                &nbsp;
                <button type="button" class="btn-clear" onclick="removeProduct(this, '${productId}')">X</button>
            `;
            selectedProducts.appendChild(productItem);
            document.getElementById('productSearch').value = '';
            document.querySelector(`.product-item[data-product-id="${productId}"]`).classList.add('selected');
            document.querySelector('.product-list').style.display = 'none';
            calculateTotal();
        }

        function removeProduct(button, productId) {
            const productItem = button.parentElement;
            productItem.remove();
            document.querySelector(`.product-item[data-product-id="${productId}"]`).classList.remove('selected');
            calculateTotal();
        }

        function confirmProcessSale(event) {
            const total = parseFloat(document.getElementById('totalPrice').textContent) || 0;
            const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
            if (amountPaid < total) {
                alert('Amount Paid must be greater than or equal to Total Price.');
                event.preventDefault();
                return;
            }
            if (!confirm('Are you sure you want to process this sale?')) {
                event.preventDefault();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('productSearch').addEventListener('input', filterProducts);
            document.querySelector('form').addEventListener('submit', confirmProcessSale);
            document.getElementById('amountPaid').addEventListener('input', validateAmountPaid);
        });
    </script>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <div class="main-content fade-in">
        <div class="form-container">
            <a href="transaction.php" class="btn-back">
                <img src="images/back.png" alt="Back">
            </a>
            <script>
                document.getElementById('another-image').addEventListener('mouseover', function() {
                    document.querySelector('.btn-back').src = 'images/back-hover.png';
                });

                document.getElementById('another-image').addEventListener('mouseout', function() {
                    document.querySelector('.btn-back').src = 'images/back.png';
                });
            </script>
            <h1>Record a Sale</h1>
            <form method="POST" action="process_sale.php">
                <div class="mb-3">
                    <label for="productSearch" class="form-label">Select Product: <span class="required">*</span></label><br>
                    <input type="text" id="productSearch" class="form-control1" placeholder="Search by product name" style="width: 100%;">
                    <div class="product-list">
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <div class="product-item" data-product-id="<?= $row['productId'] ?>" onclick="selectProduct('<?= $row['productId'] ?>', '<?= $row['productName'] ?>', '<?= number_format($row['unitPrice'], 2) ?>', '<?= number_format($row['stockLevel']) ?>')">
                                <label><?= $row['productName'] ?> (<?= number_format($row['unitPrice'], 2) ?> PHP | Stock: <?= number_format($row['stockLevel']) ?>)</label>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="selected-products"></div>
                <div class="mb-3">
                    <h1><label class="form-label">Total Price: <span id="totalPrice">0.00</span> PHP</label></h1>
                </div>

                <div class="mb-3">
                    <label class="form-label">Amount Paid: <span class="required">*</span></label><br>
                    <input type="number" id="amountPaid" name="amountPaid" step="0.01" class="form-control1" placeholder="Enter amount paid" oninput="calculateChange()" style="width: 100%;" required min="1" value="1">
                    <br>
                    <br>
                    <h3>
                        <p>Change: <span id="change" style="display: none;">N/A</span> PHP</p>
                    </h3>
                </div>

                <input type="hidden" name="transactionType" value="Cash">
                <input type="submit" name="submit" value="Process Sale" class="btn-success w-100" disabled>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('amountPaid').addEventListener('input', function() {
            if (this.value < 0) {
                this.value = 0;
            }
        });
    </script>
</body>

</html>