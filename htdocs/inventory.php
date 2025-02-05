<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
include 'db.php';

// Fetch all products
$query = "SELECT * FROM products";
$result = $conn->query($query);
$products = $result->fetch_all(MYSQLI_ASSOC);

// Handle form submission for editing or deleting products
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_product'])) {
        // Delete product
        $productId = $_POST['productId'];
        $stmt = $conn->prepare("DELETE FROM products WHERE productId = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $stmt->close();
        // Refresh the page to reflect the changes
        header("Location: inventory.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Inventory</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        .table-wrapper {
            background-color: transparent;
            max-height: 600px;
            overflow-y: auto;
            width: 100%;
            max-width: none;
            margin: 25px auto;
            position: relative;
            padding: 0 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-wrapper::-webkit-scrollbar {
            width: 5px;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .table-wrapper:hover::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2);
        }

        table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #1f1f1f;
            border-bottom: 0.25px solid rgba(187, 188, 190, 0.25);
            border-radius: 10px 10px 0 0;
        }

        table th {
            padding: 7.5px;
            background-color: rgb(17, 18, 22);
            color: #fff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 1rem;
            border-radius: 10px 10px 0 0;
        }

        table td {
            padding: 5px;
            font-size: 1rem;
            color: #eee;
        }

        table tr {
            background-color: rgb(17, 18, 22);
        }

        table tr:hover {
            background-color: rgba(187, 194, 209, 0.17);
            transition: all 0.3s ease;
        }

        .button a {
            background: transparent;
            display: inline-block;
            padding: 8px 12px;
            background-color: rgb(255, 255, 255);
            color: #000;
            text-decoration: none;
            border-radius: 7px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.26);
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        .button a:hover {
            background-color: rgba(255, 255, 255, 0.94);
            color: #000;
            border-radius: 7px;
        }

        .btn-action {
            padding: 5px 10px;
            font-size: 0.875rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            opacity: 0.8;
        }

        @media (max-width: 1024px) {
            .table-wrapper {
                width: 95%;
            }

            table th,
            table td {
                font-size: 0.95rem;
                padding: 6px;
            }
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 1.8em;
            }

            .table-wrapper {
                width: 100%;
            }

            table th,
            table td {
                font-size: 0.85rem;
                padding: 6px;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 1.5em;
            }

            h2 {
                font-size: 0.8em;
            }

            .table-wrapper {
                padding: 0 5px;
                width: 100%;
            }

            table {
                font-size: 0.8rem;
            }

            table th,
            table td {
                font-size: 0.8rem;
                padding: 5px;
            }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <!-- Main content -->
    <div class="main-content">
        <div class="container">
            <h1>Inventory</h1>
            <div class="table-wrapper">
                <div class="button">
                    <a href="insertProduct.php">Add Product</a>
                </div>
                <table>
                    <thead>
                        <tr align="left">
                            <th>Product</th>
                            <th>Category</th>
                            <th>Stock Level</th>
                            <th>Unit Price (₱)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['productName']); ?></td>
                                <td><?php echo htmlspecialchars($product['productCategory']); ?></td>
                                <td><?php echo htmlspecialchars($product['stockLevel']); ?></td>
                                <td>₱ <?php echo htmlspecialchars($product['unitPrice']); ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="productId" value="<?php echo $product['productId']; ?>">
                                        <button type="submit" name="edit_product" class="btn btn-primary btn-action">Edit</button>
                                    </form>
                                    <form method="post" style="display:inline;" onsubmit="return confirmDelete()">
                                        <input type="hidden" name="productId" value="<?php echo $product['productId']; ?>">
                                        <button type="submit" name="delete_product" class="btn btn-danger btn-action">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- JavaScript for Confirmation Dialog -->
    <script>
        function confirmDelete() {
            return confirm("Are you sure you want to delete this product?");
        }
    </script>
</body>

</html>