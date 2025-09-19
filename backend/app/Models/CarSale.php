<?php

namespace App\Models;

use App\Enums\SaleStatus;
use App\Enums\PaymentStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CarSale extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'car_id',
        'client_id',
        'sale_price',
        'down_payment',
        'remaining_balance',
        'sale_date',
        'payment_status',
        'sale_status',
        'commission_rate',
        'commission_amount',
        'notes',
        'contract_terms',
        'warranty_terms',
        'delivery_date',
        'completed_at'
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'delivery_date' => 'date',
            'completed_at' => 'datetime',
            'sale_price' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'payment_status' => PaymentStatus::class,
            'sale_status' => SaleStatus::class,
            'contract_terms' => 'array',
            'warranty_terms' => 'array',
        ];
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class, 'car_sale_id');
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function getPaymentProgressAttribute()
    {
        if ($this->sale_price == 0) return 0;
        return ($this->total_paid / $this->sale_price) * 100;
    }

    public function getProfitLossAttribute()
    {
        $totalCost = $this->car->purchase_price + $this->car->total_expenses;
        return $this->sale_price - $totalCost - $this->commission_amount;
    }

    public function scopeByPaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('sale_status', SaleStatus::COMPLETED);
    }
}
