<?php

namespace App\Exports\V1;

use Maatwebsite\Excel\Concerns\FromCollection;

class PurchasesExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        //
    }
}
