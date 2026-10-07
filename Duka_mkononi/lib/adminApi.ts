/**
 * Super Admin module — the single client for every /api/admin/* endpoint.
 *
 * Each function mirrors one website Super Admin page/operation (see
 * routes/api.php → admin group). Nothing here implements business logic:
 * validation, authorization and writes all happen in Laravel; this file only
 * names the endpoints and types their responses. Every request automatically
 * carries the X-Staff-Session token via lib/api.ts, and the server re-checks
 * the super_admin role on each call.
 */
import { apiDelete, apiGet, apiPost, apiPut } from './api';

/* ------------------------------------------------------------------ *
 * Shared shapes
 * ------------------------------------------------------------------ */

export interface AdminBranchRef {
  id: number | string;
  name: string;
  address?: string | null;
}

export interface AdminUserRow {
  id: number | string;
  name?: string | null;
  email?: string | null;
  phone?: string | null;
  role?: string | null;
  status?: string | null;
  branch_id?: number | string | null;
  branch?: AdminBranchRef | null;
  profile_picture?: string | null;
  created_at?: string | null;
}

/* ---------------------------- Dashboard ---------------------------- */

export interface DashboardPendingOrder {
  id: number | string;
  order_number: string;
  customer_name: string;
  created_at: string | null;
  minutes_ago: number;
  duration_label: string;
}

export interface DashboardBranch {
  id: number | string;
  name: string;
  is_active?: boolean;
  cashiers_count: number;
  today_sales: number;
  pending_count: number;
  in_progress_count: number;
  pending_orders: DashboardPendingOrder[];
}

export interface AdminDashboardPayload {
  today_total_revenue: number;
  total_sales_count: number;
  pending_orders: number;
  pending_approvals: number;
  active_branches: number;
  today_financials: { revenue?: number; expenses?: number; actual_sales?: number; [k: string]: unknown };
  branches: DashboardBranch[];
}

export function fetchAdminDashboard(): Promise<AdminDashboardPayload> {
  return apiGet<AdminDashboardPayload>('/admin/dashboard');
}

/* ------------------------- Daily sales view ------------------------ */

export interface DailySalesStaff {
  id: number | string;
  name: string;
  role: string;
  sales: number;
  expenses: number;
  actual: number;
  sales_percent: number;
}

export interface DailySalesRow {
  id: number | string;
  name: string;
  daily_sales: number;
  daily_expenses: number;
  actual_sales: number;
  sales_percent: number;
  transactions: number;
  staff: DailySalesStaff[];
}

export interface DailySalesPayload {
  rows: DailySalesRow[];
  company_sales: number;
  company_expenses: number;
  company_actual: number;
}

export function fetchDailySales(): Promise<DailySalesPayload> {
  return apiGet<DailySalesPayload>('/admin/daily-sales');
}

/* ------------------------ Cross-branch views ----------------------- */

export interface CrossStockRow {
  id: number | string;
  name: string;
  address?: string | null;
  total_products: number;
  low_stock: number;
  total_bottles: number;
  total_oils: number;
}

export interface CrossSalesRow {
  id: number | string;
  name: string;
  address?: string | null;
  today_revenue: number;
  today_transactions: number;
  today_paid: number;
  pending_orders: number;
  active_cashiers: number;
}

export function fetchCrossBranchStock(): Promise<{ rows: CrossStockRow[] }> {
  return apiGet<{ rows: CrossStockRow[] }>('/admin/cross-branch/stock');
}

export function fetchCrossBranchSales(): Promise<{ rows: CrossSalesRow[] }> {
  return apiGet<{ rows: CrossSalesRow[] }>('/admin/cross-branch/sales');
}

/* ---------------------------- Branches ----------------------------- */

export interface BranchListItem extends AdminBranchRef {
  is_active?: boolean;
  latitude?: number | string | null;
  longitude?: number | string | null;
  category?: string | null;
  profile_picture?: string | null;
  cashiers_count?: number;
  users?: AdminUserRow[];
}

