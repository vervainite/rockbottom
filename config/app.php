<?php
declare(strict_types=1);

define('RB_ROOT', dirname(__DIR__));
define('RB_APP_NAME', 'RockBottom');
define('RB_TAGLINE', 'Cute Things From The Depths.');
define('RB_MAX_ACTIVE_PRODUCTS', 10);
define('RB_MAX_QTY', 10);
define('RB_SHIPPING_FEE', 80.00);
define('RB_FREE_SHIPPING_AT', 1500.00);
define('RB_ORDER_STATUSES', ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled']);
define('RB_CARD_STYLES', [
    'reef'   => ['label' => 'Deep Reef',     'note' => 'Ink water, kelp and coral.',        'group' => 'house'],
    'dune'   => ['label' => 'Warm Dune',     'note' => 'Sun-bleached paper and rose ink.',  'group' => 'house'],
    'moon'   => ['label' => 'Moon Tide',     'note' => 'Plum night and salt stars.',        'group' => 'house'],
    'scrap'  => ['label' => 'Paper Scrap',   'note' => 'Taped notes and ruler lines.',      'group' => 'house'],
    'bubble' => ['label' => 'Bubblegum',     'note' => 'Lilac fizz and soft bubbles.',      'group' => 'house'],

    'void'   => ['label' => 'Void Ink',      'note' => 'Black water and ember scratches.',  'group' => 'dark'],
    'ash'    => ['label' => 'Ash Charcoal',  'note' => 'Smoke, bone and amber grit.',       'group' => 'dark'],
    'raven'  => ['label' => 'Raven Wing',    'note' => 'Cold blue-black with silver dust.', 'group' => 'dark'],
    'rust'   => ['label' => 'Rust & Bone',   'note' => 'Oxblood leather and rust.',         'group' => 'dark'],
    'crypt'  => ['label' => 'Crypt Moss',    'note' => 'Wet stone and toxic green.',        'group' => 'dark'],

    'linen'  => ['label' => 'Sea Linen',     'note' => 'Washed cloth and quiet teal.',      'group' => 'soft'],
    'sage'   => ['label' => 'Sage Wash',     'note' => 'Faded herb and soft moss.',         'group' => 'soft'],
    'clay'   => ['label' => 'Soft Clay',     'note' => 'Warm putty and dusty rose.',        'group' => 'soft'],
    'fog'    => ['label' => 'Morning Fog',   'note' => 'Cool grey with pale blue ink.',     'group' => 'soft'],
    'honey'  => ['label' => 'Honey Milk',    'note' => 'Cream paper and warm honey.',       'group' => 'soft'],

    'blush'  => ['label' => 'Blush Paper',   'note' => 'Powder pink and pencil lines.',     'group' => 'pink'],
    'rose'   => ['label' => 'Rose Ink',      'note' => 'Deep rose and wine lettering.',     'group' => 'pink'],
    'petal'  => ['label' => 'Petal Press',   'note' => 'Pressed petals and lilac fizz.',    'group' => 'pink'],
    'cherry' => ['label' => 'Cherry Fizz',   'note' => 'Soda pink and cherry dots.',        'group' => 'pink'],
    'mauve'  => ['label' => 'Mauve Hour',    'note' => 'Dusk mauve and soft plum.',         'group' => 'pink'],
]);

define('RB_CARD_STYLE_GROUPS', [
    'house' => 'The house five',
    'dark'  => 'Darker & edgier',
    'soft'  => 'Soft focus',
    'pink'  => 'Pink shades',
]);

define('RB_PROFILE_UPLOAD_MAX', 5 * 1024 * 1024);

$debug = getenv('RB_DEBUG') === '1';
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

/**
 * Resolve the app's URL path ("" at a document root, "/rockbottom" in XAMPP htdocs).
 */
function rb_base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $root = realpath(RB_ROOT);
    $doc  = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $base = '';
    if ($root && $doc) {
        $rootN = str_replace('\\', '/', $root);
        $docN  = rtrim(str_replace('\\', '/', $doc), '/');
        if ($docN !== '' && stripos($rootN, $docN) === 0) {
            $base = substr($rootN, strlen($docN));
        }
    }
    $base = '/' . trim(str_replace('\\', '/', $base), '/');
    return $base === '/' ? '' : $base;
}

