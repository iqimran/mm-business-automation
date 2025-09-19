<?php

namespace App\Http\Controllers\Api;

use App\Models\Car;
use App\Services\Car\CarService;

use App\Http\Resources\Car\CarResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Car\StoreCarRequest;
use App\Http\Requests\Car\UpdateCarRequest;

class CarController extends Controller
{
    protected CarService $carService;
    public function __construct(CarService $carService)
    {
        $this->carService = $carService;
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return CarResource::collection($this->carService->getAll());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCarRequest $request)
    {
        $car = $this->carService->create($request->validated());

        return new CarResource($car);
    }

    /**
     * Display the specified resource.
     */
    public function show(Car $car)
    {
        return new CarResource($this->carService->find($car));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCarRequest $request, Car $car)
    {
        $car = $this->carService->update($car, $request->validated());
        return new CarResource($car);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Car $car)
    {
        $this->carService->delete($car);
        return response()->noContent();
    }
}
