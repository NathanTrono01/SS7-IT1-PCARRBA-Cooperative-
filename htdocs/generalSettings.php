<?php
session_start();

include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Change from a static allowed users list to a vault-based authentication system
$canResetDatabase = false; // Default to no access
$databaseVaultUnlocked = false; // Track if the vault is currently unlocked

// Check if vault unlock request was submitted
if (isset($_POST['unlock_vault']) && isset($_POST['vault_password'])) {
    // The master vault password - ideally this should be stored securely, not hardcoded
    // For a production system, consider using a separate secure configuration
    $masterVaultPassword = "PCARBAStoreAdmin"; // Change to a very strong password
    
    if ($_POST['vault_password'] === $masterVaultPassword) {
        // Password correct - unlock access and set session flag
        $_SESSION['db_vault_unlocked'] = true;
        $_SESSION['db_vault_unlock_time'] = time();
        $databaseVaultUnlocked = true;
        $canResetDatabase = true;
    } else {
        // Wrong password
        $error = "Admin Panel access denied. Incorrect password.";
        
        // Log failed attempt
        $currentUsername = $_SESSION['username'];
        $action = "Admin Access Attempt";
        $details = "User $currentUsername attempted to access Admin with incorrect password.";
        
        // Add code to log this to your audit log if desired
        if (isset($_SESSION['userId']) && is_numeric($_SESSION['userId'])) {
            $stmt = $conn->prepare("INSERT INTO audit_logs (userId, action, details) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iss", $_SESSION['userId'], $action, $details);
                if (!$stmt->execute()) {
                    // Log failure can be handled here, e.g. add to $error message
                    $error .= " (Warning: Failed to log this action: " . $conn->error . ")";
                }
                $stmt->close();
            } else {
                // Handle prepare statement failure
                $error .= " (Warning: Failed to prepare log statement: " . $conn->error . ")";
            }
        }
    }
} 
// Check if vault is already unlocked via session
elseif (isset($_SESSION['db_vault_unlocked']) && $_SESSION['db_vault_unlocked'] === true) {
    // Check if the session has expired (30 minutes timeout)
    $vaultTimeout = 30 * 60; // 30 minutes in seconds
    if ((time() - $_SESSION['db_vault_unlock_time']) < $vaultTimeout) {
        $databaseVaultUnlocked = true;
        $canResetDatabase = true;
    } else {
        // Session expired, require re-authentication
        unset($_SESSION['db_vault_unlocked']);
        unset($_SESSION['db_vault_unlock_time']);
    }
}

// Add vault lock functionality
if (isset($_POST['lock_vault'])) {
    // Lock the vault by removing session access
    unset($_SESSION['db_vault_unlocked']);
    unset($_SESSION['db_vault_unlock_time']);
    $databaseVaultUnlocked = false;
    $canResetDatabase = false;
    $success = "Database vault locked successfully.";
}

