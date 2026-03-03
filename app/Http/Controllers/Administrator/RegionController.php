<?php

namespace Horsefly\Http\Controllers\Administrator;

use Horsefly\Applicant;
// use Horsefly\Observers\ActionObserver;
use Horsefly\Exports\ApplicantsExport;
use Horsefly\Exports\ResourcesExport;
use Horsefly\Exports\Applicants_nurses_15kmExport;
use Horsefly\Exports\Applicants_nureses_7_days_export;
use Horsefly\Exports\Regions_applicants_export;
use Maatwebsite\Excel\Facades\Excel;
use Horsefly\ApplicantNote;
use Horsefly\User;
use Illuminate\Http\Request;
use Horsefly\Http\Controllers\Controller;
use Horsefly\Office;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Permission;
use Horsefly\Unit;
use Horsefly\Sale;
use Horsefly\Applicants_pivot_sales;
use Horsefly\Notes_for_range_applicants;
use Horsefly\Specialist_job_titles;
use Horsefly\Cv_note;
use Horsefly\Crm_note;
use Horsefly\ModuleNote;
use Horsefly\Crm_rejected_cv;
use Horsefly\Quality_notes;
use Horsefly\Region;
use Illuminate\Support\Facades\DB;
// use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use Session;
use Carbon\Carbon;
//use Auth;
use Redirect;
use DateTime;
use Yajra\DataTables\DataTables;
use Horsefly\Exports\SalesRegionExport;
use Horsefly\Sales_notes;




class RegionController extends Controller
{
     public function __construct()
    {
        
        $this->middleware('auth');
        /*** Sub-Links Permissions */
        $this->middleware('permission:region', ['only' => ['index']]);
        //$this->middleware('permission:region_applicants', ['only' => ['regionApplicants','getRegionApplicants']]);
        //$this->middleware('permission:region_positions', ['only' => ['getRegionSales','regionNursesSales','getRegionNursesSales']]);
        //$this->middleware('permission:applicant_export', ['only' => ['regionExport_csv','exportRegionApplicants']]);
        
		 // $this->middleware('permission:resource_Non-Nurses-list', ['only' => ['getNonNurseSales','getNonNursingJob']]);
        // $this->middleware('permission:resource_Last-7-Days-Applicants', ['only' => ['getLast7DaysApplicantAdded','get7DaysApplicants']]);
        // $this->middleware('permission:resource_Last-21-Days-Applicants', ['only' => ['getLast21DaysApplicantAdded','get21DaysApplicants']]);
        // $this->middleware('permission:resource_All-Applicants', ['only' => ['getLast2MonthsApplicantAdded','get2MonthsApplicants']]);
        // $this->middleware('permission:resource_Crm-All-Rejected-Applicants', ['only' => ['getAllCrmRejectedApplicantCv','allCrmRejectedApplicantCvAjax']]);
        // $this->middleware('permission:resource_Crm-Rejected-Applicants', ['only' => ['getCrmRejectedApplicantCv','getCrmRejectedApplicantCvAjax']]);
        // $this->middleware('permission:resource_Crm-Request-Rejected-Applicants', ['only' => ['getCrmRequestRejectedApplicantCv','getCrmRequestRejectedApplicantCvAjax']]);
        // $this->middleware('permission:resource_Crm-Not-Attended-Applicants', ['only' => ['getCrmNotAttendedApplicantCv','getCrmNotAttendedApplicantCvAjax']]);
        // $this->middleware('permission:resource_Crm-Start-Date-Hold-Applicants', ['only' => ['getCrmStartDateHoldApplicantCv','getCrmStartDateHoldApplicantCvAjax']]);
        // $this->middleware('permission:resource_Crm-Paid-Applicants', ['only' => ['getCrmPaidApplicantCv','getCrmPaidApplicantCvAjax']]);
        // /*** Callback Permissions */
        // $this->middleware('permission:resource_Potential-Callback_list|resource_Potential-Callback_revert-callback', ['only' => ['potentialCallBackApplicants','getPotentialCallBackApplicants']]);
        // $this->middleware('permission:resource_Potential-Callback_revert-callback', ['only' => ['getApplicantRevertToSearchList']]);
        // $this->middleware('permission:applicant_export', ['only' => ['export_7_days_applicants_date','export_Last21DaysApplicantAdded','export_Last2MonthsApplicantAdded',
        // 'export_15_km_applicants','exportAllCrmRejectedApplicantCv','Export_CrmRejectedApplicantCv','getCrmRequestRejectedApplicantCv','exportCrmNotAttendedApplicantCv'
        // ,'exportCrmStartDateHoldApplicantCv','exportCrmPaidApplicantCv','exportPotentialCallBackApplicants']]);

    }
    public function index()
    {
        $regions = Region::all();
        return response()->json(['success' => $regions]);
    }

    public function regionApplicants($id,$category)
    {
        // $interval = 7;
        // echo $category;exit();
        return view('administrator.region.region_applicants',compact('id','category'));
    }


