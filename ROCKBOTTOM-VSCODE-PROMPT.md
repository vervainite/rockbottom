# Prompt: Continue and improve the RockBottom PHP e-commerce project

Act as a senior PHP 8+, MySQL, HTML/CSS, JavaScript, Bootstrap 5, UI/UX, and security engineer. Work directly inside the existing `rockbottom` project folder in VS Code. Build and refine a complete original collectible e-commerce website named **RockBottom**, tagline **“Cute Things From The Depths.”**

## Design direction
Create a premium editorial designer-toy storefront with a cute gothic / mysterious underground-treasure identity. It may take broad inspiration from collectible stores such as POP MART, but do not copy their logo, characters, text, or exact layout. Use this exact palette consistently:
- Obsidian `#211B24`
- Deep Plum `#59364F`
- Dusty Rose `#A77D91`
- Bone Cream `#F3EBDD`
- Treasure Lime `#C7D66B`
- Mystic Lavender `#B69AC8`

Use elegant oversized serif headlines, restrained sans-serif UI text, strong product photography, generous spacing, subtle transitions, accessible controls, and responsive desktop/tablet/mobile layouts. Avoid generic Bootstrap styling, excessive shadows, and clutter.

## Required architecture
Keep the customer-facing store as one continuous `index.php` homepage with one unified catalog. Never split products into separate category/catalog pages. Show no more than **10 active products**. Keep PHP, CSS, JavaScript, SQL, and local image assets organized in the current project structure.

## Storefront
- Sticky responsive navbar: logo, Home, Shop, About, Contact, search, account, cart count.
- Hero headline: “NOT YOUR ORDINARY LITTLE TREASURES.” Supporting copy: “Discover rare finds, mysterious characters, and collectibles waiting to be yours.”
- About headline: “EVERY TREASURE HAS A STORY.” Use the supplied brand story and editorial two-column layout.
- Unified catalog headline: “FIND YOUR TREASURE.” Each card has image, name, description, PHP price, category, stock, Add to Cart, and Details.
- Catalog tools: live search, category filter (filter the same catalog), price ascending/descending, newest sorting.
- Product details modal: large image, name, description, price, stock, quantity, add-to-cart.
- Promo headline: “THE UNKNOWN IS THE BEST PART.” Copy: “Every box holds a little mystery. Every mystery holds a new treasure.”
- About/contact, footer, local brand assets.

## Customer and admin functionality
Implement/test registration, login/logout, profile editing, session cart, add/remove/update quantities, totals, checkout, Cash on Delivery, order confirmation, order history, and order status tracking. Admin area needs overview metrics, product add/edit/archive, image upload with strict validation, categories, stock, orders/status changes, and customer list. Enforce a maximum of 10 active products on both server and database/application logic.

## Database and security
Use MySQL tables: users, products, categories, cart, cart_items, orders, order_items, payments, with correct keys and foreign keys. Use PDO prepared statements, password_hash/password_verify, secure session cookie settings, CSRF tokens on every state-changing form, server-side validation, escaped output, transaction-safe checkout, and stock checks. Never trust client-side price or quantity. Add an admin bootstrap procedure that does not ship a public default password.

## Image assets
Use local image files only; no remote image dependencies for product artwork. Keep:
`assets/images/logo/`, `hero/`, `products/`, `banners/`, and `icons/`.
Required distinct product assets:
`moonlight-bunny.png`, `mystery-box.png`, `shadow-cat.png`, `little-ghost.png`, `forest-sprite.png`, `candy-monster.png`, `dreamer-girl.png`, `starry-bear.png`, `lucky-rabbit.png`, `midnight-box.png`.
Keep images distinct and never use one product image for all products.

## Workflow and quality gate
1. Inspect the existing project before editing; do not overwrite working features unnecessarily.
2. Fix PHP errors and broken routes/asset paths.
3. Ensure forms validate and give useful success/error messages.
4. Check all state-changing forms use CSRF protection.
5. Verify cart, checkout, stock decrement, and order status flow.
6. Test SQL import from a clean database.
7. Check the site at desktop and mobile widths.
8. Keep setup steps in README and document any limitations honestly.
9. Do not claim a test passed unless you actually ran it.
10. Make the result easy to open in VS Code and run under XAMPP at `http://localhost/rockbottom/`.

Start by reviewing all project files, identify defects, then implement the fixes in the actual files rather than merely describing code.
