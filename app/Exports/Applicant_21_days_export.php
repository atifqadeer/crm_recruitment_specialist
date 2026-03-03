<?php

namespace Horsefly\Exports;

use Horsefly\Applicant;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class Applicant_21_days_export implements FromCollection, WithHeadings
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


        $result1= Applicant::select(
            'id','applicant_phone', 'applicant_name','applicant_homePhone','applicant_job_title',
            'applicant_postcode','is_CV_reject','applicant_source','applicant_notes')->where(function($query){
            $query->doesnthave('CVNote');
        })->whereBetween('updated_at', [$this->start_date, $this->end_date]);
        if ($this->job_category == "44"){
            $result1= $result1->where("job_category", '=',"nurse");
        }elseif ($this->job_category == "45"){
            $result1= $result1->where("job_category", "=","non-nurse")->whereNotIn('applicant_job_title', ['nonnurse specialist']);
        }elseif ($this->job_category =="46"){
            $result1= $result1->where(["job_category" => "non-nurse", "applicant_job_title" => "nonnurse specialist" ]);
        }elseif ($this->job_category =="47"){
            $result1= $result1->where(["job_category" => "chef"]);
        }
//            ->where("job_category", "=", $this->job_category)
        $not_sents=$result1->where("is_blocked", "=", "0")->where("applicants.temp_not_interested", "=", "0")
            ->where('applicants.is_no_job',"=","0")->get();
        // $not_sents= Applicant::select(
        //     'id','applicant_phone', 'applicant_name','applicant_homePhone','applicant_job_title',
        //     'applicant_postcode')->where(([['is_cv_in_quality','=','no'],['is_cv_in_quality_clear','=', 'no'],['is_CV_reject','=', 'no']]))->whereBetween('created_at', [$this->start_date, $this->end_date])->where("job_category", "=", $this->job_category)->get();

        $rejects= Applicant::select(
            'id','applicant_phone', 'applicant_name','applicant_homePhone','applicant_job_title',
            'applicant_postcode','is_CV_reject','applicant_source','applicant_notes')->with('CRMNote')->with('CVNote')
            ->where(function($query){
                $query->whereHas('CVNote')
                    ->orWhereHas('CRMNote');
            })->whereBetween('updated_at', [$this->start_date, $this->end_date]);
        if ($this->job_category == "44"){
            $rejects= $rejects->where("job_category", '=',"nurse");
        }elseif ($this->job_category == "45"){
            $rejects= $rejects->where("job_category", "=","non-nurse")->whereNotIn('applicant_job_title', ['nonnurse specialist']);
        }elseif ($this->job_category =="46"){
            $rejects= $rejects->where(["job_category" => "non-nurse", "applicant_job_title" => "nonnurse specialist" ]);
        }elseif ($this->job_category =="47"){
            $rejects= $rejects->where(["job_category" => "chef"]);
        }
