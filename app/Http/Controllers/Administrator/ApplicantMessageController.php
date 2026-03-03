<?php

namespace Horsefly\Http\Controllers\Administrator;

use Illuminate\Http\Request;
use Horsefly\Http\Controllers\Controller;
use Horsefly\Applicant_message;
use Auth;
use Horsefly\User;
use Horsefly\Notifications\smsNotification;
use Illuminate\Support\Facades\Notification;
use Horsefly\Applicant;
use Illuminate\Support\Facades\Mail;
use Horsefly\SentEmail;
use Horsefly\Mail\RandomEmail;
use Horsefly\Mail\GenericEmail;
use DB;
use Response;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ApplicantMessageController extends Controller
{
	//public function __construct()
    //{
        //$this->middleware('auth');
        //$this->middleware('permission:applicant_message-send', ['only' => //['sendMessagesApplicants','saveSendMessagesApplicants','sendMessage']]);

    //}
    public function index()
    {
        $data = Applicant_message::with('user')
        ->join('applicants', 'applicants.id', '=', 'applicant_messages.applicant_id')
        ->select("applicant_messages.*", "applicants.applicant_name as applicant_name", "applicants.applicant_job_title as job_title")
        ->latest('applicant_messages.created_at')
		->paginate(20);
        return view('administrator.chat.inbox',compact('data'));
    }
	public function sendEmailsWithCrons(){
         // Get unsent emails in batches of 100
         $unsentEmails = SentEmail::where('status', '0')->where('action_name','Random Email')->take(100)->get();

         if(count($unsentEmails) > 0){
             foreach ($unsentEmails as $emailRecord) {
                 $mailData = [
                     'subject' => $emailRecord->subject,
                     'body' => $emailRecord->template
                 ];
 
                 if($emailRecord->action_name == 'Random Email'){
                     Mail::to($emailRecord->sent_to)->send(new RandomEmail($mailData)); // Send the email
                 }else{
                     Mail::to($emailRecord->sent_to)->send(new GenericEmail($mailData)); // Send the email
                 }
 
                 // Update the status to 1 (sent)
                 $emailRecord->status = '1';
                 $emailRecord->save();
 
                 // Log or additional processing if needed
             }
 				
            return 'Unsent emails processed successfully.';
         }else{
            return 'There is no data.';
         }
    }
	public function getNotifications()
    {
        try {
            $user_info = Applicant_message::join('applicants', 'applicant_messages.applicant_id', '=', 'applicants.id')
                ->select('applicant_messages.*', 'applicants.applicant_name', 'applicants.applicant_postcode', DB::raw('count(applicant_messages.applicant_id) as total'))
                ->where('applicant_messages.is_read', '0')
                ->where('applicant_messages.status', 'incoming')
                ->orderBy('applicant_messages.created_at', 'desc')
                ->groupBy('applicant_messages.applicant_id')
                ->get();

            return response()->json($user_info);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
	
	public function fetch_data(Request $request)
    {
        $search = $request->get('query');

		$userIds = DB::table('users')
            ->where('name', 'LIKE', '%' . $search . '%')
            ->pluck('id')
            ->toArray(); // Convert to an array to prevent query issues

        $query = Applicant_message::select(
                "applicant_messages.*",
                "applicants.applicant_name as applicant_name",
				"applicants.applicant_job_title as job_title",
                "users.name as user_name"
            )
            ->leftJoin('applicants', 'applicants.id', '=', 'applicant_messages.applicant_id')
            ->leftJoin('users', 'users.id', '=', 'applicant_messages.user_id') // Join users table
            ->when(!empty($search), function ($q) use ($search, $userIds) {
                $q->where(function ($query) use ($search, $userIds) {
                    $query->where('applicants.applicant_name', 'LIKE', '%' . $search . '%')
                        ->orWhere('applicant_messages.message', 'LIKE', '%' . $search . '%')
                        ->orWhere('applicants.applicant_email', 'LIKE', '%' . $search . '%')
                        ->orWhere('applicants.applicant_postcode', 'LIKE', '%' . $search . '%')
                        ->orWhere('applicants.applicant_phone', 'LIKE', '%' . $search . '%')
                        ->orWhere('applicants.applicant_homePhone', 'LIKE', '%' . $search . '%')
						->orWhere('applicants.applicant_job_title', 'LIKE', '%' . $search . '%')
                        ->orWhere('applicants.job_category', 'LIKE', '%' . $search . '%');

                    // Filter messages by user_id if any matching user IDs are found
                    if (!empty($userIds)) {
                        $query->orWhereIn('applicant_messages.user_id', $userIds);
                    }
                });
            })
            ->with('user') // Eager load the user relationship
            ->orderBy('applicant_messages.created_at', 'desc') // Order by latest message
            ->distinct() // Ensure unique messages
            ->paginate(20);

        if ($request->ajax()) {
            return view('administrator.chat.search_partial', ['data' => $query])->render();
        } else {
            return view('administrator.chat.inbox', ['data' => $query]);
        }
    }
	
/*
    public function messageReceive(Request $request){
	 	$phoneNumber_gsm = $request->Input('phoneNumber');	
	 	$phoneNumber=str_replace("+44","0", $phoneNumber_gsm);
	 	$message = $request->Input('message');
	 	$msg_id = substr(md5(time()), 0, 14);
	 	$date_time = $request->Input('time');
	 	$date_time_arr = explode(" ", $date_time);
	 	$date_res = $date_time_arr[0];
	 	$date = str_replace("/", "-", $date_res);
	 	$time = $date_time_arr[1];

	 	$applicant_data = Applicant::where('applicant_phone',$phoneNumber)->first();
		if($applicant_data){
			 $applicant_msg = new Applicant_message();
			 $applicant_msg->applicant_id = $applicant_data['id'];
			 $applicant_msg->user_id = '1';
			 $applicant_msg->msg_id = $msg_id;
			 $applicant_msg->message = $message;
			 $applicant_msg->phone_number = $phoneNumber;
			 $applicant_msg->date = $date;
			 $applicant_msg->time = $time;
			 $applicant_msg->status = 'incoming';
			 $applicant_msg->is_read = '0';
			 $applicant_msg->save();
		}else{
			 return response()->json(['message' => 'Phone number not found in Applicant'], 404);
		}
     }
	*/
	public function messageReceive(Request $request){
	 	$phoneNumber_gsm = $request->Input('phoneNumber');	
	 	$phoneNumber=str_replace("+44","0", $phoneNumber_gsm);
	 	$message = $request->Input('message');
	 	$msg_id = substr(md5(time()), 0, 14);
	 	$date_time = $request->Input('time');
	 	$date_time_arr = explode(" ", $date_time);
	 	$date_res = $date_time_arr[0];
	 	$date = str_replace("/", "-", $date_res);
	 	$time = $date_time_arr[1];

	 	$applicant_data = Applicant::where('applicant_phone',$phoneNumber)->first();
		if($applicant_data){
			 $applicant_msg = new Applicant_message();
			 $applicant_msg->applicant_id = $applicant_data['id'];
			 $applicant_msg->user_id = '1';
			 $applicant_msg->msg_id = $msg_id;
			 $applicant_msg->message = $message;
			 $applicant_msg->phone_number = $phoneNumber;
			 $applicant_msg->date = $date;
			 $applicant_msg->time = $time;
			 $applicant_msg->status = 'incoming';
			 $applicant_msg->is_read = '0';
			 $applicant_msg->save();
		}else{
			 return response()->json(['message' => 'Phone number not found in Applicant'], 404);
		}
     }
	/*
	public function messageReceive(Request $request)
    {
       // Get the phone number as input
		$phoneNumber_gsm = $request->input('phoneNumber');

		// Ensure the phone number is treated as a string
		$phoneNumber_gsm = (string)$phoneNumber_gsm;

		// Check if the phone number starts with '+44' or '44' (after converting to string)
		if (substr($phoneNumber_gsm, 0, 3) === "+44") {
			// If it starts with '+44', replace with '0'
			$phoneNumber = "0" . substr($phoneNumber_gsm, 3);
		} elseif (substr($phoneNumber_gsm, 0, 2) === "44") {
			// If it starts with '44' (without '+'), replace with '0'
			$phoneNumber = "0" . substr($phoneNumber_gsm, 2);
		} else {
			// Otherwise, leave the phone number unchanged
			$phoneNumber = $phoneNumber_gsm;
		}
        
        $message = $request->input('message');
        $msg_id = substr(md5(time()), 0, 14);
        $date_time = $request->input('time');
        $date_time_arr = explode(" ", $date_time);
        $date_res = $date_time_arr[0];
        $date = str_replace("/", "-", $date_res);
        $time = $date_time_arr[1];

        // Check if the phone number exists in the 'Applicant' table
        $applicant_data = Applicant::where('applicant_phone', $phoneNumber)->first();

        if ($applicant_data) {
            // Save the message to 'Applicant_message' table
            $applicant_msg = new Applicant_message();
            $applicant_msg->applicant_id = $applicant_data['id'];
            $applicant_msg->user_id = '1';  // You may want to make this dynamic
            $applicant_msg->msg_id = $msg_id;
            $applicant_msg->message = $message;
            $applicant_msg->phone_number = $phoneNumber;
            $applicant_msg->date = $date;
            $applicant_msg->time = $time;
            $applicant_msg->status = 'incoming';
            $applicant_msg->is_read = '0';
            $applicant_msg->save();

			return response()->json(['message' => 'Message saved successfully'],200);
        } else {
            // If not found in 'Applicant', check 'temp_contacts_spkbp'
            $temp_contact = DB::table('temp_contacts_spkbp')->where('phone', $phoneNumber)->first();

            if ($temp_contact) {
                // Prepare data to send as query parameters
                $data = [
                    'phoneNumber' => $phoneNumber,
                    'message' => $message,
                    'time' => $date_time,  // Pass the original datetime
                ];

                // Create the query string for GET request
                $queryString = http_build_query($data);

                // Send the data using a GET request via cURL
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, "https://specialist.bilalmedicalcentre.com/message_receive?" . $queryString);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Get the response

                // Execute the cURL request and capture the response
                $response = curl_exec($ch);

                // Check for cURL errors
                if (curl_errno($ch)) {
                    return response()->json(['error' => 'Failed to send message to external site: ' . curl_error($ch)], 500);
                }

                // Close cURL session
                curl_close($ch);

                // Log or return the response from the external API if needed
                return response()->json(['message' => 'Message sent to external site successfully', 'response' => $response]);
            } else {
                return response()->json(['message' => 'Phone number not found in Applicant or temp_contacts_spkbp'], 404);
            }
        }
    }
	*/
    public function getUserMessages(Request $request)
    {
        $user = Auth::user();
        $applicant_id = $request->Input('applicant_id');
        $records_per_page = $request->Input('records_per_page');
        $skip = ($records_per_page-1)*10;
        $take = 10;
        $applicant_messages='';
		$applicant = Applicant::where('id', $applicant_id)->first();
     //    if($user->id == '1' || $user->id == '101')
     //   {
            $applicant_messages = Applicant_message::with('user')->where('applicant_id',$applicant_id)
            ->skip($skip)
            ->take($take)
            ->orderBy('created_at','asc')
            ->get();
            Applicant_message::where(['applicant_id' => $applicant_id,'is_read' => '0'])->update(['is_read' => '1']);   
    //    }
   //     else
    //    {
    //        $applicant_messages = Applicant_message::with('user')->where('applicant_id',$applicant_id)->whereIn('user_id',[0,$user->id])
    //        ->skip($skip)
    //        ->take($take)
   //         ->get();

	//		if ($applicant_messages->count()!=0){
     //           return response()->json(['msg'=> 'SUCCESS','data'=>$applicant_messages]);

     //       }else{
        //     return response()->json(['status'=>false,'msg'=> 'SUCCESS','data'=>'']);
//
      //      }

   //         Applicant_message::where(['applicant_id' => $applicant_id,'is_read' => '0'])->update(['is_read' => '1']);
    //    }
        
        return response()->json(['msg'=> 'SUCCESS','data'=>$applicant_messages,'applicant_name'=>$applicant ? $applicant->applicant_name : '']);
        

    }
	
	public function getCrmAppMessages(Request $request)
    {
        $user = Auth::user();
        $applicant_id = $request->Input('applicant_id');
        $records_per_page = $request->Input('records_per_page');
        $skip = ($records_per_page-1)*10;
        $take = 10;
        $applicant_messages='';
        $applicant_messages = Applicant_message::join('users', 'applicant_messages.user_id', '=', 'users.id')
            ->join('applicants', 'applicant_messages.applicant_id', '=', 'applicants.id')
            ->select('applicant_messages.*','users.name','applicants.applicant_name')
            ->where('applicant_messages.applicant_id',$applicant_id)
            ->skip($skip)
            ->take($take)
            ->orderBy('applicant_messages.created_at','DESC')
            ->get();
        
        return response()->json(['msg'=> 'SUCCESS','data'=>$applicant_messages]);
        

    }

    public function storeUserMessages(Request $request)
    {
        $current_user = Auth::user()->id;
		$user_name = Auth::user()->name;
        $user_all=User::whereIn('id',['1',$current_user])->get();
        // $user=User::find(1);
        $user_id = Auth::user()->id;
		$msgType = $request->Input('msgType');
        $applicant_id = $request->Input('applicant_chatbox_id');
        $applicant_data = Applicant::select('id','applicant_name','applicant_postcode','applicant_phone')->where('id',$applicant_id)->first();
        $applicant_notification_data = $applicant_data->id.':'.$applicant_data->applicant_name.'('.$applicant_data->applicant_postcode.') -'.$applicant_data->applicant_phone;
        $applicant_phone = $request->Input('msg_phone');
        $applicant_msg_time = $request->Input('msg_time');
        $applicant_msg_text = $request->Input('applicant_msg');
        $applicant_msg_id = $request->Input('msg_send_id');
        $date_arr= explode(" ", $applicant_msg_time);
		if(!empty($date_arr))
		{
			$msg_date = $date_arr[0];
        	$msg_time = $date_arr[1];
		}
		else	
		{
			$msg_date = date("Y-m-d");
			$msg_time = date("h:i:sa");
		}
        

        // $result=Applicant_message::insert(
        //     ['applicant_id' =>  $applicant_id, 'user_id' => $user_id, 'msg_id' => $applicant_msg_id, 'message' => $applicant_msg_text, 'phone_number' => $applicant_phone,
        //     'date' => $msg_date, 'time' => $msg_time, 'status' => 'outgoing', 'is_read' => '1', 'created_at' => $applicant_msg_time, 'updated_at' => $applicant_msg_time]);

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
        $applicant_msg->save();
        //Notification::send($user_all, new smsNotification($applicant_notification_data));
        // $applicant_messages = Applicant_message::where('applicant_id',$applicant_id)->get();
		
                Applicant_message::where(['applicant_id' => $applicant_id,'is_read' => '0'])->orderBy('created_at', 'desc')->take(1)->update(['is_read' => '1']);  
            
        return response()->json(['msg'=> 'success','data'=>$applicant_msg, 'user_name' => $user_name]);
    }
	
	public function markMessageAsRead()
    {
        // echo 'here message read';exit();
        Applicant_message::where(['is_read' => '0'])->update(['is_read' => '1']);   
        $user_info = Applicant_message::join('applicants', 'applicant_messages.applicant_id', '=', 'applicants.id')
        ->select('applicant_messages.*','applicants.applicant_name','applicants.applicant_postcode',DB::raw('count(applicant_messages.applicant_id) as total'))
        ->where('applicant_messages.is_read','0')
        ->where('applicant_messages.status','incoming')
        ->groupBy('applicant_messages.applicant_id')
        ->get();
        return view('administrator.crm.index', compact('user_info'));

    }
	
	public function sendMessage()
    {
        $user_info = Applicant_message::join('applicants', 'applicant_messages.applicant_id', '=', 'applicants.id')
        ->select('applicant_messages.*','applicants.applicant_name','applicants.applicant_postcode',DB::raw('count(applicant_messages.applicant_id) as total'))
        ->where('applicant_messages.is_read','0')
        ->where('applicant_messages.status','incoming')
        ->orderBy('applicant_messages.created_at', 'desc')
        ->groupBy('applicant_messages.applicant_id')
        ->get();
        return view('administrator.messages.send_message', compact('user_info'));
    }
	
	public function sendMessagesApplicants(Request $request)
    {
        $query_string = $request->Input('query_string');
        $new_string = str_replace(" ","%20",$query_string);
		$url = preg_replace("/(*BSR_ANYCRLF)\R/",'',$new_string);
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
            return response()->json(['success'=> 'SMS Sent successfully!','data'=>$response,'phonenumber'=>$phone,'time'=>$time,'report'=>$report]);

        } elseif ($report == "sending") {
                        return response()->json(['success'=> 'SMS is sending, please check later!','data'=>$response,'phonenumber'=>$phone,'time'=>$time,'report'=>$report]);

        } else {
            return response()->json(['error'=> 'SMS is failed, please check your device or settings!','data'=>$response,'report'=>$report, 'query_string' =>$query_string]);
        }
        // return response()->json(['msg'=> 'success not','data'=>$response]);
        }
        else
        {
            return response()->json(['error'=> 'something went wrong','data'=>$response,'query_string' => $url]);

        }

    }

    public function saveSendMessagesApplicants(Request $request)
    {

        $user_id = Auth::user()->id;
        $userData = json_decode($request->Input('data'), true);
        $success_numbers = array();
        $fail_numbers = array();
        foreach($userData['report'][0]  as  $data)
        {
            foreach($data as $result)
            {
                $phone = trim($result['phonenumber']);
                $applicant_data = Applicant::select('id')->where(['applicant_phone' => $phone, 'status' => 'active'])->first();
                if($applicant_data)
                {
                    $applicant_id = $applicant_data->id;
                    $applicant_msg_id = 'D'.mt_rand(1000000000000, 9999999999999);
                    $applicant_phone = $result['phonenumber'];
                    $applicant_msg_text = $userData['message'];
                    $applicant_msg_time = $result['time'];
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
                    $applicant_msg->save();
                    $success_numbers[] = $result['phonenumber'];
                }
                else
                {
                    $fail_numbers[] = $result['phonenumber'];
                }
            }
            

        }
        return response()->json(['success'=> 'success','success_data'=>$success_numbers, 'error_data' => $fail_numbers]);


    }
	
	public function saveReqSendMessage(Request $request)
    {
        $user_id = Auth::user()->id;
        $userData = json_decode($request->Input('data'), true);
        $success_numbers = array();
        $fail_numbers = array();
        $data = $userData['report'][0];
            foreach($data as $result)
            {
                $phone = trim($result[0]['phonenumber']);
                $applicant_data = Applicant::select('id')->where(['applicant_phone' => $phone, 'status' => 'active'])->first();
                if($applicant_data)
                {
                    $applicant_id = $applicant_data->id;
                    $applicant_msg_id = 'D'.mt_rand(1000000000000, 9999999999999);
                    $applicant_phone = $result[0]['phonenumber'];
                    $applicant_msg_text = $userData['message'];
                    $applicant_msg_time = $result[0]['time'];
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
                    echo $res.' result';exit();
                    $success_numbers[] = $result[0]['phonenumber'];
                }
                else
                {
                    $fail_numbers[] = $result[0]['phonenumber'];
                }
            }
            

        return response()->json(['success'=> 'success','success_data'=>$success_numbers, 'error_data' => $fail_numbers]);


    }
	   public function chatNewDesign(Request $request){

        date_default_timezone_set('Europe/London');

  $searchQuery = $request->input('search');
        if ($searchQuery) {

            // Handle search with the provided searchQuery
            $latestMessagesN = Applicant_message::whereIn('status', ['incoming', 'outgoing'])
                ->whereHas('applicants', function ($q) use ($searchQuery) {
                    $q->where('applicant_name', 'LIKE', '%' . $searchQuery . '%');
                })
                ->groupBy('applicant_id')
                ->selectRaw('MAX(id) as latest_message_id')
                ->get();

            $latestMessages = Applicant_message::whereIn('id', $latestMessagesN->pluck('latest_message_id'))
                ->with('applicants')
                ->orderByRaw('FIELD(is_read, 0, 1), created_at DESC')
                ->paginate(10);
            $iconClass = 'fas fa-sync';


            return view('administrator.messages.testChat', compact('latestMessages','iconClass'));
        } else {
            // Handle the default view without search
            $latestMessagesN = Applicant_message::whereIn('status',['incoming','outgoing'])
                ->groupBy('applicant_id')
                ->selectRaw('MAX(id) as latest_message_id')
                ->get();

            $latestMessages = Applicant_message::whereIn('id', $latestMessagesN->pluck('latest_message_id'))
                ->with('applicants')
                ->orderByRaw('FIELD(is_read, 0, 1),created_at desc')
                ->paginate(10);
            $iconClass = 'fas fa-search';
        return view('administrator.messages.testChat', compact('latestMessages','iconClass'));
    }

    }
  
    public function applicantChatHistory($id){
        try {
            date_default_timezone_set('Europe/London');
            $applicantMessage=Applicant_message::with('applicants','user')->where('applicant_id',$id)->get();
            if ($applicantMessage != null){
                foreach ($applicantMessage as $item){
                    $unreadMessageUpdate=Applicant_message::where('id',$item->id)->where('status','incoming')->where('is_read',0)->first();
                    if ($unreadMessageUpdate !=null){
                        $unreadMessageUpdate->update([
                           'is_read'=>1
                        ]);
                    }
                }
                return response()->json(['status'=>true,'data'=>$applicantMessage],200);

            }
            // check last message is read or not then read is 0 then update query read message
        }catch (\Exception $exception){
             return response()->json(['status'=>false,'Message'=>$exception->getMessage()],422);

        }

    }
    public function sendMessageApplcant(Request $request){
        try {
            $auth_id=\Illuminate\Support\Facades\Auth::id();
             $applicant=Applicant::where('id',$request->applicant_id)->first();
			
				//dd($applicant);
            // send sms user open gatway used
		 $applicant_msg_id = 'D'.mt_rand(1000000000000, 9999999999999);

            $sms_res = $this->addApplicantSms($applicant->applicant_phone, $request->message);
            if($sms_res == 'success')
            {
               $saveMessage= Applicant_message::create([
                    'applicant_id'=>$request->applicant_id,
                    'is_read'=>1,
                    'status'=>'outgoing',
                    'user_id'=>$auth_id,
                    'date'=>Carbon::now()->format('Y-m-d'),
                    'time'=>Carbon::now()->format('H:i:s'),
                    'message'=>$request->message,
					 'msg_id'=>$applicant_msg_id,

                ]);
				//dd($saveMessage);
				  return response()->json(['status'=>true],200);
            }
            else
            {
                $sms_res = 'And there is error sending sms...';
				 return response()->json(['status' => false], 422);
            }

//            return redirect('chatNewDesign');
          
			

        }catch (\Exception $exception){
			
        return response()->json(['status'=>false,'Message'=>$exception->getMessage()],422);


        }

    }
	public function addApplicantSms($applicant_number, $applicant_message)
    {
//        dd($applicant_message,$applicant_number);
//        $applicant_number1 = '07597019065';
//        $applicant_message = "Dear $applicant_name, We have come across your profile on an Online Portal and have been highly impressed with your extensive experience as a nurse. Your expertise and skills make you a valuable asset, and we believe that we can find you a position that aligns with your needs and offers great benefits. We would be delighted to schedule a conversation with you to get to know you better and introduce ourselves. Please let us know a convenient time for you to discuss further. You may reply to this message or reach out to us using the contact information provided below. Best regards: Recruitment Team. T: 01494211220 E: info@kingsburypersonnel.com";
        // $applicant_message = "Hi $applicant_name, I came across your profile on $applicant_source and was immediately impressed with your experience as a nurse, I think that your expertise as a nurse would help us in finding you a position according to your needs.  We have been impressed with your background and would like to formally offer you the  positions of Nurse. We have positions day or nights with an Great salary packages available. I d like to talk to you further so I can get to know you better and introduce our company to you. Please let us know best time to contact you. Best regards, Sohaib | Business Manager And Communications Tel: 01494211220 Email: Info@kingsburypersonnel.com";
        $query_string = 'http://milkyway.tranzcript.com:1008/sendsms?username=admin&password=admin&phonenumber='.$applicant_number.'&message='.$applicant_message.'&port=2&report=JSON&timeout=0';
//        dd($query_string);
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
	
	    public function storeUserMessagesOpenVox(Request $request)
    {
			      
			date_default_timezone_set('Europe/London');

        $current_user = Auth::user()->id;
//        echo 'get user messages'.$current_user;exit();
        $user_name = Auth::user()->name;
        $user_all=User::whereIn('id',['1',$current_user])->get();
        $user_id = Auth::user()->id;
        $msgType = $request->Input('msgType');
        $applicant_id = $request->Input('applicant_chatbox_id');
        $applicant_data = Applicant::select('id','applicant_name','applicant_postcode','applicant_phone')->where('id',$applicant_id)->first();
        $applicant_notification_data = $applicant_data->id.':'.$applicant_data->applicant_name.'('.$applicant_data->applicant_postcode.') -'.$applicant_data->applicant_phone;
        $applicant_phone = $request->Input('msg_phone');
        $applicant_msg_time = $request->Input('msg_time');
        $applicant_msg_text = $request->Input('applicant_msg');
        $applicant_msg_id = $request->Input('msg_send_id');
//        $date_arr= explode(" ", $applicant_msg_time);
        $msg_date = date("Y-m-d");
        $msg_time = date("h:i A");
        $query_string = 'http://milkyway.tranzcript.com:1008/sendsms?username=admin&password=admin&phonenumber='.$applicant_phone.'&message='.$applicant_msg_text.'&port=2&report=JSON&timeout=0';

        $sendSms=$this->sendQualityClearSms($query_string);
			//dd($sendSms);
//        $sendSms['result']='success';
        if($sendSms['result'] == 'success')
        {
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
            $applicant_msg->save();

            //if ($msgType == 'crm') {
                Applicant_message::where(['applicant_id' => $applicant_id, 'is_read' => '0'])->orderBy('created_at', 'desc')->take(1)->update(['is_read' => '1']);
            //}
//            Notification::send($user_all, new smsNotification($applicant_notification_data));


            return response()->json(['success' => true, 'data' => $applicant_msg, 'user_name' => $user_name]);
        }
    }
    public function sendQualityClearSms($data)
    {
        $query_string = $data;
        $url = str_replace(" ","%20",$query_string);
        $link = curl_init();
        curl_setopt($link, CURLOPT_HEADER, 0);
        curl_setopt($link, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($link, CURLOPT_URL, $url);
		//curl_setopt($link, CURLOPT_URL, $url);
        curl_setopt($link, CURLOPT_TIMEOUT, 10);
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

}
