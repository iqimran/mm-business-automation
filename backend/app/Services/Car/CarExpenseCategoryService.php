<?php

namespace App\Services\Car;

use App\Models\CarExpenseCategory;
use Illuminate\Database\Eloquent\Collection;

class CarExpenseCategoryService
{
    public function getAll(): Collection
    {
        return CarExpenseCategory::all();
    }

    public function create(array $data): CarExpenseCategory
    {
        return CarExpenseCategory::create($data);
    }

    public function find(CarExpenseCategory $carExpenseCategory): CarExpenseCategory
    {
        return $carExpenseCategory;
    }

    public function update(CarExpenseCategory $car, array $data): CarExpenseCategory
    {
        $car->update($data);
        return $car;
    }

    public function delete(CarExpenseCategory $car): void
    {
        $car->delete();
    }
}
