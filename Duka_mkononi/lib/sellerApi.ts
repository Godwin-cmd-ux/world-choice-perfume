/**
 * Seller module — the single client for every /api/seller/* endpoint.
 *
 * Each function mirrors one website Seller page/operation (see
 * routes/api.php → seller group and routes/web.php → seller group). Nothing
 * here implements business logic: validation, authorization and the scoping
 * all happen in Laravel; this file only names the endpoints and types their
 * responses.
 *
 * Two server rules shape the module:
 *
 *   * sales data is personal — listings are the member's own sales and a
 *     receipt rung up by anyone else answers 403;
 *   * the order queue is isolated — pending is the shared branch queue,
 *     picked/completed work only ever belongs to the seller who claimed it.
 */
import { apiGet, apiPost, apiPut } from './api';

/* ------------------------------------------------------------------ *
 * Shared shapes
 * ------------------------------------------------------------------ */

/** Branch context the app shapes navigation from. */
export interface SellerScope {
  role: 'seller';
  own_branch_id: number;
  branch_id: number;
  branch_name: string | null;
}

export function fetchSellerScope(): Promise<SellerScope> {
  return apiGet<{ scope: SellerScope }>('/seller/scope').then((r) => r.scope);
}

/* ------------------------------ Dashboard -------------------------- */

export interface SellerDashboardPayload {
  /** Sum of the seller's latest 50 sales. */
  totalSales: number;
  totalTransactions: number;
  /** The seller's own sales inside the current business day. */
  todayTotal: number;
  /** The seller's latest 50 sales, newest first. */
  mySales: {
    id: number | string;
    total?: number | null;
    payment_method?: string | null;
    sale_type?: string | null;
    created_at?: string | null;
  }[];
  /** Branch stock with quantity above zero (what the shelf holds). */
  products: {
    id: number | string;
    product_id?: number | string;
    quantity?: number | null;
    selling_price?: number | null;
    product?: { id?: number | string; name?: string | null; brand?: string | null; category?: string | null } | null;
  }[];
  pendingOrders: number;
  dailySummary: {
    daily_sales?: number;
    daily_expenses?: number;
    actual_sales?: number;
    transaction_count?: number;
  };
  scope: SellerScope;
}

export function fetchSellerDashboard(): Promise<SellerDashboardPayload> {
  return apiGet<SellerDashboardPayload>('/seller/dashboard');
}

/* ------------------------------- Sales ----------------------------- */

export interface SellerSale {
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
export function fetchSellerSales(
  params: { date_from?: string; date_to?: string } = {},
): Promise<{ sales: SellerSale[]; totalSales: number; scope: SellerScope }> {
  return apiGet('/seller/sales', {
    ...(params.date_from ? { date_from: params.date_from } : {}),
    ...(params.date_to ? { date_to: params.date_to } : {}),
  });
}

export interface SellerSaleOptionProduct {
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

export interface SellerVarietyBucket {
  volume: number;
  label: string;
  variants: { key: string; label: string; available: number; price: number }[];
}

/** Per-product bottling buckets: productId → volumes that hold stock. */
export type SellerVarietyBuckets = Record<string, SellerVarietyBucket[]>;

/** The sale form's data: branch stock, bottle stock, variety buckets. */
export function fetchSellerSaleOptions(): Promise<{
  products: SellerSaleOptionProduct[];
  bottleStock: Record<string, number>;
  bottleVariants: Record<string, Record<string, number>>;
  productVarieties: SellerVarietyBuckets;
  is_products_only: boolean;
  scope: SellerScope;
}> {
  return apiGet('/seller/sales/options');
}

export interface SellerSaleItemInput {
  product_id: number | string;
  quantity: number;
  custom_price?: number | null;
  volume?: number | null;
  variant?: string | null;
}

export interface SellerEmptyBottleLine {
  volume: number;
  quantity: number;
  price: number;
  variant?: string;
}

export interface SellerSaleFields {
  customer_id?: number | string | null;
  customer_name?: string;
  customer_phone?: string;
  payment_mode: 'single' | 'multi';
  payments: { method: 'cash' | 'bank_transfer' | 'mobile_payment'; amount?: number }[];
  items: SellerSaleItemInput[];
  empty_bottles?: SellerEmptyBottleLine[];
  sale_type: 'retail' | 'wholesale';
}

export function createSellerSale(fields: SellerSaleFields): Promise<{ message: string; sale: SellerSale }> {
  return apiPost('/seller/sales', fields);
}

/** The receipt is personal: someone else's sale answers 403. */
export function fetchSellerSale(id: number | string): Promise<{ sale: SellerSale }> {
  return apiGet<{ sale: SellerSale }>(`/seller/sales/${id}`);
}

/* ------------------------------- Orders ---------------------------- */

export interface SellerOrder {
  id: number | string;
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

export interface SellerOrderPayload {
  orders: SellerOrder[];
  counts: Record<string, number>;
  pickers: Record<string, string>;
  tab: string;
  tabLabels: Record<string, string>;
  transitions: Record<string, unknown>;
  userId: number;
  scope: SellerScope;
}

export function fetchSellerOrders(params: { tab?: string; q?: string } = {}): Promise<SellerOrderPayload> {
  return apiGet<SellerOrderPayload>('/seller/orders', {
    ...(params.tab ? { tab: params.tab } : {}),
    ...(params.q ? { q: params.q } : {}),
  });
}

export function fetchSellerOrder(id: number | string): Promise<{
  order: SellerOrder;
  transitions: Record<string, unknown>;
  userId: number;
  canName: boolean;
}> {
  return apiGet(`/seller/orders/${id}`);
}

export function updateSellerOrderStatus(id: number | string, status: 'picked' | 'served', note?: string): Promise<{ message: string }> {
  // The server's statusChangeRules() requires a note on every status
  // change — default it so a quick tap still satisfies the website's rule.
  return apiPost<{ message: string }>(`/seller/orders/${id}/status`, { status, note: note ?? (status === 'picked' ? 'Picked up' : 'Served') });
}

export function setSellerOrderPersonalName(id: number | string, personal_order_name: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/seller/orders/${id}/personal-name`, { personal_order_name });
}

/* --------------------- Profile / password (shared) ----------------- */

export function fetchSellerProfile(): Promise<Record<string, unknown>> {
  return apiGet<Record<string, unknown>>('/seller/profile');
}

export function updateSellerProfile(fields: Record<string, string>): Promise<{ message?: string }> {
  return apiPut<{ message?: string }>('/seller/profile', fields);
}

export function changeSellerPassword(fields: { current_password: string; password: string; password_confirmation: string }): Promise<{ message?: string }> {
  return apiPut<{ message?: string }>('/seller/profile/password', fields);
}
