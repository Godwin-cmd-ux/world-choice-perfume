/**
 * The app's shopping cart.
 *
 * An order always belongs to ONE branch — that is how the website's checkout
 * works (one branch_id + a list of items) and how stock is tracked. The cart
 * therefore holds one branch at a time: adding something from another branch
 * is rejected with `branch-conflict` so the screen can offer to start fresh.
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
  useRef,
  useState,
  type ReactNode,
} from 'react';

const STORAGE_KEY = 'wcp_cart_v1';

export interface CartItem {
  /** Stable identity: branch + product + chosen bottling. */
  key: string;
  productId: number | string;
  productName: string;
  brand?: string | null;
  image?: string | null;
  branchId: number | string;
  branchName: string;
  quantity: number;
  unitPrice: number;
  /** Oil-fragrance bottling, when the product is sold in varieties. */
  volume?: number;
  variant?: string;
  varietyLabel?: string;
  /** How many units the branch had when it was added (clamps quantity). */
  available?: number;
}

export type AddResult = 'added' | 'branch-conflict';

interface CartValue {
  items: CartItem[];
  branchId: number | string | null;
  branchName: string | null;
  count: number;
  subtotal: number;
  ready: boolean;
  /** `replace: true` discards a cart from another branch and starts fresh. */
  addItem: (item: CartItem, options?: { replace?: boolean }) => AddResult;
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

  // Read synchronously from an event handler without stale closures.
  const itemsRef = useRef<CartItem[]>(items);
  useEffect(() => {
    itemsRef.current = items;
  }, [items]);

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
              (parsed as CartItem[]).filter(
                (item) => item && item.productId != null && item.branchId != null,
              ),
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

  const addItem = useCallback((item: CartItem, options?: { replace?: boolean }): AddResult => {
    const replace = options?.replace ?? false;
    const current = itemsRef.current;
    if (!replace && current.length > 0 && String(current[0].branchId) !== String(item.branchId)) {
      return 'branch-conflict';
    }

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
      branchId: items[0]?.branchId ?? null,
      branchName: items[0]?.branchName ?? null,
      count,
      subtotal,
      ready,
      addItem,
      setQuantity,
      removeItem,
      clear,
    };
  }, [items, ready, addItem, setQuantity, removeItem, clear]);

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}
