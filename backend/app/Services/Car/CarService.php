<?php

namespace App\Services\Car;

use App\Models\Car;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Enums\CarStatus;

class CarService
{
    public function getAll(): Collection
    {
        return Car::all();
    }

    public function create(array $data): Car
    {
        return DB::transaction( function () use ($data) {
            $data['status'] = $data['status'] ?? CarStatus::ACTIVE;

            if (isset($data['images'])) {
                $data['images'] = $this->uploadImages($data['images']);
            }

            $car = Car::create($data);

            // Clear related caches
            Cache::tags(['cars', 'dashboard'])->flush(); 

            // Update Redis statistics
            Redis::incr('stats:total_cars');
            Redis::incrbyfloat('stats:total_car_value', $data['purchase_price']);

            return $car;
        });
    }

    public function find(Car $car): Car
    {
        return $car;
    }

    public function update(Car $car, array $data): Car
    {
        return DB::transaction(function () use ($car, $data) {
            if (isset($data['images'])) {
                // Delete old images if new ones are uploaded
                if ($car->images) {
                    foreach ($car->images as $image) {
                        Storage::disk('public')->delete('cars/' . $image);
                    }
                }
                $data['images'] = $this->uploadImages($data['images']);
            }

            $car->update($data);

            // Clear related caches
            Cache::forget("car:{$car->id}");
            Cache::forget("car:{$car->id}:details");
            Cache::tags(['cars', 'dashboard'])->flush();

            return $car;
        });
    }

    public function delete(Car $car): void
    {
        if ($car->images) {
            foreach ($car->images as $image) {
                Storage::disk('public')->delete('cars/' . $image);
            }
        }
        // Update Redis statistics
        Redis::decr('stats:total_cars');
        Redis::decrbyfloat('stats:total_car_value', $car->purchase_price);
        Cache::forget("car:{$car->id}:details");
        Cache::tags(['cars', 'dashboard'])->flush();

        $car->delete();
    }

    public function uploadCarImages(Car $car, array $images): Car
    {
        $uploadedImages = $this->uploadImages($images);

        // Merge with existing images
        $existingImages = $car->images ?? [];
        $allImages = array_merge($existingImages, $uploadedImages);

        // Limit to 5 images maximum
        $allImages = array_slice($allImages, 0, 5);

        $car->update(['images' => $allImages]);

        return $car;
    }
    protected function uploadImages(array $images): array
    {
        $uploadedImages = [];

        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('public/cars', $filename);
                $uploadedImages[] = $filename;
            }
        }

        return $uploadedImages;
    }

    public function removeCarImage(Car $car, string $imageName): Car
    {
        if ($car->images && in_array($imageName, $car->images)) {
            // Delete from storage
            Storage::disk('public')->delete('cars/' . $imageName);

            // Remove from images array
            $updatedImages = array_filter($car->images, fn($img) => $img !== $imageName);
            $car->update(['images' => array_values($updatedImages)]); // Reindex array

            // Clear related caches
            Cache::forget("car:{$car->id}:details");
            Cache::tags(['cars', 'dashboard'])->flush();
        }

        return $car;
    }
}