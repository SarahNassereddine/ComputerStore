<?php
require_once 'config.php';

// Get statistics with trend
$sql = "SELECT COUNT(*) AS Total FROM Product";
$stmt = executeQuery($conn, $sql);
$totalProducts = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)['Total'];

$sql = "SELECT COUNT(*) AS Total FROM Customer";
$stmt = executeQuery($conn, $sql);
$totalCustomers = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)['Total'];

$sql = "SELECT COUNT(*) AS Total FROM Orders";
$stmt = executeQuery($conn, $sql);
$totalOrders = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)['Total'];

$sql = "SELECT ISNULL(SUM(TotalAmount), 0) AS Total FROM Orders WHERE Status = 'Completed'";
$stmt = executeQuery($conn, $sql);
$totalRevenue = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)['Total'];

// Today's stats
$today = date('Y-m-d');
$sql = "SELECT COUNT(*) as orders, ISNULL(SUM(TotalAmount), 0) as revenue 
        FROM Orders 
        WHERE CONVERT(DATE, OrderDate) = ? AND Status = 'Completed'";
$stmt = executeQuery($conn, $sql, array($today));
$todayStats = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Monthly revenue trend
$sql = "SELECT FORMAT(OrderDate, 'yyyy-MM') as month, 
               SUM(TotalAmount) as revenue,
               COUNT(*) as orders
        FROM Orders 
        WHERE Status = 'Completed'
        GROUP BY FORMAT(OrderDate, 'yyyy-MM')
        ORDER BY month DESC
        OFFSET 0 ROWS FETCH NEXT 6 ROWS ONLY";
$monthlyTrend = executeQuery($conn, $sql);

// Recent orders with details
$sql = "SELECT TOP 5 * FROM vw_OrderSummary 
        ORDER BY OrderDate DESC";
$recentOrders = executeQuery($conn, $sql);

// Low stock products
$sql = "SELECT TOP 5 * FROM vw_LowStockProducts ORDER BY Stock ASC";
$lowStockProducts = executeQuery($conn, $sql);

// Best selling products
$sql = "SELECT TOP 5 p.Name, p.Category, 
               SUM(od.Quantity) as total_sold,
               SUM(od.Quantity * od.UnitPrice) as revenue
        FROM OrderDetail od
        JOIN Product p ON od.ProductID = p.ProductID
        JOIN Orders o ON od.OrderID = o.OrderID
        WHERE o.Status = 'Completed'
        GROUP BY p.ProductID, p.Name, p.Category
        ORDER BY total_sold DESC";
$bestSellers = executeQuery($conn, $sql);

// Customer activity
$sql = "SELECT TOP 5 c.Name, c.Email, 
               COUNT(o.OrderID) as order_count,
               SUM(o.TotalAmount) as total_spent
        FROM Customer c
        LEFT JOIN Orders o ON c.CustomerID = o.CustomerID
        WHERE o.Status = 'Completed'
        GROUP BY c.CustomerID, c.Name, c.Email
        ORDER BY total_spent DESC";
