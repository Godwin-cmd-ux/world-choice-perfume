/**
 * Cross-branch monitoring state for the Cashier module — the stateless twin
 * of the website's `session('cashier_cross_branch_id')`.
 *
 * The website remembers the monitored branch in the HTTP session; the app
 * has no cookie jar, so the choice lives here (in memory only, like
 * staffSession — an app restart clears it) and is sent as `monitor_branch`
 * with each read. The server re-authorises monitor rights and refuses every
 * write while a monitor branch is set, so this is convenience, never
 * authority.
 */

export interface MonitorBranch {
  id: number;
  name: string;
}

let current: MonitorBranch | null = null;
const listeners = new Set<() => void>();

function emit() {
  listeners.forEach((l) => l());
}

export const cashierMonitor = {
  get(): MonitorBranch | null {
    return current;
  },
  set(branch: MonitorBranch): void {
    current = branch;
    emit();
  },
  clear(): void {
    current = null;
    emit();
  },
  subscribe(listener: () => void): () => void {
    listeners.add(listener);
    return () => {
      listeners.delete(listener);
    };
  },
};
