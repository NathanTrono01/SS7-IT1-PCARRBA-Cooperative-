<?php
session_start();
include 'db.php';

$credit_error = "";
$creditResults = [];
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['searchCredit'])) {
    $credit_name = trim($_POST['credit_name']);
    
    if (empty($credit_name)) {
        $credit_error = "Please enter a name to search.";
    } else {
        // First, get total count for pagination
        $count_query = "SELECT COUNT(*) as total FROM creditor c 
                       LEFT JOIN credits cr ON c.creditorId = cr.creditorId
                       WHERE c.customerName LIKE ? AND c.creditBalance > 0";
        
        $stmt = $conn->prepare($count_query);
        $like = "%" . $credit_name . "%";
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $total_records = $stmt->get_result()->fetch_assoc()['total'];
        $total_pages = ceil($total_records / $records_per_page);

        // Then get the actual records
        $query = "SELECT c.customerName, c.phoneNumber, c.amountPaid, c.creditBalance,
                  CASE 
                    WHEN c.amountPaid = 0 THEN 'Unpaid'
                    ELSE 'Partially Paid'
                  END as payment_status,
                  cr.transactionDate as date
                  FROM creditor c
                  LEFT JOIN credits cr ON c.creditorId = cr.creditorId
                  WHERE c.customerName LIKE ? 
                  AND c.creditBalance > 0
                  LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $like, $records_per_page, $offset);
        $stmt->execute();
        $creditResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($creditResults)) {
            $credit_error = "No unpaid credits found for " . htmlspecialchars($credit_name);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>PCARBA Credit Search</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/customcard.css">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        .admin-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: transparent;
            border: 1px solid #bdbebe;
            color: #bdbebe;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .admin-btn:hover {
            border-color: white;
            color: white;
        }
        .modal-content {
            background-color: #333;
            color: white;
        }
        .modal-header {
            border-bottom: 1px solid #444;
        }
        .modal-footer {
            border-top: 1px solid #444;
        }
        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        .text-muted {
            color: #bdbebe !important;
        }
    </style>
</head>
<body>
    <!-- Admin Login Button -->
    <button type="button" class="admin-btn" data-bs-toggle="modal" data-bs-target="#adminModal">
        System Admin
    </button>

    <!-- Admin Login Modal -->
    <div class="modal fade" id="adminModal" tabindex="-1" aria-labelledby="adminModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="w-100 text-center">
                        <h5 class="modal-title" id="adminModalLabel">PCARBA Sari-Sari Store</h5>
                        <small class="text-muted">Inventory System Access</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="admin.php" id="adminLoginForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <input type="text" name="username" class="custom-input" placeholder="Username" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="custom-input" placeholder="Password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="custom-button" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="custom-button">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="custom-card">
            <div class="text-center mb-4">
                <h3>PCARBA Sari-Sari Store</h3>
                <small class="text-muted">Credit Records Search</small>
            </div>

            <!-- Credit Search Form -->
            <form method="POST" action="">
                <div class="mb-3">
                    <input type="text" 
                           name="credit_name" 
                           class="custom-input" 
                           placeholder="Enter your name" 
                           required 
                           value="<?php echo isset($_POST['credit_name']) ? htmlspecialchars($_POST['credit_name']) : ''; ?>">
                </div>
                <button type="submit" name="searchCredit" class="custom-button">Search Credit</button>
                
                <?php if (!empty($credit_error)): ?>
                    <div class="alert mt-3"><?php echo $credit_error; ?></div>
                <?php endif; ?>
            </form>

            <!-- Search Results -->
            <?php if (!empty($creditResults)): ?>
                <div class="table-responsive mt-4" style="max-height: 400px;">
                    <table class="table table-dark table-hover">
                        <thead style="position: sticky; top: 0; background-color: #333;">
                            <tr>
                                <th>Name</th>
                                <th>Amount Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($creditResults as $credit): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($credit['customerName']); ?></td>
                                    <td>₱<?php echo number_format($credit['amountPaid'], 2); ?></td>
                                    <td>₱<?php echo number_format($credit['creditBalance'], 2); ?></td>
                                    <td>
                                        <span class="<?php 
                                            echo ($credit['payment_status'] == 'Unpaid') ? 'text-danger' : 'text-warning';
                                        ?>">
                                            <?php echo $credit['payment_status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (isset($total_pages) && $total_pages > 1): ?>
                    <div class="d-flex justify-content-center mt-3">
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo ($page - 1); ?>&credit_name=<?php echo urlencode($credit_name); ?>">&laquo;</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&credit_name=<?php echo urlencode($credit_name); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo ($page + 1); ?>&credit_name=<?php echo urlencode($credit_name); ?>">&raquo;</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- <div class="text-center mt-3">
                <a href="admin.php" class="text-muted" style="font-size: 12px;">System Login</a>
            </div> -->
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);

        // Optional: Add loading animation for admin login
        document.getElementById('adminLoginForm').addEventListener('submit', function() {
            this.querySelector('button[type="submit"]').disabled = true;
            this.querySelector('button[type="submit"]').innerHTML = '•••';
        });
    </script>

    <style>
        .table-responsive {
            scrollbar-width: thin;
            scrollbar-color: #666 #333;
        }
        .table-responsive::-webkit-scrollbar {
            width: 8px;
        }
        .table-responsive::-webkit-scrollbar-track {
            background: #333;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background-color: #666;
            border-radius: 4px;
        }
        .pagination .page-link {
            background-color: #333;
            border-color: #444;
            color: #fff;
        }
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }
        .pagination .page-link:hover {
            background-color: #444;
            border-color: #666;
            color: #fff;
        }
    </style>
</body>
</html>