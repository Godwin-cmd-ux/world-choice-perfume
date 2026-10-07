/**
 * Cashier module — the single client for every /api/cashier/* endpoint.
 *
 * Each function mirrors one website Cashier page/operation (see
 * routes/api.php → cashier group and routes/web.php → cashier group).
 * Nothing here implements business logic: validation, authorization and the
 * scoping all happen in Laravel; this file only names the endpoints and
 * types their responses.
 *
 * Three server rules shape the module:
 *
 *   * sales data is personal (cashier_id) — unless an HQ monitor passes
 *     `monitor_branch`, the stateless twin of the website's session-based
 *     cross-branch mode, which the server re-authorises on every call;
 *   * every write is refused while monitoring another branch ("read-only.
 *     Exit the branch");
 *   * pick and serve each REQUIRE a note, and serve records the sale and
 *     deducts stock — the full handover, not a plain status flip.
 */
import { apiGet, apiPost, apiPut } from './api';

/* ------------------------------------------------------------------ *
 * Shared shapes
 * ------------------------------------------------------------------ */

/** Branch context the app shapes navigation from. */
export interface CashierScope {
  role: 'cashier';
  own_branch_id: number;
  /** The active branch: own branch, or the monitored one while monitoring. */
  branch_id: number;
  branch_name: string | null;
  /** HQ cashier / Super Admin only — may monitor other branches. */
  is_cross_branch_monitor: boolean;
  /** True while watching a branch that is not the member's own. */
  in_cross_branch: boolean;
}

export function fetchCashierScope(params: { monitor_branch?: number } = {}): Promise<CashierScope> {
  return apiGet<{ scope: CashierScope }>('/cashier/scope', {
    ...(params.monitor_branch ? { monitor_branch: params.monitor_branch } : {}),
  }).then((r) => r.scope);
}

/* ------------------------------ Dashboard -------------------------- */

export interface CashierDashboardPayload {
  todaySales: number;
  todayTransactions: number;
  pendingOrders: number;
  /** Orders this cashier currently carries (branch-wide while monitoring). */
  myAssignedOrders: number;
  recentSales: {
    id: number | string;
    total?: number | null;
    sale_number?: string | null;
    payment_method?: string | null;
    sale_type?: string | null;
    created_at?: string | null;
    cashier?: { id?: number | string; name?: string | null } | null;
    items?: Record<string, unknown>[] | null;
  }[];
  dailySummary: {
    daily_sales?: number;
    daily_expenses?: number;
    actual_sales?: number;
    transaction_count?: number;
  };
  scope: CashierScope;
}

export function fetchCashierDashboard(params: { monitor_branch?: number } = {}): Promise<CashierDashboardPayload> {
  return apiGet('/cashier/dashboard', {
    ...(params.monitor_branch ? { monitor_branch: params.monitor_branch } : {}),
  });
}

/* ------------------------------- Sales ----------------------------- */

export interface CashierSale {
  id: number | string;
  sale_number?: string | null;
  total?: number | null;
  subtotal?: number | null;
  payment_method?: string | null;
  payment_summary?: string | null;
  sale_type?: string | null;
  payment_status?: string | null;
  created_at?: string | null;
  cashier_id?: number | string | null;
  customer?: { id?: number | string; name?: string | null; phone?: string | null } | null;
  cashier?: { id?: number | string; name?: string | null } | null;
  branch?: { id?: number | string; name?: string | null; address?: string | null } | null;
  items?: Record<string, unknown>[] | null;
}

/** The website's index filters: date_from/date_to (YYYY-MM-DD). */
export function fetchCashierSales(
  params: { date_from?: string; date_to?: string; monitor_branch?: number } = {},
): Promise<{ sales: CashierSale[]; totalSales: number; scope: CashierScope }> {
  return apiGet('/cashier/sales', {
    ...(params.date_from ? { date_from: params.date_from } : {}),
    ...(params.date_to ? { date_to: params.date_to } : {}),
    ...(params.monitor_branch ? { monitor_branch: params.monitor_branch } : {}),
  });
}

export interface CashierSaleOptionProduct {
  id: number | string;
  product_id: number | string;
  quantity: number;
  selling_price: number;
  product?: {
    id?: number | string;
    name?: string | null;
    brand?: string | null;
    category?: string | null;
    images?: { image_url?: string | null }[];
  } | null;
}

export interface CashierVarietyBucket {
  volume: number;
  label: string;
  variants: { key: string; label: string; available: number; price: number }[];
}

/** Per-product bottling buckets: productId → volumes that hold stock. */
export type CashierVarietyBuckets = Record<string, CashierVarietyBucket[]>;

/**
 * The sale form's data: branch stock, bottle stock, variety buckets.
 * Answers 403 while monitoring another branch (the website's create()).
 */
export function fetchCashierSaleOptions(): Promise<{
  products: CashierSaleOptionProduct[];
  bottleStock: Record<string, number>;
  bottleVariants: Record<string, Record<string, number>>;
  productVarieties: CashierVarietyBuckets;
  is_products_only: boolean;
  scope: CashierScope;
}> {
  return apiGet('/cashier/sales/options');
}

export interface CashierSaleItemInput {
  product_id: number | string;
  quantity: number;
  custom_price?: number | null;
  volume?: number | null;
  variant?: string | null;
}

export interface CashierEmptyBottleLine {
  volume: number;
  quantity: number;
  price: number;
  variant?: string;
}

export interface CashierSaleFields {
  customer_id?: number | string | null;
  customer_name?: string;
  customer_phone?: string;
  payment_mode: 'single' | 'multi';
  payments: { method: 'cash' | 'bank_transfer' | 'mobile_payment'; amount?: number }[];
  items: CashierSaleItemInput[];
  empty_bottles?: CashierEmptyBottleLine[];
  sale_type: 'retail' | 'wholesale';
}

