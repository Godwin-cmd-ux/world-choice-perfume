/**
 * Which stock a sale type may offer.
 *
 * Retail refuses oil fragrance bottlings under 50ml — 6ml, 12ml and 30ml are
 * wholesale sizes only. Wholesale buys oil fragrance in every size, but brand
 * perfumes are not sold wholesale at all. The website's sale form and these
 * screens apply the same rule, so staff see the same products on both.
 */
export type SaleType = 'retail' | 'wholesale';

export const OIL_FRAGRANCE = 'Oil Fragrance';
export const BRAND_PERFUME = 'Brand Perfume';
export const RETAIL_MIN_OIL_ML = 50;

/**
 * Whether a product is offered at all for the sale type. `volumes` is the
 * bottling sizes the product holds at the branch: an oil fragrance bottled
 * only in 6/12/30ml has nothing to sell retail, so it is hidden rather than
 * listed with an empty size picker. A product with no bottling records has no
 * size to judge and stays as it is.
 */
export function productSellable(
  category: string | null | undefined,
  volumes: number[],
  saleType: SaleType,
): boolean {
  if (saleType === 'wholesale') {
    return category === OIL_FRAGRANCE;
  }

  if (category === OIL_FRAGRANCE && volumes.length > 0) {
    return volumes.some((volume) => volume >= RETAIL_MIN_OIL_ML);
  }

  return true;
}

/** Whether one bottling size may be sold for the sale type. */
export function volumeSellable(
  category: string | null | undefined,
  volume: number | undefined,
  saleType: SaleType,
): boolean {
  if (saleType === 'wholesale') {
    return category === OIL_FRAGRANCE;
  }

  return !(category === OIL_FRAGRANCE && volume !== undefined && volume > 0 && volume < RETAIL_MIN_OIL_ML);
}

/** Whether a line already in the sale still belongs to the sale type. */
export function lineSellable(
  category: string | null | undefined,
  volume: number | undefined,
  saleType: SaleType,
): boolean {
  return volumeSellable(category, volume, saleType);
}
