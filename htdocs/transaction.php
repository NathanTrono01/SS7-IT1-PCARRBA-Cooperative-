<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
include 'db.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Transaction Selection</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        .flex-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 10px;
        }

        .search-container {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            position: relative;
            width: 100%;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-wrapper {
            display: flex;
            align-items: center;
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        .search-wrapper .search-icon {
            position: absolute;
            left: 10px;
            width: 16px;
            height: 16px;
        }

        .search-wrapper .clear-icon {
            position: absolute;
            right: 10px;
            width: 16px;
            height: 16px;
            cursor: pointer;
            display: none;
        }

        .search-wrapper input {
            padding: 8px 8px 8px 30px;
            /* Adjust padding to make space for the search icon */
            width: 100%;
            border-radius: 5px;
            border: 1px solid #333942;
            background-color: rgba(33, 34, 39, 255);
            color: #f7f7f8;
        }

        .search-wrapper input::placeholder {
            color: rgba(247, 247, 248, 0.64);
            font-weight: 200;
        }

        table {
            font-family: Arial, Helvetica, sans-serif;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #1f1f1f;
        }

        table th {
            padding: 7px;
            background-color: #0c0c0f;
            color: rgba(247, 247, 248, 0.9);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 1rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.83);
            border-top: 2px solid #333942;
            border-bottom: 2px solid #333942;
            width: 20%; /* Set each column to take up 20% of the table width */
        }

        table th:first-child {
            border-left: 2px solid #333942;
            border-top: 2px solid #333942;
            border-bottom: 2px solid #333942;
        }

        table th:last-child {
            border-right: 2px solid #333942;
            border-top: 2px solid #333942;
            border-bottom: 2px solid #333942;
            width: 150px; /* Fixed width for the Actions column */
        }

        table td {
            padding: 5px 10px; /* Add padding to the sides of the td elements */
            font-size: 1rem;
            color: #eee;
            margin: 0 5px;
            width: 20%; /* Set each column to take up 20% of the table width */
        }

        table td:last-child {
            width: 150px; /* Fixed width for the Actions column */
        }

        table tr {
            background-color: transparent;
        }

        table tr:hover {
            background-color: rgba(187, 194, 209, 0.17);
            transition: all 0.3s ease;
        }

        table tr td:first-child {
            border-top-left-radius: 7px;
            border-bottom-left-radius: 7px;
        }

        table tr td:last-child {
            border-top-right-radius: 7px;
            border-bottom-right-radius: 7px;
        }

        .button a {
            background: transparent;
            display: inline-block;
            padding: 8px 12px;
            background-color: rgb(255, 255, 255);
            color: #000;
            text-decoration: none;
            border-radius: 7px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.26);
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        .button a:hover {
            background-color: rgba(255, 255, 255, 0.94);
            color: #000;
            border-radius: 7px;
        }

        .btn-action {
            text-decoration: none;
            padding: 5px 10px;
            font-size: 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 75px;
            text-align: center;
            box-sizing: border-box;
        }

        .btn-action img {
            display: none;
        }

        .btn-action span {
            display: inline;
        }

        .btn-edit {
            background-color: #335fff !important;
            color: white !important;
        }

        .btn-delete {
            border: 1px solid #ff3d3d !important;
            background-color: transparent !important;
            color: #ff3d3d !important;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .button-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 50px; /* Increased margin to bring it further down */
        }

        .button-container a {
            margin: 5px;
        }

        @media (max-width: 1024px) {
            .table-wrapper {
                padding: 10px;
                overflow-x: auto;
            }

            table {
                width: 100%;
                table-layout: auto;
            }

            table th,
            table td {
                font-size: 0.95rem;
                padding: 6px;
            }

            .btn-action {
                padding: 6px 10px;
                font-size: 0.85rem;
            }

            .btn-action span {
                display: none;
            }

            .btn-action img {
                display: inline;
                width: 20px;
                height: 20px;
            }
        }

        @media (max-width: 768px) {
            .table-wrapper {
                padding: 10px;
                overflow-x: auto;
            }

            table {
                width: 100%;
                table-layout: auto;
            }

            table th,
            table td {
                font-size: 0.85rem;
                padding: 6px;
            }

            .btn-action {
                padding: 6px 10px;
                font-size: 0.75rem;
            }

            .btn-action span {
                display: none;
            }

            .btn-action img {
                display: inline;
                width: 15px;
                height: 15px;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 1.5em;
            }

            h2 {
                font-size: 0.8em;
            }

            .table-wrapper {
                margin: 0;
                width: 100%;
            }

            table {
                font-size: 0.8rem;
                display: block;
                width: 100%;
                overflow-x: auto;
                white-space: nowrap;
            }

            table th,
            table td {
                width: 100%;
                font-size: 0.8rem;
                padding: 5px;
            }

            .btn-action {
                padding: 4px 10px;
                font-size: 0.7rem;
                min-width: 30px;
            }

            .btn-action span {
                display: none;
            }

            .btn-action img {
                display: inline;
                width: 15px;
                height: 15px;
            }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <!-- Main content -->
    <div class="main-content">
        <!-- Centered buttons with text -->
        <div class="button-container">
            <h2>Select Transaction Type</h2>
            <a href="addSale.php" class="btn btn-primary">Record a Sale</a>
            <a href="addCredit.php" class="btn btn-primary">Record a Credit</a>
        </div>
    </div>
</body>

</html>