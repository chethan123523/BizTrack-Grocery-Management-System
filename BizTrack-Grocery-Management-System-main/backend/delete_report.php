<?php
include "db.php";

$id = $_GET['id'];

$conn->query("DELETE FROM profit_reports WHERE id=$id");

echo "deleted";
?>