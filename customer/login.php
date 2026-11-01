<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
header('Cache-Control: no-store');

$next = rb_safe_next((string) ($_POST['next'] ?? $_GET['next'] ?? ''));
if (user() !== null) {
    if (is_admin()) {
        go(url('admin/dashboard.php'));
    }
    go($next ?? url('customer/profile.php'));
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(rb_post_string('email', 190));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    $now = time();
    $attempts = array_values(array_filter(
        $_SESSION['login_attempts'] ?? [],
        static fn ($ts): bool => is_int($ts) && $ts > $now - 600
    ));

    if (count($attempts) >= 8) {
        $error = 'Too many attempts. Please wait a few minutes and try again.';
    } elseif (!rb_valid_email($email) || $password === '') {
        $error = 'Enter your email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if ($row && password_verify($password, (string) $row['password'])) {
            if (password_needs_rehash((string) $row['password'], PASSWORD_DEFAULT)) {
                $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), (int) $row['id']]);
            }
            unset($_SESSION['login_attempts']);
            rb_login_user($pdo, $row);
            flash('success', 'Welcome back, ' . $row['name'] . '.');
            if ($row['role'] === 'admin') {
                go(url('admin/dashboard.php'));
            }
            go($next ?? url('customer/profile.php'));
        }

        $attempts[] = $now;
        $_SESSION['login_attempts'] = array_slice($attempts, -8);
        usleep(180000);
        $error = 'That email and password combination was not recognized.';
    }
}

$page_title = 'Sign in — RockBottom';
$page_description = 'Sign in to your RockBottom account to view your bag, orders, and little treasures.';
$body_class = 'auth-page';
$nav_active = 'account';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
show_flash();
?>
<main id="main" class="auth-main">
  <div class="rb-container auth-shell">
    <div class="auth-art auth-art--promo">
      <img src="<?= e(asset('images/hero/promo-art-boys.webp')) ?>" alt="Night Walker collectible sitting with a lantern in a crystal cave" width="1024" height="1280">
      <div class="auth-art__overlay">
        <p class="eyebrow eyebrow--light">Welcome back</p>
        <p class="auth-quote">“The best things<br>are found <em>below.</em>”</p>
        <span>Est. 2026 · Cebu</span>
      </div>
    </div>
    <div class="auth-panel">
      <p class="eyebrow">Your little corner</p>
      <h1>Sign in to <em>RockBottom.</em></h1>
      <p class="auth-panel__lede">Your bag, your orders, and all the strange little things you left here are waiting.</p>

      <?php if ($error): ?><div class="rb-alert rb-alert--error" role="alert"><span><?= e($error) ?></span></div><?php endif; ?>

      <form method="post" class="form-stack" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next ?? '') ?>">
        <div class="form-field">
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" maxlength="190" value="<?= e($email) ?>" autocomplete="email" required>
        </div>
        <div class="form-field">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" maxlength="200" autocomplete="current-password" required>
        </div>
        <button class="btn btn-plum btn-block" type="submit">Sign in</button>
      </form>
      <p class="auth-switch">New to the underground? <a href="<?= e(url('customer/register.php')) ?>">Create an account</a></p>
      <p class="auth-fineprint">Store staff can sign in through the <a href="<?= e(url('admin/login.php')) ?>">admin entrance</a>.</p>
    </div>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
