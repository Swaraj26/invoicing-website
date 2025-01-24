<?php
// public/index.php

require_once __DIR__ . '/../config/db.php';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoicing Home</title>
</head>
<body>
    <h1>Invoicing Home</h1>
    <ul>
        <li><a href="create_invoice.php">Create New Invoice</a></li>
        <!-- You can add more links, e.g. view invoices, etc. -->
    </ul>
</body>
</html>
