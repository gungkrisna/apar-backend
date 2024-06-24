<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create suppliers');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $supplier = $this->route('supplier');

        return [
            'name' => ['required', 'string', Rule::unique('suppliers')->ignore($supplier)],
            'category' => ['required', 'string'],
            'phone' => ['required', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:9'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'address' => ['required', 'string']
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama perusahaan wajib diisi.',
            'name.string' => 'Nama perusahaan harus berupa string.',
            'name.unique' => 'Nama perusahaan sudah tersimpan sebagai supplier.',
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
