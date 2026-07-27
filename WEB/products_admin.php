<?php
require_once 'config.php';
$orderBy = "";

if (isset($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'price_asc':
            $orderBy = " ORDER BY Price ASC";
            break;
        case 'price_desc':
            $orderBy = " ORDER BY Price DESC";
            break;
        case 'name_asc':
            $orderBy = " ORDER BY Name ASC";
            break;
        case 'name_desc':
            $orderBy = " ORDER BY Name DESC";
            break;
        case 'stock_asc':
            $orderBy = " ORDER BY Stock ASC";
            break;
        case 'stock_desc':
            $orderBy = " ORDER BY Stock DESC";
            break;
    }
}

$sql = "SELECT ProductID, Name, Price, Stock FROM Product" . $orderBy;
$stmt = executeQuery($conn, $sql);

?>
<html>
<head>
    <title>Products Admin - TechStore</title>
        <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
        }

        .product-table {
            width: 85%;
            margin: 40px auto;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        .product-table th {
            background: #2c3e50;
            color: #fff;
            padding: 12px;
            text-align: left;
        }

        .product-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .product-table tr:hover {
            background: #f1f1f1;
        }

        .btn-edit {
            padding: 6px 12px;
            background: #f39c12;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }

        .btn-edit:hover {
            background: #d68910;
        }
        .filter-box {
    width: 85%;
    margin: 20px auto;
    background: #ffffff;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 15px;
}

.filter-box label {
    font-weight: bold;
    color: #2c3e50;
}

.filter-box select {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 14px;
    cursor: pointer;
}

.filter-box select:focus {
    outline: none;
    border-color: #3498db;
}

    </style>
</head>
<body>
  <div class="filter-box">
    <form method="GET">
        <label>Sort Products</label>
        <select name="sort" onchange="this.form.submit()">
            <option value="">Default</option>
            <option value="price_asc">Price: Low → High</option>
            <option value="price_desc">Price: High → Low</option>
            <option value="name_asc">Name: A → Z</option>
            <option value="name_desc">Name: Z → A</option>
            <option value="stock_asc">Stock: Low → High</option>
            <option value="stock_desc">Stock: High → Low</option>
        </select>
    </form>
</div>


<table class="product-table">
    <tr>
        <th>Name</th>
        <th>Price</th>
        <th>Stock</th>
        <th>Action</th>
        <th>Contact supplier</th>
    </tr>

<?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
    <tr>
        <td><?= htmlspecialchars($row['Name']) ?></td>
        <td>$<?= number_format($row['Price'], 2) ?></td>
        <td><?= $row['Stock'] ?></td>
        <td>
            <a href="edit_product.php?id=<?= $row['ProductID'] ?>" class="btn-edit">
                Edit
            </a>
        </td>
        <td>
            <a href="supplier_info.php?product_id=<?= $row['ProductID'] ?>" class="btn-edit">
                Contact Supplier
            </a>
    </tr>
<?php } ?>
</table>
</body>
</html>