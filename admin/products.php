<?php
declare(strict_types=1);
require __DIR__ . '/_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'archive') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    if ($productId > 0) {
        $stmt = $pdo->prepare('UPDATE products SET active = 0 WHERE id = ?');
        $stmt->execute([$productId]);
        flash('success', 'Product archived. It is hidden from the storefront and can be restored by editing it.');
    }
    go(url('admin/products.php'));
}

$filter = $_GET['status'] ?? 'all';
if (!in_array($filter, ['all', 'active', 'archived'], true)) {
    $filter = 'all';
}
$where = match ($filter) {
    'active'   => 'WHERE p.active = 1',
    'archived' => 'WHERE p.active = 0',
    default    => '',
};
$rows = $pdo->query(
    "SELECT p.*, c.name AS category
     FROM products p LEFT JOIN categories c ON c.id = p.category_id
     $where
     ORDER BY p.active DESC, p.created_at DESC, p.id DESC"
)->fetchAll();

$activeCount = rb_active_product_count($pdo);
$totalCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$archivedCount = $totalCount - $activeCount;

$admin_title = 'Products';
$adminActive = 'products';
require __DIR__ . '/_header.php';
show_flash();
?>
<div class="admin-head">
  <div>
    <p class="eyebrow">Catalog management</p>
    <h1>THE <em>COLLECTION.</em></h1>
    <p><?= $activeCount ?> of <?= RB_MAX_ACTIVE_PRODUCTS ?> active slots used · <?= $archivedCount ?> archived</p>
  </div>
  <a class="btn btn-plum" href="<?= e(url('admin/add_product.php')) ?>">Add product <span aria-hidden="true">↗</span></a>
</div>

<?php if ($activeCount >= RB_MAX_ACTIVE_PRODUCTS): ?>
  <div class="admin-note admin-note--lime">
    <span aria-hidden="true">✦</span>
    <p>The storefront is full at <?= RB_MAX_ACTIVE_PRODUCTS ?> active products. New products can be saved as archived, or you can archive an existing one first.</p>
  </div>
<?php endif; ?>

<div class="admin-toolbar">
  <nav class="admin-tabs" aria-label="Filter products">
    <a class="<?= $filter === 'all' ? 'is-active' : '' ?>" href="<?= e(url('admin/products.php')) ?>">All <span><?= $totalCount ?></span></a>
    <a class="<?= $filter === 'active' ? 'is-active' : '' ?>" href="<?= e(url('admin/products.php?status=active')) ?>">Active <span><?= $activeCount ?></span></a>
    <a class="<?= $filter === 'archived' ? 'is-active' : '' ?>" href="<?= e(url('admin/products.php?status=archived')) ?>">Archived <span><?= $archivedCount ?></span></a>
  </nav>
  <a class="text-link" href="<?= e(url('admin/categories.php')) ?>">Manage categories ↗</a>
</div>

<section class="admin-panel">
  <?php if ($rows): ?>
    <div class="table-wrap">
      <table class="admin-table admin-table--products">
        <thead>
          <tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th class="align-end">Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td>
                <div class="table-product">
                  <img src="<?= e(rb_product_image_url($row['image'])) ?>" alt="" width="72" height="56">
                  <div><strong><?= e($row['name']) ?></strong><small>/<?= e($row['slug']) ?></small></div>
                </div>
              </td>
              <td><?= e($row['category'] ?? 'Uncategorised') ?></td>
              <td><?= money($row['price']) ?></td>
              <td><span class="<?= (int) $row['stock'] === 0 ? 'stock-out' : ((int) $row['stock'] <= 3 ? 'stock-low' : '') ?>"><?= (int) $row['stock'] ?></span></td>
              <td><span class="status-pill <?= $row['active'] ? 'status-pill--lime' : 'status-pill--muted' ?>"><?= $row['active'] ? 'Active' : 'Archived' ?></span></td>
              <td class="align-end">
                <div class="table-actions">
                  <a class="table-link" href="<?= e(url('admin/edit_product.php?id=' . (int) $row['id'])) ?>">Edit</a>
                  <?php if ($row['active']): ?>
                    <form method="post">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="archive">
                      <input type="hidden" name="product_id" value="<?= (int) $row['id'] ?>">
                      <button class="table-link table-link--danger" type="submit" data-confirm="Archive “<?= e($row['name']) ?>”? It will disappear from the storefront.">Archive</button>
                    </form>
                  <?php else: ?>
                    <a class="table-link" href="<?= e(url('admin/edit_product.php?id=' . (int) $row['id'])) ?>">Restore</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="admin-empty">No products match this filter.</p>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
