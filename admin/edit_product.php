<?php
declare(strict_types=1);
require __DIR__ . '/_auth.php';

$productId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product) {
    flash('error', 'That product could not be found.');
    go(url('admin/products.php'));
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$activeCount = rb_active_product_count($pdo);
$form = [
    'name'        => (string) $product['name'],
    'category_id' => (string) ($product['category_id'] ?? ''),
    'description' => (string) $product['description'],
    'price'       => (string) $product['price'],
    'stock'       => (string) $product['stock'],
    'active'      => (int) $product['active'],
    'image'       => (string) $product['image'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['name'] = rb_post_string('name', 140);
    $form['category_id'] = (string) ($_POST['category_id'] ?? '');
    $form['description'] = rb_post_string('description', 2000);
    $form['price'] = trim((string) ($_POST['price'] ?? ''));
    $form['stock'] = trim((string) ($_POST['stock'] ?? ''));
    $form['active'] = (int) (($_POST['active'] ?? '0') === '1');

    if (mb_strlen($form['name']) < 2) {
        $errors['name'] = 'Give the product a name (2–140 characters).';
    }
    $categoryId = (int) $form['category_id'];
    $categoryCheck = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = ?');
    $categoryCheck->execute([$categoryId]);
    if ($categoryId < 1 || (int) $categoryCheck->fetchColumn() === 0) {
        $errors['category_id'] = 'Choose a category.';
    }
    if (mb_strlen($form['description']) < 10) {
        $errors['description'] = 'Write at least 10 characters about this treasure.';
    }
    if ($form['price'] === '' || !is_numeric($form['price']) || (float) $form['price'] < 0 || (float) $form['price'] > 999999.99) {
        $errors['price'] = 'Enter a price between 0 and 999,999.99.';
    }
    $stock = filter_var($form['stock'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9999]]);
    if ($stock === false) {
        $errors['stock'] = 'Enter stock between 0 and 9,999.';
    }
    if ($form['active'] === 1 && (int) $product['active'] === 0 && $activeCount >= RB_MAX_ACTIVE_PRODUCTS) {
        $errors['active'] = 'The 10 active slots are full. Archive another product first.';
    }

    $fileError = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($fileError !== UPLOAD_ERR_NO_FILE && $fileError !== UPLOAD_ERR_OK) {
        $errors['image'] = 'The image upload failed. Try a smaller JPG, PNG or WebP file.';
    }

    if ($errors === []) {
        $newImage = null;
        try {
            if ($fileError === UPLOAD_ERR_OK) {
                $newImage = rb_handle_image_upload($_FILES['image'], rb_slugify($form['name']));
            }
            rb_product_save($pdo, [
                'name'        => $form['name'],
                'category_id' => $categoryId,
                'description' => $form['description'],
                'price'       => (float) $form['price'],
                'stock'       => (int) $stock,
                'image'       => $newImage ?? (string) $product['image'],
                'active'      => (int) $form['active'],
            ], $productId);
            if ($newImage !== null && $newImage !== $product['image']) {
                rb_delete_uploaded_image((string) $product['image']);
            }
            flash('success', $form['name'] . ' has been updated.');
            go(url('admin/products.php'));
        } catch (PDOException $e) {
            if ($e->getCode() === '45000') {
                $errors['active'] = 'The storefront already has 10 active products. Archive one first.';
            } else {
                error_log('Product update failed: ' . $e->getMessage());
                $errors['form'] = 'The product could not be saved. Please try again.';
            }
        } catch (RuntimeException $e) {
            $errors['form'] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Product update failed: ' . $e->getMessage());
            $errors['form'] = 'The product could not be saved. Please try again.';
        }
        if ($newImage !== null && $errors !== []) {
            rb_delete_uploaded_image($newImage);
        }
    }
}

$admin_title = 'Edit product';
$adminActive = 'products';
$isEdit = true;
$formAction = url('admin/edit_product.php?id=' . $productId);
require __DIR__ . '/_header.php';
show_flash();
?>
<div class="admin-head">
  <div>
    <p class="eyebrow">Editing /<?= e($product['slug']) ?></p>
    <h1>EDIT <em>TREASURE.</em></h1>
    <p><?= e($product['name']) ?> · added <?= e(rb_date($product['created_at'], 'M j, Y')) ?></p>
  </div>
  <a class="text-link" href="<?= e(url('admin/products.php')) ?>">← Back to products</a>
</div>
<?php require __DIR__ . '/_product-form.php'; ?>
<?php require __DIR__ . '/_footer.php'; ?>
