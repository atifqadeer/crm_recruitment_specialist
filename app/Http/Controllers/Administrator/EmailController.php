<?php

namespace Horsefly\Http\Controllers\Administrator;

use Illuminate\Http\Request;
use Horsefly\Http\Controllers\Controller;

use Horsefly\SentEmail;
use Horsefly\ModuleNote;
//use Auth;
use DB;
use Illuminate\Support\Facades\Auth;
use Redirect;
use Validator;
use Session;
use Carbon\Carbon;
use Horsefly\EmailCountPerDay;
use Horsefly\Jobs\SendQueueEmail;
use Horsefly\Sale;
use Horsefly\Applicant;
use Horsefly\EmailTemplate;
use Horsefly\Exports\ApplicantEmailJobExport;
use Maatwebsite\Excel\Facades\Excel;

class EmailController extends Controller
{
    public function index()
    {
        return view('administrator.emails.sent_emails');
    }

    public function getEmails(Request $request)
    {
        $auth_user = Auth::user();
        $result = SentEmail::orderBy('id', 'DESC');

        $aColumns = ['email_added_date','email_added_time','sent_from','sent_to','cc_emails','subject'];

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

            $result->Where(function($query) use ($sKeywords) {
                $query->orWhere('sent_from', 'LIKE', "%{$sKeywords}%");
                $query->orWhere('sent_to', 'LIKE', "%{$sKeywords}%");
                $query->orWhere('subject', 'LIKE', "%{$sKeywords}%");
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
        $officeData = $result->get();

        $iTotal = $iFilteredTotal;
        $output = array(
             "sEcho" => intval($request->get('sEcho')),
             "iTotalRecords" => $iTotal,
             "iTotalDisplayRecords" => $iFilteredTotal,
             "aaData" => array()
        );
        $i = 0;
        
        foreach ($officeData as $oRow) {
            $format1 = 'Y-m-d';
            $format2 = 'H:i:s';
            $date = Carbon::parse($oRow->created_at)->format($format1);
            $time = Carbon::parse($oRow->created_at)->format($format2);

                $notes = '';
                $notes .= '<a href="#" class="notes_history" data-email="' . $oRow->id . '"
                                 data-controls-modal="#notes_history' . $oRow->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#notes_history' . $oRow->id . '">History</a>';
                $notes .= '<div id="notes_history' . $oRow->id . '" class="modal fade" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><span id="modal_title"></span></h5>
                            <button type="button" class="close email_modal_close" data-dismiss="modal">&times;</button>
                        </div>
                        <form class="form-horizontal email_modal">
                            <div class="form-group row">
                            <div class="col-sm-2">
                                   <label>Email To: </labe>
                                               </div>
                                               <div class="col-sm-10">
                                   <input type="text" name="email_to" readonly id="email_to" class="form-control"/>
                                               </div>
                                </div>

       <div class="form-group row">
       <div class="col-sm-2">
       <label>CC: </labe>
                   </div>
                            <div class="col-sm-10">
                                    <input type="text" name="cc_emails" readonly id="cc_emails" class="form-control"/>
                                               </div>
                                </div>
                                <div class="form-group row">
                                <div class="col-sm-2">
                                <label>Subject: </labe>
                                            </div>
                            <div class="col-sm-10">
                            <input type="text" name="email_title" readonly id="email_title" class="form-control"/>
                                               </div>
                                </div>
                                <div class="form-group row">
                                <div class="col-sm-12">
                                <div class="editable" contenteditable="false" id="office_notes_history' . $oRow->id . '" placeholder="Email body..."></div>
                                                   </div>
                                    </div>
                                    <div class="modal-footer">
                                    <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>
                            </div>
                            </form>
                    </div>
                </div>
            </div>'; 

                // $notes .= '<div id="notes_history' . $oRow->id . '" class="modal fade" tabindex="-1">';
                // $notes .= '<div class="modal-dialog modal-lg">';
                // $notes .= '<div class="modal-content">';
                // $notes .= '<div class="modal-header">';
                // $notes .= '<h6 class="modal-title"><span class="font-weight-semibold" id="title_text"></span></h6>';
                // $notes .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                // $notes .= '</div>';
                // $notes .= '<div class="modal-body" id="office_notes_history' . $oRow->id . '" style="max-height: 500px; overflow-y: auto;">';


                // $notes .= '</div>';
                // $notes .= '<div class="modal-footer">';
                // $notes .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                // $notes .= '</div>';
                // $notes .= '</div>';
                // $notes .= '</div>';
                // $notes .= '</div>';

                '<div id="notes_history' . $oRow->id . '" class="modal fade" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><span class="font-weight-semibold" id="title_text">Send Quality Clear Email To Applicant</span></h5>
                                    <button type="button" class="close email_modal_close" data-dismiss="modal">&times;</button>
                                </div>
                                    <div class="form-group row">
                                    <div class="col-sm-12">
                                            <input type="text" name="quality_email" id="quality_email" class="form-control"/>
                                                       </div>
                                        </div>

               <div class="form-group row">
                                    <div class="col-sm-12">
                                            <input type="text" name="cc_emails" id="cc_emails" class="form-control"/>
                                                       </div>
                                        </div>
                                        <div class="form-group row">
                                    <div class="col-sm-12">
                                    <input type="text" name="email_title" id="email_title" class="form-control"/>
                                                       </div>
                                        </div>
                                        <div class="form-group row">
                                        <div class="col-sm-12">
                                        <div class="editable" contenteditable="true" id="office_notes_history' . $oRow->id . '" placeholder="Email body..."></div>
                                                           </div>
                                            </div>
                                            <div class="modal-footer">
                                            <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>
                                    </div>
                            </div>
                        </div>
                    </div>';

            $output['aaData'][$i] = array(
                "DT_RowId" => "row_{$oRow->id}",
            //    @$checkbox,
                @$oRow->email_added_date,
                @$oRow->email_added_time,
                @$oRow->sent_from,
                @$oRow->sent_to,
                @$oRow->cc_emails,
                @$oRow->subject,
                @$notes
            );
            
            $i++;
        }

         echo json_encode($output);
    }
	
	 public function sentEmailUpdate(Request $request)
    {
        // Validate the request data
        $request->validate([
            'id' => 'required|integer|exists:sent_emails,id',
            'email' => 'required|email',
        ]);
    
        // Find the record and update
        $exist = SentEmail::find($request->id);
    
        if ($exist) {
            $exist->update(['sent_to' => $request->email,'status'=>'0']);
    
            return response()->json([
                'success' => true,
                'message' => 'Email updated successfully.',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Email update failed.',
            ], 400); // Use 400 Bad Request status code for failure
        }
    }

    public function sendEmailDelete(Request $request)
    {
        // Validate the request data
        $request->validate([
            'id' => 'required|integer|exists:sent_emails,id',
        ]);
    
        try {
            // Find the record
            $exist = SentEmail::find($request->id);
    
            if ($exist) {
                $exist->delete();
    
                return response()->json([
                    'success' => true,
                    'message' => 'Email deleted successfully.',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found.',
                ], 404); // Use 404 Not Found status code if the record is not found
            }
        } catch (\Exception $e) {
            // Handle any unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the email.',
            ], 500); // Use 500 Internal Server Error status code for unexpected errors
        }
    }

