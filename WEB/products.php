<?php
require_once 'config.php';

// Get filter parameters
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$in_stock = isset($_GET['in_stock']) ? true : false;

// Build query
$sql = "SELECT p.*, s.Name AS SupplierName FROM Product p 
        LEFT JOIN Supplier s ON p.SupplierID = s.SupplierID 
        WHERE 1=1";
$params = array();

// Apply filters
if ($category) {
    $sql .= " AND p.Category = ?";
    $params[] = $category;
}

if ($search) {
    $sql .= " AND (p.Name LIKE ? OR p.Description LIKE ? OR s.Name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($min_price !== '') {
    $sql .= " AND p.Price >= ?";
    $params[] = $min_price;
}

if ($max_price !== '') {
    $sql .= " AND p.Price <= ?";
    $params[] = $max_price;
}

if ($in_stock) {
    $sql .= " AND p.Stock > 0";
}

// Apply sorting
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY p.Price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY p.Price DESC";
        break;
    case 'name':
        $sql .= " ORDER BY p.Name ASC";
        break;
    case 'stock':
        $sql .= " ORDER BY p.Stock DESC";
        break;
    default:
        $sql .= " ORDER BY p.CreatedAt DESC";
}

$stmt = executeQuery($conn, $sql, $params);

// Get total products count for display
$countSql = str_replace("p.*, s.Name AS SupplierName", "COUNT(*) as total", $sql);
$countSql = preg_replace('/ORDER BY.*$/', '', $countSql);
$countStmt = executeQuery($conn, $countSql, $params);
$totalProducts = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC)['total'];

// Get filter data
$sqlCategories = "SELECT Category, COUNT(*) as count FROM Product GROUP BY Category ORDER BY Category";
$categoriesStmt = executeQuery($conn, $sqlCategories);

