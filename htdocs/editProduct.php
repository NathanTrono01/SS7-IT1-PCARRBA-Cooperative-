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

// Fetch categories from the database
$categories = [];
$category_sql = "SELECT categoryId, categoryName FROM categories";
$result = $conn->query($category_sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

$productDetails = null;
if (isset($_GET['productName'])) {
    $productName = $_GET['productName'];
    $fetch_sql = "SELECT p.*, i.totalStock, i.reorderLevel, b.costPrice, c.categoryName AS productCategory, p.imagePath as image FROM products p 
                  LEFT JOIN inventory i ON p.productId = i.productId 
                  LEFT JOIN batchItem b ON p.productId = b.productId 
                  LEFT JOIN categories c ON p.categoryId = c.categoryId 
                  WHERE p.productName = ?";
    $stmt = $conn->prepare($fetch_sql);
    $stmt->bind_param("s", $productName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $productDetails = $result->fetch_assoc();
    } else {
        $message = "Item not found.";
        $alert_class = "alert-danger";
    }
}

if (isset($_POST['edit_item'])) {
    $productId = $productDetails['productId'];
    $stockLevel = $productDetails['totalStock']; // Use the existing stock level
    $costPrice = $_POST['cost_price'];
    $unitPrice = $_POST['unit_price'];

    // Initialize image path with current image
    $imagePath = $productDetails['image'];

    // Handle image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $file = $_FILES['product_image'];
        $fileName = basename($file['name']);
        $fileSize = $file['size'];
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $uploadDir = 'uploads/';

        // Define allowed file types and size limit (e.g., 2MB for images)
        $allowedTypes = ['jpg', 'jpeg', 'png'];
        $maxSize = 2 * 1024 * 1024; // 2 MB

        // Validate file type and size
        if (in_array($fileExt, $allowedTypes) && $fileSize <= $maxSize) {
            // Create unique file name and upload file
            $newFileName = uniqid() . '.' . $fileExt;
            $uploadFile = $uploadDir . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
                // Delete the old image if it exists and is not the default image
                if (!empty($imagePath) && $imagePath != 'images/no-image.png' && file_exists($imagePath)) {
                    @unlink($imagePath);
                }
                $imagePath = $uploadFile;
            } else {
                $message = "Error uploading image.";
                $alert_class = "alert-danger";
            }
        } else {
            $message = "Invalid file type or size exceeded.";
            $alert_class = "alert-danger";
        }
    }

    $conn->begin_transaction();
    try {
        // Update product details including image path
        $update_product_sql = "UPDATE products SET unitPrice = ?, imagePath = ? WHERE productId = ?";
        $stmt = $conn->prepare($update_product_sql);
        $stmt->bind_param("dsi", $unitPrice, $imagePath, $productId);
        $stmt->execute();

        // Update inventory details
        $update_inventory_sql = "UPDATE inventory SET totalStock = ?, reorderLevel = ? WHERE productId = ?";
        $stmt = $conn->prepare($update_inventory_sql);
        $stmt->bind_param("iii", $stockLevel, $reorderLevel, $productId);
        $stmt->execute();

        // Update batch item details
        $update_batch_sql = "UPDATE batchItem SET quantity = ?, costPrice = ? WHERE productId = ?";
        $stmt->prepare($update_batch_sql);
        $stmt->bind_param("idi", $stockLevel, $costPrice, $productId);
        $stmt->execute();

        $conn->commit();

        // Log the action
        logAction('Edit Product', [$productId], [$stockLevel], $_SESSION['userId'], $conn);

        $_SESSION['message'] = 'Product "' . $productName . '" updated successfully!';
        $_SESSION['alert_class'] = "alert-success";
        header("Location: inventory.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error updating item: " . $e->getMessage();
        $alert_class = "alert-danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        .form-container {
            align-content: center;
            background-color: rgb(17, 18, 22, 0.7);
            padding: 10px;
            border-radius: 12px;
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

        /* Product image preview */
        .product-image-preview {
            max-width: 150px;
            max-height: 150px;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 5px;
            background-color: rgba(0, 0, 0, 0.2);
        }

        .text-mute {
            color: rgba(255, 255, 255, 0.7) !important;
            font-size: 0.85rem;
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

        /* Field disabled styles */
        .form-c:disabled, .form-selects:disabled {
            background-color: rgba(208, 217, 251, 0.04);
            color: rgba(255, 255, 255, 0.5);
            cursor: not-allowed;
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
                <h3 class="text-center flex-grow-1 m-0">Update Product</h3>
                <hr>
                
                <?php if (isset($message)): ?>
                    <div class="alert <?php echo $alert_class; ?>" id="alert">
                        <i class="fas <?php echo $alert_class === 'alert-success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <span><?php echo $message; ?></span>
                        <i class="fas fa-times" onclick="closeAlert()"></i>
                    </div>

                    <script>
                        setTimeout(function() {
                            document.getElementById("alert").style.opacity = "0";
                        }, 4000);
                    </script>
                <?php endif; ?>

                <?php if ($productDetails): ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="product_image" class="form-label">Product Image:</label>
                            <div>
                                <?php
                                $currentImagePath = isset($productDetails['image']) && !empty($productDetails['image'])
                                    ? $productDetails['image']
                                    : 'images/no-image.png';
                                ?>
                                <img src="<?php echo htmlspecialchars($currentImagePath); ?>" alt="Current product image" class="product-image-preview">
                            </div>
                            <input type="file" class="form-c" name="product_image" id="product_image">
                            <small class="text-mute">Leave empty to keep current image</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="productName" class="form-label">Product Name:</label>
                            <input type="text" class="form-c" name="productName" id="productName" value="<?php echo htmlspecialchars($productDetails['productName']); ?>" disabled readonly>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="product_category" class="form-label">Category:</label>
                                <select class="form-c custom-input" name="product_category" id="product_category" disabled readonly>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['categoryId']); ?>" <?php echo $category['categoryId'] == $productDetails['categoryId'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['categoryName']); ?></option>
                                    <?php endforeach; ?>
                                    <option value="new">+ Add new</option>
                                </select>
                            </div>
                            <div class="form-group" id="new_category_group" style="display: none;">
                                <label for="new_category" class="form-label">New Category: </label>
                                <input type="text" class="form-c" name="new_category" id="new_category">
                            </div>
                        </div>
                        
                        <!-- Updated quantity and unit section -->
                        <div class="form-group">
                            <label for="quantity" class="form-label">Stock Quantity:</label>
                            <div class="quantity-unit-container">
                                <div class="quantity-container">
                                    <input type="number" class="form-c" name="quantity" id="quantity" value="<?php echo htmlspecialchars($productDetails['totalStock']); ?>" disabled readonly>
                                </div>
                                <div class="unit-container">
                                    <select class="form-c custom-input" name="unit" id="unit" disabled readonly>
                                        <option value="pcs" <?php echo ($productDetails['unit'] == 'pcs') ? 'selected' : ''; ?>>pcs</option>
                                        <option value="kg" <?php echo ($productDetails['unit'] == 'kg') ? 'selected' : ''; ?>>kg</option>
                                        <option value="L" <?php echo ($productDetails['unit'] == 'L') ? 'selected' : ''; ?>>L</option>
                                        <option value="pack" <?php echo ($productDetails['unit'] == 'pack') ? 'selected' : ''; ?>>pack</option>
                                        <option value="mL" <?php echo ($productDetails['unit'] == 'mL') ? 'selected' : ''; ?>>mL</option>
                                        <option value="doz" <?php echo ($productDetails['unit'] == 'doz') ? 'selected' : ''; ?>>doz</option>
                                        <option value="m" <?php echo ($productDetails['unit'] == 'm') ? 'selected' : ''; ?>>m</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cost_price" class="form-label">Purchase Cost: <span class="required">*</span></label>
                                <input type="number" class="form-c" name="cost_price" id="cost_price" value="<?php echo htmlspecialchars($productDetails['costPrice'] ?? ''); ?>" placeholder="Enter purchase cost" required step="0.01">
                            </div>
                            <div class="form-group">
                                <label for="unit_price" class="form-label">Selling Price: <span class="required">*</span></label>
                                <input type="number" class="form-c" name="unit_price" id="unit_price" value="<?php echo htmlspecialchars($productDetails['unitPrice']); ?>" placeholder="Enter selling price" required step="0.01">
                            </div>
                        </div>
                        
                        <input type="hidden" name="quantity" value="<?php echo htmlspecialchars($productDetails['totalStock']); ?>">
                        <input type="hidden" name="unit" value="<?php echo htmlspecialchars($productDetails['unit']); ?>">
                        
                        <div class="d-grid">
                            <button type="submit" name="edit_item" class="btn-primary">Save Changes</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger">
                        Item not found in the inventory. Please check the product name.
                    </div>
                <?php endif; ?>
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