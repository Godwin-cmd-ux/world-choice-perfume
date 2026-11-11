<?php

namespace App\Http\Controllers\BranchAdmin;

use App\Http\Controllers\Controller;
use App\Services\ProfitReportService;
use App\Support\BusinessDay;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private ProfitReportService $profitReports;

    public function __construct()
    {
        $this->profitReports = new ProfitReportService();
    }

    public function index(Request $request)
    {
        $branchId = (int) (auth()->user()->branch_id ?? 0);

        $startDate = $request->date_from
            ? Carbon::parse($request->date_from, BusinessDay::TIMEZONE)->startOfDay()
            : Carbon::now(BusinessDay::TIMEZONE)->startOfMonth();

        $endDate = $request->date_to
            ? Carbon::parse($request->date_to, BusinessDay::TIMEZONE)->endOfDay()
            : Carbon::now(BusinessDay::TIMEZONE)->endOfDay();

        $summary = $this->profitReports->salesProfit($branchId ?: null, $startDate, $endDate);
        $stock = $this->profitReports->stockProfit($branchId ?: null);

        return view('branch-admin.reports.index', [
            'summary' => $summary,
            'rows' => $stock['rows'],
            'stockSummary' => $stock['summary'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'rangeLabel' => $startDate->format('M d, Y') . ' – ' . $endDate->format('M d, Y'),
        ]);
    }
}