define('RB_BASE_URL', rb_base_url());

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_name('ROCKBOTTOMSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => RB_BASE_URL === '' ? '/' : RB_BASE_URL,
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header(
        "Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; "
        . "img-src 'self' data:; font-src 'self'; style-src 'self' 'unsafe-inline'; "
        . "script-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'self'"
    );
    if ($isHttps ?? false) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

$pdo = require RB_ROOT . '/config/database.php';

/* -------------------------------------------------------------------------
 * Output, URLs and validation
 * ---------------------------------------------------------------------- */

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return RB_BASE_URL . ($path === '' ? '/' : '/' . $path);
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $href = url('assets/' . $path);
    if (str_contains($path, '..')) {
        return $href;
    }
    $file = RB_ROOT . '/assets/' . $path;
    if (is_file($file)) {
        $mtime = filemtime($file);
        if ($mtime !== false) {
            $href .= '?v=' . $mtime;
        }
    }
    return $href;
}

function money(float|int|string $amount): string
{
    return '₱' . number_format((float) $amount, 2);
}

function go(string $location, int $status = 302): never
{
    header('Location: ' . $location, true, $status);
    exit;
}

function rb_safe_next(?string $next): ?string
{
    if ($next === null || $next === '') {
        return null;
    }
    if ($next[0] !== '/' || str_starts_with($next, '//') || str_contains($next, '\\')) {
        return null;
    }
    if (preg_match('/[\r\n]/', $next)) {
        return null;
    }
    return $next;
}

function rb_post_string(string $key, int $max = 5000): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    return trim(mb_substr($value, 0, $max));
}

function rb_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 190;
}

function rb_slugify(string $text, string $fallback = 'treasure'): string
{
    $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $text), '-'));
    $slug = trim(substr($slug, 0, 140), '-');
    return $slug !== '' ? $slug : $fallback;
}

function rb_date(?string $sqlDate, string $format = 'M j, Y'): string
{
    if (!$sqlDate) {
        return '—';
    }
    $ts = strtotime($sqlDate);
    return $ts ? date($format, $ts) : '—';
}

function rb_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';
    return mb_strtoupper($first . $last);
}

/* -------------------------------------------------------------------------
 * CSRF
 * ---------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['csrf'] ?? '';
    return is_string($sent)
        && isset($_SESSION['csrf'])
        && is_string($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $sent);
}

function rb_csrf_fail(): never
{
    http_response_code(419);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Session expired — RockBottom</title>';
    echo '<link rel="stylesheet" href="' . e(asset('css/style.css')) . '">';
    echo '<main class="error-page"><p class="eyebrow">A LITTLE GLITCH</p><h1>Your session expired.</h1>';
    echo '<p>Please go back, refresh the page, and try again.</p>';
    echo '<a class="btn btn-plum" href="' . e(url('index.php')) . '">Return to the store</a></main></body></html>';
    exit;
}

/* -------------------------------------------------------------------------
 * Flash messages
 * ---------------------------------------------------------------------- */

function flash(string $type, string $message): void
{
    if (!in_array($type, ['success', 'error', 'info'], true)) {
        $type = 'info';
    }
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($flashes) ? $flashes : [];
}

function show_flash(): void
{
    foreach (pull_flashes() as $flash) {
        $type = in_array($flash['type'] ?? '', ['success', 'error', 'info'], true)
            ? $flash['type']
            : 'info';
        echo '<div class="rb-alert rb-alert--' . e($type) . '" role="' . ($type === 'error' ? 'alert' : 'status') . '">';
        echo '<span>' . e($flash['message'] ?? '') . '</span>';
        echo '<button type="button" class="rb-alert__close" aria-label="Dismiss message" data-dismiss-alert>&times;</button>';
        echo '</div>';
    }
}

