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

// Add new item to inventory
if (isset($_POST['add_item'])) {
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

    // Check if the item already exists in the inventory
    $check_sql = "SELECT * FROM products WHERE productName = '$productName'";
    $result = mysqli_query($conn, $check_sql);

    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) > 0) {
        // Item exists, update the quantity and prices
        $row = mysqli_fetch_assoc($result);
        $new_quantity = $row['stockLevel'] + $stockLevel;
        $update_sql = "UPDATE products SET stockLevel = '$new_quantity', productCategory = '$category', costPrice = '$costPrice', unitPrice = '$unitPrice', reorderLevel = '$reorderLevel', userId = '$userId' WHERE productName = '$productName'";

        if (mysqli_query($conn, $update_sql)) {
            $message = "Item quantity and prices updated successfully!";
            $alert_class = "alert-success";
        } else {
            $message = "Error updating item: " . mysqli_error($conn);
            $alert_class = "alert-danger";
        }
    } else {
        // Item does not exist, add new item to the inventory
        $insert_sql = "INSERT INTO products (productName, productCategory, stockLevel, costPrice, unitPrice, reorderLevel, userId) VALUES ('$productName', '$category', '$stockLevel', '$costPrice', '$unitPrice', '$reorderLevel', '$userId')";

        if (mysqli_query($conn, $insert_sql)) {
            $message = "Item added successfully!";
            $alert_class = "alert-success";
        } else {
            $message = "Error adding item: " . mysqli_error($conn);
            $alert_class = "alert-danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Item</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
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
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>
    <div class="container main-content">
        <div class="content">
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

            <div class="form-container">
                <h1>Add New Item to Inventory</h1>

                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="productName" class="form-label">Item Name:</label>
                            <input type="text" class="form-control" name="productName" id="productName" required>
                        </div>
                        <div class="form-group">
                            <label for="product_category" class="form-label">Category:</label>
                            <select class="form-select" name="product_category" id="product_category" required>
                                <option value="" disabled selected>Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                                <?php endforeach; ?>
                                <option value="new">+ Add new</option>
                            </select>
                        </div>
                        <div class="form-group" id="new_category_group" style="display: none;">
                            <label for="new_category" class="form-label">New Category:</label>
                            <input type="text" class="form-control" name="new_category" id="new_category">
                        </div>
                        <div class="form-group">
                            <label for="quantity" class="form-label">Quantity:</label>
                            <input type="number" class="form-control" name="quantity" id="quantity" required>
                        </div>
                        <div class="form-group">
                            <label for="unit_price" class="form-label">Unit Price:</label>
                            <input type="text" class="form-control" name="unit_price" id="unit_price" required>
                        </div>
                        <div class="form-group">
                            <label for="cost_price" class="form-label">Cost Price:</label>
                            <input type="text" class="form-control" name="cost_price" id="cost_price" required>
                        </div>
                        <div class="form-group">
                            <label for="reorder_level" class="form-label">Stock Level Alert:</label>
                            <input type="number" class="form-control" name="reorder_level" id="reorder_level" required>
                        </div>
                    </div>
                    <br>
                    <div class="d-grid">
                        <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
                    </div>
                </form>
            </div>

            <div class="text-center">
                <a href="inventory.php" class="btn btn-back">Back to Inventory</a>
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