<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Concerns\BranchSalesReportPdf;
use App\Http\Controllers\Controller;
use App\Services\BranchReportService;
use App\Services\CashierScope;
use App\Support\BusinessDay;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use BranchSalesReportPdf;

    private BranchReportService $branchReports;
    private CashierScope $scope;

    public function __construct()
    {
        $this->branchReports = new BranchReportService();
        $this->scope = new CashierScope();
    }

    public function index()
    {
        return view('reports.index', $this->pageContext());
    }

    public function sales(Request $request)
    {
        $date = $request->date
            ? Carbon::parse($request->date, BusinessDay::TIMEZONE)
            : BusinessDay::now();

        return view('reports.sales', array_merge(
            $this->branchReports->sales($this->branchId(), $date),
            $this->pageContext()
        ));
    }

    public function expenses(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);

        return view('reports.expenses', array_merge(
            $this->branchReports->expenses($this->branchId(), $startDate, $endDate),
            ['startDate' => $startDate, 'endDate' => $endDate],
            $this->pageContext()
        ));
    }

    public function stock()
    {
        return view('reports.stock', array_merge(
            ['report' => $this->branchReports->stock($this->branchId())],
            $this->pageContext()
        ));
    }

    public function productPerformance(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);

        return view('reports.product-performance', array_merge(
            $this->branchReports->productPerformance($this->branchId(), $startDate, $endDate),
            ['startDate' => $startDate, 'endDate' => $endDate],
            $this->pageContext()
        ));
    }

    public function profit(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);

        return view('reports.profit', array_merge(
            $this->branchReports->profit($this->branchId(), $startDate, $endDate),
            [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'rangeLabel' => $startDate->format('M d, Y') . ' – ' . $endDate->format('M d, Y'),
            ],
            $this->pageContext()
        ));
    }

    protected function reportBranchId(): int
    {
        return $this->branchId();
    }

    protected function salesPdfStreamRouteName(): string
    {
        return 'cashier.reports.stream-sales-report';
    }

    private function branchId(): int
    {
        return $this->scope->activeBranchId();
    }

    private function pageContext(): array
    {
        return [
            'routePrefix' => 'cashier.reports',
            'showCashierBanner' => true,
            'inCrossBranch' => $this->scope->inCrossBranchMode(),
            'activeBranchName' => $this->scope->activeBranchName(),
            'branchName' => $this->scope->activeBranchName(),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function dateRange(Request $request): array
    {
        $startDate = $request->date_from
            ? Carbon::parse($request->date_from, BusinessDay::TIMEZONE)->startOfDay()
            : Carbon::now(BusinessDay::TIMEZONE)->startOfMonth();

        $endDate = $request->date_to
            ? Carbon::parse($request->date_to, BusinessDay::TIMEZONE)->endOfDay()
            : Carbon::now(BusinessDay::TIMEZONE)->endOfDay();

        return [$startDate, $endDate];
    }
}
