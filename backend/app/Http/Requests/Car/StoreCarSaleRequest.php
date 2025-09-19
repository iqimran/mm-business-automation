<?php

namespace App\Http\Requests\Car;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCarSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('sales.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'car_id' => ['required', 'exists:cars,id'],
            'client_id' => ['nullable', 'exists:clients,id'],

            // Client information (if creating new client)
            'client.first_name' => ['required_without:client_id', 'string', 'max:100'],
            'client.last_name' => ['required_without:client_id', 'string', 'max:100'],
            'client.email' => ['required_without:client_id', 'email', 'unique:clients,email', 'max:255'],
            'client.phone' => ['required_without:client_id', 'string', 'max:20'],
            'client.address' => ['nullable', 'string', 'max:500'],
            'client.city' => ['nullable', 'string', 'max:100'],
            'client.state' => ['nullable', 'string', 'max:100'],
            'client.zip_code' => ['nullable', 'string', 'max:20'],
            'client.date_of_birth' => ['nullable', 'date', 'before:today'],
            'client.license_number' => ['nullable', 'string', 'max:50'],

            // Sale information
            'sale_price' => ['required', 'numeric', 'min:1'],
            'down_payment' => ['nullable', 'numeric', 'min:0', 'lte:sale_price'],
            'sale_date' => ['required', 'date', 'before_or_equal:today'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'contract_terms' => ['nullable', 'array'],
            'warranty_terms' => ['nullable', 'array'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:sale_date'],
            'payment_method' => ['nullable', 'in:' . implode(',', array_column(PaymentMethod::cases(), 'value'))],
            'receipt_image' => ['nullable', 'array'],
            'receipt_image.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:2048'], // Max 2MB per file

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

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Car sale validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
