<?php

namespace App\Http\Resources\Car;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalePaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'car_sale' => new CarSaleResource($this->whenLoaded('carSale')),
            'payment_method' => [
                'value' => $this->payment_method->value,
                'label' => $this->payment_method->label(),
                'icon' => $this->payment_method->icon(),
            ],
            'amount' => (float) $this->amount,
            'payment_date' => $this->payment_date->toDateString(),
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'transaction_reference' => $this->transaction_reference,
            'notes' => $this->notes,
            'receipt_image' => $this->receipt_image ? asset('storage/' . $this->receipt_image) : null,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
