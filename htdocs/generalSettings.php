<?php
session_start();

include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Check if user has admin privileges for database reset
// Only allow specific usernames to see and use the database reset feature
$allowedResetUsers = ['admin', 'superadmin', 'itadmin', 'Nathrix']; // Add specific usernames here
$canResetDatabase = false;

// Check if current user is in the allowed list
if (isset($_SESSION['username']) && in_array($_SESSION['username'], $allowedResetUsers)) {
    $canResetDatabase = true;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';
$success = '';

// Clean up old backups - delete files older than 30 days
if ($canResetDatabase) {
    $backupDir = 'backups/';
    if (is_dir($backupDir)) {
        $files = glob($backupDir . '*.sql');
        $now = time();
        $threshold = 30 * 24 * 60 * 60; // 30 days in seconds
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) > $threshold) {
                    unlink($file); // Delete file if older than 30 days
                }
            }
        }
    }
}

// Handle database import request
if (isset($_FILES['import_file']) && $canResetDatabase) {
    $uploadedFile = $_FILES['import_file'];
    
    // Check for errors
    if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
        $tempPath = $uploadedFile['tmp_name'];
        $fileName = basename($uploadedFile['name']);
        
        // Validate file extension
        if (pathinfo($fileName, PATHINFO_EXTENSION) === 'sql') {
            // Database credentials from db.php, using the global variables defined there
            global $servername, $username, $password, $dbname;
            
            // Import SQL file
            $importCommand = "mysql -h $servername -u $username -p$password $dbname < $tempPath";
            exec($importCommand, $output, $returnCode);
            
            if ($returnCode !== 0) {
                $error = "Failed to import database file. Error code: $returnCode";
            } else {
                $success = "Database imported successfully from file: $fileName";
                
                // Log this critical action
                $currentUsername = $_SESSION['username'];
                $action = "Database Import";
                $details = "User $currentUsername imported database from file: $fileName";
                
                // Check if userId exists and is valid
                if (isset($_SESSION['userId']) && is_numeric($_SESSION['userId'])) {
                    // Check if the userId exists in the users table
                    $checkUserStmt = $conn->prepare("SELECT userId FROM users WHERE userId = ?");
                    $checkUserStmt->bind_param("i", $_SESSION['userId']);
                    $checkUserStmt->execute();
                    $checkUserStmt->store_result();
                    
                    if ($checkUserStmt->num_rows > 0) {
                        // User exists, proceed with logging
                        // Check if username column exists in audit_logs table
                        $columnCheckResult = $conn->query("SHOW COLUMNS FROM audit_logs LIKE 'username'");
                        if($columnCheckResult->num_rows > 0) {
                            // If username column exists, use the original query
                            $stmt = $conn->prepare("INSERT INTO audit_logs (userId, username, action, details) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("isss", $_SESSION['userId'], $currentUsername, $action, $details);
                        } else {
                            // If username column doesn't exist, use a query without the username column
                            $stmt = $conn->prepare("INSERT INTO audit_logs (userId, action, details) VALUES (?, ?, ?)");
                            $stmt->bind_param("iss", $_SESSION['userId'], $action, $details);
                        }
                        $stmt->execute();
                    } else {
                        // User doesn't exist, log this information but don't attempt to insert
                        $error .= " Warning: Could not log this action as user ID is invalid.";
                    }
                    
                    $checkUserStmt->close();
                } else {
                    // No valid userId, log this information
                    $error .= " Warning: Could not log this action as user ID is not set.";
                }
            }
        } else {
            $error = "Invalid file format. Please upload a .sql file.";
        }
    } else {
        $error = "File upload failed with error code: " . $uploadedFile['error'];
    }
}

