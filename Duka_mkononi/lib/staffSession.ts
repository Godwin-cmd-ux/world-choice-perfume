/**
 * Session state for the staff area.
 *
 * Kept in memory only — nothing about the staff secret code or the signed-in
 * staff member is persisted to disk, so a restart of the app clears it. The
 * secret code itself is never stored anywhere: the server only ever tells us
 * "verified" or "not verified".
 */
export interface StaffIdentity {
  id?: number | string | null;
  name?: string | null;
  email?: string | null;
  role?: string | null;
  branch?: { id?: number | string | null; name?: string | null } | null;
}

let accessVerified = false;
let accessToken: string | null = null;
let sessionToken: string | null = null;
let identity: StaffIdentity | null = null;

export const staffSession = {
  isVerified(): boolean {
    return accessVerified;
  },
  setVerified(value: boolean): void {
    accessVerified = value;
  },
  /**
   * Encrypted grant issued by POST /api/verify-staff-access. The secret code
   * is never stored — only this short-lived, server-issued stand-in for the
   * website's `staff_access_verified` session flag. Sent automatically as
   * the X-Staff-Access header by the API client.
   */
  getAccessToken(): string | null {
    return accessToken;
  },
  setAccessToken(value: string | null): void {
    accessToken = value;
  },
  /**
   * Signed-in session token issued by POST /api/staff/login after the server
   * checked the password. Sent automatically as X-Staff-Session; the server
   * re-reads the user row and re-checks the role on every admin API call, so
   * keeping it here is convenience, never authority. In memory only — an app
   * restart clears it and the staff member signs in again.
   */
  getSessionToken(): string | null {
    return sessionToken;
  },
  setSessionToken(value: string | null): void {
    sessionToken = value;
  },
  /** True only when the server told us the signed-in role is super_admin. */
  isSuperAdmin(): boolean {
    return identity?.role === 'super_admin';
  },
  /**
   * Drop the verified flag + grant when the server says the grant is
   * missing/expired (403), so the secret-code prompt is shown again.
   * Any staff identity is intentionally kept.
   */
  invalidateAccess(): void {
    accessVerified = false;
    accessToken = null;
  },
  getIdentity(): StaffIdentity | null {
    return identity;
  },
  setIdentity(value: StaffIdentity | null): void {
    identity = value;
  },
  clear(): void {
    accessVerified = false;
    accessToken = null;
    sessionToken = null;
    identity = null;
  },
};

/**
 * Role → label + website dashboard path, mirroring AuthController::redirectByRole
 * and the route prefixes in routes/web.php. The dashboard itself stays on the
 * website behind its own role middleware — the app only links to it.
 */
export const STAFF_ROLES: Record<string, { label: string; path: string }> = {
  super_admin: { label: 'Super Admin', path: '/super-admin/dashboard' },
  branch_admin: { label: 'Branch Admin', path: '/branch-admin/dashboard' },
  cashier: { label: 'Cashier', path: '/cashier/dashboard' },
  stock_manager: { label: 'Stock Manager', path: '/stock-manager/dashboard' },
  customer_care: { label: 'Customer Care', path: '/customer-care/dashboard' },
  seller: { label: 'Seller', path: '/seller/dashboard' },
  graphic_designer: { label: 'Graphic Designer', path: '/graphic-designer/news' },
};

export function roleInfo(role?: string | null): { label: string; path: string } {
  if (role && STAFF_ROLES[role]) return STAFF_ROLES[role];
  return { label: 'Staff', path: '/login' };
}
