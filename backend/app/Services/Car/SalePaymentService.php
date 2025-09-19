<?php

namespace App\Services\Car;

use App\Models\SalePayment;
use Illuminate\Database\Eloquent\Collection;

class SalePaymentService
{
    public function getAll(): Collection
    {
        return SalePayment::all();
    }

    public function createPayment(array $data): SalePayment
    {
        return SalePayment::create($data);
    }

    public function find(SalePayment $carSale): SalePayment
    {
        return $carSale;
    }

    public function updatePayment(SalePayment $carSale, array $data): SalePayment
    {
        $carSale->update($data);
        return $carSale;
    }

    public function deletePayment(SalePayment $carSale): void
    {
        $carSale->delete();
    }


    public function markAsCompleted(SalePayment $carSale): SalePayment
    {
        $carSale->status = 'completed';
        $carSale->save();
        return $carSale;
    }
}
