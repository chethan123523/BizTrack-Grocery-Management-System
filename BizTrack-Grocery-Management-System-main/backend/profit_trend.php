<?php
require "db.php";

$result = $conn->query("
    SELECT end_date as report_date, net_profit
    FROM profit_reports
    ORDER BY end_date ASC
");

$data = [];
while($row = $result->fetch_assoc()){
    $data[] = $row;
}

// Handle empty table with placeholder
if(empty($data)){
    $data[] = [
        "report_date" => "No Data",
        "net_profit" => 0
    ];
}

echo json_encode($data);
?>