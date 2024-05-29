<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->cannot('access dashboard')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $period = $request->input('period');

            if (empty($fromDate) || empty($toDate)) {
                $oldestInvoiceDate = Invoice::orderBy('created_at', 'asc')->first()
                    ->created_at->startOfDay();

                $fromDate = $oldestInvoiceDate ?? Carbon::now()->subYear()->startOfDay();
                $toDate = Carbon::now()->endOfDay();
            } else {
                $fromDate = Carbon::parse($fromDate)->startOfDay();
                $toDate = Carbon::parse($toDate)->endOfDay();
            }

            $interval = $fromDate->diffInDays($toDate);
            $prevFromDate = $fromDate->copy()->subDays($interval)->startOfDay();
            $prevToDate = $fromDate->copy()->subDays()->endOfDay();


            $data = [
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'prevFromDate' => $prevFromDate,
                'prevToDate' => $prevToDate,

                'revenue' => Invoice::where('status', 1)
                    ->whereBetween('date', [$fromDate, $toDate])
                    ->get()
                    ->sum('total'),

                'previous_revenue' => Invoice::where('status', 1)
                    ->whereBetween('date', [$prevFromDate, $prevToDate])
                    ->get()
                    ->sum('total'),

                'approved_orders' => Invoice::where('status', 1)
                    ->whereBetween('date', [$fromDate, $toDate])
                    ->count(),
                'previous_approved_orders' => Invoice::where('status', 1)
                    ->whereBetween('date', [$prevFromDate, $prevToDate])
                    ->count(),

                'products_sold' => round(InvoiceItem::whereHas('invoice', function ($query) use ($fromDate, $toDate) {
                    $query->where('status', 1)
                        ->whereBetween('date', [$fromDate, $toDate]);
                })->sum('quantity')),
                'previous_products_sold' => round(InvoiceItem::whereHas('invoice', function ($query) use ($prevFromDate, $prevToDate) {
                    $query->where('status', 1)
                        ->whereBetween('date', [$prevFromDate, $prevToDate]);
                })->sum('quantity')),

                'customers' => Customer::whereBetween('created_at', [$fromDate, $toDate])->count(),
                'previous_customers' => Customer::whereBetween('created_at', [$prevFromDate, $prevToDate])->count(),

                'sales_timeseries' => $this->generateSalesTimeseries($fromDate, $toDate, $period),
            ];

            return ResponseFormatter::success(200, 'OK', $data);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    private function generateSalesTimeseries($fromDate, $toDate, $period = 'monthly')
    {
        $invoices = Invoice::where('status', 1)
            ->whereBetween('date', [$fromDate, $toDate])
            ->get();

        $salesTimeseries = [];

        if ($period === 'daily') {
            $dateRange = collect($fromDate->copy()->daysUntil($toDate));

            foreach ($dateRange as $date) {
                $formattedDate = $date->format('j');
                $formattedMonth = $date->format('M');
                $formattedYear = $date->format('Y');

                $salesTimeseries[] = [
                    'date' => $formattedDate,
                    'month' => $formattedMonth,
                    'year' => $formattedYear,
                    'revenue' => 0,
                ];
            }

            $invoices->groupBy(function ($item) {
                return Carbon::parse($item->date)->format('j M Y');
            });
        } else if ($period === 'monthly') {
            $monthRange = collect($fromDate->copy()->monthUntil($toDate));

            foreach ($monthRange as $month) {
                $formattedMonth = $month->format('M');
                $formattedYear = $month->format('Y');

                $salesTimeseries[] = [
                    'month' => $formattedMonth,
                    'year' => $formattedYear,
                    'revenue' => 0,
                ];
            }

            $invoices->groupBy(function ($item) {
                return Carbon::parse($item->date)->format('M Y');
            });
        } else if ($period === 'yearly') {
            $yearRange = collect($fromDate->copy()->startOfYear()->yearUntil($toDate->copy()->endOfYear()));

            foreach ($yearRange as $year) {
                $formattedYear = $year->format('Y');

                $salesTimeseries[] = [
                    'year' => $formattedYear,
                    'revenue' => 0,
                ];
            }

            $invoices->groupBy(function ($item) {
                return Carbon::parse($item->date)->format('Y');
            });
        }

        foreach ($invoices as $invoice) {
            $invoiceDate = Carbon::parse($invoice->date);
            $date = $invoiceDate->format('j');
            $month = $invoiceDate->format('M');
            $year = $invoiceDate->format('Y');

            foreach ($salesTimeseries as &$entry) {
                if ($period === 'daily' && $entry['date'] == $date && $entry['month'] == $month && $entry['year'] == $year) {
                    $entry['revenue'] += $invoice->total;
                    break;
                } elseif ($period === 'monthly' && $entry['month'] == $month && $entry['year'] == $year) {
                    $entry['revenue'] += $invoice->total;
                    break;
                } elseif ($period === 'yearly' && $entry['year'] == $year) {
                    $entry['revenue'] += $invoice->total;
                    break;
                }
            }
        }

        return $salesTimeseries;
    }
}
