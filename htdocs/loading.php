<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Determine the redirect URL based on the user's role
$redirectUrl = ($_SESSION['accountLevel'] === 'Admin') ? 'dashboard.php' : 'dashboard.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading...</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .loading-container {
            text-align: center;
        }
        .loading-container h3 {
            font-size: 24px;
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.4em;
        }
    </style>
    <script>
        // Perform redirection after 2 seconds
        setTimeout(function () {
            window.location.href = "<?php echo $redirectUrl; ?>";
        }, 1);
    </script>
</head>
<body>
    <div class="loading-container">
        <h2>Loading</h2>
        <br>
        <div class="spinner-border" role="status">
            <span class="sr-only"></span>
        </div>
    </div>
</body>
</html>
