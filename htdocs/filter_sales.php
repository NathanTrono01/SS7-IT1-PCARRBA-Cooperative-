<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

$startDate = $_GET['startDate'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['endDate'] ?? date('Y-m-d');

// Fetch sales data for the selected date range
$sales_data_sql = "SELECT DATE(dateSold) as sale_date, SUM(totalPrice) as total_sales 
                   FROM sales 
                   WHERE DATE(dateSold) BETWEEN ? AND ? 
                   GROUP BY DATE(dateSold) 
                   ORDER BY DATE(dateSold)";
$stmt = $conn->prepare($sales_data_sql);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$sales_dates = [];
$sales_totals = [];

while ($row = $result->fetch_assoc()) {
    $sales_dates[] = $row['sale_date'];
    $sales_totals[] = $row['total_sales'] ?? 0; // Add 0 if no sales
}

// Return data as JSON
header('Content-Type: application/json');
echo json_encode([
    'dates' => $sales_dates,
    'totals' => $sales_totals
]);
?>