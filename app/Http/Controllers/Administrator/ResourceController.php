<?php
namespace Horsefly\Http\Controllers\Administrator;

use Horsefly\Applicant;
//use Horsefly\Observers\ActionObserver;
use Horsefly\Exports\ApplicantsExport;
use Horsefly\Exports\ResourcesExport;
use Horsefly\Exports\Applicants_nurses_15kmExport;
use Horsefly\Exports\Applicants_nureses_7_days_export;
use Horsefly\Exports\AllRejectedApplicantsExport;
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
use Horsefly\Cv_note;
use Horsefly\History;
use Horsefly\Crm_note;
use Horsefly\ModuleNote;
use Horsefly\Crm_rejected_cv;
use Horsefly\Specialist_job_titles;
use Horsefly\Quality_notes;
use Horsefly\Sales_notes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Session;
use Carbon\Carbon;
use Pusher\Pusher;
//use Auth;
use Redirect;
use DateTime;
use Horsefly\EmailCountPerDay;
use Horsefly\Exports\Applicant_21_days_export;
use Horsefly\Exports\Applicant_2M_days_export;
use Illuminate\Support\Facades\Cache;

class ResourceController extends Controller
{
    public function __construct()
    {
        
        $this->middleware('auth');
        /*** Sub-Links Permissions */
        $this->middleware('permission:resource_Nurses-list', ['only' => ['getNurseSales','getNursingJob']]);
        $this->middleware('permission:resource_Non-Nurses-list', ['only' => ['getNonNurseSales','getNonNursingJob']]);
		        $this->middleware('permission:resource_Non-Nurses-specialist', ['only' => ['getNonNurseSpecialistSales','getNonNursingSpecialistJob']]);
        $this->middleware('permission:resource_Last-7-Days-Applicants', ['only' => ['getLast7DaysApplicantAdded','get7DaysApplicants']]);
        $this->middleware('permission:resource_Last-21-Days-Applicants', ['only' => ['getLast21DaysApplicantAdded','get21DaysApplicants']]);
        $this->middleware('permission:resource_All-Applicants', ['only' => ['getLast2MonthsApplicantAdded', 'get2MonthsApplicants']]);
        $this->middleware('permission:resource_Crm-All-Rejected-Applicants', ['only' => ['getAllCrmRejectedApplicantCv','allCrmRejectedApplicantCvAjax']]);
        $this->middleware('permission:resource_Crm-Rejected-Applicants', ['only' => ['getCrmRejectedApplicantCv','getCrmRejectedApplicantCvAjax']]);
        $this->middleware('permission:resource_Crm-Request-Rejected-Applicants', ['only' => ['getCrmRequestRejectedApplicantCv','getCrmRequestRejectedApplicantCvAjax']]);
        $this->middleware('permission:resource_Crm-Not-Attended-Applicants', ['only' => ['getCrmNotAttendedApplicantCv','getCrmNotAttendedApplicantCvAjax']]);
        $this->middleware('permission:resource_Crm-Start-Date-Hold-Applicants', ['only' => ['getCrmStartDateHoldApplicantCv','getCrmStartDateHoldApplicantCvAjax']]);
        $this->middleware('permission:resource_Crm-Paid-Applicants', ['only' => ['getCrmPaidApplicantCv','getCrmPaidApplicantCvAjax']]);
        /*** Callback Permissions */
        $this->middleware('permission:resource_Potential-Callback_list|resource_Potential-Callback_revert-callback', ['only' => ['potentialCallBackApplicants','getPotentialCallBackApplicants']]);
        $this->middleware('permission:resource_Potential-Callback_revert-callback', ['only' => ['getApplicantRevertToSearchList']]);
		                $this->middleware('permission:applicant_export', ['only' => ['export_7_days_applicants_date','export_Last21DaysApplicantAdded','export_Last2MonthsApplicantAdded','export_15_km_applicants','exportAllCrmRejectedApplicantCv','Export_CrmRejectedApplicantCv','exportCrmRequestRejectedApplicantCv','exportCrmNotAttendedApplicantCv','exportCrmStartDateHoldApplicantCv'
,'exportCrmPaidApplicantCv','exportPotentialCallBackApplicants']]);
		$this->middleware('permission:resource_Crm-All-Rejected-Applicants', ['only' => ['getRejectedAppDateWise','getRejectedAppDateWiseAjax']]);


    }
//    public function index(){
//        $sales = Office::join('sales', 'offices.id', '=', 'sales.head_office')
//            ->select('sales.*','offices.office_name')->where(['sales.status' => 'active','sales.job_category' => 'nurse'])->get();
//        echo '<pre>';print_r($sales->toArray());exit;
//        return view('administrator.resource.direct_listing',compact('sales'));
//    }
	
    public function getNurseSales()
    {
        // $sales = Office::join('sales', 'offices.id', '=', 'sales.head_office')
        //     ->join('units', 'units.id', '=', 'sales.head_office_unit')
        //     ->select('sales.*', 'offices.office_name', 'units.contact_name',
        //         'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
        //     ->where(['sales.status' => 'active', 'sales.job_category' => 'nurse'])->get();
        $value = '0';
        return view('administrator.resource.nursing', compact('value'));
    }
    
    public function getNursingJob(Request $request)
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
				
