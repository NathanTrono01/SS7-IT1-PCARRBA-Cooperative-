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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.79);
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
            background-color: #007bff;
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
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
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .product-item label {
            flex: 1;
            color: #f7f7f8;
        }

        .product-item input[type="number"] {
            width: 80px;
        }
    </style>
    <script>
        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('input[name="quantity[]"]').forEach((input, index) => {
                const quantity = parseInt(input.value) || 0;
                const price = parseFloat(document.querySelectorAll('input[name="price[]"]')[index].value) || 0;
                total += quantity * price;
            });
            document.getElementById('totalPrice').textContent = total.toFixed(2);
        }

        function calculateChange() {
            const total = parseFloat(document.getElementById('totalPrice').textContent) || 0;
            const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
            const change = amountPaid - total;
            document.getElementById('change').textContent = change.toFixed(2);
        }

        function toggleCashInput() {
            const transactionType = document.querySelector('select[name="transactionType"]').value;
            const cashInput = document.getElementById('cashInput');
            if (transactionType === 'Cash') {
                cashInput.style.display = 'block';
            } else {
                cashInput.style.display = 'none';
            }
        }

        function filterProducts() {
            const query = document.getElementById('productSearch').value.toLowerCase();
            document.querySelectorAll('.product-item').forEach(item => {
                const productName = item.querySelector('label').textContent.toLowerCase();
                item.style.display = productName.includes(query) ? 'flex' : 'none';
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('input[name="productId[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', calculateTotal);
            });
            document.querySelectorAll('input[name="quantity[]"]').forEach(input => {
                input.addEventListener('input', calculateTotal);
            });
            document.getElementById('productSearch').addEventListener('input', filterProducts);
        });
    </script>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>

    <div class="main-content">
        <div class="form-container">
            <h1>Record a Sale</h1>
            <form method="POST" action="process_sale.php">
                <div class="mb-3">
                    <label for="productSearch" class="form-label">Search Products:</label>
                    <input type="text" id="productSearch" class="form-control" placeholder="Search by product name">
                </div>
                <div class="product-list">
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <div class="product-item">
                            <label>
                                <input type="checkbox" name="productId[]" value="<?= $row['productId'] ?>">
                                <?= $row['productName'] ?> (₱<?= $row['unitPrice'] ?> | Stock: <?= $row['stockLevel'] ?>)
                            </label>
                            <input type="number" name="quantity[]" min="1" max="<?= $row['stockLevel'] ?>" value="1" class="form-control" onchange="calculateTotal()">
                            <input type="hidden" name="price[]" value="<?= $row['unitPrice'] ?>">
                        </div>
                    <?php } ?>
                </div>

                <h3>Total Price: ₱<span id="totalPrice">0.00</span></h3>

                <div class="mb-3">
                    <label for="transactionType" class="form-label">Transaction Type:</label>
                    <select name="transactionType" id="transactionType" class="form-select" onchange="toggleCashInput()">
                        <option value="" disabled selected>Select transaction type</option>
                        <option value="Cash">Cash</option>
                        <option value="Credit">Credit</option>
                    </select>
                </div>

                <div id="cashInput" class="mb-3" style="display: none;">
                    <label for="amountPaid" class="form-label">Amount Paid:</label>
                    <input type="number" id="amountPaid" name="amountPaid" step="0.01" class="form-control" onchange="calculateChange()">
                    <p>Change: ₱<span id="change">0.00</span></p>
                </div>

                <input type="submit" name="submit" value="Process Sale" class="btn btn-primary w-100">
            </form>
        </div>
    </div>
</body>

</html>