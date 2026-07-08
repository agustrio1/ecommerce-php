<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Presentation\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Dashboard\Application\Services\DashboardService;

class ReportController
{
    private DashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    public function index(Request $request): Response
    {
        $from    = $request->query('from', date('Y-m-01'));
        $to      = $request->query('to', date('Y-m-d'));
        $groupBy = $request->query('group_by', 'day');

        $summary         = $this->dashboardService->getPeriodSummary($from, $to);
        $salesChart      = $this->dashboardService->getSalesReport($from, $to, $groupBy);
        $productReport   = $this->dashboardService->getProductReport($from, $to, 10);
        $categoryReport  = $this->dashboardService->getCategoryReport($from, $to);

        return Response::make(view('Dashboard::report', [
            'title'          => 'Laporan Penjualan',
            'from'           => $from,
            'to'             => $to,
            'groupBy'        => $groupBy,
            'summary'        => $summary,
            'salesChart'     => $salesChart,
            'productReport'  => $productReport,
            'categoryReport' => $categoryReport,
        ]));
    }

    public function export(Request $request): Response
    {
        $from  = $request->query('from', date('Y-m-01'));
        $to    = $request->query('to', date('Y-m-d'));
        $type  = $request->query('type', 'sales');

        $filename = "laporan_{$type}_{$from}_{$to}.csv";
        $output   = fopen('php://temp', 'r+');

        switch ($type) {
            case 'products':
                fputcsv($output, ['Produk', 'Total Terjual', 'Total Order', 'Revenue', 'Harga Rata-rata']);
                foreach ($this->dashboardService->getProductReport($from, $to, 100) as $row) {
                    fputcsv($output, [
                        $row['product_name'],
                        $row['total_qty'],
                        $row['total_orders'],
                        $row['total_revenue'],
                        round($row['avg_price']),
                    ]);
                }
                break;

            case 'categories':
                fputcsv($output, ['Kategori', 'Total Terjual', 'Total Order', 'Revenue']);
                foreach ($this->dashboardService->getCategoryReport($from, $to) as $row) {
                    fputcsv($output, [
                        $row['category_name'],
                        $row['total_qty'],
                        $row['total_orders'],
                        $row['total_revenue'],
                    ]);
                }
                break;

            default: // sales
                fputcsv($output, ['Periode', 'Total Order', 'Order Valid', 'Revenue', 'Subtotal', 'Ongkir', 'Diskon']);
                foreach ($this->dashboardService->getSalesReport($from, $to) as $row) {
                    fputcsv($output, [
                        $row['period'],
                        $row['total_orders'],
                        $row['valid_orders'],
                        $row['revenue'],
                        $row['subtotal'],
                        $row['shipping'],
                        $row['discount'],
                    ]);
                }
                break;
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return Response::make($csv, 200)
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}