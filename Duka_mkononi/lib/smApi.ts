/**
 * Stock Manager module — the single client for every /api/sm/* endpoint.
 *
 * Each function mirrors one website Stock Manager page/operation (see
 * routes/api.php → sm group and routes/web.php → stock-manager group).
 * Nothing here implements business logic: validation, authorization, stock
 * arithmetic and the products-only (branch.category) rules all happen in
 * Laravel; this file only names the endpoints and types their responses.
 *
 * Two server rules show up in the types:
 *   * `scope.is_products_only` — true when the manager's branch is
 *     products_based: the app hides bottles/oil/accessories, and those
 *     endpoints answer 403 if called anyway;
 *   * `scope.can_monitor_cross_branch` — the Kinondoni manager may pass
 *     `branchId` to read-only listing calls to monitor another branch.
 */
import { apiDelete, apiGet, apiPost, apiPut } from './api';

/* ------------------------------------------------------------------ *
 * Shared shapes
 * ------------------------------------------------------------------ */

/** Branch/category context the app uses to hide whole sections. */
export interface SmScope {
  is_products_only: boolean;
  branch_category: 'autonomous' | 'products_based';
  branch_category_label: string;
  own_branch_id: number;
  branch_id: number;
  branch_name: string | null;
  in_cross_branch: boolean;
  can_monitor_cross_branch: boolean;
  can_returned_stock?: boolean;
  monitorable_branches?: SmBranch[];
}

export interface SmBranch {
  id: number;
  name: string;
  address?: string | null;
}

/** Optional cross-branch monitoring filter (Kinondoni manager only). */
export interface SmBranchScoped {
  branchId?: number | null;
}

function branchQuery(branchId?: number | null): Record<string, string | number> {
  return branchId ? { branch_id: branchId } : {};
}

export interface SmMovement {
  id: number | string;
  branch_id?: number | string;
  product_id?: number | string | null;
  volume?: string | null;
  name?: string | null;
  type?: string | null;
  movement_type?: string | null;
  quantity?: number | null;
  unit_price?: number | null;
  reason?: string | null;
  notes?: string | null;
  performed_by?: number | string | null;
  performedBy?: { id: number; name: string } | null;
  created_at?: string | null;
}

/* --------------------------- Dashboard ---------------------------- */

export interface SmDashboardPayload {
  totalProductItems: number;
  totalProductTypes: number;
  lowStockProducts: number;
  lowStockThreshold: number;
  mySalesTodayCount: number;
  mySalesTodayTotal: number;
  totalBottles: number;
  totalOilFragrances: number;
  recentBottleMovements: SmMovement[];
  recentOilMovements: SmMovement[];
  pendingOrders: number;
  openOrders: number;
  ordersToday: number;
  scope: SmScope;
}

export function fetchSmScope(): Promise<SmScope> {
  return apiGet<SmScope>('/sm/scope');
}

export function fetchSmDashboard(): Promise<SmDashboardPayload> {
  return apiGet<SmDashboardPayload>('/sm/dashboard');
}

export interface SmCrossBranchRow {
  id: number;
  name: string;
  address?: string | null;
  totalProducts: number;
  lowStock: number;
  totalBottles: number;
  totalOils: number;
}

export function fetchCrossBranchRows(): Promise<{ rows: SmCrossBranchRow[]; scope: SmScope }> {
  return apiGet<{ rows: SmCrossBranchRow[]; scope: SmScope }>('/sm/cross-branch');
}

/* -------------------------- Product stock ------------------------- */

export interface SmProductRef {
  id: number | string;
  name?: string | null;
  brand?: string | null;
  category?: string | null;
  images?: { image_url?: string | null }[] | null;
}

export interface SmStockRow {
  kind: 'variety' | 'product';
  label: string;
  product?: SmProductRef | null;
  stock_id?: number | string | null;
  quantity: number;
  selling_price: number;
  category?: string | null;
  date_received?: string | null;
  variety?: Record<string, unknown> | null;
}

export function fetchProductStock(params: { search?: string; branchId?: number | null } = {}): Promise<{
  rows: SmStockRow[];
  totalValue: number;
  scope: SmScope;
}> {
  return apiGet<{ rows: SmStockRow[]; totalValue: number; scope: SmScope }>('/sm/product-stock', {
    ...(params.search ? { search: params.search } : {}),
    ...branchQuery(params.branchId),
  });
}

