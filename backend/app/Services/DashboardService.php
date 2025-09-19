<?php
namespace App\Services;

use Carbon\Carbon;
use App\Models\Car;
use App\Models\User;
use App\Models\CarSale;
use App\Enums\CarStatus;
use App\Models\CarExpense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class DashboardService
{
    /**
    * Get comprehensive dashboard statistics
    */
    public function getDashboardStats($period = 'month')
    {
        $cacheKey = "dashboard_stats_{$period}";

        return Cache::remember($cacheKey, 600, function () use ($period) {
            $dateRange = $this->getDateRange($period);

            return [
            'overview' => $this->getOverviewStats($dateRange),
            'financial' => $this->getFinancialStats($dateRange),
            'inventory' => $this->getInventoryStats(),
            'recent_activities' => $this->getRecentActivities(),
            'charts_data' => $this->getChartsData($period),
            'alerts' => $this->getAlerts()
            ];
        });
    }

    /**
    * Get overview statistics
    */
    private function getOverviewStats($dateRange)
    {
        $totalCars = Car::count();
        $availableCars = Car::where('status', CarStatus::ACTIVE)->count();
        $inactiveCars = Car::where('status', CarStatus::INACTIVE)->count();
        $soldCars = Car::where('status', CarStatus::SOLD)->count();
        $maintenanceCars = Car::where('status', CarStatus::MAINTENANCE)->count();

        // Period comparisons
        $soldThisPeriod = CarSale::whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
        ->count();

        $previousPeriod = $this->getPreviousPeriodRange($dateRange);
        $soldPreviousPeriod = CarSale::whereBetween('sale_date', [$previousPeriod['start'], $previousPeriod['end']])
        ->count();

        $salesGrowth = $soldPreviousPeriod > 0 ?
        (($soldThisPeriod - $soldPreviousPeriod) / $soldPreviousPeriod) * 100 : 0;

        return [
            'total_cars' => $totalCars,
            'available_cars' => $availableCars,
            'inactive_cars' => $inactiveCars,
            'sold_cars' => $soldCars,
            'maintenance_cars' => $maintenanceCars,
            'sold_this_period' => $soldThisPeriod,
            'sales_growth' => round($salesGrowth, 1),
            'inventory_turnover' => $totalCars > 0 ? round(($soldCars / $totalCars) * 100, 1) : 0
        ];
    }

    /**
    * Get financial statistics
    */
    private function getFinancialStats($dateRange)
    {
        // Revenue and profit calculations
        $soldCars = Car::sold()
        ->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
        ->with('expenses')
        ->get();

        $totalRevenue = $soldCars->sum('sale_price');
        $totalCost = $soldCars->sum('purchase_price');
        $totalExpenses = $soldCars->sum(function ($car) {
            return $car->expenses->sum('amount');
        });

        $grossProfit = $totalRevenue - $totalCost;
        $netProfit = $grossProfit - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        // Previous period comparison
        $previousPeriod = $this->getPreviousPeriodRange($dateRange);
        $previousSoldCars = Car::sold()
        ->whereBetween('sale_date', [$previousPeriod['start'], $previousPeriod['end']])
        ->with('expenses')
        ->get();

        $previousRevenue = $previousSoldCars->sum('sale_price');
        $previousNetProfit = $previousRevenue - $previousSoldCars->sum('purchase_price') -
        $previousSoldCars->sum(function ($car) {
            return $car->expenses->sum('amount');
        });

        $revenueGrowth = $previousRevenue > 0 ?
        (($totalRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;

        $profitGrowth = $previousNetProfit > 0 ?
        (($netProfit - $previousNetProfit) / $previousNetProfit) * 100 : 0;

        // Current month expenses
        $currentMonthExpenses = CarExpense::whereBetween('expense_date', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ])->sum('amount');

        return [
            'total_revenue' => $totalRevenue,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'profit_margin' => round($profitMargin, 2),
            'total_expenses' => $totalExpenses,
            'current_month_expenses' => $currentMonthExpenses,
            'revenue_growth' => round($revenueGrowth, 1),
            'profit_growth' => round($profitGrowth, 1),
            'average_sale_price' => $soldCars->count() > 0 ? $totalRevenue / $soldCars->count() : 0,
            'average_profit_per_car' => $soldCars->count() > 0 ? $netProfit / $soldCars->count() : 0
        ];
    }

    /**
    * Get inventory statistics
    */
    private function getInventoryStats()
    {
        $totalInventoryValue = Car::available()
        ->with('expenses')
        ->get()
        ->sum(function ($car) {
        return $car->purchase_price + $car->total_expenses;
        });

        $averageInventoryAge = Car::available()
        ->whereNotNull('purchase_date')
        ->get()
        ->avg(function ($car) {
        return $car->purchase_date->diffInDays(Carbon::now());
        });

        // Top makes
        $topMakes = Car::available()
        ->select('make', DB::raw('COUNT(*) as count'))
        ->groupBy('make')
        ->orderByDesc('count')
        ->limit(5)
        ->get();

        // Condition breakdown
        $conditionBreakdown = Car::available()
        ->select('condition', DB::raw('COUNT(*) as count'))
        ->groupBy('condition')
        ->get()
        ->mapWithKeys(function ($item) {
        return [$item->condition => $item->count];
        });

        return [
            'total_inventory_value' => $totalInventoryValue,
            'average_inventory_age' => round($averageInventoryAge ?? 0),
            'top_makes' => $topMakes,
            'condition_breakdown' => $conditionBreakdown,
            'aging_analysis' => $this->getAgingAnalysis()
        ];
    }


    /**
    * Get recent activities
    */
    private function getRecentActivities()
    {
        $recentCars = Car::with('client')
        ->orderByDesc('created_at')
        ->limit(5)
        ->get();

        $recentExpenses = CarExpense::orderByDesc('expense_date')
        ->limit(5)
        ->get();

        $recentUsers = User::orderByDesc('created_at')
        ->limit(5)
        ->get();

        return [
            'recent_cars' => $recentCars,
            'recent_expenses' => $recentExpenses,
            'recent_users' => $recentUsers
        ];
    }

    /**
    * Get data for charts
    */
    private function getChartsData($period)
    {
        return [
            'sales_trend' => $this->getSalesTrendData($period),
            'revenue_vs_profit' => $this->getRevenueVsProfitData($period),
            'inventory_status' => $this->getInventoryStatusData(),
            'expenses_breakdown' => $this->getExpensesBreakdownData($period)
        ];
    }


    /**
     * Get notifications
     */
    private function getNotifications()
    {
        return Notification::all();
    }
    /**
     * Get date range based on period
     */
    private function getDateRange($period)
    {
        $now = Carbon::now();
        switch ($period) {
            case 'week':
                return [
                    'start' => $now->startOfWeek(),
                    'end' => $now->endOfWeek()
                ];
            case 'month':
                return [
                    'start' => $now->startOfMonth(),
                    'end' => $now->endOfMonth()
                ];
            case 'quarter':
                return [
                    'start' => $now->firstOfQuarter(),
                    'end' => $now->lastOfQuarter()
                ];
            case 'year':
                return [
                    'start' => $now->startOfYear(),
                    'end' => $now->endOfYear()
                ];
            default:
                return [
                    'start' => $now->startOfMonth(),
                    'end' => $now->endOfMonth()
                ];
        }
    }

    private function getPreviousPeriodRange($currentRange)
    {
        $start = Carbon::parse($currentRange['start']);
        $end = Carbon::parse($currentRange['end']);
        $diffInDays = $start->diffInDays($end);

        return [
            'start' => $start->subDays($diffInDays + 1),
            'end' => $end->subDays($diffInDays + 1)
        ];
    }

    private function getSalesTrendData($period)
    {
        $dateFormat = $this->getDateFormat($period);
        $salesData = Car::sold()
        ->select(DB::raw("DATE_FORMAT(sale_date, '$dateFormat') as period"), DB::raw('COUNT(*) as count'))
        ->groupBy('period')
        ->orderBy('period')
        ->get();

        return $salesData->map(function ($item) {
            return [
                'period' => $item->period,
                'sales_count' => $item->count
            ];
        });
    }

    private function getDateFormat($period)
    {
        switch ($period) {
            case 'week':
                return '%Y-%u'; // Year-Week
            case 'month':
                return '%Y-%m'; // Year-Month
            case 'quarter':
                return '%Y-Q%q'; // Year-Quarter
            case 'year':
                return '%Y'; // Year
            default:
                return '%Y-%m'; // Default to Year-Month
        }
    }

    private function getRevenueVsProfitData($period)
    {
        $dateFormat = $this->getDateFormat($period);
        $data = Car::sold()
        ->select(DB::raw("DATE_FORMAT(sale_date, '$dateFormat') as period"),
                 DB::raw('SUM(sale_price) as total_revenue'),
                 DB::raw('SUM(purchase_price) as total_cost'))
        ->groupBy('period')
        ->orderBy('period')
        ->get()
        ->map(function ($item) {
            $grossProfit = $item->total_revenue - $item->total_cost;
            return [
                'period' => $item->period,
                'total_revenue' => $item->total_revenue,
                'gross_profit' => $grossProfit
            ];
        });

        return $data;
    }

    private function getInventoryStatusData()
    {
        $data = Car::select('status', DB::raw('COUNT(*) as count'))
        ->groupBy('status')
        ->get()
        ->mapWithKeys(function ($item) {
            return [$item->status => $item->count];
        });

        return $data;
    }

    private function getExpensesBreakdownData($period)
    {
        $dateRange = $this->getDateRange($period);
        $data = CarExpense::whereBetween('expense_date', [$dateRange['start'], $dateRange['end']])
        ->select('category', DB::raw('SUM(amount) as total_amount'))
        ->groupBy('category')
        ->get();

        return $data;
    }

    private function getAgingAnalysis()
    {
        $now = Carbon::now();
        $agingBuckets = [
            '0-30 days' => 0,
            '31-60 days' => 0,
            '61-90 days' => 0,
            '91-120 days' => 0,
            '120+ days' => 0
        ];

        $cars = Car::available()->whereNotNull('purchase_date')->get();

        foreach ($cars as $car) {
            $ageInDays = $car->purchase_date->diffInDays($now);

            if ($ageInDays <= 30) {
                $agingBuckets['0-30 days']++;
            } elseif ($ageInDays <= 60) {
                $agingBuckets['31-60 days']++;
            } elseif ($ageInDays <= 90) {
                $agingBuckets['61-90 days']++;
            } elseif ($ageInDays <= 120) {
                $agingBuckets['91-120 days']++;
            } else {
                $agingBuckets['120+ days']++;
            }
        }

        return $agingBuckets;
    }
}