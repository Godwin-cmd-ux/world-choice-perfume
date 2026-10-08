/**
 * Customer Care module — the single client for every /api/care/* endpoint.
 *
 * Each function mirrors one website Customer Care page/operation (see
 * routes/api.php → care group and routes/web.php → customer-care group).
 * Nothing here implements business logic: validation, authorization and the
 * Head Quarters gate all happen in Laravel; this file only names the
 * endpoints and types their responses.
 *
 * One server rule shows up everywhere: `scope.is_hq` — true only for the
 * Head Quarters-Mikocheni member. The app hides inquiries/news/mails for
 * everyone else, and those endpoints answer 403 if called anyway
 * ("Only customer care from Head Quarters-Mikocheni can access this page.").
 */
import { apiDelete, apiGet, apiPost, apiPut } from './api';

/* ------------------------------------------------------------------ *
 * Shared shapes
 * ------------------------------------------------------------------ */

/** Branch/HQ context the app shapes navigation from. */
export interface CcScope {
  role: 'customer_care';
  own_branch_id: number;
  branch_id: number;
  branch_name: string | null;
  is_hq: boolean;
}

export interface CcBranchRef {
  id: number;
  name: string;
  address?: string | null;
}

export function fetchCcScope(): Promise<CcScope> {
  return apiGet<{ scope: CcScope }>('/care/scope').then((r) => r.scope);
}

/* ------------------------------ Dashboard -------------------------- */

export interface CcInquiryRow {
  id: number | string;
  subject?: string | null;
  message?: string | null;
  reply_message?: string | null;
  status?: string;
  is_read?: boolean;
  is_featured?: boolean;
  created_at?: string | null;
  user?: { id: number; name: string } | null;
  name?: string | null;
}

export interface CcMailRow {
  id: number | string;
  subject?: string | null;
  from_email?: string | null;
  from_name?: string | null;
  to_email?: string | null;
  body_text?: string | null;
  is_read?: boolean;
  is_starred?: boolean;
  status?: string | null;
  has_attachments?: boolean;
  attachment_names?: string | null;
  received_at?: string | null;
  created_at?: string | null;
}

