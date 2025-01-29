<?php
// config/db.php

$host = "sql110.infinityfree.com";
$dbName = "if0_38191118_invoicing_db";
$username = "if0_38191118";
$password = "Swaraj2601"; // or your MySQL password if you've set one

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
    exit;
}
