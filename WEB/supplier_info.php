<?php
require_once 'config.php';

if (!isset($_GET['product_id'])) {
    die("Product not specified");
}

$productId = $_GET['product_id'];

/* 1️⃣ Get product + supplier id */
$sqlProduct = "
SELECT Name, SupplierID 
FROM Product 
WHERE ProductID = ?
";
$stmtProduct = executeQuery($conn, $sqlProduct, array($productId));
$product = sqlsrv_fetch_array($stmtProduct, SQLSRV_FETCH_ASSOC);

if (!$product || !$product['SupplierID']) {
    die("Supplier not found for this product");
}

/* 2️⃣ Get supplier info */
$sqlSupplier = "
SELECT Name, Email, Phone, Address 
FROM Supplier 
WHERE SupplierID = ?
";
$stmtSupplier = executeQuery($conn, $sqlSupplier, array($product['SupplierID']));
$supplier = sqlsrv_fetch_array($stmtSupplier, SQLSRV_FETCH_ASSOC);

if (!$supplier) {
    die("Supplier not found");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Supplier</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
        }

        .card {
            width: 420px;
            margin: 80px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .card h2 {
            text-align: center;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .product-name {
            text-align: center;
            font-size: 14px;
            color: #777;
            margin-bottom: 25px;
        }

        .info {
            margin-bottom: 15px;
        }

        .info label {
            font-size: 13px;
            color: #888;
        }

        .info div {
            font-size: 15px;
            font-weight: 600;
            color: #333;
        }

        .actions {
            margin-top: 25px;
            display: flex;
            gap: 10px;
        }

        .btn {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            color: white;
        }

        .btn-email {
            background: #3498db;
        }

        .btn-back {
            background: #7f8c8d;
        }

        .note {
            margin-top: 20px;
            font-size: 12px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>Supplier Contact</h2>
    <div class="product-name">
        Product: <?= htmlspecialchars($product['Name']) ?>
    </div>

    <div class="info">
        <label>Supplier Name</label>
        <div><?= htmlspecialchars($supplier['Name']) ?></div>
    </div>

    <div class="info">
        <label>Email</label>
        <div><?= htmlspecialchars($supplier['Email']) ?></div>
    </div>

    <div class="info">
        <label>Phone</label>
        <div><?= htmlspecialchars($supplier['Phone']) ?></div>
    </div>

    <div class="info">
        <label>Address</label>
        <div><?= htmlspecialchars($supplier['Address']) ?></div>
    </div>

    <div class="actions">
        <a href="mailto:<?= htmlspecialchars($supplier['Email']) ?>" class="btn btn-email">
            Send Email
        </a>
        <a href="products_admin.php" class="btn btn-back">
            Back
        </a>
    </div>

    <div class="note">
        Supplier information is provided for business communication purposes only.
    </div>
</div>

</body>
</html>
