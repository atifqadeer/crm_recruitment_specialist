<?php

namespace Horsefly\Http\Controllers\Administrator;
use Horsefly\Exports\ApplicantsExport;
use Horsefly\Exports\ApplicantsExportIncorrectPostcode;
use Horsefly\Exports\IdleApplicantExport;
use Horsefly\Exports\Export_blocked_applicants;
use Horsefly\Exports\Export_temp_not_interested_applicants;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Horsefly\ApplicantNote;
use Horsefly\Observers\ApplicantObserver;
//use Horsefly\Observers\ActionObserver;
use Horsefly\User;
use Illuminate\Http\Request;
use Horsefly\Http\Controllers\Controller;
use Horsefly\Exports\Applicants_nureses_7_days_export;
use Horsefly\Exports\NotUpdatedApplicantsExport;
use Horsefly\Applicant;
use Horsefly\Applicants_pivot_sales;
use Horsefly\Notes_for_range_applicants;
use Horsefly\Specialist_job_titles;
use Illuminate\Support\Facades\Mail;
use Horsefly\Mail\MailNotify;
use Horsefly\Mail\GenericEmail;
use Horsefly\Mail\RandomEmail;
use Horsefly\EmailTemplate;
use Horsefly\History;
use Horsefly\Cv_note;
use Horsefly\Crm_rejected_cv;
use Horsefly\Quality_notes;
use Horsefly\Crm_note;
use Horsefly\Sale;
use Horsefly\Office;
use Horsefly\Unit;
use Horsefly\ModuleNote;
use Horsefly\SentEmail;
//use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Redirect;
//use Validator;
use Session;
use Response;
use yajra\Datatables\Datatables;
use DateTime;
use Horsefly\Exports\ApplicantEmailExport;
use Horsefly\ApplicantUpdatedHistory;