/* -------------------------------------------------------------------------
 * Authentication
 * ---------------------------------------------------------------------- */

function user(): ?array
{
    $user = $_SESSION['user'] ?? null;
    return is_array($user) ? $user : null;
}

function is_admin(): bool
{
    return (user()['role'] ?? '') === 'admin';
}

function require_login(): void
{
    if (user() !== null) {
        return;
    }
    flash('info', 'Sign in to keep going.');
    $next = rawurlencode($_SERVER['REQUEST_URI'] ?? url('index.php'));
    go(url('customer/login.php?next=' . $next));
}

function require_admin(): void
{
    if (is_admin()) {
        return;
    }
    if (user() !== null) {
        flash('error', 'That area is reserved for store staff.');
    }
    go(url('admin/login.php'));
}

function rb_login_user(PDO $pdo, array $row): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'     => (int) $row['id'],
        'name'   => (string) $row['name'],
        'email'  => (string) $row['email'],
        'role'   => (string) $row['role'],
        'avatar' => $row['avatar'] ?? null,
    ];
    rb_merge_guest_cart($pdo, (int) $row['id']);
    unset($_SESSION['csrf']);
}

function rb_logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
}

/* -------------------------------------------------------------------------
 * Cart (database-backed for members, session-backed for guests)
 * ---------------------------------------------------------------------- */

function rb_cart_row_id(PDO $pdo, int $userId, bool $create = true): ?int
{
    if ($create) {
        $pdo->prepare('INSERT IGNORE INTO cart (user_id) VALUES (?)')->execute([$userId]);
    }
    $stmt = $pdo->prepare('SELECT id FROM cart WHERE user_id = ?');
    $stmt->execute([$userId]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

function cart_map(PDO $pdo, ?int $userId = null): array
{
    if ($userId === null) {
        $userId = user()['id'] ?? null;
    }
    if ($userId === null) {
        $map = [];
        foreach (($_SESSION['cart'] ?? []) as $pid => $qty) {
            $pid = (int) $pid;
            $qty = (int) $qty;
            if ($pid > 0 && $qty > 0) {
                $map[$pid] = $qty;
            }
        }
        return $map;
    }

    $cartId = rb_cart_row_id($pdo, $userId, false);
    if ($cartId === null) {
        return [];
    }
    $stmt = $pdo->prepare('SELECT product_id, quantity FROM cart_items WHERE cart_id = ? ORDER BY id');
    $stmt->execute([$cartId]);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(int) $row['product_id']] = (int) $row['quantity'];
    }
    return $map;
}

function rb_cart_write(PDO $pdo, int $productId, int $qty): void
{
    $member = user()['id'] ?? null;
    if ($qty < 1) {
        if ($member === null) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $cartId = rb_cart_row_id($pdo, (int) $member, false);
            if ($cartId !== null) {
                $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?')
                    ->execute([$cartId, $productId]);
            }
        }
        return;
    }

    if ($member === null) {
        $_SESSION['cart'][$productId] = $qty;
        return;
    }

    $cartId = rb_cart_row_id($pdo, (int) $member, true);
    if ($cartId === null) {
        return;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)'
    );
    $stmt->execute([$cartId, $productId, $qty]);
}

/**
 * @return array{0: bool, 1: string}
 */
function cart_set_qty(PDO $pdo, int $productId, int $qty): array
{
    $stmt = $pdo->prepare('SELECT id, name, stock FROM products WHERE id = ? AND active = 1');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) {
        return [false, 'That treasure is no longer available.'];
    }
    if ((int) $product['stock'] < 1) {
        return [false, $product['name'] . ' is sold out.'];
    }

    $qty = max(0, min(RB_MAX_QTY, $qty));
    $limited = false;
    if ($qty > (int) $product['stock']) {
        $qty = (int) $product['stock'];
        $limited = true;
    }
    rb_cart_write($pdo, $productId, $qty);
    if ($qty === 0) {
        return [true, $product['name'] . ' was removed from your bag.'];
    }
    return [true, $limited
        ? 'Only ' . $qty . ' of ' . $product['name'] . ' available — quantity adjusted.'
        : $product['name'] . ' is in your bag.'];
}

