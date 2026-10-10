/**
 * The app's single HTTP client.
 *
 * Every request goes to BASE_URL (lib/config.ts) with `Accept:
 * application/json`, which is what makes the Laravel controllers return their
 * JSON branch instead of an HTML view. Errors are normalised into ApiError so
 * screens can tell a dead connection apart from a server fault apart from a
 * validation problem — the staff secret-code flow depends on that distinction
 * (a network failure must never be shown as "invalid code").
 */
import { API_BASE, REQUEST_TIMEOUT_MS } from './config';
import { staffSession } from './staffSession';

export type ApiErrorKind =
  | 'network' // request never reached the server
  | 'timeout' // server did not answer in time
  | 'validation' // 422 — field-level errors (Laravel validate())
  | 'notFound' // 404
  | 'auth' // 401 / 403 — rejected or not allowed
  | 'server'; // 5xx and anything unexpected

export class ApiError extends Error {
  readonly kind: ApiErrorKind;
  readonly status: number;
  readonly fields: Record<string, string>;

  constructor(kind: ApiErrorKind, message: string, status = 0, fields: Record<string, string> = {}) {
    super(message);
    this.name = 'ApiError';
    this.kind = kind;
    this.status = status;
    this.fields = fields;
  }
}

const NETWORK_MESSAGE = "Can't reach World Choice Perfumes. Check your internet connection and try again.";
const TIMEOUT_MESSAGE = 'The request timed out. Please check your connection and try again.';
const SERVER_MESSAGE = 'The server had a problem. Please try again in a moment.';

export function isApiError(e: unknown): e is ApiError {
  return e instanceof ApiError;
}

/** Message to show for any thrown error, without leaking internals. */
export function errorMessage(e: unknown): string {
  if (isApiError(e)) return e.message;
  return SERVER_MESSAGE;
}

type QueryValue = string | number | null | undefined;
type QueryParams = Record<string, QueryValue>;

interface RequestOptions {
  query?: QueryParams;
  body?: unknown;
  timeoutMs?: number;
}

function buildUrl(path: string, query?: QueryParams): string {
  const url = `${API_BASE}${path}`;
  if (!query) return url;
  const parts: string[] = [];
  for (const [key, value] of Object.entries(query)) {
    if (value === null || value === undefined || value === '') continue;
    parts.push(`${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`);
  }
  return parts.length ? `${url}?${parts.join('&')}` : url;
}

function asRecord(value: unknown): Record<string, unknown> | null {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
    ? (value as Record<string, unknown>)
    : null;
}

/** Laravel 422 body: { message, errors: { field: [msg, ...] } } → { field: msg } */
function fieldErrors(data: unknown): Record<string, string> {
  const out: Record<string, string> = {};
  const errors = asRecord(asRecord(data)?.errors);
  if (!errors) return out;
  for (const [field, value] of Object.entries(errors)) {
    if (Array.isArray(value) && typeof value[0] === 'string') out[field] = value[0];
    else if (typeof value === 'string') out[field] = value;
  }
  return out;
}

function messageOf(data: unknown): string {
  const message = asRecord(data)?.message;
  return typeof message === 'string' ? message.trim() : '';
}

function isAbort(e: unknown): boolean {
  return typeof e === 'object' && e !== null && (e as { name?: string }).name === 'AbortError';
}

