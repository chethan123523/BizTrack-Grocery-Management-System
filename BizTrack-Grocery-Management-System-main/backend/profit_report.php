<?php
header("Content-Type: application/json");
require 'db.php'; // Make sure your DB connection file is correct

// Read JSON input
$data = json_decode(file_get_contents('php://input'), true);

$start = $data['startDate'] ?? '';
$end = $data['endDate'] ?? '';

if (!$start || !$end) {
    echo json_encode(["error" => "Start date or End date missing"]);
    exit;
}

// Optional: Previous period dates
$prevStart = date('Y-m-d', strtotime("$start -1 month"));
$prevEnd = date('Y-m-d', strtotime("$end -1 month"));

// ===================== TOTAL SALES =====================
$salesQuery = $conn->query("
    SELECT SUM(total_amount) AS totalSales 
    FROM bills 
    WHERE DATE(bill_date) BETWEEN '$start' AND '$end'
");
$salesRow = $salesQuery->fetch_assoc();
$totalSales = $salesRow['totalSales'] ?? 0;

// Previous period sales
$prevSalesQuery = $conn->query("
    SELECT SUM(total_amount) AS totalSales 
    FROM bills 
    WHERE DATE(bill_date) BETWEEN '$prevStart' AND '$prevEnd'
");
$prevSalesRow = $prevSalesQuery->fetch_assoc();
$prevTotalSales = $prevSalesRow['totalSales'] ?? 0;

// ================== TOTAL PURCHASE / COGS ==================
$purchaseQuery = $conn->query("
    SELECT SUM(quantity * purchase_cost) AS totalPurchase
    FROM bill_items
    WHERE DATE(created_at) BETWEEN '$start' AND '$end'
");
$purchaseRow = $purchaseQuery->fetch_assoc();
$totalPurchase = $purchaseRow['totalPurchase'] ?? 0;

// Previous period purchase
$prevPurchaseQuery = $conn->query("
    SELECT SUM(quantity * purchase_cost) AS totalPurchase
    FROM bill_items
    WHERE DATE(created_at) BETWEEN '$prevStart' AND '$prevEnd'
");
$prevPurchaseRow = $prevPurchaseQuery->fetch_assoc();
$prevTotalPurchase = $prevPurchaseRow['totalPurchase'] ?? 0;

// ===================== PROFIT CALCULATIONS =====================
$grossProfit = $totalSales - $totalPurchase;
$prevGrossProfit = $prevTotalSales - $prevTotalPurchase;

$netProfit = $grossProfit; // Add other expenses if needed
$prevNetProfit = $prevGrossProfit;
// Get previous report from DB
$prevQuery = $conn->query("
    SELECT net_profit 
    FROM profit_reports 
    ORDER BY id DESC 
    LIMIT 1
");

$prevRow = $prevQuery->fetch_assoc();

$prevNetProfit = $prevRow['net_profit'] ?? 0;

$profitMargin = $totalSales > 0 ? ($netProfit / $totalSales) * 100 : 0;

// Profit change
$change = $prevNetProfit != 0 
    ? (($netProfit - $prevNetProfit) / $prevNetProfit) * 100 
    : 0;

// ===================== RESPONSE =====================
$response = [
    "reportId" => rand(1000,9999),
    "reportTime" => date("Y-m-d H:i:s"),
    "totalPurchase" => round($totalPurchase,2),
    "totalSales" => round($totalSales,2),
    "grossProfit" => round($grossProfit,2),
    "netProfit" => round($netProfit,2),
    "profitMargin" => round($profitMargin,2),
    "prevProfit" => round($prevNetProfit,2),
    "currentProfit" => round($netProfit,2),
    "change" => round($change,2)
];

echo json_encode($response);
$conn->close();
?>