<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class SalePayment extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $fillable = [
        'car_sale_id',
        'amount',
        'payment_date',
        'payment_method',
        'status',
        'reference_number',
        'notes',
        'receipt_image',
        'processed_by'
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
        ];
    }

    public function carSale()
    {
        return $this->belongsTo(CarSale::class);
    }

    public function getReceiptUrlAttribute()
    {
        return $this->receipt_image
            ? asset('storage/payment-receipts/' . $this->receipt_image)
            : null;
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', PaymentStatus::COMPLETED);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('payment_date', $date);
    }
}
