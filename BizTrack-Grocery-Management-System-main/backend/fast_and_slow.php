<?php
include "db.php";

// Initialize arrays and counters
$fastList = $slowList = $normalList = $deadList = [];
$fast = $slow = $normal = $dead = 0;

// Fetch all products
$sql = "SELECT * FROM products";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        // SAFETY FIX (IMPORTANT)
        $sold = isset($row['total_sold']) ? (int)$row['total_sold'] : 0;

        // CLASSIFICATION
        if ($sold == 0) {
            $deadList[] = $row['product_name'];
            $dead++;
        }
        elseif ($sold <= 5) {
            $slowList[] = $row['product_name'];
            $slow++;
        }
        elseif ($sold <= 20) {
            $normalList[] = $row['product_name'];
            $normal++;
        }
        else {
            $fastList[] = $row['product_name'];
            $fast++;
        }
    }
}

// Prepare JSON response
$response = [
    "fast" => $fast,
    "slow" => $slow,
    "normal" => $normal,
    "dead" => $dead,
    "fastList" => $fastList,
    "slowList" => $slowList,
    "normalList" => $normalList,
    "deadList" => $deadList
];

header("Content-Type: application/json");
echo json_encode($response);

$conn->close();
?>