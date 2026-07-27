<?php
// Database configuration for MS SQL Server
$serverName = "localhost";
$connectionOptions = array(
    "Database" => "ComputerStore",
    "CharacterSet" => "UTF-8"
);

// Create connection
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Check connection
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Start session for cart
session_start();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}


// Store Information
define('STORE_NAME', 'TechZone Computers');
define('STORE_TAGLINE', 'Your Ultimate Computer Destination');
define('STORE_EMAIL', 'support@techzone.com');
define('STORE_PHONE', '+961 1 234 567');
define('STORE_ADDRESS', 'Beirut Digital District, Lebanon');

// Helper function for queries
function executeQuery($conn, $sql, $params = array()) {
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    return $stmt;
}

// Helper function to format price
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// Helper function to get cart count
function getCartCount() {
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $quantity) {
            $count += $quantity;
        }
    }
    return $count;
}

// Computer categories for easy reference
$computerCategories = [
    'Laptops' => '💻',
    'Desktops' => '🖥️', 
    'Monitors' => '📺',
    'Components' => '⚙️',
    'Peripherals' => '⌨️',
    'Storage' => '💾',
    'Networking' => '📡',
    'Software' => '📀',
    'Accessories' => '🔧'
];
?>