export function createCashierSale(fields: CashierSaleFields): Promise<{ message: string; sale: CashierSale }> {
  return apiPost('/cashier/sales', fields);
}

/** The receipt is personal: someone else's sale answers 403. */
export function fetchCashierSale(id: number | string): Promise<{ sale: CashierSale }> {
  return apiGet<{ sale: CashierSale }>(`/cashier/sales/${id}`);
}

/* ------------------------------ Expenses --------------------------- */

export interface CashierExpense {
  id: number | string;
  amount?: number | null;
  category?: string | null;
  description?: string | null;
  date?: string | null;
  created_at?: string | null;
  user?: { id?: number | string; name?: string | null } | null;
}

/**
 * The active branch's expenses with the business-day window (a date range
 * replaces it), the window total and the categories the store accepts.
 */
export function fetchCashierExpenses(
  params: { date_from?: string; date_to?: string; category?: string; monitor_branch?: number } = {},
): Promise<{
  expenses: CashierExpense[];
  totalExpenses: number;
  rangeLabel: string;
  categories: string[];
  scope: CashierScope;
}> {
  return apiGet('/cashier/expenses', {
    ...(params.date_from ? { date_from: params.date_from } : {}),
    ...(params.date_to ? { date_to: params.date_to } : {}),
    ...(params.category ? { category: params.category } : {}),
    ...(params.monitor_branch ? { monitor_branch: params.monitor_branch } : {}),
  });
}

/** Duplicate of the website's hardcoded rule: min amount 0.01, description ≥ 10. */
export function createCashierExpense(fields: { category: string; amount: number; description: string }): Promise<{ message: string }> {
  return apiPost('/cashier/expenses', fields);
}

/* ------------------------------- Orders ---------------------------- */

export interface CashierOrder {
  id: number | string;
  order_number?: string | null;
  status?: string | null;
  customer_name?: string | null;
  personal_order_name?: string | null;
  items?: unknown;
  total?: number | null;
  created_at?: string | null;
  assigned_to?: number | string | null;
  picked_by?: number | string | null;
  [key: string]: unknown;
}

export interface CashierOrderPayload {
  orders: CashierOrder[];
  counts: Record<string, number>;
  pickers: Record<string, string>;
  tab: string;
  tabLabels: Record<string, string>;
  transitions: Record<string, unknown>;
  userId: number;
  scope: CashierScope;
}

export function fetchCashierOrders(
  params: { tab?: string; q?: string; monitor_branch?: number } = {},
): Promise<CashierOrderPayload> {
  return apiGet<CashierOrderPayload>('/cashier/orders', {
    ...(params.tab ? { tab: params.tab } : {}),
    ...(params.q ? { q: params.q } : {}),
    ...(params.monitor_branch ? { monitor_branch: params.monitor_branch } : {}),
  });
}

export function fetchCashierOrder(id: number | string): Promise<{
  order: CashierOrder;
  transitions: Record<string, unknown>;
  userId: number;
  canName: boolean;
}> {
  return apiGet(`/cashier/orders/${id}`);
}

/** Claim a pending order — the note is required, exactly like the website. */
export function pickCashierOrder(id: number | string, note: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/cashier/orders/${id}/pick`, { note });
}

/**
 * Serve a picked order — the full handover: the sale is recorded and stock
 * deducted server-side. The note is required, exactly like the website.
 */
export function serveCashierOrder(id: number | string, note: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/cashier/orders/${id}/serve`, { note });
}

export function setCashierOrderPersonalName(id: number | string, personal_order_name: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/cashier/orders/${id}/personal-name`, { personal_order_name });
}

/* --------------------- HQ monitoring (HQ cashier) ------------------ */

export interface CashierBranchRow {
  id: number;
  name: string;
  address?: string | null;
  todayRevenue: number;
  todayTransactions: number;
  todayPaid: number;
  pendingOrders: number;
  activeCashiers: number;
}

/** Every branch's today — 403 unless the member is an HQ monitor. */
export function fetchCashierCrossBranch(): Promise<{ branches: CashierBranchRow[]; isSuperAdmin: boolean; scope: CashierScope }> {
  return apiGet('/cashier/cross-branch');
}

export interface CashierOverviewRow {
  id: number;
  name: string;
  dailySales: number;
  dailyExpenses: number;
  actualSales: number;
  salesPercent: number;
  transactions: number;
  staff: {
    id: number;
    name: string;
    role: string;
    sales: number;
    expenses: number;
    actual: number;
    salesPercent: number;
  }[];
}

/** Company-wide daily overview — 403 unless the member is an HQ monitor. */
export function fetchCashierDailyOverview(): Promise<{
  rows: CashierOverviewRow[];
  companySales: number;
  companyExpenses: number;
  isSuperAdmin: boolean;
  scope: CashierScope;
}> {
  return apiGet('/cashier/daily-sales-overview');
}

/* --------------------- Profile / password (shared) ----------------- */

export function fetchCashierProfile(): Promise<Record<string, unknown>> {
  return apiGet<Record<string, unknown>>('/cashier/profile');
}

export function updateCashierProfile(fields: Record<string, string>): Promise<{ message?: string }> {
  return apiPut<{ message?: string }>('/cashier/profile', fields);
}

export function changeCashierPassword(fields: { current_password: string; password: string; password_confirmation: string }): Promise<{ message?: string }> {
  return apiPut<{ message?: string }>('/cashier/profile/password', fields);
}
