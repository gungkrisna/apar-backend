<?php

namespace App\Http\Requests\V1;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $invoice = Invoice::find($this->route('invoice'));

        if ($invoice->status !== 1 && $this->user()->can('update invoices')) {
            return true;
        }
            
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
        'invoice_number' => 'required',
        'date' => 'required',
        'customer_id' => 'required|exists:customers,id',
        'discount' => 'numeric|min:0',
        'tax' => 'numeric|min:0',
        'description' => 'nullable',

        'invoice_items' => 'required|array|min:1',
        'invoice_items.*.id' => 'sometimes|nullable|exists:invoice_items,id',
        'invoice_items.*.category_id' => 'required|exists:categories,id',
        'invoice_items.*.product_id' => 'required|exists:products,id',
        'invoice_items.*.description' => 'nullable',
        'invoice_items.*.unit_price' => 'required|numeric|min:0',
        'invoice_items.*.quantity' => 'required|numeric|min:1',

        'images.array' => 'Format data gambar tidak valid.',
        'images.max' => 'Jumlah gambar tidak boleh lebih dari 20.',
        'images' => 'nullable|array|max:20',
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
            'invoice_number.required' => 'Nomor pembelian harus diisi.',
            'date.required' => 'Tanggal pembelian harus diisi.',
            'customer_id.required' => 'Customer tidak valid.',
            'customer_id.exists' => 'Customer tidak ditemukan.',
            'discount.numeric' => 'Nilai diskon harus berupa angka.',
            'tax.numeric' => 'Nilai pajak harus berupa angka.',

            'invoice_items.*.id.exists' => 'ID produk tidak valid.',
            'invoice_items.*.category_id.required' => 'Kategori produk harus diisi.',
            'invoice_items.*.category_id.exists' => 'Kategori produk tidak ditemukan.',
            'invoice_items.*.product_id.required' => 'Produk harus diisi.',
            'invoice_items.*.product_id.exists' => 'Produk tidak ditemukan.',
            'invoice_items.*.description.required' => 'Deskripsi produk harus diisi.',
            'invoice_items.*.unit_price.required' => 'Harga satuan produk harus diisi.',
            'invoice_items.*.unit_price.numeric' => 'Harga satuan produk harus berupa angka.',
            'invoice_items.*.unit_price.min' => 'Harga satuan produk tidak boleh kurang dari 0.',
            'invoice_items.*.quantity.required' => 'Kuantitas produk harus diisi.',
            'invoice_items.*.quantity.numeric' => 'Kuantitas produk harus berupa angka.',
            'invoice_items.*.quantity.min' => 'Kuantitas produk tidak boleh kurang dari 0.',

            'images.array' => 'Format data gambar tidak valid.',
            'images.*.required' => 'Data tidak memuat gambar.',
            'images.*.exists' => 'Gambar tidak valid.',
        ];
    }
}
