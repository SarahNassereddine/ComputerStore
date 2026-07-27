<?php
require_once 'config.php';

$product_id = $_GET['id'] ?? 0;
$message = '';

// Get product details
$sql = "SELECT p.*, s.Name as SupplierName FROM Product p 
        LEFT JOIN Supplier s ON p.SupplierID = s.SupplierID 
        WHERE p.ProductID = ?";
$stmt = executeQuery($conn, $sql, array($product_id));
$product = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit;
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = $_POST['quantity'] ?? 1;
    
    if ($quantity > 0 && $quantity <= $product['Stock']) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        $message = '<div class="alert alert-success">✅ Added to cart successfully!</div>';
    } else {
        $message = '<div class="alert alert-error">❌ Invalid quantity!</div>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $product['Name']; ?> - Computer Store</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Product Detail Styles - Beautiful Design */
        .product-detail-page {
            padding: 20px 0;
        }
        
        .product-container {
            display: flex;
            gap: 40px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
.product-image-box {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

        .product-main-image {
    width: 100%;
    max-width: 380px;
    height: 300px;
    object-fit: contain;
    background: #f8f9fa;
    border-radius: 12px;
    padding: 15px;
}

.product-category-label {
    font-size: 16px;
    font-weight: 600;
    color: #3498db;
}
        
        .product-icon-large {
            font-size: 120px;
            color: #3498db;
            margin-bottom: 20px;
        }
        
        .product-info-box {
            flex: 2;
        }
        
        .product-title {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .product-price-large {
            font-size: 32px;
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .product-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
        }
        
        .meta-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .meta-value {
            color: #2c3e50;
            font-weight: 600;
        }
        
        .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .stock-in {
            background: #d4edda;
            color: #155724;
        }
        
        .stock-out {
            background: #f8d7da;
            color: #721c24;
        }
        
        .description-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .description-box h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .description-box p {
            line-height: 1.6;
            color: #555;
        }
        
        .action-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .quantity-input {
            width: 80px;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            text-align: center;
            font-size: 16px;
        }
        
        .btn-add-cart {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-view-cart {
            background: #27ae60;
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-view-cart:hover {
            background: #219653;
            transform: translateY(-2px);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .product-container {
                flex-direction: column;
            }
            
            .product-meta-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-add-cart, .btn-view-cart {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
  <header class="main-header">
    <div class="container header-content">
        <a href="index.php" class="logo">
            <i class="fas fa-desktop"></i>
            <span><?php echo STORE_NAME; ?></span>
        </a>

        <nav class="nav-menu">
            <a href="index.php" class="nav-link">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="products.php" class="nav-link">
                <i class="fas fa-microchip"></i> Products
            </a>
            <a href="cart.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i>
                Cart <span class="cart-badge"><?php echo getCartCount(); ?></span>
            </a>
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
        </nav>
    </div>
</header>


    <main class="container product-detail-page">
        <?php echo $message; ?>
        
        <!-- Back button -->
        <div style="margin-bottom: 20px;">
            <a href="products.php" class="btn" style="display: inline-flex; align-items: center; gap: 8px;">
                ← Back to Products
            </a>
        </div>
        
        <!-- Product Main Section -->
        <div class="product-container">
            <!-- Product Image/Icon -->
            <div class="product-image-box">
  <img 
    src="assets/images/products/<?php echo htmlspecialchars($product['ImageURL']); ?>" 
    alt="<?php echo htmlspecialchars($product['Name']); ?>"
    class="product-main-image"
>

     <div style="color: #3498db; font-weight: 600; font-size: 18px;">
                    <?php echo $product['Category']; ?>
                </div>


            </div>
            
            <!-- Product Information -->
            <div class="product-info-box">
                <h1 class="product-title"><?php echo $product['Name']; ?></h1>
                
                <div class="product-price-large">
                    <?php echo formatPrice($product['Price']); ?>
                </div>
                
                <!-- Stock Status -->
                <div class="stock-badge <?php echo $product['Stock'] > 0 ? 'stock-in' : 'stock-out'; ?>">
                    <?php if ($product['Stock'] > 0): ?>
                        ✅ <?php echo $product['Stock']; ?> units available
                    <?php else: ?>
                        ❌ Out of stock
                    <?php endif; ?>
                </div>
                
                <!-- Product Meta Information -->
                <div class="product-meta-grid">
                    <div class="meta-item">
                        <span class="meta-label">Product ID</span>
                        <span class="meta-value">#<?php echo $product['ProductID']; ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Category</span>
                        <span class="meta-value"><?php echo $product['Category']; ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Supplier</span>
                        <span class="meta-value"><?php echo $product['SupplierName']; ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Added Date</span>
                        <span class="meta-value"><?php echo $product['CreatedAt']->format('Y-m-d'); ?></span>
                    </div>
                </div>
                
                <!-- Product Description -->
                <?php if (!empty($product['Description'])): ?>
                <div class="description-box">
                    <h3>Product Description</h3>
                    <p><?php echo $product['Description']; ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Add to Cart Section -->
        <div class="action-box">
            <h3 style="margin-bottom: 20px; color: #2c3e50;">Order Now</h3>
            
            <?php if ($product['Stock'] > 0): ?>
            <form method="POST">
                <div class="quantity-control">
                    <label style="font-weight: 600;">Quantity:</label>
                    <input type="number" name="quantity" value="1" 
                           min="1" max="<?php echo $product['Stock']; ?>" 
                           class="quantity-input">
                    <span style="color: #666;">(Max: <?php echo $product['Stock']; ?> available)</span>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" name="add_to_cart" class="btn-add-cart">
                        🛒 Add to Cart
                    </button>
                    <a href="cart.php" class="btn-view-cart">
                        👁️ View Cart
                    </a>
                </div>
            </form>
            <?php else: ?>
            <div style="text-align: center; padding: 30px;">
                <div style="font-size: 48px; margin-bottom: 20px;">😔</div>
                <h3 style="color: #e74c3c; margin-bottom: 10px;">Currently Unavailable</h3>
                <p style="color: #666; margin-bottom: 20px;">
                    This product is out of stock. Check back soon or browse similar products.
                </p>
                <a href="products.php?category=<?php echo urlencode($product['Category']); ?>" 
                   class="btn-add-cart" style="background: #9b59b6;">
                    🔍 Browse <?php echo $product['Category']; ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Computer Store - University Project</p>
        </div>
    </footer>

    <script>
        // Simple quantity validation
        const quantityInput = document.querySelector('.quantity-input');
        if (quantityInput) {
            quantityInput.addEventListener('change', function() {
                const max = parseInt(this.max);
                const min = parseInt(this.min);
                let value = parseInt(this.value);
                
                if (isNaN(value)) {
                    this.value = min;
                } else if (value < min) {
                    this.value = min;
                } else if (value > max) {
                    this.value = max;
                }
            });
        }
    </script>
</body>
</html>