export interface BranchOptions {
  admins: AdminUserRow[];
  categories: string[];
}

export interface BranchFormFields {
  name: string;
  address?: string;
  admin_id?: string;
  latitude?: string;
  longitude?: string;
  category?: string;
  is_active?: boolean;
}

export function fetchBranches(): Promise<{ branches: BranchListItem[] }> {
  return apiGet<{ branches: BranchListItem[] }>('/admin/branches');
}

export function fetchBranchOptions(): Promise<BranchOptions> {
  return apiGet<BranchOptions>('/admin/branches/options');
}

export function fetchBranch(id: string): Promise<{ branch: BranchListItem; admins: AdminUserRow[] }> {
  return apiGet<{ branch: BranchListItem; admins: AdminUserRow[] }>(`/admin/branches/${id}`);
}

export function createBranch(fields: BranchFormFields, photo?: { uri: string; name: string; type: string }): Promise<{ message: string }> {
  if (photo) {
    const form = new FormData();
    for (const [k, v] of Object.entries(fields)) {
      if (v !== undefined && v !== null && v !== '') form.append(k, typeof v === 'boolean' ? (v ? '1' : '0') : String(v));
    }
    form.append('profile_picture', photo as unknown as Blob);
    return apiPost<{ message: string }>('/admin/branches', form);
  }
  return apiPost<{ message: string }>('/admin/branches', fields);
}

export function updateBranch(
  id: string,
  fields: BranchFormFields,
  photo?: { uri: string; name: string; type: string },
): Promise<{ message: string }> {
  if (photo) {
    const form = new FormData();
    for (const [k, v] of Object.entries(fields)) {
      // Laravel's boolean rule accepts '1'/'0', not 'true'/'false'.
      if (v !== undefined && v !== null && v !== '') form.append(k, typeof v === 'boolean' ? (v ? '1' : '0') : String(v));
    }
    form.append('_method', 'PUT');
    form.append('profile_picture', photo as unknown as Blob);
    return apiPost<{ message: string }>(`/admin/branches/${id}`, form);
  }
  return apiPut<{ message: string }>(`/admin/branches/${id}`, fields);
}

export function deactivateBranch(id: string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/admin/branches/${id}`);
}

export function purgeBranch(id: string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/admin/branches/${id}/purge`);
}

/* ---------------------------- Approvals ---------------------------- */

export interface ApprovalPayload {
  users: AdminUserRow[];
  total: number;
  page: number;
  per_page: number;
  role: string;
}

export function fetchApprovals(params: { role?: string; status?: string; page?: number }): Promise<ApprovalPayload> {
  return apiGet<ApprovalPayload>('/admin/approvals', {
    role: params.role,
    status: params.status,
    page: params.page,
  });
}

export function fetchApproval(id: string): Promise<{ user: AdminUserRow & { branch?: AdminBranchRef | null } }> {
  return apiGet<{ user: AdminUserRow & { branch?: AdminBranchRef | null } }>(`/admin/approvals/${id}`);
}

export function approveUser(id: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/admin/approvals/${id}/approve`, {});
}

export function rejectUser(id: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/admin/approvals/${id}/reject`, {});
}

/* ------------------------------ Orders ----------------------------- */

export interface AdminOrderRow {
  id: number | string;
  order_number?: string;
  status?: string;
  total?: number | string;
  customer_name?: string | null;
  personal_order_name?: string | null;
  created_at?: string | null;
  branch_id?: number | string | null;
  branch?: AdminBranchRef | null;
  minutes_ago?: number;
  duration_label?: string;
  [k: string]: unknown;
}

export interface AdminOrdersPayload {
  orders: AdminOrderRow[];
  counts: Record<string, number>;
  pickers: Record<string, string> | Record<string, string>[];
  branches: AdminBranchRef[];
  tab: string;
  tab_labels: Record<string, string>;
}