export interface CcSale {
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

export interface CcDashboardPayload {
  isHq: boolean;
  inquiriesCount: number;
  unreadInquiries: number;
  recentInquiries: CcInquiryRow[];
  mailStats: Record<string, number> | null;
  unreadMails: number;
  recentMails: CcMailRow[];
  sales: CcSale[];
  totalRevenue: number;
  totalSalesCount: number;
  todayRevenue: number;
  orders: Record<string, unknown>[];
  ordersTotal: number;
  orderStatusCounts: Record<string, number>;
  pendingOrders: number;
  clients: Record<string, unknown>[];
  clientsTotal: number;
  clientsWithPhone: number;
  dailySummary: Record<string, number | string | null>;
  scope: CcScope;
}

export function fetchCcDashboard(): Promise<CcDashboardPayload> {
  return apiGet<CcDashboardPayload>('/care/dashboard');
}

/* ------------------------------ Clients ---------------------------- */

export interface CcCustomerRow {
  id: number | string;
  name?: string | null;
  phone?: string | null;
  whatsapp?: string | null;
  email?: string | null;
  created_at?: string | null;
}

export interface CcCustomerList {
  customers: CcCustomerRow[];
  q: string;
  tab: number;
  tabName: string;
  tabs: { id: number; name: string; count: number }[];
  counts: Record<string, number>;
  totalCustomers: number;
  branchNames: Record<string, string>;
  truncated: boolean;
  visits: Record<string, { last_visit: string | null; branches: number[] | Record<number, boolean> }>;
  scope: CcScope;
}

export function fetchCustomers(params: { q?: string; branch?: number } = {}): Promise<CcCustomerList> {
  return apiGet('/care/customers', {
    ...(params.q ? { q: params.q } : {}),
    ...(params.branch ? { branch: params.branch } : {}),
  });
}

export interface CcCustomerFields {
  name: string;
  phone?: string;
  whatsapp?: string;
  email?: string;
}

/** Duplicate phone answers { duplicate: true, customer } — the website redirects to that record. */
export function createCustomer(fields: CcCustomerFields): Promise<{
  message: string;
  duplicate: boolean;
  customer: CcCustomerRow;
}> {
  return apiPost('/care/customers', fields);
}

export interface CcCustomerDetail {
  customer: CcCustomerRow;
  whatsappLink: string | null;
  emailLink: string | null;
  branchTabs: { id: number; name: string; address?: string | null; count: number; last_visit: string | null }[];
  selected: string;
  selectedName: string;
  transactions: {
    id: number | string;
    sale_number?: string | null;
    branch_id: number;
    branch_name?: string | null;
    total: number;
    payment_status?: string | null;
    payment_summary?: string | null;
    sale_type?: string | null;
    created_at?: string | null;
    items: { name: string; brand?: string | null; quantity: number; unit_price: number; total: number }[];
  }[];
  itemSummary: { name: string; brand?: string | null; times: number; quantity: number; spent: number; last_bought?: string | null }[];
  totalSpent: number;
  scope: CcScope;
}

export function fetchCustomer(id: number | string, branch?: string): Promise<CcCustomerDetail> {
  return apiGet(`/care/customers/${id}`, branch ? { branch } : {});
}

/* ------------------------------- Sales ----------------------------- */

export function fetchSales(
  params: { date_from?: string; date_to?: string; status?: string } = {},
): Promise<{ sales: CcSale[]; totalRevenue: number; scope: CcScope }> {
  return apiGet('/care/sales', params);
}

export interface CcSaleOptionProduct {
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

export interface CcVarietyBucket {
  volume: number;
  label: string;
  variants: { key: string; label: string; available: number; price: number }[];
}

/** Per-product bottling buckets: productId → volumes that hold stock. */
export type CcVarietyBuckets = Record<string, CcVarietyBucket[]>;

export function fetchSaleOptions(): Promise<{
  products: CcSaleOptionProduct[];
  bottleStock: Record<string, number>;
  bottleVariants: Record<string, Record<string, number>>;
  productVarieties: CcVarietyBuckets;
  is_products_only: boolean;
  scope: CcScope;
}> {
  return apiGet('/care/sales/options');
}

export interface CcSaleItemInput {
  product_id: number | string;
  quantity: number;
  custom_price?: number | null;
  volume?: number | null;
  variant?: string | null;
}

export interface CcEmptyBottleLine {
  volume: number;
  quantity: number;
  price: number;
  variant?: string;
}

export interface CcSaleFields {
  customer_id?: number | string | null;
  customer_name?: string;
  customer_phone?: string;
  payment_mode: 'single' | 'multi';
  payments: { method: 'cash' | 'bank_transfer' | 'mobile_payment'; amount?: number }[];
  items: CcSaleItemInput[];
  empty_bottles?: CcEmptyBottleLine[];
  sale_type: 'retail' | 'wholesale';
}

export function createSale(fields: CcSaleFields): Promise<{ message: string; sale: CcSale }> {
  return apiPost('/care/sales', fields);
}

export function fetchSale(id: number | string): Promise<{ sale: CcSale }> {
  return apiGet<{ sale: CcSale }>(`/care/sales/${id}`);
}

/* ------------------------------- Orders ---------------------------- */

export interface CcOrder {
  id: number | string;
  order_number?: string | null;
  status?: string | null;
  customer_name?: string | null;
  personal_order_name?: string | null;
  items?: unknown;
  total?: number | null;
  created_at?: string | null;
  /** The current picker column (legacy orders only ever wrote cashier_id). */
  assigned_to?: number | string | null;
  cashier_id?: number | string | null;
  picked_by?: number | string | null;
  delivery_notes?: string | null;
  customer?: { name?: string | null; phone?: string | null } | null;
  cashier?: { id?: number | string; name?: string | null } | null;
  branch?: { id?: number | string; name?: string | null } | null;
  notes?: { id?: number | string; note?: string | null; created_at?: string | null }[] | null;
  [key: string]: unknown;
}

export interface CcOrderPayload {
  orders: CcOrder[];
  counts: Record<string, number>;
  pickers: Record<string, string>;
  tab: string;
  transitions: Record<string, unknown>;
  userId: number;
  scope: CcScope;
}

export function fetchOrders(params: { tab?: string; q?: string } = {}): Promise<CcOrderPayload> {
  return apiGet<CcOrderPayload>('/care/orders', {
    ...(params.tab ? { tab: params.tab } : {}),
    ...(params.q ? { q: params.q } : {}),
  });
}

export function fetchOrder(id: number | string): Promise<{
  order: CcOrder;
  transitions: Record<string, unknown>;
  userId: number;
  canName: boolean;
}> {
  return apiGet(`/care/orders/${id}`);
}

export function updateOrderStatus(id: number | string, status: 'picked' | 'served', note: string): Promise<{ message: string }> {
  // The server's statusChangeRules() requires a note on every status change
  // ("what point you have reached") — the screens collect it before calling,
  // exactly like the website's requireNote()/attachOrderNote() prompts.
  return apiPost<{ message: string }>(`/care/orders/${id}/status`, { status, note });
}

export function setOrderPersonalName(id: number | string, personal_order_name: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/care/orders/${id}/personal-name`, { personal_order_name });
}

/* ----------------------------- Inquiries (HQ) ---------------------- */

export function fetchInquiries(params: { status?: 'read' | 'unread' } = {}): Promise<{
  inquiries: CcInquiryRow[];
  counts: { all: number; unread: number; replied: number };
  scope: CcScope;
}> {
  return apiGet('/care/inquiries', params.status ? { status: params.status } : {});
}

export function fetchInquiry(id: number | string): Promise<{ inquiry: CcInquiryRow }> {
  return apiGet<{ inquiry: CcInquiryRow }>(`/care/inquiries/${id}`);
}

export function replyToInquiry(id: number | string, reply_message: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/care/inquiries/${id}/reply`, { reply_message });
}

export function markInquiryRead(id: number | string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/care/inquiries/${id}/read`, {});
}

export function markInquiryFeatured(id: number | string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/care/inquiries/${id}/comment`, {});
}

