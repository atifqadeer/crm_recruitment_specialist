<?php

namespace Horsefly\Http\Controllers\Administrator;

use Illuminate\Http\Request;
use Horsefly\Http\Controllers\Controller;
use Horsefly\Note;
use Horsefly\Applicant;
use Auth;
use DB;
use Redirect;

class NoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function store(Request $request){

        $email = $request->input('appl_email');
        $phone_number = $request->input('phone');
        $postcode = $request->input('postcode');



        if(!empty($email)){
            $applicant = Applicant::select("id")->where("applicant_email",$email)->first();
            $app_id = $applicant->id;
            $note = new Note();
            $note->user_id = $app_id;
            $note->note_title = $request->input('note_title');
            $note->note_description = $request->input('note_descr');
            $note->save();
            $last_inserted_note = $note->id;
            if($last_inserted_note){
                $note_uid = md5($last_inserted_note);
                DB::table('notes')->where('id', $last_inserted_note)->update(['note_uid' => $note_uid]);
                return response()->json(['success' => 'True']);
            }
            else{
                return response()->json(['success' => 'False']);
            }

        }
        if(!empty($phone_number)){
            $applicant = Applicant::select("id")->where("applicant_phone",$phone_number)->first();
            $app_id = $applicant->id;
            $note = new Note();
            $note->user_id = $app_id;
            $note->note_title = $request->input('note_title');
            $note->note_description = $request->input('note_descr');
            $note->save();
            $last_inserted_note = $note->id;
            if($last_inserted_note){
                $note_uid = md5($last_inserted_note);
                DB::table('notes')->where('id', $last_inserted_note)->update(['note_uid' => $note_uid]);
                return response()->json(['success'=>'True']);
            }
            else{
                return response()->json(['success'=>'False']);
            }
        }
        if(!empty($postcode)){
            $applicant = Applicant::select("id")->where("applicant_postcode",$postcode)->first();
            $app_id = $applicant->id;
            $note = new Note();
            $note->user_id = $app_id;
            $note->note_title = $request->input('note_title');
            $note->note_description = $request->input('note_descr');
            $note->save();
            $last_inserted_note = $note->id;
            if($last_inserted_note){
                $note_uid = md5($last_inserted_note);
                DB::table('notes')->where('id', $last_inserted_note)->update(['note_uid' => $note_uid]);
                return response()->json(['success'=>'True']); //$note->note_title]);
            }
            else{
                return response()->json(['success'=>'False']);
            }
        }
    }
}
