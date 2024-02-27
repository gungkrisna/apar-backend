<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update categories');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'description' => 'required',
            'image' => 'nullable|string|exists:images,id',
            'features' => 'nullable|array',
            'features.*.icon' => 'required|string',
            'features.*.name' => 'required|string',
            'features.*.description' => 'required|string',
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
            'name.required' => 'Atribut nama kategori harus diisi.',
            'name.string' => 'Atribut nama kategori harus berupa string.',
            'description.required' => 'Atribut deskripsi kategori harus diisi.',
            'description.string' => 'Atribut deskripsi kategori harus berupa string.',
            'features.*.name.required' => 'Atribut nama untuk setiap fitur harus diisi.',
            'features.*.name.string' => 'Atribut nama untuk setiap fitur harus berupa string.',
            'features.*.description.required' => 'Atribut deskripsi untuk setiap fitur harus diisi.',
            'features.*.description.string' => 'Atribut deskripsi untuk setiap fitur harus berupa string.',
        ];
    }
}
