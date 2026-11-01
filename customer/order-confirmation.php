<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
header('Cache-Control: no-store');
require_login();

$orderId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$orderId, (int) user()['id']]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    $page_title = 'Order not found — RockBottom';
    $body_class = 'account-page';
    $nav_active = 'account';
    require __DIR__ . '/../includes/header.php';
    require __DIR__ . '/../includes/navbar.php';
    ?>
    <main id="main" class="section">
      <div class="rb-container">
        <div class="empty-state">
          <span class="empty-state__star" aria-hidden="true">✦</span>
          <h2>This order is not yours to find.</h2>
          <p>We could not locate that order on your account.</p>
          <a class="btn btn-plum" href="<?= e(url('customer/orders.php')) ?>">View your orders</a>
        </div>
      </div>
    </main>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$itemStmt = $pdo->prepare(
    'SELECT oi.*, p.image FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ? ORDER BY oi.id'
);
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();

$paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1');
$paymentStmt->execute([$orderId]);
$payment = $paymentStmt->fetch();

$page_title = 'Order ' . rb_order_no($orderId) . ' received — RockBottom';
$body_class = 'account-page confirmation-page';
$nav_active = 'account';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
show_flash();
?>
<main id="main" class="section confirmation-section">
  <div class="rb-container">
    <div class="confirmation-hero">
      <span class="confirmation-mark" aria-hidden="true">✓</span>
      <p class="eyebrow">Order received</p>
      <h1>THANK YOU FOR<br>DIGGING <em>DEEPER.</em></h1>
      <p>Your treasures are reserved. We will pack them by hand — follow every step from your account.</p>
      <div class="confirmation-ref">
        <span>Order</span><strong><?= e(rb_order_no($orderId)) ?></strong>
        <span>Total</span><strong><?= money($order['total']) ?></strong>
      </div>
    </div>

    <div class="confirmation-layout">
      <section class="panel">
        <div class="panel__head">
          <h2>Your treasures</h2>
          <span><?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?></span>
        </div>
        <ul class="checkout-items">
          <?php foreach ($items as $item): ?>
            <li>
              <img src="<?= e(rb_product_image_url($item['image'] ?? '')) ?>" alt="" width="596" height="464" loading="lazy">
              <div><strong><?= e($item['product_name']) ?></strong><span><?= (int) $item['quantity'] ?> × <?= money($item['unit_price']) ?></span></div>
              <span><?= money($item['line_total']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <dl class="summary-lines">
          <div><dt>Subtotal</dt><dd><?= money($order['subtotal']) ?></dd></div>
          <div><dt>Delivery</dt><dd><?= (float) $order['shipping_fee'] > 0 ? money($order['shipping_fee']) : 'Free' ?></dd></div>
          <div class="summary-lines__total"><dt>Total</dt><dd><?= money($order['total']) ?></dd></div>
        </dl>
      </section>

      <aside class="confirmation-side">
        <section class="panel">
          <div class="panel__head"><h2>Track this order</h2><a class="link-btn" href="<?= e(url('customer/order.php?id=' . $orderId)) ?>">Details ↗</a></div>
          <?php require __DIR__ . '/../includes/order-timeline.php'; ?>
        </section>
        <section class="panel">
          <div class="panel__head"><h2>Delivery</h2><span>COD</span></div>
          <p class="confirmation-address"><?= nl2br(e($order['shipping_address'])) ?></p>
          <p class="confirmation-address"><?= e($order['contact_phone']) ?></p>
          <p class="payment-pill"><?= e($payment['method'] ?? 'Cash on Delivery') ?> · <?= e($payment['status'] ?? 'Pending') ?></p>
        </section>
        <div class="confirmation-actions">
          <a class="btn btn-plum btn-block" href="<?= e(url('customer/orders.php')) ?>">View all orders</a>
          <a class="btn btn-outline btn-block" href="<?= e(url('index.php#shop')) ?>">Keep exploring</a>
        </div>
      </aside>
    </div>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