export function fetchLowStock(): Promise<{
  rows: {
    product?: SmProductRef | null;
    product_id?: number | string | null;
    name: string;
    brand?: string | null;
    category?: string | null;
    quantity: number;
    selling_price: number;
  }[];
  threshold: number;
  scope: SmScope;
}> {
  return apiGet('/sm/product-stock/low-stock');
}

export interface SmProductStockEntryForm {
  products: { id: number | string; name: string; brand?: string | null; category?: string | null }[];
  preselectProductId?: number | string | null;
  preselectCategory?: string | null;
  /** [volumeMl][variantKey] = quantity available at this branch. */
  bottleVariants: Record<string, Record<string, number>>;
  bottleVariantsBranchName?: string | null;
  is_products_only?: boolean;
}

export function fetchProductStockEntry(productId?: number | string | null): Promise<SmProductStockEntryForm> {
  return apiGet<SmProductStockEntryForm>('/sm/product-stock/entry', productId ? { product_id: productId } : {});
}

export interface SmStockEntryFields {
  product_id: number | string;
  quantity: number;
  selling_price: number;
  variety_price?: number | null;
  category: 'Oil Fragrance' | 'Brand Perfume';
  bottle_volume?: number | null;
  bottle_variant?: string | null;
  date_received: string;
}

export function createProductStockEntry(fields: SmStockEntryFields): Promise<{ message: string }> {
  return apiPost<{ message: string }>('/sm/product-stock/entry', fields);
}

export function updateProductStock(
  stockId: number | string,
  fields: { quantity: number; selling_price: number },
): Promise<{ message: string }> {
  return apiPut<{ message: string }>(`/sm/product-stock/${stockId}`, fields);
}

export function deleteProductStock(stockId: number | string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/sm/product-stock/${stockId}`);
}

export function updateStockVariety(
  varietyId: number | string,
  fields: { quantity: number; selling_price: number },
): Promise<{ message: string }> {
  return apiPut<{ message: string }>(`/sm/product-stock/varieties/${varietyId}`, fields);
}

export function deleteStockVariety(varietyId: number | string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/sm/product-stock/varieties/${varietyId}`);
}

export function fetchProductMovements(params: { product_id?: number | string; type?: string } = {}): Promise<{
  movements: (SmMovement & { product?: SmProductRef | null })[];
  products: { id: number | string; name: string; brand?: string | null }[];
  scope: SmScope;
}> {
  return apiGet('/sm/product-stock/movements', params);
}

/* --------------------------- Bottle stock ------------------------- */

export interface SmBottleRecord {
  id: number | string;
  volume?: string | null;
  variant?: string | null;
  quantity?: number | null;
  has_box?: string | null;
  has_logo?: string | null;
  logo_color?: string | null;
  box_color?: string | null;
}

export function fetchBottleStock(params: { search?: string; branchId?: number | null } = {}): Promise<{
  volumes: string[];
  bottleMap: Record<string, number>;
  variantLabelMap: Record<string | number, string>;
  bottleRecords: SmBottleRecord[];
  scope: SmScope;
}> {
  return apiGet('/sm/bottle-stock', { ...(params.search ? { search: params.search } : {}), ...branchQuery(params.branchId) });
}

export interface SmBottleStockInFields {
  volume: string;
  quantity: number;
  reason?: string;
  has_box?: 'yes' | 'no';
  has_logo?: 'yes' | 'no';
  logo_color?: 'yellow' | 'black' | 'white';
}

export function addBottleStock(fields: SmBottleStockInFields): Promise<{ message: string; variant_tracking: boolean }> {
  return apiPost('/sm/bottle-stock/in', fields);
}

export function fetchBrokenOptions(): Promise<{
  volumes: string[];
  bottleVariants: Record<string, Record<string, number>>;
}> {
  return apiGet('/sm/bottle-stock/broken');
}

export function recordBrokenBottles(fields: {
  volume: string;
  quantity: number;
  reason?: string;
  variant?: string;
}): Promise<{ message: string }> {
  return apiPost<{ message: string }>('/sm/bottle-stock/broken', fields);
}

export function updateBottleStock(
  id: number | string,
  fields: {
    quantity: number;
    has_box?: 'yes' | 'no';
    has_logo?: 'yes' | 'no';
    logo_color?: 'yellow' | 'black' | 'white';
    box_color?: 'black' | 'white';
  },
): Promise<{ message: string }> {
  return apiPut<{ message: string }>(`/sm/bottle-stock/${id}`, fields);
}

