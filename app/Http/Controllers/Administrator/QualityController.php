<?php

namespace Horsefly\Http\Controllers\Administrator;

use Carbon\Carbon;
use Horsefly\Audit;
use Horsefly\Cv_note;
use Horsefly\Observers\ActionObserver;
use Horsefly\Office;
use Horsefly\Sale;
use Horsefly\Sales_notes;
use Illuminate\Http\Request;
use Horsefly\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Horsefly\Mail\FeedbackMail;
use Horsefly\Applicant;
use Horsefly\History;
use Horsefly\Crm_note;
use Horsefly\Crm_rejected_cv;
use Horsefly\Quality_notes;
use Illuminate\Support\Facades\DB;
use Horsefly\Specialist_job_titles;
use Response;
use Horsefly\Applicant_message;
use Redirect;
//use Auth;
use Session;
use Horsefly\RevertStage;

class QualityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        /*** Quality CVs */
        $this->middleware('permission:quality_CVs_list|quality_CVs_cv-download|quality_CVs_job-detail|quality_CVs_cv-clear|quality_CVs_cv-reject|quality_CVs_manager-detail', ['only' => ['getAllApplicantWithSentCv']]);
        $this->middleware('permission:quality_CVs_cv-clear', ['only' => ['updateConfirmInterview']]);
        $this->middleware('permission:quality_CVs_cv-reject|quality_CVs_cv-hold', ['only' => ['updateCVReject']]);
        /*** Quality CVs Rejected */
        $this->middleware('permission:quality_CVs-Rejected_list|quality_CVs-Rejected_job-detail|quality_CVs-Rejected_cv-download|quality_CVs-Rejected_manager-detail|quality_CVs-Rejected_revert-quality-cv', ['only' => ['getAllApplicantWithRejectedCv']]);
        $this->middleware('permission:quality_CVs-Rejected_revert-quality-cv', ['only' => ['revertQualityCv']]);
        /*** Quality CVs Cleared */
        $this->middleware('permission:quality_CVs-Cleared_list|quality_CVs-Cleared_job-detail|quality_CVs-Cleared_cv-download|quality_CVs-Cleared_manager-detail', ['only' => ['getAllApplicantsWithConfirmedInterview']]);
        /*** Common Permissions */
        $this->middleware('permission:quality_CVs_cv-download|quality_CVs-Rejected_cv-download|quality_CVs-Cleared_cv-download', ['only' => ['getDownloadApplicantCv']]);
        /*** Quality Sales */
        $this->middleware('permission:quality_Sales_list|quality_Sales_sale-clear|quality_Sales_sale-reject', ['only' => ['qualitySales','getQualitySales']]);
        $this->middleware('permission:quality_Sales_sale-clear|quality_Sales_sale-reject', ['only' => ['clearRejectSale']]);

    }
	
	    public function sendSmsCurl(Request $request)
    {
        $maxTries = 5;  // Set the maximum number of tries
       $report = null;
        for ($tries = 1; $tries <= $maxTries; $tries++) {
            try {
                $query_string = $request->input('query_string');
                $url = str_replace(" ", "%20", $query_string);

                $link = curl_init();

                // Set cURL options
                curl_setopt($link, CURLOPT_HEADER, 0);
                curl_setopt($link, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($link, CURLOPT_URL, $url);
                curl_setopt($link, CURLOPT_TIMEOUT, 10);

                // Execute cURL session
                $response = curl_exec($link);

                // Check for cURL errors
                if (curl_errno($link)) {
                    throw new \Exception('cURL Error: ' . curl_error($link));
                }

                // Check for a successful response (HTTP code 200)
                $httpCode = curl_getinfo($link, CURLINFO_HTTP_CODE);
                if ($httpCode !== 200) {
                    throw new \Exception('HTTP Error: ' . $httpCode);
                }

                // Close cURL session
                curl_close($link);

                // Parse the response
                $report = $this->getSubstring($response, "result");
                $time = $this->getSubstring($response, "time");
                $phone = $this->getSubstring($response, "phonenumber");

//                if (stripos($response, 'error_keyword') !== false) {
//                    // Replace 'error_keyword' with the actual word or character you're checking for
//                    throw new \Exception('Error: Specific error condition detected in the response.');
//                }

                if ($report == "success") {
                    break;
                } elseif ($report == "sending") {
                    break;
                }
            } catch (\Exception $e) {
                // Log the exception (if needed) and continue to the next try
                error_log('Attempt ' . $tries . ' failed: ' . $e->getMessage());
            }
        }

        // Check the status after the loop
        if ($report == "success") {
            return response()->json(['success' => 'SMS Sent successfully!', 'data' => $response, 'phonenumber' => $phone, 'time' => $time, 'report' => $report]);
        } elseif ($report == "sending") {
            return response()->json(['success' => 'SMS is sending, please check later!', 'data' => $response, 'phonenumber' => $phone, 'time' => $time, 'report' => $report]);
        } else {
            // If all tries fail, return an error response
            return response()->json(['error' => 'SMS failed, please check your device or settings!', 'data' => $response, 'report' => $report]);
        }
    }

	private function getSubstring($text, $keyword)
	{
		$parts = explode("\"", strstr($text, $keyword));

		return isset($parts[2]) ? $parts[2] : null;
	}

    public function getAllApplicantWithSentCv()
    {
		$user=Auth::user();
$user_info = Applicant_message::join('applicants', 'applicant_messages.applicant_id', '=', 'applicants.id')
        ->select('applicant_messages.*','applicants.applicant_name','applicants.applicant_postcode',DB::raw('count(applicant_messages.applicant_id) as total'))
        ->where('applicant_messages.is_read','0')
        ->where('applicant_messages.status','incoming')
        ->groupBy('applicant_messages.applicant_id')
        ->get();

        return view('administrator.quality.cvs.sent',compact('user_info'));
    }

    public function getQualityCVApplicants()
    {
        $user = Auth::user();
        $applicant_with_cvs='';
        if($user->is_admin !== 1)
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
                $applicant_with_cvs = Applicant::join('cv_notes', 'applicants.id', '=', 'cv_notes.applicant_id')
					->join('users', 'users.id', '=', 'cv_notes.user_id')
					->join('sales', 'cv_notes.sale_id', '=', 'sales.id')
					->join('offices', 'sales.head_office', '=', 'offices.id')
					->join('units', 'sales.head_office_unit', '=', 'units.id')
					->join('history', function ($join) {
						$join->on('cv_notes.applicant_id', '=', 'history.applicant_id');
						$join->on('cv_notes.sale_id', '=', 'history.sale_id');
					})->select('cv_notes.applicant_id as cv_note_app_id', 'cv_notes.created_at as cv_note_created_at', 'cv_notes.details', 'cv_notes.send_added_date', 'cv_notes.send_added_time', 'cv_notes.status as cv_note_status',
						'applicants.*', 'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
						'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
						'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
						'units.contact_email', 'units.website',
						'users.name')
						->where([
							"applicants.status" => "active",
							"cv_notes.status" => "active",
							"history.sub_stage" => "quality_cvs", 
							"history.status" => "active"
						])->whereIn('sales.head_office', $user_permissions);
            }
            else
            {
                $applicant_with_cvs = Applicant::join('cv_notes', 'applicants.id', '=', 'cv_notes.applicant_id')
					->join('users', 'users.id', '=', 'cv_notes.user_id')
					->join('sales', 'cv_notes.sale_id', '=', 'sales.id')
					->join('offices', 'sales.head_office', '=', 'offices.id')
					->join('units', 'sales.head_office_unit', '=', 'units.id')
					->join('history', function ($join) {
					$join->on('cv_notes.applicant_id', '=', 'history.applicant_id');
					$join->on('cv_notes.sale_id', '=', 'history.sale_id');
				})->select('cv_notes.applicant_id as cv_note_app_id', 'cv_notes.created_at as cv_note_created_at', 'cv_notes.details', 'cv_notes.send_added_date', 'cv_notes.send_added_time', 'cv_notes.status as cv_note_status',
					'applicants.*', 'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
					'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
					'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
					'units.contact_email', 'units.website',
					'users.name')
				->where([
					"applicants.status" => "active",
					"cv_notes.status" => "active",
					"history.sub_stage" => "quality_cvs", 
					"history.status" => "active"
				]);
            }
        }
        else
        {
            $applicant_with_cvs = Applicant::join('cv_notes', 'applicants.id', '=', 'cv_notes.applicant_id')
				->join('users', 'users.id', '=', 'cv_notes.user_id')
				->join('sales', 'cv_notes.sale_id', '=', 'sales.id')
				->join('offices', 'sales.head_office', '=', 'offices.id')
				->join('units', 'sales.head_office_unit', '=', 'units.id')
				->join('history', function ($join) {
					$join->on('cv_notes.applicant_id', '=', 'history.applicant_id');
					$join->on('cv_notes.sale_id', '=', 'history.sale_id');
				})->select('cv_notes.applicant_id as cv_note_app_id','cv_notes.created_at as cv_note_created_at',  'cv_notes.details', 'cv_notes.send_added_date', 'cv_notes.send_added_time', 'cv_notes.status as cv_note_status',
					'applicants.*', 'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
					'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
					'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
					'units.contact_email', 'units.website',
					'users.name')
				->where([
					"applicants.status" => "active",
					"cv_notes.status" => "active",
					"history.sub_stage" => "quality_cvs", "history.status" => "active"
				]);
        }

        $auth_user = Auth::user();
        $raw_columns = ['action'];
        $datatable = datatables()->of($applicant_with_cvs)
			
			->editColumn('applicant_job_title', function ($applicant_with_cvs) {
					$job_title_desc='';
					if($applicant_with_cvs->job_title_prof!=null)
					{

						$job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $applicant_with_cvs->job_title_prof)->first();
						$job_title_desc = $applicant_with_cvs->applicant_job_title.' ('.$job_prof_res->specialist_prof.')';
					}
					else
					{
						$job_title_desc = $applicant_with_cvs->applicant_job_title;
					}

					return strtoupper($job_title_desc);
			 })
			->editColumn('applicant_postcode', function ($applicant_with_cvs) {
                return strtoupper($applicant_with_cvs->applicant_postcode);
            })
			->editColumn('updated_at', function ($applicant_with_cvs) {
                return Carbon::parse($applicant_with_cvs->cv_note_created_at)->toFormattedDateString();
            })
            ->editColumn('updated_time', function ($applicant_with_cvs) {
                return Carbon::parse($applicant_with_cvs->cv_note_created_at)->toFormattedTimeString();
            })
            ->addColumn('action', function ($applicant) use ($auth_user) {
                $content =
                    '<div class="list-icons">
                        <div class="dropdown">
                            <a href="#" class="list-icons-item" data-toggle="dropdown">
                                <i class="icon-menu9"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasPermissionTo('quality_CVs_cv-clear')) {
                    $content .=
                        '<a href="#" class="dropdown-item sms_action_option" data-controls-modal="#clear_cv' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                   data-keyboard="false" data-toggle="modal"
								   data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                   data-applicantNameJs="' . $applicant->applicant_name . '"
                                    data-applicantIdJs="' . $applicant->id . '"
								   data-target="#clear_cv' . $applicant->id . '-' . $applicant->sale_id . '">
                                    <i class="icon-file-confirm"></i>
                                    Clear Cv
                                </a>';
                }
                if ($auth_user->hasPermissionTo('quality_CVs_cv-reject')) {
                    $content .=
                        '<a href="#" class="dropdown-item" data-controls-modal="#reject_cv' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                   data-keyboard="false" data-toggle="modal" data-target="#reject_cv' . $applicant->id . '-' . $applicant->sale_id . '">
                                    <i class="icon-file-reject"></i>
                                    Reject Cv</a>';
                }
				 if ($auth_user->hasPermissionTo('quality_CVs_cv-hold')) {
                    $content .=
                        '<a href="#" class="dropdown-item sms_action_option" data-controls-modal="#on_hold_cv' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                   data-keyboard="false" data-toggle="modal"
								   data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                   data-applicantNameJs="' . $applicant->applicant_name . '"
                                    data-applicantIdJs="' . $applicant->id . '"
								   data-target="#on_hold_cv' . $applicant->id . '-' . $applicant->sale_id . '">
                                    <i class="icon-file-confirm"></i>
                                    Open Cv
                                </a>';
                }
				
				$content .= '<a href="#" class="dropdown-item notes_history" data-applicant="' . $applicant->id . '" data-sale="' . $applicant->sale_id . '" data-controls-modal="#notes_history' . $applicant->id . '"
                                           data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#notes_history' . $applicant->id . '"
                                        > <i class="icon-file-reject"></i>Notes History </a>';
				
                $content .=
                    '<a href="#" class="dropdown-item" data-controls-modal="#manager_details' . $applicant->id . '-' . $applicant->sale_id . '"
                                   data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                   data-target="#manager_details' . $applicant->id . '-' . $applicant->sale_id . '">
                                    <i class="icon-file-reject"></i>
                                    Manager Details </a>';
                $content .=
                    '</div>
                        </div>
                    </div>';


                /*** Manager Details Modal */
                $content .=
                    '<div id="manager_details' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">
                        <div class="modal-dialog ">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Manager Details</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <ul class="list-group ">
                                        <li class="list-group-item active"><p class="col-12"><b>Name:</b>' . $applicant->contact_name . '</p></li>
                                        <li class="list-group-item"><p><b>Email:</b>' . $applicant->contact_email . '</p></li>
                                        <li class="list-group-item"><p><b>Phone:</b>' . $applicant->contact_phone_number . '</p></li>
                                    </ul>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>';
                /*** /Manager Details Modal */

                if ($auth_user->hasPermissionTo('quality_CVs_cv-clear')) {
                    /*** Clear CV Modal */
                    $content .=
                        '<div id="clear_cv' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Clear CV Notes</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>

                                <form action="' . route('updateToInterviewConfirmed', ['id' => $applicant->id, 'viewString' => 'applicantWithSentCv']) . '"
                                      method="GET" class="form-horizontal msg_form_id"><input type="hidden" name="_token" value="' . csrf_token() . '">
                                    <div class="modal-body">
                                        <div class="form-group row">
                                            <label class="col-form-label col-sm-3">Details</label>
                                            <div class="col-sm-9">
                                                <input type="hidden" name="applicant_hidden_id" id="applicant_hidden_id" value="' . $applicant->id . '">
												<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">
                                                <input type="hidden" name="applicant_name_chat" id="applicant_name_chat">
                                                <input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">
                                                <input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">
                                                <textarea name="details" class="form-control" cols="30" rows="4"
                                                          placeholder="TYPE HERE.." required></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
									    <button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>
                                        <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn bg-teal legitRipple">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>';
                    /*** /Clear CV Modal */
                }
                if ($auth_user->hasPermissionTo('quality_CVs_cv-reject')) {
                    /*** Reject CV Modal */
                    $content .=
                        '<div id="reject_cv' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Reject CV Notes</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>

                                <form action="' . route('updateToRejectedCV', ['id' => $applicant->id, 'viewString' => 'applicantWithSentCv']) . '"
                                      method="GET" class="form-horizontal"><input type="hidden" name="_token" value="' . csrf_token() . '">
                                    <div class="modal-body">
                                        <div class="form-group row">
                                            <label class="col-form-label col-sm-3">Details</label>
                                            <div class="col-sm-9">
                                                <input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">
                                                <input type="hidden" name="applicant_hidden_id" value="{{ $applicant->id }}">
                                                <textarea name="details" class="form-control" cols="30" rows="4"
                                                          placeholder="TYPE HERE.." required></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn bg-teal legitRipple">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>';
                    /*** /reject CV Modal */
                }
				
				   if ($auth_user->hasPermissionTo('quality_CVs_cv-hold')) {
                    /*** Hold CV Modal */
                    $content .=
                        '<div id="on_hold_cv' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Open CV Notes</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>

                                <form action="' . route('updateToCVHoldSentCV', ['id' => $applicant->id, 'viewString' => 'applicantWithSentCv']) . '"
                                      method="GET" class="form-horizontal msg_form_id"><input type="hidden" name="_token" value="' . csrf_token() . '">
                                    <div class="modal-body">
                                        <div class="form-group row">
                                            <label class="col-form-label col-sm-3">Details</label>
                                            <div class="col-sm-9">
                                                <input type="hidden" name="applicant_hidden_id" id="applicant_hidden_id" value="' . $applicant->id . '">
												<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">
                                                <input type="hidden" name="applicant_name_chat" id="applicant_name_chat">
                                                <input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">
                                                <input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">
                                                <textarea name="details" class="form-control" cols="30" rows="4"
                                                          placeholder="TYPE HERE.." required></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
								   
                                        <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn bg-teal legitRipple">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>';
                    /*** /Hold CV Modal */
                }
				  $content .= '<div id="notes_history' . $applicant->id . '" class="modal fade" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Applicant Notes History - 
                                        <span class="font-weight-semibold">' . $applicant->applicant_name . '</span></h5>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body" id="applicants_notes_history' . $applicant->id . '" style="max-height: 500px; overflow-y: auto;">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                        </button>
                                    </div>
                                    
                                </div>
                            </div>
                          </div>';

                return $content;
            });

        if ($auth_user->hasPermissionTo('quality_CVs_cv-download')) {
            $datatable = $datatable->addColumn('download', function ($applicant) {
               $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';
                return $download;
            });
            array_push($raw_columns, 'download');
        }
        if ($auth_user->hasPermissionTo('quality_CVs_job-detail')) {
            $datatable = $datatable->addColumn('job_details', function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details' . $applicant->id . '-' . $applicant->sale_id . '"
                                         data-backdrop="static"
                                         data-keyboard="false" data-toggle="modal"
                                         data-target="#job_details' . $applicant->id . '-' . $applicant->sale_id . '">Details</a>';
                // Job Details Modal
                $content .= '<div id="job_details' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content pl-3 pr-4">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">' . $applicant->applicant_name . '\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<div class=" header-elements-sm-inline">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name . '/' . $applicant->unit_name;
                $content .= '</h5>';
                $content .= '<div><span class="font-weight-semibold">Posted Date: </span><span class="mb-3">' . $applicant->posted_date . '</span></div>';
                $content .= '</div>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">' . strtoupper($applicant->job_category) . ', ' . strtoupper($applicant->job_title) . '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-4"><h6 class="font-weight-semibold">Job Title:</h6><p>' . strtoupper($applicant->job_title) . '</p></div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Postcode:</h6>
                    <p class="mb-3">' . strtoupper($applicant->postcode) . '</p>
                    </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Job Type:</h6>
                    <p class="mb-3">' . ucwords($applicant->job_type) . '</p>
                    </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Timings:</h6>
                    <p class="mb-3">' . $applicant->timing . '</p>
                    </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Salary:</h6>
                    <p class="mb-3">' . $applicant->salary . '</p>
                    </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Experience:</h6>
                    <p class="mb-3">' . $applicant->experience . '</p>
                    </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Qualification:</h6>
                    <p class="mb-3">' . $applicant->qualification . '</p>
                    </div>';
                $content .= '<div class="col-8"> <h6 class="font-weight-semibold">Benefits:</h6>
                    <p class="mb-3">' . $applicant->benefits . '</p>
                    </div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer"> <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>
                    </div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                //<!-- /Job Details Modal -->
                return $content;
            });
            array_push($raw_columns, "job_details");
        }
		$datatable = $datatable->addColumn('updated_cv', function ($applicants) {
            $filePath = $applicants->updated_cv;
                 // Check if the file exists
                 $disabled = (!file_exists($filePath) || $applicants->updated_cv == null ) ? 'disabled' : '';
                 $disabled_color = (!file_exists($filePath) || $applicants->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                 $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicants->id);
                 return
                     '<a class="download-link ' . $disabled . '" href="' . $href . '">
                        <i class="fas fa-file-download '. $disabled_color .'"></i>
                     </a>';
        });
        array_push($raw_columns, 'updated_cv');
		array_push($raw_columns, "applicant_job_title");

       $datatable = $datatable->addColumn('upload', function ($applicants) {
            return
            '<a href="#"
            data-controls-modal="#import_applicant_cv" class="import_cv"
            data-backdrop="static"
            data-keyboard="false" data-toggle="modal" data-id="'.$applicants->id.'"
            data-target="#import_applicant_cv">
             <i class="fas fa-file-upload text-teal-400"></i>
             &nbsp;</a>';
        });
        array_push($raw_columns, 'upload'); 
        $datatable = $datatable->rawColumns($raw_columns)
            ->make(true);
        return $datatable;
    }

    public function getAllApplicantWithAddCv()
    {
        $applicant_with_cvs = Applicant::where("is_cv_in_quality", "yes")->where("is_CV_sent", "no")->where("is_CV_reject", "no")->where("is_interview_confirm", "no")->where("is_interview_attend", "no")->get();
        return view('administrator.quality.cvs.add', compact('applicant_with_cvs'));
    }

    public function updateAddCV($applicant_id, $viewString)
    {
        Applicant::where("id", $applicant_id)->update(['is_CV_sent' => 'no', 'is_CV_reject' => 'no', 'is_interview_confirm' => 'no', 'is_interview_attend' => 'no']);

        return redirect()->route($viewString);
    }

    public function updateSentCV($applicant_id, $viewString)
    {
        Applicant::where("id", $applicant_id)->update(['is_cv_in_quality' => 'yes', 'is_CV_reject' => 'no', 'is_interview_confirm' => 'no']);

        return redirect()->route($viewString);
    }

    public function updateCVReject($applicant_id, $viewString)
    {
        date_default_timezone_set('Europe/London');
		
        $audit_data['action'] = "Reject";
		
        Applicant::where("id", $applicant_id)
			->update(['is_CV_reject' => 'yes', 'is_cv_in_quality' => 'no']);
		
        $audit_data['sale'] = $sale_id = request()->job_hidden_id;
		
        $user = Auth::user();
        $current_user_id = $user->id;
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
		
		$details = request()->details. " --- Rejected By: " . $user->name;
		
        $quality_notes = new Quality_notes();
        $audit_data['applicant'] = $quality_notes->applicant_id = $applicant_id;
        $quality_notes->user_id = $current_user_id;
        $quality_notes->sale_id = $sale_id;
        $audit_data['details'] = $quality_notes->details = $details;
        $audit_data['added_date'] = $quality_notes->quality_added_date = date("jS F Y");
        $audit_data['added_time'] = $quality_notes->quality_added_time = date("h:i A");
        $quality_notes->moved_tab_to = "rejected";
        $quality_notes->save();

        /*** activity log
         * $action_observer = new ActionObserver();
         * $action_observer->action($audit_data, 'Quality');
         */

        $last_inserted_note = $quality_notes->id;
        if ($last_inserted_note > 0) {
            $quality_note_uid = md5($last_inserted_note);
            Quality_notes::where('id', $last_inserted_note)->update(['quality_notes_uid' => $quality_note_uid]);
            Cv_note::where(['sale_id' => $sale_id, 'applicant_id' => $applicant_id])->update(['status' => 'disable']);
            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(["status" => "disable"]);
			
            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $current_user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'quality';
            $history->sub_stage = 'quality_reject';
            $history->history_added_date = date("jS F Y");
            $history->history_added_time = date("h:i A");
            $history->save();
			
            $last_inserted_history = $history->id;
            if ($last_inserted_history > 0) {
                $history_uid = md5($last_inserted_history);
                History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
				$revertRecord=RevertStage::create([
                    'applicant_id'=>$applicant_id,
                    'sale_id'=>$sale_id,
                    'revert_added_date' =>date("jS F Y"),
                    'revert_added_time' =>date("h:i A"),
                    'stage'=>'quality_note',
                    'user_id'=>$current_user_id,
                    'notes'=>request()->details,
                ]);
                return redirect()->route($viewString);
            }
        } else {
            return redirect()->route($viewString);
        }
    }
	
	public function updateCVRejectRevertSentCV($applicant_id)
    {
        date_default_timezone_set('Europe/London');
		
		$user = Auth::user();
		$current_user_id = $user->id;
		
        $audit_data['action'] = "Reject";
        $audit_data['applicant'] = $applicant = request()->applicant_hidden_id;
        $applicant_id = $applicant;
        $audit_data['sale'] = $sale_id = request()->job_hidden_id;
		$is_no_job = request()->applicant_hidden_no_job;
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
		
		$details = request()->details. " --- Rejected By: " . $user->name;
    
		if (!empty($is_no_job)&&$is_no_job=="is_no_job"){
           Applicant::where("id", $applicant_id)->update([
			   'is_interview_confirm' => 'no', 
			   'is_cv_in_quality_clear' => 'no', 
			   'is_CV_reject' => 'yes', 
			   'is_cv_in_quality' => 'no', 
			   'updated_at' => Carbon::now()
		   ]);

           Quality_notes::where([
			   'applicant_id' => $applicant_id, 
			   'sale_id' => $sale_id, 
			   'moved_tab_to' => 'cleared'
		   ])->orderBy("updated_at", "DESC")->take(1)->delete();
			
           $quality_notes = new Quality_notes();
           $audit_data['applicant'] = $quality_notes->applicant_id = $applicant_id;
           $quality_notes->user_id = $current_user_id;
           $quality_notes->sale_id = $sale_id;
           $audit_data['details'] = $quality_notes->details = $details;
           $audit_data['added_date'] = $quality_notes->quality_added_date = date("jS F Y");
           $audit_data['added_time'] = $quality_notes->quality_added_time = date("h:i A");
           $quality_notes->moved_tab_to = "rejected";
           $quality_notes->save();

           $last_inserted_note = $quality_notes->id;
           if ($last_inserted_note > 0) {
               $quality_note_uid = md5($last_inserted_note);
			   
               Quality_notes::where('id', $last_inserted_note)
				   ->update(['quality_notes_uid' => $quality_note_uid]);
			   
               Cv_note::where(['sale_id' => $sale_id, 'applicant_id' => $applicant_id])
				   ->update(['status' => 'disable']);
			   
               History::where([
                   "applicant_id" => $applicant_id,
                   "sale_id" => $sale_id
               ])->update(["status" => "disable"]);
			   
               $history = new History();
               $history->applicant_id = $applicant_id;
               $history->user_id = $current_user_id;
               $history->sale_id = $sale_id;
               $history->stage = 'quality';
               $history->sub_stage = 'quality_no_job_reject';
               $history->history_added_date = date("jS F Y");
               $history->history_added_time = date("h:i A");
               $history->save();
			   
               $last_inserted_history = $history->id;
               if ($last_inserted_history > 0) {
                   $history_uid = md5($last_inserted_history);
				   
                   History::where('id', $last_inserted_history)
					   ->update(['history_uid' => $history_uid]);
				   
                   $details_revert = $details;

                   $revertRecord = RevertStage::create([
                       'applicant_id' => $applicant_id,
                       'sale_id' => $sale_id,
                       'revert_added_date' => date("jS F Y"),
                       'revert_added_time' => date("h:i A"),
                       'stage' => 'crm_revert',
                       'user_id' => $current_user_id,
                       'notes' => $details_revert,
                   ]);

                   return Redirect::back()->with('success', 'Applicant is revert back in quality rejected tab.');
               }

           } else {

               return Redirect::back()->with('error', 'Applicant can not be reverted');
           }
       }
		else{
        	Applicant::where("id", $applicant_id)->update([
				'is_interview_confirm' => 'no', 
				'is_cv_in_quality_clear' => 'no',
				'is_CV_reject' => 'yes', 
				'is_cv_in_quality' => 'no', 
				'updated_at'=>Carbon::now()
			]);
			
			if(isset(request()->cv_modal_name) && request()->cv_modal_name == 'sent_cv_no_job'){
				Quality_notes::where(['applicant_id' => $applicant_id, 'sale_id' => $sale_id, 'moved_tab_to' => 'cleared_no_job'])
					->orderBy("updated_at", "DESC")
					->take(1)
					->delete();
			}else{
				Quality_notes::where(['applicant_id' => $applicant_id, 'sale_id' => $sale_id, 'moved_tab_to' => 'cleared'])
					->orderBy("updated_at", "DESC")
					->take(1)
					->delete();
			}
			
			$quality_notes = new Quality_notes();
			$audit_data['applicant'] = $quality_notes->applicant_id = $applicant_id;
			$quality_notes->user_id = $current_user_id;
			$quality_notes->sale_id = $sale_id;
			$audit_data['details'] = $quality_notes->details = $details;
			$audit_data['added_date'] = $quality_notes->quality_added_date = date("jS F Y");
			$audit_data['added_time'] = $quality_notes->quality_added_time = date("h:i A");
			$quality_notes->moved_tab_to = "rejected";
			$quality_notes->save();

        	$last_inserted_note = $quality_notes->id;
			
			if ($last_inserted_note > 0) {
				$quality_note_uid = md5($last_inserted_note);
				Quality_notes::where('id', $last_inserted_note)
					->update(['quality_notes_uid' => $quality_note_uid]);
				
				Cv_note::where(['sale_id' => $sale_id, 'applicant_id' => $applicant_id])
					->update(['status' => 'disable']);
				
				History::where([
					"applicant_id" => $applicant_id,
					"sale_id" => $sale_id
				])->update(["status" => "disable"]);
				
				$history = new History();
				$history->applicant_id = $applicant_id;
				$history->user_id = $current_user_id;
				$history->sale_id = $sale_id;
				$history->stage = 'quality';
				$history->sub_stage = 'quality_reject';
				$history->history_added_date = date("jS F Y");
				$history->history_added_time = date("h:i A");
				$history->save();
				
				$last_inserted_history = $history->id;
				if ($last_inserted_history > 0) {
					$history_uid = md5($last_inserted_history);
					History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
					   $revertRecord=RevertStage::create([
					   		'applicant_id'=>$applicant_id,
					   		'sale_id'=>$sale_id,
					   		'revert_added_date' =>date("jS F Y"),
					   		'revert_added_time' =>date("h:i A"),
							'stage'=>'crm_revert',
							'user_id'=>$current_user_id,
							'notes'=>$details,
					]);

					return Redirect::back()->with('success','Applicant is revert back in quality rejected tab.');
				}
			} else {
				return Redirect::back()->with('error','Applicant can not be reverted');
			}
		}
    }

    public function revertQualityCv(Request $request)
    {
        date_default_timezone_set('Europe/London');
		
		$auth_user = Auth::user();
        $audit_data['action'] = "Revert Quality > Rejected CV";
        $applicant_id = request('applicant_hidden_id');
        $audit_data['sale'] = $sale_id = request('job_hidden_id');
		$is_no_job = $request->applicant_hidden_no_job;
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
		
		$details = request('details'). " --- Revert By: " . $auth_user->name;
		
		 if (!empty($is_no_job) && $is_no_job == "no_job"){
			 $cv_count = Cv_note::where(['cv_notes.sale_id' => $sale_id, 'cv_notes.status' => 'active'])->count();
			 $sale_cv_count = Sale::select('send_cv_limit')->where('id',$sale_id)->first();

			 Quality_notes::where([
				 'applicant_id' => $applicant_id, 
				 'sale_id' => $sale_id, 
				 'moved_tab_to' => 'rejected'
			 ])->delete();

			 $update_cv_note = Cv_note::where(['sale_id' => $sale_id, 'applicant_id' => $applicant_id])
				 ->orderBy('id', 'desc')
				 ->limit(1)->update([
					 'user_id' => $auth_user->id,
					 'details' => $details,
					 'send_added_date' => date("jS F Y"),
					 'send_added_time' => date("h:i A"),
					 'status' => 'disable',
					 'created_at' => Carbon::now(),
					 'updated_at' => Carbon::now()
				 ]);

            $update_cv_note = true;
			 
            if ($update_cv_note) {
                History::where([
                    "applicant_id" => $applicant_id,
                    "sale_id" => $sale_id
                ])->update(["status" => "disable"]);
				
                $history = new History();
                $history->applicant_id = $applicant_id;
                $history->user_id = $auth_user->id;
                $history->sale_id = $sale_id;
                $history->stage = 'quality';
                $history->sub_stage = 'no_job_quality_cvs';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
				
                $last_inserted_history = $history->id;
                if ($last_inserted_history > 0) {
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    //revert qualtiy
                    $revertRecord=RevertStage::create([
                        'applicant_id'=>$applicant_id,
                        'sale_id'=>$sale_id,
                        'revert_added_date' =>date("jS F Y"),
                        'revert_added_time' =>date("h:i A"),
                        'stage'=>'no_job_quality_cvs',
                        'user_id'=>$auth_user->id,
                        'notes'=>$details,
                    ]);

                    return redirect()->back()->with('qualityApplicantMsg', 'Applicant has been sent to quality');
                }

            } else {
                return redirect()->back()->with('qualityApplicantErr', 'Applicant Cant be Sent');
            }
        }
		else{

			$cv_count = Cv_note::where(['cv_notes.sale_id' => $sale_id, 'cv_notes.status' => 'active'])->count();
			$sale_cv_count = Sale::select('send_cv_limit')->where('id',$sale_id)->first();
			
			if($cv_count >=  $sale_cv_count->send_cv_limit)
			{
				return redirect()->back()->with('error', 'Sale cv limit exceeds.');
			}
			
        	Quality_notes::where([
				'applicant_id' => $applicant_id, 
				'sale_id' => $sale_id, 
				'moved_tab_to' => 'rejected'
			])->delete();
	
			$update_cv_note = Cv_note::where(['sale_id' => $sale_id, 'applicant_id' => $applicant_id])->orderBy('id', 'desc')
				->limit(1)->update([
					'user_id' => $auth_user->id,
					'details' => $details,
					'send_added_date' => date("jS F Y"),
					'send_added_time' => date("h:i A"),
					'status' => 'active',
					'created_at' => Carbon::now(),
					'updated_at' => Carbon::now()
				]);

			if ($update_cv_note) {
				History::where([
					"applicant_id" => $applicant_id,
					"sale_id" => $sale_id
				])->update(["status" => "disable"]);

				$history = new History();
				$history->applicant_id = $applicant_id;
				$history->user_id = $auth_user->id;
				$history->sale_id = $sale_id;
				$history->stage = 'quality';
				$history->sub_stage = 'quality_cvs';
				$history->history_added_date = date("jS F Y");
				$history->history_added_time = date("h:i A");
				$history->save();

				$last_inserted_history = $history->id;
				if ($last_inserted_history > 0) {
					$history_uid = md5($last_inserted_history);
					History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
					//revert qualtiy
					$revertRecord=RevertStage::create([
							'applicant_id'=>$applicant_id,
							'sale_id'=>$sale_id,
							'revert_added_date' =>date("jS F Y"),
							'revert_added_time' =>date("h:i A"),
							'stage'=>'quality_revert',
							'user_id'=>$auth_user->id,
							'notes'=>$details,
						]);

					return redirect()->back()->with('qualityApplicantMsg', 'Applicant has been sent to quality');
				}

			} else {
				return redirect()->back()->with('qualityApplicantErr', 'Applicant Cant be Sent');
			}
		}
    }

    public function getAllApplicantWithRejectedCv()
    {
        return view('administrator.quality.cvs.rejected');
    }

    public function getRejectCVApplicants()
    {
        $user = Auth::user();
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
                date_default_timezone_set('Europe/London');
                $applicant_with_cvs = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
                    ->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
                    ->join('offices', 'sales.head_office', '=', 'offices.id')
                    ->join('units', 'sales.head_office_unit', '=', 'units.id')
                    ->join('cv_notes', function ($join) {
                        $join->on('cv_notes.applicant_id', '=', 'quality_notes.applicant_id');
                        $join->on('cv_notes.sale_id', '=', 'quality_notes.sale_id');
                    })->join('users', 'users.id', '=', 'cv_notes.user_id')
					->join('users as quality_user', 'quality_user.id', '=', 'quality_notes.user_id')
                    ->select('quality_notes.details', 'quality_notes.quality_added_date', 'quality_notes.quality_added_time',
                        'applicants.id', 'applicants.applicant_name', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.applicant_postcode', 'applicants.applicant_phone', 'applicants.applicant_homePhone','applicants.applicant_cv',
                        'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
                        'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                        'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline','units.contact_email', 'units.website','users.name','quality_user.name as quality_name','applicants.is_no_job')
                    ->where([
                        "applicants.status" => "active",
                        "quality_notes.moved_tab_to" => "rejected"
                    ])->whereIn('sales.head_office', $user_permissions)
					->whereIn('quality_notes.id', function($query){
						$query->select(DB::raw('MAX(id) FROM quality_notes WHERE moved_tab_to="rejected" and sale_id=sales.id and applicant_id=applicants.id'));
					});
            }
            else
            {
                date_default_timezone_set('Europe/London');
				$applicant_with_cvs = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
					->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
					->join('offices', 'sales.head_office', '=', 'offices.id')
					->join('units', 'sales.head_office_unit', '=', 'units.id')
					->join('cv_notes', function ($join) {
						$join->on('cv_notes.applicant_id', '=', 'quality_notes.applicant_id');
						$join->on('cv_notes.sale_id', '=', 'quality_notes.sale_id');
					})->join('users', 'users.id', '=', 'cv_notes.user_id')
					->join('users as quality_user', 'quality_user.id', '=', 'quality_notes.user_id')
					->select('quality_notes.details', 'quality_notes.quality_added_date', 'quality_notes.quality_added_time',
						'applicants.id', 'applicants.applicant_name', 'applicants.applicant_job_title','applicants.applicant_cv', 'applicants.job_title_prof', 'applicants.applicant_postcode', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
						'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
						'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
						'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
						'units.contact_email', 'units.website',
						'users.name','quality_user.name as quality_name','applicants.is_no_job')
					->where([
						"applicants.status" => "active",
						"quality_notes.moved_tab_to" => "rejected"
					])
					->whereIn('quality_notes.id', function($query){
						$query->select(DB::raw('MAX(id) FROM quality_notes WHERE moved_tab_to="rejected" and sale_id=sales.id and applicant_id=applicants.id'));
					});

            }
    
        }
        else
        {
            date_default_timezone_set('Europe/London');
				$applicant_with_cvs = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
					->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
					->join('offices', 'sales.head_office', '=', 'offices.id')
					->join('units', 'sales.head_office_unit', '=', 'units.id')
					->join('cv_notes', function ($join) {
						$join->on('cv_notes.applicant_id', '=', 'quality_notes.applicant_id');
						$join->on('cv_notes.sale_id', '=', 'quality_notes.sale_id');
					})->join('users', 'users.id', '=', 'cv_notes.user_id')
					->join('users as quality_user', 'quality_user.id', '=', 'quality_notes.user_id')
					->select('quality_notes.details', 'quality_notes.quality_added_date', 'quality_notes.quality_added_time',
						'applicants.id', 'applicants.applicant_name', 'applicants.applicant_job_title','applicants.applicant_cv', 'applicants.job_title_prof', 'applicants.applicant_postcode', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
						'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
						'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
						'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
						'units.contact_email', 'units.website',
						'users.name','quality_user.name as quality_name','applicants.is_no_job')
					->where([
						"applicants.status" => "active",
						"quality_notes.moved_tab_to" => "rejected"
					])
					->whereIn('quality_notes.id', function($query){
						$query->select(DB::raw('MAX(id) FROM quality_notes WHERE moved_tab_to="rejected" and sale_id=sales.id and applicant_id=applicants.id'));
					});
        }


        date_default_timezone_set('Europe/London');
        $applicant_with_cvs = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
            ->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('cv_notes', function ($join) {
                $join->on('cv_notes.applicant_id', '=', 'quality_notes.applicant_id');
                $join->on('cv_notes.sale_id', '=', 'quality_notes.sale_id');
            })->join('users', 'users.id', '=', 'cv_notes.user_id')
			->join('users as quality_user', 'quality_user.id', '=', 'quality_notes.user_id')
            ->select('quality_notes.details', 'quality_notes.quality_added_date', 'quality_notes.quality_added_time','quality_notes.created_at',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.applicant_postcode', 'applicants.applicant_phone', 'applicants.applicant_homePhone','applicants.applicant_cv',
                'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
                'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
                'units.contact_email', 'units.website',
                'users.name','quality_user.name as quality_name','applicants.is_no_job')
            ->where([
                "applicants.status" => "active",
                "quality_notes.moved_tab_to" => "rejected"
            ])
			->whereIn('quality_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM quality_notes WHERE moved_tab_to="rejected" and sale_id=sales.id and applicant_id=applicants.id'));
            });

        /*** not used
         * $status = array();
         * $x = 0;
         * foreach ($applicant_with_cvs as $applicant) {
         * $status[$x] = "rejected";
         * if ($applicant->is_interview_attend == "yes") {
         * $status[$x] = "attended";
         * } else if ($applicant->is_interview_confirm == "yes") {
         * $status[$x] = "confirmed";
         * }
         * $x++;
         * }
         *** add 'status' in compact()
         */

        $auth_user = Auth::user();
        $raw_columns = ['action'];
        $datatable = datatables()->of($applicant_with_cvs)
			->editColumn('applicant_job_title', function ($applicant) {
					$job_title_desc='';
					if($applicant->job_title_prof!=null)
					{

						$job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $applicant->job_title_prof)->first();
						$job_title_desc = $applicant->applicant_job_title.' ('.$job_prof_res->specialist_prof.')';
					}
						else
						{
							$job_title_desc = $applicant->applicant_job_title;
						}

					return strtoupper($job_title_desc);
			 })
            ->addColumn('action', function ($applicant) use ($auth_user) {
                $content =
                    '<div class="list-icons">
                        <div class="dropdown">
                            <a href="#" class="list-icons-item" data-toggle="dropdown">
                                <i class="icon-menu9"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a href="#" class="dropdown-item" data-controls-modal="#manager_details' . $applicant->id . '-' . $applicant->sale_id . '"
                                   data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                   data-target="#manager_details' . $applicant->id . '-' . $applicant->sale_id . '">
                                    <i class="icon-file-reject"></i>
                                    Manager Details </a>';
                if ($auth_user->hasPermissionTo('quality_CVs-Rejected_revert-quality-cv')) {
                       $jobHide=Sale::where('id',$applicant->sale_id)->first();
                   
                        if ($jobHide->status == "disable") {
                               $content .=
                                '<a href="javascript:void(0);" class="dropdown-item text-danger disabled" >
                                    <i class="icon-file-confirm"></i> Sale Disabled  </a>';
                        } else {
                            $content .=
                                '<a href="#" class="dropdown-item"
                                   data-controls-modal="#revert_to_quality_cvs' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                   data-keyboard="false" data-toggle="modal"
                                   data-target="#revert_to_quality_cvs' . $applicant->id . '-' . $applicant->sale_id . '">
                                    <i class="icon-file-confirm"></i> Revert  </a>';
                        }
                    
                }
                $content .=
                    '</div>
                        </div>
                    </div>
                    <!-- Manager Details Modal -->
                    <div id="manager_details' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">
                        <div class="modal-dialog modal-sm">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Manager Details</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <ul class="list-group ">
                                        <li class="list-group-item active"><p><b>Name:</b>' . $applicant->contact_name . '</p></li>
                                        <li class="list-group-item"><p><b>Email:</b>' . $applicant->contact_email . '</p></li>
                                        <li class="list-group-item"><p><b>Phone:</b>' . $applicant->contact_phone_number . '</p></li>
                                    </ul>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Manager Details Modal -->';
                if ($auth_user->hasPermissionTo('quality_CVs-Rejected_revert-quality-cv')) {
                    $content .=
                        '<!-- Revert To Quality > CVs Modal -->
                    <div id="revert_to_quality_cvs' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">
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
                                           
                                                           $content .=' <input type="hidden" name="applicant_hidden_id"
                                                       value="' . $applicant->id . '">
                                                <input type="hidden" name="job_hidden_id"
                                                       value="' . $applicant->sale_id . '">
                                                <textarea name="details" class="form-control" cols="30" rows="4"
                                                          placeholder="TYPE HERE.." required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" name="rejected_cv_revert_to_quality_cvs" value="rejected_cv_revert_to_quality_cvs"
                                                class="btn bg-dark legitRipple">Quality CV
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- ./Revert To Quality > CVs Modal -->';
                }
                return $content;
            });
        if ($auth_user->hasPermissionTo('quality_CVs-Rejected_job-detail')) {
            $datatable = $datatable->addColumn('job_details', function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details' . $applicant->id . '-' . $applicant->sale_id . '"
                                         data-backdrop="static"
                                         data-keyboard="false" data-toggle="modal"
                                         data-target="#job_details' . $applicant->id . '-' . $applicant->sale_id . '">Details</a>';
                //<!-- Job Details Modal -->
                $content .= '<div id="job_details' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content pl-3 pr-4">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">' . $applicant->applicant_name . '\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<div class=" header-elements-sm-inline">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name . '/' . $applicant->unit_name;
                $content .= '</h5>';
                $content .= '<div><span class="font-weight-semibold">Posted Date: </span><span class="mb-3">' . $applicant->posted_date . '</span></div>';
                $content .= '</div>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">' . strtoupper($applicant->job_category) . ', ' . strtoupper($applicant->job_title) . '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-4"><h6 class="font-weight-semibold">Job Title:</h6><p>' . strtoupper($applicant->job_title) . '</p></div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Postcode:</h6>
                    <p class="mb-3">' . strtoupper($applicant->postcode) . '</p>
                    </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Job Type:</h6>
                    <p class="mb-3">' . ucwords($applicant->job_type) . '</p>
                    </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Timings:</h6>
                    <p class="mb-3">' . $applicant->timing . '</p>
                    </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Salary:</h6>
                    <p class="mb-3">' . $applicant->salary . '</p>
                    </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Experience:</h6>
                    <p class="mb-3">' . $applicant->experience . '</p>
                    </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Qualification:</h6>
                    <p class="mb-3">' . $applicant->qualification . '</p>
                    </div>';
                $content .= '<div class="col-8"> <h6 class="font-weight-semibold">Benefits:</h6>
                    <p class="mb-3">' . $applicant->benefits . '</p>
                    </div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer"> <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>
                    </div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                //<!-- /Job Details Modal -->
                return $content;
            });
            array_push($raw_columns, 'job_details');
        }
        if ($auth_user->hasPermissionTo('quality_CVs-Rejected_cv-download')) {
            $datatable = $datatable->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';
                return $download;
            });
            array_push($raw_columns, 'download');
        }
		$datatable->addColumn('quality_added_date', function ($applicant) {
            return '<span data-popup="tooltip" title="'.$applicant->quality_name.'">'.@Carbon::parse($applicant->created_at)->toFormattedDateString().'</span>';});

        array_push($raw_columns, "quality_added_date");
		array_push($raw_columns, "applicant_job_title");
        $datatable = $datatable->rawColumns($raw_columns)
            ->make(true);
        return $datatable;
    }
	public function sendQualityClearSms($data)
    {
        $query_string = $data;
        $url = str_replace(" ","%20",$query_string);
        $link = curl_init();
        curl_setopt($link, CURLOPT_HEADER, 0);
        curl_setopt($link, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($link, CURLOPT_URL, $url);
        $response = curl_exec($link);
        curl_close($link);
        $report = explode("\"",strchr($response,"result"))[2];
        $time = explode("\"",strchr($response,"time"))[2];
        $phone = explode("\"",strchr($response,"phonenumber"))[2];
        if($response)
        {
            if ($report == "success") {
                return ['result'=> 'success','data'=>$response,'phonenumber'=>$phone,'time'=>$time,'report'=>$report];
    
            } elseif ($report == "sending") {
                            return ['result'=> 'success','data'=>$response,'phonenumber'=>$phone,'time'=>$time,'report'=>$report];
    
            } else {
                return ['result'=> 'error','data'=>$response,'report'=>$report];
            }
        }
        else
        {
            return ['result'=> 'error'];;

        }
    }


    public function saveQualityClearSendMessage($applicant_msg_text, $applicant_phone, $applicant_msg_time)
    {
        $user_id = Auth::user()->id;
                $applicant_data = Applicant::select('id')->where(['applicant_phone' => $applicant_phone, 'status' => 'active'])->first();
                if($applicant_data)
                {
                    $applicant_id = $applicant_data->id;
                    $applicant_msg_id = 'D'.mt_rand(1000000000000, 9999999999999);
                    $date_arr= explode(" ", $applicant_msg_time);
                    $msg_date = $date_arr[0];
                    $msg_time = $date_arr[1];
                    $applicant_msg = new Applicant_message();
                    $applicant_msg->applicant_id = $applicant_id;
                    $applicant_msg->user_id = $user_id;
                    $applicant_msg->msg_id = $applicant_msg_id;
                    $applicant_msg->message = $applicant_msg_text;
                    $applicant_msg->phone_number = $applicant_phone;
                    $applicant_msg->date = $msg_date;
                    $applicant_msg->time = $msg_time;
                    $applicant_msg->status = 'outgoing';
                    $applicant_msg->is_read = '1';
                    $applicant_msg->created_at = $applicant_msg_time;
                    $applicant_msg->updated_at = $applicant_msg_time;
                    $res = $applicant_msg->save();
                    return $res;
                }
                else
                {
                    return false;
                }

    }

    public function updateConfirmInterview($applicant_id, $viewString)
    {
        date_default_timezone_set('Europe/London');
		
        $audit_data['action'] = "Clear";
        $audit_data['sale'] = $sale_id = request()->job_hidden_id;
		$unit_name = Sale::join('units', 'sales.head_office_unit', '=', 'units.id')
        ->where('sales.id','=', $sale_id)
        ->select('units.unit_name')->first();	
		
        $unit_name =  $unit_name->unit_name;
		
        $user = Auth::user();
        $current_user_id = $user->id;
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
		
		$details = request()->details. " --- Cleared By: " . $user->name;
		
		$applicant_phone = Applicant::select('applicant_phone')->find($applicant_id);
        $applicant_phone = $applicant_phone->applicant_phone;
		
        Applicant::where("id", $applicant_id)->update([
			'is_interview_confirm' => 'yes', 
			'is_cv_in_quality_clear' => 'yes', 
			'is_cv_in_quality' => 'no'
		]);
		
        $quality_notes = new Quality_notes();
        $audit_data['applicant'] = $quality_notes->applicant_id = $applicant_id;
        $quality_notes->user_id = $current_user_id;
        $quality_notes->sale_id = $sale_id;
        $audit_data['details'] = $quality_notes->details = $details;
        $audit_data['added_date'] = $quality_notes->quality_added_date = date("jS F Y");
        $audit_data['added_time'] = $quality_notes->quality_added_time = date("h:i A");
        $quality_notes->moved_tab_to = "cleared";
        $quality_notes->save();

        $last_inserted_note = $quality_notes->id;
        if ($last_inserted_note > 0) {
            $quality_note_uid = md5($last_inserted_note);
			
            Quality_notes::where('id', $last_inserted_note)
				->update(['quality_notes_uid' => $quality_note_uid]);
			
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $current_user_id;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->crm_added_date = date("jS F Y");
            $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "cv_sent";
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
                $history->sub_stage = 'quality_cleared';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
				
                $last_inserted_history = $history->id;
                if ($last_inserted_history > 0) {
                    $history_uid = md5($last_inserted_history);
					
                    History::where('id', $last_inserted_history)
						->update(['history_uid' => $history_uid]);
					
                    //$applicant_numbers='07597019065';
                    $applicant_number = $applicant_phone;
                    $applicant_message = 'Hi Thank you for your time over the phone. I am sharing your resume details with the manager of '.$unit_name.' for the discussed vacancy. If you have any questions, feel free to reach out. Thank you for choosing Kingbury to represent you. Best regards, CRM TEAM T: 01494211220 E: crm@kingsburypersonnel.com';

					$applicant_message_encoded = urlencode($applicant_message);
                	$query_string = 'http://milkyway.tranzcript.com:1008/sendsms?username=admin&password=admin&phonenumber='.$applicant_number.'&message='.$applicant_message_encoded.'&port=2&report=JSON&timeout=0';

                    $sms_res = $this->sendQualityClearSms($query_string);
                    $smsSaveRes='';
                    if($sms_res['result'] == 'success')
                    {
                        $userData = json_decode($sms_res['data'], true);
                        $message = $userData['message'];
                        $phone = $userData['report'][0][1][0]['phonenumber'];
                        $timeString = $userData['report'][0][1][0]['time'];
                        $sms_response = $this->saveQualityClearSendMessage($message, $phone, $timeString);
                        if($sms_response)
                        {
                            $smsSaveRes = 'success';
                        }
                        else
                        {
                            $smsSaveRes = 'error';
                        }
                        $smsResult = 'Successfuly!';
                    }
                    else
                    {
                        $smsResult = 'Error';
                    }
                    return redirect()->route($viewString)->with('success', 'Applicant against position is cleared from quality and quality sms sent is '.$smsResult.' sms save status is '.$smsSaveRes );
                }
            } else {
                return redirect()->route($viewString);
            }
        }
    }

    public function getAllApplicantsWithConfirmedInterview()
    {
        return view('administrator.quality.cvs.confirmed');
    }

    public function getConfirmCVApplicants()
    {
        $user = Auth::user();
        $applicant_with_cvs='';
        if($user->is_admin !== 1)
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
				$applicant_with_cvs = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
					->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
					->join('offices', 'sales.head_office', '=', 'offices.id')
					->join('units', 'sales.head_office_unit', '=', 'units.id')
					->join('cv_notes', function ($join) {
						$join->on('cv_notes.applicant_id', '=', 'quality_notes.applicant_id');
						$join->on('cv_notes.sale_id', '=', 'quality_notes.sale_id');
					})->join('users', 'users.id', '=', 'cv_notes.user_id')
					->join('users as quality_user', 'quality_user.id', '=', 'quality_notes.user_id')
					->select('quality_notes.details', 'quality_notes.quality_added_date', 'quality_notes.quality_added_time', 'quality_notes.created_at',
						'applicants.id', 'applicants.applicant_name', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.applicant_postcode', 'applicants.applicant_phone', 'applicants.applicant_homePhone','applicants.applicant_cv',
						'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
						'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
						'offices.office_name', 'units.unit_name',
						'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
						'units.contact_email', 'units.website',
						'users.name','quality_user.name as quality_name')
					->where([
						"applicants.status" => "active",
						//"quality_notes.moved_tab_to" => "cleared"
					])->whereIn("quality_notes.moved_tab_to" ,["cleared","cleared_no_job"])->whereIn('sales.head_office', $user_permissions);

            }
            else
            {
                $applicant_with_cvs = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
            ->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('cv_notes', function ($join) {
                $join->on('cv_notes.applicant_id', '=', 'quality_notes.applicant_id');
                $join->on('cv_notes.sale_id', '=', 'quality_notes.sale_id');
            })->join('users', 'users.id', '=', 'cv_notes.user_id')
			->join('users as quality_user', 'quality_user.id', '=', 'quality_notes.user_id')
            ->select('quality_notes.details', 'quality_notes.quality_added_date', 'quality_notes.quality_added_time', 'quality_notes.created_at',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.applicant_postcode', 'applicants.applicant_phone', 'applicants.applicant_homePhone','applicants.applicant_cv',
                'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
                'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name', 'units.unit_name',
                'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
                'units.contact_email', 'units.website',
                'users.name','quality_user.name as quality_name')
            ->where([
                "applicants.status" => "active",
                //"quality_notes.moved_tab_to" => "cleared"
            ])->whereIn("quality_notes.moved_tab_to" ,["cleared","cleared_no_job"]);

            }
    
        }
        else
        {
            $applicant_with_cvs = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
            ->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('cv_notes', function ($join) {
                $join->on('cv_notes.applicant_id', '=', 'quality_notes.applicant_id');
                $join->on('cv_notes.sale_id', '=', 'quality_notes.sale_id');
            })->join('users', 'users.id', '=', 'cv_notes.user_id')
			->join('users as quality_user', 'quality_user.id', '=', 'quality_notes.user_id')
            ->select('quality_notes.details', 'quality_notes.quality_added_date', 'quality_notes.quality_added_time', 'quality_notes.created_at',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.applicant_postcode', 'applicants.applicant_phone', 'applicants.applicant_homePhone','applicants.applicant_cv',
                'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
                'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name', 'units.unit_name',
                'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
                'units.contact_email', 'units.website',
                'users.name','quality_user.name as quality_name')
            ->where([
                "applicants.status" => "active",
                //"quality_notes.moved_tab_to" => "cleared"
            ])->whereIn("quality_notes.moved_tab_to" ,["cleared","cleared_no_job"]);

        }


        // $applicant_with_cvs = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
        //     ->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
        //     ->join('offices', 'sales.head_office', '=', 'offices.id')
        //     ->join('units', 'sales.head_office_unit', '=', 'units.id')
        //     ->join('cv_notes', function ($join) {
        //         $join->on('cv_notes.applicant_id', '=', 'quality_notes.applicant_id');
        //         $join->on('cv_notes.sale_id', '=', 'quality_notes.sale_id');
        //     })->join('users', 'users.id', '=', 'cv_notes.user_id')
        //     ->select('quality_notes.details', 'quality_notes.quality_added_date', 'quality_notes.quality_added_time', 'quality_notes.created_at',
        //         'applicants.id', 'applicants.applicant_name', 'applicants.applicant_job_title', 'applicants.applicant_postcode', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
        //         'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
        //         'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
        //         'offices.office_name', 'units.unit_name',
        //         'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
        //         'units.contact_email', 'units.website',
        //         'users.name')
        //     ->where([
        //         "applicants.status" => "active",
        //         "quality_notes.moved_tab_to" => "cleared"
        //     ]);

        $auth_user = Auth::user();
        $raw_columns = ['action'];
        $datatable = datatables()->of($applicant_with_cvs)
			->editColumn('applicant_job_title', function ($applicant) {
				$job_title_desc='';
				if($applicant->job_title_prof!=null)
					{

						$job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $applicant->job_title_prof)->first();
						$job_title_desc = $applicant->applicant_job_title.' ('.$job_prof_res->specialist_prof.')';
					}
					else
					{
						$job_title_desc = $applicant->applicant_job_title;
					}

							return strtoupper($job_title_desc);
				 })
            ->addColumn('action', function ($applicant) {
                return
                    '<div class="list-icons">
                        <div class="dropdown">
                            <a href="#" class="list-icons-item" data-toggle="dropdown">
                                <i class="icon-menu9"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">

                                <a href="#" class="dropdown-item" data-controls-modal="#manager_details' . $applicant->id . '-' . $applicant->sale_id . '"
                                   data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                   data-target="#manager_details' . $applicant->id . '-' . $applicant->sale_id . '">
                                    <i class="icon-file-reject"></i>
                                    Manager Details </a>
                            </div>
                        </div>
                    </div>
                        
                    <!-- Manager Details Modal -->
                    <div id="manager_details' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">
                        <div class="modal-dialog modal-sm">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Manager Details</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <ul class="list-group ">
                                        <li class="list-group-item active"><p><b>Name:</b> ' . $applicant->contact_name . '</p></li>
                                        <li class="list-group-item"><p><b>Email:</b> ' . $applicant->contact_email . '</p></li>
                                        <li class="list-group-item"><p><b>Phone:</b> ' . $applicant->contact_phone_number . '</p></li>
                                    </ul>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Manager Details Modal -->
                ';
            });
        if ($auth_user->hasPermissionTo('quality_CVs-Cleared_cv-download')) {
            $datatable = $datatable->addColumn('download', function ($applicant) {
               $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';
                return $download;
            });
            array_push($raw_columns, 'download');
        }
        if ($auth_user->hasPermissionTo('quality_CVs-Cleared_job-detail')) {
            $datatable = $datatable->addColumn('job_details', function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details' . $applicant->id . '-' . $applicant->sale_id . '"
                                         data-backdrop="static"
                                         data-keyboard="false" data-toggle="modal"
                                         data-target="#job_details' . $applicant->id . '-' . $applicant->sale_id . '">Details</a>';
                //<!-- Job Details Modal -->
                $content .= '<div id="job_details' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content pl-3 pr-4">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">' . $applicant->applicant_name . '\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<div class=" header-elements-sm-inline">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name . '/' . $applicant->unit_name;
                $content .= '</h5>';
                $content .= '<div><span class="font-weight-semibold">Posted Date: </span><span class="mb-3">' . $applicant->posted_date . '</span></div>';
                $content .= '</div>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">' . strtoupper($applicant->job_category) . ', ' . strtoupper($applicant->job_title) . '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-4"><h6 class="font-weight-semibold">Job Title:</h6><p>' . strtoupper($applicant->job_title) . '</p></div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Postcode:</h6>
                    <p class="mb-3">' . strtoupper($applicant->postcode) . '</p>
                    </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Job Type:</h6>
                    <p class="mb-3">' . ucwords($applicant->job_type) . '</p>
                    </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Timings:</h6>
                    <p class="mb-3">' . $applicant->timing . '</p>
                    </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Salary:</h6>
                    <p class="mb-3">' . $applicant->salary . '</p>
                    </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Experience:</h6>
                    <p class="mb-3">' . $applicant->experience . '</p>
                    </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Qualification:</h6>
                    <p class="mb-3">' . $applicant->qualification . '</p>
                    </div>';
                $content .= '<div class="col-8"> <h6 class="font-weight-semibold">Benefits:</h6>
                    <p class="mb-3">' . $applicant->benefits . '</p>
                    </div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer"> <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>
                    </div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                //<!-- /Job Details Modal -->
                return $content;
            });
            array_push($raw_columns, 'job_details');
        }
		 $datatable->addColumn('quality_added_date', function ($applicant) {
            return '<span data-popup="tooltip" title="'.$applicant->quality_name.'">'.@Carbon::parse($applicant->created_at)->toFormattedDateString().'</span>';
        });

        array_push($raw_columns, "quality_added_date");
		array_push($raw_columns, "applicant_job_title");
        $datatable = $datatable->rawColumns($raw_columns)
            ->make(true);

        return $datatable;
    }

    public function updateAttendInterview($applicant_id, $viewString)
    {
        Applicant::where("id", $applicant_id)->update(['is_interview_attend' => 'yes']);

        return redirect()->route($viewString);
    }

    public function getAllApplicantsWithAttendedInterview()
    {
        $applicant_with_cvs = Applicant::where("is_interview_attend", "yes")->where("is_CV_reject", "no")->get();

        return view('administrator.quality.cvs.attended', compact('applicant_with_cvs'));
    }

    public function getDownloadApplicantCv($cv_id)
    {
        $url = url()->previous();
        $applicant = Applicant::select("applicant_cv")->where('id', $cv_id)->first();
        if ($applicant['applicant_cv'] == '' || $applicant['applicant_cv'] == 'old_image') 
		{
                        return redirect($url)->with('error', 'Do not have CV');

        } else {
			if (strpos($applicant->applicant_cv, 'public') !== false) {

                $file = public_path('/' . substr($applicant->applicant_cv, 7));
            } else {
                $file = public_path('/' . $applicant->applicant_cv);
            }
            $headers = array(
                'Content-Type: application/*',
            );
            $app_cv = substr($applicant->applicant_cv, 8);

            return Response::download($file, $app_cv, $headers);
        }
    }

    public function sendEmailToManager(Request $request)
    {
        $comment = $request->input('email_message');
        $toEmail = $request->input('email_message');
        if (Mail::to($toEmail)->send(new FeedbackMail($comment))) {
            return 'success';
        } else {
            return 'fail';
        }
    }

    public function qualitySales()
    {
        return view('administrator.quality.sales.index');
    }

    public function getQualitySales(Request $request)
    {

        $user = Auth::user();
        $result='';
        if($user->is_admin !== 1)
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

                $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
					    ->whereIn('sales.status', ['pending','re_open'])

					//->whereIn('sales.head_office', $user_permissions)
                    ->orderBy('sales.updated_at', 'DESC');
            }
            else
            {
                $auth_user = Auth::user();
				$result = Office::with('user')
					->join('sales', 'offices.id', '=', 'sales.head_office')
					->join('units', 'units.id', '=', 'sales.head_office_unit')
					->join('users', 'users.id', '=', 'sales.user_id')
					->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
						'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
					//->where('sales.status', '=', 'pending')
						 ->whereIn('sales.status', ['pending','re_open'])
					->orderBy('sales.updated_at', 'DESC');

            }
    
        }
        else
        {
            $auth_user = Auth::user();
            $result = Office::with('user')
            ->join('sales', 'offices.id', '=', 'sales.head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
            //->where('sales.status', '=', 'pending')
			->whereIn('sales.status', ['pending','re_open'])
            ->orderBy('sales.updated_at', 'DESC');
        }

        $aColumns = ['sale_added_date', 'updated_at', 'job_category', 'job_title',
            'office_name', 'unit_name', 'postcode', 'job_type', 'experience', 'qualification', 'salary'];

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

           // $status = '<h5><span class="badge badge-warning">Pending</span></h5>';
			 if ($sRow->status=="pending") {
                $status = '<h5><span class="badge badge-warning">Pending</span></h5>';
            }else{
                $status = '<h5><span class="badge badge-warning">Re_Open</span></h5>';

            }

            $url = '/clear-reject-sale';
            $csrf = csrf_token();

            $action = "<div class=\"list-icons\">
                        <div class=\"dropdown\">
                            <a href=\"#\" class=\"list-icons-item\" data-toggle=\"dropdown\">
                                <i class=\"icon-menu9\"></i>
                            </a>
                            <div class=\"dropdown-menu dropdown-menu-right\">";
            if ($auth_user->hasPermissionTo('sale_edit')) {
                $action .= "<a href=\"/sales/{$sRow->id}/edit\" class=\"dropdown-item\"> Edit</a>";
            }
            if ($auth_user->hasPermissionTo('sale_view')) {
                $action .= "<a href=\"/sales/{$sRow->id}\" class=\"dropdown-item\"> View </a>";
            }
            if ($auth_user->hasAnyPermission(['quality_Sales_sale-clear', 'quality_Sales_sale-reject'])) {
                $action .=
                    "<a href=\"#\" class=\"dropdown-item\"
                                               data-controls-modal=\"#clear_reject_sale{$sRow->id}\"
                                               data-backdrop=\"static\"
                                               data-keyboard=\"false\" data-toggle=\"modal\"
                                               data-target=\"#clear_reject_sale{$sRow->id}\"
                                            > Clear/Reject </a>";
            }
            $action .=
                "<a href=\"#\" class=\"dropdown-item\"
                                               data-controls-modal=\"#manager_details{$sRow->id}\"
                                               data-backdrop=\"static\"
                                               data-keyboard=\"false\" data-toggle=\"modal\"
                                               data-target=\"#manager_details{$sRow->id}\"
                                            > Manager Details </a>";
            $action .=
                "</div>
                        </div>
                      </div>";
            if ($auth_user->hasAnyPermission(['quality_Sales_sale-clear', 'quality_Sales_sale-reject'])) {
                $action .=
                    "<div id=\"clear_reject_sale{$sRow->id}\" class=\"modal fade\" tabindex=\"-1\">
                            <div class=\"modal-dialog modal-lg\">
                                <div class=\"modal-content\">
                                    <div class=\"modal-header\">
                                        <h5 class=\"modal-title\">Clear/Reject Sale Notes</h5>
                                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\">&times;</button>
                                    </div>
                                    <form action=\"{$url}\"
                                          method=\"POST\" class=\"form-horizontal\">
                                        <input type=\"hidden\" name=\"_token\" value=\"{$csrf}\">
                                        <div class=\"modal-body\">
                                            <div class=\"form-group row\">
                                                <label class=\"col-form-label col-sm-3\">Details</label>
                                                <div class=\"col-sm-9\">
                                                    <input type=\"hidden\" name=\"sale_id\" value=\"{$sRow->id}\">
                                                    <textarea name=\"details\" class=\"form-control\" cols=\"30\" rows=\"4\"
                                                              placeholder=\"TYPE HERE..\" required></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class=\"modal-footer\">
                                            <button type=\"button\" class=\"btn btn-default legitRipple\" data-dismiss=\"modal\">
                                                Cancel
                                            </button>";
                if ($auth_user->hasPermissionTo('quality_Sales_sale-reject')) {
                    $action .=
                        "<button type=\"submit\" name='form_action' value='sale_reject' class=\"btn bg-orange-800 legitRipple\"> Reject</button>";
                }
                if ($auth_user->hasPermissionTo('quality_Sales_sale-clear')) {
                    $action .=
                        "<button type=\"submit\" name='form_action' value='sale_clear' class=\"btn bg-dark legitRipple\"> Clear</button>";
                }
                $action .=
                    "</div>
                                    </form>
                                </div>
                            </div>
                        </div>";
            }
            $action .=
                "<div id=\"manager_details{$sRow->id}\" class=\"modal fade\" tabindex=\"-1\">
                            <div class=\"modal-dialog modal-sm\">
                                <div class=\"modal-content\">
                                    <div class=\"modal-header\">
                                        <h5 class=\"modal-title\">Manager Details</h5>
                                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\">&times;</button>
                                    </div>
                                    <div class=\"modal-body\">
                                        <ul class=\"list-group\">
                                            <li class=\"list-group-item active\"><p><b>Name:</b> {$sRow->contact_name}
                                            </p></li>
                                            <li class=\"list-group-item\"><p><b>Email:</b> {$sRow->contact_email}</p></li>
                                            <li class=\"list-group-item\"><p><b>Phone#:</b> {$sRow->contact_phone_number}
                                            </p></li>
                                        </ul>
                                    </div>
                                    <div class=\"modal-footer\">
                                        <button type=\"button\" class=\"btn bg-teal legitRipple\" data-dismiss=\"modal\">CLOSE
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>";
            $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                ->where(['audits.auditable_id' => $sRow->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                ->where('audits.message', 'like', '%has been updated%')
                ->select('users.name')
                ->orderBy('audits.created_at', 'desc')->first();
            $updated_by = $updated_by ? strtoupper($updated_by->name) : strtoupper($sRow->name);
			$job_title_desc='';
            if($sRow->job_title_prof!=null)
                {
                  
        			$job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $sRow->job_title_prof)->first();
                    $job_title_desc = strtoupper($sRow->job_title.' ('.$job_prof_res->specialist_prof.')');
                }
                else
                {
                    $job_title_desc = strtoupper($sRow->job_title);
                }
			
            $output['aaData'][] = array(
                "DT_RowId" => "row_{$sRow->id}",
                //    @$checkbox,
                '<span data-popup="tooltip" title="' . $sRow->name . '">' . @Carbon::parse($sRow->sale_added_date)->toFormattedDateString() . '</span>',
                '<span data-popup="tooltip" title="' . $updated_by . '">' . @Carbon::parse($sRow->updated_at)->toFormattedDateString() . '</span>',
				//@$sRow->name,
                @strtoupper($sRow->job_category),
                $job_title_desc,
                '<span data-popup="tooltip" title="' . $sRow->user->name . '">' . @$sRow->office_name . '</span>',
                @$sRow->unit_name,
                @strtoupper($sRow->postcode),
                @ucwords($sRow->job_type),
                @$sRow->experience,
                @$sRow->qualification,
                @$sRow->salary,
                @$status,
                @$action
            );
            $i++;
        }

        //  print_r($output);
        echo json_encode($output);
    }

    public function clearRejectSale(Request $request)
    {
        date_default_timezone_set('Europe/London');
        $notes = $request->input('details');
        $id = $request->input('sale_id');
        $auth_user = Auth::user();
        $form_action = $request->input('form_action');
        $sale = Sale::find($id);
        if ($sale) {
           //old code sale reject and clear
			// $sale->update(['status' => ($form_action == 'sale_clear') ? 'active' : 'rejected']);
			//new code sale reopen show direct close tab not show quality reject
			  if ($sale->status=="re_open"){
                if ($form_action=="sale_reject"){
                    $sale->update(['status' => 'disable']);
                }else{
                    $sale->update(['status' => ($form_action == 'sale_clear') ? 'active' : 'rejected','is_re_open'=>1]);
                }
            }else{
                $sale->update(['status' => ($form_action == 'sale_clear') ? 'active' : 'rejected','is_re_open' => ($form_action == 'sale_clear') ? 1 : 0]);
            }

            $audit = new ActionObserver();
            $audit->changeSaleStatus($sale, ['status' => $sale->status]);
            Sales_notes::where('sale_id', '=', $sale->id)->update(['status' => 'disable']);
            $sale_note = new Sales_notes();
            $sale_note->sale_id = $id;
            $sale_note->user_id = $auth_user->id;
            $sale_note->sales_note_added_date = date("jS F Y");
            $sale_note->sales_note_added_time = date("h:i A");
            $sale_note->sale_note = $notes;
            $sale_note->save();

            $last_inserted_sale_note_id = $sale_note->id;
            if($last_inserted_sale_note_id > 0){
                $sale_note_uid = md5($last_inserted_sale_note_id);
                Sales_notes::where('id',$last_inserted_sale_note_id)->update(['sales_notes_uid' => $sale_note_uid,'status' => ($form_action == 'sale_clear') ? 'active' : 'disable']);
                return redirect()->back()->with('success', 'Sale '.(($form_action == 'sale_clear') ? 'opened' : 'rejected').' Successfully');
            }
        } else {
            return redirect()->back()->with('error', 'Sale not found!');
        }
    }

    public function clearedSales()
    {
		$offices = Office::where('status','active')->select('id','office_name')->orderBy('office_name','asc')->get();
        return view('administrator.quality.sales.cleared',compact('offices'));
    }

    public function getClearedSales(Request $request)
    {
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
                // $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
                // ->join('units', 'units.id', '=', 'sales.head_office_unit')
                // ->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
                // ->select('sales.*', 'offices.office_name', 'units.contact_name',
                //     'units.contact_email', 'units.unit_name', 'units.contact_phone_number', 'sales_notes.sale_note')
                // ->where(['sales.status' => 'active', 'sales.job_category' => 'nurse', 'sales_notes.status' => 'active'])->whereIn('sales.head_office', $user_permissions)->orderBy('id', 'DESC');
                $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                    ->whereIn('sales.status', ['active','disable'])->whereIn('sales.head_office', $user_permissions)
                    ->orderBy('sales.updated_at', 'DESC');
            }
            else
            {
                $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                    ->whereIn('sales.status', ['active','disable'])
                    ->orderBy('sales.updated_at', 'DESC');

            }
    
        }
        else
        {
            $auth_user = Auth::user();
            $result = Office::with('user')
                ->join('sales', 'offices.id', '=', 'sales.head_office')
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
                ->join('users', 'users.id', '=', 'sales.user_id')
                ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                ->whereIn('sales.status', ['active','disable'])
                ->orderBy('sales.updated_at', 'DESC');
        }

        // $auth_user = Auth::user();
        // $result = Office::with('user')
        //     ->join('sales', 'offices.id', '=', 'sales.head_office')
        //     ->join('units', 'units.id', '=', 'sales.head_office_unit')
        //     ->join('users', 'users.id', '=', 'sales.user_id')
        //     ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
        //         'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
        //     ->whereIn('sales.status', ['active','disable'])
        //     ->orderBy('sales.updated_at', 'DESC');

        $aColumns = ['sale_added_date', 'updated_at', 'job_category', 'job_title',
            'office_name', 'unit_name', 'postcode', 'job_type', 'experience', 'qualification', 'salary'];

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

            if($sRow->status == 'active'){
                $status = '<h5><span class="badge badge-success">Active</span></h5>';
            }else{
                $status = '<h5><span class="badge badge-danger">Closed</span></h5>';
            }
            $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                ->where(['audits.auditable_id' => $sRow->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                ->where('audits.message', 'like', '%has been updated%')
                ->select('users.name')
                ->orderBy('audits.created_at', 'desc')->first();
            $updated_by = $updated_by ? $updated_by->name : $sRow->name;
			$job_title_desc='';
            if($sRow->job_title_prof!=null)
                {
        			$job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $sRow->job_title_prof)->first();
                    $job_title_desc = strtoupper($sRow->job_title.' ('.$job_prof_res->specialist_prof.')');
                }
                else
                {
                    $job_title_desc = strtoupper($sRow->job_title);
                }
            $output['aaData'][] = array(
                "DT_RowId" => "row_{$sRow->id}",
                //    @$checkbox,
                '<span data-popup="tooltip" title="' . $sRow->name . '">' . @Carbon::parse($sRow->sale_added_date)->toFormattedDateString() . '</span>',
                '<span data-popup="tooltip" title="' . $updated_by . '">' . @Carbon::parse($sRow->updated_at)->toFormattedDateString() . '</span>',
                @strtoupper($sRow->job_category),
                $job_title_desc,
                '<span data-popup="tooltip" title="' . $sRow->user->name . '">' . @$sRow->office_name . '</span>',
                @$sRow->unit_name,
                @strtoupper($sRow->postcode),
                @ucwords($sRow->job_type),
                @$sRow->experience,
                @$sRow->qualification,
                @$sRow->salary,
                @$status
            );
            $i++;
        }
        echo json_encode($output);
    }
	
	public function getClearedSalesNurse(Request $request)
    {
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
                 $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                    ->whereIn('sales.status', ['active','disable'])
                    ->whereIn('sales.head_office', $user_permissions)
                    ->where('sales.job_category', 'nurse')
                    ->orderBy('sales.updated_at', 'DESC');
            }
            else
            {
                $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                    ->whereIn('sales.status', ['active','disable'])
                    ->where('sales.job_category', 'nurse')
                    ->orderBy('sales.updated_at', 'DESC');
            }
        }
        else
        {
            $auth_user = Auth::user();
            $result = Office::with('user')
                ->join('sales', 'offices.id', '=', 'sales.head_office')
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
                ->join('users', 'users.id', '=', 'sales.user_id')
                ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                ->whereIn('sales.status', ['active','disable'])
                ->where('sales.job_category', 'nurse')
                ->orderBy('sales.updated_at', 'DESC');
        }

        $aColumns = ['sale_added_date', 'updated_at', 'job_category', 'job_title',
            'office_name', 'unit_name', 'postcode', 'job_type', 'experience', 'qualification', 'salary'];

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

            if($sRow->status == 'active'){
                $status = '<h5><span class="badge badge-success">Active</span></h5>';
            }else{
                $status = '<h5><span class="badge badge-danger">Closed</span></h5>';
            }
            $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                ->where(['audits.auditable_id' => $sRow->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                ->where('audits.message', 'like', '%has been updated%')
                ->select('users.name')
				->where('user_id','<>','1')
                ->orderBy('audits.created_at', 'desc')->first();
            $updated_by = $updated_by ? ucwords($updated_by->name) : ucwords($sRow->name);
            $job_title_desc='';
            if($sRow->job_title_prof!=null)
            {

                $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $sRow->job_title_prof)->first();
                $job_title_desc = strtoupper($sRow->job_title.' ('.$job_prof_res->specialist_prof.')');
            }
            else
            {
                $job_title_desc = strtoupper($sRow->job_title);
            }
            $output['aaData'][] = array(
                "DT_RowId" => "row_{$sRow->id}",
                //    @$checkbox,
                '<span data-popup="tooltip" title="' . $sRow->name . '">' . @Carbon::parse($sRow->sale_added_date)->toFormattedDateString() . '</span>',
                '<span data-popup="tooltip" title="' . $updated_by . '">' . @Carbon::parse($sRow->updated_at)->toFormattedDateString() . '</span>',
                @strtoupper($sRow->job_category),
                $job_title_desc,
                '<span data-popup="tooltip" title="' . $sRow->user->name . '">' . @$sRow->office_name . '</span>',
                @$sRow->unit_name,
                @strtoupper($sRow->postcode),
                @ucwords($sRow->job_type),
                @$sRow->experience,
                @$sRow->qualification,
                @$sRow->salary,
                @$status
            );
            $i++;
        }
        echo json_encode($output);
    }
    public function getClearedSalesNonnurse(Request $request)
    {
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
                $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                    ->whereIn('sales.status', ['active','disable'])
                    ->whereIn('sales.head_office', $user_permissions)
                    ->where('sales.job_category', 'nonnurse')
                    ->orderBy('sales.updated_at', 'DESC');
            }
            else
            {
                $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                    ->whereIn('sales.status', ['active','disable'])
                    ->where('sales.job_category', 'nonnurse')
                    ->orderBy('sales.updated_at', 'DESC');
            }
        }
        else
        {
            $auth_user = Auth::user();
            $result = Office::with('user')
                ->join('sales', 'offices.id', '=', 'sales.head_office')
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
                ->join('users', 'users.id', '=', 'sales.user_id')
                ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                ->whereIn('sales.status', ['active','disable'])
                ->where('sales.job_category', 'nonnurse')
                ->orderBy('sales.updated_at', 'DESC');
        }

        $aColumns = ['sale_added_date', 'updated_at', 'job_category', 'job_title',
            'office_name', 'unit_name', 'postcode', 'job_type', 'experience', 'qualification', 'salary'];

        $iStart = $request->get('iDisplayStart');
        $iPageSize = $request->get('iDisplayLength');

        $order = 'id';
        $sort = ' DESC';

        if ($request->get('iSortCol_0')) {
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

            if($sRow->status == 'active'){
                $status = '<h5><span class="badge badge-success">Active</span></h5>';
            }else{
                $status = '<h5><span class="badge badge-danger">Closed</span></h5>';
            }
            $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                ->where(['audits.auditable_id' => $sRow->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                ->where('audits.message', 'like', '%has been updated%')
                ->select('users.name')
				->where('user_id','<>','1')
                ->orderBy('audits.created_at', 'desc')->first();
            $updated_by = $updated_by ? $updated_by->name : $sRow->name;
            $job_title_desc='';
            if($sRow->job_title_prof!=null)
            {

                $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $sRow->job_title_prof)->first();
                $job_title_desc = $sRow->job_title.' ('.$job_prof_res->specialist_prof.')';
            }
            else
            {
                $job_title_desc = $sRow->job_title;
            }
            $output['aaData'][] = array(
                "DT_RowId" => "row_{$sRow->id}",
                //    @$checkbox,
                '<span data-popup="tooltip" title="' . $sRow->name . '">' . @Carbon::parse($sRow->sale_added_date)->toFormattedDateString() . '</span>',
                '<span data-popup="tooltip" title="' . $updated_by . '">' . @Carbon::parse($sRow->updated_at)->toFormattedDateString() . '</span>',
                @$sRow->job_category,
                $job_title_desc,
                '<span data-popup="tooltip" title="' . $sRow->user->name . '">' . @$sRow->office_name . '</span>',
                @$sRow->unit_name,
                @$sRow->postcode,
                @$sRow->job_type,
                @$sRow->experience,
                @$sRow->qualification,
                @$sRow->salary,
                @$status
            );
            $i++;
        }
        echo json_encode($output);
    }
    public function getClearedSalesSpecialist(Request $request)
    {
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
               $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                    ->whereIn('sales.status', ['active','disable'])
                    ->whereIn('sales.head_office', $user_permissions)
                    ->whereNotIn('sales.job_category', ['nonnurse','nurse'])
                    ->orderBy('sales.updated_at', 'DESC');
            }
            else
            {
                $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                    ->whereIn('sales.status', ['active','disable'])
                    ->whereNotIn('sales.job_category', ['nonnurse','nurse'])
                    ->orderBy('sales.updated_at', 'DESC');

            }

        }
        else
        {
            $auth_user = Auth::user();
            $result = Office::with('user')
                ->join('sales', 'offices.id', '=', 'sales.head_office')
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
                ->join('users', 'users.id', '=', 'sales.user_id')
                ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                ->whereIn('sales.status', ['active','disable'])
                ->whereNotIn('sales.job_category', ['nonnurse','nurse'])
                ->orderBy('sales.updated_at', 'DESC');
        }

        $aColumns = ['sale_added_date', 'updated_at', 'job_category', 'job_title',
            'office_name', 'unit_name', 'postcode', 'job_type', 'experience', 'qualification', 'salary'];

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

            if($sRow->status == 'active'){
                $status = '<h5><span class="badge badge-success">Active</span></h5>';
            }else{
                $status = '<h5><span class="badge badge-danger">Closed</span></h5>';
            }
            $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                ->where(['audits.auditable_id' => $sRow->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                ->where('audits.message', 'like', '%has been updated%')
                ->select('users.name')
				->where('user_id','<>','1')
                ->orderBy('audits.created_at', 'desc')->first();
            $updated_by = $updated_by ? $updated_by->name : $sRow->name;
            $job_title_desc='';
            if($sRow->job_title_prof!=null)
            {
                $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $sRow->job_title_prof)->first();
                $job_title_desc = $sRow->job_title.' ('.$job_prof_res->specialist_prof.')';
            }
            else
            {
                $job_title_desc = $sRow->job_title;
            }
            $output['aaData'][] = array(
                "DT_RowId" => "row_{$sRow->id}",
                //    @$checkbox,
                '<span data-popup="tooltip" title="' . $sRow->name . '">' . @Carbon::parse($sRow->sale_added_date)->toFormattedDateString() . '</span>',
                '<span data-popup="tooltip" title="' . $updated_by . '">' . @Carbon::parse($sRow->updated_at)->toFormattedDateString() . '</span>',
                @$sRow->job_category,
                $job_title_desc,
                '<span data-popup="tooltip" title="' . $sRow->user->name . '">' . @$sRow->office_name . '</span>',
                @$sRow->unit_name,
                @$sRow->postcode,
                @$sRow->job_type,
                @$sRow->experience,
                @$sRow->qualification,
                @$sRow->salary,
                @$status
            );
            $i++;
        }
        echo json_encode($output);
    }
    public function allClearedSalesNurseFilter(Request $request)
    {
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
                $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                    ->whereIn('sales.status', ['active','disable'])
                    ->whereIn('sales.head_office', $user_permissions)
                    ->where('sales.job_category', 'nurse')
                    ->where('sales.head_office',$request['office_id'])
                    ->orderBy('sales.updated_at', 'DESC');
            }
            else
            {
                $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                    ->whereIn('sales.status', ['active','disable'])
                    ->where('sales.job_category', 'nurse')
                    ->where('sales.head_office',$request['office_id'])
                    ->orderBy('sales.updated_at', 'DESC');
            }
        }
        else
        {
            $auth_user = Auth::user();
            $result = Office::with('user')
                ->join('sales', 'offices.id', '=', 'sales.head_office')
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
                ->join('users', 'users.id', '=', 'sales.user_id')
                ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                ->whereIn('sales.status', ['active','disable'])
                ->where('sales.job_category', 'nurse')
                ->where('sales.head_office',$request['office_id'])
                ->orderBy('sales.updated_at', 'DESC');
        }

        $aColumns = ['sale_added_date', 'updated_at', 'job_category', 'job_title',
            'office_name', 'unit_name', 'postcode', 'job_type', 'experience', 'qualification', 'salary'];

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

            if($sRow->status == 'active'){
                $status = '<h5><span class="badge badge-success">Active</span></h5>';
            }else{
                $status = '<h5><span class="badge badge-danger">Closed</span></h5>';
            }
            $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                ->where(['audits.auditable_id' => $sRow->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                ->where('audits.message', 'like', '%has been updated%')
                ->select('users.name')
                ->orderBy('audits.created_at', 'desc')->first();
            $updated_by = $updated_by ? $updated_by->name : $sRow->name;
            $job_title_desc='';
            if($sRow->job_title_prof!=null)
            {

                $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $sRow->job_title_prof)->first();
                $job_title_desc = $sRow->job_title.' ('.$job_prof_res->specialist_prof.')';
            }
            else
            {
                $job_title_desc = $sRow->job_title;
            }
            $output['aaData'][] = array(
                "DT_RowId" => "row_{$sRow->id}",
                //    @$checkbox,
                '<span data-popup="tooltip" title="' . $sRow->name . '">' . @Carbon::parse($sRow->sale_added_date)->toFormattedDateString() . '</span>',
                '<span data-popup="tooltip" title="' . $updated_by . '">' . @Carbon::parse($sRow->updated_at)->toFormattedDateString() . '</span>',
                @$sRow->job_category,
                $job_title_desc,
                '<span data-popup="tooltip" title="' . $sRow->user->name . '">' . @$sRow->office_name . '</span>',
                @$sRow->unit_name,
                @$sRow->postcode,
                @$sRow->job_type,
                @$sRow->experience,
                @$sRow->qualification,
                @$sRow->salary,
                @$status
            );
            $i++;
        }
        echo json_encode($output);
    }
    public function allClearedSalesNonNurseFilter(Request $request)
    {
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
                $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                    ->whereIn('sales.status', ['active','disable'])
                    ->whereIn('sales.head_office', $user_permissions)
                    ->where('sales.job_category', 'nonnurse')
                    ->where('sales.head_office',$request['office_id'])
                    ->orderBy('sales.updated_at', 'DESC');
            }
            else
            {
                $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                    ->whereIn('sales.status', ['active','disable'])
                    ->where('sales.job_category', 'nonnurse')
                    ->where('sales.head_office',$request['office_id'])
                    ->orderBy('sales.updated_at', 'DESC');
            }
        }
        else
        {
            $auth_user = Auth::user();
            $result = Office::with('user')
                ->join('sales', 'offices.id', '=', 'sales.head_office')
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
                ->join('users', 'users.id', '=', 'sales.user_id')
                ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                ->whereIn('sales.status', ['active','disable'])
                ->where('sales.job_category', 'nonnurse')
                ->where('sales.head_office',$request['office_id'])
                ->orderBy('sales.updated_at', 'DESC');
        }

        $aColumns = ['sale_added_date', 'updated_at', 'job_category', 'job_title',
            'office_name', 'unit_name', 'postcode', 'job_type', 'experience', 'qualification', 'salary'];

        $iStart = $request->get('iDisplayStart');
        $iPageSize = $request->get('iDisplayLength');

        $order = 'id';
        $sort = ' DESC';

        if ($request->get('iSortCol_0')) {
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

            if($sRow->status == 'active'){
                $status = '<h5><span class="badge badge-success">Active</span></h5>';
            }else{
                $status = '<h5><span class="badge badge-danger">Closed</span></h5>';
            }
            $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                ->where(['audits.auditable_id' => $sRow->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                ->where('audits.message', 'like', '%has been updated%')
                ->select('users.name')
                ->orderBy('audits.created_at', 'desc')->first();
            $updated_by = $updated_by ? $updated_by->name : $sRow->name;
            $job_title_desc='';
            if($sRow->job_title_prof!=null)
            {

                $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $sRow->job_title_prof)->first();
                $job_title_desc = $sRow->job_title.' ('.$job_prof_res->specialist_prof.')';
            }
            else
            {
                $job_title_desc = $sRow->job_title;
            }
            $output['aaData'][] = array(
                "DT_RowId" => "row_{$sRow->id}",
                //    @$checkbox,
                '<span data-popup="tooltip" title="' . $sRow->name . '">' . @Carbon::parse($sRow->sale_added_date)->toFormattedDateString() . '</span>',
                '<span data-popup="tooltip" title="' . $updated_by . '">' . @Carbon::parse($sRow->updated_at)->toFormattedDateString() . '</span>',
                @$sRow->job_category,
                $job_title_desc,
                '<span data-popup="tooltip" title="' . $sRow->user->name . '">' . @$sRow->office_name . '</span>',
                @$sRow->unit_name,
                @$sRow->postcode,
                @$sRow->job_type,
                @$sRow->experience,
                @$sRow->qualification,
                @$sRow->salary,
                @$status
            );
            $i++;
        }
        echo json_encode($output);
    }
    public function allClearedSalesSpecialistFilter(Request $request)
    {
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
                $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                    ->whereIn('sales.status', ['active','disable'])
                    ->whereIn('sales.head_office', $user_permissions)
                    ->whereNotIn('sales.job_category', ['nonnurse','nurse'])
                    ->where('sales.head_office',$request['office_id'])
                    ->orderBy('sales.updated_at', 'DESC');
            }
            else
            {
                $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                    ->whereIn('sales.status', ['active','disable'])
                    ->whereNotIn('sales.job_category', ['nonnurse','nurse'])
                    ->where('sales.head_office',$request['office_id'])
                    ->orderBy('sales.updated_at', 'DESC');

            }

        }
        else
        {
            $auth_user = Auth::user();
            $result = Office::with('user')
                ->join('sales', 'offices.id', '=', 'sales.head_office')
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
                ->join('users', 'users.id', '=', 'sales.user_id')
                ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                ->whereIn('sales.status', ['active','disable'])
                ->whereNotIn('sales.job_category', ['nonnurse','nurse'])
                ->where('sales.head_office',$request['office_id'])
                ->orderBy('sales.updated_at', 'DESC');
        }

        $aColumns = ['sale_added_date', 'updated_at', 'job_category', 'job_title',
            'office_name', 'unit_name', 'postcode', 'job_type', 'experience', 'qualification', 'salary'];

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

            if($sRow->status == 'active'){
                $status = '<h5><span class="badge badge-success">Active</span></h5>';
            }else{
                $status = '<h5><span class="badge badge-danger">Closed</span></h5>';
            }
            $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                ->where(['audits.auditable_id' => $sRow->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                ->where('audits.message', 'like', '%has been updated%')
                ->select('users.name')
                ->orderBy('audits.created_at', 'desc')->first();
            $updated_by = $updated_by ? $updated_by->name : $sRow->name;
            $job_title_desc='';
            if($sRow->job_title_prof!=null)
            {
                $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $sRow->job_title_prof)->first();
                $job_title_desc = $sRow->job_title.' ('.$job_prof_res->specialist_prof.')';
            }
            else
            {
                $job_title_desc = $sRow->job_title;
            }
            $output['aaData'][] = array(
                "DT_RowId" => "row_{$sRow->id}",
                //    @$checkbox,
                '<span data-popup="tooltip" title="' . $sRow->name . '">' . @Carbon::parse($sRow->sale_added_date)->toFormattedDateString() . '</span>',
                '<span data-popup="tooltip" title="' . $updated_by . '">' . @Carbon::parse($sRow->updated_at)->toFormattedDateString() . '</span>',
                @$sRow->job_category,
                $job_title_desc,
                '<span data-popup="tooltip" title="' . $sRow->user->name . '">' . @$sRow->office_name . '</span>',
                @$sRow->unit_name,
                @$sRow->postcode,
                @$sRow->job_type,
                @$sRow->experience,
                @$sRow->qualification,
                @$sRow->salary,
                @$status
            );
            $i++;
        }
        echo json_encode($output);
    }

    public function rejectedSales()
    {
        return view('administrator.quality.sales.rejected');
    }

    public function getRejectedSales(Request $request)
    {
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
            //     $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
            //     ->join('units', 'units.id', '=', 'sales.head_office_unit')
            //     ->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
            //     ->select('sales.*', 'offices.office_name', 'units.contact_name',
            //         'units.contact_email', 'units.unit_name', 'units.contact_phone_number', 'sales_notes.sale_note')
            //     ->where(['sales.status' => 'active', 'sales.job_category' => 'nurse', 'sales_notes.status' => 'active'])->whereIn('sales.head_office', $user_permissions)->orderBy('id', 'DESC');
                $auth_user = Auth::user();
                $result = Office::with('user')
                    ->join('sales', 'offices.id', '=', 'sales.head_office')
                    ->join('units', 'units.id', '=', 'sales.head_office_unit')
                    ->join('users', 'users.id', '=', 'sales.user_id')
                    ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                        'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                    ->where('sales.status', 'rejected')->whereIn('sales.head_office', $user_permissions)
                    ->orderBy('sales.updated_at', 'DESC');
            }
            else
            {
                    $auth_user = Auth::user();
                $result = Office::with('user')
                ->join('sales', 'offices.id', '=', 'sales.head_office')
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
                ->join('users', 'users.id', '=', 'sales.user_id')
                ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                ->where('sales.status', 'rejected')
                ->orderBy('sales.updated_at', 'DESC');

            }
    
        }
        else
        {
            $auth_user = Auth::user();
            $result = Office::with('user')
                ->join('sales', 'offices.id', '=', 'sales.head_office')
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
                ->join('users', 'users.id', '=', 'sales.user_id')
                ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
                ->where('sales.status', 'rejected')
                ->orderBy('sales.updated_at', 'DESC');
        }

        $auth_user = Auth::user();
        $result = Office::with('user')
            ->join('sales', 'offices.id', '=', 'sales.head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->select('sales.*', 'offices.office_name', 'units.contact_name', 'users.name',
                'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
            ->where('sales.status', 'rejected')
            ->orderBy('sales.updated_at', 'DESC');

        $aColumns = ['sale_added_date', 'updated_at', 'job_category', 'job_title',
            'office_name', 'unit_name', 'postcode', 'job_type', 'experience', 'qualification', 'salary'];

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

            $status = '<h5><span class="badge badge-danger">Rejected</span></h5>';

            $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                ->where(['audits.auditable_id' => $sRow->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                ->where('audits.message', 'like', '%has been updated%')
                ->select('users.name')
				->where('user_id','<>','1')
                ->orderBy('audits.created_at', 'desc')->first();
            $updated_by = $updated_by ? $updated_by->name : $sRow->name;
			$job_title_desc='';
            if($sRow->job_title_prof!=null)
                {
                  
        $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $sRow->job_title_prof)->first();
                    $job_title_desc = strtoupper($sRow->job_title.' ('.$job_prof_res->specialist_prof.')');
                }
                else
                {
                    $job_title_desc = strtoupper($sRow->job_title);
                }
            $output['aaData'][] = array(
                "DT_RowId" => "row_{$sRow->id}",
                //    @$checkbox,
                '<span data-popup="tooltip" title="' . $sRow->name . '">' . @Carbon::parse($sRow->sale_added_date)->toFormattedDateString() . '</span>',
                '<span data-popup="tooltip" title="' . $updated_by . '">' . @Carbon::parse($sRow->updated_at)->toFormattedDateString() . '</span>',
                @strtoupper($sRow->job_category),
                $job_title_desc,
                '<span data-popup="tooltip" title="' . $sRow->user->name . '">' . @$sRow->office_name . '</span>',
                @$sRow->unit_name,
                @strtoupper($sRow->postcode),
                @ucwords($sRow->job_type),
                @$sRow->experience,
                @$sRow->qualification,
                @$sRow->salary,
                @$status
            );
            $i++;
        }
        echo json_encode($output);
    }
	
	
	    public function getQualityHOldCVApplicants(Request $request)
    {
        // Get the order parameter from the request
        $orderColumnIndex = $request->input('order.0.column'); // Column index
        $orderDirection = $request->input('order.0.dir'); // asc or desc
        $orderColumn = $request->input("columns.$orderColumnIndex.name"); // Get column name
        
        $auth_user = Auth::user();
        $query = Applicant::join('cv_notes', 'applicants.id', '=', 'cv_notes.applicant_id')
            ->join('sales', 'cv_notes.sale_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('revert_stages', function ($join) {
                $join->on('applicants.id', '=', 'revert_stages.applicant_id')
                    ->on('sales.id', '=', 'revert_stages.sale_id')
                    ->whereIn('revert_stages.id', function ($query) {
                        $query->select(DB::raw('MAX(id)'))
                            ->from('revert_stages')
                            ->whereColumn('applicant_id', 'applicants.id')
                            ->whereColumn('sale_id', 'sales.id')
                            ->whereIn('stage', ['quality_note', 'cv_hold', 'no_job_quality_cvs']);
                    });
            })
            ->join('users', 'users.id', '=', 'revert_stages.user_id')
            ->join('history', function ($join) {
                $join->on('cv_notes.applicant_id', '=', 'history.applicant_id')
                    ->on('cv_notes.sale_id', '=', 'history.sale_id');
            })
            ->select(
                'cv_notes.applicant_id as cv_note_app_id',
                'cv_notes.details',
                'cv_notes.send_added_date',
                'cv_notes.send_added_time',
                'cv_notes.status as cv_note_status',
                'applicants.id',
                'applicants.applicant_name',
                'applicants.applicant_postcode',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_phone',
                'sales.id as sale_id',
                'sales.job_category as sale_job_category',
                'sales.job_title',
                'sales.postcode',
                'sales.job_type',
                'sales.timing',
                'sales.salary',
                'sales.experience',
                'sales.qualification',
                'sales.benefits',
                'sales.posted_date',
                'offices.office_name',
                'units.unit_name',
                'units.unit_postcode',
                'units.contact_name',
                'units.contact_phone_number',
                'units.contact_landline',
                'units.contact_email',
                'units.website',
                'users.name as user_name',
                'revert_stages.user_id as revert_user_id',
                'revert_stages.notes as revert_notes',
                'revert_stages.stage as revert_stage',
                'revert_stages.updated_at as revert_stage_updated_at'
            )
            ->where([
                "applicants.status" => "active",
                "cv_notes.status" => "active",
                "history.sub_stage" => "quality_cvs_hold",
                "history.status" => "active"
            ])
            ->orderBy($orderColumn, $orderDirection); // Apply sorting

        if ($auth_user->is_admin !== 1) {
            $permissions = $auth_user->getAllPermissions()->pluck('name', 'id');
            $user_permissions = $this->extractUserPermissions($permissions);

            if (!empty($user_permissions)) {
                $query->whereIn('sales.head_office', $user_permissions);
            }
        }

        $applicants = $query;

        $raw_columns = ['action'];
        $datatable = datatables()->of($applicants)
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {

                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where("id", $applicant->job_title_prof)->first();
                    $job_title_desc = $applicant->applicant_job_title . ' (' . $job_prof_res->specialist_prof . ')';
                } else {
                    $job_title_desc = $applicant->applicant_job_title;
                }

                return strtoupper($job_title_desc);
            })
            ->editColumn('updated_at', function ($applicant) {
                return Carbon::parse($applicant->revert_stage_updated_at)->toFormattedDateString();
            })
            ->editColumn('updated_time', function ($applicant) {
                return Carbon::parse($applicant->revert_stage_updated_at)->toFormattedTimeString();
            })
			->editColumn('applicant_postcode', function ($applicant) {
                return strtoupper($applicant->applicant_postcode);
            })
            ->addColumn('action', function ($applicant) use ($auth_user) {
                $content =
                    '<div class="list-icons">
                        <div class="dropdown">
                            <a href="#" class="list-icons-item" data-toggle="dropdown">
                                <i class="icon-menu9"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasPermissionTo('quality_CVs_cv-hold') || $auth_user->id == '15') {
                    $jobHide = Sale::where('id', $applicant->sale_id)->first();
                    if ($jobHide->status == "disable") {
                        $content .= '';
                    } else {
                        $content .=
                            '<a href="#" class="dropdown-item"
                                   data-controls-modal="#revert_to_quality_hold_cvs' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                   data-keyboard="false" data-toggle="modal"
                                   data-target="#revert_to_quality_hold_cvs' . $applicant->id . '-' . $applicant->sale_id . '">
                                    <i class="icon-file-confirm"></i> Revert Cv</a>';
                    }
                }

                if ($auth_user->hasPermissionTo('quality_CVs_cv-hold') || $auth_user->id == '15') {
                    $content .=
                        '<a href="#" class="dropdown-item" data-controls-modal="#reject_cv' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                   data-keyboard="false" data-toggle="modal" data-target="#reject_cv' . $applicant->id . '-' . $applicant->sale_id . '">
                                    <i class="icon-file-reject"></i>
                                    Reject Cv</a>';
                }

                $content .= '<a href="#" class="dropdown-item notes_history" data-applicant="' . $applicant->id . '" data-sale="' . $applicant->sale_id . '" data-controls-modal="#notes_history' . $applicant->id . '"
                                           data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#notes_history' . $applicant->id . '"
                                        > <i class="icon-file-reject"></i>Notes History </a>';

                $content .=
                    '<a href="#" class="dropdown-item" data-controls-modal="#manager_details' . $applicant->id . '-' . $applicant->sale_id . '"
                                   data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                   data-target="#manager_details' . $applicant->id . '-' . $applicant->sale_id . '">
                                    <i class="icon-file-reject"></i>
                                    Manager Details </a>';
                $content .=
                    '</div>
                        </div>
                    </div>';


                /*** Manager Details Modal */
                $content .=
                    '<div id="manager_details' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">
                        <div class="modal-dialog ">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Manager Details</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <ul class="list-group ">
                                        <li class="list-group-item active"><p class="col-12"><b>Name:</b>' . $applicant->contact_name . '</p></li>
                                        <li class="list-group-item"><p><b>Email:</b>' . $applicant->contact_email . '</p></li>
                                        <li class="list-group-item"><p><b>Phone:</b>' . $applicant->contact_phone_number . '</p></li>
                                    </ul>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>';
                /*** /Manager Details Modal */

                if ($auth_user->hasPermissionTo('quality_CVs_cv-hold')) {
                    $content .=
                        '<!-- Revert To Quality > CVs Modal -->
                    <div id="revert_to_quality_hold_cvs' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Rejected CV Notes</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>

                                <form action="' . route('revertHoldQualityCv') . '"
                                      method="POST" class="form-horizontal">
                                    <input type="hidden" name="_token" value="' . csrf_token() . '">
                                    <div class="modal-body">
                                        <div class="form-group row">
                                            <label class="col-form-label col-sm-3">Details</label>
                                            <div class="col-sm-9">
                                                <input type="hidden" name="applicant_hidden_id"
                                                       value="' . $applicant->id . '">
                                                <input type="hidden" name="job_hidden_id"
                                                       value="' . $applicant->sale_id . '">
                                                <textarea name="details" class="form-control" cols="30" rows="4"
                                                          placeholder="TYPE HERE.." required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" name="rejected_cv_revert_to_quality_cvs" value="rejected_cv_revert_to_quality_cvs"
                                                class="btn bg-dark legitRipple">Quality CV
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- ./Revert To Quality > CVs Modal -->';
                }

                if ($auth_user->hasPermissionTo('quality_CVs_cv-hold')) {
                    /*** Reject CV Modal */
                    $content .=
                        '<div id="reject_cv' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Reject CV Notes</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>

                                <form action="' . route('updateToRejectedCV', ['id' => $applicant->id, 'viewString' => 'applicantWithSentCv']) . '"
                                      method="GET" class="form-horizontal"><input type="hidden" name="_token" value="' . csrf_token() . '">
                                    <div class="modal-body">
                                        <div class="form-group row">
                                            <label class="col-form-label col-sm-3">Details</label>
                                            <div class="col-sm-9">
                                                <input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">
                                                <input type="hidden" name="applicant_hidden_id" value="{{ $applicant->id }}">
                                                <textarea name="details" class="form-control" cols="30" rows="4"
                                                          placeholder="TYPE HERE.." required></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn bg-teal legitRipple">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>';
                    /*** /reject CV Modal */
                }

                $content .= '<div id="notes_history' . $applicant->id . '" class="modal fade" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Applicant Notes History - 
                                        <span class="font-weight-semibold">' . $applicant->applicant_name . '</span></h5>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body" id="applicants_notes_history' . $applicant->id . '" style="max-height: 500px; overflow-y: auto;">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                        </button>
                                    </div>
                                    
                                </div>
                            </div>
                          </div>';

                return $content;
            });

        if ($auth_user->hasPermissionTo('quality_CVs_cv-download')) {
            $datatable = $datatable->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                        <i class="fas fa-file-download ' . $disabled_color . '"></i>
                        </a>';
                return $download;
            });
            array_push($raw_columns, 'download');
        }
        if ($auth_user->hasPermissionTo('quality_CVs_job-detail')) {
            $datatable = $datatable->addColumn('job_details', function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details' . $applicant->id . '-' . $applicant->sale_id . '"
                                            data-backdrop="static"
                                            data-keyboard="false" data-toggle="modal"
                                            data-target="#job_details' . $applicant->id . '-' . $applicant->sale_id . '">Details</a>';
                // Job Details Modal
                $content .= '<div id="job_details' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content pl-3 pr-4">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">' . $applicant->applicant_name . '\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<div class=" header-elements-sm-inline">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name . '/' . $applicant->unit_name;
                $content .= '</h5>';
                $content .= '<div><span class="font-weight-semibold">Posted Date: </span><span class="mb-3">' . $applicant->posted_date . '</span></div>';
                $content .= '</div>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">' . strtoupper($applicant->job_category) . ', ' . strtoupper($applicant->job_title) . '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-4"><h6 class="font-weight-semibold">Job Title:</h6><p>' . strtoupper($applicant->job_title) . '</p></div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Postcode:</h6>
                        <p class="mb-3">' . strtoupper($applicant->postcode) . '</p>
                        </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Job Type:</h6>
                        <p class="mb-3">' . ucwords($applicant->job_type) . '</p>
                        </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Timings:</h6>
                        <p class="mb-3">' . $applicant->timing . '</p>
                        </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Salary:</h6>
                        <p class="mb-3">' . $applicant->salary . '</p>
                        </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Experience:</h6>
                        <p class="mb-3">' . $applicant->experience . '</p>
                        </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Qualification:</h6>
                        <p class="mb-3">' . $applicant->qualification . '</p>
                        </div>';
                $content .= '<div class="col-8"> <h6 class="font-weight-semibold">Benefits:</h6>
                        <p class="mb-3">' . $applicant->benefits . '</p>
                        </div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer"> <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>
                        </div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                //<!-- /Job Details Modal -->
                return $content;
            });
            array_push($raw_columns, "job_details");
        }

        $datatable = $datatable->addColumn('updated_cv', function ($applicant) {
            $filePath = $applicant->updated_cv;

            // Check if the file exists
            $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
            $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
            $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
            return
                '<a class="download-link ' . $disabled . '" href="' . $href . '">
                        <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
        });
        array_push($raw_columns, 'updated_cv');
        array_push($raw_columns, "applicant_job_title");

        $datatable = $datatable->addColumn('upload', function ($applicant) {
            return
                '<a href="#"
                data-controls-modal="#import_applicant_cv" class="import_cv"
                data-backdrop="static"
                data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
                data-target="#import_applicant_cv">
                <i class="fas fa-file-upload text-teal-400"></i>
                &nbsp;</a>';
        });
        array_push($raw_columns, 'upload');
        $datatable = $datatable->rawColumns($raw_columns)
            ->make(true);

        return $datatable;
    }
	
	 // Helper method to extract user permissions
    protected function extractUserPermissions($permissions)
    {
        $user_permissions = [];
        foreach ($permissions as $per) {
            if (str_contains($per, 'Hoffice_')) {
                $res = explode("-", $per);
                $user_permissions[] = $res[1];
            }
        }
        return $user_permissions;
    }
	
	public function getQualityNoJobCVApplicants()
    {
        $auth_user = Auth::user();

        $applicant_with_cvs = Applicant::join('cv_notes', 'applicants.id', '=', 'cv_notes.applicant_id')
            ->join('users', 'users.id', '=', 'cv_notes.user_id')
            ->join('sales', 'cv_notes.sale_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('cv_notes.applicant_id', '=', 'history.applicant_id')
                    ->on('cv_notes.sale_id', '=', 'history.sale_id');
            })
            ->select(
                'cv_notes.applicant_id as cv_note_app_id', 
				'cv_notes.created_at as cv_note_created_at', 
                'cv_notes.details', 
                'cv_notes.send_added_date', 
                'cv_notes.send_added_time', 
                'cv_notes.status as cv_note_status',
                'applicants.*', 
                'sales.id as sale_id', 
                'sales.job_category', 
                'sales.job_title', 
                'sales.postcode', 
                'sales.job_type',
                'sales.timing', 
                'sales.salary', 
                'sales.experience', 
                'sales.qualification', 
                'sales.benefits', 
                'sales.posted_date',
                'offices.office_name', 
                'units.unit_name', 
                'units.unit_postcode', 
                'units.contact_name', 
                'units.contact_phone_number', 
                'units.contact_landline',
                'units.contact_email', 
                'units.website',
                'users.name'
            )
            ->where([
                "applicants.status" => "active",
                "history.sub_stage" => "no_job_quality_cvs",
                "history.status" => "active"
            ]);

        if ($auth_user->is_admin !== 1) {
            $permissions = $auth_user->getAllPermissions()->pluck('name', 'id');
            $user_permissions = $this->extractUserPermissions($permissions);

            if (!empty($user_permissions)) {
                $applicant_with_cvs->whereIn('sales.head_office', $user_permissions);
            }
        }

        $raw_columns = ['action'];
        $datatable = datatables()->of($applicant_with_cvs)
            ->editColumn('applicant_job_title', function ($applicant_with_cvs) {
                $job_title_desc = '';
                if ($applicant_with_cvs->job_title_prof != null) {

                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where("id", $applicant_with_cvs->job_title_prof)->first();
                    $job_title_desc = $applicant_with_cvs->applicant_job_title . ' (' . $job_prof_res->specialist_prof . ')';
                } else {
                    $job_title_desc = $applicant_with_cvs->applicant_job_title;
                }

                return strtoupper($job_title_desc);
            })
			 ->editColumn('updated_at', function ($applicant_with_cvs) {
                return Carbon::parse($applicant_with_cvs->cv_note_created_at)->toFormattedDateString();
            })
            ->editColumn('updated_time', function ($applicant_with_cvs) {
                return Carbon::parse($applicant_with_cvs->cv_note_created_at)->toFormattedTimeString();
            })
			->editColumn('applicant_postcode', function ($applicant_with_cvs) {
                return strtoupper($applicant_with_cvs->applicant_postcode);
            })
            ->addColumn('action', function ($applicant) use ($auth_user) {
                $content =
                    '<div class="list-icons">
                        <div class="dropdown">
                            <a href="#" class="list-icons-item" data-toggle="dropdown">
                                <i class="icon-menu9"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasPermissionTo('quality_CVs_cv-clear')) {
                    $content .=
                        '<a href="#" class="dropdown-item sms_action_option" data-controls-modal="#clear_cv' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                   data-keyboard="false" data-toggle="modal"
								   data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                   data-applicantNameJs="' . $applicant->applicant_name . '"
                                    data-applicantIdJs="' . $applicant->id . '"
								   data-target="#clear_cv' . $applicant->id . '-' . $applicant->sale_id . '">
                                    <i class="icon-file-confirm"></i>
                                    Clear
                                </a>';
                }

                if ($auth_user->hasPermissionTo('quality_CVs_cv-hold')) {
                    $content .=
                        '<a href="#" class="dropdown-item" data-controls-modal="#reject_cv' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                   data-keyboard="false" data-toggle="modal" data-target="#reject_cv' . $applicant->id . '-' . $applicant->sale_id . '">
                                    <i class="icon-file-reject"></i>
                                    Reject </a>';
                }

                $content .= '<a href="#" class="dropdown-item notes_history" data-applicant="' . $applicant->id . '" data-sale="' . $applicant->sale_id . '" data-controls-modal="#notes_history' . $applicant->id . '"
                                           data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#notes_history' . $applicant->id . '"
                                        > <i class="icon-file-reject"></i>Notes History </a>';

                $content .=
                    '<a href="#" class="dropdown-item" data-controls-modal="#manager_details' . $applicant->id . '-' . $applicant->sale_id . '"
                                   data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                   data-target="#manager_details' . $applicant->id . '-' . $applicant->sale_id . '">
                                    <i class="icon-file-reject"></i>
                                    Manager Details </a>';
                $content .=
                    '</div>
                        </div>
                    </div>';


                /*** Manager Details Modal */
                $content .=
                    '<div id="manager_details' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">
                        <div class="modal-dialog ">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Manager Details</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <ul class="list-group ">
                                        <li class="list-group-item active"><p class="col-12"><b>Name:</b>' . $applicant->contact_name . '</p></li>
                                        <li class="list-group-item"><p><b>Email:</b>' . $applicant->contact_email . '</p></li>
                                        <li class="list-group-item"><p><b>Phone:</b>' . $applicant->contact_phone_number . '</p></li>
                                    </ul>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>';
                /*** /Manager Details Modal */
                if ($auth_user->hasPermissionTo('quality_CVs_cv-clear')) {
                    /*** Clear CV Modal */
                    $content .=
                        '<div id="clear_cv' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Clear CV Notes</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>

                                <form action="' . route('updateToInterviewNoJobConfirmed', ['id' => $applicant->id, 'viewString' => 'applicantWithSentCv']) . '"
                                      method="GET" class="form-horizontal msg_form_id"><input type="hidden" name="_token" value="' . csrf_token() . '">
                                    <div class="modal-body">
                                        <div class="form-group row">
                                            <label class="col-form-label col-sm-3">Details</label>
                                            <div class="col-sm-9">
                                                <input type="hidden" name="applicant_hidden_id" id="applicant_hidden_id" value="' . $applicant->id . '">
												<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">
                                                <input type="hidden" name="applicant_name_chat" id="applicant_name_chat">
                                                <input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">
                                                <input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">
                                                <textarea name="details" class="form-control" cols="30" rows="4"
                                                          placeholder="TYPE HERE.." required></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
									    
                                        <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn bg-teal legitRipple">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>';
                    /*** /Clear CV Modal */
                }
                if ($auth_user->hasPermissionTo('quality_CVs-Rejected_revert-quality-cv')) {
                    $content .=
                        '<!-- Revert To Quality > CVs Modal -->
                    <div id="revert_to_quality_cvs' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Revert CV Notes</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>

                                <form action="' . route('revertHoldQualityCv') . '"
                                      method="POST" class="form-horizontal">
                                    <input type="hidden" name="_token" value="' . csrf_token() . '">
                                    <div class="modal-body">
                                        <div class="form-group row">
                                            <label class="col-form-label col-sm-3">Details</label>
                                            <div class="col-sm-9">
                                                <input type="hidden" name="applicant_hidden_id"
                                                       value="' . $applicant->id . '">
                                                <input type="hidden" name="job_hidden_id"
                                                       value="' . $applicant->sale_id . '">
                                                <textarea name="details" class="form-control" cols="30" rows="4"
                                                          placeholder="TYPE HERE.." required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" name="rejected_cv_revert_to_quality_cvs" value="rejected_cv_revert_to_quality_cvs"
                                                class="btn bg-dark legitRipple">Quality CV
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- ./Revert To Quality > CVs Modal -->';
                }
                if ($auth_user->hasPermissionTo('quality_CVs_cv-clear')) {
                    /*** Clear CV Modal */
                    $content .=
                        '<div id="on_hold_cv' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">On Hold CV Notes</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>

                                <form action="' . route('updateToInterviewNoJobConfirmed', ['id' => $applicant->id, 'viewString' => 'applicantWithSentCv']) . '"
                                      method="GET" class="form-horizontal msg_form_id"><input type="hidden" name="_token" value="' . csrf_token() . '">
                                    <div class="modal-body">
                                        <div class="form-group row">
                                            <label class="col-form-label col-sm-3">Details</label>
                                            <div class="col-sm-9">
                                                <input type="hidden" name="applicant_hidden_id" id="applicant_hidden_id" value="' . $applicant->id . '">
												<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">
                                                <input type="hidden" name="applicant_name_chat" id="applicant_name_chat">
                                                <input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">
                                                <input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">
                                                <textarea name="details" class="form-control" cols="30" rows="4"
                                                          placeholder="TYPE HERE.." required></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
								   
                                        <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn bg-teal legitRipple">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>';
                    /*** /Clear CV Modal */
                }
                if ($auth_user->hasPermissionTo('quality_CVs_cv-reject')) {
                    /*** Reject CV Modal */
                    $content .=
                        '<div id="reject_cv' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Reject CV Notes</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>

                                <form action="' . route('updateToRejectedCV', ['id' => $applicant->id, 'viewString' => 'applicantWithSentCv']) . '"
                                      method="GET" class="form-horizontal"><input type="hidden" name="_token" value="' . csrf_token() . '">
                                    <div class="modal-body">
                                        <div class="form-group row">
                                            <label class="col-form-label col-sm-3">Details</label>
                                            <div class="col-sm-9">
                                                <input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">
                                                <input type="hidden" name="applicant_hidden_id" value="{{ $applicant->id }}">
                                                <textarea name="details" class="form-control" cols="30" rows="4"
                                                          placeholder="TYPE HERE.." required></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn bg-teal legitRipple">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>';
                    /*** /reject CV Modal */
                }

                $content .= '<div id="notes_history' . $applicant->id . '" class="modal fade" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Applicant Notes History - 
                                        <span class="font-weight-semibold">' . $applicant->applicant_name . '</span></h5>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body" id="applicants_notes_history' . $applicant->id . '" style="max-height: 500px; overflow-y: auto;">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                        </button>
                                    </div>
                                    
                                </div>
                            </div>
                          </div>';

                return $content;
            });

        if ($auth_user->hasPermissionTo('quality_CVs_cv-download')) {
            $datatable = $datatable->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                        <i class="fas fa-file-download ' . $disabled_color . '"></i>
                        </a>';
                return $download;
            });
            array_push($raw_columns, 'download');
        }
        if ($auth_user->hasPermissionTo('quality_CVs_job-detail')) {
            $datatable = $datatable->addColumn('job_details', function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details' . $applicant->id . '-' . $applicant->sale_id . '"
                                            data-backdrop="static"
                                            data-keyboard="false" data-toggle="modal"
                                            data-target="#job_details' . $applicant->id . '-' . $applicant->sale_id . '">Details</a>';
                // Job Details Modal
                $content .= '<div id="job_details' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content pl-3 pr-4">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">' . $applicant->applicant_name . '\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<div class=" header-elements-sm-inline">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name . '/' . $applicant->unit_name;
                $content .= '</h5>';
                $content .= '<div><span class="font-weight-semibold">Posted Date: </span><span class="mb-3">' . $applicant->posted_date . '</span></div>';
                $content .= '</div>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">' . strtoupper($applicant->job_category) . ', ' . strtoupper($applicant->job_title) . '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-4"><h6 class="font-weight-semibold">Job Title:</h6><p>' . strtoupper($applicant->job_title) . '</p></div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Postcode:</h6>
                        <p class="mb-3">' . strtoupper($applicant->postcode) . '</p>
                        </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Job Type:</h6>
                        <p class="mb-3">' . ucwords($applicant->job_type) . '</p>
                        </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Timings:</h6>
                        <p class="mb-3">' . $applicant->timing . '</p>
                        </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Salary:</h6>
                        <p class="mb-3">' . $applicant->salary . '</p>
                        </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Experience:</h6>
                        <p class="mb-3">' . $applicant->experience . '</p>
                        </div>';
                $content .= '<div class="col-4"> <h6 class="font-weight-semibold">Qualification:</h6>
                        <p class="mb-3">' . $applicant->qualification . '</p>
                        </div>';
                $content .= '<div class="col-8"> <h6 class="font-weight-semibold">Benefits:</h6>
                        <p class="mb-3">' . $applicant->benefits . '</p>
                        </div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer"> <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>
                        </div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                //<!-- /Job Details Modal -->
                return $content;
            });
            array_push($raw_columns, "job_details");
        }
        $datatable = $datatable->addColumn('updated_cv', function ($applicants) {
            $filePath = $applicants->updated_cv;

            // Check if the file exists
            $disabled = (!file_exists($filePath) || $applicants->updated_cv == null) ? 'disabled' : '';
            $disabled_color = (!file_exists($filePath) || $applicants->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
            $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicants->id);
            return
                '<a class="download-link ' . $disabled . '" href="' . $href . '">
                        <i class="fas fa-file-download ' . $disabled_color . '"></i>
                        </a>';
        });
        array_push($raw_columns, 'updated_cv');
        array_push($raw_columns, "applicant_job_title");

        $datatable = $datatable->addColumn('upload', function ($applicants) {
            return
                '<a href="#"
                data-controls-modal="#import_applicant_cv" class="import_cv"
                data-backdrop="static"
                data-keyboard="false" data-toggle="modal" data-id="' . $applicants->id . '"
                data-target="#import_applicant_cv">
                <i class="fas fa-file-upload text-teal-400"></i>
                &nbsp;</a>';
        });
        array_push($raw_columns, 'upload');
        $datatable = $datatable->rawColumns($raw_columns)
            ->make(true);
        return $datatable;
    }

	 public function updateCVHoldRevertSentCV($applicant_id)
	 {
        date_default_timezone_set('Europe/London');
		 
        $audit_data['action'] = "Reject";
        $audit_data['applicant'] = $applicant = request()->applicant_hidden_id;
        $applicant_id=$applicant;
        $audit_data['sale'] = $sale_id = request()->job_hidden_id;

        $user = Auth::user();
        $current_user_id = $user->id;
		 
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
		
		$details = request()->details. " --- Opened By: " . $user->name;

        $quality_notes = new Quality_notes();
        $audit_data['applicant'] = $quality_notes->applicant_id = $applicant_id;
        $quality_notes->user_id = $current_user_id;
        $quality_notes->sale_id = $sale_id;
        $audit_data['details'] = $quality_notes->details = $details;
        $audit_data['added_date'] = $quality_notes->quality_added_date = date("jS F Y");
        $audit_data['added_time'] = $quality_notes->quality_added_time = date("h:i A");
        $quality_notes->moved_tab_to = "cv_hold";
        $quality_notes->save();

        $last_inserted_note = $quality_notes->id;
        if ($last_inserted_note > 0) {
            $quality_note_uid = md5($last_inserted_note);
            Quality_notes::where('id', $last_inserted_note)
				->update(['quality_notes_uid' => $quality_note_uid]);
			
            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(["status" => "disable"]);
			
            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $current_user_id;
            $history->sale_id = $sale_id;
            $history->stage = 'quality';
            $history->sub_stage = 'quality_cvs_hold';
            $history->history_added_date = date("jS F Y");
            $history->history_added_time = date("h:i A");
            $history->save();
			
            $last_inserted_history = $history->id;
            if ($last_inserted_history > 0) {
                $history_uid = md5($last_inserted_history);
				
                History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
				
                RevertStage::create([
                    'applicant_id'=>$applicant_id,
                    'sale_id'=>$sale_id,
                    'revert_added_date' =>date("jS F Y"),
                    'revert_added_time' =>date("h:i A"),
                    'stage' => 'cv_hold',
                    'user_id' => $current_user_id,
                    'notes' => $details,
                ]);

                return Redirect::back()->with('success','Applicant is revert back in quality cv hold tab.');
            }

        } else {

            return Redirect::back()->with('error','Applicant can not be reverted');
        }
    }
	
	 public function qualityNotesHistory(Request  $request)
	 {
        try {
			$qualityNotes = RevertStage::with('user')
				->where('applicant_id',$request->applicant_id)
				->where('sale_id',$request->sale_id)
				->whereIn('stage',['quality_note','cv_hold','no_job_quality_cvs'])
				->get();
			
        	return response()->json(['success' => true, 'data' => $qualityNotes]);
        } catch (\Exception $e) {
        // Handle any exceptions that might occur
          return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
	
	public function revertHoldQualityCv(Request $request)
    {
        $auth_user = Auth::user();
        date_default_timezone_set('Europe/London');
        $audit_data['action'] = "Revert Quality > Rejected CV";
        $details = request('details');
        $applicant_id = request('applicant_hidden_id');
        $audit_data['sale'] = $sale_id = request('job_hidden_id');
        $cv_count = Cv_note::where(['cv_notes.sale_id' => $sale_id, 'cv_notes.status' => 'active'])->count();

        Quality_notes::where(['applicant_id' => $applicant_id, 'sale_id' => $sale_id, 'moved_tab_to' => 'rejected'])->delete();
        //Cv_note::where(['cv_notes.sale_id' => $sale_id,'applicant_id' => $applicant_id])->update([
          // 'status'=>'disable'
        //]);
        $date_now = Carbon::now();
      

        /*** activity log
         * $action_observer = new ActionObserver();
         * $action_observer->action($audit_data, 'Quality');
         */
     $update_cv_note=true;
        if ($update_cv_note) {
            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id,
				"sub_stage"=>"quality_cvs_hold"
            ])->update(["status" => "disable"]);
            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $auth_user->id;
            $history->sale_id = $sale_id;
            $history->stage = 'quality';
            $history->sub_stage = 'quality_cvs';
            $history->history_added_date = date("jS F Y");
            $history->history_added_time = date("h:i A");
            $history->save();
            $last_inserted_history = $history->id;
            if ($last_inserted_history > 0) {
                $history_uid = md5($last_inserted_history);
                History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                //revert qualtiy
                $revertRecord=RevertStage::create([
                    'applicant_id'=>$applicant_id,
                    'sale_id'=>$sale_id,
                    'revert_added_date' =>date("jS F Y"),
                    'revert_added_time' =>date("h:i A"),
                    'stage'=>'quality_revert',
                    'user_id'=>$auth_user->id,
                    'notes'=>$details,
                ]);

                return redirect()->back()->with('qualityApplicantMsg', 'Applicant has been sent to quality');
            }

        } else {
            return redirect()->back()->with('qualityApplicantErr', 'Applicant Cant be Sent');
        }
    }
}
