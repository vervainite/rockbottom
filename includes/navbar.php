<?php
if (!defined('RB_ROOT')) {
    require_once __DIR__ . '/../config/app.php';
}
$nav_active = $nav_active ?? '';
$currentUser = user();
$navAvatar = $currentUser ? rb_avatar_url($currentUser) : null;
$bagCount = cart_count($pdo);
?>
<div class="announcement">
  <div class="rb-container announcement__inner">
    <span>Free local delivery over <?= money(RB_FREE_SHIPPING_AT) ?></span>
    <span class="announcement__mid">Cash on Delivery available</span>
    <span>Est. 2026 · Cebu, PH</span>
  </div>
</div>
<header class="site-header" id="siteHeader">
  <div class="rb-container site-header__inner">
    <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="RockBottom home">
      <?php require __DIR__ . '/brand-mark.php'; ?>
      <span class="brand__word">RockBottom</span>
      <span class="brand__dot" aria-hidden="true">.</span>
    </a>

    <nav class="main-nav" aria-label="Primary">
      <a class="<?= $nav_active === 'home' ? 'is-active' : '' ?>" href="<?= e(url('index.php')) ?>">Home</a>
      <a class="<?= $nav_active === 'shop' ? 'is-active' : '' ?>" href="<?= e(url('index.php#shop')) ?>">Shop</a>
      <a class="<?= $nav_active === 'about' ? 'is-active' : '' ?>" href="<?= e(url('index.php#about')) ?>">About</a>
      <a class="<?= $nav_active === 'contact' ? 'is-active' : '' ?>" href="<?= e(url('index.php#contact')) ?>">Contact</a>
    </nav>

    <div class="header-tools">
      <button class="icon-btn" type="button" data-search-trigger data-search-href="<?= e(url('index.php#shop')) ?>" aria-label="Search the collection">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.5 15.5L21 21"/></svg>
      </button>

      <button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-label="Switch to dark mode" aria-pressed="false">
        <svg class="theme-toggle__moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 13.5A8 8 0 019.4 3.2 8.4 8.4 0 1020 13.5z"/><path d="M16.5 3.5l.6 1.6 1.6.6-1.6.6-.6 1.6-.6-1.6-1.6-.6 1.6-.6z"/></svg>
        <svg class="theme-toggle__sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.2 5.2l1.4 1.4M17.4 17.4l1.4 1.4M18.8 5.2l-1.4 1.4M6.6 17.4l-1.4 1.4"/></svg>
      </button>

      <?php if ($currentUser): ?>
        <a class="icon-btn account-link" href="<?= e(url('customer/profile.php')) ?>" aria-label="Open your profile" title="Your profile">
          <?php if ($navAvatar): ?>
            <img class="avatar-mini avatar-mini--photo" src="<?= e($navAvatar) ?>" alt="" width="30" height="30">
          <?php else: ?>
            <span class="avatar-mini"><?= e(rb_initials((string) $currentUser['name'])) ?></span>
          <?php endif; ?>
        </a>
      <?php else: ?>
        <a class="icon-btn" href="<?= e(url('customer/login.php')) ?>" aria-label="Sign in">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4.5 21c.6-4.2 3.6-6.5 7.5-6.5s6.9 2.3 7.5 6.5"/></svg>
        </a>
      <?php endif; ?>

      <a class="bag-link" href="<?= e(url('customer/cart.php')) ?>" aria-label="Your bag, <?= $bagCount ?> items">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1.2 12H6.2L5 8z"/><path d="M9 8V6a3 3 0 016 0v2"/></svg>
        <span class="bag-link__count"><?= $bagCount ?></span>
      </a>

      <button class="menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-label="Open menu and settings" title="Menu &amp; settings">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>

<div class="offcanvas offcanvas-end mobile-nav settings-dock" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
  <div class="offcanvas-header">
    <span class="offcanvas-title brand brand--small" id="mobileNavLabel">
      <?php require __DIR__ . '/brand-mark.php'; ?>
      <span class="brand__word">RockBottom</span><span class="brand__dot">.</span>
    </span>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close menu"></button>
  </div>
  <div class="offcanvas-body">
    <?php if ($currentUser): ?>
      <div class="dock-user">
        <span class="dock-user__avatar">
          <?php if ($navAvatar): ?>
            <img src="<?= e($navAvatar) ?>" alt="" width="48" height="48">
          <?php else: ?>
            <?= e(rb_initials((string) $currentUser['name'])) ?>
          <?php endif; ?>
        </span>
        <span class="dock-user__meta">
          <span>Signed in</span>
          <strong><?= e($currentUser['name']) ?></strong>
        </span>
        <a class="dock-user__edit" href="<?= e(url('customer/profile.php')) ?>">Edit</a>
      </div>
    <?php else: ?>
      <div class="dock-signin">
        <p>Keep your own little corner of the deep.</p>
        <a class="btn btn-plum btn-sm" href="<?= e(url('customer/login.php')) ?>">Sign in</a>
        <a class="mobile-nav__register" href="<?= e(url('customer/register.php')) ?>">Create an account</a>
      </div>
    <?php endif; ?>

    <p class="eyebrow">Find your way</p>
    <nav class="mobile-nav__links" aria-label="Mobile">
      <a href="<?= e(url('index.php')) ?>">Home <span>01</span></a>
      <a href="<?= e(url('index.php#shop')) ?>">Shop <span>02</span></a>
      <a href="<?= e(url('index.php#about')) ?>">About <span>03</span></a>
      <a href="<?= e(url('index.php#contact')) ?>">Contact <span>04</span></a>
    </nav>

    <?php if ($currentUser): ?>
      <div class="dock-group">
        <p class="dock-group__title">Your corner <span>01</span></p>
        <a class="dock-link" href="<?= e(url('customer/profile.php')) ?>">
          <span>Collector card</span><small>Photo, design &amp; profile</small>
        </a>
        <a class="dock-link" href="<?= e(url('customer/orders.php')) ?>">
          <span>Order history</span><small>Track every find</small>
        </a>
        <a class="dock-link" href="<?= e(url('customer/cart.php')) ?>">
          <span>Your bag</span><small><?= $bagCount ?> item<?= $bagCount === 1 ? '' : 's' ?></small>
        </a>
        <?php if (is_admin()): ?>
          <a class="dock-link" href="<?= e(url('admin/dashboard.php')) ?>">
            <span>Admin dashboard</span><small>Store control room</small>
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="dock-group">
      <p class="dock-group__title">Settings <span>02</span></p>
      <button class="mobile-nav__theme" type="button" data-theme-toggle aria-pressed="false">
        <span>Appearance</span>
        <span class="mobile-nav__theme-state">
          <span data-theme-label>Dark mode</span>
          <span class="theme-toggle__switch" aria-hidden="true"></span>
        </span>
      </button>
      <?php if ($currentUser): ?>
        <a class="dock-link" href="<?= e(url('customer/profile.php')) ?>#settings">
          <span>Account settings</span><small>Card, contact &amp; password</small>
        </a>
      <?php endif; ?>
      <a class="dock-link" href="<?= e(url('index.php#contact')) ?>">
        <span>Contact the studio</span><small>Questions &amp; custom finds</small>
      </a>
    </div>

    <?php if ($currentUser): ?>
      <form method="post" action="<?= e(url('customer/logout.php')) ?>" class="dock-signout">
        <?= csrf_field() ?>
        <button class="link-btn" type="submit">Sign out</button>
      </form>
    <?php endif; ?>
  </div>
</div>
