<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreCategoryImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::any(['create categories', 'update categories']);;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules()
    {
        return [
            'image' => 'required|image|mimes:jpg,jpeg,png|max:10000',
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
            'photo.required' => 'Pilih file gambar yang akan diunggah.',
            'photo.image' => 'File yang diunggah harus berupa gambar.',
            'photo.mimes' => 'Ekstensi file yang diperbolehkan: .JPG, .JPEG, .PNG.',
            'photo.max' => 'Besar file: maksimum 10.000.000 bytes (10 Megabytes).',
        ];
    }
}
