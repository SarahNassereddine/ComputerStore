<?php
require_once 'config.php';

$message = "";

// Get suppliers for dropdown
$sqlSuppliers = "SELECT SupplierID, Name FROM Supplier ORDER BY Name";
$suppliers = executeQuery($conn, $sqlSuppliers);

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $category = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($category));
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $supplierID = !empty($_POST['supplier']) ? $_POST['supplier'] : null;
    $description = $_POST['description'];
 $imagePath = null;

if (!empty($_FILES['image']['name'])) {

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        die("Invalid image type");
    }

    // folder 
    $folder = "assets/images/products/";

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $imageName = uniqid() . "." . $ext;
    $imagePath = $folder . $imageName;

    move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
}

$sqlCheck = "SELECT COUNT(*) AS total FROM Product WHERE Name = ?";
$stmt = executeQuery($conn, $sqlCheck, [$name]);
$exists = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)['total'];

    $sql = "INSERT INTO Product 
            (Name, Category, Price, Stock, SupplierID, Description,imageURL
)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $params = [
        $name,
        $category,
        $price,
        $stock,
        $supplierID,
        $description,
        $imageName

    ];

    $stmt = executeQuery($conn, $sql, $params);

    if ($stmt) {
        header("Location: dashboard.php?success=1");
        exit;
    } else {
        $message = "❌ Error adding product";
    }
}
?>
<!DOCTYPE html>
<html lang="en" >
    <head>
    <meta charset="UTF-8">
        <link rel="stylesheet" href="style.css">
    </head>
<body>
<div class="add-product-page">

    <div class="add-product-card">
        <h2>Add New Product</h2>
        <p class="subtitle">Create and manage your store inventory</p>
<form method="POST" enctype="multipart/form-data" class="add-product-form">

            <div>
                <label>Product Name</label>
                <input type="text" name="name" required>
            </div>

            <div>
                <label>Category</label>
                <input type="text" name="category" required>
            </div>

            <div>
                <label>Price ($)</label>
                <input type="number" name="price" step="0.01" required>
            </div>

            <div>
                <label>Stock</label>
                <input type="number" name="stock" min="0" required>
            </div>

            <div class="form-full">
                <label>Supplier</label>
                <select name="supplier">
                    <option value="">-- No Supplier --</option>
                    <?php while ($s = sqlsrv_fetch_array($suppliers, SQLSRV_FETCH_ASSOC)): ?>
                        <option value="<?php echo $s['SupplierID']; ?>">
                            <?php echo htmlspecialchars($s['Name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-full">
            </div>
            <label>Product Image</label>
            <input type="file" name="image" accept="image/*" required>

            <div class="form-full">
                <label>Description</label>
                <textarea name="description" rows="4"></textarea>
            </div>

            <div class="form-actions">
                <a href="dashboard.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </div>

        </form>
    </div>

</div>
</body>

