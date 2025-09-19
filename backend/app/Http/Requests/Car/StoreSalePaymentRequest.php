<?php

namespace App\Http\Requests\Car;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSalePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('payments.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'car_sale_id' => ['required', 'exists:car_sales,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', 'string', 'in:' . implode(',', array_column(PaymentMethod::cases(), 'value'))],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'receipt_image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,pdf'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'car_sale_id.required' => 'Car sale is required.',
            'amount.min' => 'Payment amount must be at least $0.01.',
            'amount.max' => 'Payment amount cannot exceed $99,999.99.',
            'receipt_image.max' => 'Receipt image must be less than 2MB.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Payment validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
