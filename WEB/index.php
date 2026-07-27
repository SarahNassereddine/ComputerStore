<?php
require_once 'config.php';

// Get featured computer products
$sql = "SELECT TOP 8 p.*, s.Name AS SupplierName FROM Product p 
        LEFT JOIN Supplier s ON p.SupplierID = s.SupplierID 
        WHERE p.Stock > 0 
        ORDER BY NEWID()";
$stmt = executeQuery($conn, $sql);

// Get all computer categories
$sqlCategories = "SELECT Category, COUNT(*) as count FROM Product GROUP BY Category ORDER BY Category";
$categoriesStmt = executeQuery($conn, $sqlCategories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo STORE_NAME; ?> - Premium Computers & Tech</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Main Header -->
    <header class="main-header">
        <div class="container header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-desktop"></i>
                <span><?php echo STORE_NAME; ?></span>
            </a>
            
            <nav class="nav-menu">
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-home"></i>
                    Home
                </a>
                <a href="products.php" class="nav-link">
                    <i class="fas fa-microchip"></i>
                    Products
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
                <a href="#services" class="nav-link">
                    <i class="fas fa-tools"></i>
                    Services
                </a>
            </nav>
        </div>
    </header>
            
            <!-- Featured Products -->
            <div class="section-header" style="text-align: center; margin: 80px 0 40px;">
                <h2 class="section-title"> Our products</h2>
           
            </div>
            <div class='container'>
            <div class="products-grid">
                <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): 
                    $stock = $row['Stock'];
                    $stockClass = $stock > 10 ? 'stock-available' : ($stock > 0 ? 'stock-low' : 'stock-out');
                    $stockText = $stock > 10 ? 'In Stock' : ($stock > 0 ? 'Low Stock' : 'Out of Stock');
                    $icon = $computerCategories[$row['Category']] ?? '💻';
                ?>
                <div class="product-card animate-fade">
                    <?php if ($stock < 5 && $stock > 0): ?>
                        <div class="product-badge">Almost Gone!</div>
                    <?php elseif ($row['Price'] > 1000): ?>
                        <div class="product-badge" style="background: var(--accent);">Premium</div>
                    <?php endif; ?>
                    <?php
$imagePath = "assets/images/products/" . $row['ImageURL'];
if (!file_exists($imagePath) || empty($row['ImageURL'])) {
    $imagePath = "assets/images/products/default.png";
}
?>

