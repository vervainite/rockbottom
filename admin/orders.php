<?php
declare(strict_types=1);
require __DIR__ . '/_auth.php';

$status = (string) ($_GET['status'] ?? 'all');
if ($status !== 'all' && !in_array($status, RB_ORDER_STATUSES, true)) {
    $status = 'all';
}
$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 80) {
    $search = mb_substr($search, 0, 80);
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;

$where = [];
$params = [];
if ($status !== 'all') {
    $where[] = 'o.status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $like = '%' . $search . '%';
    $parts = ['u.name LIKE ?', 'u.email LIKE ?', 'o.shipping_address LIKE ?'];
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    if (preg_match('/\d+/', $search, $m)) {
        $parts[] = 'o.id = ?';
        $params[] = (int) $m[0];
    }
    $where[] = '(' . implode(' OR ', $parts) . ')';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON u.id = o.user_id $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);

$stmt = $pdo->prepare(
    "SELECT o.*, u.name AS customer, u.email,
       (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.order_id = o.id) AS units,
       (SELECT pay.status FROM payments pay WHERE pay.order_id = o.id ORDER BY pay.id DESC LIMIT 1) AS payment_status
     FROM orders o JOIN users u ON u.id = o.user_id
     $whereSql
     ORDER BY o.created_at DESC, o.id DESC
     LIMIT $perPage OFFSET " . (($page - 1) * $perPage)
);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statusCounts = ['all' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn()];
foreach (RB_ORDER_STATUSES as $s) {
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE status = ?');
    $countStmt->execute([$s]);
    $statusCounts[$s] = (int) $countStmt->fetchColumn();
}

$pendingCount = $statusCounts['Pending'];
$admin_title = 'Orders';
$adminActive = 'orders';
require __DIR__ . '/_header.php';
show_flash();

$queryBase = static function (array $overrides = []) use ($status, $search): string {
    $query = array_filter([
        'status' => $overrides['status'] ?? ($status !== 'all' ? $status : null),
        'q' => $overrides['q'] ?? ($search !== '' ? $search : null),
        'page' => $overrides['page'] ?? null,
    ], static fn ($value) => $value !== null && $value !== '');
    return url('admin/orders.php' . ($query ? '?' . http_build_query($query) : ''));
};
?>
<div class="admin-head">
  <div>
    <p class="eyebrow">Fulfilment</p>
    <h1>THE <em>LEDGER.</em></h1>
    <p><?= number_format($total) ?> order<?= $total === 1 ? '' : 's' ?> found<?= $pendingCount > 0 ? ' · ' . $pendingCount . ' awaiting action' : '' ?></p>
  </div>
  <form method="get" action="<?= e(url('admin/orders.php')) ?>" class="admin-search" role="search">
    <?php if ($status !== 'all'): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <label class="sr-only" for="order-search">Search orders</label>
    <input type="search" id="order-search" name="q" value="<?= e($search) ?>" placeholder="Order #, name, email, address">
    <button class="btn btn-outline-plum" type="submit">Search</button>
  </form>
</div>

<div class="admin-toolbar">
  <nav class="admin-tabs" aria-label="Filter orders by status">
    <a class="<?= $status === 'all' ? 'is-active' : '' ?>" href="<?= e($queryBase(['status' => null, 'page' => null])) ?>">All <span><?= $statusCounts['all'] ?></span></a>
    <?php foreach (RB_ORDER_STATUSES as $s): ?>
      <a class="<?= $status === $s ? 'is-active' : '' ?>" href="<?= e($queryBase(['status' => $s, 'page' => null])) ?>"><?= e($s) ?> <span><?= $statusCounts[$s] ?></span></a>
    <?php endforeach; ?>
  </nav>
  <?php if ($search !== '' || $status !== 'all'): ?>
    <a class="text-link" href="<?= e(url('admin/orders.php')) ?>">Reset filters</a>
  <?php endif; ?>
</div>

<section class="admin-panel">
  <?php if ($orders): ?>
    <div class="table-wrap">
      <table class="admin-table admin-table--orders">
        <thead>
          <tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th class="align-end">Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td>
                <strong><?= e(rb_order_no((int) $order['id'])) ?></strong>
                <small><?= e(rb_date($order['created_at'], 'M j, Y · g:i A')) ?></small>
              </td>
              <td>
                <strong><?= e($order['customer']) ?></strong>
                <small><?= e($order['email']) ?></small>
              </td>
              <td><?= (int) $order['units'] ?></td>
              <td><?= money($order['total']) ?></td>
              <td>
                <span class="table-sub"><?= e($order['payment_method']) ?></span>
                <small><span class="status-pill status-pill--<?= e(match ((string) $order['payment_status']) { 'Paid' => 'lime', 'Cancelled' => 'muted', default => 'lavender' }) ?>"><?= e($order['payment_status'] ?? 'Pending') ?></span></small>
              </td>
              <td><span class="status-pill status-pill--<?= e(rb_status_tone((string) $order['status'])) ?>"><?= e($order['status']) ?></span></td>
              <td class="align-end"><a class="table-link" href="<?= e(url('admin/order.php?id=' . (int) $order['id'])) ?>">Open</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
      <nav class="admin-pagination" aria-label="Order pages">
        <?php if ($page > 1): ?>
          <a class="text-link" href="<?= e($queryBase(['page' => $page - 1])) ?>">← Previous</a>
        <?php else: ?>
          <span class="text-link is-disabled">← Previous</span>
        <?php endif; ?>
        <span>Page <?= $page ?> of <?= $pages ?></span>
        <?php if ($page < $pages): ?>
          <a class="text-link" href="<?= e($queryBase(['page' => $page + 1])) ?>">Next →</a>
        <?php else: ?>
          <span class="text-link is-disabled">Next →</span>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php else: ?>
    <div class="admin-empty">
      <p>No orders match this view.</p>
      <?php if ($search !== '' || $status !== 'all'): ?>
        <a class="text-link" href="<?= e(url('admin/orders.php')) ?>">Clear filters</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
