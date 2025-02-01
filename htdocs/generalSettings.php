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
    <title>Settings</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <link rel="stylesheet" href="css/registercustomcard.css">
</head>

<?php include 'navbar.php'; ?>
<script src="js/bootstrap.bundle.min.js"></script>

<body>
<div class="main-content">
    <div class="container">
        <h1>Settings</h1>
        <p>Welcome, <?php echo $_SESSION['username']; ?>.</p>
        <a href="logout.php" class="btn btn-secondary">Logout</a>

        <div class="container">
            <h2>General Settings</h2>
            <p>This section is visible to all users.</p>
        </div>

        <?php if ($_SESSION['accountLevel'] === 'Admin'): ?>
            <div class="container">
                <h2>Admin Panel</h2>
                <form method="POST" action="" class="custom-card">
                    <div class="card-header text-center">
                        <h3>Register a User</h3>
                        <hr>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" class="custom-input" placeholder="Username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" class="custom-input" placeholder="Password" required>
                    </div>
                    <div class="mb-3">
                        <label for="accountLevel" class="form-label">Account Level</label>
                        <select name="accountLevel" class="form-select custom-input" required>
                            <option value="nonAdmin">Non-Admin</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <?php if (isset($error)) echo "<div class='alert'>$error</div>"; ?>
                    <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
                    <button type="submit" class="custom-button" id="submitButton">Register</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>