// Handle admin registration form submission
if (isset($_POST['register_admin']) && $canResetDatabase) {
    $adminUsername = htmlspecialchars($_POST['admin_username']);
    $adminPassword = $_POST['admin_password'];
    $adminConfirmPassword = $_POST['admin_confirm_password'];

    // Check if passwords match
    if ($adminPassword !== $adminConfirmPassword) {
        $error = "Passwords do not match.";
    }
    // Check if password meets length requirement
    else if (strlen($adminPassword) < 8) {
        $error = "Password must be at least 8 characters long.";
    } 
    else {
        // Hash the password
        $password_hashed = password_hash($adminPassword, PASSWORD_DEFAULT);

        // Check if username is unique
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $adminUsername);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already exists. Choose a different username.";
        } else {
            // Insert new admin user - using the actual schema structure
            $stmt = $conn->prepare("INSERT INTO users (username, password, login_count) VALUES (?, ?, 0)");
            $stmt->bind_param("ss", $adminUsername, $password_hashed);
            
            if ($stmt->execute()) {
                $success = "New account registered successfully!";
                
                // Log this critical action
                $currentUsername = $_SESSION['username'];
                $action = "Account Creation";
                $details = "User $currentUsername created new account: $adminUsername";
                
                if (isset($_SESSION['userId']) && is_numeric($_SESSION['userId'])) {
                    $logStmt = $conn->prepare("INSERT INTO audit_logs (userId, action, details) VALUES (?, ?, ?)");
                    $logStmt->bind_param("iss", $_SESSION['userId'], $action, $details);
                    $logStmt->execute();
                }
            } else {
                $error = "Failed to register account. Database error.";
            }
            $stmt->close();
        }
    }
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
                        if ($columnCheckResult->num_rows > 0) {
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
                        if ($columnCheckResult->num_rows > 0) {
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
        /* Main container styling */
        .settings-container {
            max-width: 100%;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Page header */
        .settings-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }

        .settings-header h2 {
            font-size: 28px;
            font-weight: 600;
            margin: 0;
        }

        .settings-header p {
            color: #94a3b8;
            margin: 5px 0 0 0;
        }

        /* Tab navigation */
        .settings-tabs {
            display: flex;
            background-color: rgba(23, 25, 30, 0.6);
            border-radius: 12px;
            padding: 5px;
            margin-bottom: 30px;
            overflow-x: auto;
            scrollbar-width: thin;
            scrollbar-color: #335fff #1e2028;
        }

        .settings-tabs::-webkit-scrollbar {
            height: 4px;
        }

        .settings-tabs::-webkit-scrollbar-track {
            background: transparent;
        }

        .settings-tabs::-webkit-scrollbar-thumb {
            background-color: rgba(51, 95, 255, 0.5);
            border-radius: 4px;
        }

        .settings-tab {
            padding: 12px 20px;
            color: #94a3b8;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .settings-tab:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.05);
        }

        .settings-tab.active {
            color: white;
            background-color: rgba(51, 95, 255, 0.2);
        }

        .tab-icon {
            font-size: 16px;
        }

        /* Card styling */
        .settings-card {
            margin-bottom: 24px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .settings-card:hover {
            border-color: rgba(51, 95, 255, 0.3);
        }

        .card-header {
            padding: 20px 24px;
            background-color: rgba(33, 34, 39, 0.6);
            border-bottom: 1px solid rgba(51, 57, 66, 0.5);
        }

        .card-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: white;
        }

        .card-header p {
            margin: 5px 0 0 0;
            color: #94a3b8;
            font-size: 14px;
        }

        .card-body {
            padding: 24px;
        }

        /* Form controls */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #f7f7f8;
            font-weight: 500;
        }

        .form-hint {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 5px;
            display: block;
        }

        .custom-input {
            background-color: rgba(17, 18, 22, 0.7);
            color: white;
            border: 1px solid rgba(51, 57, 66, 0.5);
            padding: 12px 16px;
            font-size: 16px;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .custom-input::placeholder {
            color: rgba(247, 247, 248, 0.5);
        }

        .custom-input:focus {
            border-color: #335fff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(51, 95, 255, 0.2);
        }

        .custom-button {
            background-color: rgba(51, 95, 255, 0.1);
            color: #f7f7f8;
            border: 1px solid rgba(51, 95, 255, 0.3);
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            text-align: center;
            width: auto;
            min-width: 120px;
        }

        .custom-button:hover {
            background-color: rgba(51, 95, 255, 0.2);
            border-color: rgba(51, 95, 255, 0.5);
            transform: translateY(-1px);
        }

        .btn-primary {
            background-color: rgb(42, 56, 255);
            border-color: transparent;
        }

        .btn-primary:hover {
            background-color: rgb(61, 74, 255);
        }

        .btn-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #ff6b6b;
            border-color: rgba(220, 53, 69, 0.3);
        }

        .btn-danger:hover {
            background-color: rgba(220, 53, 69, 0.2);
            border-color: rgba(220, 53, 69, 0.5);
        }

        .btn-warning {
            background-color: rgba(255, 152, 0, 0.1);
            color: #ff9800;
            border-color: rgba(255, 152, 0, 0.3);
        }

        .btn-warning:hover {
            background-color: rgba(255, 152, 0, 0.2);
            border-color: rgba(255, 152, 0, 0.5);
        }

        .btn-info {
            background-color: rgba(75, 171, 247, 0.1);
            color: #4dabf7;
            border-color: rgba(75, 171, 247, 0.3);
        }

        .btn-info:hover {
            background-color: rgba(75, 171, 247, 0.2);
            border-color: rgba(75, 171, 247, 0.5);
        }

        .input-group {
            display: flex;
            gap: 10px;
        }

        .input-group .custom-input {
            flex: 1;
        }

        .input-group .custom-button {
            flex-shrink: 0;
        }

        /* Alert notifications */
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease-out;
            position: relative;
            z-index: 100;
        }

        .alert-error {
            background-color: rgba(220, 53, 69, 0.1);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
            margin-bottom: 20px;
        }

        /* Fixed alert that appears at the top */
        .fixed-alert {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1050;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 300px;
            max-width: 80%;
        }

        .fixed-alert.show {
            animation: fadeIn 0.3s ease-out forwards;
        }

        .alert-warning {
            background-color: rgba(255, 152, 0, 0.1);
            color: #ff9800;
            border: 1px solid rgba(255, 152, 0, 0.3);
        }

        .alert-icon {
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .alert-close {
            margin-left: auto;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s;
        }

        .alert-close:hover {
            opacity: 1;
        }

        /* Section styling */
        .section {
            display: none;
            animation: fadeIn 0.4s ease-out;
        }

        .section.active {
            display: block;
        }

        /* Database backup styling */
        .backup-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .backup-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-radius: 8px;
            background-color: rgba(17, 18, 22, 0.4);
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .backup-item:hover {
            background-color: rgba(17, 18, 22, 0.6);
        }

        .backup-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .backup-icon {
            color: #4dabf7;
            font-size: 18px;
        }

        .backup-name {
            font-weight: 500;
            color: #f7f7f8;
        }

        .backup-date {
            color: #94a3b8;
            font-size: 13px;
        }

        .backup-actions {
            display: flex;
            gap: 8px;
        }

        .backup-button {
            background-color: transparent;
            border: none;
            color: #94a3b8;
            padding: 6px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .backup-button:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #f7f7f8;
        }

        /* File upload styling */
        .file-upload {
            position: relative;
            display: block;
        }

        .file-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            height: 100%;
            width: 100%;
            cursor: pointer;
        }

        .file-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 16px;
            border: 1px dashed rgba(51, 57, 66, 0.7);
            border-radius: 8px;
            background-color: rgba(17, 18, 22, 0.4);
            color: #94a3b8;
            font-weight: 500;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-label:hover {
            border-color: #4dabf7;
            background-color: rgba(17, 18, 22, 0.6);
            color: #f7f7f8;
        }

        .file-icon {
            margin-right: 10px;
            font-size: 18px;
        }

        .file-name {
            margin-left: 10px;
            font-weight: normal;
            color: #4dabf7;
        }

        /* Animation keyframes */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Media queries */
        @media (max-width: 768px) {
            .settings-container {
                padding: 0 15px;
                margin: 20px auto;
            }

            .card-header, .card-body {
                padding: 15px;
            }

            .settings-tab {
                padding: 10px 15px;
                font-size: 14px;
            }

            .input-group {
                flex-direction: column;
                gap: 10px;
            }
        }

        /* Add these new vault-specific styles to your existing CSS */
        
        
        .vault-animation {
            display: flex;
            justify-content: center;
            margin: 20px 0 30px;
        }
        
        .vault-door {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: all 0.6s cubic-bezier(0.68, -0.6, 0.32, 1.6);
        }
        
        .vault-door:before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 8px solid #333;
            box-sizing: border-box;
        }
        
        .vault-door:after {
            content: '';
            position: absolute;
            width: 90%;
            height: 90%;
            border-radius: 50%;
            border: 6px dashed rgba(255, 193, 7, 0.3);
            box-sizing: border-box;
        }
        
        .vault-door.closed {
            background-color: #333;
            box-shadow: 
                0 0 0 6px rgba(51, 51, 51, 0.6),
                0 0 0 12px rgba(51, 51, 51, 0.3),
                0 0 30px rgba(0, 0, 0, 0.5);
        }
        
        .vault-door.open {
            background-color: rgba(40, 167, 69, 0.2);
            box-shadow: 
                0 0 0 6px rgba(40, 167, 69, 0.2),
                0 0 0 12px rgba(40, 167, 69, 0.1),
                0 0 30px rgba(40, 167, 69, 0.2);
        }
        
        .vault-icon {
            font-size: 38px;
            z-index: 10;
        }
        
        .vault-door.open .vault-icon {
            animation: unlockPulse 2s infinite;
        }
        
        .vault-message {
            text-align: center;
            color: #94a3b8;
            font-size: 16px;
            margin-bottom: 25px;
        }
        
        .vault-password-group {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 16px;
            cursor: pointer;
            padding: 5px;
            z-index: 5;
        }
        
        .toggle-password:hover {
            color: white;
        }
        
        .vault-button {
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
        }
        
        .vault-controls {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .vault-controls form {
            flex: 1;
        }
        
        .vault-controls button {
            width: 100%;
        }
        
        .vault-timer {
            font-size: 14px;
            opacity: 0.8;
        }
        
        #vault-countdown {
            font-weight: bold;
        }
        
        .vault-unlock-icon,
        .vault-lock-icon,
        .vault-enter-icon {
            font-size: 18px;
        }
        
        @keyframes unlockPulse {
            0% { opacity: 0.7; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.1); }
            100% { opacity: 0.7; transform: scale(1); }
        }
        
        /* Override for tab styling when vault is active */
        .settings-tab .tab-icon {
            transition: all 0.3s ease;
        }
        
        /* Make icons slightly larger for better visibility */
        .tab-icon {
            font-size: 18px;
            margin-right: 5px;
        }

        /* Fix the alert styling */
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        /* Create a separate class for the fixed success alert */
        .fixed-alert-success {
            position: fixed;
            top: 100px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
        }
        
        .fixed-alert-success.show {
            animation: fadeIn 0.3s ease-out forwards;
            pointer-events: auto;
        }
        
        /* Fix the vault door open animation to prevent icon rotation */
        .vault-door.open {
            background-color: rgba(40, 167, 69, 0.2);
            box-shadow: 
                0 0 0 6px rgba(40, 167, 69, 0.2),
                0 0 0 12px rgba(40, 167, 69, 0.1),
                0 0 30px rgba(40, 167, 69, 0.2);
        }
        
        .vault-door.open:before {
            border-color: #28a745;
        }
        
        /* Use a separate animation for the door rather than transform */
        .vault-door.open {
            animation: doorOpen 0.6s cubic-bezier(0.68, -0.6, 0.32, 1.6) forwards;
        }
        
        @keyframes doorOpen {
            0% {
                background-color: #333;
                box-shadow: 
                    0 0 0 6px rgba(51, 51, 51, 0.6),
                    0 0 0 12px rgba(51, 51, 51, 0.3),
                    0 0 30px rgba(0, 0, 0, 0.5);
            }
            100% {
                background-color: rgba(40, 167, 69, 0.2);
                box-shadow: 
                    0 0 0 6px rgba(40, 167, 69, 0.2),
                    0 0 0 12px rgba(40, 167, 69, 0.1),
                    0 0 30px rgba(40, 167, 69, 0.2);
            }
        }
        
        /* Create a separate shine effect instead of rotating the door */
        .vault-door.open:after {
            animation: shineEffect 2s infinite;
            border-color: rgba(40, 167, 69, 0.3);
        }
        
        @keyframes shineEffect {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <div class="main-content fade-in">
        <div class="settings-container">
            <div class="settings-header">
                <div>
                    <h2>Settings</h2>
                    <p>Configure your system preferences and account settings</p>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error" id="alert-error">
                <div class="alert-icon">⚠️</div>
                <div><?php echo $error; ?></div>
                <div class="alert-close" onclick="this.parentElement.style.display='none'">×</div>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success" id="alert-success">
                <div class="alert-icon">✓</div>
                <div><?php echo $success; ?></div>
                <div class="alert-close" onclick="this.parentElement.style.display='none'">×</div>
            </div>
            <?php endif; ?>

            <!-- Tab Navigation -->
            <div class="settings-tabs">
                <div class="settings-tab active" onclick="showSection('store-settings')">
                    <span class="tab-icon">🏪</span> Store Settings
                </div>
                <div class="settings-tab" onclick="showSection('user-settings')">
                    <span class="tab-icon">👤</span> User Settings
                </div>
                <div class="settings-tab" onclick="showSection('database-vault')">
                    <span class="tab-icon"><?php echo $databaseVaultUnlocked ? '🔓' : '🔒'; ?></span> Admin Panel
                </div>
            </div>

            <!-- Store Settings Section -->
            <div id="store-settings" class="section active">
                <div class="settings-card">
                    <div class="card-header">
                        <h3>Inventory Alert Settings</h3>
                        <p>Configure when you'll be notified about low stock levels</p>
                    </div>
                    <br>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="reorder_level" class="form-label">Low Stock Alert Threshold</label>
                                <input type="number" name="reorder_level" id="reorder_level" class="custom-input" placeholder="Enter number of items" value="<?php echo htmlspecialchars($reorderLevel); ?>" required>
                                <span class="form-hint">Products with stock equal to or below this number will be flagged as low stock</span>
                            </div>
                            <button type="submit" class="custom-button btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- User Settings Section -->
            <div id="user-settings" class="section">
                <div class="settings-card">
                    <div class="card-header">
                        <h3>Password Management</h3>
                        <p>Update your login credentials</p>
                    </div>
                    <br>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="change_password" value="1">
                            <div class="form-group">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" name="current_password" id="current_password" class="custom-input" placeholder="Enter your current password" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" name="new_password" id="new_password" class="custom-input" placeholder="Enter your new password" required>
                                <span class="form-hint">Use a strong password with at least 8 characters, including numbers and symbols</span>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="custom-input" placeholder="Confirm your new password" required>
                            </div>
                            <button type="submit" class="custom-button btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Database Vault Section -->
            <div id="database-vault" class="section">
                <?php if (!$databaseVaultUnlocked): ?>
                <!-- Locked Vault View -->
                <div class="settings-card vault-card">
                    <div class="card-header">
                        <h3>Admin Panel</h3>
                        <p>Secure access to Admin features</p>
                    </div>
                    <br>
                    <div class="card-body">
                        <div class="vault-animation">
                            <div class="vault-door closed">
                                <div class="vault-icon">🔒</div>
                            </div>
                        </div>
                        <p class="vault-message">Access to Admin features is restricted.</p>
                        
                        <form method="POST" action="" id="vaultForm">
                            <div class="form-group">
                                <label for="vault_password" class="form-label">Admin Panel Password</label>
                                <div class="input-group vault-password-group">
                                    <input type="password" name="vault_password" id="vault_password" class="custom-input" 
                                           placeholder="Enter vault password" required>
                                </div>
                                <span class="form-hint">Enter the Admin secret password to access database management features</span>
                            </div>
                            <input type="hidden" name="unlock_vault" value="1">
                            <button type="submit" class="custom-button btn-primary vault-button">
                                <span class="vault-unlock-icon">🔑</span> Unlock Vault Access
                            </button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <!-- Unlocked Vault View -->
                <div class="settings-card vault-card">
                    <div class="card-header">
                        <h3>Admin Panel</h3>
                        <p>You have secure access to Admin features</p>
                    </div>
                    <div class="card-body">
                        <div class="vault-animation">
                            <div class="vault-door open">
                                <div class="vault-icon">🔓</div>
                            </div>
                        </div>
                        
                        <div class="alert alert-success">
                            <div class="alert-icon">✓</div>
                            <div>
                                <strong>Admin Panel unlocked.</strong> 
                                You now have access to sensitive Admin features.
                                <br>
                                <span class="vault-timer">Session will automatically expire in <span id="vault-countdown">30:00</span></span>
                            </div>
                        </div>
                        
                        <div class="vault-controls">
                            <form method="POST" action="">
                                <input type="hidden" name="lock_vault" value="1">
                                <button type="submit" class="custom-button btn-warning vault-button">
                                    <span class="vault-lock-icon">🔒</span> Lock Vault Access
                                </button>
                            </form>
                            
                            <button type="button" class="custom-button btn-primary" onclick="showSection('database-settings')">
                                <span class="vault-enter-icon">⚙️</span> Access Database Settings
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Database Settings Section (Admin Only) -->
            <?php if ($canResetDatabase): ?>
            <div id="database-settings" class="section">
                <!-- Database Import Card -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3>Import Database</h3>
                        <p>Replace the current database with data from a SQL file</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data" id="importForm">
                            <div class="form-group">
                                <label class="form-label">Select SQL File</label>
                                <div class="file-upload">
                                    <input type="file" name="import_file" id="import_file" class="file-input" accept=".sql" required>
                                    <div class="file-label">
                                        <span class="file-icon">📁</span>
                                        <span id="file-placeholder">Choose a SQL file</span>
                                        <span class="file-name" id="file-name"></span>
                                    </div>
                                </div>
                                <span class="form-hint">Only .sql files are supported</span>
                            </div>
                            <div class="alert alert-warning">
                                <div class="alert-icon">⚠️</div>
                                <div>Warning: Importing a database will replace all existing data. This action cannot be undone.</div>
                            </div>
                            <button type="button" class="custom-button btn-info" onclick="confirmDatabaseImport(document.getElementById('importForm'))">Import Database</button>
                        </form>
                    </div>
                </div>

                <!-- Database Backups Card -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3>Database Backups</h3>
                        <p>View and download existing database backups (auto-deleted after 30 days)</p>
                    </div>
                    <div class="card-body">
                        <?php
                        $backups = glob('backups/*.sql');
                        if (!empty($backups)):
                        ?>
                            <ul class="backup-list">
                                <?php foreach ($backups as $backup):
                                    $filename = basename($backup);
                                    $fileDate = date("F d, Y H:i:s", filemtime($backup));
                                ?>
                                    <li class="backup-item">
                                        <div class="backup-info">
                                            <div class="backup-icon">📊</div>
                                            <div>
                                                <div class="backup-name"><?php echo $filename; ?></div>
                                                <div class="backup-date"><?php echo $fileDate; ?></div>
                                            </div>
                                        </div>
                                        <div class="backup-actions">
                                            <a href="<?php echo $backup; ?>" download class="backup-button" title="Download">⬇️</a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No backups available at this time.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- New Account Registration Card -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3>Register New Account</h3>
                        <p>Create a new user account for the system</p>
                    </div>
                    <br>
                    <div class="card-body">
                        <form method="POST" action="" id="adminRegisterForm">
                            <div class="form-group">
                                <label for="admin_username" class="form-label">Username</label>
                                <input type="text" name="admin_username" id="admin_username" class="custom-input" 
                                       placeholder="Enter username" required>
                                <span class="form-hint">Choose a unique username for the account</span>
                            </div>
                            <div class="form-group">
                                <label for="admin_password" class="form-label">Password</label>
                                <input type="password" name="admin_password" id="admin_password" class="custom-input" 
                                       placeholder="Enter password" required minlength="8">
                                <span class="form-hint">Password must be at least 8 characters long</span>
                            </div>
                            <div class="form-group">
                                <label for="admin_confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" name="admin_confirm_password" id="admin_confirm_password" class="custom-input" 
                                       placeholder="Confirm password" required minlength="8">
                            </div>
                            <input type="hidden" name="register_admin" value="1">
                            <div class="alert alert-info">
                                <div class="alert-icon">ℹ️</div>
                                <div>This account will have full access to the system</div>
                            </div>
                            <button type="submit" class="custom-button btn-primary">Register New Account</button>
                        </form>
                    </div>
                </div>

                <!-- Database Reset Card -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3>Reset Database</h3>
                        <p>Clear all operational data while preserving user accounts</p>
                    </div>
                    <br>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <div class="alert-icon">⚠️</div>
                            <div>
                                <strong>DANGER:</strong> This will permanently delete all products, sales, inventory, and other operational data. User accounts will be preserved.
                                <br>A backup file will be automatically created before resetting.
                            </div>
                        </div>
                        <form id="resetDatabaseForm" method="POST" action="" style="display: none;">
                            <input type="hidden" name="reset_database" value="confirm">
                        </form>
                        <button type="button" class="custom-button btn-danger" onclick="confirmDatabaseReset()">Reset Database</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // File input display handling
            const fileInput = document.getElementById('import_file');
            const filePlaceholder = document.getElementById('file-placeholder');
            const fileName = document.getElementById('file-name');

            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    if (fileInput.files.length > 0) {
                        filePlaceholder.textContent = 'Selected file:';
                        fileName.textContent = fileInput.files[0].name;
                    } else {
                        filePlaceholder.textContent = 'Choose a SQL file';
                        fileName.textContent = '';
                    }
                });
            }

            // Success alert handling
            const successAlert = document.getElementById('alert-success');
            if (successAlert && successAlert.textContent.trim() !== '') {
                // Add fixed position class
                successAlert.classList.add('fixed-alert');
                
                setTimeout(function() {
                    successAlert.classList.remove('show');
                }, 5000); // Hide after 5 seconds
            }
            
            // For error alerts, also add fixed position if needed
            const errorAlert = document.querySelector('.alert-error');
            if (errorAlert) {
                errorAlert.classList.add('fixed-alert');
                errorAlert.classList.add('show');
            }
        });
        
        // Rest of the JavaScript code remains the same

        // Section tab navigation
        function showSection(sectionId) {
            // Hide all sections and deactivate all tabs
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            document.querySelectorAll('.settings-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activate selected section and its tab
            document.getElementById(sectionId).classList.add('active');
            document.querySelector(`.settings-tab[onclick="showSection('${sectionId}')"]`).classList.add('active');
        }

        function confirmDatabaseReset() {
            // First confirmation
            if (confirm('WARNING: You are about to delete ALL data from the database.\n\nA backup will be created automatically, but this action will remove all products, sales, and inventory data.\n\nAre you sure you want to continue?')) {
                // Second confirmation with typing requirement for extra safety
                const confirmText = prompt('To confirm, please type "RESET" in all capitals:');
                if (confirmText === 'RESET') {
                    // Show loading message
                    showLoadingOverlay('Creating database backup and resetting...');
                    
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
            // Check if a file was selected
            const fileInput = form.querySelector('input[type="file"]');
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Please select a SQL file to import.');
                return;
            }

            // First confirmation
            if (confirm('WARNING: You are about to import a database file. This may overwrite existing data.\n\nAre you sure you want to continue?')) {
                // Second confirmation with typing requirement for extra safety
                const confirmText = prompt('To confirm, please type "IMPORT" in all capitals:');
                if (confirmText === 'IMPORT') {
                    // Show loading message
                    showLoadingOverlay('Importing database...');
                    
                    // Submit the form
                    setTimeout(() => {
                        form.submit();
                    }, 100);
                } else {
                    alert('Database import cancelled. The confirmation text did not match "IMPORT".');
                }
            }
        }

        function showLoadingOverlay(message) {
            const loadingModal = document.createElement('div');
            loadingModal.style.position = 'fixed';
            loadingModal.style.top = '0';
            loadingModal.style.left = '0';
            loadingModal.style.width = '100%';
            loadingModal.style.height = '100%';
            loadingModal.style.backgroundColor = 'rgba(0,0,0,0.8)';
            loadingModal.style.zIndex = '9999';
            loadingModal.style.display = 'flex';
            loadingModal.style.flexDirection = 'column';
            loadingModal.style.alignItems = 'center';
            loadingModal.style.justifyContent = 'center';
            loadingModal.style.color = 'white';
            
            // Add loading spinner
            loadingModal.innerHTML = `
                <div style="width: 50px; height: 50px; border: 5px solid rgba(255,255,255,0.3); 
                      border-radius: 50%; border-top-color: white; 
                      animation: spin 1s ease-in-out infinite;"></div>
                <h3 style="margin-top: 20px;">${message}</h3>
                <p style="margin-top: 10px;">Please do not close this window.</p>
            `;
            
            // Add the spinner animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes spin {
                    to { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(loadingModal);
        }
    </script>
</body>

</html>