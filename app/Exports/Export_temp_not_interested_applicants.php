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

class Export_temp_not_interested_applicants implements FromCollection, WithHeadings
{
    protected $end_date;
    protected $start_date;

    /**
    * @return \Illuminate\Support\Collection
    */
    function __construct($start_date,$end_date) {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
 }
    public function collection()
    {
       
        $not_sents= Applicant::select(
            'applicant_phone', 'applicant_name','applicant_homePhone','applicant_job_title',
            'applicant_postcode','is_CV_reject','applicant_source')->whereBetween('updated_at', [$this->start_date, $this->end_date])
        ->where("temp_not_interested", "=", "1")->get();
        return $not_sents;
    }
    public function headings(): array
    {
        return [
            'Phone',
            'Name',
            'Home Phone',
            'Job Title',
            'Postcode',
			'Is CV Reject',
			'Source',
        ];
    }
}
