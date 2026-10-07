/**
 * Branch Admin module — the single client for every /api/ba/* endpoint.
 *
 * Each function mirrors one website Branch Admin page/operation (see
 * routes/api.php → ba group and routes/web.php → branch-admin group).
 * Nothing here implements business logic: validation, authorization and the
 * branch scoping all happen in Laravel; this file only names the endpoints
 * and types their responses.
 *
 * Two server rules shape the module:
 *
 *   * everything is scoped to the admin's own branch (no HQ tier, no
 *     cross-branch mode — the website's role:branch_admin group is the same);
 *   * the order queue is SUPERVISORY: it lists every order at the branch
 *     with waiting times and team activity, but picking/serving still
 *     belongs to whoever claimed the order — the server refuses a takeover.
 */
import { apiGet, apiPost, apiPut } from './api';

/* ------------------------------------------------------------------ *
 * Shared shapes
 * ------------------------------------------------------------------ */

/** Branch context the app shapes navigation from. */
export interface BaScope {
  role: 'branch_admin';
  own_branch_id: number;
  branch_id: number;
  branch_name: string | null;
}

export function fetchBaScope(): Promise<BaScope> {
  return apiGet<{ scope: BaScope }>('/ba/scope').then((r) => r.scope);
}

/* ------------------------------ Dashboard -------------------------- */

export interface BaDashboardPayload {
  todaySales: number;
  todayTransactions: number;
  pendingOrders: number;
  lowStock: number;
  totalStockValue: number;
  dailySummary: {
    daily_sales?: number;
    daily_expenses?: number;
    actual_sales?: number;
    transaction_count?: number;
  };
  financials: {
    revenue?: number;
    expenses?: number;
    transaction_count?: number;
    products_sold?: number;
    stock_remaining?: number;
  };
  scope: BaScope;
}

export function fetchBaDashboard(): Promise<BaDashboardPayload> {
  return apiGet<BaDashboardPayload>('/ba/dashboard');
}

/* ------------------------------- Sales ----------------------------- */

export interface BaSale {
  id: number | string;
  sale_number?: string | null;
  total?: number | null;
  subtotal?: number | null;
  payment_method?: string | null;
  payment_summary?: string | null;
  sale_type?: string | null;
  payment_status?: string | null;
  created_at?: string | null;
  customer?: { id?: number | string; name?: string | null; phone?: string | null } | null;
  cashier?: { id?: number | string; name?: string | null } | null;
  branch?: { id?: number | string; name?: string | null; address?: string | null } | null;
  items?: Record<string, unknown>[] | null;
}

export interface BaSalesList {
  sales: BaSale[];
  totalSales: number;
  cashiers: { id: number | string; name: string }[];
  scope: BaScope;
}

/** The website's index filters: cashier + date_from/date_to (YYYY-MM-DD). */
export function fetchBaSales(
  params: { cashier_id?: number | string; date_from?: string; date_to?: string } = {},
): Promise<BaSalesList> {
  return apiGet<BaSalesList>('/ba/sales', {
    ...(params.cashier_id ? { cashier_id: params.cashier_id } : {}),
    ...(params.date_from ? { date_from: params.date_from } : {}),
    ...(params.date_to ? { date_to: params.date_to } : {}),
  });
}

export interface BaSaleOptionProduct {
  id: number | string;
  product_id: number | string;
  quantity: number;
  selling_price: number;
  product?: {
    id: number | string;
    name?: string | null;
    brand?: string | null;
    category?: string | null;
    images?: { image_url?: string | null }[];
  } | null;
}

export interface BaVarietyBucket {
  volume: number;
  label: string;
  variants: { key: string; label: string; available: number; price: number }[];
}

/** Per-product bottling buckets: productId → volumes that hold stock. */
export type BaVarietyBuckets = Record<string, BaVarietyBucket[]>;

/** The sale form's data: branch stock, bottle stock, variety buckets. */
export function fetchBaSaleOptions(): Promise<{
  products: BaSaleOptionProduct[];
  bottleStock: Record<string, number>;
  bottleVariants: Record<string, Record<string, number>>;
  productVarieties: BaVarietyBuckets;
  is_products_only: boolean;
  scope: BaScope;
}> {
  return apiGet('/ba/sales/options');
}

export interface BaSaleItemInput {
  product_id: number | string;
  quantity: number;
  custom_price?: number | null;
  volume?: number | null;
  variant?: string | null;
}

export interface BaEmptyBottleLine {
  volume: number;
  quantity: number;
  price: number;
  variant?: string;
}

export interface BaSaleFields {
  customer_id?: number | string | null;
  customer_name?: string;
  customer_phone?: string;
  payment_mode: 'single' | 'multi';
  payments: { method: 'cash' | 'bank_transfer' | 'mobile_payment'; amount?: number }[];
  items: BaSaleItemInput[];
  empty_bottles?: BaEmptyBottleLine[];
  sale_type: 'retail' | 'wholesale';
}

export function createBaSale(fields: BaSaleFields): Promise<{ message: string; sale: BaSale }> {
  return apiPost('/ba/sales', fields);
}

export function fetchBaSale(id: number | string): Promise<{ sale: BaSale }> {
  return apiGet<{ sale: BaSale }>(`/ba/sales/${id}`);
}

