<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['accountLevel'] !== 'nonAdmin') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>General Settings</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    
</head>

<?php include 'navbar.php'; ?>
<script src="js/bootstrap.bundle.min.js"></script>

<body>
<div class="main-content">
<div class="container">
    <h1>Settings</h1>
    <p>Welcome, <?php echo $_SESSION['username']; ?>.</p>
    <a href="logout.php" class="btn btn-secondary">Logout</a>
</div>
</div>

</body>
</html>
