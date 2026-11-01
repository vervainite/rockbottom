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
    $accountActive = 'orders';
    require __DIR__ . '/../includes/header.php';
    require __DIR__ . '/../includes/navbar.php';
    ?>
    <main id="main" class="section account-section">
      <div class="rb-container account-layout">
        <?php require __DIR__ . '/../includes/account-nav.php'; ?>
        <div class="account-main">
          <div class="empty-state empty-state--left">
            <span class="empty-state__star" aria-hidden="true">✦</span>
            <h2>We could not find that order.</h2>
            <p>It may belong to another account, or it may never have existed.</p>
            <a class="btn btn-plum" href="<?= e(url('customer/orders.php')) ?>">Back to your orders</a>
          </div>
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

$page_title = 'Order ' . rb_order_no($orderId) . ' — RockBottom';
$body_class = 'account-page';
$nav_active = 'account';
$accountActive = 'orders';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
show_flash();
?>
<main id="main" class="section account-section">
  <div class="rb-container account-layout">
    <?php require __DIR__ . '/../includes/account-nav.php'; ?>
    <div class="account-main">
      <header class="account-head account-head--row">
        <div>
          <p class="eyebrow">Order <?= e(rb_order_no($orderId)) ?></p>
          <h1>YOUR <em>TREASURE.</em></h1>
          <p>Placed <?= e(rb_date($order['created_at'], 'F j, Y')) ?> · <?= e($order['payment_method']) ?></p>
        </div>
        <span class="status-pill status-pill--<?= e(rb_status_tone((string) $order['status'])) ?>"><?= e($order['status']) ?></span>
      </header>

      <div class="order-detail-grid">
        <section class="panel">
          <div class="panel__head"><h2>Tracking</h2><span>Live status</span></div>
          <?php require __DIR__ . '/../includes/order-timeline.php'; ?>
        </section>

        <section class="panel">
          <div class="panel__head"><h2>Delivery</h2><span>COD</span></div>
          <p class="confirmation-address"><?= nl2br(e($order['shipping_address'])) ?></p>
          <p class="confirmation-address"><?= e($order['contact_phone']) ?></p>
          <?php if (!empty($order['notes'])): ?><p class="order-note">“<?= e($order['notes']) ?>”</p><?php endif; ?>
          <p class="payment-pill"><?= e($payment['method'] ?? 'Cash on Delivery') ?> · <?= e($payment['status'] ?? 'Pending') ?></p>
        </section>
      </div>

      <section class="panel">
        <div class="panel__head"><h2>Items</h2><span><?= count($items) ?> line<?= count($items) === 1 ? '' : 's' ?></span></div>
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

      <div class="order-detail-actions">
        <a class="text-link" href="<?= e(url('customer/orders.php')) ?>">← All orders</a>
        <a class="text-link" href="mailto:hello@rockbottom.ph?subject=<?= rawurlencode('Question about ' . rb_order_no($orderId)) ?>">Ask about this order ↗</a>
      </div>
    </div>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
