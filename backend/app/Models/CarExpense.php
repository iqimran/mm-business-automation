<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CarExpense extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'car_id',
        'expense_category_id',
        'title',
        'note',
        'document',
        'amount',
        'expense_date',
        'is_active'
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2'
        ];
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function category()
    {
        return $this->belongsTo(CarExpenseCategory::class, 'expense_category_id');
    }

    public function getReceiptUrlAttribute()
    {
        return $this->receipt_image
            ? asset('storage/receipts/' . $this->receipt_image)
            : null;
    }

    public function scopeByCar($query, $carId)
    {
        return $query->where('car_id', $carId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }
}
