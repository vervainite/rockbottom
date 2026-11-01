<?php
declare(strict_types=1);
require_once __DIR__ . '/config/app.php';

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantity  = (int) ($_POST['quantity'] ?? 1);
    [$ok, $message] = cart_add($pdo, $productId, $quantity);
    flash($ok ? 'success' : 'error', $message);
    go(url('index.php#shop'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'contact') {
    $name    = rb_post_string('name', 120);
    $email   = mb_strtolower(rb_post_string('email', 190));
    $message = rb_post_string('message', 2000);
    $errors  = [];

    if (!empty($_POST['website'])) {
        flash('success', 'Thank you — your note is on its way.');
        go(url('index.php#contact'));
    }
    if (mb_strlen($name) < 2) {
        $errors['name'] = 'Please tell us your name.';
    }
    if (!rb_valid_email($email)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (mb_strlen($message) < 10) {
        $errors['message'] = 'A few more words, please — at least 10 characters.';
    }

    if ($errors !== []) {
        $_SESSION['contact_errors'] = $errors;
        $_SESSION['contact_old'] = ['name' => $name, 'email' => $email, 'message' => $message];
        flash('error', 'Please check the highlighted fields.');
        go(url('index.php#contact'));
    }

    $stmt = $pdo->prepare('INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, $message]);
    flash('success', 'Your note is safely below the surface. We will reply by email soon.');
    go(url('index.php#contact'));
}

$contactErrors = $_SESSION['contact_errors'] ?? [];
$contactOld = $_SESSION['contact_old'] ?? [];
unset($_SESSION['contact_errors'], $_SESSION['contact_old']);

$stmt = $pdo->query(
    "SELECT p.*, c.name AS category, c.slug AS category_slug
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.active = 1
     ORDER BY p.created_at DESC, p.id DESC
     LIMIT " . RB_MAX_ACTIVE_PRODUCTS
);
$products = $stmt->fetchAll();

$categories = $pdo->query(
    "SELECT c.id, c.name, COUNT(p.id) AS total
     FROM categories c
     JOIN products p ON p.category_id = c.id AND p.active = 1
     GROUP BY c.id, c.name
     ORDER BY c.name"
)->fetchAll();

$catalogData = [];
foreach ($products as $product) {
    $catalogData[] = [
        'id'          => (int) $product['id'],
        'name'        => $product['name'],
        'description' => $product['description'],
        'price'       => (float) $product['price'],
        'stock'       => (int) $product['stock'],
        'category'    => $product['category'] ?? 'Collectible',
        'image'       => rb_product_image_url($product['image']),
    ];
}

$page_title = 'RockBottom — Cute Things From The Depths';
$page_description = 'A curated collection of rare finds, mysterious characters, and little treasures waiting beneath the surface.';
$body_class = 'page-home';
$nav_active = 'home';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
show_flash();
?>
<main id="main">
  <section class="hero" aria-labelledby="heroTitle">
    <div class="hero__backdrop" aria-hidden="true"></div>
    <div class="hero__doodles" aria-hidden="true">
      <svg class="hero__reeds hero__reeds--left" viewBox="0 0 320 260">
        <g stroke="#453b3a" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
          <path d="M8 260c10-48 8-92 22-130 4 44 2 88-4 130" fill="#78866b"/>
          <path d="M36 260c-4-40 2-80 18-112 3 38 1 76-6 112" fill="#8d9a7e"/>
          <path d="M62 260c-3-32 3-62 16-88 5 30 3 60-3 88" fill="#78866b"/>
          <path d="M92 260c-2-26 3-50 13-70 4 24 2 48-2 70" fill="#8d9a7e"/>
          <path d="M120 260c3-54 1-102 11-150" fill="none"/>
          <path d="M156 260c-4-46 3-90 18-130" fill="none"/>
          <path d="M186 260c2-36 0-68 9-100" fill="none"/>
          <ellipse cx="128" cy="108" rx="7" ry="18" fill="#a99b8a" transform="rotate(5 128 108)"/>
          <ellipse cx="170" cy="132" rx="6" ry="15" fill="#453b3a" transform="rotate(-6 170 132)"/>
          <path d="M112 206c-16-9-28-24-34-42 17 9 29 24 34 42Z" fill="#78866b"/>
          <path d="M150 196c15-10 25-24 30-41-16 8-27 23-30 41Z" fill="#8d9a7e"/>
          <path d="M94 238c-12-6-21-17-26-30 13 6 22 17 26 30Z" fill="#78866b"/>
          <path d="M200 260c-7-14-1-27 15-32 20-6 40 2 48 15 5 8 4 12 0 17Z" fill="#a99b8a"/>
          <path d="M224 244c7-8 18-10 27-5" fill="none" stroke="#ede5d8"/>
          <path d="M46 260c3-17 5-29 12-40" fill="none"/>
          <circle cx="58" cy="214" r="3.4" fill="#ede5d8"/>
          <circle cx="65" cy="220" r="2.4" fill="#ede5d8"/>
          <path d="M272 232c2-13 3-22 8-30" fill="none"/>
          <circle cx="280" cy="195" r="3" fill="#c18b65"/>
        </g>
      </svg>
      <svg class="hero__reeds hero__reeds--right" viewBox="0 0 320 260">
        <g stroke="#453b3a" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
          <path d="M8 260c10-48 8-92 22-130 4 44 2 88-4 130" fill="#78866b"/>
          <path d="M36 260c-4-40 2-80 18-112 3 38 1 76-6 112" fill="#8d9a7e"/>
          <path d="M62 260c-3-32 3-62 16-88 5 30 3 60-3 88" fill="#78866b"/>
          <path d="M92 260c-2-26 3-50 13-70 4 24 2 48-2 70" fill="#8d9a7e"/>
          <path d="M120 260c3-54 1-102 11-150" fill="none"/>
          <path d="M156 260c-4-46 3-90 18-130" fill="none"/>
          <path d="M186 260c2-36 0-68 9-100" fill="none"/>
          <ellipse cx="128" cy="108" rx="7" ry="18" fill="#a99b8a" transform="rotate(5 128 108)"/>
          <ellipse cx="170" cy="132" rx="6" ry="15" fill="#453b3a" transform="rotate(-6 170 132)"/>
          <path d="M112 206c-16-9-28-24-34-42 17 9 29 24 34 42Z" fill="#78866b"/>
          <path d="M150 196c15-10 25-24 30-41-16 8-27 23-30 41Z" fill="#8d9a7e"/>
          <path d="M94 238c-12-6-21-17-26-30 13 6 22 17 26 30Z" fill="#78866b"/>
          <path d="M200 260c-7-14-1-27 15-32 20-6 40 2 48 15 5 8 4 12 0 17Z" fill="#a99b8a"/>
          <path d="M224 244c7-8 18-10 27-5" fill="none" stroke="#ede5d8"/>
          <path d="M46 260c3-17 5-29 12-40" fill="none"/>
          <circle cx="58" cy="214" r="3.4" fill="#ede5d8"/>
          <circle cx="65" cy="220" r="2.4" fill="#ede5d8"/>
          <path d="M272 232c2-13 3-22 8-30" fill="none"/>
          <circle cx="280" cy="195" r="3" fill="#c18b65"/>
        </g>
      </svg>
      <svg class="hero__float" viewBox="0 0 240 240">
        <g fill="none" stroke="#ede5d8" stroke-width="2" stroke-linecap="round" opacity=".7">
          <circle cx="24" cy="42" r="7"/>
          <circle cx="46" cy="20" r="4"/>
          <circle cx="10" cy="74" r="3"/>
          <path d="M150 20l5 12 12 5-12 5-5 12-5-12-12-5 12-5z" fill="#ede5d8" stroke="none"/>
        </g>
      </svg>
    </div>
    <div class="rb-container hero__inner">
      <p class="hero__kicker"><span aria-hidden="true">✦</span> Cute things from the depths <span aria-hidden="true">✦</span></p>
      <h1 class="hero__title" id="heroTitle">NOT YOUR ORDINARY<br>LITTLE <em>TREASURES.</em></h1>
      <p class="hero__lede">Discover rare finds, mysterious characters, and collectibles waiting to be yours.</p>
      <div class="hero__actions">
        <a class="btn btn-lime" href="#shop">Shop the collection <span aria-hidden="true">↗</span></a>
        <a class="btn btn-ghost" href="#about">Discover more</a>
      </div>
      <dl class="hero__stats">
        <div><dt><?= count($products) ?></dt><dd>Little treasures</dd></div>
        <div><dt>3</dt><dd>Strange realms</dd></div>
        <div><dt>01</dt><dd>Found family</dd></div>
      </dl>
      <div class="hero__arrow" aria-hidden="true">
        <svg viewBox="0 0 34 46"><path d="M17 2c-8 10 6 15 0 26m0 0l-7-7m7 7l8-6"/></svg>
      </div>
    </div>
    <div class="hero__marquee" aria-hidden="true">
      <div class="hero__marquee-track">
        <span>Figures</span><i>✦</i><span>Blind boxes</span><i>✦</i><span>Keychains</span><i>✦</i><span>Little oddities</span><i>✦</i>
        <span>Figures</span><i>✦</i><span>Blind boxes</span><i>✦</i><span>Keychains</span><i>✦</i><span>Little oddities</span><i>✦</i>
      </div>
    </div>
  </section>

  <section class="about section" id="about" aria-labelledby="aboutTitle">
    <svg class="about__orbit" viewBox="0 0 460 460" aria-hidden="true">
      <path d="M230 18c118-10 218 78 214 202-4 128-98 226-220 222C104 438 16 336 30 208 43 92 122 30 230 18Z" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="8 12" stroke-linecap="round"/>
      <path d="M364 52l7 18 18 7-18 7-7 18-7-18-18-7 18-7z" fill="currentColor"/>
      <circle cx="76" cy="120" r="6" fill="none" stroke="currentColor" stroke-width="2"/>
      <circle cx="56" cy="104" r="3" fill="none" stroke="currentColor" stroke-width="1.6"/>
      <path d="M92 382c8-10 21-10 29 0s21 10 29 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>
    <div class="rb-container about__grid">
      <div class="about__visual reveal">
        <div class="about__art">
          <img src="<?= e(asset('images/banners/cowboy-featured.webp')) ?>" alt="The Cowboy collectible strumming a ukulele beside a glowing lantern in a purple crystal cave" width="1024" height="1536" loading="lazy">
        </div>
        <div class="about__caption">
          <span aria-hidden="true">✦</span>
          <p>Found family<br><em>Series 01</em></p>
        </div>
      </div>
      <div class="about__copy reveal">
        <p class="eyebrow">Our story</p>
        <h2 id="aboutTitle">EVERY TREASURE<br>HAS A <em>STORY.</em></h2>
        <p class="about__lead">Welcome to RockBottom, where the cutest treasures are waiting beneath the surface. From mysterious blind boxes to adorable collectible figures, every find brings a little surprise.</p>
        <p>We make small worlds for the curious: soft-spoken creatures, impossible little friends, and characters who look like they wandered out of a dream. Each piece is chosen for its personality first, and its shine second. Dig a little deeper and discover something special.</p>
        <div class="about__facts">
          <div><span>01</span><p><strong>Small batch</strong>Selected in tiny runs, never mass-produced.</p></div>
          <div><span>02</span><p><strong>Hand-finished</strong>Little differences are part of the charm.</p></div>
          <div><span>03</span><p><strong>Sent with care</strong>Packed by hand, shipped from Cebu.</p></div>
        </div>
        <a class="text-link" href="#shop">Explore the collection <span aria-hidden="true">↗</span></a>
      </div>
    </div>
  </section>

  <section class="shop section" id="shop" aria-labelledby="shopTitle"
    data-products="<?= e(json_encode($catalogData, JSON_UNESCAPED_UNICODE)) ?>">
    <div class="shop__doodles" aria-hidden="true">
      <svg class="shop__jelly" viewBox="0 0 120 200">
        <g fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 76c0-28 18-48 42-48s42 20 42 48c-8-6-14-6-21 0-7-6-13-6-21 0-7-6-13-6-21 0-7-6-13-6-21 0Z"/>
          <path d="M34 80c-2 20 2 30-2 44-3 10-9 14-7 26"/>
          <path d="M52 82c2 24-4 36 0 52 3 12 0 20-4 28"/>
          <path d="M72 82c-2 18 4 30 0 46-2 12 2 20 6 28"/>
          <path d="M88 80c3 18-1 28 4 42 3 10 8 14 6 24"/>
          <path d="M56 28c6-5 12-5 18 0" stroke-width="1.8"/>
        </g>
        <circle cx="46" cy="48" r="3" fill="currentColor"/>
        <circle cx="70" cy="50" r="3" fill="currentColor"/>
      </svg>
      <svg class="shop__fish" viewBox="0 0 220 120">
        <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M30 60c14-12 32-10 40 4-8 14-26 16-40 4l-12 8 3-12-3-12Z"/>
          <path d="M120 30c10-8 22-7 28 3-6 10-18 11-28 3l-8 6 2-9-2-9Z"/>
          <path d="M150 82c12-10 27-8 34 4-7 12-22 13-34 4l-10 7 2-11-2-11Z"/>
          <circle cx="146" cy="38" r="3"/>
          <circle cx="166" cy="20" r="2"/>
          <circle cx="60" cy="64" r="1.5" fill="currentColor"/>
        </g>
      </svg>
    </div>
    <div class="rb-container">
      <div class="section-head">
        <div>
          <p class="eyebrow">The collection</p>
          <h2 id="shopTitle">FIND YOUR <em>TREASURE.</em></h2>
        </div>
        <p class="section-head__note">Small wonders.<br>Big personalities.</p>
      </div>

      <div class="catalog-tools" role="search" aria-label="Filter the collection">
        <label class="catalog-search">
          <span class="sr-only">Search treasures</span>
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.5 15.5L21 21"/></svg>
          <input type="search" id="catalogSearch" placeholder="Search treasures..." autocomplete="off">
        </label>
        <label class="catalog-select">
          <span class="sr-only">Filter by category</span>
          <select id="catalogCategory">
            <option value="">All categories</option>
            <?php foreach ($categories as $category): ?>
              <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?> (<?= (int) $category['total'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="catalog-select">
          <span class="sr-only">Sort products</span>
          <select id="catalogSort">
            <option value="newest">Sort: Newest</option>
            <option value="price-asc">Price: Low to high</option>
            <option value="price-desc">Price: High to low</option>
          </select>
        </label>
        <button type="button" class="catalog-reset" id="catalogReset" hidden>Clear filters <span aria-hidden="true">×</span></button>
      </div>

      <div class="catalog-status">
        <p id="catalogCount" aria-live="polite">Showing <?= count($products) ?> treasures</p>
        <p class="catalog-status__hint"><?= count($products) ?> / <?= RB_MAX_ACTIVE_PRODUCTS ?> active finds</p>
      </div>

      <?php if ($products): ?>
        <div class="product-grid" id="productGrid">
          <?php foreach ($products as $index => $p): ?>
            <?php $p['_index'] = $index; require __DIR__ . '/includes/product-card.php'; ?>
          <?php endforeach; ?>
        </div>
        <div class="catalog-empty" id="catalogEmpty" hidden>
          <span aria-hidden="true">✦</span>
          <h3>Nothing down here.</h3>
          <p>Try a different word, or clear the filters to see every treasure.</p>
          <button class="btn btn-outline" type="button" id="catalogResetEmpty">Show everything</button>
        </div>
      <?php else: ?>
        <div class="catalog-empty">
          <span aria-hidden="true">✦</span>
          <h3>The shelves are quiet.</h3>
          <p>New treasures are being unearthed. Check back soon.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="promo" aria-labelledby="promoTitle">
    <div class="rb-container promo__grid">
      <svg class="promo__deco promo__deco--bubbles" viewBox="0 0 140 170" aria-hidden="true">
        <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity=".55">
          <circle cx="30" cy="120" r="9"/>
          <circle cx="54" cy="90" r="5"/>
          <circle cx="16" cy="82" r="4"/>
          <circle cx="96" cy="58" r="6"/>
          <circle cx="116" cy="30" r="3.5"/>
          <path d="M78 142c7-9 18-9 25 0s18 9 25 0"/>
        </g>
      </svg>
      <svg class="promo__deco promo__deco--spark" viewBox="0 0 160 120" aria-hidden="true">
        <g fill="currentColor" opacity=".6">
          <path d="M34 20l5 13 13 5-13 5-5 13-5-13-13-5 13-5z"/>
          <path d="M118 68l3.5 9 9 3.5-9 3.5-3.5 9-3.5-9-9-3.5 9-3.5z"/>
        </g>
        <path d="M18 92c7-9 18-9 25 0s18 9 25 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity=".55"/>
      </svg>
      <div class="promo__copy reveal">
        <p class="eyebrow eyebrow--light">A surprise in every corner</p>
        <h2 id="promoTitle">THE UNKNOWN<br>IS THE <em>BEST PART.</em></h2>
        <p>Every box holds a little mystery. Every mystery holds a new treasure.</p>
        <a class="btn btn-lime" href="#shop">Find your next surprise <span aria-hidden="true">↗</span></a>
        <p class="promo__foot">Blind boxes · Sealed · One of ten</p>
      </div>
      <div class="promo__visual reveal">
        <img src="<?= e(asset('images/hero/promo-art-boys.webp')) ?>" alt="Night Walker collectible sitting with a glowing lantern in a crystal cave" width="1024" height="1280" loading="lazy">
        <span class="promo__stamp">OPEN<br>IF<br>BRAVE</span>
      </div>
    </div>
  </section>

  <section class="contact section" id="contact" aria-labelledby="contactTitle">
    <svg class="contact__wave" viewBox="0 0 1440 40" preserveAspectRatio="none" aria-hidden="true">
      <path d="M0 30c60-16 120-16 180 0s120 16 180 0 120-16 180 0 120 16 180 0 120-16 180 0 120 16 180 0 120-16 180 0 120 16 180 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
    </svg>
    <div class="rb-container contact__grid">
      <div class="contact__copy">
        <p class="eyebrow">Let’s talk treasures</p>
        <h2 id="contactTitle">Have a little <em>question?</em></h2>
        <p>Need help finding a treasure, checking on an order, or choosing a gift for someone who likes strange little things? Send us a note.</p>
        <dl class="contact__details">
          <div><dt>Email</dt><dd><a href="mailto:hello@rockbottom.ph">hello@rockbottom.ph</a></dd></div>
          <div><dt>Hours</dt><dd>Monday–Saturday, 10am–7pm</dd></div>
          <div><dt>Studio</dt><dd>Cebu, Philippines</dd></div>
        </dl>
      </div>
      <form class="contact-form" method="post" action="<?= e(url('index.php#contact')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="contact">
        <div class="contact-form__trap" aria-hidden="true">
          <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>
        <div class="form-field">
          <label for="contactName">Your name</label>
          <input id="contactName" name="name" type="text" maxlength="120" required value="<?= e($contactOld['name'] ?? '') ?>" <?= isset($contactErrors['name']) ? 'aria-invalid="true"' : '' ?>>
          <?php if (isset($contactErrors['name'])): ?><p class="field-error"><?= e($contactErrors['name']) ?></p><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="contactEmail">Email address</label>
          <input id="contactEmail" name="email" type="email" maxlength="190" required value="<?= e($contactOld['email'] ?? '') ?>" <?= isset($contactErrors['email']) ? 'aria-invalid="true"' : '' ?>>
          <?php if (isset($contactErrors['email'])): ?><p class="field-error"><?= e($contactErrors['email']) ?></p><?php endif; ?>
        </div>
        <div class="form-field">
          <label for="contactMessage">Your message</label>
          <textarea id="contactMessage" name="message" rows="4" maxlength="2000" required <?= isset($contactErrors['message']) ? 'aria-invalid="true"' : '' ?>><?= e($contactOld['message'] ?? '') ?></textarea>
          <?php if (isset($contactErrors['message'])): ?><p class="field-error"><?= e($contactErrors['message']) ?></p><?php endif; ?>
        </div>
        <button class="btn btn-plum" type="submit">Send a message <span aria-hidden="true">↗</span></button>
        <p class="contact-form__note">We reply by email, usually within one working day.</p>
      </form>
    </div>
  </section>
</main>

<div class="modal fade product-modal" id="productModal" tabindex="-1" aria-labelledby="productModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close details">
        <span aria-hidden="true">×</span>
      </button>
      <div class="modal-product">
        <div class="modal-product__media">
          <img id="modalImage" src="<?= e(asset('images/products/mystery-box.png')) ?>" alt="" width="1024" height="768">
        </div>
        <div class="modal-product__body">
          <p class="eyebrow" id="modalCategory">Collectible</p>
          <h2 id="productModalTitle">Treasure</h2>
          <p class="modal-product__desc" id="modalDescription"></p>
          <p class="modal-product__price" id="modalPrice"></p>
          <p class="modal-product__stock" id="modalStock"></p>
          <form method="post" action="<?= e(url('index.php')) ?>" id="modalForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" id="modalProductId" value="">
            <div class="modal-product__buy">
              <div class="qty-stepper" data-stepper>
                <button type="button" data-step="-1" aria-label="Decrease quantity">−</button>
                <label class="sr-only" for="modalQty">Quantity</label>
                <input id="modalQty" name="quantity" type="number" min="1" max="1" value="1" inputmode="numeric">
                <button type="button" data-step="1" aria-label="Increase quantity">+</button>
              </div>
              <button class="btn btn-plum modal-add" type="submit" id="modalAdd">Add to bag</button>
            </div>
          </form>
          <ul class="modal-product__notes">
            <li>Packed by hand in Cebu</li>
            <li>Cash on Delivery available</li>
            <li>Free local delivery over <?= money(RB_FREE_SHIPPING_AT) ?></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