export function deleteBottleStock(id: number | string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/sm/bottle-stock/${id}`);
}

export function fetchBottleMovements(params: { type?: string; volume?: string } = {}): Promise<{
  movements: SmMovement[];
  volumes: string[];
  scope: SmScope;
}> {
  return apiGet('/sm/bottle-stock/movements', params);
}

/* ------------------------- Oil fragrance -------------------------- */

export interface SmOilRecord {
  id: number | string;
  name?: string | null;
  volume?: number | string | null;
  quantity?: number | null;
}

export function fetchOilStock(params: { search?: string; branchId?: number | null } = {}): Promise<{
  oils: SmOilRecord[];
  totalQuantity: number;
  scope: SmScope;
}> {
  return apiGet('/sm/oil-fragrance', { ...(params.search ? { search: params.search } : {}), ...branchQuery(params.branchId) });
}

export function fetchOilOptions(): Promise<{
  oilProducts: { id: number | string; name: string; brand?: string | null }[];
  stockByProduct: Record<string, number>;
  stockByProductVolume: Record<string, number>;
}> {
  return apiGet('/sm/oil-fragrance/options');
}

export interface SmOilMoveFields {
  product_id: number | string;
  quantity: number;
  bottle_volume: 500 | 1000;
  reason?: string;
}

export function oilStockIn(fields: SmOilMoveFields): Promise<{ message: string }> {
  return apiPost<{ message: string }>('/sm/oil-fragrance/in', fields);
}

export function oilStockOut(fields: SmOilMoveFields): Promise<{ message: string }> {
  return apiPost<{ message: string }>('/sm/oil-fragrance/out', fields);
}

export function updateOilStock(id: number | string, fields: { quantity: number }): Promise<{ message: string }> {
  return apiPut<{ message: string }>(`/sm/oil-fragrance/${id}`, fields);
}

export function deleteOilStock(id: number | string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/sm/oil-fragrance/${id}`);
}

export function fetchOilMovements(params: { type?: string } = {}): Promise<{
  movements: SmMovement[];
  scope: SmScope;
}> {
  return apiGet('/sm/oil-fragrance/movements', params);
}

/* ----------------------- Bottle accessories ----------------------- */

export type SmAccessoryType = 'straws' | 'bottlenecks' | 'bottle_tops';
export type SmAccessoryColor = 'silver' | 'gold';

export interface SmAccessory {
  id: number | string;
  type?: string | null;
  color?: string | null;
  quantity?: number | null;
}

export function fetchAccessories(params: { branchId?: number | null } = {}): Promise<{
  grouped: Record<string, SmAccessory[]>;
  totalPackets: number;
  scope: SmScope;
}> {
  return apiGet('/sm/bottle-accessories', branchQuery(params.branchId));
}

export interface SmAccessoryFields {
  type: SmAccessoryType;
  color: SmAccessoryColor;
  quantity: number;
  reason?: string;
}

export function addAccessory(fields: SmAccessoryFields): Promise<{ message: string }> {
  return apiPost<{ message: string }>('/sm/bottle-accessories', fields);
}

export function accessoryStockOut(fields: SmAccessoryFields): Promise<{ message: string }> {
  return apiPost<{ message: string }>('/sm/bottle-accessories/stock-out', fields);
}

export function updateAccessory(id: number | string, fields: { quantity: number }): Promise<{ message: string }> {
  return apiPut<{ message: string }>(`/sm/bottle-accessories/${id}`, fields);
}

