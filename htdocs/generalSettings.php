<?php
include 'db.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';
$success = '';

// Fetch the current reorder level from the products table
$reorderLevel = 5; // Default value
$stmt = $conn->prepare("SELECT reorderLevel FROM inventory LIMIT 1");
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($reorderLevel);
    $stmt->fetch();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['reorder_level'])) {
        $reorderLevel = intval($_POST['reorder_level']);

        // Update reorder level in the products table
        $stmt = $conn->prepare("UPDATE inventory SET reorderLevel = ? WHERE reorderLevel != ?");
        if ($stmt) {
            $stmt->bind_param("ii", $reorderLevel, $reorderLevel);
            if ($stmt->execute()) {
                $success = "Reorder level updated successfully!";
            } else {
                $error = "Failed to update reorder level. Please try again.";
            }
            $stmt->close();
        } else {
            $error = "Database error: Unable to prepare statement.";
        }
    } elseif (isset($_POST['current_password']) && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if ($newPassword !== $confirmPassword) {
            $error = "New password and confirm password do not match.";
        } else {
            $username = $_SESSION['username'];
            $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->bind_result($hashedPassword);
                $stmt->fetch();
                $stmt->close();

                if (password_verify($currentPassword, $hashedPassword)) {
                    $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                    if ($stmt) {
                        $stmt->bind_param("ss", $newHashedPassword, $username);
                        if ($stmt->execute()) {
                            $success = "Password changed successfully!";
                        } else {
                            $error = "Failed to change password. Please try again.";
                        }
                        $stmt->close();
                    } else {
                        $error = "Database error: Unable to prepare statement.";
                    }
                } else {
                    $error = "Current password is incorrect.";
                }
            } else {
                $error = "Database error: Unable to prepare statement.";
            }
        }
    }

    $conn->close(); // Close the database connection
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Settings</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">

    <style>
        .card-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            flex: 1 1 auto;
        }

        .custom-card {
            background-color: transparent;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            width: 1000px;
            max-width: 100%;
            justify-content: center;
        }

        .custom-input {
            background-color: rgba(0, 0, 0, 0.3);
            ;
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

        .custom-button {
            background: transparent;
            border: 0.5px solid rgba(187, 188, 190, 0.5);
            transition: border-color 0.3s, color 0.3s;
            color: rgba(255, 255, 255, 0.92);
            padding: 10px;
            font-size: 1rem;
            border-radius: 4px;
            width: 100%;
            margin-top: 10px;
        }

        .custom-button:hover {
            background-color: rgba(255, 255, 255, 0.06);
            border: 1.5px solid rgb(187, 188, 190);
            color: #fff;
        }

        .alert-error {
            border: 1px solid red;
            background-color: rgb(255, 147, 147);
            color: red;
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            margin-top: 10px;
        }

        .alert-success {
            position: fixed;
            top: 100px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #d4edda;
            border: 1px solid green;
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
            color: green;
        }

        .alert-success.show {
            display: block;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #fff;
        }
    </style>
</head>

<?php include 'navbar.php'; ?>
<script src="js/bootstrap.bundle.min.js"></script>

<body>
    <div class="main-content fade-in">
        <div class="container">
            <div class="container">
                <div class="card-container">
                    <div class="custom-card">
                        <?php if ($error) echo "<div class='alert-error'>$error</div>"; ?>
                        <?php if ($success) echo "<div class='alert-success show' id='alert-success'>$success</div>"; ?>
                        <div class="section-title">Store Settings</div>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="reorder_level" class="form-label">Low Stock Alert (Reorder Level)</label>
                                <input type="number" name="reorder_level" class="custom-input" placeholder="Enter reorder level" value="<?php echo htmlspecialchars($reorderLevel); ?>" required>
                            </div>
                            <button type="submit" class="custom-button" id="submitButton">Submit</button>
                        </form>
    </div>
                    <div class="custom-card">
                        <div class="section-title">User Settings</div>
                        <form method="POST" action="">
                            <input type="hidden" name="change_password" value="1">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Change Password</label>
                                <input type="password" name="current_password" class="custom-input" placeholder="Current Password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" name="new_password" class="custom-input" placeholder="New Password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="custom-input" placeholder="Confirm New Password" required>
                            </div>
                            <button type="submit" class="custom-button" id="submitButton">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const successAlert = document.querySelector('.alert-success');
                const errorAlert = document.querySelector('.alert-error');

                if (successAlert && successAlert.textContent.trim() !== '') {
                    successAlert.classList.add('show');
                    setTimeout(function() {
                        successAlert.classList.remove('show');
                    }, 4000); // Hide after 4 seconds
                }

                if (errorAlert && errorAlert.textContent.trim() !== '') {
                    errorAlert.style.display = 'block';
                }
            });

            function exportData() {
                // Implement data export functionality
                alert('Data export functionality to be implemented.');
            }

            function importData() {
                // Implement data import functionality
                alert('Data import functionality to be implemented.');
            }

            function clearCache() {
                // Implement cache clearing functionality
                alert('Cache clearing functionality to be implemented.');
            }

            function resetData() {
                // Implement data reset functionality
                alert('Data reset functionality to be implemented.');
            }
        </script>
</body>

</html>