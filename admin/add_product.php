<?php
declare(strict_types=1);
require __DIR__ . '/_auth.php';

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$activeCount = rb_active_product_count($pdo);
$form = [
    'name'        => '',
    'category_id' => '',
    'description' => '',
    'price'       => '',
    'stock'       => '1',
    'active'      => $activeCount < RB_MAX_ACTIVE_PRODUCTS ? 1 : 0,
    'image'       => '',
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
    if ($form['active'] === 1 && $activeCount >= RB_MAX_ACTIVE_PRODUCTS) {
        $errors['active'] = 'The 10 active slots are full. Save this product as archived first.';
    }
    if (($imageError = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors['image'] = $imageError === UPLOAD_ERR_NO_FILE
            ? 'Upload a product image.'
            : 'The image upload failed. Try a smaller JPG, PNG or WebP file.';
    }

    if ($errors === []) {
        $uploaded = null;
        try {
            $uploaded = rb_handle_image_upload($_FILES['image'], rb_slugify($form['name']));
            rb_product_save($pdo, [
                'name'        => $form['name'],
                'category_id' => $categoryId,
                'description' => $form['description'],
                'price'       => (float) $form['price'],
                'stock'       => (int) $stock,
                'image'       => $uploaded,
                'active'      => (int) $form['active'],
            ]);
            flash('success', $form['name'] . ' has been added to the collection.');
            go(url('admin/products.php'));
        } catch (PDOException $e) {
            if ($e->getCode() === '45000') {
                $errors['active'] = 'The storefront already has 10 active products. Archive one first.';
            } else {
                error_log('Product create failed: ' . $e->getMessage());
                $errors['form'] = 'The product could not be saved. Please try again.';
            }
        } catch (RuntimeException $e) {
            $errors['form'] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('Product create failed: ' . $e->getMessage());
            $errors['form'] = 'The product could not be saved. Please try again.';
        }
        if ($uploaded !== null && $errors !== []) {
            rb_delete_uploaded_image($uploaded);
        }
    }
}

$admin_title = 'Add product';
$adminActive = 'products';
$isEdit = false;
$formAction = url('admin/add_product.php');
require __DIR__ . '/_header.php';
show_flash();
?>
<div class="admin-head">
  <div>
    <p class="eyebrow">New find</p>
    <h1>ADD A <em>TREASURE.</em></h1>
    <p>Give it a story, a face, and a place in the collection.</p>
  </div>
  <a class="text-link" href="<?= e(url('admin/products.php')) ?>">← Back to products</a>
</div>
<?php require __DIR__ . '/_product-form.php'; ?>
<?php require __DIR__ . '/_footer.php'; ?>
