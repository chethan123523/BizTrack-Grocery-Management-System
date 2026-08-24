<?php
require "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$customer_name = $data["customer_name"] ?? "Walk-in";
$phone = $data["phone"] ?? "";
$payment_mode = $data["payment_mode"] ?? "Cash";
$total_amount = $data["total_amount"] ?? 0;
$items = $data["items"] ?? [];

if (empty($items)) {
    echo json_encode(["status"=>"error", "message"=>"No items"]);
    exit;
}

// Insert bill into 'bills' table
$stmt = $conn->prepare("
    INSERT INTO bills (bill_date, customer_name, phone_number, total_amount, payment_mode)
    VALUES (NOW(), ?, ?, ?, ?)
");
$stmt->bind_param("ssds", $customer_name, $phone, $total_amount, $payment_mode);
$stmt->execute();

$bill_id = $conn->insert_id;

// Process each item
foreach ($items as $item) {
    $name = $item["product_name"];
    $qty = intval($item["qty"]);
    $price = floatval($item["price"]);
    $total = $qty * $price;

    // Fetch purchase cost and current stock from 'products' table
    $stockCheck = $conn->prepare("
        SELECT current_stock, purchase_rate 
        FROM stock 
        WHERE product_name = ?
    ");
    $stockCheck->bind_param("s", $name);
    $stockCheck->execute();
    $stockResult = $stockCheck->get_result();
    
    if ($stockResult->num_rows == 0) continue; // Skip if product not found
    
    $stock = $stockResult->fetch_assoc();
    $purchase_cost = $stock['purchase_rate'];
    $current_stock = $stock['current_stock'];

    // Skip if stock is insufficient
    if ($current_stock < $qty) continue;

    // Insert into bill_items table
    $insertItem = $conn->prepare("
        INSERT INTO bill_items (bill_id, product_name, quantity, selling_price, purchase_cost, item_total)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $insertItem->bind_param("isidds", $bill_id, $name, $qty, $price, $purchase_cost, $total);
    $insertItem->execute();

    // Update stock table: decrease current stock & increase total_sold
    $updateStock = $conn->prepare("
        UPDATE stock 
        SET current_stock = current_stock - ?, 
            total_sold = total_sold + ? 
        WHERE product_name = ?
    ");
    $updateStock->bind_param("iis", $qty, $qty, $name);
    $updateStock->execute();

    // Update total_sold in products table
$updateProductSold = $conn->prepare("
    UPDATE products
    SET total_sold = total_sold + ?
    WHERE product_name = ?
");
$updateProductSold->bind_param("is", $qty, $name);
$updateProductSold->execute();
}

echo json_encode([
    "status" => "success",
    "bill_id" => $bill_id
]);

$conn->close();
?>