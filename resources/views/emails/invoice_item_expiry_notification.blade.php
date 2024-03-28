<x-mail::message>
# Pemberitahuan Masa Kedaluwarsa Barang

Halo,

Anda menerima email ini karena pernah melakukan transaksi di {{ config('app.name') }}. Berdasarkan data pada sistem, item faktur berikut akan mendekati masa kedaluwarsanya:

Invoice Number: {{ $invoiceItem->invoice->invoice_number }}<br />
Nama Barang: {{ $invoiceItem->product->name }}<br />
Tanggal Kedaluwarsa: {{ $invoiceItem->expiry_date->format('d/m/Y') }}

Hubungi <a href="https://www.indokasuryajaya.com">representatif Indoka Surya Jaya</a> untuk memperoleh bantuan teknis.

Terima kasih,<br />
{{ config('app.name') }}
</x-mail::message>