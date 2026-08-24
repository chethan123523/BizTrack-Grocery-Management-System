<?php

require "db.php";

$data = [];

/* Today's Sales */

$res = $conn->query("
SELECT SUM(quantity * selling_price) AS total
FROM bill_items
WHERE DATE(created_at) = CURDATE()
");

$row = $res->fetch_assoc();
$data['todaySales'] = $row['total'] ?? 0;


/* Net Profit */

$res = $conn->query("
SELECT COALESCE(SUM((selling_price - purchase_cost) * quantity),0) AS profit
FROM bill_items
");

$row = $res->fetch_assoc();
$data['netProfit'] = $row['profit'] ?? 0;


/* Total Products */

$res = $conn->query("SELECT COUNT(*) AS total FROM stock");
$data['totalProductss'] = $res->fetch_assoc()['total'];


/* Low Stock */

$res = $conn->query("
SELECT COUNT(*) AS total
FROM stock
WHERE current_stock > 0 AND current_stock <= 10
");

$data['lowStock'] = $res->fetch_assoc()['total'];


/* Out of Stock */

$res = $conn->query("
SELECT COUNT(*) AS total
FROM stock
WHERE current_stock = 0
");

$data['outOfStock'] = $res->fetch_assoc()['total'];


/* Expired Products */

$res = $conn->query("

SELECT COUNT(*) AS total
FROM stock
WHERE current_stock > 0
AND expiry_date IS NOT NULL
AND expiry_date < CURDATE()
");

$data['expiredProducts'] = $res->fetch_assoc()['total'];


/* Expiring Soon */

$res = $conn->query("

SELECT COUNT(*) AS total
FROM stock
WHERE current_stock > 0
AND expiry_date IS NOT NULL
AND expiry_date BETWEEN CURDATE() 
AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)

");

$data['expiringSoon'] = $res->fetch_assoc()['total'];


/* Recent Transactions */

$res = $conn->query("
SELECT bill_id,customer_name, bill_date, total_amount, payment_mode
FROM bills
ORDER BY bill_date DESC
LIMIT 5
");

$transactions = [];

while($row = $res->fetch_assoc()){
$transactions[] = $row;
}

$data['transactions'] = $transactions;

echo json_encode($data);

?>