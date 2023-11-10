<?php

namespace App\Http\Requests\V1;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $supplier = Supplier::find($this->route('supplier'));

        return !$supplier->trashed() && $this->user()->can('update suppliers');
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
            'phone' => ['required', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:9'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'address' => ['required', 'string']
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Kolom nama perusahaan wajib diisi.',
            'name.string' => 'Kolom nama perusahaan harus berupa string.',
            'name.unique' => 'Nama perusahaan sudah tersimpan sebagai supplier.',
            'phone.required' => 'Kolom telepon wajib diisi.',
            'phone.string' => 'Kolom telepon harus berupa string.',
            'phone.regex' => 'Format telepon tidak valid.',
            'phone.min' => 'Telepon harus memiliki setidaknya 9 karakter.',
            'email.required' => 'Kolom email wajib diisi.',
            'email.string' => 'Kolom email harus berupa string.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Panjang email tidak boleh lebih dari 255 karakter.',
            'address.required' => 'Kolom alamat wajib diisi.',
            'address.string' => 'Kolom alamat harus berupa string.',
        ];
    }
}
