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
    <title>Sale Type Transaction Selection</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/layer1.css">
    <style>
        /* Enhanced container layout */
        .transaction-container {
            max-width: 100%;
            margin: 0px auto;
            padding: 30px;
        }

        .transaction-header {
            text-align: center;
            margin-bottom: 30px;
            color: whitesmoke;
        }

        .transaction-header h2 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .transaction-header p {
            font-size: 16px;
            color: #94a3b8;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Card options grid */
        .transaction-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .transaction-card {
            background-color: rgba(39, 41, 48, 0.8);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(51, 57, 66, 0.5);
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .transaction-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            border-color: rgba(51, 95, 255, 0.5);
        }

        .transaction-card:active {
            transform: translateY(0px);
        }

        .card-header {
            padding: 20px;
            background-color: rgba(33, 34, 39, 0.9);
            border-bottom: 1px solid rgba(51, 57, 66, 0.5);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(51, 95, 255, 0.15);
            border-radius: 12px;
            font-size: 24px;
        }

        .card-title {
            color: whitesmoke;
            font-size: 20px;
            font-weight: 500;
        }

        .card-body {
            padding: 20px;
            flex: 1;
        }

        .card-description {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .card-features {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
        }

        .card-features li {
            color: #f7f7f8;
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            font-size: 14px;
        }

        .card-features li::before {
            content: "✓";
            color: #4CAF50;
            font-weight: bold;
        }

        .card-action {
            display: flex;
            justify-content: center;
            padding: 15px 20px;
            border-top: 1px solid rgba(51, 57, 66, 0.5);
            background-color: rgba(33, 34, 39, 0.6);
            margin-top: auto;
        }

        .btn-card {
            padding: 12px 25px;
            background-color: rgb(42, 56, 255);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
        }

        .btn-card:hover {
            background-color: rgb(61, 74, 255);
            box-shadow: 0 4px 12px rgba(51, 95, 255, 0.3);
        }

        .btn-card.alt {
            background-color: rgba(51, 95, 255, 0.15);
            color: rgb(51, 95, 255);
            border: 1px solid rgba(51, 95, 255, 0.3);
        }

        .btn-card.alt:hover {
            background-color: rgba(51, 95, 255, 0.25);
            border-color: rgb(51, 95, 255);
        }

        /* Highlight animation */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(51, 95, 255, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(51, 95, 255, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(51, 95, 255, 0);
            }
        }

        .card-highlight {
            animation: pulse 2s infinite;
        }

        /* Back button styling */
        .btn-back-wrapper {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #f7f7f8;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .btn-back-wrapper:hover {
            transform: translateX(-3px);
        }

        .btn-back-wrapper span {
            margin-left: 10px;
            font-size: 16px;
        }

        .btn-back-wrapper img {
            width: 25px;
            height: 25px;
            transition: all 0.3s ease;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .transaction-container {
                padding: 20px;
                margin: 20px auto;
            }

            .transaction-header h2 {
                font-size: 24px;
            }

            .transaction-options {
                gap: 15px;
            }

            .card-header {
                padding: 15px;
            }

            .card-icon {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }

            .card-title {
                font-size: 18px;
            }

            .card-body {
                padding: 15px;
            }

            .btn-card {
                padding: 10px 20px;
                font-size: 14px;
            }
        }

        /* Animation utilities */
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

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

        .hover-lift {
            transition: transform 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-3px);
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <!-- Main content -->
    <div class="main-content fade-in">
        <div class="transaction-container">
            <a href="dashboard.php" class="btn-back-wrapper" id="back-button">
                <img src="images/back.png" alt="Back" class="btn-back" id="back-image">
                <b><span>Back to Dashboard</span></b>
            </a>

            <div class="transaction-header">
                <h2>Select Transaction Type</h2>
                <p>Choose the type of transaction you want to record</p>
            </div>

            <div class="transaction-options">
                <div class="transaction-card" id="sale-card" onclick="navigateTo('addSale.php')">
                    <div class="card-header">
                        <div class="card-icon">💵</div>
                        <h3 class="card-title">Cash Sale</h3>
                    </div>
                    <div class="card-body">
                        <p class="card-description">Record a direct cash transaction with immediate payment.</p>
                        <ul class="card-features">
                            <li>Immediate payment collection</li>
                            <li>Automatic inventory update</li>
                            <li>Change calculation</li>
                            <li>Complete sales receipt</li>
                        </ul>
                    </div>
                    <div class="card-action">
                        <a href="addSale.php" class="btn-card">Record Cash Sale</a>
                    </div>
                </div>

                <div class="transaction-card" id="credit-card" onclick="navigateTo('addCredit.php')">
                    <div class="card-header">
                        <div class="card-icon">📝</div>
                        <h3 class="card-title">Credit Sale</h3>
                    </div>
                    <div class="card-body">
                        <p class="card-description">Record a sale with delayed payment for trusted customers.</p>
                        <ul class="card-features">
                            <li>Customer information tracking</li>
                            <li>Payment schedule options</li>
                            <li>Balance tracking</li>
                            <li>Payment reminders</li>
                        </ul>
                    </div>
                    <div class="card-action">
                        <a href="addCredit.php" class="btn-card alt">Record Credit Sale</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Button hover effect for back button
            const backImage = document.getElementById('back-image');
            
            backImage.addEventListener('mouseover', function() {
                this.src = 'images/back-hover.png';
            });

            backImage.addEventListener('mouseout', function() {
                this.src = 'images/back.png';
            });
            
            // Add subtle interaction effects
            const cards = document.querySelectorAll('.transaction-card');
            
            cards.forEach(card => {
                // Add subtle hover lift effect
                card.addEventListener('mouseenter', function() {
                    this.classList.add('hover-active');
                });
                
                card.addEventListener('mouseleave', function() {
                    this.classList.remove('hover-active');
                });
                
                // Add focus effect for keyboard navigation
                card.addEventListener('focus', function() {
                    this.classList.add('hover-active');
                });
                
                card.addEventListener('blur', function() {
                    this.classList.remove('hover-active');
                });
                
                // Add click effect
                card.addEventListener('mousedown', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                
                card.addEventListener('mouseup', function() {
                    this.style.transform = 'translateY(-5px)';
                });
            });
            
            // Check URL parameters to highlight appropriate card
            const urlParams = new URLSearchParams(window.location.search);
            const highlight = urlParams.get('highlight');
            
            if (highlight === 'credit') {
                document.getElementById('credit-card').classList.add('card-highlight');
            } else if (highlight === 'sale') {
                document.getElementById('sale-card').classList.add('card-highlight');
            }
        });
        
        function navigateTo(url) {
            window.location.href = url;
        }
    </script>
</body>

</html>