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

class AllRejectedApplicantsExport implements FromCollection, WithHeadings
{
    protected $end_date;
    protected $start_date;
    protected $job_category;
    /**
    * @return \Illuminate\Support\Collection
    */
    function __construct($start_date,$end_date,$job_category) {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->job_category = $job_category;
 }
    public function collection()
    {
       

        $end_date = Carbon::now();
        // $edate7 = $end_date->subDays(10);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(42);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";
        $crm_rejected_applicants = Applicant::with('cv_notes')
        ->join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
        ->join('history', function ($join) {
            $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
            $join->on('crm_notes.sales_id', '=', 'history.sale_id');
        })->select('applicants.applicant_name', 'applicants.applicant_phone', 'applicants.applicant_homePhone', 'applicants.applicant_job_title',
            'applicants.applicant_postcode',
            DB::raw('
        CASE 
            WHEN history.sub_stage="crm_reject"
            THEN "Rejected CV" 
            WHEN history.sub_stage="crm_request_reject"
            THEN "Rejected By Request"
            WHEN history.sub_stage="crm_declined"
            THEN "Declined"
            WHEN history.sub_stage="crm_interview_not_attended"
            THEN "Not Attended"
            WHEN history.sub_stage="crm_start_date_hold" OR history.sub_stage = "crm_start_date_hold_save"
            THEN "Start Date Hold"
            WHEN history.sub_stage="crm_dispute"
            THEN "Dispute" 
            END AS sub_stage'))->whereIn("history.sub_stage", ["crm_dispute","crm_interview_not_attended","crm_declined","crm_request_reject","crm_reject","crm_start_date_hold", "crm_start_date_hold_save"])
            ->whereIn("crm_notes.moved_tab_to", ["dispute","interview_not_attended","declined","request_reject","cv_sent_reject","start_date_hold", "start_date_hold_save"])
            ->whereBetween('crm_notes.updated_at', [$sdate, $edate])
        ->where([
            "applicants.status" => "active", "history.status" => "active"
        ])->orderBy("crm_notes.id","DESC")->get();
        return $crm_rejected_applicants;
    }
    public function headings(): array
    {
        return [
            'Name',
            'Phone',
            'Home Phone',
            'Job Title',
            'Postcode',
        ];
    }
}
