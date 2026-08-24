<?php
$conn = new mysqli("localhost", "root", "", "biztrack_db");

if ($conn->connect_error) {
    die("Database connection failed");
}

