<?php
include "db.php";

header("Content-Type: text/plain"); // 🔥 IMPORTANT

if (!isset($_GET['id'])) {
    echo "error";
    exit;
}

$id = (int)$_GET['id'];

// Get product name
$res = $conn->query("SELECT product_name FROM purchases WHERE id=$id");

if ($res && $res->num_rows > 0) {

    $row = $res->fetch_assoc();
    $product = trim($row['product_name']);

    // Delete purchase
    $del1 = $conn->query("DELETE FROM purchases WHERE id=$id");

    // Delete stock (SAFE MATCH)
    $del2 = $conn->query("
        DELETE FROM stock 
        WHERE LOWER(TRIM(product_name)) = LOWER('$product')
    ");

    if ($del1 && $del2) {
        echo "success"; // 🔥 EXACT STRING
    } else {
        echo "error";
    }

} else {
    echo "error";
}
?>