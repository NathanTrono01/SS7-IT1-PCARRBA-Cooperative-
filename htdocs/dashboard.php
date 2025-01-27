<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Redirect based on user role
if ($_SESSION['role'] === 'Admin') {
    header("Refresh: 2; url=admin.php"); // Wait for 2 seconds before redirecting
    $redirectUrl = 'admin.php';
} else {
    header("Refresh: 2; url=user.php"); // Wait for 2 seconds before redirecting
    $redirectUrl = 'user.php';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading...</title>
    <link href="bootstrap.min.css" rel="stylesheet">
    <style>
        /* Style the loading screen */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .loading-container {
            text-align: center;
        }
        .loading-container h3 {
            font-size: 24px;
            color: #007bff;
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.4em;
        }
    </style>
</head>
<body class="bg-light">
    <div class="loading-container">
        <h3>Loading, please wait...</h3>
        <div class="spinner-border" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
</body>
</html>
