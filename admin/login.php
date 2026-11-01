<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
header('Cache-Control: no-store');

if (is_admin()) {
    go(url('admin/dashboard.php'));
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(rb_post_string('email', 190));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    $now = time();
    $attempts = array_values(array_filter(
        $_SESSION['admin_login_attempts'] ?? [],
        static fn ($ts): bool => is_int($ts) && $ts > $now - 900
    ));

    if (count($attempts) >= 6) {
        $error = 'Too many attempts. Wait a few minutes before trying again.';
    } elseif (!rb_valid_email($email) || $password === '') {
        $error = 'Enter your staff email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if ($row && password_verify($password, (string) $row['password'])) {
            unset($_SESSION['admin_login_attempts']);
            rb_login_user($pdo, $row);
            flash('success', 'Control room unlocked. Welcome back, ' . $row['name'] . '.');
            go(url('admin/dashboard.php'));
        }
        $attempts[] = $now;
        $_SESSION['admin_login_attempts'] = array_slice($attempts, -6);
        usleep(200000);
        $error = 'Those staff credentials were not recognized.';
    }
}

$page_title = 'Admin sign in — RockBottom';
$body_class = 'admin-login-page';
require __DIR__ . '/../includes/header.php';
?>
<main id="main" class="admin-login">
  <div class="admin-login__art">
    <img src="<?= e(asset('images/hero/promo-art-boys.webp')) ?>" alt="Night Walker collectible sitting with a lantern in a crystal cave" width="1024" height="1280">
    <div class="admin-login__overlay">
      <a class="brand brand--light" href="<?= e(url('index.php')) ?>">
        <?php require __DIR__ . '/../includes/brand-mark.php'; ?>
        <span class="brand__word">RockBottom</span><span class="brand__dot">.</span>
      </a>
      <div>
        <p class="eyebrow eyebrow--light">The control room</p>
        <p class="auth-quote">“Keep the little<br>things <em>safe.</em>”</p>
        <span>Staff access only · Est. 2026</span>
      </div>
    </div>
  </div>
  <div class="admin-login__panel">
    <div class="admin-login__topline">
      <a class="admin-login__back" href="<?= e(url('index.php')) ?>">← Back to the store</a>
      <button class="theme-link" type="button" data-theme-toggle aria-pressed="false">
        <svg class="theme-toggle__moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 13.5A8 8 0 019.4 3.2 8.4 8.4 0 1020 13.5z"/></svg>
        <svg class="theme-toggle__sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.2 5.2l1.4 1.4M17.4 17.4l1.4 1.4M18.8 5.2l-1.4 1.4M6.6 17.4l-1.4 1.4"/></svg>
        <span data-theme-label>Dark mode</span>
      </button>
    </div>
    <p class="eyebrow">RockBottom staff</p>
    <h1>ADMIN <em>SIGN IN.</em></h1>
    <p class="auth-panel__lede">Manage the collection, orders, and the people who collect them.</p>

    <?php if ($error): ?><div class="rb-alert rb-alert--error" role="alert"><span><?= e($error) ?></span></div><?php endif; ?>

    <form method="post" class="form-stack" novalidate>
      <?= csrf_field() ?>
      <div class="form-field">
        <label for="email">Staff email</label>
        <input id="email" name="email" type="email" maxlength="190" value="<?= e($email) ?>" autocomplete="username" required>
      </div>
      <div class="form-field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" maxlength="200" autocomplete="current-password" required>
      </div>
      <button class="btn btn-plum btn-block" type="submit">Unlock control room</button>
    </form>
    <p class="auth-fineprint">No default staff password ships with RockBottom. Create the first admin with <code>scripts/create_admin.php</code>.</p>
  </div>
</main>
<?php require __DIR__ . '/_scripts.php'; ?>
