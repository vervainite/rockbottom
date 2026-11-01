<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $quantities = $_POST['quantity'] ?? [];
        if (is_array($quantities)) {
            foreach ($quantities as $pid => $qty) {
                $pid = (int) $pid;
                if ($pid < 1 || !is_scalar($qty)) {
                    continue;
                }
                cart_set_qty($pdo, $pid, (int) $qty);
            }
            flash('success', 'Your bag has been updated.');
        }
        go(url('customer/cart.php'));
    }

    if ($action === 'remove') {
        $pid = (int) ($_POST['product_id'] ?? 0);
        if ($pid > 0) {
            cart_remove($pdo, $pid);
            flash('info', 'That treasure was removed from your bag.');
        }
        go(url('customer/cart.php'));
    }

    if ($action === 'clear') {
        rb_clear_cart($pdo);
        flash('info', 'Your bag is empty again.');
        go(url('customer/cart.php'));
    }

    go(url('customer/cart.php'));
}

$totals = cart_totals($pdo);
$items = $totals['rows'];
$itemCount = array_sum(array_column($items, 'qty'));
$remaining = max(0, RB_FREE_SHIPPING_AT - (float) $totals['subtotal']);
$progress = min(100, (float) $totals['subtotal'] / RB_FREE_SHIPPING_AT * 100);

$page_title = 'Your bag — RockBottom';
$body_class = 'bag-page';
$nav_active = 'account';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
show_flash();
?>
<main id="main" class="section bag-section">
  <div class="rb-container">
    <header class="page-head">
      <div>
        <p class="eyebrow">Your finds, so far</p>
        <h1>YOUR <em>BAG.</em></h1>
      </div>
      <p class="page-head__meta"><?= $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?> · <?= count($items) ?> treasure<?= count($items) === 1 ? '' : 's' ?></p>
    </header>

    <?php if (!$items): ?>
      <div class="empty-state">
        <span class="empty-state__star" aria-hidden="true">✦</span>
        <h2>Nothing down here yet.</h2>
        <p>Your bag is waiting for something strange and lovely.</p>
        <a class="btn btn-plum" href="<?= e(url('index.php#shop')) ?>">Find a treasure <span aria-hidden="true">↗</span></a>
      </div>
    <?php else: ?>
      <div class="bag-layout">
        <div class="bag-items">
          <?php foreach ($items as $item): ?>
            <?php
            $stock = (int) $item['stock'];
            $adjusted = (int) $item['requested_qty'] > (int) $item['qty'];
            ?>
            <article class="bag-item">
              <a class="bag-item__media" href="<?= e(url('index.php#shop')) ?>" tabindex="-1" aria-hidden="true">
                <img src="<?= e(rb_product_image_url($item['image'])) ?>" alt="" width="596" height="464" loading="lazy">
              </a>
              <div class="bag-item__info">
                <p class="mini-label"><?= e($item['category'] ?? 'Collectible') ?></p>
                <h2><?= e($item['name']) ?></h2>
                <p class="bag-item__price"><?= money($item['price']) ?> each</p>
                <?php if ($stock < 1): ?>
                  <p class="stock-warning">Sold out — remove to continue.</p>
                <?php elseif ($adjusted): ?>
                  <p class="stock-warning">Only <?= $stock ?> available. Quantity adjusted in your total.</p>
                <?php endif; ?>
                <form method="post" class="bag-item__controls">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update">
                  <div class="qty-stepper" data-stepper>
                    <button type="button" data-step="-1" aria-label="Decrease quantity of <?= e($item['name']) ?>">−</button>
                    <label class="sr-only" for="qty-<?= (int) $item['id'] ?>">Quantity</label>
                    <input id="qty-<?= (int) $item['id'] ?>" name="quantity[<?= (int) $item['id'] ?>]" type="number" min="1" max="<?= max(1, min(RB_MAX_QTY, $stock)) ?>" value="<?= max(1, (int) $item['qty']) ?>" inputmode="numeric" <?= $stock < 1 ? 'disabled' : '' ?>>
                    <button type="button" data-step="1" aria-label="Increase quantity of <?= e($item['name']) ?>">+</button>
                  </div>
                  <button class="link-btn" type="submit">Update</button>
                </form>
              </div>
              <div class="bag-item__line">
                <span class="bag-item__line-label">Line total</span>
                <strong><?= money($item['line_total']) ?></strong>
              </div>
              <form method="post" class="bag-item__remove">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                <button type="submit" class="icon-remove" aria-label="Remove <?= e($item['name']) ?> from bag" data-confirm="Remove this treasure from your bag?">×</button>
              </form>
            </article>
          <?php endforeach; ?>

          <div class="bag-actions">
            <a class="text-link" href="<?= e(url('index.php#shop')) ?>">← Keep exploring</a>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="clear">
              <button class="link-btn link-btn--muted" type="submit" data-confirm="Empty your entire bag?">Empty bag</button>
            </form>
          </div>
        </div>

        <aside class="bag-summary">
          <p class="eyebrow">Order summary</p>
          <h2>YOUR <em>TOTAL.</em></h2>
          <dl class="summary-lines">
            <div><dt>Subtotal</dt><dd><?= money($totals['subtotal']) ?></dd></div>
            <div><dt>Delivery</dt><dd><?= $totals['shipping'] > 0 ? money($totals['shipping']) : 'Free' ?></dd></div>
          </dl>
          <?php if ($remaining > 0): ?>
            <div class="shipping-meter">
              <p>Add <strong><?= money($remaining) ?></strong> more for free delivery.</p>
              <div class="shipping-meter__bar" aria-hidden="true"><span style="width: <?= e(number_format($progress, 1, '.', '')) ?>%"></span></div>
            </div>
          <?php else: ?>
            <p class="shipping-free"><span aria-hidden="true">✦</span> Free local delivery unlocked.</p>
          <?php endif; ?>
          <div class="summary-total"><span>Total</span><strong><?= money($totals['total']) ?></strong></div>
          <?php if (user()): ?>
            <a class="btn btn-plum btn-block" href="<?= e(url('customer/checkout.php')) ?>">Proceed to checkout <span aria-hidden="true">↗</span></a>
          <?php else: ?>
            <a class="btn btn-plum btn-block" href="<?= e(url('customer/login.php?next=' . rawurlencode(url('customer/checkout.php')))) ?>">Sign in to check out</a>
            <p class="bag-summary__note">New here? <a href="<?= e(url('customer/register.php')) ?>">Create an account</a>. Your bag will be saved.</p>
          <?php endif; ?>
          <ul class="summary-perks">
            <li>Cash on Delivery</li>
            <li>Packed by hand in Manila</li>
            <li>Secure checkout</li>
          </ul>
        </aside>
      </div>
    <?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
