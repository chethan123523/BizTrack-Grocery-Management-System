<?php
require "db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$search = $_GET['search'] ?? '';
$from   = $_GET['from'] ?? '';
$to     = $_GET['to'] ?? '';

$sql = "SELECT * FROM purchases WHERE 1=1";

//  Search filter
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (product_name LIKE '%$search%' OR purchase_id LIKE '%$search%')";
}

//  Date filter
if (!empty($from) && !empty($to)) {
    $sql .= " AND DATE(purchase_date) BETWEEN '$from' AND '$to'";
}

//  Order
$sql .= " ORDER BY purchase_date DESC";

$result = $conn->query($sql);

$data = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "id" => $row["id"],
            "purchase_id" => $row["purchase_id"],
            "product_name" => $row["product_name"],
            "purchase_date" => $row["purchase_date"],
            "purchase_quantity" => $row["purchase_quantity"],
            "purchase_rate" => $row["purchase_rate"],
            "selling_price" => $row["selling_price"],
            "total_cost" => $row["total_cost"],
            "expiry_date" => $row["expiry_date"]
        ];
    }
}

//  Always return valid JSON
header('Content-Type: application/json');
echo json_encode($data);
?>