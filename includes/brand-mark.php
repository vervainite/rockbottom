<?php
if (!defined('RB_ROOT')) {
    require_once __DIR__ . '/../config/app.php';
}
?>
<img
  class="brand__mark"
  src="<?= e(asset('images/logo/rock-mark-480.webp')) ?>"
  srcset="<?= e(asset('images/logo/rock-mark-240.webp')) ?> 240w, <?= e(asset('images/logo/rock-mark-480.webp')) ?> 480w"
  sizes="3.8rem"
  width="480"
  height="290"
  alt=""
  aria-hidden="true"
  decoding="async"
>
