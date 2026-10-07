<?php

use App\Http\Controllers\Api\Admin\AdminApprovalController;
use App\Http\Controllers\Api\Admin\AdminBranchController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminMailController;
use App\Http\Controllers\Api\Admin\AdminNotificationController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminReportController;
use App\Http\Controllers\Api\Admin\AdminSettingsController;
use App\Http\Controllers\Api\Admin\AdminStaffController;
use App\Http\Controllers\Api\BranchAdmin\BaDashboardController;
use App\Http\Controllers\Api\BranchAdmin\BaExpenseController;
use App\Http\Controllers\Api\BranchAdmin\BaOrderController;
use App\Http\Controllers\Api\BranchAdmin\BaSalesController;
use App\Http\Controllers\Api\BranchAdmin\BaStaffController;
use App\Http\Controllers\Api\Cashier\CaCrossBranchController;
use App\Http\Controllers\Api\Cashier\CaDashboardController;
use App\Http\Controllers\Api\Cashier\CaExpenseController;
use App\Http\Controllers\Api\Cashier\CaOrderController;
use App\Http\Controllers\Api\Cashier\CaSalesController;
use App\Http\Controllers\Api\CustomerCare\CcCustomerController;
use App\Http\Controllers\Api\CustomerCare\CcDashboardController;
use App\Http\Controllers\Api\CustomerCare\CcInquiryController;
use App\Http\Controllers\Api\CustomerCare\CcMailController;
use App\Http\Controllers\Api\CustomerCare\CcNewsController;
use App\Http\Controllers\Api\CustomerCare\CcOrderController;
use App\Http\Controllers\Api\CustomerCare\CcSalesController;
use App\Http\Controllers\Api\GraphicDesigner\GdBrandsController;
use App\Http\Controllers\Api\GraphicDesigner\GdDashboardController;
use App\Http\Controllers\Api\GraphicDesigner\GdNewsController;
use App\Http\Controllers\Api\GraphicDesigner\GdQrCodeController;
use App\Http\Controllers\Api\Seller\SeDashboardController;
use App\Http\Controllers\Api\Seller\SeOrderController;
use App\Http\Controllers\Api\Seller\SeSalesController;
use App\Http\Controllers\Api\StockManager\SmBottleAccessoriesController;
use App\Http\Controllers\Api\StockManager\SmBottleStockController;
use App\Http\Controllers\Api\StockManager\SmDashboardController;
use App\Http\Controllers\Api\StockManager\SmOilFragranceController;
use App\Http\Controllers\Api\StockManager\SmOrderController;
use App\Http\Controllers\Api\StockManager\SmProductController;
use App\Http\Controllers\Api\StockManager\SmProductStockController;
use App\Http\Controllers\Api\StockManager\SmSalesController;
use App\Http\Controllers\Api\StockManager\SmTransferController;
use App\Http\Controllers\Api\StockManager\SmTransferReturnController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\InfoMailWebhookController;
use App\Http\Controllers\Api\StaffAuthController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\ProductController;
use App\Http\Middleware\EnsureStaffAccessApi;
use App\Http\Middleware\EnsureStaffSessionApi;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| Machine endpoints only — no session, no CSRF, no signed-in user. The mail
| webhook authenticates with the EMAIL_RECEIVING_WEBHOOK secret instead of
| a login, so nothing here is reachable with a password.
|
*/

// Where mail sent to info@worldchoiceperfume.com is delivered.
// Cloudflare Email Routing → a Worker → this URL. See config/info_mail.php.
Route::post('/inbound-emails', [InfoMailWebhookController::class, 'store'])
    ->name('api.inbound-emails.store');

/*
|--------------------------------------------------------------------------
| Mobile app (World Choice Perfume) JSON endpoints
|--------------------------------------------------------------------------
|
| The website stays session-based; these routes give the Expo app JSON
| versions of the same server-side logic by dispatching to the very
| controllers the website uses (they return JSON when the request sends
| `Accept: application/json`). No endpoint here accepts or returns the
| staff secret code or a password hash — the code is compared against
| CompanySettingService on the server and only "verified" comes back.
|
*/

