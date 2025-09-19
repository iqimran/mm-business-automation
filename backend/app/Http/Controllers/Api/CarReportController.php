<?php

namespace App\Http\Controllers\Api;

use App\Models\CarExpense;
use Illuminate\Http\Request;
use App\Services\ReportService;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class CarReportController extends Controller
{
    protected $reportService;
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get profit/loss report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profitLoss(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'make' => 'nullable|string|max:50',
                'model' => 'nullable|string|max:50',
                'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1)
            ]);

            $filters = $request->only(['make', 'model', 'year']);
            $report = $this->reportService->generateProfitLossReport(
                $request->start_date,
                $request->end_date,
                $filters
            );

            return response()->json([
                'success' => true,
                'message' => 'Profit/Loss report generated successfully',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating profit/loss report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get expense analysis report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function expenseAnalysis(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'category' => [
                    'nullable',
                    'string',
                    Rule::in(CarExpense::getCategories())
                ],
                'car_id' => 'nullable|integer|exists:cars,id'
            ]);

            $filters = $request->only(['category', 'car_id']);
            $report = $this->reportService->generateExpenseReport(
                $request->start_date,
                $request->end_date,
                $filters
            );

            return response()->json([
                'success' => true,
                'message' => 'Expense analysis report generated successfully',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating expense analysis report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sales performance report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function salesAnalysis(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date'
            ]);

            $report = $this->reportService->generateSalesReport(
                [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Sales performance report generated successfully',
                'data' => $report
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating sales performance report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export report to PDF
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportReport(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'report_type' => ['required', 'string', Rule::in(['profit_loss', 'expense_analysis', 'inventory', 'sales_performance'])],
                'format' => ['required', 'string', Rule::in(['pdf', 'excel', 'csv'])],
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'filters' => 'nullable|array'
            ]);

            // This would typically generate and return a file download
            // For now, returning success message
            return response()->json([
                'success' => true,
                'message' => 'Report export initiated successfully',
                'data' => [
                    'export_id' => uniqid('exp_'),
                    'status' => 'processing',
                    'estimated_completion' => now()->addMinutes(5)->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error initiating report export',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get report summary for quick view
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        try {
            $currentMonth = now()->startOfMonth();
            $endOfMonth = now()->endOfMonth();

            $summary = [
                'this_month' => [
                    'cars_sold' => \App\Models\Car::sold()
                        ->whereBetween('sale_date', [$currentMonth, $endOfMonth])
                        ->count(),
                    'total_revenue' => \App\Models\Car::sold()
                        ->whereBetween('sale_date', [$currentMonth, $endOfMonth])
                        ->sum('sale_price'),
                    'total_expenses' => CarExpense::byDateRange($currentMonth, $endOfMonth)
                        ->sum('amount')
                ],
                'last_month' => [
                    'cars_sold' => \App\Models\Car::sold()
                        ->whereBetween('sale_date', [$currentMonth->copy()->subMonth(), $currentMonth->copy()->subMonth()->endOfMonth()])
                        ->count(),
                    'total_revenue' => \App\Models\Car::sold()
                        ->whereBetween('sale_date', [$currentMonth->copy()->subMonth(), $currentMonth->copy()->subMonth()->endOfMonth()])
                        ->sum('sale_price')
                ],
                'year_to_date' => [
                    'cars_sold' => \App\Models\Car::sold()
                        ->whereYear('sale_date', now()->year)
                        ->count(),
                    'total_revenue' => \App\Models\Car::sold()
                        ->whereYear('sale_date', now()->year)
                        ->sum('sale_price'),
                    'total_profit' => 0 // Would calculate based on expenses
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Report summary retrieved successfully',
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving report summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
