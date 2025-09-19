<?php

namespace App\Services\Car;

use App\Models\Car;
use App\Models\CarSale;
use App\Enums\CarStatus;
use App\Services\ClientService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class CarSaleService
{

    protected ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function getAll(): Collection
    {
        return CarSale::all();
    }

    public function createSale(array $data): CarSale
    {
        return DB::transaction(
            function () use ($data) {

                // Handle client creation or selection
                if (!isset($data['client_id']) && isset($data['client'])) {
                    $client = $this->clientService->createClient($data['client']);
                    $data['client_id'] = $client->id;
                }

                $data['remaining_balance'] = $data['sale_price'] - ($data['down_payment'] ?? 0);

                if (isset($data['commission_rate']) && $data['commission_rate'] > 0) {
                    $data['commission_amount'] = ($data['sale_price'] * $data['commission_rate']) / 100;
                }

                $carSale = CarSale::create($data);

                // Update car status
                $car = Car::find($data['car_id']);
                $car->update(['status' => CarStatus::SOLD]);

                return $carSale;
            }
        );
    }

    public function find(CarSale $carSale): CarSale
    {
        return $carSale;
    }

    public function updateSale(CarSale $carSale, array $data): CarSale
    {
        $carSale->update($data);
        return $carSale;
    }

    public function deleteSale(CarSale $carSale): void
    {
        $carSale->delete();
    }

    public function markAsCompleted(CarSale $carSale): CarSale
    {
        $carSale->status = 'completed';
        $carSale->save();
        return $carSale;
    }
}
