<?php

namespace App\Http\Resources\Car;

use Illuminate\Http\Request;
use App\Http\Resources\ClientResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CarSaleResource extends JsonResource
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
            'car' => new CarResource($this->whenLoaded('car')),
            'client' => new ClientResource($this->whenLoaded('client')),
            'sale_price' => (float) $this->sale_price,
            'down_payment' => (float) $this->down_payment,
            'remaining_balance' => (float) $this->remaining_balance,
            'sale_date' => $this->sale_date->toDateString(),
            'total_paid' => (float) $this->total_paid,
            'profit_loss' => (float) $this->profit_loss,
            'payment_status' => [
                'value' => $this->payment_status->value,
                'label' => $this->payment_status->label(),
                'color' => $this->payment_status->color(),
            ],
            'sale_status' => [
                'value' => $this->sale_status->value,
                'label' => $this->sale_status->label(),
                'color' => $this->sale_status->color(),
            ],
            'commission_rate' => (float) $this->commission_rate,
            'commission_amount' => (float) $this->commission_amount,
            'notes' => $this->notes,
            'contract_terms' => $this->contract_terms,
            'warranty_terms' => $this->warranty_terms,
            'delivery_date' => $this->delivery_date?->toDateString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'payments' => SalePaymentResource::collection($this->whenLoaded('payments')),
            'payments_count' => $this->payments_count ?? $this->payments()->count(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
