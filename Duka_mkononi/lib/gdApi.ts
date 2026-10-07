/**
 * Graphic Designer module — the single client for every /api/gd/* endpoint.
 *
 * Each function mirrors one website Graphic Designer page/operation (see
 * routes/api.php → gd group). Nothing here implements business logic:
 * validation, authorization and writes all happen in Laravel; this file only
 * names the endpoints and types their responses. Every request automatically
 * carries the X-Staff-Session token via lib/api.ts, and the server re-checks
 * the graphic_designer role on each call.
 */
import { apiDelete, apiGet, apiPost, apiPut } from './api';

/* ------------------------------------------------------------------ *
 * Shared shapes
 * ------------------------------------------------------------------ */

export interface GdBranchRef {
  id: number | string;
  name: string;
}

/** Normalised news post — status is always approved | pending | rejected. */
export interface GdPost {
  id: number | string;
  title: string;
  content?: string | null;
  image_url?: string | null;
  branch_id?: number | string | null;
  branch?: GdBranchRef | null;
  status: 'approved' | 'pending' | 'rejected';
  rejection_reason?: string | null;
  is_published?: boolean;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface GdNewsCounts {
  total: number;
  approved: number;
  pending: number;
  rejected: number;
  today: number;
}

/* ---------------------------- Dashboard ---------------------------- */

export interface GdDashboardPayload {
  counts: GdNewsCounts;
  rejected: GdPost[];
}

export function fetchGdDashboard(): Promise<GdDashboardPayload> {
  return apiGet<GdDashboardPayload>('/gd/dashboard');
}

/* ------------------------------- News ------------------------------ */

export interface GdNewsPayload {
  posts: GdPost[];
  counts: GdNewsCounts;
}

export function fetchGdNews(): Promise<GdNewsPayload> {
  return apiGet<GdNewsPayload>('/gd/news');
}

export function fetchGdPost(id: string): Promise<{ post: GdPost }> {
  return apiGet<{ post: GdPost }>(`/gd/news/${id}`);
}

export interface GdPostFields {
  title: string;
  content: string;
}

export function createGdPost(
  fields: GdPostFields,
  image?: { uri: string; name: string; type: string },
): Promise<{ message: string; post: GdPost }> {
  if (image) {
    const form = new FormData();
    form.append('title', fields.title);
    form.append('content', fields.content);
    form.append('image', image as unknown as Blob);
    return apiPost<{ message: string; post: GdPost }>('/gd/news', form);
  }
  return apiPost<{ message: string; post: GdPost }>('/gd/news', fields);
}

export function updateGdPost(id: string, fields: GdPostFields): Promise<{ message: string }> {
  return apiPut<{ message: string }>(`/gd/news/${id}`, fields);
}

export function deleteGdPost(id: string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/gd/news/${id}`);
}

/* ------------------------------ Brands ----------------------------- */

export interface GdBrand {
  id: number | string;
  name: string;
  logo_url?: string | null;
  is_active?: boolean;
  product_count?: number;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface GdBrandCounts {
  total: number;
  active: number;
  inactive: number;
  with_logo: number;
}

export function fetchGdBrands(): Promise<{ brands: GdBrand[]; counts: GdBrandCounts }> {
  return apiGet<{ brands: GdBrand[]; counts: GdBrandCounts }>('/gd/brands');
}

export function fetchGdBrand(id: string): Promise<{ brand: GdBrand }> {
  return apiGet<{ brand: GdBrand }>(`/gd/brands/${id}`);
}

export interface GdBrandFormFields {
  name: string;
  is_active?: boolean;
}

export function createGdBrand(
  fields: GdBrandFormFields,
  logo?: { uri: string; name: string; type: string },
): Promise<{ message: string; brand: GdBrand }> {
  if (logo) {
    const form = new FormData();
    form.append('name', fields.name);
    // Laravel's boolean rule accepts '1'/'0', not 'true'/'false' — the
    // website's checkbox sends value="1", so multipart mirrors that.
    if (fields.is_active !== undefined) form.append('is_active', fields.is_active ? '1' : '0');
    form.append('logo', logo as unknown as Blob);
    return apiPost<{ message: string; brand: GdBrand }>('/gd/brands', form);
  }
  return apiPost<{ message: string; brand: GdBrand }>('/gd/brands', fields);
}

export function updateGdBrand(
  id: string,
  fields: GdBrandFormFields,
  logo?: { uri: string; name: string; type: string },
): Promise<{ message: string }> {
  if (logo) {
    const form = new FormData();
    form.append('name', fields.name);
    // See createGdBrand: '1'/'0' is what Laravel's boolean rule accepts.
    if (fields.is_active !== undefined) form.append('is_active', fields.is_active ? '1' : '0');
    form.append('_method', 'PUT');
    form.append('logo', logo as unknown as Blob);
    return apiPost<{ message: string }>(`/gd/brands/${id}`, form);
  }
  return apiPut<{ message: string }>(`/gd/brands/${id}`, fields);
}

export function deleteGdBrand(id: string): Promise<{ message: string }> {
  return apiDelete<{ message: string }>(`/gd/brands/${id}`);
}

/* ------------------------------ QR code ---------------------------- */

export interface GdQrCodePayload {
  url: string;
  image: string | null;
}

export function fetchGdQrCode(): Promise<GdQrCodePayload> {
  return apiGet<GdQrCodePayload>('/gd/qr-code');
}

/* --------------------------- Profile/account ----------------------- */

export interface GdProfileUser {
  id: number | string;
  name?: string | null;
  email?: string | null;
  phone?: string | null;
  role?: string | null;
  status?: string | null;
  branch_id?: number | string | null;
  profile_picture?: string | null;
}

export function fetchGdProfile(): Promise<{ user: GdProfileUser; branch: GdBranchRef | null }> {
  return apiGet<{ user: GdProfileUser; branch: GdBranchRef | null }>('/gd/profile');
}

export function saveGdProfile(
  fields: { name: string; email: string; phone?: string },
  photo?: { uri: string; name: string; type: string },
): Promise<{ message: string; user: GdProfileUser }> {
  if (photo) {
    const form = new FormData();
    for (const [k, v] of Object.entries(fields)) {
      if (v !== undefined && v !== null && v !== '') form.append(k, String(v));
    }
    form.append('_method', 'PUT');
    form.append('profile_picture', photo as unknown as Blob);
    return apiPost<{ message: string; user: GdProfileUser }>('/gd/profile', form);
  }
  return apiPut<{ message: string; user: GdProfileUser }>('/gd/profile', fields);
}

export function changeGdPassword(fields: {
  current_password: string;
  password: string;
  password_confirmation: string;
}): Promise<{ message: string }> {
  return apiPut<{ message: string }>('/gd/profile/password', fields);
}
