<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include('db.php');

// Fetch total credit
$totalCreditQuery = "SELECT SUM(totalAmount) as totalCredit FROM credits WHERE userId = ?";
$stmt = $conn->prepare($totalCreditQuery);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();
$totalCredit = $result->fetch_assoc()['totalCredit'] ?? 0;

// Fetch credits
$creditsQuery = "SELECT credits.creditId, creditor.customerName, credits.totalAmount, credits.transactionDate, credits.paymentStatus 
                 FROM credits 
                 JOIN creditor ON credits.creditorId = creditor.creditorId 
                 WHERE credits.userId = ?";
$stmt = $conn->prepare($creditsQuery);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$credits = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch creditors
$creditorsQuery = "SELECT creditorId, customerName FROM creditor";
$creditors = $conn->query($creditorsQuery)->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $creditorId = $_POST['creditorId'];
    $totalAmount = $_POST['totalAmount'];
    $transactionDate = date('Y-m-d H:i:s');
    $paymentStatus = 'Unpaid';

    $insertCreditQuery = "INSERT INTO credits (userId, creditorId, totalAmount, transactionDate, paymentStatus) 
                          VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertCreditQuery);
    $stmt->bind_param("iidss", $_SESSION['userId'], $creditorId, $totalAmount, $transactionDate, $paymentStatus);
    $stmt->execute();

    header("Location: credit.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Credit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        .main-content {
            padding: 20px;
        }

        .table-wrapper {
            background-color: #191a1f;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.79);
            margin-top: 20px;
        }

        .table-wrapper h2 {
            color: #f7f7f8;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: #f7f7f8;
        }

        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #333942;
        }

        table th {
            background-color: #0c0c0f;
            color: #f7f7f8;
            font-weight: bold;
        }

        table tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .btn-action {
            padding: 6px 12px;
            font-size: 14px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

        .btn-action.paid {
            background-color: #28a745;
            border: none;
            color: white;
        }

        .btn-action.paid:hover {
            background-color: #218838;
        }

        .btn-action.delete {
            background-color: #dc3545;
            border: none;
            color: white;
        }

        .btn-action.delete:hover {
            background-color: #c82333;
        }

        .form-container {
            background-color: #1f1f1f;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .form-container label {
            color: #f7f7f8;
            font-weight: 500;
        }

        .form-container input,
        .form-container select {
            background-color: #191a1f;
            border: 1px solid #333942;
            color: #f7f7f8;
        }

        .form-container input:focus,
        .form-container select:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.5);
        }

        .total-credit {
            font-size: 18px;
            font-weight: bold;
            color: #f7f7f8;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <div class="main-content">
        <div class="container">
            <div class="table-wrapper">
                <h2>Credits</h2>
                <div class="total-credit">
                    Total Credit: ₱<?php echo number_format($totalCredit, 2); ?>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Transaction Date</th>
                            <th>Creditor</th>
                            <th>Total Amount</th>
                            <th>Payment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($credits as $credit) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($credit['transactionDate']); ?></td>
                                <td><?php echo htmlspecialchars($credit['customerName']); ?></td>
                                <td>₱<?php echo number_format($credit['totalAmount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($credit['paymentStatus']); ?></td>
                                <td>
                                    <?php if ($credit['paymentStatus'] === 'Unpaid') { ?>
                                        <button class="btn-action paid" onclick="markAsPaid(<?php echo $credit['creditId']; ?>)">Mark as Paid</button>
                                    <?php } ?>
                                    <button class="btn-action delete" onclick="deleteCredit(<?php echo $credit['creditId']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="form-container">
                <h3>Add New Credit</h3>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="creditorId" class="form-label">Creditor</label>
                        <select class="form-control" id="creditorId" name="creditorId" required>
                            <?php foreach ($creditors as $creditor) { ?>
                                <option value="<?php echo $creditor['creditorId']; ?>"><?php echo htmlspecialchars($creditor['customerName']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="totalAmount" class="form-label">Total Amount</label>
                        <input type="number" class="form-control" id="totalAmount" name="totalAmount" step="0.01" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Credit</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function markAsPaid(creditId) {
            if (confirm("Are you sure you want to mark this credit as paid?")) {
                fetch(`mark_as_paid.php?creditId=${creditId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert("Failed to mark as paid.");
                        }
                    });
            }
        }

        function deleteCredit(creditId) {
            if (confirm("Are you sure you want to delete this credit?")) {
                fetch(`delete_credit.php?creditId=${creditId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert("Failed to delete credit.");
                        }
                    });
            }
        }
    </script>
</body>

</html>