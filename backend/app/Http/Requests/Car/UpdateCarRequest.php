<?php

namespace App\Http\Requests\Car;

use App\Enums\CarStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'model' => 'sometimes|required|string|max:255',
            'year' => 'sometimes|required|integer|min:1900|max:' . date('Y'),
            'license_plate' => 'sometimes|required|string|unique:cars,license_plate,' . $this->car->id,
            'color' => 'nullable|string|max:255',
            'mileage' => 'nullable|integer|min:0',
            'purchase_price' => 'sometimes|required|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'purchase_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'registration_date' => 'nullable|date',
            'fitness_date' => 'nullable|date',
            'tax_token_date' => 'nullable|date',
            'insurance_expiry' => ['nullable', 'date', 'after:today'],
            'registration_expiry' => ['nullable', 'date', 'after:today'],
            'last_service_date' => ['nullable', 'date', 'before_or_equal:today'],
            'next_service_due' => ['nullable', 'date', 'after:today'],
            'notes' => 'nullable|string',
            'status' => ['sometimes', 'string', 'in:' . implode(',', array_column(CarStatus::cases(), 'value'))],
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Car update validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
