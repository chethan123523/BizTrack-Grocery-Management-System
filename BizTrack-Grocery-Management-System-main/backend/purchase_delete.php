<?php
require "db.php";

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    echo json_encode(["status" => "error"]);
    exit;
}

$conn->begin_transaction();

try {

    $stmt = $conn->prepare("SELECT * FROM purchases WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();

    if (!$old) {
        throw new Exception();
    }

    $stmt = $conn->prepare("
        UPDATE stock
        SET total_purchased = total_purchased - ?,
            current_stock = current_stock - ?
        WHERE product_name = ?
    ");
    $stmt->bind_param("iis", $old['purchase_quantity'], $old['purchase_quantity'], $old['product_name']);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM purchases WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $conn->commit();

    echo json_encode(["status" => "success"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error"]);
}
?>