export function deleteAccessory(id: number | string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/sm/bottle-accessories/${id}`);
}

export function fetchAccessoryMovements(params: { type?: string } = {}): Promise<{
  movements: SmMovement[];
  scope: SmScope;
}> {
  return apiGet('/sm/bottle-accessories/movements', params);
}

/* --------------------------- Transfers ---------------------------- */

export type SmTransferType = 'product' | 'bottle' | 'oil_fragrance' | 'bottle_accessories';

export interface SmTransfer {
  id: number | string;
  transfer_number?: string | null;
  stock_type?: string | null;
  stock_type_label?: string;
  from_branch_id?: number;
  to_branch_id?: number;
  from_branch_name?: string;
  to_branch_name?: string;
  status?: string;
  officer_name?: string | null;
  officer_phone?: string | null;
  received_at?: string | null;
  created_at?: string | null;
  items_total?: number;
  items_pending?: number;
  items_returned?: number;
  note?: string | null;
}

export function fetchTransfers(params: { branchId?: number | null } = {}): Promise<{
  transfers: SmTransfer[];
  branchName: string | null;
  activeBranchId: number;
  scope: SmScope;
}> {
  return apiGet('/sm/stock-transfers', branchQuery(params.branchId));
}

export interface SmTransferFormPayload {
  type: SmTransferType;
  type_label: string;
  branches: SmBranch[];
  fromBranchId: number;
  fromBranchName: string | null;
  is_products_only: boolean;
  options?: Record<string, unknown>[];
  productVarieties?: Record<string, { volume: number; label: string; variants: { key: string; label: string; available: number }[] }[]>;
  hasVarietyTracking?: boolean;
  volumes?: string[];
}

export function fetchTransferForm(type: SmTransferType): Promise<SmTransferFormPayload> {
  return apiGet<SmTransferFormPayload>('/sm/stock-transfers/create', { type });
}

export interface SmTransferItemInput {
  product_id?: number | string;
  quantity: number;
  volume?: string | number | null;
  variant?: string | null;
  name?: string | null;
  type?: string | null;
  color?: string | null;
}

export interface SmTransferCreateFields {
  type: SmTransferType;
  to_branch_id: number;
  officer_name: string;
  officer_phone: string;
  officer_id?: string | null;
  note?: string | null;
  items: SmTransferItemInput[];
}

export function createTransfer(fields: SmTransferCreateFields): Promise<{ message: string; transfer: SmTransfer }> {
  return apiPost('/sm/stock-transfers', fields);
}

export interface SmIncomingRow {
  item: Record<string, unknown> & { id: number | string; quantity?: number | null; status?: string | null };
  item_name: string;
  variety_label: string;
  oil_type: string;
  item_label: string;
  transfer_number?: string | null;
  stock_type?: string | null;
  stock_type_label?: string;
  from_branch_name?: string;
  note?: string | null;
  officer_name?: string | null;
  created_at?: string | null;
}

export function fetchIncoming(params: { branchId?: number | null } = {}): Promise<{
  rows: SmIncomingRow[];
  branchName: string | null;
  activeBranchId: number;
  scope: SmScope;
}> {
  return apiGet('/sm/stock-transfers/incoming', branchQuery(params.branchId));
}

export function receiveTransferItem(itemId: number | string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/sm/stock-transfers/items/${itemId}/receive`, {});
}

export function rejectTransferItem(itemId: number | string, reason: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/sm/stock-transfers/items/${itemId}/receive-invalid`, { reason });
}

export function fetchTransfer(id: number | string): Promise<{
  transfer: SmTransfer;
  stock_type_label: string;
  from_branch_name: string;
  to_branch_name: string;
  created_by_name?: string | null;
  received_by_name?: string | null;
  items: { item: Record<string, unknown>; item_label: string; received_by_name?: string | null; received_at?: string | null }[];
  pendingCount: number;
  activeBranchId: number;
}> {
  return apiGet(`/sm/stock-transfers/${id}`);
}

export interface SmReturnRow {
  item: Record<string, unknown> & {
    id: number | string;
    return_reason?: string | null;
    return_status?: string | null;
    resent_transfer_id?: number | string | null;
  };
  item_label: string;
  transfer_number?: string | null;
  stock_type_label?: string;
  to_branch_name?: string;
  returned_at?: string | null;
}

export function fetchReturns(params: { branchId?: number | null } = {}): Promise<{
  rows: SmReturnRow[];
  branchName: string | null;
  scope: SmScope;
}> {
  return apiGet('/sm/stock-transfers/returns', branchQuery(params.branchId));
}

export function resendReturnItem(itemId: number | string, officer?: { officer_name?: string; officer_phone?: string }): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/sm/stock-transfers/items/${itemId}/resend`, officer ?? {});
}

