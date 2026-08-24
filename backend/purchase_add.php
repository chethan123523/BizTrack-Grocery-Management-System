<?php
require "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

$date = $_POST['purchase_date'] ?? '';
$pid = $_POST['purchase_id'] ?? '';
$name = strtolower(trim($_POST['product_name'])) ?? '';
$qty = (int)($_POST['purchase_quantity'] ?? 0);
$rate = (float)($_POST['purchase_rate'] ?? 0);
$sell = (float)($_POST['selling_price'] ?? 0);
$expiry = $_POST['expiry_date'] ?? null;

$total = $qty * $rate;

$editId = $_POST['edit_id'] ?? null;


/* ---------------- VALIDATION ---------------- */

if (empty($date) || empty($pid) || empty($name) || $qty <= 0) {
    die("Invalid data");
}


/* ---------------- EDIT PURCHASE ---------------- */

if (!empty($editId)) {

$oldStmt = $conn->prepare("
SELECT purchase_quantity, product_name
FROM purchases
WHERE id = ?
");

$oldStmt->bind_param("i", $editId);
$oldStmt->execute();

$oldResult = $oldStmt->get_result();
$oldData = $oldResult->fetch_assoc();

if ($oldData) {

$oldQty = $oldData['purchase_quantity'];
$oldProduct = $oldData['product_name'];

$reduceStock = $conn->prepare("
UPDATE stock
SET current_stock = current_stock - ?,
total_purchased = total_purchased - ?
WHERE product_name = ?
");

$reduceStock->bind_param("iis", $oldQty, $oldQty, $oldProduct);
$reduceStock->execute();
}


/* update purchase */

$updatePurchase = $conn->prepare("
UPDATE purchases
SET purchase_id=?, product_name=?, purchase_date=?,
purchase_quantity=?, purchase_rate=?,
selling_price=?, total_cost=?, expiry_date=?
WHERE id=?
");

$updatePurchase->bind_param(
"sssiddssi",
$pid,
$name,
$date,
$qty,
$rate,
$sell,
$total,
$expiry,
$editId
);

$updatePurchase->execute();

}


/* ---------------- INSERT PURCHASE ---------------- */

if (empty($editId)) {

$stmt = $conn->prepare("
INSERT INTO purchases
(purchase_id, product_name, purchase_date,
purchase_quantity, purchase_rate, selling_price,
total_cost, expiry_date, created_at)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmt->bind_param(
"sssiddss",
$pid,
$name,
$date,
$qty,
$rate,
$sell,
$total,
$expiry
);

$stmt->execute();

}


// CHECK PRODUCT EXISTS
$productCheck = $conn->prepare("
    SELECT product_id, total_purchased, total_sold, current_stock 
    FROM products 
    WHERE product_name = ?
");
$productCheck->bind_param("s", $name);
$productCheck->execute();

$productResult = $productCheck->get_result();

if ($productResult->num_rows > 0) {

    //  UPDATE EXISTING PRODUCT
    $row = $productResult->fetch_assoc();

    $newPurchased = $row['total_purchased'] + $qty;
    $newStock = $row['current_stock'] + $qty;

    $updateProduct = $conn->prepare("
        UPDATE products 
        SET total_purchased = ?, 
            current_stock = ?, 
            selling_price = ?
        WHERE product_name = ?
    ");

    $updateProduct->bind_param("iids", $newPurchased, $newStock, $sell, $name);
    $updateProduct->execute();

} else {

    // 👉 INSERT NEW PRODUCT
    $insertProduct = $conn->prepare("
        INSERT INTO products 
        (product_name, selling_price, total_purchased, total_sold, current_stock, min_stock, created_at)
        VALUES (?, ?, ?, 0, ?, 5, NOW())
    ");

    $insertProduct->bind_param("sdii", $name, $sell, $qty, $qty);
    $insertProduct->execute();
}

/* ---------------- UPDATE STOCK ---------------- */
$checkStat = $conn->prepare("
    SELECT total_purchased, current_stock 
    FROM stock 
    WHERE product_name = ?
");
$checkStat->bind_param("s", $name);
$checkStat->execute();
$result = $checkStat->get_result();

if ($result->num_rows > 0) {

    $row = $result->fetch_assoc();

    $newPurchased = $row['total_purchased'] + $qty;
    $newStock = $row['current_stock'] + $qty;

    $updateStat = $conn->prepare("
        UPDATE stock 
        SET 
            total_purchased = ?,
            current_stock = ?,
            purchase_rate = ?,
            selling_price = ?,
            expiry_date = ?,        -- ✅ FIXED
            updated_at = NOW()
        WHERE product_name = ?
    ");

    $updateStat->bind_param(
        "iiddss",
        $newPurchased,
        $newStock,
        $rate,
        $sell,
        $expiry,   // ✅ FIXED
        $name
    );

    $updateStat->execute();

} else {

    $insertStock = $conn->prepare("
        INSERT INTO stock 
        (product_name, total_purchased, total_sold, current_stock, purchase_rate, selling_price, expiry_date, updated_at)
        VALUES (?, ?, 0, ?, ?, ?, ?, NOW())
    ");

    $insertStock->bind_param(
        "siidds",
        $name,
        $qty,
        $qty,
        $rate,
        $sell,
        $expiry   // ✅ FIXED
    );

    $insertStock->execute();
}


/* ---------------- REDIRECT ---------------- */

header("Location: ../frontend/index.php");
exit();

}
?>