<?php

namespace App\Http\Requests\V1;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $product = Product::find($this->route('product'));

        return !$product->trashed() && $this->user()->can('update products');
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
        'price' => 'required|numeric|min:0',
        'expiry_period' => 'nullable|numeric',
        'unit_id' => 'required|exists:units,id',
        'supplier_id' => 'required|exists:suppliers,id',
        'category_id' => 'required|exists:categories,id',
        'images' => 'required|array|min:1|max:12',
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
            'status.required' => 'Status produk harus diisi.',
            'serial_number.required' => 'Serial number produk harus diisi.',
            'name.required' => 'Nama produk harus diisi.',
            'description.required' => 'Deskripsi produk harus diisi.',
            'price.required' => 'Harga produk harus diisi.',
            'price.numeric' => 'Harga produk harus berupa angka.',
            'price.min' => 'Harga produk tidak boleh kurang dari 0.',
            'expiry_period.numeric' => 'Periode kedaluwarsa harus berupa angka yang merepresentasikan durasi dalam jumlah bulan.',
            'unit_id.required' => 'Unit produk harus diisi.',
            'unit_id.exists' => 'Unit produk tidak valid.',
            'supplier_id.required' => 'Supplier produk harus diisi.',
            'supplier_id.exists' => 'Supplier produk tidak valid.',
            'category_id.required' => 'Kategori produk harus diisi.',
            'category_id.exists' => 'Kategori produk tidak valid.',
            'images.required' => 'Produk wajib memiliki gambar.',
            'images.min' => 'Produk wajib memiliki minimal satu gambar.',
            'images.max' => 'Jumlah gambar tidak boleh lebih dari 20.',
            'images.*.required' => 'Produk wajib memiliki gambar.',
        ];
    }
}
