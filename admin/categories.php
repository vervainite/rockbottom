<?php
declare(strict_types=1);
require __DIR__ . '/_auth.php';

$errors = [];
$form = ['name' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $form['name'] = trim(rb_post_string('name', 80));
        $form['description'] = trim(rb_post_string('description', 500));
        if ($form['name'] === '') {
            $errors['name'] = 'Give the category a name.';
        } elseif (mb_strlen($form['name']) < 2) {
            $errors['name'] = 'Category names need at least two characters.';
        }
        if ($errors === []) {
            $stmt = $pdo->prepare('INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)');
            $stmt->execute([$form['name'], rb_category_slug($pdo, $form['name']), $form['description'] ?: null]);
            flash('success', '“' . $form['name'] . '” was added to the collection.');
            go(url('admin/categories.php'));
        }
    }

    if ($action === 'rename') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $name = trim(rb_post_string('name', 80));
        $description = trim(rb_post_string('description', 500));
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch();
        if (!$category) {
            flash('error', 'That category no longer exists.');
            go(url('admin/categories.php'));
        }
        if ($name === '' || mb_strlen($name) < 2) {
            flash('error', 'Category names need at least two characters.');
            go(url('admin/categories.php'));
        }
        $update = $pdo->prepare('UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?');
        $update->execute([$name, rb_category_slug($pdo, $name, $categoryId), $description ?: null, $categoryId]);
        flash('success', 'Category updated.');
        go(url('admin/categories.php'));
    }

    if ($action === 'delete') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $count = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $count->execute([$categoryId]);
        $productCount = (int) $count->fetchColumn();
        if ($productCount > 0) {
            flash('error', 'Move or reassign the ' . $productCount . ' product' . ($productCount === 1 ? '' : 's') . ' in this category before deleting it.');
            go(url('admin/categories.php'));
        }
        $stmt = $pdo->prepare('SELECT name FROM categories WHERE id = ?');
        $stmt->execute([$categoryId]);
        $name = $stmt->fetchColumn();
        if ($name === false) {
            flash('error', 'That category no longer exists.');
            go(url('admin/categories.php'));
        }
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$categoryId]);
        flash('success', '“' . $name . '” was removed.');
        go(url('admin/categories.php'));
    }

    flash('error', 'Unrecognised category action.');
    go(url('admin/categories.php'));
}

$categories = $pdo->query(
    'SELECT c.*, COUNT(p.id) AS product_count, SUM(CASE WHEN p.active = 1 THEN 1 ELSE 0 END) AS active_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id
     ORDER BY c.name'
)->fetchAll();
$uncategorised = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE category_id IS NULL')->fetchColumn();
$activeCount = rb_active_product_count($pdo);

$admin_title = 'Categories';
$adminActive = 'categories';
require __DIR__ . '/_header.php';
show_flash();
?>
<div class="admin-head">
  <div>
    <p class="eyebrow">Catalog structure</p>
    <h1>THE <em>MAP.</em></h1>
    <p><?= count($categories) ?> categor<?= count($categories) === 1 ? 'y' : 'ies' ?> · <?= $activeCount ?> of <?= RB_MAX_ACTIVE_PRODUCTS ?> active treasures</p>
  </div>
  <a class="btn btn-outline-plum" href="<?= e(url('admin/products.php')) ?>">All products <span aria-hidden="true">↗</span></a>
</div>

<div class="admin-split">
  <section class="admin-panel">
    <div class="admin-panel__head">
      <div><p class="eyebrow">New shelf</p><h2>Add category</h2></div>
    </div>
    <form method="post" class="admin-form admin-form--compact" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">
      <label class="field">
        <span>Name</span>
        <input type="text" name="name" maxlength="80" required value="<?= e($form['name']) ?>" placeholder="e.g. Plush"<?= isset($errors['name']) ? ' aria-invalid="true" aria-describedby="cat-name-error"' : '' ?>>
        <?php if (isset($errors['name'])): ?><small class="field-error" id="cat-name-error"><?= e($errors['name']) ?></small><?php endif; ?>
      </label>
      <label class="field">
        <span>Description <em>optional</em></span>
        <textarea name="description" rows="3" maxlength="500" placeholder="A short line for this shelf."><?= e($form['description']) ?></textarea>
      </label>
      <button class="btn btn-plum" type="submit">Add category</button>
    </form>
  </section>

  <section class="admin-panel">
    <div class="admin-panel__head">
      <div><p class="eyebrow">Shelf by shelf</p><h2>Categories</h2></div>
      <?php if ($uncategorised > 0): ?>
        <span class="status-pill status-pill--muted"><?= $uncategorised ?> uncategorised</span>
      <?php endif; ?>
    </div>
    <?php if ($categories): ?>
      <ul class="category-list">
        <?php foreach ($categories as $category): ?>
          <li class="category-row">
            <form method="post" class="category-row__form">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="rename">
              <input type="hidden" name="category_id" value="<?= (int) $category['id'] ?>">
              <div class="category-row__fields">
                <label class="field field--flush">
                  <span class="sr-only">Category name</span>
                  <input type="text" name="name" maxlength="80" required value="<?= e($category['name']) ?>">
                </label>
                <label class="field field--flush">
                  <span class="sr-only">Category description</span>
                  <input type="text" name="description" maxlength="500" value="<?= e($category['description'] ?? '') ?>" placeholder="Add a description…">
                </label>
              </div>
              <div class="category-row__meta">
                <span><strong><?= (int) $category['active_count'] ?></strong> active</span>
                <span><strong><?= (int) $category['product_count'] ?></strong> total</span>
              </div>
              <button class="table-link" type="submit">Save</button>
            </form>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="category_id" value="<?= (int) $category['id'] ?>">
              <button class="table-link table-link--danger" type="submit"
                data-confirm="Delete “<?= e($category['name']) ?>”? Only empty categories can be deleted.">Delete</button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="admin-empty">No categories yet. Add the first shelf on the left.</p>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