				$sale_notes = Sales_notes::select('sale_id','sales_notes.sale_note', DB::raw('MAX(created_at) as 
               sale_created_at'))
               ->groupBy('sale_id');
                $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
				
                //->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
                ->select('sales.*', 'offices.office_name', 'units.contact_name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number', DB::raw("(SELECT count(cv_notes.sale_id) from cv_notes
                WHERE cv_notes.sale_id=sales.id AND cv_notes.status='active' group by cv_notes.sale_id) as sale_count"))
                ->where(['sales.status' => 'active', 'sales.is_on_hold' => '0', 'sales.job_category' => 'nurse'
						 //, 'sales_notes.status' => 'active'
						])
					//->whereIn('sales.head_office', $user_permissions)
					->orderBy('id', 'DESC');
				
                
            }
            else
            {
				
				$sale_notes = Sales_notes::select('sale_id','sales_notes.sale_note', DB::raw('MAX(created_at) as 
               sale_created_at'))
               ->groupBy('sale_id');
                $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
			
            //->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
            ->select('sales.*', 'offices.office_name', 'units.contact_name',
                'units.contact_email', 'units.unit_name', 'units.contact_phone_number'
					 ,DB::raw("(SELECT count(cv_notes.sale_id) from cv_notes
                WHERE cv_notes.sale_id=sales.id AND cv_notes.status='active' group by cv_notes.sale_id) as sale_count"))
            ->where(['sales.status' => 'active', 'sales.is_on_hold' => '0', 'sales.job_category' => 'nurse'
					 //, 'sales_notes.status' => 'active'
					])->orderBy('id', 'DESC');
				
            }
    
        }
        else
        {

			
			$sale_notes = Sales_notes::select('sale_id','sales_notes.sale_note', DB::raw('MAX(created_at) as 
               sale_created_at'))
               ->groupBy('sale_id');
            $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
		
            //->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
            ->select('sales.*','offices.office_name', 'units.contact_name',
                'units.contact_email', 'units.unit_name', 'units.contact_phone_number', DB::raw("(SELECT count(cv_notes.sale_id) from cv_notes
                WHERE cv_notes.sale_id=sales.id AND cv_notes.status='active' group by cv_notes.sale_id) as sale_count"))
            ->where(['sales.status' => 'active', 'sales.is_on_hold' => '0', 'sales.job_category' => 'nurse'])
				->orderBy('sales.id', 'DESC');
			
			
        }
		
       

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
			$post_code = strtoupper($sRow->postcode);
            $checkbox = "<label class=\"mt-checkbox mt-checkbox-single mt-checkbox-outline\">
                             <input type=\"checkbox\" class=\"checkbox-index\" value=\"{$sRow->id}\">
                             <span></span>
                          </label>";

            $postcode = "<a href=\"/applicants-within-15-km/{$sRow->id}\">{$post_code}</a>";

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
			 $job_title_desc='';
            if(@$sRow->job_title_prof!='')
                {
                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $sRow->job_title_prof)->first();
                    $job_title_desc = $sRow->job_title.' ('.$job_prof_res->specialist_prof.')';
                    // $job_title_desc = @$sRow->job_title.' ('.@$sRow->job_title_prof.')';
                }
                else
                {
                    $job_title_desc = @$sRow->job_title;
                } 
		
           $date=EmailCountPerDay::where('date','=',Carbon::now()->format('Y-m-d'))->first();
            if ($user->is_admin==1){

            if (!isset($date)||$date->Email_count_per_day <= '1500') {
                $action = '<a href="' . url('/sent-email-applicants') . '/' . $sRow->id . '" data-id="' . $sRow->id . '" class="btn bg-teal legitRipple">Send Email</a>';
            }else{
                $action = '<a href="#"  class="btn bg-teal legitRipple disabled" title="Email sending limit completed per day">Send Email</a>';
            }

            }else{
				
                if ($user->hasAnyPermission(['applicant_sent-email-bulk'])){

                   $action = '<a href="' . url('/sent-email-applicants') . '/' . $sRow->id . '" data-id="' . $sRow->id . '" class="btn bg-teal legitRipple">Send Email</a>';

                }else{
				
                    $action='';

                }

            }
            $output['aaData'][] = array(
                "DT_RowId" => "row_{$sRow->id}",
                @$sRow->sale_added_date,
                @$sRow->sale_added_time,
				strtoupper($job_title_desc),
                @ucwords(strtolower($sRow->office_name)),
                @ucwords(strtolower($sRow->unit_name)),
                @$postcode,
                @ucwords($sRow->job_type),
                @$sRow->experience,
                @$sRow->qualification,
                @$sRow->salary,
                //@$sRow->sale_note,
				@$sRow->sale_count==$sRow->send_cv_limit?'<span class="badge w-100 badge-danger" style="font-size:90%">Limit Reached</span>':"<span class='badge w-100 badge-success' style='font-size:90%'>".((int)$sRow->send_cv_limit - (int)$sRow->sale_count)." Cv's limit remaining</span>",
				@$action,


            );
            $i++;
        }

        //  print_r($output);
        echo json_encode($output);
    }

    public function getNonNurseSales()
    {
        // $sales = Office::join('sales', 'offices.id', '=', 'sales.head_office')
        //     ->join('units', 'units.id', '=', 'sales.head_office_unit')
        //     ->select('sales.*', 'offices.office_name', 'units.contact_name',
        //         'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
        //     ->where(['sales.status' => 'active', 'sales.job_category' => 'nonnurse'])->get();
        $value = '1';
        return view('administrator.resource.non_nurse', compact('value'));
    }

    public function getNonNursingJob(Request $request)
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
				$sale_notes = Sales_notes::select('sale_id','sales_notes.sale_note', DB::raw('MAX(created_at) as 
               sale_created_at'))
               ->groupBy('sale_id');
                $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
				//->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
			
                ->select('sales.*', 'offices.office_name', 'units.contact_name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number', DB::raw("(SELECT count(cv_notes.sale_id) from cv_notes
                    WHERE cv_notes.sale_id=sales.id AND cv_notes.status='active' group by cv_notes.sale_id) as sale_count"))
                ->where(['sales.status' => 'active', 'sales.is_on_hold' => '0', 'sales.job_category' => 'nonnurse'
						 //, 'sales_notes.status' => 'active'
						])
					->whereNotIn('sales.job_title', ['nonnurse specialist'])
					//->whereIn('sales.head_office', $user_permissions)
					->orderBy('id', 'DESC');
            }
            else
            {
				$sale_notes = Sales_notes::select('sale_id','sales_notes.sale_note', DB::raw('MAX(created_at) as 
               sale_created_at'))
               ->groupBy('sale_id');
                $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
					
			//->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
            ->select('sales.*', 'offices.office_name', 'units.contact_name',
                'units.contact_email', 'units.unit_name', 'units.contact_phone_number', DB::raw("(SELECT count(cv_notes.sale_id) from cv_notes
                    WHERE cv_notes.sale_id=sales.id AND cv_notes.status='active' group by cv_notes.sale_id) as sale_count"))
            ->where(['sales.status' => 'active', 'sales.is_on_hold' => '0', 'sales.job_category' => 'nonnurse'
					 //, 'sales_notes.status' => 'active'
					])
					->whereNotIn('sales.job_title', ['nonnurse specialist'])
					->orderBy('id', 'DESC');

            }
    
        }
        else
        {
			$sale_notes = Sales_notes::select('sale_id','sales_notes.sale_note', DB::raw('MAX(created_at) as 
               sale_created_at'))
               ->groupBy('sale_id');

            $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
			// ->joinSub($sale_notes, 'sales_notes', function ($join) {
              //  $join->on('sales.id', '=', 'sales_notes.sale_id');
            //})
			//->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
            ->select('sales.*', 'offices.office_name', 'units.contact_name',
                'units.contact_email', 'units.unit_name', 'units.contact_phone_number',  DB::raw("(SELECT count(cv_notes.sale_id) from cv_notes WHERE cv_notes.sale_id=sales.id AND cv_notes.status='active' group by cv_notes.sale_id) as sale_count"))
            ->where(['sales.status' => 'active', 'sales.is_on_hold' => '0', 'sales.job_category' => 'nonnurse'
					 //, 'sales_notes.status' => 'active'
					])
				->whereNotIn('sales.job_title', ['nonnurse specialist'])
				->orderBy('id', 'DESC');
        }
			
        
        // $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
        //     ->join('units', 'units.id', '=', 'sales.head_office_unit')
        //     ->select('sales.*', 'offices.office_name', 'units.contact_name',
        //         'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
        //     ->where(['sales.status' => 'active', 'sales.job_category' => 'nonnurse'])->orderBy('id', 'DESC');

        $aColumns = ['sale_added_date', 'sale_added_time', 'job_title', 'office_name', 'unit_name',
            'postcode', 'job_type', 'experience', 'qualification', 'salary', 'sale_notes', 'status', 'Cv Limit'];

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
			$post_code = strtoupper($sRow->postcode);
            $checkbox = "<label class=\"mt-checkbox mt-checkbox-single mt-checkbox-outline\">
                             <input type=\"checkbox\" class=\"checkbox-index\" value=\"{$sRow->id}\">
                             <span></span>
                          </label>";
            $postcode = "<a href=\"/applicants-within-15-km/{$sRow->id}\">{$post_code}</a>";
            if ($sRow->status == 'active') {
                $status = '<h5><span class="badge w-100 badge-success">Active</span></h5>';
            } else {
                $status = '<h5><span class="badge w-100 badge-danger">Disable</span></h5>';
            }

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
				$job_title_desc='';
            if(@$sRow->job_title_prof!='')
                {
                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $sRow->job_title_prof)->first();
                    $job_title_desc = $sRow->job_title.' ('.$job_prof_res->specialist_prof.')';
                    // $job_title_desc = @$sRow->job_title.' ('.@$sRow->job_title_prof.')';
                }
                else
                {
                    $job_title_desc = @$sRow->job_title;
                }
            $output['aaData'][] = array(
                "DT_RowId" => "row_{$sRow->id}",
                //    @$checkbox,
                @$sRow->sale_added_date,
                @$sRow->sale_added_time,
                strtoupper($job_title_desc),
                @ucwords(strtolower($sRow->office_name)),
                @ucwords(strtolower($sRow->unit_name)),
                @$postcode,
                @ucwords($sRow->job_type),
                @$sRow->experience,
                @$sRow->qualification,
                @$sRow->salary,
                @$sRow->sale_note,
                @$status,
				@$sRow->sale_count==$sRow->send_cv_limit?'<span class="badge w-100 badge-danger" style="font-size:90%">Limit Reached</span>':"<span class='badge w-100 badge-success' style='font-size:90%'>".((int)$sRow->send_cv_limit - (int)$sRow->sale_count)." Cv's limit remaining</span>",
                @$action
				

            );


            $i++;

        }
        echo json_encode($output);
    }
	
	public function getNonNurseSpecialistSales()
	{
		$value = '1';
		return view('administrator.resource.non_nurse_specialist', compact('value'));
	}

	public function getNonNursingSpecialistJob(Request $request)
	{
		$user = Auth::user();
		$result='';
   
        $sale_notes = Sales_notes::select('sale_id','sales_notes.sale_note', DB::raw('MAX(created_at) as 
        sale_created_at'))
        ->groupBy('sale_id');
		
        $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
			->join('units', 'units.id', '=', 'sales.head_office_unit')
			->joinSub($sale_notes, 'sales_notes', function ($join) {
				$join->on('sales.id', '=', 'sales_notes.sale_id');
			})
        //->join('sales_notes', 'sales.id', '=', 'sales_notes.sale_id')
        	->select('sales.*', 'offices.office_name', 'units.contact_name',
            'units.contact_email', 'units.unit_name', 'units.contact_phone_number', 'sales_notes.sale_note', DB::raw("(SELECT count(cv_notes.sale_id) from cv_notes
            WHERE cv_notes.sale_id=sales.id AND cv_notes.status='active' group by cv_notes.sale_id) as result"))
			->where([
				'sales.status' => 'active', 
				'sales.is_on_hold' => '0', 
				'sales.job_category' => 'nonnurse', 
				'sales.job_title' => 'nonnurse specialist'
			])
			->orderBy('id', 'DESC');

		$aColumns = ['sale_added_date', 'sale_added_time', 'job_title', 'office_name', 'unit_name',
			'postcode', 'job_type', 'experience', 'qualification', 'salary', 'sale_note', 'status', 'Cv Limit'];

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
		$post_code = strtoupper($sRow->postcode);
        $checkbox = "<label class=\"mt-checkbox mt-checkbox-single mt-checkbox-outline\">
                         <input type=\"checkbox\" class=\"checkbox-index\" value=\"{$sRow->id}\">
                         <span></span>
                      </label>";
        $postcode = "<a href=\"/applicants-within-15-km/{$sRow->id}\">{$post_code}</a>";
        if ($sRow->status == 'active') {
            $status = '<h5><span class="badge w-100 badge-success">Active</span></h5>';
        } else {
            $status = '<h5><span class="badge w-100 badge-danger">Disable</span></h5>';
        }

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
						$job_title_desc='';
				if(@$sRow->job_title_prof!='')
				{
					$job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $sRow->job_title_prof)->first();
					$job_title_desc = $job_prof_res->specialist_prof;
				}
				else
				{
					$job_title_desc = @$sRow->job_title;
				}
			$output['aaData'][] = array(
				"DT_RowId" => "row_{$sRow->id}",
				//    @$checkbox,
				@$sRow->sale_added_date,
				@$sRow->sale_added_time,
				strtoupper($job_title_desc),
				@ucwords(strtolower($sRow->office_name)),
				@ucwords(strtolower($sRow->unit_name)),
				@$postcode,
				@ucwords($sRow->job_type),
				@$sRow->experience,
				@$sRow->qualification,
				@$sRow->salary,
				@$sRow->sale_note,
				@$status,
				@$action,
				@$sRow->result==$sRow->send_cv_limit?'<span style="color:red;">Limit Reached</span>':"<span style='color:green'>".((int)$sRow->send_cv_limit - (int)$sRow->result)." Cv's limit remaining</span>",

			);


			$i++;

		}
		echo json_encode($output);
	}
	
	 public function getIndirectNurseResource()
    {
        $category = 'nurse';
        return view('administrator.resource.indirect.indirect_page',compact('category'));
    }

    public function getIndirectNonNurseResource()
    {
        $category = 'nonnurse';
        return view('administrator.resource.indirect.indirect_page',compact('category'));
    }

    public function getIndirectSpecialistResource()
    {
        $category = 'specialist';
        return view('administrator.resource.indirect.indirect_page',compact('category'));
    }

    public function getIndirectChefResource()
    {
        $category = 'chef';
        return view('administrator.resource.indirect.indirect_page',compact('category'));
    }

    public function getIndirectNurseryResource()
    {
        $category = 'nursery';
        return view('administrator.resource.indirect.indirect_page',compact('category'));
    }

    public function getIndirectResourcesAjax($category, $status)
    {
		$filterBySaleDate = request()->query('filterBySaleDate');
		$filterByUpdatedSale = request()->query('filterByUpdatedSale');
		
		// Sorting Logic
		$orderColumnIndex = request()->input('order.0.column', 0);
		$orderDirection = request()->input('order.0.dir', 'asc');
		$columns = [
			'applicants.updated_at', '', 'applicants.applicant_name', 'applicants.applicant_email',
			'applicants.applicant_job_title', '', 'applicants.applicant_postcode', '', '', '',
			'', '', 'applicants.applicant_source', '', '', 'cv_notes.status'
		];
		$orderColumn = $columns[$orderColumnIndex] ?? $columns[0];

		$sales = DB::table('sales')
			->distinct()
			->select('sales.id', 'sales.created_at', 'sales.lat', 'sales.lng', 'sales.job_title', 
					 DB::raw("COALESCE(audits.updated_at, NULL) AS audit_updated_at"))
			->leftJoin('audits', function ($join) {
				$join->on('audits.auditable_id', '=', 'sales.id')
					 ->where('audits.auditable_type', '=', 'Horsefly\\Sale')
					 ->where('audits.message', 'like', '%sale-opened%');
			})
			->where('sales.status', 'active')
			->where('sales.is_on_hold', 0)
			->where(function ($query) use ($filterByUpdatedSale, $filterBySaleDate) {
				$query->whereDate('audits.updated_at', $filterBySaleDate); // Always applied

				$query->orWhere(function ($subQuery) use ($filterByUpdatedSale, $filterBySaleDate) {
					if ($filterByUpdatedSale == '1') {
						// Checkbox checked: Use updated_at
						$subQuery->whereDate('sales.updated_at', $filterBySaleDate);
					} else {
						// Default case: Use created_at
						$subQuery->whereDate('sales.created_at', $filterBySaleDate);
					}
				});
			});



		// **Add Job Category Filter**
		switch ($category) {
			case "nurse":
				$sales->where('sales.job_category', 'nurse')->whereNotIn('sales.job_title', ['nurse specialist']);
				break;
			case "nonnurse":
				$sales->where('sales.job_category', 'nonnurse')->whereNotIn('sales.job_title', ['nonnurse specialist']);
				break;
			case "specialist":
				$sales->whereIn('sales.job_title', ['nurse specialist', 'nonnurse specialist']);
				break;
			case "chef":
				$sales->where('sales.job_category', 'chef');
				break;
			case "nursery":
				$sales->where('sales.job_category', 'nursery');
				break;
		}

		// **Optimize Fetching of Sales Data**
		$salesData = $sales->groupBy('sales.id')->orderBy('sales.id')->get();
		$count = $salesData->count();
		// **Batch Processing of Applicants**
		$radius = 8;
		$near_by_applicants = collect();

		// Process sales data in chunks to optimize memory and performance
		$salesData->chunk(500)->each(function ($chunk) use (&$near_by_applicants, $radius, $status, $category, $orderColumn, $orderDirection) {
			$chunk->each(function ($sale) use (&$near_by_applicants, $radius, $status, $category, $orderColumn, $orderDirection) {
				$applicants = collect($this->getApplicantsAgainstSales($sale->lat, $sale->lng, $radius, $sale->job_title, $status, $category, $orderColumn, $orderDirection));

				if ($applicants->isNotEmpty()) {
					$near_by_applicants = $near_by_applicants->merge($applicants);
				}
			});
		});

		// Remove duplicates and sort the results
		$near_by_applicants = $near_by_applicants->unique('id')->sortByDesc('updated_at')->values();
		
        return datatables()->of($near_by_applicants)
			->with('total_sale_count', $count)
            ->addColumn("updated_at", function ($applicant) {
                return Carbon::parse($applicant->updated_at)->toFormattedDateString();
            })
            ->addColumn("updated_time", function ($applicant) {
                return Carbon::parse($applicant->updated_at)->toFormattedTimeString();
            })
            ->editColumn("applicant_name", function ($applicant) {
               // return ucwords($applicant->applicant_name);
				 return htmlspecialchars(utf8_encode(ucwords($applicant->applicant_name)), ENT_QUOTES, 'UTF-8');
            })
            ->editColumn('applicant_postcode', function ($applicant) {
                $status_value = 'open';
                $postcode = '';
                if ($applicant->paid_status == 'close') {
                    $status_value = 'paid';
                } else {
                    // foreach ($applicant->cv_notes as $key => $value) {
                        if ($applicant->cv_notes_status == 'active') {
                            $status_value = 'sent';
                           
                        } elseif ($applicant->cv_notes_status == 'disable') {
                            $status_value = 'reject';
                        }
                    // }
                }
                if ($status_value == 'open' || $status_value == 'reject') {
                    $postcode .= '<a href="/available-jobs/' . $applicant->id . '">';
                    $postcode .= htmlspecialchars(strtoupper($applicant->applicant_postcode), ENT_QUOTES, 'UTF-8');
                    $postcode .= '</a>';
                } else {
                    $postcode .= htmlspecialchars(strtoupper($applicant->applicant_postcode), ENT_QUOTES, 'UTF-8');
                }
                return $postcode;
            })
            ->editColumn('applicant_notes', function ($applicant) {
                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                 $app_notes_final = '';
				if ($app_new_note) {
					$app_notes_final = $app_new_note->details;
				} else {
					$app_notes_final = $applicant->applicant_notes;
				}

				// Ensuring proper encoding and escaping for notes
				$app_notes_final = htmlspecialchars(utf8_encode($app_notes_final), ENT_QUOTES, 'UTF-8');
				
                $status_value = 'open';
                $postcode = '';
                if ($applicant->paid_status == 'close') {
                    $status_value = 'paid';
                } else {
                    // foreach ($applicant->cv_notes as $key => $value) {
                        if ($applicant->cv_notes_status == 'active') {
                            $status_value = 'sent';
                           
                        } elseif ($applicant->cv_notes_status == 'disable') {
                            $status_value = 'reject';
                        }
                    // }
                }

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                    data-controls-modal="#clear_cv' . $applicant->id . '"
                                    data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                    data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="7_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
					
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" 
							 data-applicant="' . $applicant->id . '" 
							 data-controls-modal="#reject_history' . $applicant->id . '" 
							 data-backdrop="static" data-keyboard="false" data-toggle="modal"
							 data-target="#reject_history' . $applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . htmlspecialchars($applicant->applicant_name, ENT_QUOTES, 'UTF-8') . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
            ->editColumn('status', function ($applicant) {
                $status_value = 'open';
                $color_class = 'bg-teal-800';
                if ($applicant->paid_status == 'close') {
                    $status_value = 'paid';
                    $color_class = 'bg-slate-700';
                } else {
                   // foreach ($applicant->cv_notes as $key => $value) {
                        if ($applicant->cv_notes_status == 'active') {
                            $status_value = 'sent';
                           
                        } elseif ($applicant->cv_notes_status == 'disable') {
                            $status_value = 'reject';
                        }
                    // }
                }
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                if ($applicant->applicant_job_title == 'nurse specialist' || $applicant->applicant_job_title == 'nonnurse specialist') {
                    $selected_prof_data = Specialist_job_titles::select("specialist_prof")->where("id", $applicant->job_title_prof)->first();
                    if ($selected_prof_data) {
                        $spec_job_title = ($applicant->job_title_prof != '') ? $applicant->applicant_job_title . ' (' . $selected_prof_data->specialist_prof . ')' : $applicant->applicant_job_title;
                        return strtoupper($spec_job_title);
                    } else {
                        return strtoupper($applicant->applicant_job_title);
                    }
                } else {
                    return strtoupper($applicant->applicant_job_title);
                }
            })
            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
                data-controls-modal="#import_applicant_cv" class="import_cv"
                data-backdrop="static"
                data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
                data-target="#import_applicant_cv">
                <i class="fas fa-file-upload text-teal-400"></i>
                &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
                   // foreach ($applicant->cv_notes as $key => $value) {
                        if ($applicant->cv_notes_status == 'active') {
                            $row_class = 'class_success';
                          //  break;
                        } elseif ($applicant->cv_notes_status == 'disable') {
                            $row_class = 'class_danger';
                        }
                  //  }
                }
                return $row_class;
            })
            ->rawColumns(['updated_at', 'updated_time', 'applicant_job_title', 'applicant_name', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }

    private function getApplicantsAgainstSales($lat, $lon, $radius, $job_title, $status, $category, $orderColumn, $orderDirection)
    {
       $query = "SELECT 
            applicants.id, applicants.applicant_phone, applicants.applicant_name, 
            applicants.applicant_homePhone, applicants.applicant_job_title, 
            applicants.applicant_postcode, applicants.applicant_source, 
            applicants.is_callback_enable, applicants.is_no_job, applicants.applicant_cv, 
            applicants.updated_cv, applicants.paid_status, applicants.temp_not_interested, 
            applicants.updated_at, applicants.job_category, applicants.applicant_notes, applicants.is_blocked,
            applicants_pivot_sales.sales_id as pivot_sale_id,
            applicants_pivot_sales.id as pivot_id,
            cv_notes.status as cv_notes_status, 
            ((ACOS(SIN(? * PI() / 180) * SIN(lat * PI() / 180) + 
            COS(? * PI() / 180) * COS(lat * PI() / 180) * COS((? - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) 
            AS distance
        FROM applicants
        LEFT JOIN applicants_pivot_sales ON applicants_pivot_sales.applicant_id = applicants.id
        LEFT JOIN cv_notes ON cv_notes.applicant_id = applicants.id
        WHERE applicants.status = 'active'
            AND applicants.is_in_nurse_home = 'no'
            AND applicants.is_callback_enable = 'no'
            AND applicants.is_no_job = '0'
            AND applicants.is_blocked = '0'
            AND ((ACOS(SIN(? * PI() / 180) * SIN(lat * PI() / 180) + 
            COS(? * PI() / 180) * COS(lat * PI() / 180) * COS((? - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) < ?
            AND (cv_notes.status IS NULL OR cv_notes.status != 'active')";

		$bindings = [$lat, $lat, $lon, $lat, $lat, $lon, $radius];
    
        // Apply job category filter
        switch ($category) {
            case "nurse":
                $query .= " AND applicants.job_category = 'nurse' AND applicants.applicant_job_title NOT IN ('nurse specialist')";
                break;
            case "nonnurse":
                $query .= " AND applicants.job_category = 'non-nurse' AND applicants.applicant_job_title NOT IN ('nonnurse specialist')";
                break;
            case "specialist":
                $query .= " AND applicants.applicant_job_title IN ('nurse specialist', 'nonnurse specialist')";
                break;
            case "chef":
                $query .= " AND applicants.job_category = 'chef'";
                break;
            case "nursery":
                $query .= " AND applicants.job_category = 'nursery'";
                break;
        }
    
        // Apply status filter
        switch ($status) {
            case 'interested':
                $query .= " AND applicants.temp_not_interested = '0'";
                break;
            case 'not_interested':
                $query .= " AND applicants.temp_not_interested = '1'";
                break;
        }
    
        // Apply job title filter
        $titles = $this->getAllTitles($job_title);
        if (!empty($titles)) {
            $titlePlaceholders = implode(',', array_fill(0, count($titles), '?'));
            $query .= " AND applicants.applicant_job_title IN ($titlePlaceholders)";
            $bindings = array_merge($bindings, $titles);
        }
    
        // Apply sorting if column is provided
        if ($orderColumn) {
            $query .= " ORDER BY $orderColumn $orderDirection";
        }
    
        // Execute raw query
        $applicants = DB::select($query, $bindings);
    
        // Remove duplicates based on 'id'
        $uniqueApplicants = collect($applicants)->unique('id')->values();
    
        return $uniqueApplicants;
    }


    public function get15kmApplicantsAjax($id, $radius = null, $param_type = null)
    {
        $job_result = Sale::find($id);

        $job_title = $job_result->job_title;
        $job_postcode = $job_result->postcode;
		
		 $job_title_prop = null;
        if ($job_title == "nonnurse specialist"){
            $job_title_prop =  $job_result->job_title_prof;
        }
        if($radius==10 || $radius == null || $radius == 0 || $radius == '')
        {
           // $radius = 8;
			$radius = 10;
        }
		
		// Initialize search value
        $searchValue = request()->input('search.value', null);
		$orderValue = request()->input('order', null);
		
		$near_by_applicants = $this->distance($id, $job_result->lat, $job_result->lng, $radius, $job_title, $job_title_prop, $param_type, $searchValue, $orderValue);
		
		$orderValue = request()->input('order.value', null);

        return datatables($near_by_applicants)
            ->addColumn('action', function ($applicant) use ($id) {
                $status_value = 'open';
                if ($applicant['paid_status'] == 'close') {
                    $status_value = 'paid';
                } else {
                    foreach ($applicant['cv_notes'] as $key => $value) {
                        if ($value['status'] == 'active') {
                            $status_value = 'sent';
                            break;
                        } elseif (($value['status'] == 'disable') && ($value['sale_id'] == $id)) {
                            $status_value = 'reject_job';
                            break;
                        } elseif ($value['status'] == 'disable') {
                            $status_value = 'reject';
                        } elseif (($value['status'] == 'paid') && ($value['sale_id'] == $id) && ($applicant['paid_status'] == 'open')) {
                            $status_value = 'paid';
                            break;
                        }
                    }
                }
                $content = "";
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class=list-icons-item" data-toggle="dropdown">
                                    <i class="icon-menu9"></i>
                                </a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($status_value == 'open' || $status_value == 'reject') {
                    $content .= '<a href="#" id="openNotInterestedModal" 
                                        class="dropdown-item"
                                        data-toggle="modal" 
                                        data-target="#notInterestedModal"
                                        data-applicant-id="' . $applicant['id'] . '"
                                        data-job-id="' . $id . '">
                                        NOT INTERESTED
                                    </a>';

                    $content .= '<a href="#"   
                            class="dropdown-item" 
                            data-toggle="modal" 
                            data-target="#sent_cvModal"
                            id="openSentCvModal"
                            data-applicant-id="' . $applicant['id'] . '"
                                        data-job-id="' . $id . '">
                            <span>SEND CV</span></a>';


             /**       $content .= '<a href="#" class="dropdown-item" 
                            data-toggle="modal" 
                            data-target="#nurseHomeModal"
                            id="openNurseHomeModal"
                            data-applicant-id="' . $applicant['id'] . '"
                            data-job-id="' . $id . '">
                            NO NURSING HOME</a>'; **/

                    $content .= '<a href="#" id="openCallBackModal" 
                                    class="dropdown-item" 
                                    data-toggle="modal" 
                                    data-target="#callBackModal"
                                    data-applicant-id="' . $applicant['id'] . '"
                                    data-job-id="' . $id . '">CALLBACK</a>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';

                  

                } elseif ($status_value == 'sent' || $status_value == 'reject_job' || $status_value == 'paid') {
                    $content .= '<a href="#" class="disabled dropdown-item">LOCKED</a>';
                    $content .= '<a href="#" class="disabled dropdown-item">LOCKED</a>';
                    $content .= '<a href="#" class="disabled dropdown-item">LOCKED</a>';
                    $content .= '<a href="#" class="disabled dropdown-item">LOCKED</a>';
                }
                return $content;
            })
            ->addColumn('status', function ($applicant) use ($id) {
                $status_value = 'open';
                $color_class = 'bg-teal-800';
                if ($applicant['paid_status'] == 'close') {
                    $status_value = 'paid';
                    $color_class = 'bg-slate-700';
                } else {
                    foreach ($applicant['cv_notes'] as $key => $value) {
                        if ($value['status'] == 'active') {
                            $status_value = 'sent';
                            break;
                        } elseif (($value['status'] == 'disable') && ($value['sale_id'] == $id)) {
                            $status_value = 'reject_job';
                            break;
                        } elseif ($value['status'] == 'disable') {
                            $status_value = 'reject';
                        } elseif (($value['status'] == 'paid') && ($value['sale_id'] == $id) && ($applicant['paid_status'] == 'open')) {
                            $status_value = 'paid';
                            $color_class = 'bg-slate-700';
                            break;
                        }
                    }
                }
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('applicant_notes', function ($applicant) use ($id) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant['id'], 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
                    $app_notes_final = $applicant['applicant_notes'];
                }
                $status_value = 'open';
                $postcode = '';
                if ($applicant['paid_status'] == 'close') {
                    $status_value = 'paid';
                } else {
                    foreach ($applicant['cv_notes'] as $key => $value) {
                        if ($value['status'] == 'active') {
                            $status_value = 'sent';
                            break;
                        } elseif ($value['status'] == 'disable') {
                            $status_value = 'reject';
                        }
                    }
                }

                if ($applicant['is_blocked'] == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant['id'] . '"
                             data-controls-modal="#clear_cv' . $applicant['id'] . '"
                             data-backdrop="static" data-keyboard="false" data-toggle="modal"
                             data-target="#clear_cv' . $applicant['id'] . '">"' . $app_notes_final . '"</a>';
                    $content .= '<div id="clear_cv' . $applicant['id'] . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('block_or_casual_notes') . '" method="POST" id="app_notes_form' . $applicant['id'] . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="app_notes_alert' . $applicant['id'] . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant['id'] . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant['id'] . '">';
                    $content .= '<input type="hidden" name="applicant_sale_id" value="' . $id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant['id'] . '" value="15_km_applicants_nurses">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant['id'] . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant['id'] . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
                    $content .= '</select>';
                    $content .= '</div>';
                    $content .= '</div>';

                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';

                    $content .= '<button type="button" class="btn bg-dark legitRipple sent_cv_submit" data-dismiss="modal">Close</button>';

                    $content .= '<button type="submit" data-note_key="' . $applicant['id'] . '" value="cv_sent_save" class="btn bg-teal legitRipple sent_cv_submit app_notes_form_submit">Save</button>';

                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';

                    return $content;
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn("applicant_postcode", function ($applicant) {
                $status_value = 'open';
                $postcode = '';
                if ($applicant['paid_status'] == 'close') {
                    $status_value = 'paid';
                } else {
                    foreach ($applicant['cv_notes'] as $key => $value) {
                        if ($value['status'] == 'active') {
                            $status_value = 'sent';
                            break;
                        } elseif ($value['status'] == 'disable') {
                            $status_value = 'reject';
                        }
                    }
                }
                if ($status_value == 'open' || $status_value == 'reject') {
                    $postcode .= '<a href="/available-jobs/' . $applicant['id'] . '">';
                    $postcode .= strtoupper($applicant['applicant_postcode']);
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant['applicant_postcode'];
                }
                return $postcode;
            })
            ->addColumn('download', function ($applicant) {
                $download = '<a href="' . route('downloadApplicantCv', $applicant['id']) . '">
                       <i class="fas fa-file-download text-teal-400"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                return
                    '<a href="' . route('downloadUpdatedApplicantCv', $applicant['id']) . '">
                       <i class="fas fa-file-download text-teal-400"></i>
                    </a>';
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant['job_title_prof'] != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant['job_title_prof'])->first();
                    $job_title_desc =  $job_prof_res->specialist_prof;
                } else {

                    $job_title_desc = $applicant['applicant_job_title'];
                }
                return strtoupper($job_title_desc);
            })
            ->editColumn('updated_at', function ($applicant) {
                $updatedAt = Carbon::parse($applicant['updated_at'])->format('d M Y');
                return $updatedAt;
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant['paid_status'] == 'close') {
                    $row_class = 'class_dark';
                } else {
                    /*** $applicant['paid_status'] == 'open' || $applicant['paid_status'] == 'pending' */
                    foreach ($applicant['cv_notes'] as $key => $value) {
                        if ($value['status'] == 'active') {
                            $row_class = 'class_success';
                            break;
                        } elseif ($value['status'] == 'disable') {
                            $row_class = 'class_danger';
                        }
                    }
                }
                return $row_class;
            })
			
            ->rawColumns(['applicant_job_title', 'applicant_postcode', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'action'])
            ->make(true);
    }
	
	public function export_15_km_applicants($id)
    {
        $job_result = Sale::find($id);
        $job_title = $job_result->job_title;
        $job_postcode = $job_result->postcode;
        $radius = 8;
       
		$near_by_applicants = $this->export_applicants_15km_distance($job_result->lat, $job_result->lng, $radius, $job_title);

		$check_applicant_availibility = [];
		
		if($near_by_applicants){
			$non_interest_response = $this->check_not_interested_applicants_export($near_by_applicants, $id);
			$check_applicant_availibility = array_values($non_interest_response);
		}
        return Excel::download(new Applicants_nurses_15kmExport($check_applicant_availibility), 'applicants.csv');
    }
	
	function check_not_interested_applicants_export($applicants_object, $job_id)
    {

        $pivot_result = array();
        $filter_applicant = array();
        $app_id = '';
        $job_db_id='';
        foreach ($applicants_object as $key => $value) {
            $applicant_id = $value->id;
            $pivot_result[] = Applicants_pivot_sales::where("applicant_id", $applicant_id)->where("sales_id", $job_id)->first();
            foreach ($pivot_result as $res) 
            { 
                if(isset($res['applicant_id']) && isset($res['applicant_id']))
                {
                    $app_id = $res['applicant_id'];
                    $job_db_id = $res['sales_id'];
                }
            }
            if (($applicant_id == $app_id) && ($job_id == $job_db_id)) {
                $applicants_object->forget($key);
            }
        }
        foreach ($applicants_object as $key => $filter_val) {
            if (($filter_val['is_in_nurse_home'] == 'yes') || ($filter_val['is_callback_enable'] == 'yes') || ($filter_val['is_blocked'] == '1')) {
                $applicants_object->forget($key);

            }
            unset( $filter_val['distance']);
			


			// to get applicant history

             $applicants_in_crm = Applicant::join('crm_notes', 'crm_notes.applicant_id', '=', 'applicants.id')
            ->join('sales', 'sales.id', '=', 'crm_notes.sales_id')
            ->join('offices', 'offices.id', '=', 'head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
            ->join('history', function($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })
            ->select("applicants.*", "applicants.id as app_id", "crm_notes.*", "crm_notes.id as crm_notes_id", "sales.*", "sales.id as sale_id", "sales.postcode as sale_postcode", "sales.job_title as sale_job_title", "sales.job_category as sales_job_category", "sales.status as sale_status", "history.history_added_date", "history.sub_stage","office_name", "unit_name","history.updated_at")
            ->where(array('applicants.id' => $filter_val['id'], 'history.status' => 'active'))
            ->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE sales_id=sales.id and applicants.id=applicant_id'));
            })
            ->get()->last();
            $history_stages = config('constants.history_stages');
			if($applicants_in_crm!='')
				{
			
				if($history_stages[$applicants_in_crm['sub_stage']]=='Sent CV' || $history_stages[$applicants_in_crm['sub_stage']]=='Request' || $history_stages[$applicants_in_crm['sub_stage']]=='Confirmation' || $history_stages[$applicants_in_crm['sub_stage']]=='Rebook' || $history_stages[$applicants_in_crm['sub_stage']]=='Attended to Pre-Start Date' || 
			  $history_stages[$applicants_in_crm['sub_stage']]=='Attended to Pre-Start Date' || $history_stages[$applicants_in_crm['sub_stage']]=='Start Date' || 
			  $history_stages[$applicants_in_crm['sub_stage']]=='Invoice' ||
			  $history_stages[$applicants_in_crm['sub_stage']]=='Paid')  
			   {   
				   //echo $history_stages[$applicants_in_crm->sub_stage];exit();
				   unset($applicants_object[$key]); 

				}
			}	
			unset( $filter_val['id']);			            
			unset( $filter_val['cv_notes']);
        
        }
        return $applicants_object->toArray();

    }

    public function get15kmApplicants($id, $radius = null)
    {
        $sent_cv_count = Cv_note::where(['sale_id' => $id, 'status' => 'active'])->count();
		$open_cv_count = History::where(['sale_id' => $id, 'status' => 'active', 'sub_stage' => 'quality_cvs_hold'])->count();
		$net_sent_cv_count = $sent_cv_count - $open_cv_count;

		$cv_limit = Cv_note::where(['sale_id'=> $id,'status' => 'active'])->count();
		
        $job = Office::join('sales', 'offices.id', '=', 'sales.head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
            ->select('sales.*', 'offices.office_name', 'units.contact_name',
                'units.contact_email', 'units.unit_name', 'units.contact_phone_number', 'sales.id as sale_id')
            ->where(['sales.status' => 'active', 'sales.id' => $id])->first();

		$sale_export_id= $id;

        $active_applicants = [];
        if ($net_sent_cv_count == $job['send_cv_limit']) {
            $active_applicants = Applicant::join('history', function ($join) use ($id) {
                    $join->on('history.applicant_id', '=', 'applicants.id');
                    $join->where('history.sale_id', '=', $id);
                })->whereIn('history.sub_stage', ['quality_cvs', 'quality_cleared', 'crm_save', 'crm_request', 'crm_request_save', 'crm_request_confirm', 'crm_interview_save', 'crm_interview_attended', 'crm_prestart_save', 'crm_start_date', 'crm_start_date_save', 'crm_start_date_back', 'crm_invoice', 'crm_final_save'])
                ->where('history.status', '=', 'active')
                ->select('applicants.applicant_name','applicants.applicant_postcode',
                    'history.stage','history.sub_stage','history.history_added_date','history.history_added_time')
                ->get()->toArray();
        }

        return view('administrator.resource.15km_applicants', compact('id', 'job', 'sent_cv_count', 'active_applicants', 'sale_export_id', 'radius', 'cv_limit', 'net_sent_cv_count', 'open_cv_count'));
    }

    public function getActive15kmApplicants($id)
    {

        $applicant = Applicant::find($id);

        return view('administrator.resource.15km_jobs', compact('applicant'));
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
            $title[0] = "senior nurse";
            $title[1] = "clinical lead";
            $title[2] = "nurse manager";
            $title[3] = "rgn";
            $title[4] = $job_title;
            $title[5] = $job_title;
            $title[6] = $job_title;
            $title[7] = $job_title;
            $title[8] = $job_title;
            $title[9] = $job_title;
            $title[10] = $job_title;
        } elseif ($job_title === "nurse manager") {
            $title[0] = "nurse deputy manager";
            $title[1] = "clinical lead";
            $title[2] = $job_title;
            $title[3] = $job_title;
            $title[4] = $job_title;
            $title[5] = $job_title;
            $title[6] = $job_title;
            $title[7] = $job_title;
            $title[8] = $job_title;
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
        }elseif ($job_title === "head chef") {
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
        }elseif ($job_title === "sous chef") {
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
            $title[3] = "commis chef";
            $title[4] = $job_title;
            $title[5] = $job_title;
            $title[6] = $job_title;
            $title[7] = $job_title;
            $title[8] = $job_title;
            $title[9] = $job_title;
            $title[10] = $job_title;
		} elseif ($job_title === "support worker / care assistant") {
            $title[0] = "care assistant";
            $title[1] = "support worker";
            $title[2] = $job_title;
            $title[3] = $job_title;
            $title[4] = $job_title;
            $title[5] = $job_title;
            $title[6] = $job_title;
            $title[7] = $job_title;
            $title[8] = $job_title;
            $title[9] = $job_title;
            $title[10] = $job_title;
		} elseif ($job_title === "senior support worker / senior care assistant") {
            $title[0] = "senior care assistant";
            $title[1] = "senior support worker";
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
	
	 function export_applicants_15km_distance($lat, $lon, $radius, $job_title)
    {
        $title = $this->getAllTitles($job_title);

        $location_distance = Applicant::with('cv_notes')
			->select(DB::raw("id,applicant_phone,applicant_name,applicant_homePhone,applicant_job_title,
        applicant_postcode,applicant_source,applicant_notes, ((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) + 
                COS($lat * PI() / 180) * COS(lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) 
                AS distance"))->having("distance", "<", $radius)->orderBy("distance")
            ->where(array("status" => "active", "is_in_nurse_home" => "no", 'is_blocked' => '0', 'is_callback_enable' => 'no')); //->get();

        $location_distance = $location_distance->where("applicant_job_title", $title[0])
			->orWhere("applicant_job_title", $title[1])
			->orWhere("applicant_job_title", $title[2])
			->orWhere("applicant_job_title", $title[3])
			->orWhere("applicant_job_title", $title[4])
			->orWhere("applicant_job_title", $title[5])
			->orWhere("applicant_job_title", $title[6])
			->orWhere("applicant_job_title", $title[7])
			->orWhere("applicant_job_title", $title[8])
			->orWhere("applicant_job_title", $title[9])
			->orWhere("applicant_job_title", $title[10])
			->get();

        return $location_distance;
    }

    function distance(
		$sale_id, 
		$lat, 
		$lon, 
		$radius, 
		$job_title, 
		$job_title_prop = null, 
		$param_type = null, 
		$searchValue = null,
        $orderValue = null
	)
    {
        $location_distance = Applicant::with('cv_notes', 'pivotSales', 'history_request_nojob')
            ->select(DB::raw("*, ((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) +
                COS($lat * PI() / 180) * COS(lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515)
                AS distance"))
            ->havingRaw("distance < ?", [$radius])
           // ->orderBy("distance")
            ->where([
                "status" => "active",
                "is_in_nurse_home" => "no"
            ]);

        // Apply job title filters
        if ($job_title_prop != null) {
            $location_distance = $location_distance->where("job_title_prof", $job_title_prop);
        } else {
            $title = $this->getAllTitles($job_title);
            // $location_distance = $location_distance->whereIn("applicant_job_title", $title);
            $location_distance = $location_distance->whereIn("applicant_job_title", [
                $title[0],
                $title[1],
                $title[2],
                $title[3],
                $title[4],
                $title[5],
                $title[6],
                $title[7],
                $title[8],
                $title[9],
                $title[10]
            ]);
        }

        if($searchValue){
            // Apply search filters to the query
            $location_distance =  $location_distance->where(function($q) use ($searchValue) {
                $q->where('applicant_name', 'LIKE', "%{$searchValue}%")
                    ->orWhere('applicant_postcode', 'LIKE', "%{$searchValue}%")
                    ->orWhere('applicant_source', 'LIKE', "%{$searchValue}%")
                    ->orWhere('applicant_email', 'LIKE', "%{$searchValue}%")
                	->orWhere('applicant_notes', 'LIKE', "%{$searchValue}%");
            });
        }
		
		// Apply ordering
        $defaultColumn = 'distance';
        $defaultDirection = 'asc';
    
        if ($orderValue) {
            $orderObject = $orderValue[0] ?? null; // Fetch the first order object
            $columnIndex = $orderObject['column'] ?? null; // Extract column index
            $direction = strtolower($orderObject['dir'] ?? $defaultDirection); // Extract direction
    
            // Validate direction
            if (!in_array($direction, ['asc', 'desc'])) {
                $direction = $defaultDirection;
            }
    
            // Map column index to column name
            $validColumns = [
                'updated_at',
                'applicant_added_time',
                'applicant_name',
                'applicant_email',
                'applicant_job_title',
                'job_category',
                'applicant_postcode',
                '', // Placeholder for empty columns
                '', // Placeholder for empty columns
                '', // Placeholder for empty columns
                'applicant_source',
                '',
                'status',
            ];
    
            $column = $validColumns[$columnIndex] ?? null;
    
            if ($column) {
				$location_distance->orderBy($column, $direction);
            } else {
                $location_distance->orderBy($defaultColumn, $defaultDirection);
            }
        } else {
            $location_distance->orderBy($defaultColumn, $defaultDirection);
        }

        // Apply param_type filters
        if ($param_type == 'blocked') {
            $location_distance = $location_distance->where('is_no_job', '0')
                ->where("is_blocked", "1")
                ->where("is_callback_enable", "no")
                ->where("temp_not_interested", "0");
        }
        if ($param_type == 'interested') {
            $location_distance = $location_distance
                ->where("is_blocked", "0")
                ->where('is_no_job', '0')
                ->where(function ($query) {
                    // Check for combinations of 'temp_not_interested' and 'is_callback_enable'
                    $query->where(function ($subQuery) {
                        $subQuery->where("temp_not_interested", "0")
                            ->where("is_callback_enable", "yes");
                    })
                        ->orWhere(function ($subQuery) {
                            $subQuery->where("temp_not_interested", "1")
                                ->where("is_callback_enable", "yes");
                        })
                        ->orWhere(function ($subQuery) {
                            $subQuery->where("temp_not_interested", "0")
                                ->where("is_callback_enable", "no");
                        })
                        // Add more combinations as needed
                    ;
                })                
                ->whereDoesntHave('pivotSales', function ($query) use ($sale_id) {
                    $query->where('sales_id', $sale_id);  // Adjust the column name as needed
                });
        }
        if ($param_type == 'not_interested') {
            $location_distance = $location_distance->where("is_callback_enable", "no")
                ->where(function ($query) use ($sale_id) {
                    $query->where("temp_not_interested", "1")
                        // Or applicants related to a specific sale_id via pivotSales relationship
                        ->orWhereHas('pivotSales', function ($query) use ($sale_id) {
                            $query->where('sales_id', $sale_id); // Ensure the relationship exists with the specific sales_id
                        });
                })
                ->where('is_no_job', '0') // Ensure applicant has a job
                ->where('is_blocked', '0') // Ensure applicant is not blocked
                ->doesntHave('history_request_nojob', 'and', function ($query) use ($sale_id) {
                    $query->where('sale_id', $sale_id); // Exclude applicants with a history of no job for the given sale_id
                });
        }
        if ($param_type == 'no_job') {
            $location_distance = $location_distance
                ->where(function ($query) {
                    $query->where('is_no_job', '1')
                        ->where('is_callback_enable', 'no');
                })
                ->orWhereHas('history_request_nojob', function ($query) use ($sale_id) {
                    $query->where('sale_id', $sale_id)
                        ->orderBy('id', 'desc')
                        ->take(1); // Orders by 'id' descending to get the most recent
                });

        }

        // If param_type == 'all', no filter is applied
        if ($param_type == 'all') {
            // Skip all filters and get all data
            $location_distance = $location_distance;
        }else{
			 $location_distance = $location_distance->where('department', $param_type);
		}

        // Execute the query
        return $location_distance;
    }

    public function get15kmJobsAvailableAjax($applicant_id, $radius = null)
	{
        $applicant = Applicant::with('cv_notes')->find($applicant_id);
        $applicant_job_title = $applicant->applicant_job_title;
        $applicant_postcode = $applicant->applicant_postcode;
		
        if($radius != null)
        {
            $radius = 10;
        }
        else
        {
            $radius = 15;
        }
		
		$near_by_jobs = $this->job_distance($applicant->lat, $applicant->lng, $radius, $applicant_job_title);

		if (empty($near_by_jobs))
		{
			$jobs = [];
		}
		else
		{
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
				$job['cv_notes_count'] = $job['cv_notes_count'];
				$job['history_count'] = $job['history_count'];
			}
		}    

        return datatables($jobs)
			->editColumn('job_title',function($job){
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
                $content .= '<a href="#" class=list-icons-item" data-toggle="dropdown">
                            <i class="icon-menu9"></i>
                        </a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if($option == 'open') {

                    $content .= '<a href="#" class="dropdown-item"
                                       data-controls-modal="#modal_form_horizontal'.$job['id'].'"
                                       data-backdrop="static"
                                       data-keyboard="false" data-toggle="modal"
                                       data-target="#modal_form_horizontal'.$job['id'].'"> NOT INTERESTED</a>';
                    $content .= '<a href="#" class="dropdown-item"
                                       data-controls-modal="#sent_cv'.$job['id'].'" data-backdrop="static"
                                       data-keyboard="false" data-toggle="modal"
                                       data-target="#sent_cv'.$job['id'].'">SEND CV</a>';
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
                   // Send CV Modal
                    $sent_cv_count = Cv_note::where(['sale_id' => $job['id'], 'status' => 'active'])->count();
                    $content .= '<div id="sent_cv'.$job['id'].'" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Add CV Notes Below:</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $cv_url = '/applicant-cv-to-quality';
                    $cv_csrf = csrf_token();
                    $content .= '<form action="'.$cv_url.'/'.$applicant->id.'" method="GET" class="form-horizontal">';
                    $content .= '<input type="hidden" name="_token" value="' .$cv_csrf.'">';
                    $content .= '<div class="modal-body">';
                    $content  .='<div id="interested">'; // Added a container div for the fields to be hidden
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="'.$applicant->id.'">';
                    $content .= '<input type="hidden" name="sale_hidden_id" value="'.$job['id'].'">';
                    $content  .='<div class="form-group row">';
                    $content  .='<label class="col-form-label col-sm-3"><strong style="font-size:18px">1.</strong> Current Employer Name</label>';
                    $content  .='<div class="col-sm-9">';
                    $content  .='<input type="text" name="current_employer_name" class="form-control" placeholder="Enter Employer Name">';
                    $content  .='</div>';
                    $content  .='</div>';

                    $content  .='<div class="form-group row">';
                    $content  .='<label class="col-form-label col-sm-3"><strong style="font-size:18px">2.</strong> PostCode</label>';
                    $content  .='<div class="col-sm-3">';
                    $content  .='<input type="text" name="postcode" class="form-control" placeholder="Enter PostCode">';
                    $content  .='</div>';
                    $content  .='<label class="col-form-label col-sm-3"><strong style="font-size:18px">3.</strong> Current/Expected Salary</label>';
                    $content  .='<div class="col-sm-3">';
                    $content  .='<input type="text" name="expected_salary" class="form-control" placeholder="Enter Salary">';
                    $content  .='</div>';
                    $content  .='</div>';

                    $content  .='<div class="form-group row">';
                    $content  .='<label class="col-form-label col-sm-3"><strong style="font-size:18px">4.</strong> Qualification</label>';
                    $content  .='<div class="col-sm-9">';
                    $content  .='<input type="text" name="qualification" class="form-control" placeholder="Enter Qualification">';
                    $content  .='</div>';
                    $content  .='</div>';

                    $content  .='<div class="form-group row">';
                    $content  .='<label class="col-form-label col-sm-3"><strong style="font-size:18px">5.</strong> Transport Type</label>';
                    $content  .='<div class="col-sm-9 d-flex align-items-center">';
					$content  .='<div class="form-check form-check-inline">';
                    $content  .='<input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="walk" value="By Walk">';
                    $content  .='<label class="form-check-label" for="walk">By Walk</label>';
                    $content  .='</div>';
                    $content  .='<div class="form-check form-check-inline">';
                    $content  .='<input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="cycle" value="Cycle">';
                    $content  .='<label class="form-check-label" for="cycle">Cycle</label>';
                    $content  .='</div>';
                    $content  .='<div class="form-check form-check-inline ml-3">';
                    $content  .='<input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="car" value="Car">';
                    $content  .='<label class="form-check-label" for="car">Car</label>';
                    $content  .='</div>';
                    $content  .='<div class="form-check form-check-inline ml-3">';
                    $content  .='<input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="public_transport" value="Public Transport">';
                    $content  .='<label class="form-check-label" for="public_transport">Public Transport</label>';
                    $content  .='</div>';
                    $content  .='</div>';
                    $content  .='</div>';

                    $content  .='<div class="form-group row">';
                    $content  .='<label class="col-form-label col-sm-3"><strong style="font-size:18px">6.</strong> Shift Pattern</label>';
                    $content  .='<div class="col-sm-9 d-flex align-items-center">';
                    $content  .='<div class="form-check form-check-inline">';
                    $content  .='<input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="day" value="Day">';
                    $content  .='<label class="form-check-label" for="day">Day</label>';
                    $content  .='</div>';
					$content  .='<div class="form-check form-check-inline">';
                    $content  .='<input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="night" value="Night">';
                    $content  .='<label class="form-check-label" for="night">Night</label>';
                    $content  .='</div>';
                    $content  .='<div class="form-check form-check-inline ml-3">';
                    $content  .='<input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="full_time" value="Full Time">';
                    $content  .='<label class="form-check-label" for="full_time">Full Time</label>';
                    $content  .='</div>';
                    $content  .='<div class="form-check form-check-inline ml-3">';
                    $content  .='<input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="part_time" value="Part Time">';
                    $content  .='<label class="form-check-label" for="part_time">Part Time</label>';
                    $content  .='</div>';
                    $content  .='<div class="form-check form-check-inline ml-3">';
                    $content  .='<input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="twenty_four_hours" value="24 hours">';
                    $content  .='<label class="form-check-label" for="twenty_four_hours">24 Hours</label>';
                    $content  .='</div>';
					 $content  .='<div class="form-check form-check-inline">';
                    $content  .='<input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="day_night" value="Day/Night">';
                    $content  .='<label class="form-check-label" for="day_night">Day/Night</label>';
                    $content  .='</div>';
					
                    $content  .='</div>';
                    $content  .='</div>';

                    $content  .='<div class="form-group row">';
                    $content  .='<label class="col-form-label col-sm-3"><strong style="font-size:18px">7.</strong> Nursing Home</label>';
                    $content  .='<div class="col-sm-3 d-flex align-items-center">';
                    $content  .='<div class="form-check mt-0">';
                    $content  .='<input type="checkbox" name="nursing_home" style="margin-top:-3px" id="nursing_home_checkbox" class="form-check-input" value="0">';
                    $content  .='</div>';
                    $content  .='</div>';
                    $content  .='<label class="col-form-label col-sm-3"><strong style="font-size:18px">8.</strong> Alternate Weekend</label>';
                    $content  .='<div class="col-sm-3 d-flex align-items-center">';
                    $content  .='<div class="form-check mt-0">';
                    $content  .='<input type="checkbox" name="alternate_weekend" style="margin-top:-3px" id="alternate_weekend_checkbox" class="form-check-input" value="0">';
                    $content  .='</div>';
                    $content  .='</div>';
                    $content  .='</div>';

                    $content  .='<div class="form-group row">';
                    $content  .='<label class="col-form-label col-sm-3"><strong style="font-size:18px">9.</strong> Interview Availability</label>';
                    $content  .='<div class="col-sm-3 d-flex align-items-center">';
                    $content  .='<div class="form-check mt-0">';
                    $content  .='<input type="text" class="form-control" name="interview_availability" id="interview_availability" class="form-check-input">';
                    $content  .='</div>';
                    $content  .='</div>';
                    $content  .='<label class="col-form-label col-sm-3"><strong style="font-size:18px">10.</strong> No Job</label>';
                    $content  .='<div class="col-sm-3 d-flex align-items-center">';
                    $content  .='<div class="form-check mt-0">';
                    $content  .='<input type="checkbox" name="no_job" id="no_job_checkbox" style="margin-top:-3px" class="form-check-input" value="0">';
                    $content  .='</div>';
                    $content  .='</div>';
                    $content  .='</div>';
					
					 $content  .='<div class="form-group row">';
                    $content  .='<label class="col-form-label col-sm-3"><strong style="font-size:18px">11.</strong> Visa Status</label>';
                    $content  .='<div class="col-sm-3">';
                    $content  .='<div class="form-check form-check-inline">';
                    $content  .='<input type="radio" name="visa_status" id="british" class="form-check-input mt-0" value="British">';
                    $content  .='<label class="form-check-label" for="british">British</label>';
                    $content  .='</div><br>';
                    $content  .='<div class="form-check form-check-inline">';
                    $content  .='<input type="radio" name="visa_status" id="required_sponsorship" class="form-check-input mt-0" value="Required Sponsorship">';
                    $content  .='<label class="form-check-label" for="required_sponsorship">Required Sponsorship</label>';
                    $content  .='</div>';
                    $content  .='</div>';
                    $content  .='</div>';
					
					 $content  .= '<div class="form-group row">';
                    $content  .= '<label class="col-form-label col-sm-6"><strong style="font-size:18px">12.</strong> How far applicant can travel (miles or minutes) ?</label>';
                    $content  .= '<div class="col-sm-3">';
                    $content  .= '<div class="form-check form-check-inline">';
                    $content  .= '<input type="type" name="travel_range" id="travel_range" class="form-check-input mt-0" value="">';
                    $content  .= '</div>';
                    $content  .= '</div>';
                    $content  .= '</div>';

                    $content  .='</div>'; // Close upper-fields div
                    $content  .='<div class="form-group row">';
                    $content  .='<div class="col-sm-1 d-flex justify-content-center align-items-center">';
                    $content  .='<input type="checkbox" name="hangup_call" id="hangup_call" class="form-check-input" value="0">';
                    $content  .='</div>';
                    $content  .='<div class="col-sm-11">';
                    $content  .='<label for="hangup_call" class="col-form-label" style="font-size:16px;">Call Hung up/Not Interested</label>';
                    $content  .='</div>';
                    $content  .='</div>';
                        
                    $content  .='<div class="form-group row">';
                    $content  .='<label class="col-form-label col-sm-3">Other Details <span class="text-danger">*</span></label>';
                    $content  .='<div class="col-sm-9">';
                    $content  .='<input type="hidden" name="module_key" value="'.$applicant->id.'">';
                    $content  .='<textarea name="details" id="note_details'. $applicant->id .'" class="form-control" cols="30" rows="4" placeholder="TYPE HERE .." required></textarea>';
                    $content  .='</div>';
                    $content  .='</div>';
                    $content  .='</div>';
                                    
                    $content  .='<div class="modal-footer">';
                    $content  .='<button type="button" class="btn btn-link legitRipple" data-dismiss="modal">Close</button>';
                    $content  .='<button type="submit" data-note_key="'. $applicant->id .'" class="btn bg-teal legitRipple note_form_submit">Save</button>';
                    $content  .='</div>';
                    $content  .='</form>';
                    $content  .='</div>';
                    $content  .='</div>';
                    $content  .='</div>';
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
                    // /Add To Non Interest List Modal
                } else if($option == 'sent' || $option == 'reject_job' || $option == 'paid'){
                    $content .= '<a href="#" class="disabled dropdown-item"> LOCKED</a>';
                    $content .= '<a href="#" class="disabled dropdown-item"> LOCKED</a>';
                    $content .= '<a href="#" class="disabled dropdown-item"> LOCKED</a>';
                    $content .= '<a href="#" class="disabled dropdown-item"> LOCKED</a>';
                }
                return $content;
            })
            ->addColumn('head_office',function($job){
                return $job['office_name'];
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
                            $value_data = 'reject_job';
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
			->editColumn('cv_limit',function($job){   
				$cv_notes_count = $job['cv_notes_count'];
				$open_cv_count = $job['history_count'];
				$net_sent_cv_count = $cv_notes_count - $open_cv_count;
				
                if($net_sent_cv_count == null)
                {
                    $net_sent_cv_count = 0;
                }
				
				return $net_sent_cv_count == $job['send_cv_limit']
			? '<span class="badge w-100 badge-danger" style="font-size:90%">0/' . (int)$job['send_cv_limit'] . ' Limit Reached</span>'
			: "<span class='badge w-100 badge-success' style='font-size:90%'>" . ((int)$job['send_cv_limit'] - $net_sent_cv_count) . "/" . (int)$job['send_cv_limit'] . " Limit Remains</span>"
			. ($open_cv_count ? "<br><span class='badge w-100 badge-warning' style='font-size:90%'>" . $open_cv_count . " CV Open</span>" : "");
			})
            ->rawColumns(['job_title','head_office','head_office_unit','status','cv_limit','action'])
            ->make(true);
    }

    public function get15kmAvailableJobs($id, $radius = null)
    {
        $applicant = Applicant::find($id);
        $is_applicant_in_quality = $applicant['is_cv_in_quality'];
        if ($applicant->paid_status == 'close') {
            return view('administrator.resource.15km_jobs_for_closed_applicant', compact('applicant', 'is_applicant_in_quality', 'radius'));
        }
        return view('administrator.resource.15km_jobs', compact('applicant', 'is_applicant_in_quality', 'radius'));
    }

    function job_distance($lat, $lon, $radius, $applicant_job_title)
    {
        $title = $this->getAllTitles($applicant_job_title);
		
		$location_distance = Sale::select(
            DB::raw("*, 
                ((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) + 
                 COS($lat * PI() / 180) * COS(lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) 
                AS distance"),
            DB::raw("(SELECT COUNT(cv_notes.sale_id) 
                     FROM cv_notes 
                     WHERE cv_notes.sale_id = sales.id 
                       AND cv_notes.status = 'active' 
                     GROUP BY cv_notes.sale_id) as cv_notes_count"),
            DB::raw("(SELECT COUNT(history.sale_id) 
                     FROM history 
                     WHERE history.sale_id = sales.id 
                       AND history.sub_stage = 'quality_cvs_hold' 
                       AND history.status = 'active' 
                     GROUP BY history.sale_id) as history_count")
        )
        ->having("distance", "<", $radius)
        ->orderBy("distance")
        ->where("status", "active")
        ->where("is_on_hold", "0");

      //  $location_distance = Sale::select(DB::raw("*, ((ACOS(SIN($lat * PI() / 180) * SIN(lat * PI() / 180) + 
      //          COS($lat * PI() / 180) * COS(lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) 
      //          AS distance"),DB::raw("(SELECT count(cv_notes.sale_id) from cv_notes
      //          WHERE cv_notes.sale_id=sales.id AND cv_notes.status='active' group by cv_notes.sale_id) as cv_notes_count"))->having("distance", "<", $radius)->orderBy("distance")->where("status", "active")->where("is_on_hold", "0");

//        $location_distance = $location_distance->where("job_title", $title[0])->orWhere("job_title", $title[1])->orWhere("job_title", $title[2])->orWhere("job_title", $title[3])->orWhere("job_title", $title[4])->orWhere("job_title", $title[5])->orWhere("job_title", $title[6])->orWhere("job_title", $title[7])->get();
		
        $location_distance = $location_distance->where(function ($query) use ($title) {
            $query->orWhere("job_title", $title[0]);
            $query->orWhere("job_title", $title[1]);
            $query->orWhere("job_title", $title[2]);
            $query->orWhere("job_title", $title[3]);
            $query->orWhere("job_title", $title[4]);
            $query->orWhere("job_title", $title[5]);
            $query->orWhere("job_title", $title[6]);
            $query->orWhere("job_title", $title[7]);
            $query->orWhere("job_title", $title[8]);
            $query->orWhere("job_title", $title[9]);
            $query->orWhere("job_title", $title[10]);
        })->get();

        return $location_distance;
    }

    public function getMarkApplicant(Request $request)
    {
        date_default_timezone_set('Europe/London');
        $audit_data['applicant'] = $applicant_id = $request->input('applicant_hidden_id');
        $audit_data['action'] = "Not Interested";
        $audit_data['sale'] = $job_id = $request->input('job_hidden_id');
        $not_interested_reason_note = $request->input('reason');

        $interest = new Applicants_pivot_sales();
        $audit_data['added_date'] = $interest->interest_added_date = date("jS F Y");
        $audit_data['added_time'] = $interest->interest_added_time = date("h:i A");
        $audit_data['is_interested'] = "no";
        $interest->applicant_id = $applicant_id;
        $interest->sales_id = $job_id;
        $interest->save();
		
        $last_inserted_interest = $interest->id;
        if ($last_inserted_interest) {
            $interest_uid = md5($last_inserted_interest);
            DB::table('applicants_pivot_sales')->where('id', $last_inserted_interest)->update(['applicants_pivot_sales_uid' => $interest_uid]);
            $notes_for_range = new Notes_for_range_applicants();
            $notes_for_range->applicants_pivot_sales_id = $last_inserted_interest;
            $audit_data['reason'] = $notes_for_range->reason = $not_interested_reason_note;
            $notes_for_range->save();
			
            $notes_for_range_last_insert_id = $notes_for_range->id;
            if ($notes_for_range_last_insert_id) {
                $range_notes_uid = md5($notes_for_range_last_insert_id);
                Notes_for_range_applicants::where('id', $notes_for_range_last_insert_id)->update(['range_uid' => $range_notes_uid]);
            }
            //$pivot_object = Applicants_pivot_sales::where('id',$last_inserted_interest)->get();
//            $return_response = $this->check_interest_mark_note($pivot_object);
//            if($return_response){
//                return redirect('direct-resource')->with('jobApplicantInterest', 'Job Interest Note Added');
//            }
//            else{
//                return redirect('direct-resource')->with('jobApplicantInterestFail', 'Job Interest Note Cannot be Added');
//            }
            /*** activity log
             * $action_observer = new ActionObserver();
             * $action_observer->action($audit_data, 'Resource');
             */

			if(isset(request()->requestByAjax) && request()->requestByAjax == 'yes')
            {
                return response()->json(['success' => true, 'message' => 'Job Interest Note Added.' ]);
            }else{
                return Redirect::back()->with('jobApplicantInterest', 'Job Interest Note Added');
            }
            
        } else {
			if(isset(request()->requestByAjax) && request()->requestByAjax == 'yes')
            {
                return response()->json(['success' => true, 'message' => 'WHOOPS!! Something went wrong.' ]);
            }else{
                return Redirect::back()->with('jobApplicantInterestError', 'WHOOPS!! Something went wrong');
            }
        }

    }
//    function check_interest_mark_note($interest_object){
//        $data = '';
//        foreach($interest_object as $object){
//            $data = Applicant::where("id",$object->applicant_id)->update(['is_interested' => 'no']);
//        }
//        if($data)
//        return true;
//    }
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

    function check_not_interested_applicants($applicants_object, $job_id)
    {

        $pivot_result = array();
        $filter_applicant = array();
        $app_id = '';
        $job_db_id='';
        foreach ($applicants_object as $key => $value) {
            $applicant_id = $value->id;
            $pivot_result[] = Applicants_pivot_sales::where("applicant_id", $applicant_id)->where("sales_id", $job_id)->first();
            foreach ($pivot_result as $res) 
            { 
                if(isset($res['applicant_id']) && isset($res['applicant_id']))
                {
                    $app_id = $res['applicant_id'];
                    $job_db_id = $res['sales_id'];
                }
            }
            if (($applicant_id == $app_id) && ($job_id == $job_db_id)) {
                $applicants_object->forget($key);
            }
        }
        foreach ($applicants_object as $key => $filter_val) {
            if (($filter_val['is_in_nurse_home'] == 'yes') || ($filter_val['is_callback_enable'] == 'yes') || ($filter_val['is_blocked'] == '1')) {
                $applicants_object->forget($key);
            }
						 unset( $filter_val['distance']);

        }
        return $applicants_object->toArray();

    }

    function check_applicant_interest_for_different_job($check_applicant_availibility)
    {
//        return $check_applicant_availibility;
        $pivot_result = array();
        $colors = array();
        foreach ($check_applicant_availibility as $availibility) {
            $applicant_id = $availibility['id'];
            $pivot_result[] = Applicants_pivot_sales::where("applicant_id", $applicant_id)->first();

            foreach ($pivot_result as $res) {
                $app_id = $res['applicant_id'];
                if ($applicant_id == $app_id) {
                    $colors[] = $applicant_id;
                }
            }

        }
        return $colors;
    }

    public function getNotInterestedNoteReason($non_interest_id)
    {
        $reason_note = array();
        $applicants_pivot_sales = Applicants_pivot_sales::select("id")->where('applicant_id', $non_interest_id)->first();

        $response = Notes_for_range_applicants::select("reason")->where("applicants_pivot_sales_id", $applicants_pivot_sales->id)->get();
        foreach ($response as $data) {
            $reason_note[] = $data->reason;
        }
        return view('administrator.resource.show', compact('reason_note'));
    }
	
	public function getTestDaysApplicantAdded($id)
    {
        $interval = 7;
        return view('administrator.resource.tester', compact('interval','id'));
    }
	
	public function getTestDaysApp($id)
	{
		// Define the date range
        $current_date = Carbon::now();
        $edate = $current_date->format('Y-m-d') . " 23:59:59";
        $start_date = $current_date->subDays(16);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

		// Build the base query for applicants
		$query = Applicant::with(['cv_notes' => function ($query) {
			$query->select('status', 'applicant_id', 'sale_id', 'user_id')
				->with(['user:id,name'])
				->latest('cv_notes.created_at');
		}])
			->select(
			'applicants.id',
			'applicants.updated_at',
			'applicants.is_no_job',
			'applicants.applicant_added_time',
			'applicants.applicant_name',
			'applicants.applicant_job_title',
			'applicants.job_title_prof',
			'applicants.job_category',
			'applicants.applicant_postcode',
			'applicants.applicant_phone',
			'applicants.applicant_homePhone',
			'applicants.applicant_source',
			'applicants.applicant_email',
			'applicants.applicant_notes',
			'applicants.paid_status',
			'applicants.applicant_cv',
			'applicants.updated_cv',
			'applicants.lat',
			'applicants.lng'
		)
			->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
			->where('applicants.status', 'active')
			->where('applicants.is_blocked', '=', '0')
			->whereBetween('applicants.updated_at', [$sdate, $edate])
			->where('applicants.temp_not_interested', '=', '0')
			->whereNull('applicants_pivot_sales.applicant_id')
			->where('applicants.is_follow_up', '<>', '2')
			->whereDoesntHave('cv_notes', function ($query) {
				$query->where('status', 'active');
			});

		// Add job category filter based on $id
		 switch ($id) {
            case "44":
                $query->where('applicants.job_category', '=', 'nurse');
                break;
            case "45":
                $query->where('applicants.job_category', '=', 'non-nurse')
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $query->where('applicants.job_category', 'non-nurse')
                    ->where('applicants.applicant_job_title', 'nonnurse specialist');
                break;
            case "47":
                $query->where('applicants.job_category', 'chef');
                break;
            case "48":
                $query->where('applicants.job_category', 'nursery');
                break;
        }
		
		$result = $query->orderBy('applicants.updated_at', 'DESC')->get();
		
		// Get all applicants and filter those with nearby jobs
		$radius = 15; // km
        $filtered_applicants = collect();

       foreach ($result as $applicant) {
			// Check if the applicant has nearby sales
			$isNearSales = $this->checkNearbySales($applicant, $radius);

			// If there are sales nearby, push the applicant to the filtered list
			if ($isNearSales) {
				$filtered_applicants->push($applicant);
			}
		}

        return datatables()->of($filtered_applicants)
            ->addColumn("agent_name", function ($applicant) {
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
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
                    $postcode .= '<a href="/available-jobs/' . $applicant->id . '">';
                    $postcode .= strtoupper($applicant->applicant_postcode);
                    $postcode .= '</a>';
                } else {
                    $postcode .= strtoupper($applicant->applicant_postcode);
                }

                return $postcode;
            })
            ->addColumn("updated_at", function ($applicant) {
                $updated_at = new DateTime($applicant->updated_at);
                $date = date_format($updated_at, 'd F Y');
                return $date;
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where([
                    'module_noteable_id' => $applicant->id,
                    'module_noteable_type' => 'Horsefly\Applicant'
                ])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="7_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
                return $content;
            })
            ->addColumn('history', function ($applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $applicant->id . '" style="max-height: 500px; overflow-y: auto;">';


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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                if ($applicant->applicant_job_title == 'nurse specialist' || $applicant->applicant_job_title == 'nonnurse specialist') {
                    $selected_prof_data = Specialist_job_titles::select("specialist_prof")->where("id", $applicant->job_title_prof)->first();
                    if ($selected_prof_data) {
                        $spec_job_title = ($applicant->job_title_prof != '') ? $applicant->applicant_job_title . ' (' . $selected_prof_data->specialist_prof . ')' : $applicant->applicant_job_title;
                        return strtoupper($spec_job_title);
                    } else {
                        return strtoupper($applicant->applicant_job_title);
                    }
                } else {
                    return strtoupper($applicant->applicant_job_title);
                }
            })
            ->addColumn('checkbox', function ($applicant) {
                return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
            })
            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
            data-controls-modal="#import_applicant_cv" class="import_cv"
            data-backdrop="static"
            data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
            data-target="#import_applicant_cv">
             <i class="fas fa-file-upload text-teal-400"></i>
             &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'checkbox', 'applicant_postcode', 'history'])
            ->make(true);
    }
	
	private function checkNearbySales($applicant, $radius)
	{
		// Extract the latitude, longitude, and job title of the applicant
		$lat = $applicant->lat;
		$lon = $applicant->lng;
		
		$title = $this->getAllTitles($applicant->applicant_job_title); // Ensure this function returns an array of titles

		// Query sales within the radius
		$location_distance = Sale::selectRaw("
			*, 
			(ACOS(SIN(? * PI() / 180) * SIN(lat * PI() / 180) + 
			COS(? * PI() / 180) * COS(lat * PI() / 180) * COS((? - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515 AS distance,
			(SELECT COUNT(cv_notes.sale_id) 
			 FROM cv_notes 
			 WHERE cv_notes.sale_id = sales.id AND cv_notes.status = 'active' 
			 GROUP BY cv_notes.sale_id) AS cv_notes_count
		", [$lat, $lat, $lon])
			->where("status", "active")
			->where("is_on_hold", "0")
			->havingRaw("distance < ?", [$radius]) // Use havingRaw with a parameter
			->whereIn("job_title", $title)
			->get();

		// Return true if there are any sales within the radius
		return $location_distance->isNotEmpty();
	}

    public function getLast7DaysApplicantAdded($id)
    {
        $interval = 7;
        return view('administrator.resource.last_7_days_applicant_added', compact('interval','id'));
    }
	//public function export_7_days_applicants()
    //{
       //$end_date = Carbon::now();
        //$edate = $end_date->format('Y-m-d') . " 23:59:59";
        //$start_date = $end_date->subDays(9);
        //$sdate = $start_date->format('Y-m-d') . " 00:00:00";
        //$job_category='nurse';
        //return Excel::download(new ResourcesExport($sdate,$edate,$job_category), 'applicants.csv');
                    

        
    //}
	
	public function export_7_days_applicants_date(Request $request)
    {
	    //$start_date = $request->input('applicants_date');
        //$sdate = Carbon::parse($start_date)->format('Y-m-d'). " 00:00:00:000";
        //$end_date = $request->input('applicants_date');
        //$edate = Carbon::parse($end_date)->format('Y-m-d'). " 23:59:59:999";
		  $start_date = $request->input('custom_start_date_value');
        $sdate = Carbon::parse($start_date)->format('Y-m-d'). " 00:00:00:000";
        $end_date = $request->input('custom_end_date_value');
        $edate = Carbon::parse($end_date)->format('Y-m-d'). " 23:59:59:999";
		
		
        $saleId=$request->input('hidden_job_value');

        //$job_category='nurse';
       
                    //echo '<pre>';print_r($not_sents);echo '</pre>';exit();
   $result1= Applicant::select(
                'id','applicant_phone', 'applicant_name','applicant_homePhone','applicant_job_title',
                'applicant_postcode','applicant_source','applicant_notes')->where(function($query){
            $query->doesnthave('CVNote');
        })->whereBetween('updated_at', [$sdate, $edate]);
        if($saleId == "45"){
           // $result1= $result1->where("job_category", '=',"nurse");
			$result1= $result1->where("job_category", "=","non-nurse")->whereNotIn('applicant_job_title', ['nonnurse specialist']);
        }elseif ($saleId == "44"){
			$result1 = $result1->where("job_category", '=',"nurse");
            //$result1= $result1->where("job_category", "=","non-nurse")->whereNotIn('applicant_job_title', ['nonnurse specialist']);
        }elseif ($saleId =="46"){
            $result1= $result1->where(["job_category" => "non-nurse", "applicant_job_title" => "nonnurse specialist" ]);
        }

        $not_sents=$result1->where("is_blocked", "=", "0")->where("temp_not_interested", "=", "0")->where('is_no_job',"=","0")->get();
		


		
		
		$result_rej = Applicant::with('cv_notes')
                    ->select('applicants.id','applicant_phone', 'applicant_name','applicant_homePhone','applicant_job_title',
                'applicant_postcode','applicant_source','applicant_notes')
                    ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
                    ->where("applicants.status", "=", "active");
//                    ->where("applicants.is_in_nurse_home", "=", "no")
//                    ->where("applicants.job_category", "=", "nurse");
               if($saleId == "44"){
                   $result_rej= $result_rej->where("applicants.job_category", '=',"nurse");
               }elseif ($saleId == "45"){
                   $result_rej= $result_rej->where("applicants.job_category", "=","non-nurse")->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
               }elseif ($saleId =="46"){
                   $result_rej= $result_rej->where(["job_category" => "non-nurse", "applicants.applicant_job_title" => "nonnurse specialist" ]);
               }
                   $rejecteds =$result_rej->where("is_blocked", "=", "0")->where("applicants.temp_not_interested", "=", "0")->where("applicants.is_blocked", "=", "0")->where('applicants.is_no_job',"=","0")
					   ->whereBetween('applicants.updated_at', [$sdate, $edate])->get();
		
	


    $not_sents->map(function($row){
        //$row->sub_stage = "Not Sent";
        unset($row->id);
    });

$arr = array();
		$reslut = array();
		$totalIterations = 0;
   foreach ($rejecteds as $key => $filter_val) {
	 
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
		
		
		$not_sent[] = $not_sents->toArray();
		$admin_author_collection  = array_merge($arr,$not_sent);
	
        return Excel::download(new Applicants_nureses_7_days_export($admin_author_collection), 'applicants.csv');
		//return Excel::download(new Applicants_nureses_7_days_export($sdate,$edate,$saleId), 'applicants.csv');
    }

    public function get7DaysApplicants($id)
    {
        $end_date = Carbon::now();
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(16);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";
		
       $result1 = Applicant::with(['cv_notes' => function($query) {
                        $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                            ->with(['user:id,name'])
                            ->latest('cv_notes.created_at'); // Eager load the 'user' relationship and only select 'id' and 'name'
                    }])
			->select('applicants.id', 'applicants.updated_at', 'applicants.is_no_job', 
					 'applicants.applicant_added_time', 'applicants.applicant_name', 
					 'applicants.applicant_job_title', 'applicants.job_title_prof', 
					 'applicants.job_category', 'applicants.applicant_postcode', 
					 'applicants.applicant_phone', 'applicants.applicant_homePhone', 
					 'applicants.applicant_source', 'applicants.applicant_email', 
					 'applicants.applicant_notes', 'applicants.paid_status', 
					 'applicants.applicant_cv', 'applicants.updated_cv', 
					 'applicants.applicant_experience', 'applicants.department', 'applicants.sub_department', 
					 'applicants.lat', 'applicants.lng','applicants.is_job_within_radius')
			->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
			->where("applicants.status", "=", "active")
			->whereBetween('applicants.updated_at', [$sdate, $edate])
			->where("applicants.is_blocked", "=", "0")
			->where("applicants.temp_not_interested", "=", "0")
			->whereNull('applicants_pivot_sales.applicant_id')
		   //	->where('applicants.is_follow_up', '<>', '2')
			->whereDoesntHave('cv_notes', function ($query) {
				$query->where('status', 'active');
			})
			->where(function ($query) {
				$query->where("applicants.is_job_within_radius", "1")
					  ->orWhereDate('applicants.created_at', '=', Carbon::now());  // Current date condition
			});
		
		switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }
		
		$result = $result1->orderBy('updated_at','DESC');

        return datatables()->of($result)
			->addColumn("agent_name", function($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
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
                    $postcode .= strtoupper($applicant->applicant_postcode);
                    $postcode .= '</a>';
                } else {
                    $postcode .= strtoupper($applicant->applicant_postcode);
                }
				
               	return $postcode;
            })
			->editColumn("department",function($applicants){
				$applicant_department = $applicants->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicants){
				$sub_department = $applicants->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
			->editColumn("applicant_experience",function($applicant){
                $applicant_experience = $applicant->applicant_experience;
                return $applicant_experience ? $applicant_experience : '-';
			})
            ->addColumn("updated_at",function($applicant){
                $updated_at = new DateTime($applicant->updated_at);
                $date = date_format($updated_at,'d F Y');
                     return Carbon::parse($applicant->updated_at)->toFormattedDateString();
                })
			->addColumn('applicant_notes', function($applicant){
                    $app_new_note = ModuleNote::where(['module_noteable_id' =>$applicant->id, 
													   'module_noteable_type' =>'Horsefly\Applicant'])
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
					$content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
				return $content;
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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 '.$color_class.'">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
			->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';

            })
			->editColumn('applicant_job_title', function ($applicant) {
            if($applicant->applicant_job_title == 'nurse specialist' || $applicant->applicant_job_title == 'nonnurse specialist')
            {
                $selected_prof_data = Specialist_job_titles::select("specialist_prof")->where("id", $applicant->job_title_prof)->first();
                if($selected_prof_data)
                {
                $spec_job_title = ($applicant->job_title_prof!='')?$selected_prof_data->specialist_prof:$applicant->applicant_job_title;
               return strtoupper($spec_job_title);

                }
                else
                {
                   return strtoupper($applicant->applicant_job_title);
                }
            }
            else
            {
                return strtoupper($applicant->applicant_job_title);
            }

     })
			->addColumn('checkbox', function ($applicant) {
                 return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
             })
			->addColumn('upload', function ($applicant) {
				return
				'<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="'.$applicant->id.'"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
			}) 
			->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
				} elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
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
                return $row_class;
            })
            ->rawColumns(['applicant_job_title','updated_at','download','updated_cv','upload','applicant_notes','status','checkbox','applicant_postcode', 'history'])
            ->make(true);
    }
	
	
	 public function getlast7DaysAppNotInterested($id)
    {
        $end_date = Carbon::now();
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(16);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function($query) {
			$query->select('status', 'applicant_id', 'sale_id', 'user_id')
				->with(['user:id,name'])->latest('cv_notes.created_at'); // Eager load the 'user' relationship and only select 'id' and 'name'
		}])
		->leftJoin('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
		->leftJoin('sales', 'sales.id', '=', 'applicants_pivot_sales.sales_id')
		->leftJoin('notes_for_range_applicants', 'applicants_pivot_sales.id', '=', 'notes_for_range_applicants.applicants_pivot_sales_id')
		->leftJoin('offices', 'offices.id', '=', 'sales.head_office')
		->leftJoin('units', 'units.id', '=', 'sales.head_office_unit')
		->select(
			'applicants.id',
			'applicants.updated_at',
			'applicants.temp_not_interested',
			'applicants.applicant_added_time',
			'applicants.is_no_job',
			'applicants.applicant_name',
			'applicants.applicant_job_title',
			'applicants.job_title_prof',
			'applicants.job_category',
			'applicants.applicant_postcode',
			'applicants.applicant_phone',
			'applicants.applicant_homePhone',
			'applicants.applicant_source',
			'applicants.applicant_email',
			'applicants.applicant_notes',
			'applicants.paid_status',
			'applicants.applicant_cv',
			'applicants.updated_cv',
			'applicants_pivot_sales.sales_id as pivot_sale_id',
			'applicants_pivot_sales.id as pivot_id',
			'applicants.is_job_within_radius',
			'applicants.applicant_experience',
			'applicants.department', 'applicants.sub_department',
		)
			 ->where("applicants.status", "active")
			 ->where("applicants.is_blocked", "0")
            ->where("applicants.is_no_job", "=", "0")
			->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where(function ($query) {
                $query->where("applicants.temp_not_interested", "1")
                      ->orWhereNotNull('applicants_pivot_sales.applicant_id');
            })
            ->whereDoesntHave('cv_notes', function ($query) {
                $query->where('status', 'active');
            })
            ->where(function ($query) {
                $query->where("applicants.is_job_within_radius", "1")
                      ->orWhereDate('applicants.created_at', '=', Carbon::now());  // Current date condition
            });

		 switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

		$result = $result1->orderBy('applicants.updated_at', 'DESC');
		 
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
                     $postcode .= strtoupper($applicant->applicant_postcode);
                      $postcode .= '</a>';
                 } else {
                      $postcode .= strtoupper($applicant->applicant_postcode);
                    }
                return $postcode;
            })
			->addColumn("agent_name", function($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
			->editColumn("applicant_experience",function($applicant){
                $applicant_experience = $applicant->applicant_experience;
                return $applicant_experience ? $applicant_experience : '-';
			})
            ->addColumn("updated_at",function($applicant){
                $updated_at = new DateTime($applicant->updated_at);
                $date = date_format($updated_at,'d F Y');
                   return Carbon::parse($applicant->updated_at)->toFormattedDateString();
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
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
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
				return $content;
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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 '.$color_class.'">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
			->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';

            })
			->editColumn('applicant_job_title', function ($applicant) {
                    if($applicant->applicant_job_title == 'nurse specialist' || $applicant->applicant_job_title == 'nonnurse specialist')
                    {
                        $selected_prof_data = Specialist_job_titles::select("specialist_prof")->where("id", $applicant->job_title_prof)->first();
                        if($selected_prof_data)
                        {
                        $spec_job_title = ($applicant->job_title_prof!='')?$selected_prof_data->specialist_prof:$applicant->applicant_job_title;
                        return strtoupper($spec_job_title);

                        }
                        else
                        {
                            return strtoupper($applicant->applicant_job_title);
                        }
                    }
                    else
                    {
                        return strtoupper($applicant->applicant_job_title);
                    }

            })
			->addColumn('upload', function ($applicant) {
                return
                '<a href="#"
                data-controls-modal="#import_applicant_cv" class="import_cv"
                data-backdrop="static"
                data-keyboard="false" data-toggle="modal" data-id="'.$applicant->id.'"
                data-target="#import_applicant_cv">
                <i class="fas fa-file-upload text-teal-400"></i>
                &nbsp;</a>';
            }) 
            ->addColumn('checkbox', function ($applicant) {
                return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
            })
			->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
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
                return $row_class;
            })
            ->rawColumns(['applicant_job_title','updated_at','download','updated_cv','upload','applicant_notes','status','applicant_postcode','checkbox', 'history'])
            ->make(true);
    }
	
    public function getlast7DaysAppBlocked($id)
    {
        $end_date = Carbon::now();
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(16);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function($query) {
                        $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                            ->with(['user:id,name'])->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
                    }])
				->leftJoin('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
				->leftJoin('sales', 'sales.id', '=', 'applicants_pivot_sales.sales_id')
				->leftJoin('notes_for_range_applicants', 'applicants_pivot_sales.id', '=', 'notes_for_range_applicants.applicants_pivot_sales_id')
				->leftJoin('offices', 'offices.id', '=', 'sales.head_office')
				->leftJoin('units', 'units.id', '=', 'sales.head_office_unit')
				->select(
					'applicants.id',
					'applicants.updated_at',
					'applicants.temp_not_interested',
					'applicants.applicant_added_time',
					'applicants.is_no_job',
					'applicants.applicant_name',
					'applicants.applicant_job_title',
					'applicants.job_title_prof',
					'applicants.job_category',
					'applicants.applicant_postcode',
					'applicants.applicant_phone',
					'applicants.applicant_homePhone',
					'applicants.applicant_source',
					'applicants.applicant_email',
					'applicants.applicant_notes',
					'applicants.paid_status',
					'applicants.applicant_cv',
					'applicants.updated_cv',
					'applicants_pivot_sales.sales_id as pivot_sale_id',
					'applicants_pivot_sales.id as pivot_id',
					'applicants.applicant_experience',
					'applicants.department', 'applicants.sub_department',
				)
			 ->where("applicants.is_blocked", "1")
            ->where("applicants.temp_not_interested", "0")
            ->where("applicants.status", "active")
            ->where("applicants.is_no_job", "=", "0")
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->whereDoesntHave('cv_notes', function ($query) {
                $query->where('status', 'active');
            });
    
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }
        
        $result = $result1->orderBy('applicants.updated_at', 'DESC');

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
                return strtoupper($applicant->applicant_postcode);
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
			->editColumn("applicant_experience",function($applicant){
                $applicant_experience = $applicant->applicant_experience;
                return $applicant_experience ? $applicant_experience : '-';
			})
			->addColumn("agent_name", function($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at",function($applicant){
                $updated_at = new DateTime($applicant->updated_at);
                $date = date_format($updated_at,'d F Y');
                    return Carbon::parse($applicant->updated_at)->toFormattedDateString();
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
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
				return $content;
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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 '.$color_class.'">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
			->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';

            })
			->editColumn('applicant_job_title', function ($applicant) {
                    if($applicant->applicant_job_title == 'nurse specialist' || $applicant->applicant_job_title == 'nonnurse specialist')
                    {
                        $selected_prof_data = Specialist_job_titles::select("specialist_prof")->where("id", $applicant->job_title_prof)->first();
                        if($selected_prof_data)
                        {
                        $spec_job_title = ($applicant->job_title_prof!='')?$selected_prof_data->specialist_prof:$applicant->applicant_job_title;
                        return strtoupper($spec_job_title);

                        }
                        else
                        {
                            return strtoupper($applicant->applicant_job_title);
                        }
                    }
                    else
                    {
                        return strtoupper($applicant->applicant_job_title);
                    }

            })
			->addColumn('upload', function ($applicant) {
                return
                '<a href="#"
                data-controls-modal="#import_applicant_cv" class="import_cv"
                data-backdrop="static"
                data-keyboard="false" data-toggle="modal" data-id="'.$applicant->id.'"
                data-target="#import_applicant_cv">
                <i class="fas fa-file-upload text-teal-400"></i>
                &nbsp;</a>';
            }) 
            ->addColumn('checkbox', function ($applicant) {
                return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
            })
			->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
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
                return $row_class;
            })
            ->rawColumns(['applicant_job_title','updated_at','download','updated_cv','upload','applicant_notes','status','applicant_postcode','checkbox', 'history'])
            ->make(true);
		

    }
	
public function export_Last21DaysApplicantAdded(Request $request)
    {
        $end_date = Carbon::now();
        $edate7 = $end_date->subDays(16);
        $edate = $edate7->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(21);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";
        // echo '21 days';exit();
        //$job_category='nurse';
	      $job_category=$request->input('hidden_job_value');
        return Excel::download(new Applicant_21_days_export($sdate,$edate,$job_category), 'applicants.csv');

//        return Excel::download(new ApplicantsExport($sdate,$edate,$job_category), 'applicants.csv');
    }


    public function getLast21DaysApplicantAdded($id)
    {
        $interval = 21;

        return view('administrator.resource.last_21_days_applicant_added', compact('interval','id'));
    }
	public function get21DaysApplicants($id)
    {
        $end_date = Carbon::now();
        $edate7 = $end_date->subDays(16);
        $edate = $edate7->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(21);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
        ->select([
            'applicants.id', 'applicants.updated_at', 'applicants.is_no_job', 
            'applicants.applicant_added_time', 'applicants.applicant_name', 
            'applicants.applicant_job_title', 'applicants.job_title_prof', 
            'applicants.job_category', 'applicants.applicant_postcode', 
            'applicants.applicant_phone', 'applicants.applicant_homePhone', 
            'applicants.applicant_source', 'applicants.applicant_email', 
            'applicants.applicant_notes', 'applicants.paid_status', 
            'applicants.applicant_cv', 'applicants.updated_cv', 
            'applicants.lat', 'applicants.lng', 'applicants.is_job_within_radius', 
			'applicants.department', 'applicants.sub_department',
        ])
        ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
        ->whereBetween('applicants.updated_at', [$sdate, $edate])
        ->where("applicants.status", "active")
		->where("applicants.is_job_within_radius", "1")
        ->where("applicants.temp_not_interested", "0")
		->where("applicants.is_blocked", "0")
		->whereNull('applicants_pivot_sales.applicant_id');

		// Apply filters based on $id
		switch ($id) {
			case "44":
				$result1->where("applicants.job_category", "nurse");
				break;
			case "45":
				$result1->where("applicants.job_category", "non-nurse")
						 ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
				break;
			case "46":
				$result1->where("applicants.job_category", "non-nurse")
						 ->where("applicants.applicant_job_title", "nonnurse specialist");
				break;
			case "47":
				$result1->where("applicants.job_category", "chef");
				break;
			case "48":
				$result1->where("applicants.job_category", "nursery");
				break;
		}

		$result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants
		
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
                if($status_value == 'open' || $status_value == 'reject'){
                    $postcode .= '<a href="/available-jobs/'.$applicant->id.'">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }
				
                return $postcode;
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
			->addColumn("agent_name", function($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at",function($result){
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at,'d F Y');
                   return Carbon::parse($result->updated_at)->toFormattedDateString();
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
                $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
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
					$content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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

                })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$crm_start_date_hold_applicant->id.'"; 
                                 data-controls-modal="#reject_history'.$crm_start_date_hold_applicant->id.'" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$crm_start_date_hold_applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">'.$crm_start_date_hold_applicant->applicant_name.'</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$crm_start_date_hold_applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 '.$color_class.'">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
			->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';

            })
			->editColumn('applicant_job_title', function ($applicant) {
				$job_title_desc='';
				if($applicant->job_title_prof!=null)
				{
					$job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $applicant->job_title_prof)->first();
					$job_title_desc = $job_prof_res->specialist_prof;
				}
				else
				{

					$job_title_desc = $applicant->applicant_job_title;
				}
				return strtoupper($job_title_desc);

			})
       
			->addColumn('upload', function ($applicant) {
				return
				'<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="'.$applicant->id.'"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
			})
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
				} elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
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
                return $row_class;
            })
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
        ->make(true);
    }
	
	
    public function get21DaysApplicantsDead($id, Request $request)
	{
		// Define end and start dates
        $current_date = Carbon::now();
        $end_date = $current_date->subDays(16);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(21);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

		// Base query for applicants
		$query = Applicant::with(['cv_notes' => function ($query) {
			$query->select('status', 'applicant_id', 'sale_id', 'user_id')
				->with(['user:id,name'])
				->latest(); // Eager load the 'user' relationship
		}])
		->select([
			'applicants.id', 'applicants.updated_at', 'applicants.is_no_job', 
			'applicants.applicant_added_time', 'applicants.applicant_name', 
			'applicants.applicant_job_title', 'applicants.job_title_prof', 
			'applicants.job_category', 'applicants.applicant_postcode', 
			'applicants.applicant_phone', 'applicants.applicant_homePhone', 
			'applicants.applicant_source', 'applicants.applicant_email', 
			'applicants.applicant_notes', 'applicants.paid_status', 
			'applicants.applicant_cv', 'applicants.updated_cv', 
			'applicants.lat', 'applicants.lng', 'applicants.is_job_within_radius'
		])
		->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
		->whereBetween('applicants.updated_at', [$sdate, $edate])
		->where("applicants.status", "active")
		->where("applicants.temp_not_interested", 0)
		->where("applicants.is_blocked", 0)
		->where("applicants.is_job_within_radius", "1")
		->whereNull('applicants_pivot_sales.applicant_id')
		->whereDoesntHave('cv_notes', function ($query) {
			$query->where('status', 'active');
		});

		// Apply filters based on $id
		$this->applyJobCategoryFilter($query, $id);

		// Apply search filters from request
		$this->applySearchFilters($query->get(), $request);
		
		$radius = 15; //km
		//$this->filterApplicantsWithActiveSales($query, $radius);
		

		// Prepare output
		$iFilteredTotal = $query->count();

		// Pagination handling
		$iStart = $request->get('iDisplayStart');  // The starting point for the next page
		$iPageSize = $request->get('iDisplayLength');  // The number of records per page
		if ($iStart !== null && $iPageSize !== '-1') {
			$query->skip($iStart)->take($iPageSize);
		}

		// Sorting logic
		$order = $this->getSortingOrder($request);
		$query->orderBy($order['column'], $order['direction']);

		// Execute the query
		$applicants = $query->get();


		$output = [
			"sEcho" => intval($request->get('sEcho')),
			"iTotalRecords" => $iFilteredTotal,
			"iTotalDisplayRecords" => $iFilteredTotal,
			"aaData" => array()
		];

		$i = 0;

	foreach ($applicants as $sRow) {
		$status_value = 'open';
		$color_class = 'bg-teal-800';
		if ($sRow->paid_status == 'close') {
			$status_value = 'paid';
			$color_class = 'bg-slate-700';
		} else {
			foreach ($sRow->cv_notes as $key => $value) {
				if ($value->status == 'active') {
					$status_value = 'sent';
					break;
				} elseif ($value->status == 'disable') {
					$status_value = 'reject';
				}
			}
		}

		// Status Badge
		$status = '<h3><span class="badge w-100 ' . $color_class . '">' . strtoupper($status_value) . '</span></h3>';

		// CV and Updated CV File Links
		$filePath = $sRow->applicant_cv;
		$disabled = (!file_exists($filePath) || $sRow->applicant_cv == null) ? 'disabled' : '';
		$disabled_color = (!file_exists($filePath) || $sRow->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
		$href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $sRow->id);
		$download = '<a class="download-link ' . $disabled . '" href="' . $href . '"><i class="fas fa-file-download ' . $disabled_color . '"></i></a>';

		// Updated CV Links
		$updated_cvfilePath = $sRow->updated_cv;
		$disabled = (!file_exists($updated_cvfilePath) || $sRow->updated_cv == null) ? 'disabled' : '';
		$disabled_color = (!file_exists($updated_cvfilePath) || $sRow->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
		$href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $sRow->id);
		$updated_cv = '<a class="download-link ' . $disabled . '" href="' . $href . '"><i class="fas fa-file-download ' . $disabled_color . '"></i></a>';

		// Job Title Description
		if ($sRow->job_title_prof != null) {
			$job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $sRow->job_title_prof)->first();
			$job_title_desc = $sRow->applicant_job_title . ' (' . $job_prof_res->specialist_prof . ')';
		} else {
			$job_title_desc = $sRow->applicant_job_title;
		}
		$job_title_desc = strtoupper($job_title_desc);

		// Upload Link
		$upload = '<a href="#" data-controls-modal="#import_applicant_cv" class="import_cv" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-id="' . $sRow->id . '" data-target="#import_applicant_cv"><i class="fas fa-file-upload text-teal-400"></i></a>';

		// Row Class based on status
		$row_class = '';
		if ($sRow->paid_status == 'close') {
			$row_class = 'class_dark';
		} elseif ($sRow->is_no_job == '1') {
			$row_class = 'class_noJob';
		} else {
			foreach ($sRow->cv_notes as $key => $value) {
				if ($value->status == 'active') {
					$row_class = 'class_success';
					break;
				} elseif ($value->status == 'disable') {
					$row_class = 'class_danger';
				}
			}
		}

		if ($sRow->cv_notes->isNotEmpty()) {
			$agent_name = $sRow->cv_notes->first()->user->name ?? null;
		}else{
			$agent_name = '-';
		}

		$history = '';
		$history .= '<a href="#" class="reject_history" data-applicant="' . $sRow->id . '"; 
									 data-controls-modal="#reject_history' . $sRow->id . '" 
									 data-backdrop="static" data-keyboard="false" data-toggle="modal"
									 data-target="#reject_history' . $sRow->id . '">History</a>';

		$history .= '<div id="reject_history' . $sRow->id . '" class="modal fade" tabindex="-1">';
		$history .= '<div class="modal-dialog modal-lg">';
		$history .= '<div class="modal-content">';
		$history .= '<div class="modal-header">';
		$history .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $sRow->applicant_name . '</span></h6>';
		$history .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
		$history .= '</div>';
		$history .= '<div class="modal-body" id="applicant_rejected_history' . $sRow->id . '" style="max-height: 500px; overflow-y: auto;">';

		/*** Details are fetched via ajax request */

		$history .= '</div>';
		$history .= '<div class="modal-footer">';
		$history .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
		$history .= '</div>';
		$history .= '</div>';
		$history .= '</div>';
		$history .= '</div>';


		$status_value2 = 'open';
		$postcode = '';
		if ($sRow->paid_status == 'close') {
			$status_value2 = 'paid';
		} else {
			foreach ($sRow->cv_notes as $key => $value) {
				if ($value->status == 'active') {
					$status_value2 = 'sent';
					break;
				} elseif ($value->status == 'disable') {
					$status_value2 = 'reject';
				}
			}
		}
		if ($status_value2 == 'open' || $status_value2 == 'reject') {
			$postcode .= '<a href="/available-jobs/' . $sRow->id . '">';
			$postcode .= $sRow->applicant_postcode;
			$postcode .= '</a>';
		} else {
			$postcode .= $sRow->applicant_postcode;
		}

		// Output the data for DataTable
		$output['aaData'][] = array(
			@Carbon::parse($sRow->updated_at)->toFormattedDateString(),
			@Carbon::parse($sRow->updated_at)->format('h:i A'),
			@$agent_name,
			@ucwords($sRow->applicant_name),
			@$sRow->applicant_email,
			@$job_title_desc,
			@strtoupper($sRow->job_category),
			@$postcode,
			@$sRow->applicant_phone,
			@$download,
			@$updated_cv,
			@$upload,
			@$sRow->applicant_homePhone,
			@$sRow->applicant_source,
			@$sRow->applicant_notes,
			@$history,
			@$status
		);

		$i++;
	}


		return response()->json($output);
	}
		// Helper method for job category filtering
	private function applyJobCategoryFilter($query, $id)
	{
		switch ($id) {
			case "44":
				$query->where("applicants.job_category", "nurse");
				break;
			case "45":
				$query->where("applicants.job_category", "non-nurse")
					->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
				break;
			case "46":
				$query->where("applicants.job_category", "non-nurse")
					->where("applicants.applicant_job_title", "nonnurse specialist");
				break;
			case "47":
				$query->where("applicants.job_category", "chef");
				break;
			case "48":
				$query->where("applicants.job_category", "nursery");
				break;
		}
	}

	// Helper method for search filters
	private function applySearchFilters($query, $request)
	{
		$sKeywords = $request->get('sSearch');
		if (!empty($sKeywords)) {
			$query->where(function ($query) use ($sKeywords) {
				$query->orWhere('applicant_job_title', 'LIKE', "%{$sKeywords}%")
					->orWhere('applicant_name', 'LIKE', "%{$sKeywords}%")
					->orWhere('applicant_postcode', 'LIKE', "%{$sKeywords}%")
					->orWhere('status', 'LIKE', "%{$sKeywords}%");
			});
		}

		$aColumns = ['updated_at', 'applicant_name', 'applicant_email', 'applicant_job_title', 'job_category', 'applicant_postcode', 'applicant_phone', 'applicant_homePhone', 'status'];
		for ($i = 0; $i < count($aColumns); $i++) {
			$searchColumn = $request->get('sSearch_' . $i);
			if ($request->get('bSearchable_' . $i) == "true" && !empty($searchColumn)) {
				$query->orWhere($aColumns[$i], 'LIKE', "%" . $searchColumn . "%");
			}
		}
	}

	// Helper method for sorting logic
	private function getSortingOrder($request)
	{
		$aColumns = ['updated_at', 'applicant_name', 'applicant_email', 'applicant_job_title', 'job_category', 'applicant_postcode', 'applicant_phone', 'applicant_homePhone', 'status'];
		$orderColumn = 'id';
		$sortDirection = 'ASC';

		if ($request->get('iSortCol_0')) {
			$sOrder = "";
			for ($i = 0; $i < intval($request->get('iSortingCols')); $i++) {
				if ($request->get('bSortable_' . intval($request->get('iSortCol_' . $i))) == "true") {
					$sOrder .= $aColumns[intval($request->get('iSortCol_' . $i))] . " " . $request->get('sSortDir_' . $i) . ", ";
				}
			}

			$sOrder = rtrim($sOrder, ", ");
			if (empty($sOrder)) {
				$sOrder = "id ASC";
			}

			$OrderArray = explode(' ', $sOrder);
			$orderColumn = trim($OrderArray[0]);
			$sortDirection = strtoupper(trim($OrderArray[1]));
		}

		return ['column' => $orderColumn, 'direction' => $sortDirection];
	}
	
	public function filterApplicantsWithActiveSales($query, $radius)
{
    $query->whereHas('active_sales', function($query) use ($radius) {
        $applicant = $this;  // Assuming you're calling this inside an Applicant instance context
        $lat = 51.127888;  // Get latitude
        $lon = -3.003632;  // Get longitude

        // Calculate the distance using the Haversine formula
        $query->select(DB::raw("*, 
            ((ACOS(SIN(lat * PI() / 180) * SIN($lat * PI() / 180) + 
            COS(lat * PI() / 180) * COS($lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) 
            AS distance"))
            ->having("distance", "<", $radius);  // Apply the radius filter
    });
}


	
	// Function to filter applicants with active sales
	 private function filterApplicantsWithActiveSalesOld($applicants, $radius) {
		$filtered_applicants = collect();

		foreach ($applicants as $applicant) {

			$lat = $applicant->lat;
			$lon = $applicant->lng;
			$title = $this->getAllTitles($applicant->applicant_job_title); // Ensure this function returns an array of titles

			// Query sales within the radius
			$location_distance = Sale::select(DB::raw("*, 
				((ACOS(SIN(lat * PI() / 180) * SIN($lat * PI() / 180) + 
				COS(lat * PI() / 180) * COS($lat * PI() / 180) * COS(($lon - lng) * PI() / 180)) * 180 / PI()) * 60 * 1.1515) 
				AS distance"), DB::raw("(SELECT count(cv_notes.sale_id) from cv_notes
				WHERE cv_notes.sale_id=sales.id AND cv_notes.status='active' group by cv_notes.sale_id) as cv_notes_count"))
				->where("status", "active")
				->where("is_on_hold", "0")
				->having("distance", "<", $radius)
				->whereIn("job_title", $title);


			// Execute the query and check if results exist
			if ($location_distance->exists()) {
				$filtered_applicants->push($applicant);
			}
		}

		return $filtered_applicants;
	}
	
	public function getLast2monthsNewApplicantAddedTemp($id)
    {
        $interval = 21;

        return view('administrator.resource.resources_added_applicants.temp_last_2_months_applicant_added', compact('interval', 'id'));
    }

	public function getlast2MonthsApplicationAjaxTemp($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subMonths(1)->subDays(37);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(25);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
				'applicants.is_job_within_radius'
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where("applicants.temp_not_interested", "0")
            ->where("applicants.is_blocked", "0")
			->where("applicants.is_job_within_radius", "1")
			->where("applicants.is_no_job", "0")
            ->whereNull('applicants_pivot_sales.applicant_id')
			->whereDoesntHave('cv_notes', function ($query) {
				$query->where('status', 'active');
			});

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants
       
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
                /*** logic before open-applicant-cv-feature
                foreach ($applicant->cv_notes as $key => $value) {
                    if ($value->status == 'active') {
                        $status_value = 'sent';
                        break;
                    } elseif ($value->status == 'disable') {
                        $status_value = 'reject';
                    } elseif ($value->status == 'paid') {
                        $status_value = 'paid';
                        break;
                    }
                }
                 */
                if ($status_value == 'open' || $status_value == 'reject') {
                    $postcode .= '<a href="/available-jobs/' . $applicant->id . '">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }

                return $postcode;
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $applicant->applicant_job_title . ' (' . $job_prof_res->specialist_prof . ')';
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })

            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }
	
	public function getlast2MonthsNotInterestedApplicationAjaxTemp($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subMonths(1)->subDays(37);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(25);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
				'applicants.is_job_within_radius',
				'applicants.temp_not_interested'
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where("applicants.is_blocked", "0")
			->where("applicants.is_job_within_radius", "1")
			->where("applicants.is_no_job", "0")
            ->where(function ($query) {
                $query->where("applicants.temp_not_interested", "1")
                    ->orWhereNotNull('applicants_pivot_sales.applicant_id');
            })
			->whereDoesntHave('cv_notes', function ($query) {
				$query->where('status', 'active');
			});

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants
       
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
                /*** logic before open-applicant-cv-feature
                foreach ($applicant->cv_notes as $key => $value) {
                    if ($value->status == 'active') {
                        $status_value = 'sent';
                        break;
                    } elseif ($value->status == 'disable') {
                        $status_value = 'reject';
                    } elseif ($value->status == 'paid') {
                        $status_value = 'paid';
                        break;
                    }
                }
                 */
                if ($status_value == 'open' || $status_value == 'reject') {
                    $postcode .= '<a href="/available-jobs/' . $applicant->id . '">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }

                return $postcode;
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $applicant->applicant_job_title . ' (' . $job_prof_res->specialist_prof . ')';
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })

            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }
	
	public function getlast2MonthsBlockedApplicationAjaxTemp($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subMonths(1)->subDays(37);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(25);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
				'applicants.is_job_within_radius',
				'applicants.temp_not_interested'
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where("applicants.is_blocked", "1")
			->where("applicants.is_no_job", "0")
            ->where("applicants.temp_not_interested", "0")
			->whereDoesntHave('cv_notes', function ($query) {
				$query->where('status', 'active');
			});

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants
       
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
               // if ($status_value == 'open' || $status_value == 'reject') {
                 //   $postcode .= '<a href="/available-jobs/'.$applicant->id.'">';
                //    $postcode .= strtoupper($applicant->applicant_postcode);
                //    $postcode .= '</a>';
              //  } else {
               //     $postcode .= strtoupper($applicant->applicant_postcode);
             //   }
                return strtoupper($applicant->applicant_postcode);
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $applicant->applicant_job_title . ' (' . $job_prof_res->specialist_prof . ')';
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })
 			->addColumn('checkbox', function ($applicant) {
                return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
            })
            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'checkbox', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }
	
	public function getLast2monthsNewApplicantAddedTemp2($id)
    {
        $interval = 21;

        return view('administrator.resource.resources_added_applicants.temp2_last_2_months_applicant_added', compact('interval', 'id'));
    }

    public function getlast2MonthsApplicationAjaxTemp2($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subMonths(1)->subDays(62);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(5);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
				'applicants.is_job_within_radius'
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where("applicants.temp_not_interested", "0")
            ->where("applicants.is_blocked", "0")
			->where("applicants.is_job_within_radius", "1")
			->where("applicants.is_no_job", "0")
            ->whereNull('applicants_pivot_sales.applicant_id')
			->whereDoesntHave('cv_notes', function ($query) {
				$query->where('status', 'active');
			});

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants
       
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
                /*** logic before open-applicant-cv-feature
                foreach ($applicant->cv_notes as $key => $value) {
                    if ($value->status == 'active') {
                        $status_value = 'sent';
                        break;
                    } elseif ($value->status == 'disable') {
                        $status_value = 'reject';
                    } elseif ($value->status == 'paid') {
                        $status_value = 'paid';
                        break;
                    }
                }
                 */
                if ($status_value == 'open' || $status_value == 'reject') {
                    $postcode .= '<a href="/available-jobs/' . $applicant->id . '">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }

                return $postcode;
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
               return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $applicant->applicant_job_title . ' (' . $job_prof_res->specialist_prof . ')';
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })

            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }
	
	public function getlast2MonthsNotInterestedApplicationAjaxTemp2($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subMonths(1)->subDays(62);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(5);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
				'applicants.is_job_within_radius'
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where("applicants.is_blocked", "0")
			->where("applicants.is_job_within_radius", "1")
			->where("applicants.is_no_job", "0")
            ->where(function ($query) {
                $query->where("applicants.temp_not_interested", "1")
                    ->orWhereNotNull('applicants_pivot_sales.applicant_id');
            })
			->whereDoesntHave('cv_notes', function ($query) {
				$query->where('status', 'active');
			});

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants
       
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
                /*** logic before open-applicant-cv-feature
                foreach ($applicant->cv_notes as $key => $value) {
                    if ($value->status == 'active') {
                        $status_value = 'sent';
                        break;
                    } elseif ($value->status == 'disable') {
                        $status_value = 'reject';
                    } elseif ($value->status == 'paid') {
                        $status_value = 'paid';
                        break;
                    }
                }
                 */
                if ($status_value == 'open' || $status_value == 'reject') {
                    $postcode .= '<a href="/available-jobs/' . $applicant->id . '">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }

                return $postcode;
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $applicant->applicant_job_title . ' (' . $job_prof_res->specialist_prof . ')';
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })

            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }
	
	public function getlast2MonthsBlockedApplicationAjaxTemp2($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subMonths(1)->subDays(62);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(5);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
				'applicants.is_job_within_radius'
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where("applicants.is_blocked", "1")
			->where("applicants.is_no_job", "0")
			->where("applicants.temp_not_interested", "0")
			->whereDoesntHave('cv_notes', function ($query) {
				$query->where('status', 'active');
			});

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants
       
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
               // if ($status_value == 'open' || $status_value == 'reject') {
                 //   $postcode .= '<a href="/available-jobs/'.$applicant->id.'">';
                //    $postcode .= strtoupper($applicant->applicant_postcode);
                //    $postcode .= '</a>';
              //  } else {
               //     $postcode .= strtoupper($applicant->applicant_postcode);
             //   }
                return strtoupper($applicant->applicant_postcode);
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $applicant->applicant_job_title . ' (' . $job_prof_res->specialist_prof . ')';
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })
 			->addColumn('checkbox', function ($applicant) {
                return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
            })
            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'checkbox', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }
	
	 // new added
    public function getLast2monthsNewApplicantAdded($id)
    {
        $interval = 21;

        return view('administrator.resource.resources_added_applicants.last_2_months_applicant_added', compact('interval', 'id'));
    }

    public function getlast2MonthsApplicationAjax($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subDays(37);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subMonths(1);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
				'applicants.is_job_within_radius'
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where("applicants.temp_not_interested", "0")
            ->where("applicants.is_blocked", "0")
			->where("applicants.is_no_job", "0")
			->where("applicants.is_job_within_radius", "1")
            ->whereNull('applicants_pivot_sales.applicant_id')
			->whereDoesntHave('cv_notes', function ($query) {
				$query->where('status', 'active');
			});

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants
       
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
                /*** logic before open-applicant-cv-feature
                foreach ($applicant->cv_notes as $key => $value) {
                    if ($value->status == 'active') {
                        $status_value = 'sent';
                        break;
                    } elseif ($value->status == 'disable') {
                        $status_value = 'reject';
                    } elseif ($value->status == 'paid') {
                        $status_value = 'paid';
                        break;
                    }
                }
                 */
                if ($status_value == 'open' || $status_value == 'reject') {
                    $postcode .= '<a href="/available-jobs/' . $applicant->id . '">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }

                return $postcode;
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $applicant->applicant_job_title . ' (' . $job_prof_res->specialist_prof . ')';
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })

            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }
	
	public function getlast2MonthsNotInterestedApplicationAjax($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subDays(37);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subMonths(1);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
                'applicants.is_job_within_radius',
				"applicants.temp_not_interested"
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where(function ($query) {
                $query->where("applicants.temp_not_interested", "1")
                      ->orWhereNotNull('applicants_pivot_sales.applicant_id');
            })
            ->where("applicants.is_blocked", "0")
            ->where("applicants.is_no_job", "0")
            ->where("applicants.is_job_within_radius", "1")
            ->whereDoesntHave('cv_notes', function ($query) {
                $query->where('status', 'active');
            });

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants

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
                /*** logic before open-applicant-cv-feature
                foreach ($applicant->cv_notes as $key => $value) {
                    if ($value->status == 'active') {
                        $status_value = 'sent';
                        break;
                    } elseif ($value->status == 'disable') {
                        $status_value = 'reject';
                    } elseif ($value->status == 'paid') {
                        $status_value = 'paid';
                        break;
                    }
                }
                 */
                if ($status_value == 'open' || $status_value == 'reject') {
                    $postcode .= '<a href="/available-jobs/' . $applicant->id . '">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }

                return $postcode;
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $applicant->applicant_job_title . ' (' . $job_prof_res->specialist_prof . ')';
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })

            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }
   
    public function getlast2MonthsBlockedApplicationAjax($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subDays(37);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subMonths(1);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
                'applicants.is_job_within_radius'
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where("applicants.temp_not_interested", "0")
            ->where("applicants.is_blocked", "1")
            ->where("applicants.is_no_job", "0")
            ->whereDoesntHave('cv_notes', function ($query) {
                $query->where('status', 'active');
            });

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants

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
                // if ($status_value == 'open' || $status_value == 'reject') {
                //   $postcode .= '<a href="/available-jobs/'.$applicant->id.'">';
                //    $postcode .= strtoupper($applicant->applicant_postcode);
                //    $postcode .= '</a>';
                //  } else {
                //     $postcode .= strtoupper($applicant->applicant_postcode);
                //   }
                return strtoupper($applicant->applicant_postcode);
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
               return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $applicant->applicant_job_title . ' (' . $job_prof_res->specialist_prof . ')';
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })
 			->addColumn('checkbox', function ($applicant) {
                return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
            })
            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'checkbox', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }

    public function getLast3monthsApplicantAdded($id)
    {
        $interval = 21;

        return view('administrator.resource.resources_added_applicants.last_3_months_applicant_added', compact('interval', 'id'));
    }

    public function getlast3MonthsApplicationAjax($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subMonths(2)->subDays(37);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subMonths(3);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
				'applicants.is_job_within_radius',
				'applicants.department', 'applicants.sub_department',
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where("applicants.temp_not_interested", "0")
            ->where("applicants.is_blocked", "0")
			->where("applicants.is_job_within_radius", "1")
            ->whereNull('applicants_pivot_sales.applicant_id')
			->whereDoesntHave('cv_notes', function ($query) {
				$query->where('status', 'active');
			});

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants

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
                    $postcode .= '<a href="/available-jobs/' . $applicant->id . '">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }

                return $postcode;
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $job_prof_res->specialist_prof;
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }
	
	 public function getlast3MonthsNotInterestedApplicationAjax($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subMonths(2)->subDays(37);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subMonths(3);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
                'applicants.is_job_within_radius',
				'applicants.department', 'applicants.sub_department',
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where(function ($query) {
                $query->where("applicants.temp_not_interested", "1")
                    ->orWhereNotNull('applicants_pivot_sales.applicant_id');
            })
            ->where("applicants.is_blocked", "0")
            ->where("applicants.is_job_within_radius", "1")
            ->whereDoesntHave('cv_notes', function ($query) {
                $query->where('status', 'active');
            });

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants

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
                    $postcode .= '<a href="/available-jobs/' . $applicant->id . '">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }

                return $postcode;
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $job_prof_res->specialist_prof;
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })

            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }

    public function getlast3MonthsBlockedApplicationAjax($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subMonths(2)->subDays(37);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subMonths(3);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
                'applicants.is_job_within_radius',
				"applicants.temp_not_interested",
				'applicants.department', 'applicants.sub_department',
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where("applicants.temp_not_interested", "0")
            ->where("applicants.is_blocked", "1")
            ->where("applicants.is_job_within_radius", "1")
            ->whereDoesntHave('cv_notes', function ($query) {
                $query->where('status', 'active');
            });

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants

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
                return strtoupper($applicant->applicant_postcode);
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
			 ->addColumn('checkbox', function ($applicant) {
                return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $job_prof_res->specialist_prof;
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
			
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'checkbox', 'upload', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }

    public function getLast6monthsApplicantAdded($id)
    {
        $interval = 21;

        return view('administrator.resource.resources_added_applicants.last_6_months_applicant_added', compact('interval', 'id'));
    }

    public function getlast6MonthsApplicationAjax($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subMonths(5)->subDays(37);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subMonths(6);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
				'applicants.is_job_within_radius',
				'applicants.temp_not_interested',
				'applicants.department', 'applicants.sub_department',
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where("applicants.temp_not_interested", "0")
            ->where("applicants.is_blocked", "0")
            ->whereNull('applicants_pivot_sales.applicant_id')
			->where("applicants.is_job_within_radius", "1")
			->whereDoesntHave('cv_notes', function ($query) {
				$query->where('status', 'active');
			});

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants

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
                    $postcode .= '<a href="/available-jobs/' . $applicant->id . '">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }

                return $postcode;
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                 return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $job_prof_res->specialist_prof;
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }
	
	public function getlast6MonthsNotInterestedApplicationAjax($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subMonths(5)->subDays(37);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subMonths(6);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
                'applicants.is_job_within_radius',
                'applicants.temp_not_interested',
				'applicants.department', 'applicants.sub_department',
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where("applicants.is_blocked", "0")
            ->where("applicants.is_job_within_radius", "1")
            ->where(function ($query) {
                $query->where("applicants.temp_not_interested", "1")
                      ->orWhereNotNull('applicants_pivot_sales.applicant_id');
            })
            ->whereDoesntHave('cv_notes', function ($query) {
                $query->where('status', 'active');
            });

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants

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
                    $postcode .= '<a href="/available-jobs/' . $applicant->id . '">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }

                return $postcode;
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                 return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $job_prof_res->specialist_prof;
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }

    public function getlast6MonthsBlockedApplicationAjax($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subMonths(5)->subDays(37);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subMonths(6);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
                'applicants.is_job_within_radius',
				'applicants.department', 'applicants.sub_department'
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where("applicants.temp_not_interested", "0")
            ->where("applicants.is_blocked", "1")
            ->whereDoesntHave('cv_notes', function ($query) {
                $query->where('status', 'active');
            });

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants

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
                return strtoupper($applicant->applicant_postcode);
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                 return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
			 ->addColumn('checkbox', function ($applicant) {
                return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $job_prof_res->specialist_prof;
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })

            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'checkbox', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }
    
    public function getLast12monthsApplicantAdded($id)
    {
        $interval = 21;

        return view('administrator.resource.resources_added_applicants.last_12_months_applicant_added', compact('interval', 'id'));
    }

    public function getlast12MonthsApplicationAjax($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subMonths(11)->subDays(37);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subMonths(12);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
				'applicants.is_job_within_radius',
				'applicants.department', 'applicants.sub_department'
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where("applicants.temp_not_interested", "0")
            ->where("applicants.is_blocked", "0")
			->where("applicants.is_job_within_radius", "1")
            ->whereNull('applicants_pivot_sales.applicant_id')
			->whereDoesntHave('cv_notes', function ($query) {
				$query->where('status', 'active');
			});

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants
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
                    $postcode .= '<a href="/available-jobs/' . $applicant->id . '">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }

                return $postcode;
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')
						->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc =  $job_prof_res->specialist_prof;
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicants){
				$sub_department = $applicants->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }
	
	 public function getlast12MonthsNotInterestedApplicationAjax($id)
	 {
        $end_date = Carbon::now();
        $end_date = $end_date->subMonths(11)->subDays(37);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subMonths(12);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
                'applicants.is_job_within_radius',
                'applicants.temp_not_interested',
				'applicants.department'
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where(function ($query) {
                $query->where("applicants.temp_not_interested", "1")
                    ->orWhereNotNull('applicants_pivot_sales.applicant_id');
            })
            ->where("applicants.is_blocked", "0")
            ->where("applicants.is_job_within_radius", "1")
            ->whereDoesntHave('cv_notes', function ($query) {
                $query->where('status', 'active');
            });

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants

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
                    $postcode .= '<a href="/available-jobs/' . $applicant->id . '">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }

                return $postcode;
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $job_prof_res->specialist_prof;
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })

            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }
    
    public function getlast12MonthsBlockedApplicationAjax($id)
    {
        $end_date = Carbon::now();
        $end_date = $end_date->subMonths(11)->subDays(37);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subMonths(12);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
        }])
            ->select([
                'applicants.id',
                'applicants.updated_at',
                'applicants.is_no_job',
                'applicants.applicant_added_time',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants.lat',
                'applicants.lng',
                'applicants.is_job_within_radius',
                'applicants.temp_not_interested',
				'applicants.department', 'applicants.sub_department',
            ])
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->where("applicants.status", "active")
            ->where("applicants.temp_not_interested", "0")
            ->where("applicants.is_blocked", "1")
            ->whereNull('applicants_pivot_sales.applicant_id')
            ->whereDoesntHave('cv_notes', function ($query) {
                $query->where('status', 'active');
            });

        // Apply filters based on $id
        switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }

        $result = $result1->orderBy('applicants.updated_at', 'DESC'); // Execute the query to get the applicants

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
                return strtoupper($applicant->applicant_postcode);
            })
            ->addColumn("agent_name", function ($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn("updated_at", function ($result) {
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at, 'd F Y');
                return Carbon::parse($result->updated_at)->toFormattedDateString();
            })
            ->addColumn('applicant_notes', function ($applicant) {

                $app_new_note = ModuleNote::where(['module_noteable_id' => $applicant->id, 'module_noteable_type' => 'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();
                $app_notes_final = '';
                if ($app_new_note) {
                    $app_notes_final = $app_new_note->details;
                } else {
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

                if ($applicant->is_blocked == 0 && $status_value == 'open' || $status_value == 'reject') {

                    $content = '';
                    // if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="' . $applicant->id . '"
                                 data-controls-modal="#clear_cv' . $applicant->id . '"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"' . $app_notes_final . '"</a>';
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
                    $content .= '<div id="app_notes_alert' . $applicant->id . '"></div>';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id . '">';
                    $content .= '<option value="0" >Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
                } else {
                    return $app_notes_final;
                }
            })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="' . $crm_start_date_hold_applicant->id . '"; 
                                 data-controls-modal="#reject_history' . $crm_start_date_hold_applicant->id . '" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history' . $crm_start_date_hold_applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">' . $crm_start_date_hold_applicant->applicant_name . '</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history' . $crm_start_date_hold_applicant->id . '" style="max-height: 500px; overflow-y: auto;">';

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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 ' . $color_class . '">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
            ->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download ' . $disabled_color . '"></i>
                    </a>';
            })
            ->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $job_prof_res->specialist_prof;
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
 			->addColumn('checkbox', function ($applicant) {
                return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
            })
            ->addColumn('upload', function ($applicant) {
                return
                    '<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="' . $applicant->id . '"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
                } else {
                    /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
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
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'checkbox', 'applicant_notes', 'status', 'applicant_postcode', 'history'])
            ->make(true);
    }
    //end new
	
	
	public function getlast21DaysAppNotInterested($id)
    {
        $end_date = Carbon::now();
        $edate7 = $end_date->subDays(16);
        $edate = $edate7->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(21);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function($query) {
                        $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                            ->with(['user:id,name'])->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
                    }])
            ->leftJoin('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
            ->leftJoin('sales', 'sales.id', '=', 'applicants_pivot_sales.sales_id')
            ->leftJoin('notes_for_range_applicants', 'applicants_pivot_sales.id', '=', 'notes_for_range_applicants.applicants_pivot_sales_id')
            ->leftJoin('offices', 'offices.id', '=', 'sales.head_office')
            ->leftJoin('units', 'units.id', '=', 'sales.head_office_unit')
            ->select(
                'applicants.id',
                'applicants.updated_at',
                'applicants.temp_not_interested',
                'applicants.applicant_added_time',
                'applicants.is_no_job',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants_pivot_sales.sales_id as pivot_sale_id',
                'applicants_pivot_sales.id as pivot_id',
				'applicants.is_job_within_radius',
				'applicants.department', 'applicants.sub_department',
            )
            ->where(function ($query) {
				$query->where("applicants.temp_not_interested", "1")
					  ->orWhereNotNull('applicants_pivot_sales.applicant_id');
			})
            ->where("applicants.is_job_within_radius", "1")
            ->where("applicants.is_no_job", "=", "0")
            ->where("applicants.status", "active")
            ->whereBetween('applicants.updated_at', [$sdate, $edate]);

            // Apply filters based on $id
			switch ($id) {
				case "44":
					$result1->where("applicants.job_category", "nurse");
					break;
				case "45":
					$result1->where("applicants.job_category", "non-nurse")
						->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
					break;
				case "46":
					$result1->where("applicants.job_category", "non-nurse")
						->where("applicants.applicant_job_title", "nonnurse specialist");
					break;
				case "47":
					$result1->where("applicants.job_category", "chef");
					break;
				case "48":
					$result1->where("applicants.job_category", "nursery");
					break;
			}

           $result = $result1->orderBy('applicants.updated_at', 'DESC');
		
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
                if($status_value == 'open' || $status_value == 'reject'){
                    $postcode .= '<a href="/available-jobs/'.$applicant->id.'">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }
				
                return $postcode;
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
            ->addColumn("updated_at",function($result){
                $updated_at = new DateTime($result->updated_at);
                $date = date_format($updated_at,'d F Y');
                    return Carbon::parse($result->updated_at)->toFormattedDateString();
                })
			->addColumn("agent_name", function($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
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
                $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
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
					$content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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

                })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$crm_start_date_hold_applicant->id.'"; 
                                 data-controls-modal="#reject_history'.$crm_start_date_hold_applicant->id.'" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$crm_start_date_hold_applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">'.$crm_start_date_hold_applicant->applicant_name.'</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$crm_start_date_hold_applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 '.$color_class.'">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
			->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';

            })
			->editColumn('applicant_job_title', function ($applicant) {
				$job_title_desc='';
				if($applicant->job_title_prof!=null)
				{
					$job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $applicant->job_title_prof)->first();
					$job_title_desc = $job_prof_res->specialist_prof;
				}
				else
				{

					$job_title_desc = $applicant->applicant_job_title;
				}
				return strtoupper($job_title_desc);

			})
			->addColumn('upload', function ($applicant) {
				return
				'<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="'.$applicant->id.'"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
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
                return $row_class;
            })
            ->rawColumns(['applicant_job_title','updated_at','download','updated_cv','upload','applicant_notes','status','applicant_postcode','history'])
        ->make(true);
    }

    public function getlast21DaysAppBlocked($id)
    {
		// '2024-08-06'
        $end_date = Carbon::now();
        $edate7 = $end_date->subDays(16);
        $edate = $edate7->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(21);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";

        $result1 = Applicant::with(['cv_notes' => function($query) {
                        $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                            ->with(['user:id,name'])->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
                    }])
			->leftJoin('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
			->leftJoin('sales', 'sales.id', '=', 'applicants_pivot_sales.sales_id')
			->leftJoin('notes_for_range_applicants', 'applicants_pivot_sales.id', '=', 'notes_for_range_applicants.applicants_pivot_sales_id')
			->leftJoin('offices', 'offices.id', '=', 'sales.head_office')
			->leftJoin('units', 'units.id', '=', 'sales.head_office_unit')
			->select(
				'applicants.id',
				'applicants.updated_at',
				'applicants.temp_not_interested',
				'applicants.applicant_added_time',
				'applicants.is_no_job',
				'applicants.applicant_name',
				'applicants.applicant_job_title',
				'applicants.job_title_prof',
				'applicants.job_category',
				'applicants.applicant_postcode',
				'applicants.applicant_phone',
				'applicants.applicant_homePhone',
				'applicants.applicant_source',
				'applicants.applicant_email',
				'applicants.applicant_notes',
				'applicants.paid_status',
				'applicants.applicant_cv',
				'applicants.updated_cv',
				'applicants_pivot_sales.sales_id as pivot_sale_id',
				'applicants_pivot_sales.id as pivot_id',
				'applicants.is_job_within_radius',
				'applicants.department', 'applicants.sub_department',
			)
			->where("applicants.temp_not_interested", "0")
			->where("applicants.temp_not_interested", "0")
			->where("applicants.is_blocked", "1")
			->where("applicants.is_no_job", "=", "0")
			->where("applicants.status", "active")
			->whereBetween('applicants.updated_at', [$sdate, $edate]);
    
        // Apply filters based on $id
         switch ($id) {
            case "44":
                $result1->where("applicants.job_category", "nurse");
                break;
            case "45":
                $result1->where("applicants.job_category", "non-nurse")
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                break;
            case "46":
                $result1->where("applicants.job_category", "non-nurse")
                    ->where("applicants.applicant_job_title", "nonnurse specialist");
                break;
            case "47":
                $result1->where("applicants.job_category", "chef");
                break;
            case "48":
                $result1->where("applicants.job_category", "nursery");
                break;
        }
        
         $result = $result1->orderBy('applicants.updated_at', 'DESC');

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
                return $applicant->applicant_postcode;
            })
            ->addColumn("updated_at",function($result){
              //  $updated_at = new DateTime($result->updated_at);
             //   $date = date_format($updated_at,'d F Y');
                    return Carbon::parse($result->updated_at)->toFormattedDateString();
                })
			->addColumn("agent_name", function($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
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
                $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="21_days_applicants">';
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
					$content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
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
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$crm_start_date_hold_applicant->id.'"; 
                                 data-controls-modal="#reject_history'.$crm_start_date_hold_applicant->id.'" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$crm_start_date_hold_applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">'.$crm_start_date_hold_applicant->applicant_name.'</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$crm_start_date_hold_applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

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
                $status = '';
                $status .= '<h3>';
                $status .= '<span class="badge w-100 '.$color_class.'">';
                $status .= strtoupper($status_value);
                $status .= '</span>';
                $status .= '</h3>';
                return $status;
            })
			->addColumn('download', function ($applicant) {
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';
                return $download;
            })
            ->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';

            })
			->editColumn('applicant_job_title', function ($applicant) {
				$job_title_desc='';
				if($applicant->job_title_prof!=null)
				{
					$job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $applicant->job_title_prof)->first();
					$job_title_desc = $job_prof_res->specialist_prof;
				}
				else
				{

					$job_title_desc = $applicant->applicant_job_title;
				}
				return strtoupper($job_title_desc);

			})
       		->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
			->addColumn('upload', function ($applicant) {
				return
				'<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="'.$applicant->id.'"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
			})
			 ->addColumn('checkbox', function ($applicant) {
                return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
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
                return $row_class;
            })
            ->rawColumns(['applicant_job_title', 'updated_at', 'download', 'updated_cv', 'upload', 'applicant_notes', 'status', 'checkbox', 'applicant_postcode', 'history'])
        ->make(true);
    }
	
	public function export_Last2MonthsApplicantAdded(Request $request)
    {
        //$end_date = Carbon::now();
        //$edate21 = $end_date->subMonth(1)->subDays(6); // 9 + 21 + excluding last_day . 00:00:00
        //$edate = $edate21->format('Y-m-d');
        //$start_date = $end_date->subMonths(60);
        //$sdate = $start_date->format('Y-m-d');
		 //$job_category=$request->input('hidden_job_value');
        //return Excel::download(new Applicant_21_days_export($sdate,$edate,$job_category), 'applicants.csv');
        //$job_category='nurse';
        //return Excel::download(new ResourcesExport($sdate,$edate,$job_category), 'applicants.csv');
		//new code
		 $end_date = Carbon::now();
        $edate = $end_date->format('Y-m-d');

        $start_date = $end_date->subMonth(1)->subDays(6);
        $sdate = $start_date->format('Y-m-d');
        $job_category=$request->input('hidden_job_value');
        return Excel::download(new Applicant_2M_days_export($sdate,$edate,$job_category), 'applicants.csv');

    }
	
	 public function getLast2MonthsBlockedApplicantAdded()
    {
        // $end_date = Carbon::now();
        // //$edate21 = $end_date->subDays(31); // 9 + 21 + excluding last_day . 00:00:00
        // $edate = $end_date->format('Y-m-d');
        // $start_date = $end_date->subMonths(60);
        // $sdate = $start_date->format('Y-m-d');
        // echo $edate.' and '.$sdate;exit();
        $interval = 60;
        return view('administrator.resource.last_2_months_blocked_applicants', compact('interval'));
    }

    public function getLast2MonthsBlockedApplicantAddedAjax()
    {

        $end_date = Carbon::now();
        $edate = $end_date->format('Y-m-d');

       // $start_date = $end_date->subMonths(60);
      //  $sdate = $start_date->format('Y-m-d');
        $result = Applicant::with(['cv_notes' => function($query) {
                        $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                            ->with(['user:id,name'])->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
                    }])
            ->select('applicants.id', 'applicants.updated_at', 'applicants.applicant_added_time', 
					 'applicants.applicant_name', 'applicants.applicant_job_title', 'applicants.job_title_prof', 
					 'applicants.job_category', 'applicants.applicant_postcode', 'applicants.applicant_phone', 
					 'applicants.applicant_homePhone', 'applicants.applicant_source', 'applicants.applicant_notes',
					 'applicants.paid_status', 'applicants.department', 'applicants.sub_department')
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereDate('applicants.updated_at', '<=', $edate)
            ->where("applicants.status", "=", "active")
            ->where("applicants.is_blocked", "=", "1")
            ->where('applicants_pivot_sales.applicant_id', '=', NULL);

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
                if ($status_value == 'open' || $status_value == 'reject'){
                    $postcode .= '<a href="/available-jobs/'.$applicant->id.'">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }
                return strtoupper($postcode);
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
			->addColumn("agent_name", function($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
            ->addColumn('applicant_notes', function($applicant){

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
                   
                
                    
                $content = '';
				
				/*** Export Applicants Modal */
                $content .= '<div id="export_applicant_action" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-sm">';
                $content .= '<div class="modal-content">';

                $content .= '<div class="modal-header">';
                $content .= '<h3 class="modal-title">Export Applicants</h3>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<form action="' . route('export_blocked_applicants') . '" method="POST" id="export_block_applicants" class="form-horizontal">';
                $content .= csrf_field();

                $content .= '<div class="mb-4">';
                $content .= '<div class="input-group">';
                $content .= '<span class="input-group-prepend">';
                $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                $content .= '</span>';
                $content .= '<input type="text" class="form-control pickadate-year" name="start_date" id="start_date" placeholder="Select From Date">';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="mb-4">';
                $content .= '<div class="input-group">';
                $content .= '<span class="input-group-prepend">';
                $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                $content .= '</span>';

                $content .= '<input type="text" class="form-control pickadate-year" name="end_date" id="end_date" placeholder="Select To Date">';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<button type="submit" class="btn bg-teal legitRipple btn-block">Submit</button>';
                $content .= '</form>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
				
				/*** Unblock Applicants Modal */
                 $content .= '<div id="applicant_action" class="modal fade" tabindex="-1">';
                 $content .= '<div class="modal-dialog modal-sm">';
                 $content .= '<div class="modal-content">';
 
                 $content .= '<div class="modal-header">';
                 $content .= '<h3 class="modal-title" >Unblock Applicants</h3>';
                 $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                 $content .= '</div>';
                 $content .= '<div class="modal-body">';
                 $content .= '<div id="applicant_unblock_alert"></div>';
                 $content .= '<form action="' . route('scheduleInterview') . '" method="POST" id="applicant_unblock_form" class="form-horizontal">';
                 $content .= csrf_field();

                 $content .= '<div class="mb-4">';
                 $content .= '<div class="input-group">';
                 $content .= '<span class="input-group-prepend">';
                 $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                 $content .= '</span>';
                 $content .= '<input type="text" class="form-control pickadate-year" name="from_date" id="from_date" placeholder="Select From Date">';
                 $content .= '</div>';
                 $content .= '</div>';
                 $content .= '<div class="mb-4">';
                 $content .= '<div class="input-group">';
                 $content .= '<span class="input-group-prepend">';
                 $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                 $content .= '</span>';
 //                $content .= '<input type="text" class="form-control time_pickerrrr" id="anytime-time'.$applicant->id.'-'.$applicant->sale_id.'" name="schedule_time" placeholder="Select Schedule Time e.g., 00:00">';
                 $content .= '<input type="text" class="form-control pickadate-year" name="to_date" id="to_date" placeholder="Select To Date">';
                 $content .= '</div>';
                 $content .= '</div>';
                 $content .= '<button type="submit" class="btn bg-teal legitRipple btn-block applicant_action_submit" data-app_sale="">Submit</button>';
                 $content .= '</form>';
                 $content .= '</div>';
                 $content .= '</div>';
                 $content .= '</div>';
                 $content .= '</div>';
				if ($status_value == 'open' || $status_value == 'reject'){

                $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant->id.'"
                                 data-controls-modal="#clear_cv'.$applicant->id.'"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"'.$applicant->applicant_notes.'"</a>';
                $content .= '<div id="clear_cv' . $applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Notes</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<form action="' . route('unblock_notes') . '" method="POST" class="form-horizontal">';
                $content .= csrf_field();
                $content .= '<div class="modal-body">';
                $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                $content .= '<div class="form-group row">';
                $content .= '<label class="col-form-label col-sm-3">Details</label>';
                $content .= '<div class="col-sm-9">';
                $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id .'" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                $content .= '</div>';
                $content .= '</div>';
                 

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                   
                    $content .= '<button type="button" class="btn bg-dark legitRipple sent_cv_submit" data-dismiss="modal">Close</button>';
                    
                    $content .= '<button type="submit" value="cv_sent_save" class="btn bg-teal legitRipple sent_cv_submit">Unblock</button>';

                $content .= '</div>';
                $content .= '</form>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
            } else {
                $content .= $applicant->applicant_notes;
                }
               
                return $content;

			})
                
            ->addColumn("history", function ($applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant->id.'"
                                 data-controls-modal="#reject_history'.$applicant->id.'"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History';
                $content .= '<span class="font-weight-semibold">';
                $content .=  utf8_encode($applicant->applicant_name);
                $content .= '</span>';
                $content .= '</h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                return $content;
            })
			->editColumn('applicant_job_title', function ($applicant) {
            $job_title_desc='';
            if($applicant->job_title_prof!=null)
            {
                $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $applicant->job_title_prof)->first();
                            $job_title_desc = $job_prof_res->specialist_prof;
            }
            else
            {
                
                $job_title_desc = $applicant->applicant_job_title;
            }
            return strtoupper($job_title_desc);

     })
            ->addColumn("updated_at",function($applicant){
                $updated_at = new DateTime($applicant->updated_at);
                $date = date_format($updated_at,'d F Y');
                    return $date;
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
			->addColumn('checkbox', function ($applicant) {
                 return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
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

                return $row_class;
            })
            ->rawColumns(['applicant_job_title','history','applicant_notes','updated_at','status','applicant_postcode','checkbox'])
            ->make(true);

    }

    public function getLast2MonthsApplicantAdded($id)
    {
        $interval = 60;
        return view('administrator.resource.resources_added_applicants.all_resources_applicants', compact('interval','id'));
    }

    public function get2MonthsApplicants($id)
    {
		// date_default_timezone_set('Europe/London');

        $current_date = Carbon::now();
        $end_date = $current_date->subMonth(23)->subDays(37);
        $edate = $end_date->format('Y-m-d');

       $result1 = Applicant::with(['cv_notes' => function ($query) {
		   $query->select('status', 'applicant_id', 'sale_id', 'user_id')
				->with(['user:id,name'])
				->latest();
		   }])
		   ->select(
			   'applicants.id',
			   'applicants.updated_at',
			   'applicants.is_no_job',
			   'applicants.applicant_added_time',
			   'applicants.applicant_name',
			   'applicants.applicant_job_title',
			   'applicants.job_title_prof',
			   'applicants.job_category',
			   'applicants.applicant_postcode',
			   'applicants.applicant_phone',
			   'applicants.applicant_homePhone',
			   'applicants.applicant_source',
			   'applicants.applicant_notes',
			   'applicants.paid_status',
			   'applicants.applicant_cv',
			   'applicants.updated_cv',
			   'applicants.lat',
			   'applicants.lng',
			   'applicants.is_job_within_radius',
			   'applicants.department', 'applicants.sub_department',
		   )
		   ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
		   ->whereDate('applicants.updated_at', '<=', $edate)
		   ->where('applicants.status', '=', 'active')
		   ->where('applicants.temp_not_interested', '=', '0')
		   ->whereNull('applicants_pivot_sales.applicant_id')
		   ->where("applicants.is_job_within_radius", "1")
		   ->whereDoesntHave('cv_notes', function ($query) {
                $query->where('status', 'active');
            });

		switch ($id) {
			case "44":
				$result1->where('applicants.job_category', '=', 'nurse');
				break;
			case "45":
				$result1->where('applicants.job_category', '=', 'non-nurse')
					->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
				break;
			case "46":
				$result1->where('applicants.job_category', '=', 'non-nurse')
					->where('applicants.applicant_job_title', '=', 'nonnurse specialist');
				break;
			case "47":
				$result1->where('applicants.job_category', '=', 'chef');
				break;
			case "48":
				$result1->where('applicants.job_category', '=', 'nursery');
				break;
		}

		$result = $result1->orderBy('applicants.updated_at', 'DESC');

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
                if ($status_value == 'open' || $status_value == 'reject'){
                    $postcode .= '<a href="/available-jobs/'.$applicant->id.'">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }
                return $postcode;
            })
			->addColumn("agent_name", function($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
			->addColumn('applicant_notes', function($applicant){
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
                   
                $content = '';
                if ($status_value == 'open' || $status_value == 'reject'){

                $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant->id.'"
                                 data-controls-modal="#clear_cv'.$applicant->id.'"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"'.$applicant->applicant_notes.'"</a>';
                $content .= '<div id="clear_cv' . $applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Notes</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<form action="' . route('block_or_casual_notes') . '" method="POST" id="notes_form' . $applicant->id . '" class="form-horizontal">';
                $content .= csrf_field();
                $content .= '<div class="modal-body">';
                $content .='<div id="notes_alert' . $applicant->id . '"></div>';

                $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                $content .= '<div class="form-group row">';
                $content .= '<label class="col-form-label col-sm-3">Details</label>';
                $content .= '<div class="col-sm-9">';
                $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="2_months_applicants">';
                $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id .'" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                $content .= '</div>';
                $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id .'">';
                    $content .= '<option value="0">Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
					$content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
                    $content .= '</select>';
                    $content .= '</div>';
                    $content .= '</div>';

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                   
                    $content .= '<button type="button" class="btn bg-dark legitRipple sent_cv_submit" data-dismiss="modal">Close</button>';
                    
                    $content .= '<button type="submit" data-note_key="' . $applicant->id . '" value="cv_sent_save" class="btn bg-teal legitRipple sent_cv_submit notes_form_submit">Save</button>';

                $content .= '</div>';
                $content .= '</form>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
            } else {
                $content .= $applicant->applicant_notes;
                }
               
                return $content;

            })
            ->addColumn("history", function ($applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant->id.'"
                                 data-controls-modal="#reject_history'.$applicant->id.'"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History';
                $content .= '<span class="font-weight-semibold">';
                $content .=  utf8_encode($applicant->applicant_name);
                $content .= '</span>';
                $content .= '</h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                return $content;
            })
            ->addColumn("updated_at",function($applicant){
                $updated_at = new DateTime($applicant->updated_at);
                $date = date_format($updated_at,'d F Y');
                    return Carbon::parse($applicant->updated_at)->toFormattedDateString();
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
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';
                return $download;
            })
			->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';

            })
			->editColumn('applicant_job_title', function ($applicant) {
					$job_title_desc='';
					if($applicant->job_title_prof!=null)
					{
						$job_prof_res = Specialist_job_titles::select('id','specialist_prof')
							->where('id', $applicant->job_title_prof)->first();

						$job_title_desc = $job_prof_res->specialist_prof;
					}
					else
					{
						$job_title_desc = $applicant->applicant_job_title;
					}
					return strtoupper($job_title_desc);

			 })

			->addColumn('upload', function ($applicant) {
				return
				'<a href="#"
				data-controls-modal="#import_applicant_cv" class="import_cv"
				data-backdrop="static"
				data-keyboard="false" data-toggle="modal" data-id="'.$applicant->id.'"
				data-target="#import_applicant_cv">
				 <i class="fas fa-file-upload text-teal-400"></i>
				 &nbsp;</a>';
			})
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
				} elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
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
                return $row_class;
            })
            ->rawColumns(['applicant_job_title','history','download','updated_cv','upload','applicant_notes','updated_at','status','applicant_postcode'])
            ->make(true);
		
	 
    }
	
	public function getlast2MonthsAppNotInterested($id)
    {
		 // date_default_timezone_set('Europe/London');

        $current_date = Carbon::now();
        $end_date = $current_date->subMonth(23)->subDays(37);
        $edate = $end_date->format('Y-m-d');

        $result1 = Applicant::with(['cv_notes' => function($query) {
                        $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                            ->with(['user:id,name'])->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
                    }])
            ->leftJoin('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
            ->leftJoin('sales', 'sales.id', '=', 'applicants_pivot_sales.sales_id')
            ->leftJoin('notes_for_range_applicants', 'applicants_pivot_sales.id', '=', 'notes_for_range_applicants.applicants_pivot_sales_id')
            ->leftJoin('offices', 'offices.id', '=', 'sales.head_office')
            ->leftJoin('units', 'units.id', '=', 'sales.head_office_unit')
            ->select(
                'applicants.id',
                'applicants.updated_at',
                'applicants.temp_not_interested',
                'applicants.applicant_added_time',
                'applicants.is_no_job',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants_pivot_sales.sales_id as pivot_sale_id',
                'applicants_pivot_sales.id as pivot_id',
				'applicants.is_job_within_radius',
				'applicants.department', 'applicants.sub_department',
            )
             ->where(function ($query) {
                $query->where("applicants.temp_not_interested", "1")
                      ->orWhereNotNull('applicants_pivot_sales.applicant_id');
            })
            ->where("applicants.is_no_job", "=", "0")
            ->where("applicants.status", "active")
			->where("applicants.is_job_within_radius", "1")
            ->whereDate('applicants.updated_at', '<=', $edate)
			->whereDoesntHave('cv_notes', function ($query) {
                $query->where('status', 'active');
            });
        
            switch ($id) {
				case "44":
					$result1->where('applicants.job_category', '=', 'nurse');
					break;
				case "45":
					$result1->where('applicants.job_category', '=', 'non-nurse')
						->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
					break;
				case "46":
					$result1->where('applicants.job_category', '=', 'non-nurse')
						->where('applicants.applicant_job_title', '=', 'nonnurse specialist');
					break;
				case "47":
					$result1->where('applicants.job_category', '=', 'chef');
					break;
				case "48":
					$result1->where('applicants.job_category', '=', 'nursery');
					break;
        	}
    
        $result = $result1->orderBy('applicants.updated_at', 'DESC');

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
                return $applicant->applicant_postcode;
            })
			->addColumn("agent_name", function($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
			->addColumn('applicant_notes', function($applicant){

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
                    
                $content = '';
                if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant->id.'"
                                    data-controls-modal="#clear_cv'.$applicant->id.'"
                                    data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                    data-target="#clear_cv' . $applicant->id . '">"'.$applicant->applicant_notes.'"</a>';
                    $content .= '<div id="clear_cv' . $applicant->id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('block_or_casual_notes') . '" method="POST" id="notes_form' . $applicant->id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .='<div id="notes_alert' . $applicant->id . '"></div>';

                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="2_months_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id .'" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id .'">';
                    $content .= '<option value="0">Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
                    $content .= '</select>';
                    $content .= '</div>';
                    $content .= '</div>';

                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    
                    $content .= '<button type="button" class="btn bg-dark legitRipple sent_cv_submit" data-dismiss="modal">Close</button>';
                    
                    $content .= '<button type="submit" data-note_key="' . $applicant->id . '" value="cv_sent_save" class="btn bg-teal legitRipple sent_cv_submit notes_form_submit">Save</button>';

                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                } else {
                    $content .= $applicant->applicant_notes;
                }
               
                return $content;

            })
            ->addColumn("history", function ($applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant->id.'"
                                 data-controls-modal="#reject_history'.$applicant->id.'"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History';
                $content .= '<span class="font-weight-semibold">';
                $content .=  utf8_encode($applicant->applicant_name);
                $content .= '</span>';
                $content .= '</h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                return $content;
            })
            ->addColumn("updated_at",function($applicant){
                $updated_at = new DateTime($applicant->updated_at);
                $date = date_format($updated_at,'d F Y');
                     return Carbon::parse($applicant->updated_at)->toFormattedDateString();
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
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';
                return $download;
            })
			->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';
            })
			->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc='';
                if($applicant->job_title_prof!=null)
                {
                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $applicant->job_title_prof)->first();
					$job_title_desc = $job_prof_res->specialist_prof;
                }
                else
                {
                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
			->addColumn('upload', function ($applicant) {
                return
                '<a href="#"
                data-controls-modal="#import_applicant_cv" class="import_cv"
                data-backdrop="static"
                data-keyboard="false" data-toggle="modal" data-id="'.$applicant->id.'"
                data-target="#import_applicant_cv">
                <i class="fas fa-file-upload text-teal-400"></i>
                &nbsp;</a>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
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
                return $row_class;
            })
            ->rawColumns(['applicant_job_title','history','download','updated_cv','upload','applicant_notes','updated_at','status','applicant_postcode'])
            ->make(true);
    }

    public function getlast2MonthsAppBlocked($id)
    {
		       // date_default_timezone_set('Europe/London');
        $current_date = Carbon::now();
        $end_date = $current_date->subMonth(23)->subDays(37);
        $edate = $end_date->format('Y-m-d');

        $result1 = Applicant::with(['cv_notes' => function($query) {
                        $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                            ->with(['user:id,name'])->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
                    }])
            ->leftJoin('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
            ->leftJoin('sales', 'sales.id', '=', 'applicants_pivot_sales.sales_id')
            ->leftJoin('notes_for_range_applicants', 'applicants_pivot_sales.id', '=', 'notes_for_range_applicants.applicants_pivot_sales_id')
            ->leftJoin('offices', 'offices.id', '=', 'sales.head_office')
            ->leftJoin('units', 'units.id', '=', 'sales.head_office_unit')
            ->select(
                'applicants.id',
                'applicants.updated_at',
                'applicants.temp_not_interested',
                'applicants.applicant_added_time',
                'applicants.is_no_job',
                'applicants.applicant_name',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
                'applicants.applicant_postcode',
                'applicants.applicant_phone',
                'applicants.applicant_homePhone',
                'applicants.applicant_source',
                'applicants.applicant_email',
                'applicants.applicant_notes',
                'applicants.paid_status',
                'applicants.applicant_cv',
                'applicants.updated_cv',
                'applicants_pivot_sales.sales_id as pivot_sale_id',
                'applicants_pivot_sales.id as pivot_id',
				'applicants.is_job_within_radius',
				'applicants.department', 'applicants.sub_department',
            )
            ->where("applicants.temp_not_interested", "0")
            ->where("applicants.is_no_job", "=", "0")
            ->where("applicants.is_blocked", "=", "1")
            ->where("applicants.status", "active")
            ->whereDate('applicants.updated_at', '<=', $edate)
            ->whereDoesntHave('cv_notes', function ($query) {
                $query->where('status', 'active');
            });
        
            switch ($id) {
				case "44":
					$result1->where('applicants.job_category', '=', 'nurse');
					break;
				case "45":
					$result1->where('applicants.job_category', '=', 'non-nurse')
						->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
					break;
				case "46":
					$result1->where('applicants.job_category', '=', 'non-nurse')
						->where('applicants.applicant_job_title', '=', 'nonnurse specialist');
					break;
				case "47":
					$result1->where('applicants.job_category', '=', 'chef');
					break;
				case "48":
					$result1->where('applicants.job_category', '=', 'nursery');
					break;
			}
    
         $result = $result1->orderBy('applicants.updated_at', 'DESC');

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
                return $applicant->applicant_postcode;
            })
			->addColumn("agent_name", function($applicant) {
                // Since we're fetching only the latest cv_note, this should be straightforward
                if ($applicant->cv_notes->isNotEmpty()) {
                    return $applicant->cv_notes->first()->user->name ?? null;
                }
                return '-';
            })
			->addColumn('applicant_notes', function($applicant){

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
                    
                $content = '';
                if ($status_value == 'open' || $status_value == 'reject'){

                    $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant->id.'"
                                    data-controls-modal="#clear_cv'.$applicant->id.'"
                                    data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                    data-target="#clear_cv' . $applicant->id . '">"'.$applicant->applicant_notes.'"</a>';
                    $content .= '<div id="clear_cv' . $applicant->id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('block_or_casual_notes') . '" method="POST" id="notes_form' . $applicant->id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .='<div id="notes_alert' . $applicant->id . '"></div>';

                    $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="applicant_page' . $applicant->id . '" value="2_months_applicants">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id .'" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<select name="reject_reason" class="form-control crm_select_reason" id="reason' . $applicant->id .'">';
                    $content .= '<option value="0">Select Reason</option>';
                    $content .= '<option value="1">Casual Notes</option>';
                    $content .= '<option value="2">Block Applicant Notes</option>';
                    $content .= '<option value="3">Temporary Not Interested Applicants Notes</option>';
                    $content .= '</select>';
                    $content .= '</div>';
                    $content .= '</div>';

                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    
                    $content .= '<button type="button" class="btn bg-dark legitRipple sent_cv_submit" data-dismiss="modal">Close</button>';
                    
                    $content .= '<button type="submit" data-note_key="' . $applicant->id . '" value="cv_sent_save" class="btn bg-teal legitRipple sent_cv_submit notes_form_submit">Save</button>';

                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                } else {
                    $content .= $applicant->applicant_notes;
                }
               
                return $content;

            })
            ->addColumn("history", function ($applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant->id.'"
                                 data-controls-modal="#reject_history'.$applicant->id.'"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History';
                $content .= '<span class="font-weight-semibold">';
                $content .=  utf8_encode($applicant->applicant_name);
                $content .= '</span>';
                $content .= '</h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                return $content;
            })
            ->addColumn("updated_at",function($applicant){
                $updated_at = new DateTime($applicant->updated_at);
                $date = date_format($updated_at,'d F Y');
                     return Carbon::parse($applicant->updated_at)->toFormattedDateString();
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
                $filePath = $applicant->applicant_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->applicant_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->applicant_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadApplicantCv', $applicant->id);

                $download = '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';
                return $download;
            })
			->addColumn('updated_cv', function ($applicant) {
                $filePath = $applicant->updated_cv;

                // Check if the file exists
                $disabled = (!file_exists($filePath) || $applicant->updated_cv == null ) ? 'disabled' : '';
                $disabled_color = (!file_exists($filePath) || $applicant->updated_cv == null) ? 'text-grey-400' : 'text-teal-400';
                $href = ($disabled == 'disabled') ? 'javascript:void(0);' : route('downloadUpdatedApplicantCv', $applicant->id);
                return
                    '<a class="download-link ' . $disabled . '" href="' . $href . '">
                       <i class="fas fa-file-download '. $disabled_color .'"></i>
                    </a>';

            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
			->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc='';
                if($applicant->job_title_prof!=null)
                {
                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $applicant->job_title_prof)->first();
					$job_title_desc = $job_prof_res->specialist_prof;
                }
                else
                {
                    
                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);

            })

			->addColumn('upload', function ($applicant) {
                return
                '<a href="#"
                data-controls-modal="#import_applicant_cv" class="import_cv"
                data-backdrop="static"
                data-keyboard="false" data-toggle="modal" data-id="'.$applicant->id.'"
                data-target="#import_applicant_cv">
                <i class="fas fa-file-upload text-teal-400"></i>
                &nbsp;</a>';
            })
			 ->addColumn('checkbox', function ($applicant) {
                return '<input type="checkbox" name="applicant_checkbox[]" class="applicant_checkbox" value="' . $applicant->id . '"/>';
            })
            ->setRowClass(function ($applicant) {
                $row_class = '';
                if ($applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } elseif ($applicant->is_no_job == '1') {
                    $row_class = 'class_noJob';
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
                return $row_class;
            })
            ->rawColumns(['applicant_job_title','history','download','updated_cv','upload','applicant_notes','checkbox','updated_at','status','applicant_postcode'])
            ->make(true);
    }
	
    // mycode
    public function getAllCrmRejectedApplicantCv()
    {
        // echo "all rejected applicants.";exit();
        return view('administrator.resource.all_rejected_applicants');
    } 
	public function getTempNotInterestedApplicants(){
        // echo 'temp not';exit();
        $interval = 60;
        return view('administrator.resource.temp_not_interested', compact('interval'));
    }
	
	public function get_temp_not_interested_applicants_ajax()
    {
        $end_date = Carbon::now();
        //$edate21 = $end_date->subDays(31); // 9 + 21 + excluding last_day . 00:00:00
        $edate = $end_date->format('Y-m-d');

        $start_date = $end_date->subMonths(60);
        $sdate = $start_date->format('Y-m-d');
        $result = Applicant::with('cv_notes')
            ->select('applicants.id', 'applicants.updated_at', 'applicants.applicant_added_time', 'applicants.applicant_name', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_postcode', 'applicants.applicant_phone','applicants.applicant_homePhone','applicants.applicant_source','applicants.applicant_notes','applicants.paid_status')
            ->leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
//            ->whereBetween('applicants.updated_at', [$sdate, $edate])
            ->whereDate('applicants.updated_at', '<=', $edate)
            ->where("applicants.status", "=", "active")
            ->where("applicants.temp_not_interested", "=", "1")
//            ->where("applicants.is_in_nurse_home", "=", "no")
            // ->where("applicants.job_category", "=", "nurse")
            ->where('applicants_pivot_sales.applicant_id', '=', NULL);

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
                /*** logic before open-appllicant-cv feature
                foreach ($applicant->cv_notes as $key => $value) {
                    if ($value->status == 'active') {
                        $status_value = 'sent';
                        break;
                    } elseif ($value->status == 'disable') {
                        $status_value = 'reject';
                    } elseif ($value->status == 'paid') {
                        $status_value = 'paid';
                        break;
                    }
                }
                */
                if ($status_value == 'open' || $status_value == 'reject'){
                    $postcode .= '<a href="/available-jobs/'.$applicant->id.'">';
                    $postcode .= $applicant->applicant_postcode;
                    $postcode .= '</a>';
                } else {
                    $postcode .= $applicant->applicant_postcode;
                }
                return $postcode;
            })
            ->addColumn('applicant_notes', function($applicant){

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
                   
                
                    
                $content = '';


                /*** Export Applicants Modal */
                $content .= '<div id="export_temp_not_interest_applicants" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-sm">';
                $content .= '<div class="modal-content">';

                $content .= '<div class="modal-header">';
                $content .= '<h3 class="modal-title">Export Applicants</h3>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<form action="' . route('export_temp_not_interested_applicants') . '" method="POST" id="export_block_applicants" class="form-horizontal">';
                $content .= csrf_field();
                // $content .= '<input type="hidden" name="applicant_id" value="' . $applicant->id . '">';
                // $content .= '<input type="hidden" name="sale_id" value="' . $applicant->sale_id . '">';
                $content .= '<div class="mb-4">';
                $content .= '<div class="input-group">';
                $content .= '<span class="input-group-prepend">';
                $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                $content .= '</span>';
                $content .= '<input type="text" class="form-control pickadate-year" name="start_date" id="start_date" placeholder="Select From Date">';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="mb-4">';
                $content .= '<div class="input-group">';
                $content .= '<span class="input-group-prepend">';
                $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                $content .= '</span>';
//                $content .= '<input type="text" class="form-control time_pickerrrr" id="anytime-time'.$applicant->id.'-'.$applicant->sale_id.'" name="schedule_time" placeholder="Select Schedule Time e.g., 00:00">';
                $content .= '<input type="text" class="form-control pickadate-year" name="end_date" id="end_date" placeholder="Select To Date">';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<button type="submit" class="btn bg-teal legitRipple btn-block">Submit</button>';
                $content .= '</form>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';



                 /*** Unblock Applicants Modal */
                 $content .= '<div id="applicant_action" class="modal fade" tabindex="-1">';
                 $content .= '<div class="modal-dialog modal-sm">';
                 $content .= '<div class="modal-content">';
 
                 $content .= '<div class="modal-header">';
                 $content .= '<h3 class="modal-title" >Unblock Applicants</h3>';
                 $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                 $content .= '</div>';
                 $content .= '<div class="modal-body">';
                 $content .= '<div id="applicant_unblock_alert"></div>';
                 $content .= '<form action="' . route('scheduleInterview') . '" method="POST" id="applicant_unblock_form" class="form-horizontal">';
                 $content .= csrf_field();
                 // $content .= '<input type="hidden" name="applicant_id" value="' . $applicant->id . '">';
                 // $content .= '<input type="hidden" name="sale_id" value="' . $applicant->sale_id . '">';
                 $content .= '<div class="mb-4">';
                 $content .= '<div class="input-group">';
                 $content .= '<span class="input-group-prepend">';
                 $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                 $content .= '</span>';
                 $content .= '<input type="text" class="form-control pickadate-year" name="from_date" id="from_date" placeholder="Select From Date">';
                 $content .= '</div>';
                 $content .= '</div>';
                 $content .= '<div class="mb-4">';
                 $content .= '<div class="input-group">';
                 $content .= '<span class="input-group-prepend">';
                 $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                 $content .= '</span>';
 //                $content .= '<input type="text" class="form-control time_pickerrrr" id="anytime-time'.$applicant->id.'-'.$applicant->sale_id.'" name="schedule_time" placeholder="Select Schedule Time e.g., 00:00">';
                 $content .= '<input type="text" class="form-control pickadate-year" name="to_date" id="to_date" placeholder="Select To Date">';
                 $content .= '</div>';
                 $content .= '</div>';
                 $content .= '<button type="submit" class="btn bg-teal legitRipple btn-block applicant_action_submit" data-app_sale="">Submit</button>';
                 $content .= '</form>';
                 $content .= '</div>';
                 $content .= '</div>';
                 $content .= '</div>';
                 $content .= '</div>';



                if ($status_value == 'open' || $status_value == 'reject'){

                $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant->id.'"
                                 data-controls-modal="#clear_cv'.$applicant->id.'"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#clear_cv' . $applicant->id . '">"'.$applicant->applicant_notes.'"</a>';
                $content .= '<div id="clear_cv' . $applicant->id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Notes</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<form action="' . route('interested_notes') . '" method="POST" class="form-horizontal">';
                $content .= csrf_field();
                $content .= '<div class="modal-body">';
                $content .= '<div id="sent_cv_alert' . $applicant->id . '"></div>';
                $content .= '<div class="form-group row">';
                $content .= '<label class="col-form-label col-sm-3">Details</label>';
                $content .= '<div class="col-sm-9">';
                $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id .'" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                $content .= '</div>';
                $content .= '</div>';
                 

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                   
                    $content .= '<button type="button" class="btn bg-dark legitRipple sent_cv_submit" data-dismiss="modal">Close</button>';
                    
                    $content .= '<button type="submit" value="cv_sent_save" class="btn bg-teal legitRipple sent_cv_submit">Interested</button>';

                $content .= '</div>';
                $content .= '</form>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
            } else {
                $content .= $applicant->applicant_notes;
                }
               
                return $content;

            })
            ->addColumn("history", function ($applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$applicant->id.'"
                                 data-controls-modal="#reject_history'.$applicant->id.'"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History';
                $content .= '<span class="font-weight-semibold">';
                $content .=  utf8_encode($applicant->applicant_name);
                $content .= '</span>';
                $content .= '</h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                return $content;
            })
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
            ->addColumn("updated_at",function($applicant){
                $updated_at = new DateTime($applicant->updated_at);
                $date = date_format($updated_at,'d F Y');
                    return $date;
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
                /*** logic before open-applicant-cv feature
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
                /*** logic before open-applicant feature
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
            ->rawColumns(['applicant_job_title','history','applicant_notes','updated_at','status','applicant_postcode'])
            ->make(true);
    }
	//code
    public function exportAllCrmRejectedApplicantCv(Request $request)
    {
        
        // $end_date = Carbon::now();
        // $edate7 = $end_date->subDays(10);
        // $edate = $end_date->format('Y-m-d') . " 23:59:59";
        // $start_date = $end_date->subDays(42);
        // $sdate = $start_date->format('Y-m-d') . " 00:00:00";
        // $sdate = '2020-01-01 00:00:00';



        $start_date = $request->input('start_date');
        $sdate = Carbon::parse($start_date)->format('Y-m-d'). " 00:00:01";

        $end_date = $request->input('end_date');
        $edate = Carbon::parse($end_date)->format('Y-m-d'). " 23:59:59";   
        // echo $sdate.' and'.$edate;exit(); 
        $job_category='nurse';
        return Excel::download(new AllRejectedApplicantsExport($sdate,$edate,$job_category), 'applicants.csv');


    }
    // my code
    public function getallCrmRejectedApplicantCvAjax()
    {
        $end_date = Carbon::now();
        // $edate7 = $end_date->subDays(10);
        $edate = $end_date->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subDays(42);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";
        $crm_rejected_applicants = Applicant::with('cv_notes')
        ->join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
        ->join('history', function ($join) {
            $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
            $join->on('crm_notes.sales_id', '=', 'history.sale_id');
        })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
            'applicants.id', 'applicants.applicant_name', 'applicants.applicant_job_title', 'applicants.job_title_prof',
 'applicants.job_category',
            'applicants.applicant_postcode', 'applicants.applicant_phone', 'applicants.paid_status',
            'applicants.applicant_homePhone','applicants.applicant_source',
            DB::raw('
        CASE 
            WHEN history.sub_stage="crm_reject"
            THEN "Rejected CV" 
            WHEN history.sub_stage="crm_request_reject"
            THEN "Rejected By Request"
            WHEN history.sub_stage="crm_declined"
            THEN "Declined"
            WHEN history.sub_stage="crm_interview_not_attended"
            THEN "Not Attended"
            WHEN history.sub_stage="crm_start_date_hold" OR history.sub_stage = "crm_start_date_hold_save"
            THEN "Start Date Hold"
            WHEN history.sub_stage="crm_dispute"
            THEN "Dispute" 
            END AS sub_stage'))->whereIn("history.sub_stage", ["crm_dispute","crm_interview_not_attended","crm_declined","crm_request_reject","crm_reject","crm_start_date_hold", "crm_start_date_hold_save"])
            ->whereIn("crm_notes.moved_tab_to", ["dispute","interview_not_attended","declined","request_reject","cv_sent_reject","start_date_hold", "start_date_hold_save"])
            //->whereBetween('crm_notes.updated_at', [$sdate, $edate])
        ->where([
            "applicants.status" => "active", "history.status" => "active"
        ])->orderBy("crm_notes.id","DESC")->groupBy('applicants.applicant_phone');

        return datatables()->of($crm_rejected_applicants)
            ->addColumn("applicant_postcode",function($crm_rejected_applicant) {
                if ($crm_rejected_applicant->paid_status == 'close') {
                    return $crm_rejected_applicant->applicant_postcode;
                } 
                else {
                    foreach ($crm_rejected_applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            return $crm_rejected_applicant->applicant_postcode;
                        }
                    }
                    return '<a href="/available-jobs/'.$crm_rejected_applicant->id.'">'.$crm_rejected_applicant->applicant_postcode.'</a>';
                }
            })
			->editColumn('applicant_job_title', function ($crm_rejected_applicant) {
                $job_title_desc='';
                if($crm_rejected_applicant->job_title_prof!=null)
                {
                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $crm_rejected_applicant->job_title_prof)->first();
                                $job_title_desc = $crm_rejected_applicant->applicant_job_title.' ('.$job_prof_res->specialist_prof.')';
                }
                else
                {
                    
                    $job_title_desc = $crm_rejected_applicant->applicant_job_title;
                }
                return $job_title_desc;
    
         })
            ->addColumn('history', function ($crm_rejected_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$crm_rejected_applicant->id.'"; 
                                 data-controls-modal="#reject_history'.$crm_rejected_applicant->id.'" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_rejected_applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$crm_rejected_applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">'.$crm_rejected_applicant->applicant_name.'</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$crm_rejected_applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

                /*** Details are fetched via ajax request */

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
				
				/*** Export Applicants Modal */
                $content .= '<div id="export_all_rejected_applicant_action" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-sm">';
                $content .= '<div class="modal-content">';

                $content .= '<div class="modal-header">';
                $content .= '<h3 class="modal-title">Export Applicants</h3>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<form action="' . route('export_all_rejected_applicants') . '" method="POST" id="export_block_applicants" class="form-horizontal">';
                $content .= csrf_field();
                // $content .= '<input type="hidden" name="applicant_id" value="' . $applicant->id . '">';
                // $content .= '<input type="hidden" name="sale_id" value="' . $applicant->sale_id . '">';
                $content .= '<div class="mb-4">';
                $content .= '<div class="input-group">';
                $content .= '<span class="input-group-prepend">';
                $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                $content .= '</span>';
                $content .= '<input type="text" class="form-control pickadate-year" name="start_date" id="start_date" placeholder="Select From Date">';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="mb-4">';
                $content .= '<div class="input-group">';
                $content .= '<span class="input-group-prepend">';
                $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                $content .= '</span>';
//                $content .= '<input type="text" class="form-control time_pickerrrr" id="anytime-time'.$applicant->id.'-'.$applicant->sale_id.'" name="schedule_time" placeholder="Select Schedule Time e.g., 00:00">';
                $content .= '<input type="text" class="form-control pickadate-year" name="end_date" id="end_date" placeholder="Select To Date">';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<button type="submit" class="btn bg-teal legitRipple btn-block">Submit</button>';
                $content .= '</form>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
				
                return $content;

            })
            ->setRowClass(function ($crm_rejected_applicant) {
                $row_class = '';
                if ($crm_rejected_applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } else { /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
                    foreach ($crm_rejected_applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $row_class = 'class_success'; // status: sent
                            break;
                        }
                    }
                }
                return $row_class;
            })
            ->rawColumns(['applicant_job_title','applicant_postcode', 'history'])
            ->make(true);
    }
	
		
	
