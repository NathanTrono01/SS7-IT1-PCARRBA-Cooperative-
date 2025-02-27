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

// Fetch categories from the database
$categories = [];
$category_sql = "SELECT categoryId, categoryName FROM categories";
$result = $conn->query($category_sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Add new item to inventory
if (isset($_POST['add_item'])) {
    $productName = $_POST['productName'];
    $categoryId = $_POST['product_category'];
    $newCategory = $_POST['new_category'];
    $stockLevel = $_POST['quantity'];
    $costPrice = $_POST['cost_price'];
    $unitPrice = $_POST['unit_price'];
    $reorderLevel = $_POST['reorder_level'];

    // Use new category if provided
    if (!empty($newCategory)) {
        // Check if the new category already exists
        $check_category_sql = "SELECT categoryId FROM categories WHERE categoryName = ?";
        $stmt = $conn->prepare($check_category_sql);
        $stmt->bind_param("s", $newCategory);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Category exists, use the existing categoryId
            $row = $result->fetch_assoc();
            $categoryId = $row['categoryId'];
        } else {
            // Insert new category into the database
            $insert_category_sql = "INSERT INTO categories (categoryName) VALUES (?)";
            $stmt = $conn->prepare($insert_category_sql);
            $stmt->bind_param("s", $newCategory);
            $stmt->execute();
            $categoryId = $stmt->insert_id;
        }
        $stmt->close();
    }

    // Check if the item already exists in the inventory
    $check_sql = "SELECT * FROM products WHERE productName = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $productName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Item exists, show an alert
        $_SESSION['message'] = "Item already exists!";
        $_SESSION['alert_class'] = "alert-danger";
    } else {
        // Item does not exist, add new item to the inventory
        $conn->begin_transaction();
        try {
            // Insert new product
            $insert_sql = "INSERT INTO products (productName, productCategory, unitPrice, categoryId) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssdi", $productName, $categoryId, $unitPrice, $categoryId);
            $stmt->execute();
            $productId = $stmt->insert_id;

            // Insert batch item
            $insert_batch_sql = "INSERT INTO batchItem (quantity, costPrice, productId, userId) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_batch_sql);
            $stmt->bind_param("idii", $stockLevel, $costPrice, $productId, $userId);
            $stmt->execute();

            // Calculate total stock
            $total_stock_sql = "SELECT SUM(quantity) AS totalStock FROM batchItem WHERE productId = ?";
            $stmt = $conn->prepare($total_stock_sql);
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $totalStock = $row['totalStock'];

            // Insert inventory
            $insert_inventory_sql = "INSERT INTO inventory (totalStock, reorderLevel, productId) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_inventory_sql);
            $stmt->bind_param("iii", $totalStock, $reorderLevel, $productId);
            $stmt->execute();

            $conn->commit();

            $_SESSION['message'] = "Item added successfully!";
            $_SESSION['alert_class'] = "alert-success";
            header("Location: inventory.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = "Error adding item: " . $e->getMessage();
            $_SESSION['alert_class'] = "alert-danger";
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Product</title>
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

        .form-group input,
        .form-group select {
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

        .form-row1 {
            width: 50%;
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

        .custom-input option {
            background: rgb(17, 18, 22);
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>
    <div class="main-content fade-in">
        <div class="form-container">
            <div class="container">
                <img src="images/back.png" alt="Another Image" class="btn-back" id="another-image" onclick="window.history.back()">
                <h3 class="text-center flex-grow-1 m-0">New Product</h3>
                <hr style="height: 1px; border: white; color: rgb(255, 255, 255); background-color: rgb(255, 255, 255);">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert <?php echo $_SESSION['alert_class']; ?>" id="alert">
                        <i class="fas <?php echo $_SESSION['alert_class'] === 'alert-success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <span><?php echo $_SESSION['message']; ?></span>
                        <i class="fas fa-times" onclick="closeAlert()"></i>
                    </div>
                    <?php unset($_SESSION['message']);
                    unset($_SESSION['alert_class']); ?>
                    <script>
                        setTimeout(function() {
                            document.getElementById("alert").style.opacity = "0";
                        }, 4000);
                    </script>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="productName" class="form-label">Product Name:</label><br>
                        <input type="text" class="form-c" name="productName" id="productName" style="width: 100%" placeholder="Enter product name" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_category" class="form-label">Categories:</label>
                            <select class="form-c custom-input" name="product_category" id="product_category" style="width: 100%" required>
                                <option value="" disabled selected>Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['categoryId']; ?>"><?php echo $category['categoryName']; ?></option>
                                <?php endforeach; ?>
                                <option value="new">+ Add new</option>
                            </select>
                        </div>
                        <div class="form-group" id="new_category_group" style="display: none;">
                            <label for="new_category" class="form-label">New Category:</label><br>
                            <input type="text" class="form-c" name="new_category" id="new_category" style="width: 100%" placeholder="Enter new category">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity" class="form-label">Stock Quantity:</label>
                            <input type="number" class="form-c" name="quantity" id="quantity" style="width: 100%" placeholder="Enter quantity" required>
                        </div>
                        <div class="form-group">
                            <label for="reorder_level" class="form-label">Reorder Level:</label>
                            <input type="number" class="form-c" name="reorder_level" id="reorder_level" style="width: 100%" placeholder="Enter reorder level" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cost_price" class="form-label">Purchase Cost:</label>
                            <input type="text" class="form-c" name="cost_price" id="cost_price" style="width: 100%" placeholder="Enter purchase cost" required>
                        </div>
                        <div class="form-group">
                            <label for="unit_price" class="form-label">Selling Price:</label>
                            <input type="text" class="form-c" name="unit_price" id="unit_price" style="width: 100%" placeholder="Enter selling price" required>
                        </div>
                    </div>
                    <br>
                    <div class="d-grid">
                        <button type="submit" name="add_item" class="btn-primary">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        document.getElementById('another-image').addEventListener('mouseover', function() {
            document.querySelector('.btn-back').src = 'images/back-hover.png';
        });

        document.getElementById('another-image').addEventListener('mouseout', function() {
            document.querySelector('.btn-back').src = 'images/back.png';
        });

        document.getElementById('product_category').addEventListener('change', function() {
            var newCategoryGroup = document.getElementById('new_category_group');
            if (this.value === 'new') {
                newCategoryGroup.style.display = 'block';
            } else {
                newCategoryGroup.style.display = 'none';
            }
        });

        function closeAlert() {
            document.getElementById("alert").style.display = "none";
        }
    </script>
</body>

</html>