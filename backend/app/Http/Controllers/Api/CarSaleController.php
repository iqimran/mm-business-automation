<?php

namespace App\Http\Controllers\Api;

use App\Models\CarSale;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Car\CarSaleService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Resources\Car\CarSaleResource;
use App\Http\Requests\Car\StoreCarSaleRequest;
use App\Http\Requests\Car\UpdateCarSaleRequest;

class CarSaleController extends Controller
{
    protected CarSaleService $carSaleService;

    public function __construct(CarSaleService $carSaleService)
    {
        $this->carSaleService = $carSaleService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $cacheKey = "sales:user:{$user->id}:" . md5(serialize($request->all())) . ':page:' . $request->get('page', 1);

        $sales = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($request, $user) {
            return QueryBuilder::for(CarSale::class)
                ->with(['car', 'client', 'user', 'payments'])
                ->withCount(['payments'])
                ->byUser($user->id)
                ->allowedFilters([
                    'sale_status',
                    'payment_status',
                    AllowedFilter::exact('car_id'),
                    AllowedFilter::exact('client_id'),
                    AllowedFilter::scope('byPaymentStatus'),
                ])
                ->allowedSorts(['sale_date', 'sale_price', 'created_at'])
                ->defaultSort('-sale_date')
                ->paginate($request->per_page ?? 15);
        });

        return response()->json([
            'success' => true,
            'data' => CarSaleResource::collection($sales),
            'meta' => [
                'total' => $sales->total(),
                'per_page' => $sales->perPage(),
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
            ]
        ]);
    }

    public function store(StoreCarSaleRequest $request)
    {
        try {
            $sale = $this->carSaleService->createSale($request->validated());

            // Cache the new sale
            Cache::put("sale:{$sale->id}", $sale, now()->addHours(12));

            // Clear sales list cache
            Cache::tags(['sales'])->flush();

            // Update real-time statistics via Redis
            Redis::lpush('recent_sales', json_encode([
                'id' => $sale->id,
                'car' => $sale->car->full_name,
                'client' => $sale->client->full_name,
                'amount' => $sale->sale_price,
                'date' => $sale->sale_date->toDateString(),
            ]));
            Redis::ltrim('recent_sales', 0, 9); // Keep only last 10

            return response()->json([
                'success' => true,
                'message' => 'Car sale created successfully',
                'data' => new CarSaleResource($sale->load(['car', 'client']))
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create car sale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(CarSale $carSale)
    {
        $cacheKey = "sale:{$carSale->id}:details";

        $saleData = Cache::remember($cacheKey, now()->addHours(6), function () use ($carSale) {
            return $carSale->load(['car', 'client', 'payments' => function ($query) {
                $query->orderBy('payment_date', 'desc');
            }]);
        });

        return response()->json([
            'success' => true,
            'data' => new CarSaleResource($saleData)
        ]);
    }

    public function update(UpdateCarSaleRequest $request, CarSale $carSale)
    {
       try {
            $updatedSale = $this->carSaleService->updateSale($carSale, $request->validated());

            // Update cache
            Cache::put("sale:{$carSale->id}", $updatedSale, now()->addHours(12));
            Cache::forget("sale:{$carSale->id}:details");
            Cache::tags(['sales'])->flush();

            return response()->json([
                'success' => true,
                'message' => 'Car sale updated successfully',
                'data' => new CarSaleResource($updatedSale->load(['car', 'client']))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update car sale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(CarSale $carSale)
    {
        try {
            $this->carSaleService->deleteSale($carSale);

            // Clear cache
            Cache::forget("sale:{$carSale->id}");
            Cache::forget("sale:{$carSale->id}:details");
            Cache::tags(['sales'])->flush();

            return response()->json([
                'success' => true,
                'message' => 'Car sale deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete car sale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function markAsCompleted(CarSale $carSale)
    {

        try {
            $completedSale = $this->carSaleService->markAsCompleted($carSale);

            // Update cache and Redis stats
            Cache::forget("sale:{$carSale->id}:details");
            Cache::tags(['sales', 'dashboard'])->flush();

            // Update Redis statistics
            Redis::incr('stats:completed_sales:' . date('Y-m'));
            Redis::incrbyfloat('stats:sales_revenue:' . date('Y-m'), $carSale->sale_price);

            return response()->json([
                'success' => true,
                'message' => 'Car sale marked as completed',
                'data' => new CarSaleResource($completedSale)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete car sale',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
