<?php
session_start();
include('db.php');
include 'functions.php'; // Include the functions.php file

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

// Fetch the reorder level from the inventory table
$reorderLevel = 5; // Default value
$stmt = $conn->prepare("SELECT reorderLevel FROM inventory LIMIT 1");
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($reorderLevel);
    $stmt->fetch();
    $stmt->close();
}

if (isset($_POST['add_item'])) {
    $productName = $_POST['productName'];
    $categoryId = $_POST['product_category'];
    $newCategory = $_POST['new_category'];
    $stockLevel = $_POST['quantity'];
    $costPrice = $_POST['cost_price'];
    $unitPrice = $_POST['unit_price'];
    $imagePath = null;
    $unit = $_POST['unit']; // Get the selected unit

    // Handle image upload
    $uploadDir = 'uploads/';
    $newFileName = '';

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $file = $_FILES['product_image'];
        $fileName = basename($file['name']);
        $fileSize = $file['size'];
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);

        // Define allowed file types and size limit (e.g., 2MB for images)
        $allowedTypes = ['jpg', 'jpeg', 'png'];
        $maxSize = 2 * 1024 * 1024; // 2 MB

        // Validate file type and size
        if (in_array($fileExt, $allowedTypes) && $fileSize <= $maxSize) {
            // Create unique file name and upload file
            $newFileName = uniqid() . '.' . $fileExt;
            $uploadFile = $uploadDir . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
                $imagePath = $uploadFile;
            } else {
                $_SESSION['message'] = "Error uploading image.";
                $_SESSION['alert_class'] = "alert-danger";
                header("Location: insertProduct.php");
                exit();
            }
        } else {
            $_SESSION['message'] = "Invalid file type or size exceeded.";
            $_SESSION['alert_class'] = "alert-danger";
            header("Location: insertProduct.php");
            exit();
        }
    }

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
        $_SESSION['message'] = "Product already exists! <a href='restock.php'>Restock it instead</a>";
        $_SESSION['alert_class'] = "alert-danger";
    } else {
        // Item does not exist, add new item to the inventory
        $conn->begin_transaction();
        try {
            // Insert new product
            $insert_sql = "INSERT INTO products (productName, productCategory, unitPrice, categoryId, imagePath, unit) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssdiss", $productName, $categoryId, $unitPrice, $categoryId, $imagePath, $unit);
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

            // Commit the transaction
            $conn->commit();

            // Log the action
            logAction('Insert Product', [$productId], [$stockLevel], $userId, $conn);

            $_SESSION['message'] = "Product added successfully!";
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
            background-color: rgb(17, 18, 22, 0.7);
            padding: 10px;
            border-radius: 12px;
            width: 100%;
            max-width: 100%;
        }

        .form-container h1, .form-container h3 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            color: whitesmoke;
            text-align: center;
        }

        .form-c {
            color: white;
            padding: 12px;
            font-size: 1rem;
            border-radius: 8px;
            width: 100%;
            border: 1px solid rgba(208, 217, 251, 0.12);
            background-color: rgba(208, 217, 251, 0.08);
            transition: all 0.3s ease;
        }

        .form-c:focus {
            border: 2px solid #335fff;
            color: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(51, 95, 255, 0.2);
        }

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

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #f7f7f8;
            font-weight: 500;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 10px;
        }

        .form-row .form-group {
            flex: 1;
            min-width: 200px;
        }

        .quantity-unit-container {
            display: flex;
            gap: 10px;
        }

        .quantity-container {
            flex: 2;
        }

        .unit-container {
            flex: 1;
            min-width: 100px;
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

        /* Custom styling for file input */
        input[type="file"] {
            padding: 10px;
            background-color: rgba(208, 217, 251, 0.05);
            border: 1px dashed rgba(208, 217, 251, 0.3);
            border-radius: 8px;
            cursor: pointer;
        }

        input[type="file"]:hover {
            background-color: rgba(208, 217, 251, 0.08);
        }

        .custom-input option {
            background: rgb(21, 22, 26);
            border-radius: 8px;
            padding: 10px;
        }

        .required {
            color: #dc3545;
            margin-left: 3px;
        }

        /* Make form elements more consistent */
        input, select, button {
            font-family: inherit;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .form-container {
                padding: 5px;
                width: 100%;
            }

            .form-row {
                flex-direction: column;
                gap: 10px;
            }

            .form-c {
                padding: 10px;
            }

            .quantity-unit-container {
                flex-direction: row;
            }

            .quantity-container {
                flex: 1;
            }

            .unit-container {
                flex: 1;
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
            <div class="container">
                <a href="inventory.php" class="btn-back-wrapper">
                    <img src="images/back.png" alt="Another Image" class="btn-back" id="another-image">
                    <b><span>Back</span></b>
                </a>
                <br>
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
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="product_image" class="form-label">Product Image:</label><br>
                        <input type="file" class="form-c" name="product_image" id="product_image" style="width: 100%">
                    </div>
                    <div class="form-group">
                        <label for="productName" class="form-label">Product Name: <span class="required">*</span></label><br>
                        <input type="text" class="form-c" name="productName" id="productName" style="width: 100%" placeholder="Enter product name" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_category" class="form-label">Categories: <span class="required">*</span></label>
                            <select class="form-c custom-input" name="product_category" id="product_category" style="width: 100%" required>
                                <option value="" disabled selected>Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['categoryId']; ?>"><?php echo $category['categoryName']; ?></option>
                                <?php endforeach; ?>
                                <option value="new">+ Add new</option>
                            </select>
                        </div>
                        <div class="form-group" id="new_category_group" style="display: none;">
                            <label for="new_category" class="form-label">New Category: <span class="required">*</span></label><br>
                            <input type="text" class="form-c" name="new_category" id="new_category" style="width: 100%" placeholder="Enter new category">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity" class="form-label">Stock Quantity: <span class="required">*</span></label>
                            <div class="quantity-unit-container">
                                <div class="quantity-container">
                                    <input type="number" class="form-c" name="quantity" id="quantity" placeholder="Enter quantity" required>
                                </div>
                                <div class="unit-container">
                                    <select class="form-c custom-input" name="unit" id="unit">
                                        <option value="pcs">pcs</option>
                                        <option value="kg">kg</option>
                                        <option value="L">L</option>
                                        <option value="pack">pack</option>
                                        <option value="mL">mL</option>
                                        <option value="doz">doz</option>
                                        <option value="m">m</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cost_price" class="form-label">Purchase Cost: <span class="required">*</span></label>
                            <input type="number" step="0.01" class="form-c" name="cost_price" id="cost_price" style="width: 100%" placeholder="Enter purchase cost" required>
                        </div>
                        <div class="form-group">
                            <label for="unit_price" class="form-label">Selling Price: <span class="required">*</span></label>
                            <input type="number" step="0.01" class="form-c" name="unit_price" id="unit_price" style="width: 100%" placeholder="Enter selling price" required>
                        </div>
                    </div>
            </div>
            <br>
            <div class="d-grid">
                <button type="submit" name="add_item" class="btn-primary">Add Product</button>
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

        document.addEventListener('DOMContentLoaded', function() {
            var referrer = document.referrer;
            var backButton = document.querySelector('.btn-back-wrapper');
            
            if (referrer.includes('inventory.php')) {
                backButton.href = 'inventory.php';
            } else if (referrer.includes('dashboard.php')) {
                backButton.href = 'dashboard.php';
            } else if (referrer.includes('grid.php')) {
                backButton.href = 'grid.php';
            } else if (referrer.includes('reports.php')) {
                backButton.href = 'reports.php?tab=product'
            } else {
                backButton.href = 'inventory.php'; // Default fallback
            }
        });
    </script>
</body>

</html>