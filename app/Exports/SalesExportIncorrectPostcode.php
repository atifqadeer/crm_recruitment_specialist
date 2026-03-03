<?php

namespace Horsefly\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Horsefly\Sale;
use Horsefly\Office;

class SalesExportIncorrectPostcode implements FromCollection, WithHeadings
{
    public function __construct() {
        //
    }

    public function collection()
    {
        return Office::join('sales', 'offices.id', '=', 'sales.head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
            ->select(
                'sales.job_category',
                'sales.job_title',
                'offices.office_name',
                'units.unit_name',
                'sales.postcode',
                'sales.lat',
                'sales.lng',
                'sales.job_type',
                'sales.experience',
                'sales.salary'
            )
            ->where('sales.status', 'active')
            ->where('sales.lat', '0.000000')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Category',
            'Job Title',
            'Head Office',
            'Unit',
            'Postcode',
            'Latitude',
            'Longitude',
            'Type',
            'Experience',
            'Salary',
        ];
    }
}
