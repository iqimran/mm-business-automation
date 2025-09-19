<?php

namespace App\Http\Resources\Car;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Http\Resources\Car\CarResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CarExpenseResource extends JsonResource
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
            'title' => $this->title,
            'amount' => (float) $this->amount,
            'expense_date' => $this->expense_date->toDateString(),
            'document' => $this->document ? asset('storage/' . $this->document) : null,
            'car' => new CarResource($this->whenLoaded('car')),
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'icon' => $this->category->icon,
                'color' => $this->category->color,
            ],
            'note'       => $this->note,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
