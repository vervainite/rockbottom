<?php
declare(strict_types=1);
require __DIR__ . '/_auth.php';

$orderId = (int) ($_GET['id'] ?? 0);
if ($orderId < 1) {
    go(url('admin/orders.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $next = (string) ($_POST['status'] ?? '');
    [$ok, $message] = rb_change_order_status($pdo, $orderId, $next);
    flash($ok ? 'success' : 'error', $message);
    go(url('admin/order.php?id=' . $orderId));
}

$stmt = $pdo->prepare(
    'SELECT o.*, u.name AS customer, u.email, u.phone AS customer_phone, u.address AS customer_address
     FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?'
);
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order) {
    flash('error', 'That order does not exist.');
    go(url('admin/orders.php'));
}

$itemStmt = $pdo->prepare(
    'SELECT oi.*, p.image, p.slug FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ? ORDER BY oi.id'
);
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();

$paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1');
$paymentStmt->execute([$orderId]);
$payment = $paymentStmt->fetch();

$units = 0;
foreach ($items as $item) {
    $units += (int) $item['quantity'];
}

$nextStatuses = match ((string) $order['status']) {
    'Pending'    => ['Processing', 'Cancelled'],
    'Processing' => ['Shipped', 'Cancelled'],
    'Shipped'    => ['Delivered'],
    default      => [],
};

$admin_title = 'Order ' . rb_order_no($orderId);
$adminActive = 'orders';
require __DIR__ . '/_header.php';
show_flash();
?>
<div class="admin-head">
  <div>
    <p class="eyebrow"><a class="text-link" href="<?= e(url('admin/orders.php')) ?>">Orders</a> / <?= e(rb_order_no($orderId)) ?></p>
    <h1><?= e(rb_order_no($orderId)) ?><span class="admin-head__status status-pill status-pill--<?= e(rb_status_tone((string) $order['status'])) ?>"><?= e($order['status']) ?></span></h1>
    <p>Placed <?= e(rb_date($order['created_at'], 'F j, Y · g:i A')) ?> by <?= e($order['customer']) ?></p>
  </div>
  <a class="btn btn-outline-plum" href="<?= e(url('admin/orders.php')) ?>">← All orders</a>
</div>

<div class="admin-grid admin-grid--order">
  <div class="admin-stack">
    <section class="admin-panel">
      <div class="admin-panel__head">
        <div><p class="eyebrow">What is inside</p><h2><?= $units ?> treasure<?= $units === 1 ? '' : 's' ?></h2></div>
        <span class="table-sub"><?= count($items) ?> line<?= count($items) === 1 ? '' : 's' ?></span>
      </div>
      <ul class="admin-order-items">
        <?php foreach ($items as $item): ?>
          <li>
            <img src="<?= e(rb_product_image_url($item['image'] ?? '')) ?>" alt="" width="76" height="60">
            <div class="admin-order-items__name">
              <strong><?= e($item['product_name']) ?></strong>
              <small><?= (int) $item['quantity'] ?> × <?= money($item['unit_price']) ?><?= $item['product_id'] === null ? ' · product removed' : '' ?></small>
            </div>
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

    <section class="admin-panel">
      <div class="admin-panel__head">
        <div><p class="eyebrow">Journey</p><h2>Status timeline</h2></div>
      </div>
      <?php require __DIR__ . '/../includes/order-timeline.php'; ?>
    </section>

    <?php if (!empty($order['notes'])): ?>
      <section class="admin-panel">
        <div class="admin-panel__head"><div><p class="eyebrow">From the customer</p><h2>Order note</h2></div></div>
        <p class="order-note">“<?= nl2br(e($order['notes'])) ?>”</p>
      </section>
    <?php endif; ?>
  </div>

  <div class="admin-stack">
    <section class="admin-panel admin-panel--dark">
      <div class="admin-panel__head">
        <div><p class="eyebrow">Next step</p><h2>Move this order</h2></div>
      </div>
      <?php if ($nextStatuses): ?>
        <form method="post" class="admin-status-form">
          <?= csrf_field() ?>
          <div class="admin-status-actions">
            <?php foreach ($nextStatuses as $next): ?>
              <?php if ($next === 'Cancelled'): ?>
                <button class="btn btn-ghost-light" type="submit" name="status" value="Cancelled"
                  data-confirm="Cancel <?= e(rb_order_no($orderId)) ?>? The reserved stock will be returned to the store and the payment marked cancelled.">Cancel order</button>
              <?php else: ?>
                <button class="btn btn-lime" type="submit" name="status" value="<?= e($next) ?>" data-confirm="Mark <?= e(rb_order_no($orderId)) ?> as <?= e($next) ?>?">Mark <?= e($next) ?></button>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <p class="admin-form__hint">
            <?php if ((string) $order['status'] === 'Shipped'): ?>
              Delivering confirms the cash payment as collected.
            <?php elseif ((string) $order['status'] === 'Pending'): ?>
              Processing starts packing. Cancelling restocks every item.
            <?php else: ?>
              Shipped orders can only be marked delivered. Cancelled orders are final.
            <?php endif; ?>
          </p>
        </form>
      <?php else: ?>
        <p class="admin-status-final"><?= (string) $order['status'] === 'Cancelled' ? 'This order was cancelled. Its stock has already been restored.' : 'This order is complete. Nothing left to do.' ?></p>
      <?php endif; ?>
    </section>

    <section class="admin-panel">
      <div class="admin-panel__head"><div><p class="eyebrow">Customer</p><h2>Account</h2></div></div>
      <ul class="admin-kv">
        <li><span>Name</span><strong><?= e($order['customer']) ?></strong></li>
        <li><span>Email</span><a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a></li>
        <li><span>Account phone</span><strong><?= e($order['customer_phone'] ?? '—') ?></strong></li>
        <li><span>Orders</span><a class="text-link" href="<?= e(url('admin/customers.php?q=' . rawurlencode((string) $order['email']))) ?>">View customer ↗</a></li>
      </ul>
    </section>

    <section class="admin-panel">
      <div class="admin-panel__head"><div><p class="eyebrow">Where it goes</p><h2>Delivery</h2></div></div>
      <p class="admin-address"><?= nl2br(e($order['shipping_address'])) ?></p>
      <p class="admin-address"><?= e($order['contact_phone']) ?></p>
    </section>

    <section class="admin-panel">
      <div class="admin-panel__head"><div><p class="eyebrow">Cash on delivery</p><h2>Payment</h2></div></div>
      <ul class="admin-kv">
        <li><span>Method</span><strong><?= e($payment['method'] ?? $order['payment_method']) ?></strong></li>
        <li><span>Amount</span><strong><?= money($payment['amount'] ?? $order['total']) ?></strong></li>
        <li><span>Status</span><span class="status-pill status-pill--<?= e(match ((string) ($payment['status'] ?? 'Pending')) { 'Paid' => 'lime', 'Cancelled' => 'muted', 'Refunded' => 'plum', default => 'lavender' }) ?>"><?= e($payment['status'] ?? 'Pending') ?></span></li>
        <li><span>Reference</span><strong><?= e($payment['reference'] ?? '—') ?></strong></li>
      </ul>
    </section>
  </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
