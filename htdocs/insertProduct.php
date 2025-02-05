<?php
session_start();
include('db.php');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
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

// Add new item to inventory
if (isset($_POST['add_item'])) {
    $productName = $_POST['item_name'];
    $category = $_POST['category'];
    $newCategory = $_POST['new_category'];
    $stockLevel = $_POST['quantity'];
    $costPrice = $_POST['cost_price'];
    $unitPrice = $_POST['unit_price'];

    // Use new category if provided
    if (!empty($newCategory)) {
        $category = $newCategory;
    }

    // Check if the item already exists in the inventory
    $check_sql = "SELECT * FROM products WHERE productName = '$productName'";
    $result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($result) > 0) {
        // Item exists, update the quantity and prices
        $row = mysqli_fetch_assoc($result);
        $new_quantity = $row['stockLevel'] + $stockLevel;
        $update_sql = "UPDATE products SET stockLevel = '$new_quantity', category = '$category', costPrice = '$costPrice', unitPrice = '$unitPrice' WHERE productName = '$productName'";

        if (mysqli_query($conn, $update_sql)) {
            $message = "Item quantity and prices updated successfully!";
            $alert_class = "alert-success";
        } else {
            $message = "Error updating item: " . mysqli_error($conn);
            $alert_class = "alert-danger";
        }
    } else {
        // Item does not exist, add new item to the inventory
        $insert_sql = "INSERT INTO products (productName, category, stockLevel, costPrice, unitPrice) VALUES ('$productName', '$category', '$stockLevel', '$costPrice', '$unitPrice')";

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
            padding: 30px;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            color: black;
        }

        .btn-back {
            display: inline-block;
            background-color: #f44336;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            margin-top: 20px;
            text-align: center;
            width: auto;
            min-width: 150px;
        }

        .btn-back:hover {
            background-color: #d32f2f;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .content {
            flex: 1;
            padding: 20px;
            background-color: transparent;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            margin-bottom: 20px;
            color: black;
        }

        .alert {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            transition: opacity 1s ease-out;
        }

        .alert-success {
            background-color: rgba(7, 149, 66, 0.8);
        }

        .alert-danger {
            background-color: rgba(220, 17, 1, 0.8);
        }

        .alert-message {
            display: flex;
            align-items: center;
        }

        .alert .start-icon {
            margin-right: 5px;
        }

        .alert .fa-times {
            cursor: pointer;
        }

        .form-label, .form-control {
            color: black;
        }
    </style>
</head>

<body>
<?php include 'navbar.php'; ?>
<script src="js/bootstrap.bundle.min.js"></script>
    <div class="container main-content">
        <div class="content">
            <h1>Add New Item to Inventory</h1>

            <?php if (isset($message)): ?>
                <div class="alert <?php echo $alert_class; ?>" id="alert">
                    <div class="alert-message">
                        <span class="start-icon"><?php echo $alert_class === 'alert-success' ? '✔' : '❌'; ?></span>
                        <span><?php echo $message; ?></span>
                        <span class="fa-times" onclick="closeAlert()">×</span>
                    </div>
                </div>

                <script>
                    document.getElementById("alert").style.display = "block";
                    setTimeout(function() {
                        document.getElementById("alert").style.opacity = "0";
                    }, 4000);
                </script>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST">
                    <div class="mb-3">
                        <label for="item_name" class="form-label">Item Name:</label>
                        <input type="text" class="form-control" name="item_name" id="item_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Category:</label>
                        <select class="form-select" name="category" id="category" required>
                            <option value="" disabled selected>Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                            <?php endforeach; ?>
                            <br>
                            <hr>
                            <option value="new">+ Add new</option>
                        </select>
                    </div>
                    <div class="mb-3" id="new_category_group" style="display: none;">
                        <label for="new_category" class="form-label">New Category:</label>
                        <input type="text" class="form-control" name="new_category" id="new_category">
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity:</label>
                        <input type="number" class="form-control" name="quantity" id="quantity" required>
                    </div>
                    <div class="mb-3">
                        <label for="cost_price" class="form-label">Cost Price:</label>
                        <input type="text" class="form-control" name="cost_price" id="cost_price" required>
                    </div>
                    <div class="mb-3">
                        <label for="unit_price" class="form-label">Unit Price:</label>
                        <input type="text" class="form-control" name="unit_price" id="unit_price" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
                    </div>
                </form>
            </div>

            <a href="inventory.php" class="btn btn-back">Back to Inventory</a>
        </div>
    </div>

    <script>
        document.getElementById('category').addEventListener('change', function () {
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