async function request<T>(method: 'GET' | 'POST' | 'PUT' | 'DELETE', path: string, options: RequestOptions = {}): Promise<T> {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), options.timeoutMs ?? REQUEST_TIMEOUT_MS);

  let response: Response;
  try {
    // The encrypted staff-access grant (if any) rides along automatically so
    // staff login/registration routes — gated by EnsureStaffAccessApi — are
    // authorised the same way the website's session flag authorises them.
    const headers: Record<string, string> = {
      Accept: 'application/json',
    };
    const token = staffSession.getAccessToken();
    if (token) headers['X-Staff-Access'] = token;
    // Signed-in staff session (issued at /api/staff/login) — required by the
    // /api/admin/* routes, which re-verify role server-side on every call.
    const session = staffSession.getSessionToken();
    if (session) headers['X-Staff-Session'] = session;

    // Multipart (cashier profile photo) is passed through untouched — the
    // runtime sets its own boundary.
    const isFormData =
      typeof FormData !== 'undefined' && options.body instanceof FormData;
    if (options.body !== undefined && !isFormData) {
      headers['Content-Type'] = 'application/json';
    }

    response = await fetch(buildUrl(path, options.query), {
      method,
      headers,
      body:
        options.body === undefined
          ? undefined
          : isFormData
            ? (options.body as FormData)
            : JSON.stringify(options.body),
      signal: controller.signal,
    });
  } catch (e) {
    if (isAbort(e)) throw new ApiError('timeout', TIMEOUT_MESSAGE);
    throw new ApiError('network', NETWORK_MESSAGE);
  } finally {
    clearTimeout(timer);
  }

  const text = await response.text().catch(() => '');
  let data: unknown = null;
  if (text) {
    try {
      data = JSON.parse(text) as unknown;
    } catch {
      data = null;
    }
  }

  if (response.ok) return data as T;

  const serverMessage = messageOf(data);

  if (response.status === 422) {
    throw new ApiError('validation', serverMessage || 'Please check your input and try again.', 422, fieldErrors(data));
  }
  if (response.status === 404) {
    throw new ApiError('notFound', serverMessage || 'Sorry, we could not find what you were looking for.', 404);
  }
  if (response.status === 401 || response.status === 403) {
    throw new ApiError('auth', serverMessage || 'You are not allowed to do that.', response.status);
  }
  throw new ApiError('server', serverMessage || SERVER_MESSAGE, response.status);
}

export function apiGet<T>(path: string, query?: QueryParams): Promise<T> {
  return request<T>('GET', path, { query });
}

export function apiPost<T>(path: string, body: unknown): Promise<T> {
  return request<T>('POST', path, { body });
}

/** PUT — same body handling as POST (JSON or FormData passthrough). */
export function apiPut<T>(path: string, body: unknown): Promise<T> {
  return request<T>('PUT', path, { body });
}

/** DELETE — path-only by default; an optional JSON body is supported. */
export function apiDelete<T>(path: string, body?: unknown): Promise<T> {
  return request<T>('DELETE', path, { body });
}

/* ------------------------------------------------------------------ *
 * Payload types — mirrors of what the Laravel controllers return.
 * ------------------------------------------------------------------ */

export interface Contact {
  phone: string | null;
  dial: string | null;
  whatsapp: string | null;
  whatsapp_link: string | null;
  hours: string | null;
  email: string | null;
}

export interface Branch {
  id: number | string;
  name: string;
  address: string | null;
  latitude: number | string | null;
  longitude: number | string | null;
  is_active?: boolean;
  profile_picture?: string | null;
}

export interface Brand {
  id: number | string;
  name: string;
  logo_url?: string | null;
}

export interface Review {
  email?: string | null;
  subject?: string | null;
  message?: string | null;
  created_at?: string | null;
}

export interface HomePayload {
  branches: Branch[];
  reviews: Review[];
  featured_brands: Brand[];
  category_images: Record<string, string[]>;
  contact: Contact;
}

export interface ProductImage {
  id?: number | string;
  image_url: string;
}

export interface Product {
  id?: number | string | null;
  name?: string;
  description?: string | null;
  brand?: string | null;
  category?: string | null;
  sex_category?: string | null;
  fundamental_ingredient?: string | null;
  images?: ProductImage[];
}

export interface StockItem {
  id?: number | string | null;
  branch_id?: number | string | null;
  product_id?: number | string;
  quantity?: number;
  selling_price?: number | string | null;
  product: Product;
}

export interface ProductsPayload {
  branches: Branch[];
  products: StockItem[];
  brands: string[];
  branch_counts: Record<string, number>;
  selected_branch: Branch | null;
}

export interface BranchStock {
  id?: number | string;
  branch_id?: number | string;
  quantity?: number;
  selling_price?: number | string | null;
  branch?: { id?: number | string; name?: string } | null;
}

