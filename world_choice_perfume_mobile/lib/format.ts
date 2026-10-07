const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

/**
 * "TZS 1,234,567" — matches the website's `TZS {{ number_format(...) }}`
 * without relying on Intl (which Hermes does not fully guarantee).
 */
export function formatMoney(value: number | string | null | undefined): string {
  const n = Number(value ?? 0);
  if (!Number.isFinite(n)) return 'TZS —';
  const rounded = Math.round(n);
  const grouped = Math.abs(rounded)
    .toString()
    .replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  return `${rounded < 0 ? '-' : ''}TZS ${grouped}`;
}

/** "12 Mar 2026" from an ISO timestamp. */
export function formatDate(iso: string | null | undefined): string {
  if (!iso) return '';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  return `${d.getDate()} ${MONTHS[d.getMonth()]} ${d.getFullYear()}`;
}

/** "12 Mar 2026 · 14:05" from an ISO timestamp. */
export function formatDateTime(iso: string | null | undefined): string {
  if (!iso) return '';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  const hh = d.getHours().toString().padStart(2, '0');
  const mm = d.getMinutes().toString().padStart(2, '0');
  return `${formatDate(iso)} · ${hh}:${mm}`;
}
