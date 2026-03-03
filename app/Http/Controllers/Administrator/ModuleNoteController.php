<?php

namespace Horsefly\Http\Controllers\Administrator;

use Illuminate\Http\Request;
use Horsefly\Http\Controllers\Controller;
use Horsefly\User;
use Horsefly\Applicant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ModuleNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $module = \request()->input('module');
        if (($module == 'Applicant') || ($module == 'Office') || ($module == 'Unit') || ($module == 'Sale')) {
            if (\request()->has('details')) {
                $this->middleware('permission:applicant_note-create|office_note-create|unit_note-create|sale_note-create', ['only' => ['store']]);
				$this->middleware('permission:sale_on-hold', ['only' => ['unhold_sales_notes']]);
            } else {
                $this->middleware('permission:applicant_note-history|office_note-history|unit_note-history|sale_note-history', ['only' => ['index']]);
            }
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return string
     * @throws \Throwable
     */
    public function index(Request $request)
    {
        $input = $request->all();
        $input['module'] = filter_var($request->input('module'), FILTER_SANITIZE_STRING);
        $request->replace($input);
        $module_notes_history = [];

        $validator = Validator::make($request->all(), [
            'module' => "required|in:Office,Sale,Unit,Applicant",
            'module_key' => "required"
        ])->validate();

        $module_key = $request->input('module_key');
        $model_class = 'Horsefly\\' . $request->input('module');
        $module_name = $request->input('module');

        $module_notes_name = strtolower($request->input('module')).'_notes';
        if ($module_name == "Unit") {
            $module_notes_name = strtolower($request->input('module')).'s_notes';
        } elseif($module_name == "Sale") {
            $module_notes_name = strtolower($request->input('module')).'_note';
        }


        if ($module_name == 'Sale') {
            $module_notes_history = User::join('sales_notes', 'users.id', '=', 'sales_notes.user_id')
                ->select('users.name as username', 'sales_notes.sale_note as note', 'sales_notes.status as status', 'sales_notes.created_at')
                ->where('sales_notes.sale_id', '=', $module_key)
                ->orderBy('sales_notes.created_at', 'desc')
                ->get();
        } else {
            $module_notes_history = User::join('module_notes', 'users.id','=','module_notes.user_id')
            ->select( 'users.name as username', 'module_notes.details as note', 'module_notes.status as status', 'module_notes.created_at', 'module_notes.updated_at')
            ->where(['module_notes.module_noteable_id' => $module_key, 
            'module_noteable_type' => $model_class])
            ->orderBy('module_notes.id', 'DESC')
            ->get();
        }

        $history_modal_body = view('administrator.partial.module_notes_history', compact('module_notes_history'))->render();
        return $history_modal_body;
    }
	
	public function unhold_sales_notes(Request $request)
    {
        $input = $request->all();
        $input['module'] = filter_var($request->input('module'), FILTER_SANITIZE_STRING);
        $request->replace($input);
        $model_notes_history = [];

        $validator = Validator::make($request->all(), [
            'module' => "required|in:Office,Sale,Unit,Applicant",
            'module_key' => "required"
        ])->validate();

        $module_key = $request->input('module_key');
        $model_class = 'Horsefly\\' . $request->input('module');
        $module_name = $request->input('module');

        $module_notes_name = strtolower($request->input('module')).'_notes';
        if ($module_name == "Unit") {
            $module_notes_name = strtolower($request->input('module')).'s_notes';
        } elseif($module_name == "Sale") {
            $module_notes_name = strtolower($request->input('module')).'_note';
        }

        $module_notes_history = User::join('module_notes', 'users.id','=','module_notes.user_id')
            ->select( 'users.name', 'module_notes.details')
            ->where(['module_notes.module_noteable_id' => $module_key, 'module_noteable_type' => $model_class])
            ->orderBy('module_notes.id', 'DESC')->limit(1)->get();
        // if ($module_name == 'Sale') {
        //     $model_notes_history = User::join('sales_notes', 'users.id', '=', 'sales_notes.user_id')
        //         ->select('users.name as username', 'sales_notes.sale_note as note')
        //         ->where('sales_notes.sale_id', '=', $module_key)
        //         ->orderBy('sales_notes.created_at', 'desc')
        //         ->get()->toArray();
        // } else {
        //     $model = $model_class::with('user')->find($request->input('module_key'));
        //     $model_notes_history[0]['username'] = $model->user->name;
        //     $model_notes_history[0]['note'] = $model->$module_notes_name;
        // }

        $history_modal_body = view('administrator.partial.module_onhold_sales_notes', compact('module_notes_history'))->render();
        return $history_modal_body;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        date_default_timezone_set('Europe/London');

        $input = $request->all();
        $input['module'] = filter_var($input['module'], FILTER_SANITIZE_STRING);
        $input['details'] = filter_var($input['details'], FILTER_SANITIZE_STRING);
        $request->replace($input);

       $validator = Validator::make($request->all(), [
            'module' => "required|in:Office,Sale,Unit,Applicant",
            'module_key' => "required",
            'details' => "required|string",
        ])->validate();

		 $noteDetail = '';
       if($request->input('module') != 'Unit' && $request->input('module') != 'Sale' && $request->input('module') != 'Office'){
			if($request->has('hangup_call') && $request->input('hangup_call') == 1){
				$noteDetail .= '<strong>Date:</strong> ' . Carbon::now()->format('d-m-Y') . '<br>';
				$noteDetail .= '<strong>Call Hung up/Not Interested:</strong> ' . ($request->input('hangup_call') ? 'Yes' : 'No') . '<br>';
				$noteDetail .= '<strong>Details:</strong> ' . nl2br(htmlspecialchars($request->input('details'))) . '<br>';
				$noteDetail .= '<strong>By:</strong> ' . auth()->user()->name . '<br>';

				$applicant_id = $request->input('module_key');
				Applicant::find($applicant_id)->update(['temp_not_interested'=>1]);
				
			}elseif($request->has('no_job') && $request->input('no_job') == 1){
                $noteDetail .= '<strong>Date:</strong> ' . Carbon::now()->format('d-m-Y') . '<br>';
                $noteDetail .= '<strong>No Job:</strong> ' . ($request->input('no_job') ? 'Yes' : 'No') . '<br>';
                $noteDetail .= '<strong>Details:</strong> ' . nl2br(htmlspecialchars($request->input('details'))) . '<br>';
                $noteDetail .= '<strong>By:</strong> ' . auth()->user()->name . '<br>';

                $applicant_id = $request->input('module_key');

                Applicant::where('id', $applicant_id)
                        ->update([
                            'no_response'=>'0',
                            'temp_not_interested' => '0',
                            'is_blocked' => '0',
                            'is_no_job' => '1',
                            'applicant_notes' => $noteDetail,
                            'updated_at'=>Carbon::now()
                        ]);


			}else{
				$transportType ='';
				$shiftPattern ='';
				 // Format transport_type and shift_pattern if needed
				if($request->has('transport_type')){
					$transportType = implode(', ', $request->input('transport_type'));
				}
				if($request->has('shift_pattern')){
					$shiftPattern = implode(', ', $request->input('shift_pattern'));
				}
				 $noteDetail .= '<strong>Date:</strong> ' . Carbon::now()->format('d-m-Y') . '<br>';
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
				 $noteDetail .= '<strong>Details:</strong> ' . nl2br(htmlspecialchars($request->input('details'))) . '<br>';
				$noteDetail .= '<strong>By:</strong> ' . auth()->user()->name . '<br>';
			}
	   	}else{
            $noteDetail .= $request->Input('details').' --- By:'.auth()->user()->name.' Date: '.Carbon::now()->format('d-m-Y');
        }

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> Note Could not Added
                </div>';

        $model_class = 'Horsefly\\' . $request->input('module');
        $model = $model_class::find($request->input('module_key'));
        if ($model) {
            $module_note = $model->module_notes()->create([
                'user_id' => Auth::id(),
                'module_note_added_date' => date('jS F Y'),
                'module_note_added_time' => date("h:i A"),
                'details' => $noteDetail,
                'status' => 'active'
            ]);

            $last_inserted_module_note = $module_note->id;
            if($last_inserted_module_note){
                $module_note_uid = md5($last_inserted_module_note);
                DB::table('module_notes')->where('id', $last_inserted_module_note)->update(['module_note_uid' => $module_note_uid]);
                $html = '<div class="alert alert-success border-0 alert-dismissible" id="alert_note'.$model->id.'">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Note Added Successfully
						</div>';
                echo $html;
            }
            else {
                echo $html;
            }
        } else {
            echo $html;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