// Home payload: branches, featured brands, category images, reviews,
// and the contact details from config/contact.php + config/info_mail.php.
Route::get('/home', [HomeController::class, 'index']);

// Catalogue — reuses Customer\ProductController with the website's own
// branch/search/category/brand query parameters.
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Place an order — twin of the customer website's POST /orders
// (Customer\OrderController@store): the same validation, stock checks,
// variety pricing and order creation. Answers JSON for the app.
Route::post('/orders', [OrderController::class, 'store']);

// Order tracking by phone — same lookup, timeline and status logic as
// the customer website's POST /orders/track.
Route::post('/orders/track', [OrderController::class, 'trackByPhone']);

// Staff flow mirrors the website: verify the secret code first…
Route::post('/verify-staff-access', [AuthController::class, 'verifyStaffAccess']);

// …then everything the website protects with the `staff.access` middleware
// (login form + all registrations) requires the encrypted grant issued by
// the line above, presented as the X-Staff-Access header.
Route::middleware(EnsureStaffAccessApi::class)->group(function () {
    // Stateless twin of AuthController::login.
    Route::post('/staff/login', [StaffAuthController::class, 'login']);

    // The six registration types linked from the website's login page.
    // Same controllers as the Blade forms; they answer JSON when the
    // request sends `Accept: application/json`.
    Route::post('/register/cashier', [AuthController::class, 'registerCashier']);
    Route::post('/register/branch-admin', [AuthController::class, 'registerBranchAdmin']);
    Route::post('/register/stock-manager', [AuthController::class, 'registerStockManager']);
    Route::post('/register/customer-care', [AuthController::class, 'registerCustomerCare']);
    Route::post('/register/seller', [AuthController::class, 'registerSeller']);
    Route::post('/register/graphic-designer', [AuthController::class, 'registerGraphicDesigner']);
});

// OTP verification — public on the website too (knowledge-based: it needs
// the emailed code), so it stays open here with the same validation.
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