export function deleteInquiry(id: number | string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/care/inquiries/${id}`);
}

/* ----------------------------- News (HQ) --------------------------- */

export interface CcNewsPost {
  id: number | string;
  title?: string | null;
  content?: string | null;
  image_url?: string | null;
  status?: 'approved' | 'pending' | 'rejected';
  rejection_reason?: string | null;
  is_published?: boolean;
  created_at?: string | null;
  branch?: { id: number; name: string } | null;
  author?: { id: number; name: string; role?: string | null } | null;
}

export function fetchNews(params: { tab?: 'designer' | 'custom' } = {}): Promise<{
  designerPosts: CcNewsPost[];
  customPosts: CcNewsPost[];
  tab: string;
  counts: { designer: number; custom: number; pending: number; rejected: number; approved: number };
  branches: CcBranchRef[];
  scope: CcScope;
}> {
  return apiGet('/care/news', params.tab ? { tab: params.tab } : {});
}

export interface CcNewsFields {
  title: string;
  content: string;
  branch_id: number | string;
}

export function createNews(
  fields: CcNewsFields,
  image?: { uri: string; name: string; type: string } | null,
): Promise<{ message: string; post: CcNewsPost }> {
  if (image) {
    const form = new FormData();
    Object.entries(fields).forEach(([k, v]) => form.append(k, String(v)));
    form.append('image', image as unknown as Blob);
    return apiPost('/care/news', form);
  }
  return apiPost('/care/news', fields);
}

export function fetchNewsForm(id: number | string): Promise<{ post: CcNewsPost; branches: CcBranchRef[] }> {
  return apiGet(`/care/news/${id}/edit`);
}

export function updateNews(
  id: number | string,
  fields: CcNewsFields,
  image?: { uri: string; name: string; type: string } | null,
): Promise<{ message: string }> {
  if (image) {
    const form = new FormData();
    Object.entries(fields).forEach(([k, v]) => form.append(k, String(v)));
    form.append('image', image as unknown as Blob);
    return apiPut<{ message: string }>(`/care/news/${id}`, form);
  }
  return apiPut<{ message: string }>(`/care/news/${id}`, fields);
}

export function approveNews(id: number | string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/care/news/${id}/approve`, {});
}

