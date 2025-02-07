<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

if (isset($_SESSION['accountLevel'])) {
    $accountLevel = $_SESSION['accountLevel'];
} else {
    $accountLevel = ''; // Set role to empty if not logged in
}

// Get all inventory items
$sql = "SELECT * FROM products";
$result = mysqli_query($conn, $sql);

// Record a sale
$message = '';
$alert_class = '';
if (isset($_POST['record_sale'])) {
    $productId = $_POST['item_id'];
    $quantity = (int)$_POST['quantity']; // Cast to integer for safety

    // Fetch the current inventory quantity and price of the item
    $item_sql = "SELECT unitPrice, stockLevel FROM products WHERE productId = '$productId'";
    $item_result = mysqli_query($conn, $item_sql);
    $item = mysqli_fetch_assoc($item_result);

    if ($item) {
        $unitPrice = $item['unitPrice'];
        $current_quantity = $item['stockLevel'];

        // Check if there is enough stock
        if ($quantity > $current_quantity) {
            $message = "Not enough stock available.";
            $alert_class = "alert-danger";
        } else {
            // Calculate total price
            $total_price = $unitPrice * $quantity;

            // Insert sale into sales table with timestamp
            $userId = $_SESSION['userId']; // Assuming userId is stored in session
            $sale_sql = "INSERT INTO sales (productId, userId, quantitySold, totalPrice, saleDate) VALUES ('$productId', '$userId', '$quantity', '$total_price', NOW())";
            if (mysqli_query($conn, $sale_sql)) {
                // Update inventory by subtracting the sold quantity
                $new_quantity = $current_quantity - $quantity;
                $update_sql = "UPDATE products SET stockLevel = '$new_quantity' WHERE productId = '$productId'";

                if (mysqli_query($conn, $update_sql)) {
                    $message = "Sale recorded successfully! Inventory updated.";
                    $alert_class = "alert-success";
                } else {
                    $message = "Error updating inventory: " . mysqli_error($conn);
                    $alert_class = "alert-danger";
                }
            } else {
                $message = "Error recording sale: " . mysqli_error($conn);
                $alert_class = "alert-danger";
            }
        }
    } else {
        $message = "Item not found.";
        $alert_class = "alert-danger";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record a Sale</title>
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
    <!-- Selection box for different products -->

    <!-- product dropdown, multiple selection, and stock -->

    <!-- Total Price Display -->

    <!-- Validatation, and Double Check, Displays list of products selected with its quantity -->

    <!-- Input customer's Cash amount and calculates it to total price -->

    <!-- Submit button to record the sale -->
</body>
</html>