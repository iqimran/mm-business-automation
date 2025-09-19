<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCarExpenseRequest extends FormRequest
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
            'car_id'             => 'sometimes|exists:cars,id',
            'expense_category_id' => 'sometimes|exists:car_expense_categories,id',
            'expense_date'       => 'sometimes|date',
            'amount'             => 'sometimes|numeric|min:0',
            'title'              => 'nullable|string|max:255',
            'note'               => 'nullable|string',
            'document'           => 'nullable|string|max:255',
            'is_active'          => 'boolean',
        ];
    }
}
