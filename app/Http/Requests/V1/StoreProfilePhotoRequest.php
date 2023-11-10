<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfilePhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'photo' => 'required|image|mimes:jpeg,png|max:10000',
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
            'photo.required' => 'Pilih file untuk foto profil Anda.',
            'photo.image' => 'File yang diunggah harus berupa gambar.',
            'photo.mimes' => 'Ekstensi file yang diperbolehkan: .JPG, .JPEG, .PNG.',
            'photo.max' => 'Besar file: maksimum 10.000.000 bytes (10 Megabytes).',
        ];
    }
}
