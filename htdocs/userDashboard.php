<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'nonAdmin') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Dashboard</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
</head>
<body class="bg-dark text-white">
<div class="container mt-5">
    <h1>User Dashboard</h1>
    <p>Welcome, <?php echo $_SESSION['username']; ?>.</p>
    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    <a href="logout.php" class="btn btn-secondary">Logout</a>
</div>
</body>
</html>
