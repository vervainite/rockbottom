<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
header('Cache-Control: no-store');
require_login();

$stmt = $pdo->prepare(
    "SELECT o.*,
       (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS line_count,
       (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.order_id = o.id) AS unit_count
     FROM orders o
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC, o.id DESC"
);
$stmt->execute([(int) user()['id']]);
$orders = $stmt->fetchAll();

$page_title = 'Order history — RockBottom';
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
      <header class="account-head">
        <p class="eyebrow">Your treasure trail</p>
        <h1>ORDER <em>HISTORY.</em></h1>
        <p>Every little thing you have found, and where it is now.</p>
      </header>

      <?php if (!$orders): ?>
        <div class="empty-state empty-state--left">
          <span class="empty-state__star" aria-hidden="true">✦</span>
          <h2>No orders yet.</h2>
          <p>Your first treasure is waiting somewhere below.</p>
          <a class="btn btn-plum" href="<?= e(url('index.php#shop')) ?>">Start exploring</a>
        </div>
      <?php else: ?>
        <div class="order-list">
          <?php foreach ($orders as $order): ?>
            <article class="order-card">
              <div class="order-card__head">
                <div>
                  <p class="mini-label">Order <?= e(rb_order_no((int) $order['id'])) ?></p>
                  <p class="order-card__date"><?= e(rb_date($order['created_at'], 'F j, Y')) ?> · <?= (int) $order['unit_count'] ?> item<?= (int) $order['unit_count'] === 1 ? '' : 's' ?></p>
                </div>
                <span class="status-pill status-pill--<?= e(rb_status_tone((string) $order['status'])) ?>"><?= e($order['status']) ?></span>
              </div>
              <div class="order-card__body">
                <div><span>Total</span><strong><?= money($order['total']) ?></strong></div>
                <div><span>Payment</span><strong><?= e($order['payment_method']) ?></strong></div>
                <div><span>Placed</span><strong><?= e(rb_date($order['created_at'], 'M j')) ?></strong></div>
                <a class="btn btn-outline" href="<?= e(url('customer/order.php?id=' . (int) $order['id'])) ?>">Track order <span aria-hidden="true">↗</span></a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
