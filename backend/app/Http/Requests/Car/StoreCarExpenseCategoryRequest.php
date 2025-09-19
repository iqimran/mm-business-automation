<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;

class StoreCarExpenseCategoryRequest extends FormRequest
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
            'name' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'parent_id' => 'nullable|exists:car_expense_categories,id',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'type' => 'required|in:fixed,variable',
            'default_amount' => 'nullable|numeric|min:0',
            'frequency' => 'nullable|in:daily,weekly,monthly,yearly',
            'reminder' => 'nullable|boolean',
            'reminder_days_before' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'metadata' => 'nullable|array',
            'metadata.*' => 'string',
        ];
    }
}
