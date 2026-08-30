<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ========================
// PUBLIC / CUSTOMER ROUTES
// ========================
Route::get('/', function() {
    try {
        $supabase = new \App\Services\SupabaseService();
        $branches = collect($supabase->query('branches', [
            'select' => 'id,name,address,latitude,longitude,is_active',
            'is_active' => 'eq.true',
            'order' => 'name.asc',
        ]))->map(fn($b) => (object) $b);
    } catch (\Exception $e) {
        $branches = collect();
    }
    return view('home', compact('branches'));
})->name('home');
Route::get('/products', [\App\Http\Controllers\Customer\ProductController::class, 'index'])->name('customer.products.index');
Route::get('/products/{product}', [\App\Http\Controllers\Customer\ProductController::class, 'show'])->name('customer.products.show');

// Customer Orders
Route::prefix('orders')->name('customer.orders.')->group(function () {
    Route::get('/create', [\App\Http\Controllers\Customer\OrderController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Customer\OrderController::class, 'store'])->name('store');
    Route::get('/track', [\App\Http\Controllers\Customer\OrderController::class, 'track'])->name('track');
    Route::post('/track', [\App\Http\Controllers\Customer\OrderController::class, 'trackByPhone'])->name('track-by-phone');
});

// Twende Dukani — Navigate to branch
Route::get('/twende-dukani/{branch}', [\App\Http\Controllers\Customer\NavigationController::class, 'show'])->name('customer.twende-dukani');

// Customer search API (for sales)
Route::get('/api/customers/search', function(\Illuminate\Http\Request $request) {
    $q = $request->q ?? '';
    if (strlen($q) < 2) return response()->json([]);
    $sb = new \App\Services\SupabaseService();

    // Search by name first
    $byName = $sb->query('customers', [
        'select' => '*',
        'name' => 'ilike.*' . urlencode($q) . '*',
        'limit' => 10,
        'order' => 'name.asc',
    ]);

    // Search by phone
    $byPhone = $sb->query('customers', [
        'select' => '*',
        'phone' => 'ilike.*' . urlencode($q) . '*',
        'limit' => 10,
        'order' => 'name.asc',
    ]);

    // Merge and deduplicate by id
    $merged = [];
    foreach (array_merge($byName, $byPhone) as $c) {
        $merged[$c['id']] = $c;
    }
    ksort($merged);

    return response()->json(array_values(array_slice($merged, 0, 10)));
})->name('api.customers.search');

Route::post('/api/customers', function(\Illuminate\Http\Request $request) {
    $request->validate([
        'name' => 'nullable|string|max:255',
        'phone' => 'nullable|string|max:20',
    ]);
    $sb = new \App\Services\SupabaseService();
    $customer = $sb->insert('customers', [
        'name' => $request->name ?? null,
        'phone' => $request->phone ?? null,
        'whatsapp' => $request->phone ?? null,
        'created_at' => now()->toIso8601String(),
        'updated_at' => now()->toIso8601String(),
    ]);
    return response()->json($customer);
})->name('api.customers.store');

// ========================
// AUTH ROUTES
// ========================
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::post('/verify-staff-access', [AuthController::class, 'verifyStaffAccess'])->name('verify-staff-access');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Registration — protected by staff access secret code
    Route::middleware('staff.access')->group(function () {
        Route::get('/register/super-admin', [AuthController::class, 'showSuperAdminRegistration'])->name('register.super-admin');
        Route::post('/register/super-admin', [AuthController::class, 'registerSuperAdmin']);
        Route::get('/register/branch-admin', [AuthController::class, 'showBranchAdminRegistration'])->name('register.branch-admin');
        Route::post('/register/branch-admin', [AuthController::class, 'registerBranchAdmin']);
        Route::get('/register/cashier', [AuthController::class, 'showCashierRegistration'])->name('register.cashier');
        Route::post('/register/cashier', [AuthController::class, 'registerCashier']);
    });

    // OTP
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify-otp');
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('resend-otp');

    // Forgot / Reset Password
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.forgot');
    Route::post('/forgot-password', [AuthController::class, 'sendPasswordResetOtp'])->name('password.email');
    Route::get('/reset-password', [AuthController::class, 'showPasswordResetForm'])->name('password.reset.form');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
});

