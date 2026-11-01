<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
header('Cache-Control: no-store');

if (user() !== null) {
    go(url('customer/profile.php'));
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = rb_post_string('name', 120);
    $email = mb_strtolower(rb_post_string('email', 190));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirm = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';

    if (mb_strlen($name) < 2) {
        $errors['name'] = 'Please enter your name.';
    }
    if (!rb_valid_email($email)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Use at least 8 characters.';
    } elseif (strlen($password) > 200) {
        $errors['password'] = 'That password is too long.';
    }
    if ($password !== $confirm) {
        $errors['password_confirm'] = 'The passwords do not match.';
    }

    if ($errors === []) {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $id = (int) $pdo->lastInsertId();
            rb_login_user($pdo, ['id' => $id, 'name' => $name, 'email' => $email, 'role' => 'customer']);
            flash('success', 'Welcome below the surface, ' . $name . '. Your account is ready.');
            go(url('customer/profile.php'));
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors['email'] = 'That email is already registered. Try signing in.';
            } else {
                error_log('Registration failed: ' . $e->getMessage());
                $errors['form'] = 'We could not create your account just now. Please try again.';
            }
        }
    }
}

$page_title = 'Create your account — RockBottom';
$page_description = 'Join RockBottom to keep your treasures, track orders, and check out faster.';
$body_class = 'auth-page';
$nav_active = 'account';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
show_flash();
?>
<main id="main" class="auth-main">
  <div class="rb-container auth-shell">
    <div class="auth-art">
      <img src="<?= e(asset('images/banners/cowboy-featured.webp')) ?>" alt="The Cowboy collectible strumming a ukulele beside a glowing lantern in a purple crystal cave" width="1024" height="1536">
      <div class="auth-art__overlay">
        <p class="eyebrow eyebrow--light">Join the underground</p>
        <p class="auth-quote">“Every little treasure<br>deserves a <em>keeper.</em>”</p>
        <span>Series 01 · Found below</span>
      </div>
    </div>
    <div class="auth-panel">
      <p class="eyebrow">A new little corner</p>
      <h1>Create an <em>account.</em></h1>
      <p class="auth-panel__lede">Save your bag, follow your orders, and keep every strange little find in one place.</p>

      <?php if (isset($errors['form'])): ?>
        <div class="rb-alert rb-alert--error" role="alert"><span><?= e($errors['form']) ?></span></div>
      <?php endif; ?>

      <form method="post" class="form-stack" novalidate>
        <?= csrf_field() ?>
        <div class="form-field">
          <label for="name">Your name</label>
          <input id="name" name="name" type="text" maxlength="120" value="<?= e($name) ?>" autocomplete="name" required <?= isset($errors['name']) ? 'aria-invalid="true"' : '' ?>>
          <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" maxlength="190" value="<?= e($email) ?>" autocomplete="email" required <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
          <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" minlength="8" maxlength="200" autocomplete="new-password" required <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>>
          <?php if (isset($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php else: ?><p class="field-hint">At least 8 characters.</p><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="password_confirm">Confirm password</label>
          <input id="password_confirm" name="password_confirm" type="password" minlength="8" maxlength="200" autocomplete="new-password" required <?= isset($errors['password_confirm']) ? 'aria-invalid="true"' : '' ?>>
          <?php if (isset($errors['password_confirm'])): ?><p class="field-error"><?= e($errors['password_confirm']) ?></p><?php endif; ?>
        </div>
        <button class="btn btn-plum btn-block" type="submit">Create account</button>
      </form>
      <p class="auth-switch">Already a member? <a href="<?= e(url('customer/login.php')) ?>">Sign in</a></p>
    </div>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
