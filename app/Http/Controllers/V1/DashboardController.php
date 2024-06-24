<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Purchase;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
            $rankMonthlyRevenueByLeast = $request->input('rank_monthly_revenue_by_least', false);

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

            //Revenue
            $revenue = Invoice::where('status', 1)
                ->whereBetween('date', [$fromDate, $toDate])
                ->get()
                ->sum('total');
            $prev_revenue = Invoice::where('status', 1)
                ->whereBetween('date', [$prevFromDate, $prevToDate])
                ->get()
                ->sum('total');

            // Expense
            $expense = Purchase::where('status', 1)
                ->whereBetween('date', [$fromDate, $toDate])
                ->get()
                ->sum('total');
            $prev_expense = Purchase::where('status', 1)
                ->whereBetween('date', [$prevFromDate, $prevToDate])
                ->get()
                ->sum('total');

            //Monthly Revenue
            $invoices = Invoice::where('status', 1)
                ->whereBetween('date', [$fromDate, $toDate])
                ->get();

            $period = array_reverse(CarbonPeriod::create($fromDate, '1 month', $toDate)->toArray());
            $monthlyRevenue = collect();

            foreach ($period as $date) {
                $formattedMonth = $date->locale('id')->isoFormat('MMMM YYYY');
                $monthlyRevenue[$formattedMonth] = 0;
            }

            $invoices->groupBy(function($invoice) {
                return Carbon::parse($invoice->date)->format('Y-m'); 
            })->each(function($group, $month) use (&$monthlyRevenue) {
                $total = $group->sum(function($invoice) {
                    return $invoice->total;
                });
                $formattedMonth = Carbon::createFromFormat('Y-m', $month)->locale('id')->isoFormat('MMMM YYYY');
                $monthlyRevenue[$formattedMonth] = $total;
            });
            
            if ($rankMonthlyRevenueByLeast) {
                $monthlyRevenue = $monthlyRevenue->sort();
            } else {
                $monthlyRevenue = $monthlyRevenue->sortDesc();
            }
            
            $data = [
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'prevFromDate' => $prevFromDate,
                'prevToDate' => $prevToDate,
                'revenue' => $revenue,
                'previous_revenue' => $prev_revenue,
                'expense' => $expense,
                'previous_expense' => $prev_expense,
                'net_income' => $revenue - $expense,
                'previous_net_income' => $prev_revenue - $prev_expense,
                'approved_orders' => Invoice::where('status', 1)
                    ->whereBetween('date', [$fromDate, $toDate])
                    ->count(),
                'previous_approved_orders' => Invoice::where('status', 1)
                    ->whereBetween('date', [$prevFromDate, $prevToDate])
                    ->count(),
                'products_sold' => round(InvoiceItem::whereHas(
                    'invoice',
                    function ($query) use ($fromDate, $toDate) {
                        $query->where('status', 1)
                            ->whereBetween('date', [$fromDate, $toDate]);
                    }
                )->sum('quantity')),
                'previous_products_sold' => round(InvoiceItem::whereHas(
                    'invoice',
                    function ($query) use ($prevFromDate, $prevToDate) {
                        $query->where('status', 1)
                            ->whereBetween('date', [$prevFromDate, $prevToDate]);
                    }
                )->sum('quantity')),
                'monthly_revenue' => $monthlyRevenue,
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
