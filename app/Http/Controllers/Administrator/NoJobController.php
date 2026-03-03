<?php

namespace Horsefly\Http\Controllers\Administrator;

use Carbon\Carbon;
use Horsefly\Applicant;
use Horsefly\Applicants_pivot_sales;
use Horsefly\Crm_note;
use Horsefly\Cv_note;
use Horsefly\History;
use Horsefly\Office;
use Horsefly\Quality_notes;
use Horsefly\RevertStage;
use Horsefly\Sales_notes;
use Horsefly\Sale;
use Horsefly\Specialist_job_titles;
use Horsefly\Unit;
use Illuminate\Http\Request;
use Horsefly\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class NoJobController extends Controller
{
	public function __construct()
    {
        $this->middleware('auth');
	}
	
    public function get15kmAvailableNoJobs($id)
    {
        $applicant = Applicant::find($id);
        $is_applicant_in_quality = $applicant->is_cv_in_quality;
        if ($applicant->paid_status == 'close') {
            return view('administrator.resource.15km_jobs_for_closed_applicant', compact('applicant', 'is_applicant_in_quality'));
        }
        return view('administrator.resource.15km_no_jobs', compact('applicant', 'is_applicant_in_quality'));
    }
	
    public function get15kmNoJobsAvailableAjax($applicant_id)
	{
        $applicant = Applicant::with('cv_notes')->find($applicant_id);

        $applicant_job_title = $applicant->applicant_job_title;
        $applicant_postcode = $applicant->applicant_postcode;
        $lati=$applicant->lat;
        $longi=$applicant->lng;
        $radius = 35;
		$near_by_jobs = $this->job_distance($lati, $longi, $radius, $applicant_job_title);

        $jobs = $this->check_not_interested_in_jobs($near_by_jobs, $applicant_id);
        foreach ($jobs as &$job) {
            $office_id = $job['head_office'];
            $unit_id = $job['head_office_unit'];
            $office = Office::select("office_name")->where(["id" => $office_id, "status" => "active"])->first();
            $office = $office->office_name;
            $unit = Unit::select("unit_name")->where(["id" => $unit_id, "status" => "active"])->first();
            $unit = $unit->unit_name;
            $job['office_name'] = $office;
            $job['unit_name'] = $unit;
            $job['cv_notes_count']=$job['cv_notes_count'];
			
			$sale_notes = Sales_notes::where('sale_id',$job['id'])->latest()->first();
            $job['sale_notes'] = $sale_notes ? '<b>Dated: '.Carbon::parse($sale_notes->created_at)->format('d-m-Y, h:i A') . '</b><br>' . $sale_notes->sale_note : '';
        }

        return datatables($jobs)
            ->editColumn('job_title',function($job){
                // $job_title_desc = ($job['job_title_prof']!='')?$job['job_title'].' ('.$job['job_title_prof'].')':$job['job_title'];
                // return $job_title_desc;

                $job_title_desc='';
                if($job['job_title_prof']!=null)
                {
                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $job['job_title_prof'])->first();
                    $job_title_desc = $job['job_title'].' ('.$job_prof_res->specialist_prof.')';
                }
                else
                {

                    $job_title_desc = $job['job_title'];
                }
                return strtoupper($job_title_desc);

            })
           ->addColumn('action', function ($job) use ($applicant) {
                $option = 'open';
                foreach ($applicant->cv_notes as $key => $value) {
                    if ($value->sale_id == $job['id']) {
                        if ($value->status == 'active') {
                            $option = 'sent';
                            break;
                        } elseif ($value->status == 'disable') {
                            $option = 'reject_job';
                            break;
                        } elseif ($value->status == 'paid') {
                            $option = 'paid';
                            break;
                        }
                    }
                }
                $content = "";
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">
                    <i class="icon-menu9"></i>
                </a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($option == 'open') {
                    $content .= '<a href="#" class="dropdown-item"
                           data-controls-modal="#modal_form_horizontal' . $job['id'] . '"
                           data-backdrop="static"
                           data-keyboard="false" data-toggle="modal"
                           data-target="#modal_form_horizontal' . $job['id'] . '"> NOT INTERESTED</a>';
                    $content .= '<a href="#" class="dropdown-item"
                           data-controls-modal="#sent_cv' . $job['id'] . '" data-backdrop="static"
                           data-keyboard="false" data-toggle="modal"
                           data-target="#sent_cv' . $job['id'] . '">SEND CV</a>';
                    if ($applicant->is_in_nurse_home == "no") {
                        $content .= '<a href="#"
                           class="dropdown-item"
                           data-controls-modal="#no_nurse_home' . $applicant['id'] . '" data-backdrop="static"
                           data-keyboard="false" data-toggle="modal"
                           data-target="#no_nurse_home' . $applicant['id'] . '">NO NURSING HOME</a>';
                    }
                    if ($applicant->is_callback_enable == "no") {
                        $content .= '<a href="#" class="dropdown-item"
                       data-controls-modal="#call_back' . $applicant['id'] . '" data-backdrop="static"
                       data-keyboard="false" data-toggle="modal"
                       data-target="#call_back' . $applicant['id'] . '">CALLBACK</a>';
                    }


                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                if ($applicant->is_in_nurse_home == "no") {
                    // No Nursing Home Modal
                    $content .= '<div id="no_nurse_home' . $applicant['id'] . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Add No Nursing Home Below:</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $sent_to_nurse_home_url = '/sent-to-nurse-home';
                    $sent_to_nurse_home_csrf = csrf_token();
                    $content .= '<form action="' . $sent_to_nurse_home_url . '" method="GET"
                                      class="form-horizontal">';
                    $content .= '<input type="hidden" name="_token" value="' . $sent_to_nurse_home_csrf . '">';
                    $content .= '<div class="modal-body">';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id"
                             value="' . $applicant['id'] . '">';
                    $content .= '<input type="hidden" name="sale_hidden_id" value="' . $job['id'] . '">';
                    $content .= '<textarea name="details" class="form-control" cols="30" rows="4"
                            placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    $content .= '<button type="button" class="btn btn-link legitRipple" data-dismiss="modal">Close</button>';
                    $content .= '<button type="submit" class="btn bg-teal legitRipple">Save</button>';
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    // /No Nursing Home Modal
                }
                if (Auth::id()==5) {
                    if ($applicant->is_callback_enable == "no") {
                        // CallBack Modal
                        $content .= '<div id="call_back' . $applicant['id'] . '" class="modal fade"  tabindex="-1">';
                        $content .= '<div class="modal-dialog modal-lg">';
                        $content .= '<div class="modal-content">';
                        $content .= '<div class="modal-header">';
                        $content .= '<h5 class="modal-title">Add Callback Notes Below:</h5>';
                        $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                        $content .= '</div>';
                        $call_back_list_url = '/sent-applicant-to-call-back-list';
                        $call_back_list_csrf = csrf_token();
                        $content .= '<form action="' . $call_back_list_url . '" method="GET"
                                  class="form-horizontal">';
                        $content .= '<input type="hidden" name="_token" value="' . $call_back_list_csrf . '">';
                        $content .= '<div class="modal-body">';
                        $content .= '<div class="form-group row">';
                        $content .= '<label class="col-form-label col-sm-3">Details</label>';
                        $content .= '<div class="col-sm-9">';
                        $content .= '<input type="hidden" name="applicant_hidden_id"
                        value="' . $applicant['id'] . '">';
                        $content .= '<input type="hidden" name="sale_hidden_id" value="' . $job['id'] . '">';
                        $content .= '<textarea name="details" class="form-control" cols="30" rows="4"
                         placeholder="TYPE HERE.." required></textarea>';
                        $content .= '</div>';
                        $content .= '</div>';
                        $content .= '</div>';
                        $content .= '<div class="modal-footer">';
                        $content .= '<button type="button" class="btn btn-link legitRipple" data-dismiss="modal">Close</button>';
                        $content .= '<button type="submit" class="btn bg-teal legitRipple">Save</button>';
                        $content .= '</div>';
                        $content .= '</form>';
                        $content .= '</div>';
                        $content .= '</div>';
                        $content .= '</div>';
                        // /CallBack Modal
                    }
                }
                // Send CV Modal
                $sent_cv_count = Cv_note::where(['sale_id' => $job['id'], 'status' => 'active'])->count();
                $content .= '<div id="sent_cv'.$job['id'].'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Add CV Notes Below:</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
//                    $cv_url = '/applicant-cv-to-quality';
                $cv_url = '/applicant-no-job-cv-to-quality';
                $cv_csrf = csrf_token();
                $content .= '<form action="'.$cv_url.'/'.$applicant->id.'" method="GET"
                                      class="form-horizontal">';
                $content .= '<input type="hidden" name="_token" value="' .$cv_csrf.'">';
                $content .= '<div class="modal-body">';
                $content .= '<div class="form-group row">';
                $content .= '<label class="col-form-label col-sm-3">Sent CV</label>';
                $content .= '<div class="col-sm-9">';
                $content .= '<label class="col-form-label font-weight-semibold">'.$sent_cv_count.' out of '.$job['send_cv_limit'].'</label>';
                $content .= '</div>';
                $content .= '<label class="col-form-label col-sm-3">Details</label>';
                $content .= '<div class="col-sm-9">';
                $content .= '<input type="hidden" name="applicant_hidden_id"
                             value="'.$applicant->id.'">';
                $content .= '<input type="hidden" name="sale_hidden_id"
                             value="'.$job['id'].'">';
                $content .= '<textarea name="details" class="form-control" cols="30" rows="4"
                             placeholder="TYPE HERE.." required></textarea>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn btn-link legitRipple"
                            data-dismiss="modal">Close</button>';
                $content .= '<button type="submit" class="btn bg-teal legitRipple">Save</button>';
                $content .= '</div>';
                $content .= '</form>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                // /Sent CV Modal

                // Add To Non Interest List Modal
                $content .= '<div id="modal_form_horizontal'.$job['id'].'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Enter Reason of Not Interest Below:</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $mark_url = '/mark-applicant';
                $mark_csrf = csrf_token();
                $content .= '<form action="'.$mark_url.'" method="POST"
                                      class="form-horizontal">';
                $content .= '<input type="hidden" name="_token" value="' .$mark_csrf.'">';
                $content .= '<div class="modal-body">';
                $content .= '<div class="form-group row">';
                $content .= '<label class="col-form-label col-sm-3">Reason</label>';
                $content .= '<div class="col-sm-9">';
                $content .= '<input type="hidden" name="applicant_hidden_id"
                             value="'.$applicant->id.'">';
                $content .= '<input type="hidden" name="job_hidden_id"
                             value="'.$job['id'].'">';
                $content .= '<textarea name="reason" class="form-control" cols="30" rows="4"
                             placeholder="TYPE HERE.." required></textarea>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn btn-link legitRipple"
                             data-dismiss="modal">Close</button>';
                $content .= '<button type="submit" class="btn bg-teal legitRipple">Save</button>';
                $content .= '</div>';
                $content .= '</form>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                } elseif ($option == 'sent' || $option == 'reject_job' || $option == 'paid') {

                   if (Auth::id()==1||Auth::id()==101||Auth::id()==66){
                       $history=History::where('applicant_id',$applicant->id)->where('sale_id',$job['id'])->where('sub_stage','no_job_quality_cvs')
                           ->where('status','active')->orderBy('id','desc')->first();
//                       if ($history==null) {


                           $content .= '<a href="#" class="dropdown-item"
                            data-controls-modal="#revert_to_quality_cvs' . $applicant->id . '-' . $job['id'] . '" 
                            data-backdrop="static"
                            data-keyboard="false" data-toggle="modal"
                            data-target="#revert_to_quality_cvs' . $applicant->id . '-' . $job['id'] . '">
                            Revert no job </a>';
//                       }
                   }else{
                       $content .= '<a href="#" class="disabled dropdown-item"> LOCKED</a>';
                       $content .= '<a href="#" class="disabled dropdown-item"> LOCKED</a>';
                       $content .= '<a href="#" class="disabled dropdown-item"> LOCKED</a>';
                       $content .= '<a href="#" class="disabled dropdown-item"> LOCKED</a>';
                   }
                }
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                // Revert To Quality > CVs Modal
                $content .= '<div id="revert_to_quality_cvs' . $applicant->id . '-' . $job['id'] . '" class="modal fade" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Rejected CV Notes</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <form action="' . route('revertQualityCv') . '"
                                  method="POST" class="form-horizontal">
                                <input type="hidden" name="_token" value="' . csrf_token() . '">
                                <div class="modal-body">
                                    <div class="form-group row">
                                        <label class="col-form-label col-sm-3">Details</label>
                                        <div class="col-sm-9">';
                // Conditionally set hidden field for no_job
                if ($applicant->is_no_job == 1) {
                    $content .= '<input type="hidden" name="applicant_hidden_no_job" value="no_job">';
                }
                $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">
                                            <input type="hidden" name="job_hidden_id" value="' . $job['id'] . '">
                                            <textarea name="details" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="rejected_cv_revert_to_quality_cvs" value="rejected_cv_revert_to_quality_cvs" class="btn bg-dark legitRipple">Quality CV</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>';

                return $content;
            })
            ->addColumn('head_office',function($job){
                return $job['office_name'];
            })
			->addColumn('sale_notes',function($job){
                return $job['sale_notes'];
            })
            ->addColumn('head_office_unit',function($job){
                return $job['unit_name'];
            })
            ->editColumn('updated_at', function($job){
                $updatedAt = new Carbon($job['updated_at']);
                return $updatedAt->timestamp;
            })
            ->addColumn('status', function ($job) use($applicant){
                $value_data = 'open';
                foreach ($applicant->cv_notes as $key => $value) {
                    if ($value->sale_id == $job['id']) {
                        if ($value->status == 'active') {

                            $value_data = 'sent';
                            break;
                        } elseif ($value->status == 'disable') {
                            $value_data = 'sent_job';
                            break;
                        } elseif ($value->status == 'paid') {
                            $value_data = 'paid';
                            break;
                        }
                    }
                }
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 bg-teal-800">';
                $status .= strtoupper($value_data);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('sale_status',function ($job){
                $btn='';
                    if($job['status']=='active'){
                        $btn='<span class="badge badge-success">Active</span>';
                    }elseif ($job['status']="disbale"){
                        $btn='<span class="badge badge-danger">Disable</span>';

                    }
                    return $btn;
            })
            ->editColumn('cv_limit',function($job){
                if($job['cv_notes_count']==null)
                {
                    $job['cv_notes_count']=0;
                }
                return $job['cv_notes_count']==$job['send_cv_limit']?'<span class="badge w-100 badge-danger" style="font-size:90%">Limit Reached</span>':"<span class='badge w-100 badge-success' style='font-size:90%'>".((int)$job['send_cv_limit'] - (int)$job['cv_notes_count'])." Cv's limit remaining</span>";
            })
            ->rawColumns(['job_title','sale_notes','head_office','head_office_unit','status','cv_limit','action','sale_status'])
            ->make(true);
    }
    function getAllTitles($job_title)
    {

        $title = array();
        if ($job_title === 'rgn/rmn') {
            $title[0] = "rgn";
            $title[1] = "rmn";
            $title[2] = "rmn/rnld";
            $title[3] = "rgn/rmn/rnld";
            $title[4] = $job_title;
            $title[5] = $job_title;
            $title[6] = $job_title;
            $title[7] = $job_title;
            $title[8] = $job_title;
            $title[9] = $job_title;
            $title[10] = $job_title;
        } elseif ($job_title === "rmn/rnld") {
            $title[0] = "rmn";
            $title[1] = "rnld";
            $title[2] = "rgn/rmn";
            $title[3] = "rgn/rmn/rnld";
            $title[4] = $job_title;
            $title[5] = $job_title;
            $title[6] = $job_title;
            $title[7] = $job_title;
            $title[8] = $job_title;
            $title[9] = $job_title;
            $title[10] = $job_title;
        } elseif ($job_title === "rgn/rmn/rnld") {
            $title[0] = "rmn";
            $title[1] = "rgn";
            $title[2] = "rnld";
            $title[3] = "rgn/rmn";
            $title[4] = "rmn/rnld";
            $title[5] = $job_title;
            $title[6] = $job_title;
            $title[7] = $job_title;
            $title[8] = $job_title;
            $title[9] = $job_title;
            $title[10] = $job_title;
        } elseif ($job_title === 'rgn') {
            $title[0] = "rgn/rmn";
            $title[1] = "rgn/rmn/rnld";
            $title[2] = $job_title;
            $title[3] = $job_title;
            $title[4] = $job_title;
            $title[5] = $job_title;
            $title[6] = $job_title;
            $title[7] = $job_title;
            $title[8] = $job_title;
            $title[9] = $job_title;
            $title[10] = $job_title;
        } elseif ($job_title === "rmn") {
            $title[0] = "rgn/rmn";
            $title[1] = "rmn/rnld";
            $title[2] = "rgn/rmn/rnld";
            $title[3] = $job_title;
            $title[4] = $job_title;
            $title[5] = $job_title;
            $title[6] = $job_title;
            $title[7] = $job_title;
            $title[8] = $job_title;
            $title[9] = $job_title;
            $title[10] = $job_title;
        } elseif ($job_title === "rnld") {
            $title[0] = "rmn/rnld";
            $title[1] = "rgn/rmn/rnld";
            $title[2] = $job_title;
            $title[3] = $job_title;
            $title[4] = $job_title;
            $title[5] = $job_title;
            $title[6] = $job_title;
            $title[7] = $job_title;
            $title[8] = $job_title;
            $title[9] = $job_title;
            $title[10] = $job_title;
        } elseif ($job_title === "senior nurse") {
            $title[0] = "rmn";
            $title[1] = "rgn";
            $title[2] = "rnld";
            $title[3] = "rgn/rmn";
            $title[4] = "rmn/rnld";
            $title[5] = "rgn/rmn/rnld";
            $title[6] = "senior nurse";
            $title[7] = "clinical lead";
            $title[8] = $job_title;
            $title[9] = $job_title;
            $title[10] = $job_title;
        } elseif ($job_title === "nurse deputy manager") {
            $title[0] = "rmn";
            $title[1] = "rgn";
            $title[2] = "rnld";
            $title[3] = "rgn/rmn";
            $title[4] = "rmn/rnld";
            $title[5] = "rgn/rmn/rnld";
            $title[6] = "senior nurse";
            $title[7] = "clinical lead";
            $title[8] = $job_title;
            $title[9] = $job_title;
            $title[10] = $job_title;
        } elseif ($job_title === "nurse manager") {
            $title[0] = "rmn";
            $title[1] = "rgn";
            $title[2] = "rnld";
            $title[3] = "rgn/rmn";
            $title[4] = "rmn/rnld";
            $title[5] = "rgn/rmn/rnld";
            $title[6] = "nurse deputy manager";
            $title[7] = "senior nurse";
            $title[8] = "clinical lead";
            $title[9] = $job_title;
            $title[10] = $job_title;
        } elseif ($job_title === "clinical lead") {
            $title[0] = "rmn";
            $title[1] = "rgn";
            $title[2] = "rnld";
            $title[3] = "rgn/rmn";
            $title[4] = "rmn/rnld";
            $title[5] = "rgn/rmn/rnld";
            $title[6] = "nurse deputy manager";
            $title[7] = "senior nurse";
            $title[8] = "clinical lead";
            $title[9] = $job_title;
            $title[10] = $job_title;
        } elseif ($job_title === "rcn") {
            $title[0] = "rmn";
            $title[1] = "rgn";
            $title[2] = "rnld";
            $title[3] = "rgn/rmn";
            $title[4] = "rmn/rnld";
            $title[5] = "rgn/rmn/rnld";
            $title[6] = "nurse deputy manager";
            $title[7] = "senior nurse";
            $title[8] = "clinical lead";
            $title[9] = "rcn";
            $title[10] = $job_title;
        } elseif ($job_title === "head chef") {
            $title[0] = "sous chef";
            $title[1] = "Senior sous chef";
            $title[2] = "junior sous chef";
            $title[3] = "head chef";
            $title[4] = $job_title;
            $title[5] = $job_title;
            $title[6] = $job_title;
            $title[7] = $job_title;
            $title[8] = $job_title;
            $title[9] = $job_title;
            $title[10] = $job_title;
        } elseif ($job_title === "sous chef") {
            $title[0] = "chef de partie";
            $title[1] = "Senior chef de partie";
            $title[2] = "sous chef";
            $title[3] = $job_title;
            $title[4] = $job_title;
            $title[5] = $job_title;
            $title[6] = $job_title;
            $title[7] = $job_title;
            $title[8] = $job_title;
            $title[9] = $job_title;
            $title[10] = $job_title;
        }
        elseif ($job_title === "chef de partie") {
            $title[0] = "junior chef de partie";
            $title[1] = "Demmi chef de partie";
            $title[2] = "chef de partie";
            $title[3] = $job_title;
            $title[4] = $job_title;
            $title[5] = $job_title;
            $title[6] = $job_title;
            $title[7] = $job_title;
            $title[8] = $job_title;
            $title[9] = $job_title;
            $title[10] = $job_title;
        }
        else {
            $title[0] = $job_title;
            $title[1] = $job_title;
            $title[2] = $job_title;
            $title[3] = $job_title;
            $title[4] = $job_title;
            $title[5] = $job_title;
            $title[6] = $job_title;
            $title[7] = $job_title;
            $title[8] = $job_title;
            $title[9] = $job_title;
            $title[10] = $job_title;
        }
        return $title;
    }

    function job_distance($lat, $lon, $radius, $applicant_job_title)
    {
//        dd($lat,$lon);
        $title = $this->getAllTitles($applicant_job_title);

//        $location_distance = Sale::select(DB::raw("*, ((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) +
//                COS($lat * PI() / 180) * COS(lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515)
//                AS distance"),DB::raw("(SELECT count(cv_notes.sale_id) from cv_notes
//                WHERE cv_notes.sale_id=sales.id AND cv_notes.status='active' group by cv_notes.sale_id) as cv_notes_count"))->having("distance", "<", $radius)->orderBy("distance");
////        $location_distance = $location_distance->where("job_title", $title[0])->orWhere("job_title", $title[1])->orWhere("job_title", $title[2])->orWhere("job_title", $title[3])->orWhere("job_title", $title[4])->orWhere("job_title", $title[5])->orWhere("job_title", $title[6])->orWhere("job_title", $title[7])->get();
//        $location_distance = $location_distance->where(function ($query) use ($title) {
//            $query->orWhere("job_title", $title[0]);
//            $query->orWhere("job_title", $title[1]);
//            $query->orWhere("job_title", $title[2]);
//            $query->orWhere("job_title", $title[3]);
//            $query->orWhere("job_title", $title[4]);
//            $query->orWhere("job_title", $title[5]);
//            $query->orWhere("job_title", $title[6]);
//            $query->orWhere("job_title", $title[7]);
//            $query->orWhere("job_title", $title[8]);
//            $query->orWhere("job_title", $title[9]);
//            $query->orWhere("job_title", $title[10]);
//        })->whereIn("status",[ "active",'disable'])->whereIn("is_on_hold", ["0","1"])->get();

        $location_distance2 = Sale::select(
            '*',
            DB::raw("((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) +
                COS($lat * PI() / 180) * COS(lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) AS distance"),
            DB::raw("(SELECT count(cv_notes.sale_id) FROM cv_notes
                WHERE cv_notes.sale_id = sales.id AND cv_notes.status='active' GROUP BY cv_notes.sale_id) AS cv_notes_count")
        )->having("distance", "<", $radius)->orderBy("distance")
            ->where(function ($query) use ($title) {
                foreach ($title as $jobTitle) {
                    $query->orWhere("job_title", $jobTitle);
                }
            })
            ->whereIn("status", ["active", 'disable'])
            ->whereIn("is_on_hold", ["0", "1"])
            ->groupBy('job_title', 'head_office','head_office_unit')->get();
//        dd($location_distance2->count());
//        $location_distance2 = $location_distance2->where(function ($query) use ($title) {
//            $query->orWhere("job_title", $title[0]);
//            $query->orWhere("job_title", $title[1]);
//            $query->orWhere("job_title", $title[2]);
//            $query->orWhere("job_title", $title[3]);
//            $query->orWhere("job_title", $title[4]);
//            $query->orWhere("job_title", $title[5]);
//            $query->orWhere("job_title", $title[6]);
//            $query->orWhere("job_title", $title[7]);
//            $query->orWhere("job_title", $title[8]);
//            $query->orWhere("job_title", $title[9]);
//            $query->orWhere("job_title", $title[10]);
//        })->get();

        return $location_distance2;
    }
    function check_not_interested_in_jobs($job_object, $applicant_id)
    {
        $pivot_result = array();
        $app_id = '';
        foreach ($job_object as $key => $value) {
            $job_id = $value->id;
            $pivot_result = Applicants_pivot_sales::where("applicant_id", $applicant_id)->where("sales_id", $job_id)->first();
            if (!empty($pivot_result)) {
                $job_object->forget($key);
            }

            /***
            $pivot_result[] = Applicants_pivot_sales::where("applicant_id", $applicant_id)->where("sales_id", $job_id)->first();
            foreach ($pivot_result as $res) {
            $app_id = $res['applicant_id'];
            $job_db_id = $res['sales_id'];
            }
            if (($applicant_id == $app_id) && ($job_id == $job_db_id)) {
            $job_object->forget($key);
            }
             */
        }
        return $job_object->toArray();
    }

    public function getApplicantNoJobCvSendToQuality($applicant_cv_id)
    {
        $audit_data['action'] = "Send CV";
        date_default_timezone_set('Europe/London');
        $audit_data['applicant'] = $applicant = request()->applicant_hidden_id;
        $audit_data['sale'] = $sale = request()->sale_hidden_id;
        $applicant_title_prof = Applicant::find($audit_data['applicant']);
        $sale_title_prof = Sale::find($sale);
        if($applicant_title_prof->job_title_prof==$sale_title_prof->job_title_prof)
        {


            // echo $sale_title_prof->job_title_prof;exit();

            $detail_note = request()->details;

            $sale_details = Sale::find($sale);
            if ($sale_details) {

//                $sent_cv_count = Cv_note::where(['sale_id' => $sale, 'status' => 'active'])->count();
//                if ($sent_cv_count < $sale_details->send_cv_limit) {

                    $applicants_rejected = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
                        ->where('applicants.status', 'active');
                    $applicants_rejected = $applicants_rejected->where('is_in_crm_reject', 'yes')
                        ->orWhere('is_in_crm_request_reject', 'yes')
                        ->orWhere('is_crm_interview_attended', 'no')
                        ->orWhere('is_in_crm_start_date_hold', 'yes')
                        ->orWhere('is_in_crm_dispute', 'yes')
                        ->orWhere([['is_CV_reject', 'yes'], ["quality_notes.moved_tab_to", "rejected"]])
                        ->get();

                    $rejectedApp = 0;
                    foreach ($applicants_rejected as $app) {
                        if ($app->id == $applicant_cv_id) {
                            $rejectedApp = 1;
                        }
                    }
                    // echo 'limit'.$rejectedApp;exit();

                    Applicant::where('id', $applicant_cv_id)->update(['is_cv_in_quality' => 'yes']);
                    $user = Auth::user();
                    $current_user_id = $user->id;
                    $cv_note = new Cv_note();
                    $cv_note->sale_id = $sale;
                    $cv_note->user_id = $current_user_id;
                    $cv_note->applicant_id = $applicant;
                    $cv_note->status = 'disable';
                    $audit_data['detail_note'] = $cv_note->details = $detail_note;
                    $audit_data['added_date'] = $cv_note->send_added_date = date("jS F Y");
                    $audit_data['added_time'] = $cv_note->send_added_time = date("h:i A");
                    $cv_note->save();
                    $last_inserted_note = $cv_note->id;
                    if ($last_inserted_note > 0) {
                        $cv_note_uid = md5($last_inserted_note);
                        Cv_note::where('id', $last_inserted_note)->update(['cv_uid' => $cv_note_uid]);
                        $history = new History();
                        $history->applicant_id = $applicant;
                        $history->user_id = $current_user_id;
                        $history->sale_id = $sale;
                        $audit_data['stage'] = $history->stage = 'quality';
                        $audit_data['sub_stage'] = $history->sub_stage = 'no_job_quality_cvs';
//                        $audit_data['sub_stage'] = $history->sub_stage = 'quality_cvs';
                        $history->history_added_date = date("jS F Y");
                        $history->history_added_time = date("h:i A");
                        $history->save();
                        $last_inserted_history = $history->id;
                        if ($last_inserted_history > 0) {
                            $history_uid = md5($last_inserted_history);
                            History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                            $revertRecord=RevertStage::create([
                                'applicant_id'=>$applicant,
                                'sale_id'=>$sale,
                                'revert_added_date' =>date("jS F Y"),
                                'revert_added_time' =>date("h:i A"),
                                'stage'=>'no_job_quality_cvs',
                                'user_id'=>$current_user_id,
                                'notes'=>'Applicant has been sent to quality',
                            ]);
                            if ($rejectedApp == 1) {
                                return Redirect::back()->with('qualityApplicantMsg', 'Applicant has been sent to quality');
                            } else
                                return Redirect::back()->with('qualityApplicantErr', 'Applicant Cant be Sent');
                        }
                    } else {
                        if ($rejectedApp == 1)
                            return Redirect::back()->with('qualityApplicantMsg', 'Applicant has been sent to quality');
                        else
                            return Redirect::back()->with('qualityApplicantErr', 'Applicant Cant be Sent');
                    }
//                } else {
//                    return Redirect::back()->with('notFoundCv','WHOOPS! You cannot perform this action. Send CV Limit for this Sale has reached maximum.');
//                }
            } else {
                return Redirect::back()->with('notFoundCv','Sale not found.');
            }
        }
        else
        {

            return Redirect::back()->with('error','Specialist Title is mismatched!');
        }
    }
    public function updateCvCLearNoJobInterview($applicant_id, $viewString)
    {
//        dd('ada');
        date_default_timezone_set('Europe/London');
        $audit_data['action'] = "Clear";
        $details = request()->details;
        $audit_data['sale'] = $sale_id = request()->job_hidden_id;
        $unit_name = Sale::join('units', 'sales.head_office_unit', '=', 'units.id')
            ->where('sales.id','=', $sale_id)
            ->select('units.unit_name')->first();
        $unit_name =  $unit_name->unit_name;
        $user = Auth::user();
        $current_user_id = $user->id;
        $applicant_phone = Applicant::select('applicant_phone')->find($applicant_id);
        $applicant_phone = $applicant_phone->applicant_phone;
        Applicant::where("id", $applicant_id)->update(['is_interview_confirm' => 'yes', 'is_cv_in_quality_clear' => 'yes', 'is_cv_in_quality' => 'no']);
        $quality_notes = new Quality_notes();
        $audit_data['applicant'] = $quality_notes->applicant_id = $applicant_id;
        $quality_notes->user_id = $current_user_id;
        $quality_notes->sale_id = $sale_id;
        $audit_data['details'] = $quality_notes->details = $details;
        $audit_data['added_date'] = $quality_notes->quality_added_date = date("jS F Y");
        $audit_data['added_time'] = $quality_notes->quality_added_time = date("h:i A");
        $quality_notes->moved_tab_to = "cleared_no_job";
        //old status quality clear cv
//        $quality_notes->moved_tab_to = "cleared";
        $quality_notes->save();

        /*** activity log
         * $action_observer = new ActionObserver();
         * $action_observer->action($audit_data, 'Quality');
         */

        $last_inserted_note = $quality_notes->id;
        if ($last_inserted_note > 0) {
            $quality_note_uid = md5($last_inserted_note);
            Quality_notes::where('id', $last_inserted_note)->update(['quality_notes_uid' => $quality_note_uid]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $current_user_id;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->crm_added_date = date("jS F Y");
            $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "cv_sent_no_job";
            // sent cv to clear  quality
//            $crm_notes->moved_tab_to = "cv_sent";
            $crm_notes->save();
            $last_inserted_note = $crm_notes->id;
            if ($last_inserted_note > 0) {
                $crm_note_uid = md5($last_inserted_note);
                Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
                History::where([
                    "applicant_id" => $applicant_id,
                    "sale_id" => $sale_id
                ])->update(["status" => "disable"]);
                $history = new History();
                $history->applicant_id = $applicant_id;
                $history->user_id = $current_user_id;
                $history->sale_id = $sale_id;
                $history->stage = 'quality';
                $history->sub_stage = 'quality_cleared_no_job';
                //old status cv clear
//                $history->sub_stage = 'quality_cleared';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if ($last_inserted_history > 0) {
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    //$applicant_numbers='07597019065';
//                    $applicant_number = $applicant_phone;
//                    $applicant_message = 'Hi Thank you for your time over the phone. I am sharing your resume details with the manager of '.$unit_name.' for the discussed vacancy. If you have any questions, feel free to reach out. Thank you for choosing Kingbury to represent you. Best regards, CRM TEAM T: 01494211220 E: crm@kingsburypersonnel.com';
//
//                    $query_string = 'http://milkyway.tranzcript.com:1008/sendsms?username=admin&password=admin&phonenumber='.$applicant_number.'&message='.$applicant_message.'&port=1&report=JSON&timeout=0';
//
//                    $sms_res = $this->sendQualityClearSms($query_string);
//                    $smsSaveRes='';
//                    if($sms_res['result'] == 'success')
//                    {
//                        $userData = json_decode($sms_res['data'], true);
//                        $message = $userData['message'];
//                        $phone = $userData['report'][0][1][0]['phonenumber'];
//                        $timeString = $userData['report'][0][1][0]['time'];
//                        $sms_response = $this->saveQualityClearSendMessage($message, $phone, $timeString);
//                        if($sms_response)
//                        {
//                            $smsSaveRes = 'success';
//                        }
//                        else
//                        {
//                            $smsSaveRes = 'error';
//                        }
//                        // echo $message.' and number: '.$phone.' time: '.$timeString;exit();
//                        $smsResult = 'Successfuly!';
//
//                    }
//                    else
//                    {
//                        $smsResult = 'Error';
//                    }
                    $smsSaveRes = 'error';
                    $smsResult = 'Error';
                    return redirect()->route($viewString)->with('success', 'Applicant against position is cleared from quality and quality sms sent is '.$smsResult.' sms save status is '.$smsSaveRes );
                }
            } else {
                return redirect()->route($viewString);
            }
        }
    }



}
