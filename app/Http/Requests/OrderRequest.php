<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'customer_id' => [
                'nullable',
                'exists:customers,id'
            ],

            'table_number' => [
                'nullable',
                'integer',
                'min:1'
            ],

            'delivery_address' => [
                'nullable',
                'string'
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20'
            ],

            'delivery_person' => [
                'nullable',
                'string',
                'max:255'
            ],

            'type' => [
                'required',
                Rule::in([
                    'dine_in',
                    'takeaway',
                    'delivery'
                ])
            ],

            // أضف هذه القواعد
            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.menu_id' => [
                'required',
                'exists:menu,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'items.*.notes' => [
                'nullable',
                'string',
            ],

            'notes' => [
                'nullable',
                'string'
            ],
        ];
    }
}