//my code 

    public function storeUnblockNotes(Request $request)
    {
        $applicant_id = $request->Input('applicant_hidden_id');
        $applicant_notes = $request->Input('details');
        // $notes_reason = $request->Input('reject_reason');
        // $updated_at = Carbon::now();

            Applicant::where('id', $applicant_id)
            ->update(['is_blocked' => '0','applicant_notes' => $applicant_notes]);
        // echo $applicant_id.' notes: '.$applicant_notes.' reason : '.$notes_reason.' date: '.$end_date;exit();
        // return redirect()->route('getlast2MonthsApp');[+]
        $interval = 60;
        return view('administrator.resource.last_2_months_blocked_applicants', compact('interval'));
        $interval = 60;
        return view('administrator.resource.last_2_months_blocked_applicants', compact('interval'));
    }
	public function store_interested_notes(Request $request)
    {
        $applicant_id = $request->Input('applicant_hidden_id');
        $applicant_notes = $request->Input('details');
        // $notes_reason = $request->Input('reject_reason');
        // $updated_at = Carbon::now();

            Applicant::where('id', $applicant_id)
            ->update(['temp_not_interested' => '0','applicant_notes' => $applicant_notes]);
        // echo $applicant_id.' notes: '.$applicant_notes.' reason : '.$notes_reason.' date: '.$end_date;exit();
        // return redirect()->route('getlast2MonthsApp');[+]
        $interval = 60;
        return view('administrator.resource.temp_not_interested', compact('interval'));
    }
	

    
    public function getCrmRejectedApplicantCv()
    {
        return view('administrator.resource.rejected_applicants');
    }
	
