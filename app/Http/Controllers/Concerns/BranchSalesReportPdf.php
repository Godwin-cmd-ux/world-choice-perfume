<?php

namespace App\Http\Controllers\Concerns;

use App\Services\BranchReportService;
use App\Support\BusinessDay;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Sales report download / print actions for the branch-scoped report modules.
 *
 * The Super Admin has its own version that takes a branch filter; here the
 * branch is fixed by the role using the trait, so the same PDF template and
 * print page can be reused with only the date as a parameter.
 */
trait BranchSalesReportPdf
{
    abstract protected function reportBranchId(): int;

    abstract protected function salesPdfStreamRouteName(): string;

    public function generateSalesReport(Request $request)
    {
        [$pdf, $filename] = $this->buildBranchSalesReportPdf($this->reportBranchId(), $request);

        return $pdf->download($filename);
    }

    public function printSalesReport(Request $request)
    {
        return view('super-admin.reports.sales-print', [
            'pdfUrl' => route($this->salesPdfStreamRouteName(), $request->only(['date'])),
            'title' => 'Print Sales Report',
        ]);
    }

    public function streamSalesReport(Request $request)
    {
        [$pdf, $filename] = $this->buildBranchSalesReportPdf($this->reportBranchId(), $request);

        return $pdf->stream($filename);
    }

    /**
     * @return array{0: \Barryvdh\DomPDF\PDF, 1: string}
     */
    private function buildBranchSalesReportPdf(int $branchId, Request $request): array
    {
        $date = $request->date
            ? Carbon::parse($request->date, BusinessDay::TIMEZONE)
            : BusinessDay::now();

        $data = (new BranchReportService())->sales($branchId, $date);

        $html = view('super-admin.reports.sales-pdf', [
            'date' => $data['date'],
            'branchGroups' => $data['branchGroups'],
            'totalSales' => $data['totalSales'],
            'totalTransactions' => $data['totalTransactions'],
            'totalItems' => $data['totalItems'],
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHtml($html)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

        $filename = 'sales-report-' . $date->timezone(BusinessDay::TIMEZONE)->format('Y-m-d') . '.pdf';

        return [$pdf, $filename];
    }
}
