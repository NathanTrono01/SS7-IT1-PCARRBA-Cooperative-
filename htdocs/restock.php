<?php
session_start();
include('db.php');

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
    }

    if (!isset($_SESSION['message'])) {
        $_SESSION['message'] = "Items restocked successfully!";
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
            background-color: transparent;
            padding: 20px;
            border-radius: 10px;
            width: 95%;
            max-width: 1200px;
            margin-left: 10px;
            margin-right: 10px;
        }

        .form-container h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            color: whitesmoke;
            text-align: center;
        }

        .form-c {
            color: white;
            padding: 8.5px;
            font-size: 1rem;
            border-radius: 7.5px;
            width: 100%;
        }

        .form-c:focus {
            border: 3px solid;
            border-color: #335fff;
            color: white;
            outline: none;
            background-color: rgba(0, 0, 0, 0.7);
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
            color: white;
            background-color: #335fff;
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

        .alert-warning {
            background-color: #ff9800;
        }

        .alert .fa-times {
            cursor: pointer;
            margin-left: auto;
        }

        .form-group input {
            background-color: rgba(208, 217, 251, .08);
            margin-bottom: 10px;
            color: white;
            border: 1px solid rgba(208, 217, 251, .12);
            padding: 8.5px;
            font-size: 1rem;
            border-radius: 7.5px;
            width: 100%;
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

        function selectProduct(productId, productName, stockLevel) {
            const selectedProducts = document.querySelector('.selected-products');
            const productItem = document.createElement('div');
            productItem.className = 'selected-product';
            productItem.innerHTML = `
                <label>${productName} (Stock: ${stockLevel})</label>
                <input type="number" name="quantity[]" min="1" value="1" class="form-control2" required>
                <input type="hidden" name="productId[]" value="${productId}">
                &nbsp;
                <input type="text" name="cost_price[]" placeholder="Enter cost price" class="form-control2" required>
                &nbsp;
                <button type="button" class="btn-clear" onclick="removeProduct(this, '${productId}')">X</button>
            `;
            selectedProducts.appendChild(productItem);
            document.getElementById('productSearch').value = '';
            document.querySelector(`.product-item[data-product-id="${productId}"]`).classList.add('selected');
            document.querySelector('.product-list').style.display = 'none';
        }

        function removeProduct(button, productId) {
            const productItem = button.parentElement;
            productItem.remove();
            document.querySelector(`.product-item[data-product-id="${productId}"]`).classList.remove('selected');
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('productSearch').addEventListener('input', filterProducts);

            // Pre-select the product if provided
            const preselectedProduct = "<?php echo $preselectedProduct; ?>";
            if (preselectedProduct) {
                const productItem = document.querySelector(`.product-item[data-product-name="${preselectedProduct}"]`);
                if (productItem) {
                    productItem.click();
                }
            }
        });
    </script>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>
    <div class="main-content fade-in">
        <div class="form-container">
            <div class="container">
                <img src="images/back.png" alt="Another Image" class="btn-back" id="another-image" onclick="window.history.back()">
                <script>
                    document.getElementById('another-image').addEventListener('mouseover', function() {
                        document.querySelector('.btn-back').src = 'images/back-hover.png';
                    });

                    document.getElementById('another-image').addEventListener('mouseout', function() {
                        document.querySelector('.btn-back').src = 'images/back.png';
                    });
                </script>
                <h3 class="text-center flex-grow-1 m-0">Restock Products</h3>
                <hr style="height: 1px; border: white; color: rgb(255, 255, 255); background-color: rgb(255, 255, 255);">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert <?php echo $_SESSION['alert_class']; ?>" id="alert">
                        <i class="fas <?php echo $_SESSION['alert_class'] === 'alert-success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <span><?php echo $_SESSION['message']; ?></span>
                        <i class="fas fa-times" onclick="closeAlert()"></i>
                    </div>

                    <script>
                        setTimeout(function() {
                            document.getElementById("alert").style.opacity = "0";
                        }, 4000);
                    </script>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="productSearch" class="form-label">Select Product:</label>
                        <br>
                        <input type="text" id="productSearch" class="form-control1" placeholder="Search by product name" style="width: 100%;">
                        <div class="product-list">
                            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                <div class="product-item" data-product-id="<?= $row['productId'] ?>" data-product-name="<?= $row['productName'] ?>" onclick="selectProduct('<?= $row['productId'] ?>', '<?= $row['productName'] ?>', '<?= number_format($row['stockLevel'] ?? 0) ?>')">
                                    <label><?= $row['productName'] ?> (Stock: <?= number_format($row['stockLevel'] ?? 0) ?>)</label>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="selected-products"></div>
                    <div class="d-grid">
                        <button type="submit" name="restock_items" class="btn-primary">Restock Item/s</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function closeAlert() {
            document.getElementById("alert").style.display = "none";
        }
    </script>
</body>

</html>