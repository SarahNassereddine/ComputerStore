<?php
require_once './config.php';

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$id = (int)$_GET['id'];

$sql = "SELECT * FROM Product WHERE ProductID = ?";
$stmt = executeQuery($conn, $sql, [$id]);
$product = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$product) {
    die("Product not found");
}
?>
<html>
    <head>
        <title>Edit Product - TechStore</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
<div class="form-container">
<form method="POST">

    <h2>Edit Product</h2>

    <label>Name</label>
    <input type="text" name="name"
           value="<?= htmlspecialchars($product['Name']); ?>" required>

    <label>Category</label>
    <input type="text" name="category"
           value="<?= htmlspecialchars($product['Category']); ?>" required>

    <label>Price</label>
    <input type="number" name="price" step="0.01"
           value="<?= $product['Price']; ?>" required>

    <label>Stock</label>
    <input type="number" name="stock"
           value="<?= $product['Stock']; ?>" required>

    <label>Description</label>
    <textarea name="description"><?= htmlspecialchars($product['Description']); ?></textarea>

    <button type="submit" name="update">Update Product</button>

</form>
</div>

<?php
if (isset($_POST['update'])) {

    $sql = "UPDATE Product
            SET Name=?, Category=?, Price=?, Stock=?, Description=?
            WHERE ProductID=?";

    executeQuery($conn, $sql, [
        $_POST['name'],
        $_POST['category'],
        $_POST['price'],
        $_POST['stock'],
        $_POST['description'],
        $id
    ]);

    header("Location: dashboard.php?updated=1");
    exit;
}
?>
<div class="form-container">
<form method="POST">

    <h2>Edit Product</h2>

    <label>Name</label>
    <input type="text" name="name"
           value="<?= htmlspecialchars($product['Name']); ?>" required>

    <label>Category</label>
    <input type="text" name="category"
           value="<?= htmlspecialchars($product['Category']); ?>" required>

    <label>Price</label>
    <input type="number" name="price" step="0.01"
           value="<?= $product['Price']; ?>" required>

    <label>Stock</label>
    <input type="number" name="stock"
           value="<?= $product['Stock']; ?>" required>

    <label>Description</label>
    <textarea name="description"><?= htmlspecialchars($product['Description']); ?></textarea>

    <button type="submit" name="update">Update Product</button>

</form>
</div>
    </body>
</html>