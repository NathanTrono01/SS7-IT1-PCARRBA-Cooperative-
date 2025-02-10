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
    $productName = $_POST['productName'];
    $category = $_POST['product_category'];
    $newCategory = $_POST['new_category'];
    $stockLevel = $_POST['quantity'];
    $costPrice = $_POST['cost_price'];
    $unitPrice = $_POST['unit_price'];
    $reorderLevel = $_POST['reorder_level'];

    // Use new category if provided
    if (!empty($newCategory)) {
        $category = $newCategory;
    }

    // Update item details
    $update_sql = "UPDATE products SET 
                        productCategory = '$category', 
                        stockLevel = '$stockLevel', 
                        costPrice = '$costPrice', 
                        unitPrice = '$unitPrice', 
                        reorderLevel = '$reorderLevel' 
                    WHERE productName = '$productName'";

    if (mysqli_query($conn, $update_sql)) {
        $message = "Item details updated successfully!";
        $alert_class = "alert-success";
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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        .form-container {
            background-color: #ffffff;
            padding: 20px;
            margin-top: 10px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 95%;
            max-width: 1200px;
            margin-left: 10px;
            margin-right: 10px;
        }

        .form-container h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }

        .form-label {
            font-weight: 500;
            color: #555;
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
            background-color: transparent;
            color: black;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            margin-top: 20px;
            text-align: center;
            transition: background-color 0.3s ease;
        }

        .btn-back:hover {
            color:rgb(255, 0, 0);
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
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <div class="container main-content">
        <div class="form-container">
            <h1>Edit Item in Inventory</h1>

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
                        <label for="productName" class="form-label">Item Name:</label>
                        <input type="text" class="form-control" name="productName" id="productName" value="<?php echo $productDetails['productName']; ?>" required readonly>
                    </div>
                    <div class="form-group">
                        <label for="product_category" class="form-label">Category:</label>
                        <select class="form-select form-control" name="product_category" id="product_category" required>
                            <option value="" disabled>Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category; ?>" <?php echo $category === $productDetails['productCategory'] ? 'selected' : ''; ?>><?php echo $category; ?></option>
                            <?php endforeach; ?>
                            <option value="new">+ Add new</option>
                        </select>
                    </div>
                    <div class="form-group" id="new_category_group" style="display: none;">
                        <label for="new_category" class="form-label">New Category:</label>
                        <input type="text" class="form-control" name="new_category" id="new_category">
                    </div>
                    <div class="form-group">
                        <label for="quantity" class="form-label">Stock:</label>
                        <input type="number" class="form-control" name="quantity" id="quantity" value="<?php echo $productDetails['stockLevel']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="cost_price" class="form-label">Purchase Cost:</label>
                        <input type="text" class="form-control" name="cost_price" id="cost_price" value="<?php echo $productDetails['costPrice']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="unit_price" class="form-label">Selling Price:</label>
                        <input type="text" class="form-control" name="unit_price" id="unit_price" value="<?php echo $productDetails['unitPrice']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="reorder_level" class="form-label">Minimum Stock Alert:</label>
                        <input type="number" class="form-control" name="reorder_level" id="reorder_level" value="<?php echo $productDetails['reorderLevel']; ?>" required>
                    </div>
                    <br>
                    <div class="d-grid">
                        <button type="submit" name="edit_item" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-danger">
                    Item not found in the inventory. Please check the product name.
                </div>
            <?php endif; ?>

            <div class="text-center">
                <a href="inventory.php" class="btn-back">Back to Inventory</a>
            </div>
        </div>
    </div>

    <script>
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