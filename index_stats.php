<?php
// Today Sales
$stmt = $pdo->query("SELECT SUM(final_amount) FROM sales WHERE DATE(created_at) = CURDATE()");
$today_sales = $stmt->fetchColumn() ?: 0;

// All Sales Amount
$stmt = $pdo->query("SELECT SUM(final_amount) FROM sales");
$all_sales_amount = $stmt->fetchColumn() ?: 0;

// Total Orders
$stmt = $pdo->query("SELECT COUNT(*) FROM sales");
$total_orders = $stmt->fetchColumn() ?: 0;

// Total Products
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$total_products = $stmt->fetchColumn() ?: 0;
?>