/**
 * One size a product is bottled in at a branch — GET /api/products/{id}.
 *
 * The box, logo and colour behind a bottling decide what the branch packs,
 * never what the customer pays (every 50ml of a product costs the same), so
 * the customer endpoint collapses those buckets into one entry per volume.
 * `available` is therefore the whole volume's stock. Staff sale screens read
 * their own payload, which still breaks each size down by packaging.
 */
export interface ProductSize {
  volume: number;
  label: string;
  available: number;
  price: number;
}

export interface ProductDetailPayload {
  product: Product;
  branches: Branch[];
  branch_stocks: BranchStock[];
  selected_branch: Branch | null;
  price: number | string | null;
  /**
   * `varieties[branchId]` = this product's sizes at that branch (one entry per
   * volume). The detail endpoint loads a single product, so the branch level is
   * already the list of sizes — it is NOT nested again by product id.
   */
  varieties: Record<string, ProductSize[]>;
  in_stock_branch_count: number;
}

/** One line of POST /api/orders — a product and, for bottled sizes, the size
 * the customer chose. The packaging is resolved by the server. */
export interface PlaceOrderItem {
  product_id: number | string;
  quantity: number;
  volume?: number;
}

export interface PlaceOrderInput {
  branch_id: number | string;
  customer_name: string;
  customer_phone: string;
  customer_email?: string;
  delivery_notes?: string;
  items: PlaceOrderItem[];
}

/** Confirmation returned by POST /api/orders. */
export interface PlacedOrder {
  id?: number | string | null;
  order_number: string;
  total: number | string;
  status?: string;
}

export interface TrackItem {
  quantity?: number;
  unit_price?: number | string;
  total?: number | string;
  variety_label?: string;
  product?: { name?: string } | null;
}

export interface TrackStep {
  at: string | null;
  label: string;
}

export interface TrackOrder {
  order_number: string;
  status?: string;
  total?: number | string;
  created_at?: string | null;
  delivery_notes?: string | null;
  branch?: { name?: string } | null;
  items?: TrackItem[];
  timeline?: TrackStep[];
}

export interface TrackPayload {
  orders: TrackOrder[];
  message?: string;
}

export interface VerifyAccessPayload {
  verified: boolean;
  /** Encrypted grant required by staff login/registration API routes. */
  access_token?: string;
  message?: string;
}

/** Response of POST /api/register/{type} — registration awaits an OTP. */
export interface OtpChallenge {
  otp_required: boolean;
  email: string;
  type: string;
  user_id: number | string;
  message: string;
}

/** Response of POST /api/verify-otp after registration. */
export interface OtpResult {
  verified: boolean;
  pending: boolean;
  message: string;
  email?: string;
}

export interface StaffIdentityPayload {
  user: {
    id?: number | string | null;
    name?: string | null;
    email?: string | null;
    role?: string | null;
    branch?: { id?: number | string | null; name?: string | null } | null;
  };
  /**
   * Encrypted staff-session token issued alongside the identity — the app
   * stores it and sends it as X-Staff-Session on every /api/admin/* call,
   * where the server re-checks role + account status.
   */
  'X-Staff-Session'?: string;
}

/* ------------------------------------------------------------------ *
 * Endpoints — one function per backend route (see routes/api.php).
 * ------------------------------------------------------------------ */

/** GET /api/home — branches, brands, category images, reviews, contact. */
export function fetchHome(): Promise<HomePayload> {
  return apiGet<HomePayload>('/home');
}

/** GET /api/products — same filters as the website shop. */
export function fetchProducts(params: {
  search?: string;
  sex_category?: string;
  branch_id?: string;
  brand?: string;
} = {}): Promise<ProductsPayload> {
  return apiGet<ProductsPayload>('/products', params);
}

/** GET /api/products/{id} — detail with per-branch stock and sizes. */
export function fetchProduct(id: string, branchId?: string): Promise<ProductDetailPayload> {
  return apiGet<ProductDetailPayload>(`/products/${encodeURIComponent(id)}`, { branch_id: branchId });
}