export function writeOffReturnItem(itemId: number | string, loss_reason: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/sm/stock-transfers/items/${itemId}/write-off`, { loss_reason });
}

export function fetchLostForm(): Promise<{
  transfers: { id: number | string; transfer_number?: string | null; stock_type?: string | null; stock_type_label: string; to_branch_name: string; created_at?: string | null }[];
  branchName: string | null;
  scope: SmScope;
}> {
  return apiGet('/sm/stock-transfers/lost-items');
}

export function declareLost(fields: {
  transfer_id: number | string;
  item: string;
  quantity: number;
  reason: string;
}): Promise<{ message: string }> {
  return apiPost<{ message: string }>('/sm/stock-transfers/lost-items', fields);
}

/* ------------------------- Returned stock ------------------------- */

export interface SmReturnedRow {
  item: Record<string, unknown> & {
    id: number | string;
    return_reason?: string | null;
    return_status?: string | null;
    damage_type?: string | null;
    damage_reason?: string | null;
    damage_reported_at?: string | null;
  };
  item_label: string;
  transfer_number?: string | null;
  stock_type_label?: string;
  from_branch_name?: string;
  to_branch_name?: string;
  officer_name?: string | null;
  return_reason?: string | null;
  return_status?: string | null;
  damage_type?: string | null;
  damage_reason?: string | null;
  damage_reported_by_name?: string | null;
  damage_reported_at?: string | null;
  returned_at?: string | null;
}

export function fetchReturnedStock(): Promise<{
  rows: SmReturnedRow[];
  branchName: string | null;
  activeBranchId: number;
  hasDamageColumns: boolean;
}> {
  return apiGet('/sm/returned-stock');
}

export function fetchDamageReportForm(itemId: number | string): Promise<{
  item: Record<string, unknown>;
  itemLabel: string;
  transfer: SmTransfer;
  fromBranchName: string | null;
  toBranchName: string | null;
}> {
  return apiGet(`/sm/returned-stock/items/${itemId}/damage-report`);
}

export function fileDamageReport(
  itemId: number | string,
  fields: { damage_type: 'lost' | 'broken'; damage_reason: string },
): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/sm/returned-stock/items/${itemId}/damage-report`, fields);
}

/* ------------------------------ Sales ----------------------------- */

export interface SmSale {
  id: number | string;
  sale_number?: string | null;
  total?: number | null;
  subtotal?: number | null;
  payment_method?: string | null;
  payment_summary?: string | null;
  sale_type?: string | null;
  created_at?: string | null;
  customer?: { id?: number | string; name?: string | null; phone?: string | null } | null;
  items?: Record<string, unknown>[] | null;
  cashier?: { id?: number | string; name?: string | null } | null;
}

export function fetchSales(params: { date_from?: string; date_to?: string; branchId?: number | null } = {}): Promise<{
  sales: SmSale[];
  totalSales: number;
  scope: SmScope;
}> {
  return apiGet('/sm/sales', {
    ...(params.date_from ? { date_from: params.date_from } : {}),
    ...(params.date_to ? { date_to: params.date_to } : {}),
    ...branchQuery(params.branchId),
  });
}

export interface SmSaleOptionProduct {
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

export interface SmVarietyBucket {
  volume: number;
  label: string;
  variants: { key: string; label: string; available: number; price: number }[];
}

/** Per-product bottling buckets: productId → volumes that hold stock. */
export type SmVarietyBuckets = Record<string, SmVarietyBucket[]>;

export function fetchSaleOptions(): Promise<{
  products: SmSaleOptionProduct[];
  bottleStock: Record<string, number>;
  bottleVariants: Record<string, Record<string, number>>;
  productVarieties: SmVarietyBuckets;
  is_products_only: boolean;
}> {
  return apiGet('/sm/sales/options');
}

export interface SmSaleItemInput {
  product_id: number | string;
  quantity: number;
  custom_price?: number | null;
  discount_price?: number | null;
  volume?: number | null;
  variant?: string | null;
}

export interface SmSaleFields {
  customer_name?: string;
  customer_phone?: string;
  payment_mode: 'single' | 'multi';
  payments: { method: 'cash' | 'bank_transfer' | 'mobile_payment'; amount?: number }[];
  items: SmSaleItemInput[];
  sale_type: 'retail' | 'wholesale';
}

export function createSale(fields: SmSaleFields): Promise<{ message: string; sale: SmSale }> {
  return apiPost('/sm/sales', fields);
}

export function fetchSale(id: number | string): Promise<{ sale: SmSale }> {
  return apiGet<{ sale: SmSale }>(`/sm/sales/${id}`);
}

/* ------------------------------ Orders ---------------------------- */

export interface SmOrder {
  id: number | string;
  status?: string | null;
  customer_name?: string | null;
  personal_order_name?: string | null;
  items?: unknown;
  total?: number | null;
  created_at?: string | null;
  picked_by?: number | string | null;
  [key: string]: unknown;
}

export interface SmOrderPayload {
  orders: SmOrder[];
  counts: Record<string, number>;
  pickers: Record<string, string>;
  tab: string;
  transitions: Record<string, unknown>;
  userId: number;
  scope: SmScope;
}

export function fetchOrders(params: { tab?: string; q?: string } = {}): Promise<SmOrderPayload> {
  return apiGet<SmOrderPayload>('/sm/orders', {
    ...(params.tab ? { tab: params.tab } : {}),
    ...(params.q ? { q: params.q } : {}),
  });
}

export function fetchOrder(id: number | string): Promise<{
  order: SmOrder;
  transitions: Record<string, unknown>;
  userId: number;
  canName: boolean;
}> {
  return apiGet(`/sm/orders/${id}`);
}

export function updateOrderStatus(id: number | string, status: 'picked' | 'served', note?: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/sm/orders/${id}/status`, { status, note });
}

export function setOrderPersonalName(id: number | string, personal_order_name: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/sm/orders/${id}/personal-name`, { personal_order_name });
}

