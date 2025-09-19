<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarExpense;
use App\Models\CarSale;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ReportService
{
    public function generateSalesReport(array $filters = []): array
    {
        $cacheKey = 'report:sales:' . md5(serialize($filters));

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($filters) {
            $query = CarSale::with(['car', 'client', 'payments']);

            // Apply filters
            if (isset($filters['start_date']) && isset($filters['end_date'])) {
                $query->whereBetween('sale_date', [$filters['start_date'], $filters['end_date']]);
            }

            if (isset($filters['status'])) {
                $query->where('sale_status', $filters['status']);
            }

            if (isset($filters['payment_status'])) {
                $query->where('payment_status', $filters['payment_status']);
            }

            $sales = $query->orderBy('sale_date', 'desc')->get();

            // Calculate summary statistics
            $totalSales = $sales->count();
            $totalRevenue = $sales->sum('sale_price');
            $totalPaid = $sales->sum(function ($sale) {
                return $sale->payments->where('status', 'completed')->sum('amount');
            });
            $totalPending = $sales->sum('remaining_balance');
            $averageSalePrice = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

            // Group by month for trend analysis
            $monthlyTrends = $sales->groupBy(function ($sale) {
                return $sale->sale_date->format('Y-m');
            })->map(function ($monthlySales, $month) {
                return [
                    'month' => Carbon::createFromFormat('Y-m', $month)->format('M Y'),
                    'count' => $monthlySales->count(),
                    'revenue' => $monthlySales->sum('sale_price'),
                    'average_price' => $monthlySales->avg('sale_price'),
                ];
            })->values();

            // Payment status breakdown
            $paymentStatusBreakdown = $sales->groupBy('payment_status')->map(function ($statusSales, $status) {
                return [
                    'status' => $status,
                    'count' => $statusSales->count(),
                    'total_amount' => $statusSales->sum('sale_price'),
                    'percentage' => 0, // Will be calculated below
                ];
            });

            // Calculate percentages
            foreach ($paymentStatusBreakdown as $key => $breakdown) {
                $paymentStatusBreakdown[$key]['percentage'] = $totalSales > 0
                    ? round(($breakdown['count'] / $totalSales) * 100, 2)
                    : 0;
            }

            return [
                'summary' => [
                    'total_sales' => $totalSales,
                    'total_revenue' => (float) $totalRevenue,
                    'total_paid' => (float) $totalPaid,
                    'total_pending' => (float) $totalPending,
                    'average_sale_price' => (float) $averageSalePrice,
                    'payment_completion_rate' => $totalRevenue > 0 ? round(($totalPaid / $totalRevenue) * 100, 2) : 0,
                ],
                'monthly_trends' => $monthlyTrends,
                'payment_status_breakdown' => $paymentStatusBreakdown->values(),
                'sales_details' => $sales->map(function ($sale) {
                    return [
                        'id' => $sale->uuid,
                        'car' => $sale->car->full_name,
                        'client' => $sale->client->full_name,
                        'sale_price' => (float) $sale->sale_price,
                        'total_paid' => (float) $sale->total_paid,
                        'remaining_balance' => (float) $sale->remaining_balance,
                        'sale_date' => $sale->sale_date->toDateString(),
                        'payment_status' => $sale->payment_status->label(),
                        'sale_status' => $sale->sale_status->label(),
                    ];
                }),
            ];
        });
    }

    public function generateExpenseReport($startDate = null, $endDate = null, array $filters = []): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();

        $cacheKey = "expense_report_" . md5($startDate . $endDate . serialize($filters));


        return Cache::remember($cacheKey, now()->addHours(2), function () use ($startDate, $endDate, $filters) {
            $query = CarExpense::with(['car', 'category']);

            // Apply filters
            if (isset($startDate) && isset($endDate)) {
                $query->whereBetween('expense_date', [$startDate, $endDate]);
            }

            if (isset($filters['car_id'])) {
                $query->where('car_id', $filters['car_id']);
            }

            if (isset($filters['category_id'])) {
                $query->where('expense_category_id', $filters['category_id']);
            }

            $expenses = $query->orderBy('expense_date', 'desc')->get();

            // Calculate summary statistics
            $totalExpenses = $expenses->sum('amount');
            $averageExpense = $expenses->count() > 0 ? $totalExpenses / $expenses->count() : 0;
            $expenseCount = $expenses->count();

            // Group by category
            $categoryBreakdown = $expenses->groupBy('category.name')->map(function ($categoryExpenses, $category) {
                $amount = $categoryExpenses->sum('amount');
                $count = $categoryExpenses->count();
                return [
                    'category' => $category,
                    'count' => $categoryExpenses->count(),
                    'total_amount' => $amount,
                    'average_amount' => $count > 0 ? $amount / $count : $categoryExpenses->avg('amount'),
                    'percentage' => 0, // Will be calculated below
                ];
            });

            // Calculate percentages
            foreach ($categoryBreakdown as $key => $breakdown) {
                $categoryBreakdown[$key]['percentage'] = $totalExpenses > 0
                    ? round(($breakdown['total_amount'] / $totalExpenses) * 100, 2)
                    : 0;
            }

            // Group by month
            $monthlyTrends = $expenses->groupBy(function ($expense) {
                return $expense->expense_date->format('Y-m');
            })->map(function ($monthlyExpenses, $month) {
                return [
                    'month' => Carbon::createFromFormat('Y-m', $month)->format('M Y'),
                    'count' => $monthlyExpenses->count(),
                    'total_amount' => $monthlyExpenses->sum('amount'),
                    'average_amount' => $monthlyExpenses->avg('amount'),
                ];
            })->values();

            // Car-wise expenses
            $carWiseExpenses = $expenses->groupBy('car_id')->map(function ($carExpenses) {
                $car = $carExpenses->first()->car;
                return [
                    'car' => $car ? "{$car->full_name} {$car->model} {$car->year}" : 'Unknown',
                    'expense_count' => $carExpenses->count(),
                    'total_expenses' => $carExpenses->sum('amount'),
                    'average_expense' => $carExpenses->avg('amount'),
                    'categories' => $carExpenses->groupBy('category')->map(function ($catExpenses, $category) {
                        return [
                            'category' => $category,
                            'amount' => $catExpenses->sum('amount'),
                            'count' => $catExpenses->count()
                        ];
                    })->values()
                ];
            })->sortByDesc('total_expenses')->values();

            return [
                'summary' => [
                    'total_expenses' => (float) $totalExpenses,
                    'total_transactions' => $expenseCount,
                    'average_expense' => (float) $averageExpense,
                    'unique_categories' => $expenses->pluck('category')->unique()->count(),
                    'unique_cars' => $expenses->pluck('car_id')->unique()->count()
                ],
                'category_breakdown' => $categoryBreakdown->values(),
                'monthly_trends' => $monthlyTrends,
                'car_wise_expenses' => $carWiseExpenses,
                'expense_details' => $expenses->map(function ($expense) {
                    return [
                        'id' => $expense->id,
                        'title' => $expense->title,
                        'car' => $expense->car->full_name,
                        'category' => $expense->category->name,
                        'amount' => (float) $expense->amount,
                        'expense_date' => $expense->expense_date->toDateString()
                    ];
                }),
            ];
        });
    }

    public function generateProfitLossReport($startDate = null, $endDate = null, array $filters = []): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();

        $cacheKey = "profit_loss_report_" . md5($startDate . $endDate . serialize($filters));

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($startDate, $endDate, $filters) {

            // Get cars sold in the period
            $soldCarsQuery = CarSale::whereBetween('sale_date', [$startDate, $endDate]);

            if (isset($filters['car_id'])) {
                $soldCarsQuery->where('car_id', $filters['car_id']);
            }
            if (isset($filters['client_id'])) {
                $soldCarsQuery->where('client_id', $filters['client_id']);
            }
            if (isset($filters['payment_status'])) {
                $soldCarsQuery->where('payment_status', $filters['payment_status']);
            }
            if (isset($filters['sale_status'])) {
                $soldCarsQuery->where('sale_status', $filters['sale_status']);
            }
            if (!empty($filters['model'])) {
                $soldCarsQuery->where('model', $filters['model']);
            }
            if (!empty($filters['year'])) {
                $soldCarsQuery->where('year', $filters['year']);
            }

            $soldCars = $soldCarsQuery->with(['car.expenses', 'payments'])->get();

            // Summary calculations
            $totalRevenue = $soldCars->sum('sale_price');
            $totalCost = $soldCars->sum(function ($sale) {
                $car = $sale->car;
                $purchasePrice = (float) $car->purchase_price;
                $totalExpenses = (float) $car->expenses->sum('amount');
                return $purchasePrice + $totalExpenses;
            });

            $totalCommission = $soldCars->sum('commission_amount');
            $netRevenue = $totalRevenue - $totalCommission;
            $totalProfitLoss = $netRevenue - $totalCost;
            $totalProfitMargin = $netRevenue > 0 ? ($totalProfitLoss / $netRevenue) * 100 : 0;
            $grossProfit = $totalRevenue - $totalCost;
            $netProfit = $grossProfit - $totalCommission;
            $totalExpenses = $soldCars->sum(function ($sale) {
                return $sale->car->expenses->sum('amount');
            });
            $profitMargin = $netRevenue > 0 ? ($totalProfitLoss / $netRevenue) * 100 : 0;
            $averageProfitPerCar = $soldCars->count() > 0 ? $totalProfitLoss / $soldCars->count() : 0;

            // Car-wise breakdown
            $carBreakdown = $soldCars->map(function ($sale) {
                $car = $sale->car;

                // Calculate total cost (purchase price + expenses)
                $purchasePrice = (float) $car->purchase_price;
                $totalExpenses = (float) $car->expenses->sum('amount');
                $totalCost = $purchasePrice + $totalExpenses;

                // Calculate revenue and commission
                $salePrice = (float) $sale->sale_price;
                $commission = (float) $sale->commission_amount;
                $netRevenue = $salePrice - $commission;

                // Calculate profit/loss
                $profitLoss = $netRevenue - $totalCost;
                $profitMargin = $netRevenue > 0 ? ($profitLoss / $netRevenue) * 100 : 0;

                return [
                    'id' => $car->id,
                    'model' => $car->model,
                    'year' => $car->year,
                    'car' => $car->full_name,
                    'sale_id' => $sale->id,
                    'client' => $sale->client->full_name,
                    'sale_date' => $sale->sale_date->toDateString(),
                    'purchase_price' => $purchasePrice,
                    'total_expenses' => $totalExpenses,
                    'total_cost' => $totalCost,
                    'sale_price' => $salePrice,
                    'commission' => $commission,
                    'net_revenue' => $netRevenue,
                    'profit_loss' => $profitLoss,
                    'profit_margin' => round($profitMargin, 2),
                    'is_profitable' => $profitLoss > 0,
                ];
            });

            return [
                'summary' => [
                    'total_cars_sold' => $soldCars->count(),
                    'total_revenue' => $totalRevenue,
                    'total_cost' => $totalCost,
                    'total_expenses' => $totalExpenses,
                    'gross_profit' => $grossProfit,
                    'net_profit' => $netProfit,
                    'profit_margin' => round($profitMargin, 2),
                    'average_profit_per_car' => $averageProfitPerCar
                ],
                'car_breakdown' => $carBreakdown,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d')
                ]
            ];
        });
    }
}