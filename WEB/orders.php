<?php
require_once 'config.php';

// Get all orders
$sql = "SELECT * FROM vw_OrderSummary ORDER BY OrderDate DESC";
$stmt = executeQuery($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - TechStore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    Dashboard
                </a>
                <a href="orders.php" class="nav-link active">
                    <i class="fas fa-shopping-cart"></i>
                    Orders
                </a>
            </nav>
        </div>
    </header>

    <section style="background: var(--light); min-height: 100vh; padding: 80px 0;">
        <div class="container">
            <div style="background: white; border-radius: var(--radius); padding: 32px; margin-bottom: 32px; box-shadow: var(--shadow);">
                <h1 style="font-size: 32px; font-weight: 700; margin-bottom: 8px;">All Orders</h1>
                <p style="color: var(--gray);">Manage and track all customer orders</p>
            </div>
            
            <div class="chart-card">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f3f4f6;">
                                <th style="padding: 16px; text-align: left; font-weight: 600;">Order #</th>
                                <th style="padding: 16px; text-align: left; font-weight: 600;">Customer</th>
                                <th style="padding: 16px; text-align: left; font-weight: 600;">Date</th>
                                <th style="padding: 16px; text-align: left; font-weight: 600;">Items</th>
                                <th style="padding: 16px; text-align: left; font-weight: 600;">Amount</th>
                                <th style="padding: 16px; text-align: left; font-weight: 600;">Status</th>
                                <th style="padding: 16px; text-align: left; font-weight: 600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 16px; font-weight: 600;">#<?php echo $order['OrderID']; ?></td>
                                <td style="padding: 16px;">
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($order['CustomerName']); ?></div>
                                    <div style="font-size: 12px; color: var(--gray);"><?php echo $order['CustomerEmail']; ?></div>
                                </td>
                                <td style="padding: 16px;"><?php echo $order['OrderDate']->format('Y-m-d H:i'); ?></td>
                                <td style="padding: 16px;"><?php echo $order['TotalItems']; ?></td>
                                <td style="padding: 16px; font-weight: 600;"><?php echo formatPrice($order['TotalAmount']); ?></td>
                                <td style="padding: 16px;">
                                    <span class="status-badge status-<?php echo strtolower($order['Status']); ?>">
                                        <?php echo $order['Status']; ?>
                                    </span>
                                </td>
                                <td style="padding: 16px;">
                                   <form method="POST" action="delete_order.php" 
      onsubmit="return confirm('Are you sure you want to delete this order?');"
      style="display:inline;">
    <input type="hidden" name="order_id" value="<?php echo $order['OrderID']; ?>">
    <button class="btn btn-danger" style="padding:6px 12px; font-size:12px;">
        <i class="fas fa-trash"></i> Delete
    </button>
</form>

                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Add interactivity to order status
        document.addEventListener('DOMContentLoaded', function() {
            const statusBadges = document.querySelectorAll('.status-badge');
            statusBadges.forEach(badge => {
                badge.addEventListener('click', function() {
                    const orderId = this.closest('tr').querySelector('td:first-child').textContent.replace('#', '');
                    const newStatus = prompt('Update order status:', this.textContent.trim());
                    if (newStatus) {
                        // In a real app, you would send an AJAX request here
                        alert(`Order ${orderId} status updated to: ${newStatus}`);
                    }
                });
            });
        });
    </script>
</body>
</html>