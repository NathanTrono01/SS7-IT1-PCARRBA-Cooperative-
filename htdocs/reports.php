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
    <title>Reports</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #181818;
            color: white;
            text-align: center;
        }

        .tabs {
            display: flex;
            justify-content: flex-start;
            border-bottom: 2px solid #555;
        }

        .tab {
            padding: 10px 50px;
            cursor: pointer;
            color: #aaa;
        }

        .tab.active {
            color: white;
            border-bottom: 2px solid white;
        }

        .content {
            display: none;
            padding: 20px;
        }

        .content.active {
            display: block;
        }
    </style>
</head>

<?php include 'navbar.php'; ?>
<script src="js/bootstrap.bundle.min.js"></script>

<body>
    <div class="main-content fade-in">
        <div class="container">
            <h1>Reports</h1>

            <!-- Navigation Tabs -->
            <div class="tabs">
                <div class="tab active" onclick="showTab('sales')">Sales Summary</div>
                <div class="tab" onclick="showTab('revenue')">Revenue</div>
                <div class="tab" onclick="showTab('download')">Download</div>
            </div>

            <!-- Tab Contents -->
            <div id="sales" class="content active">
                <h2>Sales Data</h2>
                <p>Here is the sales analytics data...</p>
            </div>

            <div id="revenue" class="content">
                <h2>Credits</h2>
                <p>Here is the credits information...</p>
            </div>

            <div id="download" class="content">
                <h2>Revenue</h2>
                <p>Here is the revenue information...</p>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabId) {
            // Hide all content
            document.querySelectorAll('.content').forEach(c => c.classList.remove('active'));
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            // Show selected content
            document.getElementById(tabId).classList.add('active');
            // Highlight selected tab
            event.target.classList.add('active');
        }
    </script>

</body>

</html>