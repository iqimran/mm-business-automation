<?php

namespace App\Models;

use App\Enums\CarStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Car extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'model',
        'year',
        'license_plate',
        'color',
        'mileage',
        'purchase_price',
        'current_value',
        'purchase_date',
        'registration_date',
        'fitness_date',
        'tax_token_date',
        'insurance_expiry',
        'registration_expiry',
        'last_service_date',
        'next_service_due',
        'status',
        'notes',
        'images'
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'fitness_date' => 'date',
            'tax_token_date' => 'date',
            'insurance_expiry' => 'date',
            'registration_expiry' => 'date',
            'last_service_date' => 'date',
            'next_service_due' => 'date',
            'purchase_price' => 'decimal:2',
            'current_value' => 'decimal:2',
            'mileage' => 'integer',
            'year' => 'integer',
            'status' => CarStatus::class,
            'images' => 'array',
        ];
    }

    public function expenses()
    {
        return $this->hasMany(CarExpense::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->name} {$this->year} {$this->model}";
    }

    public function getTotalExpensesAttribute()
    {
        return $this->expenses()->sum('amount');
    }

    public function getDepreciationAttribute()
    {
        return $this->purchase_price - $this->current_value;
    }

    public function getMainImageAttribute()
    {
        if (empty($this->images)) {
            return asset('images/default-car.jpg');
        }
        return asset('storage/cars/' . $this->images[0]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', CarStatus::ACTIVE);
    }

    public function scopeMaintenance($query)
    {
        return $query->where('status', CarStatus::MAINTENANCE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', CarStatus::INACTIVE);
    }

    public function scopeSold($query)
    {
        return $query->where('status', CarStatus::SOLD);
    }

}
