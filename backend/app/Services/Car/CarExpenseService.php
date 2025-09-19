<?php

namespace App\Services\Car;

use App\Models\CarExpense;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;

class CarExpenseService
{
    public function getAll(): Collection
    {
        return CarExpense::all();
    }

    public function create(array $data): CarExpense
    {
        return DB::transaction(
            function () use ($data) {
                if (isset($data['document']) && $data['document'] instanceof UploadedFile) {
                    $data['document'] = $this->uploadReceipt($data['document']);
                }

                $carExpense = CarExpense::create($data);

                // Update Redis statistics
                Redis::incrbyfloat('stats:total_expenses', $data['amount']);
                Redis::incrbyfloat('stats:expenses:' . $data['expense_date'], $data['amount']);

                // Clear related caches
                Cache::tags(['expenses', 'dashboard'])->flush();


                return $carExpense;
            }
        );
    }

    public function find(CarExpense $carExpense): CarExpense
    {
        return $carExpense;
    }

    public function update(CarExpense $carExpense, array $data)
    {
        DB::transaction(
            function () use ($carExpense, $data) {
                $oldAmount = $carExpense->amount;

                if (isset($data['document']) && $data['document'] instanceof UploadedFile) {
                    // Delete old receipt
                    if ($carExpense->document) {
                        Storage::disk('public')->delete('car_expense_receipts/' . $carExpense->document);
                    }
                    $data['document'] = $this->uploadReceipt($data['document']);
                }

                $carExpense->update($data);
                // Update Redis statistics if amount has changed
                if (isset($data['amount']) && $data['amount'] != $oldAmount) {
                    $amountDiff = $data['amount'] - $oldAmount;
                    Redis::incrbyfloat('stats:total_expenses', $amountDiff);
                    Redis::incrbyfloat('stats:expenses:' . ($data['expense_date'] ?? $carExpense->expense_date), $amountDiff);
                }

                // Clear related caches
                Cache::tags(['expenses', 'dashboard'])->flush();

                return $carExpense;
           }
        );

    }

    public function delete(CarExpense $carExpense): void
    {
        $carExpense->delete();
    }

    protected function uploadReceipt(UploadedFile $file): string
    {
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('public/car_expense_receipts', $filename);
        return $filename;
    }
}
