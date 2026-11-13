<?php
header("Content-Type: application/json");
require "db.php";

// Get filter
$filter = $_GET['filter'] ?? 'all';

if($filter == "expiring"){
    $sql = "
    SELECT product_name, total_purchased, total_sold, current_stock, purchase_rate, selling_price, expiry_date 
    FROM stock 
    WHERE current_stock > 0 
    AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    ORDER BY product_name ASC
    ";
}
else if($filter == "expired"){
    $sql = "
    SELECT product_name, total_purchased, total_sold, current_stock, purchase_rate, selling_price, expiry_date 
    FROM stock 
    WHERE current_stock > 0 
    AND expiry_date < CURDATE()
    ORDER BY product_name ASC
    ";
}
else if($filter == "out"){
    $sql = "
    SELECT product_name, total_purchased, total_sold, current_stock, purchase_rate, selling_price, expiry_date 
    FROM stock 
    WHERE current_stock = 0
    ORDER BY product_name ASC
    ";
}
else if($filter == "low"){
    $sql = "
    SELECT product_name, total_purchased, total_sold, current_stock, purchase_rate, selling_price, expiry_date 
    FROM stock 
    WHERE current_stock > 0 AND current_stock <= 5
    ORDER BY product_name ASC
    ";
}
else {
    $sql = "
    SELECT product_name, total_purchased, total_sold, current_stock, purchase_rate, selling_price, expiry_date 
    FROM stock 
    ORDER BY product_name ASC
    ";
}

$result = $conn->query($sql);

$data = [];

if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }
}

echo json_encode($data);
?>