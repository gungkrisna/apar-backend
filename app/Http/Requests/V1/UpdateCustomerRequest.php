<?php

namespace App\Http\Requests\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    { 
        return $this->user()->can('update customers');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'company_name' => ['required', 'string', Rule::unique('customers')->ignore($customer)],
            'pic_name' => ['required', 'string'],
            'phone' => ['required', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:9'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'address' => ['required', 'string']
        ];
    }

    public function messages()
    {
        return [
            'company_name.required' => 'Nama perusahaan wajib diisi.',
            'company_name.string' => 'Nama perusahaan harus berupa string.',
            'company_name.unique' => 'Nama perusahaan sudah tersimpan sebagai customer.',
            'pic_name.required' => 'Nama person in contact wajib diisi.',
            'pic_name.string' => 'Nama  person in contact harus berupa string.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Format nomor telepon tidak valid.',
            'phone.min' => 'Nomor telepon harus memiliki setidaknya 9 karakter.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.string' => 'Alamat email harus berupa string.',
            'email.email' => 'Alamat email tidak valid.',
            'address.required' => 'Alamat wajib diisi.',
            'address.string' => 'Alamat harus berupa string.',
        ];
    }
}
