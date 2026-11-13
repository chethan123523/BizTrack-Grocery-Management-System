<?php
require "db.php";



$id = $_POST['id'];
$old_name =strtolower(trim( $_POST['old_name']));
$new_name =strtolower(trim($_POST['product_name']));
$selling_price = $_POST['selling_price'];
$purchase_rate = $_POST['purchase_rate'];
$total_purchased = $_POST['total_purchased'];
$total_sold = $_POST['total_sold'];

$current_stock = $total_purchased - $total_sold;

try {

    //  STEP 1: UPDATE VALUES (WITHOUT CHANGING NAME)

    $stmt = $conn->prepare("
        UPDATE products 
        SET product_name=? selling_price=?, total_purchased=?, total_sold=?, current_stock=? 
        WHERE LOWER(product_name)=?
    ");
    $stmt->bind_param("sdiiis",$new_name, $selling_price, $total_purchased, $total_sold, $current_stock, $old_name);
    $stmt->execute();


    $stmt = $conn->prepare("
        UPDATE stock 
        SET  product_name=?, purchase_rate=?, selling_price=?, total_purchased=?, total_sold=?, current_stock=? 
        WHERE LOWER(product_name)=?
    ");
    $stmt->bind_param("sddiiis",$new_name, $purchase_rate, $selling_price, $total_purchased, $total_sold, $current_stock, $old_name);
    $stmt->execute();


    //  STEP 2: CHANGE NAME EVERYWHERE (LAST STEP)

    if ($old_name !== $new_name) {

        $stmt = $conn->prepare("UPDATE products SET product_name=? WHERE product_name=?");
        $stmt->bind_param("ss", $new_name, $old_name);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE stock SET product_name=? WHERE product_name=?");
        $stmt->bind_param("ss", $new_name, $old_name);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE purchases SET product_name=? WHERE product_name=?");
        $stmt->bind_param("ss", $new_name, $old_name);
        $stmt->execute();
    }

    echo json_encode(["status" => "success"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error"]);
}
?>