/**
 * POST /api/orders — place an order for a branch. The very same
 * Customer\OrderController@store the website checkout posts to, so the
 * order number, stock checks and variety pricing all come from the server.
 */
export function placeOrder(input: PlaceOrderInput): Promise<{ order: PlacedOrder; message: string }> {
  return apiPost<{ order: PlacedOrder; message: string }>('/orders', input);
}

/** POST /api/orders/track — orders for a phone number. */
export function trackOrders(phone: string): Promise<TrackPayload> {
  return apiPost<TrackPayload>('/orders/track', { phone });
}

/** One published news post — GET /api/news (Customer\NewsController). */
export interface NewsPost {
  id: number | string;
  title: string;
  content: string;
  image_url?: string | null;
  created_at?: string | null;
  branch?: { id: number | string; name: string } | null;
}

/** GET /api/news — the posts the website's /news page lists. */
export function fetchNews(): Promise<{ posts: NewsPost[] }> {
  return apiGet<{ posts: NewsPost[] }>('/news');
}

/** POST /api/contact — the website's Contact Us form (POST /contact). */
export function sendContact(input: {
  email: string;
  phone: string;
  subject: string;
  message: string;
}): Promise<{ message: string }> {
  return apiPost<{ message: string }>('/contact', input);
}

/**
 * POST /api/verify-staff-access — validates the staff secret code on the
 * server (CompanySettingService). The code is only ever sent here; it is
 * never stored, logged, or compared inside the app.
 */
export function verifyStaffAccess(secretCode: string): Promise<VerifyAccessPayload> {
  return apiPost<VerifyAccessPayload>('/verify-staff-access', { secret_code: secretCode });
}

/** POST /api/staff/login — validates staff credentials (stateless login). */
export function staffLogin(email: string, password: string): Promise<StaffIdentityPayload> {
  return apiPost<StaffIdentityPayload>('/staff/login', { email, password });
}

/* ------------------------------------------------------------------ *
 * Registration — the six signup types linked from the website's login
 * page. Each POST hits the very controller its Blade form posts to, so
 * validation, duplicate checks, role assignment, password hashing and
 * OTP generation all stay server-side. Requires the X-Staff-Access
 * grant (sent automatically) — same gate as the website's
 * `staff.access` middleware.
 * ------------------------------------------------------------------ */

export type StaffSignupType =
  | 'cashier'
  | 'branch-admin'
  | 'stock-manager'
  | 'customer-care'
  | 'seller'
  | 'graphic-designer';

export interface RegisterFields {
  name: string;
  email: string;
  phone: string;
  password: string;
  password_confirmation: string;
  branch_id?: string;
  secret_code?: string;
}

export interface PhotoAttachment {
  uri: string;
  name: string;
  type: string;
}

export function registerStaff(
  type: StaffSignupType,
  fields: RegisterFields,
  photo?: PhotoAttachment,
): Promise<OtpChallenge> {
  if (photo) {
    // Cashier's Blade form posts multipart (profile photo) — same here.
    const form = new FormData();
    for (const [key, value] of Object.entries(fields)) {
      if (value !== undefined && value !== null && value !== '') {
        form.append(key, String(value));
      }
    }
    // React Native's FormData accepts the {uri,name,type} file shape that
    // the DOM typings do not model — hence the cast.
    form.append('profile_picture', photo as unknown as Blob);
    return request<OtpChallenge>('POST', `/register/${type}`, { body: form });
  }
  return apiPost<OtpChallenge>(`/register/${type}`, fields);
}

/** POST /api/verify-otp — the emailed 6-digit code after registration. */
export function verifyRegistrationOtp(input: {
  email: string;
  otp: string;
  type: string;
  user_id: string | number;
}): Promise<OtpResult> {
  return apiPost<OtpResult>('/verify-otp', input);
}

/** POST /api/resend-otp — request a fresh code for the same signup. */
export function resendRegistrationOtp(input: {
  email: string;
  type: string;
  user_id: string | number;
}): Promise<{ message: string }> {
  return apiPost<{ message: string }>('/resend-otp', input);
}
