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
$profile['card_style'] = rb_card_style($profile['card_style'] ?? null);
$profile['bio'] = is_string($profile['bio'] ?? null) ? $profile['bio'] : '';

$errors = [];
$cardErrors = [];
$pwErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $email = mb_strtolower(rb_post_string('email', 190));
        $phone = rb_post_string('phone', 30);
        $address = rb_post_string('address', 500);

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
                $stmt = $pdo->prepare('UPDATE users SET email = ?, phone = ?, address = ? WHERE id = ?');
                $stmt->execute([$email, $phone !== '' ? $phone : null, $address !== '' ? $address : null, (int) $profile['id']]);
                $_SESSION['user']['email'] = $email;
                flash('success', 'Your contact details have been updated.');
                go(url('customer/profile.php#details'));
            } catch (PDOException $e) {
                error_log('Profile update failed: ' . $e->getMessage());
                $errors['form'] = 'Your profile could not be saved just now.';
            }
        }

        $profile['email'] = $email;
        $profile['phone'] = $phone;
        $profile['address'] = $address;
    }

    if ($action === 'update_card') {
        $name = rb_post_string('name', 120);
        $bio = rb_post_string('bio', 200);
        $style = rb_post_string('card_style', 20);

        if (mb_strlen($name) < 2) {
            $cardErrors['name'] = 'Please enter the name on your card.';
        }
        if (!isset(RB_CARD_STYLES[$style])) {
            $cardErrors['card_style'] = 'Choose one of the five card designs.';
        }

        $oldAvatar = is_string($profile['avatar'] ?? null) ? $profile['avatar'] : null;
        $oldCover = is_string($profile['cover'] ?? null) ? $profile['cover'] : null;
        $uploadedAvatar = null;
        $uploadedCover = null;

        $avatarFile = $_FILES['avatar'] ?? null;
        if (is_array($avatarFile) && ($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $uploadedAvatar = rb_handle_profile_upload($avatarFile, (int) $profile['id'], 'avatar');
            } catch (RuntimeException $e) {
                $cardErrors['avatar'] = $e->getMessage();
            }
        }

        $coverFile = $_FILES['cover'] ?? null;
        if (is_array($coverFile) && ($coverFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $uploadedCover = rb_handle_profile_upload($coverFile, (int) $profile['id'], 'cover');
            } catch (RuntimeException $e) {
                $cardErrors['cover'] = $e->getMessage();
            }
        }

        if ($cardErrors === []) {
            $removeAvatar = isset($_POST['remove_avatar']);
            $removeCover = isset($_POST['remove_cover']);
            $nextAvatar = $uploadedAvatar ?? ($removeAvatar ? null : $oldAvatar);
            $nextCover = $uploadedCover ?? ($removeCover ? null : $oldCover);

            try {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, bio = ?, card_style = ?, avatar = ?, cover = ? WHERE id = ?');
                $stmt->execute([
                    $name,
                    $bio !== '' ? $bio : null,
                    $style,
                    $nextAvatar,
                    $nextCover,
                    (int) $profile['id'],
                ]);

                if ($oldAvatar !== null && $oldAvatar !== $nextAvatar) {
                    rb_delete_profile_image($oldAvatar);
                }
                if ($oldCover !== null && $oldCover !== $nextCover) {
                    rb_delete_profile_image($oldCover);
                }

                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['avatar'] = $nextAvatar;
                flash('success', 'Your collector card is ready.');
                go(url('customer/profile.php#card-studio'));
            } catch (PDOException $e) {
                error_log('Profile card update failed: ' . $e->getMessage());
                if ($uploadedAvatar !== null) {
                    rb_delete_profile_image($uploadedAvatar);
                }
                if ($uploadedCover !== null) {
                    rb_delete_profile_image($uploadedCover);
                }
                $cardErrors['form'] = 'Your card could not be saved just now.';
            }
        } elseif ($uploadedAvatar !== null || $uploadedCover !== null) {
            // Validation failed elsewhere: never leave orphaned uploads behind.
            if ($uploadedAvatar !== null) {
                rb_delete_profile_image($uploadedAvatar);
            }
            if ($uploadedCover !== null) {
                rb_delete_profile_image($uploadedCover);
            }
        }

        $profile['name'] = $name;
        $profile['bio'] = $bio;
        $profile['card_style'] = isset(RB_CARD_STYLES[$style]) ? $style : $profile['card_style'];
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

$itemsStmt = $pdo->prepare(
    "SELECT COALESCE(SUM(oi.quantity), 0)
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.user_id = ? AND o.status <> 'Cancelled'"
);
$itemsStmt->execute([(int) $profile['id']]);
$treasures = (int) $itemsStmt->fetchColumn();

$ordersCount = (int) $orderSummary['total'];
$rank = $ordersCount >= 5 ? 'Abyss regular' : ($ordersCount >= 3 ? 'Reef keeper' : ($ordersCount >= 1 ? 'Beachcomber' : 'Newcomer'));
$collectorNo = str_pad((string) $profile['id'], 4, '0', STR_PAD_LEFT);
$avatarUrl = rb_profile_image_url($profile['avatar'] ?? null);
$coverUrl = rb_profile_image_url($profile['cover'] ?? null);
$joined = rb_date($profile['created_at'], 'M Y');
$bioText = trim((string) ($profile['bio'] ?? ''));
$bioDisplay = $bioText !== '' ? $bioText : 'Collecting small strange things from the bottom of the pond.';
$styleMeta = RB_CARD_STYLES[$profile['card_style']];

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
    <div class="account-main" id="settings">
      <header class="account-head account-head--split">
        <div>
          <p class="eyebrow">Your little corner</p>
          <h1>Hello, <em><?= e($profile['name']) ?>.</em></h1>
          <p>Member since <?= e($joined) ?> · <?= $ordersCount ?> order<?= $ordersCount === 1 ? '' : 's' ?> · <?= money($orderSummary['spent']) ?> in treasures</p>
        </div>
        <button class="dock-open" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav">
          <span class="dock-open__lines" aria-hidden="true"><i></i><i></i><i></i></span>
          Settings &amp; more
        </button>
      </header>

      <section class="collector-stage" aria-labelledby="cardStudioTitle">
        <div class="collector-scene">
          <div
            class="collector-card is-<?= e($profile['card_style']) ?>"
            data-collector-card
            data-initials="<?= e(rb_initials((string) $profile['name'])) ?>"
            aria-label="Your collector card. Choose Flip card to see the field notes."
          >
            <div class="collector-card__inner">
              <div class="collector-card__face collector-card__front">
                <div class="collector-cover"<?= $coverUrl ? ' style="background-image:url(' . e($coverUrl) . ')"' : '' ?> data-card-cover>
                  <span class="collector-cover__tag">RockBottom collectors</span>
                  <span class="collector-cover__edition">No. <?= e($collectorNo) ?></span>
                </div>
                <div class="collector-body">
                  <div class="collector-top">
                    <div class="collector-avatar" data-card-avatar-wrap>
                      <img<?= $avatarUrl ? ' src="' . e($avatarUrl) . '"' : '' ?> alt="" width="120" height="120" data-card-avatar<?= $avatarUrl ? '' : ' hidden' ?>>
                      <span data-card-initials<?= $avatarUrl ? ' hidden' : '' ?>><?= e(rb_initials((string) $profile['name'])) ?></span>
                    </div>
                    <span class="collector-rank"><?= e($rank) ?></span>
                  </div>
                  <h2 class="collector-name" data-card-name><?= e($profile['name']) ?></h2>
                  <p class="collector-bio" data-card-bio><?= e($bioDisplay) ?></p>
                  <div class="collector-specimen">
                    <span class="collector-specimen__label">Found in</span>
                    <span class="collector-specimen__value">Cebu · PH</span>
                  </div>
                  <svg class="collector-doodle" viewBox="0 0 220 46" aria-hidden="true" focusable="false">
                    <path d="M4 36c9-10 18-10 27 0s18 10 27 0 18-10 27 0"/>
                    <path d="M152 26c9-8 22-8 29 0-7 8-20 8-29 0z"/>
                    <path d="M152 26l-8-7v14z"/>
                    <circle class="dot" cx="172" cy="24" r="1.6"/>
                    <circle cx="196" cy="14" r="3.5"/>
                    <circle cx="206" cy="27" r="2"/>
                    <path d="M22 14l2.4 6.4 6.6 2.4-6.6 2.4-2.4 6.4-2.4-6.4-6.6-2.4 6.6-2.4z"/>
                  </svg>
                  <div class="collector-stats">
                    <div><strong><?= $ordersCount ?></strong><span>orders</span></div>
                    <div><strong><?= $treasures ?></strong><span>treasures</span></div>
                    <div><strong><?= e($joined) ?></strong><span>joined</span></div>
                  </div>
                  <span class="collector-skin-label" data-card-style-label><?= e($styleMeta['label']) ?></span>
                </div>
                <span class="collector-shine" aria-hidden="true"></span>
              </div>
              <div class="collector-card__face collector-card__back">
                <p class="collector-back__kicker">Field notes</p>
                <h3>Found at the bottom.</h3>
                <dl class="collector-back__list">
                  <div><dt>Collector</dt><dd>No. <?= e($collectorNo) ?></dd></div>
                  <div><dt>Member since</dt><dd><?= e($joined) ?></dd></div>
                  <div><dt>Tide rank</dt><dd><?= e($rank) ?></dd></div>
                  <div><dt>Treasures</dt><dd><?= $treasures ?></dd></div>
                  <div><dt>Home reef</dt><dd>Cebu, PH</dd></div>
                </dl>
                <p class="collector-back__quote">“The best things are found below.”</p>
              </div>
            </div>
          </div>
          <div class="collector-controls">
            <button class="btn btn-outline btn-sm" type="button" data-card-flip aria-pressed="false">Flip card</button>
            <p>Tilt with your cursor · <span data-card-style-name><?= e($styleMeta['label']) ?></span></p>
          </div>
        </div>

        <div class="card-studio panel" id="card-studio">
          <div class="panel__head">
            <h2 id="cardStudioTitle">Card studio</h2>
            <span>01</span>
          </div>
          <p class="card-studio__intro">Pick a design, add a face, write a line. The card updates as you type, then save it to keep it.</p>

          <?php if (isset($cardErrors['form'])): ?><div class="rb-alert rb-alert--error" role="alert"><span><?= e($cardErrors['form']) ?></span></div><?php endif; ?>

          <form method="post" class="form-stack card-studio__form" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_card">

            <fieldset class="card-styles">
              <legend>Card design <span>Five ways to be found</span></legend>
              <div class="card-style-grid">
                <?php foreach (RB_CARD_STYLES as $key => $meta): ?>
                  <label class="card-swatch is-<?= e($key) ?>">
                    <input type="radio" name="card_style" value="<?= e($key) ?>" data-card-style<?= $profile['card_style'] === $key ? ' checked' : '' ?>>
                    <span class="card-swatch__mini" aria-hidden="true"><i></i></span>
                    <span class="card-swatch__text">
                      <strong><?= e($meta['label']) ?></strong>
                      <small><?= e($meta['note']) ?></small>
                    </span>
                  </label>
                <?php endforeach; ?>
              </div>
              <?php if (isset($cardErrors['card_style'])): ?><p class="field-error"><?= e($cardErrors['card_style']) ?></p><?php endif; ?>
            </fieldset>

            <div class="form-field">
              <label for="name">Display name</label>
              <input id="name" name="name" type="text" maxlength="120" value="<?= e($profile['name']) ?>" required data-card-name-input <?= isset($cardErrors['name']) ? 'aria-invalid="true"' : '' ?>>
              <?php if (isset($cardErrors['name'])): ?><p class="field-error"><?= e($cardErrors['name']) ?></p><?php endif; ?>
            </div>

            <div class="form-field">
              <label for="bio">Card line <span>(optional)</span></label>
              <textarea id="bio" name="bio" rows="2" maxlength="200" data-card-bio-input><?= e($bioText) ?></textarea>
              <p class="field-hint"><span data-bio-count><?= mb_strlen($bioText) ?></span>/200 · Leave it empty for the house line.</p>
            </div>

            <div class="upload-grid">
              <div class="upload-field">
                <label for="avatar">Profile photo</label>
                <div class="upload-field__row">
                  <span class="upload-thumb" data-avatar-thumb>
                    <?php if ($avatarUrl): ?>
                      <img src="<?= e($avatarUrl) ?>" alt="" width="64" height="64" data-avatar-thumb-img>
                    <?php else: ?>
                      <span data-avatar-thumb-initials><?= e(rb_initials((string) $profile['name'])) ?></span>
                      <img alt="" width="64" height="64" data-avatar-thumb-img hidden>
                    <?php endif; ?>
                  </span>
                  <div class="upload-field__body">
                    <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" data-avatar-input <?= isset($cardErrors['avatar']) ? 'aria-invalid="true"' : '' ?>>
                    <p class="field-hint">JPG, PNG or WebP · 200 × 200 minimum · up to 3 MB</p>
                    <?php if (isset($cardErrors['avatar'])): ?><p class="field-error"><?= e($cardErrors['avatar']) ?></p><?php endif; ?>
                    <?php if ($avatarUrl): ?>
                      <label class="check-line"><input type="checkbox" name="remove_avatar" value="1" data-avatar-remove> Remove current photo</label>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="upload-field">
                <label for="cover">Card cover</label>
                <div class="upload-field__row">
                  <span class="upload-thumb upload-thumb--wide" data-cover-thumb<?= $coverUrl ? ' style="background-image:url(' . e($coverUrl) . ')"' : '' ?>></span>
                  <div class="upload-field__body">
                    <input id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp" data-cover-input <?= isset($cardErrors['cover']) ? 'aria-invalid="true"' : '' ?>>
                    <p class="field-hint">Wide images look best · 200 × 200 minimum · up to 3 MB</p>
                    <?php if (isset($cardErrors['cover'])): ?><p class="field-error"><?= e($cardErrors['cover']) ?></p><?php endif; ?>
                    <?php if ($coverUrl): ?>
                      <label class="check-line"><input type="checkbox" name="remove_cover" value="1" data-cover-remove> Remove current cover</label>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>

            <button class="btn btn-plum" type="submit">Save card</button>
          </form>
        </div>
      </section>

      <div class="account-cards">
        <section class="panel" id="details">
          <div class="panel__head">
            <h2>Contact &amp; delivery</h2>
            <span>02</span>
          </div>
          <?php if (isset($errors['form'])): ?><div class="rb-alert rb-alert--error" role="alert"><span><?= e($errors['form']) ?></span></div><?php endif; ?>
          <form method="post" class="form-stack" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile">
            <div class="form-field">
              <label for="email">Email address</label>
              <input id="email" name="email" type="email" maxlength="190" value="<?= e($profile['email']) ?>" required <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
              <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
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
            <button class="btn btn-outline" type="submit">Save contact details</button>
          </form>
        </section>

        <section class="panel">
          <div class="panel__head">
            <h2>Change password</h2>
            <span>03</span>
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
