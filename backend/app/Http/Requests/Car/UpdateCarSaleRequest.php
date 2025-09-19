<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCarSaleRequest extends FormRequest
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
            // Add validation rules here
            'car_id' => ['sometimes', 'exists:cars,id'],
            'client_id' => ['sometimes', 'exists:clients,id'],

            'sale_price' => ['sometimes', 'numeric', 'min:1'],
            'down_payment' => ['sometimes', 'numeric', 'min:0', 'lte:sale_price'],
            'sale_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'commission_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'notes' => ['sometimes', 'string', 'max:1000'],
            'contract_terms' => ['sometimes', 'array'],
            'warranty_terms' => ['sometimes', 'array'],
            'delivery_date' => ['sometimes', 'date', 'after_or_equal:sale_date']

        ];
    }

    public function messages(): array
    {
        return [
            'car_id.required' => 'Please select a car to sell.',
            'client.email.unique' => 'A client with this email already exists.',
            'down_payment.lte' => 'Down payment cannot exceed the sale price.',
            //'sale_price.max' => 'Sale price cannot exceed $9,999,999.99.',
            'delivery_date.after_or_equal' => 'Delivery date must be on or after the sale date.',
        ];
    }
}