/**
 * @return array{0: bool, 1: string}
 */
function cart_add(PDO $pdo, int $productId, int $qty): array
{
    $stmt = $pdo->prepare('SELECT id, name, stock FROM products WHERE id = ? AND active = 1');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) {
        return [false, 'That treasure is no longer available.'];
    }
    if ((int) $product['stock'] < 1) {
        return [false, $product['name'] . ' is sold out.'];
    }

    $qty = max(1, min(RB_MAX_QTY, $qty));
    $map = cart_map($pdo);
    $current = (int) ($map[$productId] ?? 0);
    $target = min((int) $product['stock'], RB_MAX_QTY, $current + $qty);
    if ($target <= $current) {
        return [false, 'You already have the last available ' . $product['name'] . '.'];
    }
    rb_cart_write($pdo, $productId, $target);
    return [true, $product['name'] . ' was added to your bag.'];
}

function cart_remove(PDO $pdo, int $productId): void
{
    rb_cart_write($pdo, $productId, 0);
}

function rb_merge_guest_cart(PDO $pdo, int $userId): void
{
    $guest = $_SESSION['cart'] ?? [];
    unset($_SESSION['cart']);
    if (!is_array($guest) || $guest === []) {
        return;
    }

    $select = $pdo->prepare('SELECT stock FROM products WHERE id = ? AND active = 1');
    foreach ($guest as $pid => $qty) {
        $pid = (int) $pid;
        $qty = (int) $qty;
        if ($pid < 1 || $qty < 1) {
            continue;
        }
        $select->execute([$pid]);
        $stock = $select->fetchColumn();
        if ($stock === false || (int) $stock < 1) {
            continue;
        }
        $map = cart_map($pdo, $userId);
        $current = (int) ($map[$pid] ?? 0);
        $target = min((int) $stock, RB_MAX_QTY, $current + $qty);
        rb_cart_write($pdo, $pid, $target);
    }
}

function cart_rows(PDO $pdo): array
{
    $map = cart_map($pdo);
    if ($map === []) {
        return [];
    }

    $ids = array_keys($map);
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT p.*, c.name AS category, c.slug AS category_slug
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.active = 1 AND p.id IN ($marks)"
    );
    $stmt->execute(array_values($ids));
    $byId = [];
    foreach ($stmt->fetchAll() as $row) {
        $byId[(int) $row['id']] = $row;
    }

    $rows = [];
    foreach ($map as $pid => $requested) {
        if (!isset($byId[$pid])) {
            continue;
        }
        $row = $byId[$pid];
        $stock = (int) $row['stock'];
        $qty = min((int) $requested, $stock, RB_MAX_QTY);
        $row['requested_qty'] = (int) $requested;
        $row['qty'] = $qty;
        $row['line_total'] = round($qty * (float) $row['price'], 2);
        $rows[] = $row;
    }
    return $rows;
}

function cart_count(PDO $pdo): int
{
    return array_sum(array_map(static fn (array $row): int => (int) $row['qty'], cart_rows($pdo)));
}

function cart_totals(PDO $pdo): array
{
    $rows = cart_rows($pdo);
    $subtotal = round(array_sum(array_column($rows, 'line_total')), 2);
    $shipping = ($subtotal > 0 && $subtotal < RB_FREE_SHIPPING_AT) ? RB_SHIPPING_FEE : 0.00;
    return [
        'rows'     => $rows,
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total'    => round($subtotal + $shipping, 2),
    ];
}

function rb_clear_cart(PDO $pdo): void
{
    $member = user()['id'] ?? null;
    if ($member !== null) {
        $cartId = rb_cart_row_id($pdo, (int) $member, false);
        if ($cartId !== null) {
            $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);
        }
    }
    unset($_SESSION['cart']);
}

/* -------------------------------------------------------------------------
 * Products, categories and uploads
 * ---------------------------------------------------------------------- */