public function Export_CrmRejectedApplicantCv()
    {
        // echo 'herer is crm export';exit();
        $crm_rejected_applicants = Applicant::with('cv_notes')
        ->join('crm_rejected_cv', 'applicants.id', '=', 'crm_rejected_cv.applicant_id')
        ->join('history', function ($join) {
            $join->on('crm_rejected_cv.applicant_id', '=', 'history.applicant_id');
            $join->on('crm_rejected_cv.sale_id', '=', 'history.sale_id');
        })->select('applicants.applicant_phone', 'applicants.applicant_name', 'applicants.applicant_homePhone','applicants.applicant_job_title',
        'applicants.applicant_postcode','applicants.applicant_source','applicants.applicant_notes')
        ->where([
            "applicants.status" => "active",
            "history.sub_stage" => "crm_reject", "history.status" => "active","is_blocked" => "0"
        ])->orderBy("crm_rejected_cv.id","DESC")->get();
        return Excel::download(new Applicants_nureses_7_days_export($crm_rejected_applicants), 'applicants.csv');
    }
    public function getCrmRejectedApplicantCvAjax()
    {
        $crm_rejected_applicants = Applicant::with('cv_notes')
            ->join('crm_rejected_cv', 'applicants.id', '=', 'crm_rejected_cv.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_rejected_cv.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_rejected_cv.sale_id', '=', 'history.sale_id');
            })->select('crm_rejected_cv.crm_rejected_cv_note', 'crm_rejected_cv.crm_rejected_cv_date', 'crm_rejected_cv.crm_rejected_cv_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.paid_status',
                'applicants.applicant_postcode', 'applicants.applicant_phone',
                'applicants.applicant_homePhone','applicants.applicant_source')
            ->where([
                "applicants.status" => "active",
                "history.sub_stage" => "crm_reject", "history.status" => "active"
            ])->orderBy("crm_rejected_cv.id","DESC");