export function fetchAdminOrders(params: { tab?: string; branch_id?: string; q?: string }): Promise<AdminOrdersPayload> {
  return apiGet<AdminOrdersPayload>('/admin/orders', {
    tab: params.tab,
    branch_id: params.branch_id,
    q: params.q,
  });
}

export function fetchAdminOrder(id: string): Promise<{ order: AdminOrderRow; pickers: unknown }> {
  return apiGet<{ order: AdminOrderRow; pickers: unknown }>(`/admin/orders/${id}`);
}

export function saveOrderPersonalName(id: string, personalOrderName: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/admin/orders/${id}/personal-name`, {
    personal_order_name: personalOrderName,
  });
}

/* ------------------------------ Staff ------------------------------ */

export interface StaffListPayload {
  users: AdminUserRow[];
}

export interface StaffOptions {
  branches: AdminBranchRef[];
  roles: string[];
}

export interface StaffDetailPayload {
  user: AdminUserRow & { branch?: AdminBranchRef | null };
  sales: Record<string, unknown>[];
  expenses: Record<string, unknown>[];
  audit_logs: Record<string, unknown>[];
  total_sales: number;
  total_expenses: number;
}

export interface StaffFormFields {
  name: string;
  email: string;
  phone?: string;
  password: string;
  password_confirmation: string;
  role: string;
  branch_id: string;
}

export function fetchStaff(params: { role?: string; status?: string; search?: string }): Promise<StaffListPayload> {
  return apiGet<StaffListPayload>('/admin/staff', {
    role: params.role,
    status: params.status,
    search: params.search,
  });
}

export function fetchStaffOptions(): Promise<StaffOptions> {
  return apiGet<StaffOptions>('/admin/staff/options');
}

export function createStaff(fields: StaffFormFields): Promise<{ message: string }> {
  return apiPost<{ message: string }>('/admin/staff', fields);
}

export function fetchStaffDetail(id: string): Promise<StaffDetailPayload> {
  return apiGet<StaffDetailPayload>(`/admin/staff/${id}`);
}

export function toggleStaffStatus(id: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/admin/staff/${id}/toggle-status`, {});
}

export function changeStaffStatus(id: string, status: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/admin/staff/${id}/status`, { status });
}

export function deleteStaff(id: string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/admin/staff/${id}`);
}

/* ------------------------------ Emails ----------------------------- */

export interface MailRow {
  id: number | string;
  from_email?: string | null;
  from_name?: string | null;
  subject?: string | null;
  body_text?: string | null;
  received_at?: string | null;
  is_read?: boolean;
  is_starred?: boolean;
  status?: string | null;
  has_attachments?: boolean;
  attachment_names?: string[] | string | null;
  replied_at?: string | null;
}

export interface MailListPayload {
  mails: MailRow[];
  box: string;
  page: number;
  shown: number;
  unread_count: number;
}

export interface MailDetailPayload {
  mail: MailRow;
  thread: MailRow[];
  replies: Record<string, unknown>[];
  attachments: Record<string, unknown>[];
  info_address?: string;
  signature?: string;
  reply_limits: { files: number; file_mb: number; total_mb: number };
}

export function fetchMails(params: { box?: string; search?: string; page?: number }): Promise<MailListPayload> {
  return apiGet<MailListPayload>('/admin/emails', {
    box: params.box,
    search: params.search,
    page: params.page,
  });
}

export function fetchMail(id: string): Promise<MailDetailPayload> {
  return apiGet<MailDetailPayload>(`/admin/emails/${id}`);
}

