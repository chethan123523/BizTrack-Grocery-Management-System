<?php
include "db.php";

$sql = "SELECT * FROM profit_reports ORDER BY created_at DESC";
$result = $conn->query($sql);

$data = [];

while($row = $result->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);
?>