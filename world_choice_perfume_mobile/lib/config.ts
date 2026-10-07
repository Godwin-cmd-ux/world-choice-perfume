/**
 * Central configuration for the World Choice Perfume mobile app.
 *
 * BASE_URL is the single production base URL for the whole app: every JSON
 * request, every website deep link and every external asset is derived from
 * this one value. No other file may hard-code a worldchoiceperfume.com URL
 * (or an http:// variant of it).
 */
export const BASE_URL = 'https://worldchoiceperfume.com';

/** Root of the Laravel JSON routes (the `api` route prefix). */
export const API_BASE = `${BASE_URL}/api`;

/** How long a single request may take before it is treated as a timeout. */
export const REQUEST_TIMEOUT_MS = 15000;
