# RockBottom — Cute Things From The Depths.

A collectible shop built with PHP 8, MySQL, Bootstrap 5, and a custom editorial design system. The storefront is a single continuous homepage with one unified catalog (maximum 10 active products), plus customer accounts, a session/DB hybrid cart, Cash on Delivery checkout, order tracking, and a complete admin control room.

## Requirements

- PHP **8.1+** with `pdo_mysql`, `gd`, `fileinfo`, and `mbstring`
- MySQL **8.0+** or MariaDB **10.6+**
- Apache (XAMPP is fine) with `.htaccess` support (`AllowOverride All`)

Tested locally on PHP 8.2.35 and MariaDB 11.4.4 (Windows). The base URL is detected automatically, so a `/rockbottom` subfolder install does not require path edits.

## Setup with XAMPP

1. Install XAMPP and start **Apache** and **MySQL**.
2. Copy this project folder into `C:\xampp\htdocs\rockbottom`.
3. Open `http://localhost/phpmyadmin/` → **Import** → choose `database/rockbottom.sql`.
   The file creates the `rockbottom` database, tables, keys, triggers, three categories, and ten products. Re-importing replaces all existing data.
4. Database defaults live in `config/database.php` (host `127.0.0.1`, port `3306`, database `rockbottom`, user `root`, blank password). Override with environment variables `RB_DB_HOST`, `RB_DB_PORT`, `RB_DB_NAME`, `RB_DB_USER`, `RB_DB_PASS`.
5. Visit `http://localhost/rockbottom/`.

The app detects its own URL base, so a subfolder install works without path edits. Apache `.htaccess` files block direct web access to `config/`, `includes/`, `database/`, `scripts/`, dotfiles, SQL/Markdown/log files, and disallow script execution under `assets/`.

For PHP's built-in server during development (**not** production; it ignores `.htaccess`):

```bash
php -S 127.0.0.1:8000 -t .
```

## Create the first administrator

No default admin account or password is shipped. Bootstrap one from the command line, in the project root:

```bash
php scripts/create_admin.php
```

You will be prompted for name, email, and a password of at least 10 characters (hidden input where `stty` is available). Non-interactive forms:

```bash
php scripts/create_admin.php --name="Ada" --email="ada@example.com"
php scripts/create_admin.php --email="ada@example.com" --update
RB_ADMIN_PASSWORD="..." php scripts/create_admin.php --name="Ada" --email="ada@example.com"
```

- `--update` promotes an existing account and resets its password.
- The script refuses to run over HTTP (returns 404) and exits if the email already exists without `--update`.
- Passwords are stored with `password_hash()`; the script contains no hard-coded secret.

Sign in at `/admin/login.php`. On Windows the password prompt is visible (no `stty`), so prefer the `RB_ADMIN_PASSWORD` environment variable and clear your shell history afterwards.

Alternative bootstrap without CLI: register a normal account at `/customer/register.php`, then in phpMyAdmin run:

```sql
UPDATE users SET role = 'admin' WHERE email = 'YOUR_REGISTERED_EMAIL';
```

Use a unique, strong password either way.

## What is included

**Storefront**
- One-page composition: announcement bar, sticky responsive navbar (logo, Home, Shop, About, Contact, search, account, bag count), hero, about story, unified catalog, promo, contact, footer.
- Exact editorial copy and palette: Obsidian `#211B24`, Deep Plum `#59364F`, Dusty Rose `#A77D91`, Bone Cream `#F3EBDD`, Treasure Lime `#C7D66B`, Mystic Lavender `#B69AC8`.
- Live search, category filter, price ascending/descending, newest sort, result count, empty state, reset.
- Product cards and a large product modal with quantity stepper, stock display, details, add to bag.
- Local Playfair Display / Montserrat fonts, local Bootstrap 5.3.3, local imagery, no CDN dependencies.

**Customers**
- Registration, login/logout, profile editing, password change.
- Guest session cart with merge into the database cart on login.
- Quantity update/removal, server-calculated totals, free delivery over ₱1,500 (₱80 below).
- Checkout requires a signed-in account; Cash on Delivery only.
- Transaction-safe order placement, confirmation page, order history, order detail and status timeline.

**Admin**
- Dashboard: active slots, customers, orders, non-cancelled revenue, recent orders, low stock, unread messages.
- Products: add/edit/archive, strict image upload, stock, category, active toggle; 10-active-slot enforcement.
- Categories: create, rename, delete (blocked while products use the category).
- Orders: status filters, search, pagination, detail view, controlled status transitions, cancel with automatic restock.
- Customers: search, order count, lifetime spend excluding cancelled orders, last order.
- Messages: contact-form inbox with read/unread, reply link, delete.
- Responsive admin shell with off-canvas sidebar.

