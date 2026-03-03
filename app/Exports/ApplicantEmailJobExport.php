<?php

namespace Horsefly\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ApplicantEmailJobExport implements FromArray
{

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
    /**
    * @return \Illuminate\Support\Collection
    */


    public function array(): array
    {
        // Return the data as an array of arrays
        $rows = [];
        foreach ($this->data as $email) {
            $rows[] = [$email];
        }
        return $rows;
    }


}
