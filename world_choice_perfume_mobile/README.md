# World Choice Perfume — Mobile

The mobile app for **World Choice Perfume**, an Expo (React Native) project using
[expo-router](https://docs.expo.dev/router/introduction/) for file-based routing.

The app talks to the production backend at **https://worldchoiceperfume.com**
(the Laravel website in the repository root) and reproduces its visual identity:
deep-black surfaces, luxury gold accents, the official logo and the same
typographic rhythm.

## Get started

1. Install dependencies

   ```bash
   npm install
   ```

2. Start the app

   ```bash
   npx expo start
   ```

   Then open it in an Android emulator, an iOS simulator, or
   [Expo Go](https://expo.dev/go).

## Application flow

```
Splash (native, app.json)
  → Landing page (app/index.tsx — logo + 6 menu buttons)
      → HOME         → app/home.tsx        (homepage sections)
      → SHOPPING     → app/shop.tsx        (search, filters, grid)
                        → app/product.tsx  (product detail)
      → TRACK ORDERS → app/track.tsx       (phone lookup + timeline)
      → CONTACTS     → app/contacts.tsx   (Contact Us portal: details + message form)
      → NEWS         → app/news.tsx       (published posts, pull to refresh)
      → STAFF LOGIN  → app/staff-access.tsx (secret-code prompt, modal)
                        → app/staff.tsx    (staff sign-in + six signup tiles)
                            → LOGIN        → straight to that module's dashboard
                            → SIGNUP       → app/signup/cashier.tsx           (photo, branch)
                              (×6)           app/signup/branch-admin.tsx      (super-admin code)
                                             app/signup/stock-manager.tsx
                                             app/signup/customer-care.tsx
                                             app/signup/seller.tsx
                                             app/signup/graphic-designer.tsx  (no branch)
                                   → app/otp.tsx (6-digit email code → pending approval)
                                   → Back to Login → app/staff.tsx
                            → Super Admin (role = super_admin only)
                                → app/admin/  (right after LOGIN)
                                   (tabs) Dashboard · Orders · Approvals · More
                                   More → Monitor / Records / System launcher
                            → Graphic Designer (role = graphic_designer only)
                                → app/gd/  (right after LOGIN)
                                   (tabs) Dashboard · News · Brands · More
                                   More → QR Code · Profile · Sign Out
                            → Stock Manager (role = stock_manager only)
                                → app/sm/  (right after LOGIN)
                                   (tabs) Dashboard · Stock · Transfers · More
                                   Dashboard → stock entry · movements · low stock
                                   Stock → product stock (search, edit, delete)
                                   Transfers → Outgoing · Incoming · Returns (+ lost items)
                                   More → Bottle Stock · Oil Fragrance · Accessories ·
                                          Sales · New Sale · Orders · Products ·
                                          Returned Stock (Kinondoni) · Cross-branch ·
                                          Profile · Sign Out
                                   Branch gate: `branch.category = products_based`
                                   hides bottle/oil/accessories (products-only
                                   branches); `autonomous` branches see everything.
                            → Customer Care (role = customer_care only)
                                → app/care/  (right after LOGIN)
                                   (tabs) Dashboard · Clients · Sales · Orders · Profile
                                          — the website's customer-care sidebar, in its
                                          own order; Profile carries the account and
                                          password forms plus Sign Out
                                   More (Head Quarters tab only) → Inquiries · News ·
                                          info@ Mails · New Sale · Account
                                   Branch members never see the More tab; the server
                                   re-checks the HQ gate (assertHq) on every call.
                            → Branch Admin (role = branch_admin only)
                                → app/ba/  (right after LOGIN)
                                   (tabs) Dashboard · Sales · Orders · More (amber tint)
                                   More → Branch ops (Staffs · Expenses) ·
                                          Selling (New Sale · Sales History) ·
                                          Account (Profile · Sign Out)
                                   Orders are supervisory: the whole branch's
                                   queue with waiting times, pending watch and
                                   team activity — picking/serving still belongs
                                   to whoever claimed the order (server refuses
                                   a takeover). Expenses are view-only; cashiers
                                   commit them.
                            → Seller (role = seller only)
                                → app/seller/  (right after LOGIN)
                                   (tabs) Dashboard · My Sales · Orders · Account
                                   (cyan tint) — sales are personal: the listing
                                   and receipts are the member's own (cashier_id),
                                   and the order queue is isolated: pending is
                                   shared with the branch, picked/completed only
                                   ever belong to the seller who claimed them.
                            → Cashier (role = cashier or super_admin)
                                → app/cashier/  (right after LOGIN)
                                   (tabs) Dashboard · Sales · Orders · More
                                   (amber-600 tint) — sales are personal
                                   (cashier_id); Orders runs pick/serve with the
                                   required note, and serve is the full handover
                                   (sale recorded + stock deducted server-side).
                                   More → Records (Expenses · New Sale) ·
                                          Head Quarters (Cross-Branch Monitoring ·
                                          Daily Sales — All Branches; only for the
                                          HQ cashier or the Super Admin) ·
                                          Account (Profile · Sign Out)
                                   Entering a branch from Cross-Branch makes every
                                   screen follow it read-only until the monitor
                                   exits (the stateless twin of the website's
                                   session-based cross-branch mode).
```

## Project structure

```
app/
  _layout.tsx        # Root stack (header hidden, staff-access as modal)
  index.tsx          # Landing/menu page — logo + six section buttons
  home.tsx           # HOME
  shop.tsx           # SHOPPING (grid, search, branch/category/brand filters)
  product.tsx        # Product detail (per-branch stock, prices, varieties)
  track.tsx          # TRACK ORDERS (status, timeline, items)
  contacts.tsx       # CONTACTS (the website's Contact Us section + message form)
  news.tsx           # NEWS (the website's /news page)
  branches.tsx       # Branch list + Twende Dukani maps link (HOME / product)
  staff-access.tsx   # STAFF LOGIN secret-code prompt (modal, stores X-Staff-Access grant)
  staff.tsx          # Staff login (Welcome Back) + six signup tiles → module dashboard
  signup/            # Six dedicated signup pages (one small config each)
    cashier.tsx        branch-admin.tsx   stock-manager.tsx
    customer-care.tsx  seller.tsx         graphic-designer.tsx
  otp.tsx            # Post-registration OTP verify + pending-approval screens
  admin/              # Super Admin module (server-role-guarded screens)
    _layout.tsx        # Role guard + stack for every admin screen
    (tabs)/            # Bottom tabs: Dashboard, Orders, Approvals, More
    daily-sales.tsx cross-stock.tsx cross-sales.tsx
    branches.tsx branch-form.tsx   # CRUD incl. deactivate + permanent delete
    staff.tsx staff-form.tsx staff-detail.tsx
    emails.tsx email-detail.tsx    # info@ mailbox (reply, star, status…)
    notifications.tsx returned-stock.tsx
    reports.tsx report-{sales,expenses,stock,staff,products}.tsx
    settings.tsx profile.tsx       # Secret codes + account/password
  gd/                # Graphic Designer module (server-role-guarded screens)
    _layout.tsx        # Role guard + stack for every GD screen
    (tabs)/            # Bottom tabs: Dashboard, News, Brands, More
    news-form.tsx      # Create/edit post (image on create, resubmit → approval)
    brand-form.tsx     # Create/edit brand (logo, active flag, duplicate-name 422)
    qr-code.tsx        # Server-rendered QR (PNG data URI; download/print on web)
    profile.tsx        # Shared account/password (same ProfileController as web)
  sm/                 # Stock Manager module (server-role-guarded screens)
    _layout.tsx        # Role guard + stack for every SM screen
    (tabs)/            # Bottom tabs: Dashboard, Stock, Transfers, More (emerald tint)
    stock-entry.tsx stock-edit.tsx movements.tsx low-stock.tsx
    bottle-stock.tsx oil-fragrance.tsx accessories.tsx   # 403-aware when products-only
    sales.tsx sale-new.tsx orders.tsx products.tsx product-form.tsx
    transfer-new.tsx transfer-detail.tsx incoming.tsx returns.tsx
    returned-stock.tsx cross-branch.tsx profile.tsx
  care/                # Customer Care module (server-role-guarded screens)
    _layout.tsx        # Role guard + stack for every CC screen
    (tabs)/            # Bottom tabs: Dashboard, Clients, Sales, Orders, Profile
                       #              (+ More when Head Quarters; sky tint)
    (tabs)/sales.tsx   # branch sales history — the sidebar's "Sales"
    (tabs)/profile.tsx # account + password + Sign Out — the sidebar's "Profile"
    (tabs)/more.tsx    # HQ only: Inquiries · News · info@ Mails · New Sale
    customer-new.tsx customer-detail.tsx
    sale-new.tsx sale-detail.tsx             # checkout incl. empty-bottle lines
    inquiries.tsx news.tsx news-form.tsx     # Head Quarters only (assertHq)
    mails.tsx mail-detail.tsx                # info@ inbox — Head Quarters only
  ba/                  # Branch Admin module (server-role-guarded screens)
    _layout.tsx        # Role guard + stack for every BA screen
    (tabs)/            # Bottom tabs: Dashboard, Sales, Orders, More (amber tint)
    (tabs)/sales.tsx   # cashier chip + date-range filters, window total
    (tabs)/orders.tsx  # supervisory queue: pending watch, team activity, waiting times
    staff.tsx staff-new.tsx   # team list (role/status filters) + approve/reject + create
    expenses.tsx       # view-only: business-day window, date range, category
    sale-new.tsx sale-detail.tsx   # full checkout (varieties, empty bottles, splits)
    profile.tsx
  seller/              # Seller module (server-role-guarded screens)
    _layout.tsx        # Role guard + stack for every seller screen
    (tabs)/            # Bottom tabs: Dashboard, My Sales, Orders, Account (cyan tint)
    (tabs)/sales.tsx   # own sales + date-range filters, window total
    (tabs)/orders.tsx  # isolated queue: shared pending, personal picked/completed
    (tabs)/account.tsx # shared profile + password
    sale-new.tsx sale-detail.tsx   # full checkout (varieties, empty bottles, splits)
  cashier/             # Cashier module (server-role-guarded screens)
    _layout.tsx        # Role guard (cashier|super_admin) + stack for every screen
    (tabs)/            # Bottom tabs: Dashboard, Sales, Orders, More (amber tint)
    (tabs)/orders.tsx  # isolated queue — pick/serve with the required note
    expenses.tsx expense-new.tsx   # business-day window + the commit form
    sale-new.tsx sale-detail.tsx   # full checkout (varieties, empty bottles, splits)
    cross-branch.tsx   # HQ monitor: enter/exit a branch (read-only everywhere)
    daily-overview.tsx # company totals + per-branch/per-staff contribution
    profile.tsx        # shared account/password
components/
  SignupForm.tsx     # Config-driven signup engine (validation, 422 mapping, photo upload)
  authkit.tsx        # AuthHeader / AuthField / Banner shared by all seven auth pages
  adminkit.tsx       # AdminPage shell, stat tiles, chips, DataCard, ConfirmDialog, useAsyncData
  ScreenHeader + shared UI (buttons, states, headings)
lib/
  config.ts          # BASE_URL — the single production base URL
  api.ts             # HTTP client, typed payloads, error classification
  adminApi.ts        # Typed client for every /api/admin/* Super Admin endpoint
  gdApi.ts           # Typed client for every /api/gd/* Graphic Designer endpoint
  smApi.ts           # Typed client for every /api/sm/* Stock Manager endpoint
  careApi.ts         # Typed client for every /api/care/* Customer Care endpoint
  baApi.ts           # Typed client for every /api/ba/* Branch Admin endpoint
  sellerApi.ts       # Typed client for every /api/seller/* Seller endpoint
  cashierApi.ts      # Typed client for every /api/cashier/* Cashier endpoint
  cashierMonitor.ts  # In-memory monitored-branch state (the session twin)
  theme.ts           # Website colour tokens (dark + gold + GD/SM/CC/BA/Seller/Cashier accents)
  staffSession.ts    # In-memory staff state + role → dashboard map
  format.ts          # Money/date formatting
assets/images/       # App icons, splash, official logo (logo.jpeg)
app.json             # Expo config (name, slug, package ids, plugins)
```

## Backend API

All requests go through `BASE_URL` in `lib/config.ts` and hit the JSON routes
declared in the Laravel app's `routes/api.php` (they reuse the website's own
controllers and return JSON when called with `Accept: application/json`):

- `GET  /api/home` — branches, featured brands, category images, contact
- `GET  /api/products` — catalogue with the website's filters
- `GET  /api/products/{id}` — detail, per-branch stock and varieties
- `POST /api/orders/track` — order tracking by phone
- `POST /api/verify-staff-access` — server-side secret-code validation (issues encrypted grant)
- `POST /api/staff/login` — server-side credential validation *(grant required; issues `X-Staff-Session`)*
- `POST /api/register/{type}` — the six signup types → OTP challenge *(grant required)*
- `POST /api/verify-otp` / `POST /api/resend-otp` — emailed 6-digit code

Super Admin module — every route below sits behind `EnsureStaffSessionApi:super_admin`,
which decrypts the `X-Staff-Session` token, re-reads the user row and refuses
any role that is not `super_admin` on **every** call (the app can never grant
itself access):

- `GET  /api/admin/dashboard` / `daily-sales` / `cross-branch/{stock,sales}` — overview screens
- `GET|POST /api/admin/branches`, `GET|PUT|DELETE /api/admin/branches/{id}`, `DELETE …/{id}/purge`, `GET …/options`
- `GET  /api/admin/approvals`, `…/{id}`, `POST …/{id}/approve|reject`
- `GET  /api/admin/orders`, `…/{id}`, `POST …/{id}/personal-name`
- `GET|POST /api/admin/staff`, `GET …/options`, `GET /…/{id}`, `POST …/{id}/toggle-status|status`, `DELETE …/{id}`
- `GET  /api/admin/emails` + `…/{id}/reply|read|star|status`, `DELETE …/{id}`
- `GET  /api/admin/notifications` + `…/{id}/mark-read`, `mark-all-read`, `report`
- `GET  /api/admin/reports/{sales,expenses,stock,staff-performance,product-performance}`
- `GET  /api/admin/returned-stock`
- `GET|POST /api/admin/settings`, `GET|PUT /api/admin/profile`, `PUT …/profile/password`

Graphic Designer module — every route below sits behind
`EnsureStaffSessionApi:graphic_designer`, with the same per-call session +
role re-verification as the admin group:

- `GET  /api/gd/dashboard` — five stat counts + rejected-posts list
- `GET|POST /api/gd/news`, `GET|PUT|DELETE /api/gd/news/{id}` — author-scoped posts (submit/edit always re-enter approval)
- `GET|POST /api/gd/brands`, `GET|PUT|DELETE /api/gd/brands/{id}` — duplicate-name guard, rename → product sync, in-use delete protection
- `GET  /api/gd/qr-code` — server-rendered QR PNG as a `data:` URI (ECC H)
- `GET|PUT /api/gd/profile`, `PUT …/profile/password` — the website's shared ProfileController

Stock Manager module — every route below sits behind
`EnsureStaffSessionApi:stock_manager` with the same per-call session + role
re-verification, plus the website's three branch rules translated to JSON:

- **products-only gating** — when the manager's `branch.category` is
  `products_based`, the bottle, oil-fragrance and accessories endpoints answer
  403 ("Bottle, oil fragrance and bottle accessories management are not
  available for your branch.") and the app hides those tiles; `autonomous`
  branches see and manage everything.
- **cross-branch monitoring** — only the Kinondoni manager (or Super Admin)
  may pass `?branch_id=` to read-only listings; in that mode all writes are
  refused ("Cross-branch monitoring is read-only…") and sales/orders are hidden.
- **Returned Stock** — Kinondoni manager only (403 otherwise); write-off there
  requires a damage report (`code: damage_report_required`).

- `GET  /api/sm/dashboard` / `scope` / `cross-branch` — overview + branch context
- `GET  /api/sm/product-stock`, `/low-stock`, `/entry`, `POST /entry`, `GET|PUT|DELETE …/{id}`
- `PUT|DELETE /api/sm/product-stock/varieties/{id}`, `GET …/movements`
- `GET  /api/sm/bottle-stock`, `POST …/in`, `POST …/broken`, `PUT|DELETE …/{id}`, `GET …/movements`
- `GET  /api/sm/oil-fragrance`, `…/options`, `POST …/in|out`, `PUT|DELETE …/{id}`, `GET …/movements`
- `GET|POST /api/sm/bottle-accessories`, `POST …/stock-out`, `PUT|DELETE …/{id}`, `GET …/movements`
- `GET|POST /api/sm/stock-transfers`, `GET …/create`, `GET …/incoming`, `GET …/{id}`
- `POST /api/sm/stock-transfers/items/{id}/receive|receive-invalid|resend|write-off`
- `GET|POST /api/sm/stock-transfers/lost-items`, `GET …/returns`
- `GET  /api/sm/returned-stock`, `GET|POST …/items/{id}/damage-report`
- `GET  /api/sm/sales`, `…/options`, `POST /api/sm/sales`, `GET …/{id}`
- `GET  /api/sm/orders`, `…/{id}`, `POST …/{id}/status|personal-name`
- `GET|POST /api/sm/products`, `GET|PUT|DELETE …/{id}`, `GET …/form-data`, `DELETE /api/sm/product-images/{id}`
- `GET|PUT /api/sm/profile`, `PUT …/profile/password`

Customer Care module — every route below sits behind
`EnsureStaffSessionApi:customer_care` with the same per-call session +
role re-verification, in two tiers exactly like the website:

- **branch tier (every member)** — clients, sales and orders of their own
  branch. Customer care has no cross-branch mode at all.
- **Head Quarters tier (`assertHq`)** — inquiries, news moderation and the
  info@ mailbox answer 403 ("Only customer care from Head Quarters-Mikocheni
  can access this page.") for anyone else, mirroring the website's
  `customer-care.hq` middleware. The app's More hub hides those tiles until
  `scope.is_hq` says otherwise.

- `GET  /api/care/scope` / `dashboard` — HQ flag + branch figures
- `GET|POST /api/care/customers`, `GET …/{id}` — clients (branch tabs, duplicate-phone returns the existing record)
- `GET  /api/care/sales`, `…/options`, `POST /api/care/sales`, `GET …/{id}` — listing, form data, checkout (products, varieties, empty bottles, split payments), receipt
- `GET  /api/care/orders`, `…/{id}`, `POST …/{id}/status|personal-name`
- `GET  /api/care/inquiries`, `…/{id}`, `POST …/{id}/reply|read|comment`, `DELETE …/{id}` *(HQ)*
- `GET|POST /api/care/news`, `GET …/{id}/edit`, `PUT …/{id}`, `POST …/{id}/approve|reject`, `DELETE …/{id}` *(HQ)*
- `GET  /api/care/mails`, `…/{id}`, `POST …/{id}/reply|read|star|status`, `DELETE …/{id}` *(HQ)*
- `GET|PUT /api/care/profile`, `PUT …/profile/password`

Branch Admin module — every route below sits behind
`EnsureStaffSessionApi:branch_admin` with the same per-call session +
role re-verification. No Head Quarters tier and no cross-branch mode: every
controller scopes to the admin's own branch, exactly like the website's
`role:branch_admin` group:

- **supervisory orders** — `tabRows`/`counts` run `isolated=false`, so the
  whole branch's queue is listed (oldest pending first) with waiting times,
  the pending watch and the team activity board; picking and serving still
  belong to whoever claimed the order and `OrderWorkflowService` refuses a
  takeover, which the app surfaces as the website's message.
- **view-only expenses** — cashiers commit them; the default window is the
  current business day (`BusinessDay::localRangeFilter`), a date range
  replaces it, and the total spans the whole window.
- **branch-scoped staff** — create (duplicate email → 422 "This email is
  already registered."), approve and reject only for this branch's
  stock_manager/seller/customer_care/cashier members (403 otherwise).
- **checkout** — the shared `SmSalesSupport` commit with the website's
  branch-admin audit labels: action `sale_created_by_admin`, "(Branch
  Admin)" movement notes and a Price Customized audit on custom prices.

- `GET  /api/ba/scope` / `dashboard` — branch flags + today's money, queue, stock and month financials
- `GET  /api/ba/sales`, `…/options`, `POST /api/ba/sales`, `GET …/{id}` — cashier/date-filtered listing, form data, checkout, receipt
- `GET  /api/ba/orders`, `…/{id}`, `POST …/{id}/status|personal-name` — supervisory queue
- `GET  /api/ba/expenses`, `…/{id}` — view-only, business-day window + date range + category
- `GET  /api/ba/staffs`, `…/form-data`, `POST /api/ba/staffs`, `POST …/{id}/approve|reject`
- `GET|PUT /api/ba/profile`, `PUT …/profile/password`

Seller module — every route below sits behind
`EnsureStaffSessionApi:seller` with the same per-call session + role
re-verification, mirroring the website's `role:seller` group:

- **personal sales** — listings filter `cashier_id` to the signed-in
  member (the date range is applied PHP-side, exactly like the website)
  and a receipt rung up by anyone else answers 403.
- **isolated orders** — `tabRows`/`counts` run `isolated=true`: pending is
  the shared branch queue, picked/completed only ever belong to the seller
  who claimed them, and only picked/served transitions are accepted — an
  order never goes back to pending.
- **checkout** — the shared `SmSalesSupport` commit with the website's
  seller audit labels: action `sale_created` and a Price Customized audit
  signed "(Seller)" on custom prices.

- `GET  /api/seller/scope` / `dashboard` — branch flags + the seller's own today/overall sales, recent sales, branch daily summary, pending count, products in stock
- `GET  /api/seller/sales`, `…/options`, `POST /api/seller/sales`, `GET …/{id}` — own-sales listing (date range), form data, checkout, receipt (403 for someone else's sale)
- `GET  /api/seller/orders`, `…/{id}`, `POST …/{id}/status|personal-name` — isolated queue
- `GET|PUT /api/seller/profile`, `PUT …/profile/password`

Cashier module — every route below sits behind
`EnsureStaffSessionApi:cashier,super_admin` with the same per-call session +
role re-verification, mirroring the website's `role:cashier,super_admin`
group (the website also wraps the group in `cashier-cross-branch.readonly`):

- **personal sales + isolated orders** — listings and receipts are scoped to
  the member's own `cashier_id`, the queue runs `isolated=true` like the
  website, and pick/serve each **require the note**. Serve is the full
  handover, not a status flip: close the order (CAS on `status=picked`),
  record the sale and its items, deduct branch stock through the variety
  buckets (ordered pick first, largest-bucket fallback), write the stock
  movements and audit `order_served` → "Order served and sale recorded!".
- **cross-branch monitoring (stateless)** — the twin of the website's
  `session('cashier_cross_branch_id')`: an HQ cashier (Head
  Quarters-Mikocheni) or the Super Admin sends `monitor_branch=` with each
  read, the server re-authorises monitor rights on every call, and every
  write is refused while monitoring ("Cross-branch monitoring is read-only.
  Exit the branch to make changes." 403). The app remembers the choice in
  `lib/cashierMonitor.ts` — convenience only, never authority.
- **expenses — the only role that commits them** — category from the six
  hardcoded values, amount ≥ 0.01, description ≥ 10 characters; recording
  while monitoring another branch is refused.
- **HQ-only screens** — `cross-branch` (each branch's today) and
  `daily-sales-overview` (company totals with per-branch and per-staff
  contribution percentages) answer 403 "Only the HQ cashier or the Super
  Admin can monitor other branches." otherwise.

- `GET  /api/cashier/scope` / `dashboard` — branch context + today's money, queue, daily summary (monitor-aware)
- `GET  /api/cashier/sales`, `…/options`, `POST /api/cashier/sales`, `GET …/{id}` — personal listing, form data, checkout, receipt
- `GET|POST /api/cashier/expenses` — business-day window + date range + category, commit
- `GET  /api/cashier/orders`, `…/{id}`, `POST …/{id}/pick|serve|personal-name` — isolated queue, required notes
- `GET  /api/cashier/cross-branch` / `daily-sales-overview` — HQ monitor only
- `GET|PUT /api/cashier/profile`, `PUT …/profile/password`

The staff secret code is **never** stored, logged, or compared inside the app;
the server only answers `verified` or not.

## Next steps

- Deeper staff dashboards as JSON endpoints are added to the Laravel app.
- Note: the new `/api/*` routes ship with the Laravel app — until the backend
  is redeployed, they answer 404 on worldchoiceperfume.com.
- Replace the placeholder icons in `assets/images/` with final branding.
- Set the final `slug`, `android.package` and `ios.bundleIdentifier` in
  `app.json` before publishing, and create a new EAS project for this app.