function rb_active_product_count(PDO $pdo, ?int $excludeId = null): int
{
    if ($excludeId !== null) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE active = 1 AND id <> ?');
        $stmt->execute([$excludeId]);
    } else {
        $stmt = $pdo->query('SELECT COUNT(*) FROM products WHERE active = 1');
    }
    return (int) $stmt->fetchColumn();
}

function rb_product_image_url(?string $image): string
{
    $name = basename((string) $image);
    if ($name === '' || !is_file(RB_ROOT . '/assets/images/products/' . $name)) {
        $name = 'mystery-box.png';
    }
    return asset('images/products/' . rawurlencode($name));
}

/**
 * Validate, re-encode and store an uploaded product image.
 * Throws RuntimeException with a safe, user-facing message on failure.
 */
function rb_handle_image_upload(array $file, string $slug): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Choose an image to upload (5 MB maximum).');
    }
    $tmp = $file['tmp_name'] ?? '';
    if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('The upload could not be read. Please try again.');
    }

    $size = filesize($tmp);
    if ($size === false || $size < 1) {
        throw new RuntimeException('The uploaded file is empty.');
    }
    if ($size > 5 * 1024 * 1024) {
        throw new RuntimeException('The image is larger than 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $allowed = [
        'image/jpeg' => IMAGETYPE_JPEG,
        'image/png'  => IMAGETYPE_PNG,
        'image/webp' => IMAGETYPE_WEBP,
    ];
    if (!is_string($mime) || !isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG or WebP images are allowed.');
    }

    $info = @getimagesize($tmp);
    if ($info === false || !isset($info[0], $info[1], $info[2])) {
        throw new RuntimeException('That file is not a readable image.');
    }
    if ($info[2] !== $allowed[$mime]) {
        throw new RuntimeException('The image type does not match its contents.');
    }
    [$width, $height] = $info;
    if ($width < 300 || $height < 300) {
        throw new RuntimeException('Product images need to be at least 300 × 300 pixels.');
    }
    if ($width > 6000 || $height > 6000) {
        throw new RuntimeException('Product images must be 6000 pixels or smaller.');
    }

    if (!function_exists('imagewebp') || !function_exists('imagecreatefromstring')) {
        throw new RuntimeException('The PHP GD extension with WebP support is required for uploads.');
    }

    $raw = file_get_contents($tmp);
    $source = $raw !== false ? @imagecreatefromstring($raw) : false;
    if (!$source) {
        throw new RuntimeException('The image could not be processed.');
    }

    $max = 1200;
    $scale = min(1, $max / max($width, $height));
    $newW = max(1, (int) round($width * $scale));
    $newH = max(1, (int) round($height * $scale));
    $canvas = imagecreatetruecolor($newW, $newH);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
    imagealphablending($canvas, true);
    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);
    imagedestroy($source);

    $filename = rb_slugify($slug, 'treasure') . '-' . bin2hex(random_bytes(4)) . '.webp';
    $dir = RB_ROOT . '/assets/images/products';
    if (!is_dir($dir) || !is_writable($dir)) {
        imagedestroy($canvas);
        throw new RuntimeException('The product image folder is not writable. Check folder permissions.');
    }
    $destination = $dir . '/' . $filename;
    $saved = imagewebp($canvas, $destination, 86);
    imagedestroy($canvas);
    if (!$saved) {
        throw new RuntimeException('The image could not be saved.');
    }
    @chmod($destination, 0644);

    return $filename;
}

