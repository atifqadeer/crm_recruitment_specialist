<?php

namespace Horsefly\Exports;
// use Illuminate\Support\Facades\Auth;

use Horsefly\Applicant;
use Horsefly\History;
use Horsefly\Crm_note;
use Horsefly\ApplicantNote;
use Horsefly\ModuleNote;
use DB;
use Carbon\Carbon;
use Horsefly\Sale;
use Horsefly\Cv_note;
use DateTime;


use Illuminate\Foundation\Auth\User as Authenticatable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class IdleApplicantExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $end;
    protected $start;
    protected $job;
    protected $radius;

    /**
    * @return \Illuminate\Support\Collection
    */
    function __construct($start,$end,$job,$radius) {
        $this->start = $start;
        $this->end = $end;
        $this->job = $job;
        $this->radius = $radius;
 }
    public function collection()
    {
        $resultArray =array();
        ini_set('max_execution_time', 1800);
        $result = Applicant::select("applicant_phone","applicant_name","applicant_homePhone","applicant_job_title","job_category","applicant_postcode","lat","lng","id")
        ->whereBetween("created_at", [$this->start, $this->end]);
        if($this->job == 'non-nurse')
        {
           $result= $result->where("job_category", $this->job)->whereNotIn('applicant_job_title', ['nonnurse specialist']);
        }
        else if($this->job == 'nurse')
        {
            $result= $result->where("job_category", $this->job);
        }
        else if($this->job == 'specialist')
        {
            $result= $result->where(["job_category" => "non-nurse", "applicant_job_title" => "nonnurse specialist" ]);
        }
        $result= $result->where(["status"=>"active","is_blocked"=>0])
        ->where(function($query){
         $query->doesnthave("CRMNote.History")
            ->orWhereHas("CRMNote.History",function($query){
                $query->whereIn("sub_stage", ["crm_reject", "crm_request_reject","crm_interview_not_attended", "crm_declined","crm_start_date_hold", "crm_dispute"])
                ->where("status","active");
        });
        })
        ->get();

        foreach ($result as $key => $app) {

            $today =Carbon::parse(date("Y-m-d"));
            $postcode = $app->applicant_postcode;
            // $this->radius = $app->radius;

            if($app->lat != '0.000000' || $app->lng != '0.000000')
            {
                $lati = $app->lat;
                $longi = $app->lng;
                    $data['cordinate_results'] = $this->distance($lati,$longi,$this->radius);
                    if ($data['cordinate_results']->isNotEmpty()) 
                    {

                        
                            $sent_cv_count = Cv_note::where(['sale_id' => $data['cordinate_results'][0]->id, 'status' => 'active'])->count();
                            // echo 'db lat lng sent_cv_count: '.$sent_cv_count.' and send_cv_limit: '.$data['cordinate_results'][0]->send_cv_limit;exit();

                            if ($sent_cv_count < $data['cordinate_results'][0]->send_cv_limit) {

                                $resultArray[]= $result[$key];
                                $result[$key]->lat='';
                                $result[$key]->lng='';
								$result[$key]->id='';
                            }
                    } 
            }
		}

    return new Collection($resultArray);
        
    }
    public function distance($lat, $lon, $location_radius)
    {
        $location_distance = Sale::select(DB::raw("*, ((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) +
                COS($lat * PI() / 180) * COS(lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515)
                AS distance"))->having("distance", "<", $location_radius)->orderBy("distance")->where("status", "active")->where("is_on_hold", "0")->get();
        return $location_distance;
    }

    public function headings(): array
    {
        return [
            'Phone',
            'Name',
            'Home Phone',
            'Job Title',
            'Job category',
            'applicant Postcode',
        ];
    }
}
