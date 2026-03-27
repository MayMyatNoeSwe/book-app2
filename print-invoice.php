<?php
// print-invoice.php
require_once 'vendor/autoload.php';
require_once 'includes/sessions.php';
require_once 'includes/env_loader.php';
require_once 'includes/functions.php';

use App\Auth;
use App\Cart;

if (!Auth::check()) {
    header("Location: login.php");
    exit;
}

$orderNumber = $_GET['id'] ?? null;
if (!$orderNumber) {
    die("Invalid Order ID");
}

$config = require 'config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

$cart = new Cart($pdo);
$userId = Auth::id();

// Fetch order. If admin, don't restrict by userId
$order = null;
if (isAdmin()) {
    $stmt = $pdo->prepare("SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.order_number = ?");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($order) {
        $stmt = $pdo->prepare("SELECT oi.*, b.title, b.author FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE oi.order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    $order = $cart->getOrderDetails($orderNumber, $userId);
}

if (!$order) {
    die("Order not found or access denied.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - #<?= e($order['order_number']) ?></title>
    <style>
        body { font-family: 'Inter', -apple-system, sans-serif; color: #333; line-height: 1.6; padding: 40px; }
        .invoice-box { max-width: 800px; margin: auto; border: 1px solid #eee; padding: 30px; border-radius: 10px; background: white; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #f8fafc; padding-bottom: 20px; margin-bottom: 30px; }
        .brand { font-size: 24px; font-weight: 800; color: #E07A5F; }
        .order-meta { text-align: right; font-size: 14px; }
        .row { display: flex; gap: 40px; margin-bottom: 30px; }
        .col { flex: 1; }
        .label { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 1px; margin-bottom: 5px; }
        .val { font-size: 14px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { text-align: left; background: #f8fafc; padding: 12px; font-size: 12px; text-transform: uppercase; color: #64748b; }
        td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .totals { margin-left: auto; width: 250px; }
        .totals-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
        .totals-row.grand-total { border-top: 2px solid #E07A5F; margin-top: 10px; padding-top: 15px; font-weight: 800; font-size: 18px; color: #E07A5F; }
        .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 20px; }
        @media print {
            body { padding: 0; }
            .invoice-box { border: none; box-shadow: none; }
            .no-print { display: none; }
        }
        .btn-print { background: #E07A5F; color: white; border: none; padding: 10px 25px; border-radius: 8px; font-weight: 700; cursor: pointer; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center;">
        <button onclick="window.print()" class="btn-print">Print This Invoice</button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <div class="brand"><?= strtoupper(e(getSetting('site_name', 'BOOKHOUSE'))) ?></div>
            <div class="order-meta">
                <div class="val">INVOICE #<?= e($order['order_number']) ?></div>
                <div class="label" style="margin-top:5px;">Date: <?= date('F j, Y', strtotime($order['created_at'])) ?></div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <div class="label">Billed To</div>
                <div class="val"><?= e($order['username'] ?? Auth::user()) ?></div>
                <div class="val" style="font-weight:400;"><?= e($order['email'] ?? '') ?></div>
                <div class="val" style="font-weight:400; margin-top:10px;"><?= nl2br(e($order['shipping_address'])) ?></div>
            </div>
            <div class="col" style="text-align: right;">
                <div class="label">Shipping Details</div>
                <div class="val"><?= e($order['delivery_method']) ?></div>
                <div class="val" style="font-weight:400;">Payment: <?= e($order['payment_method']) ?></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal = 0;
                foreach($order['items'] as $item): 
                    $lineTotal = $item['price'] * $item['quantity'];
                    $subtotal += $lineTotal;
                ?>
                <tr>
                    <td>
                        <div style="font-weight: 700;"><?= e($item['title']) ?></div>
                        <div style="font-size: 11px; color: #64748b;">Author: <?= e($item['author']) ?></div>
                    </td>
                    <td style="text-align: center;"><?= $item['quantity'] ?></td>
                    <td style="text-align: right;"><?= number_format($item['price']) ?> Ks</td>
                    <td style="text-align: right;"><?= number_format($lineTotal) ?> Ks</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <span class="label">Subtotal</span>
                <span class="val"><?= number_format($subtotal) ?> Ks</span>
            </div>
            <div class="totals-row">
                <span class="label">Shipping</span>
                <span class="val"><?= number_format($order['shipping_cost']) ?> Ks</span>
            </div>
            <div class="totals-row grand-total">
                <span>TOTAL</span>
                <span><?= number_format($order['total_amount']) ?> Ks</span>
            </div>
        </div>

        <div class="footer">
            Thank you for your purchase from <?= e(getSetting('site_name', 'BOOKHOUSE')) ?>!<br>
            If you have any questions, please contact <?= e(getSetting('contact_email', 'support@example.com')) ?>
        </div>
    </div>

    <script>
        // Auto-trigger print if requested
        if (window.location.search.indexOf('print=true') > -1) {
            window.print();
        }
    </script>
</body>
</html>