/* ------------------------------- Orders ---------------------------- */

export interface BaOrder {
  id: number | string;
  status?: string | null;
  customer_name?: string | null;
  personal_order_name?: string | null;
  items?: unknown;
  total?: number | null;
  created_at?: string | null;
  waiting_time?: string | null;
  picked_by?: number | string | null;
  [key: string]: unknown;
}

export interface BaOrderPayload {
  orders: BaOrder[];
  counts: Record<string, number>;
  /** Whole-branch pending watch: overdue/oldest pending orders. */
  pendingWatch: Record<string, unknown>;
  /** Who is doing what at the branch right now. */
  team: Record<string, unknown>;
  pickers: Record<string, string>;
  tab: string;
  tabLabels: Record<string, string>;
  transitions: Record<string, unknown>;
  userId: number;
  scope: BaScope;
}

export function fetchBaOrders(params: { tab?: string; q?: string } = {}): Promise<BaOrderPayload> {
  return apiGet<BaOrderPayload>('/ba/orders', {
    ...(params.tab ? { tab: params.tab } : {}),
    ...(params.q ? { q: params.q } : {}),
  });
}

export function fetchBaOrder(id: number | string): Promise<{
  order: BaOrder;
  transitions: Record<string, unknown>;
  userId: number;
  canName: boolean;
}> {
  return apiGet(`/ba/orders/${id}`);
}

export function updateBaOrderStatus(id: number | string, status: 'picked' | 'served', note?: string): Promise<{ message: string }> {
  // The server's statusChangeRules() requires a note on every status
  // change — default it so a quick tap still satisfies the website's rule.
  return apiPost<{ message: string }>(`/ba/orders/${id}/status`, { status, note: note ?? (status === 'picked' ? 'Picked up' : 'Served') });
}

export function setBaOrderPersonalName(id: number | string, personal_order_name: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/ba/orders/${id}/personal-name`, { personal_order_name });
}

/* ------------------------------ Expenses --------------------------- */

export interface BaExpense {
  id: number | string;
  amount?: number | null;
  category?: string | null;
  description?: string | null;
  created_at?: string | null;
  user?: { id: number | string; name?: string | null } | null;
  branch?: { id: number | string; name?: string | null } | null;
}

/**
 * View-only expenses (cashiers commit them). With no dates the server
 * window is the current business day; a date range replaces it.
 */
export function fetchBaExpenses(
  params: { date_from?: string; date_to?: string; category?: string } = {},
): Promise<{ expenses: BaExpense[]; totalExpenses: number; rangeLabel: string; scope: BaScope }> {
  return apiGet('/ba/expenses', {
    ...(params.date_from ? { date_from: params.date_from } : {}),
    ...(params.date_to ? { date_to: params.date_to } : {}),
    ...(params.category ? { category: params.category } : {}),
  });
}

export function fetchBaExpense(id: number | string): Promise<{ expense: BaExpense }> {
  return apiGet<{ expense: BaExpense }>(`/ba/expenses/${id}`);
}

/* -------------------------------- Staff ---------------------------- */

export type BaStaffRole = 'stock_manager' | 'seller' | 'customer_care' | 'cashier';

export const BA_STAFF_ROLES: BaStaffRole[] = ['stock_manager', 'seller', 'customer_care', 'cashier'];

export interface BaStaffMember {
  id: number | string;
  name?: string | null;
  email?: string | null;
  phone?: string | null;
  role?: string | null;
  status?: string | null;
  created_at?: string | null;
  total_sales?: number;
}

export function fetchBaStaff(params: { role?: string; status?: string } = {}): Promise<{
  staff: BaStaffMember[];
  roles: string[];
  scope: BaScope;
}> {
  return apiGet('/ba/staffs', {
    ...(params.role ? { role: params.role } : {}),
    ...(params.status ? { status: params.status } : {}),
  });
}

/** The staff form's data: the branch name and the assignable roles. */
export function fetchBaStaffFormData(): Promise<{ roles: string[]; branchName: string | null }> {
  return apiGet('/ba/staffs/form-data');
}

export interface BaStaffFields {
  name: string;
  email: string;
  phone?: string;
  password: string;
  password_confirmation: string;
  role: BaStaffRole;
}

/** Duplicate email answers 422 { email: "This email is already registered." }. */
export function createBaStaff(fields: BaStaffFields): Promise<{ message: string; staff: BaStaffMember }> {
  return apiPost('/ba/staffs', fields);
}

export function approveBaStaff(id: number | string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/ba/staffs/${id}/approve`, {});
}

export function rejectBaStaff(id: number | string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/ba/staffs/${id}/reject`, {});
}

/* --------------------- Profile / password (shared) ----------------- */

export function fetchBaProfile(): Promise<Record<string, unknown>> {
  return apiGet<Record<string, unknown>>('/ba/profile');
}

export function updateBaProfile(fields: Record<string, string>): Promise<{ message?: string }> {
  return apiPut<{ message?: string }>('/ba/profile', fields);
}

export function changeBaPassword(fields: { current_password: string; password: string; password_confirmation: string }): Promise<{ message?: string }> {
  return apiPut<{ message?: string }>('/ba/profile/password', fields);
}
