<?php

namespace App\Http\Controllers\Api;


use App\Models\SalePayment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Car\SalePaymentService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Resources\Car\SalePaymentResource;
use App\Http\Requests\Car\StoreSalePaymentRequest;


class SalePaymentController extends Controller
{
    protected SalePaymentService $paymentService;

    public function __construct(SalePaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $cacheKey = "payments:user:{$user->id}:" . md5(serialize($request->all())) . ':page:' . $request->get('page', 1);

        $payments = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($request) {
            return QueryBuilder::for(SalePayment::class)
                ->with(['carSale.car', 'carSale.client', 'user'])
                ->allowedFilters([
                    'status',
                    'payment_method',
                    AllowedFilter::exact('car_sale_id'),
                    AllowedFilter::scope('byDate'),
                ])
                ->allowedSorts(['payment_date', 'amount', 'created_at'])
                ->defaultSort('-payment_date')
                ->paginate($request->per_page ?? 15);
        });

        return response()->json([
            'success' => true,
            'data' => SalePaymentResource::collection($payments),
            'meta' => [
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
            ]
        ]);
    }

    public function store(StoreSalePaymentRequest $request)
    {
        try {
            $payment = $this->paymentService->createPayment($request->validated(), $request->user());

            // Update Redis for real-time stats
            Redis::lpush('recent_payments', json_encode([
                'id' => $payment->uuid,
                'amount' => $payment->amount,
                'method' => $payment->payment_method->label(),
                'car_sale_id' => $payment->car_sale_id,
                'date' => $payment->payment_date->toDateString(),
            ]));
            Redis::ltrim('recent_payments', 0, 9); // Keep only last 10

            // Update daily payment stats
            Redis::incrbyfloat('stats:payments:' . $payment->payment_date->format('Y-m-d'), $payment->amount);

            // Clear related caches
            Cache::tags(['payments', 'sales'])->flush();
            Cache::forget("sale:{$payment->carSale->id}:details");

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => new SalePaymentResource($payment->load(['carSale.car', 'carSale.client']))
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(SalePayment $salePayment)
    {
        return response()->json([
            'success' => true,
            'data' => new SalePaymentResource($salePayment->load(['carSale.car', 'carSale.client']))
        ]);
    }

    public function update(Request $request, SalePayment $salePayment)
    {
        $request->validate([
            'status' => 'sometimes|in:pending,completed,failed,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $updatedPayment = $this->paymentService->updatePayment($salePayment, $request->validated());

            // Clear caches
            Cache::tags(['payments', 'sales'])->flush();
            Cache::forget("sale:{$salePayment->carSale->id}:details");

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully',
                'data' => new SalePaymentResource($updatedPayment)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(SalePayment $salePayment)
    {
        try {
            $this->paymentService->deletePayment($salePayment);

            // Clear caches
            Cache::tags(['payments', 'sales'])->flush();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