//            ->where("job_category", "=", $this->job_category)

        $rejecteds=$rejects->where("is_blocked", "=", "0")->where("applicants.temp_not_interested", "=", "0")
            ->where('applicants.is_no_job',"=","0")->get();


        $not_sents->map(function($row){
            $row->sub_stage = "Not Sent";
            unset($row->id);
            unset($row->is_CV_reject);

        });


        $clean_data = collect();
        $rejecteds->map(function($row) use($clean_data){
            //  if(!empty($row->CVNote->History))
            //  dd($row->CVNote->History);
            $rejected_status = 'Rejected CV';

            if(isset($row->CRMNote->moved_tab_to) && $row->CRMNote->moved_tab_to=='dispute')
                $rejected_status = 'crm_dispute';
            else if(isset($row->CRMNote->moved_tab_to) && $row->CRMNote->moved_tab_to=='interview_not_attended')
                $rejected_status = 'crm_interview_not_attended';
            else if(isset($row->CRMNote->moved_tab_to) && $row->CRMNote->moved_tab_to=='declined')
                $rejected_status = 'crm_declined';
            else if(isset($row->CRMNote->moved_tab_to) && $row->CRMNote->moved_tab_to=='request_reject')
                $rejected_status = 'crm_request_reject';
            else if(isset($row->CRMNote->moved_tab_to) && $row->CRMNote->moved_tab_to=='cv_sent_reject')
                $rejected_status = 'crm_reject';
            else if(isset($row->CRMNote->moved_tab_to) && $row->CRMNote->moved_tab_to=='start_date_hold')
                $rejected_status = 'crm_start_date_hold';
            else if(isset($row->CRMNote->moved_tab_to) && $row->CRMNote->moved_tab_to=='start_date_hold_save')
                $rejected_status = 'crm_start_date_hold_save';
            else if(isset($row->CVNote->status) && $row->CVNote->status=='active' && $row->is_CV_reject=='yes')
                $rejected_status = 'quality_rejected';
            // if(isset($row->CRMNote->History->sub_stage))
            // dd($row->CRMNote->History->sub_stage);
            // if(isset($row->CVNote->History->sub_stage))
            // dd($row->CVNote->History->sub_stage);
            if($rejected_status == 'Rejected CV')
            {
                unset($row);
            }
            else{
                //$row->sub_stage = $rejected_status;
                //unset($row->id);
                unset($row->is_CV_reject);
                $clean_data->push($row);
            }


        });
        $rejects =$clean_data->toArray();
        $history_stages = config('constants.history_stages');
        $quality_array=array("quality_cvs"=>"quality_cvs", "quality_cleared"=>"quality_cleared");
        $history_stages=array_merge($history_stages, $quality_array);
        //print_r($history_stages);exit();
        $arr = array();
        $reslut = array();
        foreach ($rejecteds as $key => $filter_val) {
            $applicants_in_crm = Applicant::join('crm_notes', 'crm_notes.applicant_id', '=', 'applicants.id')
                ->join('sales', 'sales.id', '=', 'crm_notes.sales_id')
                ->join('offices', 'offices.id', '=', 'head_office')
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
                ->join('history', function($join) {
                    $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                    $join->on('crm_notes.sales_id', '=', 'history.sale_id');
                })
                ->select("applicants.*", "applicants.id as app_id", "crm_notes.*", "crm_notes.id as crm_notes_id", "sales.*", "sales.id as sale_id", "sales.postcode as sale_postcode", "sales.job_title as sale_job_title", "sales.job_category as sales_job_category", "sales.status as sale_status", "history.history_added_date", "history.sub_stage","office_name", "unit_name","history.id as history_id","history.updated_at as history_updated","crm_notes.updated_at as crm_updated","crm_notes.moved_tab_to as crm_moved_tab_to","crm_notes.status","history.history_uid as history_uid")
                ->where(array("applicants.id" => $filter_val['id'],"history.status"=> "active"))
                //->where("crm_notes.status","=","active")
                ->whereIn('crm_notes.id', function($query){
                    $query->select(DB::raw('MAX(id) FROM crm_notes WHERE sales_id=sales.id and applicants.id=applicant_id'));
                })->latest('history.updated_at')->get();
            //->orderBy('history.id', 'DESC')
            //->orderBy('crm_notes.id', 'DESC')->get();
            //print_r($applicants_in_crm[0]->history_uid);exit();

            if(!empty($applicants_in_crm[0]))
            {
                //echo $applicants_in_crm[0]->sub_stage];exit();

                if($history_stages[$applicants_in_crm[0]->sub_stage]=='Sent CV' || $history_stages[$applicants_in_crm[0]->sub_stage]=='Request' || $history_stages[$applicants_in_crm[0]->sub_stage]=='Confirmation' || $history_stages[$applicants_in_crm[0]->sub_stage]=='Rebook' || $history_stages[$applicants_in_crm[0]->sub_stage]=='Attended to Pre-Start Date' ||
                    $history_stages[$applicants_in_crm[0]->sub_stage]=='Attended to Pre-Start Date' ||
                    $history_stages[$applicants_in_crm[0]->sub_stage]=='Start Date' ||
                    $history_stages[$applicants_in_crm[0]->sub_stage]=='Invoice' ||
                    $history_stages[$applicants_in_crm[0]->sub_stage]=='Paid' || $history_stages[$applicants_in_crm[0]->sub_stage]=='quality_cvs'
                    || $history_stages[$applicants_in_crm[0]->sub_stage]=='quality_cleared')
                {
                    if($history_stages[$applicants_in_crm[0]->sub_stage]=='quality_cvs')
                    {
                        //echo 'here';exit();

                        $crm_reject_stages = ["dispute","interview_not_attended","declined","request_reject","cv_sent_reject",
                            "start_date_hold", "start_date_hold_save"];
                        if(in_array($applicants_in_crm[0]->moved_tab_to, $crm_reject_stages))
                        {
                            unset($filter_val['id']);

                            //$res = array_add($rejecteds[$key], 'notes', $applicants_in_crm[0]->moved_tab_to);
                            //echo 'here';exit();
                            $arr[]=$rejecteds[$key];
                        }
                    }
                    //$rejects->forget($key);
                    unset( $filter_val['id']);

                }
                else
                {
                    unset($filter_val['id']);

                    //$res = array_add($rejecteds[$key], 'notes', $applicants_in_crm[0]->moved_tab_to);

                    $arr[]=$rejecteds[$key];
                }
            }

            unset( $arr['id']);
        }
        //unset($arr['id']);

        //echo '<pre>';print_r($arr);'echo </pre>';exit();
        $arr = collect($arr);

        //$admin_author_collection  = array_merge($arr,$not_sent);
        $admin_author_collection  = $arr->toBase()->merge($not_sents->toBase());
        //echo '<pre>';print_r($admin_author_collection);'echo </pre>';exit();

        // return $admin_author_collection;
        // return $res;
        // $merged_data = $rejecteds->merge($not_sents);
        return $admin_author_collection;
    }
    public function headings(): array
    {
        return [
            'Phone',
            'Name',
            'Home Phone',
            'Job Title',
            'Postcode',
			'Applicant Source',
			'Notes'
        ];
    }
}