<img 
    src="<?php echo $imagePath; ?>" 
    alt="<?php echo htmlspecialchars($row['Name']); ?>" 
    class="product-image">
                    <div class="product-info">
                        <div class="product-category">
                            <?php echo $icon; ?> <?php echo htmlspecialchars($row['Category']); ?>
                        </div>
                        <h3 class="product-name"><?php echo htmlspecialchars($row['Name']); ?></h3>
                        <p class="product-description"><?php echo htmlspecialchars(substr($row['Description'] ?? 'No description', 0, 80)) . '...'; ?></p>
                        
                        <div class="product-meta">
                            <div class="product-stock">
                                <i class="fas fa-box"></i>
                                <span class="<?php echo $stockClass; ?>"><?php echo $stockText; ?></span>
                            </div>
                            <div class="product-rating">
                                <i class="fas fa-star"></i>
                                <span>4.7</span>
                            </div>
                        </div>
                        
                        <div class="product-price"><?php echo formatPrice($row['Price']); ?></div>
                        
                        <div class="product-actions">
                            <a href="product_detail.php?id=<?php echo $row['ProductID']; ?>" class="btn btn-full">
                                <i class="fas fa-eye"></i> View Specs
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <div style="text-align: center; margin-top: 60px;">
                <a href="products.php" class="btn" style="padding: 16px 48px; font-size: 16px;">
                    <i class="fas fa-microchip"></i> View All Computer Products
                </a>
            </div>
        </div>
    </section>
    
    <section class="products-section">
        <div class="container">
            <!-- Categories Section -->
            <div class="section-header" style="text-align: center; margin-bottom: 40px;">
                <h2 class="section-title"> Browse by Category</h2>
                <p class="section-subtitle">Find exactly what you need for your computer setup</p>
            </div>
            
            <div class="category-grid">
                <?php while ($cat = sqlsrv_fetch_array($categoriesStmt, SQLSRV_FETCH_ASSOC)): 
                    $icon = $computerCategories[$cat['Category']] ?? '💻';
                ?>
                <a href="products.php?category=<?php echo urlencode($cat['Category']); ?>" class="category-card">
                    <div class="category-icon"><?php echo $icon; ?></div>
                    <div class="category-name"><?php echo htmlspecialchars($cat['Category']); ?></div>
                    <div class="category-count"><?php echo $cat['count']; ?> items</div>
                </a>
                <?php endwhile; ?>
            </div></section>

    <!-- Services Section -->
    <section id="services"  class="services-section">
        <div class="container">
            <div class="section-header" style="text-align: center; margin-bottom: 60px;">
                <h2 class="section-title">🔧 Our Computer Services</h2>
                <p class="section-subtitle">More than just a store - we're your tech partner</p>
            </div>
            
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3>PC Building</h3>
                    <p>Custom gaming and workstation PC assembly with cable management</p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3>Hardware Repair</h3>
                    <p>Component-level repair for laptops, desktops, and peripherals</p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Warranty Support</h3>
                    <p>Manufacturer warranty claims and extended protection plans</p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Tech Consultation</h3>
                    <p>Expert advice on system upgrades and component selection</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature Banner -->
    <div class="feature-banner">
        <div class="container">
            <h2>Ready to Upgrade Your Setup?</h2>
            <p style="font-size: 18px; margin-bottom: 30px; max-width: 600px; margin-left: auto; margin-right: auto;">
                From gaming rigs to professional workstations, we have everything you need.
            </p>
            <a href="products.php" class="btn" style="background: white; color: var(--primary); padding: 16px 40px;">
                <i class="fas fa-shopping-bag"></i> Start Shopping Now
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer style="background: var(--dark); color: white; padding: 60px 0 20px;">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 40px; margin-bottom: 40px;">
                <div>
                    <h3 style="margin-bottom: 20px; font-size: 20px;">
                        <i class="fas fa-desktop"></i> <?php echo STORE_NAME; ?>
                    </h3>
                    <p style="color: #9ca3af; line-height: 1.7;">
                        Lebanon's premier computer and technology store. 
                        Specializing in gaming PCs, components, and tech solutions.
                    </p>
                </div>
                <div>
                    <h3 style="margin-bottom: 20px; font-size: 20px;">Quick Links</h3>
                    <ul style="list-style: none;">
                        <li style="margin-bottom: 10px;"><a href="products.php?category=Laptops" style="color: #9ca3af; text-decoration: none;"><i class="fas fa-laptop"></i> Laptops</a></li>
                        <li style="margin-bottom: 10px;"><a href="products.php?category=Components" style="color: #9ca3af; text-decoration: none;"><i class="fas fa-microchip"></i> Components</a></li>
                        <li style="margin-bottom: 10px;"><a href="products.php?category=Gaming" style="color: #9ca3af; text-decoration: none;"><i class="fas fa-gamepad"></i> Gaming Gear</a></li>
                        <li style="margin-bottom: 10px;"><a href="products.php?category=Peripherals" style="color: #9ca3af; text-decoration: none;"><i class="fas fa-keyboard"></i> Peripherals</a></li>
                    </ul>
                </div>
                <div>
                    <h3 style="margin-bottom: 20px; font-size: 20px;">Contact Us</h3>
                    <ul style="list-style: none; color: #9ca3af;">
                        <li style="margin-bottom: 10px;">
                            <i class="fas fa-map-marker-alt"></i> <?php echo STORE_ADDRESS; ?>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <i class="fas fa-phone"></i> <?php echo STORE_PHONE; ?>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <i class="fas fa-envelope"></i> <?php echo STORE_EMAIL; ?>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <i class="fas fa-clock"></i> Mon-Sat: 9AM-8PM, Sun: 10AM-6PM
                        </li>
                    </ul>
                </div>
            </div>
            
            <div style="border-top: 1px solid #374151; padding-top: 20px; text-align: center; color: #9ca3af;">
                <p>&copy; <?php echo date('Y'); ?> <?php echo STORE_NAME; ?>. All rights reserved. | Lebanon's Computer Experts</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>