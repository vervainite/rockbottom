<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
header('Cache-Control: no-store');
require_login();

$userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$userStmt->execute([(int) user()['id']]);
$userRow = $userStmt->fetch();
if (!$userRow) {
    rb_logout_user();
    go(url('customer/login.php'));
}

$errors = [];
$phone = (string) ($userRow['phone'] ?? '');
$address = (string) ($userRow['address'] ?? '');
$notes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'place') {
    $phone = rb_post_string('phone', 30);
    $address = rb_post_string('address', 500);
    $notes = rb_post_string('notes', 500);

    if (!preg_match('/^[0-9+()\-\s]{7,30}$/', $phone)) {
        $errors['phone'] = 'Enter a phone number we can call for delivery.';
    }
    if (mb_strlen($address) < 10) {
        $errors['address'] = 'Enter your full delivery address.';
    }

    if ($errors === []) {
        try {
            $map = cart_map($pdo);
            if ($map === []) {
                throw new RuntimeException('Your bag is empty.');
            }

            $pdo->beginTransaction();

            $ids = array_keys($map);
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare(
                "SELECT p.* FROM products p WHERE p.active = 1 AND p.id IN ($marks) FOR UPDATE"
            );
            $stmt->execute(array_values($ids));
            $locked = [];
            foreach ($stmt->fetchAll() as $row) {
                $locked[(int) $row['id']] = $row;
            }
            if (count($locked) !== count($map)) {
                throw new RuntimeException('One of your treasures is no longer available. Please review your bag.');
            }

            $lines = [];
            $subtotal = 0.0;
            foreach ($map as $pid => $wanted) {
                $product = $locked[$pid];
                $qty = min((int) $wanted, RB_MAX_QTY);
                $stock = (int) $product['stock'];
                if ($qty < 1 || $stock < 1) {
                    throw new RuntimeException($product['name'] . ' is sold out. Remove it from your bag to continue.');
                }
                if ($qty > $stock) {
                    throw new RuntimeException('Only ' . $stock . ' of ' . $product['name'] . ' remain. Please update your bag.');
                }
                $price = (float) $product['price'];
                $lineTotal = round($price * $qty, 2);
                $subtotal += $lineTotal;
                $lines[] = [
                    'id'    => (int) $product['id'],
                    'name'  => (string) $product['name'],
                    'price' => $price,
                    'qty'   => $qty,
                    'total' => $lineTotal,
                ];
            }
            $subtotal = round($subtotal, 2);
            $shipping = $subtotal < RB_FREE_SHIPPING_AT ? RB_SHIPPING_FEE : 0.00;
            $total = round($subtotal + $shipping, 2);

            $orderStmt = $pdo->prepare(
                "INSERT INTO orders
                 (user_id, subtotal, shipping_fee, total, status, payment_method, shipping_address, contact_phone, notes)
                 VALUES (?, ?, ?, ?, 'Pending', 'Cash on Delivery', ?, ?, ?)"
            );
            $orderStmt->execute([
                (int) $userRow['id'], $subtotal, $shipping, $total,
                $address, $phone, $notes !== '' ? $notes : null,
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $updateStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
            $insertItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($lines as $line) {
                $updateStock->execute([$line['qty'], $line['id'], $line['qty']]);
                if ($updateStock->rowCount() !== 1) {
                    throw new RuntimeException('Stock changed while you were checking out. Please review your bag.');
                }
                $insertItem->execute([
                    $orderId, $line['id'], $line['name'], $line['price'], $line['qty'], $line['total'],
                ]);
            }

            $pdo->prepare(
                "INSERT INTO payments (order_id, method, amount, status) VALUES (?, 'Cash on Delivery', ?, 'Pending')"
            )->execute([$orderId, $total]);

            $pdo->prepare(
                'DELETE ci FROM cart_items ci JOIN cart c ON c.id = ci.cart_id WHERE c.user_id = ?'
            )->execute([(int) $userRow['id']]);

            $pdo->prepare('UPDATE users SET phone = ?, address = ? WHERE id = ?')
                ->execute([$phone, $address, (int) $userRow['id']]);
            $_SESSION['user']['name'] = (string) $userRow['name'];

            $pdo->commit();
            unset($_SESSION['cart']);
            go(url('customer/order-confirmation.php?id=' . $orderId));
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e instanceof RuntimeException && $e->getMessage() !== '') {
                $errors['form'] = $e->getMessage();
            } else {
                error_log('Checkout failed: ' . $e->getMessage());
                $errors['form'] = 'Your order could not be placed just now. Please try again.';
            }
        }
    }
}

$totals = cart_totals($pdo);
if (!$totals['rows'] && $errors === []) {
    flash('info', 'Your bag is empty — find a treasure first.');
    go(url('customer/cart.php'));
}

$page_title = 'Checkout — RockBottom';
$body_class = 'checkout-page';
$nav_active = 'account';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
show_flash();
?>
<main id="main" class="section checkout-section">
  <div class="rb-container">
    <header class="page-head">
      <div>
        <p class="eyebrow">Almost yours</p>
        <h1>CHECK<em>OUT.</em></h1>
      </div>
      <a class="text-link" href="<?= e(url('customer/cart.php')) ?>">← Back to bag</a>
    </header>

    <ol class="checkout-steps" aria-label="Checkout progress">
      <li class="is-current"><span>01</span> Delivery</li>
      <li><span>02</span> Payment</li>
      <li><span>03</span> Review</li>
    </ol>

    <?php if (isset($errors['form'])): ?>
      <div class="rb-alert rb-alert--error" role="alert"><span><?= e($errors['form']) ?></span></div>
    <?php endif; ?>

    <div class="checkout-layout">
      <form class="checkout-form" method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="place">

        <section class="panel">
          <div class="panel__head">
            <h2>Delivery details</h2>
            <span>01</span>
          </div>
          <div class="checkout-account">
            <div><span>Account</span><strong><?= e($userRow['name']) ?></strong></div>
            <div><span>Email</span><strong><?= e($userRow['email']) ?></strong></div>
            <a class="link-btn" href="<?= e(url('customer/profile.php')) ?>">Edit profile</a>
          </div>
          <div class="form-field">
            <label for="phone">Phone number</label>
            <input id="phone" name="phone" type="tel" maxlength="30" value="<?= e($phone) ?>" autocomplete="tel" required <?= isset($errors['phone']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['phone'])): ?><p class="field-error"><?= e($errors['phone']) ?></p><?php else: ?><p class="field-hint">We only use this to arrange delivery.</p><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="address">Full delivery address</label>
            <textarea id="address" name="address" rows="3" maxlength="500" autocomplete="street-address" required <?= isset($errors['address']) ? 'aria-invalid="true"' : '' ?>><?= e($address) ?></textarea>
            <?php if (isset($errors['address'])): ?><p class="field-error"><?= e($errors['address']) ?></p><?php endif; ?>
          </div>
          <div class="form-field">
            <label for="notes">Delivery notes <span>(optional)</span></label>
            <textarea id="notes" name="notes" rows="2" maxlength="500" placeholder="Landmarks, gate code, or a little hello."><?= e($notes) ?></textarea>
          </div>
        </section>

        <section class="panel">
          <div class="panel__head">
            <h2>Payment</h2>
            <span>02</span>
          </div>
          <div class="payment-options">
            <label class="payment-option is-selected">
              <input type="radio" name="payment" value="cod" checked>
              <span class="payment-option__mark" aria-hidden="true"></span>
              <span><strong>Cash on Delivery</strong><small>Pay in cash when your treasures arrive.</small></span>
            </label>
            <label class="payment-option payment-option--disabled">
              <input type="radio" name="payment" value="card" disabled>
              <span class="payment-option__mark" aria-hidden="true"></span>
              <span><strong>Card payment</strong><small>Coming later — not available yet.</small></span>
            </label>
          </div>
          <p class="payment-note">No payment is taken online. Our courier collects the exact total on delivery.</p>
        </section>

        <section class="panel panel--review">
          <div class="panel__head">
            <h2>Review your order</h2>
            <span>03</span>
          </div>
          <ul class="checkout-items">
            <?php foreach ($totals['rows'] as $item): ?>
              <li>
                <img src="<?= e(rb_product_image_url($item['image'])) ?>" alt="" width="596" height="464" loading="lazy">
                <div><strong><?= e($item['name']) ?></strong><span><?= (int) $item['qty'] ?> × <?= money($item['price']) ?></span></div>
                <span><?= money($item['line_total']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
          <p class="checkout-terms">By placing this order you agree to our delivery and returns policy. We will contact you if anything needs confirming.</p>
          <button class="btn btn-plum btn-block btn-lg" type="submit">Place order · <?= money($totals['total']) ?></button>
        </section>
      </form>

      <aside class="checkout-summary">
        <p class="eyebrow">Your summary</p>
        <h2>THE <em>DETAILS.</em></h2>
        <dl class="summary-lines">
          <div><dt>Subtotal</dt><dd><?= money($totals['subtotal']) ?></dd></div>
          <div><dt>Delivery</dt><dd><?= $totals['shipping'] > 0 ? money($totals['shipping']) : 'Free' ?></dd></div>
          <div><dt>Payment</dt><dd>Cash on Delivery</dd></div>
        </dl>
        <div class="summary-total"><span>Total</span><strong><?= money($totals['total']) ?></strong></div>
        <p class="checkout-summary__note">Need to change something? <a href="<?= e(url('customer/cart.php')) ?>">Edit your bag</a>.</p>
        <div class="checkout-help">
          <span aria-hidden="true">✦</span>
          <p>Questions about your order?<br><a href="mailto:hello@rockbottom.ph">hello@rockbottom.ph</a></p>
        </div>
      </aside>
    </div>
  </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
