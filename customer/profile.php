<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
header('Cache-Control: no-store');
require_login();

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([(int) user()['id']]);
$profile = $stmt->fetch();
if (!$profile) {
    rb_logout_user();
    go(url('customer/login.php'));
}

$errors = [];
$pwErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = rb_post_string('name', 120);
        $email = mb_strtolower(rb_post_string('email', 190));
        $phone = rb_post_string('phone', 30);
        $address = rb_post_string('address', 500);

        if (mb_strlen($name) < 2) {
            $errors['name'] = 'Please enter your name.';
        }
        if (!rb_valid_email($email)) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if ($phone !== '' && !preg_match('/^[0-9+()\-\s]{7,30}$/', $phone)) {
            $errors['phone'] = 'Enter a valid phone number.';
        }

        if ($errors === []) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?');
            $check->execute([$email, (int) $profile['id']]);
            if ((int) $check->fetchColumn() > 0) {
                $errors['email'] = 'That email is already used by another account.';
            }
        }

        if ($errors === []) {
            try {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?');
                $stmt->execute([$name, $email, $phone !== '' ? $phone : null, $address !== '' ? $address : null, (int) $profile['id']]);
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
                flash('success', 'Your profile has been updated.');
                go(url('customer/profile.php'));
            } catch (PDOException $e) {
                error_log('Profile update failed: ' . $e->getMessage());
                $errors['form'] = 'Your profile could not be saved just now.';
            }
        }

        $profile['name'] = $name;
        $profile['email'] = $email;
        $profile['phone'] = $phone;
        $profile['address'] = $address;
    }

    if ($action === 'change_password') {
        $current = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
        $new = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
        $confirm = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';

        if (!password_verify($current, (string) $profile['password'])) {
            $pwErrors['current_password'] = 'That current password is not correct.';
        }
        if (strlen($new) < 8) {
            $pwErrors['new_password'] = 'Use at least 8 characters.';
        }
        if ($new !== $confirm) {
            $pwErrors['password_confirm'] = 'The new passwords do not match.';
        }

        if ($pwErrors === []) {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), (int) $profile['id']]);
            session_regenerate_id(true);
            flash('success', 'Your password has been changed.');
            go(url('customer/profile.php'));
        }
    }
}

$orderStmt = $pdo->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(total),0) AS spent FROM orders WHERE user_id = ? AND status <> 'Cancelled'");
$orderStmt->execute([(int) $profile['id']]);
$orderSummary = $orderStmt->fetch() ?: ['total' => 0, 'spent' => 0];

$page_title = 'My profile — RockBottom';
$body_class = 'account-page';
$nav_active = 'account';
$accountActive = 'profile';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
show_flash();
?>
<main id="main" class="section account-section">
  <div class="rb-container account-layout">
    <?php require __DIR__ . '/../includes/account-nav.php'; ?>
    <div class="account-main">
      <header class="account-head">
        <p class="eyebrow">Your little corner</p>
        <h1>Hello, <em><?= e($profile['name']) ?>.</em></h1>
        <p>Member since <?= e(rb_date($profile['created_at'], 'F Y')) ?> · <?= (int) $orderSummary['total'] ?> order<?= (int) $orderSummary['total'] === 1 ? '' : 's' ?> · <?= money($orderSummary['spent']) ?> in treasures</p>
      </header>

      <div class="account-cards">
        <section class="panel">
          <div class="panel__head">
            <h2>Profile details</h2>
            <span>01</span>
          </div>
          <?php if (isset($errors['form'])): ?><div class="rb-alert rb-alert--error" role="alert"><span><?= e($errors['form']) ?></span></div><?php endif; ?>
          <form method="post" class="form-stack" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile">
            <div class="form-row">
              <div class="form-field">
                <label for="name">Your name</label>
                <input id="name" name="name" type="text" maxlength="120" value="<?= e($profile['name']) ?>" required <?= isset($errors['name']) ? 'aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
              </div>
              <div class="form-field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" maxlength="190" value="<?= e($profile['email']) ?>" required <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label for="phone">Phone <span>(optional)</span></label>
                <input id="phone" name="phone" type="tel" maxlength="30" value="<?= e($profile['phone'] ?? '') ?>" autocomplete="tel" <?= isset($errors['phone']) ? 'aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['phone'])): ?><p class="field-error"><?= e($errors['phone']) ?></p><?php endif; ?>
              </div>
              <div class="form-field form-field--grow">
                <label for="address">Default delivery address <span>(optional)</span></label>
                <input id="address" name="address" type="text" maxlength="500" value="<?= e($profile['address'] ?? '') ?>" autocomplete="street-address">
              </div>
            </div>
            <button class="btn btn-plum" type="submit">Save profile</button>
          </form>
        </section>

        <section class="panel">
          <div class="panel__head">
            <h2>Change password</h2>
            <span>02</span>
          </div>
          <form method="post" class="form-stack" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">
            <div class="form-field">
              <label for="current_password">Current password</label>
              <input id="current_password" name="current_password" type="password" maxlength="200" autocomplete="current-password" required <?= isset($pwErrors['current_password']) ? 'aria-invalid="true"' : '' ?>>
              <?php if (isset($pwErrors['current_password'])): ?><p class="field-error"><?= e($pwErrors['current_password']) ?></p><?php endif; ?>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label for="new_password">New password</label>
                <input id="new_password" name="new_password" type="password" minlength="8" maxlength="200" autocomplete="new-password" required <?= isset($pwErrors['new_password']) ? 'aria-invalid="true"' : '' ?>>
                <?php if (isset($pwErrors['new_password'])): ?><p class="field-error"><?= e($pwErrors['new_password']) ?></p><?php endif; ?>
              </div>
              <div class="form-field">
                <label for="password_confirm">Confirm new password</label>
                <input id="password_confirm" name="password_confirm" type="password" minlength="8" maxlength="200" autocomplete="new-password" required <?= isset($pwErrors['password_confirm']) ? 'aria-invalid="true"' : '' ?>>
                <?php if (isset($pwErrors['password_confirm'])): ?><p class="field-error"><?= e($pwErrors['password_confirm']) ?></p><?php endif; ?>
              </div>
            </div>
            <button class="btn btn-outline" type="submit">Update password</button>
          </form>
        </section>
      </div>
    </div>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
