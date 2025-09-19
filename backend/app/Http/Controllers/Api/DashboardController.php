<?php

namespace App\Http\Controllers\Api;

use App\Enums\CarStatus;
use App\Models\CarExpense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\DashboardService;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get comprehensive dashboard statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'period' => ['nullable', 'string', Rule::in(['week', 'month', 'quarter', 'year'])]
            ]);

            $period = $request->get('period', 'month');
            $stats = $this->dashboardService->getDashboardStats($period);

            return response()->json([
                'success' => true,
                'message' => 'Dashboard statistics retrieved successfully',
                'data' => $stats,
                'period' => $period,
                'generated_at' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quick statistics for dashboard cards
     *
     * @return JsonResponse
     */
    public function quickStats(): JsonResponse
    {
        try {
            $stats = [
                'total_cars' => \App\Models\Car::count(),
                'available_cars' => \App\Models\Car::available()->count(),
                'cars_sold_this_month' => \App\Models\Car::sold()
                    ->whereMonth('sale_date', now()->month)
                    ->whereYear('sale_date', now()->year)
                    ->count(),
                'monthly_revenue' => \App\Models\Car::sold()
                    ->whereMonth('sale_date', now()->month)
                    ->whereYear('sale_date', now()->year)
                    ->sum('sale_price'),
                'monthly_expenses' => CarExpense::whereMonth('expense_date', now()->month)
                    ->whereYear('expense_date', now()->year)
                    ->sum('amount'),
                'maintenance_cars' => \App\Models\Car::where('status', CarStatus::MAINTENANCE)->count()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Quick statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving quick statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent activities for dashboard
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function activities(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'limit' => 'nullable|integer|min:1|max:50'
            ]);

            $limit = $request->get('limit', 20);

            // Get recent activities from different sources
            $activities = collect();

            // Recent car additions
            $recentCars = \App\Models\Car::latest()
                ->limit($limit / 3)
                ->get()
                ->map(function ($car) {
                    return [
                        'id' => $car->id,
                        'type' => 'car_added',
                        'title' => 'New Car Added',
                        'description' => "{$car->make} {$car->model} {$car->year} added to inventory",
                        'timestamp' => $car->created_at,
                        'data' => [
                            'car_id' => $car->id,
                            'make' => $car->make,
                            'model' => $car->model,
                            'year' => $car->year,
                            'purchase_price' => $car->purchase_price
                        ]
                    ];
                });

            // Recent sales
            $recentSales = \App\Models\Car::sold()
                ->latest('sale_date')
                ->limit($limit / 3)
                ->get()
                ->map(function ($car) {
                    return [
                        'id' => $car->id,
                        'type' => 'car_sold',
                        'title' => 'Car Sold',
                        'description' => "{$car->make} {$car->model} sold for $" . number_format($car->sale_price, 2),
                        'timestamp' => $car->updated_at,
                        'data' => [
                            'car_id' => $car->id,
                            'sale_price' => $car->sale_price,
                            'profit' => $car->net_profit
                        ]
                    ];
                });

            // Recent expenses
            $recentExpenses = CarExpense::with('car')
                ->latest()
                ->limit($limit / 3)
                ->get()
                ->map(function ($expense) {
                    $carInfo = $expense->car ? "{$expense->car->make} {$expense->car->model}" : "Unknown Car";
                    return [
                        'id' => $expense->id,
                        'type' => 'expense_added',
                        'title' => 'New Expense',
                        'description' => "{$expense->category} expense of $" . number_format($expense->amount, 2) . " for {$carInfo}",
                        'timestamp' => $expense->created_at,
                        'data' => [
                            'expense_id' => $expense->id,
                            'amount' => $expense->amount,
                            'category' => $expense->category,
                            'car_id' => $expense->car_id
                        ]
                    ];
                });

            $allActivities = $activities
                ->merge($recentCars)
                ->merge($recentSales)
                ->merge($recentExpenses)
                ->sortByDesc('timestamp')
                ->take($limit)
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Recent activities retrieved successfully',
                'data' => $allActivities,
                'count' => $allActivities->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving recent activities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get chart data for dashboard visualizations
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function charts(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'period' => ['nullable', 'string', Rule::in(['week', 'month', 'quarter', 'year'])],
                'chart_type' => ['nullable', 'string', Rule::in(['sales', 'expenses', 'profit', 'inventory'])]
            ]);

            $period = $request->get('period', 'month');
            $chartType = $request->get('chart_type', 'all');

            $stats = $this->dashboardService->getDashboardStats($period);
            $chartsData = $stats['charts_data'];

            // Filter chart data if specific type requested
            if ($chartType !== 'all' && isset($chartsData[$chartType . '_trend'])) {
                $chartsData = [$chartType . '_trend' => $chartsData[$chartType . '_trend']];
            }

            return response()->json([
                'success' => true,
                'message' => 'Chart data retrieved successfully',
                'data' => $chartsData,
                'period' => $period
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving chart data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get alerts and notifications for dashboard
     *
     * @return JsonResponse
     */
    public function alerts(): JsonResponse
    {
        try {
            $stats = $this->dashboardService->getDashboardStats('month');
            $alerts = $stats['alerts'];

            return response()->json([
                'success' => true,
                'message' => 'Dashboard alerts retrieved successfully',
                'data' => $alerts,
                'count' => count($alerts)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance metrics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function performance(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'period' => ['nullable', 'string', Rule::in(['week', 'month', 'quarter', 'year'])]
            ]);

            $period = $request->get('period', 'month');
            $stats = $this->dashboardService->getDashboardStats($period);

            $performance = [
                'financial' => $stats['financial'],
                'inventory_turnover' => $stats['overview']['inventory_turnover'],
                'average_days_to_sell' => $stats['inventory']['average_inventory_age'],
                'profit_trends' => $stats['charts_data']['profit_trend'],
                'top_performers' => $this->getTopPerformingCars(),
                'kpis' => [
                    'gross_profit_margin' => $stats['financial']['profit_margin'],
                    'sales_growth' => $stats['overview']['sales_growth'],
                    'revenue_growth' => $stats['financial']['revenue_growth'],
                    'expense_ratio' => $stats['financial']['total_revenue'] > 0 ?
                        ($stats['financial']['total_expenses'] / $stats['financial']['total_revenue']) * 100 : 0
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Performance metrics retrieved successfully',
                'data' => $performance,
                'period' => $period
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving performance metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top performing cars
     *
     * @return \Illuminate\Support\Collection
     */
    private function getTopPerformingCars()
    {
        return \App\Models\Car::sold()
            ->with('expenses')
            ->whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year)
            ->get()
            ->map(function ($car) {
                $profit = $car->net_profit;
                $margin = $car->sale_price > 0 ? ($profit / $car->sale_price) * 100 : 0;

                return [
                    'car' => "{$car->make} {$car->model} {$car->year}",
                    'profit' => $profit,
                    'profit_margin' => round($margin, 2),
                    'sale_price' => $car->sale_price,
                    'days_in_inventory' => $car->purchase_date && $car->sale_date ?
                        $car->purchase_date->diffInDays($car->sale_date) : 0
                ];
            })
            ->sortByDesc('profit_margin')
            ->take(5)
            ->values();
    }
}

