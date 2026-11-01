<?php
/** @var array $p */
$stock = (int) $p['stock'];
$category = $p['category'] ?? 'Collectible';
$searchBlob = mb_strtolower($p['name'] . ' ' . $p['description'] . ' ' . $category);
?>
<article
  class="product-card"
  data-product-card
  data-search="<?= e($searchBlob) ?>"
  data-category="<?= (int) ($p['category_id'] ?? 0) ?>"
  data-price="<?= e((string) $p['price']) ?>"
  data-newest="<?= (int) strtotime((string) $p['created_at']) ?>"
>
  <div class="product-card__media">
    <img
      src="<?= e(rb_product_image_url($p['image'])) ?>"
      alt="<?= e($p['name']) ?> collectible"
      width="1024"
      height="768"
      loading="<?= ((int) ($p['_index'] ?? 99)) < 3 ? 'eager' : 'lazy' ?>"
    >
    <span class="product-card__category"><?= e($category) ?></span>
    <?php if ($stock < 1): ?>
      <span class="product-card__stock product-card__stock--out">Sold out</span>
    <?php elseif ($stock <= 3): ?>
      <span class="product-card__stock">Only <?= $stock ?> left</span>
    <?php else: ?>
      <span class="product-card__stock">In stock</span>
    <?php endif; ?>
    <span class="product-card__folio" aria-hidden="true">No. <?= str_pad((string) (int) $p['id'], 2, '0', STR_PAD_LEFT) ?></span>
  </div>
  <div class="product-card__body">
    <div class="product-card__heading">
      <h3><?= e($p['name']) ?></h3>
      <p class="product-card__price"><?= money($p['price']) ?></p>
    </div>
    <p class="product-card__description"><?= e($p['description']) ?></p>
    <p class="product-card__meta">
      <span><?= e($category) ?></span>
      <span aria-hidden="true">·</span>
      <span><?= $stock > 0 ? $stock . ' available' : 'Restocking soon' ?></span>
    </p>
    <div class="product-card__actions">
      <form method="post" action="<?= e(url('index.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
        <input type="hidden" name="quantity" value="1">
        <button class="btn btn-plum btn-card" type="submit" <?= $stock < 1 ? 'disabled' : '' ?>>
          <?= $stock < 1 ? 'Sold out' : 'Add to bag' ?>
        </button>
      </form>
      <button
        class="btn btn-outline btn-card"
        type="button"
        data-product-id="<?= (int) $p['id'] ?>"
        data-bs-toggle="modal"
        data-bs-target="#productModal"
      >Details</button>
    </div>
  </div>
</article>