export function replyToMail(id: string, body: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/admin/emails/${id}/reply`, { body });
}

export function toggleMailRead(id: string): Promise<{ message: string; is_read: boolean }> {
  return apiPost<{ message: string; is_read: boolean }>(`/admin/emails/${id}/read`, {});
}

export function toggleMailStar(id: string): Promise<{ message: string; is_starred: boolean }> {
  return apiPost<{ message: string; is_starred: boolean }>(`/admin/emails/${id}/star`, {});
}

export function setMailStatus(id: string, status: 'new' | 'replied' | 'closed'): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/admin/emails/${id}/status`, { status });
}

export function deleteMail(id: string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/admin/emails/${id}`);
}

/* -------------------------- Notifications -------------------------- */

export interface AdminNotificationRow {
  id: number | string;
  title?: string | null;
  message?: string | null;
  type?: string | null;
  is_read?: boolean;
  created_at?: string | null;
  branch_name?: string | null;
  user_name?: string | null;
}

export function fetchNotifications(params: { type?: string; date_from?: string; date_to?: string }): Promise<{
  notifications: AdminNotificationRow[];
  unread_count: number;
}> {
  return apiGet<{ notifications: AdminNotificationRow[]; unread_count: number }>('/admin/notifications', {
    type: params.type,
    date_from: params.date_from,
    date_to: params.date_to,
  });
}

export function markNotificationRead(id: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/admin/notifications/${id}/mark-read`, {});
}

/**
 * The website's Notifications → Generate Report page: the same rows with
 * the type + date filters, up to 500 entries for a printable listing.
 */
export function fetchNotificationsReport(params: {
  type?: string;
  date_from?: string;
  date_to?: string;
}): Promise<{ notifications: AdminNotificationRow[] }> {
  return apiGet<{ notifications: AdminNotificationRow[] }>('/admin/notifications/report', {
    type: params.type,
    date_from: params.date_from,
    date_to: params.date_to,
  });
}

export function markAllNotificationsRead(): Promise<{ message: string }> {
  return apiPost<{ message: string }>('/admin/notifications/mark-all-read', {});
}

/* ----------------------------- Reports ----------------------------- */

export interface SalesReportGroup {
  branch_name: string;
  total_sales: number;
  transactions: number;
  items_sold: number;
  sales: Record<string, unknown>[];
}

export interface SalesReportPayload {
  date: string;
  branch_groups: SalesReportGroup[];
  total_sales: number;
  total_transactions: number;
  total_items: number;
  branches: AdminBranchRef[];
}

export interface ExpensesReportPayload {
  expenses_by_category: { category: string; total: number; count: number }[];
  total: number;
  start_date: string;
  end_date: string;
  branches: AdminBranchRef[];
}

export interface StockReportRow {
  product: string;
  brand?: string | null;
  branch: string;
  quantity: number;
  selling_price: number;
  stock_value: number;
}

export interface StockReportPayload {
  report: StockReportRow[];
  total_value: number;
  total_units: number;
  branches: AdminBranchRef[];
}

export interface StaffPerfRow {
  user_id: number | string;
  user_name: string;
  role: string;
  branch_name: string;
  total_sales: number;
  transaction_count: number;
  items_sold: number;
}

export interface StaffPerfPayload {
  report: StaffPerfRow[];
  start_date: string;
  end_date: string;
  branches: AdminBranchRef[];
}

export interface ProductPerfRow {
  id: number | string;
  name: string;
  total_sold: number;
  total_revenue: number;
  remaining: number;
  available: number;
  sell_through: number;
  contribution: number;
}

export interface ProductPerfPayload {
  report: ProductPerfRow[];
  total_revenue: number;
  start_date: string;
  end_date: string;
  branches: AdminBranchRef[];
}

export interface ReportFilters {
  date?: string;
  date_from?: string;
  date_to?: string;
  branch_id?: string;
  [key: string]: string | undefined;
}

export function fetchSalesReport(filters: ReportFilters): Promise<SalesReportPayload> {
  return apiGet<SalesReportPayload>('/admin/reports/sales', filters);
}