/* ------------------------- Product catalogue ---------------------- */

export interface SmCatalogueProduct {
  id: number | string;
  name: string;
  brand?: string | null;
  category?: string | null;
  sex_category?: string | null;
  fundamental_ingredient?: string | null;
  description?: string | null;
  is_active?: boolean;
  created_at?: string | null;
  images?: { id?: number | string; image_url?: string | null }[] | null;
}

export function fetchProducts(params: { search?: string; include_inactive?: boolean } = {}): Promise<{
  products: SmCatalogueProduct[];
  brands: string[];
}> {
  return apiGet('/sm/products', {
    ...(params.search ? { search: params.search } : {}),
    ...(params.include_inactive ? { include_inactive: 1 } : {}),
  });
}

export function fetchProductFormData(): Promise<{
  brands: string[];
  fundamentalIngredients: string[];
  categories: string[];
  sexCategories: string[];
}> {
  return apiGet('/sm/products/form-data');
}

export interface SmProductFields {
  name: string;
  description?: string | null;
  brand?: string | null;
  category: 'Oil Fragrance' | 'Brand Perfume';
  sex_category?: string | null;
  fundamental_ingredient?: string | null;
}

export function createProduct(
  fields: SmProductFields,
  images?: { uri: string; name: string; type: string }[],
): Promise<{ message: string; product: SmCatalogueProduct }> {
  if (images && images.length > 0) {
    const form = new FormData();
    Object.entries(fields).forEach(([k, v]) => {
      if (v !== undefined && v !== null) form.append(k, String(v));
    });
    images.forEach((img) => form.append('images[]', img as unknown as Blob));
    return apiPost('/sm/products', form);
  }
  return apiPost('/sm/products', fields);
}

export function fetchProduct(id: number | string): Promise<{ product: SmCatalogueProduct; brands: string[]; fundamentalIngredients: string[] }> {
  return apiGet(`/sm/products/${id}`);
}

export function updateProduct(
  id: number | string,
  fields: SmProductFields & { is_active?: boolean },
  images?: { uri: string; name: string; type: string }[],
): Promise<{ message: string }> {
  if (images && images.length > 0) {
    const { is_active: active, ...rest } = fields;
    const form = new FormData();
    Object.entries(rest).forEach(([k, v]) => {
      if (v !== undefined && v !== null) form.append(k, String(v));
    });
    // Laravel's boolean rule only accepts true/false/0/1 — send '1'/'0',
    // and keep it out of the loop above so the key is not appended twice.
    form.append('is_active', active ? '1' : '0');
    images.forEach((img) => form.append('images[]', img as unknown as Blob));
    return apiPut<{ message: string }>(`/sm/products/${id}`, form);
  }
  return apiPut<{ message: string }>(`/sm/products/${id}`, {
    ...fields,
    // Laravel's boolean rule only accepts true/false/0/1 — send '1'/'0'.
    is_active: fields.is_active ? '1' : '0',
  });
}

export function deleteProduct(id: number | string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/sm/products/${id}`);
}

export function removeProductImage(imageId: number | string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/sm/product-images/${imageId}`);
}

/* --------------------- Profile / password (shared) ---------------- */

export function fetchSmProfile(): Promise<Record<string, unknown>> {
  return apiGet<Record<string, unknown>>('/sm/profile');
}

export function updateSmProfile(fields: Record<string, string>): Promise<{ message?: string }> {
  return apiPut<{ message?: string }>('/sm/profile', fields);
}

export function changeSmPassword(fields: { current_password: string; password: string; password_confirmation: string }): Promise<{ message?: string }> {
  return apiPut<{ message?: string }>('/sm/profile/password', fields);
}
