<?php

namespace Horsefly\Http\Controllers\Administrator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Horsefly\Http\Controllers\Controller;
use Session;
use Carbon\Carbon;
//use Auth;
use Redirect;
use Horsefly\Sale;
use DateTime;
use Illuminate\Support\Facades\Validator;
use Horsefly\Applicant;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Horsefly\Specialist_job_titles;
use Horsefly\User;



class SpecialistTitleController extends Controller
{
    public function index(){
		
        $all_titles = Specialist_job_titles::orderBy('updated_at', 'desc')->get();
        return view('administrator.specialist_titles.index',compact('all_titles'));
    }
    public function create()
    {
        return view('administrator.specialist_titles.create');
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'specialist_title' => 'required',
            'job_title_prof' => 'required',
            
        ])->validate();
        $specialist = new Specialist_job_titles();
        $specialist->specialist_title = $request->input('specialist_title');
        $specialist->specialist_prof = $request->input('job_title_prof');
        $specialist->save();
        $last_inserted_sale_note_id = $specialist->id;
        if($last_inserted_sale_note_id > 0){
            // $sale_note_uid = md5($last_inserted_sale_note_id);
            // Sales_notes::where('id',$last_inserted_sale_note_id)->update(['sales_notes_uid' => $sale_note_uid]);
            return redirect()->route('specialist_titles.index')->with('success', 'Job created Successfully');
        }
        else{
            return redirect()->route('specialist_titles.index')->with('error', 'Something went wrong!');
        }
        // return redirect('specialist_titles.create')->with('success',  'created Successfully');
        // return redirect('specialist_titles-create')->with('success', 'Job created Successfully');
        // return view('administrator.specialist_titles.create')->with('success',  'created Successfully');
    }
    public function get(Request $request)
    {
        $result = Specialist_job_titles::select("*")->where("specialist_title", $request->input('specialist'))->get();
        return response()->json($result);
    }
    public function edit($id){
        $special_title = Specialist_job_titles::find($id);
        return view('administrator.specialist_titles.edit',compact('special_title'));
    }
    public function update(Request $request)
    {
        $special_title = Specialist_job_titles::find($request->Input('id'));
        $special_title->specialist_title = $request->Input('specialist_title');
        $special_title->specialist_prof = $request->Input('specialist_prof');
        $updated = $special_title->update();
        if($updated)
        {
        return redirect()->route('specialist_titles.index')->with('success', 'Content has been updated successfully!');
        }
        else{
        return redirect()->route('specialist_titles.index')->with('error', 'Something went wrong!');

        }
// echo $request->Input('specialist_prof').' and id is : '.$request->Input('id');exit();

    }
    public function get_all_titles(Request $request){
        $selected_spec_job_title = explode('-', $request->Input('job_title_spec'));
        $sale_id =$request->Input('sale_id');
        // return response()->json($selected_spec_job_title);
        $sale = Sale::find($sale_id);
        $selected_prof_data='';
        $all_prof_data = '';
        if($sale->job_title_prof)
        {
            $selected_prof_data = Specialist_job_titles::select("*")->where("id", $sale->job_title_prof)->first();
            $all_prof_data = Specialist_job_titles::select("*")->where("specialist_title", $selected_spec_job_title[0])->get();
            return response()->json(array("all_prof_data" => $all_prof_data,"selected_prof_data" => $selected_prof_data));
        }
        else
        {
            $all_prof_data = Specialist_job_titles::select("*")->where("specialist_title", $selected_spec_job_title[0])->get();
            return response()->json(array("all_prof_data" => $all_prof_data));
        }
    }

    public function app_get_all_titles(Request $request){
        $selected_spec_job_title =  $request->Input('job_title_spec');
        $app_id =$request->Input('applicant_id');
        $applicant = Applicant::find($app_id);
        // return response()->json($selected_spec_job_title);
        $selected_prof_data='';
        $all_prof_data = '';
        if($applicant->job_title_prof)
        {
            $selected_prof_data = Specialist_job_titles::select("*")->where("id", $applicant->job_title_prof)->first();
            $all_prof_data = Specialist_job_titles::select("*")->where("specialist_title", $selected_spec_job_title)->get();
        }
        else
        {
            $all_prof_data = Specialist_job_titles::select("*")->where("specialist_title", $selected_spec_job_title)->get();
        }
        
        return response()->json(array($all_prof_data,$selected_prof_data));
    }
    
}