/*
|--------------------------------------------------------------------------
| Super Admin module (mobile)
|
| JSON twins of the website's /super-admin/* pages. Every route below sits
| behind EnsureStaffSessionApi:super_admin, which decrypts the X-Staff-Session
| token issued at /api/staff/login, re-reads the user row from the database
| and refuses anything whose role is not super_admin — the app can never grant
| itself access, it can only present the token the server issued.
|--------------------------------------------------------------------------
*/
Route::middleware(EnsureStaffSessionApi::class.':super_admin')->prefix('admin')->group(function () {
    // Overview: dashboard tiles + the three monitoring screens on the sidebar.
    Route::get('/dashboard', [AdminDashboardController::class, 'dashboard']);
    Route::get('/daily-sales', [AdminDashboardController::class, 'dailySales']);
    Route::get('/cross-branch/stock', [AdminDashboardController::class, 'crossBranchStock']);
    Route::get('/cross-branch/sales', [AdminDashboardController::class, 'crossBranchSales']);

    // Branches (options first so it is not swallowed by {id}).
    Route::get('/branches/options', [AdminBranchController::class, 'options']);
    Route::get('/branches', [AdminBranchController::class, 'index']);
    Route::post('/branches', [AdminBranchController::class, 'store']);
    Route::get('/branches/{id}', [AdminBranchController::class, 'show']);
    Route::put('/branches/{id}', [AdminBranchController::class, 'update']);
    Route::delete('/branches/{id}', [AdminBranchController::class, 'destroy']);
    Route::delete('/branches/{id}/purge', [AdminBranchController::class, 'purge']);

    // Approvals (pending / all staff accounts).
    Route::get('/approvals', [AdminApprovalController::class, 'index']);
    Route::get('/approvals/{id}', [AdminApprovalController::class, 'show']);
    Route::post('/approvals/{id}/approve', [AdminApprovalController::class, 'approve']);
    Route::post('/approvals/{id}/reject', [AdminApprovalController::class, 'reject']);

    // Orders monitor (all branches).
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
    Route::post('/orders/{id}/personal-name', [AdminOrderController::class, 'personalName']);

    // Staff management.
    Route::get('/staff/options', [AdminStaffController::class, 'options']);
    Route::get('/staff', [AdminStaffController::class, 'index']);
    Route::post('/staff', [AdminStaffController::class, 'store']);
    Route::get('/staff/{id}', [AdminStaffController::class, 'show']);
    Route::post('/staff/{id}/toggle-status', [AdminStaffController::class, 'toggleStatus']);
    Route::post('/staff/{id}/status', [AdminStaffController::class, 'changeStatus']);
    Route::delete('/staff/{id}', [AdminStaffController::class, 'destroy']);

    // info@ mailbox (the same service Customer Care reads).
    Route::get('/emails', [AdminMailController::class, 'index']);
    Route::get('/emails/{id}', [AdminMailController::class, 'show']);
    Route::post('/emails/{id}/reply', [AdminMailController::class, 'reply']);
    Route::post('/emails/{id}/read', [AdminMailController::class, 'toggleRead']);
    Route::post('/emails/{id}/star', [AdminMailController::class, 'star']);
    Route::post('/emails/{id}/status', [AdminMailController::class, 'status']);
    Route::delete('/emails/{id}', [AdminMailController::class, 'destroy']);

    // Admin notifications.
    Route::get('/notifications', [AdminNotificationController::class, 'index']);
    Route::get('/notifications/report', [AdminNotificationController::class, 'report']);
    Route::post('/notifications/mark-all-read', [AdminNotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/mark-read', [AdminNotificationController::class, 'markRead']);

    // Reports + Returned Stock.
    Route::get('/reports', [AdminReportController::class, 'index']);
    Route::get('/reports/sales', [AdminReportController::class, 'sales']);
    Route::get('/reports/expenses', [AdminReportController::class, 'expenses']);
    Route::get('/reports/stock', [AdminReportController::class, 'stock']);
    Route::get('/reports/staff-performance', [AdminReportController::class, 'staffPerformance']);
    Route::get('/reports/product-performance', [AdminReportController::class, 'productPerformance']);
    Route::get('/returned-stock', [AdminReportController::class, 'returnedStock']);

    // Settings + account (profile / password).
    Route::get('/settings', [AdminSettingsController::class, 'settings']);
    Route::post('/settings', [AdminSettingsController::class, 'updateSettings']);
    Route::get('/profile', [AdminSettingsController::class, 'profile']);
    Route::put('/profile', [AdminSettingsController::class, 'updateProfile']);
    Route::put('/profile/password', [AdminSettingsController::class, 'changePassword']);
});

/*
|--------------------------------------------------------------------------
| Graphic Designer module (mobile)
|
| JSON twins of the website's /graphic-designer/* pages, behind
| EnsureStaffSessionApi:graphic_designer — the same per-call session + role
| re-verification the admin group uses. The shared profile endpoints reuse
| AdminSettingsController because the website's profile pages are the same
| ProfileController for every role.
|--------------------------------------------------------------------------
*/
Route::middleware(EnsureStaffSessionApi::class.':graphic_designer')->prefix('gd')->group(function () {
    // Dashboard: five stat counts + rejected posts (Redesign & Submit list).
    Route::get('/dashboard', [GdDashboardController::class, 'dashboard']);

    // News (author-scoped CRUD; submit/edit always re-enter approval).
    Route::get('/news', [GdNewsController::class, 'index']);
    Route::post('/news', [GdNewsController::class, 'store']);
    Route::get('/news/{id}', [GdNewsController::class, 'show']);
    Route::put('/news/{id}', [GdNewsController::class, 'update']);
    Route::delete('/news/{id}', [GdNewsController::class, 'destroy']);

    // Brands (duplicate-name guard, rename → product sync, in-use delete
    // protection).
    Route::get('/brands', [GdBrandsController::class, 'index']);
    Route::post('/brands', [GdBrandsController::class, 'store']);
    Route::get('/brands/{id}', [GdBrandsController::class, 'show']);
    Route::put('/brands/{id}', [GdBrandsController::class, 'update']);
    Route::delete('/brands/{id}', [GdBrandsController::class, 'destroy']);

    // QR code (server-rendered PNG data URI — no canvas on mobile).
    Route::get('/qr-code', [GdQrCodeController::class, 'qrCode']);

    // Shared profile / password (the website's ProfileController, all roles).
    Route::get('/profile', [AdminSettingsController::class, 'profile']);
    Route::put('/profile', [AdminSettingsController::class, 'updateProfile']);
    Route::put('/profile/password', [AdminSettingsController::class, 'changePassword']);
});

/*
|--------------------------------------------------------------------------
| Stock Manager module (mobile)
|
| JSON twins of the website's /stock-manager/* pages, behind
| EnsureStaffSessionApi:stock_manager — the same per-call session + role
| re-verification the admin and GD groups use. Two branch rules from the
| website are enforced inside the controllers:
|
|   * branch.category = products_based  →  bottle / oil fragrance /
|     bottle accessories endpoints answer 403 and the dashboard reports
|     is_products_only (stateless twin of `stock-manager.bottle-access`);
|   * the Kinondoni stock manager may read other branches via ?branch_id=
|     (stateless twin of the cross-branch session) and everything outside
|     his own branch is read-only.
|
| Route order matters: specific paths are declared before their {id}
| placeholders.
|--------------------------------------------------------------------------
*/
Route::middleware(EnsureStaffSessionApi::class.':stock_manager')->prefix('sm')->group(function () {
    // Scope flags (products-only / cross-branch) + dashboard.
    Route::get('/scope', [SmDashboardController::class, 'scope']);
    Route::get('/dashboard', [SmDashboardController::class, 'dashboard']);
    Route::get('/cross-branch', [SmDashboardController::class, 'crossBranch']);

    // Product Stock (available to every branch category).
    Route::get('/product-stock', [SmProductStockController::class, 'index']);
    Route::get('/product-stock/low-stock', [SmProductStockController::class, 'lowStock']);
    Route::get('/product-stock/movements', [SmProductStockController::class, 'movements']);
    Route::get('/product-stock/entry', [SmProductStockController::class, 'entry']);
    Route::post('/product-stock/entry', [SmProductStockController::class, 'storeEntry']);
    Route::put('/product-stock/varieties/{variety}', [SmProductStockController::class, 'updateVariety']);
    Route::delete('/product-stock/varieties/{variety}', [SmProductStockController::class, 'destroyVariety']);
    Route::put('/product-stock/{stock}', [SmProductStockController::class, 'update']);
    Route::delete('/product-stock/{stock}', [SmProductStockController::class, 'destroy']);

    // Bottle Stock (403 for products-only branches, like the website).
    Route::get('/bottle-stock', [SmBottleStockController::class, 'index']);
    Route::get('/bottle-stock/movements', [SmBottleStockController::class, 'movements']);
    Route::get('/bottle-stock/broken', [SmBottleStockController::class, 'brokenOptions']);
    Route::post('/bottle-stock/in', [SmBottleStockController::class, 'storeIn']);
    Route::post('/bottle-stock/broken', [SmBottleStockController::class, 'storeBroken']);
    Route::get('/bottle-stock/{stock}', [SmBottleStockController::class, 'show']);
    Route::put('/bottle-stock/{stock}', [SmBottleStockController::class, 'update']);
    Route::delete('/bottle-stock/{stock}', [SmBottleStockController::class, 'destroy']);

    // Oil Fragrance Stock (403 for products-only branches).
    Route::get('/oil-fragrance', [SmOilFragranceController::class, 'index']);
    Route::get('/oil-fragrance/options', [SmOilFragranceController::class, 'options']);
    Route::get('/oil-fragrance/movements', [SmOilFragranceController::class, 'movements']);
    Route::post('/oil-fragrance/in', [SmOilFragranceController::class, 'storeIn']);
    Route::post('/oil-fragrance/out', [SmOilFragranceController::class, 'storeOut']);
    Route::put('/oil-fragrance/{stock}', [SmOilFragranceController::class, 'update']);
    Route::delete('/oil-fragrance/{stock}', [SmOilFragranceController::class, 'destroy']);

    // Bottle Accessories (403 for products-only branches).
    Route::get('/bottle-accessories', [SmBottleAccessoriesController::class, 'index']);
    Route::get('/bottle-accessories/movements', [SmBottleAccessoriesController::class, 'movements']);
    Route::post('/bottle-accessories', [SmBottleAccessoriesController::class, 'store']);
    Route::post('/bottle-accessories/stock-out', [SmBottleAccessoriesController::class, 'stockOut']);
    Route::put('/bottle-accessories/{accessory}', [SmBottleAccessoriesController::class, 'update']);
    Route::delete('/bottle-accessories/{accessory}', [SmBottleAccessoriesController::class, 'destroy']);

    // Stock Transfers (specific paths before {transfer}).
    $stc = SmTransferController::class;
    $strc = SmTransferReturnController::class;
    Route::get('/stock-transfers', [$stc, 'index']);
    Route::get('/stock-transfers/create', [$stc, 'create']);
    Route::post('/stock-transfers', [$stc, 'store']);
    Route::get('/stock-transfers/incoming', [$stc, 'incoming']);
    Route::get('/stock-transfers/returns', [$strc, 'returns']);
    Route::get('/stock-transfers/lost-items', [$strc, 'lostForm']);
    Route::post('/stock-transfers/lost-items', [$strc, 'declareLost']);
    Route::post('/stock-transfers/items/{item}/receive', [$stc, 'receive']);
    Route::post('/stock-transfers/items/{item}/receive-invalid', [$stc, 'receiveInvalid']);
    Route::post('/stock-transfers/items/{item}/resend', [$strc, 'resend']);
    Route::post('/stock-transfers/items/{item}/write-off', [$strc, 'writeOff']);
    Route::get('/stock-transfers/{transfer}', [$stc, 'show']);

    // Returned Stock module (Kinondoni branch stock manager only).
    Route::get('/returned-stock', [$strc, 'returnedStockIndex']);
    Route::get('/returned-stock/items/{item}/damage-report', [$strc, 'damageReportForm']);
    Route::post('/returned-stock/items/{item}/damage-report', [$strc, 'damageReportStore']);

    // Sales (the manager's own sales record).
    Route::get('/sales', [SmSalesController::class, 'index']);
    Route::get('/sales/options', [SmSalesController::class, 'options']);
    Route::post('/sales', [SmSalesController::class, 'store']);
    Route::get('/sales/{sale}', [SmSalesController::class, 'show']);

    // Orders (three tabs: pending queue / picked / served).
    Route::get('/orders', [SmOrderController::class, 'index']);
    Route::get('/orders/{order}', [SmOrderController::class, 'show']);
    Route::post('/orders/{order}/status', [SmOrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/personal-name', [SmOrderController::class, 'personalName']);

    // Product catalogue management.
    Route::get('/products', [SmProductController::class, 'index']);
    Route::get('/products/form-data', [SmProductController::class, 'formData']);
    Route::post('/products', [SmProductController::class, 'store']);
    Route::get('/products/{product}', [SmProductController::class, 'show']);
    Route::put('/products/{product}', [SmProductController::class, 'update']);
    Route::delete('/products/{product}', [SmProductController::class, 'destroy']);
    Route::delete('/product-images/{image}', [SmProductController::class, 'removeImage']);

    // Shared profile / password (same ProfileController twins as admin/GD).
    Route::get('/profile', [AdminSettingsController::class, 'profile']);
    Route::put('/profile', [AdminSettingsController::class, 'updateProfile']);
    Route::put('/profile/password', [AdminSettingsController::class, 'changePassword']);
});

/*
|--------------------------------------------------------------------------
| Customer Care module (mobile)
|
| JSON twins of the website's /customer-care/* pages, behind
| EnsureStaffSessionApi:customer_care. Two tiers, exactly as the website:
| every member works their own branch's clients, sales and orders; the
| Head Quarters-Mikocheni member additionally owns inquiries, news
| moderation and the info@ mailbox, which the controllers gate with
| assertHq() — the same rule as the `customer-care.hq` middleware.
|--------------------------------------------------------------------------
*/
Route::middleware(EnsureStaffSessionApi::class.':customer_care')->prefix('care')->group(function () {
    // Overview + the HQ flag the app's navigation is shaped from.
    Route::get('/scope', [CcDashboardController::class, 'scope']);
    Route::get('/dashboard', [CcDashboardController::class, 'dashboard']);

    // Clients (list with branch tabs, create, record with transactions).
    Route::get('/customers', [CcCustomerController::class, 'index']);
    Route::post('/customers', [CcCustomerController::class, 'store']);
    Route::get('/customers/{customer}', [CcCustomerController::class, 'show']);

    // Sales (listing, sale-form data, checkout, receipt).
    Route::get('/sales', [CcSalesController::class, 'index']);
    Route::get('/sales/options', [CcSalesController::class, 'options']);
    Route::post('/sales', [CcSalesController::class, 'store']);
    Route::get('/sales/{sale}', [CcSalesController::class, 'show']);

    // Orders (three tabs: pending queue / picked / served).
    Route::get('/orders', [CcOrderController::class, 'index']);
    Route::get('/orders/{order}', [CcOrderController::class, 'show']);
    Route::post('/orders/{order}/status', [CcOrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/personal-name', [CcOrderController::class, 'personalName']);

    // Inquiries — Head Quarters-Mikocheni customer care only (assertHq).
    Route::get('/inquiries', [CcInquiryController::class, 'index']);
    Route::post('/inquiries/{inquiry}/reply', [CcInquiryController::class, 'reply']);
    Route::post('/inquiries/{inquiry}/read', [CcInquiryController::class, 'markAsRead']);
    Route::post('/inquiries/{inquiry}/comment', [CcInquiryController::class, 'markAsComment']);
    Route::delete('/inquiries/{inquiry}', [CcInquiryController::class, 'destroy']);
    Route::get('/inquiries/{inquiry}', [CcInquiryController::class, 'show']);

    // News moderation — Head Quarters-Mikocheni customer care only (assertHq).
    Route::get('/news', [CcNewsController::class, 'index']);
    Route::post('/news', [CcNewsController::class, 'store']);
    Route::get('/news/{post}/edit', [CcNewsController::class, 'edit']);
    Route::put('/news/{post}', [CcNewsController::class, 'update']);
    Route::post('/news/{post}/approve', [CcNewsController::class, 'approve']);
    Route::post('/news/{post}/reject', [CcNewsController::class, 'reject']);
    Route::delete('/news/{post}', [CcNewsController::class, 'destroy']);

    // The info@ mailbox — Head Quarters-Mikocheni customer care only (assertHq).
    Route::get('/mails', [CcMailController::class, 'index']);
    Route::get('/mails/{mail}', [CcMailController::class, 'show']);
    Route::post('/mails/{mail}/reply', [CcMailController::class, 'reply']);
    Route::post('/mails/{mail}/read', [CcMailController::class, 'toggleRead']);
    Route::post('/mails/{mail}/star', [CcMailController::class, 'star']);
    Route::post('/mails/{mail}/status', [CcMailController::class, 'status']);
    Route::delete('/mails/{mail}', [CcMailController::class, 'destroy']);

    // Shared profile / password (same ProfileController twins as admin/GD/SM).
    Route::get('/profile', [AdminSettingsController::class, 'profile']);
    Route::put('/profile', [AdminSettingsController::class, 'updateProfile']);
    Route::put('/profile/password', [AdminSettingsController::class, 'changePassword']);
});

/*
|--------------------------------------------------------------------------
| Branch Admin module (mobile)
|
| JSON twins of the website's /branch-admin/* pages, behind
| EnsureStaffSessionApi:branch_admin. No HQ tier and no cross-branch mode —
| every controller scopes to the member's own branch. Two behavioural rules
| from the website are enforced inside the controllers:

|   * orders are SUPERVISORY: tabRows/counts run isolated=false, so the
|     whole branch's queue is visible with waiting times and team activity,
|     while picking/serving still belongs to whoever claimed the order;
|   * expenses are read-only (cashiers commit them) and staff management is
|     branch-scoped with the website's 403 guards.
|
| Route order matters: specific paths are declared before their {id}
| placeholders.
|--------------------------------------------------------------------------
*/
Route::middleware(EnsureStaffSessionApi::class.':branch_admin')->prefix('ba')->group(function () {
    // Overview + the branch flags the app's navigation is shaped from.
    Route::get('/scope', [BaDashboardController::class, 'scope']);
    Route::get('/dashboard', [BaDashboardController::class, 'dashboard']);

    // Sales (cashier/date-filtered listing, sale-form data, checkout, receipt).
    Route::get('/sales', [BaSalesController::class, 'index']);
    Route::get('/sales/options', [BaSalesController::class, 'options']);
    Route::post('/sales', [BaSalesController::class, 'store']);
    Route::get('/sales/{sale}', [BaSalesController::class, 'show']);

    // Orders (supervisory: the whole branch's queue, read-only visibility).
    Route::get('/orders', [BaOrderController::class, 'index']);
    Route::get('/orders/{order}', [BaOrderController::class, 'show']);
    Route::post('/orders/{order}/status', [BaOrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/personal-name', [BaOrderController::class, 'personalName']);

    // Expenses (view-only; cashiers commit them).
    Route::get('/expenses', [BaExpenseController::class, 'index']);
    Route::get('/expenses/{expense}', [BaExpenseController::class, 'show']);

    // Staff (branch-scoped list + form data, create, approve / reject).
    Route::get('/staffs', [BaStaffController::class, 'index']);
    Route::get('/staffs/form-data', [BaStaffController::class, 'create']);
    Route::post('/staffs', [BaStaffController::class, 'store']);
    Route::post('/staffs/{staff}/approve', [BaStaffController::class, 'approve']);
    Route::post('/staffs/{staff}/reject', [BaStaffController::class, 'reject']);

    // Shared profile / password (same ProfileController twins as the rest).
    Route::get('/profile', [AdminSettingsController::class, 'profile']);
    Route::put('/profile', [AdminSettingsController::class, 'updateProfile']);
    Route::put('/profile/password', [AdminSettingsController::class, 'changePassword']);
});

/*
|--------------------------------------------------------------------------
| Seller module (mobile)
|
| JSON twins of the website's /seller/* pages, behind
| EnsureStaffSessionApi:seller. Two behavioural rules from the website are
| enforced inside the controllers:

|   * sales data is PERSONAL: listings filter cashier_id to the member and
|     a receipt that was not rung up by them answers 403;
|   * order tabs run ISOLATED (pending is the shared branch queue, picked /
|     served only ever belong to the seller who claimed them), and only
|     picked/served transitions are accepted.
|
| Route order matters: specific paths are declared before their {id}
| placeholders.
|--------------------------------------------------------------------------
*/
Route::middleware(EnsureStaffSessionApi::class.':seller')->prefix('seller')->group(function () {
    // Overview + the branch flags the app's navigation is shaped from.
    Route::get('/scope', [SeDashboardController::class, 'scope']);
    Route::get('/dashboard', [SeDashboardController::class, 'dashboard']);

    // Sales (own sales with date range, sale-form data, checkout, receipt).
    Route::get('/sales', [SeSalesController::class, 'index']);
    Route::get('/sales/options', [SeSalesController::class, 'options']);
    Route::post('/sales', [SeSalesController::class, 'store']);
    Route::get('/sales/{sale}', [SeSalesController::class, 'show']);

    // Orders (isolated queue: shared pending, personal picked/completed).
    Route::get('/orders', [SeOrderController::class, 'index']);
    Route::get('/orders/{order}', [SeOrderController::class, 'show']);
    Route::post('/orders/{order}/status', [SeOrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/personal-name', [SeOrderController::class, 'personalName']);

    // Shared profile / password (same ProfileController twins as the rest).
    Route::get('/profile', [AdminSettingsController::class, 'profile']);
    Route::put('/profile', [AdminSettingsController::class, 'updateProfile']);
    Route::put('/profile/password', [AdminSettingsController::class, 'changePassword']);
});

/*
|--------------------------------------------------------------------------
| Cashier module (mobile)
|
| JSON twins of the website's /cashier/* pages, behind
| EnsureStaffSessionApi:cashier,super_admin — the same two roles the
| website's `role:cashier,super_admin` group accepts. Three behavioural
| rules from the website are enforced inside the controllers:

|   * sales data is personal (cashier_id) unless an HQ monitor watches
|     another branch, in which case reads follow `monitor_branch` — the
|     stateless twin of CashierScope's session mode, re-checked against
|     the database row on every call;
|   * every write is refused while monitoring ("read-only. Exit the
|     branch"), exactly like EnsureCashierCrossBranchReadOnly;
|   * pick and serve are dedicated endpoints that REQUIRE a note, and
|     serve records the sale, deducts stock and writes the movements.
|
| Route order matters: specific paths are declared before their {id}
| placeholders.
|--------------------------------------------------------------------------
*/
Route::middleware(EnsureStaffSessionApi::class.':cashier,super_admin')->prefix('cashier')->group(function () {
    // Overview + the flags the app's navigation is shaped from.
    Route::get('/scope', [CaDashboardController::class, 'scope']);
    Route::get('/dashboard', [CaDashboardController::class, 'dashboard']);

    // Sales (own sales / monitored branch, sale-form data, checkout, receipt).
    Route::get('/sales', [CaSalesController::class, 'index']);
    Route::get('/sales/options', [CaSalesController::class, 'options']);
    Route::post('/sales', [CaSalesController::class, 'store']);
    Route::get('/sales/{sale}', [CaSalesController::class, 'show']);

    // Expenses — the only role that commits them.
    Route::get('/expenses', [CaExpenseController::class, 'index']);
    Route::post('/expenses', [CaExpenseController::class, 'store']);

    // Orders (isolated queue; dedicated pick/serve, note required).
    Route::get('/orders', [CaOrderController::class, 'index']);
    Route::get('/orders/{order}', [CaOrderController::class, 'show']);
    Route::post('/orders/{order}/pick', [CaOrderController::class, 'pick']);
    Route::post('/orders/{order}/serve', [CaOrderController::class, 'serve']);
    Route::post('/orders/{order}/personal-name', [CaOrderController::class, 'personalName']);

    // HQ-only monitoring (HQ cashier / Super Admin — 403 otherwise).
    Route::get('/cross-branch', [CaCrossBranchController::class, 'crossBranch']);
    Route::get('/daily-sales-overview', [CaCrossBranchController::class, 'dailySalesOverview']);

    // Shared profile / password (same ProfileController twins as the rest).
    Route::get('/profile', [AdminSettingsController::class, 'profile']);
    Route::put('/profile', [AdminSettingsController::class, 'updateProfile']);
    Route::put('/profile/password', [AdminSettingsController::class, 'changePassword']);
});
