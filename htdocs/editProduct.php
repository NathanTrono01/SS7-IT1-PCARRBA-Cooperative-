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

// Define categories for the dropdown
$categories = [
    'Beverages',
    'Snacks',
    'Canned Goods',
    'Personal Care',
    'Household Items',
    'Condiments',
    'Dairy Products',
    'Frozen Foods',
    'Bakery Items'
];

// Fetch product details for editing
$productDetails = null;
if (isset($_GET['productName'])) {
    $productName = $_GET['productName'];
    $fetch_sql = "SELECT * FROM products WHERE productName = '$productName'";
    $result = mysqli_query($conn, $fetch_sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $productDetails = mysqli_fetch_assoc($result);
    } else {
        $message = "Item not found.";
        $alert_class = "alert-danger";
    }
}

// Edit item in inventory
if (isset($_POST['edit_item'])) {
    $productName = $productDetails['productName'];
    $stockLevel = $_POST['quantity'];
    $costPrice = $_POST['cost_price'];
    $unitPrice = $_POST['unit_price'];
    $reorderLevel = $_POST['reorder_level'];

    // Update item details
    $update_sql = "UPDATE products SET 
                        stockLevel = '$stockLevel', 
                        costPrice = '$costPrice', 
                        unitPrice = '$unitPrice', 
                        reorderLevel = '$reorderLevel' 
                    WHERE productName = '$productName'";

    if (mysqli_query($conn, $update_sql)) {
        $_SESSION['message'] = 'Product "' . $productName . '" updated successfully!';
        $_SESSION['alert_class'] = "alert-success";
        header("Location: inventory.php");
        exit();
    } else {
        $message = "Error updating item: " . mysqli_error($conn);
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
            background-color:rgb(5, 255, 92);
            border: 2px solid rgb(5, 255, 92);
            padding: 10px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color:rgba(149, 255, 151, 0.4);
            border: 2px solid rgb(5, 255, 92);
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

        .text-mute {
            color: rgb(255 255 255 / 50%) !important
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
            border-radius: 7.5px;
            border: 1px solid rgba(208, 217, 251, .12);
        }

        .form-selects:disabled {
            background-color: rgba(208, 217, 251, .08);
        }

        /* .main-content{
            margin-top: 50px;
        } */
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <div class="form-container">
        <div class="container main-content">
            <img src="images/back.png" alt="Another Image" class="btn-back" id="another-image" onclick="window.history.back()">
            <h3 class="text-center flex-grow-1 m-0">Update Product</h3>
            <hr style="height: 1px; border: white; color: rgb(255, 255, 255); background-color: rgb(255, 255, 255);">
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

                <form method="POST">
                    <div class="form-group">
                        <label for="productName" class="form-label">Product Name:</label><br>
                        <input type="text" class="form-c form-text text-mute" name="productName" id="productName" style="width: 100%" value="<?php echo $productDetails['productName']; ?>" disabled readonly>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_category" class="form-label">Category:</label>
                            <select class="form-selects form-c text-mute" name="product_category" id="product_category" style="width: 100%" disabled readonly>
                                <option value="" disabled selected>Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category; ?>" <?php echo $category === $productDetails['productCategory'] ? 'selected' : ''; ?>><?php echo $category; ?></option>
                                <?php endforeach; ?>
                                <option value="new">+ Add new</option>
                            </select>
                        </div>
                        <div class="form-group" id="new_category_group" style="display: none;">
                            <label for="new_category" class="form-label">New Category:</label><br>
                            <input type="text" class="form-c" name="new_category" id="new_category" style="width: 100%">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity" class="form-label">Stock Quantity:</label>
                            <input type="number" class="form-c" name="quantity" id="quantity" style="width: 100%" value="<?php echo $productDetails['stockLevel']; ?>" placeholder="Enter stock quantity" required>
                        </div>
                        <div class="form-group">
                            <label for="reorder_level" class="form-label">Reorder Level:</label>
                            <input type="number" class="form-c" name="reorder_level" id="reorder_level" value="<?php echo $productDetails['reorderLevel']; ?>" placeholder="Enter reorder level" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cost_price" class="form-label">Purchase Cost:</label>
                            <input type="text" class="form-c" name="cost_price" id="cost_price" value="<?php echo $productDetails['costPrice']; ?>" placeholder="Enter purchase cost" required>
                        </div>
                        <div class="form-group">
                            <label for="unit_price" class="form-label">Selling Price:</label>
                            <input type="text" class="form-c" name="unit_price" id="unit_price" value="<?php echo $productDetails['unitPrice']; ?>" placeholder="Enter selling price" required>
                        </div>
                    </div>
                    <br>
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