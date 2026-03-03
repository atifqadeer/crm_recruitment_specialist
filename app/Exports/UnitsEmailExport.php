<?php

namespace Horsefly\Exports;

use Carbon\Carbon;
use Horsefly\Office;
use Horsefly\Unit;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UnitsEmailExport implements FromCollection ,WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    function __construct() {

    }
    public function collection()
    {
          $result = Unit::join('offices', 'offices.id', '=', 'units.head_office')
            ->select('offices.office_name', 'units.unit_name', 'units.unit_postcode',
                'units.contact_email','units.contact_name','units.contact_phone_number')
            ->where(['units.status' => 'active'])->orderBy('units.id', 'DESC')->get();


        return $result;
    }
    public function headings(): array
    {
        return [
            'Head Office',
            'Unit',
            'PostCode',
            'Email',
            'Contact Name',
            'Phone'
        ];
    }
}
