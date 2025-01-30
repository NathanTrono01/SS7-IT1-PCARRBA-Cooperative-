<?php
session_start();
if (!isset($_SESSION['username'])) {
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

<?php include 'navbar.php'; ?>
<script src="js/bootstrap.bundle.min.js"></script>

<body>
<div class="main-content">
        <div class="container">
            <h1>Dashboard</h1>
            <p>Welcome, <?php echo $_SESSION['username']; ?>!</p>
            <a href="logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>

</div>
</body>
</html>
