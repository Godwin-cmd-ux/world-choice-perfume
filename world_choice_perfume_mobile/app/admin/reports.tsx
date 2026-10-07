import { router } from 'expo-router';
import { AdminPage, DataCard, GroupLabel } from '../../components/adminkit';

/**
 * Reports hub — the mobile twin of super-admin/reports/index.blade.php.
 * The website hub links to the five report pages; the app does the same,
 * each opening a filtered, phone-native rendering of the same figures.
 */
export default function ReportsHub() {
  const reports = [
    { icon: 'trending-up-outline' as const, title: 'Sales Report', hint: 'Per branch, per day — totals and transactions', path: '/admin/report-sales' },
    { icon: 'wallet-outline' as const, title: 'Expenses Report', hint: 'By category for a date range', path: '/admin/report-expenses' },
    { icon: 'cube-outline' as const, title: 'Stock Report', hint: 'Units and stock value per product/branch', path: '/admin/report-stock' },
    { icon: 'people-outline' as const, title: 'Staff Performance', hint: 'Sales, transactions and items per staff member', path: '/admin/report-staff' },
    { icon: 'pricetag-outline' as const, title: 'Product Performance', hint: 'Sell-through and revenue contribution', path: '/admin/report-products' },
  ];

  return (
    <AdminPage title="Reports" onBack={() => router.back()}>
      <GroupLabel>Choose a report</GroupLabel>
      {reports.map((r) => (
        <DataCard
          key={r.path}
          title={r.title}
          subtitle={r.hint}
          onPress={() => router.push(r.path as never)}
          right={undefined}
        />
      ))}
    </AdminPage>
  );
}
