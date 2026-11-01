<?php
if (!defined('RB_ROOT')) {
    require_once __DIR__ . '/../config/app.php';
}
?>
<footer class="site-footer">
  <div class="rb-container">
    <div class="footer-top">
      <div class="footer-brand">
        <a class="brand brand--light footer-brand__mark" href="<?= e(url('index.php')) ?>" aria-label="RockBottom home">
          <?php require __DIR__ . '/brand-mark.php'; ?>
          <span class="brand__word">RockBottom</span>
          <span class="brand__dot" aria-hidden="true">.</span>
        </a>
        <p>A small world of collectible characters, dug up from somewhere soft and strange.</p>
        <span class="footer-est">EST. 2026 · MANILA, PH</span>
      </div>
      <div class="footer-col">
        <h2>Explore</h2>
        <a href="<?= e(url('index.php#shop')) ?>">The collection</a>
        <a href="<?= e(url('index.php#about')) ?>">Our story</a>
        <a href="<?= e(url('index.php#contact')) ?>">Contact</a>
      </div>
      <div class="footer-col">
        <h2>Your account</h2>
        <a href="<?= e(url('customer/cart.php')) ?>">Your bag</a>
        <a href="<?= e(url('customer/orders.php')) ?>">Order history</a>
        <a href="<?= e(url('customer/profile.php')) ?>">Profile</a>
        <a href="<?= e(url('customer/login.php')) ?>">Sign in</a>
      </div>
      <div class="footer-col footer-col--contact">
        <h2>Say hello</h2>
        <a href="mailto:hello@rockbottom.ph">hello@rockbottom.ph</a>
        <span>Mon–Sat · 10am–7pm</span>
        <span>Cash on Delivery</span>
        <div class="footer-social" aria-label="Social channels">
          <span>Instagram</span><span>TikTok</span><span>Facebook</span>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <small>© <?= date('Y') ?> RockBottom. All treasures reserved.</small>
      <small class="footer-bottom__line">Cute things from the depths <span aria-hidden="true">✦</span> Made with care below the surface</small>
      <a href="#main" class="footer-top-link">Back to top ↑</a>
    </div>
  </div>
  <div class="footer-floor" aria-hidden="true">
    <svg viewBox="0 0 1440 190" preserveAspectRatio="xMidYMax meet">
      <g class="footer-floor__waves">
        <path d="M60 38c60-16 120 16 180 0s120-16 180 0 120 16 180 0 120-16 180 0 120 16 180 0 120-16 180 0 120 16 180 0 120-16 180 0"/>
        <path d="M210 62c52-13 104 13 156 0s104-13 156 0 104 13 156 0 104-13 156 0 104 13 156 0 104-13 156 0"/>
      </g>
      <path class="footer-floor__bed" d="M0 190V150c120-26 240 22 360-2s240-40 360-16 240 36 360 12 240-36 360-8V190Z"/>
      <g class="footer-floor__plants">
        <path d="M96 148c-10-24 10-34 0-58 14 20-2 36 8 58"/>
        <path d="M120 152c-8-20 8-30 0-48"/>
        <path d="M150 150c-12-22 8-34 0-56 12 18-2 34 10 56"/>
        <path d="M260 150c-9-18 7-28 0-46"/>
        <path d="M286 148c-12-24 9-34 0-58 14 20-2 36 10 58"/>
        <path d="M1236 146c-12-26 10-36 0-62 14 22-2 38 10 62"/>
        <path d="M1266 150c-9-20 7-30 0-50"/>
        <path d="M1302 148c-13-28 10-40 0-68 15 24-2 42 11 68"/>
        <path d="M1340 152c-8-18 6-28 0-46"/>
      </g>
      <g class="footer-floor__coral">
        <path d="M190 150c2-18-8-26-4-40 8 8 10 18 8 28 8-12 20-16 24-30 2 18-10 28-20 34 10 0 18 4 22 12"/>
        <path d="M1156 148c-2-16 8-24 4-38-8 8-9 18-7 27-8-11-19-15-23-28-2 17 9 27 19 32-9 1-17 5-21 12"/>
      </g>
      <g class="footer-floor__rocks">
        <path d="M520 178c-8-13 0-25 15-29 17-4 33 3 38 15 3 8 1 11-2 14Z"/>
        <path d="M940 180c-7-12 0-23 14-27 16-4 30 3 35 14 3 8 1 10-2 13Z"/>
        <path d="M556 164c5-6 13-8 20-5"/>
      </g>
      <g class="footer-floor__bubbles">
        <circle cx="350" cy="104" r="6"/>
        <circle cx="372" cy="82" r="3.5"/>
        <circle cx="705" cy="86" r="5"/>
        <circle cx="722" cy="62" r="2.6"/>
        <circle cx="1010" cy="98" r="4"/>
        <circle cx="1028" cy="76" r="2.4"/>
      </g>
      <g class="footer-floor__fish">
        <path class="footer-floor__fish-body" d="M690 146c12-11 28-10 34 2-6 12-22 14-34 4l-11 7 3-11-3-11Z"/>
        <circle class="footer-floor__fish-eye" cx="714" cy="150" r="1.7"/>
        <path class="footer-floor__fish-antenna" d="M700 140c-2-6 1-10 6-10"/>
        <circle class="footer-floor__fish-glow" cx="707" cy="128" r="3.2"/>
      </g>
    </svg>
  </div>
</footer>
<script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>" defer></script>
<script src="<?= e(asset('js/script.js')) ?>" defer></script>
</body>
</html>
