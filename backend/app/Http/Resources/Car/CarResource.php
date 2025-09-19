<?php

namespace App\Http\Resources\Car;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
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
            'name' => $this->name,
            'model' => $this->model,
            'year' => $this->year,
            'license_plate' => $this->license_plate,
            'color' => $this->color,
            'mileage' => $this->mileage,
            'purchase_price' => $this->purchase_price,
            'current_value' => $this->current_value,
            'purchase_date' => $this->purchase_date,
            'registration_date' => $this->registration_date,
            'fitness_date' => $this->fitness_date,
            'tax_token_date' => $this->tax_token_date,
            'insurance_expiry' => $this->insurance_expiry,
            'registration_expiry' => $this->registration_expiry,
            'last_service_date' => $this->last_service_date,
            'next_service_due' => $this->next_service_due,
            'notes' => $this->notes,
            'status' => $this->status,
            'images' => $this->images,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
