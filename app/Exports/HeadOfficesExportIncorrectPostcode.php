<?php

namespace Horsefly\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Horsefly\Sale;
use Horsefly\Office;

class HeadOfficesExportIncorrectPostcode implements FromCollection, WithHeadings
{
    public function __construct() {
        //
    }

    public function collection()
    {
        return Office::select(
                'office_name',
                'office_postcode',
                'lat',
                'lng',
                'office_type',
                'office_contact_phone',
                'office_contact_landline'
            )
            ->where('status', 'active')
            ->where('lat', '0.000000')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Head Office',
            'Postcode',
            'Latitude',
            'Longitude',
            'Type',
            'Phone',
            'Landline'
        ];
    }
}
