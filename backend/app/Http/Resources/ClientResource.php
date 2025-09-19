<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'country' => $this->country,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'license_number' => $this->license_number,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            //'total_purchases' => (float) $this->total_purchases,
            //'pending_payments' => (float) $this->pending_payments,
            'sales_count' => $this->sales_count ?? $this->sales()->count(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString()
        ];
    }
}
