<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report — {{ $date->timezone('Africa/Dar_es_Salaam')->format('d M Y') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; padding: 30px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #059669; padding-bottom: 15px; }
        .header h1 { font-size: 18px; color: #059669; margin-bottom: 4px; }
        .header p { font-size: 12px; color: #666; }
        .summary { display: flex; gap: 20px; margin-bottom: 20px; }
        .summary-card { flex: 1; text-align: center; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; }
        .summary-card .value { font-size: 20px; font-weight: bold; color: #059669; }
        .summary-card .value.blue { color: #2563eb; }
        .summary-card .value.amber { color: #d97706; }
        .summary-card .label { font-size: 10px; color: #888; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .branch-section { margin-bottom: 20px; page-break-inside: avoid; }
        .branch-header { background: #f3f4f6; padding: 8px 12px; border-radius: 6px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
        .branch-header h3 { font-size: 13px; color: #1f2937; }
        .branch-header .branch-total { font-size: 14px; font-weight: bold; color: #059669; }
        .branch-header .branch-meta { font-size: 9px; color: #888; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th { background: #f9fafb; text-align: left; padding: 6px 8px; font-size: 9px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e5e7eb; }
        td { padding: 5px 8px; border-bottom: 1px solid #f3f4f6; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #aaa; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>World Choice Perfumes — Daily Sales Report</h1>
        <p>{{ $date->timezone('Africa/Dar_es_Salaam')->format('l, d F Y') }}</p>
    </div>

    <div class="summary">
        <div class="summary-card">
            <div class="value">TZS {{ number_format($totalSales) }}</div>
            <div class="label">Total Revenue</div>
        </div>
        <div class="summary-card">
            <div class="value blue">{{ $totalTransactions }}</div>
            <div class="label">Total Transactions</div>
        </div>
        <div class="summary-card">
            <div class="value amber">{{ $totalItems }}</div>
            <div class="label">Total Items Sold</div>
        </div>
    </div>

    @foreach($branchGroups as $group)
        <div class="branch-section">
            <div class="branch-header">
                <div>
                    <h3>{{ $group['branch_name'] }}</h3>
                    <div class="branch-meta">{{ $group['transactions'] }} transaction{{ $group['transactions'] !== 1 ? 's' : '' }} • {{ $group['items_sold'] }} item{{ $group['items_sold'] !== 1 ? 's' : '' }} sold</div>
                </div>
                <div class="branch-total">TZS {{ number_format($group['total_sales']) }}</div>
            </div>
            @if($group['sales']->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Sale #</th>
                            <th>Cashier</th>
                            <th class="text-center">Items</th>
                            <th class="text-right">Total</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($group['sales'] as $sale)
                            <tr>
                                <td>{{ $sale->sale_number ?? '—' }}</td>
                                <td>{{ $sale->cashier?->name ?? '—' }}</td>
                                <td class="text-center">{{ $sale->items_count ?? 0 }}</td>
                                <td class="text-right">TZS {{ number_format($sale->total ?? 0) }}</td>
                                <td>{{ $sale->created_at ? \\Carbon\\Carbon::parse($sale->created_at)->setTimezone('Africa/Dar_es_Salaam')->format('H:i') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="text-align:center;color:#999;padding:8px;">No sales</p>
            @endif
        </div>
    @endforeach

    @if($branchGroups->isEmpty())
        <p style="text-align:center;color:#999;padding:20px;">No sales recorded for this day.</p>
    @endif

    <div class="footer">
        Generated on {{ now()->timezone('Africa/Dar_es_Salaam')->format('d M Y H:i') }} • World Choice Perfumes
    </div>
</body>
</html>
