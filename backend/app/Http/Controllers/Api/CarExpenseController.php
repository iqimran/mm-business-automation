<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Car\CarExpenseService;
use App\Http\Resources\Car\CarExpenseResource;
use App\Http\Requests\Car\StoreCarExpenseRequest;
use App\Http\Requests\Car\UpdateCarExpenseRequest;
use App\Models\CarExpense;
class CarExpenseController extends Controller
{
    public function __construct(private CarExpenseService $service) {}

    public function index()
    {
        return CarExpenseResource::collection($this->service->getAll());
    }

    public function store(StoreCarExpenseRequest $request)
    {
        $expense = $this->service->create($request->validated());
        return new CarExpenseResource($expense);
    }

    public function show(CarExpense $carExpense)
    {
        return new CarExpenseResource($this->service->find($carExpense));
    }

    public function update(UpdateCarExpenseRequest $request, CarExpense $carExpense)
    {
        $expense = $this->service->update($carExpense, $request->validated());
        return new CarExpenseResource($expense);
    }

    public function destroy(CarExpense $carExpense)
    {
        $this->service->delete($carExpense);
        return response()->noContent();
    }
}
