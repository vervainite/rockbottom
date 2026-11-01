<?php
declare(strict_types=1);

/**
 * Create or promote a RockBottom administrator.
 *
 * Usage (from the project root):
 *   php scripts/create_admin.php
 *   php scripts/create_admin.php --name="Ada" --email="ada@example.com"
 *   php scripts/create_admin.php --email="ada@example.com" --update
 *
 * There is deliberately no default password. Supply one interactively or with
 * --password=... / the RB_ADMIN_PASSWORD environment variable.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../config/app.php';

$options = getopt('', ['name::', 'email::', 'password::', 'update']);
$update = array_key_exists('update', $options);

$name = trim((string) ($options['name'] ?? ''));
$email = strtolower(trim((string) ($options['email'] ?? '')));
$password = (string) ($options['password'] ?? getenv('RB_ADMIN_PASSWORD') ?: '');

function prompt(string $label): string
{
    fwrite(STDOUT, $label);
    $value = fgets(STDIN);
    return $value === false ? '' : trim($value);
}

function prompt_secret(string $label): string
{
    if (DIRECTORY_SEPARATOR === '/') {
        $disabled = shell_exec('stty -g 2>/dev/null');
        if (is_string($disabled) && $disabled !== '') {
            shell_exec('stty -echo');
            $value = prompt($label);
            shell_exec('stty ' . escapeshellarg(trim($disabled)));
            fwrite(STDOUT, PHP_EOL);
            return $value;
        }
    }
    return prompt($label);
}

if ($name === '') {
    $name = prompt('Admin name: ');
}
if ($email === '') {
    $email = strtolower(prompt('Admin email: '));
}

if ($name === '' || mb_strlen($name) < 2) {
    fwrite(STDERR, "A name of at least two characters is required.\n");
    exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "That email address is not valid.\n");
    exit(1);
}

$stmt = $pdo->prepare('SELECT id, role FROM users WHERE email = ?');
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing && !$update) {
    fwrite(STDERR, "An account with that email already exists. Re-run with --update to promote it and reset its password.\n");
    exit(1);
}

if ($password === '') {
    $password = prompt_secret('Password (min 10 characters): ');
    $confirm = prompt_secret('Confirm password: ');
    if (!hash_equals($password, $confirm)) {
        fwrite(STDERR, "Passwords did not match.\n");
        exit(1);
    }
}

if (strlen($password) < 10) {
    fwrite(STDERR, "Choose a password of at least 10 characters.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
if ($hash === false) {
    fwrite(STDERR, "The password could not be hashed.\n");
    exit(1);
}

if ($existing) {
    $pdo->prepare("UPDATE users SET name = ?, role = 'admin', password = ? WHERE id = ?")
        ->execute([$name, $hash, (int) $existing['id']]);
    fwrite(STDOUT, "Updated {$email} (user #{$existing['id']}) as an administrator.\n");
} else {
    $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')")
        ->execute([$name, $email, $hash]);
    fwrite(STDOUT, "Created administrator {$email} (user #" . $pdo->lastInsertId() . ").\n");
}

fwrite(STDOUT, "Sign in at " . RB_APP_NAME . " → /admin/login.php. Delete this shell history if you passed a password inline.\n");