function rb_delete_uploaded_image(string $filename): void
{
    if (!preg_match('/^[a-z0-9-]+-[a-f0-9]{8}\.webp$/', $filename)) {
        return; // Keep the original supplied brand assets.
    }
    $path = RB_ROOT . '/assets/images/products/' . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

/* -------------------------------------------------------------------------
 * Member profile card
 * ---------------------------------------------------------------------- */

function rb_card_style(?string $style): string
{
    return isset(RB_CARD_STYLES[$style ?? '']) ? (string) $style : 'reef';
}

function rb_profile_image_url(?string $file): ?string
{
    if (!is_string($file) || !preg_match('/^u\d+-(?:avatar|cover)-[a-f0-9]{8}\.webp$/', $file)) {
        return null;
    }
    if (!is_file(RB_ROOT . '/assets/images/profiles/' . $file)) {
        return null;
    }
    return asset('images/profiles/' . rawurlencode($file));
}

function rb_avatar_url(?array $account): ?string
{
    return rb_profile_image_url($account['avatar'] ?? null);
}

function rb_bytes_from_ini(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    $bytes = (int) $value;
    $unit = strtolower(substr($value, -1));
    if ($unit === 'g') {
        $bytes *= 1024;
    }
    if ($unit === 'g' || $unit === 'm') {
        $bytes *= 1024;
    }
    if ($unit === 'g' || $unit === 'm' || $unit === 'k') {
        $bytes *= 1024;
    }
    return max(0, $bytes);
}

/**
 * Largest image the server can accept right now, in bytes.
 *
 * The UI and the validator share this so we never advertise a limit the
 * PHP configuration will reject.
 */
function rb_profile_upload_limit(): int
{
    $limits = [RB_PROFILE_UPLOAD_MAX];

    $ini = rb_bytes_from_ini((string) ini_get('upload_max_filesize'));
    if ($ini > 0) {
        $limits[] = $ini;
    }
    $post = rb_bytes_from_ini((string) ini_get('post_max_size'));
    if ($post > 0) {
        $limits[] = max(1024 * 1024, $post - 512 * 1024);
    }

    return min($limits);
}

function rb_format_bytes(int $bytes): string
{
    if ($bytes >= 1024 * 1024) {
        $mb = $bytes / (1024 * 1024);
        return ($mb == floor($mb) ? (string) (int) $mb : number_format($mb, 1)) . ' MB';
    }
    return max(1, (int) round($bytes / 1024)) . ' KB';
}

/**
 * Validate, re-encode and store an avatar or card cover.
 * Throws RuntimeException with a safe, user-facing message on failure.
 */
function rb_handle_profile_upload(array $file, int $userId, string $kind): string
{
    if (!in_array($kind, ['avatar', 'cover'], true)) {
        throw new InvalidArgumentException('Unknown profile image type.');
    }

    $limit = rb_profile_upload_limit();
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That image is larger than ' . rb_format_bytes($limit) . '. Pick a smaller file.',
            UPLOAD_ERR_PARTIAL    => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE    => 'Choose an image to upload.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'The server could not store that file. Please try again.',
            default               => 'The upload failed. Please try again.',
        });
    }

    $tmp = $file['tmp_name'] ?? '';
    if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('The upload could not be read. Please try again.');
    }

    $size = filesize($tmp);
    if ($size === false || $size < 1) {
        throw new RuntimeException('The uploaded file is empty.');
    }
    if ($size > $limit) {
        throw new RuntimeException('That image is larger than ' . rb_format_bytes($limit) . '. Pick a smaller file.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $allowed = [
        'image/jpeg' => IMAGETYPE_JPEG,
        'image/png'  => IMAGETYPE_PNG,
        'image/webp' => IMAGETYPE_WEBP,
    ];
    if (!is_string($mime) || !isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG or WebP images are allowed.');
    }

    $info = @getimagesize($tmp);
    if ($info === false || !isset($info[0], $info[1], $info[2])) {
        throw new RuntimeException('That file is not a readable image.');
    }
    if ($info[2] !== $allowed[$mime]) {
        throw new RuntimeException('The image type does not match its contents.');
    }
    [$width, $height] = $info;
    if ($width < 200 || $height < 200) {
        throw new RuntimeException('Use an image at least 200 × 200 pixels.');
    }
    if ($width > 5000 || $height > 5000) {
        throw new RuntimeException('That image is too large. Keep it under 5000 pixels.');
    }

    if (!function_exists('imagewebp') || !function_exists('imagecreatefromstring')) {
        throw new RuntimeException('The PHP GD extension with WebP support is required for uploads.');
    }

    $raw = file_get_contents($tmp);
    $source = $raw !== false ? @imagecreatefromstring($raw) : false;
    if (!$source) {
        throw new RuntimeException('The image could not be processed.');
    }

    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmp);
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($angle !== 0) {
            $rotated = @imagerotate($source, $angle, 0);
            if ($rotated !== false) {
                imagedestroy($source);
                $source = $rotated;
                $width = imagesx($source);
                $height = imagesy($source);
            }
        }
    }

    $max = $kind === 'avatar' ? 900 : 1800;
    $scale = min(1, $max / max($width, $height));
    $newW = max(1, (int) round($width * $scale));
    $newH = max(1, (int) round($height * $scale));
    $canvas = imagecreatetruecolor($newW, $newH);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
    imagealphablending($canvas, true);
    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);
    imagedestroy($source);

    $dir = RB_ROOT . '/assets/images/profiles';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        imagedestroy($canvas);
        throw new RuntimeException('The profile image folder could not be created.');
    }
    if (!is_writable($dir)) {
        imagedestroy($canvas);
        throw new RuntimeException('The profile image folder is not writable. Check folder permissions.');
    }

    $filename = 'u' . $userId . '-' . $kind . '-' . bin2hex(random_bytes(4)) . '.webp';
    $destination = $dir . '/' . $filename;
    $saved = imagewebp($canvas, $destination, 86);
    imagedestroy($canvas);
    if (!$saved) {
        throw new RuntimeException('The image could not be saved.');
    }
    @chmod($destination, 0644);

    return $filename;
}

