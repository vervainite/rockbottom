<?php
if (!defined('RB_ROOT')) {
    require_once __DIR__ . '/../config/app.php';
}
$nav_active = $nav_active ?? '';
$currentUser = user();
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
        <div class="dropdown">
          <button class="icon-btn account-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
            <span class="avatar-mini"><?= e(rb_initials((string) $currentUser['name'])) ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end account-menu">
            <li class="account-menu__head">
              <span>Signed in as</span>
              <strong><?= e($currentUser['name']) ?></strong>
            </li>
            <li><a class="dropdown-item" href="<?= e(url('customer/profile.php')) ?>">My profile</a></li>
            <li><a class="dropdown-item" href="<?= e(url('customer/orders.php')) ?>">Order history</a></li>
            <?php if (is_admin()): ?>
              <li><a class="dropdown-item" href="<?= e(url('admin/dashboard.php')) ?>">Admin dashboard</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="post" action="<?= e(url('customer/logout.php')) ?>">
                <?= csrf_field() ?>
                <button class="dropdown-item" type="submit">Sign out</button>
              </form>
            </li>
          </ul>
        </div>
      <?php else: ?>
        <a class="icon-btn" href="<?= e(url('customer/login.php')) ?>" aria-label="Sign in">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4.5 21c.6-4.2 3.6-6.5 7.5-6.5s6.9 2.3 7.5 6.5"/></svg>
        </a>
      <?php endif; ?>

      <a class="bag-link" href="<?= e(url('customer/cart.php')) ?>" aria-label="Your bag, <?= $bagCount ?> items">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1.2 12H6.2L5 8z"/><path d="M9 8V6a3 3 0 016 0v2"/></svg>
        <span class="bag-link__count"><?= $bagCount ?></span>
      </a>

      <button class="menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-label="Open menu">
        <span></span><span></span>
      </button>
    </div>
  </div>
</header>

<div class="offcanvas offcanvas-end mobile-nav" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
  <div class="offcanvas-header">
    <span class="offcanvas-title brand brand--small" id="mobileNavLabel">
      <?php require __DIR__ . '/brand-mark.php'; ?>
      <span class="brand__word">RockBottom</span><span class="brand__dot">.</span>
    </span>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close menu"></button>
  </div>
  <div class="offcanvas-body">
    <p class="eyebrow">CUTE THINGS FROM THE DEPTHS</p>
    <nav class="mobile-nav__links" aria-label="Mobile">
      <a href="<?= e(url('index.php')) ?>">Home <span>01</span></a>
      <a href="<?= e(url('index.php#shop')) ?>">Shop <span>02</span></a>
      <a href="<?= e(url('index.php#about')) ?>">About <span>03</span></a>
      <a href="<?= e(url('index.php#contact')) ?>">Contact <span>04</span></a>
    </nav>
    <div class="mobile-nav__account">
      <?php if ($currentUser): ?>
        <p class="mobile-nav__hello">Hello, <?= e($currentUser['name']) ?>.</p>
        <a href="<?= e(url('customer/profile.php')) ?>">My profile</a>
        <a href="<?= e(url('customer/orders.php')) ?>">Order history</a>
        <?php if (is_admin()): ?><a href="<?= e(url('admin/dashboard.php')) ?>">Admin dashboard</a><?php endif; ?>
        <form method="post" action="<?= e(url('customer/logout.php')) ?>">
          <?= csrf_field() ?>
          <button type="submit" class="link-btn">Sign out</button>
        </form>
      <?php else: ?>
        <a class="btn btn-plum btn-sm" href="<?= e(url('customer/login.php')) ?>">Sign in</a>
        <a class="mobile-nav__register" href="<?= e(url('customer/register.php')) ?>">Create an account</a>
      <?php endif; ?>
    </div>
    <a class="mobile-nav__bag" href="<?= e(url('customer/cart.php')) ?>">
      <span>Your bag</span><span><?= $bagCount ?> item<?= $bagCount === 1 ? '' : 's' ?></span>
    </a>
    <button class="mobile-nav__theme" type="button" data-theme-toggle aria-pressed="false">
      <span>Appearance</span>
      <span class="mobile-nav__theme-state">
        <span data-theme-label>Dark mode</span>
        <span class="theme-toggle__switch" aria-hidden="true"></span>
      </span>
    </button>
  </div>
</div>
