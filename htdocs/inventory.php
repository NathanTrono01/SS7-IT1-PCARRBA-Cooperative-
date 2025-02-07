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
            background-color: #191a1f;
            width: 100%;
            margin: 25px auto;
            position: relative;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.58);
        }

        .flex-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 10px;
        }

        .search-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            position: relative;
            width: 100%;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-container input {
            padding: 8px 8px 8px 30px;
            /* Add padding to the left for the search icon */
            width: 100%;
            max-width: 400px;
            border-radius: 5px;
            border: 1px solid #333942;
            background-color: rgba(33, 34, 39, 255);
            color: #f7f7f8;
        }

        .search-container input::placeholder {
            color: rgba(247, 247, 248, 0.64);
            font-weight: 200;
        }

        .search-container .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
        }

        .search-container .clear-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            cursor: pointer;
            display: none;
            /* Initially hidden */
        }

        table {
            font-family: Arial, Helvetica, sans-serif;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #1f1f1f;
        }

        table th {
            padding: 7.5px;
            background-color: rgba(0, 0, 0, 0.52);
            color:rgba(247, 247, 248, 0.9);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 1rem;
            margin: 0 5px;
            border-top: 2px solid #333942;
            border-bottom: 2px solid #333942;
        }

        table th:first-child {
            border-left: 2px solid #333942;
            border-top: 2px solid #333942;
            border-bottom: 2px solid #333942;
        }

        table th:last-child {
            border-right: 2px solid #333942;
            border-top: 2px solid #333942;
            border-bottom: 2px solid #333942;
        }

        table td {
            padding: 10px;
            font-size: 1rem;
            color: #eee;
            margin: 0 5px;
        }

        table tr {
            background-color: #191a1f;
        }

        table tr:hover {
            background-color: rgba(187, 194, 209, 0.17);
            transition: all 0.3s ease;
        }

        table tr:hover td:first-child {
            border-top-left-radius: 7px;
            border-bottom-left-radius: 7px;
        }

        table tr:hover td:last-child {
            border-top-right-radius: 7px;
            border-bottom-right-radius: 7px;
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
            padding: 10px 15px;
            font-size: 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 75px;
            text-align: center;
            box-sizing: border-box;
        }

        .btn-action img {
            display: none;
        }

        .btn-action span {
            display: inline;
        }

        @media (max-width: 1024px) {
            .table-wrapper {
                padding: 10px;
                overflow-x: auto;
                /* Ensures horizontal scroll on small devices */
            }

            table {
                width: 100%;
                table-layout: auto;
                /* Makes sure the table columns adjust based on content */
            }

            table th,
            table td {
                font-size: 0.95rem;
                padding: 6px;
            }

            .btn-action {
                padding: 8px 12px;
                font-size: 0.85rem;
                min-width: 60px;
            }

            .btn-action span {
                display: none;
            }

            .btn-action img {
                display: inline;
                width: 24px;
                height: 24px;
            }
        }

        @media (max-width: 768px) {
            .table-wrapper {
                padding: 10px;
                overflow-x: auto;
                /* Ensures horizontal scroll if the table overflows */
            }

            table {
                width: 100%;
                table-layout: auto;
                /* Makes the table columns flexible */
            }

            table th,
            table td {
                font-size: 0.85rem;
                /* Slightly smaller font size for smaller screens */
                padding: 6px;
            }

            .btn-action {
                padding: 6px 10px;
                font-size: 0.75rem;
                /* Slightly smaller button size */
                min-width: 50px;
                /* Ensure buttons have a minimum width */
            }

            .btn-action span {
                display: none;
            }

            .btn-action img {
                display: inline;
                width: 24px;
                height: 24px;
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
                margin: 0;
                padding: 10px;
                /* Adjusted padding for smaller screens */
                width: 100%;
            }

            table {
                font-size: 0.8rem;
                display: block;
                width: 100%;
                overflow-x: auto;
                white-space: nowrap;
                /* Prevent text from wrapping */
            }

            table th,
            table td {
                font-size: 0.8rem;
                padding: 5px;
            }

            .btn-action {
                padding: 4px 10px;
                font-size: 0.7rem;
                min-width: 50px;
            }

            .btn-action span {
                display: none;
            }

            .btn-action img {
                display: inline;
                width: 24px;
                height: 24px;
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
            <h1>Product Inventory</h1>
            <div class="table-wrapper">
                <div class="header-container">
                    <h5>List of Products</h5>
                    <div class="button">
                        <a href="insertProduct.php">+ New Product</a>
                    </div>
                </div>
                <div class="search-container">
                    <img src="images/search-icon.png" alt="Search" class="search-icon">
                    <input type="text" id="searchBar" placeholder="Search Product/s" onkeyup="filterProducts(); toggleClearIcon();">
                    <img src="images/x-circle.png" alt="Clear" class="clear-icon" onclick="clearSearch()">
                </div>
                <hr style="height: 1px; border: none; color: rgb(187, 188, 190); background-color: rgb(187, 188, 190);">
                <table id="productTable">
                    <thead>
                        <tr align="left">
                            <th>&nbsp;Product</th>
                            <th>&nbsp;Category</th>
                            <th>&nbsp;Stock</th>
                            <th>&nbsp;(₱) Price</th>
                            <th>&nbsp;Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product) { ?>
                            <tr>
                                <td>&nbsp;<?php echo htmlspecialchars($product['productName']); ?></td>
                                <td>&nbsp;<?php echo htmlspecialchars($product['productCategory']); ?></td>
                                <td>&nbsp;<?php echo htmlspecialchars($product['stockLevel']); ?></td>
                                <td>&nbsp;₱ <?php echo htmlspecialchars($product['unitPrice']); ?></td>
                                <td>&nbsp;
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="productId" value="<?php echo $product['productId']; ?>">
                                        <button type="submit" name="edit_product" class="btn btn-primary btn-action">
                                            <span>Edit</span>
                                            <img src="images/white-pencil.png" alt="Edit">
                                        </button>
                                    </form>
                                    <form method="post" style="display:inline;" onsubmit="return confirmDelete()">
                                        <input type="hidden" name="productId" value="<?php echo $product['productId']; ?>">
                                        <button type="submit" name="delete_product" class="btn btn-danger btn-action">
                                            <span>Delete</span>
                                            <img src="images/delete.png" alt="Delete">
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function filterProducts() {
            const query = document.getElementById('searchBar').value.toLowerCase();
            const rows = document.querySelectorAll('#productTable tbody tr');

            rows.forEach(row => {
                const productName = row.cells[0].textContent.toLowerCase();
                row.style.display = productName.includes(query) ? '' : 'none';
            });
        }

        function clearSearch() {
            document.getElementById('searchBar').value = '';
            filterProducts();
            toggleClearIcon();
        }

        function toggleClearIcon() {
            const searchBar = document.getElementById('searchBar');
            const clearIcon = document.querySelector('.clear-icon');
            clearIcon.style.display = searchBar.value ? 'block' : 'none';
        }

        document.addEventListener('DOMContentLoaded', toggleClearIcon);
    </script>
</body>

</html>