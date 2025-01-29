<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
?>

<head>
    <title>Credit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <link rel="stylesheet" href="css/layer2.css">
</head>
<?php include 'navbar.php'; ?>
<body>
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h1>Credit</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>.</p>
            <!-- Inventory Management Content Goes Here -->
            <a href="logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>