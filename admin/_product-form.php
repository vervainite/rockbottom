<?php
/** @var array $form */
/** @var array $errors */
/** @var array $categories */
/** @var bool $isEdit */
/** @var int $activeCount */
/** @var string $formAction */
$currentImage = $form['image'] ?? '';
$atLimit = $activeCount >= RB_MAX_ACTIVE_PRODUCTS;
?>
<form class="admin-form" method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?>

  <?php if (isset($errors['form'])): ?>
    <div class="rb-alert rb-alert--error" role="alert"><span><?= e($errors['form']) ?></span></div>
  <?php endif; ?>

  <div class="admin-form-grid">
    <section class="admin-panel">
      <div class="admin-panel__head"><div><p class="eyebrow">The basics</p><h2>Product details</h2></div><span>01</span></div>

      <div class="form-field">
        <label for="name">Product name</label>
        <input id="name" name="name" type="text" maxlength="140" value="<?= e($form['name']) ?>" required <?= isset($errors['name']) ? 'aria-invalid="true"' : '' ?>>
        <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
      </div>
      <div class="form-row">
        <div class="form-field">
          <label for="category_id">Category</label>
          <select id="category_id" name="category_id" required <?= isset($errors['category_id']) ? 'aria-invalid="true"' : '' ?>>
            <option value="">Choose a category</option>
            <?php foreach ($categories as $category): ?>
              <option value="<?= (int) $category['id'] ?>" <?= (int) $form['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['category_id'])): ?><p class="field-error"><?= e($errors['category_id']) ?></p><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="price">Price (PHP)</label>
          <input id="price" name="price" type="number" step="0.01" min="0" max="999999.99" value="<?= e($form['price']) ?>" required <?= isset($errors['price']) ? 'aria-invalid="true"' : '' ?>>
          <?php if (isset($errors['price'])): ?><p class="field-error"><?= e($errors['price']) ?></p><?php endif; ?>
        </div>
      </div>
      <div class="form-field">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="7" maxlength="2000" required <?= isset($errors['description']) ? 'aria-invalid="true"' : '' ?>><?= e($form['description']) ?></textarea>
        <p class="field-hint">A small story works better than a spec sheet. 10–2000 characters.</p>
        <?php if (isset($errors['description'])): ?><p class="field-error"><?= e($errors['description']) ?></p><?php endif; ?>
      </div>
    </section>

    <div class="admin-stack">
      <section class="admin-panel">
        <div class="admin-panel__head"><div><p class="eyebrow">Artwork</p><h2>Product image</h2></div><span>02</span></div>
        <?php if ($currentImage): ?>
          <div class="upload-preview">
            <img src="<?= e(rb_product_image_url($currentImage)) ?>" alt="Current product image" width="280" height="218">
            <small>Current image</small>
          </div>
        <?php else: ?>
          <div class="upload-preview upload-preview--empty" aria-hidden="true">
            <span>✦</span>
            <small>No image yet</small>
          </div>
        <?php endif; ?>
        <div class="form-field">
          <label for="image"><?= $isEdit ? 'Replace image' : 'Upload image' ?><?= $isEdit ? ' (optional)' : '' ?></label>
          <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" <?= $isEdit ? '' : 'required' ?> <?= isset($errors['image']) ? 'aria-invalid="true"' : '' ?>>
          <p class="field-hint">JPG, PNG or WebP · at least 300 × 300 px · 5 MB maximum. Images are re-encoded for safety.</p>
          <?php if (isset($errors['image'])): ?><p class="field-error"><?= e($errors['image']) ?></p><?php endif; ?>
        </div>
      </section>

      <section class="admin-panel">
        <div class="admin-panel__head"><div><p class="eyebrow">Availability</p><h2>Stock &amp; status</h2></div><span>03</span></div>
        <div class="form-field">
          <label for="stock">Stock on hand</label>
          <input id="stock" name="stock" type="number" min="0" max="9999" value="<?= e($form['stock']) ?>" required <?= isset($errors['stock']) ? 'aria-invalid="true"' : '' ?>>
          <?php if (isset($errors['stock'])): ?><p class="field-error"><?= e($errors['stock']) ?></p><?php endif; ?>
        </div>
        <label class="checkbox-field">
          <input type="checkbox" name="active" value="1" <?= (int) $form['active'] === 1 ? 'checked' : '' ?> <?= $atLimit && (int) $form['active'] !== 1 ? 'disabled' : '' ?>>
          <span><strong>Show on storefront</strong><small><?= $atLimit ? 'The 10 active slots are full. Archive a product before activating another.' : 'Active products appear in the one unified catalog.' ?></small></span>
        </label>
        <?php if ($atLimit && (int) $form['active'] !== 1): ?>
          <input type="hidden" name="active" value="0">
        <?php endif; ?>
        <?php if (isset($errors['active'])): ?><p class="field-error"><?= e($errors['active']) ?></p><?php endif; ?>
      </section>
    </div>
  </div>

  <div class="admin-form-actions">
    <a class="text-link" href="<?= e(url('admin/products.php')) ?>">← Cancel</a>
    <button class="btn btn-plum" type="submit"><?= $isEdit ? 'Save changes' : 'Create product' ?></button>
  </div>
</form>
