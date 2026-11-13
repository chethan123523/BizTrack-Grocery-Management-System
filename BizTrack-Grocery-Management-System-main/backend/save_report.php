<?php

require "db.php";

$data = json_decode(file_get_contents("php://input"),true);

$start = $data["startDate"];
$end = $data["endDate"];
$purchase = $data["totalPurchase"];
$sales = $data["totalSales"];
$gross = $data["grossProfit"];
$net = $data["netProfit"];
$margin = $data["profitMargin"];

$stmt = $conn->prepare("
INSERT INTO profit_reports 
(start_date,end_date,total_purchase,total_sales,gross_profit,net_profit,profit_margin)
VALUES (?,?,?,?,?,?,?)
");

$stmt->bind_param("ssddddd",$start,$end,$purchase,$sales,$gross,$net,$margin);
$stmt->execute();

echo json_encode(["status"=>"success"]);