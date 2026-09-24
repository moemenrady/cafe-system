<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method'  => ['required', 'string', 'in:cash,InstaPay,card'],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'shift_id'        => ['nullable', 'integer', 'exists:shifts,id'],
            'note'            => ['nullable', 'string', 'max:500'],
            'force'           => ['nullable', 'boolean'],
            'device_uuid'     => ['nullable', 'string', 'max:100'],
            'customer_id'     => ['nullable', 'integer', 'exists:customers,id'],
            'customer_phone'  => ['nullable', 'string', 'max:50'],
            'customer_name'   => ['nullable', 'string', 'max:255'],
            'phone'           => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'يجب تحديد طريقة الدفع.',
            'payment_method.in'       => 'طريقة الدفع غير صالحة.',
            'shift_id.exists'         => 'الشِفت المحدد غير موجود.',
        ];
    }
}
