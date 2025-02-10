<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

// Fetch all products
$sql = "SELECT * FROM products";
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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        .form-container {
            background-color: #191a1f;
            padding: 20px;
            margin-top: 10px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.21);
            width: 95%;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
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

        .form-control {
            border-radius: 8px;
            padding: 10px;
            border: 1px solid #ddd;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.25);
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

        .btn-primary:hover {
            background-color: rgb(0, 75, 156);
        }

        .btn-back {
            display: inline-block;
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            margin-top: 20px;
            text-align: center;
            transition: background-color 0.3s ease;
        }

        .btn-back:hover {
            background-color: #5a6268;
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
        }

        .product-list {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
            display: none;
            /* Hide initially */
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

        .summary-container {
            border: 1px solid #ddd;
            display: none;
            background-color: #191a1f;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            color: black;
            /* Change text color to black */
        }

        .summary-container h4,
        .summary-container p,
        .summary-container ul {
            color: #f7f7f8;
            /* Change text color to black */
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .receipt-table th,
        .receipt-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .receipt-table th {
            background-color: #f2f2f2;
        }

        .receipt-total {
            text-align: right;
            font-weight: bold;
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

        function toggleCashInput() {
            const transactionType = document.querySelector('input[name="transactionType"]:checked').value;
            const cashInput = document.getElementById('cashInput');
            if (transactionType === 'Cash') {
                cashInput.style.display = 'block';
            } else {
                cashInput.style.display = 'none';
            }
        }

        function filterProducts() {
            const query = document.getElementById('productSearch').value.toLowerCase();
            const productList = document.querySelector('.product-list');
            let hasResults = false;

            document.querySelectorAll('.product-item').forEach(item => {
                const productName = item.querySelector('label').textContent.toLowerCase();
                if (productName.includes(query)) {
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
                <input type="number" name="quantity[]" min="1" max="${stockLevel}" value="1" class="form-control" onchange="calculateTotal()" required>
                <input type="hidden" name="productId[]" value="${productId}">
                <input type="hidden" name="price[]" value="${unitPrice}">
                <button type="button" class="btn btn-danger" onclick="removeProduct(this)">X</button>
            `;
            selectedProducts.appendChild(productItem);
            document.getElementById('productSearch').value = '';
            document.querySelector('.product-list').style.display = 'none';
            calculateTotal();
        }

        function removeProduct(button) {
            const productItem = button.parentElement;
            productItem.remove();
            calculateTotal();
        }

        function showSummary() {
            const summaryContainer = document.getElementById('summaryContainer');
            const summaryList = document.getElementById('summaryList');
            summaryList.innerHTML = '';

            document.querySelectorAll('.selected-product').forEach(item => {
                const productName = item.querySelector('label').textContent.split(' (')[0]; // Remove stock and price info
                const quantity = item.querySelector('input[type="number"]').value;
                const price = item.querySelector('input[name="price[]"]').value;
                const amount = (quantity * price).toFixed(2);
                summaryList.innerHTML += `<li>${quantity} x ${productName} @ ${price} PHP = ${amount} PHP</li>`;
            });

            const totalPrice = document.getElementById('totalPrice').textContent;
            const amountPaid = document.getElementById('amountPaid').value;
            const change = document.getElementById('change').textContent;

            document.getElementById('summaryTotalPrice').textContent = `${totalPrice} PHP`;
            document.getElementById('summaryAmountPaid').textContent = `${amountPaid} PHP`;
            document.getElementById('summaryChange').textContent = change;

            summaryContainer.style.display = 'block';
        }

        function confirmProcessSale(event) {
            if (!confirm('Are you sure you want to process this sale?')) {
                event.preventDefault();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('productSearch').addEventListener('input', filterProducts);
            document.querySelectorAll('input[name="transactionType"]').forEach(radio => {
                radio.addEventListener('change', toggleCashInput);
            });
            document.querySelector('form').addEventListener('submit', confirmProcessSale);
        });
    </script>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <div class="main-content">
        <div class="form-container">
            <h1>Record a Sale</h1>
            <form method="POST" action="process_sale.php">
                <div class="mb-3">
                    <label for="productSearch" class="form-label">Search Products: <span class="required">*</span></label>
                    <input type="text" id="productSearch" class="form-control" placeholder="Search by product name">
                    <div class="product-list">
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <div class="product-item" onclick="selectProduct('<?= $row['productId'] ?>', '<?= $row['productName'] ?>', '<?= $row['unitPrice'] ?>', '<?= $row['stockLevel'] ?>')">
                                <label><?= $row['productName'] ?> (<?= $row['unitPrice'] ?> PHP | Stock: <?= $row['stockLevel'] ?>)</label>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="selected-products"></div>
                <div class="mb-3">
                   <h1><label class="form-label">Total Price: <span id="totalPrice">0.00</span> PHP</label></h1>
                </div>

                <div class="mb-3">
                    <label class="form-label">Transaction Type: <span class="required">*</span></label>
                    <div>
                        <input type="radio" name="transactionType" value="Cash" id="transactionTypeCash" required>
                        <label for="transactionTypeCash">Cash</label>
                        <input type="radio" name="transactionType" value="Credit" id="transactionTypeCredit" required>
                        <label for="transactionTypeCredit">Credit</label>
                    </div>
                </div>

                <div id="cashInput" class="mb-3" style="display: none;">
                    <label for="amountPaid" class="form-label">Amount Paid: <span class="required">*</span></label>
                    <input type="number" id="amountPaid" name="amountPaid" step="0.01" class="form-control" oninput="calculateChange()" required>
                    <br>
                    <h3><p>Change: <span id="change" style="display: none;">N/A</span> PHP</p></h3>
                </div>

                <button type="button" class="btn-primary w-100" onclick="showSummary()">Calculate</button>
                <div id="summaryContainer" class="summary-container">
                    <h4>Summary</h4>
                    <ul id="summaryList"></ul>
                    <p>Total Price: <span id="summaryTotalPrice">0.00</span></p>
                    <p>Amount Paid: <span id="summaryAmountPaid">0.00</span></p>
                    <p>Change: <span id="summaryChange">N/A</span></p>
                    <input type="submit" name="submit" value="Process Sale" class="btn-success w-100">
                </div>
            </form>
        </div>
    </div>
</body>

</html>