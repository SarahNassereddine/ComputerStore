<?php
require_once 'config.php';

$message = '';

// Handle cart actions
if (isset($_GET['remove'])) {
    $removeId = (int)$_GET['remove'];
    unset($_SESSION['cart'][$removeId]);
    $message = '<div class="alert alert-success">✅ Item removed</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $productId => $quantity) {
            $productId = (int)$productId;
            $quantity = (int)$quantity;
            if ($quantity > 0) {
                $_SESSION['cart'][$productId] = $quantity;
            } else {
                unset($_SESSION['cart'][$productId]);
            }
        }
        $message = '<div class="alert alert-success">✅ Cart updated</div>';
    }
    
    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = array();
        $message = '<div class="alert alert-success">✅ Cart cleared</div>';
    }
    
    if (isset($_POST['checkout'])) {
        header('Location: checkout.php');
        exit;
    }
}

// Get cart items
$cartItems = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    $sql = "SELECT * FROM Product WHERE ProductID IN ($placeholders)";
    $stmt = executeQuery($conn, $sql, $ids);
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $productId = $row['ProductID'];
        $quantity = $_SESSION['cart'][$productId];
        
        if ($quantity > $row['Stock']) {
            $quantity = $row['Stock'];
            $_SESSION['cart'][$productId] = $quantity;
        }
        
        $subtotal = $row['Price'] * $quantity;
        $total += $subtotal;
        
        $cartItems[] = [
            'product' => $row,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo STORE_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
  main.cart-page {
    padding: 20px 0;
}


        .cart-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }
        
        .cart-header h1 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .cart-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .cart-items-box, .cart-summary-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
        }
        
        .cart-item:hover {
            background: #f8f9fa;
        }
        
      .item-icon {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    background: #f4f6f8;
    display: flex;
    align-items: center;
    justify-content: center;
}