## Catalog and order rules

- Exactly **10 active products** maximum. Enforced in application code, by two database triggers (an 11th active insert and an inactive→active update both raise SQLSTATE `45000`), and by a MySQL named lock during catalog writes.
- Maximum quantity per line: 10. Client-side prices and quantities are ignored; checkout re-reads price and stock from the database.
- Order status machine: `Pending → Processing → Shipped → Delivered`; `Pending`/`Processing → Cancelled`. `Delivered` and `Cancelled` are terminal.
- Placing an order locks product rows with `SELECT … FOR UPDATE`, revalidates stock, decrements conditionally, writes order, items, and a Pending payment in one transaction, then clears the cart.
- Delivery marks payment as Paid. Cancelling restocks items and marks payment Cancelled.

## Security

- PDO prepared statements for every query; no string-interpolated input.
- `password_hash()` / `password_verify()`; session ID regenerated on login.
- Session cookie: `ROCKBOTTOMSESSID`, `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS, strict mode, cookie-only.
- CSRF token required on every state-changing form, verified with `hash_equals()`; a global POST guard renders a styled 419 page. Tokens rotate on login.
- Output escaped through `e()` (`htmlspecialchars`, `ENT_QUOTES`, UTF-8).
- Server-side validation for registration, profile, products, categories, contact, checkout, and uploads.
- Security headers include CSP, `X-Frame-Options`, `nosniff`, Referrer-Policy, Permissions-Policy, and HSTS over HTTPS. Scripts are external files; the CSP allows no inline JavaScript.
- Uploads are limited to 5 MB and 300×300 minimum, checked with `finfo` + `getimagesize`, then re-encoded through GD to WebP (max 1200 px). Filenames are generated as `slug-8hex.webp`.
- Direct access to application config, SQL, and scripts is denied by `.htaccess`.

## Images

All assets are local:

```
assets/images/logo/       wordmarks and favicon
assets/images/hero/       hero and promo artwork
assets/images/products/   ten supplied edgy-boy figure photographs (≈843×750)
assets/images/banners/    about-section artwork
assets/images/icons/      icon assets
```

Product images are the ten supplied edgy-boy figure photographs. Seed products reference the required `.png` files in `assets/images/products/`; when adding a product, upload a JPG, PNG, or WebP — it is converted to WebP on save.

## Tests performed

Verified during development (not an automated test suite):

- Clean SQL import into an empty database; all tables, foreign keys, and triggers created.
- 11th active product rejected on insert and on archived→active update (both triggers).
- `php -l` clean on every PHP file.
- Guest cart merged into the account cart on login; quantity update and server-side totals verified.
- Full customer flow on a clean import: registration, 10-card catalog, live search ("dreamer" → 1 result), 2 units added, free-delivery threshold, COD checkout, confirmation, payment created as Pending, cart cleared, and stock decremented by 2. Another user's order returned 404.
- POST without a valid CSRF token returned 419.
- Admin login, dashboard and every admin page rendered without PHP warnings; order moved Pending → Processing.
- Status transitions through Delivered (payment marked Paid); invalid transitions rejected; cancellation restocked 6 → 7 and marked payment Cancelled.
- Valid image upload re-encoded to WebP; invalid file rejected; 11th active product blocked in the UI and by the trigger.
- Category create/rename/delete, including deletion blocked while in use.
- Contact form persisted to the database; admin inbox read/unread/delete actions.
- Playwright checks at **1440×950** and **390×844** across storefront, account, cart, checkout, and admin pages: no horizontal overflow, navigation/off-canvas usable, catalog search/filter/sort/modal behavior confirmed.

## Limitations

- Cash on Delivery only; no card gateway.
- No outbound email. The contact form stores messages in the admin inbox and offers a `mailto:` reply link; there is no SMTP sending, password reset, or email verification.
- No rate limiting, CAPTCHA, 2FA, audit log, or automated backup. The contact form uses a honeypot field only.
- File-based PHP sessions and local disk uploads; suitable for a single server, not a distributed deployment.
- The admin bootstrap script's hidden-password prompt only works on Unix-like systems.
- Not production-hardened. Before deployment add HTTPS enforcement at the web-server level, a secrets manager, SMTP, rate limiting, monitoring, and database/upload backups.

## Project layout

```
index.php                 storefront homepage and catalog
config/                   app bootstrap, helpers, database connection
includes/                 shared header, navbar, footer
customer/                 auth, cart, checkout, orders, profile
admin/                    dashboard and management screens
scripts/create_admin.php  CLI administrator bootstrap
database/rockbottom.sql   schema, triggers, seed catalog
assets/                   local CSS, JS, fonts, images, Bootstrap
```
