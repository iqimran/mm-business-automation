<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarSaleController;
use App\Http\Controllers\Api\CarReportController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\CarExpenseController;
use App\Http\Controllers\Api\SalePaymentController;
use App\Http\Controllers\Api\CarExpenseCategoryController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgot']);
Route::post('/reset-password',  [AuthController::class, 'reset']);

Route::middleware('auth:api')->group(function () {
    // Authenticated user routes
    Route::get('/logout',  [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me',       [AuthController::class, 'me']);

    Route::prefix('v1')->group(function () {
        // Protected routes for car management can be added here
        // Cars
        Route::apiResource('cars', CarController::class)->middleware([
            'index'   => 'permission:cars.view',
            'show'    => 'permission:cars.view',
            'store'   => 'permission:cars.create',
            'update'  => 'permission:cars.update',
            'destroy' => 'permission:cars.delete',
        ]);
        Route::get('/cars/statistics/overview', [CarController::class, 'statistics'])->middleware('permission:cars.view');

        // Car Expenses
        Route::apiResource('car-expense-categories', CarExpenseCategoryController::class)->middleware([
            'index'   => 'permission:expenses.view',
            'show'    => 'permission:expenses.view',
            'store'   => 'permission:expenses.create',
            'update'  => 'permission:expenses.update',
            'destroy' => 'permission:expenses.delete',
        ]);
        Route::apiResource('car-expenses', CarExpenseController::class)->middleware([
            'index'   => 'permission:expenses.view',
            'show'    => 'permission:expenses.view',
            'store'   => 'permission:expenses.create',
            'update'  => 'permission:expenses.update',
            'destroy' => 'permission:expenses.delete',
        ]);

        Route::get('/expenses', [CarExpenseController::class, 'allExpenses'])->middleware('permission:expenses.view');

        // Car Sale + Payments
        Route::apiResource('car-sales', CarSaleController::class)->middleware([
            'index'   => 'permission:car-sales.view',
            'show'    => 'permission:car-sales.view',
            'store'   => 'permission:car-sales.create',
            'update'  => 'permission:car-sales.update',
            'destroy' => 'permission:car-sales.delete',
        ]);
        Route::apiResource('sale-payments', SalePaymentController::class)->middleware([
            'index'   => 'permission:sale-payments.view',
            'show'    => 'permission:sale-payments.view',
            'store'   => 'permission:sale-payments.create',
            'update'  => 'permission:sale-payments.update',
            'destroy' => 'permission:sale-payments.delete',
        ]);

        // Dashboard Routes
        Route::prefix('dashboard')->middleware('permission:reports.view')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::get('/quick-stats', [DashboardController::class, 'quickStats']);
            Route::get('/activities', [DashboardController::class, 'activities']);
            Route::get('/charts', [DashboardController::class, 'charts']);
            Route::get('/alerts', [DashboardController::class, 'alerts']);
            Route::get('/performance', [DashboardController::class, 'performance']);
        });

        // Report Routes
        Route::prefix('reports')->middleware('permission:reports.view')->group(function () {
            Route::get('/summary', [CarReportController::class, 'summary']);
            Route::get('/profit-loss', [CarReportController::class, 'profitLoss']);
            Route::get('/expense', [CarReportController::class, 'expenseAnalysis']);
            Route::get('/inventory', [CarReportController::class, 'inventory']);
            Route::get('/sales-performance', [CarReportController::class, 'salesPerformance']);
            Route::post('/export', [CarReportController::class, 'exportReport']);
            Route::get('/car-summary/{car}', [CarReportController::class, 'carSummary']);
        });

    });
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Car Automation API is running',
        'timestamp' => now(),
    ]);
});