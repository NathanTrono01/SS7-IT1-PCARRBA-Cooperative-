<?php
include 'db.php';
session_start();

if (!isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if username is unique
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username already exists.";
        } else {
            $stmt->close(); // Close the previous statement

            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("ss", $username, $password);
                if ($stmt->execute()) {
                    $success = "Registration successful!";
                } else {
                    $error = "Registration failed. Please try again.";
                }
                $stmt->close(); // Close the statement after execution
            } else {
                $error = "Database error: Unable to prepare statement.";
            }
        }
    } else {
        $error = "Database error: Unable to prepare statement.";
    }
    $conn->close(); // Close the database connection
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        /* Custom Card Styling */
        .custom-card {
            width: 90%;
            max-width: 400px;
            background-color: #333;
            color: white;
            padding: 20px;
            box-sizing: border-box;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin: 20px auto; /* Center the card */
        }

        /* Custom Input Styling */
        .custom-input {
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            border: 1px solid hsla(0, 0%, 100%, 0.2);
            padding: 8.5px;
            font-size: 1rem;
            border-radius: 7.5px;
            width: 100%;
        }

        .custom-input::placeholder {
            color: #bdbebe;
        }

        .custom-input:focus {
            border-color: white;
            color: white;
            outline: none;
            background-color: rgba(0, 0, 0, 0.7);
        }

        /* Custom Button Styling */
        .custom-button {
            background: transparent;
            border: 1px solid #bdbebe;
            color: hsla(0, 0%, 100%, 0.7);
            transition: border-color 0.3s, color 0.3s;
            padding: 10px;
            font-size: 1rem;
            border-radius: 4px;
            width: 100%;
            margin-top: 10px;
        }

        .custom-button:hover {
            border-color: white;
            background: transparent;
            color: white;
        }

        /* Alert Styling */
        .alert {
            border: 1px solid red;
            background-color: #f8d7da;
            color: red;
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            margin-top: 10px;
        }

        .alert-success {
            border: 1px solid green;
            background-color: #d4edda;
            color: green;
        }

        /* Loading Indicator Styling */
        .loading-indicator {
            display: none;
            color: white;
            font-size: 1.5rem;
            text-align: center;
            margin-top: 15px;
        }

        /* Enhanced Responsive Adjustments */
        @media (min-width: 769px) {
            .custom-card {
                width: 400px;
            }
        }

        @media (max-width: 768px) {
            .custom-card {
                width: 80%;
                max-width: 350px;
                padding: 18px;
            }

            .custom-input,
            .custom-button {
                font-size: 0.95rem;
                padding: 8px;
            }

            .loading-indicator {
                font-size: 1.3rem;
                margin-top: 12px;
            }
        }

        @media (max-width: 576px) {
            .custom-card {
                width: 95%;
                max-width: 100%;
                padding: 15px;
                border-radius: 6px;
            }

            .custom-input,
            .custom-button {
                font-size: 0.9rem;
                padding: 7px;
            }

            .loading-indicator {
                font-size: 1.2rem;
                margin-top: 10px;
            }
        }

        /* Form Background Styling */
        form.custom-card {
            background-color: #333;
        }
    </style>
</head>

<?php include 'navbar.php'; ?>
<script src="js/bootstrap.bundle.min.js"></script>

<body>

</body>

</html>