export function rejectNews(id: number | string, rejection_reason: string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/care/news/${id}/reject`, { rejection_reason });
}

export function deleteNews(id: number | string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/care/news/${id}`);
}

/* ----------------------------- Mails (HQ) -------------------------- */

export function fetchMails(params: { box?: string; search?: string; page?: number } = {}): Promise<{
  mails: CcMailRow[];
  box: string;
  page: number;
  shown: number;
  unread_count: number;
  scope: CcScope;
}> {
  return apiGet('/care/mails', {
    ...(params.box && params.box !== 'all' ? { box: params.box } : {}),
    ...(params.search ? { search: params.search } : {}),
    ...(params.page && params.page > 1 ? { page: params.page } : {}),
  });
}

export interface CcMailDetail {
  mail: CcMailRow & { body_html?: string | null };
  thread: CcMailRow[];
  replies: Record<string, unknown>[];
  attachments: { id: number | string; file_name?: string | null; mime_type?: string | null; size?: number | null }[];
  info_address: string | null;
  signature: string;
  reply_limits: { files: number; file_mb: number; total_mb: number };
}

export function fetchMail(id: number | string): Promise<CcMailDetail> {
  return apiGet<CcMailDetail>(`/care/mails/${id}`);
}

/** Text reply plus optional attachments (multipart; limits enforced server-side). */
export function replyToMail(
  id: number | string,
  body: string,
  attachments: { uri: string; name: string; type: string }[] = [],
): Promise<{ message: string }> {
  if (attachments.length > 0) {
    const form = new FormData();
    form.append('body', body);
    attachments.forEach((file) => form.append('attachments[]', file as unknown as Blob));
    return apiPost(`/care/mails/${id}/reply`, form);
  }
  return apiPost<{ message: string }>(`/care/mails/${id}/reply`, { body });
}

export function toggleMailRead(id: number | string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/care/mails/${id}/read`, {});
}

export function toggleMailStar(id: number | string): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/care/mails/${id}/star`, {});
}

export function setMailStatus(id: number | string, status: 'new' | 'replied' | 'closed'): Promise<{ message: string }> {
  return apiPost<{ message: string }>(`/care/mails/${id}/status`, { status });
}

export function deleteMail(id: number | string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/care/mails/${id}`);
}

/* --------------------- Profile / password (shared) ----------------- */

export interface CcProfilePayload {
  user: {
    id?: number | string;
    name?: string | null;
    email?: string | null;
    phone?: string | null;
    role?: string | null;
    status?: string | null;
    branch_id?: number | string | null;
    profile_picture?: string | null;
  };
  branch?: { id?: number | string; name?: string | null } | null;
  [key: string]: unknown;
}

export function fetchCcProfile(): Promise<CcProfilePayload> {
  return apiGet<CcProfilePayload>('/care/profile');
}

/**
 * Profile save — the same multipart PUT the website's profile form posts
 * (POST + _method=PUT so PHP still populates $_FILES for the picture).
 */
export function updateCcProfile(
  fields: Record<string, string>,
  photo?: { uri: string; name: string; type: string },
): Promise<{ message?: string }> {
  if (photo) {
    const form = new FormData();
    for (const [k, v] of Object.entries(fields)) {
      if (v !== undefined && v !== null && v !== '') form.append(k, String(v));
    }
    form.append('_method', 'PUT');
    form.append('profile_picture', photo as unknown as Blob);
    return apiPost<{ message?: string }>('/care/profile', form);
  }
  return apiPut<{ message?: string }>('/care/profile', fields);
}

export function changeCcPassword(fields: { current_password: string; password: string; password_confirmation: string }): Promise<{ message?: string }> {
  return apiPut<{ message?: string }>('/care/profile/password', fields);
}