    public function getRegionApplicants($id,$category)
    {
        
        // $end_date = Carbon::now();
        // $edate = $end_date->format('Y-m-d') . " 23:59:59";
        // $start_date = $end_date->subDays(9);
        // $sdate = $start_date->format('Y-m-d') . " 00:00:00";
        $reg = Region::where('id',$id)->first();
        $district = $reg['districts_code'];
        // print_r($res);exit();
// echo $res['districts_code'];exit();
        // $reg_applicants = DB::select(DB::raw("SELECT * FROM applicants WHERE UPPER(TRIM(applicant_postcode)) REGEXP '^('$res['districts_code']')[0-9]' "));
        $result_data = Applicant::with('cv_notes')
                    ->select('applicants.id', 'applicants.updated_at', 'applicants.applicant_added_time', 'applicants.applicant_name','applicants.applicant_email', 'applicants.applicant_job_title', 'applicants.job_category', 'applicants.applicant_postcode', 'applicants.applicant_phone','applicants.applicant_homePhone','applicants.applicant_source','applicants.applicant_notes','applicants.paid_status')
                    ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id');
                    if ($category == 44) {
                 $result_data = $result_data->whereIn('applicants.job_category', ['nurse']);
                } else {
                 $result_data = $result_data->whereIn('applicants.job_category', ['non-nurse','chef']);
                }
                   $result= $result_data->where("applicants.status", "=", "active")
                    //->where("applicants.job_category", "=", $category)
                    ->where("applicants.is_blocked", "=", "0")
                    
                    // ->where('applicants_pivot_sales.applicant_id', '=', NULL)
                    ->whereRaw("UPPER(TRIM(applicants.applicant_postcode)) REGEXP '^($district)[0-9]'");
                //    $result->whereRaw("UPPER(TRIM(applicants.applicant_postcode)) REGEXP '^($district)[0-9]' limit 50");
                    // print_r($exp);exit();

        return datatables()->of($result)
//            ->filter(function ($query) {
//                if (request()->has('created_at')) {
//                    $date = new DateTime(request('created_at'));
//                    $date = date_format($date, 'Y-m-d');
//                    $query->whereDate('applicants.created_at', $date);
//                }
//            })
            ->addColumn('applicant_postcode', function ($applicant) {
                $status_value = 'open';
                $postcode = '';
                if ($applicant->paid_status == 'close') {
                    $status_value = 'paid';
                } else {
                    foreach ($applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $status_value = 'sent';
                            break;
                        } elseif ($value->status == 'disable') {
                            $status_value = 'reject';
                        }
                    }
                }
                /*** old logic before open applicant cv
                foreach ($applicant->cv_notes as $key => $value) {
                    if ($value->status == 'active') {
                        $status_value = 'sent'; // alert-success
                        break;
                    } elseif ($value->status == 'disable') {
                        $status_value = 'reject'; // alert-danger
                    } elseif ($value->status == 'paid') {
                        $status_value = 'paid';
                        break;
                    }
                }
                */
                if ($status_value == 'open' || $status_value == 'reject') {
                    $postcode .= '<a href="/available-jobs/'.$applicant->id.'">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }
                return $postcode;
            })
            ->addColumn("updated_at",function($applicant){
                $updated_at = new DateTime($applicant->updated_at);
                $date = date_format($updated_at,'d F Y');
                    return $date;
                })
                ->addColumn('applicant_notes', function($applicant){

                    $app_new_note = ModuleNote::where(['module_noteable_id' =>$applicant->id, 'module_noteable_type' =>'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                    $app_notes_final='';
                    if($app_new_note){
                        $app_notes_final = $app_new_note->details;

                    }
                    else{
                        $app_notes_final = $applicant->applicant_notes;
                    }
                $status_value = 'open';
                $postcode = '';
                if ($applicant->paid_status == 'close') {
                    $status_value = 'paid';
                } else {
                    foreach ($applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $status_value = 'sent';
                            break;
                        } elseif ($value->status == 'disable') {
                            $status_value = 'reject';
                        }
                    }
                }
                   
                if($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject')
                {
                    
                $content = '';
                // if ($status_value == 'open' || $status_value == 'reject'){

                $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant->id.'"
                                 data-controls-modal="#clear_cv'.$applicant->id.'"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"'.$app_notes_final.'"</a>';
                $content .= '<div id="clear_cv' . $applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Notes</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<form action="' . route('block_or_casual_notes') . '" method="POST" id="app_notes_form' . $applicant->id . '" class="form-horizontal">';
                $content .= csrf_field();
                $content .= '<div class="modal-body">';
                $content .='<div id="app_notes_alert' . $applicant->id . '"></div>';
                $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                $content .= '<div class="form-group row">';
                $content .= '<label class="col-form-label col-sm-3">Details</label>';
                $content .= '<div class="col-sm-9">';
                $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="7_days_applicants">';
                $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id .'" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                $content .= '</div>';
                $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id .'">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '</select>';
                    $content .= '</div>';
                    $content .= '</div>';

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                   
                    $content .= '<button type="button" class="btn bg-dark legitRipple sent_cv_submit" data-dismiss="modal">Close</button>';
                    
                    $content .= '<button type="submit" data-note_key="' . $applicant->id . '" value="cv_sent_save" class="btn bg-teal legitRipple sent_cv_submit app_notes_form_submit">Save</button>';

                $content .= '</div>';
                $content .= '</form>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
            // } else {
                // $content .= $applicant->applicant_notes;
                // }

                    //return $app_notes_final;
                   return $content;
            }else
            {
               return $app_notes_final;
            }
            // return $content;

                })
                
            ->addColumn('history', function ($applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant->id.'"; 
                                 data-controls-modal="#reject_history'.$applicant->id.'" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">'.$applicant->applicant_name.'</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

                /*** Details are fetched via ajax request */

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;

            })
            ->addColumn('status', function ($applicant) {
                $status_value = 'open';
                $color_class = 'bg-teal-800';
                if ($applicant->paid_status == 'close') {
                    $status_value = 'paid';
                    $color_class = 'bg-slate-700';
                } else {
                    foreach ($applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $status_value = 'sent';
                            break;
                        } elseif ($value->status == 'disable') {
                            $status_value = 'reject';
                        }
                    }
                }
                /*** logic before open-applicant-cv-feature
                foreach ($applicant->cv_notes as $key => $value) {
                    if ($value->status == 'active') {
                        $status_value = 'sent';
                        break;
                    } elseif ($value->status == 'disable') {
                        $status_value = 'reject';
                    } elseif ($value->status == 'paid') {
                        $status_value = 'paid';
                        $color_class = 'bg-slate-700';
                        break;
                    }
                }
                */
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 '.$color_class.'">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $download = '<a href="'. route('downloadApplicantCv',$applicant->id).'">
                   <i class="fas fa-file-download text-teal-400" style="font-size: 30px;"></i>
                </a>';
                return $download;
        })
        ->addColumn('updated_cv', function ($applicant) {
            return
                '<a href="' . route('downloadUpdatedApplicantCv', $applicant->id) . '">
                   <i class="fas fa-file-download text-teal-400" style="font-size: 30px;"></i>
                </a>';
        })
         ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } else { /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
                    foreach ($applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $row_class = 'class_success';
                            break;
                        } elseif ($value->status == 'disable') {
                            $row_class = 'class_danger';
                        }
                    }
                }
                /*** logic before open-applicant-cv-feature
                foreach ($applicant->cv_notes as $key => $value) {
                    if ($value->status == 'active') {
                        $row_class = 'class_success'; // status: sent
                        break;
                    } elseif ($value->status == 'disable') {
                        $row_class = 'class_danger'; // status: reject
                    } elseif ($value->status == 'paid') {
                        $row_class = 'class_dark';
                        break;
                    }
                }
                */
                return $row_class;
            })
            ->rawColumns(['updated_at','download','updated_cv','upload','applicant_notes','status','applicant_postcode', 'history'])
            ->make(true);
		
	/*	->addColumn('upload', function ($applicant) {
            return
            '<a href="#"
            data-controls-modal="#import_applicant_cv" class="import_cv"
            data-backdrop="static"
            data-keyboard="false" data-toggle="modal" data-id="'.$applicant->id.'"
            data-target="#import_applicant_cv">
             <i class="fas fa-file-upload text-teal-400" style="font-size: 30px;"></i>
             &nbsp;</a>';
        }) ***/
    }
	
	public function regionAppNonNurseSpec($id,$category)
    {
        // $interval = 7;
        // echo $category;exit();
        return view('administrator.region.region_app_nonnurse_spec',compact('id','category'));
    }
    public function getRegionAppNonNurseSpecialist($id,$category)
    {
        if($category==44)
        {
            $category='nurse';
        }
        else
        {
            $category='non-nurse';

        }
        $reg = Region::where('id',$id)->first();
        $district = $reg['districts_code'];
        // echo $district;exit();
        $result = Applicant::with('cv_notes')
                    ->select('applicants.id', 'applicants.updated_at', 'applicants.applicant_added_time', 'applicants.applicant_name','applicants.applicant_email', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_postcode', 'applicants.applicant_phone','applicants.applicant_homePhone','applicants.applicant_source','applicants.applicant_notes','applicants.paid_status')
                    ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
                    ->where("applicants.status", "=", "active")
                    ->where("applicants.job_category", "=", $category)
                    ->where("applicants.applicant_job_title", "=", "nonnurse specialist")
                    ->where("applicants.is_blocked", "=", "0")
                    ->whereRaw("UPPER(TRIM(applicants.applicant_postcode)) REGEXP '^($district)[0-9]'");
                    // print_r($result);exit();

        return datatables()->of($result)
            ->addColumn('applicant_postcode', function ($applicant) {
                $status_value = 'open';
                $postcode = '';
                if ($applicant->paid_status == 'close') {
                    $status_value = 'paid';
                } else {
                    foreach ($applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $status_value = 'sent';
                            break;
                        } elseif ($value->status == 'disable') {
                            $status_value = 'reject';
                        }
                    }
                }
                if ($status_value == 'open' || $status_value == 'reject') {
                    $postcode .= '<a href="/available-jobs/'.$applicant->id.'">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }
                return $postcode;
            })
            ->addColumn("updated_at",function($applicant){
                $updated_at = new DateTime($applicant->updated_at);
                $date = date_format($updated_at,'d F Y');
                    return $date;
                })
                ->addColumn('applicant_notes', function($applicant){

                    $app_new_note = ModuleNote::where(['module_noteable_id' =>$applicant->id, 'module_noteable_type' =>'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                    $app_notes_final='';
                    if($app_new_note){
                        $app_notes_final = $app_new_note->details;

                    }
                    else{
                        $app_notes_final = $applicant->applicant_notes;
                    }
                $status_value = 'open';
                $postcode = '';
                if ($applicant->paid_status == 'close') {
                    $status_value = 'paid';
                } else {
                    foreach ($applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $status_value = 'sent';
                            break;
                        } elseif ($value->status == 'disable') {
                            $status_value = 'reject';
                        }
                    }
                }
                   
                if($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject')
                {
                    
                $content = '';

                $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant->id.'"
                                 data-controls-modal="#clear_cv'.$applicant->id.'"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"'.$app_notes_final.'"</a>';
                $content .= '<div id="clear_cv' . $applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Notes</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<form action="' . route('block_or_casual_notes') . '" method="POST" id="app_notes_form' . $applicant->id . '" class="form-horizontal">';
                $content .= csrf_field();
                $content .= '<div class="modal-body">';
                $content .='<div id="app_notes_alert' . $applicant->id . '"></div>';
                $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                $content .= '<div class="form-group row">';
                $content .= '<label class="col-form-label col-sm-3">Details</label>';
                $content .= '<div class="col-sm-9">';
                $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="7_days_applicants">';
                $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id .'" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                $content .= '</div>';
                $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id .'">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '</select>';
                    $content .= '</div>';
                    $content .= '</div>';

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                   
                    $content .= '<button type="button" class="btn bg-dark legitRipple sent_cv_submit" data-dismiss="modal">Close</button>';
                    
                    $content .= '<button type="submit" data-note_key="' . $applicant->id . '" value="cv_sent_save" class="btn bg-teal legitRipple sent_cv_submit app_notes_form_submit">Save</button>';

                $content .= '</div>';
                $content .= '</form>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                   return $content;
            }else
            {
               return $app_notes_final;
            }

                })
                
            ->addColumn('history', function ($applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant->id.'"; 
                                 data-controls-modal="#reject_history'.$applicant->id.'" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">'.$applicant->applicant_name.'</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

                /*** Details are fetched via ajax request */

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;

            })
			->editColumn('applicant_job_title', function ($applicants) {
                    $selected_prof_data = Specialist_job_titles::select("specialist_prof")->where("id", $applicants->job_title_prof)->first();
                    $spec_job_title = ($applicants->job_title_prof!='')?$applicants->applicant_job_title.' ('.$selected_prof_data->specialist_prof.')':$applicants->applicant_job_title;
                    return $spec_job_title;
    
         })
            ->addColumn('status', function ($applicant) {
                $status_value = 'open';
                $color_class = 'bg-teal-800';
                if ($applicant->paid_status == 'close') {
                    $status_value = 'paid';
                    $color_class = 'bg-slate-700';
                } else {
                    foreach ($applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $status_value = 'sent';
                            break;
                        } elseif ($value->status == 'disable') {
                            $status_value = 'reject';
                        }
                    }
                }
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 '.$color_class.'">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $download = '<a href="'. route('downloadApplicantCv',$applicant->id).'">
                   <i class="fas fa-file-download text-teal-400" style="font-size: 30px;"></i>
                </a>';
                return $download;
        })
        ->addColumn('updated_cv', function ($applicant) {
            return
                '<a href="' . route('downloadUpdatedApplicantCv', $applicant->id) . '">
                   <i class="fas fa-file-download text-teal-400" style="font-size: 30px;"></i>
                </a>';
        })
         ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } else {
                    foreach ($applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $row_class = 'class_success';
                            break;
                        } elseif ($value->status == 'disable') {
                            $row_class = 'class_danger';
                        }
                    }
                }
                return $row_class;
            })
            ->rawColumns(['updated_at','download','updated_cv','upload','applicant_notes','status','applicant_postcode', 'history','applicant_job_title'])
            ->make(true);
		
	/*	->addColumn('upload', function ($applicant) {
            return
            '<a href="#"
            data-controls-modal="#import_applicant_cv" class="import_cv"
            data-backdrop="static"
            data-keyboard="false" data-toggle="modal" data-id="'.$applicant->id.'"
            data-target="#import_applicant_cv">
             <i class="fas fa-file-upload text-teal-400" style="font-size: 30px;"></i>
             &nbsp;</a>';
        }) ***/
    }

    public function regionExport_csv($id)
    {
        $users = User::where(["is_admin" => 0])->get();
        return view('administrator.region.region_applicants_export',compact('users','id'));
    }
    public function exportRegionApplicants(Request $request) 
    {
        $job_category =  $request->user_selected;
        $region_id = $request->region_id;
        if($job_category==44)
        {
            $job_category='nurse';
        }
        else
        {
            $job_category='non-nurse';

        }
        $start_date = $request->input('start_date');
        $start_date = Carbon::parse($start_date)->format('Y-m-d'). " 00:00:00:000";
        $end_date = $request->input('end_date');
        $end_date = Carbon::parse($end_date)->format('Y-m-d'). " 23:59:59:999";
// echo $start_date.' and '.$end_date.' and '.$job_category;exit();
        $not_sents= Applicant::select(
            'id','applicant_phone', 'applicant_name','applicant_homePhone','applicant_job_title',
            'applicant_postcode')->where(function($query){
        $query->doesnthave('CVNote');
    })->whereBetween('created_at', [$start_date, $end_date])->where("job_category", "=", $job_category)
    ->where("is_blocked", "=", "0")->get();
    // echo '<pre>';print_r($not_sents);'echo </pre>';exit();

    $rejecteds= Applicant::select(
        'id','applicant_phone', 'applicant_name','applicant_homePhone','applicant_job_title',
        'applicant_postcode','is_CV_reject','applicant_source')->with('CRMNote')->with('CVNote')
        ->where(function($query){ 
        $query->whereHas('CVNote')
        ->orWhereHas('CRMNote');
        })->whereBetween('updated_at', [$start_date, $end_date])->where("job_category", "=", $job_category)
        ->where("is_blocked", "=", "0")->get();
        
        // dd($rejecteds);
        
        $not_sents->map(function($row){
        $row->sub_stage = "Not Sent";
        unset($row->id);
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
            $row->sub_stage = $rejected_status;
           // unset($row->id);
            unset($row->is_CV_reject);
            $clean_data->push($row);
        }
        
        
        });
            // echo '<pre>';print_r($clean_data);echo '</pre>';exit();


            $arr = array();
            $reslut = array();
            foreach ($clean_data->toArray() as $key => $filter_val) {
                // echo $key;exit();
            $applicants_in_crm = Applicant::join('crm_notes', 'crm_notes.applicant_id', '=', 'applicants.id')
            ->join('sales', 'sales.id', '=', 'crm_notes.sales_id')
            ->join('offices', 'offices.id', '=', 'head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
            ->join('history', function($join) {
            $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
            $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })
            ->select("applicants.*", "applicants.id as app_id", "crm_notes.*", "crm_notes.id as crm_notes_id", "sales.*", "sales.id as sale_id", "sales.postcode as sale_postcode", "sales.job_title as sale_job_title", "sales.job_category as sales_job_category", "sales.status as sale_status", "history.history_added_date", "history.sub_stage","office_name", "unit_name","history.id as history_id","history.updated_at as history_updated","crm_notes.updated_at as crm_updated","crm_notes.moved_tab_to")
            ->where(array('applicants.id' => $filter_val['id'],"history.status"=>"active"))
            ->whereIn('crm_notes.id', function($query){
            $query->select(DB::raw('MAX(id) FROM crm_notes WHERE sales_id=sales.id and applicants.id=applicant_id'));
            })->latest('history.updated_at')->get();

            $history_stages = config('constants.history_stages');
            $quality_array=array("quality_cvs"=>"quality_cvs", "quality_cleared"=>"quality_cleared");
            $history_stages=array_merge($history_stages, $quality_array);
            if(!empty($applicants_in_crm[0]))
            {
            if($history_stages[$applicants_in_crm[0]->sub_stage]=='Sent CV' || $history_stages[$applicants_in_crm[0]->sub_stage]=='Request' || $history_stages[$applicants_in_crm[0]->sub_stage]=='Confirmation' || $history_stages[$applicants_in_crm[0]->sub_stage]=='Rebook' || $history_stages[$applicants_in_crm[0]->sub_stage]=='Attended to Pre-Start Date' || 
            $history_stages[$applicants_in_crm[0]->sub_stage]=='Attended to Pre-Start Date' || 
            $history_stages[$applicants_in_crm[0]->sub_stage]=='Start Date' || 
            $history_stages[$applicants_in_crm[0]->sub_stage]=='Invoice' ||
            $history_stages[$applicants_in_crm[0]->sub_stage]=='Paid' || $history_stages[$applicants_in_crm[0]->sub_stage]=='quality_cvs'
            || $history_stages[$applicants_in_crm[0]->sub_stage]=='quality_cleared')
            {
            if($history_stages[$applicants_in_crm[0]->sub_stage]=='quality_cvs')
            {
                $crm_reject_stages = ["dispute","interview_not_attended","declined","request_reject","cv_sent_reject",
                                        "start_date_hold", "start_date_hold_save"];
                if(in_array($applicants_in_crm[0]->moved_tab_to, $crm_reject_stages))
                    {
                    $res = array_add($rejecteds[$key], 'notes', $applicants_in_crm[0]->moved_tab_to);
                    $arr[]=$res;
                    }
            }
                $rejecteds->forget($key);
            }
            else
            {
                    $res = array_add($rejecteds[$key], 'notes', $applicants_in_crm[0]->moved_tab_to);
                    $arr[]=$res;
            }
            }
                    
            unset( $filter_val['id']); 
            }
            // echo '<pre>';print_r($arr);'echo </pre>';exit();              

               
        return Excel::download(new Regions_applicants_export($start_date,$end_date,$job_category,$region_id), 'applicants.csv');
        
    }

    public function regionNursesSales($id,$category)
    {
        // echo $id.' '.$category;exit();
        $value = '0';
        return view('administrator.region.region_nurses_sales', compact('value','id','category'));
    }
    public function getRegionNursesSales(Request $request,$id='',$category='')
    {

        if($category==44)
        {
            $category='nurse';
        }
        else
        {
            $category='nonnurse';

        }
        $reg = Region::where('id',$id)->first();
        $district = $reg['districts_code'];
        $user = Auth::user();
        $result='';
        if($user->name!=='Super Admin')
        {
            $permissions = $user->getAllPermissions()->pluck('name', 'id');
            $arrays = @json_decode(json_encode($permissions), true);
            $user_permissions = array();
            foreach($arrays as $per){
                if(str_contains($per, 'Hoffice_'))
                {
                    $res = explode("-", $per);
                    $user_permissions[]=$res[1];
    
                }
            }
            if(isset($user_permissions) && count($user_permissions)>0)
            {
                $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
                ->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
                ->select('sales.*', 'offices.office_name', 'units.contact_name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number', 'sales_notes.sale_note')
                    ->whereRaw("UPPER(TRIM(sales.postcode)) REGEXP '^($district)[0-9]'")
                ->where(['sales.status' => 'active', 'sales.is_on_hold' => '0', 'sales.job_category' => $category, 'sales_notes.status' => 'active'])
                ->whereIn('sales.head_office', $user_permissions)->orderBy('id', 'DESC');
                
            }
            else
            {
                $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
            ->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
            ->select('sales.*', 'offices.office_name', 'units.contact_name',
                'units.contact_email', 'units.unit_name', 'units.contact_phone_number', 'sales_notes.sale_note')
                ->whereRaw("UPPER(TRIM(sales.postcode)) REGEXP '^($district)[0-9]'")
            ->where(['sales.status' => 'active', 'sales.is_on_hold' => '0', 'sales.job_category' => $category, 'sales_notes.status' => 'active'])->orderBy('id', 'DESC');

            }
    
        }
        else
        {
            $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
            ->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
            ->select('sales.*', 'offices.office_name', 'units.contact_name',
                'units.contact_email', 'units.unit_name', 'units.contact_phone_number', 'sales_notes.sale_note')
                ->whereRaw("UPPER(TRIM(sales.postcode)) REGEXP '^($district)[0-9]'")
            ->where(['sales.status' => 'active', 'sales.is_on_hold' => '0', 'sales.job_category' => $category, 'sales_notes.status' => 'active'])->orderBy('id', 'DESC');
        }

        // $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
        //     ->join('units', 'units.id', '=', 'sales.head_office_unit')
        //     ->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
        //     ->select('sales.*', 'offices.office_name', 'units.contact_name',
        //         'units.contact_email', 'units.unit_name', 'units.contact_phone_number', 'sales_notes.sale_note')
        //     ->where(['sales.status' => 'active', 'sales.job_category' => 'nurse', 'sales_notes.status' => 'active'])->orderBy('id', 'DESC');

        $aColumns = ['sale_added_date', 'sale_added_time', 'job_title', 'office_name', 'unit_name',
            'postcode', 'job_type', 'experience', 'qualification', 'salary', 'sale_note'];

        $iStart = $request->get('iDisplayStart');
        $iPageSize = $request->get('iDisplayLength');

        $order = 'id';
        $sort = ' DESC';

        if ($request->get('iSortCol_0')) { //iSortingCols

            $sOrder = "ORDER BY  ";

            for ($i = 0; $i < intval($request->get('iSortingCols')); $i++) {
                if ($request->get('bSortable_' . intval($request->get('iSortCol_' . $i))) == "true") {
                    $sOrder .= $aColumns[intval($request->get('iSortCol_' . $i))] . " " . $request->get('sSortDir_' . $i) . ", ";
                }
            }

            $sOrder = substr_replace($sOrder, "", -2);
            if ($sOrder == "ORDER BY") {
                $sOrder = " id ASC";
            }

            $OrderArray = explode(' ', $sOrder);
            $order = trim($OrderArray[3]);
            $sort = trim($OrderArray[4]);

        }

        $sKeywords = $request->get('sSearch');
        if ($sKeywords != "") {

            $result->Where(function ($query) use ($sKeywords) {
                $query->orWhere('job_title', 'LIKE', "%{$sKeywords}%");
                $query->orWhere('office_name', 'LIKE', "%{$sKeywords}%");
                $query->orWhere('unit_name', 'LIKE', "%{$sKeywords}%");
                $query->orWhere('postcode', 'LIKE', "%{$sKeywords}%");
            });
        }

        for ($i = 0; $i < count($aColumns); $i++) {
            $request->get('sSearch_' . $i);
            if ($request->get('bSearchable_' . $i) == "true" && $request->get('sSearch_' . $i) != '') {
                $result->orWhere($aColumns[$i], 'LIKE', "%" . $request->orWhere('sSearch_' . $i) . "%");
            }
        }

        $iFilteredTotal = $result->count();

        if ($iStart != null && $iPageSize != '-1') {
            $result->skip($iStart)->take($iPageSize);
        }

        $result->orderBy($order, trim($sort));
        $result->limit($request->get('iDisplayLength'));
        $saleData = $result->get();
        $iTotal = $iFilteredTotal;
        $output = array(
            "sEcho" => intval($request->get('sEcho')),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );
        $i = 0;

        foreach ($saleData as $sRow) {

            $checkbox = "<label class=\"mt-checkbox mt-checkbox-single mt-checkbox-outline\">
                             <input type=\"checkbox\" class=\"checkbox-index\" value=\"{$sRow->id}\">
                             <span></span>
                          </label>";

            $postcode = "<a href=\"/applicants-within-15-km/{$sRow->id}\">{$sRow->postcode}</a>";

            $action = "<div class=\"list-icons\">
            <div class=\"dropdown\">
                <a href=\"#\" class=\"list-icons-item\" data-toggle=\"dropdown\">
                    <i class=\"icon-menu9\"></i>
                </a>
                <div class=\"dropdown-menu dropdown-menu-right\">
                    <a href=\"#\" class=\"dropdown-item\"
                                               data-controls-modal=\"#manager_details{$sRow->id}\"
                                               data-backdrop=\"static\"
                                               data-keyboard=\"false\" data-toggle=\"modal\"
                                               data-target=\"#manager_details{$sRow->id}\"
                                            > Manager Details </a>
                </div>
            </div>
          </div>
          <div id=\"manager_details{$sRow->id}\" class=\"modal fade\" tabindex=\"-1\">
                            <div class=\"modal-dialog modal-sm\">
                                <div class=\"modal-content\">
                                    <div class=\"modal-header\">
                                        <h5 class=\"modal-title\">Manager Details</h5>
                                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\">&times;</button>
                                    </div>
                                    <div class=\"modal-body\">
                                        <ul class=\"list-group\">
                                            <li class=\"list-group-item active\"><p><b>Name: </b>{$sRow->contact_name}</p>
                                            </li>
                                            <li class=\"list-group-item\"><p><b>Email: </b>{$sRow->contact_email}</p></li>
                                            <li class=\"list-group-item\"><p><b>Phone#: </b>{$sRow->contact_phone_number}</p>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class=\"modal-footer\">
                                        <button type=\"button\" class=\"btn bg-teal legitRipple\" data-dismiss=\"modal\">CLOSE
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>";

//            $history = "<a href=\"#\" class=\"reject_history\" data-applicant=\"{$result->id}\"
//                                 data-controls-modal=\"#reject_history{$result->id}\"
//                                 data-backdrop=\"static\" data-keyboard=\"false\" data-toggle=\"modal\"
//                                 data-target=\"#reject_history{$result->id}\">History</a>
//                        <div id=\"reject_history{$result->id}\" class=\"modal fade\" tabindex=\"-1\">
//                            <div class=\"modal-dialog modal-lg\">
//                                <div class=\"modal-content\">
//                                    <div class=\"modal-header\">
//                                        <h6 class=\"modal-title\">Rejected History - <span class=\"font-weight-semibold\">{$result->applicant_name}</span></h6>
//                                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\">&times;</button>
//                                    </div>
//                                    <div class=\"modal-body\" id=\"applicant_rejected_history{$result->id}\" style=\"max-height: 500px; overflow-y: auto;\">
//                                    </div>
//                                    <div class=\"modal-footer\">
//                                        <button type=\"button\" class=\"btn bg-teal legitRipple\" data-dismiss=\"modal\">Close</button>
//                                    </div>
//                                </div>
//                            </div>
//                        </div>";

            $output['aaData'][] = array(
                "DT_RowId" => "row_{$sRow->id}",
                //    @$checkbox,
                @$sRow->sale_added_date,
                @$sRow->sale_added_time,
                @$sRow->job_title,
                @$sRow->office_name,
                @$sRow->unit_name,
                @$postcode,
                @$sRow->job_type,
                @$sRow->experience,
                @$sRow->qualification,
                @$sRow->salary,
                @$sRow->sale_note,
            );
            $i++;
        }

        //  print_r($output);
        echo json_encode($output);
    }
	
	    public function getRegionSalesRemoveDouble(Request $request, $id = '', $category = '')
    {
      

       
        $reg = Region::where('id', $id)->first();
        $district = $reg['districts_code'];
        $user = Auth::user();
			//dd($user);

         
			//else remo
			
            $result_data = Office::join('sales', function ($join) {
                $join->on('offices.id', '=', 'sales.head_office');
            })
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
                ->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
                ->select('sales.*', 'offices.office_name', 'units.contact_name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number', 'sales_notes.sale_note')
                ->whereRaw("UPPER(TRIM(sales.postcode)) REGEXP '^($district)[0-9]'")
                //->where('sales.status', 'active')
                ->where('sales.is_on_hold', '0');
			   if ($category == 44) {
                 $result_data = $result_data->whereIn('sales.job_category', ['nurse']);

                } else {
                 $result_data = $result_data->whereIn('sales.job_category', ['nonnurse','chef']);
                }
                
               $result=$result_data->where('sales.status', 'active')->where('sales_notes.status', 'active') // Include sales_notes.status in the main query
                ->groupBy('sales.job_title', 'sales.job_category', 'sales.head_office', 'sales.head_office_unit')
                ->orderBy('sales.id', 'DESC')
                ->distinct();
			//dd($result->count());

            return DataTables::of($result)
                ->addIndexColumn()
			

                ->addColumn('action', function ($row) {
                    $modalId = 'manager_details' . $row->id;
                    $action = "<div class=\"list-icons\">
                            <div class=\"dropdown\">
                                <a href=\"#\" class=\"list-icons-item\" data-toggle=\"dropdown\">
                                    <i class=\"icon-menu9\"></i>
                                </a>
                                <div class=\"dropdown-menu dropdown-menu-right\">
                                    <a href=\"#\" class=\"dropdown-item\"
                                        data-controls-modal=\"#{$modalId}\"
                                        data-backdrop=\"static\"
                                        data-keyboard=\"false\" data-toggle=\"modal\"
                                        data-target=\"#{$modalId}\"
                                        > Manager Details </a>
                                </div>
                            </div>
                        </div>
                        <div id=\"{$modalId}\" class=\"modal fade\" tabindex=\"-1\">
                            <div class=\"modal-dialog modal-sm\">
                                <!-- Modal content goes here -->
                            </div>
                        </div>";
                    return $action;
                })
                ->rawColumns(['action'])
                ->make(true);
        
    }
	    public function export($id, $category)
    {
       

        $reg = Region::where('id', $id)->first();
        $district = $reg['districts_code'];

        
			 $result_data = Office::join('sales', function ($join) {
                $join->on('offices.id', '=', 'sales.head_office');
            })
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
                ->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
                ->select('sales.*', 'offices.office_name', 'units.contact_name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number', 'sales_notes.sale_note')
                ->whereRaw("UPPER(TRIM(sales.postcode)) REGEXP '^($district)[0-9]'")
                //->where('sales.status', 'disable')
                ->where('sales.is_on_hold', '0');
             if ($category == 44) {
                 $result_data = $result_data->whereIn('sales.job_category', ['nurse']);

                } else {
                 $result_data = $result_data->whereIn('sales.job_category', ['nonnurse','chef']);
                }

        $result=$result_data->where('sales_notes.status', 'disable') // Include sales_notes.status in the main query
                ->groupBy('sales.job_title', 'sales.job_category', 'sales.head_office', 'sales.head_office_unit', 'sales.postcode')
                ->orderBy('id', 'DESC')
                ->distinct()->get();
       //dd($result->count());

        // Generate and return the CSV file using Maatwebsite\Excel
        return Excel::download(new SalesRegionExport($result), 'sales.csv');
    }
	
	  public function regionCloseSales($id,$category)
    {
//        dd('sda');
        // echo $id.' '.$category;exit();
        $value = '0';
        return view('administrator.region.region_nurses_close_sales', compact('value','id','category'));
    }
	 public function getRegionSalesRemoveCloseDouble(Request $request, $id = '', $category = '')
    {
//

      

        $reg = Region::where('id', $id)->first();
        $district = $reg['districts_code'];
        $user = Auth::user();

//        if ($user->name !== 'Super Admin') {
//            // Handle permissions for non-Super Admin users
//            // Your logic here...
//            if(isset($user_permissions) && count($user_permissions)>0)
//            {
//                $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
//                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
//                    ->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
//                    ->select('sales.*', 'offices.office_name', 'units.contact_name',
//                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number', 'sales_notes.sale_note')
//                    ->whereRaw("UPPER(TRIM(sales.postcode)) REGEXP '^($district)[0-9]'")
//                    ->where(['sales.status' => 'disable', 'sales.is_on_hold' => '0', 'sales.job_category' => $category, 'sales_notes.status' => 'active'])
//                    ->whereIn('sales.head_office', $user_permissions)
//                    ->groupBy('sales.job_title', 'sales.job_category', 'sales.head_office', 'sales.head_office_unit', 'sales.postcode')
//                    ->orderBy('id', 'DESC')
//                    ->distinct();
//
//            }
//            else
//            {
//                $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
//                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
//                    ->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
//                    ->select('sales.*', 'offices.office_name', 'units.contact_name',
//                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number', 'sales_notes.sale_note')
//                    ->whereRaw("UPPER(TRIM(sales.postcode)) REGEXP '^($district)[0-9]'")
//                    ->where(['sales.status' => 'disable', 'sales.is_on_hold' => '0', 'sales.job_category' => $category, 'sales_notes.status' => 'active'])
//                    ->groupBy('sales.job_title', 'sales.job_category', 'sales.head_office', 'sales.head_office_unit', 'sales.postcode')
//                    ->orderBy('id', 'DESC')
//                    ->distinct();
//
//            }
//        }
           $result_data = Office::join('sales', function ($join) {
                $join->on('offices.id', '=', 'sales.head_office');
            })
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
                ->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
                ->select('sales.*', 'offices.office_name', 'units.contact_name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number', 'sales_notes.sale_note')
                ->whereRaw("UPPER(TRIM(sales.postcode)) REGEXP '^($district)[0-9]'")
                //->where('sales.status', 'disable')
                ->where('sales.is_on_hold', '0');
             if ($category == 44) {
                 $result_data = $result_data->whereIn('sales.job_category', ['nurse']);

                } else {
                 $result_data = $result_data->whereIn('sales.job_category', ['nonnurse','chef']);
                }

        $result=$result_data->where('sales_notes.status', 'disable') // Include sales_notes.status in the main query
                ->groupBy('sales.job_title', 'sales.job_category', 'sales.head_office', 'sales.head_office_unit', 'sales.postcode')
                ->orderBy('id', 'DESC')
                ->distinct();

            return DataTables::of($result)
                ->addIndexColumn()
                ->addColumn('postcode',function ($sRow){
                    $postcode = "<a href=\"/region-applicants-within-15-km/{$sRow->id}\">{$sRow->postcode}</a>";

//                    $postcode = "<a href=\"/region-applicants-within-15-km/{$sRow->id}\{$sRow->postcode}</a>";
                    return $postcode;
                })
				->addColumn('sale_note_dis',function ($row){
                    $saleNote=Sales_notes::where('sale_id',$row->id)->orderBY('id','desc')->first();
                    return $saleNote->sale_note;
                })
                ->addColumn('action', function ($row) {
                    $managerModalId = 'manager_details' . $row->id;
                    $salesNotesModalId = 'sales_notes' . $row->id;


                    $managerAction = "<div class=\"list-icons\">
        <div class=\"dropdown\">
            <a href=\"#\" class=\"list-icons-item\" data-toggle=\"dropdown\">
                <i class=\"icon-menu9\"></i>
            </a>
            <div class=\"dropdown-menu dropdown-menu-right\">
                
                <a href=\"#\" class=\"dropdown-item btn abtn-primary\"
                    data-toggle=\"modal\"
                    data-target=\"#{$salesNotesModalId}\"
                    > Add Sales Notes </a>
                             <a href=\"" . route('viewAllCloseNotes', $row->id) . "\" class=\"dropdown-item btn abtn-primary\">Notes</a>
            
                    
                    
            </div>
        </div>
    </div>
    <div id=\"{$managerModalId}\" class=\"modal fade\" tabindex=\"-1\">
        <div class=\"modal-dialog modal-sm\">
            <div class=\"modal-content\">
                <div class=\"modal-header\">
                    <h5 class=\"modal-title\">Manager Details</h5>
                    <button type=\"button\" class=\"close\" data-dismiss=\"modal\">&times;</button>
                </div>
                <div class=\"modal-body\">
                    <!-- Manager Details Content goes here -->
                    <p>Name: {$row->manager_name}</p>
                    <p>Email: {$row->manager_email}</p>
                    <p>Phone: {$row->manager_phone}</p>
                </div>
                <div class=\"modal-footer\">
                    <button type=\"button\" class=\"btn btn-secondary gree\" data-dismiss=\"modal\">Close</button>
                </div>
            </div>
        </div>
    </div>";
                    $csrf = csrf_token();
                    $salesNotesAction = "<div id=\"{$salesNotesModalId}\" class=\"modal fade\" tabindex=\"-1\">
        <div class=\"modal-dialog modal-lg\">
            <div class=\"modal-content\">
                <div class=\"modal-header\">
                    <h5 class=\"modal-title\">Add Sales Notes</h5>
                    <button type=\"button\" class=\"close\" data-dismiss=\"modal\">&times;</button>
                </div>
                <div class=\"modal-body\">
                    <!-- Sales Notes Form goes here -->
                    <form action=\"" . route('sales.notes.store', ['sale' => $row->id]) . "\" method=\"POST\">
                      <input type=\"hidden\" name=\"_token\" value=\"{$csrf}\">
   <!-- Add CSRF token for security -->
    <div class=\"form-group\">
        <input type=\"hidden\" id=\"sale_id\" name=\"sale_id\" value=\"{ $row->id }\">
        <label for=\"sales_notes\">Sales Notes:</label>
        <textarea class=\"form-control\" id=\"sales_notes\" name=\"notes\" rows=\"3\"></textarea>
    </div>
     <div class=\"modal-footer\">
                    <button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Cancel</button>
                    <button type=\"submit\" class=\"btn bg-teal legitRipple\">Save</button>
                </div>
</form>
                </div>
               
            </div>
        </div>
    </div>";

//                    </div>";

                    return $managerAction . $salesNotesAction;
                })


                ->rawColumns(['action','postcode','sale_note_dis'])
                ->make(true);

    }
    public function storeNotes(Request $request,$id)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'notes' => 'required|string|max:255',
        ]);