// return "here u go";exit();
        return datatables()->of($crm_rejected_applicants)
            ->addColumn("applicant_postcode",function($crm_rejected_applicant) {
                if ($crm_rejected_applicant->paid_status == 'close') {
                    return $crm_rejected_applicant->applicant_postcode;
                } 
                else {
                    foreach ($crm_rejected_applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            return $crm_rejected_applicant->applicant_postcode;
                        }
                    }
                    return '<a href="/available-jobs/'.$crm_rejected_applicant->id.'">'.$crm_rejected_applicant->applicant_postcode.'</a>';
                }
            })
			->editColumn('applicant_job_title', function ($crm_rejected_applicant) {
                $job_title_desc='';
                if($crm_rejected_applicant->job_title_prof!=null)
                {
                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $crm_rejected_applicant->job_title_prof)->first();
                                $job_title_desc = $crm_rejected_applicant->applicant_job_title.' ('.$job_prof_res->specialist_prof.')';
                }
                else
                {
                    
                    $job_title_desc = $crm_rejected_applicant->applicant_job_title;
                }
                return $job_title_desc;
    
         })
            ->addColumn('history', function ($crm_rejected_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$crm_rejected_applicant->id.'"; 
                                 data-controls-modal="#reject_history'.$crm_rejected_applicant->id.'" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_rejected_applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$crm_rejected_applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">'.$crm_rejected_applicant->applicant_name.'</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$crm_rejected_applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

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
            ->setRowClass(function ($crm_rejected_applicant) {
                $row_class = '';
                if ($crm_rejected_applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } else { /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
                    foreach ($crm_rejected_applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $row_class = 'class_success'; // status: sent
                            break;
                        }
                    }
                }
                return $row_class;
            })
            ->rawColumns(['applicant_job_title','applicant_postcode', 'history'])
            ->make(true);
    }

    public function getCrmRequestRejectedApplicantCv()
    {
        return view('administrator.resource.crm_rejected_request_applicants');
    }
	public function exportCrmRequestRejectedApplicantCv()
    {
        $crm_request_rejected_applicants = Applicant::with('cv_notes')
            ->join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('applicants.applicant_phone', 'applicants.applicant_name', 'applicants.applicant_homePhone','applicants.applicant_job_title',
            'applicants.applicant_postcode','applicants.applicant_source','applicants.applicant_notes')
            ->where([
                "applicants.status" => "active",
                "crm_notes.moved_tab_to" => "request_reject",
                "history.sub_stage" => "crm_request_reject", "history.status" => "active"
            ])->orderBy("crm_notes.id","DESC")->get();

        return Excel::download(new Applicants_nureses_7_days_export($crm_request_rejected_applicants), 'applicants.csv');

    }

    public function getCrmRequestRejectedApplicantCvAjax()
    {
        $crm_request_rejected_applicants = Applicant::with('cv_notes')
            ->join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_job_title','applicants.job_title_prof',
 'applicants.job_category',
                'applicants.applicant_postcode', 'applicants.applicant_phone', 'applicants.paid_status',
                'applicants.applicant_homePhone','applicants.applicant_source')
            ->where([
                "applicants.status" => "active",
                "crm_notes.moved_tab_to" => "request_reject",
                "history.sub_stage" => "crm_request_reject", "history.status" => "active"
            ])->orderBy("crm_notes.id","DESC");

        return datatables()->of($crm_request_rejected_applicants)
            ->addColumn("applicant_postcode",function($crm_request_rejected_applicant) {
                if ($crm_request_rejected_applicant->paid_status == 'close') {
                    return $crm_request_rejected_applicant->applicant_postcode;
                } else {
                    foreach ($crm_request_rejected_applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            return $crm_request_rejected_applicant->applicant_postcode;
                        }
                    }
                    return '<a href="/available-jobs/' . $crm_request_rejected_applicant->id . '">' . $crm_request_rejected_applicant->applicant_postcode . '</a>';
                }
            })
			->editColumn('applicant_job_title', function ($crm_request_rejected_applicant) {
                $job_title_desc='';
                if($crm_request_rejected_applicant->job_title_prof!=null)
                {
                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $crm_request_rejected_applicant->job_title_prof)->first();
                                $job_title_desc = $crm_request_rejected_applicant->applicant_job_title.' ('.$job_prof_res->specialist_prof.')';
                }
                else
                {
                    
                    $job_title_desc = $crm_request_rejected_applicant->applicant_job_title;
                }
                return $job_title_desc;
    
         })
            ->addColumn('history', function ($crm_request_rejected_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$crm_request_rejected_applicant->id.'"; 
                                 data-controls-modal="#reject_history'.$crm_request_rejected_applicant->id.'" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_request_rejected_applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$crm_request_rejected_applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">'.$crm_request_rejected_applicant->applicant_name.'</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$crm_request_rejected_applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

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
            ->setRowClass(function ($crm_request_rejected_applicants) {
                $row_class = '';
                if ($crm_request_rejected_applicants->paid_status == 'close') {
                    $row_class = 'class_dark';
                } else { /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
                    foreach ($crm_request_rejected_applicants->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $row_class = 'class_success'; // status: sent
                            break;
                        }
                    }
                }
                return $row_class;
            })
            ->rawColumns(['applicant_job_title','applicant_postcode', 'history'])
            ->make(true);
    }

    public function potentialCallBackApplicants()
    {
        return view('administrator.resource.callback_applicants');
    }
	
