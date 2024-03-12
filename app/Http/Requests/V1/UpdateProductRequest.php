<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update products');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
        'status' => 'required',
        'serial_number' => 'required',
        'name' => 'required',
        'description' => 'required',
        // 'stock' => 'required|integer|min:0',
        'price' => 'required|numeric|min:0',
        'expiry_period' => 'nullable|numeric',
        'unit_id' => 'required|exists:units,id',
        'supplier_id' => 'required|exists:suppliers,id',
        'category_id' => 'required|exists:categories,id',
        'images' => 'required|array|min:1',
        'images.*' => 'required|integer|exists:images,id',
        ];
    }
    
    /**
     * Get custom messages for validator errors
     * 
     * @return array
     */
    public function message()
    {
        return [
            'status.required' => 'Atribut status produk harus diisi.',
            'serial_number.required' => 'Atribut serial number produk harus diisi.',
            'name.required' => 'Atribut nama produk harus diisi.',
            'description.required' => 'Atribut deskripsi produk harus diisi.',
            // 'stock.required' => 'Atribut stok produk harus diisi.',
            // 'stock.integer' => 'Atribut stok produk harus berupa angka.',
            // 'stock.min' => 'Atribut stok produk tidak boleh kurang dari 0.',
            'price.required' => 'Atribut harga produk harus diisi.',
            'price.numeric' => 'Atribut harga produk harus berupa angka.',
            'price.min' => 'Atribut harga produk tidak boleh kurang dari 0.',
            'expiry_period.numeric' => 'Atribut periode kedaluwarsa harus berupa angka yang merepresentasikan durasi dalam jumlah bulan.',
            'unit_id.required' => 'Atribut unit produk harus diisi.',
            'unit_id.exists' => 'Atribut unit produk tidak valid.',
            'supplier_id.required' => 'Atribut supplier produk harus diisi.',
            'supplier_id.exists' => 'Atribut supplier produk tidak valid.',
            'category_id.required' => 'Atribut kategori produk harus diisi.',
            'category_id.exists' => 'Atribut kategori produk tidak valid.',
            'images.required' => 'Minimal satu gambar produk diperlukan.',
            'images.min' => 'Produk wajib memiliki minimal satu gambar.',
            'images.*.required' => 'Produk wajib memiliki gambar.',
        ];
    }
}
