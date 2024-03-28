<?php

namespace App\Console\Commands;

use App\Models\InvoiceItem;
use Illuminate\Console\Command;

class CheckExpiringItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:expiring-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cek produk yang akan mendekati masa kedaluwarsa dan kirimkan email pengingat kepada klien';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $items = InvoiceItem::whereDate('expiry_date', '<=', now()->addDays(7)->toDateString())->get();

        foreach ($items as $item) {
            $item->sendExpiryNotification();
        }

        $this->info('Item yang akan kedaluwarsa telah diperiksa dan pemberitahuan telah dikirim.');
    }
}
