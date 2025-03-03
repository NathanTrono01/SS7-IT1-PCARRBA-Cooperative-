<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db.php';

// Function to check if a product is referenced in the sale_item table
function isProductReferencedInSaleItem($conn, $productId)
{
    $query = "SELECT COUNT(*) as count FROM sale_item WHERE productId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

// Fetch all products with total stock level and category name
$query = "
    SELECT 
        p.productId, 
        p.productName, 
        c.categoryName AS productCategory, 
        p.unitPrice, 
        COALESCE(SUM(b.quantity), 0) AS totalStock,
        p.unit,
        p.imagePath as image
    FROM 
        products p
    LEFT JOIN 
        batchItem b ON p.productId = b.productId
    LEFT JOIN 
        categories c ON p.categoryId = c.categoryId
    GROUP BY 
        p.productId, p.productName, c.categoryName, p.unitPrice, p.unit, p.imagePath
";


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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Grid</title>
    <style>
        /* Dark theme styles */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Grid layout */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(275px, 1fr));
            gap: 20px;
        }

        /* Product card */
        .product-card {
            background-color: #1e293b;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease-in-out;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        /* Product image */
        .product-image {
            height: 200px;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-image img:hover {
            transform: scale(1.05);
        }

        /* Product info */
        .product-info {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .product-name {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 12px 0;
        }

        .product-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
            align-items: center;
        }

        .detail-label {
            color: #94a3b8;
        }

        /* Status badges */
        .status-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }

        .status-active {
            background-color: #15803d;
            color: white;
        }

        .status-disabled {
            background-color: #1d4ed8;
            color: white;
            opacity: 0.85;
        }

        .status-badge::before {
            content: "•";
            margin-right: 4px;
        }

        /* Price styling */
        .product-price {
            font-weight: 700;
        }

        /* Action buttons */
        .card-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .action-button {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            text-align: center;
            flex: 1;
            text-decoration: none;
            display: inline-block;
        }

        .edit-button {
            background-color: #0284c7;
            color: white;
        }

        .delete-button {
            background-color: #b91c1c;
            color: white;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Toggle view buttons */
        .view-toggle {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 16px;
        }

        .toggle-button {
            background-color: #334155;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            margin-left: 8px;
        }

        .toggle-button.active {
            background-color: #0284c7;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="view-toggle">
            <button id="tableViewBtn" class="toggle-button">Table View</button>
            <button id="gridViewBtn" class="toggle-button active">Grid View</button>
        </div>

        <!-- This div will contain the grid view -->
        <div id="gridView" class="product-grid">
            <!-- Product cards will be generated here by JavaScript -->
        </div>

        <!-- This div will contain the table view (from your original PHP) -->
        <div id="tableView" style="display: none;">
            <!-- Your original table goes here -->
        </div>
    </div>

    <script>
        // Sample product data (would be replaced by your PHP data)
        const products = [{
                id: 1,
                name: "Ocean",
                category: "Furniture",
                isActive: true,
                sales: 11,
                stock: 36,
                price: 560,
                image: "path/to/image1.jpg"
            },
            {
                id: 2,
                name: "Lou",
                category: "Kitchen",
                isActive: false,
                sales: 6,
                stock: 46,
                price: 710,
                image: "path/to/image2.jpg"
            },
            {
                id: 3,
                name: "Yellow",
                category: "Decoration",
                isActive: true,
                sales: 61,
                stock: 56,
                price: 360,
                image: "path/to/image3.jpg"
            },
            {
                id: 4,
                name: "Dreamy",
                category: "Bedroom",
                isActive: false,
                sales: 41,
                stock: 66,
                price: 260,
                image: "path/to/image4.jpg"
            },
            {
                id: 5,
                name: "Boheme",
                category: "Furniture",
                isActive: true,
                sales: 27,
                stock: 30,
                price: 480,
                image: "path/to/image5.jpg"
            },
            {
                id: 6,
                name: "Sky",
                category: "Bathroom",
                isActive: true,
                sales: 18,
                stock: 25,
                price: 390,
                image: "path/to/image6.jpg"
            },
            {
                id: 7,
                name: "Midnight",
                category: "Furniture",
                isActive: true,
                sales: 33,
                stock: 42,
                price: 620,
                image: "path/to/image7.jpg"
            },
            {
                id: 8,
                name: "Boheme 2",
                category: "Furniture",
                isActive: false,
                sales: 15,
                stock: 20,
                price: 490,
                image: "path/to/image8.jpg"
            }
        ];

        // Function to generate product cards
        function renderProductGrid() {
            const gridContainer = document.getElementById('gridView');
            gridContainer.innerHTML = '';

            products.forEach(product => {
                const productCard = document.createElement('div');
                productCard.className = 'product-card';

                const statusClass = product.isActive ? 'status-active' : 'status-disabled';
                const statusText = product.isActive ? 'Active' : 'Disabled';

                // Use a default image if product image is missing
                const imgSrc = product.image || 'images/no-image.png';

                productCard.innerHTML = `
                    <div class="product-image">
                        <img src="${imgSrc}" alt="${product.name}" onerror="this.src='images/no-image.png'">
                    </div>
                    <div class="product-info">
                        <h3 class="product-name">${product.name}</h3>
                        
                        <div class="product-detail">
                            <span class="detail-label">Category:</span>
                            <span>${product.category}</span>
                        </div>
                        
                        <div class="product-detail">
                            <span class="detail-label">Status:</span>
                            <span class="status-badge ${statusClass}">${statusText}</span>
                        </div>
                        
                        <div class="product-detail">
                            <span class="detail-label">Sales:</span>
                            <span>${product.sales}</span>
                        </div>
                        
                        <div class="product-detail">
                            <span class="detail-label">Stock:</span>
                            <span>${product.stock}</span>
                        </div>
                        
                        <div class="product-detail">
                            <span class="detail-label">Price:</span>
                            <span class="product-price">$${product.price}</span>
                        </div>
                        
                        <div class="card-actions">
                            <a href="editProduct.php?productId=${product.id}" class="action-button edit-button">Edit</a>
                            <button class="action-button delete-button" onclick="confirmDelete(${product.id})">Delete</button>
                        </div>
                    </div>
                `;

                gridContainer.appendChild(productCard);
            });
        }

        // Function to handle delete confirmation
        function confirmDelete(productId) {
            if (confirm("Are you sure you want to delete this product?")) {
                // In real implementation, submit a form or make an AJAX request
                console.log("Deleting product with ID:", productId);
                // Example AJAX delete:
                // fetch('delete_product.php', {
                //     method: 'POST',
                //     headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                //     body: `delete_product=1&productId=${productId}`
                // })
                // .then(response => response.json())
                // .then(data => {
                //     if (data.success) {
                //         // Remove the product from the UI
                //     }
                // });
            }
        }

        // Toggle between grid and table views
        document.getElementById('gridViewBtn').addEventListener('click', function() {
            document.getElementById('gridView').style.display = 'grid';
            document.getElementById('tableView').style.display = 'none';
            document.getElementById('gridViewBtn').classList.add('active');
            document.getElementById('tableViewBtn').classList.remove('active');
        });

        document.getElementById('tableViewBtn').addEventListener('click', function() {
            document.getElementById('gridView').style.display = 'none';
            document.getElementById('tableView').style.display = 'block';
            document.getElementById('tableViewBtn').classList.add('active');
            document.getElementById('gridViewBtn').classList.remove('active');
        });

        // Initialize the grid on page load
        document.addEventListener('DOMContentLoaded', function() {
            renderProductGrid();
        });
    </script>
</body>

</html>