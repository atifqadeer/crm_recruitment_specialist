<?php

namespace Horsefly\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesRegionExport implements FromCollection,WithHeadings
{
    protected $sales;

    public function __construct($sales)
    {
        $this->sales = $sales;
    }

    public function collection()
    {
        $data = [];

        foreach ($this->sales as $sale) {
            $data[] = [
                $sale->sale_added_date,
                $sale->sale_added_time,
                $sale->job_title,
                $sale->office_name,
                $sale->unit_name,
                $sale->postcode,
                $sale->job_type,
                $sale->experience,
                $sale->qualification,
                $sale->salary,
                $sale->sale_note,
            ];
        }

        return new Collection($data);
    }

    public function headings(): array
    {
        return [
            'Sale Added Date',
            'Sale Added Time',
            'Job Title',
            'Office Name',
            'Unit Name',
            'Postcode',
            'Job Type',
            'Experience',
            'Qualification',
            'Salary',
            'Sale Note',
        ];
    }
}
