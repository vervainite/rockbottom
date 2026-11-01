<?php
if (!defined('RB_ROOT')) {
    require_once __DIR__ . '/../config/app.php';
}
require_admin();
$adminActive = $adminActive ?? '';
$adminUser = user();
$adminUnread = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
$adminPending = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();
$page_title = ($admin_title ?? 'Admin') . ' — RockBottom Control Room';
$body_class = 'admin-page';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-shell">
  <aside class="admin-sidebar" id="adminSidebar">
    <a class="admin-brand" href="<?= e(url('admin/dashboard.php')) ?>">
      <span class="brand__star" aria-hidden="true">✦</span>
      <span>RockBottom<small>Control room</small></span>
    </a>
    <nav class="admin-nav" aria-label="Admin">
      <a class="<?= $adminActive === 'dashboard' ? 'is-active' : '' ?>" href="<?= e(url('admin/dashboard.php')) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        <span>Dashboard</span>
      </a>
      <a class="<?= $adminActive === 'products' ? 'is-active' : '' ?>" href="<?= e(url('admin/products.php')) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l9 5-9 5-9-5 9-5z"/><path d="M3 12l9 5 9-5"/><path d="M3 17l9 5 9-5"/></svg>
        <span>Products</span>
      </a>
      <a class="<?= $adminActive === 'categories' ? 'is-active' : '' ?>" href="<?= e(url('admin/categories.php')) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h7v7H4z"/><path d="M13 4h7v7h-7z"/><path d="M4 13h7v7H4z"/><path d="M13 13h7v7h-7z"/></svg>
        <span>Categories</span>
      </a>
      <a class="<?= $adminActive === 'orders' ? 'is-active' : '' ?>" href="<?= e(url('admin/orders.php')) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/><path d="M9 8h6M9 12h6"/></svg>
        <span>Orders</span>
        <?php if ($adminPending > 0): ?><em><?= $adminPending ?></em><?php endif; ?>
      </a>
      <a class="<?= $adminActive === 'customers' ? 'is-active' : '' ?>" href="<?= e(url('admin/customers.php')) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 21c.5-4 3.2-6 6.5-6s6 2 6.5 6"/><path d="M16 5.5a3.5 3.5 0 010 6"/><path d="M17.5 15.5c2.4.5 3.8 2.3 4 5.5"/></svg>
        <span>Customers</span>
      </a>
      <a class="<?= $adminActive === 'messages' ? 'is-active' : '' ?>" href="<?= e(url('admin/messages.php')) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h18v12H8l-5 4V5z"/><path d="M7 9h10M7 13h6"/></svg>
        <span>Messages</span>
        <?php if ($adminUnread > 0): ?><em><?= $adminUnread ?></em><?php endif; ?>
      </a>
    </nav>
    <div class="admin-sidebar__foot">
      <a class="admin-store-link" href="<?= e(url('index.php')) ?>">← View storefront</a>
      <form method="post" action="<?= e(url('customer/logout.php')) ?>">
        <?= csrf_field() ?>
        <button class="link-btn" type="submit">Sign out</button>
      </form>
    </div>
  </aside>
  <button class="admin-backdrop" type="button" data-admin-menu hidden aria-label="Close admin menu"></button>
  <div class="admin-main">
    <header class="admin-topbar">
      <button class="admin-menu-btn" type="button" data-admin-menu aria-label="Open admin menu" aria-controls="adminSidebar" aria-expanded="false">
        <span></span><span></span>
      </button>
      <p class="admin-topbar__title"><?= e($admin_title ?? 'Dashboard') ?></p>
      <div class="admin-topbar__tools">
        <a class="admin-topbar__store" href="<?= e(url('index.php')) ?>">Storefront ↗</a>
        <button class="icon-btn theme-toggle theme-toggle--admin" type="button" data-theme-toggle aria-label="Switch to dark mode" aria-pressed="false">
          <svg class="theme-toggle__moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 13.5A8 8 0 019.4 3.2 8.4 8.4 0 1020 13.5z"/><path d="M16.5 3.5l.6 1.6 1.6.6-1.6.6-.6 1.6-.6-1.6-1.6-.6 1.6-.6z"/></svg>
          <svg class="theme-toggle__sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.2 5.2l1.4 1.4M17.4 17.4l1.4 1.4M18.8 5.2l-1.4 1.4M6.6 17.4l-1.4 1.4"/></svg>
        </button>
        <span class="avatar-mini"><?= e(rb_initials((string) $adminUser['name'])) ?></span>
        <span class="admin-topbar__name"><?= e($adminUser['name']) ?></span>
      </div>
    </header>
    <main class="admin-content" id="main">
