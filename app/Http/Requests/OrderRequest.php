<?php

namespace App\Http\Requests;

use App\Models\Table;
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
                'exists:customers,id',
            ],

            // table_id هو المعرّف الصحيح المرتبط بجدول الطاولات
            'table_id' => [
                'nullable',
                'integer',
                'exists:tables,id',
                // لو نوع الطلب Dine-In → الطاولة مطلوبة ويجب أن تكون نشطة
                Rule::when(fn() => $this->input('type') === 'dine_in', [
                    'required',
                    function ($attribute, $value, $fail) {
                        $table = Table::find($value);
                        if (!$table) {
                            $fail('الطاولة غير موجودة.');
                            return;
                        }
                        if (!$table->is_active) {
                            $fail('الطاولة المختارة غير نشطة. الرجاء اختيار طاولة نشطة.');
                        }
                    },
                ]),
            ],

            'delivery_address' => [
                'nullable',
                'string',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'delivery_person' => [
                'nullable',
                'string',
                'max:255',
            ],

            'type' => [
                'required',
                Rule::in([
                    'dine_in',
                    'takeaway',
                    'delivery',
                ]),
            ],

            'payment_method' => [
                'nullable',
                Rule::in(['cash', 'InstaPay', 'card']),
            ],

            'discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],

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
        ];
    }

    public function messages(): array
    {
        return [
            'table_id.required'        => 'يجب اختيار طاولة لطلبات الصالة.',
            'type.required'            => 'يجب تحديد نوع الطلب.',
            'type.in'                  => 'نوع الطلب غير صحيح.',
            'items.required'           => 'يجب إضافة منتج واحد على الأقل.',
            'items.min'                => 'يجب إضافة منتج واحد على الأقل.',
            'items.*.menu_id.required' => 'معرف المنتج مطلوب.',
            'items.*.menu_id.exists'   => 'أحد المنتجات غير موجود في القائمة.',
            'items.*.quantity.min'     => 'يجب أن تكون الكمية 1 على الأقل.',
            'payment_method.in'        => 'طريقة الدفع غير صحيحة.',
        ];
    }
}