$sqlPriceRange = "SELECT MIN(Price) as min_price, MAX(Price) as max_price FROM Product";
$priceStmt = executeQuery($conn, $sqlPriceRange);
$priceRange = sqlsrv_fetch_array($priceStmt, SQLSRV_FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - TechStore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .products-header {
            background: white;
            border-radius: var(--radius);
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .products-count {
            font-size: 14px;
            color: var(--gray);
        }
        
        .sort-select {
            padding: 10px 16px;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            background: white;
            font-size: 14px;
            min-width: 200px;
        }
        
        .filter-toggle {
            display: none;
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .filter-toggle {
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            
            .filter-panel {
                display: none;
            }
            
            .filter-panel.active {
                display: block;
                animation: slideIn 0.3s ease-out;
            }
        }
        
        .filter-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        
        .product-stock {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }
        
        .stock-available { color: var(--success); }
        .stock-low { color: var(--warning); }
        .stock-out { color: var(--danger); }
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
                <a href="products.php" class="nav-link active">
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

    <section class="products-section">
        <div class="container">
            
            <button class="filter-toggle" onclick="toggleFilter()">
                <i class="fas fa-filter"></i> Filter Products
            </button>
            
            <div class="product-layout" style="display: grid; grid-template-columns: 300px 1fr; gap: 32px;">
                <!-- Filter Panel -->
                <div class="filter-panel" id="filterPanel">
                    <form method="GET" action="" id="filter-form">
                        <div class="filter-group">
                            <label class="filter-label">Search</label>
                            <input type="text" name="search" class="filter-input" 
                                   placeholder="Search products..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Category</label>
                         <!-- In the filter form section, update the category select -->
<select name="category" class="filter-select">
    <option value="">All Computer Categories</option>
    <?php 
    // Reset and fetch categories again
    sqlsrv_free_stmt($categoriesStmt);
    $sqlCategories = "SELECT Category, COUNT(*) as count FROM Product 
                     WHERE Category IN ('Laptops', 'Desktops', 'Monitors', 'Components', 'Peripherals', 'Storage', 'Networking', 'Software', 'Accessories') 
                     GROUP BY Category ORDER BY Category";
    $categoriesStmt = executeQuery($conn, $sqlCategories);
    
    while ($cat = sqlsrv_fetch_array($categoriesStmt, SQLSRV_FETCH_ASSOC)): 
    ?>
        <option value="<?php echo htmlspecialchars($cat['Category']); ?>" 
            <?php echo ($category == $cat['Category']) ? 'selected' : ''; ?>>
            <?php 
            $icon = $computerCategories[$cat['Category']] ?? '💻';
            echo $icon . ' ' . htmlspecialchars($cat['Category']); 
            ?> (<?php echo $cat['count']; ?>)
        </option>
    <?php endwhile; ?>
</select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Price Range</label>
                            <div class="price-range-inputs" style="display: flex; gap: 12px; margin-top: 12px;">
                                <input type="number" name="min_price" class="filter-input" 
                                       placeholder="Min" value="<?php echo htmlspecialchars($min_price); ?>"
                                       min="0" max="<?php echo $priceRange['max_price']; ?>">
                                <input type="number" name="max_price" class="filter-input" 
                                       placeholder="Max" value="<?php echo htmlspecialchars($max_price); ?>"
                                       min="0" max="<?php echo $priceRange['max_price']; ?>">
                            </div>
                            <div class="price-range">
                                <span>$<?php echo number_format($priceRange['min_price'] ?? 0, 0); ?></span>
                                <span>$<?php echo number_format($priceRange['max_price'] ?? 1000, 0); ?></span>
                            </div>
                        </div>
                        
                        <div class="filter-group">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="in_stock" <?php echo $in_stock ? 'checked' : ''; ?>>
                                <span>Show only in stock</span>
                            </label>
                        </div>
                        
                        <input type="hidden" name="sort" value="<?php echo $sort; ?>">
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            <a href="products.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Products Grid -->
                <div>
                    <?php if ($totalProducts == 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>No products found</h3>
                            <p>Try adjusting your filters or search term</p>
                            <a href="products.php" class="btn" style="margin-top: 20px;">
                                Clear All Filters
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="products-grid">
                            <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): 
                                $stock = $row['Stock'];
                                $stockClass = $stock > 10 ? 'stock-available' : ($stock > 0 ? 'stock-low' : 'stock-out');
                                $stockText = $stock > 10 ? 'In Stock' : ($stock > 0 ? 'Low Stock' : 'Out of Stock');
                            ?>
                            <div class="product-card animate-fade">
                                <?php if ($stock < 5 && $stock > 0): ?>
                                    <div class="product-badge">Low Stock</div>
                                <?php elseif ($stock >= 20): ?>
                                    <div class="product-badge" style="background: var(--accent);">Popular</div>
                                <?php endif; ?>
                               <img 
    src="assets/images/products/<?php echo htmlspecialchars($row['ImageURL']); ?>" 
    alt="<?php echo htmlspecialchars($row['Name']); ?>"
    class="product-image"
/>

                                
                                <div class="product-info">
                                    <div class="product-category"><?php echo htmlspecialchars($row['Category']); ?></div>
                                    <h3 class="product-name"><?php echo htmlspecialchars($row['Name']); ?></h3>
                                    <p class="product-description"><?php echo htmlspecialchars(substr($row['Description'] ?? 'No description', 0, 80)) . '...'; ?></p>
                                    
                                    <div class="product-meta">
                                        <div class="product-stock">
                                            <i class="fas fa-box"></i>
                                            <span class="<?php echo $stockClass; ?>"><?php echo $stockText; ?></span>
                                        </div>
                                        <div class="product-rating">
                                            <i class="fas fa-star"></i>
                                            <span>4.5</span>
                                        </div>
                                    </div>
                                    
                                    <div class="product-price"><?php echo formatPrice($row['Price']); ?></div>
                                    
                                    <div class="product-actions">
                                        <a href="product_detail.php?id=<?php echo $row['ProductID']; ?>" class="btn btn-full">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
        function toggleFilter() {
            const filterPanel = document.getElementById('filterPanel');
            filterPanel.classList.toggle('active');
        }
        
        // Auto-submit sort select
        document.querySelector('.sort-select').addEventListener('change', function() {
            document.getElementById('filter-form').submit();
        });
        
        // Mobile filter toggle
        if (window.innerWidth <= 768) {
            document.getElementById('filterPanel').style.display = 'none';
        }
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('filterPanel').style.display = 'block';
            }
        });
    </script>
</body>
</html>