class ApplicantController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:applicant_list|applicant_import|applicant_create|applicant_edit|applicant_view|applicant_history|applicant_note-create|applicant_note-history', ['only' => ['index','getApplicants']]);
        $this->middleware('permission:applicant_import', ['only' => ['getUploadApplicantCsv']]);
        $this->middleware('permission:applicant_create', ['only' => ['create','store']]);
        $this->middleware('permission:applicant_edit', ['only' => ['edit','update']]);
        $this->middleware('permission:applicant_view', ['only' => ['show']]);
        $this->middleware('permission:applicant_history', ['only' => ['getApplicantHistory','getApplicantFullHistory']]);
        $this->middleware('permission:resource_No-Nursing-Home_list|resource_No-Nursing-Home_revert-no-nursing-home', ['only' => ['getNurseHomeApplicants','getNurseHomeApplicantsAjax']]);
        $this->middleware('permission:resource_Non-Interested-Applicants', ['only' => ['getNonInterestedApplicants','getNonInterestAppAjax']]);
        $this->middleware('permission:resource_Potential-Callback_revert-callback|resource_No-Nursing-Home_revert-no-nursing-home', ['only' => ['revertApplicants']]);
		$this->middleware('permission:applicant_export', ['only' => ['export_csv','export','exportNurseHomeApplicants','exportNonInterestedLastApplicants','export_block_applicants']]);
		        $this->middleware('permission:applicant_no-job', ['only' => ['getNoJobApplicants','getNoJobApplicantsAjax']]);
		$this->middleware('permission:applicant_generic-email', ['only' => ['genericEmail','sendAppGenEmail']]);
		

    }
	
	
	public function runManual()
	{
		$applicants = Applicant::with(['cv_notes' => function($query) {
							$query->select('status', 'applicant_id', 'sale_id', 'user_id', 'updated_at')
								->with(['user:id,name'])
								->latest('cv_notes.updated_at')  // Get the latest cv_note by updated_at
								->limit(1); // Only need the latest cv_note
						}])
			->select('applicants.id', 'applicants.updated_at', 'applicants.is_no_job', 
					 'applicants.applicant_added_time', 'applicants.applicant_name', 
					 'applicants.applicant_job_title', 'applicants.job_title_prof', 
					 'applicants.job_category', 'applicants.applicant_postcode', 
					 'applicants.applicant_phone', 'applicants.applicant_homePhone', 
					 'applicants.applicant_source', 'applicants.applicant_email', 
					 'applicants.applicant_notes', 'applicants.paid_status', 
					 'applicants.applicant_cv', 'applicants.updated_cv', 
					 'applicants.lat', 'applicants.lng', 
					 'applicants.is_job_within_radius')
			->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
			->where("applicants.status", "=", "active")
			->where("applicants.is_job_within_radius", "1")
			->where("applicants.is_blocked", "=", "0")
			->where("applicants.temp_not_interested", "=", "0")
			->whereNull('applicants_pivot_sales.applicant_id')
			->whereDate('applicants.created_at', '<>', '2025-01-28')
			->where('applicants.is_follow_up', '<>', 2)  // More explicit inequality check
			->whereDoesntHave('cv_notes', function ($query) {
				$query->where('status', 'active');
			});

		$result = $applicants->take(10)->get();
		$data = [];

		foreach ($result as $row) {
			// Get the latest cv_note for each applicant
			$cv_note = Cv_note::where('applicant_id',$row->id)->orderBy('updated_at','desc')->first();
			if ($cv_note) {
				// Update applicant's updated_at to the latest cv_note's updated_at
				$row->updated_at = $cv_note->updated_at;
				$row->save(); // Save the updated applicant's updated_at field
				$data = $row;  // Add the latest cv_note to the data
			}
		}

		return $data;
	}



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
		return view('administrator.applicant.index');
    }
	
	    public function temporaryDataIndex()
    {
        return view('administrator.applicant.temporary_data');
    }

    public function importTemporaryData(Request $request)
    {
        date_default_timezone_set('Europe/London');
        if ($request->file('applicant_csv') != null) {

            $file = $request->file('applicant_csv');

            // File Details
            $filename = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $tempPath = $file->getRealPath();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

            // Valid File Extensions
            $valid_extension = array("csv");

            // 2MB in Bytes
            $maxFileSize = 1073741824; // 1 GB in bytes

            // Check file extension
            if (in_array(strtolower($extension), $valid_extension)) {

                // Check file size
                if ($fileSize <= $maxFileSize) {

                    // File upload location
                    $location = 'uploads';

                    // Upload file
                    $file->move($location, $filename);

                    // Import CSV to Database
                    $filepath = public_path($location . "/" . $filename);

                    // Reading file
                    $file = fopen($filepath, "r");

                    $importData_arr = array();
                    $i = 0;

                    while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
                        $num = count($filedata);

                        // Skip first row (Remove below comment if you want to skip the first row)
                        if ($i == 0) {
                            $i++;
                            continue;
                        }
                        for ($c = 0; $c < $num; $c++) {
                            $importData_arr[$i][] = $filedata[$c];
                        }
                        $i++;
                    }
                    fclose($file);
                    DB::table('temporary_data')->truncate();
                    foreach ($importData_arr as $importData) {
                        $postcode = $importData[3];

                        DB::table('temporary_data')->insert([
                            'applicant_job_title' => $importData[0],
                            'applicant_name' => $importData[1],
                            'applicant_email' => $importData[2],
                            'applicant_postcode' => $postcode,
                            'applicant_phone' => $importData[4],
                            'applicant_homePhone' => $importData[5],
                            'job_category' => $importData[6],
                            'applicant_source' => $importData[7],
                            'applicant_notes' => $importData[8]
                        ]);
                    }
                    Session::flash('message', 'Import Successful.');
                } else {
                    Session::flash('message', 'File too large. File must be less than 2MB.');
                }
            } else {
                Session::flash('message', 'Invalid File Extension.');
            }
        }
        return redirect('temporary-data')->with('applicant_success_msg', 'Applicant Added Successfully');
    }

    public function getTemporaryData()
    {
        // Initialize the raw columns array
        $raw_columns = ['applicant_notes']; // Add 'applicant_notes' to raw columns

        // Fetch temporary data
        $temporaryData = DB::table('temporary_data')->orderBy('id', 'asc')->get();

        // Process data for DataTables
        $datatable = datatables()->of($temporaryData)
            ->addColumn('applicant_notes', function ($applicants) {
                // Determine the final notes to display
                $app_notes_final = $applicants->applicant_notes;

                // Generate HTML content for applicant notes with a unique ID
                $content = '<a href="#" class="edit-notes-btn" data-target="#updateNotesModal" 
                             data-applicant-id="' . $applicants->id . '" 
                             id="applicant-notes-' . $applicants->id . '">
                                 ' . $app_notes_final . '
                             </a>';

                return $content;
            });

        // Set raw columns and make the DataTable response
        return $datatable->rawColumns($raw_columns)->make(true);
    }

    public function update_temporary_data_notes(Request $request)
    {
        // Set the timezone
        date_default_timezone_set('Europe/London');


        // Get the authenticated user's name
        $userName = auth()->user()->name;

        // Prepare the updated notes
        if (auth()->user()->id == '101' || auth()->user()->id == '1') {
            $applicant_notes = $request->input('details');
        } else {
            $applicant_notes = $request->input('details') . ' ---- By: ' . $userName;
        }

        // Update the notes in the database
        DB::table('temporary_data')
            ->where('id', $request->input('applicant_id'))
            ->update(['applicant_notes' => $applicant_notes]);

        // Return a JSON response
        return response()->json([
            'success' => true,
            'message' => 'Notes updated successfully',
            'updated_notes' => $applicant_notes, // Return the updated notes for frontend use
            'applicant_id' => $request->input('applicant_id'),
        ]);
    }
	
	public function exportApplicantsWithNoLatLng(Request $request) 
    {
        return Excel::download(new ApplicantsExportIncorrectPostcode(), 'applicants_incorrect_postcodes.csv');
    }
	
	public function export_csv()
    {
        $users = User::where(["is_admin" => 0])->get();

        return view('administrator.applicant.export_csv',compact('users'));
    }
	
	public function idelApplicantExport()
    {
        $users = User::where(["is_admin" => 0])->get();
        return view('administrator.applicant.export_idle_csv',compact('users'));
    }
	
	public function idelSpecialistApplicantExport()
    {
        $users = User::where(["is_admin" => 0])->get();
        return view('administrator.applicant.export_idle_specialist_csv',compact('users'));
    }

    public function export(Request $request) 
    {
        $job_category =  $request->user_selected;
        if($job_category==44)
        {
            $job_category='nurse';
        }
        elseif($job_category==45)
        {
            $job_category= 'non-nurse';

        }elseif ($job_category==47){
            $job_category= 'chef';

        }
        
		$start_date = $request->input('start_date');
        $start_date = Carbon::parse($start_date)->format('Y-m-d'). " 00:00:00.000";
        $end_date = $request->input('end_date');
        $end_date = Carbon::parse($end_date)->format('Y-m-d'). " 23:59:59:999";

        return Excel::download(new ApplicantsExport($start_date,$end_date,$job_category), 'applicants.csv');
        
    }

    public function getApplicants()
    {
        $auth_user = Auth::user();
        $raw_columns = ['applicant_notes','created_at'];
        $datatable = datatables()->of(Applicant::orderBy('created_at', 'DESC'))
                 ->addColumn('applicant_notes', function($applicants){
                    $app_new_note = ModuleNote::where([
						'module_noteable_id' =>$applicants->id, 
						'module_noteable_type' =>'Horsefly\Applicant'
					])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
					 
                    $app_notes_final='';
                    if($app_new_note){
                        $app_notes_final = $app_new_note->details;
                    }
                    else{
                        $app_notes_final = $applicants->applicant_notes;
                    }

                $status_value = 'open';
                $postcode = '';
                if ($applicants->paid_status == 'close') {
                    $status_value = 'paid';
                } else {
                    foreach ($applicants->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $status_value = 'sent';
                            break;
                        } elseif ($value->status == 'disable') {
                            $status_value = 'reject';
                        }
                    }
                }
                   
                if($applicants->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject')
                {
                    
                $content = '';

                $content .= '<a href="#" class="reject_history" data-applicant="'.$applicants->id.'"
                                 data-controls-modal="#clear_cv'.$applicants->id.'"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicants->id . '">"'.$app_notes_final.'"</a>';
                $content .= '<div id="clear_cv' . $applicants->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Notes</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<form action="' . route('block_or_casual_notes') . '" method="POST" id="app_notes_form' . $applicants->id . '" class="form-horizontal">';
                $content .= csrf_field();
                $content .= '<div class="modal-body">';
                $content .='<div id="app_notes_alert' . $applicants->id . '"></div>';
                $content .= '<div id="sent_cv_alert' . $applicants->id . '"></div>';
                $content .= '<div class="form-group row">';
                $content .= '<label class="col-form-label col-sm-3">Details</label>';
                $content .= '<div class="col-sm-9">';
                $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicants->id . '">';
                $content .= '<input type="hidden" name="applicant_page' . $applicants->id . '" value="applicants">';
                $content .= '<textarea name="details" id="sent_cv_details' . $applicants->id .'" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                $content .= '</div>';
                $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicants->id .'">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Blocked Notes</option>';
					$content .= '<option value="3">Temporary Not Interested Notes</option>';
					//$content .= '<option value="5">No Job</option>';
                    $content .= '</select>';
                    $content .= '</div>';
                    $content .= '</div>';

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                   
                    $content .= '<button type="button" class="btn bg-dark legitRipple sent_cv_submit" data-dismiss="modal">Close</button>';
                    
                    $content .= '<button type="submit" data-note_key="' . $applicants->id . '" value="cv_sent_save" class="btn bg-teal legitRipple sent_cv_submit app_notes_form_submit">Save</button>';

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

		});
        if ($auth_user->hasAnyPermission(['applicant_edit','applicant_view','applicant_history','applicant_note-create','applicant_note-history'])) {
            $datatable = $datatable->addColumn('action', function ($applicants) use ($auth_user) {
                $action =
                    '<div class="list-icons">
                                <div class="dropdown">
                                <a href="#" class=list-icons-item" data-toggle="dropdown">
                                    <i class="icon-menu9"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasPermissionTo('applicant_edit')) {
                    $action .= '<a href="' . route('applicants.edit', $applicants->id) . '" class="dropdown-item"> Edit</a>';
                }
				     if ($auth_user->is_admin==1) {
                    $action .= '<a href="' . url('get_edited_by_history_applicant', $applicants->id) . '" class="dropdown-item"> EditedBY</a>';
                }
                if ($auth_user->hasPermissionTo('applicant_view')) {
                    $action .= '<a href="' . route('applicants.show', $applicants->id) . '" class="dropdown-item"> View </a>';
                }
                if ($auth_user->hasPermissionTo('applicant_history')) {
                    $action .= '<a href="' . route('applicantHistory', $applicants->id) . '" class="dropdown-item"> History</a>';
                }
                if ($auth_user->hasPermissionTo('applicant_note-create')) {
                    $action .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#add_applicant_note' . $applicants->id . '"
                                           data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#add_applicant_note' . $applicants->id . '">
                                           Add Note</a>';
                }
                if ($auth_user->hasPermissionTo('applicant_note-history')) {
                    $action .= '<a href="#" class="dropdown-item notes_history" data-applicant="' . $applicants->id . '" data-controls-modal="#notes_history' . $applicants->id . '"
                                           data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#notes_history' . $applicants->id . '"
                                        > Notes History </a>';
                }
				 if (Auth::id()== '66' || Auth::id()=="101") {
                    $action .= '<a class="dropdown-item" href="/available-no-jobs/' . $applicants->id . '">No job</a>';
                }
                $action .=
                            '</div>
                        </div>
                    </div>';
                if ($auth_user->hasPermissionTo('applicant_note-create')) {
                        $action .=
                            '<div id="add_applicant_note' . $applicants->id . '" class="modal fade" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Add Applicant Note</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                      <form action="' . route('module_note.store') . '" method="POST" class="form-horizontal" id="note_form' . $applicants->id . '">
                                        <input type="hidden" name="_token" value="' . csrf_token() . '">
                                            <input type="hidden" name="module" value="Applicant">
                                            <div class="modal-body">
                                                <div id="note_alert'. $applicants->id .'"></div>
                                                
                                                <div class="form-group row">
                                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">1.</strong> Current Employer Name</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" name="current_employer_name" class="form-control" placeholder="Enter Employer Name" >
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group row">
                                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">2.</strong> PostCode</label>
                                                    <div class="col-sm-3">
                                                        <input type="text" name="postcode" class="form-control" placeholder="Enter PostCode" >
                                                    </div>
                                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">3.</strong> Current/Expected Salary</label>
                                                    <div class="col-sm-3">
                                                        <input type="text" name="expected_salary" class="form-control" placeholder="Enter Salary" >
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group row">
                                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">4.</strong> Qualification</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" name="qualification" class="form-control" placeholder="Enter Qualification">
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group row">
                                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">5.</strong> Transport Type</label>
                                                    <div class="col-sm-9 d-flex align-items-center">
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="by_walk" value="By Walk">
                                                            <label class="form-check-label" for="by_walk">By Walk</label>
                                                        </div>
														 <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="cycle" value="Cycle">
                                                            <label class="form-check-label" for="cycle">Cycle</label>
                                                        </div>
                                                        <div class="form-check form-check-inline ml-3">
                                                            <input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="car" value="Car">
                                                            <label class="form-check-label" for="car">Car</label>
                                                        </div>
                                                        <div class="form-check form-check-inline ml-3">
                                                            <input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="public_transport" value="Public Transport">
                                                            <label class="form-check-label" for="public_transport">Public Transport</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group row">
                                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">6.</strong> Shift Pattern</label>
                                                    <div class="col-sm-9 d-flex align-items-center">
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="day" value="Day">
                                                            <label class="form-check-label" for="day">Day</label>
                                                        </div>
														<div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="night" value="Night">
                                                            <label class="form-check-label" for="night">Night</label>
                                                        </div>
                                                        <div class="form-check form-check-inline ml-3">
                                                            <input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="full_time" value="Full Time">
                                                            <label class="form-check-label" for="full_time">Full Time</label>
                                                        </div>
                                                        <div class="form-check form-check-inline ml-3">
                                                            <input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="part_time" value="Part Time">
                                                            <label class="form-check-label" for="part_time">Part Time</label>
                                                        </div>
                                                        <div class="form-check form-check-inline ml-3">
                                                            <input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="twenty_four_hours" value="24 hours">
                                                            <label class="form-check-label" for="twenty_four_hours">24 Hours</label>
                                                        </div>
														 <div class="form-check form-check-inline">
                                                            <input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="day_night" value="Day/Night">
                                                            <label class="form-check-label" for="day_night">Day/Night</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group row">
                                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">7.</strong> Nursing Home</label>
                                                    <div class="col-sm-3 d-flex align-items-center">
                                                        <div class="form-check mt-0">
                                                            <input type="checkbox" name="nursing_home" style="margin-top:-3px" id="nursing_home_checkbox" class="form-check-input" value="0">
                                                        </div>
                                                    </div>
                                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">8.</strong> Alternate Weekend</label>
                                                    <div class="col-sm-3 d-flex align-items-center">
                                                        <div class="form-check mt-0">
                                                            <input type="checkbox" name="alternate_weekend" style="margin-top:-3px" id="alternate_weekend_checkbox" class="form-check-input" value="0">
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                               <div class="form-group row">
                                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">9.</strong> Interview Availability</label>
                                                    <div class="col-sm-3 d-flex align-items-center">
                                                        <div class="form-check mt-0">
                                                            <input type="checkbox" name="interview_availability" style="margin-top:-3px" id="interview_availability_checkbox" class="form-check-input" value="0">
                                                        </div>
                                                    </div>
                                                   
                                                </div>
                                               <div class="form-group row">
                                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">10.</strong> Visa Status</label>
                                                    <div class="col-sm-9">
                                                        <div class="form-check form-check-inline">
                                                            <input type="radio" name="visa_status" id="british" class="form-check-input mt-0" value="British">
                                                            <label class="form-check-label" for="british">British</label>
                                                        </div><br>
                                                        <div class="form-check form-check-inline">
                                                            <input type="radio" name="visa_status" id="required_sponsorship" class="form-check-input mt-0" value="Required Sponsorship">
                                                            <label class="form-check-label" for="required_sponsorship">Required Sponsorship</label>
                                                        </div>
                                                    </div>
                                                </div>
												<div class="form-group row">
                                                    <div class="col-sm-1 d-flex justify-content-center align-items-center">
                                                        <input type="checkbox" name="no_job" id="no_job_checkbox" style="margin-top:-3px" class="form-check-input" value="0">
                                                    </div>
                                                    <div class="col-sm-11">
                                                        <label for="no_job" class="col-form-label" style="font-size:16px; cursor: pointer;">
                                                            No Job
                                                        </label>
                                                    </div>
                                                </div>
                                               <div class="form-group row">
                                                    <div class="col-sm-1 d-flex justify-content-center align-items-center">
                                                        <input type="checkbox" name="hangup_call" id="hangup_call" class="form-check-input" value="0">
                                                    </div>
                                                    <div class="col-sm-11">
                                                        <label for="hangup_call" class="col-form-label" style="font-size:16px;">
                                                            Call Hung up/Not Interested
                                                        </label>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="request_from_applicants" value="1">
                                                <div class="form-group row">
                                                    <label class="col-form-label col-sm-3">Other Details <span class="text-danger">*</span></label>
                                                    <div class="col-sm-9">
                                                        <input type="hidden" name="module_key" value="'.$applicants->id.'">
                                                        <textarea name="details" id="note_details'. $applicants->id .'" class="form-control" cols="30" rows="4" placeholder="TYPE HERE .." required></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">Close</button>
                                                <button type="submit" data-note_key="'. $applicants->id .'" class="btn bg-teal legitRipple note_form_submit">Save</button>
                                            </div>
                                        </form>

                                        
                                    </div>
                                </div>
                            </div>';
                    }
                if ($auth_user->hasPermissionTo('applicant_note-history')) {
                    $action .= '<div id="notes_history' . $applicants->id . '" class="modal fade" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Applicant Notes History - 
                                        <span class="font-weight-semibold">' . $applicants->applicant_name . '</span></h5>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body" id="applicants_notes_history' . $applicants->id . '" style="max-height: 500px; overflow-y: auto;">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                        </button>
                                    </div>
                                    
                                </div>
                            </div>
                          </div>';
                }
                return $action;
            });
            $raw_columns = ['applicant_notes','created_at','action'];
        }
          $datatable = $datatable->addColumn('download', function ($applicants) {
            $filePath = $applicants->applicant_cv;

            // Check if the file exists
            $disabled = (!file_exists($filePath) || $applicants->applicant_cv == null || $applicants->applicant_cv == 'old_image') ? 'disabled' : '';
            $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicants->id);

            $disabled_color = (!file_exists($filePath) || $applicants->applicant_cv == null || $applicants->applicant_cv == 'old_image') ? 'text-grey-400' : 'text-teal-400';
            return '<a class="download-link ' . $disabled . '" href="' . $href . '">
               <i class="fas fa-file-download '. $disabled_color .'"></i>
            </a>';
        });
        array_push($raw_columns, 'download');
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
			
		$datatable = $datatable->editColumn('applicant_job_title', function ($applicants) {                
			if($applicants->applicant_job_title == 'nurse specialist' || $applicants->applicant_job_title == 'nonnurse specialist')
			{
				$selected_prof_data = Specialist_job_titles::select("specialist_prof")->where("id", $applicants->job_title_prof)->first();
				$spec_job_title = ($applicants->job_title_prof!='')?$selected_prof_data->specialist_prof:$applicants->applicant_job_title;
				return strtoupper($spec_job_title);
			}
			else
			{
				return strtoupper($applicants->applicant_job_title);
			}
         });
		array_push($raw_columns, 'applicant_job_title');
        $datatable = $datatable->addColumn("created_date",function($applicants){
                    $created_at = new DateTime($applicants->created_at);
                    return DATE_FORMAT($created_at, "d M Y");
			})
			 ->addColumn("applicant_experience",function($applicants){
				 $applicant_experience = $applicants->applicant_experience;
				 return $applicant_experience ? $applicant_experience : '-';
			 })
			->editColumn("department",function($applicants){
				$applicant_department = $applicants->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicants){
				$sub_department = $applicants->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
			->addColumn("created_time",function($applicants){
				$created_at = new DateTime($applicants->created_at);
				return DATE_FORMAT($created_at, "h:i A");
			})
			->rawColumns($raw_columns)
			->make(true);
        return $datatable;
    }
	
	public function exportIdelApplicants(Request $request)
    {
        $job_category =  $request->user_selected;
        $radius =  $request->radius;
        if($job_category==44)
        {
            $job_category='nurse';
        }
        else if ($job_category==45)
        {
            $job_category='non-nurse';

        }
        else if ($job_category==46)
        {
            $job_category='specialist';
        }
        $start_date = $request->input('start_date');
        $start_date = Carbon::parse($start_date)->format('Y-m-d'). " 00:00:00:000";
        $end_date = $request->input('end_date');
        $end_date = Carbon::parse($end_date)->format('Y-m-d'). " 23:59:59:999";
        return Excel::download(new IdleApplicantExport($start_date,$end_date,$job_category,$radius), 'IdleApplicants.csv');
    }

    public function getuserslist(){

        $applicants = Applicant::select("applicant_added_date","applicant_added_time","applicant_name",
            "applicant_job_title","job_category","applicant_postcode",
            "applicant_phone","applicant_homePhone","applicant_notes","status");
        return datatables($applicants)->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
		$applicant_source = array(
            'Total Jobs' => 'Total Jobs',
            'Reed' => 'Reed',
            'Niche' => 'Niche',
            'CV Library' => 'CV Library',
			'Social Media' => 'Social Media',
            'Referral' => 'Referral',
           	'Other Source' => 'Other Source',
			'Monster' => 'Monster',
            'Jobmedic' => 'Jobmedic',
			'Totally Legal' => 'Totally Legal'
		);

        return view('administrator.applicant.create', compact('applicant_source'));
    }
	
	public function getIdleApplicants()
    {
        $auth_user = Auth::user();
		
		$resultapp=Applicant::select('created_at', 'applicant_added_time', 'applicant_name', 'applicant_job_title', 'job_category', 'applicant_postcode', 'applicant_phone', 'applicant_homePhone', 'applicant_source', 'applicant_notes')
		->where(["status"=>"active","is_blocked"=>0,"job_category"=>"nurse"])
        ->where(function($query){
         $query->doesnthave("CRMNote.History")
        ->orWhereHas("CRMNote.History",function($query){
                $query->whereIn("sub_stage", ["crm_reject", "crm_request_reject","crm_interview_not_attended", "crm_declined","crm_start_date_hold", "crm_dispute"])
                ->where("status","active");
        });
        })->get();

        
        $raw_columns = [];
        $datatable = datatables()->of($resultapp);
           
            $datatable = $datatable->editColumn('applicant_job_title', function ($applicants) {
                
                if($applicants->applicant_job_title == 'nurse specialist' || $applicants->applicant_job_title == 'nonnurse specialist')
                {
                    $selected_prof_data = Specialist_job_titles::select("specialist_prof")->where("id", $applicants->job_title_prof)->first();
                    $spec_job_title = ($applicants->job_title_prof!='')?$applicants->applicant_job_title.' ('.$selected_prof_data->specialist_prof.')':$applicants->applicant_job_title;
                    return $spec_job_title;
                }
                else
                {
                    return $applicants->applicant_job_title;
                }
    
         });
            array_push($raw_columns, 'applicant_job_title');

        
        $datatable = $datatable->addColumn("created_at",function($applicants){
                    $created_at = new DateTime($applicants->created_at);
                    return DATE_FORMAT($created_at, "d M Y");
                })->rawColumns($raw_columns)
                ->make(true);
		array_push($raw_columns, 'created_at');

        return $datatable;
    }
	public function getIdleApplicantsNonNurse()
    {
        $auth_user = Auth::user();


        $resultappnon= Applicant::where(["status"=>"active","is_blocked"=>0,"job_category"=>"non-nurse"])
		->whereNotIn('applicant_job_title', ['nonnurse specialist'])
        ->where(function($query){
         $query->doesnthave("CRMNote.History")
        ->orWhereHas("CRMNote.History",function($query){
                $query->whereIn("sub_stage", ["crm_reject", "crm_request_reject","crm_interview_not_attended", "crm_declined","crm_start_date_hold", "crm_dispute"])
                ->where("status","active");
        });
        })
        ->get();
            



        $raw_columns = [];
        $datatable = datatables()->of($resultappnon);
           
            $datatable = $datatable->editColumn('applicant_job_title', function ($applicants) {
                
                if($applicants->applicant_job_title == 'nurse specialist' || $applicants->applicant_job_title == 'nonnurse specialist')
                {
                    $selected_prof_data = Specialist_job_titles::select("specialist_prof")->where("id", $applicants->job_title_prof)->first();
                    $spec_job_title = ($applicants->job_title_prof!='')?$applicants->applicant_job_title.' ('.$selected_prof_data->specialist_prof.')':$applicants->applicant_job_title;
                    return $spec_job_title;
                }
                else
                {
                    return $applicants->applicant_job_title;
                }
    
         });
            array_push($raw_columns, 'applicant_job_title');

        
        $datatable = $datatable->addColumn("created_at",function($applicants){
                    $created_at = new DateTime($applicants->created_at);
                    return DATE_FORMAT($created_at, "d M Y");
                })->rawColumns($raw_columns)
                ->make(true);
		array_push($raw_columns, 'created_at');

        return $datatable;
    }
	public function getIdleApplicantsNonNurseSpecialist()
    {
        $auth_user = Auth::user();


        $resultappnon= Applicant::where(["status"=>"active","is_blocked"=>0,"job_category"=>"non-nurse", "applicant_job_title" => "nonnurse specialist"])
        ->where(function($query){
         $query->doesnthave("CRMNote.History")
        ->orWhereHas("CRMNote.History",function($query){
                $query->whereIn("sub_stage", ["crm_reject", "crm_request_reject","crm_interview_not_attended", "crm_declined","crm_start_date_hold", "crm_dispute"])
                ->where("status","active");
        });
        })
        ->get();
            



        $raw_columns = ['applicant_notes','created_at'];
        $datatable = datatables()->of($resultappnon);
            $datatable = $datatable->addColumn('download', function ($applicants) {
                return
                    '<a href="' . route('downloadApplicantCv', $applicants->id) . '">
                       <i class="fas fa-file-download text-teal-400" style="font-size: 30px;"></i>
                    </a>';
            });
            array_push($raw_columns, 'download');
            $datatable = $datatable->addColumn('updated_cv', function ($applicants) {
                return
                    '<a href="' . route('downloadUpdatedApplicantCv', $applicants->id) . '">
                       <i class="fas fa-file-download text-teal-400" style="font-size: 30px;"></i>
                    </a>';
            });
            array_push($raw_columns, 'updated_cv');

            $datatable = $datatable->addColumn('upload', function ($applicants) {
                return
                '<a href="#"
                data-controls-modal="#import_applicant_cv" class="import_cv"
                data-backdrop="static"
                data-keyboard="false" data-toggle="modal" data-id="'.$applicants->id.'"
                data-target="#import_applicant_cv">
                 <i class="fas fa-file-upload text-teal-400" style="font-size: 30px;"></i>
                 &nbsp;</a>';
            });
            array_push($raw_columns, 'upload');
            $datatable = $datatable->editColumn('applicant_job_title', function ($applicants) {
                
                if($applicants->applicant_job_title == 'nurse specialist' || $applicants->applicant_job_title == 'nonnurse specialist')
                {
                    $selected_prof_data = Specialist_job_titles::select("specialist_prof")->where("id", $applicants->job_title_prof)->first();
                    $spec_job_title = ($applicants->job_title_prof!='')?$applicants->applicant_job_title.' ('.$selected_prof_data->specialist_prof.')':$applicants->applicant_job_title;
                    return $spec_job_title;
                }
                else
                {
                    return $applicants->applicant_job_title;
                }
    
         });
            array_push($raw_columns, 'applicant_job_title');

        
        $datatable = $datatable->addColumn("created_at",function($applicants){
                    $created_at = new DateTime($applicants->created_at);
                    return DATE_FORMAT($created_at, "d M Y");
                })->rawColumns($raw_columns)
                ->make(true);
        return $datatable;
    }
	
	public function store_block_or_casual_notes(Request $request)
    {
	   	date_default_timezone_set('Europe/London');
		
        $applicant_id = $request->Input('applicant_hidden_id');
        $raw_notes = $request->Input('details');
		$date = $request->Input('date');
        $notes_reason = $request->Input('reject_reason');
        $applicant_page = $request->Input('applicant_page'.$applicant_id);
		$applicant_notes = $raw_notes .' ---- By: '. auth()->user()->name.' Date: '. Carbon::now()->format('d-m-Y');
        
		if($notes_reason =='2')//block applicants
        {
           Applicant::where('id', $applicant_id)
                ->update([
                    'no_response' => '0', 
                    'is_blocked' => '1', 
                    'temp_not_interested' => '0', 
                    'applicant_notes' => $applicant_notes, 
                    'updated_at' => Carbon::now()
                ]);
						
			 if($applicant_page == 'follow_up'){
                Applicant::where('id', $applicant_id)
                ->update([
                    'is_follow_up' => '1',
                ]);
            }

        }
        else if($notes_reason =='1')//casual notes
        {
           Applicant::where('id', $applicant_id)
                ->update([
                    'no_response' => '0', 
                    'temp_not_interested' => '0', 
                    'is_blocked' => '0', 
                    'applicant_notes' => $applicant_notes, 
                    'updated_at' => Carbon::now()
                ]);
					
			 if($applicant_page == 'follow_up'){
                Applicant::where('id', $applicant_id)
                ->update([
                    'is_follow_up' => '1',
                ]);
            }
        }
        else if($notes_reason=='3')//not interested applicants
        {
            Applicant::where('id', $applicant_id)
                ->update([
                    'no_response' => '0', 
                    'temp_not_interested' => '1', 
                    'is_blocked' => '0', 
                    'applicant_notes' => $applicant_notes, 
                    'updated_at' => Carbon::now()
                ]);
			
			 if($applicant_page == 'follow_up'){
                Applicant::where('id', $applicant_id)
                ->update([
                    'is_follow_up' => '1',
                ]);
            }
        }
        else if($notes_reason=='4')//no responed
        {
            Applicant::where('id', $applicant_id)
                ->update([
                    'is_circuit_busy' => '0', 
                    'no_response' => '1', 
                    'temp_not_interested' => '0', 
                    'is_blocked' => '0', 
                    'applicant_notes' => $applicant_notes, 
                    'updated_at' => Carbon::now()
                ]);
			
			 if($applicant_page == 'follow_up'){
                Applicant::where('id', $applicant_id)
                ->update([
                    'is_follow_up' => '2',
                ]);
            }
        }
        else if($notes_reason=='5')//no job applicants
        {
            Applicant::where('id', $applicant_id)
                ->update([
                    'no_response' => '0', 
                    'temp_not_interested' => '0', 
                    'is_blocked' => '0', 
                    'is_no_job' => '1', 
                    'applicant_notes' => $applicant_notes, 
                    'updated_at' => Carbon::now()
                ]);
            
			if ($applicant_page == 'follow_up') {
                Applicant::where('id', $applicant_id)
                    ->update([
                        'is_follow_up' => '2',
                    ]);
            }
        }
		 else if($notes_reason=='6')//callback
        {
            Applicant::where('id', $applicant_id)
                ->update([
                    'is_callback_enable' => 'yes',
                    'no_response' => '0',
                    'is_circuit_busy' => '0',
                    'temp_not_interested' => '0',
                    'is_blocked' => '0',
                    'is_no_job' => '0',
                    'applicant_notes' => $applicant_notes,
                    'updated_at' => Carbon::now()
                ]);
			 
			 $applicantNote = ApplicantNote::create([
				 'details' => $applicant_notes,
				 'applicant_id' =>$applicant_id,
				 'moved_tab_to' =>'callback',
				 'added_date'=> Carbon::now()->format('jS F Y'),
				 'added_time'=> Carbon::now()->format('h:i A'),
				 'user_id'=> Auth::id(),
				 'status'=> 'active'
			 ]);
			 
			 $applicantNote_uid = md5($applicantNote->id);
			 DB::table('applicant_notes')
                ->where('id', $applicantNote->id)
                ->update(['note_uid' => $applicantNote_uid]);
			 
			 if($applicant_page == 'follow_up'){
				 Applicant::where('id', $applicant_id)
					 ->update([
						 'is_follow_up' => '2',
					 ]);
			 }
        }
        else if($notes_reason=='7')//circuit busy
        {
            Applicant::where('id', $applicant_id)
                ->update([
                    'no_response' => '0',
                    'is_circuit_busy' => '1',
                    'is_callback_enable' => 'no',
                    'temp_not_interested' => '0',
                    'is_blocked' => '0',
                    'is_no_job' => '0',
                    'applicant_notes' => $applicant_notes,
                    'updated_at' => Carbon::now()
                ]);
            
            if($applicant_page == 'follow_up'){
                Applicant::where('id', $applicant_id)
                ->update([
                    'is_follow_up' => '2',
                ]);
            }
        }
		
		ModuleNote::where(['module_noteable_id' => $applicant_id, 
            'module_noteable_type' => 'Horsefly\Applicant'])
            ->orderBy('id', 'desc')
            ->take(1)
            ->update(['status' => 'disable']);

        $molduleNote = ModuleNote::create([
            'details' => $applicant_notes,
            'module_noteable_id' => $applicant_id,
            'module_noteable_type' => 'Horsefly\Applicant',
            'module_note_added_date' => Carbon::now()->format('jS F Y'),
            'module_note_added_time' => Carbon::now()->format('h:i A'),
            'user_id' => Auth::id(),
            'status' => 'active'
        ]);

        $molduleNote_uid = md5($molduleNote->id);
        DB::table('module_notes')
            ->where('id', $molduleNote->id)
            ->update(['module_note_uid' => $molduleNote_uid]);
		
		if($applicant_page == 'follow_up')
		{
			// If the request is an AJAX request, return a JSON response
			return response()->json([
				'success' => true,
				'message' => 'Action completed successfully',
			]);
		}
		
        if($applicant_page == 'applicants')
        {
            return redirect('applicants');
        }
        else if($applicant_page == '2_months_applicants')
        {
            //return redirect()->route('last2months');
			return redirect()->back();
        }
        else if($applicant_page == '7_days_applicants')
        {
            //return redirect()->route('last7days');
			 return redirect()->back();
            
        }
        else if($applicant_page == '21_days_applicants')
        {
            //return redirect()->route('last21days');
			 return redirect()->back();

        }
        else if($applicant_page == '15_km_applicants_nurses')
        {
			$sale_id=$request->Input('applicant_sale_id');
            return redirect('applicants-within-15-km/'.$sale_id);
        }
       
    }
	
	public function store_no_job_to_applicant(Request $request)
    {
        $applicant_id = $request->Input('applicant_hidden_id');
        $raw_notes = $request->Input('details');
        $notes_reason = $request->Input('reject_reason');
        $applicant_page = $request->Input('applicant_page'.$applicant_id);
		$applicant_notes = $raw_notes .' --- By: '. auth()->user()->name.' Date: '. Carbon::now()->format('d-m-Y');
        
		Applicant::where('id', $applicant_id)
            ->update(['is_no_job' => '0','applicant_notes' => $applicant_notes, 'updated_at'=>Carbon::now()]);
		
		ModuleNote::where(['module_noteable_id' =>$applicant_id, 'module_noteable_type' =>'Horsefly\Applicant'])
            ->orderBy('id','desc')
            ->take(1)
            ->update(['details' => $applicant_notes, 'updated_at'=>Carbon::now()]);

        if($applicant_page == 'applicants')
        {
            return redirect('applicants');
        }
        else if($applicant_page == '2_months_applicants')
        {

            // $interval = 60;
            // return view('administrator.resource.last_2_months_applicant_added', compact('interval'));
            // return redirect('last2months');
            //return redirect()->route('last2months');
			 return redirect()->back();
        }
        else if($applicant_page == '7_days_applicants')
        {
            //return redirect()->route('last7days');
			 return redirect()->back();
            
        }
        else if($applicant_page == '21_days_applicants')
        {
            //return redirect()->route('last21days');
			 return redirect()->back();

        }
        else if($applicant_page == '15_km_applicants_nurses')
        {
			$sale_id=$request->Input('applicant_sale_id');
            return redirect('applicants-within-15-km/'.$sale_id);
            // return redirect()->route('applicants-within-15-km/'.$sale_id);    
        }
       
    }
	
	public function sendApplicantEmail(Request $request)
    {
        // Validate request data
        $validatedData = $request->validate([
            'emailAddress' => 'required',
            'emailSubject' => 'required|max:255',
            'emailBody' => 'required',
            'applicantId' => 'required',
            'saleId' => 'required|integer',
            'title' => 'required|string'
        ]);
    
        $applicantId = $validatedData['applicantId'];
        $saleId = $validatedData['saleId'];
        $emailAddress = $validatedData['emailAddress'];
        $emailSubject = $validatedData['emailSubject'];
        $emailBody = $validatedData['emailBody'];
        $title = $validatedData['title'];
        $email_from = 'crm@kingsburypersonnel.com';
    
        try {    
            // Send email
            Mail::send([], [], function ($message) use ($emailBody, $email_from, $emailSubject, $emailAddress) {
                $message->from($email_from, 'Kingsbury Personnel Ltd');
                $message->to($emailAddress);
                $message->subject($emailSubject);
                $message->setBody($emailBody, 'text/html');
            });
    
            // Save email details to the database
            $email_sent_to_cc = '';
            $this->saveSentEmails($emailAddress, $email_sent_to_cc, $email_from, $emailSubject, $emailBody, $title, $applicantId, $saleId);
    
            return response()->json(['success' => 'Email sent successfully']);
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
	
	public function saveSentEmails($email_to, $email_cc, $email_from, $email_title, $email_body, $action_name, $applicant_id = Null, $sale_id = Null)
	{
		$sent_email = new SentEmail();
			$sent_email->action_name = $action_name;
			$sent_email->sent_from = $email_from;
			$sent_email->sent_to = $email_to;
			$sent_email->cc_emails = $email_cc;
			$sent_email->subject = $email_title;
			$sent_email->template = $email_body;
			$sent_email->applicant_id = $applicant_id;
			$sent_email->sale_id = $sale_id;
			$sent_email->email_added_date = date("jS F Y");
			$sent_email->email_added_time = date("h:i A");
			if($action_name == 'Random Email'){
				$sent_email->status = '0';
			}
			$sent_email->save();
			if($sent_email->id)
			{
				return 'success';
			}
			else
			{
				return 'error';
			}
	}
	
	public function addApplicantSms($applicant_number, $applicant_name, $applicant_source)
    {
		 $applicant_message = "Dear $applicant_name, We have come across your profile on an Online Portal and have been highly impressed with your extensive experience as a solicitor. Your expertise and skills make you a valuable asset, and we believe that we can find you a position that aligns with your needs and offers great benefits. We would be delighted to schedule a conversation with you to get to know you better and introduce ourselves. Please let us know a convenient time for you to discuss further. You may reply to this message or reach out to us using the contact information provided below. Best regards: Recruitment Team. T: 01494211220 E: info@kingsburypersonnel.com";

        $query_string = 'http://milkyway.tranzcript.com:1008/sendsms?username=admin&password=admin&phonenumber='.$applicant_number.'&message='.$applicant_message.'&port=2&report=JSON&timeout=0';
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
                return 'success';
            } elseif ($report == "sending") {
                            return 'success';

            } else {
                return 'error';
            }
        }
        else
        {
            return 'error';

        }

    }
	
	public function applicantEmail($applicant_name, $applicant_source, $applicant_email)
    {
        $template = EmailTemplate::where('title','generic_email')->first();
        $data = $template->template;
        $replace = [$applicant_name,' an Online Portal'];
        $prev_val = ['(Applicant Name)', '(website name)'];

    $newPhrase = str_replace($prev_val, $replace, $data);
        
        Mail::send([],[], function($message) use ($newPhrase, $applicant_email) {
            $message->from('info@kingsburypersonnel.com', 'Kingsbury Personnel Ltd');
            $message->to($applicant_email);
            $message->subject('New Job Alert');
            $message->setBody($newPhrase, 'text/html');
        });
        if (Mail::failures()) {
            return 'error';
        }
        else
        {
			 $email_from = 'info@kingsburypersonnel.com';
            $email_sent_to_cc ='';
            $subject = 'New Job Alert';
            $action_name = 'New Applicant Added Email';
            $dbSaveEmail = $this->saveSentEmails($applicant_email, $email_sent_to_cc, $email_from, $subject, $newPhrase, $action_name);
            return 'success';
        }
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        date_default_timezone_set('Europe/London');
        $auth_user = Auth::user()->id;
		$job_title_category = $request->Input('applicant_job_title');

        $job_title_prof_validate= '';
        if($job_title_category=='nonnurse specialist' || $job_title_category=='nurse specialist')
        {
            $job_title_prof_validate='required';
        }
        else
        {
            $job_title_prof_validate='';
        }

		if (isset($request->applicant_cv) && $request->hasFile('applicant_cv')) {
			$validator = Validator::make($request->all(), [
				'applicant_cv' => 'nullable|mimes:docx,doc,csv,pdf|max:5000',
			])->validate();
			
            $filenameWithExt = $request->file('applicant_cv')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('applicant_cv')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;
            $path = $request->file('applicant_cv')->move('uploads/', $fileNameToStore);
        } else {
            $path = 'old_image';
        } 
	
         $validator = Validator::make($request->all(), [
			 'applicant_email'=>'email|unique:applicants,applicant_email',
			 'applicant_job_title' => 'required',
			 'job_title_prof' => $job_title_prof_validate,
			 'applicant_postcode' => 'required',
			 'applicant_phone' => 'unique:applicants',
			 'applicant_homePhone' => 'unique:applicants',
			 'department' => 'required'
        ])->validate();

        $postcode = $request->input('applicant_postcode');
		
		//get lat and long
       	$data_arr = $this->geocode($postcode);

        $latitude = 00.000000;
        $longitude = 00.000000;
        if ($data_arr) {
   			$latitude = isset($data_arr[0])?$data_arr[0]:null;
            $longitude = isset($data_arr[1])?$data_arr[1]:null;
       	}
		$applicant_notes = $request->input('applicant_notes').' --- By: '.auth()->user()->name.' Date: '.Carbon::now()->format('d-m-Y');
		
        $applicant = new Applicant();
        $applicant->applicant_user_id = $auth_user;
        $applicant->applicant_job_title = $request->input('applicant_job_title');
		if($job_title_category=='nonnurse specialist' || $job_title_category=='nurse specialist')
        {
            $applicant->job_title_prof = $request->input('job_title_prof');
        }   
        $applicant->applicant_name = $request->input('applicant_name');
        $applicant->applicant_email = $request->input('applicant_email');
        $applicant->applicant_source = $request->input('applicant_source');
        $applicant->applicant_postcode = $request->input('applicant_postcode');
        $applicant->applicant_phone = $request->input('applicant_phone');
        $applicant->applicant_homePhone = $request->input('applicant_homePhone');
		$applicant->applicant_experience = $request->input('applicant_experience');
		$applicant->department = $request->input('department');
		$applicant->sub_department = isset($request->subDepartment) && $request->input('subDepartment') ? $request->input('subDepartment') : null;
		
		if ($applicant->applicant_job_title == "rgn" || 
			$applicant->applicant_job_title == "rmn" || 
			$applicant->applicant_job_title == "rnld" ||
			$applicant->applicant_job_title == "nurse deputy manager" || 
			$applicant->applicant_job_title == "nurse manager" || 
			$applicant->applicant_job_title == "senior nurse" ||
			$applicant->applicant_job_title == "rgn/rmn" || 
			$applicant->applicant_job_title == "rmn/rnld" || 
			$applicant->applicant_job_title == "rgn/rmn/rnld" ||
			$applicant->applicant_job_title == "clinical lead" || 
			$applicant->applicant_job_title == "rcn" || 
			$applicant->applicant_job_title == "peripatetic nurse" ||
			$applicant->applicant_job_title === "unit manager" || 
			$applicant->applicant_job_title === "nurse specialist") 
		{
			$applicant->job_category = "nurse";
			
		}elseif ($applicant->applicant_job_title == "head chef" || 
				 $applicant->applicant_job_title == "chef" || 
				 $applicant->applicant_job_title == "sous chef" || 
				 $applicant->applicant_job_title == "chef de partie"|| 
				 $applicant->applicant_job_title == "commis chef")
		{
            $applicant->job_category = "chef";
		}elseif($applicant->applicant_job_title == "nursery manager" ||
				$applicant->applicant_job_title == "nursery deputy manager" ||
				$applicant->applicant_job_title == "nursery practitioner" ||
				$applicant->applicant_job_title == "room leader" ||
				$applicant->applicant_job_title == "teacher" ||
				$applicant->applicant_job_title == "room attendant / nursery assistant"
			   )
		{
			$applicant->job_category = "nursery"; 
		
		}else {
            $applicant->job_category = "non-nurse";
        }
		
        $applicant->applicant_cv = $path;
        $applicant->applicant_notes = $applicant_notes;
        $applicant->applicant_added_date = date("jS F Y");
        $applicant->applicant_added_time = date("h:i A");
        $applicant->lat = $latitude;
        $applicant->lng = $longitude;
        $applicant->save();
		
        $last_inserted_applicant = $applicant->id;
        if ($last_inserted_applicant) {
            $applicant_uid = md5($last_inserted_applicant);
            DB::table('applicants')->where('id', $last_inserted_applicant)->update(['applicant_u_id' => $applicant_uid]);
			    $molduleNote= ModuleNote::create([
                'details' => $applicant_notes,
                'module_noteable_id' =>$last_inserted_applicant,
                'module_noteable_type' =>'Horsefly\Applicant',
                'module_note_added_date'=> Carbon::now()->format('jS F Y'),
                'module_note_added_time'=> Carbon::now()->format('h:i A'),
                'user_id'=> Auth::id(),
                'status'=> 'active'
            ]);
            $molduleNote_uid=md5($molduleNote->id);
            DB::table('module_notes')->where('id', $molduleNote->id)->update(['module_note_uid' => $molduleNote_uid]);

            $job_title = $request->input('applicant_job_title');
            $nurse_titles = array('rgn','rmn','rnld','nurse deputy manager','nurse manager','senior nurse','rgn/rmn','rmn/rnld','rgn/rmn/rnld','clinical lead','rcn','peripatetic nurse','unit manager');

            if(in_array($job_title, $nurse_titles))
            {
             
				   $result = $this->applicantEmail($request->input('applicant_name'), $request->input('applicant_source'), $request->input('applicant_email'));
                if($result == 'success')
                {
                    $result = ', Email Sent Successfuly';
                }
                else
                {
                    $result = ', Email Could Not Be Sent';
                }
            
				 $sms_res = $this->addApplicantSms($request->input('applicant_phone'), $request->input('applicant_name'), $request->input('applicant_source'));
                if($sms_res == 'success')
                {
                    $sms_res = 'And Sms Sent Successfuly.';
                }
                else
                 {
                    $sms_res = 'And there is error sending sms...';
                }
				 
                    return redirect('applicants')->with('success', 'Applicant Saved In Records'.$result.' '.$sms_res);

            }
            else
            {
                return redirect('applicants')->with('success', 'Applicant Saved In Records');
            }
        } else {
            return redirect('applicants.create')->with('applicant_add_error', 'WHOOPS! Applicant Could not Added');
        }
    }



    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $sec_job_data='';
        $applicant = Applicant::find($id);
        if($applicant->applicant_job_title=='nurse specialist' || $applicant->applicant_job_title=='nonnurse specialist')
        $sec_job_data = Specialist_job_titles::select("*")->where("id",$applicant->job_title_prof)->first();

        return view('administrator.applicant.show', compact('applicant','sec_job_data'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $applicant = Applicant::find($id);
		$selectedID = $applicant->applicant_source;
        $applicant_source = array(
                'Total Jobs' => 'Total Jobs',
                'Reed' => 'Reed',
                'Niche' => 'Niche',
                'CV Library' => 'CV Library',
                'Social Media' => 'Social Media',
                'Referral' => 'Referral',
                'Other Source' => 'Other Source',
	       	    'Monster' => 'Monster',
                'Jobmedic' => 'Jobmedic',
				'Totally Legal' => 'Totally Legal'
		);
		
		$sec_job_data = Specialist_job_titles::select("*")
			->where("id",$applicant->job_title_prof)
			->first();
		
        $spec_all_jobs_data = Specialist_job_titles::select("*")
			->where("specialist_title", $applicant->applicant_job_title)
			->get();
		
        return view('administrator.applicant.edit', compact('applicant', 'sec_job_data', 'spec_all_jobs_data', 'applicant_source', 'selectedID'));
    }
	
	public function ajax_unblock_applicants(Request $request)
    {
        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                     WHOOPS! Something Went Wrong!!
                </div>';
        $start_date = $request->input('from_date');
        $start_date = Carbon::parse($start_date)->format('Y-m-d H:i:s');
        $start_date=Carbon::createFromFormat('Y-m-d H:i:s', $start_date);
        $end_date = $request->input('to_date');
        $end_date = Carbon::parse($end_date)->format('Y-m-d'). " 23:59:59";

        if($start_date && $end_date)
        {
            DB::table('applicants')->whereBetween('updated_at', [$start_date, $end_date])->update(['is_blocked' => '0','applicant_notes' => 'Applicant Unblocked']);
            $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							 Applicants unblocked successfully
						</div>';
                        echo $html;

        }
        else
        {
            echo $html;
        }
        
    }
	
	public function export_block_applicants(Request $request)
    {
       
        $start_date = $request->input('start_date');
        $start_date = Carbon::parse($start_date)->format('Y-m-d'). " 00:00:01";
        $end_date = $request->input('end_date');
        $end_date = Carbon::parse($end_date)->format('Y-m-d'). " 23:59:59";     

        return Excel::download(new Export_blocked_applicants($start_date,$end_date), 'applicants.csv');
    }
	public function exportNotInterestedApplicants(Request $request)
    {
        $start_date = $request->input('start_date');
        $start_date = Carbon::parse($start_date)->format('Y-m-d'). " 00:00:01";

        $end_date = $request->input('end_date');
        $end_date = Carbon::parse($end_date)->format('Y-m-d'). " 23:59:59";     

        return Excel::download(new Export_temp_not_interested_applicants($start_date,$end_date), 'applicants.csv');
    }
	
	
	public function getDownloadApplicantCv($cv_id)
    {
        $url = url()->previous();

        $applicant = Applicant::select("applicant_cv")->where('id', $cv_id)->first();
        if ($applicant['applicant_cv'] == '' || $applicant['applicant_cv'] == 'old_image')
        {
                        return redirect($url)->with('error', 'CV Against This Applicant Is Not Uploaded Yet!');
        }
        else
        {
			if (strpos($applicant->applicant_cv, 'public') !== false) {
                $file = public_path(substr($applicant->applicant_cv, 7));
            } else {
                $file = public_path($applicant->applicant_cv);
            }
            $headers = array(
                'Content-Type: application/*',
            );
			if (!File::exists(public_path($applicant->applicant_cv))) {
				return redirect($url)->with('error', 'CV for this applicant is not found in folder...');
				}
            $app_cv = substr($applicant->applicant_cv, 8);
            return Response::download($file, $app_cv, $headers);
        }
       
    }

    public function getUpdatedDownloadApplicantCv($cv_id)
    {
        
        $url = url()->previous();

        $applicant = Applicant::select("updated_cv")->where('id', $cv_id)->first();
        if($applicant['updated_cv'] == '' || $applicant['updated_cv'] == 'old_image')
        {

            return redirect($url)->with('error', 'Updated CV Against This Applicant Is Not Uploaded Yet!');
        }
        else
        {
            if (strpos($applicant->updated_cv, 'public') !== false) {

                $file = public_path(substr($applicant->updated_cv, 7));
            } else {
                $file = public_path($applicant->updated_cv);
            }
            $headers = array(
                'Content-Type: application/*',
            );
            $app_cv = substr($applicant->updated_cv, 8);

            return Response::download($file, $app_cv, $headers);
			            

        }
       
    }
    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        date_default_timezone_set('Europe/London');
        $auth_user = Auth::user()->id;
        if ($request->hasFile('applicant_cv')) {
            $filenameWithExt = $request->file('applicant_cv')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('applicant_cv')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;
            $path = $request->file('applicant_cv')->move('uploads/', $fileNameToStore);
        } else {
            $path = $request->get('old_image');
        }

		$applicant_note = $request->get('notes_details').' --- By: '.auth()->user()->name.' Date: '.Carbon::now()->format('d-m-Y');
        $applicant = Applicant::find($id);
		$new_job_title = $request->get('applicant_job_title');
        $applicant->applicant_user_id = $auth_user;
		$applicant->applicant_experience = $request->get('applicant_experience');
        if ($applicant->applicant_job_title != $request->get('applicant_job_title'))
            $applicant->applicant_job_title = $request->get('applicant_job_title');
		
		if($new_job_title === 'nurse specialist' || $new_job_title === 'nonnurse specialist'){
                $applicant->job_title_prof= $request->get('job_title_prof');
        }
		else
        {
            $applicant->job_title_prof= null;
        }
		
		if($request->get('notes_type')=='2')
        {
            $applicant->is_blocked='1';
            $applicant->temp_not_interested='0';
            $applicant->applicant_notes=$applicant_note;
        }
        else if($request->get('notes_type')=='3')
        {
            $applicant->temp_not_interested='1';
            $applicant->is_blocked='0';
            $applicant->applicant_notes=$applicant_note;
        }
		else if($request->get('notes_type')=='1')
        {
            $applicant->temp_not_interested='0';
            $applicant->is_blocked='0';
            $applicant->applicant_notes=$applicant_note;
        }
        else
        {
			$applicant->temp_not_interested='0';
            $applicant->is_blocked='0';
            $applicant->applicant_notes=$applicant_note;
        }
		
		if(Auth::user()->is_admin == 1 || 
		   Auth::id() == 66 || Auth::id() == 83 || 
		   Auth::id() == 114 || Auth::id() == 101 || Auth::id() == 150 ||
		   Auth::id() == 164){
            if ($applicant->applicant_name !== $request->get('applicant_name'))
            {
                $applicant->applicant_name = $request->get('applicant_name');
            }
        }else{
            $name = $applicant->applicant_name;
            $applicant->applicant_name = $name;
		}
		
        if ($applicant->applicant_email != $request->get('applicant_email'))
            $applicant->applicant_email = $request->get('applicant_email');

        if ($applicant->applicant_source != $request->get('applicant_source'))
            $applicant->applicant_source = $request->get('applicant_source');

        if ($applicant->applicant_postcode != $request->get('applicant_postcode'))
        {
            $applicant->applicant_postcode = $request->get('applicant_postcode');
            $postcode = $request->get('applicant_postcode');
        	$data_arr = $this->geocode($postcode);
            $latitude = 00.000000;
            $longitude = 00.000000;
          	if ($data_arr) {
               $latitude = $data_arr[0];
               $longitude = $data_arr[1];
            }
            $applicant->lat = $latitude;
            $applicant->lng = $longitude;
        }
		
		if(Auth::user()->is_admin==1  || Auth::id()==66 || Auth::id() == 164 ||  Auth::id() == 150 || Auth::id() == 101){
            if ($applicant->applicant_phone !== $request->get('applicant_phone'))
            {
                $applicant->applicant_phone = $request->get('applicant_phone');
            }
			
			if ($applicant->applicant_homePhone != $request->get('applicant_homePhone')){
                $applicant->applicant_homePhone = $request->get('applicant_homePhone');
            }
        }else{
            $phoneNumber= $applicant->applicant_phone;
            $applicant->applicant_phone=$phoneNumber;
			if ($auth_user==66){
                if ($applicant->applicant_homePhone != $request->get('applicant_homePhone')){
                    $applicant->applicant_homePhone = $request->get('applicant_homePhone');
                }
            }else {
                $landLineNumber = $applicant->applicant_homePhone;
                $applicant->applicant_homePhone = $landLineNumber;
            }
        }
		$applicant->department = $request->input('department');
		$applicant->sub_department = isset($request->subDepartment) && $request->input('subDepartment') ? $request->input('subDepartment') : null;
        //if ($applicant->applicant_phone !== $request->get('applicant_phone'))
          //  $applicant->applicant_phone = $request->get('applicant_phone');

        //if ($applicant->applicant_homePhone !== $request->get('applicant_homePhone'))
          //  $applicant->applicant_homePhone = $request->get('applicant_homePhone');

        if ($applicant->applicant_job_title == "rgn" || 
			$applicant->applicant_job_title == "rmn" || 
			$applicant->applicant_job_title == "rnld" ||
            $applicant->applicant_job_title == "nurse deputy manager" || 
			$applicant->applicant_job_title == "nurse manager" || 
			$applicant->applicant_job_title == "senior nurse" ||
            $applicant->applicant_job_title == "rgn/rmn" || 
			$applicant->applicant_job_title == "rmn/rnld" || 
			$applicant->applicant_job_title == "rgn/rmn/rnld" ||
            $applicant->applicant_job_title == "clinical lead" || 
			$applicant->applicant_job_title == "rcn" || 
			$applicant->applicant_job_title == "peripatetic nurse" ||
            $applicant->applicant_job_title === "unit manager" || 
			$applicant->applicant_job_title === "nurse specialist") 
		{
            $applicant->job_category = "nurse";
        } elseif ($applicant->applicant_job_title == "head chef" || 
				  $applicant->applicant_job_title == "chef" || 
				  $applicant->applicant_job_title == "sous chef" || 
				  $applicant->applicant_job_title == "chef de partie"|| 
				  $applicant->applicant_job_title == "commis chef")
		{
            $applicant->job_category = "chef";

         }elseif($applicant->applicant_job_title == "nursery manager" ||
            $applicant->applicant_job_title == "nursery deputy manager" ||
            $applicant->applicant_job_title == "nursery practitioner" ||
            $applicant->applicant_job_title == "room leader" ||
            $applicant->applicant_job_title == "teacher" ||
            $applicant->applicant_job_title == "room attendant / nursery assistant"
            )
		{
                $applicant->job_category = "nursery";
		}else {
            $applicant->job_category = "non-nurse";
        }
		//        $applicant->applicant_notes = $request->input('applicant_notes');
        $applicant->applicant_added_date = date("jS F Y");
        $applicant->applicant_added_time = date("h:i A");

        $applicant->applicant_cv = $path;
        $applicant->update();
		$checkUpdateC=$applicant->getChanges();
        $columnName=array_keys($checkUpdateC);
		
		
        ModuleNote::where([
            'module_noteable_id' => $id,
            'module_noteable_type' => 'Horsefly\Applicant'
        ])
            ->orderBy('id', 'desc')
            ->take(1)
            ->update(['status' => 'disable']);
        
        $molduleNote= ModuleNote::create([
            'details' => $applicant_note,
            'module_noteable_id' =>$id,
            'module_noteable_type' =>'Horsefly\Applicant',
            'module_note_added_date'=> Carbon::now()->format('jS F Y'),
            'module_note_added_time'=> Carbon::now()->format('h:i A'),
            'user_id'=>$auth_user,
            'status'=> 'active'
        ]);
        $molduleNote_uid=md5($molduleNote->id);
        DB::table('module_notes')->where('id', $molduleNote->id)->update(['module_note_uid' => $molduleNote_uid]);

		// new requirements to show who by edit apllicants
        $updatedHistoryStore=ApplicantUpdatedHistory::create([
            'user_id'=>$auth_user,
            'applicant_id'=>$id,
            'column_name'=>json_encode($columnName)
        ]);

        return redirect('applicants')->with('updateSuccessMsg', 'Applicant has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $applicant = Applicant::find($id);
        $status = $applicant->status;
        if ($status == 'active') {
            if (DB::table('applicants')->where('id', $id)->update(['status' => 'disable'])) {
                return redirect('applicants')->with('ApplicantDeleteSuccessMsg', 'Applicant has been disabled Successfully');
            } else {
                return redirect('applicants')->with('ApplicantDeleteErrMsg', 'WHOOPS! Something Went Wrong!!');
            }

        } elseif ($status == 'disable') {
            if (DB::table('applicants')->where('id', $id)->update(['status' => 'active'])) {
                return redirect('applicants')->with('ApplicantDeleteSuccessMsg', 'Applicant has been enabled Successfully');
            } else {
                return redirect('applicants')->with('ApplicantDeleteErrMsg', 'WHOOPS! Something Went Wrong!!');
            }
        }
    }

    //FUNCTION ACCESSIBLE WITH NORMAL ROUTING OTHER THAN RESOURCE ROUTING
    public function getNurseHomeApplicants()
    {
        return view('administrator.nursing.index');
    }
	public function exportNurseHomeApplicants()
    {
        $auth_user = Auth::user();
        $applicants = Applicant::with('cv_notes')
            ->join('applicant_notes', 'applicant_notes.applicant_id', '=', 'applicants.id')
            ->select('applicants.applicant_phone', 'applicants.applicant_name', 'applicants.applicant_homePhone','applicants.applicant_job_title',
            'applicants.applicant_postcode','applicants.applicant_source','applicants.applicant_notes'
            )->where([
                'applicants.status' => 'active',"applicants.is_in_nurse_home" => "yes",
                'applicant_notes.moved_tab_to' => 'no_nursing_home','applicant_notes.status' => 'active'
            ])->get();
        return Excel::download(new Applicants_nureses_7_days_export($applicants), 'applicants.csv');

    }
    public function getNurseHomeApplicantsAjax()
    {
        $auth_user = Auth::user();
        $applicants = Applicant::with('cv_notes')
            ->join('applicant_notes', 'applicant_notes.applicant_id', '=', 'applicants.id')
            ->select("applicants.id","applicants.applicant_job_title","applicants.job_title_prof","applicants.applicant_name","applicants.applicant_postcode","applicants.applicant_phone",
                "applicants.applicant_homePhone","applicants.job_category","applicants.applicant_source","applicants.paid_status",
                "applicant_notes.details","applicant_notes.added_date","applicant_notes.added_time"
            )->where([
                'applicants.status' => 'active',"applicants.is_in_nurse_home" => "yes",'applicants.is_no_job' => '0',
                'applicant_notes.moved_tab_to' => 'no_nursing_home','applicant_notes.status' => 'active'
            ]);
        $raw_columns = ['applicant_job_title','history','postcode'];
        $datatable = datatables()->of($applicants)
			->editColumn('applicant_job_title', function ($applicant) {
            $job_title_desc='';
            if($applicant->job_title_prof!=null)
            {
                $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $applicant->job_title_prof)->first();
                            $job_title_desc = $applicant->applicant_job_title.' ('.$job_prof_res->specialist_prof.')';
            }
            else
            {
                
                $job_title_desc = $applicant->applicant_job_title;
            }
            return $job_title_desc;

     })
            ->addColumn('history', function ($applicant) {
                    $content = '';

                    $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant->id.'"; 
                                 data-controls-modal="#reject_history'.$applicant['id'].'" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $applicant->id . '">History</a>';

                    $content .= '<div id="reject_history'.$applicant->id.'" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">'.$applicant['applicant_name'].'</span></h6>';
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
//                    ->addColumn('action', function ($applicants) {
//                    return
//                          '<div class="list-icons">
//                            <div class="dropdown">
//                                <a href="#" class="list-icons-item" data-toggle="dropdown">
//                                    <i class="icon-menu9"></i>
//                                </a>
//                                <div class="dropdown-menu dropdown-menu-right">
//                                    <a href="#"
//                                       class="dropdown-item"
//                                       data-controls-modal="#revertNurseApplicant'.$applicants->id.'" data-backdrop="static"
//                                       data-keyboard="false" data-toggle="modal"
//                                       data-target="#revertNurseApplicant'.$applicants->id.'"
//                                       ><i class="icon-file-confirm"></i>&nbsp;Revert</a>
//                                </div>
//                            </div>
//                        </div>
//                        <div id="revertNurseApplicant'.$applicants->id.'" class="modal fade" tabindex="-1">
//                    <div class="modal-dialog modal-lg">
//                        <div class="modal-content">
//                            <div class="modal-header">
//                                <h5 class="modal-title">Add Revert Nursing Note Below:</h5>
//                                <button type="button" class="close" data-dismiss="modal">&times;</button>
//                            </div>
//
//                            <form action="revert-nurse-home-applicant" method="GET"
//                                  class="form-horizontal">
//                                <input type="hidden" name="_token" value="'.csrf_token().'">
//                                <div class="modal-body">
//                                    <div class="form-group row">
//                                        <label class="col-form-label col-sm-3">Details</label>
//                                        <div class="col-sm-9">
//                                            <input type="hidden" name="applicant_hidden_id"
//                                                   value="'.$applicants->id.'">
//                                            <input type="hidden" name="sale_hidden_id" value="'.$applicants->sale_id.'">
//                                            <textarea name="details" class="form-control" cols="30" rows="4"
//                                                      placeholder="TYPE HERE.." required></textarea>
//                                        </div>
//                                    </div>
//                                </div>
//
//                                <div class="modal-footer">
//                                    <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">
//                                        Close
//                                    </button>
//                                    <button type="submit" class="btn bg-teal legitRipple">Save</button>
//                                </div>
//                            </form>
//                        </div>
//                    </div>
//                </div>';
//                })
            ->addColumn("applicant_postcode",function($applicants) {
                if ($applicants->paid_status == 'close') {
                    return $applicants->applicant_postcode;
                } else {
                    foreach ($applicants->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            return $applicants->applicant_postcode;
                        }
                    }
                    //return '<a href="/available-jobs/' . $applicants->id . '">' . $applicants->applicant_postcode . '</a>';
					return '<a href="/available-jobs/' . $applicants->id . '">' . $applicants->applicant_postcode . '</a>';
                }
            });
        if ($auth_user->hasPermissionTo('resource_No-Nursing-Home_revert-no-nursing-home')) {
            $datatable = $datatable->addColumn('checkbox', function ($applicant) {
                return '<label class="mt-checkbox mt-checkbox-single mt-checkbox-outline">
                             <input type="checkbox" class="checkbox-index" value="'.$applicant->id.'">
                             <span></span>
                          </label>';
            });
            $raw_columns = ['checkbox','applicant_postcode','history'];
        }
        return $datatable->setRowClass(function ($result) {
                $row_class = '';
                if ($result->paid_status == 'close') {
                    $row_class = 'class_dark';
                } else { /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
                    foreach ($result->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $row_class = 'class_success'; // status: sent
                            break;
                        } elseif ($value->status == 'disable') {
                            $row_class = 'class_danger'; // status: reject
                        }
                    }
                }
                return $row_class;
            })
            ->rawColumns($raw_columns)
            ->make(true);
    }

    public function getNurseHomeApplicant()
    {
        date_default_timezone_set('Europe/London');
        $audit_data['action'] = "No Nursing Home";
        $details = request()->details;
        $audit_data['applicant'] = $applicant_id = request()->applicant_hidden_id;

        $user = Auth::user();
        ApplicantNote::where('applicant_id', $applicant_id)
            ->whereIn('moved_tab_to', ['no_nursing_home','revert_no_nursing_home'])
            ->update(['status' => 'disable']);
        $applicant_note = new ApplicantNote();
        $applicant_note->user_id = $user->id;
        $applicant_note->applicant_id = $applicant_id;
        $audit_data['added_date'] = $applicant_note->added_date = date("jS F Y");
        $audit_data['added_time'] = $applicant_note->added_time = date("h:i A");
        $audit_data['details'] = $applicant_note->details = $details;
        $applicant_note->moved_tab_to = "no_nursing_home";
        $applicant_note->status = "active";
        $applicant_note->save();
        $last_inserted_note = $applicant_note->id;
        if ($last_inserted_note > 0) {
            $note_uid = md5($last_inserted_note);
            ApplicantNote::where('id', $last_inserted_note)->update(['note_uid' => $note_uid]);
            Applicant::where(['id' => $applicant_id])->update(['is_in_nurse_home' => 'yes']);
            /*** activity log
             * $action_observer = new ActionObserver();
             * $action_observer->action($audit_data, 'Resource');
             */
            if(isset(request()->requestByAjax) && request()->requestByAjax == 'yes')
            {
                return response()->json(['success' => true, 'message' => 'Applicant has been Moved.' ]);
            }else{
                return Redirect::back()->with('revertNurseHomeApplicantMsg', 'Applicant has been Moved.');
            }
        }
         if(isset(request()->requestByAjax) && request()->requestByAjax == 'yes')
        {
            return response()->json(['success' => true, 'message' => 'Something went wrong.' ]);
        }else{
            return redirect()->back();
        }
    }

    public function getNurseHomeApplicantFromDays()
    {
        date_default_timezone_set('Europe/London');
        $details = request()->details;
        $sale_id = request()->sale_hidden_id;
        $applicant_id = request()->applicant_hidden_id;
        $user = Auth::user();
        $current_user_id = $user->id;
        $quality_notes = new Quality_notes();
        $quality_notes->applicant_id = $applicant_id;
        $quality_notes->user_id = $current_user_id;
        $quality_notes->sale_id = $sale_id;
        $quality_notes->details = $details;
        $quality_notes->quality_added_date = date("jS F Y");
        $quality_notes->quality_added_time = date("h:i A");
        $quality_notes->moved_tab_to = "from_days_to_no_nursing_home";
        $quality_notes->save();
        $last_inserted_note = $quality_notes->id;
        if ($last_inserted_note > 0) {
            $quality_note_uid = md5($last_inserted_note);
            Quality_notes::where('id', $last_inserted_note)->update(['quality_notes_uid' => $quality_note_uid]);
            Applicant::where('id', $applicant_id)->update(['is_in_nurse_home' => 'yes']);
            return redirect::back()->with('revertNurseHomeApplicantMsg', 'Applicant has been Moved');
        }
    }
    public function getRevertNurseHomeApplicant()
    {
        date_default_timezone_set('Europe/London');
        $audit_data['action'] = "Revert No Nursing Home";
        $audit_data['applicant'] = $applicant = request()->applicant_hidden_id;
        $audit_data['sale'] = $sale = request()->sale_hidden_id;
        $detail_note = request()->details;

        $user = Auth::user();
        $current_user_id = $user->id;
        $quality_note = new Quality_notes();
        $quality_note->sale_id = $sale;
        $quality_note->user_id = $current_user_id;
        $quality_note->applicant_id = $applicant;
        $audit_data['added_date'] = $quality_note->quality_added_date = date("jS F Y");
        $audit_data['added_time'] = $quality_note->quality_added_time = date("h:i A");
        $audit_data['details'] = $quality_note->details = $detail_note;
        $quality_note->moved_tab_to = 'revert_to_no_nursing_home';
        $quality_note->save();
//        $last_inserted_note = $quality_note->id;
        //if ($last_inserted_note > 0) {
//            $quality_note_uid = md5($last_inserted_note);
        Applicant::where('id', $applicant)->update(['is_in_nurse_home' => 'no']);

        /*** activity log
        $action_observer = new ActionObserver();
        $action_observer->action($audit_data, 'Resource');
         */

        return redirect::back()->with('revertApplicantFromNurseHomeApplicantMsg', 'Applicant has been Reverted');
//        }
//        else{
        //return redirect('nurse-home-applicants')->with('revertApplicantFromNurseHomeApplicantMsg', 'WHOOPS!Something went wrong');
        //}
    }

    public function getApplicantCvSendToQuality(Request $request, $applicant_cv_id)
    {
		date_default_timezone_set('Europe/London');
		
        $audit_data['action'] = "Send CV";
        $audit_data['applicant'] = $applicant = request()->applicant_hidden_id;
        $audit_data['sale'] = $sale = request()->sale_hidden_id;
		$applicant_title_prof = Applicant::find($audit_data['applicant']);
		
		$user = Auth::user();
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');

        $sale_title_prof = Sale::find($sale);
        if($applicant_title_prof->job_title_prof==$sale_title_prof->job_title_prof)
        {
			$noteDetail = '';
			// Format data into HTML
			if($request->has('hangup_call') && $request->hangup_call == '1'){
				$reason = '';
				$reason .= '<strong>Call Hung up/Not Interested:</strong> ' . ($request->input('hangup_call') ? 'Yes' : 'No') . '<br>';
				$reason .= '<strong>Details:</strong> ' . nl2br(htmlspecialchars($request->input('details'))) . " --- By: " . $user->name . ", Date: " . $current_date . ", Time: " . $current_time . '<br>';

				$applicant_id = $request->input('applicant_hidden_id');
				$job_id = $request->input('sale_hidden_id');
				$not_interested_reason_note = $reason;

				$interest = new Applicants_pivot_sales();
				$interest->interest_added_date = date("jS F Y");
				$interest->interest_added_time = date("h:i A");
				$interest->applicant_id = $applicant_id;
				$interest->sales_id = $job_id;
				$interest->save();

				$last_inserted_interest = $interest->id;
				if ($last_inserted_interest) {
					$interest_uid = md5($last_inserted_interest);
					DB::table('applicants_pivot_sales')->where('id', $last_inserted_interest)
						->update(['applicants_pivot_sales_uid' => $interest_uid]);
					
					$notes_for_range = new Notes_for_range_applicants();
					$notes_for_range->applicants_pivot_sales_id = $last_inserted_interest;
					$notes_for_range->reason = $not_interested_reason_note;
					$notes_for_range->save();

					$notes_for_range_last_insert_id = $notes_for_range->id;
					if ($notes_for_range_last_insert_id) {
						$range_notes_uid = md5($notes_for_range_last_insert_id);
						Notes_for_range_applicants::where('id', $notes_for_range_last_insert_id)->update(['range_uid' => $range_notes_uid]);
					}
				}

			}else{
				// Format transport_type and shift_pattern if needed
				if($request->has('transport_type')){
					$transportType = implode(', ', $request->input('transport_type'));
					$shiftPattern = implode(', ', $request->input('shift_pattern'));
				}

				if($request->has('shift_pattern') ){
					$transportType = implode(', ', $request->input('transport_type'));
					$shiftPattern = implode(', ', $request->input('shift_pattern'));
				}

				$noteDetail .= '<strong>Current Employer Name:</strong> ' . htmlspecialchars($request->input('current_employer_name')) . '<br>';
				$noteDetail .= '<strong>PostCode:</strong> ' . htmlspecialchars($request->input('postcode')) . '<br>';
				$noteDetail .= '<strong>Current/Expected Salary:</strong> ' . htmlspecialchars($request->input('expected_salary')) . '<br>';
				$noteDetail .= '<strong>Qualification:</strong> ' . htmlspecialchars($request->input('qualification')) . '<br>';
				$noteDetail .= '<strong>Transport Type:</strong> ' . htmlspecialchars($transportType) . '<br>';
				$noteDetail .= '<strong>Shift Pattern:</strong> ' . htmlspecialchars($shiftPattern) . '<br>';
				$noteDetail .= '<strong>Nursing Home:</strong> ' . ($request->input('nursing_home') ? 'Yes' : 'No') . '<br>';
				$noteDetail .= '<strong>Alternate Weekend:</strong> ' . ($request->input('alternate_weekend') ? 'Yes' : 'No') . '<br>';
				$noteDetail .= '<strong>Interview Availability:</strong> ' . ($request->input('interview_availability') ? 'Available' : 'Not Available') . '<br>';
				$noteDetail .= '<strong>No Job:</strong> ' . ($request->input('no_job') ? 'Yes' : 'No') . '<br>';
				$noteDetail .= '<strong>Visa Status:</strong> ' . ($request->input('visa_status') ?? '-') . '<br>';
				$noteDetail .= '<strong>Travel Range:</strong> ' . ($request->input('travel_range') ?? '-') . '<br>';
				$noteDetail .= '<strong>Details:</strong> ' . nl2br(htmlspecialchars($request->input('details'))) . " --- By: " . $user->name . ", Date: " . $current_date . ", Time: " . $current_time . '<br>';
			}

        $detail_note = $noteDetail;

        $sale_details = Sale::find($sale);
        if ($sale_details) {
            $sent_cv_count = Cv_note::where(['sale_id' => $sale, 'status' => 'active'])->count();
			$open_cv_count = History::where(['sale_id' => $sale, 'status' => 'active', 'sub_stage' => 'quality_cvs_hold'])->count();
			$net_sent_cv_count = $sent_cv_count - $open_cv_count;

            if ($net_sent_cv_count < $sale_details->send_cv_limit) {

                $applicants_rejected = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
                    ->where('applicants.status', 'active');

                $applicants_rejected = $applicants_rejected->where('applicants.is_in_crm_reject', 'yes')
                    ->orWhere('applicants.is_in_crm_request_reject', 'yes')
                    ->orWhere('applicants.is_crm_interview_attended', 'no')
                    ->orWhere('applicants.is_in_crm_start_date_hold', 'yes')
                    ->orWhere('applicants.is_in_crm_dispute', 'yes')
                    ->orWhere([['applicants.is_CV_reject', 'yes'], ['quality_notes.moved_tab_to', 'rejected']])
                    ->get();

                $rejectedApp = 0;
                foreach ($applicants_rejected as $app) {
                    if ($app->id == $applicant_cv_id) {
                        $rejectedApp = 1;
                    }
                }
                Applicant::where('id', $applicant_cv_id)->update(['is_cv_in_quality' => 'yes']);
                $user = Auth::user();
                $current_user_id = $user->id;
				
                $cv_note = new Cv_note();
                $cv_note->sale_id = $sale;
                $cv_note->user_id = $current_user_id;
                $cv_note->applicant_id = $applicant;
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
                    $audit_data['sub_stage'] = $history->sub_stage = 'quality_cvs';
                    $history->history_added_date = date("jS F Y");
                    $history->history_added_time = date("h:i A");
                    $history->save();
					
                    $last_inserted_history = $history->id;
                    if ($last_inserted_history > 0) {
                        $history_uid = md5($last_inserted_history);
                        History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);

                        /*** activity log
                         * $action_observer = new ActionObserver();
                         * $action_observer->action($audit_data, 'Resource');
                         */

                       if ($rejectedApp == 1) {
						   if(isset(request()->requestByAjax) && request()->requestByAjax == 'yes')
						   {
							   return response()->json(['success' => true, 'message' => 'Applicant has been sent to quality' ]);
						   }else{
							   return Redirect::back()->with('qualityApplicantMsg', 'Applicant has been sent to quality');
						   }
					   } else {
						   if(isset(request()->requestByAjax) && request()->requestByAjax == 'yes')
						   {
							   return response()->json(['success' => false, 'message' => 'The Applicant CV Cannot be Sent.' ]);
						   }else{
							   return Redirect::back()->with('qualityApplicantErr', 'The Applicant CV Cannot be Sent.');
						   }
					   }
                    }
                } else {
                   if ($rejectedApp == 1){
					   if(isset(request()->requestByAjax) && request()->requestByAjax == 'yes')
					   {
						   return response()->json(['success' => true, 'message' => 'Applicant has been sent to quality' ]);
					   }else{
						   return Redirect::back()->with('qualityApplicantMsg', 'Applicant has been sent to quality');
					   }
				   }else{
					   if(isset(request()->requestByAjax) && request()->requestByAjax == 'yes')
					   {
						   return response()->json(['success' => false, 'message' => 'The Applicant CV Cannot be Sent.' ]);
					   }else{
						   return Redirect::back()->with('qualityApplicantErr', 'Applicant Cant be Sent');
					   }
				   }
                }
            } else {
                if(isset(request()->requestByAjax) && request()->requestByAjax == 'yes')
				{
					return response()->json(['success' => false, 'message' => 'WHOOPS! You cannot perform this action. Send CV Limit for this Sale has reached maximum.' ]);
				}else{
					return Redirect::back()->with('notFoundCv', 'WHOOPS! You cannot perform this action. Send CV Limit for this Sale has reached maximum.');
				}
            }
        } else {

           if(isset(request()->requestByAjax) && request()->requestByAjax == 'yes')
		   {
			   return response()->json(['success' => false, 'message' => 'Sale not found.' ]);
		   }else{
			   return Redirect::back()->with('notFoundCv', 'Sale not found.');
		   }
        }
	}
    else
    {
         if(isset(request()->requestByAjax) && request()->requestByAjax == 'yes')
		 {
			 return response()->json(['success' => false, 'message' => 'Specialist Title is mismatched!' ]);
		 }else{
			 return Redirect::back()->with('error', 'Specialist Title is mismatched!');
		 }
    }
    }

    public function getApplicantHistory($applicant_history_id)
    {
        $auth_user = Auth::user()->id;

        $applicants_in_crm = Applicant::join('crm_notes', 'crm_notes.applicant_id', '=', 'applicants.id')
            ->join('sales', 'sales.id', '=', 'crm_notes.sales_id')
            ->join('offices', 'offices.id', '=', 'head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
            ->join('history', function($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })
            ->select("applicants.*", "applicants.id as app_id", "crm_notes.*", "crm_notes.id as crm_notes_id", "sales.*", "sales.id as sale_id", "sales.postcode as sale_postcode", "sales.job_title as sale_job_title", "sales.job_category as sales_job_category", "sales.status as sale_status", "history.history_added_date", "history.sub_stage","office_name", "unit_name")
            ->where(array('applicants.id' => $applicant_history_id, 'history.status' => 'active'))
            ->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE sales_id=sales.id and applicants.id=applicant_id'));
            })
            ->get();
		

        $applicant_crm_notes = Applicant::join('crm_notes', 'crm_notes.applicant_id', '=', 'applicants.id')
            ->select("crm_notes.*", "crm_notes.sales_id as sale_id", "applicants.id as app_id")
            ->where(['crm_notes.applicant_id' => $applicant_history_id])
            ->orderBy("crm_notes.created_at", "desc")
            ->get();
		
		
		  $applicantQualityRejectNote=Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
            ->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->select('quality_notes.details', 'quality_notes.quality_added_date',
                'applicants.id', 'applicants.applicant_name',"sales.*",
               'sales.job_title as sale_job_title', 'sales.postcode as sale_postcode','sales.job_category as sales_job_category',
                "quality_notes.quality_added_date","quality_notes.moved_tab_to","quality_notes.quality_added_time",
                'offices.office_name','units.unit_name',
               )
            ->where([
                "applicants.status" => "active",
				"quality_notes.applicant_id" => $applicant_history_id,
                "quality_notes.moved_tab_to" => "rejected",
				//"quality_notes.status" => "active"
            ])->whereIn("quality_notes.status",['active','disable'])->get();
		//dd($applicantQualityRejectNote);
            //echo $applicantQualityRejectNote->count();exit();

        $applicant = Applicant::with('callback_notes','no_nursing_home_notes')->find($applicant_history_id);

        $history_stages = config('constants.history_stages');
		//print_r($history_stages);exit();
        $crm_stages = config('constants.crm_stages');

        // ./APPLICANT SEND AGAINST THIS JOB IN QUALITY FROM SEARCH RESULTS
         return view('administrator.applicant.history.index',compact('applicants_in_crm', 'applicant_crm_notes', 'history_stages', 'crm_stages', 'applicant','applicantQualityRejectNote'));
    }
    public function getApplicantFullHistory($sale_id,$applicant_id){
        $sale = $sale_id;
        $applicant = $applicant_id;
        $auth_user = Auth::user()->id;
        $applicant_name = Applicant::select("applicant_name")->where("id",$applicant)->first();
        // Applicants Activities in Quality
        $applicant_in_quality = Quality_notes::where(array('applicant_id' => $applicant, 'user_id' => $auth_user
        ,'sale_id' => $sale, 'status' => 'active'))->first();
        // ./ Applicants Activities in Quality

        // CRM Actvity
        $applicant_in_crm = Crm_note::join('applicants', 'crm_notes.applicant_id', '=', 'applicants.id')
            ->select("applicants.applicant_job_title","applicants.applicant_name","applicants.applicant_postcode","crm_notes.*")
            ->where(array('crm_notes.applicant_id' => $applicant, 'sales_id' => $sale, 'crm_notes.user_id' => $auth_user
            , 'crm_notes.status' => 'active'))->get();
        // ./CRM Actvity

        // Tract Applicant in CRM
        $track_applicant_in_crm = History::join('applicants', 'history.applicant_id', '=', 'applicants.id')
            ->select("applicants.applicant_name","applicants.applicant_job_title","applicants.applicant_postcode","history.*")->
            where(array('history.applicant_id' => $applicant, 'history.user_id' => $auth_user,'history.sale_id' => $sale
            , 'history.status' => 'active'))->first();
        // ./Tract Applicant in CRM
        return view('administrator.applicant.history.full_history',compact('applicant_in_quality',
            'applicant_in_crm','track_applicant_in_crm','applicant_name'));
    }
	

	
    public function getUploadApplicantCsv(Request $request){
        date_default_timezone_set('Europe/London');
        if ($request->file('applicant_csv') != null ){

            $file = $request->file('applicant_csv');

            // File Details
            $filename = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $tempPath = $file->getRealPath();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

            // Valid File Extensions
            $valid_extension = array("csv");

            // 2MB in Bytes
            $maxFileSize = 2097152;

            // Check file extension
            if(in_array(strtolower($extension),$valid_extension)){

                // Check file size
                if($fileSize <= $maxFileSize){

                    // File upload location
                    $location = 'uploads';

                    // Upload file
                    $file->move($location,$filename);

                    // Import CSV to Database
                    $filepath = public_path($location."/".$filename);

                    // Reading file
                    $file = fopen($filepath,"r");

                    $importData_arr = array();
                    $i = 0;

                    while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
                        $num = count($filedata );

                        // Skip first row (Remove below comment if you want to skip the first row)
                        if($i == 0){
                            $i++;
                            continue;
                        }
                        for ($c=0; $c < $num; $c++) {
                            $importData_arr[$i][] = $filedata [$c];
                        }
                        $i++;
                    }
                    fclose($file);

					//                    $audit_data = [];
					//                    $index = 1;

										// disable Applicant model events
					//                    $dispatcher = Applicant::getEventDispatcher();
					//                    Applicant::unsetEventDispatcher();

                    foreach($importData_arr as $importData){

                        $postcode = $importData[3];
                        $data_arr = $this->geocode($postcode);
                        $latitude = 00.000000;
                        $longitude = 00.000000;
                        if ($data_arr) {
                            $latitude = $data_arr[0];
                            $longitude = $data_arr[1];
                        }
                        $applicant = new Applicant();
                        $applicant->applicant_user_id = Auth::user()->id;
                        $applicant->applicant_job_title = $importData[0];
                        $applicant->applicant_name = $importData[1];
                        $applicant->applicant_email = $importData[2];
                        $applicant->applicant_postcode = $postcode;
                        $applicant->applicant_phone = $importData[4];
                        $applicant->applicant_homePhone = $importData[5];
                        $applicant->job_category = $importData[6];
                        $applicant->applicant_source = $importData[7];
                        $applicant->applicant_notes = $importData[8];
                        $applicant->applicant_added_date = date("jS F Y");
                        $applicant->applicant_added_time = date("h:i A");
                        $applicant->lat = $latitude;
                        $applicant->lng = $longitude;
                        $applicant->save();

                        // csv data for each applicant
//                        $csv_data =  'Name: ' . $applicant->applicant_name . ' | ';
//                        $csv_data .=  'Email: ' . $applicant->applicant_email . ' | ';
//                        $csv_data .=  'Job Title: ' . $applicant->applicant_job_title . ' | ';
//                        $csv_data .=  'Postcode: ' . $applicant->applicant_postcode . ' | ';
//                        $csv_data .=  'Phone: ' . $applicant->applicant_phone . ' | ';
//                        $csv_data .=  'Added Date: ' . $applicant->applicant_added_date . ' | ';
//                        $csv_data .=  'Added time: ' . $applicant->applicant_added_time;
//                        $audit_data[$index++] = $csv_data;

//                        $insertData = array(
//                            "applicant_user_id"=>$auth_user,

//                            "applicant_job_title"=>$importData[1],
//                            "applicant_name"=>$importData[2],
//                            "applicant_email"=>$importData[3],
//                            "applicant_postcode"=>$postcode,
//                            "applicant_phone"=>$importData[5],
//                            "applicant_homePhone"=>$importData[6],
//                            "job_category"=>$importData[7],
//                            "applicant_source"=>$importData[8],
//                            "applicant_notes"=>$importData[9],
//                            "applicant_added_date"=>date("jS F Y"),
//                            "applicant_added_time"=>date("h:i A"),
//                            "lat"=>$latitude,
//                            "lng"=>$longitude);
//                        DB::table('applicants')->updateOrInsert($insertData);
//                        Applicant::insertData($insertData);
                    }
                    // enable Applicant model events
//                    Applicant::setEventDispatcher($dispatcher);

//                    $applicant_observer = new ApplicantObserver();
//                    $applicant_observer->csvAudit($audit_data);
                    Session::flash('message','Import Successful.');
                }else{
                    Session::flash('message','File too large. File must be less than 2MB.');
                }

            }else{
                Session::flash('message','Invalid File Extension.');
            }
        }
        return redirect('applicants')->with('applicant_success_msg', 'Applicant Added Successfully');
    }
	public function UploadApplicantCV(Request $request)
    {
        date_default_timezone_set('Europe/London');
        $auth_user = Auth::user()->id;
        $applicant_id = $request->input('applicant_id');
        if ($request->hasFile('applicant_cv')) {
            $filenameWithExt = $request->file('applicant_cv')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('applicant_cv')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;
            $path = $request->file('applicant_cv')->move('uploads/', $fileNameToStore);
        } else {
            $path = 'old_image';

        }
           $result =  DB::table('applicants')->where('id', $applicant_id)->update([
			   'updated_cv' => $path,
			   'updated_at'=>Carbon::now(),
		       'temp_not_interested'=>'0',
               'is_no_job'=>'0',
               'no_response'=>'0',
               'is_blocked'=>'0',
		   ]);
           if($result)
           {
		  //Applicants_pivot_sales::where('applicant_id', $applicant_id)->delete();
            return redirect('applicants')->with('success', 'Applicant CV Updated Successfully');
           }
           else
           {
            return redirect('applicants')->with('error', 'Applicant CV Could Not Be Updated!');
           }

    }

    public function getNonInterestedApplicants(){
        // $not_interest_applicants = Applicants_pivot_sales::all();
        // $non_interest_results = array();
        // foreach($not_interest_applicants as $non_interest){
        //     $non_interest_results[] = Applicants_pivot_sales::join('applicants', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
        //         ->join('sales', 'sales.id', '=', 'applicants_pivot_sales.sales_id')
        //         ->join('notes_for_range_applicants', 'applicants_pivot_sales.id', '=', 'notes_for_range_applicants.applicants_pivot_sales_id')
        //         ->join('offices', 'offices.id', '=', 'sales.head_office')
        //         ->join('units', 'units.id', '=', 'sales.head_office_unit')
        //         ->select(
        //             'applicants.id','applicants.applicant_job_title','applicants.applicant_name','applicants.applicant_postcode','applicants.applicant_phone',
        //             'applicants.applicant_homePhone','applicants.job_category','applicants.applicant_source','applicants.applicant_notes',
        //             'sales.id as sale_id','sales.job_category as sale_job_category','sales.job_title','sales.postcode','sales.job_type','sales.timing','sales.salary',
        //             'sales.experience','sales.qualification','sales.benefits','sales.sale_added_date','sales.sale_added_time',
        //             'offices.office_name','units.unit_name','units.unit_postcode','units.contact_name','units.contact_phone_number','units.contact_landline',
        //             'units.contact_email','units.units_notes','units.units_added_date','units.units_added_time',
        //             'applicants_pivot_sales.interest_added_date',
        //             'applicants_pivot_sales.interest_added_time','notes_for_range_applicants.reason'
        //         )->where(['sales.status' => 'active','applicants.status' => 'active',
        //             'applicants_pivot_sales.applicant_id' => $non_interest->applicant_id
        //             , 'applicants_pivot_sales.sales_id' => $non_interest->sales_id])
        //         ->first();
        // }
        // $applicants_rejected = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
        //     ->where('applicants.status', 'active');
        // $applicants_rejected = $applicants_rejected->where('is_in_crm_reject', 'yes')
        //     ->orWhere('is_in_crm_request_reject', 'yes')
        //     ->orWhere('is_crm_interview_attended', 'no')
        //     ->orWhere('is_in_crm_start_date_hold', 'yes')
        //     ->orWhere('is_in_crm_dispute', 'yes')
        //     ->orWhere([['is_CV_reject', 'yes'], ["quality_notes.moved_tab_to", "rejected"]])
        //     ->select('applicants.id','quality_notes.sale_id')
        //     ->get();
        //        echo '<pre>';print_r($non_interest_results);exit;
        return view('administrator.resource.non_interested_applicants');
        /*,compact('non_interest_results','applicants_rejected')*/
    }
	
	public function exportNonInterestedLastApplicants()
    {
        $non_interest_results = Applicant::with('cv_notes')
            ->join('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
            ->join('sales', 'sales.id', '=', 'applicants_pivot_sales.sales_id')
            ->join('notes_for_range_applicants', 'applicants_pivot_sales.id', '=', 'notes_for_range_applicants.applicants_pivot_sales_id')
            ->join('offices', 'offices.id', '=', 'sales.head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
            ->select('applicants.applicant_phone', 'applicants.applicant_name', 		
					 'applicants.applicant_homePhone', 'applicants.applicant_job_title',
            		'applicants.applicant_postcode', 'applicants.applicant_source', 'applicants.applicant_notes'
            )->where('applicants.status', '=', 'active')->get();
        return Excel::download(new Applicants_nureses_7_days_export($non_interest_results), 'applicants.csv');

    }
	
    public function getNonInterestAppAjax()
    {
        $non_interest_results = Applicant::with('cv_notes')
            ->join('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
            ->join('sales', 'sales.id', '=', 'applicants_pivot_sales.sales_id')
            ->join('notes_for_range_applicants', 'applicants_pivot_sales.id', '=', 'notes_for_range_applicants.applicants_pivot_sales_id')
            ->join('offices', 'offices.id', '=', 'sales.head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
            ->select('applicants.id','applicants.applicant_job_title','applicants.applicant_name','applicants.applicant_postcode', 'applicants.applicant_phone',
                'applicants.applicant_homePhone','applicants.job_category','applicants.applicant_source','applicants.paid_status',
                'sales.id as sale_id','sales.job_category as sale_job_category','sales.job_title','sales.postcode','sales.job_type',
                'sales.timing','sales.salary', 'sales.experience','sales.qualification','sales.benefits','sales.sale_added_date',
                'offices.office_name','units.unit_name','notes_for_range_applicants.reason',
                'applicants_pivot_sales.interest_added_date', 'applicants_pivot_sales.interest_added_time'
            )->where('applicants.status', '=', 'active')
			 ->where('applicants.is_no_job', '=', '0')
            ->where('applicants.temp_not_interested', '=', 1);

            return Datatables::of($non_interest_results)
                
                ->addColumn("job_details",function($non_interest_result){

                        $content = "";
                        $content .= '<a href="#" class="btn bg-teal legitRipple"
                                        data-controls-modal="#job_details'.$non_interest_result['id'].'"
                                                   data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                   data-target="#job_details'.$non_interest_result['id'].'">VIEW</a>';

                            
                            // Job Details Modal
                        $content .= '<div id="job_details'.$non_interest_result['id'].'" class="modal fade" tabindex="-1">';
                        $content .= '<div class="modal-dialog modal-lg">';
                        $content .= '<div class="modal-content">';
                        $content .= '<div class="modal-header">';
                        $content .= '<h5 class="modal-title">'.$non_interest_result['applicant_name'].'s Job Details</h5>';
                        $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                        $content .= '</div>';
                        $content .= '<div class="modal-body">';
                        $content .= '<div class="media flex-column flex-md-row mb-4">';
                        $content .= '<div class="media-body">';
                        $content .= '<h5 class="media-title font-weight-semibold">'.$non_interest_result['office_name'].'/'.$non_interest_result['unit_name'].'</h5>';
                        $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                        $content .= '<li class="list-inline-item">'.$non_interest_result['job_category'].','.$non_interest_result['job_title'].'</li>';
                        $content .= '</ul>';
                        $content .= '</div>';
                        $content .= '</div>';
                        $content .= '<div class="row">';
                        $content .= '<div class="col-3">';
                        $content .= '</div>';
                        $content .= '<div class="col-3">';
                        $content .= '<h6 class="font-weight-semibold">Job Title:</h6>';
                        $content .= '<p>'.$non_interest_result['job_title'].'</p>';
                        $content .= '</div>';
                        $content .= '<div class="col-3"></div>';
                        $content .= '<div class="col-3">';
                        $content .= '<h6 class="font-weight-semibold">Postcode:</h6>';
                        $content .= '<p class="mb-3">'.$non_interest_result['postcode'].'</p>';
                        $content .= '</div>';
                        $content .= '<div class="col-3"></div>';
                        $content .= '<div class="col-3">';
                        $content .= '<h6 class="font-weight-semibold">Job Type:</h6>';
                        $content .= '<p class="mb-3">'.$non_interest_result['job_type'].'</p>';
                        $content .= '</div>';
                        $content .= '<div class="col-3"></div>';
                        $content .= '<div class="col-3">';
                        $content .= '<h6 class="font-weight-semibold">Timings:</h6>';
                        $content .= '<p class="mb-3">'.$non_interest_result['timing'].'</p>';
                        $content .= '</div>';
                        $content .= '<div class="col-3"></div>';
                        $content .= '<div class="col-3">';
                        $content .= '<h6 class="font-weight-semibold">Salary:</h6>';
                        $content .= '<p class="mb-3">'.$non_interest_result['salary'].'</p>';
                        $content .= '</div>';
                        $content .= '<div class="col-3"></div>';
                        $content .= '<div class="col-3">';
                        $content .= '<h6 class="font-weight-semibold">Experience:</h6>';
                        $content .= '<p class="mb-3">'.$non_interest_result['experience'].'</p>';
                        $content .= '</div>';
                        $content .= '<div class="col-3"></div>';
                        $content .= '<div class="col-3">';
                        $content .= '<h6 class="font-weight-semibold">Qualification:</h6>';
                        $content .= '<p class="mb-3">'.$non_interest_result['qualification'].'</p>';
                        $content .= '</div>';
                        $content .= '<div class="col-3"></div>';
                        $content .= '<div class="col-3">';
                        $content .= '<h6 class="font-weight-semibold">Benefits:</h6>';
                        $content .= '<p class="mb-3">'.$non_interest_result['benefits'].'</p>';
                        $content .= '</div>';
                        $content .= '<div class="col-3"></div>';
                        $content .= '<div class="col-3">';
                        $content .= '<h6 class="font-weight-semibold">Posted Date:</h6>';
                        $content .= '<p class="mb-3">'.$non_interest_result['sale_added_date'].'</p>';
                        $content .= '</div>';
                        $content .= '</div>';
                        $content .= '</div>';
                        $content .= '<div class="modal-footer">';
                        $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                        $content .= '</div>';
                        $content .= '</div>';
                        $content .= '</div>';
                        $content .= '</div>';
                             // /Job Details Modal
                        return $content;
                      })
                ->addColumn('applicant_postcode',function($non_interest_result){
                    if ($non_interest_result['paid_status'] == 'close') {
                        return $non_interest_result['applicant_postcode'];
                    } else {
                        foreach ($non_interest_result['cv_notes'] as $key => $value) {
                            if ($value['status'] == 'active') {
                                return $non_interest_result['applicant_postcode'];
                            }
                        }
                        return '<a href="/available-jobs/' . $non_interest_result['id'] . '">' . $non_interest_result['applicant_postcode'] . '</a>';
                    }
                })
                ->addColumn('history', function ($non_interest_result) {
                    $content = '';
                    $content .= '<a href="#" class="reject_history" data-applicant="'.$non_interest_result->id.'"; 
                                 data-controls-modal="#reject_history'.$non_interest_result['id'].'" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $non_interest_result->id . '">History</a>';

                    $content .= '<div id="reject_history'.$non_interest_result->id.'" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">'.$non_interest_result['applicant_name'].'</span></h6>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<div class="modal-body" id="applicant_rejected_history'.$non_interest_result->id.'" style="max-height: 500px; overflow-y: auto;">';

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
				 ->addColumn('checkbox', function ($non_interest_result) {
                    return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $non_interest_result->id . '"/>';
                })
                ->setRowClass(function ($non_interest_result) {
                    $row_class = '';
                    if ($non_interest_result->paid_status == 'close') {
                        $row_class = 'class_dark';
                    } else { /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
                        foreach ($non_interest_result->cv_notes as $key => $value) {
                            if ($value->status == 'active') {
                                $row_class = 'class_success'; // status: sent
                                break;
                            } elseif ($value->status == 'disable') {
                                $row_class = 'class_danger'; // status: reject
                            }
                        }
                    }
                    return $row_class;
                })
                ->rawColumns(['job_details','applicant_postcode', 'history','checkbox'])
                ->make(true);
    }
	
	//    public function getApplicantSendToNurseHome(){
	//        date_default_timezone_set('Europe/London');
	//        $details = request()->details;
	//        $sale_id = request()->job_hidden_id;
	//        $applicant_id = request()->applicant_hidden_id;
	//        $user = Auth::user();
	//        $current_user_id = $user->id;
	//        $quality_notes = new Quality_notes();
	//        $quality_notes->applicant_id = $applicant_id;
	//        $quality_notes->user_id = $current_user_id;
	//        $quality_notes->sale_id = $sale_id;
	//        $quality_notes->details = $details;
	//        $quality_notes->quality_added_date = date("jS F Y");
	//        $quality_notes->quality_added_time = date("h:i A");
	//        $quality_notes->moved_tab_to = "no_nursing_home";
	//        $quality_notes->save();
	//        $last_inserted_note = $quality_notes->id;
	//        if ($last_inserted_note > 0) {
	//            $quality_note_uid = md5($last_inserted_note);
	//            Quality_notes::where('id', $last_inserted_note)->update(['quality_notes_uid' => $quality_note_uid]);
	//            return redirect()->back();
	//            }
	//            else {
	//                return redirect()->back();
	//            }
	//        }
	//    }
	
	//WITHIN CONTROLLER FUNCTION DEFINITION
    function geocode($address)
	{
		// URL-encode the address to ensure it works properly in the URL
		$address = urlencode($address);

		// Retrieve the API key from configuration
		$postcode_api = config('app.postcode_api');

		// Construct the URL for the Google Maps Geocoding API
		$url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$postcode_api}";

		// Retrieve the JSON response from the API
		$resp_json = file_get_contents($url);
		$resp = json_decode($resp_json, true);

		// Check the response status
		if (isset($resp['status'])) {
			switch ($resp['status']) {
				case 'OK':
					// Extract latitude and longitude if available
					$lati = $resp['results'][0]['geometry']['location']['lat'] ?? "";
					$longi = $resp['results'][0]['geometry']['location']['lng'] ?? "";

					// Verify if data is complete
					if ($lati && $longi) {
						return [$lati, $longi];
					} else {
						echo "<strong>ERROR: Incomplete geolocation data</strong>";
						return false;
					}

				case 'REQUEST_DENIED':
					echo "<strong>ERROR: Request denied. Please check your API key and permissions.</strong>";
					return false;

				case 'ZERO_RESULTS':
					echo "<strong>ERROR: No results found for the provided address.</strong>";
					return false;

				case 'OVER_QUERY_LIMIT':
					echo "<strong>ERROR: You have exceeded your daily request quota for the API.</strong>";
					return false;

				case 'INVALID_REQUEST':
					echo "<strong>ERROR: Invalid request. Please check the address parameter.</strong>";
					return false;

				default:
					echo "<strong>ERROR: An unexpected error occurred ({$resp['status']}).</strong>";
					return false;
			}
		} else {
			echo "<strong>ERROR: Unable to connect to the API.</strong>";
			return false;
		}
	}

    public function updateHistory(Request $request)
    {
        $input = $request->all();
        $input['module'] = filter_var($request->input('module'), FILTER_SANITIZE_STRING);
        $request->replace($input);

        $validator = Validator::make($request->all(), [
            'module' => "required|in:Applicant",
            'module_key' => "required"
        ])->validate();

        $model_class = 'Horsefly\\' . $request->input('module');
        $model = $model_class::find($request->input('module_key'));
        $audits = $model->audits;
        $audit_data = []; $index = 0;
        foreach ($audits as $audit) {
            if (!empty($audit->data['changes_made'])) {

                $audit_data[$index]['changes_made'] = $audit->data['changes_made'];
                $audit_data[$index++]['changes_made_by'] = User::find($audit->user_id)->name;
            }
        }
        $audit_data = array_reverse($audit_data);

        $update_modal_body = view('administrator.partial.applicant_update_history', compact('audit_data', 'audits'))->render();
        return $update_modal_body;
    }
	
//    public function getNoNiursingApplicantsNotes($applicant_id,$job_id){}

    public function revertApplicants(Request $request)
    {
        date_default_timezone_set('Europe/London');
        $details = $request->input('details');
        $action = $request->input('action');
        $selected_applicants = explode(',', $request->input('selectedApplicants'));

        $user = Auth::user();
        $data = [];
        $date = date("jS F Y");
        $time = date("h:i A");

        $html =  '<div class="alert alert-danger border-0 alert-dismissible">
                     <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                     <span class="font-weight-semibold"></span> WHOOPS! You are not authorized to perform this action!!
                 </div>';
        $applicant_column = '';
        $moved_tab_to = [];
        if ($action == 'revert-callbacks') {
            if ($user->hasPermissionTo('resource_Potential-Callback_revert-callback')) {
                $action = 'revert_callback';
                $applicant_column = 'is_callback_enable';
                $moved_tab_to = ['callback','revert_callback'];
            } else {
                echo $html;
                return;
            }
        } elseif ($action == 'revert-no-nursing-home') {
            if ($user->hasPermissionTo('resource_No-Nursing-Home_revert-no-nursing-home')) {
                $action = 'revert_no_nursing_home';
                $applicant_column = 'is_in_nurse_home';
                $moved_tab_to = ['no_nursing_home','revert_no_nursing_home'];
            } else {
                echo $html;
                return;
            }
        }
        foreach ($selected_applicants as $applicant_id) {
            $data[] = [
                "user_id"       => $user->id,
                "applicant_id"  => $applicant_id,
                "added_date"    => $date,
                "added_time"    => $time,
                "details"       => $details,
                "moved_tab_to"  => $action,
                "status"        => "active",
                "updated_at"    => Carbon::now()
            ];
        }
        ApplicantNote::whereIn('applicant_id', $selected_applicants)
            ->whereIn('moved_tab_to', $moved_tab_to)
            ->update(['status' => 'disable']);
        Applicant::whereIn('id', $selected_applicants)->update([$applicant_column => 'no']);
        \Illuminate\Support\Facades\DB::table('applicant_notes')->insert($data);
//        ApplicantNote::insert($data);

        $html = '<div class="alert alert-success border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold"></span> Applicants reverted successfully!!
                </div>';
        echo $html;
    }
	public function idle()
    {
        return view('administrator.applicant.idle_applicants');

    }
	public function idleNonNurse()
    {
        return view('administrator.applicant.idle_applicants_non_nurse');
        
    }
	public function idleNonNurseSpecialist()
    {
        return view('administrator.applicant.idle_applicants_non_nurse_specialist');
    }
	
	public function getNoJobApplicants()
    {
        
        $applicants_rejected = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
            ->where('applicants.status', 'active');
        $applicants_rejected = $applicants_rejected->where('is_in_crm_reject', 'yes')
            ->orWhere('is_in_crm_request_reject', 'yes')
            ->orWhere('is_crm_interview_attended', 'no')
            ->orWhere('is_in_crm_start_date_hold', 'yes')
            ->orWhere('is_in_crm_dispute', 'yes')
            ->orWhere([['is_CV_reject', 'yes'], ["quality_notes.moved_tab_to", "rejected"]])
            ->get();

        $applicants_accepted = Applicant::where('status', 'active');
        $applicants_accepted = $applicants_accepted->where('is_interview_confirm', 'yes')
            ->orWhere('is_in_crm_request', 'yes')
            ->orWhere('is_crm_request_confirm', 'yes')
            ->orWhere('is_crm_interview_attended', 'yes')
            ->orWhere('is_in_crm_start_date', 'yes')
            ->orWhere('is_in_crm_invoice', 'yes')
            ->orWhere('is_in_crm_start_date_hold', 'yes')
            ->orWhere('is_in_crm_paid', 'yes')
            ->orWhere('is_in_crm_dispute', 'yes')
            ->orWhere('is_cv_in_quality', 'yes')
            ->get();
            // print_r($applicants_rejected);exit();
        $today = Carbon::today();

            $custom_data['sdate'] = $start_date = new Carbon('2019-06-27 00:00:00');
        $custom_data['edate'] = $end_date = $today->copy()->endOfDay();
        return view('administrator.resource.no_job_applicants', compact('applicants_rejected', 'applicants_accepted','custom_data'));
    }

    public function getNoJobApplicantsAjax()
    {
        $auth_user = Auth::user();
        $raw_columns = ['applicant_notes','created_at'];
        $datatable = datatables()->of(Applicant::with('cv_notes')->where('is_no_job', 1)->orderBy('updated_at', 'DESC'))
       ->addColumn('applicant_postcode', function ($applicants) {
            $status_value = 'open';
            $postcode = '';
            if ($applicants->paid_status == 'close') {
                $status_value = 'paid';
            } else {
                foreach ($applicants->cv_notes as $key => $value) {
                    if ($value->status == 'active') {
                        $status_value = 'sent';
                        break;
                    } elseif ($value->status == 'disable') {
                        $status_value = 'reject';
                    }
                }
            }
            if ($status_value == 'open' || $status_value == 'reject') {
				 $postcode .= '<a href="/available-no-jobs/'.$applicants->id.'">';
                $postcode .= strtoupper($applicants->applicant_postcode);
                $postcode .= '</a>';
            } else {
                $postcode .= strtoupper($applicants->applicant_postcode);
            }
            return $postcode;
        })
                ->addColumn('applicant_notes', function($applicants){

                    $app_new_note = ModuleNote::where(['module_noteable_id' =>$applicants->id, 'module_noteable_type' =>'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                    $app_notes_final='';
                    if($app_new_note){
                        $app_notes_final = $app_new_note->details;

                    }
                    else{
                        $app_notes_final = $applicants->applicant_notes;
                    }

                $status_value = 'open';
                $postcode = '';
                if ($applicants->paid_status == 'close') {
                    $status_value = 'paid';
                } else {
                    foreach ($applicants->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $status_value = 'sent';
                            break;
                        } elseif ($value->status == 'disable') {
                            $status_value = 'reject';
                        }
                    }
                }
                   
                if($applicants->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject')
                {
                    
                $content = '';
                // if ($status_value == 'open' || $status_value == 'reject'){

                $content .= '<a href="#" class="reject_history" data-applicant="'.$applicants->id.'"
                                 data-controls-modal="#clear_cv'.$applicants->id.'"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicants->id . '">"'.$app_notes_final.'"</a>';
                $content .= '<div id="clear_cv' . $applicants->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Notes</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<form action="' . route('no_job_to_applicant') . '" method="POST" id="app_notes_form' . $applicants->id . '" class="form-horizontal">';
                $content .= csrf_field();
                $content .= '<div class="modal-body">';
                $content .='<div id="app_notes_alert' . $applicants->id . '"></div>';
                $content .= '<div id="sent_cv_alert' . $applicants->id . '"></div>';
                $content .= '<div class="form-group row">';
                $content .= '<label class="col-form-label col-sm-3">Details</label>';
                $content .= '<div class="col-sm-9">';
                $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicants->id . '">';
                $content .= '<input type="hidden" name="applicant_page' . $applicants->id . '" value="applicants">';
                $content .= '<textarea name="details" id="sent_cv_details' . $applicants->id .'" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                $content .= '</div>';
                $content .= '</div>';
                    

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                   
                    $content .= '<button type="button" class="btn bg-dark legitRipple sent_cv_submit" data-dismiss="modal">Close</button>';
                    
                    $content .= '<button type="submit" data-note_key="' . $applicants->id . '" value="cv_sent_save" class="btn bg-teal legitRipple sent_cv_submit app_notes_form_submit" disabled>Revert Applicant</button>';

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

				});
        if ($auth_user->hasAnyPermission(['applicant_edit','applicant_view','applicant_history','applicant_note-create','applicant_note-history'])) {
            $datatable = $datatable->addColumn('action', function ($applicants) use ($auth_user) {
                $action =
                    '<div class="list-icons">
                                <div class="dropdown">
                                <a href="#" class=list-icons-item" data-toggle="dropdown">
                                    <i class="icon-menu9"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasPermissionTo('applicant_edit')) {
                    $action .= '<a href="' . route('applicants.edit', $applicants->id) . '" class="dropdown-item"> Edit</a>';
                }
                if ($auth_user->hasPermissionTo('applicant_view')) {
                    $action .= '<a href="' . route('applicants.show', $applicants->id) . '" class="dropdown-item"> View </a>';
                }
                if ($auth_user->hasPermissionTo('applicant_history')) {
                    $action .= '<a href="' . route('applicantHistory', $applicants->id) . '" class="dropdown-item"> History</a>';
                }
                if ($auth_user->hasPermissionTo('applicant_note-create')) {
                    $action .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#add_applicant_note' . $applicants->id . '"
                                           data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#add_applicant_note' . $applicants->id . '">
                                           Add Note</a>';
                }
                if ($auth_user->hasPermissionTo('applicant_note-history')) {
                    $action .= '<a href="#" class="dropdown-item notes_history" data-applicant="' . $applicants->id . '" data-controls-modal="#notes_history' . $applicants->id . '"
                                           data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#notes_history' . $applicants->id . '"
                                        > Notes History </a>';
                }
                $action .=
                            '</div>
                        </div>
                    </div>';
                if ($auth_user->hasPermissionTo('applicant_note-create')) {
                    $action .=
                        '<div id="add_applicant_note' . $applicants->id . '" class="modal fade" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Add Applicant Note</h5>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <form action="' . route('module_note.store') . '" method="POST" class="form-horizontal" id="note_form' . $applicants->id . '">
                                        <input type="hidden" name="_token" value="' . csrf_token() . '">
                                        <input type="hidden" name="module" value="Applicant">
                                        <div class="modal-body">
                                            <div id="note_alert' . $applicants->id . '"></div>
                                            <div class="form-group row">
                                                <label class="col-form-label col-sm-3">Details</label>
                                                <div class="col-sm-9">
                                                    <input type="hidden" name="module_key" value="' . $applicants->id . '">
                                                    <textarea name="details" id="note_details' . $applicants->id . '" class="form-control" cols="30" rows="4"
                                                              placeholder="TYPE HERE .." required></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">
                                                Close
                                            </button>
                                            <button type="submit" data-note_key="' . $applicants->id . '" class="btn bg-teal legitRipple note_form_submit">Save</button>
                                        </div>
                                    </form>
                                    
                                </div>
                            </div>
                        </div>';
                }
               
                if ($auth_user->hasPermissionTo('applicant_note-history')) {
                    $action .= '<div id="notes_history' . $applicants->id . '" class="modal fade" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Applicant Notes History - 
                                        <span class="font-weight-semibold">' . $applicants->applicant_name . '</span></h5>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body" id="applicants_notes_history' . $applicants->id . '" style="max-height: 500px; overflow-y: auto;">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                        </button>
                                    </div>
                                    
                                </div>
                            </div>
                          </div>';
                }
                return $action;
            });
            $raw_columns = ['applicant_notes','created_at','action'];
        }
           $datatable= $datatable->addColumn('agent_name',function ($row){
            $name=ModuleNote::where('module_noteable_id',$row->id)->orderBy('id','desc')->first();
            $agent_name= User::find($name->user_id);
            return ucfirst($agent_name->name);
        });
            $datatable = $datatable->addColumn('download', function ($applicants) {
                return
                    '<a href="' . route('downloadApplicantCv', $applicants->id) . '">
                       <i class="fas fa-file-download text-teal-400" style="font-size: 30px;"></i>
                    </a>';
            });
            array_push($raw_columns, 'download');

            $datatable = $datatable->addColumn('updated_cv', function ($applicants) {
                return
                    '<a href="' . route('downloadUpdatedApplicantCv', $applicants->id) . '">
                       <i class="fas fa-file-download text-teal-400" style="font-size: 30px;"></i>
                    </a>';
            });
            array_push($raw_columns, 'updated_cv');

           /*  $datatable = $datatable->addColumn('upload', function ($applicants) {
                return
                '<a href="#"
                data-controls-modal="#import_applicant_cv" class="import_cv"
                data-backdrop="static"
                data-keyboard="false" data-toggle="modal" data-id="'.$applicants->id.'"
                data-target="#import_applicant_cv">
                 <i class="fas fa-file-upload text-teal-400" style="font-size: 30px;"></i>
                 &nbsp;</a>';
            });
            array_push($raw_columns, 'upload'); ***/ 
            $datatable = $datatable->editColumn('applicant_job_title', function ($applicants) {
                if($applicants->applicant_job_title == 'nurse specialist' || $applicants->applicant_job_title == 'nonnurse specialist')
                {
                    $selected_prof_data = Specialist_job_titles::select("specialist_prof")->where("id", $applicants->job_title_prof)->first();
                    $spec_job_title = ($applicants->job_title_prof!='')?$applicants->applicant_job_title.' ('.$selected_prof_data->specialist_prof.')':$applicants->applicant_job_title;
                    return $spec_job_title;
                }
                else
                {
                    return $applicants->applicant_job_title;
                }
    
         })
			->setRowClass(function ($applicants) {
            $row_class = '';
             $histories=History::where('applicant_id',$applicants->id)->whereIn("history.sub_stage", ['crm_request_no_job_save','quality_cleared_no_job'])
                 ->where('status','active')->orderBY('id','desc')->first();
             if ($histories){
                 if ($applicants->paid_status == 'close') {
                 $row_class = 'class_dark';
             }else{
                     $row_class = 'class_success';
                 }
             }else{
                 $histories_reject=History::where('applicant_id',$applicants->id)->whereIn("history.sub_stage", ['crm_request_no_job_reject','crm_no_job_reject'])
                     ->where('status','active')->orderBY('id','desc')->first();
                 if ($histories_reject) {
                     if ($applicants->paid_status == 'close') {
                         $row_class = 'class_dark';
                     }else {

                         $row_class = 'class_danger';
                     }
                 }else{
                     if ($applicants->paid_status == 'close') {
                         $row_class = 'class_dark';
                     }else {

                         $row_class = '';
                     }
                 }
             }

            return $row_class;
        });
            array_push($raw_columns, 'applicant_job_title','applicant_postcode');

        $datatable = $datatable->addColumn('checkbox', function ($applicants) {
        return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicants->id . '"/>';
    });
        array_push($raw_columns, 'checkbox');
        $datatable = $datatable->addColumn("created_at",function($applicants){
                    $created_at = new DateTime($applicants->updated_at);
                    return DATE_FORMAT($created_at, "d M Y");
                })->rawColumns($raw_columns)
                ->make(true);
        return $datatable;
    }
	
	public function getNoResponsedApplicants()
    {
        
        return view('administrator.resource.no_responsed_applicants');
    }
    
    public function getNoResponsedApplicantsAjax()
    {
        $auth_user = Auth::user();
        $raw_columns = ['applicant_notes','created_at'];
        $applicants = Applicant::where('no_response', 1)
        ->where('is_follow_up','2')
        ->orderBy('updated_at', 'DESC');

        $datatable = datatables()->of($applicants)
            ->addColumn('applicant_postcode', function ($applicants) {
                $status_value = 'open';
                $postcode = '';
                if ($applicants->paid_status == 'close') {
                    $status_value = 'paid';
                } else {
                    foreach ($applicants->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $status_value = 'sent';
                            break;
                        } elseif ($value->status == 'disable') {
                            $status_value = 'reject';
                        }
                    }
                }
                if ($status_value == 'open' || $status_value == 'reject') {
                    $postcode .= '<a href="/available-no-jobs/'.$applicants->id.'">';
                    $postcode .= $applicants->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicants->applicant_postcode;
                }
                return $postcode;
            })
            ->addColumn('applicant_notes', function($applicants){

                    $app_new_note = ModuleNote::where(['module_noteable_id' =>$applicants->id, 'module_noteable_type' =>'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                    $app_notes_final='';
                    if($app_new_note){
                        $app_notes_final = $app_new_note->details;

                    }
                    else{
                        $app_notes_final = $applicants->applicant_notes;
                    }

                    $status_value = 'open';
                    $postcode = '';
                    if ($applicants->paid_status == 'close') {
                        $status_value = 'paid';
                    } else {
                        foreach ($applicants->cv_notes as $key => $value) {
                            if ($value->status == 'active') {
                                $status_value = 'sent';
                                break;
                            } elseif ($value->status == 'disable') {
                                $status_value = 'reject';
                            }
                        }
                    }
                    
                    if($applicants->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject')
                    {
                        
                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="'.$applicants->id.'"
                                    data-controls-modal="#clear_cv'.$applicants->id.'"
                                    data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                    data-target="#clear_cv' . $applicants->id . '">"'.$app_notes_final.'"</a>';
                    $content .= '<div id="clear_cv' . $applicants->id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('no_job_to_applicant') . '" method="POST" id="app_notes_form' . $applicants->id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .='<div id="app_notes_alert' . $applicants->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicants->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicants->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicants->id . '" value="applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicants->id .'" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                        

                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    
                        $content .= '<button type="button" class="btn bg-dark legitRipple sent_cv_submit" data-dismiss="modal">Close</button>';
                        
                        $content .= '<button type="submit" data-note_key="' . $applicants->id . '" value="cv_sent_save" class="btn bg-teal legitRipple sent_cv_submit app_notes_form_submit" disabled>Revert Applicant</button>';

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

            });
            if ($auth_user->hasAnyPermission(['applicant_edit','applicant_view','applicant_history','applicant_note-create','applicant_note-history'])) {
                $datatable = $datatable->addColumn('action', function ($applicants) use ($auth_user) {
                    $action =
                        '<div class="list-icons">
                                    <div class="dropdown">
                                    <a href="#" class=list-icons-item" data-toggle="dropdown">
                                        <i class="icon-menu9"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">';
                    if ($auth_user->hasPermissionTo('applicant_edit')) {
                        $action .= '<a href="' . route('applicants.edit', $applicants->id) . '" class="dropdown-item"> Edit</a>';
                    }
                    if ($auth_user->hasPermissionTo('applicant_view')) {
                        $action .= '<a href="' . route('applicants.show', $applicants->id) . '" class="dropdown-item"> View </a>';
                    }
                    if ($auth_user->hasPermissionTo('applicant_history')) {
                        $action .= '<a href="' . route('applicantHistory', $applicants->id) . '" class="dropdown-item"> History</a>';
                    }
                    if ($auth_user->hasPermissionTo('applicant_note-create')) {
                        $action .= '<a href="#" class="dropdown-item"
                                            data-controls-modal="#add_applicant_note' . $applicants->id . '"
                                            data-backdrop="static"
                                            data-keyboard="false" data-toggle="modal"
                                            data-target="#add_applicant_note' . $applicants->id . '">
                                            Add Note</a>';
                    }
                    if ($auth_user->hasPermissionTo('applicant_note-history')) {
                        $action .= '<a href="#" class="dropdown-item notes_history" data-applicant="' . $applicants->id . '" data-controls-modal="#notes_history' . $applicants->id . '"
                                            data-backdrop="static"
                                            data-keyboard="false" data-toggle="modal"
                                            data-target="#notes_history' . $applicants->id . '"
                                            > Notes History </a>';
                    }
                    $action .=
                                '</div>
                            </div>
                        </div>';
                    if ($auth_user->hasPermissionTo('applicant_note-create')) {
                        $action .=
                            '<div id="add_applicant_note' . $applicants->id . '" class="modal fade" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Add Applicant Note</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <form action="' . route('module_note.store') . '" method="POST" class="form-horizontal" id="note_form' . $applicants->id . '">
                                            <input type="hidden" name="_token" value="' . csrf_token() . '">
                                            <input type="hidden" name="module" value="Applicant">
                                            <div class="modal-body">
                                                <div id="note_alert' . $applicants->id . '"></div>
                                                <div class="form-group row">
                                                    <label class="col-form-label col-sm-3">Details</label>
                                                    <div class="col-sm-9">
                                                        <input type="hidden" name="module_key" value="' . $applicants->id . '">
                                                        <textarea name="details" id="note_details' . $applicants->id . '" class="form-control" cols="30" rows="4"
                                                                placeholder="TYPE HERE .." required></textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">
                                                    Close
                                                </button>
                                                <button type="submit" data-note_key="' . $applicants->id . '" class="btn bg-teal legitRipple note_form_submit">Save</button>
                                            </div>
                                        </form>
                                        
                                    </div>
                                </div>
                            </div>';
                    }
                
                    if ($auth_user->hasPermissionTo('applicant_note-history')) {
                        $action .= '<div id="notes_history' . $applicants->id . '" class="modal fade" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Applicant Notes History - 
                                            <span class="font-weight-semibold">' . $applicants->applicant_name . '</span></h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body" id="applicants_notes_history' . $applicants->id . '" style="max-height: 500px; overflow-y: auto;">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                            </button>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>';
                    }
                    return $action;
                });
                $raw_columns = ['applicant_notes','created_at','action'];
            }
            $datatable= $datatable->addColumn('agent_name',function ($row){
                $name=ModuleNote::where('module_noteable_id',$row->id)->orderBy('id','desc')->first();
                $agent_name= User::find($name->user_id);
                return ucfirst($agent_name->name);
            });
            $datatable = $datatable->addColumn('download', function ($applicants) {
                return
                    '<a href="' . route('downloadApplicantCv', $applicants->id) . '">
                    <i class="fas fa-file-download text-teal-400" style="font-size: 30px;"></i>
                    </a>';
            });
            array_push($raw_columns, 'download');

            $datatable = $datatable->addColumn('updated_cv', function ($applicants) {
                return
                    '<a href="' . route('downloadUpdatedApplicantCv', $applicants->id) . '">
                    <i class="fas fa-file-download text-teal-400" style="font-size: 30px;"></i>
                    </a>';
            });
            array_push($raw_columns, 'updated_cv');

            $datatable = $datatable->editColumn('applicant_job_title', function ($applicants) {
                if($applicants->applicant_job_title == 'nurse specialist' || $applicants->applicant_job_title == 'nonnurse specialist')
                {
                    $selected_prof_data = Specialist_job_titles::select("specialist_prof")->where("id", $applicants->job_title_prof)->first();
                    $spec_job_title = ($applicants->job_title_prof!='')?$applicants->applicant_job_title.' ('.$selected_prof_data->specialist_prof.')':$applicants->applicant_job_title;
                    return $spec_job_title;
                }
                else
                {
                    return $applicants->applicant_job_title;
                }
        
            })
            ->setRowClass(function ($applicants) {
                $row_class = '';
                $histories=History::where('applicant_id',$applicants->id)->whereIn("history.sub_stage", ['crm_request_no_job_save','quality_cleared_no_job'])
                    ->where('status','active')->orderBY('id','desc')->first();
                if ($histories){
                    if ($applicants->paid_status == 'close') {
                    $row_class = 'class_dark';
                }else{
                        $row_class = 'class_success';
                    }
                }else{
                    $histories_reject=History::where('applicant_id',$applicants->id)->whereIn("history.sub_stage", ['crm_request_no_job_reject','crm_no_job_reject'])
                        ->where('status','active')->orderBY('id','desc')->first();
                    if ($histories_reject) {
                        if ($applicants->paid_status == 'close') {
                            $row_class = 'class_dark';
                        }else {

                            $row_class = 'class_danger';
                        }
                    }else{
                        if ($applicants->paid_status == 'close') {
                            $row_class = 'class_dark';
                        }else {

                            $row_class = '';
                        }
                    }
                }

                return $row_class;
            });
            array_push($raw_columns, 'applicant_job_title','applicant_postcode');

        $datatable = $datatable->addColumn('checkbox', function ($applicants) {
            return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicants->id . '"/>';
        });
        array_push($raw_columns, 'checkbox');
        $datatable = $datatable->addColumn("created_at",function($applicants){
                    $created_at = new DateTime($applicants->updated_at);
                    return DATE_FORMAT($created_at, "d M Y");
                })->rawColumns($raw_columns)
                ->make(true);
        return $datatable;
    }

	public function emailTemplates() 
    {
        $query = EmailTemplate::get();
        if($query){
            $dataQuery = $query->where('title','generic_email')->first();
            $data = $dataQuery ? $dataQuery->template : 'No template found';
            $randomDataQuery = $query->where('title','random_email')->first();
            $randomData = $randomDataQuery ? $randomDataQuery->template : 'No template found';
        }else{
            $data = 'No template found';
            $randomData = 'No template found';
        }
		$genericUnsentCount = SentEmail::where('action_name','Generic Email')->where('status','0')->count();
        $randomUnsentCount = SentEmail::where('action_name','Random Email')->where('status','0')->count();
		$randomUnsentFailed = SentEmail::where('action_name','Random Email')->where('status','2')->get();
        return view('administrator.emails.generic_email', compact('data','randomData','randomUnsentCount','genericUnsentCount','randomUnsentFailed'));
    }
    

    public function sendAppGenEmail(Request $request)
    {
       $email = $request->input('app_email');
       $subject = $request->input('email_title');
       $body = $request->input('email_body');
  
        $mailData = [
            'subject' => $subject,
            'body' => $body
        ];
  
        Mail::to($email)->send(new GenericEmail($mailData));
   
        if (Mail::failures()) {
            return Response::json(['error' => 'Error msg'], 404);
        }
        else
        {
			 $email_from = 'info@kingsburypersonnel.com';
            $email_sent_to_cc ='';
            $action_name = 'Generic Email';
            $dbSaveEmail = $this->saveSentEmails($email, $email_sent_to_cc, $email_from, $subject, $body, $action_name);
            return response()->json(['success'=> 'success']);
        }
    }
	
	public function sendAppRandomEmail(Request $request)
	{
		// Validate the input data
		$request->validate([
			'app_email' => 'required',
			'email_title' => 'required',
			'email_body' => 'required',
		]);

		$email = $request->input('app_email');
		$subject = $request->input('email_title');
		$content = $request->input('email_body');

		$dom = new \DomDocument();
		$dom->loadHtml($content, LIBXML_HTML_NODEFDTD);
		$imageFile = $dom->getElementsByTagName('img');

		foreach ($imageFile as $item => $image) {
			$data = $image->getAttribute('src');
			if (strpos($data, 'data:image') === 0) { // Check if it's a Base64 image
				list($type, $data) = explode(';', $data);
				list(, $data) = explode(',', $data);
				$imageData = base64_decode($data);

				// Generate the image name and path
				$imageName = time() . $item . '.png';
				$path = public_path('email_uploads/' . $imageName);
				$publicUrl = url('email_uploads/' . $imageName);

				// Save the image to the server
				if (file_put_contents($path, $imageData)) {
					// Replace the 'src' attribute with the public URL
					$image->setAttribute('src', $publicUrl);
				} else {
					\Log::error("Failed to save image: $path");
				}
			}
		}

		$content = $dom->saveHTML();

		// Send Emails
		$emailArray = explode(',', $email);
		foreach ($emailArray as $recipientEmail) {
			$emailFrom = 'customerservice@kingsburypersonnel.com';
			$emailSentToCC = '';
			$actionName = 'Random Email';
			$this->saveSentEmails(
				$recipientEmail,
				$emailSentToCC,
				$emailFrom,
				$subject,
				$content,
				$actionName
			);
		}

		return response()->json(['success' => 'Emails sent successfully']);
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
        } else {
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
	
	public function exportNotUpdateCsv($category)
    {
        $category = $category == 44 ? 'Nurse':'Non Nurse';
        $users = User::where(["is_admin" => 0])->get();
        return view('administrator.applicant.export_not_update_app',compact('users','category'));

    }

    public function exportNotUpdatedApplicants(Request $request) 
    {
        $job_category =  $request->user_selected;
        if($job_category== 'Nurse')
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
        $not_updated_applicants= '';

        $data = Sale::select('lat', 'lng')
        ->where("status", "active")->where("is_on_hold", "0")
        ->get();
        $data = collect($data->toArray());
        $result = Applicant::doesnthave('CVNote')->doesntHave('module_notes')->doesntHave('applicant_notes')
			->select('applicant_phone','applicant_name','applicant_homePhone','applicant_job_title','applicant_postcode')
			->where(['job_category' => $job_category, 'is_blocked' => 0,
         'temp_not_interested' => 0, 'no_response' => 0 ])->whereBetween('created_at', [$start_date, $end_date])
         ->orderBy('created_at', 'DESC')->get();
        //  echo $result->count();exit();
        $not_updated_applicants = [];
         foreach ($result as $key => $value) {
            $lat_val = $value->lat;
            $lng_val = $value->lng;
                foreach($data as $d)
                {
                    $res = ((ACOS(SIN($lat_val * PI() / 180) * SIN($d['lat'] * PI() / 180) +
                    COS($lat_val * PI() / 180) * COS($d['lat'] * PI() / 180) * COS(($lng_val - $d['lng']) * PI() / 180)) * 180 / PI()) * 60 * 1.1515);
                    if($res < 10)
                    {
                    $not_updated_applicants[] = $result[$key];
                    break;
                    }
                }

            }
        
        return Excel::download(new NotUpdatedApplicantsExport($not_updated_applicants), 'applicants.csv');
        
    }

	public function export_email()
    {
        $users = User::where(["is_admin" => 0])->get();
        return view('administrator.applicant.export_csv_email',compact('users'));
    }
	public function emailExportApplicant(Request $request){
        $job_category =  $request->user_selected;
        if($job_category==44)
        {
            $job_category='nurse';
        }
        else if ($job_category==45)
        {
            $job_category='non-nurse';

        }
        else if ($job_category==46)
        {
            $job_category='specialist';
        }
		else if ($job_category==47)
        {
            $job_category='chef';
        }
        $start_date = $request->input('start_date');
        $start_date = Carbon::parse($start_date)->format('Y-m-d'). " 00:00:00";
        $end_date = $request->input('end_date');
        $end_date = Carbon::parse($end_date)->format('Y-m-d'). " 23:59:59";

        return Excel::download(new ApplicantEmailExport($start_date,$end_date,$job_category), 'applicants.csv');

    }
	public function edited_by_history($id){
        $auth_id=Auth::user()->is_admin;
        $applicantHistory=ApplicantUpdatedHistory::where('applicant_id',$id)->where('user_id','!=',$auth_id)->first();
        if ($applicantHistory != null){
            return view('administrator.applicant.edited_by_history',compact('applicantHistory'));

        }
        else{
            return Redirect::back()->with('error', 'Sorry! Not Updated  any user current  to this applicant');
        }
    }
    public function editedByData($id){
        $auth_id=Auth::user()->is_admin;
        $applicantHistory=ApplicantUpdatedHistory::where('applicant_id',$id)->where('user_id','!=',$auth_id)->get();
        return datatables()->of($applicantHistory)
            ->addColumn('user_name',function ($row){
              $userName=User::find($row->user_id);
                return $userName->name;
            })
            ->addColumn('applicant_name',function ($row){
                $appName=Applicant::find($row->applicant_id);
                return $appName->applicant_name;
            })
            ->addColumn('date',function ($row){
               $date=Carbon::parse($row->created_at)->format('d M Y');
                return $date;
            })
            ->addColumn('time',function ($row){
                $date=Carbon::parse($row->created_at)->format('H:i:s');
                return $date;
            })
            ->addColumn('column_name',function ($row){
                if ($row->column_name!=null){
                     $columnNameNotAdded=array('applicant_added_time','applicant_added_date','applicant_notes','updated_at');
                    $columnNameUpdated=array_diff(json_decode($row->column_name),$columnNameNotAdded);
                    $data=array_values($columnNameUpdated);
                    
                    return $data;
                   }
                return 'null';

            })
            ->rawColumns(['user_name','date','time','applicant_name','column_name'])
            ->make(true);
    }
	
	  public function noJobRevertAll(Request $request)
	  {
		  try {
			  $applicantIds = $request->input('ids');

			  foreach ($applicantIds as $applicantId) {
				  $applicant = Applicant::find($applicantId);

				  if ($applicant) {
					  $applicant->update([
						  'is_no_job' => 0,
						  'applicant_notes'=>'Applicant is no job - reverted back'
					  ]);
					  
					  $molduleNote = ModuleNote::create([
						  'module_noteable_id' => $applicant->id,
						  'module_noteable_type' => 'Horsefly\Applicant',
						  'details' => 'Applicant is no job - reverted back',
						  'module_note_added_date' => Carbon::now()->format('jS F Y'),
						  'module_note_added_time' => Carbon::now()->format('h:i A'),
						  'user_id' => Auth::id(),
						  'status' => 'active'
					  ]);

					  $molduleNote_uid = md5($molduleNote->id);
					  DB::table('module_notes')
						  ->where('id', $molduleNote->id)
						  ->update(['module_note_uid' => $molduleNote_uid]);
				  }
			  }

			  return response()->json(['success' => true, 'message' => 'Applicants reverted successfully']);
		  } catch (\Exception $exception) {

			  return response()->json(['success' => false, 'message' => 'Error reverting applicants']);
		  }
	  }
	
		public function noResponedRevertAll(Request $request)
		{
			try {
				$applicantIds = $request->input('ids');

				foreach ($applicantIds as $applicantId) {
					$applicant = Applicant::find($applicantId);

					if ($applicant) {
						$applicant->update([
							'no_response' => 0,
							'applicant_notes'=>'Applicant is no responed - reverted back'
						]);
						$molduleNote = ModuleNote::create([
							'module_noteable_id' => $applicant->id,
							'module_noteable_type' => 'Horsefly\Applicant',
							'details' => 'Applicant is no responed - reverted back',
							'module_note_added_date' => Carbon::now()->format('jS F Y'),
							'module_note_added_time' => Carbon::now()->format('h:i A'),
							'user_id' => Auth::id(),
							'status' => 'active'
						]);

						$molduleNote_uid = md5($molduleNote->id);
						DB::table('module_notes')
							->where('id', $molduleNote->id)
							->update(['module_note_uid' => $molduleNote_uid]);
					}
				}

				return response()->json(['success' => true, 'message' => 'Applicants reverted successfully']);
			} catch (\Exception $exception) {

				return response()->json(['success' => false, 'message' => 'Error reverting applicants']);
			}
		}
	
	 public function blockedApplicantRevertAll(Request $request)
	 {
        try {
            $applicantIds = $request->input('ids');
            $blockedApplicantsExist = false;

            foreach ($applicantIds as $applicantId) {
                $applicant = Applicant::find($applicantId);

                if ($applicant && $applicant->is_blocked != 0) {
                    $blockedApplicantsExist = true;
                    $applicant->update([
                        'is_blocked' => 0,
                        'applicant_notes'=>'Applicant is unblocked reverted back'
                    ]);
                    $molduleNote = ModuleNote::create([
                        'user_id' => Auth::id(),
                        'module_noteable_id' => $applicant->id,
                        'module_noteable_type' => 'Horsefly\Applicant',
                        'details' => 'Applicant is unblocked reverted back',
                        'module_note_added_date' => Carbon::now()->format('jS F Y'),
                        'module_note_added_time' => Carbon::now()->format('h:i A'),
                        'status' => 'active'
                    ]);

                    $molduleNote_uid = md5($molduleNote->id);
                    DB::table('module_notes')
                        ->where('id', $molduleNote->id)
                        ->update(['module_note_uid' => $molduleNote_uid]);
                }
            }

            if ($blockedApplicantsExist) {
                return response()->json(['success' => true, 'message' => 'Applicants reverted successfully']);
            } else {
                return response()->json(['success' => false, 'message' => 'No blocked applicants found']);
            }
        } catch (\Exception $exception) {

            return response()->json(['success' => false, 'message' => 'Error reverting applicants']);
        }

    }

    public function nonInterestedRevertAll(Request $request)
    {
        try {
            $applicantIds = $request->input('ids');

            foreach ($applicantIds as $applicantId) {
                $applicant = Applicant::find($applicantId);

                if ($applicant) {
                    $applicant->update([
                        'temp_not_interested' => 0,
                        'applicant_notes'=>'Applicant is non interested - reverted back'
                    ]);
                    $molduleNote = ModuleNote::create([
                        'user_id' => Auth::id(),
                        'module_noteable_id' => $applicant->id,
                        'module_noteable_type' => 'Horsefly\Applicant',
                        'details' => 'Applicant is non interested - reverted back',
                        'module_note_added_date' => Carbon::now()->format('jS F Y'),
                        'module_note_added_time' => Carbon::now()->format('h:i A'),
                        'status' => 'active'
                    ]);

                    $molduleNote_uid = md5($molduleNote->id);
                    DB::table('module_notes')
                        ->where('id', $molduleNote->id)
                        ->update(['module_note_uid' => $molduleNote_uid]);
			
               $pivot_applicant=Applicants_pivot_sales::where('applicant_id',$applicant->id)->get();
                // Check if $pivot_applicant is not empty before attempting to iterate over it
                if (!$pivot_applicant->isEmpty()) {
                    foreach ($pivot_applicant as $interested) {
                        $note_range_pivot=Notes_for_range_applicants::where('applicants_pivot_sales_id',$interested->id)->get();
                        if (!$note_range_pivot->isEmpty()) {
                            foreach ($note_range_pivot as $range_note) {
                                $range_note->delete();

                            }
                            }
                        $interested->delete();

                        }
                    }
                }
                
            }

            return response()->json(['success' => true, 'message' => 'Applicants reverted successfully']);
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'message' => 'Error reverting applicants']);
        }
    }
	
   	public function nursefollowUpSheet()
    {
        return view('administrator.follow_up.nurse');
    }
	
	public function nonnursefollowUpSheet()
    {
        return view('administrator.follow_up.nonnurse');
    }
	
	public function nursefollowUpSheetSample()
    {
        return view('administrator.follow_up.sample');
    }
	
	public function addPostcodes(Request $request)
	{
		$validated = $request->validate([
			'postcodes' => 'required|array',
			'status' => 'required|integer',
		]);

		foreach ($validated['postcodes'] as $postcode) {
			DB::table('postcode_histories')->insert([
				'postcodes' => $postcode,
				'status' => $validated['status'],
				'created_at' => now(),
				'updated_at' => now(),
			]);
		}

		return response()->json(['message' => 'Postcodes added successfully!']);
	}

	public function getFollowUpApplicantsSample(Request $request, $id)
	{

		// Get postcodes from the request
		$postcodes = $request->input('postcodes', []);

		// If there are no postcodes provided, return an empty DataTables response
		if (empty($postcodes)) {
			return response()->json([
				'draw' => intval($request->input('draw')), // DataTables requires this draw parameter for request-response synchronization
				'recordsTotal' => 0,
				'recordsFiltered' => 0,
				'data' => [] // Return an empty data array
			]);
		}

		$radius = 5;

		// Determine job category based on $id
		$job_category = [];
		if ($id == "44") {
			$job_category = ['nurse'];
			$db_category = 'nurse';
		} elseif ($id == "45") {
			$job_category = ['nonnurse', 'nursery'];
			$db_category = 'nonnurse';
		}

		foreach ($postcodes as $code) {
			DB::table('postcode_histories')->insert([
				'postcode' => $code,
				'category' => $db_category,
				'status' => '1',
				'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),
			]);
		}

		// Base query to fetch sales and join with offices and units
		$salesQuery = Office::join('sales', 'offices.id', '=', 'sales.head_office')
			->join('units', 'units.id', '=', 'sales.head_office_unit')
			->select(
				'sales.*',
				'offices.office_name',
				'offices.lat as office_lat',
				'offices.lng as office_lng',
				'units.contact_name',
				'units.contact_email',
				'units.unit_name',
				'units.contact_phone_number'
			)
			->where('sales.status', 'active')
			->where('sales.is_on_hold', '0');

		// Apply job category filter
		if (!empty($job_category)) {
			$salesQuery->whereIn('sales.job_category', $job_category);
		}

		// Apply postcode filter if provided
		$salesQuery->whereIn('sales.postcode', $postcodes);

		// Fetch the sales records
		$sales = $salesQuery->get();

		// Initialize an array to hold unique applicants
		$uniqueApplicants = [];

		// Loop through each sale and fetch nearby applicants
		foreach ($sales as $job_result) {
			$lat = $job_result->lat;
			$lng = $job_result->lng;
			$jobTitle = $job_result->job_title;

			// Fetch applicants within the radius based on lat/lng and job category
			$near_by_applicants = $this->applicant_distance($lat, $lng, $radius, $job_category, $jobTitle);

			// Ensure $near_by_applicants is an array
			if ($near_by_applicants instanceof \Illuminate\Database\Eloquent\Collection) {
				$near_by_applicants = $near_by_applicants->toArray();
			}

			// Merge applicants into the uniqueApplicants array
			foreach ($near_by_applicants as $applicant) {
				if (!isset($uniqueApplicants[$applicant['id']])) {
					$uniqueApplicants[$applicant['id']] = $applicant;
				}
			}
		}

		// Return the unique applicants as a DataTables response
		return datatables()->of($uniqueApplicants)
			->addColumn('applicant_postcode', function ($applicant) {
				$status_value = $this->getApplicantStatus($applicant);
				$postcode = strtoupper($applicant['applicant_postcode']);

				if ($status_value == 'open' || $status_value == 'reject') {
					return '<a target="_blank" href="/available-jobs/' . $applicant['id'] . '">' . $postcode . '</a>';
				}

				return $postcode;
			})
			->addColumn('applicant_notes', function($applicant){
				$app_new_note = ModuleNote::where(['module_noteable_id' => $applicant['id'], 'module_noteable_type' => 'Horsefly\Applicant'])
					->select('module_notes.details')
					->orderBy('module_notes.id', 'DESC')
					->first();

				$app_notes_final = $app_new_note ? $app_new_note->details : $applicant['applicant_notes'];

				$status_value = 'open';
				if ($applicant['paid_status'] == 'close') {
					$status_value = 'paid';
				} else {
					foreach ($applicant['cv_notes'] as $value) {
						if ($value['status'] == 'active') {
							$status_value = 'sent';
							break;
						} elseif ($value['status'] == 'disable') {
							$status_value = 'reject';
						}
					}
				}

				if($applicant['is_blocked'] == 0 && ($status_value == 'open' || $status_value == 'reject')) {
					return '<a href="#" class="reject_history" data-applicant="' . $applicant['id'] . '"
								data-toggle="modal" data-target="#clear_cv">
								<i class="fa fa-commenting" aria-hidden="true" style="font-size:18px;"></i>
							</a>';
				} else {
					return $app_notes_final;
				}
			})
			->addColumn('status', function ($applicant) {
				$status_value = $this->getApplicantStatus($applicant);
				$color_class = ($status_value == 'paid') ? 'bg-slate-700' : 'bg-teal-800';

				return '<h3><span class="badge w-100 ' . $color_class . '">' . strtoupper($status_value) . '</span></h3>';
			})
			->addColumn('download', function ($applicant) {
				return $this->renderDownloadLink($applicant['applicant_cv'], route('downloadApplicantCv', $applicant['id']));
			})
			->addColumn('updated_cv', function ($applicant) {
				return $this->renderDownloadLink($applicant['updated_cv'], route('downloadUpdatedApplicantCv', $applicant['id']));
			})
			->addColumn('checkbox', function ($applicant) {
				return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant['id'] . '"/>';
			})
			->addColumn('upload', function ($applicant) {
				return '<a href="#" class="import_cv" data-toggle="modal" data-id="' . $applicant['id'] . '" data-target="#import_applicant_cv"><i class="fas fa-file-upload text-teal-400"></i></a>';
			})
			->setRowId(function ($applicant) {
				return 'applicant_' . $applicant['id']; // Sets unique row ID using the applicant's ID
			})
			->setRowClass(function ($applicant) {
				return ($applicant['is_follow_up'] == '2') ? 'class_noJob' : '';
			})
			->addColumn('action', function ($applicant) {
				$no_answer = '<a href="#" class="btn btn-primary btn_no_answer" data-id="' . $applicant['id'] . '" title="No Answer"><i class="fas fa-phone-slash text-red-400"></i></a>';
				$busy = '<a href="#" class="btn btn-secondary btn_busy" data-id="' . $applicant['id'] . '" title="Circuit Busy"><i class="fas fa-hourglass-half text-orange-400"></i></a>';

				// Return the two buttons with some spacing
				return $no_answer . '&nbsp;' . $busy; // &nbsp; adds a space between buttons
			})

			->rawColumns(['applicant_postcode', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status','action'])
			->make(true);
	}

 	public function getFollowUpApplicants(Request $request,$id)
    {
       	$radius = 8;
        $filterID = isset($request->filter_id) ? $request->filter_id : 'active';

		// Fetch sales based on office and units
		$salesQuery = Office::join('sales', 'offices.id', '=', 'sales.head_office')
			->join('units', 'units.id', '=', 'sales.head_office_unit')
			->select(
				'sales.*',
				'offices.office_name',
				'offices.lat as office_lat',
				'offices.lng as office_lng',
				'units.contact_name',
				'units.contact_email',
				'units.unit_name',
				'units.contact_phone_number'
			)
			->where('sales.status', 'active')
			->where('sales.is_on_hold', '0')
			->orderBy('sales.id', 'DESC');

		$job_category = [];
		$postcodes = [];
		
		$db_postcodes = DB::table('postcode_histories')->where('status', '1');

		$nurse_postcodes = (clone $db_postcodes)->where('category', 'nurse')->pluck('postcode')->toArray();
		$nonnurse_postcodes = (clone $db_postcodes)->where('category', 'non-nurse')->pluck('postcode')->toArray();

		// Determine job category and postcodes based on $id
		if ($id == "44") {
			$salesQuery->where('sales.job_category', 'nurse');
			$job_category = ['nurse'];
			$postcodes = $nurse_postcodes;
			$category = 'nurse';
		} elseif ($id == "45") {
			$salesQuery->whereIn('sales.job_category', ['nonnurse', 'nursery', 'chef']);
			$job_category = ['non-nurse', 'nursery', 'chef'];
			$postcodes =  $nonnurse_postcodes;
			$category = 'nonnurse';
		}

		// Fetch unique applicants within radius of each sale
    	$sales = $salesQuery->whereIn('sales.postcode',$postcodes)->get();
		
		$uniqueApplicants = [];

		// Loop through each sale and fetch nearby applicants
		foreach ($sales as $job_result) {
			$lat = $job_result->lat;
			$lng = $job_result->lng;
			$jobTitle = $job_result->job_title;
			$jobTitleProf = $job_result->job_title_prof;

			// Fetch applicants within the radius based on lat/lng and job category
			$near_by_applicants = $this->applicant_distance($lat, $lng, $radius, $job_category, $jobTitle, $jobTitleProf, $category, $filterID);

			// Ensure $near_by_applicants is an array
			if ($near_by_applicants instanceof \Illuminate\Database\Eloquent\Collection) {
				$near_by_applicants = $near_by_applicants->toArray();
			}

			// Filter applicants to exclude those with cv_notes status 'disable'
			foreach ($near_by_applicants as $applicant) {
				// Check if the applicant has cv_notes and if any of them have status 'disable'
				if (isset($applicant['cv_notes']) && collect($applicant['cv_notes'])->contains('status', 'active')) {
					continue; // Skip this applicant
				}

				// Add applicant to uniqueApplicants if not already included
				if (!isset($uniqueApplicants[$applicant['id']])) {
					$uniqueApplicants[$applicant['id']] = $applicant;
				}
			}
		}

        // Step 3: Return the unique applicants as a DataTables response
        return datatables()->of($uniqueApplicants)
            ->addColumn('applicant_postcode', function ($applicant) {
                $status_value = $this->getApplicantStatus($applicant);
                $postcode = strtoupper($applicant['applicant_postcode']);

                if ($status_value == 'open' || $status_value == 'reject') {
                    return '<a target="_blank" href="/available-jobs/' . $applicant['id'] . '">' . $postcode . '</a>';
                }

                return $postcode;
            })
          ->addColumn('applicant_notes', function($applicant){
                   $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant['id'], 'module_noteable_type' => 'Horsefly\Applicant'])
                ->select('module_notes.details')
                ->orderBy('module_notes.id', 'DESC')
                ->first();

            $app_notes_final = $app_new_note ? $app_new_note->details : $applicant['applicant_notes'];

            $status_value = 'open';
            if ($applicant['paid_status'] == 'close') { // change to array access
                $status_value = 'paid';
            } else {
                foreach ($applicant['cv_notes'] as $value) { // change to array access
                    if ($value['status'] == 'active') {
                        $status_value = 'sent';
                        break;
                    } elseif ($value['status'] == 'disable') {
                        $status_value = 'reject';
                    }
                }
            }
                   
                if($applicant['is_blocked'] == 0 && $status_value == 'open' || $status_value == 'reject')
                {
                    $content = '';
                    $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant['id'].'"
                                    data-toggle="modal" data-target="#clear_cv">
									<i class="fa fa-commenting" aria-hidden="true" style="font-size:18px;"></i>
								</a>';
                    return $content;
                }else
                {
                    return $app_notes_final;
                }
                return $content;
            })
            ->addColumn('status', function ($applicant) {
				$status_value = $this->getApplicantStatus($applicant);
				$color_class = ($status_value == 'paid') ? 'bg-slate-700' : 'bg-teal-800';

				return '<h3><span class="badge w-100 ' . $color_class . '">' . strtoupper($status_value) . '</span></h3>';
			})
            ->addColumn('download', function ($applicant) {
                return $this->renderDownloadLink($applicant['applicant_cv'], route('downloadApplicantCv', $applicant['id']));
            })
            ->addColumn('updated_cv', function ($applicant) {
                return $this->renderDownloadLink($applicant['updated_cv'], route('downloadUpdatedApplicantCv', $applicant['id']));
            })
            ->addColumn('checkbox', function ($applicant) {
                return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant['id'] . '"/>';
            })
            ->addColumn('upload', function ($applicant) {
                return '<a href="#" class="import_cv" data-toggle="modal" data-id="' . $applicant['id'] . '" data-target="#import_applicant_cv"><i class="fas fa-file-upload text-teal-400"></i></a>';
            })
			->addColumn('action', function ($applicant) {
				$no_answer = '<a href="#" class="btn btn-primary btn_no_answer" data-id="' . $applicant['id'] . '" title="No Answer"><i class="fas fa-phone-slash text-red-400"></i></a>';
				$busy = '<a href="#" class="btn btn-secondary btn_busy" data-id="' . $applicant['id'] . '" title="Circuit Busy"><i class="fas fa-hourglass-half text-orange-400"></i></a>';

				// Return the two buttons with some spacing
				return $no_answer . '&nbsp;' . $busy; // &nbsp; adds a space between buttons
			})

			->setRowId(function ($applicant) {
				return 'applicant_' . $applicant['id']; // Sets unique row ID using the applicant's ID
			})
			 ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant['is_follow_up'] == '2') {
                    $row_class = 'class_noJob';
                } 
                return $row_class;
            })
			
           ->rawColumns(['applicant_postcode', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status','action'])
            ->make(true);
    }
	
   	public function applicant_distance($lat, $lon, $radius, $job_category, $jobTitle, $jobTitleProf, $category, $filterID)
	{
		$jobTitle = trim($jobTitle);
		$jobTitle = str_replace(['%', '_'], ['\%', '\_'], $jobTitle); 

		// Handle specific cases for job titles
        if ($jobTitle == 'support worker / care assistant') {
			$jobTitle = ['support worker', 'care assistant'];
		} elseif ($jobTitle == 'senior support worker / senior care assistant') {
			$jobTitle = ['senior support worker', 'senior care assistant'];
		} elseif ($jobTitle == 'rgn/rmn' || $jobTitle == 'rgn' || $jobTitle == 'rgn/rmn/rnld') {
			$jobTitle = ['rgn/rmn', 'rgn','rgn/rmn/rnld'];
		} else {
			$jobTitle = [$jobTitle];
		}
		
		if ($category == 'nurse') {
			$Newradius = 12;
		} else {
			$Newradius = 5;
		}

		// Build the base query with distance calculation
		$location_distance = Applicant::with('cv_notes')
			->select(DB::raw("applicants.*, ((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) +
				COS($lat * PI() / 180) * COS(lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515)
				AS distance"))
			->having("distance", "<", $Newradius)
			->orderBy("distance")
			->where([
				"applicants.status" => "active",
				"applicants.is_in_nurse_home" => "no",
				"applicants.is_blocked" => "0",
				"applicants.paid_status" => "pending"
			])
			->whereIn('applicants.job_category', $job_category);

		// Apply where clause for job title using `LIKE`
		$location_distance->where(function($query) use ($jobTitle) {
			foreach ($jobTitle as $title) {
				$query->orWhere('applicants.applicant_job_title', 'LIKE', '%' . $title . '%');
			}
		});
		
		if($jobTitleProf != null)
		{
			$location_distance->where('applicants.job_title_prof', $jobTitleProf);
		}

        // Filter applicants based on the selected filterID
		if($filterID == 'active'){
            $location_distance->where([
                'applicants.is_callback_enable' => 'no',
				'applicants.no_response' => '0',
                'applicants.is_follow_up' => '0'
            ]);
        }elseif($filterID == 'no_responded'){
            $location_distance->where([
                'applicants.is_callback_enable' => 'no',
				'applicants.no_response' => '1',
                'applicants.is_follow_up' => '2'
            ]);
        }elseif($filterID == 'callback'){
            $location_distance->where([
                'applicants.is_callback_enable' => 'yes',
				'applicants.no_response' => '0',
                'applicants.is_follow_up' => '2'
            ]);
		}elseif($filterID == 'circuit_busy'){
            $location_distance->where([
                'applicants.is_circuit_busy' => '1',
				'applicants.no_response' => '0',
                'applicants.is_follow_up' => '2'
            ]);
        }
		
		// Execute the query and return the result
		return $location_distance->get();
	}


    protected function getApplicantStatus($applicant)
	{
		$status_value = 'open';

		if ($applicant['paid_status'] == 'close') { // change to array access
			$status_value = 'paid';
		} else {
			foreach ($applicant['cv_notes'] as $value) { // change to array access
				if ($value['status'] == 'active') {
					$status_value = 'sent';
					break;
				} elseif ($value['status'] == 'disable') {
					$status_value = 'reject';
				}
			}
		}

		return $status_value;
	}

    // Helper function to render download link
    protected function renderDownloadLink($filePath, $route)
    {
        $disabled = (!file_exists($filePath) || $filePath == null) ? 'disabled' : '';
        $disabled_color = ($disabled) ? 'text-grey-400' : 'text-teal-400';
        $href = ($disabled) ? 'javascript:void(0);' : $route;

        return '<a class="download-link ' . $disabled . '" href="' . $href . '">
                <i class="fas fa-file-download ' . $disabled_color . '"></i></a>';
    }

	 public function followUpPostcodes()
    {
        return view('administrator.follow_up.index');
    }

    public function getPostCodeHistory()
    {
        // Get the postcodes from postcode_histories, prioritizing status 1 and formatting the updated_at date
        $postcodes = DB::table('postcode_histories')
            ->select('postcode', 'updated_at','status','category') // Select only the necessary fields
            ->orderBy('status', 'desc') // Prioritize status 1 (1 will be before 0)
            ->orderBy('updated_at', 'desc') // Order by updated_at descending
            ->get()
            ->map(function($postcode) {
                // Format the updated_at date
                $postcode->updated_at = Carbon::parse($postcode->updated_at)->format('d-m-Y h:i A'); // Change format as needed
                return $postcode;
            });
    
        return response()->json($postcodes);
    }
   
    public function getUniquePostcodesNurse()
    {
        // Get all distinct postcodes from the Sale table
        $postcodes = Sale::select('postcode','job_category','job_title')
            ->where('status','active')
            ->whereIn('job_category',['nurse'])->orderBy('postcode','asc')->distinct()->get();
    
        // Get the postcodes from postcode_histories with status 1
        $checkedPostcodes = DB::table('postcode_histories')
            ->where('status', '1')
            ->where('category','nurse')
            ->pluck('postcode')
            ->toArray();

        // Map through postcodes and add a 'checked' key
        $postcodesWithChecked = $postcodes->map(function ($postcode) use ($checkedPostcodes) {
            return [
                'postcode' => $postcode->postcode,
				'job_title' => strtoupper($postcode->job_title),
                'checked' => in_array($postcode->postcode, $checkedPostcodes),
            ];
        });
    
        return response()->json($postcodesWithChecked);
    }
    
    public function getUniquePostcodesNonnurse()
    {

        // Get all distinct postcodes from the Sale table
        $postcodes = Sale::select('postcode','job_category','job_title')
        ->where('status','active')
        ->whereIn('job_category',['nonnurse','nursery','chef'])
        ->distinct()->orderBy('postcode','asc')->get();
    
        // Get the postcodes from postcode_histories with status 1
        $checkedPostcodes = DB::table('postcode_histories')
            ->where('status', '1')
            ->where('category','non-nurse')
            ->pluck('postcode')
            ->toArray();

        // Map through postcodes and add a 'checked' key
        $postcodesWithChecked = $postcodes->map(function ($postcode) use ($checkedPostcodes) {
            return [
                'postcode' => $postcode->postcode,
				'job_title' => strtoupper($postcode->job_title),
                'checked' => in_array($postcode->postcode, $checkedPostcodes),
            ];
        });
    
        return response()->json($postcodesWithChecked);
    }
    
    public function addPostcodesNurse(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'postcodes' => 'array',
        ]);
    
        // Step 1: Check if postcodes parameter is not set
        if (!$request->has('postcodes') || empty($validated['postcodes'])) {
            // Set all postcodes to status 0
            DB::table('postcode_histories')
                ->where('category', 'nurse')
                ->update([
                    'status' => '0',
                    'updated_at' => now(),
                ]);
    
            return response()->json(['message' => 'All postcodes have been set to inactive.'], 200);
        }
    
        // Step 2: Set all postcodes to status 0
        DB::table('postcode_histories')
            ->where('category', 'nurse')
            ->update([
                'status' => '0',
                'updated_at' => now(),
            ]);
    
        // Step 3: Process each postcode
        foreach ($validated['postcodes'] as $postcode) {
            // Check if the postcode exists with status 0
            $existingPostcode = DB::table('postcode_histories')
                ->where('category', 'nurse')
                ->where('postcode', $postcode)
                ->where('status', '0')
                ->first();
    
            if ($existingPostcode) {
                // If it exists with status 0, update the status to 1
                DB::table('postcode_histories')
                    ->where('postcode', $postcode)
                    ->where('category', 'nurse')
                    ->update([
                        'status' => '1',
                        'updated_at' => now(),
                    ]);
            } else {
                // Check if it exists with status 1 or doesn't exist at all
                $existingPostcode = DB::table('postcode_histories')
                    ->where('category', 'nurse')
                    ->where('postcode', $postcode)
                    ->first();
    
                if ($existingPostcode) {
                    // If it exists, update the status to 1
                    DB::table('postcode_histories')
                        ->where('postcode', $postcode)
                        ->where('category', 'nurse')
                        ->update([
                            'status' => '1',
                            'updated_at' => now(),
                        ]);
                } else {
                    // If it doesn't exist, insert a new record with status 1
                    DB::table('postcode_histories')->insert([
                        'postcode' => $postcode,
                        'category' => 'nurse',
                        'status' => '1',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    
        return response()->json(['message' => 'Postcodes processed successfully!']);
    }

    public function addPostcodesNonnurse(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'postcodes' => 'array',
        ]);
    
        // Step 1: Check if postcodes parameter is not set or empty
        if (!$request->has('postcodes') || empty($validated['postcodes'])) {
            // Set all postcodes to status 0
            DB::table('postcode_histories')
                ->where('category', 'non-nurse')
                ->update([
                    'status' => '0',
                    'updated_at' => now(),
                ]);
    
            return response()->json(['message' => 'All non-nurse postcodes have been set to inactive.'], 200);
        }
    
        // Step 2: Set all postcodes to status 0
        DB::table('postcode_histories')
            ->where('category', 'non-nurse')
            ->update([
                'status' => '0',
                'updated_at' => now(),
            ]);
    
        // Step 3: Process each postcode
        foreach ($validated['postcodes'] as $postcode) {
            // Check if the postcode already exists
            $existingPostcode = DB::table('postcode_histories')
                ->where('category', 'non-nurse')
                ->where('postcode', $postcode)
                ->first();
    
            if ($existingPostcode) {
                // If it exists, update the status to 1
                DB::table('postcode_histories')
                    ->where('postcode', $postcode)
                    ->where('category', 'non-nurse')
                    ->update([
                        'status' => '1',
                        'updated_at' => now(),
                    ]);
            } else {
                // If it doesn't exist, insert a new record with status 1
                DB::table('postcode_histories')->insert([
                    'postcode' => $postcode,
                    'category' => 'non-nurse',
                    'status' => '1',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    
        return response()->json(['message' => 'Postcodes processed successfully!']);
    }

}