// ========================
// PROTECTED ROUTES
// ========================
Route::middleware(['auth', 'cashier.approved'])->group(function () {

    // Profile (all roles)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');
    Route::post('/profile/branch-location', [ProfileController::class, 'updateBranchLocation'])->name('profile.branch-location');

    // ========================
    // SUPER ADMIN ROUTES
    // ========================
    Route::prefix('super-admin')->name('super-admin.')->middleware('role:super_admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\SuperAdmin\DashboardController::class, 'index'])->name('dashboard');

        // Branches (no model binding - we fetch from Supabase)
        Route::get('/branches', [\App\Http\Controllers\SuperAdmin\BranchController::class, 'index'])->name('branches.index');
        Route::get('/branches/create', [\App\Http\Controllers\SuperAdmin\BranchController::class, 'create'])->name('branches.create');
        Route::post('/branches', [\App\Http\Controllers\SuperAdmin\BranchController::class, 'store'])->name('branches.store');
        Route::get('/branches/{branch}/edit', [\App\Http\Controllers\SuperAdmin\BranchController::class, 'edit'])->name('branches.edit');
        Route::put('/branches/{branch}', [\App\Http\Controllers\SuperAdmin\BranchController::class, 'update'])->name('branches.update');
        Route::delete('/branches/{branch}', [\App\Http\Controllers\SuperAdmin\BranchController::class, 'destroy'])->name('branches.destroy');

        // Cashier Approval
        Route::get('/cashiers', [\App\Http\Controllers\SuperAdmin\CashierApprovalController::class, 'index'])->name('cashiers.index');
        Route::get('/cashiers/{cashier}', [\App\Http\Controllers\SuperAdmin\CashierApprovalController::class, 'show'])->name('cashiers.show');
        Route::post('/cashiers/{cashier}/approve', [\App\Http\Controllers\SuperAdmin\CashierApprovalController::class, 'approve'])->name('cashiers.approve');
        Route::post('/cashiers/{cashier}/reject', [\App\Http\Controllers\SuperAdmin\CashierApprovalController::class, 'reject'])->name('cashiers.reject');

        // Staff Management
        Route::get('/staff', [\App\Http\Controllers\SuperAdmin\StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/{user}', [\App\Http\Controllers\SuperAdmin\StaffController::class, 'show'])->name('staff.show');
        Route::post('/staff/{user}/toggle-status', [\App\Http\Controllers\SuperAdmin\StaffController::class, 'toggleStatus'])->name('staff.toggle-status');
        Route::delete('/staff/{user}', [\App\Http\Controllers\SuperAdmin\StaffController::class, 'destroy'])->name('staff.destroy');
    });

    // ========================
    // BRANCH ADMIN ROUTES
    // ========================
    Route::prefix('branch-admin')->name('branch-admin.')->middleware('role:branch_admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\BranchAdmin\DashboardController::class, 'index'])->name('dashboard');

        // Products (no model binding - we fetch from Supabase)
        Route::get('/products', [\App\Http\Controllers\BranchAdmin\ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [\App\Http\Controllers\BranchAdmin\ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [\App\Http\Controllers\BranchAdmin\ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [\App\Http\Controllers\BranchAdmin\ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [\App\Http\Controllers\BranchAdmin\ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [\App\Http\Controllers\BranchAdmin\ProductController::class, 'destroy'])->name('products.destroy');
        Route::delete('/product-images/{image}', [\App\Http\Controllers\BranchAdmin\ProductController::class, 'removeImage'])->name('products.remove-image');

        // Stock
        Route::get('/stock', [\App\Http\Controllers\BranchAdmin\StockController::class, 'index'])->name('stock.index');
        Route::get('/stock/entry', [\App\Http\Controllers\BranchAdmin\StockController::class, 'entryForm'])->name('stock.entry');
        Route::post('/stock/entry', [\App\Http\Controllers\BranchAdmin\StockController::class, 'storeEntry'])->name('stock.store');
        Route::get('/stock/movements', [\App\Http\Controllers\BranchAdmin\StockController::class, 'movements'])->name('stock.movements');
        Route::post('/stock/adjust', [\App\Http\Controllers\BranchAdmin\StockController::class, 'adjust'])->name('stock.adjust');

        // Sales
        Route::get('/sales', [\App\Http\Controllers\BranchAdmin\SalesController::class, 'index'])->name('sales.index');
        Route::get('/sales/create', [\App\Http\Controllers\BranchAdmin\SalesController::class, 'create'])->name('sales.create');
        Route::post('/sales', [\App\Http\Controllers\BranchAdmin\SalesController::class, 'store'])->name('sales.store');
        Route::get('/sales/{sale}', [\App\Http\Controllers\BranchAdmin\SalesController::class, 'show'])->name('sales.show');

        // Orders
        Route::get('/orders', [\App\Http\Controllers\BranchAdmin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [\App\Http\Controllers\BranchAdmin\OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/cancel', [\App\Http\Controllers\BranchAdmin\OrderController::class, 'cancel'])->name('orders.cancel');

        // Expenses
        Route::get('/expenses', [\App\Http\Controllers\BranchAdmin\ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/expenses/create', [\App\Http\Controllers\BranchAdmin\ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [\App\Http\Controllers\BranchAdmin\ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/{expense}', [\App\Http\Controllers\BranchAdmin\ExpenseController::class, 'show'])->name('expenses.show');

        // Cashier Management
        Route::get('/cashiers', [\App\Http\Controllers\BranchAdmin\CashierManagementController::class, 'index'])->name('cashiers.index');
        Route::get('/cashiers/{cashier}', [\App\Http\Controllers\BranchAdmin\CashierManagementController::class, 'show'])->name('cashiers.show');
        Route::post('/cashiers/{cashier}/approve', [\App\Http\Controllers\BranchAdmin\CashierManagementController::class, 'approve'])->name('cashiers.approve');
        Route::post('/cashiers/{cashier}/reject', [\App\Http\Controllers\BranchAdmin\CashierManagementController::class, 'reject'])->name('cashiers.reject');
        Route::get('/cashiers/accountability', [\App\Http\Controllers\BranchAdmin\CashierManagementController::class, 'accountability'])->name('cashiers.accountability');
        Route::post('/cashiers/accountability', [\App\Http\Controllers\BranchAdmin\CashierManagementController::class, 'storeAccountability'])->name('cashiers.store-accountability');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [\App\Http\Controllers\BranchAdmin\ReportController::class, 'index'])->name('index');
            Route::get('/sales', [\App\Http\Controllers\BranchAdmin\ReportController::class, 'sales'])->name('sales');
            Route::get('/profit', [\App\Http\Controllers\BranchAdmin\ReportController::class, 'profit'])->name('profit');
            Route::get('/expenses', [\App\Http\Controllers\BranchAdmin\ReportController::class, 'expenses'])->name('expenses');
            Route::get('/stock', [\App\Http\Controllers\BranchAdmin\ReportController::class, 'stock'])->name('stock');
            Route::get('/cashier-performance', [\App\Http\Controllers\BranchAdmin\ReportController::class, 'cashierPerformance'])->name('cashier-performance');
            Route::get('/product-performance', [\App\Http\Controllers\BranchAdmin\ReportController::class, 'productPerformance'])->name('product-performance');
        });
    });

    // ========================
    // CASHIER ROUTES
    // ========================
    Route::prefix('cashier')->name('cashier.')->middleware('role:cashier')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Cashier\DashboardController::class, 'index'])->name('dashboard');

        // Sales
        Route::get('/sales', [\App\Http\Controllers\Cashier\SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/create', [\App\Http\Controllers\Cashier\SaleController::class, 'create'])->name('sales.create');
        Route::post('/sales', [\App\Http\Controllers\Cashier\SaleController::class, 'store'])->name('sales.store');
        Route::get('/sales/{sale}', [\App\Http\Controllers\Cashier\SaleController::class, 'show'])->name('sales.show');

        // Expenses (view only — creation moved to branch admin)
        Route::get('/expenses', [\App\Http\Controllers\Cashier\ExpenseController::class, 'index'])->name('expenses.index');

        // Orders
        Route::get('/orders', [\App\Http\Controllers\Cashier\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [\App\Http\Controllers\Cashier\OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/pick', [\App\Http\Controllers\Cashier\OrderController::class, 'pick'])->name('orders.pick');
        Route::post('/orders/{order}/ready', [\App\Http\Controllers\Cashier\OrderController::class, 'markReady'])->name('orders.ready');
        Route::post('/orders/{order}/complete', [\App\Http\Controllers\Cashier\OrderController::class, 'complete'])->name('orders.complete');
        Route::post('/orders/{order}/serve', [\App\Http\Controllers\Cashier\OrderController::class, 'serve'])->name('orders.serve');
    });
});