public function exportPotentialCallBackApplicants()
    {
        $auth_user = Auth::user();
        $callBackApplicants = Applicant::with('cv_notes')
            ->join('applicant_notes', 'applicant_notes.applicant_id', '=', 'applicants.id')
            ->select('applicants.applicant_phone', 'applicants.applicant_name', 'applicants.applicant_homePhone','applicants.applicant_job_title',
            'applicants.applicant_postcode','applicants.applicant_source','applicants.applicant_notes')
            ->where([
                'applicants.status' => 'active', "applicants.is_callback_enable" => "yes",
                'applicant_notes.moved_tab_to' => 'callback','applicant_notes.status' => 'active'
            ])->orderBy('applicant_notes.id', 'DESC')->get();
        return Excel::download(new Applicants_nureses_7_days_export($callBackApplicants), 'applicants.csv');

    }

    public function getPotentialCallBackApplicants()
    {
        $auth_user = Auth::user();
        $callBackApplicants = Applicant::with('cv_notes')
            ->join('applicant_notes', 'applicant_notes.applicant_id', '=', 'applicants.id')
            ->select("applicants.id", "applicants.applicant_job_title","applicants.job_title_prof", "applicants.applicant_name", "applicants.applicant_postcode",
                "applicants.applicant_phone", "applicants.applicant_homePhone", "applicants.job_category", "applicants.applicant_source", "applicants.paid_status",
                "applicant_notes.details", "applicant_notes.added_date", "applicant_notes.added_time")
            ->where([
                'applicants.status' => 'active', "applicants.is_callback_enable" => "yes",'applicants.is_no_job' => '0',
                'applicant_notes.moved_tab_to' => 'callback','applicant_notes.status' => 'active'
            ])->orderBy('applicants.updated_at', 'DESC');
        $raw_columns = ['applicant_job_title','history','postcode'];
        $datatable = datatables()->of($callBackApplicants)
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
            ->addColumn('postcode', function ($applicant) {
                if ($applicant->paid_status == 'close') {
                    return $applicant->applicant_postcode;
                } else {
                    foreach ($applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            return $applicant->applicant_postcode;
                        }
                    }
                    return '<a href="/available-jobs/'.$applicant->id.'" class="btn-link legitRipple">'.$applicant->applicant_postcode.'</a>';
                }
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
            });
        if ($auth_user->hasPermissionTo('resource_Potential-Callback_revert-callback')) {
            $datatable = $datatable->addColumn('checkbox', function ($applicant) {
                return '<label class="mt-checkbox mt-checkbox-single mt-checkbox-outline">
                             <input type="checkbox" class="checkbox-index" value="'.$applicant->id.'">
                             <span></span>
                          </label>';
                })
                ->addColumn('action',  function ($applicant) {
                    return
                    '<a href="#"
                       class="btn bg-teal legitRipple"
                       data-controls-modal="#revert_call_back'.$applicant->id.'" data-backdrop="static"
                       data-keyboard="false" data-toggle="modal"
                       data-target="#revert_call_back'.$applicant->id.'">Revert
                    </a>
                    <div id="revert_call_back'.$applicant->id.'" class="modal fade" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Add Callback Notes Below:</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <form action="'.route('revertCallBackApplicants').'" method="GET" class="form-horizontal">
                                    <input type="hidden" name="_token" value="' . csrf_token() . '">
                                    <div class="modal-body">
                                        <div class="form-group row">
                                            <label class="col-form-label col-sm-3">Details</label>
                                            <div class="col-sm-9">
                                                <input type="hidden" name="applicant_hidden_id" value="'.$applicant->id.'">
                                                <textarea name="details" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">
                                            Close
                                        </button>
                                        <button type="submit" class="btn bg-teal legitRipple">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>';
                });
            $raw_columns = ['history','postcode','checkbox','action'];
        }
        return $datatable->setRowClass(function ($applicant) {
            $row_class = '';
            if ($applicant->paid_status == 'close') {
                $row_class = 'class_dark';
            } else { /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
                foreach ($applicant->cv_notes as $key => $value) {
                    if ($value->status == 'active') {
                        $row_class = 'class_success'; // status: sent
                        break;
                    } elseif ($value->status == 'disable') {
                        $row_class = 'class_danger'; // status: reject
                    }
                }
            }
            return $row_class;
        })->rawColumns($raw_columns)->make(true);
    }

    public function getApplicantSentToCallBackList()
    {
        date_default_timezone_set('Europe/London');
        $audit_data['action'] = "Callback";
        $details = request()->details;
        $audit_data['applicant'] = $applicant_id = request()->applicant_hidden_id;

        $user = Auth::user();
        ApplicantNote::where('applicant_id', $applicant_id)
            ->whereIn('moved_tab_to', ['callback','revert_callback'])
            ->update(['status' => 'disable']);
		
        $applicant_note = new ApplicantNote();
        $applicant_note->user_id = $user->id;
        $applicant_note->applicant_id = $applicant_id;
        $audit_data['added_date'] = $applicant_note->added_date = date("jS F Y");
        $audit_data['added_time'] = $applicant_note->added_time = date("h:i A");
        $audit_data['details'] = $applicant_note->details = $details;
        $applicant_note->moved_tab_to = "callback";
        $applicant_note->status = "active";
        $applicant_note->save();
		
		if(isset(request()->job_hidden_id))
        {
            $sale_id = request()->job_hidden_id;
            $pivotSale = Applicants_pivot_sales::where('applicant_id', $applicant_id)
                ->where('sales_id',$sale_id)
                ->first();
            if($pivotSale){
                Notes_for_range_applicants::where('applicants_pivot_sales_id', $pivotSale->id)->delete();
                $pivotSale->delete();
            }
        }
		
        $last_inserted_note = $applicant_note->id;
        if ($last_inserted_note > 0) {
            $note_uid = md5($last_inserted_note);
            ApplicantNote::where('id', $last_inserted_note)->update(['note_uid' => $note_uid]);
            Applicant::where(['id' => $applicant_id])->update(['is_callback_enable' => 'yes']);
            /*** activity log
             * $action_observer = new ActionObserver();
             * $action_observer->action($audit_data, 'Resource');
             */
			if(isset(request()->requestByAjax) && request()->requestByAjax == 'yes')
            {
                return response()->json(['success' => true, 'message' => 'Applicant has been Moved to Call Back.' ]);
            }else{
               return Redirect::back()->with('potentialCallBackSuccess', 'Applicant has been Moved to Call Back.');
            }
        }
		
		if(isset(request()->requestByAjax) && request()->requestByAjax == 'yes')
		{
			return response()->json(['success' => true, 'message' => 'Applicant has been Moved to Call Back.' ]);
		}else{
			return redirect()->back();
		}
        
    }

    public function getApplicantRevertToSearchList()
    {
        date_default_timezone_set('Europe/London');
        $audit_data['action'] = "Revert Callback";
        $details = request()->details;
        $audit_data['applicant'] = $applicant_id = request()->applicant_hidden_id;
        $user = Auth::user();
        ApplicantNote::where('applicant_id', $applicant_id)
            ->whereIn('moved_tab_to', ['callback','revert_callback'])
            ->update(['status' => 'disable']);
        $applicant_note = new ApplicantNote();
        $applicant_note->user_id = $user->id;
        $applicant_note->applicant_id = $applicant_id;
        $audit_data['added_date'] = $applicant_note->added_date = date("jS F Y");
        $audit_data['added_time'] = $applicant_note->added_time = date("h:i A");
        $audit_data['details'] = $applicant_note->details = $details;
        $applicant_note->moved_tab_to = "revert_callback";
        $applicant_note->status = "active";
        $applicant_note->save();
        $last_inserted_note = $applicant_note->id;
        if ($last_inserted_note > 0) {
            $note_uid = md5($last_inserted_note);
            ApplicantNote::where('id', $last_inserted_note)->update(['note_uid' => $note_uid]);
            Applicant::where(['id' => $applicant_id])->update(['is_callback_enable' => 'no']);
            /*** activity log
             * $action_observer = new ActionObserver();
             * $action_observer->action($audit_data, 'Resource');
             */
            return Redirect::back()->with('potentialCallBackSuccess', 'Added');
        }
        return redirect()->back();
    }

    public function getCrmNotAttendedApplicantCv()
    {
        return view('administrator.resource.crm_not_attended_applicants');
    }
	 public function exportCrmNotAttendedApplicantCv()
    {
        $crm_not_attended_applicants = Applicant::with('cv_notes')
            ->join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })
            ->select(
                'applicants.applicant_phone', 'applicants.applicant_name', 'applicants.applicant_homePhone','applicants.applicant_job_title',
            'applicants.applicant_postcode','applicants.applicant_source','applicants.applicant_notes')
            ->where([
                "applicants.status" => "active",
                "crm_notes.moved_tab_to" => "interview_not_attended",
                "history.sub_stage" => "crm_interview_not_attended", "history.status" => "active"
            ])->orderBy("crm_notes.id","DESC")->get();
        return Excel::download(new Applicants_nureses_7_days_export($crm_not_attended_applicants), 'applicants.csv');

    }

    public function getCrmNotAttendedApplicantCvAjax()
    {
        $crm_not_attended_applicants = Applicant::with('cv_notes')
            ->join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })
            ->select(
                'crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category',
                'applicants.applicant_postcode', 'applicants.applicant_phone', 'applicants.paid_status',
                'applicants.applicant_homePhone','applicants.applicant_source')
            ->where([
                "applicants.status" => "active",
                "crm_notes.moved_tab_to" => "interview_not_attended",
                "history.sub_stage" => "crm_interview_not_attended", "history.status" => "active"
            ])->orderBy("crm_notes.id","DESC");

        return datatables()->of($crm_not_attended_applicants)
            ->addColumn("applicant_postcode",function($crm_not_attended_applicant) {
                if ($crm_not_attended_applicant->paid_status == 'close') {
                    return $crm_not_attended_applicant->applicant_postcode;
                } else {
                    foreach ($crm_not_attended_applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            return $crm_not_attended_applicant->applicant_postcode;
                        }
                    }
                    return '<a href="/available-jobs/' . $crm_not_attended_applicant->id . '">' . $crm_not_attended_applicant->applicant_postcode . '</a>';
                }
            })
			->editColumn('applicant_job_title', function ($crm_not_attended_applicant) {
                $job_title_desc='';
                if($crm_not_attended_applicant->job_title_prof!=null)
                {
                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $crm_not_attended_applicant->job_title_prof)->first();
                                $job_title_desc = $crm_not_attended_applicant->applicant_job_title.' ('.$job_prof_res->specialist_prof.')';
                }
                else
                {
                    
                    $job_title_desc = $crm_not_attended_applicant->applicant_job_title;
                }
                return $job_title_desc;
    
         })
            ->addColumn('history', function ($crm_not_attended_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$crm_not_attended_applicant->id.'"; 
                                 data-controls-modal="#reject_history'.$crm_not_attended_applicant->id.'" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_not_attended_applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$crm_not_attended_applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">'.$crm_not_attended_applicant->applicant_name.'</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$crm_not_attended_applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

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
            ->setRowClass(function ($crm_not_attended_applicant) {
                $row_class = '';
                if ($crm_not_attended_applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } else { /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
                    foreach ($crm_not_attended_applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $row_class = 'class_success'; // status: sent
                            break;
                        }
                    }
                }
                return $row_class;
            })
            ->rawColumns(['applicant_job_title','applicant_postcode', 'history'])
            ->make(true);
    }

    public function getCrmStartDateHoldApplicantCv()
    {
        return view('administrator.resource.crm_start_date_hold_applicants');
    }
	
	public function exportCrmStartDateHoldApplicantCv()
    {
        $crm_start_date_hold_applicants = Applicant::with('cv_notes')
            ->join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('applicants.applicant_phone', 'applicants.applicant_name', 'applicants.applicant_homePhone','applicants.applicant_job_title',
            'applicants.applicant_postcode','applicants.applicant_source','applicants.applicant_notes')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'start_date_hold',
                'history.status' => 'active',
            ])->whereIn('history.sub_stage', ['crm_start_date_hold', 'crm_start_date_hold_save'])
            ->orderBy("crm_notes.id","DESC")->get();
        return Excel::download(new Applicants_nureses_7_days_export($crm_start_date_hold_applicants), 'applicants.csv');

    }
    public function getCrmStartDateHoldApplicantCvAjax()
    {
        /*** query for crm: start date hold tab */

        $crm_start_date_hold_applicants = Applicant::with('cv_notes')
            ->join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category',
                'applicants.applicant_postcode', 'applicants.applicant_phone', 'applicants.paid_status',
                'applicants.applicant_homePhone','applicants.applicant_source')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'start_date_hold',
                'history.status' => 'active',
            ])->whereIn('history.sub_stage', ['crm_start_date_hold', 'crm_start_date_hold_save'])
            ->orderBy("crm_notes.id","DESC");

        return datatables()->of($crm_start_date_hold_applicants)
            ->addColumn("applicant_postcode",function($crm_start_date_hold_applicant) {
                if ($crm_start_date_hold_applicant->paid_status == 'close') {
                    return $crm_start_date_hold_applicant->applicant_postcode;
                } else {
                    foreach ($crm_start_date_hold_applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            return $crm_start_date_hold_applicant->applicant_postcode;
                        }
                    }
                    return '<a href="/available-jobs/' . $crm_start_date_hold_applicant->id . '">' . $crm_start_date_hold_applicant->applicant_postcode . '</a>';
                }
            })
			->editColumn('applicant_job_title', function ($crm_start_date_hold_applicant) {
                $job_title_desc='';
                if($crm_start_date_hold_applicant->job_title_prof!=null)
                {
                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $crm_start_date_hold_applicant->job_title_prof)->first();
                                $job_title_desc = $crm_start_date_hold_applicant->applicant_job_title.' ('.$job_prof_res->specialist_prof.')';
                }
                else
                {
                    
                    $job_title_desc = $crm_start_date_hold_applicant->applicant_job_title;
                }
                return $job_title_desc;
    
         })
            ->addColumn('history', function ($crm_start_date_hold_applicant) {
                $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$crm_start_date_hold_applicant->id.'"; 
                                 data-controls-modal="#reject_history'.$crm_start_date_hold_applicant->id.'" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_start_date_hold_applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$crm_start_date_hold_applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">'.$crm_start_date_hold_applicant->applicant_name.'</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$crm_start_date_hold_applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

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
            ->setRowClass(function ($result) {
                $row_class = '';
                if ($result->paid_status == 'close') {
                    $row_class = 'class_dark';
                } else { /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
                    foreach ($result->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $row_class = 'class_success'; // status: sent
                            break;
                        }
                    }
                }
                return $row_class;
            })
            ->rawColumns(['applicant_job_title','applicant_postcode', 'history'])
            ->make(true);
    }

    public function getCrmPaidApplicantCv()
    {
        return view('administrator.resource.crm_paid_applicants');
    }
	public function exportCrmPaidApplicantCv()
    {
        $crm_paid_applicants = Applicant::with('cv_notes')
            ->join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->select('applicants.applicant_phone', 'applicants.applicant_name', 'applicants.applicant_homePhone','applicants.applicant_job_title',
            'applicants.applicant_postcode','applicants.applicant_source','applicants.applicant_notes')
            ->where([
                'applicants.status' => 'active', 'applicants.paid_status' => 'open',
                'crm_notes.moved_tab_to' => 'paid'
            ])
            ->whereIn('crm_notes.id', function($query){
                $query->select(\Illuminate\Support\Facades\DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="paid" and applicants.id=applicant_id'));
            })
            ->orderBy("crm_notes.id","DESC")->get();
        return Excel::download(new Applicants_nureses_7_days_export($crm_paid_applicants), 'applicants.csv');

    }

    public function getCrmPaidApplicantCvAjax(Request $request)
    {
        /*** query for crm: paid tab */
     	date_default_timezone_set('Europe/London');
        $job_category = $request->filled('job_category') ? $request->get('job_category') : null;

        $end_date = Carbon::now();
		$edate21 = $end_date->subMonth(3);

        $edate = $edate21->format('Y-m-d') . " 23:59:59";
        $start_date = $end_date->subMonths(1);
        $sdate = $start_date->format('Y-m-d') . " 00:00:00";
        $result = Applicant::with('cv_notes')
            ->join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
					'applicants.id', 'applicants.applicant_name', 'applicants.applicant_job_title', 
					'applicants.job_title_prof', 'applicants.job_category',
					'applicants.applicant_postcode', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
					'applicants.applicant_source', 'applicants.paid_status', 'applicants.paid_timestamp', 
					'applicants.department', 'applicants.sub_department'
			)
            ->where([
//                'applicants.status' => 'active', 'applicants.paid_status' => 'open',
                'applicants.status' => 'active',
//                'crm_notes.moved_tab_to' => 'paid','applicants.is_no_job' => '0'
               'applicants.is_no_job' => '0'
            ])
           //->whereBetween('crm_notes.created_at', [$sdate,$edate])
			 ->whereDate('crm_notes.updated_at', '<=', $edate)

            ->whereIn('applicants.paid_status',['open','pending'])
            ->whereIn( 'crm_notes.moved_tab_to',['paid','dispute','start_date_hold','declined','start_date'])
            ->whereIn('crm_notes.id', function($query){
//                $query->select(\Illuminate\Support\Facades\DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="paid" and applicants.id=applicant_id'));
                $query->select(DB::raw('MAX(id) FROM crm_notes'))
                    ->whereIn('moved_tab_to', ['paid','dispute','start_date_hold','declined','start_date'])
                    ->where('applicants.id', '=', DB::raw('applicant_id'));
            });
  if ($job_category=="nurse") {
            $result = $result->where('applicants.job_category', '=', $job_category);
        }elseif ($job_category=="non-nurse"){
            $result = $result->whereIn('applicants.job_category',['chef','non-nurse','nonnurse']);

        }

            $crm_paid_applicants=$result->orderBy("crm_notes.id","DESC");

        return datatables()->of($crm_paid_applicants)
            ->addColumn("applicant_postcode",function ($applicant) {
                foreach ($applicant->cv_notes as $key => $value) {
                    if ($value->status == 'active') { return $applicant->applicant_postcode; }
                }
                return '<a href="/available-jobs/'.$applicant->id.'">'.$applicant->applicant_postcode.'</a>';
            })
			->editColumn("department",function($applicant){
				$applicant_department = $applicant->department;
				return $applicant_department ? $applicant_department : '-';
			 })
			->editColumn("sub_department",function($applicant){
				$sub_department = $applicant->sub_department;
				return $sub_department ? $sub_department : '-';
			 })
			->editColumn('applicant_job_title', function ($applicant) {
                $job_title_desc='';
                if($applicant->job_title_prof!=null)
                {
                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $applicant->job_title_prof)->first();
                                $job_title_desc = $job_prof_res->specialist_prof;
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
            ->setRowClass(function ($result) {
                $row_class = '';
                foreach ($result->cv_notes as $key => $value) {
                    if ($value->status == 'active') {
                        $row_class = 'class_success'; // status: sent
                        break;
                    } elseif ($value->status == 'disable') {
                        $row_class = 'class_danger'; // status: reject
                    }
                }
                return $row_class;
            })
            ->rawColumns(['applicant_job_title','applicant_postcode', 'history'])
            ->make(true);
    }

    public function applicantRejectedHistory(Request $request)
    {
        $applicant_id = $request->input('applicant');

        $applicants_rejected_history = Crm_note::join('sales', 'sales.id', '=', 'crm_notes.sales_id')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
            ->select('sales.job_title', 'sales.postcode', 'sales.id', 'units.unit_name','crm_notes.created_at', 'crm_notes.details', 'crm_notes.moved_tab_to')
            ->whereIn('crm_notes.moved_tab_to', ['cv_sent_reject', 'request_reject', 'interview_not_attended', 'start_date_hold', 'dispute'])
            ->where('crm_notes.applicant_id', '=', $applicant_id)
            ->get();
        $history_modal_body = view('administrator.resource.partial.applicant_rejected_history', compact('applicants_rejected_history'))->render();
        return $history_modal_body;
//        return $applicants_rejected_history;
    }
	
	public function getRejectedAppDateWise($id, $month)
    {
        $range_val = '';
        if($month == 3)
        {
            $range_val = '3 Months';
        }
        elseif($month == 6)
        {
            $range_val = '6 Months';
        }
        elseif($month == 9)
        {
            $range_val = '9 Months';
        }
        else
        {
            $range_val= 'Remaining';
        }
        return view('administrator.resource.crm_rejected_app.crm_3_months_rejected_app', compact('id','month','range_val'));
    }
    public function getRejectedAppDateWiseAjax($id, $month)
    {
        $category = '';
        if($id == 44)
        {
            $category = 'nurse';
        }
        else
        {
            $category = 'non-nurse';
        }
        $start_date ='';
        $end_date ='';
        if($month == 3)
        {
            $start_date = Carbon::now()->subMonth(3)->format('Y-m-d') . " 00:00:01";
             $end_date = Carbon::now()->format('Y-m-d') . " 23:59:59";
        }
        elseif($month == 6)
        {
            $month_val_3 = Carbon::now()->subMonth(3);
            $start_date = $month_val_3->subMonth(6)->format('Y-m-d') . " 00:00:01";
             $end_date = Carbon::now()->subMonth(3)->format('Y-m-d') . " 23:59:59";
        }
		elseif($month == 9)
        {
            $month_val_3 = Carbon::now()->subMonth(6);
            $start_date = $month_val_3->subMonth(9)->format('Y-m-d') . " 00:00:01";
             $end_date = Carbon::now()->subMonth(6)->format('Y-m-d') . " 23:59:59";
        }
		else
        {
            $start_date = "2020-01-01 00:00:01";
            $end_date = Carbon::now()->subMonth(9);
        }
        $crm_rejected_applicants = Applicant::with('cv_notes')
        ->join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
        ->join('history', function ($join) {
            $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
            $join->on('crm_notes.sales_id', '=', 'history.sale_id');
        })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
            'applicants.id', 'applicants.applicant_name', 'applicants.applicant_job_title', 'applicants.job_title_prof', 'applicants.job_category',
            'applicants.applicant_postcode', 'applicants.applicant_phone', 'applicants.paid_status',
            'applicants.applicant_homePhone','applicants.applicant_source','applicants.lat','applicants.lng',
				   'applicants.applicant_email',
            DB::raw('
        CASE 
            WHEN history.sub_stage="crm_reject"
            THEN "Rejected CV" 
            WHEN history.sub_stage="crm_request_reject"
            THEN "Rejected By Request"
            WHEN history.sub_stage="crm_interview_not_attended"
            THEN "Not Attended"
            WHEN history.sub_stage="crm_start_date_hold" OR history.sub_stage = "crm_start_date_hold_save"
            THEN "Start Date Hold"
            END AS sub_stage'))->whereIn("history.sub_stage", ["crm_interview_not_attended","crm_request_reject","crm_reject","crm_start_date_hold", "crm_start_date_hold_save"])
            ->whereIn("crm_notes.moved_tab_to", ["interview_not_attended","request_reject","cv_sent_reject","start_date_hold", "start_date_hold_save"])
            ->whereBetween('crm_notes.updated_at', [$start_date, $end_date])
        ->where([
            "applicants.status" => "active", "applicants.job_category" => $category, "history.status" => "active",
"applicants.is_in_nurse_home" => "no", "applicants.is_blocked" => "0", 'applicants.is_callback_enable' => 'no',"is_no_job"=>"0"
        ])
->where("applicants.lat", "!=", 0.000000)->where("applicants.lng", "!=", 0.000000)			
->orderBy("crm_notes.id","DESC")->groupBy('applicants.applicant_phone')->get();
		


        $data = Sale::select('job_title','lat', 'lng')
        ->where("status", "active")->where("is_on_hold", "0")->where("lat", "!=", 0.000000)->where("lng", "!=", 0.000000)
        ->get();
		
        $data = collect($data->toArray());
        $crm_rejected_app = [];
         foreach ($crm_rejected_applicants as $key => $value) {
            $lat_val = $value->lat;
            $lng_val = $value->lng;
			
                foreach($data as $d)
                {
				
                    $res = ((ACOS(SIN($lat_val * PI() / 180) * SIN($d['lat'] * PI() / 180) +
                    COS($lat_val * PI() / 180) * COS($d['lat'] * PI() / 180) * COS(($lng_val - $d['lng']) * PI() / 180)) * 180 / PI()) * 60 * 1.1515);
					
                    if($res <= 15)
                    {
                    $title = $this->getAllTitles($value->applicant_job_title);
						
        if($d['job_title'] == $title[0] || $d['job_title'] == $title[1] || $d['job_title'] == $title[2] || $d['job_title'] == $title[3] || $d['job_title'] == $title[4] ||
        $d['job_title'] == $title[5] || $d['job_title'] == $title[6] || $d['job_title'] == $title[7] || $d['job_title'] == $title[8] || $d['job_title'] == $title[9])
        {
            $crm_rejected_app[] = $crm_rejected_applicants[$key];
                    break;
        }
                    }
                }

            }
	

        return datatables()->of($crm_rejected_app)
            ->addColumn("applicant_postcode",function($crm_rejected_applicant) {
                if ($crm_rejected_applicant->paid_status == 'close') {
                    return $crm_rejected_applicant->applicant_postcode;
                } 
                else {
                    foreach ($crm_rejected_applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            return $crm_rejected_applicant->applicant_postcode;
                        }
                    }
                    return '<a href="/available-jobs/'.$crm_rejected_applicant->id.'">'.$crm_rejected_applicant->applicant_postcode.'</a>';
                }
            })
            ->editColumn('applicant_job_title', function ($crm_rejected_applicant) {
                $job_title_desc='';
                if($crm_rejected_applicant->job_title_prof!=null)
                {
                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $crm_rejected_applicant->job_title_prof)->first();
                                $job_title_desc = $crm_rejected_applicant->applicant_job_title.' ('.$job_prof_res->specialist_prof.')';
                }
                else
                {
                    
                    $job_title_desc = $crm_rejected_applicant->applicant_job_title;
                }
                return $job_title_desc;
                
         })
         

         
            ->addColumn('history', function ($crm_rejected_applicant) {
            $content = '';
                $content .= '<a href="#" class="reject_history" data-applicant="'.$crm_rejected_applicant->id.'"; 
                                 data-controls-modal="#reject_history'.$crm_rejected_applicant->id.'" 
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#reject_history' . $crm_rejected_applicant->id . '">History</a>';

                $content .= '<div id="reject_history'.$crm_rejected_applicant->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Rejected History - <span class="font-weight-semibold">'.$crm_rejected_applicant->applicant_name.'</span></h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" id="applicant_rejected_history'.$crm_rejected_applicant->id.'" style="max-height: 500px; overflow-y: auto;">';

                /*** Details are fetched via ajax request */

                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';



 /*** Export Applicants Modal */
                $content .= '<div id="export_all_rejected_applicant_action" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-sm">';
                $content .= '<div class="modal-content">';

                $content .= '<div class="modal-header">';
                $content .= '<h3 class="modal-title">Export Applicants</h3>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<form action="' . route('export_all_rejected_applicants') . '" method="POST" id="export_block_applicants" class="form-horizontal">';
                $content .= csrf_field();
                // $content .= '<input type="hidden" name="applicant_id" value="' . $applicant->id . '">';
                // $content .= '<input type="hidden" name="sale_id" value="' . $applicant->sale_id . '">';
                $content .= '<div class="mb-4">';
                $content .= '<div class="input-group">';
                $content .= '<span class="input-group-prepend">';
                $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                $content .= '</span>';
                $content .= '<input type="text" class="form-control pickadate-year" name="start_date" id="start_date" placeholder="Select From Date">';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="mb-4">';
                $content .= '<div class="input-group">';
                $content .= '<span class="input-group-prepend">';
                $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                $content .= '</span>';
//                $content .= '<input type="text" class="form-control time_pickerrrr" id="anytime-time'.$applicant->id.'-'.$applicant->sale_id.'" name="schedule_time" placeholder="Select Schedule Time e.g., 00:00">';
                $content .= '<input type="text" class="form-control pickadate-year" name="end_date" id="end_date" placeholder="Select To Date">';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<button type="submit" class="btn bg-teal legitRipple btn-block">Submit</button>';
                $content .= '</form>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;

            })
            ->setRowClass(function ($crm_rejected_applicant) {
                $row_class = '';
                if ($crm_rejected_applicant->paid_status == 'close') {
                    $row_class = 'class_dark';
                } else { /*** $applicant->paid_status == 'open' || $applicant->paid_status == 'pending' */
                    foreach ($crm_rejected_applicant->cv_notes as $key => $value) {
                        if ($value->status == 'active') {
                            $row_class = 'class_success'; // status: sent
                            break;
                        }
                    }
                }
                return $row_class;
            })
            ->rawColumns(['applicant_job_title','applicant_postcode', 'history','applicant_notes'])
            ->make(true);
    }
	
	public function getChefSales()
    {
        // $sales = Office::join('sales', 'offices.id', '=', 'sales.head_office')
        //     ->join('units', 'units.id', '=', 'sales.head_office_unit')
        //     ->select('sales.*', 'offices.office_name', 'units.contact_name',
        //         'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
        //     ->where(['sales.status' => 'active', 'sales.job_category' => 'nonnurse'])->get();
        $value = '1';
        return view('administrator.resource.chef', compact('value'));
    }
	
	public function getChefJob(Request $request)
    {
        $user = Auth::user();
        $result='';
       
            $sale_notes = Sales_notes::select('sale_id','sales_notes.sale_note', DB::raw('MAX(created_at) as 
            sale_created_at'))
                ->groupBy('sale_id');
            $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
                ->join('units', 'units.id', '=', 'sales.head_office_unit')
                ->select('sales.*', 'offices.office_name', 'units.contact_name',
                    'units.contact_email', 'units.unit_name', 'units.contact_phone_number', DB::raw("(SELECT count(cv_notes.sale_id) from cv_notes
                WHERE cv_notes.sale_id=sales.id AND cv_notes.status='active' group by cv_notes.sale_id) as result"))
                ->where(['sales.status' => 'active', 'sales.is_on_hold' => '0', 'sales.job_category' => 'chef'])
                ->whereNotIn('sales.job_title', ['nonnurse specialist'])
                ->orderBy('id', 'DESC');

        

        // (cv_notes.status='active' or cv_notes.status='paid')
        // $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
        //     ->join('units', 'units.id', '=', 'sales.head_office_unit')
        //     ->select('sales.*', 'offices.office_name', 'units.contact_name',
        //         'units.contact_email', 'units.unit_name', 'units.contact_phone_number')
        //     ->where(['sales.status' => 'active', 'sales.job_category' => 'nonnurse'])->orderBy('id', 'DESC');

        $aColumns = ['sale_added_date', 'sale_added_time', 'job_title', 'office_name', 'unit_name',
            'postcode', 'job_type', 'experience', 'qualification', 'salary', 'sale_notes', 'status', 'Cv Limit'];

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
			$post_code = strtoupper($sRow->postcode);
            $checkbox = "<label class=\"mt-checkbox mt-checkbox-single mt-checkbox-outline\">
                             <input type=\"checkbox\" class=\"checkbox-index\" value=\"{$sRow->id}\">
                             <span></span>
                          </label>";
            $postcode = "<a href=\"/applicants-within-15-km/{$sRow->id}\">{$post_code}</a>";
            if ($sRow->status == 'active') {
                $status = '<h5><span class="badge w-100 badge-success">Active</span></h5>';
            } else {
                $status = '<h5><span class="badge w-100 badge-danger">Disable</span></h5>';
            }

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
            $job_title_desc='';
            if(@$sRow->job_title_prof!='')
            {
                $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $sRow->job_title_prof)->first();
                $job_title_desc = $sRow->job_title.' ('.$job_prof_res->specialist_prof.')';
                // $job_title_desc = @$sRow->job_title.' ('.@$sRow->job_title_prof.')';
            }
            else
            {
                $job_title_desc = @$sRow->job_title;
            }
            $output['aaData'][] = array(
                "DT_RowId" => "row_{$sRow->id}",
                //    @$checkbox,
                @$sRow->sale_added_date,
                @$sRow->sale_added_time,
				strtoupper($job_title_desc),
                @ucwords(strtolower($sRow->office_name)),
                @ucwords(strtolower($sRow->unit_name)),
                @$postcode,
                @ucwords($sRow->job_type),
                @$sRow->experience,
                @$sRow->qualification,
                @$sRow->salary,
                @$sRow->sale_notes,
                @$status,
                @$sRow->result==$sRow->send_cv_limit?'<span class="badge w-100 badge-danger" style="font-size:90%">Limit Reached</span>':"<span class='badge w-100 badge-success' style='font-size:90%'>". ((int)$sRow->send_cv_limit - (int)$sRow->result)." Cv's limit remaining</span>",
				@$action,
            );


            $i++;

        }

        //  print_r($output);
        echo json_encode($output);
    }
	
	 public function getNurserySales()
    {
        $value = '1';
        return view('administrator.resource.nursery', compact('value'));
    }

    public function getNurseryJob(Request $request)
    {
        $user = Auth::user();
        $result = '';

        $sale_notes = Sales_notes::select('sale_id', 'sales_notes.sale_note', DB::raw('MAX(created_at) as 
            sale_created_at'))
            ->groupBy('sale_id');
        $result = Office::join('sales', 'offices.id', '=', 'sales.head_office')
            ->join('units', 'units.id', '=', 'sales.head_office_unit')
            ->select(
                'sales.*',
                'offices.office_name',
                'units.contact_name',
                'units.contact_email',
                'units.unit_name',
                'units.contact_phone_number',
                DB::raw("(SELECT count(cv_notes.sale_id) from cv_notes
                WHERE cv_notes.sale_id=sales.id AND cv_notes.status='active' group by cv_notes.sale_id) as result")
            )
            ->where([
                'sales.status' => 'active', 
                'sales.is_on_hold' => '0', 
                'sales.job_category' => 'nursery'
            ])
            ->orderBy('id', 'DESC');

        $aColumns = [
            'sale_added_date',
            'sale_added_time',
            'job_title',
            'office_name',
            'unit_name',
            'postcode',
            'job_type',
            'experience',
            'qualification',
            'salary',
            'sale_notes',
            'status',
            'Cv Limit'
        ];

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
            $post_code = strtoupper($sRow->postcode);
            $checkbox = "<label class=\"mt-checkbox mt-checkbox-single mt-checkbox-outline\">
                             <input type=\"checkbox\" class=\"checkbox-index\" value=\"{$sRow->id}\">
                             <span></span>
                          </label>";
            $postcode = "<a href=\"/applicants-within-15-km/{$sRow->id}\">{$post_code}</a>";
            if ($sRow->status == 'active') {
                $status = '<h5><span class="badge w-100 badge-success">Active</span></h5>';
            } else {
                $status = '<h5><span class="badge w-100 badge-danger">Disable</span></h5>';
            }

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
            $job_title_desc = '';
            if (@$sRow->job_title_prof != '') {
                $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $sRow->job_title_prof)->first();
                $job_title_desc = $sRow->job_title . ' (' . $job_prof_res->specialist_prof . ')';
            } else {
                $job_title_desc = @$sRow->job_title;
            }
            $output['aaData'][] = array(
                "DT_RowId" => "row_{$sRow->id}",
                //    @$checkbox,
                @$sRow->sale_added_date,
                @$sRow->sale_added_time,
                strtoupper($job_title_desc),
                @ucwords(strtolower($sRow->office_name)),
                @ucwords(strtolower($sRow->unit_name)),
                @$postcode,
                @ucwords($sRow->job_type),
                @$sRow->experience,
                @$sRow->qualification,
                @$sRow->salary,
                @$sRow->sale_notes,
                @$status,
                @$sRow->result == $sRow->send_cv_limit ? '<span class="badge w-100 badge-danger" style="font-size:90%">Limit Reached</span>' : "<span class='badge w-100 badge-success' style='font-size:90%'>" . ((int)$sRow->send_cv_limit - (int)$sRow->result) . " Cv's limit remaining</span>",
                @$action,
            );


            $i++;
        }

        echo json_encode($output);
    }
}