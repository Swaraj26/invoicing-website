<?php
// config/db.php

$host = "localhost";
$dbName = "invoicing_db";
$username = "invoice_user";
$password = "StrongPassword123"; // or your MySQL password if you've set one

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
    exit;
}
