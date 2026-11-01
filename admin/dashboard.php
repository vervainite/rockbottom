<?php
declare(strict_types=1);
require __DIR__ . '/_auth.php';

$activeProducts = rb_active_product_count($pdo);
$customerCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$orderCount = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$revenue = (float) $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status <> 'Cancelled'")->fetchColumn();
$pendingCount = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();
$unreadMessages = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();

$recentOrders = $pdo->query(
    "SELECT o.*, u.name AS customer,
       (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.order_id = o.id) AS units
     FROM orders o JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC, o.id DESC LIMIT 7"
)->fetchAll();

$lowStock = $pdo->query(
    'SELECT id, name, stock, image FROM products WHERE active = 1 AND stock <= 3 ORDER BY stock ASC, name ASC LIMIT 6'
)->fetchAll();

$newCustomers = $pdo->query(
    "SELECT name, email, created_at FROM users WHERE role = 'customer' ORDER BY created_at DESC, id DESC LIMIT 4"
)->fetchAll();

$greeting = (int) date('G') < 12 ? 'Good morning' : ((int) date('G') < 18 ? 'Good afternoon' : 'Good evening');
$admin_title = 'Dashboard';
$adminActive = 'dashboard';
require __DIR__ . '/_header.php';
show_flash();
?>
<div class="admin-head">
  <div>
    <p class="eyebrow">The view from below</p>
    <h1><?= e($greeting) ?>, <em><?= e(explode(' ', (string) $adminUser['name'])[0]) ?>.</em></h1>
    <p>Here is what is happening in the little world today.</p>
  </div>
  <a class="btn btn-plum" href="<?= e(url('admin/add_product.php')) ?>">Add a treasure <span aria-hidden="true">↗</span></a>
</div>

<div class="admin-metrics">
  <article class="admin-metric admin-metric--dark">
    <span class="admin-metric__label">Active products</span>
    <strong><?= $activeProducts ?><small>/<?= RB_MAX_ACTIVE_PRODUCTS ?></small></strong>
    <div class="admin-meter" aria-hidden="true"><span style="width: <?= (int) round($activeProducts / RB_MAX_ACTIVE_PRODUCTS * 100) ?>%"></span></div>
    <p><?= RB_MAX_ACTIVE_PRODUCTS - $activeProducts ?> slot<?= RB_MAX_ACTIVE_PRODUCTS - $activeProducts === 1 ? '' : 's' ?> remaining</p>
  </article>
  <article class="admin-metric">
    <span class="admin-metric__label">Customers</span>
    <strong><?= number_format($customerCount) ?></strong>
    <p>Members below the surface</p>
  </article>
  <article class="admin-metric">
    <span class="admin-metric__label">Orders</span>
    <strong><?= number_format($orderCount) ?></strong>
    <p><?= $pendingCount ?> awaiting processing</p>
  </article>
  <article class="admin-metric admin-metric--lime">
    <span class="admin-metric__label">Treasure earned</span>
    <strong><?= money($revenue) ?></strong>
    <p>Excluding cancelled orders</p>
  </article>
</div>

<div class="admin-grid">
  <section class="admin-panel admin-panel--wide">
    <div class="admin-panel__head">
      <div><p class="eyebrow">Latest activity</p><h2>Recent orders</h2></div>
      <a class="text-link" href="<?= e(url('admin/orders.php')) ?>">All orders ↗</a>
    </div>
    <?php if ($recentOrders): ?>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($recentOrders as $order): ?>
              <tr>
                <td><strong><?= e(rb_order_no((int) $order['id'])) ?></strong><small><?= e(rb_date($order['created_at'], 'M j, Y')) ?></small></td>
                <td><?= e($order['customer']) ?></td>
                <td><?= (int) $order['units'] ?></td>
                <td><?= money($order['total']) ?></td>
                <td><span class="status-pill status-pill--<?= e(rb_status_tone((string) $order['status'])) ?>"><?= e($order['status']) ?></span></td>
                <td><a class="table-link" href="<?= e(url('admin/order.php?id=' . (int) $order['id'])) ?>">Manage</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="admin-empty">No orders yet. The first one will appear here.</p>
    <?php endif; ?>
  </section>

  <div class="admin-stack">
    <section class="admin-panel">
      <div class="admin-panel__head">
        <div><p class="eyebrow">Running low</p><h2>Stock watch</h2></div>
        <a class="text-link" href="<?= e(url('admin/products.php')) ?>">Products ↗</a>
      </div>
      <?php if ($lowStock): ?>
        <ul class="stock-watch">
          <?php foreach ($lowStock as $product): ?>
            <li>
              <img src="<?= e(rb_product_image_url($product['image'])) ?>" alt="" width="56" height="44">
              <span><?= e($product['name']) ?></span>
              <strong class="<?= (int) $product['stock'] === 0 ? 'is-out' : '' ?>"><?= (int) $product['stock'] === 0 ? 'Sold out' : (int) $product['stock'] . ' left' ?></strong>
              <a class="table-link" href="<?= e(url('admin/edit_product.php?id=' . (int) $product['id'])) ?>">Edit</a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="admin-empty">Every active treasure is comfortably stocked.</p>
      <?php endif; ?>
    </section>

    <section class="admin-panel">
      <div class="admin-panel__head">
        <div><p class="eyebrow">Inbox</p><h2>Messages <span class="count-badge"><?= $unreadMessages ?></span></h2></div>
        <a class="text-link" href="<?= e(url('admin/messages.php')) ?>">Open ↗</a>
      </div>
      <?php if ($newCustomers): ?>
        <ul class="new-customers">
          <?php foreach ($newCustomers as $customer): ?>
            <li>
              <span class="avatar-mini"><?= e(rb_initials((string) $customer['name'])) ?></span>
              <div><strong><?= e($customer['name']) ?></strong><small><?= e($customer['email']) ?></small></div>
              <time><?= e(rb_date($customer['created_at'], 'M j')) ?></time>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="admin-empty">No customers yet.</p>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