    public function getEmailDetails(Request $request)
    {
        $email_id = $request->input('email_id');
        $data = SentEmail::find($email_id);
        return response()->json(['success'=> 'success', 'data' => $data]);
    }
	
	public function emailTemplate($id)
	{
        $job_result=Sale::find($id);
        $unit=\Horsefly\Unit::where('head_office',$job_result->head_office)->first();
        $job_category=$job_result->job_category;
        $radius=8;
        $job_postcode = $job_result->postcode;
        $job_title = $job_result->job_title;
        $job_title_prop=null;
        if ($job_title == "nonnurse specialist"){
            $job_title_prop =  $job_result->job_title_prof;
        }
       
		$near_by_applicants = $this->distance($job_result->lat, $job_result->lng, $radius, $job_title,$job_title_prop,$job_category);
		$html ="<html><h3>test email</h3><p>hello there here we have something</p></html>";
		$template = EmailTemplate::where('title','generic_email')->first();
		$data = $template->template;

		return view('administrator.emails.job_to_send_email_applicant', compact('job_result','unit','data','near_by_applicants'));

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

    function distance($lat, $lon, $radius, $job_title,$job_title_prop=null,$job_category)
    {
         //$limit=600;
        $location_distance = Applicant::with('cv_notes')->select(\Illuminate\Support\Facades\DB::raw("*, ((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) + 
                COS($lat * PI() / 180) * COS(lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) 
                AS distance"))->having("distance", "<", $radius)->orderBy("distance")
            ->where(array("status" => "active", "is_in_nurse_home" => "no","is_blocked" => "0", 'is_callback_enable' => 'no','is_no_job' => '0')); //->get();

        if ($job_title_prop!=null){
            $job_title_cate=$job_title_prop;
            $specialList=Specialist_job_titles::where('id',$job_title_cate)->where('specialist_prof','%','Chef')->get();
            if (!isset($specialList) && $specialList->specialist_prof==""){

            }
            $location_distance = $location_distance->where("job_title_prof", $job_title_cate)->get();

        }else{
            $validDomains = ['.com', '.msn','.net','.uk','.gr'];
            $location_distance = $location_distance->where("job_category",$job_category)
                ->orWhere("applicant_job_title",$job_title)
                ->whereNotNull('applicant_email')
             ->get();
             $validEmailAddresses=$location_distance->filter(function ($user) use ($validDomains) {
                if (is_null($user->applicant_email) ||!filter_var($user->applicant_email, FILTER_VALIDATE_EMAIL)
                    || preg_match('/^[A-Za-z0-9._%+-]+@example\.com$/',$user->applicant_email) ||
                    strpos($user->applicant_email, '@') === false ) {
                    return false; // Exclude if email is null
                }
                foreach ($validDomains as $substring) {
                    if (strpos($user->applicant_email, $substring) !== false) {
                        return true; // Exclude if the email contains the substring
                    }
                }
                return false; // Include if no excluded substrings are found
            });
			//->take($limit)
		}
        return $validEmailAddresses;
    }
    public function sentEmailJobToApplicants(Request $request){
        try {
            date_default_timezone_set('Europe/London');

            $emailData=$request->app_email;
            $subject = $request->input('email_title');
            $body = $request->input('email_body');
           if ($emailData!=null){
                $mailData = [
                    'subject' => $subject,
                    'body' => $body
                ];
//                $eMail=[
//                    'developers@ibstec.com',
//                   'sunnydev0223@gmail.com'
//                ];
                $dataEmail=explode(',',$emailData);

                $countEmailCheckLimit = EmailCountPerDay::where('date', Carbon::now()->format('Y-m-d'))->first();
                if ($countEmailCheckLimit!=null) {
                  $totalEmailCount=$countEmailCheckLimit->Email_count_per_day+count($dataEmail);
                    if ($totalEmailCount<=1500){
                        //$job=new SendQueueEmail($dataEmail,$mailData);
                        //dispatch($job)->delay(30);
                        //$countEmailCheckLimit->update([
                          //  'Email_count_per_day' => $countEmailCheckLimit->Email_count_per_day + count($dataEmail),
                        //]);
                    }
                    else{
                        return  response()->json(['status'=>false,'message'=>'Limit send emails override'],422);
                    }

                } else {
                    //$job=new SendQueueEmail($dataEmail,$mailData);
                    //dispatch($job)->delay(30);
                    //EmailCountPerDay::create([
                      //  'Email_count_per_day' => count($dataEmail),
                        //'date' => Carbon::now()->format('Y-m-d')
                    //]);

                }
           }
       return response()->json(['status'=>true,'message'=>'Email sent successfully'],200);
        }catch (\Exception $e){
        return  response()->json(['status'=>false,'message'=>$e->getMessage()],422);
        }
    }
	public function exportEmails(Request $request){
        $emailData=$request->app_email;
        $dataEmail=explode(',',$emailData);
        $data=[];
        foreach ($dataEmail as $email){
            $data[]=$email;
        }
     return Excel::download(new ApplicantEmailJobExport($data), 'applicants.csv');
    }

}
