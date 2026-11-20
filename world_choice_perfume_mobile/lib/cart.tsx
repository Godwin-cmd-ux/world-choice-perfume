/**
 * The app's shopping cart.
 *
 * Every customer order belongs to Mikocheni HQ, even when the product was on a
 * shelf somewhere else. The cart therefore has one destination: a source branch
 * is never stored, shown or used to split the basket. Carts saved by an older
 * build are normalised to HQ when they are restored.
 *
 * The cart survives app restarts (AsyncStorage), so a customer can browse,
 * close the app and still check out later.
 */
import AsyncStorage from '@react-native-async-storage/async-storage';
import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { CUSTOMER_ORDER_BRANCH_ID } from './api';

const STORAGE_KEY = 'wcp_cart_v1';
const ORDER_BRANCH_NAME = 'Mikocheni HQ';

/** Rebuild a line with HQ as its only destination, including lines saved by an
 *  older build that still carried a source branch id in the key. */
function asHqItem(item: CartItem): CartItem {
  const suffix = item.key.includes('-') ? item.key.slice(item.key.indexOf('-') + 1) : item.key;

  return {
    ...item,
    key: `${CUSTOMER_ORDER_BRANCH_ID}-${suffix}`,
    branchId: CUSTOMER_ORDER_BRANCH_ID,
    branchName: ORDER_BRANCH_NAME,
  };
}

export interface CartItem {
  /** Stable identity: HQ + product + chosen bottling. */
  key: string;
  productId: number | string;
  productName: string;
  brand?: string | null;
  image?: string | null;
  /** Always HQ; kept on the line so the checkout payload is unambiguous. */
  branchId: number | string;
  branchName: string;
  quantity: number;
  unitPrice: number;
  /** Oil-fragrance bottling, when the product is sold in varieties. */
  volume?: number;
  variant?: string;
  varietyLabel?: string;
  /** How many units were available when it was added (clamps quantity). */
  available?: number;
}

export type AddResult = 'added';

interface CartValue {
  items: CartItem[];
  count: number;
  subtotal: number;
  ready: boolean;
  /** `replace: true` replaces the line rather than stacking quantities. */
  addItem: (item: CartItem, options?: { replace?: boolean }) => AddResult;
  /**
   * Park one line at an exact quantity: a line already holding this
   * product+size takes the new quantity instead of stacking on top of
   * it. The product screen owns its own line, so re-opening a perfume that is
   * already in the cart must not silently double it.
   */
  putItem: (item: CartItem, options?: { replace?: boolean }) => AddResult;
  setQuantity: (key: string, quantity: number) => void;
  removeItem: (key: string) => void;
  clear: () => void;
}

const CartContext = createContext<CartValue | null>(null);

export function useCart(): CartValue {
  const value = useContext(CartContext);
  if (!value) throw new Error('useCart must be used inside <CartProvider>');
  return value;
}

/** Keep a quantity within 1..available (when we know what is available). */
function clampQuantity(quantity: number, available?: number): number {
  const rounded = Math.max(1, Math.round(quantity));
  if (available !== undefined && available > 0 && rounded > available) return available;
  return rounded;
}

export function CartProvider({ children }: { children: ReactNode }) {
  const [items, setItems] = useState<CartItem[]>([]);
  const [ready, setReady] = useState(false);

  // Restore the saved cart once, on mount.
  useEffect(() => {
    let active = true;
    (async () => {
      try {
        const raw = await AsyncStorage.getItem(STORAGE_KEY);
        if (active && raw) {
          const parsed: unknown = JSON.parse(raw);
          if (Array.isArray(parsed)) {
            setItems(
              (parsed as CartItem[])
                .filter((item) => item && item.productId != null)
                .map(asHqItem),
            );
          }
        }
      } catch {
        // A missing/corrupt cart is simply an empty cart.
      } finally {
        if (active) setReady(true);
      }
    })();
    return () => {
      active = false;
    };
  }, []);

  // Persist after every change (skipping the initial empty render).
  useEffect(() => {
    if (!ready) return;
    AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(items)).catch(() => {
      // Persistence is best-effort; the in-memory cart still works.
    });
  }, [items, ready]);

  const addItem = useCallback((raw: CartItem, options?: { replace?: boolean }): AddResult => {
    const item = asHqItem(raw);
    const replace = options?.replace ?? false;

    setItems((list) => {
      const base = replace ? [] : list;
      const existing = base.find((i) => i.key === item.key);
      if (existing) {
        return base.map((i) =>
          i.key === item.key
            ? {
                ...i,
                quantity: clampQuantity(i.quantity + item.quantity, item.available ?? i.available),
                available: item.available ?? i.available,
                unitPrice: item.unitPrice,
              }
            : i,
        );
      }
      return [...base, { ...item, quantity: clampQuantity(item.quantity, item.available) }];
    });

    return 'added';
  }, []);

  const putItem = useCallback((raw: CartItem, options?: { replace?: boolean }): AddResult => {
    const item = asHqItem(raw);
    const replace = options?.replace ?? false;

    setItems((list) => {
      const base = replace ? [] : list;
      const line = { ...item, quantity: clampQuantity(item.quantity, item.available) };
      return base.some((i) => i.key === item.key)
        ? base.map((i) => (i.key === item.key ? line : i))
        : [...base, line];
    });

    return 'added';
  }, []);

  const setQuantity = useCallback((key: string, quantity: number) => {
    setItems((list) =>
      list.map((i) => (i.key === key ? { ...i, quantity: clampQuantity(quantity, i.available) } : i)),
    );
  }, []);

  const removeItem = useCallback((key: string) => {
    setItems((list) => list.filter((i) => i.key !== key));
  }, []);

  const clear = useCallback(() => setItems([]), []);

  const value = useMemo<CartValue>(() => {
    const count = items.reduce((sum, i) => sum + i.quantity, 0);
    const subtotal = items.reduce((sum, i) => sum + i.unitPrice * i.quantity, 0);
    return {
      items,
      count,
      subtotal,
      ready,
      addItem,
      putItem,
      setQuantity,
      removeItem,
      clear,
    };
  }, [items, ready, addItem, putItem, setQuantity, removeItem, clear]);

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}
