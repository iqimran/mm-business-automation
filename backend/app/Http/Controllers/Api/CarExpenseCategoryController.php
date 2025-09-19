<?php

namespace App\Http\Controllers\Api;

use App\Models\CarExpenseCategory;
use App\Http\Controllers\Controller;
use App\Services\Car\CarExpenseCategoryService;
use App\Http\Resources\Car\CarExpenseCategoryResource;
use App\Http\Requests\Car\StoreCarExpenseCategoryRequest;
use App\Http\Requests\Car\UpdateCarExpenseCategoryRequest;

class CarExpenseCategoryController extends Controller
{
    public function __construct(private CarExpenseCategoryService $service) {}

    public function index()
    {
        return CarExpenseCategoryResource::collection($this->service->getAll());
    }

    public function store(StoreCarExpenseCategoryRequest $request)
    {
        $category = $this->service->create($request->validated());
        return new CarExpenseCategoryResource($category);
    }

    public function show(CarExpenseCategory $carExpenseCategory)
    {
        return new CarExpenseCategoryResource($this->service->find($carExpenseCategory));
    }

    public function update(UpdateCarExpenseCategoryRequest $request, CarExpenseCategory $carExpenseCategory)
    {
        $category = $this->service->update($carExpenseCategory, $request->validated());
        return new CarExpenseCategoryResource($category);
    }

    public function destroy(CarExpenseCategory $carExpenseCategory)
    {
        $this->service->delete($carExpenseCategory);
        return response()->noContent();
    }
}
