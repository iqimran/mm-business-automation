<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCarExpenseRequest extends FormRequest
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
            'car_id'             => 'required|exists:cars,id',
            'expense_category_id' => 'required|exists:car_expense_categories,id',
            'expense_date'       => ['required', 'date', 'before_or_equal:today'],
            'amount'             => 'required|numeric|min:0',
            'title'              => 'nullable|string|max:255',
            'note'               => 'nullable|string',
            'document'           => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:2048',
            'is_active'          => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'car_id.required' => 'Please select a car.',
            'car_id.exists' => 'Selected car is invalid.',
            'expense_category_id.required' => 'Please select an expense category.',
            'amount.min' => 'Amount must be greater than 0.01.',
            'document.max' => 'Receipt image must be less than 2MB.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Expense validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
