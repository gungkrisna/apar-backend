<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StorePurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create purchases');
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
        'purchase_number' => 'required',
        'date' => 'required',
        'supplier_id' => 'required|exists:suppliers,id',
        'category_id' => 'required|exists:categories,id',
        'product_id' => 'required|exists:products,id',
        'description' => 'required',
        'unit_price' => 'required|numeric|min:0',
        'quantity' => 'required|numeric|min:0',
        'images' => 'nullable|array',
        'images.*' => 'required|integer|exists:images,id',
        ];
    }

    /**
     * Get custom messages for validator errors
     * 
     * @return array
     */
    public function messages()
    {
        return [
            'status.required' => 'Status produk harus diisi.',
            'purchase_number.required' => 'Nomor pembelian harus diisi.',
            'date.required' => 'Tanggal pembelian harus diisi.',
            'supplier_id.required' => 'Supplier tidak valid.',
            'supplier_id.exists' => 'Supplier tidak ditemukan.',
            'category_id.required' => 'Kategori produk harus diisi.',
            'category_id.exists' => 'Kategori produk tidak ditemukan.',
            'product_id.required' => 'Produk harus diisi.',
            'product_id.exists' => 'Produk tidak ditemukan.',
            'description.required' => 'Deskripsi produk harus diisi.',
            'unit_price.required' => 'Harga satuan produk harus diisi.',
            'unit_price.numeric' => 'Harga satuan produk harus berupa angka.',
            'unit_price.min' => 'Harga satuan produk tidak boleh kurang dari 0.',
            'quantity.required' => 'Kuantitas produk harus diisi.',
            'quantity.numeric' => 'Kuantitas produk harus berupa angka.',
            'quantity.min' => 'Kuantitas produk tidak boleh kurang dari 0.',
            'images.array' => 'Format data gambar tidak valid.',
            'images.*.required' => 'Data tidak memuat gambar.',
            'images.*.exists' => 'Gambar tidak valid.',
        ];
    }
}