.item-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
header .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}
header {
    background: white;
    border-bottom: 1px solid #eee;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 70px;
}


        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        .item-category {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .item-price {
            color: #e74c3c;
            font-weight: bold;
            font-size: 16px;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .qty-input {
            width: 70px;
            padding: 8px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .qty-btn {
            width: 35px;
            height: 35px;
            border: 2px solid #3498db;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            color: #3498db;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qty-btn:hover {
            background: #3498db;
            color: white;
        }
        
        .item-total {
            font-weight: bold;
            color: #2c3e50;
            min-width: 100px;
            text-align: right;
            font-size: 18px;
        }
        
        .remove-link {
            color: #e74c3c;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
        }
        
        .remove-link:hover {
            text-decoration: underline;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-total {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .btn-checkout {
            background: linear-gradient(135deg, #27ae60 0%, #219653 100%);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .empty-icon {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .cart-actions {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 15px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
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
        
        .secure-note {
            text-align: center;
            margin-top: 15px;
            padding: 12px;
            background: #f0f9ff;
            border-radius: 6px;
            color: #3498db;
            font-size: 13px;
        }
        
        @media (max-width: 768px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
            
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .item-icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
            
            .cart-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <a href="index.php" class="logo"><?php echo STORE_NAME; ?></a>
            <nav class="nav-menu">
                <a href="index.php" class="nav-link">Home</a>
                <a href="products.php" class="nav-link">Products</a>
                <a href="cart.php" class="nav-link active">Cart (<?php echo getCartCount(); ?>)</a>
                <a href="dashboard.php" class="nav-link">Dashboard</a>
            </nav>
        </div>
    </header>

    <main class="container cart-page">
        <?php echo $message; ?>
        
        <div class="cart-header">
            <h1>🛒 Shopping Cart</h1>
            <p style="color: #666;">Review and manage your items</p>
        </div>
        
        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <div class="empty-icon">🛒</div>
                <h2 style="margin-bottom: 10px; color: #2c3e50;">Your cart is empty</h2>
                <p style="color: #666; margin-bottom: 20px;">Add some awesome products to get started!</p>
                <a href="products.php" class="btn" style="padding: 12px 30px;">
                    <i class="fas fa-store"></i> Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <!-- Cart Items -->
                <div class="cart-items-box">
                    <form method="POST" id="cart-form">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                            <h3 style="color: #2c3e50;">Your Items (<?php echo count($cartItems); ?>)</h3>
                            <button type="submit" name="update_cart" class="btn" style="padding: 10px 20px;">
                                <i class="fas fa-sync-alt"></i> Update Cart
                            </button>
                        </div>
                        
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item">
                                <div class="item-icon">
                                <img src="assets/images/products/<?php echo $item['product']['ImageURL']; ?>"
         alt="<?php echo htmlspecialchars($item['product']['Name']); ?>">
                                </div>
                                
                                <div class="item-info">
                                    <div class="item-name"><?php echo $item['product']['Name']; ?></div>
                                    <div class="item-category"><?php echo $item['product']['Category']; ?></div>
                                    <div class="item-price"><?php echo formatPrice($item['product']['Price']); ?></div>
                                    <div style="font-size: 13px; color: <?php echo $item['product']['Stock'] > 10 ? '#27ae60' : '#f39c12'; ?>; margin-top: 5px;">
                                        <i class="fas fa-box"></i> Stock: <?php echo $item['product']['Stock']; ?>
                                    </div>
                                    
                                    <div class="quantity-control">
                                        <button type="button" class="qty-btn" 
                                                onclick="updateQuantity(<?php echo $item['product']['ProductID']; ?>, -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" 
                                               name="quantity[<?php echo $item['product']['ProductID']; ?>]" 
                                               value="<?php echo $item['quantity']; ?>"
                                               min="1" 
                                               max="<?php echo $item['product']['Stock']; ?>"
                                               class="qty-input"
                                               id="qty-<?php echo $item['product']['ProductID']; ?>">
                                        <button type="button" class="qty-btn" 
                                                onclick="updateQuantity(<?php echo $item['product']['ProductID']; ?>, 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div style="text-align: right;">
                                    <div class="item-total"><?php echo formatPrice($item['subtotal']); ?></div>
                                    <a href="cart.php?remove=<?php echo $item['product']['ProductID']; ?>" 
                                       class="remove-link">
                                        <i class="fas fa-trash"></i> Remove
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="cart-actions">
                            <button type="submit" name="clear_cart" class="btn" 
                                    onclick="return confirm('Clear all items from cart?')">
                                <i class="fas fa-trash"></i> Clear Cart
                            </button>
                            <a href="products.php" class="btn">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Order Summary -->
                <div class="cart-summary-box">
                    <h3 style="color: #2c3e50; margin-bottom: 25px; padding-bottom: 10px; border-bottom: 2px solid #f8f9fa;">
                        <i class="fas fa-receipt"></i> Order Summary
                    </h3>
                    
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span><?php echo formatPrice($total); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span style="color: #27ae60; font-weight: bold;">FREE</span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Tax (11%)</span>
                        <span><?php echo formatPrice($total * 0.11); ?></span>
                    </div>
                    
                    <div class="summary-row" style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #2c3e50;">
                        <span style="font-weight: bold;">Total Items</span>
                        <span style="font-weight: bold;"><?php echo getCartCount(); ?></span>
                    </div>
                    
                    <div class="summary-row" style="padding: 15px 0;">
                        <span style="font-size: 18px; font-weight: bold;">Total Amount</span>
                        <span class="summary-total"><?php echo formatPrice($total * 1.11); ?></span>
                    </div>
                    
                    <form method="POST">
                        <button type="submit" name="checkout" class="btn-checkout">
                            <i class="fas fa-lock"></i> Proceed to Payment
                        </button>
                    </form>
                    
                    <div class="secure-note">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure & encrypted payment</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo STORE_NAME; ?></p>
        </div>
    </footer>

    <script>
        function updateQuantity(productId, change) {
            const input = document.getElementById('qty-' + productId);
            let currentValue = parseInt(input.value) || 0;
            let newValue = currentValue + change;
            
            const max = parseInt(input.max);
            const min = parseInt(input.min);
            
            if (newValue < min) newValue = min;
            if (newValue > max) newValue = max;
            
            input.value = newValue;
        }
        
        // Auto-update on input change
        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('change', function() {
                const max = parseInt(this.max);
                const min = parseInt(this.min);
                let value = parseInt(this.value);
                
                if (isNaN(value) || value < min) {
                    this.value = min;
                } else if (value > max) {
                    this.value = max;
                }
            });
        });
    </script>
</body>
</html>