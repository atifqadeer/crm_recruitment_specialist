<?php

namespace Horsefly\Exports;
// use Illuminate\Support\Facades\Auth;

use Horsefly\Applicant;
use Horsefly\ApplicantNote;
use Horsefly\ModuleNote;
use DB;
use Carbon\Carbon;


use Illuminate\Foundation\Auth\User as Authenticatable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ApplicantsMonthlyStatsDetails implements FromCollection, WithHeadings
{

    protected $monthly_stats_data;
    /**
    * @return \Illuminate\Support\Collection
    */
    function __construct($check_applicant_availibility) {

        $this->monthly_stats_data = $check_applicant_availibility;
 }
    public function collection()
    {

        $collection=collect($this->monthly_stats_data);
       
       return $collection->unique('applicant_phone');
    
    }
    public function headings(): array
    {
        return [
            'Date',
            'Time',
            'Name',
            'Title',
            'Category',
            'Postcode',
            'Phone',
            'Source',
            'Notes',
        ];
    }
}
