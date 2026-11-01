<?php
if (!defined('RB_ROOT')) {
    require_once __DIR__ . '/../config/app.php';
}
require_login();
$accountActive = $accountActive ?? '';
$accountUser = user();
$accountAvatar = rb_avatar_url($accountUser);
$accountBag = cart_count($pdo);
?>
<aside class="account-side">
  <div class="account-side__user">
    <span class="avatar<?= $accountAvatar ? ' avatar--photo' : '' ?>">
      <?php if ($accountAvatar): ?>
        <img src="<?= e($accountAvatar) ?>" alt="" width="56" height="56">
      <?php else: ?>
        <?= e(rb_initials((string) $accountUser['name'])) ?>
      <?php endif; ?>
    </span>
    <div>
      <span>Your little corner</span>
      <strong><?= e($accountUser['name']) ?></strong>
    </div>
  </div>
  <nav class="account-nav" aria-label="Account">
    <a class="<?= $accountActive === 'profile' ? 'is-active' : '' ?>" href="<?= e(url('customer/profile.php')) ?>">Profile <span>01</span></a>
    <a class="<?= $accountActive === 'orders' ? 'is-active' : '' ?>" href="<?= e(url('customer/orders.php')) ?>">Orders <span>02</span></a>
    <a class="<?= $accountActive === 'cart' ? 'is-active' : '' ?>" href="<?= e(url('customer/cart.php')) ?>">Bag <span><?= $accountBag ?></span></a>
    <?php if (is_admin()): ?>
      <a href="<?= e(url('admin/dashboard.php')) ?>">Admin <span>↗</span></a>
    <?php endif; ?>
  </nav>
  <form method="post" action="<?= e(url('customer/logout.php')) ?>" class="account-side__logout">
    <?= csrf_field() ?>
    <button type="submit" class="link-btn">Sign out</button>
  </form>
</aside>
