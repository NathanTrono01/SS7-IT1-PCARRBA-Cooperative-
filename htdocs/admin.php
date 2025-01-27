<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['account_level'] !== 'admin') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Panel</title>
    <link href="bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h1>Admin Panel</h1>
    <p>Welcome, <?php echo $_SESSION['username']; ?>. You have administrative privileges.</p>
    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    <a href="logout.php" class="btn btn-secondary">Logout</a>
</div>
</body>
</html>
