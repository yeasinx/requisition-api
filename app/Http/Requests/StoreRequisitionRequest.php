<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Item array required, at least one item,
            'items' => ['required', 'array', 'min:1'],

            // Validate each item in the item array
            'items.*.item_name' => ['required', 'string', 'max:1000'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required for the requisition.',
            'items.min' => 'At least one item is required for the requisition.',
            'items.*.item_name.required' => 'Item name is required for each item.',
            'items.*.description.required' => 'Description is required for each item.',
            'items.*.quantity.required' => 'Quantity is required for each item.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.unit_price.required' => 'Unit price is required for each item.',
            'items.*.unit_price.min' => 'Unit price must be 0 or greater.',
        ];
    }
}
