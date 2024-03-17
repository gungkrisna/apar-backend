<?php

namespace App\Http\Requests\V1;

use App\Models\Purchase;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $purchase = Purchase::find($this->route('purchase'));

        if ($purchase->status !== 1 && $this->user()->can('update purchases')) {
            return true;
        }

        // if ($this->user()->role === 'Staff' && $purchase->createdBy->id !== $this->user()->id) {
        //     return false;
        // }
            
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
        'purchase_number' => 'required',
        'date' => 'required',
        'supplier_id' => 'required|exists:suppliers,id',

        'purchase_items' => 'required|array|min:1',
        'purchase_items.*.id' => 'sometimes|nullable|exists:purchase_items,id',
        'purchase_items.*.category_id' => 'required|exists:categories,id',
        'purchase_items.*.product_id' => 'required|exists:products,id',
        'purchase_items.*.description' => 'nullable',
        'purchase_items.*.unit_price' => 'required|numeric|min:0',
        'purchase_items.*.quantity' => 'required|numeric|min:1',

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
            'purchase_number.required' => 'Nomor pembelian harus diisi.',
            'date.required' => 'Tanggal pembelian harus diisi.',
            'supplier_id.required' => 'Supplier tidak valid.',
            'supplier_id.exists' => 'Supplier tidak ditemukan.',

            'purchase_items.*.id.exists' => 'ID produk tidak valid.',
            'purchase_items.*.category_id.required' => 'Kategori produk harus diisi.',
            'purchase_items.*.category_id.exists' => 'Kategori produk tidak ditemukan.',
            'purchase_items.*.product_id.required' => 'Produk harus diisi.',
            'purchase_items.*.product_id.exists' => 'Produk tidak ditemukan.',
            'purchase_items.*.description.required' => 'Deskripsi produk harus diisi.',
            'purchase_items.*.unit_price.required' => 'Harga satuan produk harus diisi.',
            'purchase_items.*.unit_price.numeric' => 'Harga satuan produk harus berupa angka.',
            'purchase_items.*.unit_price.min' => 'Harga satuan produk tidak boleh kurang dari 0.',
            'purchase_items.*.quantity.required' => 'Kuantitas produk harus diisi.',
            'purchase_items.*.quantity.numeric' => 'Kuantitas produk harus berupa angka.',
            'purchase_items.*.quantity.min' => 'Kuantitas produk tidak boleh kurang dari 0.',

            'images.array' => 'Format data gambar tidak valid.',
            'images.*.required' => 'Data tidak memuat gambar.',
            'images.*.exists' => 'Gambar tidak valid.',
        ];
    }
}
