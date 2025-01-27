<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['account_level'] !== 'user') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Dashboard</title>
    <link href="bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h1>User Dashboard</h1>
    <p>Welcome, <?php echo $_SESSION['username']; ?>. You have standard user access.</p>
    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    <a href="logout.php" class="btn btn-secondary">Logout</a>
</div>
</body>
</html>