// Handle database reset request
if (isset($_POST['reset_database']) && $_POST['reset_database'] === 'confirm' && $canResetDatabase) {
    // Create backup first
    $backupFile = 'db_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backupPath = 'backups/' . $backupFile;
    
    // Create backups directory if it doesn't exist
    if (!is_dir('backups')) {
        mkdir('backups', 0755, true);
    }
    
    // Database credentials from db.php, using the global variables defined there
    global $servername, $username, $password, $dbname;
    
    // Create backup using mysqldump
    $backupCommand = "mysqldump -h $servername -u $username -p$password $dbname > $backupPath";
    exec($backupCommand, $output, $returnCode);
    
    if ($returnCode !== 0 || !file_exists($backupPath) || filesize($backupPath) < 100) {
        $error = "Failed to create database backup. Database reset aborted.";
    } else {
        // List of all tables to truncate or reset (credits table added to clear credit entries)
        $tables = [
            'audit_logs',
            'batchItem',
            'categories',
            'inventory',
            'products',
            'sale_item',
            'sales',
            'credits'
            // Excluding 'users' table to preserve login ability
        ];
        
        // Start a transaction for safety
        $conn->begin_transaction();
        
        try {
            // Disable foreign key checks temporarily
            $conn->query("SET foreign_key_checks = 0");
            
            $success_count = 0;
            foreach ($tables as $table) {
                // TRUNCATE is faster than DELETE and resets auto-increment counters
                if ($conn->query("TRUNCATE TABLE `$table`")) {
                    $success_count++;
                }
            }
            
            // Re-enable foreign key checks
            $conn->query("SET foreign_key_checks = 1");
            
            // If all tables were successfully truncated
            if ($success_count === count($tables)) {
                // Initial data setup - recreate default categories
                $conn->query("INSERT INTO categories (categoryName) VALUES ('General')");
                
                $conn->commit();
                $success = "Database has been reset successfully. All product, sales, and credit data have been cleared. Backup created: $backupFile";
                
                // Log this critical action
                $currentUsername = $_SESSION['username'];
                $action = "Database Reset";
                $details = "User $currentUsername performed a complete database reset. Backup created: $backupFile";
                
                // Check if userId exists and is valid
                if (isset($_SESSION['userId']) && is_numeric($_SESSION['userId'])) {
                    // Check if the userId exists in the users table
                    $checkUserStmt = $conn->prepare("SELECT userId FROM users WHERE userId = ?");
                    $checkUserStmt->bind_param("i", $_SESSION['userId']);
                    $checkUserStmt->execute();
                    $checkUserStmt->store_result();
                    
                    if ($checkUserStmt->num_rows > 0) {
                        // User exists, proceed with logging
                        // Check if username column exists in audit_logs table
                        $columnCheckResult = $conn->query("SHOW COLUMNS FROM audit_logs LIKE 'username'");
                        if($columnCheckResult->num_rows > 0) {
                            // Username column exists
                            $stmt = $conn->prepare("INSERT INTO audit_logs (userId, username, action, details) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("isss", $_SESSION['userId'], $currentUsername, $action, $details);
                        } else {
                            // No username column; use alternative query
                            $stmt = $conn->prepare("INSERT INTO audit_logs (userId, action, details) VALUES (?, ?, ?)");
                            $stmt->bind_param("iss", $_SESSION['userId'], $action, $details);
                        }
                        $stmt->execute();
                    }
                    
                    $checkUserStmt->close();
                }
            } else {
                throw new Exception("Not all tables could be reset.");
            }
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error = "Database reset failed: " . $e->getMessage();
        }
    }
}

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
                
                <?php if ($canResetDatabase): ?>
                <!-- Database Management Card - Only visible to specific admin users -->
                <div class="custom-card">
                    <div class="section-title">Database Management</div>
                    <div class="mb-3">
                        <p class="text-warning">Warning: These actions cannot be undone and may result in permanent data loss.</p>
                    </div>
                    
                    <!-- Database Reset Section -->
                    <div class="mb-4">
                        <h5 style="color: #ff6b6b;">Reset Database</h5>
                        <p>This will permanently delete all products, sales, inventory, and other operational data. User accounts will be preserved.</p>
                        <p>A backup file will be automatically created before resetting the database.</p>
                        <button type="button" class="custom-button" style="background-color: rgba(255, 0, 0, 0.1); border-color: #ff6b6b;" onclick="confirmDatabaseReset()">Reset Database</button>
                    </div>
                    
                    <!-- Database Import Section -->
                    <div class="mb-4">
                        <h5 style="color: #4dabf7;">Import Database</h5>
                        <p>Import a SQL database file. This will replace the current database structure and data.</p>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <input type="file" name="import_file" class="custom-input" accept=".sql" required>
                            </div>
                            <button type="button" class="custom-button" style="background-color: rgba(75, 171, 247, 0.1); border-color: #4dabf7;" onclick="confirmDatabaseImport(this.form)">Import Database</button>
                        </form>
                    </div>
                    
                    <!-- Database Backups Section -->
                    <div class="mb-4">
                        <h5 style="color: #4dabf7;">Database Backups</h5>
                        <p>View and download existing database backups. Backups are automatically deleted after 30 days.</p>
                        <?php
                        $backups = glob('backups/*.sql');
                        if (!empty($backups)) {
                            echo '<ul style="list-style-type: none; padding-left: 0;">';
                            foreach($backups as $backup) {
                                $filename = basename($backup);
                                $fileDate = date("F d, Y H:i:s", filemtime($backup));
                                echo '<li style="margin-bottom: 5px;"><a href="' . $backup . '" download style="color: #4dabf7;">' . $filename . '</a> <span style="color: #aaa; font-size: 0.8em;">(' . $fileDate . ')</span></li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p>No backups available.</p>';
                        }
                        ?>
                    </div>
                    
                    <!-- Hidden form for database reset -->
                    <form id="resetDatabaseForm" method="POST" action="" style="display: none;">
                        <input type="hidden" name="reset_database" value="confirm">
                    </form>
                </div>
                <?php endif; ?>
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

            function confirmDatabaseReset() {
                // First confirmation
                if (confirm('WARNING: You are about to delete ALL data from the database.\n\nA backup will be created automatically, but this action will remove all products, sales, and inventory data.\n\nAre you sure you want to continue?')) {
                    // Second confirmation with typing requirement for extra safety
                    const confirmText = prompt('To confirm, please type "RESET" in all capitals:');
                    if (confirmText === 'RESET') {
                        // Show loading message
                        const loadingModal = document.createElement('div');
                        loadingModal.style.position = 'fixed';
                        loadingModal.style.top = '0';
                        loadingModal.style.left = '0';
                        loadingModal.style.width = '100%';
                        loadingModal.style.height = '100%';
                        loadingModal.style.backgroundColor = 'rgba(0,0,0,0.7)';
                        loadingModal.style.zIndex = '9999';
                        loadingModal.style.display = 'flex';
                        loadingModal.style.alignItems = 'center';
                        loadingModal.style.justifyContent = 'center';
                        loadingModal.style.color = 'white';
                        loadingModal.innerHTML = '<div><h3>Creating database backup and resetting...</h3><p>Please do not close this window.</p></div>';
                        document.body.appendChild(loadingModal);
                        
                        // Submit the form after a short delay to allow the modal to render
                        setTimeout(() => {
                            document.getElementById('resetDatabaseForm').submit();
                        }, 100);
                    } else {
                        alert('Database reset cancelled. The confirmation text did not match "RESET".');
                    }
                }
            }
            
            function confirmDatabaseImport(form) {
                // First confirmation
                if (confirm('WARNING: You are about to import a database file. This may overwrite existing data.\n\nAre you sure you want to continue?')) {
                    // Second confirmation with typing requirement for extra safety
                    const confirmText = prompt('To confirm, please type "IMPORT" in all capitals:');
                    if (confirmText === 'IMPORT') {
                        // Show loading message
                        const loadingModal = document.createElement('div');
                        loadingModal.style.position = 'fixed';
                        loadingModal.style.top = '0';
                        loadingModal.style.left = '0';
                        loadingModal.style.width = '100%';
                        loadingModal.style.height = '100%';
                        loadingModal.style.backgroundColor = 'rgba(0,0,0,0.7)';
                        loadingModal.style.zIndex = '9999';
                        loadingModal.style.display = 'flex';
                        loadingModal.style.alignItems = 'center';
                        loadingModal.style.justifyContent = 'center';
                        loadingModal.style.color = 'white';
                        loadingModal.innerHTML = '<div><h3>Importing database...</h3><p>Please do not close this window.</p></div>';
                        document.body.appendChild(loadingModal);
                        
                        // Submit the form
                        form.submit();
                    } else {
                        alert('Database import cancelled. The confirmation text did not match "IMPORT".');
                    }
                }
            }
        </script>
    </body>
</html>