function rb_delete_profile_image(?string $file): void
{
    if (!is_string($file) || !preg_match('/^u(\d+)-(?:avatar|cover)-[a-f0-9]{8}\.webp$/', $file)) {
        return;
    }
    $path = RB_ROOT . '/assets/images/profiles/' . $file;
    if (is_file($path)) {
        @unlink($path);
    }
}

function rb_unique_product_slug(PDO $pdo, string $name, ?int $ignoreId = null): string
{
    $base = rb_slugify($name, 'treasure');
    $slug = $base;
    $i = 2;
    $stmt = $ignoreId
        ? $pdo->prepare('SELECT COUNT(*) FROM products WHERE slug = ? AND id <> ?')
        : $pdo->prepare('SELECT COUNT(*) FROM products WHERE slug = ?');
    while (true) {
        $stmt->execute($ignoreId ? [$slug, $ignoreId] : [$slug]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = substr($base, 0, 150) . '-' . $i++;
    }
}

/**
 * Persist a product while holding the catalog write lock.
 *
 * @param array{name:string,category_id:?int,description:string,price:float,stock:int,image:string,active:int} $data
 */
function rb_product_save(PDO $pdo, array $data, ?int $id = null): int
{
    $lock = $pdo->query("SELECT GET_LOCK('rockbottom_catalog_write', 5)")->fetchColumn();
    if ((int) $lock !== 1) {
        throw new RuntimeException('The catalog is busy. Please try again in a moment.');
    }

    try {
        $pdo->beginTransaction();

        if ($id !== null) {
            $check = $pdo->prepare('SELECT id FROM products WHERE id = ?');
            $check->execute([$id]);
            if ($check->fetchColumn() === false) {
                throw new RuntimeException('That product no longer exists.');
            }
        }

        if ((int) $data['active'] === 1 && rb_active_product_count($pdo, $id) >= RB_MAX_ACTIVE_PRODUCTS) {
            throw new RuntimeException(
                'The store can feature a maximum of ' . RB_MAX_ACTIVE_PRODUCTS . ' active products. Archive one first.'
            );
        }

        $slug = rb_unique_product_slug($pdo, $data['name'], $id);
        $categoryId = $data['category_id'];
        if ($categoryId !== null) {
            $cat = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
            $cat->execute([$categoryId]);
            if ($cat->fetchColumn() === false) {
                throw new RuntimeException('Choose a valid category.');
            }
        }

        if ($id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO products
                 (category_id, name, slug, description, price, stock, image, active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $categoryId, $data['name'], $slug, $data['description'],
                $data['price'], $data['stock'], $data['image'], $data['active'],
            ]);
            $id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare(
                'UPDATE products
                 SET category_id = ?, name = ?, slug = ?, description = ?, price = ?,
                     stock = ?, image = ?, active = ?
                 WHERE id = ?'
            );
            $stmt->execute([
                $categoryId, $data['name'], $slug, $data['description'],
                $data['price'], $data['stock'], $data['image'], $data['active'], $id,
            ]);
        }

        $pdo->commit();
        return (int) $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('rockbottom_catalog_write')");
    }
}