$topCustomers = executeQuery($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TechStore Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 0;
        }
        
        .dashboard-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--radius);
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-message h1 {
            font-size: 32px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .date-display {
            font-size: 14px;
            color: var(--gray);
        }
        
        .quick-actions {
            display: flex;
            gap: 12px;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
            margin-bottom: 32px;
        }
        
        .chart-card {
            background: white;
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow);
        }
        
        .trend-item {
            display: flex;
            justify-content: space-between;
            padding: 16px 0;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .trend-item:last-child {
            border-bottom: none;
        }
        
        .trend-up {
            color: var(--success);
        }
        
        .trend-down {
            color: var(--danger);
        }
        
        .stats-card-small {
            background: white;
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
        }
        
        .stats-card-small:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .stats-icon-small {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .icon-products { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .icon-customers { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .icon-orders { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .icon-revenue { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        
        .stats-content-small h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .stats-content-small p {
            font-size: 14px;
            color: var(--gray);
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }
        
        .empty-chart {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }
        
        .empty-chart i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
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
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-chart-line"></i>
                    Dashboard
                </a>
            </nav>
        </div>
    </header>

    <section class="dashboard">
        <div class="container">
            <!-- Dashboard Header -->
            <div class="dashboard-header animate-fade">
                <div class="welcome-message">
                    <h1>Welcome to Dashboard</h1>
                    <p class="date-display">
                        <i class="far fa-calendar"></i>
                        <?php echo date('l, F j, Y'); ?>
                        • Today's Revenue: <?php echo formatPrice($todayStats['revenue']); ?>
                    </p>
                </div>
                
                <div class="quick-actions">
                    <button class="btn" onclick="window.location.href='add_product.php'">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                    <button class="btn btn-warning" onclick="window.location.href='products_admin.php'">
    <i class="fas fa-edit"></i> Edit Products
</button>


                    <button class="btn btn-success" onclick="window.location.href='orders.php'">
                        <i class="fas fa-shopping-cart"></i> View Orders
                    </button>
                    <button class="btn btn-secondary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="dashboard-grid animate-fade" style="animation-delay: 0.1s;">
                <div class="stats-card-small">
                    <div class="stats-icon-small icon-products">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stats-content-small">
                        <h3><?php echo $totalProducts; ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
                
                <div class="stats-card-small">
                    <div class="stats-icon-small icon-customers">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-content-small">
                        <h3><?php echo $totalCustomers; ?></h3>
                        <p>Total Customers</p>
                    </div>
                </div>
                
                <div class="stats-card-small">
                    <div class="stats-icon-small icon-orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stats-content-small">
                        <h3><?php echo $totalOrders; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                
                <div class="stats-card-small">
                    <div class="stats-icon-small icon-revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stats-content-small">
                        <h3><?php echo formatPrice($totalRevenue); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>
            
            <!-- Charts and Data -->
            <div class="charts-grid animate-fade" style="animation-delay: 0.2s;">
                <!-- Revenue Chart -->
                <div class="chart-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h3 style="font-size: 18px; font-weight: 600;">Monthly Revenue Trend</h3>
                        <select class="filter-select" style="width: auto;">
                            <option>Last 6 Months</option>
                            <option>Last Year</option>
                            <option>All Time</option>
                        </select>
                    </div>
                    
                    <?php if (sqlsrv_has_rows($monthlyTrend)): ?>
                        <div style="height: 300px; position: relative;">
                            <!-- Simple bar chart using divs -->
                            <div style="display: flex; height: 200px; align-items: flex-end; gap: 20px; padding: 20px; border-bottom: 1px solid var(--gray-light);">
                                <?php while ($trend = sqlsrv_fetch_array($monthlyTrend, SQLSRV_FETCH_ASSOC)): 
                                    $height = $trend['revenue'] > 0 ? ($trend['revenue'] / 10000) * 100 : 5;
                                ?>
                                <div style="flex: 1; text-align: center;">
                                    <div style="height: <?php echo min($height, 100); ?>%; background: linear-gradient(to top, var(--primary), var(--primary-dark)); 
                                        border-radius: 4px 4px 0 0; margin: 0 auto; width: 30px; position: relative;">
                                        <div style="position: absolute; top: -25px; left: 50%; transform: translateX(-50%); font-size: 12px; font-weight: 600;">
                                            <?php echo formatPrice($trend['revenue']); ?>
                                        </div>
                                    </div>
                                    <div style="margin-top: 10px; font-size: 12px; color: var(--gray);">
                                        <?php echo date('M', strtotime($trend['month'] . '-01')); ?>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-chart">
                            <i class="fas fa-chart-bar"></i>
                            <p>No revenue data available yet</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Low Stock Alerts -->
                <div class="chart-card">
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-exclamation-triangle" style="color: var(--warning);"></i>
                        Low Stock Alerts
                    </h3>
                    
                    <?php if (sqlsrv_has_rows($lowStockProducts)): ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php while ($product = sqlsrv_fetch_array($lowStockProducts, SQLSRV_FETCH_ASSOC)): ?>
                            <div class="trend-item">
                                <div>
                                    <div style="font-weight: 600; margin-bottom: 4px;">
                                        <?php echo htmlspecialchars($product['Name']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--gray);">
                                        <?php echo htmlspecialchars($product['Category']); ?>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 20px; font-weight: 700; color: <?php echo $product['Stock'] < 3 ? 'var(--danger)' : 'var(--warning)'; ?>;">
                                        <?php echo $product['Stock']; ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--gray);">
                                        in stock
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-chart">
                            <i class="fas fa-check-circle" style="color: var(--success);"></i>
                            <p>All products have sufficient stock</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tables Section -->
            <div class="charts-grid animate-fade" style="animation-delay: 0.3s;">
                <!-- Recent Orders -->
                <div class="chart-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h3 style="font-size: 18px; font-weight: 600;">Recent Orders</h3>
                        <a href="orders.php" style="font-size: 14px; color: var(--primary); text-decoration: none;">
                            View All →
                        </a>
                    </div>
                    
                    <div class="recent-table">
                        <div class="table-row header">
                            <div>Order #</div>
                            <div>Customer</div>
                            <div>Date</div>
                            <div>Amount</div>
                            <div>Status</div>
                        </div>
                        
                        <?php while ($order = sqlsrv_fetch_array($recentOrders, SQLSRV_FETCH_ASSOC)): ?>
                        <div class="table-row">
                            <div style="font-weight: 600;">#<?php echo $order['OrderID']; ?></div>
                            <div>
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($order['CustomerName']); ?></div>
                                <div style="font-size: 12px; color: var(--gray);"><?php echo $order['CustomerEmail']; ?></div>
                            </div>
                            <div><?php echo $order['OrderDate']->format('M d'); ?></div>
                            <div style="font-weight: 600;"><?php echo formatPrice($order['TotalAmount']); ?></div>
                            <div>
                                <span class="status-badge status-<?php echo strtolower($order['Status']); ?>">
                                    <?php echo $order['Status']; ?>
                                </span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <!-- Best Sellers -->
                <div class="chart-card">
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 24px;">Top Products</h3>
                    
                    <?php if (sqlsrv_has_rows($bestSellers)): ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php while ($product = sqlsrv_fetch_array($bestSellers, SQLSRV_FETCH_ASSOC)): ?>
                            <div class="trend-item">
                                <div>
                                    <div style="font-weight: 600; margin-bottom: 4px;">
                                        <?php echo htmlspecialchars($product['Name']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--gray);">
                                        <?php echo htmlspecialchars($product['Category']); ?>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 20px; font-weight: 700; color: var(--success);">
                                        <?php echo $product['total_sold']; ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--gray);">
                                        sold
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-chart">
                            <i class="fas fa-star"></i>
                            <p>No sales data available yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            


    <footer style="background: var(--dark); color: white; padding: 20px 0;">
        <div class="container">
            <div style="text-align: center; color: #9ca3af; font-size: 14px;">
                <p>Dashboard updated at <?php echo date('H:i:s'); ?> • 
                   <span id="liveTime"></span>
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Live time update
        function updateLiveTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour12: true, 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('liveTime').textContent = timeString;
        }
        
        setInterval(updateLiveTime, 1000);
        updateLiveTime();
        
        // Auto-refresh dashboard every 2 minutes
        setTimeout(() => {
            window.location.reload();
        }, 120000);
        
        function refreshDashboard() {
            window.location.reload();
        }
        
        // Animate stats cards on load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stats-card-small, .chart-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${0.1 + (index * 0.05)}s`;
            });
        });
    </script>
</body>
</html>