//dd($request->all(),$id);
        // Store the sales notes
        $sale=Sale::find($id);
        if ($sale!=null) {

            Sales_notes::create([
                'sale_id' => $sale->id,
                'sale_note' => $request->input('notes'),
                'sales_note_added_date' => Carbon::now()->format('jS F Y'),
                'sales_note_added_time' => Carbon::now()->format('h:i A'),
                'user_id' => Auth::id(),
                'status' => 'disable'
            ]);
        }

        // Optionally, redirect back or return a response
        return back()->with('success', 'Sales notes added successfully!');
    }
	    public function get15kmApplicantsRegion($id,$radius='')
    {
        // echo $radius;exit();
//dd('sad');
        $sent_cv_count = Cv_note::where(['sale_id' => $id, 'status' => 'active'])->count();
        $cv_limit = Cv_note::where(['sale_id' => $id, 'status' => 'active'])
            ->count();

        $job = Office::join('sales', 'offices.id', '=', 'sales.head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
            ->select('sales.*', 'offices.office_name', 'units.contact_name',
                'units.contact_email', 'units.unit_name', 'units.contact_phone_number', 'sales.id as sale_id')
            ->where(['sales.status' => 'disable', 'sales.id' => $id])->first();
//        dd($job);
        $sale_export_id= $id;
        $active_applicants = [];
        if ($sent_cv_count == $job['send_cv_limit']) {

            $active_applicants = Applicant::join('history', function ($join) use ($id) {
                $join->on('history.applicant_id', '=', 'applicants.id');
                $join->where('history.sale_id', '=', $id);
            })->whereIn('history.sub_stage', ['quality_cvs','quality_cleared','crm_save','crm_request','crm_request_save','crm_request_confirm','crm_interview_save','crm_interview_attended','crm_prestart_save', 'crm_start_date','crm_start_date_save','crm_start_date_back','crm_invoice','crm_final_save'])
                ->where('history.status', '=', 'active')
                ->select('applicants.applicant_name','applicants.applicant_postcode',
                    'history.stage','history.sub_stage','history.history_added_date','history.history_added_time')
                ->get()->toArray();
        }
        // echo $job['send_cv_limit'];exit();
        // print_r($active_applicants);exit();
        return view('administrator.resource.15km_applicants', compact('id', 'job', 'sent_cv_count', 'active_applicants','sale_export_id','radius','cv_limit'));
    }

	
	


}
