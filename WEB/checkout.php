<?php
require_once 'config.php';

$message = '';
$success = false;
$orderDetails = null;

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// Calculate cart total
$cartItems = array();
$subtotal = 0;
$shipping = 0;
$tax = 0;
$total = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    $sql = "SELECT * FROM Product WHERE ProductID IN ($placeholders)";
    $stmt = executeQuery($conn, $sql, $ids);
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $productId = $row['ProductID'];
        $quantity = $_SESSION['cart'][$productId];
        $itemTotal = $row['Price'] * $quantity;
        $subtotal += $itemTotal;
        
        $cartItems[] = array(
            'product' => $row,
            'quantity' => $quantity,
            'total' => $itemTotal
        );
    }
    
    // Calculate costs
    $shipping = $subtotal > 100 ? 0 : 15; // Free shipping over $100
    $tax = $subtotal * 0.11; // 11% tax
    $total = $subtotal + $shipping + $tax;
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    $customerName = trim($_POST['customer_name']);
    $customerEmail = trim($_POST['customer_email']);
    $customerPhone = trim($_POST['customer_phone']);
    $customerAddress = trim($_POST['customer_address']);
    $paymentMethod = $_POST['payment_method'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($customerName)) $errors[] = "Name is required";
    if (empty($customerEmail)) $errors[] = "Email is required";
    elseif (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($customerAddress)) $errors[] = "Address is required";
    if (empty($paymentMethod)) $errors[] = "Please select a payment method";
    
    if (empty($errors)) {
        // Begin transaction
        sqlsrv_begin_transaction($conn);
        try {
    // 1. التعامل مع الزبون (كودك الحالي)
    // 1. التعامل مع الزبون وجلب الـ ID
$sql = "SELECT CustomerID FROM Customer WHERE Email = ?";
$stmt = executeQuery($conn, $sql, array($customerEmail));
$customer = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if ($customer) {
    $customerId = $customer['CustomerID'];
    $sql = "UPDATE Customer SET Name = ?, Phone = ?, Address = ? WHERE CustomerID = ?";
    executeQuery($conn, $sql, array($customerName, $customerPhone, $customerAddress, $customerId));
} else {
    // دمج الـ INSERT مع جلب الـ ID في استعلام واحد لضمان عدم ضياع القيمة
    $sql = "INSERT INTO Customer (Name, Email, Phone, Address) VALUES (?, ?, ?, ?); 
            SELECT SCOPE_IDENTITY() AS CustomerID;";
    $stmt = sqlsrv_query($conn, $sql, array($customerName, $customerEmail, $customerPhone, $customerAddress));
    
    // الانتقال للنتيجة الثانية (التي تحتوي على الـ ID)
    sqlsrv_next_result($stmt); 
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $customerId = $row['CustomerID'];
}

// تأكد أن الـ ID ليس فارغاً قبل إكمال العملية
if (!$customerId) {
    throw new Exception("Could not retrieve Customer ID.");
}
    
    // 2. إنشاء الطلب
    $orderId = 0;
    $sql = "{CALL sp_CreateOrder(?, ?)}";
    $params = [
        array($customerId, SQLSRV_PARAM_IN),
        array(&$orderId, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_INT)
    ];
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) { throw new Exception("Order Creation Failed: " . print_r(sqlsrv_errors(), true)); }
    
    // ضروري جداً: استهلاك النتائج لكي تحصل على قيمة الـ OUT parameter
    while(sqlsrv_next_result($stmt)); 

    if ($orderId <= 0) {
        throw new Exception("Invalid Order ID received from database.");
    }

    // 3. إضافة العناصر (هنا تكمن المشكلة عادةً)
    foreach ($_SESSION['cart'] as $productId => $qty) {
        // جلب سعر المنتج الحالي أولاً (لأن البروسيدجر يحتاجه)
        $sqlPrice = "SELECT Price FROM Product WHERE ProductID = ?";
        $stmtPrice = executeQuery($conn, $sqlPrice, array($productId));
        $prodData = sqlsrv_fetch_array($stmtPrice, SQLSRV_FETCH_ASSOC);
        $unitPrice = $prodData['Price'];

        // استدعاء إضافة العنصر
        $sql_item = "{CALL sp_AddOrderItem(?, ?, ?)}";
        $stmt_item = sqlsrv_query($conn, $sql_item, array($orderId, $productId, $qty));
        
        if ($stmt_item === false) {
            throw new Exception("Stock Error or SQL Error: " . print_r(sqlsrv_errors(), true));
        }
        
        // مهم جداً: إفراغ النتائج لكل عنصر لكي يعمل الـ Trigger الخاص بتحديث الإجمالي
        while(sqlsrv_next_result($stmt_item));
    }

    // 4. تثبيت العملية
    if (sqlsrv_commit($conn)) {
        $success = true;
        // ملء بيانات النجاح للعرض في الصفحة
        $orderDetails = [
            'order_id' => $orderId,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'order_date' => date('Y-m-d H:i:s'),
            'total_amount' => $total, // القيمة المحسوبة في PHP
            'payment_method' => $paymentMethod
        ];
        $_SESSION['cart'] = array(); // تفريغ السلة
    } else {
        throw new Exception("Commit failed.");
    }

} catch (Exception $e) {
    sqlsrv_rollback($conn);
    $message = '<div class="alert alert-error">❌ Order Failed: ' . $e->getMessage() . '</div>';
}
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - TechStore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .checkout-progress {
            background: white;
            border-radius: var(--radius);
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: var(--shadow);
        }
        .payment-option input[type="radio"] {
    display: none;
}

        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--gray-light);
            z-index: 1;
        }
        
        .step {
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .step-number {
            width: 50px;
            height: 50px;
            background: white;
            border: 2px solid var(--gray-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin: 0 auto 10px;
            transition: var(--transition);
        }
        
        .step.active .step-number {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .step.completed .step-number {
            background: var(--success);
            color: white;
            border-color: var(--success);
        }
        
        .step-label {
            font-size: 14px;
            color: var(--gray);
        }
        
        .step.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 32px;
        }
        .payment-option input[type="radio"]:checked + .payment-content {
    border-color: var(--primary);
    background: #eef2ff;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.35);
    transform: scale(1.04);
}

.payment-content {
    transition: 0.25s ease;
}

        .checkout-form-section {
            background: white;
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow);
            margin-bottom: 24px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        
        .payment-option {
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .payment-option:hover {
           border-color: var(--primary);
    background: #f8fafc;
        }
        
        .payment-option.selected {
      border-color: var(--primary);
    background: #eef2ff;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.35);
    transform: scale(1.04);
        }
        
        .payment-icon {
            font-size: 32px;
            margin-bottom: 12px;
            color: var(--gray);
        }
        
        .payment-option.selected .payment-icon {
            color: var(--primary);
        }
        
        .checkout-summary {
            background: white;
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow);
            position: sticky;
            top: 100px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        
        .summary-divider {
            border-top: 2px solid var(--gray-light);
            margin: 24px 0;
        }
        
        .summary-total {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .cart-item-mini {
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .cart-item-mini:last-child {
            border-bottom: none;
        }
        
        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .success-container {
            background: white;
            border-radius: var(--radius);
            padding: 64px 32px;
            box-shadow: var(--shadow);
            text-align: center;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 32px;
            font-size: 48px;
            color: white;
        }
        
        .order-details {
            background: #f0f9ff;
            border-radius: 12px;
            padding: 32px;
            margin: 32px 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #dbeafe;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: var(--primary);
            border-radius: 50%;
            animation: confetti-fall 5s linear infinite;
        }
        
        @keyframes confetti-fall {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
        }
        button.btn-success:active {
    transform: translateY(2px);
}

        .payment-option.selected {
    border-color: var(--primary);
    background: #eef2ff;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
    transform: scale(1.03);
}
.payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
}

.payment-option input {
    display: none;
}

.payment-content {
    border: 2px solid var(--gray-light);
    border-radius: 10px;
    padding: 22px;
    text-align: center;
    cursor: pointer;
    transition: 0.25s ease;
    background: white;
}

.payment-icon {
    font-size: 32px;
    color: var(--gray);
    margin-bottom: 10px;
}

.payment-title {
    font-weight: 600;
}

.payment-desc {
    font-size: 12px;
    color: var(--gray);
    margin-top: 4px;
}

/* لما ينكبِس */
.payment-option input:checked + .payment-content {
    border-color: var(--primary);
    background: #eef2ff;
    box-shadow: 0 0 0 3px rgba(99,102,241,.3);
    transform: scale(1.03);
}

.payment-option input:checked + .payment-content .payment-icon {
    color: var(--primary);
}

    </style>
</head>
<body>
    <header class="main-header">
        <div class="container header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-laptop-code"></i>
                <span>TechStore</span>
            </a>
            
            <nav class="nav-menu">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    Home
                </a>
                <a href="products.php" class="nav-link">
                    <i class="fas fa-shopping-bag"></i>
                    Shop
                </a>
                <a href="cart.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                    <span class="cart-badge"><?php echo getCartCount(); ?></span>
                </a>
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    Dashboard
                </a>
            </nav>
        </div>
    </header>

    <section style="background: var(--light); min-height: 100vh; padding: 80px 0;">
        <div class="container">
            <?php if (!$success): ?>
                <!-- Progress Bar -->
                <div class="checkout-progress animate-fade">
                    <div class="progress-steps">
                        <div class="step active">
                            <div class="step-number">1</div>
                            <div class="step-label">Shipping</div>
                        </div>
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-label">Payment</div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-label">Confirmation</div>
                        </div>
                    </div>
                </div>
                
                <?php echo $message; ?>
                
                <div class="checkout-grid">
                    <!-- Left Column - Form -->
                    <div>
                        <form method="POST" action="" id="checkout-form">
                            <!-- Shipping Information -->
                            <div class="checkout-form-section animate-fade" style="animation-delay: 0.1s;">
                                <div class="section-title">
                                    <i class="fas fa-shipping-fast"></i>
                                    Shipping Information
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="customer_name" class="form-input" 
                                           value="<?php echo $_POST['customer_name'] ?? ''; ?>" 
                                           required placeholder="John Smith">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="customer_email" class="form-input" 
                                           value="<?php echo $_POST['customer_email'] ?? ''; ?>" 
                                           required placeholder="john@example.com">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="customer_phone" class="form-input" 
                                           value="<?php echo $_POST['customer_phone'] ?? ''; ?>" 
                                           placeholder="+961 71 234 567">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Shipping Address *</label>
                                    <textarea name="customer_address" class="form-input" 
                                              required rows="3" placeholder="Street, City, Lebanon"><?php echo $_POST['customer_address'] ?? ''; ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Payment Method -->
                            <div class="checkout-form-section animate-fade" style="animation-delay: 0.2s;">
                                <div class="section-title">
                                    <i class="fas fa-credit-card"></i>
                                    Payment Method
                                </div>
                                
                            <div class="payment-methods">

    <!-- Credit Card -->
    <label class="payment-option">
        <input type="radio" name="payment_method" value="credit_card"
            <?php echo ($_POST['payment_method'] ?? '') === 'credit_card' ? 'checked' : ''; ?>>

        <div class="payment-content">
            <div class="payment-icon">
                <i class="far fa-credit-card"></i>
            </div>
            <div class="payment-title">Credit Card</div>
            <div class="payment-desc">Visa, MasterCard</div>
        </div>
    </label>

    <!-- Cash -->
    <label class="payment-option">
        <input type="radio" name="payment_method" value="cash"
            <?php echo ($_POST['payment_method'] ?? '') === 'cash' ? 'checked' : ''; ?>>

        <div class="payment-content">
            <div class="payment-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="payment-title">Cash on Delivery</div>
            <div class="payment-desc">Pay when received</div>
        </div>
    </label>

    <!-- PayPal -->
    <label class="payment-option">
        <input type="radio" name="payment_method" value="paypal"
            <?php echo ($_POST['payment_method'] ?? '') === 'paypal' ? 'checked' : ''; ?>>

        <div class="payment-content">
            <div class="payment-icon">
                <i class="fab fa-paypal"></i>
            </div>
            <div class="payment-title">PayPal</div>
            <div class="payment-desc">Secure online payment</div>
        </div>
    </label>

</div>

                                
                                <!-- Credit Card Form (Shows when selected) -->
                                <div id="credit-card-form" style="display: none; margin-top: 24px;">
                                    <div class="form-group">
                                        <label class="form-label">Card Number</label>
                                        <input type="text" class="form-input" placeholder="1234 5678 9012 3456" maxlength="19">
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                        <div class="form-group">
                                            <label class="form-label">Expiry Date</label>
                                            <input type="text" class="form-input" placeholder="MM/YY">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">CVV</label>
                                            <input type="text" class="form-input" placeholder="123" maxlength="3">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Cardholder Name</label>
                                        <input type="text" class="form-input" placeholder="JOHN SMITH">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Order Button -->
                            <div class="checkout-form-section animate-fade" style="animation-delay: 0.3s; text-align: center;">
                                <div style="display: flex; gap: 16px; justify-content: center;">
                                    <a href="cart.php" class="btn btn-secondary" style="padding: 16px 32px;">
                                        <i class="fas fa-arrow-left"></i> Back to Cart
                                    </a>
                                    <button type="submit" name="complete_order" class="btn btn-success" style="padding: 16px 48px;">
                                        <i class="fas fa-lock"></i> Complete Order
                                    </button>
                                </div>
                                
                                <p style="margin-top: 24px; color: var(--gray); font-size: 14px;">
                                    <i class="fas fa-shield-alt"></i>
                                    Your payment is secure and encrypted
                                </p>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Right Column - Order Summary -->
                    <div class="checkout-summary animate-fade" style="animation-delay: 0.2s;">
                        <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 24px;">Order Summary</h3>
                        
                        <!-- Cart Items -->
                        <div style="max-height: 300px; overflow-y: auto; margin-bottom: 24px;">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="cart-item-mini">
                                    <img src="assets/images/products/<?php echo htmlspecialchars($item['product']['ImageURL']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product']['Name']); ?>" 
                                         class="cart-item-image">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; margin-bottom: 4px;">
                                            <?php echo htmlspecialchars($item['product']['Name']); ?>
                                        </div>
                                        <div style="font-size: 14px; color: var(--gray); margin-bottom: 4px;">
                                            Qty: <?php echo $item['quantity']; ?>
                                        </div>
                                        <div style="font-weight: 600; color: var(--primary);">
                                            <?php echo formatPrice($item['product']['Price']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Order Totals -->
                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        
                        <div class="summary-item">
                            <span>Shipping</span>
                            <span style="color: <?php echo $shipping == 0 ? 'var(--success)' : 'var(--dark)'; ?>;">
                                <?php echo $shipping == 0 ? 'FREE' : formatPrice($shipping); ?>
                            </span>
                        </div>
                        
                        <div class="summary-item">
                            <span>Tax (11%)</span>
                            <span><?php echo formatPrice($tax); ?></span>
                        </div>
                        
                        <div class="summary-divider"></div>
                        
                        <div class="summary-item">
                            <span style="font-weight: 600;">Total</span>
                            <span class="summary-total"><?php echo formatPrice($total); ?></span>
                        </div>
                        
                        <!-- Promo Code -->
                        <div style="margin-top: 24px;">
                            <div style="display: flex; gap: 8px;">
                                <input type="text" placeholder="Promo code" 
                                       class="form-input" style="flex: 1;">
                                <button type="button" class="btn" style="padding: 12px 16px;">
                                    Apply
                                </button>
                            </div>
                        </div>
                        
                        <!-- Shipping Info -->
                        <div style="margin-top: 32px; padding: 16px; background: #f0f9ff; border-radius: 8px;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                <i class="fas fa-truck" style="color: var(--primary);"></i>
                                <span style="font-weight: 600;">Shipping Estimate</span>
                            </div>
                            <p style="font-size: 14px; color: var(--gray);">
                                <?php if ($shipping == 0): ?>
                                    🎉 You've qualified for free shipping!
                                <?php else: ?>
                                    Add <?php echo formatPrice(100 - $subtotal); ?> more to get free shipping
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Success Page -->
                <div class="success-container animate-fade">
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    
                    <h1 style="font-size: 36px; font-weight: 700; margin-bottom: 16px; color: var(--success);">
                        Order Confirmed!
                    </h1>
                    
                    <p style="font-size: 18px; color: var(--gray); margin-bottom: 32px;">
                        Thank you for your purchase, <?php echo htmlspecialchars($orderDetails['customer_name']); ?>!
                        Your order has been successfully placed.
                    </p>
                    
                    <div class="order-details">
                        <div class="detail-row">
                            <span>Order Number</span>
                            <span style="font-weight: 600;">#<?php echo $orderDetails['order_id']; ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Order Date</span>
                            <span><?php echo $orderDetails['order_date']; ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Payment Method</span>
                            <span>
                                <?php 
                                $paymentIcons = [
                                    'credit_card' => '<i class="far fa-credit-card"></i> Credit Card',
                                    'cash' => '<i class="fas fa-money-bill-wave"></i> Cash on Delivery',
                                    'paypal' => '<i class="fab fa-paypal"></i> PayPal'
                                ];
                                echo $paymentIcons[$orderDetails['payment_method']] ?? $orderDetails['payment_method'];
                                ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span>Email</span>
                            <span><?php echo htmlspecialchars($orderDetails['customer_email']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span>Total Amount</span>
                            <span style="font-size: 20px; font-weight: 700; color: var(--primary);">
                                <?php echo formatPrice($orderDetails['total_amount']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div style="background: #d1fae5; border-radius: 8px; padding: 20px; margin: 32px 0;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                            <i class="fas fa-envelope" style="color: var(--success);"></i>
                            <span style="font-weight: 600;">What's Next?</span>
                        </div>
                        <p style="color: var(--gray);">
                            We've sent an order confirmation to <?php echo htmlspecialchars($orderDetails['customer_email']); ?>.
                            You'll receive shipping updates soon.
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 16px; justify-content: center; margin-top: 32px;">
                        <a href="index.php" class="btn" style="padding: 16px 32px;">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                        <a href="dashboard.php" class="btn btn-secondary" style="padding: 16px 32px;">
                            <i class="fas fa-chart-line"></i> View Dashboard
                        </a>
                        <button onclick="printReceipt()" class="btn" style="padding: 16px 32px;">
                            <i class="fas fa-print"></i> Print Receipt
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer style="background: var(--dark); color: white; padding: 40px 0 20px;">
        <div class="container">
            <div style="text-align: center; color: #9ca3af;">
                <p>&copy; <?php echo date('Y'); ?> TechStore. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Payment method selection
      function selectPayment(element, method) {
    
    document.querySelectorAll('.payment-option').forEach(option => {
        option.classList.remove('selected');
    });


    element.classList.add('selected');

    element.querySelector('input[type="radio"]').checked = true;

    // show / hide credit card form
    const cardForm = document.getElementById('credit-card-form');
    cardForm.style.display = method === 'credit_card' ? 'block' : 'none';
}

        
        // Initialize payment selection
     document.addEventListener('DOMContentLoaded', function () {
    const checkedRadio = document.querySelector('input[name="payment_method"]:checked');

    if (checkedRadio) {
        const option = checkedRadio.closest('.payment-option');
        option.classList.add('selected');

        // show / hide credit card form
        const cardForm = document.getElementById('credit-card-form');
        cardForm.style.display = checkedRadio.value === 'credit_card' ? 'block' : 'none';
    }
});

            // Card number formatting
            const cardInput = document.querySelector('input[placeholder="1234 5678 9012 3456"]');
            if (cardInput) {
                cardInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    value = value.replace(/(\d{4})/g, '$1 ').trim();
                    e.target.value = value.substring(0, 19);
                });
            }
            
            // Expiry date formatting
            const expiryInput = document.querySelector('input[placeholder="MM/YY"]');
            if (expiryInput) {
                expiryInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length >= 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                    e.target.value = value.substring(0, 5);
                });
            }
        
        // Form validation
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                e.preventDefault();
                alert('Please select a payment method');
                return false;
            }
            
            if (paymentMethod.value === 'credit_card') {
                const cardNumber = document.querySelector('input[placeholder="1234 5678 9012 3456"]');
                const expiry = document.querySelector('input[placeholder="MM/YY"]');
                const cvv = document.querySelector('input[placeholder="123"]');
                const cardName = document.querySelector('input[placeholder="JOHN SMITH"]');
                
                if (!cardNumber.value || !expiry.value || !cvv.value || !cardName.value) {
                    e.preventDefault();
                    alert('Please fill in all credit card details');
                    return false;
                }
            }
        });
        
        // Print receipt function
        function printReceipt() {
            const receiptContent = `
                <div style="padding: 40px; font-family: Arial, sans-serif;">
                    <h1 style="text-align: center; color: #333;">TechStore Receipt</h1>
                    <hr>
                    <p><strong>Order #:</strong> <?php echo $orderDetails['order_id']; ?></p>
                    <p><strong>Date:</strong> <?php echo $orderDetails['order_date']; ?></p>
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($orderDetails['customer_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($orderDetails['customer_email']); ?></p>
                    <p><strong>Payment:</strong> <?php echo $orderDetails['payment_method']; ?></p>
                    <hr>
                    <h3>Order Summary</h3>
                    <?php foreach ($cartItems as $item): ?>
                    <p><?php echo $item['quantity']; ?> x <?php echo htmlspecialchars($item['product']['Name']); ?> - <?php echo formatPrice($item['total']); ?></p>
                    <?php endforeach; ?>
                    <hr>
                    <h3>Total: <?php echo formatPrice($orderDetails['total_amount']); ?></h3>
                    <p style="text-align: center; margin-top: 40px;">Thank you for shopping with us!</p>
                </div>
            `;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(receiptContent);
            printWindow.document.close();
            printWindow.print();
        }
        
        // Create confetti effect for success page
        <?php if ($success): ?>
        document.addEventListener('DOMContentLoaded', function() {
            createConfetti();
        });
        
        function createConfetti() {
            const colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
            
            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.width = Math.random() * 10 + 5 + 'px';
                confetti.style.height = confetti.style.width;
                confetti.style.animationDelay = Math.random() * 5 + 's';
                confetti.style.animationDuration = Math.random() * 3 + 3 + 's';
                document.body.appendChild(confetti);
                
                setTimeout(() => {
                    confetti.remove();
                }, 8000);
            }
        }
        <?php endif; ?>
    </script>
</body>
</html>