export function fetchExpensesReport(filters: ReportFilters): Promise<ExpensesReportPayload> {
  return apiGet<ExpensesReportPayload>('/admin/reports/expenses', filters);
}

export function fetchStockReport(filters: ReportFilters): Promise<StockReportPayload> {
  return apiGet<StockReportPayload>('/admin/reports/stock', filters);
}

export function fetchStaffPerformanceReport(filters: ReportFilters): Promise<StaffPerfPayload> {
  return apiGet<StaffPerfPayload>('/admin/reports/staff-performance', filters);
}

export function fetchProductPerformanceReport(filters: ReportFilters): Promise<ProductPerfPayload> {
  return apiGet<ProductPerfPayload>('/admin/reports/product-performance', filters);
}

/* -------------------------- Returned stock ------------------------- */

export interface ReturnedStockItem {
  id?: number | string;
  stock_type?: string;
  name?: string | null;
  volume?: number | string | null;
  variant?: string | null;
  quantity?: number;
  return_reason?: string | null;
  return_status?: string | null;
  damage_type?: string | null;
  damage_reason?: string | null;
  returned_at?: string | null;
}

export interface ReturnedStockRow {
  item: ReturnedStockItem;
  item_label: string;
  transfer_number?: string | null;
  stock_type_label: string;
  from_branch_name: string;
  to_branch_name: string;
  officer_name?: string | null;
  officer_phone?: string | null;
  return_reason?: string | null;
  return_status?: string | null;
  damage_type?: string | null;
  damage_reason?: string | null;
  damage_reported_by_name?: string | null;
  damage_reported_at?: string | null;
  returned_at?: string | null;
}

export interface ReturnedStockPayload {
  rows: ReturnedStockRow[];
  lost_count: number;
  broken_count: number;
  branches: Record<string, string>;
  filters: { damage_type: string; branch_id: number; date_from: string; date_to: string };
  has_damage_columns: boolean;
}

export function fetchReturnedStock(params: {
  damage_type?: string;
  branch_id?: string;
  date_from?: string;
  date_to?: string;
}): Promise<ReturnedStockPayload> {
  return apiGet<ReturnedStockPayload>('/admin/returned-stock', {
    damage_type: params.damage_type,
    branch_id: params.branch_id,
    date_from: params.date_from,
    date_to: params.date_to,
  });
}

/* --------------------- Settings + profile/account ------------------ */

export interface AdminSettingsPayload {
  super_admin_secret: string;
  staff_secret_code: string;
}

export function fetchAdminSettings(): Promise<AdminSettingsPayload> {
  return apiGet<AdminSettingsPayload>('/admin/settings');
}

export function saveAdminSettings(fields: { super_admin_secret: string; staff_secret_code: string }): Promise<{ message: string }> {
  return apiPost<{ message: string }>('/admin/settings', fields);
}

export function fetchAdminProfile(): Promise<{ user: AdminUserRow; branch: AdminBranchRef | null }> {
  return apiGet<{ user: AdminUserRow; branch: AdminBranchRef | null }>('/admin/profile');
}

export function saveAdminProfile(fields: {
  name: string;
  email: string;
  phone?: string;
}, photo?: { uri: string; name: string; type: string }): Promise<{ message: string; user: AdminUserRow }> {
  if (photo) {
    const form = new FormData();
    for (const [k, v] of Object.entries(fields)) {
      if (v !== undefined && v !== null && v !== '') form.append(k, String(v));
    }
    form.append('_method', 'PUT');
    form.append('profile_picture', photo as unknown as Blob);
    return apiPost<{ message: string; user: AdminUserRow }>('/admin/profile', form);
  }
  return apiPut<{ message: string; user: AdminUserRow }>('/admin/profile', fields);
}

export function changeAdminPassword(fields: {
  current_password: string;
  password: string;
  password_confirmation: string;
}): Promise<{ message: string }> {
  return apiPut<{ message: string }>('/admin/profile/password', fields);
}
