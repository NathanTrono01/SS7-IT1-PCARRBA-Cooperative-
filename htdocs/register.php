<?php
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    // Check if password meets length requirement
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        // Check if username is unique
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = "Username already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $password_hashed);
            $stmt->execute();
            $success = "Registration successful!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-header text-center">
            <h2>Register</h2>
        </div>
        <div class="card-body">
            <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
                <div class="mb-3">
                    <label for="accountLevel" class="form-label">Account Level</label>
                    <select name="accountLevel" class="form-select" required>
                        <option value="Admin">Admin</option>
                        <option value="nonAdmin">Non-Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>