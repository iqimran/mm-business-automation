<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'date_of_birth',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function sales()
    {
        return $this->hasMany(CarSale::class);
    }

    public function payments()
    {
        return $this->hasManyThrough(SalePayment::class, CarSale::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getTotalPurchasesAttribute()
    {
        return $this->sales()->sum('sale_price');
    }

    public function getPendingPaymentsAttribute()
    {
        return $this->sales()->where('payment_status', '!=', 'completed')->sum('remaining_balance');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
