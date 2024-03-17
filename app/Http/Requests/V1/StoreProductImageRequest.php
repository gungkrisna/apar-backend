<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreProductImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::any(['create products', 'update products']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'images.*' => 'required|image|mimes:jpeg,png|max:10000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'images.*.required' => 'Produk wajib memiliki gambar.',
            'images.*.image' => 'File yang diunggah harus berupa gambar.',
            'images.*.mimes' => 'Ekstensi file yang diperbolehkan: .JPG, .JPEG, .PNG.',
            'images.*.max' => 'Besar file: maksimum 10.000.000 bytes (10 Megabytes).',
        ];
    }
}
