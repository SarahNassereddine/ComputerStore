<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['order_id'];

    // Delete order (OrderDetail will be deleted automatically)
    $sql = "DELETE FROM Orders WHERE OrderID = ?";
    $stmt = executeQuery($conn, $sql, [$orderId]);

    if ($stmt) {
        header("Location: orders.php?deleted=1");
        exit;
    } else {
        echo "❌ Error deleting order";
    }
}
?>