function rb_category_slug(PDO $pdo, string $name, ?int $ignoreId = null): string
{
    $base = rb_slugify($name, 'category');
    $slug = $base;
    $i = 2;
    while (true) {
        $sql = $ignoreId
            ? 'SELECT COUNT(*) FROM categories WHERE slug = ? AND id <> ?'
            : 'SELECT COUNT(*) FROM categories WHERE slug = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ignoreId ? [$slug, $ignoreId] : [$slug]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = substr($base, 0, 80) . '-' . $i++;
    }
}

/* -------------------------------------------------------------------------
 * Orders
 * ---------------------------------------------------------------------- */

function rb_order_no(int $id): string
{
    return 'RB-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
}

function rb_status_tone(string $status): string
{
    return match ($status) {
        'Delivered'  => 'lime',
        'Shipped'    => 'plum',
        'Processing' => 'rose',
        'Cancelled'  => 'muted',
        default      => 'lavender',
    };
}

function rb_status_flow(): array
{
    return ['Pending', 'Processing', 'Shipped', 'Delivered'];
}

/**
 * Cancelled and Delivered are terminal. Cancelling restores stock.
 *
 * @return array{0: bool, 1: string}
 */
function rb_change_order_status(PDO $pdo, int $orderId, string $status): array
{
    if (!in_array($status, RB_ORDER_STATUSES, true)) {
        return [false, 'Unknown order status.'];
    }

    $allowed = [
        'Pending'    => ['Pending', 'Processing', 'Cancelled'],
        'Processing' => ['Processing', 'Shipped', 'Cancelled'],
        'Shipped'    => ['Shipped', 'Delivered'],
        'Delivered'  => ['Delivered'],
        'Cancelled'  => ['Cancelled'],
    ];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            $pdo->rollBack();
            return [false, 'Order not found.'];
        }
        $current = (string) $order['status'];
        if ($current === $status) {
            $pdo->commit();
            return [true, 'No status change needed.'];
        }
        if (!in_array($status, $allowed[$current] ?? [], true)) {
            $pdo->rollBack();
            return [false, 'An order marked ' . $current . ' cannot move to ' . $status . '.'];
        }

        if ($status === 'Cancelled') {
            $restock = $pdo->prepare(
                'UPDATE products p
                 JOIN order_items oi ON oi.product_id = p.id
                 SET p.stock = p.stock + oi.quantity
                 WHERE oi.order_id = ?'
            );
            $restock->execute([$orderId]);
            $pdo->prepare("UPDATE payments SET status = 'Cancelled' WHERE order_id = ?")
                ->execute([$orderId]);
        }
        if ($status === 'Delivered') {
            $pdo->prepare("UPDATE payments SET status = 'Paid' WHERE order_id = ?")
                ->execute([$orderId]);
        }

        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $orderId]);
        $pdo->commit();
        return [true, 'Order ' . rb_order_no($orderId) . ' is now ' . $status . '.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Order status update failed: ' . $e->getMessage());
        return [false, 'The order status could not be updated. Please try again.'];
    }
}

/* -------------------------------------------------------------------------
 * Global POST protection — every state-changing form is CSRF-checked.
 * ---------------------------------------------------------------------- */

if (PHP_SAPI !== 'cli' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !csrf_valid()) {
    rb_csrf_fail();
}
