<?php

namespace App\Http\Requests\V1;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $unit = Unit::find($this->route('unit'));

        return !$unit->trashed() && $this->user()->can('update units');
    }

     /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $unit = $this->route('unit');

        return [
            'name' => ['required', 'string', Rule::unique('units')->ignore($unit)],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama unit wajib diisi.',
            'name.string' => 'Nama unit harus berupa string.',
            'name.unique' => 'Data unit sudah ada.',
        ];
    }
}
