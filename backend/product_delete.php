<?php
include "db.php";

$product = $_GET['product'];

delete from purchases
$stmt1 = $conn->prepare("DELETE FROM purchases WHERE product_name=?");
$stmt1->bind_param("s", $product);
$stmt1->execute();

delete from stock
$stmt2 = $conn->prepare("DELETE FROM stock WHERE product_name=?");
$stmt2->bind_param("s", $product);
$stmt2->execute();

 delete from product
$stmt3 = $conn->prepare("DELETE FROM products WHERE product_name=?");
$stmt3->bind_param("s", $product);

if($stmt3->execute()){
    echo "success";
} else {
    echo "error";
}
?>