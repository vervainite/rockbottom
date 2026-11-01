<?php
declare(strict_types=1);
require __DIR__ . '/_auth.php';

$search = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($search) > 80) {
    $search = mb_substr($search, 0, 80);
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;

$where = ["u.role = 'customer'"];
$params = [];
if ($search !== '') {
    $where[] = '(u.name LIKE ? OR u.email LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);

$stmt = $pdo->prepare(
    "SELECT u.id, u.name, u.email, u.phone, u.address, u.created_at,
       COUNT(o.id) AS order_count,
       COALESCE(SUM(CASE WHEN o.status <> 'Cancelled' THEN o.total ELSE 0 END), 0) AS spent,
       MAX(o.created_at) AS last_order
     FROM users u
     LEFT JOIN orders o ON o.user_id = u.id
     $whereSql
     GROUP BY u.id
     ORDER BY u.created_at DESC, u.id DESC
     LIMIT $perPage OFFSET " . (($page - 1) * $perPage)
);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$summary = $pdo->query(
    "SELECT COUNT(*) AS total,
       SUM(CASE WHEN created_at >= (NOW() - INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS recent
     FROM users WHERE role = 'customer'"
)->fetch();

$queryBase = static function (array $overrides = []) use ($search): string {
    $query = array_filter([
        'q' => $overrides['q'] ?? ($search !== '' ? $search : null),
        'page' => $overrides['page'] ?? null,
    ], static fn ($value) => $value !== null && $value !== '');
    return url('admin/customers.php' . ($query ? '?' . http_build_query($query) : ''));
};

$admin_title = 'Customers';
$adminActive = 'customers';
require __DIR__ . '/_header.php';
show_flash();
?>
<div class="admin-head">
  <div>
    <p class="eyebrow">Below the surface</p>
    <h1>THE <em>COLLECTORS.</em></h1>
    <p><?= number_format((int) $summary['total']) ?> member<?= (int) $summary['total'] === 1 ? '' : 's' ?> · <?= number_format((int) $summary['recent']) ?> joined in the last 30 days</p>
  </div>
  <form method="get" action="<?= e(url('admin/customers.php')) ?>" class="admin-search" role="search">
    <label class="sr-only" for="customer-search">Search customers</label>
    <input type="search" id="customer-search" name="q" value="<?= e($search) ?>" placeholder="Name or email">
    <button class="btn btn-outline-plum" type="submit">Search</button>
  </form>
</div>

<section class="admin-panel">
  <?php if ($customers): ?>
    <div class="table-wrap">
      <table class="admin-table admin-table--customers">
        <thead>
          <tr><th>Customer</th><th>Contact</th><th>Orders</th><th>Spent</th><th>Last order</th><th>Joined</th></tr>
        </thead>
        <tbody>
          <?php foreach ($customers as $customer): ?>
            <tr>
              <td>
                <div class="table-customer">
                  <span class="avatar-mini"><?= e(rb_initials((string) $customer['name'])) ?></span>
                  <div><strong><?= e($customer['name']) ?></strong><small>#<?= (int) $customer['id'] ?></small></div>
                </div>
              </td>
              <td>
                <a class="table-sub" href="mailto:<?= e($customer['email']) ?>"><?= e($customer['email']) ?></a>
                <small><?= e($customer['phone'] ?? 'No phone saved') ?></small>
              </td>
              <td><?= (int) $customer['order_count'] ?></td>
              <td><?= money($customer['spent']) ?></td>
              <td><?= $customer['last_order'] ? e(rb_date($customer['last_order'], 'M j, Y')) : '—' ?></td>
              <td><?= e(rb_date($customer['created_at'], 'M j, Y')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
      <nav class="admin-pagination" aria-label="Customer pages">
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
      <p><?= $search !== '' ? 'No customers match “' . e($search) . '”.' : 'No customers have registered yet.' ?></p>
      <?php if ($search !== ''): ?><a class="text-link" href="<?= e(url('admin/customers.php')) ?>">Clear search</a><?php endif; ?>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
