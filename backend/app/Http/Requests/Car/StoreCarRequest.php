<?php

namespace App\Http\Requests\Car;

use App\Enums\CarStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('cars.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'license_plate' => 'required|string|unique:cars,license_plate',
            'color' => 'nullable|string|max:255',
            'mileage' => 'nullable|integer|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'purchase_date' => ['required','date','before_or_equal:today'],
            'registration_date' => 'nullable|date',
            'fitness_date' => 'nullable|date',
            'tax_token_date' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'registration_expiry' => 'nullable|date',
            'last_service_date' => 'nullable|date',
            'next_service_due' => 'nullable|date',
            'notes' => ['sometimes','string','max:1000'],
            'status' => 'in:' . implode(',', array_column(CarStatus::cases(), 'value')),
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'license_plate.unique' => 'This license plate is already registered.',
            'images.max' => 'You can upload a maximum of 5 images.',
            'images.*.max' => 'Each image must be less than 2MB.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Car validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
