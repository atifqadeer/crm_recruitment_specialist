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

class NotUpdatedApplicantsExport implements FromCollection, WithHeadings
{

    protected $not_updated_applicants;
    /**
    * @return \Illuminate\Support\Collection
    */
    function __construct($not_updated_applicants) {

        $this->not_updated_applicants = $not_updated_applicants;
 }
    public function collection()
    {

        $collection=collect($this->not_updated_applicants);
       
       return $collection->unique('applicant_phone');
    
    }
    public function headings(): array
    {
        return [
            'Phone',
            'Name',
            'Home Phone',
            'Job Title',
            'Postcode',
        ];
    }

    // function distance($lat, $lon, $radius, $job_title)
    // {
    //     $title = $this->getAllTitles($job_title);

    //     $location_distance = Applicant::with('cv_notes')->select(DB::raw("*, ((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) + 
    //             COS($lat * PI() / 180) * COS(lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) 
    //             AS distance"))->having("distance", "<", $radius)->orderBy("distance")
    //         ->where(array("status" => "active", "is_in_nurse_home" => "no", 'is_callback_enable' => 'no')); //->get();

    //     $location_distance = $location_distance->where("applicant_job_title", $title[0])->orWhere("applicant_job_title", $title[1])->orWhere("applicant_job_title", $title[2])->orWhere("applicant_job_title", $title[3])->orWhere("applicant_job_title", $title[4])->orWhere("applicant_job_title", $title[5])->orWhere("applicant_job_title", $title[6])->orWhere("applicant_job_title", $title[7])->orWhere("applicant_job_title", $title[8])->orWhere("applicant_job_title", $title[9])->orWhere("applicant_job_title", $title[10])->get();

    //     //$location_distance = $location_distance->where("applicant_job_title", $title1)->get();
    //     return $location_distance;
    // }
}
