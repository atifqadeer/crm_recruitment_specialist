<?php

namespace Horsefly\Http\Controllers;
use Auth;
use Carbon\Carbon;
use DateTime;
use Horsefly\Applicant;
use Horsefly\ApplicantNote;
use Horsefly\Audit;
use Horsefly\Crm_note;
use Horsefly\Cv_note;
use Horsefly\History;
use Horsefly\ModuleNote;
use Horsefly\Office;
use Horsefly\Quality_notes;
use Horsefly\Sale;
use Horsefly\Crm_rejected_cv;
use Horsefly\Unit;
use Horsefly\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Horsefly\Exports\ApplicantsMonthlyStatsDetails;
use Horsefly\Specialist_job_titles;
use Horsefly\RevertStage;
use Horsefly\Sales_notes;





class HomeController extends Controller
{
    /*** sample code for middle application on specific methods
     * $this->middleware('is_admin', ['except' => ['index', 'edit']]);
     * $this->middleware('is_admin', ['only' => ['update']]);
     */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
//        $this->middleware('is_admin')->except(['edit']);
        $this->middleware('permission:dashboard_statistics', ['only' => ['index','dailyStats', 'weeklyStats', 'monthlyStats', 'customStats', 'callbackApplicants', 'getCallbackApplicants', 'userStatistics']]);
    }


    public function index()
    {
        $today = Carbon::today();
        /*** Quick Stats Widgets */
        $applicant_data['no_of_applicants'] = Applicant::where('status', 'active')->count();
        // $sales['no_of_open_sales'] = Sale::where('status', 'active')->count();
        $sales['no_of_open_sales'] = Office::join('sales', 'offices.id', '=', 'sales.head_office')
            ->where('sales.status','active')
            ->where('sales.is_on_hold','0')
            ->count();

        /*** Resource Stats */
        $days_7_end_date = Carbon::now();
        $r_e_date = $days_7_end_date->format('Y-m-d') . " 23:59:59";
        $days_7_start_date = $days_7_end_date->subDays(16);
        $r_s_date = $days_7_start_date->format('Y-m-d') . " 00:00:00";
        $resource['last_7_days'] = Applicant::leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$r_s_date, $r_e_date])
            ->where("applicants.status", "=", "active")
           // ->where("applicants.job_category", "=", "nurse")
            ->whereNull('applicants_pivot_sales.applicant_id')
			->count();

        $days_21_end_date = Carbon::now();
        $edate7 = $days_21_end_date->subDays(16);
        $r_e_date = $edate7->format('Y-m-d') . " 23:59:59";
        $days_21_start_date = $days_21_end_date->subDays(21);
        $r_s_date = $days_21_start_date->format('Y-m-d') . " 00:00:00";
        $resource['last_21_days'] = Applicant::leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereBetween('applicants.updated_at', [$r_s_date, $r_e_date])
            ->where("applicants.status", "=", "active")
          //  ->where("applicants.job_category", "=", "nurse")
            ->whereNull('applicants_pivot_sales.applicant_id')
			->count();

        $all_app_end_date = Carbon::now();
		$edate21 = $all_app_end_date->subMonth(1)->subDays(6); // 16 + 21 + excluding last_day . 00:00:00
        $r_e_date = $edate21->format('Y-m-d');

        $resource['all_applicants'] = Applicant::leftJoin('applicants_pivot_sales', 'applicants.id', '=', 'applicants_pivot_sales.applicant_id')
            ->whereDate('applicants.updated_at', '<=', $r_e_date)
            ->where("applicants.status", "=", "active")
        //    ->where("applicants.job_category", "=", "nurse")
            ->whereNull('applicants_pivot_sales.applicant_id')
			->count();

        $sales['no_of_offices'] = Office::where('status', 'active')->count();
        $no_of_units = Office::join('units', 'offices.id', '=', 'units.head_office')
			->count();
        /*** /quick stats widgets */

        /*** Daily Stats
         * Applicants */
        $applicant_data['no_of_nurses'] = Applicant::where([ 'status' => 'active', 'job_category' => 'nurse' ])->whereDate('created_at', $today)->count();
        $applicant_data['no_of_non_nurses'] = Applicant::where([ 'status' => 'active'])->whereIn( 'job_category',['non-nurse','nursery'])->whereDate('created_at', $today)->count();
        $applicant_data['no_of_callbacks'] = ApplicantNote::where('moved_tab_to', '=', 'callback')->whereDate('created_at', $today)->count();
        $applicant_data['no_of_not_interested'] = Applicant::join('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
            ->where([ 'applicants.status' => 'active', 'applicants_pivot_sales.is_interested' => 'no' ])->whereDate('applicants_pivot_sales.created_at', $today)->count();
		
		   $applicant_data['no_of_nurses_update'] = Applicant::where([ 'status' => 'active', 'job_category' => 'nurse' ])->whereDate('updated_at', $today)->whereColumn('updated_at', '!=', 'created_at')->count();
        $applicant_data['no_of_non_nurses_update'] = Applicant::where([ 'status' => 'active' ])->whereIn( 'job_category',['non-nurse','nursery'])->whereDate('updated_at', $today)->whereColumn('updated_at', '!=', 'created_at')->count();
        $applicant_data['no_of_callbacks_update'] = ApplicantNote::where('moved_tab_to', '=', 'callback')->whereDate('updated_at', $today)->whereColumn('updated_at', '!=', 'created_at')->count();
        $applicant_data['no_of_not_interested_update'] = Applicant::join('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
            ->where([ 'applicants.status' => 'active', 'applicants_pivot_sales.is_interested' => 'no' ])->whereDate('applicants_pivot_sales.updated_at', $today)->whereColumn('applicants_pivot_sales.updated_at', '!=', 'applicants_pivot_sales.created_at')->count();
 
		
//        $applicant_data['no_of_no_nursing_home'] = Quality_notes::where('moved_tab_to', '=', 'no_nursing_home')->whereDate('created_at', $today)->count();
//        $applicant_data['no_of_revert_callbacks'] = Quality_notes::where('moved_tab_to', '=', 'revert_to_callback')->whereDate('created_at', $today)->count();

        /*** Sales */
       // $sales['daily_open_sales'] = 0;
       // $sales['daily_close_sales'] = 0;
       // $sales['daily_psl_offices'] = 0;
       // $sales['daily_non_psl_offices'] = 0;
         $sales['daily_open_sales'] = Sale::where(['status' => 'active'])->whereDate('created_at', $today)->count();
         $sales['daily_close_sales'] = Audit::where(['message' => 'sale-closed', 'auditable_type' => 'Horsefly\\Sale'])->whereDate('created_at', $today)->count();
         $sales['daily_psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'psl'])->whereDate('created_at', $today)->count();
         $sales['daily_non_psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'non psl'])->whereDate('created_at', $today)->count();

		   /*** Sales Update */
        $sales['daily_open_sales_update'] = Sale::where(['status' => 'active'])->whereDate('updated_at', $today)->whereColumn('updated_at', '!=', 'created_at')->count();
        $sales['daily_close_sales_update'] = Audit::where(['message' => 'sale-closed', 'auditable_type' => 'Horsefly\\Sale'])->whereDate('updated_at', $today)->count();
        $sales['daily_psl_offices_update'] = Office::where(['status' => 'active', 'office_type' => 'psl'])->whereDate('updated_at', $today)->whereColumn('updated_at', '!=', 'created_at')->count();
        $sales['daily_non_psl_offices_update'] = Office::where(['status' => 'active', 'office_type' => 'non psl'])->whereDate('updated_at', $today)->whereColumn('updated_at', '!=', 'created_at')->count();
		        $sales['daily_reopen_sales_update'] = Audit::where(['message' => 'sale-opened', 'auditable_type' => 'Horsefly\\Sale'])->whereDate('created_at', $today)->count();


        /*** Quality */
        $quality['daily_cvs'] = 0;
        $quality['daily_cvs_rejected'] = 0;
        $quality['daily_cvs_cleared'] = 0;
        // $quality['daily_cvs'] = Cv_note::where('send_added_date', $today->format('jS F Y'))->count();
        // $quality['daily_cvs_rejected'] = History::where(['sub_stage' => 'quality_reject', 'status' => 'active'])->where('history_added_date', $today->format('jS F Y'))->count();
        // $quality['daily_cvs_cleared'] = History::where(['sub_stage' => 'quality_cleared'])->where('history_added_date', $today->format('jS F Y'))->count();

        /*** CRM */
        $crm_data["Crm Stage"] = 0;
        $crm_data['crm_sent'] = 0;
        $crm_data["crm_rejected"] = 0;
        $crm_data["crm_requested"] = 0;
        $crm_data["crm_request_rejected"] = 0;
        $crm_data["crm_confirmed"] = 0;
        $crm_data["crm_prestart_attended"] = 0;
        $crm_data["crm_rebook"] = 0;
        $crm_data["crm_not_attended"] = 0;
        $crm_data["crm_declined"] = 0;
        $crm_data["crm_date_started"] = 0;
        $crm_data["crm_start_date_held"] = 0;
        $crm_data["crm_invoiced"] = 0;
        $crm_data["crm_disputed"] = 0;
        $crm_data["crm_paid"] = 0;
		$crm_data["quality_revert"] = 0;
        $crm_data["crm_revert"] = 0;
        // $crm_data["Crm Stage"] = "Number of Applicants";
        // $crm_data['crm_sent'] = $quality['daily_cvs_cleared'];
        // $crm_data['crm_rejected'] = History::where(['sub_stage' => 'crm_reject', 'status' => 'active'])->where('history_added_date', $today->format('jS F Y'))->count();
       
        // $crm_data['crm_requested'] = History::where('history.sub_stage', 'crm_request')
        //     ->whereIn('history.id', function ($query) use ($today) {
        //         $query->select(DB::raw('MAX(h.id) FROM history AS h 
        //         WHERE h.sub_stage = "crm_request" 
        //         AND history.applicant_id = h.applicant_id 
        //         AND history.sale_id = h.sale_id 
        //         AND history.id > ( 
        //             SELECT MAX(hh.id) FROM history AS hh 
        //             WHERE ( hh.sub_stage = "quality_cleared" OR hh.sub_stage = "crm_save" ) 
        //             AND history.applicant_id = hh.applicant_id AND history.sale_id = hh.sale_id 
        //         )'));
        //     })->where('history.history_added_date', $today->format('jS F Y'))->count();
        // $crm_data['crm_request_rejected'] = History::where(['sub_stage' => 'crm_request_reject', 'status' => 'active'])->where('history_added_date', $today->format('jS F Y'))->count();
        // decline daily stat
        // rebook daily stat
       
        
        // $crm_data['crm_confirmed'] = History::where('history.sub_stage', "crm_request_confirm")
        //     ->whereIn('history.id', function ($query) use ($today) {
        //         $query->select(DB::raw('MAX(h.id) FROM history AS h 
        //         WHERE h.sub_stage = "crm_request_confirm" 
	    //         AND history.applicant_id = h.applicant_id 
	    //         AND history.sale_id = h.sale_id 
	    //         AND history.id > ( 
		//             SELECT MAX(hh.id) FROM history AS hh 
		//             WHERE hh.sub_stage = "crm_request" 
		//             AND history.applicant_id = hh.applicant_id 
		//             AND history.sale_id = hh.sale_id 
        //     	)'));
        //     })->where('history.history_added_date', $today->format('jS F Y'))->count();
        // $crm_data['crm_prestart_attended'] = History::where('history.sub_stage', "crm_interview_attended")
        //     ->whereIn('history.id', function ($query) {
        //         $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_interview_attended" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
        //     })->where('history.history_added_date', $today->format('jS F Y'))->count();
        // $crm_data['crm_rebook'] = History::where('history.sub_stage', "crm_rebook")
        //     ->whereIn('history.id', function ($query) {
        //     $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
        //     })->where('history.history_added_date', $today->format('jS F Y'))->count();
        // $crm_data['crm_not_attended'] = History::where(['sub_stage' => 'crm_interview_not_attended', 'status' => 'active'])->where('history_added_date', $today->format('jS F Y'))->count();
        // $crm_data['crm_declined'] = History::where(['sub_stage' => 'crm_declined', 'status' => 'active'])->where('history_added_date', $today->format('jS F Y'))->count();
        
        // $crm_data['crm_date_started'] = History::whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_back'])
        //     ->whereIn('history.id', function ($query) {
        //         $query->select(DB::raw('MAX(h.id) FROM history as h WHERE ( h.sub_stage="crm_start_date" OR h.sub_stage="crm_start_date_back" ) AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
        //     })->where('history.history_added_date', $today->format('jS F Y'))->count();
        // $crm_data['crm_start_date_held'] = History::where(['sub_stage' => 'crm_start_date_hold', 'status' => 'active'])->where('history_added_date', $today->format('jS F Y'))->count();
        // $crm_data['crm_invoiced'] = History::whereIn('history.sub_stage', ['crm_invoice','crm_invoice_sent'])
        //     ->whereIn('history.id', function ($query) {
        //         $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage IN ("crm_invoice","crm_invoice_sent") AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
        //     })->where('history.history_added_date', $today->format('jS F Y'))->count();
        // $crm_data['crm_disputed'] = History::where(['sub_stage' => 'crm_dispute', 'status' => 'active'])->where('history_added_date', $today->format('jS F Y'))->count();
        // $crm_data['crm_paid'] = History::where('sub_stage', 'crm_paid')->where('history_added_date', $today->format('jS F Y'))->count();
        $crm_total['daily'] = 0;
        // $crm_total['daily'] = $crm_data['crm_sent'] + $crm_data['crm_rejected'] + $crm_data['crm_requested'] + $crm_data['crm_request_rejected'] + $crm_data['crm_confirmed'] + $crm_data['crm_prestart_attended'] + $crm_data['crm_not_attended'] + $crm_data['crm_date_started'] + $crm_data['crm_start_date_held'] + $crm_data['crm_invoiced'] + $crm_data['crm_disputed'] + $crm_data['crm_paid'];
        /*** /daily stats */

        /*** Weekly Stats
         * Applicants */
        $start_of_week_date = $today->copy()->startOfWeek(Carbon::MONDAY);
        $end_of_week_date = $today->copy()->endOfWeek(Carbon::SUNDAY);

        $weekly_data['no_of_nurses'] = 0;
        $weekly_data['no_of_non_nurses'] = 0;
        $weekly_data['no_of_callbacks'] = 0;
        $weekly_data['no_of_not_interested'] = 0;
		
		$weekly_data['no_of_nurses_weekly_update'] = 0;
        $weekly_data['no_of_non_nurses_weekly_update'] = 0;
        $weekly_data['no_of_callbacks_weekly_update'] = 0;
        $weekly_data['no_of_not_interested_weekly_update'] = 0;

        // $weekly_data['no_of_nurses'] = Applicant::where([ 'status' => 'active', 'job_category' => 'nurse' ])->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $weekly_data['no_of_non_nurses'] = Applicant::where([ 'status' => 'active', 'job_category' => 'non-nurse' ])->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $weekly_data['no_of_callbacks'] = ApplicantNote::where('moved_tab_to', '=', 'callback')->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $weekly_data['no_of_not_interested'] = Applicant::join('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
        //     ->where([ 'applicants.status' => 'active', 'applicants_pivot_sales.is_interested' => 'no' ])->whereBetween('applicants_pivot_sales.created_at', [$start_of_week_date, $end_of_week_date])->count();

        /*** Sales */

        $sales['weekly_open_sales'] = 0;
        $sales['weekly_close_sales'] = 0;
        $sales['weekly_psl_offices'] = 0;
        $sales['weekly_non_psl_offices'] = 0;

        // $sales['weekly_open_sales'] = Sale::where(['status' => 'active'])->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $sales['weekly_close_sales'] = Audit::where(['message' => 'sale-closed', 'auditable_type' => 'Horsefly\\Sale'])->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $sales['weekly_psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'psl'])->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $sales['weekly_non_psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'non psl'])->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();

        /*** Quality */

        $quality['weekly_cvs'] = 0;
        $quality['weekly_cvs_cleared'] = 0;
        $quality['weekly_cvs_rejected'] = 0;

        // $quality['weekly_cvs'] = Cv_note::whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $quality['weekly_cvs_cleared'] = History::where(['sub_stage' => 'quality_cleared'])->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $quality['weekly_cvs_rejected'] = History::where(['sub_stage' => 'quality_reject', 'status' => 'active'])->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();

        /*** CRM */

        $weekly_crm_data["Crm Stage"] = 0;
        $weekly_crm_data["crm_sent"] = 0;
        $weekly_crm_data["crm_rejected"] = 0;
        $weekly_crm_data["crm_requested"] = 0;
        $weekly_crm_data["crm_request_rejected"] = 0;
        $weekly_crm_data["crm_confirmed"] = 0;
        $weekly_crm_data["crm_prestart_attended"] = 0;
        $weekly_crm_data["crm_rebook"] = 0;
        $weekly_crm_data["crm_not_attended"] = 0;
        $weekly_crm_data["crm_declined"] = 0;
        $weekly_crm_data["crm_date_started"] = 0;
        $weekly_crm_data["crm_start_date_held"] = 0;
        $weekly_crm_data["crm_invoiced"] = 0;
        $weekly_crm_data["crm_disputed"] = 0;
        $weekly_crm_data["crm_paid"] = 0;
		$weekly_crm_data["quality_revert"] = 0;
        $weekly_crm_data["crm_revert"] = 0;

        // $weekly_crm_data["Crm Stage"] = "Number of Applicants";
        // $weekly_crm_data['crm_sent'] = $quality['weekly_cvs_cleared'];
        // $weekly_crm_data['crm_rejected'] = History::where(['sub_stage' => 'crm_reject', 'status' => 'active'])->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $weekly_crm_data['crm_requested'] = History::where('history.sub_stage', 'crm_request')
        //     ->whereIn('history.id', function ($query) use ($start_of_week_date, $end_of_week_date) {
        //         $query->select(DB::raw('MAX(h.id) FROM history AS h 
        //         WHERE h.sub_stage = "crm_request" 
        //         AND history.applicant_id = h.applicant_id 
        //         AND history.sale_id = h.sale_id 
        //         AND history.id > ( 
        //             SELECT MAX(hh.id) FROM history AS hh 
        //             WHERE ( hh.sub_stage = "quality_cleared" OR hh.sub_stage = "crm_save" ) 
        //             AND history.applicant_id = hh.applicant_id AND history.sale_id = hh.sale_id 
        //         )'));
        //     })->whereBetween('history.created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $weekly_crm_data['crm_request_rejected'] = History::where(['sub_stage' => 'crm_request_reject', 'status' => 'active'])->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();

        // $weekly_crm_data['crm_confirmed'] = History::where('history.sub_stage', "crm_request_confirm")
        //     ->whereIn('history.id', function ($query) use ($start_of_week_date, $end_of_week_date) {
        //         $query->select(DB::raw('MAX(h.id) FROM history AS h 
        //         WHERE h.sub_stage = "crm_request_confirm" 
	    //         AND history.applicant_id = h.applicant_id 
	    //         AND history.sale_id = h.sale_id 
	    //         AND history.id > ( 
		//             SELECT MAX(hh.id) FROM history AS hh 
		//             WHERE hh.sub_stage = "crm_request" 
		//             AND history.applicant_id = hh.applicant_id 
		//             AND history.sale_id = hh.sale_id 
        //     	)'));
        //     })->whereBetween('history.created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $weekly_crm_data['crm_prestart_attended'] = History::where('history.sub_stage', 'crm_interview_attended')
        //     ->whereIn('history.id', function ($query) {
        //         $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_interview_attended" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
        //     })->whereBetween('history.created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $weekly_crm_data['crm_rebook'] = History::where('history.sub_stage', "crm_rebook")
        //     ->whereIn('history.id', function ($query) {
        //     $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
        //     })->whereBetween('history.created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $weekly_crm_data['crm_not_attended'] = History::where(['sub_stage' => 'crm_interview_not_attended', 'status' => 'active'])->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $weekly_crm_data['crm_declined'] = History::where(['sub_stage' => 'crm_declined', 'status' => 'active'])->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();

        // $weekly_crm_data['crm_date_started'] = History::whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_back'])
        //     ->whereIn('history.id', function ($query) {
        //         $query->select(DB::raw('MAX(h.id) FROM history as h WHERE ( h.sub_stage="crm_start_date" OR h.sub_stage="crm_start_date_back" ) AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
        //     })->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $weekly_crm_data['crm_start_date_held'] = History::where(['sub_stage' => 'crm_start_date_hold', 'status' => 'active'])->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $weekly_crm_data['crm_invoiced'] = History::whereIn('history.sub_stage', ['crm_invoice','crm_invoice_sent'])
        //     ->whereIn('history.id', function ($query) {
        //         $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage IN ("crm_invoice","crm_invoice_sent") AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
        //     })->whereBetween('history.created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $weekly_crm_data['crm_disputed'] = History::where(['sub_stage' => 'crm_dispute', 'status' => 'active'])->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $weekly_crm_data['crm_paid'] = History::where('sub_stage', 'crm_paid')->whereBetween('created_at', [$start_of_week_date, $end_of_week_date])->count();
        // $crm_total['weekly'] = $weekly_crm_data['crm_sent'] + $weekly_crm_data['crm_rejected'] + $weekly_crm_data['crm_requested'] + $weekly_crm_data['crm_request_rejected'] + $weekly_crm_data['crm_confirmed'] + $weekly_crm_data['crm_prestart_attended'] + $weekly_crm_data['crm_not_attended'] + $weekly_crm_data['crm_date_started'] + $weekly_crm_data['crm_start_date_held'] + $weekly_crm_data['crm_invoiced'] + $weekly_crm_data['crm_disputed'] + $weekly_crm_data['crm_paid'];
        /*** /weekly stats */

        /*** Monthly Stats
         * Applicants */

        // $current_month = $today->copy()->month;
        // $current_year = $today->copy()->year;

        $monthly_data['no_of_nurses'] = 0;
        $monthly_data['no_of_non_nurses'] = 0;
        $monthly_data['no_of_callbacks'] = 0;
        $monthly_data['no_of_not_interested'] = 0;
		
		$monthly_data['no_of_nurses_monthly_update'] = 0;
        $monthly_data['no_of_non_nurses_monthly_update'] = 0;
        $monthly_data['no_of_callbacks_monthly_update'] = 0;
        $monthly_data['no_of_not_interested_monthly_update'] = 0;

        // $monthly_data['no_of_nurses'] = Applicant::where([ 'status' => 'active', 'job_category' => 'nurse' ])->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
        // $monthly_data['no_of_non_nurses'] = Applicant::where([ 'status' => 'active', 'job_category' => 'non-nurse' ])->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
        // $monthly_data['no_of_callbacks'] = ApplicantNote::where('moved_tab_to', '=', 'callback')->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
        // $monthly_data['no_of_not_interested'] = Applicant::join('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
        //     ->where([ 'applicants.status' => 'active', 'applicants_pivot_sales.is_interested' => 'no' ])->whereMonth('applicants_pivot_sales.created_at', $current_month)->whereYear('applicants_pivot_sales.created_at', $current_year)->count();

        /*** Sales */

        $sales['monthly_open_sales'] = 0;
        $sales['monthly_close_sales'] = 0;
        $sales['monthly_psl_offices'] = 0;
        $sales['monthly_non_psl_offices'] = 0;

        // $sales['monthly_open_sales'] = Sale::where(['status' => 'active'])->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
        // $sales['monthly_close_sales'] = Audit::where(['message' => 'sale-closed', 'auditable_type' => 'Horsefly\\Sale'])->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
        // $sales['monthly_psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'psl'])->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
        // $sales['monthly_non_psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'non psl'])->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();

        /*** Quality */


        $quality['monthly_cvs'] = 0;
        $quality['monthly_cvs_cleared'] = 0;
        $quality['monthly_cvs_rejected'] = 0;

        // $quality['monthly_cvs'] = Cv_note::whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
        // $quality['monthly_cvs_cleared'] = History::where(['sub_stage' => 'quality_cleared'])->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
        // $quality['monthly_cvs_rejected'] = History::where(['sub_stage' => 'quality_reject', 'status' => 'active'])->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();

        /*** CRM */

        $monthly_crm_data["Crm Stage"] = 0;
        $monthly_crm_data["crm_sent"] = 0;
        $monthly_crm_data["crm_rejected"] = 0;
        $monthly_crm_data["crm_requested"] = 0;
        $monthly_crm_data["crm_request_rejected"] = 0;
        $monthly_crm_data["crm_confirmed"] = 0;
        $monthly_crm_data["crm_prestart_attended"] = 0;
        $monthly_crm_data["crm_rebook"] = 0;
        $monthly_crm_data["crm_not_attended"] = 0;
        $monthly_crm_data["crm_declined"] = 0;
        $monthly_crm_data["crm_date_started"] = 0;
        $monthly_crm_data["crm_start_date_held"] = 0;
        $monthly_crm_data["crm_invoiced"] = 0;
        $monthly_crm_data["crm_disputed"] = 0;
        $monthly_crm_data["crm_paid"] = 0;
		$monthly_crm_data["quality_revert"] = 0;
        $monthly_crm_data["crm_revert"] = 0;

        // $monthly_crm_data["Crm Stage"] = "Number of Applicants";
        // $monthly_crm_data['crm_sent'] = $quality['monthly_cvs_cleared'];
        // $monthly_crm_data['crm_rejected'] = History::where(['sub_stage' => 'crm_reject', 'status' => 'active'])->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();

        // $monthly_crm_data['crm_requested'] = History::where('history.sub_stage', 'crm_request')
        //     ->whereIn('history.id', function ($query) {
        //         $query->select(DB::raw('MAX(h.id) FROM history AS h 
        //         WHERE h.sub_stage = "crm_request" 
        //         AND history.applicant_id = h.applicant_id 
        //         AND history.sale_id = h.sale_id 
        //         AND history.id > ( 
        //             SELECT MAX(hh.id) FROM history AS hh 
        //             WHERE ( hh.sub_stage = "quality_cleared" OR hh.sub_stage = "crm_save" ) 
        //             AND history.applicant_id = hh.applicant_id AND history.sale_id = hh.sale_id 
        //         )'));
        //     })->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
        // $monthly_crm_data['crm_request_rejected'] = History::where(['sub_stage' => 'crm_request_reject', 'status' => 'active'])->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();

        // $monthly_crm_data['crm_confirmed'] = History::where('history.sub_stage', "crm_request_confirm")
        //     ->whereIn('history.id', function ($query) {
        //         $query->select(DB::raw('MAX(h.id) FROM history AS h 
        //         WHERE h.sub_stage = "crm_request_confirm" 
	    //         AND history.applicant_id = h.applicant_id 
	    //         AND history.sale_id = h.sale_id 
	    //         AND history.id > ( 
		//             SELECT MAX(hh.id) FROM history AS hh 
		//             WHERE hh.sub_stage = "crm_request" 
		//             AND history.applicant_id = hh.applicant_id 
		//             AND history.sale_id = hh.sale_id 
        //     	)'));
        //     })->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
        // $monthly_crm_data['crm_prestart_attended'] = History::where('history.sub_stage', 'crm_interview_attended')
        //     ->whereIn('history.id', function ($query) {
        //         $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_interview_attended" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
        //     })->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
        // $monthly_crm_data['crm_rebook'] = History::where('history.sub_stage', "crm_rebook")
        //     ->whereIn('history.id', function ($query) {
        //     $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
        //     })->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
        // $monthly_crm_data['crm_not_attended'] = History::where(['sub_stage' => 'crm_interview_not_attended', 'status' => 'active'])->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
        // $monthly_crm_data['crm_declined'] = History::where(['sub_stage' => 'crm_declined', 'status' => 'active'])->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();

        // $monthly_crm_data['crm_date_started'] = History::whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_back'])
        //     ->whereIn('history.id', function ($query) {
        //         $query->select(DB::raw('MAX(h.id) FROM history as h WHERE ( h.sub_stage="crm_start_date" OR h.sub_stage="crm_start_date_back" ) AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
        //     })->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
        // $monthly_crm_data['crm_start_date_held'] = History::where(['sub_stage' => 'crm_start_date_hold', 'status' => 'active'])->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
        // $monthly_crm_data['crm_invoiced'] = History::whereIn('history.sub_stage', ['crm_invoice','crm_invoice_sent'])
        //     ->whereIn('history.id', function ($query) {
        //         $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage IN ("crm_invoice","crm_invoice_sent") AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
        //     })->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
        // $monthly_crm_data['crm_disputed'] = History::where(['sub_stage' => 'crm_dispute', 'status' => 'active'])->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
        // $monthly_crm_data['crm_paid'] = History::where('sub_stage', 'crm_paid')->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
        // $crm_total['monthly'] = $monthly_crm_data['crm_sent'] + $monthly_crm_data['crm_rejected'] + $monthly_crm_data['crm_requested'] + $monthly_crm_data['crm_request_rejected'] + $monthly_crm_data['crm_confirmed'] + $monthly_crm_data['crm_prestart_attended'] + $monthly_crm_data['crm_not_attended'] + $monthly_crm_data['crm_date_started'] + $monthly_crm_data['crm_start_date_held'] + $monthly_crm_data['crm_invoiced'] + $monthly_crm_data['crm_disputed'] + $monthly_crm_data['crm_paid'];
        //monthly stats

        //Custom Range Stats Applicants
        $custom_data['sdate'] = $start_date = new Carbon('2022-06-27 00:00:00');
        $custom_data['edate'] = $end_date = $today->copy()->endOfDay();

        $custom_data['no_of_nurses'] = 0;
        $custom_data['no_of_non_nurses'] = 0;
        $custom_data['no_of_callbacks'] = 0;
        $custom_data['no_of_not_interested'] = 0;

        //Sales
        $sales['custom_open_sales'] = 0;
        $sales['custom_close_sales'] =0;
        $sales['custom_psl_offices'] = 0;
        $sales['custom_non_psl_offices'] = 0;

        //Quality

        $quality['custom_cvs'] =0;
        $quality['custom_cvs_rejected'] = 0;
        $quality['custom_cvs_cleared'] = 0;
		

       //CRM 
        $custom_crm_data["Crm Stage"] = "Number of Applicants";
        $custom_crm_data['crm_sent'] = $quality['custom_cvs_cleared'];
		//dd($custom_crm_data['crm_sent']);

        $custom_crm_data['crm_rejected'] = 0;

        $custom_crm_data['crm_requested'] = 0;
        $custom_crm_data['crm_request_rejected'] = 0;
		//dd($custom_crm_data['crm_requested']);

        $custom_crm_data['crm_confirmed'] = 0;
        $custom_crm_data['crm_prestart_attended'] = 0;
        $custom_crm_data['crm_not_attended'] = 0;
		$custom_crm_data['crm_rebook']=0;
        $custom_crm_data['crm_declined'] = 0;
        $custom_crm_data['crm_date_started'] = 0;
        $custom_crm_data['crm_start_date_held'] = 0;
        $custom_crm_data['crm_invoiced'] = 0;
        $custom_crm_data['crm_disputed'] = 0;
        $custom_crm_data['crm_paid'] = 0;
        $crm_total['custom'] = $custom_crm_data['crm_sent'] + $custom_crm_data['crm_rejected'] + $custom_crm_data['crm_requested'] + $custom_crm_data['crm_request_rejected'] + $custom_crm_data['crm_confirmed'] + $custom_crm_data['crm_prestart_attended'] + $custom_crm_data['crm_not_attended'] + $custom_crm_data['crm_date_started'] + $custom_crm_data['crm_start_date_held'] + $custom_crm_data['crm_invoiced'] + $custom_crm_data['crm_disputed'] + $custom_crm_data['crm_paid'];
	//dd($crm_total['custom']);
       //custom range stats 
		//empty variable for view
//$custom_crm_data = '';
		 $custom_data['sdate'] = $start_date = new Carbon('2019-06-27 00:00:00');
        $custom_data['edate'] = $end_date = $today->copy()->endOfDay();
		//$crm_total = '';

        $donut_colors['daily'] = [
            '#0b5baf','#0e78e6','#0d6fd4','#2a8cf2',
            '#1782f1','#2a8cf2','#469cf3','#5ca9f6',
            '#81bcf7','#458cd3','#86bef8','#73b4f6',
            '#97c9f8','#98c8f9'
        ];
        $donut_colors['weekly'] = [
            '#036d62','#059c8d','#168d81','#15b4a4',
            '#28ada2','#38bdaf','#22c7b6','#3ad1c2',
            '#65e6d9','#48c3b7','#4fd8ca','#5bd1c5',
            '#8cf8ed','#76ddd3'
        ];
        $donut_colors['monthly'] = [
            '#9e0e4f','#ce0e64','#b31b5d','#df317c',
            '#d3347b','#dd5190','#ec4590','#f0609e',
            '#f690bd','#e06d9f','#f376ae','#e795ba',
            '#f6b8d4','#e7afc8'
        ];
        $donut_colors['custom'] = [
            '#303140','#383a4b','#535468','#494b62',
            '#646580','#717392','#565974','#626483',
            '#7d80a5','#8587aa','#757798','#9da0c2',
            '#a7a8cf','#b6b8db'
        ];

        $users = User::where(["is_admin" => 0])->where('is_active','1')->get();

        return view('home', compact('applicant_data', 'no_of_units', 'resource', 'sales', 'quality', 'crm_data', 'weekly_crm_data', 'monthly_crm_data', 'custom_crm_data', 'crm_total', 'weekly_data', 'donut_colors', 'monthly_data', 'custom_data', 'users'));
    }

    public function dailyStats($date)
    {
        $validator = Validator::make(['date' => $date], ['date' => 'required|date_format:d-m-Y']);

        if ($validator->passes()) {

            $formatted_date = Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
            $d_date = Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
			
            $daily_data['no_of_nurses'] = Applicant::where([ 'status' => 'active', 'job_category' => 'nurse' ])
				->whereDate('created_at', $formatted_date)
				->count();
			
            $daily_data['no_of_non_nurses'] = Applicant::where([ 'status' => 'active'])
				->whereIn( 'job_category',['non-nurse','nursery'])
				->whereDate('created_at', $formatted_date)
				->count();
			
            $daily_data['no_of_callbacks'] = ApplicantNote::where('moved_tab_to', '=', 'callback')
				->whereDate('created_at', $formatted_date)
				->count();
			
            $daily_data['no_of_not_interested'] = Applicant::join('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
                ->where([ 'applicants.status' => 'active', 'applicants_pivot_sales.is_interested' => 'no' ])
				->whereDate('applicants_pivot_sales.created_at', $formatted_date)
				->count();
			
            $daily_data['daily_date_string'] = Carbon::createFromFormat('d-m-Y', $date)->toFormattedDateString();
			
//            $daily_data['no_of_revert_callbacks'] = Quality_notes::where('moved_tab_to', '=', 'revert_to_callback')->whereDate('created_at', $formatted_date)->count();
//            $daily_data['no_of_no_nursing_home'] = Quality_notes::where('moved_tab_to', '=', 'no_nursing_home')->whereDate('created_at', $formatted_date)->count();
			
			 $daily_data['no_of_nurses_update'] = Applicant::where([ 'status' => 'active', 'job_category' => 'nurse' ])
				 ->whereDate('updated_at', $formatted_date)
				 ->whereColumn('updated_at', '!=', 'created_at')
				 ->count();
			
            $daily_data['no_of_non_nurses_update'] = Applicant::where([ 'status' => 'active'])
				->whereIn( 'job_category',['non-nurse','nursery'])
				->whereDate('updated_at', $formatted_date)
				->whereColumn('updated_at', '!=', 'created_at')
				->count();
			
            $daily_data['no_of_callbacks_update'] = ApplicantNote::where('moved_tab_to', '=', 'callback')
				->whereDate('updated_at', $formatted_date)
				->whereColumn('updated_at', '!=', 'created_at')
				->count();
			
            $daily_data['no_of_not_interested_update'] = Applicant::join('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
                ->where([ 'applicants.status' => 'active', 'applicants_pivot_sales.is_interested' => 'no' ])
				->whereDate('applicants_pivot_sales.created_at', $formatted_date)
				->whereColumn('applicants_pivot_sales.updated_at', '!=', 'applicants_pivot_sales.created_at')
				->count();
                     
            /*** Sales */
            $daily_data['open_sales'] = Sale::where(['status' => 'active'])->whereDate('created_at', $formatted_date)->count();
            $daily_data['close_sales'] = Audit::where(['message' => 'sale-closed', 'auditable_type' => 'Horsefly\\Sale'])->whereDate('updated_at', $formatted_date)->count();
            $daily_data['psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'psl'])->whereDate('created_at', $formatted_date)->count();
            $daily_data['non_psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'non psl'])->whereDate('created_at', $formatted_date)->count();
			
			 /*** Sales  update*/
            $daily_data['open_sales_update'] = Sale::where(['status' => 'active'])
				->whereDate('updated_at', $formatted_date)
				->whereColumn('updated_at', '!=', 'created_at')
				->count();
			
            $daily_data['close_sales_update'] = Audit::where(['message' => 'sale-closed', 'auditable_type' => 'Horsefly\\Sale'])
				->whereDate('updated_at', $formatted_date)
				->count();

            $daily_data['psl_offices_update'] = Office::where(['status' => 'active', 'office_type' => 'psl'])
				->whereDate('updated_at', $formatted_date)
				->whereColumn('updated_at', '!=', 'created_at')
				->count();
			
            $daily_data['non_psl_offices_update'] = Office::where(['status' => 'active', 'office_type' => 'non psl'])
				->whereDate('updated_at', $formatted_date)
				->whereColumn('updated_at', '!=', 'created_at')
				->count();

            /*** Quality */
            $formatted_date = Carbon::createFromFormat('d-m-Y', $date)->format('jS F Y');
//            $daily_data['quality_cvs'] = History::where(['stage' => 'quality', 'sub_stage' => 'quality_cvs'])->where('history_added_date', $formatted_date)->count();
            $daily_data['quality_cvs'] = Cv_note::where('send_added_date', $formatted_date)->count();
			
            $daily_data['quality_cvs_rejected'] = History::where(['sub_stage' => 'quality_reject', 'status' => 'active'])
				->where('history_added_date', $formatted_date)->count();
			
            $daily_data['quality_cvs_cleared'] = History::where('sub_stage', 'quality_cleared')
				->where('history_added_date', $formatted_date)->count();
			
			$daily_data['quality_cvs_hold'] = History::where('sub_stage', 'quality_cvs_hold')
				->whereDate('created_at', $d_date)
				->count();

            /*** CRM */
//            $daily_data['crm_sent'] = Crm_note::where('moved_tab_to', '=', 'cv_sent')->whereIn('crm_notes.id', function($query){ $query->select(DB::raw('MAX(id) FROM crm_notes as c WHERE c.moved_tab_to = "cv_sent" AND c.applicant_id=crm_notes.applicant_id AND c.sales_id=crm_notes.sales_id')); })->where('crm_added_date', $formatted_date)->count();
            $daily_data['crm_sent'] = $daily_data['quality_cvs_cleared'];
			$daily_data['crm_open_cvs'] = $daily_data['quality_cvs_hold'];
            $daily_data['crm_rejected'] = History::where(['sub_stage' => 'crm_reject', 'status' => 'active'])->where('history_added_date', $formatted_date)->count();
            /***
            $daily_data['crm_requested'] = History::where('history.sub_stage', 'crm_request')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_request" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->where('history.history_added_date', $formatted_date)->count();
            */
            $daily_data['crm_requested'] = History::where('history.sub_stage', 'crm_request')
                ->whereIn('history.id', function ($query) use ($formatted_date) {
                    $query->select(DB::raw('MAX(h.id) FROM history AS h 
                    WHERE h.sub_stage = "crm_request" 
                    AND history.applicant_id = h.applicant_id 
                    AND history.sale_id = h.sale_id 
                    AND history.id > ( 
                        SELECT MAX(hh.id) FROM history AS hh 
                        WHERE ( hh.sub_stage = "quality_cleared" OR hh.sub_stage = "crm_save" ) 
                        AND history.applicant_id = hh.applicant_id AND history.sale_id = hh.sale_id 
                    )'));
                })->where('history.history_added_date', $formatted_date)->count();
			//dd( $daily_data['crm_requested']);
            $daily_data['crm_request_rejected'] = History::where(['sub_stage' => 'crm_request_reject', 'status' => 'active'])->where('history_added_date', $formatted_date)->count();
            /***
            $daily_data['crm_confirmed'] = History::where('history.sub_stage', 'crm_request_confirm')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_request_confirm" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->where('history.history_added_date', $formatted_date)->count();
            */
            $daily_data['crm_confirmed'] = History::where('history.sub_stage', "crm_request_confirm")
                ->whereIn('history.id', function ($query) use ($formatted_date) {
                    $query->select(DB::raw('MAX(h.id) FROM history AS h 
                    WHERE h.sub_stage = "crm_request_confirm" 
                    AND history.applicant_id = h.applicant_id 
                    AND history.sale_id = h.sale_id 
                    AND history.id > ( 
                        SELECT MAX(hh.id) FROM history AS hh 
                        WHERE hh.sub_stage = "crm_request" 
                        AND history.applicant_id = hh.applicant_id 
                        AND history.sale_id = hh.sale_id 
                    )'));
                })
				->where('history.history_added_date', $formatted_date)->count();
			//dd($daily_data['crm_confirmed']);
                // rebook daily stat
                $daily_data['crm_prestart_attended'] = History::where('history.sub_stage', 'crm_interview_attended')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_interview_attended" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->where('history.history_added_date', $formatted_date)->count();
                $daily_data['crm_rebook'] = History::where('history.sub_stage', "crm_rebook")
                ->whereIn('history.id', function ($query) {
                $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->where('history.history_added_date', $formatted_date)->count();
            
               
            $daily_data['crm_not_attended'] = History::where(['sub_stage' => 'crm_interview_not_attended', 'status' => 'active'])->where('history_added_date', $formatted_date)->count();
            $daily_data['crm_declined'] = History::where(['sub_stage' => 'crm_declined', 'status' => 'active'])->where('history_added_date', $formatted_date)->count();
            //            $daily_data['crm_date_started'] = History::whereIn('history.sub_stage', ['crm_start_date'])->where('history.history_added_date', $formatted_date)->count();
            $daily_data['crm_date_started'] = History::whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_back'])
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE ( h.sub_stage="crm_start_date" OR h.sub_stage="crm_start_date_back" ) AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->where('history.history_added_date', $formatted_date)->count();
			
            $daily_data['crm_start_date_held'] = History::where(['sub_stage' => 'crm_start_date_hold', 'status' => 'active'])->where('history_added_date', $formatted_date)->count();
            $daily_data['crm_invoiced'] = History::where('history.sub_stage', 'crm_invoice')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_invoice" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->where('history.history_added_date', $formatted_date)->count();
            $daily_data['crm_disputed'] = History::where(['sub_stage' => 'crm_dispute', 'status' => 'active'])->where('history_added_date', $formatted_date)->count();
            $daily_data['crm_paid'] = History::where('sub_stage', 'crm_paid')->where('history_added_date', $formatted_date)->count();
            $daily_data['crm_total'] = $daily_data['crm_sent'] + $daily_data['crm_rejected'] + $daily_data['crm_requested'] + $daily_data['crm_request_rejected'] + $daily_data['crm_confirmed'] + $daily_data['crm_prestart_attended'] + $daily_data['crm_not_attended'] + $daily_data['crm_date_started'] + $daily_data['crm_start_date_held'] + $daily_data['crm_invoiced'] + $daily_data['crm_disputed'] + $daily_data['crm_paid'];
			
			  $daily_data['quality_revert']=RevertStage::where('revert_added_date', $formatted_date)->where('stage','quality_revert')->count();
            $daily_data['crm_revert']=RevertStage::where('revert_added_date', $formatted_date)->where('stage','crm_revert')->count();


            return response()->json(['daily_data' => $daily_data]);
        }
        return response()->json(['error' => $validator->errors()->all()]);
    }

    public function weeklyStats($start_date, $end_date)
    {
        $validator = Validator::make(['sdate' => $start_date, 'edate' => $end_date], [
            'sdate' => 'required|date_format:d-m-Y',
            'edate' => 'required|date_format:d-m-Y'
            ]
        );

        if ($validator->passes()) {

            $formatted_start_date = Carbon::parse($start_date);
            $formatted_end_date = Carbon::parse($end_date)->endOfDay();

            $weekly_data['no_of_nurses'] = Applicant::where([ 'status' => 'active', 'job_category' => 'nurse' ])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['no_of_non_nurses'] = Applicant::where([ 'status' => 'active' ])->whereIn( 'job_category',['non-nurse','nursery'])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['no_of_callbacks'] = ApplicantNote::where('moved_tab_to', '=', 'callback')->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['no_of_not_interested'] = Applicant::join('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
                ->where([ 'applicants.status' => 'active', 'applicants_pivot_sales.is_interested' => 'no' ])->whereBetween('applicants_pivot_sales.created_at', [$formatted_start_date, $formatted_end_date])->count();
			
			
			//old applicant update
			 $weekly_data['no_of_nurses_weekly_update'] = Applicant::where([ 'status' => 'active', 'job_category' => 'nurse' ])->whereBetween('updated_at', [$formatted_start_date, $formatted_end_date])->whereColumn('updated_at', '!=', 'created_at')->count();
            $weekly_data['no_of_non_nurses_weekly_update'] = Applicant::where([ 'status' => 'active' ])->whereIn( 'job_category',['non-nurse','nursery'])->whereBetween('updated_at', [$formatted_start_date, $formatted_end_date])->whereColumn('updated_at', '!=', 'created_at')->count();
            $weekly_data['no_of_callbacks_weekly_update'] = ApplicantNote::where('moved_tab_to', '=', 'callback')->whereBetween('updated_at', [$formatted_start_date, $formatted_end_date])->whereColumn('updated_at', '!=', 'created_at')->count();
            $weekly_data['no_of_not_interested_weekly_update'] = Applicant::join('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
                ->where([ 'applicants.status' => 'active', 'applicants_pivot_sales.is_interested' => 'no' ])->whereBetween('applicants_pivot_sales.updated_at', [$formatted_start_date, $formatted_end_date])->whereColumn('applicants_pivot_sales.updated_at', '!=', 'applicants_pivot_sales.created_at')->count();
            
			
//            $weekly_data['no_of_revert_callbacks'] = Quality_notes::where('moved_tab_to', '=', 'revert_to_callback')->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
//            $weekly_data['no_of_no_nursing_home'] = Quality_notes::where('moved_tab_to', '=', 'no_nursing_home')->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();

            /*** Sales */
            $weekly_data['open_sales'] = Sale::where(['status' => 'active'])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['close_sales'] = Audit::where(['message' => 'sale-closed', 'auditable_type' => 'Horsefly\\Sale'])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'psl'])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['non_psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'non psl'])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();

            /*** Quality */
//            $weekly_data['cvs'] = History::where(['stage' => 'quality', 'sub_stage' => 'quality_cvs'])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['cvs'] = Cv_note::whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['cvs_rejected'] = History::where(['sub_stage' => 'quality_reject', 'status' => 'active'])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['cvs_cleared'] = History::where(['sub_stage' => 'quality_cleared'])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();

            /*** CRM */
            $weekly_data['crm_sent'] = $weekly_data['cvs_cleared'];
            $weekly_data['crm_rejected'] = History::where(['sub_stage' => 'crm_reject', 'status' => 'active'])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
            /***
            $weekly_data['crm_requested'] = History::where('history.sub_stage', 'crm_request')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_request" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereBetween('history.created_at', [$formatted_start_date, $formatted_end_date])->count();
            */
            $weekly_data['crm_requested'] = History::where('history.sub_stage', 'crm_request')
                ->whereIn('history.id', function ($query) use ($formatted_start_date, $formatted_end_date) {
                    $query->select(DB::raw('MAX(h.id) FROM history AS h 
                    WHERE h.sub_stage = "crm_request" 
                    AND history.applicant_id = h.applicant_id 
                    AND history.sale_id = h.sale_id 
                    AND history.id > ( 
                        SELECT MAX(hh.id) FROM history AS hh 
                        WHERE ( hh.sub_stage = "quality_cleared" OR hh.sub_stage = "crm_save" ) 
                        AND history.applicant_id = hh.applicant_id AND history.sale_id = hh.sale_id 
                    )'));
                })->whereBetween('history.created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['crm_request_rejected'] = History::where(['sub_stage' => 'crm_request_reject', 'status' => 'active'])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
            /***
            $weekly_data['crm_confirmed'] = History::where('history.sub_stage', 'crm_request_confirm')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_request_confirm" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereBetween('history.created_at', [$formatted_start_date, $formatted_end_date])->count();
            */
            $weekly_data['crm_confirmed'] = History::where('history.sub_stage', "crm_request_confirm")
                ->whereIn('history.id', function ($query) use ($formatted_start_date, $formatted_end_date) {
                    $query->select(DB::raw('MAX(h.id) FROM history AS h 
                    WHERE h.sub_stage = "crm_request_confirm" 
                    AND history.applicant_id = h.applicant_id 
                    AND history.sale_id = h.sale_id
                    AND history.id > ( 
                        SELECT MAX(hh.id) FROM history AS hh 
                        WHERE hh.sub_stage = "crm_request" 
                        AND history.applicant_id = hh.applicant_id 
                        AND history.sale_id = hh.sale_id 
                    )'));
                })->whereBetween('history.created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['crm_prestart_attended'] = History::where('history.sub_stage', 'crm_interview_attended')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_interview_attended" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereBetween('history.created_at', [$formatted_start_date, $formatted_end_date])->count();
                $weekly_crm_data['crm_rebook'] = History::where('history.sub_stage', "crm_rebook")
                ->whereIn('history.id', function ($query) {
                $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereBetween('history.created_at', [$formatted_start_date, $formatted_end_date])->count();
    
                $weekly_data['crm_not_attended'] = History::where(['sub_stage' => 'crm_interview_not_attended', 'status' => 'active'])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
                $weekly_crm_data['crm_declined'] = History::where(['sub_stage' => 'crm_declined', 'status' => 'active'])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();

                //            $weekly_data['crm_date_started'] = History::whereIn('history.sub_stage', ['crm_start_date'])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['crm_date_started'] = History::whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_back'])
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE ( h.sub_stage="crm_start_date" OR h.sub_stage="crm_start_date_back" ) AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereBetween('history.created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['crm_start_date_held'] = History::where(['sub_stage' => 'crm_start_date_hold', 'status' => 'active'])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['crm_invoiced'] = History::where('history.sub_stage', 'crm_invoice')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_invoice" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereBetween('history.created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['crm_disputed'] = History::where(['sub_stage' => 'crm_dispute', 'status' => 'active'])->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['crm_paid'] = History::where('sub_stage', 'crm_paid')->whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->count();
            $weekly_data['crm_total'] = $weekly_data['crm_sent'] + $weekly_data['crm_rejected'] + $weekly_data['crm_requested'] + $weekly_data['crm_request_rejected'] + $weekly_data['crm_confirmed'] + $weekly_data['crm_prestart_attended'] + $weekly_data['crm_not_attended'] + $weekly_data['crm_date_started'] + $weekly_data['crm_start_date_held'] + $weekly_data['crm_invoiced'] + $weekly_data['crm_disputed'] + $weekly_data['crm_paid'];
			
			  $weekly_data['quality_revert']=RevertStage::whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->where('stage','quality_revert')->count();
            $weekly_data['crm_revert']= RevertStage::whereBetween('created_at', [$formatted_start_date, $formatted_end_date])->where('stage','crm_revert')->count();


            return response()->json(['weekly_data' => $weekly_data]);
        }
        return response()->json(['error' => $validator->errors()->all()]);
    }

    public function monthlyStats($month, $year)
    {
        $validator = Validator::make(['month' => $month, 'year' => $year],
            ['month' => 'required|date_format:m', 'year' => 'required|date_format:Y']
        );
        $month = ($month == '10') ? $month : trim($month,"0");

        if ($validator->passes()) {

            $monthly_data['no_of_nurses'] = Applicant::where([ 'status' => 'active', 'job_category' => 'nurse' ])->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            $monthly_data['no_of_non_nurses'] = Applicant::where([ 'status' => 'active' ])->whereIn( 'job_category',['non-nurse','nursery'])->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            $monthly_data['no_of_callbacks'] = ApplicantNote::where('moved_tab_to', '=', 'callback')->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            $monthly_data['no_of_not_interested'] = Applicant::join('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
                ->where([ 'applicants.status' => 'active', 'applicants_pivot_sales.is_interested' => 'no' ])->whereMonth('applicants_pivot_sales.created_at', $month)->whereYear('applicants_pivot_sales.created_at', $year)->count();
			
			  /*** update applicant */
            $monthly_data['no_of_nurses_monthly_update'] = Applicant::where([ 'status' => 'active', 'job_category' => 'nurse' ])->whereMonth('updated_at', $month)->whereYear('updated_at', $year)->whereColumn('updated_at', '!=', 'created_at')->count();
            $monthly_data['no_of_non_nurses_monthly_update'] = Applicant::where([ 'status' => 'active' ])->whereIn( 'job_category',['non-nurse','nursery'])->whereMonth('updated_at', $month)->whereYear('updated_at', $year)->whereColumn('updated_at', '!=', 'created_at')->count();
            $monthly_data['no_of_callbacks_monthly_update'] = ApplicantNote::where('moved_tab_to', '=', 'callback')->whereMonth('updated_at', $month)->whereYear('updated_at', $year)->whereColumn('updated_at', '!=', 'created_at')->count();
            $monthly_data['no_of_not_interested_monthly_update'] = Applicant::join('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
                ->where([ 'applicants.status' => 'active', 'applicants_pivot_sales.is_interested' => 'no' ])->whereMonth('applicants_pivot_sales.updated_at', $month)->whereYear('applicants_pivot_sales.updated_at', $year)->whereColumn('applicants_pivot_sales.updated_at', '!=', 'applicants_pivot_sales.created_at')->count();
//dd($monthly_data['no_of_not_interested_monthly_update']);

            /*** Sales */
            $monthly_data['open_sales'] = Sale::where(['status' => 'active'])->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            $monthly_data['close_sales'] = Audit::where(['message' => 'sale-closed', 'auditable_type' => 'Horsefly\\Sale'])->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            $monthly_data['psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'psl'])->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            $monthly_data['non_psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'non psl'])->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();

            /*** Quality */
//            $monthly_data['quality_cvs'] = History::where(['stage' => 'quality', 'sub_stage' => 'quality_cvs'])->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            $monthly_data['quality_cvs'] = Cv_note::whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            $monthly_data['quality_cvs_rejected'] = History::where(['sub_stage' => 'quality_reject', 'status' => 'active'])->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            $monthly_data['quality_cvs_cleared'] = History::where(['sub_stage' => 'quality_cleared'])->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();


           
            /*** CRM */
            $monthly_data['crm_sent'] = $monthly_data['quality_cvs_cleared'];
            $monthly_data['crm_rejected'] = History::where(['sub_stage' => 'crm_reject', 'status' => 'active'])->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            /***
            $monthly_data['crm_requested'] = History::where('history.sub_stage', 'crm_request')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_request" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereMonth('history.created_at', $month)->whereYear('history.created_at', $year)->count();
            */
            $monthly_data['crm_requested'] = History::where('history.sub_stage', 'crm_request')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history AS h 
                    WHERE h.sub_stage = "crm_request" 
                    AND history.applicant_id = h.applicant_id 
                    AND history.sale_id = h.sale_id 
                    AND history.id > ( 
                        SELECT MAX(hh.id) FROM history AS hh 
                        WHERE ( hh.sub_stage = "quality_cleared" OR hh.sub_stage = "crm_save" ) 
                        AND history.applicant_id = hh.applicant_id AND history.sale_id = hh.sale_id 
                    )'));
                })->whereMonth('history.created_at', $month)->whereYear('history.created_at', $year)->count();
            $monthly_data['crm_request_rejected'] = History::where(['sub_stage' => 'crm_request_reject', 'status' => 'active'])->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            /***
            $monthly_data['crm_confirmed'] = History::where('history.sub_stage', 'crm_request_confirm')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_request_confirm" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereMonth('history.created_at', $month)->whereYear('history.created_at', $year)->count();
            */
            $monthly_data['crm_confirmed'] = History::where('history.sub_stage', "crm_request_confirm")
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history AS h 
                    WHERE h.sub_stage = "crm_request_confirm" 
                    AND history.applicant_id = h.applicant_id 
                    AND history.sale_id = h.sale_id 
                    AND history.id > ( 
                        SELECT MAX(hh.id) FROM history AS hh 
                        WHERE hh.sub_stage = "crm_request" 
                        AND history.applicant_id = hh.applicant_id 
                        AND history.sale_id = hh.sale_id 
                    )'));
                })->whereMonth('history.created_at', $month)->whereYear('history.created_at', $year)->count();
            $monthly_data['crm_prestart_attended'] = History::where('history.sub_stage', 'crm_interview_attended')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_interview_attended" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereMonth('history.created_at', $month)->whereYear('history.created_at', $year)->count();
                $custom_crm_data['crm_rebook'] = History::where('history.sub_stage', "crm_rebook")
                ->whereIn('history.id', function ($query) {
                $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereYear('history.created_at', $year)->count();
          $monthly_data['crm_rebook'] = History::where('history.sub_stage', "crm_rebook")
                ->whereIn('history.id', function ($query) {
                $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereMonth('history.created_at', $month)->whereYear('history.created_at', $year)->count();
			
                $monthly_data['crm_not_attended'] = History::where(['sub_stage' => 'crm_interview_not_attended', 'status' => 'active'])->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
                $custom_crm_data['crm_declined'] = History::where(['sub_stage' => 'crm_declined', 'status' => 'active'])->whereYear('history.created_at', $year)->count();
			$monthly_data['crm_declined'] = History::where(['sub_stage' => 'crm_declined', 'status' => 'active'])
                ->whereMonth('history.created_at', $month)->whereYear('history.created_at', $year)->count();	
			
                //            $monthly_data['crm_date_started'] = History::whereIn('history.sub_stage', ['crm_start_date'])->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            $monthly_data['crm_date_started'] = History::whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_back'])
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE ( h.sub_stage="crm_start_date" OR h.sub_stage="crm_start_date_back" ) AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereMonth('history.created_at', $month)->whereYear('history.created_at', $year)->count();
            $monthly_data['crm_start_date_held'] = History::where(['sub_stage' => 'crm_start_date_hold', 'status' => 'active'])->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            $monthly_data['crm_invoiced'] = History::where('history.sub_stage', 'crm_invoice')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_invoice" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereMonth('history.created_at', $month)->whereYear('history.created_at', $year)->count();
            $monthly_data['crm_disputed'] = History::where(['sub_stage' => 'crm_dispute', 'status' => 'active'])->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            $monthly_data['crm_paid'] = History::where('sub_stage', 'crm_paid')->whereMonth('created_at', $month)->whereYear('created_at', $year)->count();
            $monthly_data['crm_total'] = number_format($monthly_data['crm_sent'] + $monthly_data['crm_rejected'] + $monthly_data['crm_requested'] + $monthly_data['crm_request_rejected'] + $monthly_data['crm_confirmed'] + $monthly_data['crm_prestart_attended'] + $monthly_data['crm_not_attended'] + $monthly_data['crm_date_started'] + $monthly_data['crm_start_date_held'] + $monthly_data['crm_invoiced'] + $monthly_data['crm_disputed'] + $monthly_data['crm_paid']);
			
			            $monthly_data['quality_revert'] = RevertStage::whereMonth('created_at', $month)->whereYear('created_at', $year)->where('stage','quality_revert')->count();
            $monthly_data['crm_revert'] = RevertStage::whereMonth('created_at', $month)->whereYear('created_at', $year)->where('stage','crm_revert')->count();


            return response()->json(['monthly_data' => $monthly_data]);
        }
        return response()->json(['error' => $validator->errors()->all()]);
    }

    public function customStats($start_date, $end_date)
    {
        $validator = Validator::make(['sdate' => $start_date, 'edate' => $end_date],
            ['sdate' => 'required|date_format:d-m-Y', 'edate' => 'required|date_format:d-m-Y']
        );

        $start_date = Carbon::parse($start_date);
        $end_date = Carbon::parse($end_date)->endOfDay();

        $custom_data['sdate'] = $start_date->toFormattedDateString();
        $custom_data['edate'] = $end_date->toFormattedDateString();

        if ($validator->passes()) {

            $custom_data['no_of_nurses'] = Applicant::where([ 'status' => 'active', 'job_category' => 'nurse' ])->whereBetween('created_at', [$start_date, $end_date])->count();
            $custom_data['no_of_non_nurses'] = Applicant::where([ 'status' => 'active' ])->whereIn( 'job_category',['non-nurse','nursery'])->whereBetween('created_at', [$start_date, $end_date])->count();
            $custom_data['no_of_callbacks'] = ApplicantNote::where('moved_tab_to', '=', 'callback')->whereBetween('created_at', [$start_date, $end_date])->count();
            $custom_data['no_of_not_interested'] = Applicant::join('applicants_pivot_sales', 'applicants_pivot_sales.applicant_id', '=', 'applicants.id')
                ->where([ 'applicants.status' => 'active', 'applicants_pivot_sales.is_interested' => 'no' ])->whereBetween('applicants_pivot_sales.created_at', [$start_date, $end_date])->count();

            /*** Sales */
            $custom_data['open_sales'] = Sale::where(['status' => 'active'])->whereBetween('created_at', [$start_date, $end_date])->count();
            $custom_data['close_sales'] = Audit::where(['message' => 'sale-closed', 'auditable_type' => 'Horsefly\\Sale'])->whereBetween('created_at', [$start_date, $end_date])->count();
            $custom_data['psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'psl'])->whereBetween('created_at', [$start_date, $end_date])->count();
            $custom_data['non_psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'non psl'])->whereBetween('created_at', [$start_date, $end_date])->count();

            /*** Quality */
//            $custom_data['cvs'] = History::where(['stage' => 'quality', 'sub_stage' => 'quality_cvs'])->whereBetween('created_at', [$start_date, $end_date])->count();
            $custom_data['cvs'] = Cv_note::whereBetween('created_at', [$start_date, $end_date])->count();
            $custom_data['cvs_rejected'] = History::where(['sub_stage' => 'quality_reject', 'status' => 'active'])->whereBetween('created_at', [$start_date, $end_date])->count();
            $custom_data['cvs_cleared'] = History::where(['sub_stage' => 'quality_cleared'])->whereBetween('created_at', [$start_date, $end_date])->count();

            /*** CRM */
            $custom_data['crm_sent'] = $custom_data['cvs_cleared'];
            $custom_data['crm_rejected'] = History::where(['sub_stage' => 'crm_reject', 'status' => 'active'])->whereBetween('created_at', [$start_date, $end_date])->count();
            /***
            $custom_data['crm_requested'] = History::where('history.sub_stage', 'crm_request')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_request" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereBetween('history.created_at', [$start_date, $end_date])->count();
            */
            $custom_data['crm_requested'] = History::where('history.sub_stage', 'crm_request')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history AS h 
                    WHERE h.sub_stage = "crm_request" 
                    AND history.applicant_id = h.applicant_id 
                    AND history.sale_id = h.sale_id 
                    AND history.id > ( 
                        SELECT MAX(hh.id) FROM history AS hh 
                        WHERE ( hh.sub_stage = "quality_cleared" OR hh.sub_stage = "crm_save" ) 
                        AND history.applicant_id = hh.applicant_id AND history.sale_id = hh.sale_id 
                    )'));
                })->whereBetween('history.created_at', [$start_date, $end_date])->count();
            $custom_data['crm_request_rejected'] = History::where(['sub_stage' => 'crm_request_reject', 'status' => 'active'])->whereBetween('created_at', [$start_date, $end_date])->count();
            /***
            $custom_data['crm_confirmed'] = History::where('history.sub_stage', 'crm_request_confirm')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_request_confirm" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereBetween('history.created_at', [$start_date, $end_date])->count();
            */

             // crm_sent
                // crm_rejected
                // crm_requested
                // crm_request_rejected
                // crm_confirmed
                // crm_prestart_attended
                // crm_not_attended
                // crm_declined
                // crm_date_started
                // crm_start_date_held
                // crm_invoiced
                // crm_disputed
                // crm_paid
            $custom_data['crm_confirmed'] = History::where('history.sub_stage', "crm_request_confirm")
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history AS h 
                    WHERE h.sub_stage = "crm_request_confirm" 
                    AND history.applicant_id = h.applicant_id 
                    AND history.sale_id = h.sale_id 
                    AND history.id > ( 
                        SELECT MAX(hh.id) FROM history AS hh 
                        WHERE hh.sub_stage = "crm_request" 
                        AND history.applicant_id = hh.applicant_id 
                        AND history.sale_id = hh.sale_id 
                    )'));
                })->whereBetween('history.created_at', [$start_date, $end_date])->count();
            $custom_data['crm_prestart_attended'] = History::where('history.sub_stage', 'crm_interview_attended')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_interview_attended" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereBetween('history.created_at', [$start_date, $end_date])->count();
                $custom_crm_data['crm_rebook'] = History::where('history.sub_stage', "crm_rebook")
                ->whereIn('history.id', function ($query) {
                $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereBetween('history.created_at', [$start_date, $end_date])->count();
                $custom_data['crm_not_attended'] = History::where(['sub_stage' => 'crm_interview_not_attended', 'status' => 'active'])->whereBetween('created_at', [$start_date, $end_date])->count();
                $custom_crm_data['crm_declined'] = History::where(['sub_stage' => 'crm_declined', 'status' => 'active'])->whereBetween('history.created_at', [$start_date, $end_date])->count();

                //            $custom_data['crm_date_started'] = History::whereIn('history.sub_stage', ['crm_start_date'])->whereBetween('created_at', [$start_date, $end_date])->count();
            $custom_data['crm_date_started'] = History::whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_back'])
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE ( h.sub_stage="crm_start_date" OR h.sub_stage="crm_start_date_back" ) AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereBetween('history.created_at', [$start_date, $end_date])->count();
            $custom_data['crm_start_date_held'] = History::where(['sub_stage' => 'crm_start_date_hold', 'status' => 'active'])->whereBetween('created_at', [$start_date, $end_date])->count();
            $custom_data['crm_invoiced'] = History::where('history.sub_stage', 'crm_invoice')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_invoice" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                })->whereBetween('history.created_at', [$start_date, $end_date])->count();
            $custom_data['crm_disputed'] = History::where(['sub_stage' => 'crm_dispute', 'status' => 'active'])->whereBetween('created_at', [$start_date, $end_date])->count();
            $custom_data['crm_paid'] = History::where('sub_stage', 'crm_paid')->whereBetween('created_at', [$start_date, $end_date])->count();
            $custom_data['crm_total'] = number_format($custom_data['crm_sent'] + $custom_data['crm_rejected'] + $custom_data['crm_requested'] + $custom_data['crm_request_rejected'] + $custom_data['crm_confirmed'] + $custom_data['crm_prestart_attended'] + $custom_data['crm_not_attended'] + $custom_data['crm_date_started'] + $custom_data['crm_start_date_held'] + $custom_data['crm_invoiced'] + $custom_data['crm_disputed'] + $custom_data['crm_paid']);

            return response()->json(['custom_data' => $custom_data]);
        } else {
            return response()->json(['error' => $validator->errors()->all()]);
        }
    }

    public function callbackApplicants(Request $request)
    {
        if ($request->filled('app_daily_date')) {
            $request->session()->put('app_cb_daily_date', $request->input('app_daily_date'));
        }

        return view('administrator.dashboard.callback_applicants');
    }

    public function getCallbackApplicants(Request $request)
    {
        $date = Carbon::createFromFormat('d-m-Y', $request->input('app_cb_daily_date'))->format('Y-m-d');
        $callback_applicants = Applicant::where([ 'status' => 'active', 'is_callback_enable' => 'yes' ])->whereDate('updated_at', $date)->select('*');

        return datatables()->of($callback_applicants)
            ->addColumn('applicant_notes', function($applicants){

                $app_new_note = ModuleNote::where(['module_noteable_id' =>$applicants->id, 'module_noteable_type' =>'Horsefly\Applicant'])
                    ->select('module_notes.details')
                    ->orderBy('module_notes.id', 'DESC')
                    ->first();

                if($app_new_note){
                    $app_notes_final = $app_new_note->details;

                }
                else{
                    $app_notes_final = $applicants->applicant_notes;
                }

                return $app_notes_final;
            })
            ->addColumn('updated_by', function ($applicants) {

//                    $applicant = Applicant::with('audits')->find($applicants->id);
//                    $audit_data = []; $index = 0;
//                    foreach ($applicant->audits as $audit) {
//                        if (empty($audit->data['changes_made'])) {
//                            $audit_data['original_record']['applicant_job_title'] = $audit->data['applicant_job_title'];
//                            $audit_data['original_record']['applicant_name'] = $audit->data['applicant_name'];
//                            $audit_data['original_record']['applicant_email'] = $audit->data['applicant_email'];
//                            $audit_data['original_record']['applicant_postcode'] = $audit->data['applicant_postcode'];
//                            $audit_data['original_record']['applicant_phone'] = $audit->data['applicant_phone'];
//                            $audit_data['original_record']['applicant_homePhone'] = $audit->data['applicant_homePhone'];
//                            $audit_data['original_record']['job_category'] = $audit->data['job_category'];
//                            $audit_data['original_record']['applicant_source'] = $audit->data['applicant_source'];
//                            $audit_data['original_record']['applicant_notes'] = $audit->data['applicant_notes'];
//                            $audit_data['original_record']['applicant_added_date'] = $audit->data['applicant_added_date'];
//                            $audit_data['original_record']['applicant_added_time'] = $audit->data['applicant_added_time'];
//                            $audit_data['original_record']['status'] = $audit->data['status'];
//                        } else {
//                            $audit_data[$index]['changes_made'] = $audit->data['changes_made'];
//                            $audit_data[$index++]['changes_made_by'] = User::find($audit->user_id)->name;
//                        }
//                    }

                $content = "";
                $content .= '<a href="#" class=""
                                 data-controls-modal="#modal_applicant_audit'.$applicants->id.'"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#modal_applicant_audit'.$applicants->id.'">';
                $content .= User::find($applicants->applicant_user_id)->name;
                $content .= '</a>';
                $content .= '<div id="modal_applicant_audit'.$applicants->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Updation Details</h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" style="max-height: 500px; overflow-y: auto;">';
//                    if (!empty($audit->data['changes_made'])) {
//                        $content .= '<h6 class="font-weight-semibold">Changes</h6>';
//                        foreach ($audit->data['changes_made'] as $key_2 => $val_2) {
//                            $content .= '<div class="col-1"></div>';
//                            $content .= '<p><span class="font-weight-semibold">'.str_replace('_', ' ', $key_2).': </span>'.$val_2.'</p>';
//                        }
//                    } else {
//                        $content .= '<h6 class="font-weight-semibold">Details</h6>';
//                        foreach ($audit->data as $key_1 => $val_1) {
//                            $content .= '<div class="col-1"></div>';
//                            if (in_array($key_1, [
//                                'id', 'is_admin', 'is_active', 'email_verified_at', 'updated_at', 'changes_made',
//                                'applicant_cv', 'applicant_user_id'
//                            ])) {
//                                continue;
//                            } else {
//                                $content .= '<p><span class="font-weight-semibold">'.str_replace('_', ' ', $key_1).': </span>'.$val_1.'</p>';
//                            }
//                        }
//                    }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;


            })
            ->addColumn('action', function ($applicants) {
                return
                    '<div class="list-icons">
                                <div class="dropdown">
                                <a href="#" class=list-icons-item" data-toggle="dropdown">
                                    <i class="icon-menu9"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a href="'.route('applicants.edit',$applicants->id).'" class="dropdown-item"> Edit</a>
                                    <a href="'.route('applicants.show',$applicants->id).'" class="dropdown-item"> View </a>
                                    <a href="'.route('applicantHistory',$applicants->id).'" class="dropdown-item"> History</a>
                                    <a href="#" class="dropdown-item"
                                           data-controls-modal="#add_applicant_note'.$applicants->id.'"
                                           data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#add_applicant_note'.$applicants->id.'">
                                           Add Note</a >
                                <a href="#" class="dropdown-item notes_history" data-applicant="'.$applicants->id.'" data-controls-modal="#notes_history'.$applicants->id.'"
                                           data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#notes_history'.$applicants->id.'"
                                        > Notes History </a>
                            </div>
                        </div>
                    </div>
                          <div id="add_applicant_note'.$applicants->id.'" class="modal fade" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Add Applicant Note</h5>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <form action="'.route('module_note.store').'" method="POST" class="form-horizontal" id="note_form'.$applicants->id.'">
                                        <input type="hidden" name="_token" value="'.csrf_token().'">
                                        <input type="hidden" name="module" value="Applicant">
                                        <div class="modal-body">
                                            <div id="note_alert'.$applicants->id.'"></div>
                                            <div class="form-group row">
                                                <label class="col-form-label col-sm-3">Details</label>
                                                <div class="col-sm-9">
                                                    <input type="hidden" name="module_key" value="'.$applicants->id.'">
                                                    <textarea name="details" id="note_details'.$applicants->id.'" class="form-control" cols="30" rows="4"
                                                              placeholder="TYPE HERE .." required></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">
                                                Close
                                            </button>
                                            <button type="submit" data-note_key="'.$applicants->id.'" class="btn bg-teal legitRipple note_form_submit">Save</button>
                                        </div>
                                    </form>
                                    
                                </div>
                            </div>
                        </div>
                        <div id="notes_history'.$applicants->id.'" class="modal fade" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Applicant Notes History - 
                                        <span class="font-weight-semibold">'.$applicants->applicant_name.'</span></h5>
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body" id="applicants_notes_history'.$applicants->id.'" style="max-height: 500px; overflow-y: auto;">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE
                                        </button>
                                    </div>
                                    
                                </div>
                            </div>
                          </div>';})
            ->addColumn("created_at",function($applicants){
                $created_at = new DateTime($applicants->created_at);
                return DATE_FORMAT($created_at, "d M Y");
            })->rawColumns(['applicant_notes','updated_by','created_at','action'])
            ->make(true);
    }

    public function userStatistics(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'user_key' => 'required|exists:users,id',
                'start_date' => 'required|date_format:d-m-Y',
                'end_date' => 'required|date_format:d-m-Y'
            ]
        );

        if ($validator->passes()) {

            $start_date = Carbon::parse($request->input('start_date'));
            $end_date = Carbon::parse($request->input('end_date'))->endOfDay();
            $user_id = $request->input('user_key');
            $user = User::find($user_id);
            $roles = implode($user->roles->pluck('name', 'name')->all()); 
            // echo json_encode($roles);exit();
            $user_role = empty($roles) ? '' : $roles;

            $user_stats['no_of_send_cvs_from_cv_notes'] = 0;
            $user_stats['send_cvs_from_cv_notes'] = [];
			$prev_user_stats['crm_start_date']=0;
                  $prev_user_stats['crm_invoice']=0;
				  $prev_user_stats['crm_paid']=0;
			$user_stats_updated='';

            if (in_array($user_role, ['Sales', 'Sale and CRM'])) {

                $sales = Sale::where('user_id', $user_id)->whereIn('status', ['active','disable'])->whereBetween('created_at', [$start_date, $end_date])->get();
                $user_stats['close_sales'] = Audit::join('sales', 'sales.id', '=', 'audits.auditable_id')
                    ->where(['audits.message' => 'sale-closed', 'audits.auditable_type' => 'Horsefly\\Sale'])
                    ->where('sales.user_id', '=', $user_id)
                    ->whereBetween('sales.created_at', [$start_date, $end_date])
                    ->whereBetween('audits.created_at', [$start_date, $end_date])->count();
//                $user_stats['close_sales'] = Sale::where(['status' => 'disable', 'user_id' => $user_id])->whereBetween('created_at', [$start_date, $end_date])->count();
                $user_stats['open_sales'] = $sales->count() - $user_stats['close_sales'];
                $user_stats['psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'psl', 'user_id' => $user_id])->whereBetween('created_at', [$start_date, $end_date])->count();
                $user_stats['non_psl_offices'] = Office::where(['status' => 'active', 'office_type' => 'non psl', 'user_id' => $user_id])->whereBetween('created_at', [$start_date, $end_date])->count();

                foreach ($sales as $sale) {

                    $send_cvs_from_cv_notes = Cv_note::where('sale_id', '=', $sale->id)
                        ->whereBetween('updated_at', [$start_date, $end_date])->select('applicant_id', 'sale_id')->get();
					$user_stats_updated = Cv_note::where('user_id', '=', $user_id)
					->select('applicant_id', 'sale_id')
					->where('created_at','<', $start_date)->whereBetween('updated_at', [$start_date, $end_date])->get();
                    foreach ($send_cvs_from_cv_notes as $send_cvs_from_cv_note) {
                        $user_stats['send_cvs_from_cv_notes'][] = $send_cvs_from_cv_note;


                        /*** Quality  CVs*/
                        $user_stats['no_of_send_cvs_from_cv_notes']++;
                    }
                }
            } else {
                $user_stats['send_cvs_from_cv_notes'] = Cv_note::where('user_id', '=', $user_id)->whereBetween('created_at', [$start_date, $end_date])->select('applicant_id', 'sale_id')->get();

				$user_stats_updated = Cv_note::where('user_id', '=', $user_id)
					->select('applicant_id', 'sale_id')
					->where('created_at','<', $start_date)->whereBetween('updated_at', [$start_date, $end_date])->get();
                $user_stats['no_of_send_cvs_from_cv_notes'] = $user_stats['send_cvs_from_cv_notes']->count();
            }

            $user_stats['cvs_rejected'] = $user_stats['cvs_cleared'] = $user_stats['crm_sent_cvs'] = $user_stats['crm_rejected_cv'] = $user_stats['crm_request'] = $user_stats['crm_rejected_by_request'] = $user_stats['crm_confirmation'] = $user_stats['crm_rebook'] = $user_stats['crm_attended'] = $user_stats['crm_not_attended'] = $user_stats['crm_start_date'] = $user_stats['crm_start_date_hold'] = $user_stats['crm_declined'] = $user_stats['crm_invoice'] = $user_stats['crm_dispute'] = $user_stats['crm_paid'] = 0;
            foreach ($user_stats['send_cvs_from_cv_notes'] as $key => $cv) {

                $cv_cleared = History::where(['sub_stage' => 'quality_cleared', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                    ->whereBetween('updated_at', [$start_date, $end_date])->first();
                if ($cv_cleared) {
                    $user_stats['cvs_cleared']++;
                    /*** Sent CVs */
                    $user_stats['crm_sent_cvs']++;


                    /*** Rejected CV */
                    $crm_rejected_cv = History::where(['sub_stage' => 'crm_reject', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 'active'])->whereBetween('created_at', [$start_date, $end_date])->first();
                    if ($crm_rejected_cv) {
                        $user_stats['crm_rejected_cv']++;
                        continue;
                    }


                    /*** Request */
                    $crm_request = History::where(['sub_stage' => 'crm_request', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                        ->whereIn('id', function ($query) {
                            $query->select(DB::raw('MAX(id) FROM history h WHERE h.sub_stage="crm_request" and h.sale_id=history.sale_id and h.applicant_id=history.applicant_id'));
                        })->whereBetween('created_at', [$start_date, $end_date])->first();

                    $crm_sent_cv = Crm_note::where(['crm_notes.moved_tab_to' => 'cv_sent', 'crm_notes.applicant_id' => $cv->applicant_id, 'crm_notes.sales_id' => $cv->sale_id])
                        ->whereIn('crm_notes.id', function ($query) {
                            $query->select(DB::raw('MAX(id) FROM crm_notes as c WHERE c.moved_tab_to="cv_sent" and c.sales_id=crm_notes.sales_id and c.applicant_id=crm_notes.applicant_id'));
                        })->whereBetween('crm_notes.created_at', [$start_date, $end_date])->first();

                    if ($crm_request && $crm_sent_cv && (Carbon::parse($crm_request->history_added_date . ' ' . $crm_request->history_added_time)->gt($crm_sent_cv->created_at))) {
                        $user_stats['crm_request']++;


                        /*** Rejected By Request */
                        $crm_rejected_by_request = History::where(['sub_stage' => 'crm_request_reject', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 'active'])
                            ->whereBetween('created_at', [$start_date, $end_date])->first();
                        if ($crm_rejected_by_request) {
                            $user_stats['crm_rejected_by_request']++;
                            continue;
                        }


                        /*** Confirmation */
                        $crm_confirmation = History::where(['sub_stage' => 'crm_request_confirm', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                            ->whereIn('id', function ($query) {
                                $query->select(DB::raw('MAX(id) FROM history h WHERE h.sub_stage="crm_request_confirm" and h.sale_id=history.sale_id and h.applicant_id=history.applicant_id'));
                            })->whereBetween('created_at', [$start_date, $end_date])->first();
                        if ($crm_confirmation && (Carbon::parse($crm_confirmation->history_added_date . ' ' . $crm_confirmation->history_added_time)->gt(Carbon::parse($crm_request->history_added_date . ' ' . $crm_request->history_added_time)))) {
                            $user_stats['crm_confirmation']++;

                            /*** Rebook */
                            $crm_rebook = History::where(['sub_stage' => 'crm_reebok', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 'active'])
                                ->whereBetween('created_at', [$start_date, $end_date])->first();
                            if ($crm_rebook) {
                                $user_stats['crm_rebook']++;
                                continue;
                            }

                           

                           


                            /*** Attended Pre-Start Date */
                            $crm_attended = History::where(['sub_stage' => 'crm_interview_attended', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                                ->whereIn('id', function ($query) {
                                    $query->select(DB::raw('MAX(id) FROM history h WHERE h.sub_stage="crm_interview_attended" and h.sale_id=history.sale_id and h.applicant_id=history.applicant_id'));
                                })->whereBetween('created_at', [$start_date, $end_date])->first();
                            if ($crm_attended) {
                                $user_stats['crm_attended']++;

                                 /*** Declined */
                            $crm_declined = History::where(['sub_stage' => 'crm_declined', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 'active'])
                            ->whereBetween('created_at', [$start_date, $end_date])->first();
                        if ($crm_declined) {
                            $user_stats['crm_declined']++;
                            continue;
                        }

                                 /*** Not Attended */
                                 $crm_not_attended = History::where(['sub_stage' => 'crm_interview_not_attended', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 'active'])
                                 ->whereBetween('created_at', [$start_date, $end_date])->first();
                             if ($crm_not_attended) {
                                 $user_stats['crm_not_attended']++;
                                 continue;
                             }

                                /*** Start Date */
                                $crm_start_date = History::where(['history.sub_stage' => 'crm_start_date', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                                    ->whereBetween('created_at', [$start_date, $end_date])->first();

                                $crm_start_date_back = History::where(['history.sub_stage' => 'crm_start_date_back', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                                    ->whereIn('id', function ($query) {
                                        $query->select(DB::raw('MAX(id) FROM history h WHERE h.sub_stage="crm_start_date_back" and h.sale_id=history.sale_id and h.applicant_id=history.applicant_id'));
                                    })->whereBetween('created_at', [$start_date, $end_date])->first();
                                if (($crm_start_date && !$crm_start_date_back) || ($crm_start_date && $crm_start_date_back)) {

                                    $user_stats['crm_start_date']++;
                                    $crm_start_date = $crm_start_date_back ? $crm_start_date_back : $crm_start_date;


                                    /*** Start Date Hold */
                                    $crm_start_date_hold = History::where(['history.sub_stage' => 'crm_start_date_hold', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 'active'])
                                        ->whereBetween('created_at', [$start_date, $end_date])->first();
                                    if ($crm_start_date_hold) {
                                        $user_stats['crm_start_date_hold']++;
                                        continue;
                                    }

                                       

                                    /*** Invoice */
                                    $crm_invoice = History::where(['history.sub_stage' => 'crm_invoice', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                                        ->whereIn('id', function ($query) {
                                            $query->select(DB::raw('MAX(id) FROM history h WHERE h.sub_stage="crm_invoice" and h.sale_id=history.sale_id and h.applicant_id=history.applicant_id'));
                                        })->whereBetween('created_at', [$start_date, $end_date])->first();

                                    if ($crm_invoice) {
                                        $user_stats['crm_invoice']++;


                                        /*** Dispute */
                                        $crm_dispute = History::where(['sub_stage' => 'crm_dispute', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 'active'])
                                            ->whereBetween('created_at', [$start_date, $end_date])->first();
                                        if ($crm_dispute) {
                                            $user_stats['crm_dispute']++;
                                            continue;
                                        }


                                        /*** Paid */
                                        $crm_paid = History::where(['sub_stage' => 'crm_paid', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
                                            ->whereBetween('created_at', [$start_date, $end_date])->first();
                                        if ($crm_paid) {
                                            $user_stats['crm_paid']++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
				else {
                    $cv_rejected = History::where(['sub_stage' => 'quality_reject', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id, 'status' => 'active'])
                        ->whereBetween('created_at', [$start_date, $end_date])->first();
                    if ($cv_rejected) {
                        $user_stats['cvs_rejected']++;
                    }
                }
            }


			 // ---------------------------------------------------Last month stats -------------------------------------------------------------
			
			if($user_stats_updated){
				foreach ($user_stats_updated as $key => $cv) 
				{
				/*** Start Date */
                            $crm_start_date = History::where(['history.sub_stage' => 'crm_start_date', 'applicant_id' => $cv->applicant_id, 'sale_id' => 
							$cv->sale_id])->whereBetween('created_at', [$start_date, $end_date])->first();
                            $crm_start_date_back = History::where(['history.sub_stage' => 'crm_start_date_back', 'applicant_id' => $cv->applicant_id, 'sale_id' 							=> $cv->sale_id])->whereIn('id', function ($query) {
                           $query->select(DB::raw('MAX(id) FROM history h WHERE h.sub_stage="crm_start_date_back" and h.sale_id=history.sale_id and 										h.applicant_id=history.applicant_id'));})->whereBetween('created_at', [$start_date, $end_date])->first();
                            if (($crm_start_date && !$crm_start_date_back) || ($crm_start_date && $crm_start_date_back)) 
							{
                                $prev_user_stats['crm_start_date']++;
                                $crm_start_date = $crm_start_date_back ? $crm_start_date_back : $crm_start_date;
							}
                                /*** Invoice */
                                $crm_invoice = History::where(['history.sub_stage' => 'crm_invoice', 'applicant_id' => $cv->applicant_id,
								'sale_id' => $cv->sale_id])
                                 ->whereIn('id', function ($query) {
                                  $query->select(DB::raw('MAX(id) FROM history h WHERE h.sub_stage="crm_invoice" and h.sale_id=history.sale_id and 												   h.applicant_id=history.applicant_id'));})
								   ->whereBetween('created_at', [$start_date, $end_date])->first();

                                if ($crm_invoice) {
                                    $prev_user_stats['crm_invoice']++;
									}
                                   


                                    /*** Paid */
                                   $crm_paid = History::where(['sub_stage' => 'crm_paid', 'applicant_id' => $cv->applicant_id, 'sale_id' => $cv->sale_id])
									->whereBetween('created_at', [$start_date, $end_date])->first();
                                    if ($crm_paid) {
                                        $prev_user_stats['crm_paid']++;
                                    }
                                
                            //}
			
				}
			}
            unset($user_stats['send_cvs_from_cv_notes']);
            unset($user_stats['all_send_cvs_from_cv_notes']);
            $user_statistics_modal_body = view('administrator.partial.user_statistics', compact('user_stats', 'prev_user_stats', 'user_role'))->render();
            return $user_statistics_modal_body;
        }
        return response()->json(['error' => $validator->errors()->all()]);
    }

	
	    public function applicantHomeDetailStats(Request $request){
        $user_name = Auth::user()->name;
			
    if ($request->update_nurse!=null){
        $updateRecord=$request->update_nurse;

        $today = Carbon::today();
        $user_home = $request->input('user_home');
        $range = $request->input('user_key');
        $date_value = $request->input('date_value');
        // echo $date_value;
        // $date_monthly = explode("/", $date_value);
        // print_r($date_monthly);
        // $current_month=(int)$date_monthly[0];
        // $current_year=(int)$date_monthly[1];
        $stats_date='';
        $daily_date='';
        $start_of_week_date='';
        $end_of_week_date='';
        $current_month = '';
        $current_year = '';
        if($range=='daily')
        {
            $daily_date = new Carbon($request->input('date_value'));
            // $daily_date=(new Carbon($new_date_value))->format('jS F Y');
            // $daily_date = Carbon::today();
            $stats_date = $daily_date;

        }
        else if($range=='weekly')
        {
            $date_weekly = explode(" ", $date_value);
            $conv_date_value = date('Y-m-d H:i:s', strtotime($date_weekly[0]));
            $converted_date=(new Carbon($conv_date_value));

            $start_of_week_date = $converted_date->copy()->startOfWeek(Carbon::MONDAY);
            $end_of_week_date = $converted_date->copy()->endOfWeek(Carbon::SUNDAY);
            $stats_date = $start_of_week_date.'&'.$end_of_week_date;
        }
        else if($range=='monthly')
        {
            $date_monthly = explode("/", $date_value);
            $current_month=(int)$date_monthly[0];
            $current_year=(int)$date_monthly[1];
            $stats_date = $current_month.'&'.$current_year;
        }
        else if($range=='aggregate')
        {
            //$start_date = new Carbon('2019-06-27 00:00:00');
            //$end_date = $today->copy()->endOfDay();
            $start_date = new Carbon($request->input('date_value'));
            $end_dated = new Carbon($request->input('date_value_end'));
            $end_date = $end_dated->copy()->endOfDay();
            $stats_date = $start_date.'&'.$end_date;
        }
        $range_date='';
        $weekly_range_reject_cv='';
        $daily_range='';
        $daily_range_reject_cv='';



        if($range=='daily')
        {
            $range_date=$daily_date;
        }
        else if($range=='weekly')
        {
            $range_date=[$start_of_week_date, $end_of_week_date];
        }
        $stats_type = $request->input('user_name');
        $formatted_date=$today->format('jS F Y');
        $detail_stats_nurse=0;
        $detail_stats_non_nurse=0;
        $specialist_result = '';
        $specilaist = '';
        //    $start_date = new Carbon('2019-06-27 00:00:00');
        //    $end_date = $today->copy()->endOfDay();
        if($stats_type=='quality_cleared' || $stats_type == 'crm_open_cvs')
        {
			$user_job_category = ($user_home == 'non-nurse') ? ['non-nurse', 'nursery'] : [$user_home];
			
            $result_nurse = Applicant::where([ 'status' => 'active' ])->whereIn('job_category' , $user_job_category);
            $source_result = Applicant::select('applicant_source',DB::raw('count(*) as count'))
                ->whereIn('applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Jobmedic','Monster','Other Source'])->where([ 'status' => 'active' ])->whereIn('job_category' , $user_job_category);
            $other_source = Applicant::select('applicant_source')
                ->whereNotIn('applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Jobmedic','Monster','Other Source'])->where([ 'status' => 'active' ])->whereIn('job_category' , $user_job_category);
            $page_name='';
            $page_title='';
            $home='';

            if($user_home=='nurse')
            {
                $page_name = 'nursing_home';
                $page_title = 'Sent';
                $home = 'Nurse';
                $result_nurse->where(['status' => 'active','job_category' => $user_home]);
            }
            else
            {
                $page_name = 'non_nursing_home';
                $page_title = 'Sent';
                $home = 'Non Nurse';
                $result_nurse->where(['status' => 'active'])->whereIn('job_category' , $user_job_category)
                    ->whereNotIn('applicant_job_title', ['nonnurse specialist']);
                $specialist_result = Applicant::where(['status' => 'active', "applicant_job_title" => "nonnurse specialist"])
					->whereIn('job_category' , $user_job_category);
            }

            if($range=='daily')
            {
                $source_result->whereDate('updated_at', $range_date)->whereColumn('updated_at', '!=', 'created_at')->count();
                $other_source->whereDate('updated_at', $range_date)->whereColumn('updated_at', '!=', 'created_at')->count();
                $detail_stats_home = $result_nurse->whereDate('updated_at', $range_date)->whereColumn('updated_at', '!=', 'created_at')->count();
                if($user_home != 'nurse')
                {
                    $specilaist1 = $specialist_result->whereDate('updated_at', $range_date)->whereColumn('updated_at', '!=', 'created_at')->count();
				$chef =  Applicant::where(['status' => 'active','job_category' => 'chef'])->whereDate('updated_at',$range_date)->whereColumn('updated_at', '!=', 'created_at')->count();
					
					$specilaist=$specilaist1 + $chef;

                }
            }
            else if($range=='weekly')
            {
                $source_result->whereBetween('updated_at',$range_date)->whereColumn('updated_at', '!=', 'created_at')->count();
                $other_source->whereBetween('updated_at',$range_date)->whereColumn('updated_at', '!=', 'created_at')->count();
                $detail_stats_home = $result_nurse->whereBetween('updated_at',$range_date)->whereColumn('updated_at', '!=', 'created_at')->count();
//                no_of_nurses_update
                if($user_home != 'nurse')
                {
                    $specilaist1 = $specialist_result->whereBetween('updated_at',$range_date)->whereColumn('updated_at', '!=', 'created_at')->count();
					$chef =  Applicant::where(['status' => 'active','job_category' => 'chef'])->whereDate('updated_at',$range_date)->whereColumn('updated_at', '!=', 'created_at')->count();
					
					$specilaist=$specilaist1 + $chef;	
                }
            }
            else if($range=='monthly')
            {
                $source_result->whereMonth('updated_at', $current_month)->whereYear('updated_at', $current_year)->whereColumn('updated_at', '!=', 'created_at')->count();
                $other_source->whereMonth('updated_at', $current_month)->whereYear('updated_at', $current_year)->whereColumn('updated_at', '!=', 'created_at')->count();
                $detail_stats_home = $result_nurse->whereMonth('updated_at', $current_month)->whereYear('created_at', $current_year)->whereColumn('updated_at', '!=', 'created_at')->count();
                if($user_home != 'nurse')
                {
                    $specilaist1 = $specialist_result->whereMonth('updated_at', $current_month)->whereYear('updated_at', $current_year)->count();
					$chef =  Applicant::where(['status' => 'active','job_category' => 'chef'])->whereMonth('updated_at', $current_month)->whereYear('updated_at', $current_year)->count();
                    $specilaist=$specilaist1 + $chef;
                }
            }
            else if($range=='aggregate')
            {
                $source_result->whereBetween('created_at', [$start_date, $end_date])->count();
                $other_source->whereBetween('created_at', [$start_date, $end_date])->count();
                $detail_stats_home = $result_nurse->whereBetween('created_at', [$start_date, $end_date])->count();
                if($user_home != 'nurse')
                {
                    $specilaist = $specialist_result->whereBetween('created_at', [$start_date, $end_date])->count();
                }
            }
            $source_res=$source_result->groupBy('applicant_source')->get();
            $other_source_res = $other_source->get()->count();
            $user_statistics_modal_body = view('administrator.partial.applicants_details_stats', compact('detail_stats_home','page_name','page_title','source_res','home','range','date_value','current_year','stats_date','specilaist','other_source_res','updateRecord'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];
        }
    //end code
    }else{
        $updateRecord='null';
        $today = Carbon::today();
        $user_home = $request->input('user_home');
        $range = $request->input('user_key');
        $date_value = $request->input('date_value');
        // echo $date_value;
        // $date_monthly = explode("/", $date_value);
        // print_r($date_monthly);
        // $current_month=(int)$date_monthly[0];
        // $current_year=(int)$date_monthly[1];
        $stats_date='';
        $daily_date='';
        $start_of_week_date='';
        $end_of_week_date='';
        $current_month = '';
        $current_year = '';
        if($range=='daily')
        {
            $daily_date = new Carbon($request->input('date_value'));
            // $daily_date=(new Carbon($new_date_value))->format('jS F Y');
            // $daily_date = Carbon::today();
            $stats_date = $daily_date;

        }
        else if($range=='weekly')
        {
            $date_weekly = explode(" ", $date_value);
            $conv_date_value = date('Y-m-d H:i:s', strtotime($date_weekly[0]));
            $converted_date=(new Carbon($conv_date_value));

            $start_of_week_date = $converted_date->copy()->startOfWeek(Carbon::MONDAY);
            $end_of_week_date = $converted_date->copy()->endOfWeek(Carbon::SUNDAY);
            $stats_date = $start_of_week_date.'&'.$end_of_week_date;
        }
        else if($range=='monthly')
        {
            $date_monthly = explode("/", $date_value);
            $current_month=(int)$date_monthly[0];
            $current_year=(int)$date_monthly[1];
            $stats_date = $current_month.'&'.$current_year;
        }
        else if($range=='aggregate')
        {
            //$start_date = new Carbon('2019-06-27 00:00:00');
            //$end_date = $today->copy()->endOfDay();
            $start_date = new Carbon($request->input('date_value'));
            $end_dated = new Carbon($request->input('date_value_end'));
            $end_date = $end_dated->copy()->endOfDay();
            $stats_date = $start_date.'&'.$end_date;
        }
        $range_date='';
        $weekly_range_reject_cv='';
        $daily_range='';
        $daily_range_reject_cv='';



        if($range=='daily')
        {
            $range_date=$daily_date;
        }
        else if($range=='weekly')
        {
            $range_date=[$start_of_week_date, $end_of_week_date];
        }
        $stats_type = $request->input('user_name');
        $formatted_date=$today->format('jS F Y');
        $detail_stats_nurse=0;
        $detail_stats_non_nurse=0;
        $specialist_result = '';
        $specilaist = '';
        //    $start_date = new Carbon('2019-06-27 00:00:00');
        //    $end_date = $today->copy()->endOfDay();
        if($stats_type=='quality_cleared' || $stats_type == 'crm_open_cvs')
        {
			$user_job_category = ($user_home == 'non-nurse') ? ['non-nurse', 'nursery'] : [$user_home];
			
            $result_nurse = Applicant::where([ 'status' => 'active' ])->whereIn('job_category',$user_job_category);
            $source_result = Applicant::select('applicant_source',DB::raw('count(*) as count'))
                ->whereIn('applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Monster','Jobmedic','Other Source'])->where([ 'status' => 'active' ])->whereIn('job_category',$user_job_category);
            $other_source = Applicant::select('applicant_source')
                ->whereNotIn('applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Monster','Jobmedic','Other Source'])->where([ 'status' => 'active' ])->whereIn('job_category',$user_job_category);
            $page_name='';
            $page_title='';
            $home='';

            if($user_home=='nurse')
            {
                $page_name = 'nursing_home';
                $page_title = 'Sent';
                $home = 'Nurse';
                $result_nurse->where(['status' => 'active','job_category' => $user_home]);

            }
            else
            {
                $page_name = 'non_nursing_home';
                $page_title = 'Sent';
                $home = 'Non Nurse';
                $result_nurse->where(['status' => 'active'])->whereIn('job_category',$user_job_category)
                    ->whereNotIn('applicant_job_title', ['nonnurse specialist']);
                $specialist_result = Applicant::where(['status' => 'active','job_category' => $user_home, "applicant_job_title" => "nonnurse specialist"]);
            }

            if($range=='daily')
            {
                $source_result->whereDate('created_at', $range_date)->count();
                $other_source->whereDate('created_at', $range_date)->count();
                $detail_stats_home = $result_nurse->whereDate('created_at', $range_date)->count();
                if($user_home != 'nurse')
                {
                    $specilaist = $specialist_result->whereDate('created_at', $range_date)->count();
                }
            }
            else if($range=='weekly')
            {
                $source_result->whereBetween('created_at',$range_date)->count();
                $other_source->whereBetween('created_at',$range_date)->count();
                $detail_stats_home = $result_nurse->whereBetween('created_at',$range_date)->count();
                if($user_home != 'nurse')
                {
                    $specilaist = $specialist_result->whereBetween('created_at',$range_date)->count();
                }
            }
            else if($range=='monthly')
            {
                $source_result->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
                $other_source->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
                $detail_stats_home = $result_nurse->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
                if($user_home != 'nurse')
                {
                    $specilaist = $specialist_result->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->count();
                }
            }
            else if($range=='aggregate')
            {
                $source_result->whereBetween('created_at', [$start_date, $end_date])->count();
                $other_source->whereBetween('created_at', [$start_date, $end_date])->count();
                $detail_stats_home = $result_nurse->whereBetween('created_at', [$start_date, $end_date])->count();
                if($user_home != 'nurse')
                {
                    $specilaist = $specialist_result->whereBetween('created_at', [$start_date, $end_date])->count();
                }
            }
            $source_res=$source_result->groupBy('applicant_source')->get();
            $other_source_res = $other_source->get()->count();
            $user_statistics_modal_body = view('administrator.partial.applicants_details_stats', compact('detail_stats_home','page_name','page_title','source_res','home','range','date_value','current_year','stats_date','specilaist','other_source_res','updateRecord'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];
        }
     }
  }

	 public function applicantDetailStats(Request $request){
        $user_name = Auth::user()->name;
        $today = Carbon::today();
        $range = $request->input('user_key');
        $date_value = $request->input('date_value');
        $daily_dat='';
        $start_of_week_date='';
        $end_of_week_date='';
        $current_month = '';
        $current_year = '';
        $start_date = '';
            $end_date = '';
		 $stats_date='';
        if($range=='daily')
        {
            $new_date_value = date('Y-m-d H:i:s', strtotime($date_value));
            $daily_date=(new Carbon($new_date_value))->format('jS F Y');
			$stats_date = $daily_date;
        }
        else if($range=='weekly')
        {
            $date_weekly = explode(" ", $date_value);
            $conv_date_value = date('Y-m-d H:i:s', strtotime($date_weekly[0]));
            $converted_date=(new Carbon($conv_date_value));

            $start_of_week_date = $converted_date->copy()->startOfWeek(Carbon::MONDAY);
            $end_of_week_date = $converted_date->copy()->endOfWeek(Carbon::SUNDAY);
			$stats_date = $start_of_week_date.'&'.$end_of_week_date;
        }
        else if($range=='monthly')
        {
            $date_monthly = explode("/", $date_value);
            $current_month=(int)$date_monthly[0];
            $current_year=(int)$date_monthly[1];
            $stats_date = $current_month.'&'.$current_year;
            // echo $current_month;exit();


        }
        else if($range=='aggregate')
        {
            $start_date = new Carbon($request->input('date_value'));
            $end_dated = new Carbon($request->input('date_value_end'));
            $end_date = $end_dated->copy()->endOfDay();
			$stats_date = $start_date.'&'.$end_date;
        }

        $range_date='';
        $weekly_range_reject_cv='';
        $daily_range='';
        $daily_range_reject_cv='';

        if($range=='daily')
        {
            $range_date=$daily_date;
        }
        else if($range=='weekly')
        {
            $range_date=[$start_of_week_date, $end_of_week_date];
        }
        // $applicant_stage = $request->input('applicant_stage');
        $stats_type = $request->input('user_name');
        $formatted_date=$today->format('jS F Y');
        $detail_stats_nurse=0;
        $detail_stats_non_nurse=0;
        $detail_stats_non_nurse_specialist=0;

        if($stats_type=='quality_cleared')
        {
            $result_nurse = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.job_category',DB::raw('count(*) as count'))	
            ->where(['history.sub_stage' => $stats_type]);	
				
			$source_result = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source',DB::raw('count(*) as count'))	
            ->where('history.sub_stage', $stats_type)	
            ->whereIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic']);
             $unknown_source = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source')	
            ->where('history.sub_stage', $stats_type)	
            ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic']);

            if($range=='daily')
            {
				$source_result->where('history.history_added_date', $range_date)->count();
				$unknown_source->where('history.history_added_date', $range_date)->count();	
                $result_nurse->where('history.history_added_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $source_result->whereBetween('history.created_at',$range_date)->count();	
				$unknown_source->whereBetween('history.created_at',$range_date)->count();
                $result_nurse->whereBetween('history.created_at',$range_date)->count();
            }
            else if($range=='monthly')
            {

                $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();	
				$unknown_source->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
                $result_nurse->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $source_result->whereBetween('history.created_at', [$start_date, $end_date])->count();
				$unknown_source->whereBetween('history.created_at', [$start_date, $end_date])->count();
                $result_nurse->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $source_res=$source_result->groupBy('applicants.applicant_source')->get();
            $res=$result_nurse->groupBy('applicants.job_category')->get();
			 $unknown_source_res = $unknown_source->get()->count();
            foreach($res as $data)
				{
				if($data->job_category=='nurse')
                {
					$detail_stats_nurse=$data->count;
                }
				else
                {
					$non_nurse_specialist_count = '';
					$non_nurse_count = '';
					$non_nurse_specialist_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
						->where(['history.sub_stage' => $stats_type,"applicants.job_category"=>"non-nurse",
								 "applicants.applicant_job_title" => "nonnurse specialist"]);

					$chef_count= History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
						->where(['history.sub_stage' => $stats_type,"applicants.job_category"=>"chef"]);

					$non_nurse_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
						->where(['history.sub_stage' => $stats_type])
						->whereIn("applicants.job_category",["non-nurse",'nursery'])
						->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
					if($range=='daily')
					{
						$non_nurse_specialist_count->where('history.history_added_date', $range_date);		
						$chef_count->where('history.history_added_date', $range_date);
						$non_nurse_count->where('history.history_added_date', $range_date);
					}
					else if($range=='weekly')
					{
						$non_nurse_specialist_count->whereBetween('history.created_at',$range_date);																$non_nurse_count->whereBetween('history.created_at',$range_date);
						$chef_count->where('history.history_added_date', $range_date);
					}
					else if($range=='monthly')
					{
						$non_nurse_specialist_count->whereMonth('history.created_at', $current_month)
							->whereYear('history.created_at', $current_year);																						$non_nurse_count->whereMonth('history.created_at', $current_month)
							->whereYear('history.created_at', $current_year);
						$chef_count->whereMonth('history.created_at', $current_month)
							->whereYear('history.created_at', $current_year);
					}
					else if($range=='aggregate')
					{
						$non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);													$non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
					}

					$detail_stats_non_nurse1 = $non_nurse_count->get()->count();
					$detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
					$chef=$chef_count->get()->count();
					$detail_stats_non_nurse=$detail_stats_non_nurse1 + $chef;
                }
			}
			
            $page_name = 'quality_cleared';
            $page_title = 'Sent';

            $user_statistics_modal_body = view('administrator.partial.applicants_details_stats', 
            compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','detail_stats_non_nurse_specialist','unknown_source_res','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];	

			 } else if ($stats_type == 'crm_open_cvs') {
                $stats_type = 'quality_cvs_hold';
                $result_nurse = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
                    ->select('applicants.job_category', DB::raw('count(*) as count'))
                    ->where(['history.sub_stage' => $stats_type]);

                $source_result = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
                    ->select('applicants.applicant_source', DB::raw('count(*) as count'))
                    ->where('history.sub_stage', $stats_type)
                    ->whereIn('applicants.applicant_source', ['Total Jobs', 'Reed', 'Niche', 'CV Library', 'Social Media', 'Referral', 'Other Source', 'Monster', 'Jobmedic']);
                $unknown_source = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
                    ->select('applicants.applicant_source')
                    ->where('history.sub_stage', $stats_type)
                    ->whereNotIn('applicants.applicant_source', ['Total Jobs', 'Reed', 'Niche', 'CV Library', 'Social Media', 'Referral', 'Other Source', 'Monster', 'Jobmedic']);
    
                if ($range == 'daily') {
                    $source_result->where('history.history_added_date', $range_date)->count();
                    $unknown_source->where('history.history_added_date', $range_date)->count();
                    $result_nurse->where('history.history_added_date', $range_date)->count();
                } else if ($range == 'weekly') {
                    $source_result->whereBetween('history.created_at', $range_date)->count();
                    $unknown_source->whereBetween('history.created_at', $range_date)->count();
                    $result_nurse->whereBetween('history.created_at', $range_date)->count();
                } else if ($range == 'monthly') {
    
                    $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
                    $unknown_source->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
                    $result_nurse->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
                } else if ($range == 'aggregate') {
                    $source_result->whereBetween('history.created_at', [$start_date, $end_date])->count();
                    $unknown_source->whereBetween('history.created_at', [$start_date, $end_date])->count();
                    $result_nurse->whereBetween('history.created_at', [$start_date, $end_date])->count();
                }
                $source_res = $source_result->groupBy('applicants.applicant_source')->get();
                $res = $result_nurse->groupBy('applicants.job_category')->get();
                $unknown_source_res = $unknown_source->get()->count();

                foreach ($res as $data) {
                    if ($data->job_category == 'nurse') {
                        $detail_stats_nurse = $data->count;
                    } else {
                        $non_nurse_specialist_count = '';
                        $non_nurse_count = '';
                        $non_nurse_specialist_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
                            ->where([
                                'history.sub_stage' => $stats_type,
                                "applicants.job_category" => "non-nurse",
                                "applicants.applicant_job_title" => "nonnurse specialist"
                            ]);

                        $chef_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
                            ->where(['history.sub_stage' => $stats_type, "applicants.job_category" => "chef"]);
    
                        $non_nurse_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
                            ->where(['history.sub_stage' => $stats_type])
                            ->whereIn("applicants.job_category", ["non-nurse", 'nursery'])
                            ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                        if ($range == 'daily') {
                            $non_nurse_specialist_count->where('history.history_added_date', $range_date);
                            $chef_count->where('history.history_added_date', $range_date);
                            $non_nurse_count->where('history.history_added_date', $range_date);
                        } else if ($range == 'weekly') {
                            $non_nurse_specialist_count->whereBetween('history.created_at', $range_date);
                            $non_nurse_count->whereBetween('history.created_at', $range_date);
                            $chef_count->where('history.history_added_date', $range_date);
                        } else if ($range == 'monthly') {
                            $non_nurse_specialist_count->whereMonth('history.created_at', $current_month)
                                ->whereYear('history.created_at', $current_year);
                            $non_nurse_count->whereMonth('history.created_at', $current_month)
                                ->whereYear('history.created_at', $current_year);
                            $chef_count->whereMonth('history.created_at', $current_month)
                                ->whereYear('history.created_at', $current_year);
                        } else if ($range == 'aggregate') {
                            $non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);
                            $non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
                        }
    
                        $detail_stats_non_nurse1 = $non_nurse_count->get()->count();
                        $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
                        $chef = $chef_count->get()->count();
                        $detail_stats_non_nurse = $detail_stats_non_nurse1 + $chef;
                    }
                }
    
                $page_name = 'CRM Open Cvs';
                $page_title = 'Sent';
    
                $user_statistics_modal_body = view(
                    'administrator.partial.applicants_details_stats',
                    compact('detail_stats_nurse', 'detail_stats_non_nurse', 'page_name', 'page_title', 'source_res', 'detail_stats_non_nurse_specialist', 'unknown_source_res', 'stats_date', 'range', 'stats_type')
                )->render();
                return ['user_stats' => $user_statistics_modal_body, 'user_name' => $user_name];
        }
        else if($stats_type=='crm_request'){
			
$source_result = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source',DB::raw('count(*) as count'))	
            ->where('history.sub_stage', 'crm_request')	
            ->whereIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic'])
            ->whereIn('history.id', function ($query) use ($formatted_date) {
                $query->select(DB::raw('MAX(h.id) FROM history AS h 
                WHERE h.sub_stage = "crm_request" 
                AND history.applicant_id = h.applicant_id 
                AND history.sale_id = h.sale_id 
                AND history.id > (
                    SELECT MAX(hh.id) FROM history AS hh 
                    WHERE ( hh.sub_stage = "quality_cleared" OR hh.sub_stage = "crm_save" ) 
                    AND history.applicant_id = hh.applicant_id AND history.sale_id = hh.sale_id 
                )'));
            });
            $unknown_source = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source')	
            ->where('history.sub_stage', 'crm_request')	
            ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic'])
            ->whereIn('history.id', function ($query) use ($formatted_date) {
                $query->select(DB::raw('MAX(h.id) FROM history AS h 
                WHERE h.sub_stage = "crm_request" 
                AND history.applicant_id = h.applicant_id 
                AND history.sale_id = h.sale_id 
                AND history.id > (
                    SELECT MAX(hh.id) FROM history AS hh 
                    WHERE ( hh.sub_stage = "quality_cleared" OR hh.sub_stage = "crm_save" ) 
                    AND history.applicant_id = hh.applicant_id AND history.sale_id = hh.sale_id 
                )'));
            });
			
            if($range=='daily')
            {
				$source_result->where('history.history_added_date', $range_date)->count();	
                $unknown_source->where('history.history_added_date', $range_date)->count();	
            }
            else if($range=='weekly')
            {
                $source_result->whereBetween('history.created_at',$range_date)->count();	
                $unknown_source->whereBetween('history.created_at',$range_date)->count();
            }
            else if($range=='monthly')
            {
                $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();	
                $unknown_source->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $source_result->whereBetween('history.created_at', [$start_date, $end_date])->count();	
                $unknown_source->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $source_res=$source_result->groupBy('applicants.applicant_source')->get();
            $unknown_source_res = $unknown_source->get()->count();
            // $formatted_date = Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
                $result_nurse = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
                ->select('applicants.job_category',DB::raw('count(*) as count'))
                ->where('history.sub_stage', 'crm_request')
                ->whereIn('history.id', function ($query) use ($formatted_date) {
                    $query->select(DB::raw('MAX(h.id) FROM history AS h 
                    WHERE h.sub_stage = "crm_request" 
                    AND history.applicant_id = h.applicant_id 
                    AND history.sale_id = h.sale_id 
                    AND history.id > (
                        SELECT MAX(hh.id) FROM history AS hh 
                        WHERE ( hh.sub_stage = "quality_cleared" OR hh.sub_stage = "crm_save" ) 
                        AND history.applicant_id = hh.applicant_id AND history.sale_id = hh.sale_id 
                    )'));
                });
                if($range=='daily')
            {
                $result_nurse->where('history.history_added_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $result_nurse->whereBetween('history.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $result_nurse->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $result_nurse->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $res=$result_nurse->groupBy('applicants.job_category')->get();
            foreach($res as $data)
				{
                    if($data->job_category=='nurse')
                    {
                        $detail_stats_nurse=$data->count;
                    }
                    else
                    {
                        $non_nurse_specialist_count = '';
					$non_nurse_count = '';
						
						$non_nurse_specialist_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
											->where(['history.sub_stage' => 'crm_request',"applicants.job_category"=>"non-nurse",
						"applicants.applicant_job_title" => "nonnurse specialist"])
											->whereIn('history.id', function ($query) use ($formatted_date) {
												$query->select(DB::raw('MAX(h.id) FROM history AS h 
												WHERE h.sub_stage = "crm_request" 
												AND history.applicant_id = h.applicant_id 
												AND history.sale_id = h.sale_id 
												AND history.id > (
													SELECT MAX(hh.id) FROM history AS hh 
													WHERE ( hh.sub_stage = "quality_cleared" OR hh.sub_stage = "crm_save" ) 
													AND history.applicant_id = hh.applicant_id AND history.sale_id = hh.sale_id 
												)'));
											});
						
												
						$non_nurse_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
							->where(['history.sub_stage' => 'crm_request'])
							->whereIn("applicants.job_category",["non-nurse",'nursery'])
							->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist'])
							->whereIn('history.id', function ($query) use ($formatted_date) {
								$query->select(DB::raw('MAX(h.id) FROM history AS h 
													WHERE h.sub_stage = "crm_request" 
													AND history.applicant_id = h.applicant_id 
													AND history.sale_id = h.sale_id 
													AND history.id > (
														SELECT MAX(hh.id) FROM history AS hh 
														WHERE ( hh.sub_stage = "quality_cleared" OR hh.sub_stage = "crm_save" ) 
														AND history.applicant_id = hh.applicant_id AND history.sale_id = hh.sale_id 
													)'));
							});
						$chef_count= History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
						->where(['history.sub_stage' => 'crm_request',"applicants.job_category"=>"chef"]);
						if($range=='daily')
						{
							$non_nurse_specialist_count->where('history.history_added_date', $range_date);																$non_nurse_count->where('history.history_added_date', $range_date);
							$chef_count->where('history.history_added_date', $range_date);
						}
						else if($range=='weekly')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at',$range_date);																$non_nurse_count->whereBetween('history.created_at',$range_date);
							$chef_count->whereBetween('history.created_at', $range_date);
						}
						else if($range=='monthly')
						{
							$non_nurse_specialist_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);																						$non_nurse_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
							$chef_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
						}
						else if($range=='aggregate')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);													$non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
						}
					
							//$detail_stats_non_nurse_specialist1 = $non_nurse_specialist_count->get()->count();
						  //$chef=$chef_count->get()->count();
					     //$detail_stats_non_nurse_specialist=$detail_stats_non_nurse_specialist1 + $chef;
							//$detail_stats_non_nurse = $non_nurse_count->get()->count();
						
						
							$detail_stats_non_nurse1 = $non_nurse_count->get()->count();
						   $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
							$chef=$chef_count->get()->count();
							$detail_stats_non_nurse=$detail_stats_non_nurse1 + $chef;
						
                    }

			}
                
            $page_name = 'crm_request';
            $page_title = 'Request';
            //$source_res='';
                $user_statistics_modal_body = view('administrator.partial.applicants_details_stats', 
                compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','detail_stats_non_nurse_specialist','unknown_source_res','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];
        }
        else if($stats_type=='crm_confirmation')
        {
			
			$source_result = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source',DB::raw('count(*) as count'))	
            ->where('history.sub_stage', 'crm_request_confirm')	
            ->whereIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Monster','Jobmedic','Other Source'])
            ->whereIn('history.id', function ($query) use ($formatted_date) {
                $query->select(DB::raw('MAX(h.id) FROM history AS h 
                WHERE h.sub_stage = "crm_request_confirm" 
	            AND history.applicant_id = h.applicant_id 
	            AND history.sale_id = h.sale_id 
	            AND history.id > ( 
		            SELECT MAX(hh.id) FROM history AS hh 
		            WHERE hh.sub_stage = "crm_request" 
		            AND history.applicant_id = hh.applicant_id 
		            AND history.sale_id = hh.sale_id 
            	)'));
            });
            $unknown_source = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source')	
            ->where('history.sub_stage', 'crm_request_confirm')	
            ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Monster','Jobmedic','Other Source'])
            ->whereIn('history.id', function ($query) use ($formatted_date) {
                $query->select(DB::raw('MAX(h.id) FROM history AS h 
                WHERE h.sub_stage = "crm_request_confirm" 
	            AND history.applicant_id = h.applicant_id 
	            AND history.sale_id = h.sale_id 
	            AND history.id > ( 
		            SELECT MAX(hh.id) FROM history AS hh 
		            WHERE hh.sub_stage = "crm_request" 
		            AND history.applicant_id = hh.applicant_id 
		            AND history.sale_id = hh.sale_id 
            	)'));
            });
            if($range=='daily')
            {
				$source_result->where('history.history_added_date', $range_date)->count();	
                $unknown_source->where('history.history_added_date', $range_date)->count();	
            }
            else if($range=='weekly')
            {
                $source_result->whereBetween('history.created_at',$range_date)->count();	
                $unknown_source->whereBetween('history.created_at',$range_date)->count();
            }
            else if($range=='monthly')
            {
                $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();	
                $unknown_source->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $source_result->whereBetween('history.created_at', [$start_date, $end_date])->count();	
                $unknown_source->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $source_res=$source_result->groupBy('applicants.applicant_source')->get();
            $unknown_source_res = $unknown_source->get()->count();
			
			
            $result_nurse = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
            ->select('applicants.job_category',DB::raw('count(*) as count'))
            ->where('history.sub_stage', 'crm_request_confirm')
            ->whereIn('history.id', function ($query) use ($formatted_date) {
                $query->select(DB::raw('MAX(h.id) FROM history AS h 
                WHERE h.sub_stage = "crm_request_confirm" 
	            AND history.applicant_id = h.applicant_id 
	            AND history.sale_id = h.sale_id 
	            AND history.id > ( 
		            SELECT MAX(hh.id) FROM history AS hh 
		            WHERE hh.sub_stage = "crm_request" 
		            AND history.applicant_id = hh.applicant_id 
		            AND history.sale_id = hh.sale_id 
            	)'));
            });
            if($range=='daily')
            {
                $result_nurse->where('history.history_added_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $result_nurse->whereBetween('history.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $result_nurse->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $result_nurse->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $res=$result_nurse->groupBy('applicants.job_category')->get();
            foreach($res as $data)
				{
                    if($data->job_category=='nurse')
                    {
                        $detail_stats_nurse=$data->count;
                    }
                    else
                    {
                        $non_nurse_specialist_count = '';
						$non_nurse_count = '';
						$non_nurse_specialist_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
											->where(['history.sub_stage' => 'crm_request_confirm',
													 "applicants.job_category"=>"non-nurse",
													 "applicants.applicant_job_title" => "nonnurse specialist"
													])
											->whereIn('history.id', function ($query) use ($formatted_date) {
												$query->select(DB::raw('MAX(h.id) FROM history AS h 
												WHERE h.sub_stage = "crm_request_confirm" 
												AND history.applicant_id = h.applicant_id 
												AND history.sale_id = h.sale_id 
												AND history.id > ( 
													SELECT MAX(hh.id) FROM history AS hh 
													WHERE hh.sub_stage = "crm_request" 
													AND history.applicant_id = hh.applicant_id 
													AND history.sale_id = hh.sale_id 
												)'));
											});
						
						$chef_count= History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
						       ->where(['history.sub_stage' => "crm_request_confirm","applicants.job_category"=>"chef"]);
						
						$non_nurse_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
							->where(['history.sub_stage' => 'crm_request_confirm'])
							->whereIn("applicants.job_category",["non-nurse",'nursery'])
							->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist'])
							->whereIn('history.id', function ($query) use ($formatted_date) {
								$query->select(DB::raw('MAX(h.id) FROM history AS h 
													WHERE h.sub_stage = "crm_request_confirm" 
													AND history.applicant_id = h.applicant_id 
													AND history.sale_id = h.sale_id 
													AND history.id > ( 
														SELECT MAX(hh.id) FROM history AS hh 
														WHERE hh.sub_stage = "crm_request" 
														AND history.applicant_id = hh.applicant_id 
														AND history.sale_id = hh.sale_id 
													)'));
							});
						
						if($range=='daily')
						{
							$non_nurse_specialist_count->where('history.history_added_date', $range_date);	
							$non_nurse_count->where('history.history_added_date', $range_date);
							$chef_count->where('history.history_added_date', $range_date);
						}
						else if($range=='weekly')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at',$range_date);																$non_nurse_count->whereBetween('history.created_at',$range_date);
							$chef_count->whereBetween('history.created_at',$range_date);
						}
						else if($range=='monthly')
						{
							$non_nurse_specialist_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);																						$non_nurse_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
							$chef_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
						}
						else if($range=='aggregate')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);													$non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
						}
					
							//$detail_stats_non_nurse_specialist1 = $non_nurse_specialist_count->get()->count();
						   //$chef=$chef_count->get()->count();
					       //$detail_stats_non_nurse_specialist=$detail_stats_non_nurse_specialist1 + $chef;
							//$detail_stats_non_nurse = $non_nurse_count->get()->count();
					
							$detail_stats_non_nurse1 = $non_nurse_count->get()->count();
						  $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
							$chef=$chef_count->get()->count();
							$detail_stats_non_nurse=$detail_stats_non_nurse1 + $chef;
					
                    }

			}
            $page_name = 'crm_confirmation';
            $page_title = 'Confirmation';
            //$source_res='';
            // print_r($detail_stats_nurse);exit();
            $user_statistics_modal_body = view('administrator.partial.applicants_details_stats', compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','detail_stats_non_nurse_specialist','unknown_source_res','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];
        }
        else if($stats_type=='crm_prestart_attended')
        {
			
			$source_result = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source',DB::raw('count(*) as count'))	
            ->where('history.sub_stage', 'crm_interview_attended')	
            ->whereIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Monster','Jobmedic','Other Source'])
            ->whereIn('history.id', function ($query) {
                $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_interview_attended" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
            });
            $unknown_source = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source')	
            ->where('history.sub_stage', 'crm_interview_attended')	
            ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Monster','Jobmedic','Other Source'])
            ->whereIn('history.id', function ($query) {
                $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_interview_attended" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
            });
            if($range=='daily')
            {
				$source_result->where('history.history_added_date', $range_date)->count();	
                $unknown_source->where('history.history_added_date', $range_date)->count();	
            }
            else if($range=='weekly')
            {
                $source_result->whereBetween('history.created_at',$range_date)->count();	
                $unknown_source->whereBetween('history.created_at',$range_date)->count();
            }
            else if($range=='monthly')
            {
                $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();	
                $unknown_source->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $source_result->whereBetween('history.created_at', [$start_date, $end_date])->count();	
                $unknown_source->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $source_res=$source_result->groupBy('applicants.applicant_source')->get();
            $unknown_source_res = $unknown_source->get()->count();
				
	
            $results = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
            ->select('applicants.job_category',DB::raw('count(*) as count'))
            ->where('history.sub_stage', "crm_interview_attended")
            ->whereIn('history.id', function ($query) {
                $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_interview_attended" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
            });
            if($range=='daily')
            {
                $results->where('history.history_added_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $results->whereBetween('history.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $results->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $results->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $res=$results->groupBy('applicants.job_category')->get();
            foreach($res as $data)
				{
                    if($data->job_category=='nurse')
                    {
                        $detail_stats_nurse=$data->count;
                    }
                    else
                    {
                        $non_nurse_specialist_count = '';
						$non_nurse_count = '';
						$non_nurse_specialist_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
						->where(["history.sub_stage"=> "crm_interview_attended","applicants.job_category"=>"non-nurse",
									"applicants.applicant_job_title" => "nonnurse specialist"])
						->whereIn('history.id', function ($query) {
							$query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_interview_attended" 
							AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
							});
							
						$non_nurse_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
						->where(["history.sub_stage"=> "crm_interview_attended"])
						->whereIn("applicants.job_category",["non-nurse",'nursery'])
						->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist'])
						->whereIn('history.id', function ($query) {
							$query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_interview_attended" 
							AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
							});
							$chef_count= History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
						   ->where(['history.sub_stage' => "crm_interview_attended","applicants.job_category"=>"chef"]);
						if($range=='daily')
						{
							$non_nurse_specialist_count->where('history.history_added_date', $range_date);																$non_nurse_count->where('history.history_added_date', $range_date);
							$chef_count->where('history.history_added_date',$range_date);
						}
						else if($range=='weekly')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at',$range_date);																$non_nurse_count->whereBetween('history.created_at',$range_date);
								$chef_count->whereBetween('history.created_at',$range_date);
						}
						else if($range=='monthly')
						{
							$non_nurse_specialist_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);																						$non_nurse_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
							$chef_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
						}
						else if($range=='aggregate')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);													$non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
						}
					
							//$detail_stats_non_nurse_specialist1 = $non_nurse_specialist_count->get()->count();
						    //$chef=$chef_count->get()->count();
					        //$detail_stats_non_nurse_specialist=$detail_stats_non_nurse_specialist1 + $chef;
							//$detail_stats_non_nurse = $non_nurse_count->get()->count();
						
							$detail_stats_non_nurse1 = $non_nurse_count->get()->count();
						  $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
							$chef=$chef_count->get()->count();
							$detail_stats_non_nurse=$detail_stats_non_nurse1 + $chef;
						
                    }

			}
            $page_name = 'crm_prestart_attended';
            $page_title = 'Attended';
            //$source_res='';
            $user_statistics_modal_body = view('administrator.partial.applicants_details_stats', compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','detail_stats_non_nurse_specialist','unknown_source_res','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];
        }
        else if($stats_type=='crm_start_date')
        {
			
$source_result = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source',DB::raw('count(*) as count'))	
            ->whereIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Monster','Jobmedic','Other Source'])
            ->whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_back'])
            ->whereIn('history.id', function ($query) {
                $query->select(DB::raw('MAX(h.id) FROM history as h WHERE ( h.sub_stage="crm_start_date" OR h.sub_stage="crm_start_date_back" )
                 AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
            });
            $unknown_source = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source')	
            ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Monster','Jobmedic','Other Source'])
            ->whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_back'])
            ->whereIn('history.id', function ($query) {
                $query->select(DB::raw('MAX(h.id) FROM history as h WHERE ( h.sub_stage="crm_start_date" OR h.sub_stage="crm_start_date_back" )
                 AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
            });
            if($range=='daily')
            {
				$source_result->where('history.history_added_date', $range_date)->count();	
                $unknown_source->where('history.history_added_date', $range_date)->count();	
            }
            else if($range=='weekly')
            {
                $source_result->whereBetween('history.created_at',$range_date)->count();	
                $unknown_source->whereBetween('history.created_at',$range_date)->count();
            }
            else if($range=='monthly')
            {
                $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();	
                $unknown_source->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $source_result->whereBetween('history.created_at', [$start_date, $end_date])->count();	
                $unknown_source->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $source_res=$source_result->groupBy('applicants.applicant_source')->get();
            $unknown_source_res = $unknown_source->get()->count();
            // $results_app = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
            // ->select('applicants.id','applicants.applicant_postcode')
            // ->whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_back'])
            // ->whereIn('history.id', function ($query) {
            //     $query->select(DB::raw('MAX(h.id) FROM history as h WHERE ( h.sub_stage="crm_start_date" OR h.sub_stage="crm_start_date_back" ) 
            //     AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
            // })
            // ->where('history.history_added_date', $range_date)->get();

            $results = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
            ->select('applicants.job_category',DB::raw('count(*) as count'))
            ->whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_back'])
            ->whereIn('history.id', function ($query) {
                $query->select(DB::raw('MAX(h.id) FROM history as h WHERE ( h.sub_stage="crm_start_date" OR h.sub_stage="crm_start_date_back" )
                 AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
            });
            if($range=='daily')
            {
                $results->where('history.history_added_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $results->whereBetween('history.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $results->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $results->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $res=$results->groupBy('applicants.job_category')->get();
            foreach($res as $data)
				{
                    if($data->job_category=='nurse')
                    {
                        $detail_stats_nurse=$data->count;
                    }
                    else
                    {
                        $non_nurse_specialist_count = '';
						$non_nurse_count = '';
							$non_nurse_specialist_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
							->whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_back'])
							->where(["applicants.job_category"=>"non-nurse","applicants.applicant_job_title" => "nonnurse specialist"])
							->whereIn('history.id', function ($query) {
								$query->select(DB::raw('MAX(h.id) FROM history as h WHERE 
								( h.sub_stage="crm_start_date" OR h.sub_stage="crm_start_date_back" )
								 AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
							});
						$chef_count= History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
						   ->where(['history.sub_stage' => "crm_start_date","applicants.job_category"=>"chef"]);

						
							$non_nurse_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
							->whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_back'])
							->whereIn("applicants.job_category",["non-nurse",'nursery'])
							->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist'])
							->whereIn('history.id', function ($query) {
								$query->select(DB::raw('MAX(h.id) FROM history as h WHERE 
								( h.sub_stage="crm_start_date" OR h.sub_stage="crm_start_date_back" )
								 AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
							});
						if($range=='daily')
						{
							$non_nurse_specialist_count->where('history.history_added_date', $range_date);																$non_nurse_count->where('history.history_added_date', $range_date);
							$chef_count->where('history.history_added_date',$range_date);
						}
						else if($range=='weekly')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at',$range_date);																$non_nurse_count->whereBetween('history.created_at',$range_date);
							$chef_count->whereBetween('history.created_at',$range_date);
						}
						else if($range=='monthly')
						{
							$non_nurse_specialist_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);																						$non_nurse_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
								$chef_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
						}
						else if($range=='aggregate')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);													$non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
						}
					
							//$detail_stats_non_nurse_specialist1 = $non_nurse_specialist_count->get()->count();
						    //$chef=$chef_count->get()->count();
					        //$detail_stats_non_nurse_specialist=$detail_stats_non_nurse_specialist1 + $chef;
							//$detail_stats_non_nurse = $non_nurse_count->get()->count();
						
							$detail_stats_non_nurse1 = $non_nurse_count->get()->count();
						  $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
							$chef=$chef_count->get()->count();
							$detail_stats_non_nurse=$detail_stats_non_nurse1 + $chef;
						
                    }

			}
            $page_name = 'crm_start_date';
            $page_title = 'Start Date';
            //$source_res='';
            // $user_statistics_modal_body = view('administrator.partial.applicants_details_stats', compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','results_app'))->render();
            // return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];

            $user_statistics_modal_body = view('administrator.partial.applicants_details_stats', 
            compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','detail_stats_non_nurse_specialist','unknown_source_res','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];
        }
        else if($stats_type=='crm_invoice')
        {
			
			$source_result = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source',DB::raw('count(*) as count'))	
            ->whereIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic'])
            ->whereIn('history.sub_stage', ['crm_invoice','crm_invoice_sent'])
            ->whereIn('history.id', function ($query) {
                $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage IN ("crm_invoice","crm_invoice_sent") AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
            });

            $unknown_source = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source')	
            ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic'])
            ->whereIn('history.sub_stage', ['crm_invoice','crm_invoice_sent'])
            ->whereIn('history.id', function ($query) {
                $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage IN ("crm_invoice","crm_invoice_sent") AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
            });

            if($range=='daily')
            {
				$source_result->where('history.history_added_date', $range_date)->count();	
                $unknown_source->where('history.history_added_date', $range_date)->count();	
            }
            else if($range=='weekly')
            {
                $source_result->whereBetween('history.created_at',$range_date)->count();	
                $unknown_source->whereBetween('history.created_at',$range_date)->count();
            }
            else if($range=='monthly')
            {
                $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();	
                $unknown_source->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $source_result->whereBetween('history.created_at', [$start_date, $end_date])->count();	
                $unknown_source->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $source_res=$source_result->groupBy('applicants.applicant_source')->get();
            $unknown_source_res = $unknown_source->get()->count();


			
            $results = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
            ->select('applicants.job_category',DB::raw('count(*) as count'))
            ->whereIn('history.sub_stage', ['crm_invoice','crm_invoice_sent'])
            ->whereIn('history.id', function ($query) {
                $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage IN ("crm_invoice","crm_invoice_sent") AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
            });




            if($range=='daily')
            {
                $results->where('history.history_added_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $results->whereBetween('history.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $results->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $results->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $res=$results->groupBy('applicants.job_category')->get();
            foreach($res as $data)
				{
                    if($data->job_category=='nurse')
                    {
                        $detail_stats_nurse=$data->count;
                    }
                    else
                    {
                        $non_nurse_specialist_count = '';
						$non_nurse_count = '';
							$non_nurse_specialist_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
								->whereIn('history.sub_stage', ['crm_invoice','crm_invoice_sent'])
							->where(["applicants.job_category"=>"non-nurse","applicants.applicant_job_title" => "nonnurse specialist"])
							->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage IN ("crm_invoice","crm_invoice_sent")  AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                });
						$non_nurse_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
								->whereIn('history.sub_stage', ['crm_invoice','crm_invoice_sent'])
								->whereIn("applicants.job_category",["non-nurse",'nursery'])
								->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist'])
								->whereIn('history.id', function ($query) {
                    				$query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage IN ("crm_invoice","crm_invoice_sent")  AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                					});
						
						$chef_count= History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
						   ->whereIn('history.sub_stage', ['crm_invoice','crm_invoice_sent'])->where(["applicants.job_category"=>"chef"])
							->whereIn('history.id', function ($query) {
									$query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage IN ("crm_invoice","crm_invoice_sent")  AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
								});

						if($range=='daily')
						{
							$non_nurse_specialist_count->where('history.history_added_date', $range_date);																$non_nurse_count->where('history.history_added_date', $range_date);
							$chef_count->where('history.history_added_date',$range_date);
						}
						else if($range=='weekly')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at',$range_date);																$non_nurse_count->whereBetween('history.created_at',$range_date);
							$chef_count->whereBetween('history.created_at',$range_date);
						}
						else if($range=='monthly')
						{
							$non_nurse_specialist_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);																						$non_nurse_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
							$chef_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
						}
						else if($range=='aggregate')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);													$non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
						}
					
							//$detail_stats_non_nurse_specialist1 = $non_nurse_specialist_count->get()->count();
						    //$chef=$chef_count->get()->count();
                            //$detail_stats_non_nurse_specialist=$detail_stats_non_nurse_specialist1 + $chef;
							//$detail_stats_non_nurse = $non_nurse_count->get()->count();
						
							$detail_stats_non_nurse1 = $non_nurse_count->get()->count();
						  $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
							$chef=$chef_count->get()->count();
							$detail_stats_non_nurse=$detail_stats_non_nurse1 + $chef;
						
                    }

			}
            $page_name = 'crm_invoice';
            $page_title = 'Invoice';
            //$source_res='';
            $user_statistics_modal_body = view('administrator.partial.applicants_details_stats', 
            compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','detail_stats_non_nurse_specialist','unknown_source_res','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];
        }
        else if($stats_type=='crm_paid')
        {
            $results = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
            ->select('applicants.job_category',DB::raw('count(*) as count'))
            ->where('history.sub_stage', 'crm_paid');
            $source_result = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
            ->select('applicants.applicant_source',DB::raw('count(*) as count'))
            ->where('history.sub_stage', 'crm_paid')
            ->whereIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic']);
			$unknown_source = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source')	
            ->where('history.sub_stage', $stats_type)	
            ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic']);
            if($range=='daily')
            {
                $source_result->where('history.history_added_date', $range_date)->count();
				$unknown_source->where('history.history_added_date', $range_date)->count();	
                $results->where('history.history_added_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $source_result->whereBetween('history.created_at', $range_date)->count();
				$unknown_source->whereBetween('history.created_at',$range_date)->count();
                $results->whereBetween('history.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                // $results->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
                // $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
                $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
				$unknown_source->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
                $results->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $source_result->whereBetween('history.created_at', [$start_date, $end_date])->count();	
				$unknown_source->whereBetween('history.created_at', [$start_date, $end_date])->count();
                $results->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }

            // $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();	
            //     $results->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            $res=$results->groupBy('applicants.job_category')->get();
            $source_res=$source_result->groupBy('applicants.applicant_source')->get();
			$unknown_source_res = $unknown_source->get()->count();
            // $source_res=$source_result->groupBy('applicants.applicant_source')->get();
            foreach($res as $data)
				{
                    if($data->job_category=='nurse')
                    {
                        $detail_stats_nurse=$data->count;
                    }
                    else
                    {
                        $non_nurse_specialist_count = '';
						$non_nurse_count = '';			
						$non_nurse_specialist_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
						->where(['history.sub_stage' => 'crm_paid',"applicants.job_category"=>"non-nurse",
								 "applicants.applicant_job_title" => "nonnurse specialist"]);
						
						$non_nurse_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
							->where(['history.sub_stage' => 'crm_paid'])
							->whereIn("applicants.job_category",["non-nurse",'nursery'])
							->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
						
						 $chef_count= History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
						   ->where(['history.sub_stage' => "crm_paid","applicants.job_category"=>"chef"]);

						if($range=='daily')
						{
							$non_nurse_specialist_count->where('history.history_added_date', $range_date);
							$non_nurse_count->where('history.history_added_date', $range_date);
							$chef_count->where('history.history_added_date',$range_date);
						}
						else if($range=='weekly')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at',$range_date);																$non_nurse_count->whereBetween('history.created_at',$range_date);
							$chef_count->whereBetween('history.created_at',$range_date);
						}
						else if($range=='monthly')
						{
							$non_nurse_specialist_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);																						$non_nurse_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
							$chef_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
						}
						else if($range=='aggregate')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);													$non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
						}
					
							//$detail_stats_non_nurse_specialist1 = $non_nurse_specialist_count->get()->count();
					
							$detail_stats_non_nurse1 = $non_nurse_count->get()->count();
						  $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
							$chef=$chef_count->get()->count();
							$detail_stats_non_nurse=$detail_stats_non_nurse1 + $chef;
						
						    
                    }

			}
            $page_name = 'crm_paid';
            $page_title = 'Paid';

            // $source_res='';
            $user_statistics_modal_body = view('administrator.partial.applicants_details_stats', 
            compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','detail_stats_non_nurse_specialist','unknown_source_res','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];
        }
        else if($stats_type=='crm_reject')
        {
            $reasons = [
                'nurse_position_filled' => 0,
                'nurse_agency' => 0,
                'nurse_manager' => 0,
                'nurse_no_response' => 0,
                'non-nurse_position_filled' => 0,
                'non-nurse_agency' => 0,
                'non-nurse_manager' => 0,
                'non-nurse_no_response' => 0
            ];
			$results=History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
				->select('applicants.job_category',DB::raw('count(*) as count'))
			->where(['history.sub_stage' => 'crm_reject', 'history.status' => 'active']);
            if($range=='daily')
            {
                $results->where('history.history_added_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $results->whereBetween('history.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $results->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $results->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $reject=$results->groupBy('applicants.job_category')->get();
			foreach($reject as $data)
				{
                    if($data->job_category=='nurse')
                    {
                        $detail_stats_nurse=$data->count;
                    }
                    else
                    {
                        $non_nurse_specialist_count = '';
						$non_nurse_count = '';

						$non_nurse_specialist_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
						->where(['history.sub_stage' => 'crm_reject', 'history.status' => 'active',"applicants.job_category"=>"non-nurse",
								 "applicants.applicant_job_title" => "nonnurse specialist"]);

						$non_nurse_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
							->where(['history.sub_stage' => 'crm_reject', 'history.status' => 'active'])
							->whereIn("applicants.job_category",["non-nurse",'nursery'])
							->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
						
						$chef_count= History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
						   ->where(['history.sub_stage' => "crm_reject",'history.status' => 'active',"applicants.job_category"=>"chef"]);

						
						if($range=='daily')
						{
							$non_nurse_specialist_count->where('history.history_added_date', $range_date);																$non_nurse_count->where('history.history_added_date', $range_date);
							$chef_count->where('history.history_added_date',$range_date);
						}
						else if($range=='weekly')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at',$range_date);																$non_nurse_count->whereBetween('history.created_at',$range_date);
							$chef_count->whereBetween('history.created_at',$range_date);
						}
						else if($range=='monthly')
						{
							$non_nurse_specialist_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);																						$non_nurse_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
							$chef_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
						}
						else if($range=='aggregate')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);													$non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
						}
					
							//$detail_stats_non_nurse_specialist1 = $non_nurse_specialist_count->get()->count();
						    //$chef=$chef_count->get()->count();
                           //$detail_stats_non_nurse_specialist=$detail_stats_non_nurse_specialist1 + $chef;
							//$detail_stats_non_nurse = $non_nurse_count->get()->count();
						
							$detail_stats_non_nurse1 = $non_nurse_count->get()->count();							
						    $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
							$chef=$chef_count->get()->count();
							$detail_stats_non_nurse=$detail_stats_non_nurse1 + $chef;
						
                    }

			}
			$query_nurse=Crm_rejected_cv::join('applicants', 'applicants.id', '=', 'crm_rejected_cv.applicant_id')
							->select('crm_rejected_cv.reason',DB::raw('count(*) as count'))
						->where(['crm_rejected_cv.status' => 'active'])			
				->where(['applicants.job_category' => 'nurse']);
                if($range=='daily')
            {
                $query_nurse->where('crm_rejected_cv.crm_rejected_cv_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $query_nurse->whereBetween('crm_rejected_cv.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $query_nurse->whereMonth('crm_rejected_cv.created_at', $current_month)->whereYear('crm_rejected_cv.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $query_nurse->whereBetween('crm_rejected_cv.created_at', [$start_date, $end_date])->count();
            }
			$reason_result=$query_nurse->groupBy('crm_rejected_cv.reason')->get();
            
            foreach ($reason_result as $data) {
                if($data->reason=='position_filled')
                {
                    $reasons['nurse_position_filled']=$data->count;
                }
                else if($data->reason=='agency')
                {
                    $reasons['nurse_agency']=$data->count;
                }
                else if($data->reason=='manager')
                {
                    $reasons['nurse_manager']=$data->count;
                }
                else if($data->reason=='no_response')
                {
                    $reasons['nurse_no_response']=$data->count;
                }
             
             }
			
            
            $query_non_nurse=Crm_rejected_cv::join('applicants', 'applicants.id', '=', 'crm_rejected_cv.applicant_id')
							->select('crm_rejected_cv.reason',DB::raw('count(*) as count'))
						->where(['crm_rejected_cv.status' => 'active'])			
				->whereIn('applicants.job_category',['chef','non-nurse','nursery']);
                if($range=='daily')
            {
                $query_non_nurse->where('crm_rejected_cv.crm_rejected_cv_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $query_non_nurse->whereBetween('crm_rejected_cv.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $query_non_nurse->whereMonth('crm_rejected_cv.created_at', $current_month)->whereYear('crm_rejected_cv.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $query_non_nurse->whereBetween('crm_rejected_cv.created_at', [$start_date, $end_date])->count();
            }
			$reason_result2=$query_non_nurse->groupBy('crm_rejected_cv.reason')->get();
            foreach ($reason_result2 as $data) {
                if($data->reason=='position_filled')
                {
                    $reasons['non-nurse_position_filled']=$data->count;
                }
                else if($data->reason=='agency')
                {
                    $reasons['non-nurse_agency']=$data->count;
                }
                else if($data->reason=='manager')
                {
                    $reasons['non-nurse_manager']=$data->count;
                }
                else if($data->reason=='no_response')
                {
                    $reasons['non-nurse_no_response']=$data->count;
                }
             
             }
            $page_name = 'crm_reject';
            $page_title = 'Reject';
            $user_statistics_modal_body = view('administrator.partial.applicants_details_stats',
             compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','reasons','detail_stats_non_nurse_specialist','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];
        }
        else if($stats_type=='crm_req_reject')
        {
        $results = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
        ->select('applicants.job_category',DB::raw('count(*) as count'))
        ->where(['history.sub_stage' => 'crm_request_reject', 'history.status' => 'active']);
        if($range=='daily')
            {
                $results->where('history.history_added_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $results->whereBetween('history.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $results->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $results->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
			$result=$results->groupBy('applicants.job_category')->get();
        // $results = DB::select( DB::raw("SELECT applicants.job_category, COUNT(*) as res FROM history
        // INNER JOIN applicants ON history.applicant_id=applicants.id
        //  WHERE history.sub_stage = 'crm_request_reject'
        //  And history.status='active'
        //  And history.history_added_date= '$formatted_date'
        //  GROUP BY applicants.job_category;"));
         
        
        foreach ($result as $data)
        {
            if($data->job_category=='nurse')
                    {
                        $detail_stats_nurse=$data->count;
                    }
                    else
                    {
                        $non_nurse_specialist_count = '';
						$non_nurse_count = '';

						$non_nurse_specialist_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
						->where(['history.sub_stage' => 'crm_request_reject', 'history.status' => 'active',
							"applicants.job_category"=>"non-nurse","applicants.applicant_job_title" => "nonnurse specialist"]);

						
						$non_nurse_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
						->where(['history.sub_stage' => 'crm_request_reject', 'history.status' => 'active'])
							->whereIn("applicants.job_category",["non-nurse",'nursery'])
							->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
						
						$chef_count= History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
		             ->where(['history.sub_stage' => "crm_request_reject","applicants.job_category"=>"chef"]);

						if($range=='daily')
						{
							$non_nurse_specialist_count->where('history.history_added_date', $range_date);																$non_nurse_count->where('history.history_added_date', $range_date);
							$chef_count->where('history.history_added_date',$range_date);
						}
						else if($range=='weekly')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at',$range_date);																$non_nurse_count->whereBetween('history.created_at',$range_date);
							$chef_count->whereBetween('history.created_at',$range_date);
						}
						else if($range=='monthly')
						{
							$non_nurse_specialist_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);																						$non_nurse_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
							$chef_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
						}
						else if($range=='aggregate')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);													$non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
						}
					
							//$detail_stats_non_nurse_specialist1 = $non_nurse_specialist_count->get()->count();
						   //$chef=$chef_count->get()->count();
                           //$detail_stats_non_nurse_specialist=$detail_stats_non_nurse_specialist1 + $chef;
							//$detail_stats_non_nurse = $non_nurse_count->get()->count();
						
							$detail_stats_non_nurse1 = $non_nurse_count->get()->count();
						   $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
							$chef=$chef_count->get()->count();
							$detail_stats_non_nurse=$detail_stats_non_nurse1 + $chef;
						
                    }
        }
            
        $page_name = 'crm_req_reject';
        $page_title = 'Request Reject';
        $source_res='';
        $user_statistics_modal_body = view('administrator.partial.applicants_details_stats', 
        compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','detail_stats_non_nurse_specialist','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];

        }
        else if($stats_type=='crm_rebook')
        {
			
			$source_result = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source',DB::raw('count(*) as count'))	
            ->whereIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic'])
            ->where('history.sub_stage', "crm_rebook")
            ->whereIn('history.id', function ($query) {
            $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
            });
            $unknown_source = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source')	
            ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic'])
            ->where('history.sub_stage', "crm_rebook")
            ->whereIn('history.id', function ($query) {
            $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
            });

            if($range=='daily')
            {
				$source_result->where('history.history_added_date', $range_date)->count();	
                $unknown_source->where('history.history_added_date', $range_date)->count();	
            }
            else if($range=='weekly')
            {
                $source_result->whereBetween('history.created_at',$range_date)->count();	
                $unknown_source->whereBetween('history.created_at',$range_date)->count();
            }
            else if($range=='monthly')
            {
                $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();	
                $unknown_source->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $source_result->whereBetween('history.created_at', [$start_date, $end_date])->count();	
                $unknown_source->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $source_res=$source_result->groupBy('applicants.applicant_source')->get();
            $unknown_source_res = $unknown_source->get()->count();



            $results = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
            ->select('applicants.job_category',DB::raw('count(*) as count'))
            ->where('history.sub_stage', "crm_rebook")
            ->whereIn('history.id', function ($query) {
            $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
            });
            if($range=='daily')
            {
                $results->where('history.history_added_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $results->whereBetween('history.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $results->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $results->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
			$result=$results->groupBy('applicants.job_category')->get();
            
            foreach ($result as $data)
        {
            if($data->job_category=='nurse')
                    {
                        $detail_stats_nurse=$data->count;
                    }
                    else
                    {
                        $non_nurse_specialist_count = '';
						$non_nurse_count = '';
						
						$non_nurse_specialist_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
						->where(['history.sub_stage' => "crm_rebook","applicants.job_category"=>"non-nurse",
								 "applicants.applicant_job_title" => "nonnurse specialist"])
						->whereIn('history.id', function ($query) {
						$query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" 
						AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
						});

						$non_nurse_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
							->where(['history.sub_stage' => "crm_rebook"])
							->whereIn("applicants.job_category",["non-nurse",'nursery'])
							->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist'])
							->whereIn('history.id', function ($query) {
						$query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" 
						AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
						});
						
						$chef_count= History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
		                ->where(['history.sub_stage' => "crm_rebook","applicants.job_category"=>"chef"])
							->whereIn('history.id', function ($query) {
						$query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" 
						AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
						});

						
						if($range=='daily')
						{
							$non_nurse_specialist_count->where('history.history_added_date', $range_date);																$non_nurse_count->where('history.history_added_date', $range_date);
							$chef_count->where('history.history_added_date', $range_date);
						}
						else if($range=='weekly')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at',$range_date);																$non_nurse_count->whereBetween('history.created_at',$range_date);
							$chef_count->whereBetween('history.created_at',$range_date);
						}
						else if($range=='monthly')
						{
							$non_nurse_specialist_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);																						$non_nurse_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
							$chef_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
						}
						else if($range=='aggregate')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);													$non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
						}
					
							//$detail_stats_non_nurse_specialist1 = $non_nurse_specialist_count->get()->count();
						    //$chef=$chef_count->get()->count();
                            //$detail_stats_non_nurse_specialist=$detail_stats_non_nurse_specialist1 + $chef;
							//$detail_stats_non_nurse = $non_nurse_count->get()->count();
						
							$detail_stats_non_nurse1 = $non_nurse_count->get()->count();
						   $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
							$chef=$chef_count->get()->count();
							$detail_stats_non_nurse=$detail_stats_non_nurse1 + $chef;
						
                    }
        }
            
        $page_name = 'crm_rebook';
        $page_title = 'Rebook';
        //$source_res='';
        $user_statistics_modal_body = view('administrator.partial.applicants_details_stats', 
        compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','detail_stats_non_nurse_specialist','unknown_source_res','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];

        }
        else if($stats_type=='crm_not_attended')
        {
			
			$source_result = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source',DB::raw('count(*) as count'))	
            ->whereIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic'])
            ->where(['history.sub_stage' => 'crm_interview_not_attended', 'history.status' => 'active']);
            $unknown_source = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source')	
            ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic'])
            ->where(['history.sub_stage' => 'crm_interview_not_attended', 'history.status' => 'active']);

            if($range=='daily')
            {
				$source_result->where('history.history_added_date', $range_date)->count();	
                $unknown_source->where('history.history_added_date', $range_date)->count();	
            }
            else if($range=='weekly')
            {
                $source_result->whereBetween('history.created_at',$range_date)->count();	
                $unknown_source->whereBetween('history.created_at',$range_date)->count();
            }
            else if($range=='monthly')
            {
                $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();	
                $unknown_source->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $source_result->whereBetween('history.created_at', [$start_date, $end_date])->count();	
                $unknown_source->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $source_res=$source_result->groupBy('applicants.applicant_source')->get();
            $unknown_source_res = $unknown_source->get()->count();




            $results = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
            ->select('applicants.job_category',DB::raw('count(*) as count'))
            ->where(['history.sub_stage' => 'crm_interview_not_attended', 'history.status' => 'active']);
            if($range=='daily')
            {
                $results->where('history.history_added_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $results->whereBetween('history.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $results->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $results->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
			$result=$results->groupBy('applicants.job_category')->get();
            foreach ($result as $data)
            {  
                if($data->job_category=='nurse')
                {
                    $detail_stats_nurse=$data->count;
                }
                else
                {
                    $non_nurse_specialist_count = '';
						$non_nurse_count = '';
					
						$non_nurse_specialist_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
						->where(['history.sub_stage' => 'crm_interview_not_attended', 'history.status' => 'active',
								 "applicants.job_category"=>"non-nurse",
								 "applicants.applicant_job_title" => "nonnurse specialist"]);
					
						$non_nurse_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
						->where(['history.sub_stage' => 'crm_interview_not_attended', 'history.status' => 'active'])
							->whereIn("applicants.job_category",["non-nurse",'nursery'])
						->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
					
					$chef_count= History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
		        ->where(['history.sub_stage' => "crm_interview_not_attended","applicants.job_category"=>"chef"]);

						
						if($range=='daily')
						{
							$non_nurse_specialist_count->where('history.history_added_date', $range_date);																$non_nurse_count->where('history.history_added_date', $range_date);
							$chef_count->where('history.history_added_date', $range_date);
						}
						else if($range=='weekly')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at',$range_date);																$non_nurse_count->whereBetween('history.created_at',$range_date);
							$chef_count->whereBetween('history.created_at',$range_date);
						}
						else if($range=='monthly')
						{
							$non_nurse_specialist_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);																						$non_nurse_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
							$chef_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
						}
						else if($range=='aggregate')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);													$non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
						}
					
							//$detail_stats_non_nurse_specialist1 = $non_nurse_specialist_count->get()->count();
					        //$chef=$chef_count->get()->count();
                           //$detail_stats_non_nurse_specialist=$detail_stats_non_nurse_specialist1 + $chef;
							//$detail_stats_non_nurse = $non_nurse_count->get()->count();
					   
							$detail_stats_non_nurse1 = $non_nurse_count->get()->count();
						    $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
							$chef=$chef_count->get()->count();
							$detail_stats_non_nurse=$detail_stats_non_nurse1 + $chef;
						
                }
            }
            
        $page_name = 'crm_not_attended';
        $page_title = 'Not Attended';
        //$source_res='';
        $user_statistics_modal_body = view('administrator.partial.applicants_details_stats', 
        compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','detail_stats_non_nurse_specialist','unknown_source_res','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];

        }
        else if($stats_type=='crm_start_date_hold')
        {
			
			$source_result = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source',DB::raw('count(*) as count'))	
            ->whereIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic'])
            ->where(['history.sub_stage' => 'crm_start_date_hold', 'history.status' => 'active']);
            $unknown_source = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source')	
            ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic'])
            ->where(['history.sub_stage' => 'crm_start_date_hold', 'history.status' => 'active']);

            if($range=='daily')
            {
				$source_result->where('history.history_added_date', $range_date)->count();	
                $unknown_source->where('history.history_added_date', $range_date)->count();	
            }
            else if($range=='weekly')
            {
                $source_result->whereBetween('history.created_at',$range_date)->count();	
                $unknown_source->whereBetween('history.created_at',$range_date)->count();
            }
            else if($range=='monthly')
            {
                $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();	
                $unknown_source->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $source_result->whereBetween('history.created_at', [$start_date, $end_date])->count();	
                $unknown_source->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $source_res=$source_result->groupBy('applicants.applicant_source')->get();
            $unknown_source_res = $unknown_source->get()->count();


            $results = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
            ->select('applicants.job_category',DB::raw('count(*) as count'))
            ->where(['history.sub_stage' => 'crm_start_date_hold', 'history.status' => 'active']);
            if($range=='daily')
            {
                $results->where('history.history_added_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $results->whereBetween('history.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $results->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $results->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
			$result=$results->groupBy('applicants.job_category')->get();
            foreach ($result as $data)
            {  
                if($data->job_category=='nurse')
                {
                    $detail_stats_nurse=$data->count;
                }
                else
                {
                    $non_nurse_specialist_count = '';
						$non_nurse_count = '';
						$non_nurse_specialist_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
						->where(['history.sub_stage' => 'crm_start_date_hold', 'history.status' => 'active',
								 "applicants.job_category"=>"non-nurse",
								 "applicants.applicant_job_title" => "nonnurse specialist"]);

						$non_nurse_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
							->where(['history.sub_stage' => 'crm_start_date_hold', 'history.status' => 'active'])
							->whereIn("applicants.job_category",["non-nurse",'nursery'])
							->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
					
					$chef_count= History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
		->where(['history.sub_stage' => "crm_start_date_hold","applicants.job_category"=>"chef"]);
	
					
						if($range=='daily')
						{
							$non_nurse_specialist_count->where('history.history_added_date', $range_date);																$non_nurse_count->where('history.history_added_date', $range_date);
							$chef_count->where('history.history_added_date', $range_date);
						}
						else if($range=='weekly')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at',$range_date);																$non_nurse_count->whereBetween('history.created_at',$range_date);
							$chef_count->whereBetween('history.created_at',$range_date);
						}
						else if($range=='monthly')
						{
							$non_nurse_specialist_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);																						$non_nurse_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
							$chef_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
						}
						else if($range=='aggregate')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);													$non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
						}
					
							//$detail_stats_non_nurse_specialist1 = $non_nurse_specialist_count->get()->count();
					        //$chef=$chef_count->get()->count();
                           //$detail_stats_non_nurse_specialist=$detail_stats_non_nurse_specialist1 + $chef;
							//$detail_stats_non_nurse = $non_nurse_count->get()->count();
					
							$detail_stats_non_nurse1 = $non_nurse_count->get()->count();
						   $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
							$chef=$chef_count->get()->count();
							$detail_stats_non_nurse=$detail_stats_non_nurse1 + $chef;
						
                }
            }
            
        $page_name = 'crm_start_date_hold';
        $page_title = 'Start Date Hold';
        //$source_res='';
        $user_statistics_modal_body = view('administrator.partial.applicants_details_stats',
         compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','detail_stats_non_nurse_specialist','unknown_source_res','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];

        }
        else if($stats_type=='crm_declined')
        {
			
		$source_result = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source',DB::raw('count(*) as count'))	
            ->whereIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic'])
            ->where(['history.sub_stage' => 'crm_declined', 'history.status' => 'active']);
            $unknown_source = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source')	
            ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic'])
            ->where(['history.sub_stage' => 'crm_declined', 'history.status' => 'active']);

            if($range=='daily')
            {
				$source_result->where('history.history_added_date', $range_date)->count();	
                $unknown_source->where('history.history_added_date', $range_date)->count();	
            }
            else if($range=='weekly')
            {
                $source_result->whereBetween('history.created_at',$range_date)->count();	
                $unknown_source->whereBetween('history.created_at',$range_date)->count();
            }
            else if($range=='monthly')
            {
                $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();	
                $unknown_source->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $source_result->whereBetween('history.created_at', [$start_date, $end_date])->count();	
                $unknown_source->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $source_res=$source_result->groupBy('applicants.applicant_source')->get();
            $unknown_source_res = $unknown_source->get()->count();


        $results = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
        ->select('applicants.job_category',DB::raw('count(*) as count'))
        ->where(['history.sub_stage' => 'crm_declined', 'history.status' => 'active']);
        if($range=='daily')
            {
                $results->where('history.history_added_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $results->whereBetween('history.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $results->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $results->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
			$result=$results->groupBy('applicants.job_category')->get();
        foreach ($result as $data)
            {  
                if($data->job_category=='nurse')
                {
                    $detail_stats_nurse=$data->count;
                }
                else
                {
                    $non_nurse_specialist_count = '';
						$non_nurse_count = '';
							$non_nurse_specialist_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
									->where(['history.sub_stage' => 'crm_declined', 'history.status' => 'active',
								 "applicants.job_category"=>"non-nurse",
								 "applicants.applicant_job_title" => "nonnurse specialist"]);
							$non_nurse_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
									->where(['history.sub_stage' => 'crm_declined', 'history.status' => 'active'])
								->whereIn("applicants.job_category",["non-nurse",'nursery'])
								->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
					$chef_count= History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
		         ->where(['history.sub_stage' => "crm_declined","applicants.job_category"=>"chef"]);

						
						if($range=='daily')
						{
							$non_nurse_specialist_count->where('history.history_added_date', $range_date);																$non_nurse_count->where('history.history_added_date', $range_date);
							$chef_count->where('history.history_added_date', $range_date);
						}
						else if($range=='weekly')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at',$range_date);																$non_nurse_count->whereBetween('history.created_at',$range_date);
								$chef_count->whereBetween('history.created_at',$range_date);
						}
						else if($range=='monthly')
						{
							$non_nurse_specialist_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);																						$non_nurse_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
							$chef_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
						}
						else if($range=='aggregate')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);													$non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
						}
					
							//$detail_stats_non_nurse_specialist1 = $non_nurse_specialist_count->get()->count();
					      //$chef=$chef_count->get()->count();
                           //$detail_stats_non_nurse_specialist=$detail_stats_non_nurse_specialist1 + $chef;
							//$detail_stats_non_nurse = $non_nurse_count->get()->count();
					
							$detail_stats_non_nurse1 = $non_nurse_count->get()->count();
						   $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
							$chef=$chef_count->get()->count();
							$detail_stats_non_nurse=$detail_stats_non_nurse1 + $chef;
					
                }
            }
            
        $page_name = 'crm_declined';
        $page_title = 'Declined';
        //$source_res='';
        $user_statistics_modal_body = view('administrator.partial.applicants_details_stats',
         compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','detail_stats_non_nurse_specialist','unknown_source_res','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];


        }
        else if($stats_type=='crm_dispute')
        {
			
		$source_result = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source',DB::raw('count(*) as count'))	
            ->whereIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic'])
            ->where(['history.sub_stage' => 'crm_dispute', 'history.status' => 'active']);
            $unknown_source = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
            ->select('applicants.applicant_source')	
            ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic'])
            ->where(['history.sub_stage' => 'crm_dispute', 'history.status' => 'active']);

            if($range=='daily')
            {
				$source_result->where('history.history_added_date', $range_date)->count();	
                $unknown_source->where('history.history_added_date', $range_date)->count();	
            }
            else if($range=='weekly')
            {
                $source_result->whereBetween('history.created_at',$range_date)->count();	
                $unknown_source->whereBetween('history.created_at',$range_date)->count();
            }
            else if($range=='monthly')
            {
                $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();	
                $unknown_source->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $source_result->whereBetween('history.created_at', [$start_date, $end_date])->count();	
                $unknown_source->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $source_res=$source_result->groupBy('applicants.applicant_source')->get();
            $unknown_source_res = $unknown_source->get()->count();


        $results= History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
        ->select('applicants.job_category',DB::raw('count(*) as count'))
        ->where(['history.sub_stage' => 'crm_dispute', 'history.status' => 'active']);
        if($range=='daily')
            {
                $results->where('history.history_added_date', $range_date)->count();
            }
            else if($range=='weekly')
            {
                $results->whereBetween('history.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $results->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $results->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
			$result=$results->groupBy('applicants.job_category')->get();

        
        foreach ($result as $data)
            {  
                if($data->job_category=='nurse')
                {
                    $detail_stats_nurse=$data->count;
                }
                else
                {
                    $non_nurse_specialist_count = '';
						$non_nurse_count = '';
					
								$non_nurse_specialist_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
									->where(['history.sub_stage' => 'crm_dispute', 'history.status' => 'active',
								 "applicants.job_category"=>"non-nurse",
								 "applicants.applicant_job_title" => "nonnurse specialist"]);
							$non_nurse_count = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
									->where(['history.sub_stage' => 'crm_dispute', 'history.status' => 'active'])
								->whereIn("applicants.job_category",["non-nurse",'nursery'])
								->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
						$chef_count= History::join('applicants', 'applicants.id', '=', 'history.applicant_id')	
		       ->where(['history.sub_stage' => "crm_dispute","applicants.job_category"=>"chef"]);

						if($range=='daily')
						{
							$non_nurse_specialist_count->where('history.history_added_date', $range_date);																$non_nurse_count->where('history.history_added_date', $range_date);
							$chef_count->where('history.history_added_date', $range_date);
						}
						else if($range=='weekly')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at',$range_date);																$non_nurse_count->whereBetween('history.created_at',$range_date);
							$chef_count->whereBetween('history.created_at',$range_date);
						}
						else if($range=='monthly')
						{
							$non_nurse_specialist_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);																						$non_nurse_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
							$chef_count->whereMonth('history.created_at', $current_month)
								->whereYear('history.created_at', $current_year);
						}
						else if($range=='aggregate')
						{
							$non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);													$non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
							
						}
					
							//$detail_stats_non_nurse_specialist1 = $non_nurse_specialist_count->get()->count();
					        //$chef=$chef_count->get()->count();
                           //$detail_stats_non_nurse_specialist=$detail_stats_non_nurse_specialist1 + $chef;
							//$detail_stats_non_nurse = $non_nurse_count->get()->count();
					  
							$detail_stats_non_nurse1 = $non_nurse_count->get()->count();
						    $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
							$chef=$chef_count->get()->count();
							$detail_stats_non_nurse=$detail_stats_non_nurse1 + $chef;
						
                }
            }
        $page_name = 'crm_dispute';
        $page_title = 'Disputed';
        //$source_res='';
        $user_statistics_modal_body = view('administrator.partial.applicants_details_stats', 
        compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','detail_stats_non_nurse_specialist','unknown_source_res','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];
        }
		 
		  else if($stats_type=='crm_revert')
        {
            $source_result = RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                ->where('stage','crm_revert')->select('applicants.applicant_source',DB::raw('count(*) as count'))
                ->whereIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source'])
                ;
            $unknown_source= RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                ->where('stage','crm_revert')->select('applicants.applicant_source',DB::raw('count(*) as count'))
                ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source']);



            // Loop through each $crm_revert record and get corresponding history records


            if($range=='daily')
            {


                $source_result->where('revert_added_date', $range_date)->count();
                $unknown_source->where('revert_added_date', $range_date)->count();

            }
            else if($range=='weekly')
            {

                $test=$source_result->whereBetween('revert_stages.created_at',$range_date)->count();
                $test1=$unknown_source->whereBetween('revert_stages.created_at',$range_date)->count();
//                dd($test,$test1);
            }
            else if($range=='monthly')
            {
                $source_result->whereMonth('revert_stages.created_at', $current_month)->whereYear('revert_stages.created_at', $current_year)->count();
                $unknown_source->whereMonth('revert_stages.created_at', $current_month)->whereYear('revert_stages.created_at', $current_year)->count();
            }
            $source_res=$source_result->groupBy('applicants.applicant_source')->get();
            $unknown_source_res = $unknown_source->get()->count();

            $results= RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
               ->select('applicants.job_category',DB::raw('count(*) as count'))
               ->where('stage','crm_revert');





            if($range=='daily')
            {

                $results->where('revert_added_date', $range_date)->count();

            }
            else if($range=='weekly')
            {
                $results->whereBetween('revert_stages.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $results->whereMonth('revert_stages.created_at', $current_month)->whereYear('revert_stages.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $results->whereBetween('revert_stages.created_at', [$start_date, $end_date])->count();
            }
            $result=$results->groupBy('applicants.job_category')->get();


            foreach ($result as $data)
            {
                if($data->job_category=='nurse')
                {
                    $detail_stats_nurse=$data->count;
                }
                else
                {
                    $non_nurse_specialist_count = '';
                    $non_nurse_count = '';

                    $non_nurse_specialist_count = RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                        ->where([ "applicants.job_category"=>"non-nurse","applicants.applicant_job_title" => "nonnurse specialist"])
                        ->where('stage','crm_revert');

                    $non_nurse_count = RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                    ->whereIn("applicants.job_category",["non-nurse",'nursery'])
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist'])
                    ->where('stage','crm_revert');
                    $chef = RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                        ->where([ "applicants.job_category"=>"chef"])
                        ->where('stage','crm_revert');

                    $non_nurse_count_old = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
                        ->where(['history.sub_stage' => 'crm_dispute', 'history.status' => 'active'])
						->whereIn("applicants.job_category",["non-nurse",'nursery'])
                        ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                    $non_nurse_specialist_count1 = History::join('applicants', 'applicants.id', '=', 'history.applicant_id')
                        ->where(['history.sub_stage' => 'crm_dispute', 'history.status' => 'active',
                            "applicants.job_category"=>"non-nurse",
                            "applicants.applicant_job_title" => "nonnurse specialist"]);

                    if($range=='daily')
                    {

                        //$non_nurse_count->where('revert_added_date', $range_date);
                    	$non_nurse_count->where('revert_added_date', $range_date);
                        $non_nurse_specialist_count->where('revert_added_date', $range_date);
                        $chef->where('revert_added_date', $range_date);


                    }
                    else if($range=='weekly')
                    {
                        $non_nurse_specialist_count->whereBetween('revert_stages.created_at',$range_date);
                        $non_nurse_count->whereBetween('revert_stages.created_at',$range_date);
                        $chef->whereBetween('revert_stages.created_at',$range_date);
                    }
                    else if($range=='monthly')
                    {
                        $non_nurse_specialist_count->whereMonth('revert_stages.created_at', $current_month)
                            ->whereYear('revert_stages.created_at', $current_year);
                        $non_nurse_count->whereMonth('revert_stages.created_at', $current_month)
                        ->whereYear('revert_stages.created_at', $current_year);
                        $chef->whereMonth('revert_stages.created_at', $current_month)
                            ->whereYear('revert_stages.created_at', $current_year);
                    }
//                    else if($range=='aggregate')
//                    {
//                        $non_nurse_specialist_count->whereBetween('history.created_at', [$start_date, $end_date]);													$non_nurse_count->whereBetween('history.created_at', [$start_date, $end_date]);
//                    }

                    $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
                    $detail_stats_non_nurse1 = $non_nurse_count->get()->count();
                    $chef_count=$chef->get()->count();
                    $detail_stats_non_nurse=$detail_stats_non_nurse1 +$chef_count;
                }
            }
            $page_name = 'crm_revert';
            $page_title = 'revert';
            //$source_res='';
            $user_statistics_modal_body = view('administrator.partial.applicants_details_stats',
                compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','detail_stats_non_nurse_specialist','unknown_source_res','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];
        }
		         else if($stats_type=='quality_revert')
        {
            $source_result = RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                ->where('stage','quality_revert')->select('applicants.applicant_source',DB::raw('count(*) as count'))
                ->whereIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media'
                    ,'Referral','Other Source'])
                ;
            $unknown_source=RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                ->where('stage','quality_revert')
                ->select('applicants.applicant_source',DB::raw('count(*) as count'))
                ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source']);



            // Loop through each $crm_revert record and get corresponding history records


            if($range=='daily')
            {
                $source_result->where('revert_added_date', $range_date)->count();
                $unknown_source->where('revert_added_date', $range_date)->count();
            }
            else if($range=='weekly')
            {

               $source_result->whereBetween('revert_stages.created_at',$range_date)->count();
               $unknown_source->whereBetween('revert_stages.created_at',$range_date)->count();

            }
            else if($range=='monthly')
            {
                $source_result->whereMonth('revert_stages.created_at', $current_month)->whereYear('revert_stages.created_at', $current_year)->count();
                $unknown_source->whereMonth('revert_stages.created_at', $current_month)->whereYear('revert_stages.created_at', $current_year)->count();
            }
            $source_res=$source_result->groupBy('applicants.applicant_source')->get();
            $unknown_source_res = $unknown_source->count();
//            $unknown_source_res = $unknown_source->get()->count();


            $results= RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
               ->select('applicants.job_category',DB::raw('count(*) as count'))
               ->where('stage','quality_revert');





            if($range=='daily')
            {

                $results->where('revert_added_date', $range_date)->count();

            }
            else if($range=='weekly')
            {
                $results->whereBetween('revert_stages.created_at', $range_date)->count();
            }
            else if($range=='monthly')
            {
                $results->whereMonth('revert_stages.created_at', $current_month)->whereYear('revert_stages.created_at', $current_year)->count();
            }
            else if($range=='aggregate')
            {
                $results->whereBetween('history.created_at', [$start_date, $end_date])->count();
            }
            $result=$results->groupBy('applicants.job_category')->get();


            foreach ($result as $data)
            {
                if($data->job_category=='nurse')
                {
                    $detail_stats_nurse=$data->count;
                }
                else
                {
                    $non_nurse_specialist_count = '';
                    $non_nurse_count = '';

                    $non_nurse_specialist_count = RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                        ->where([ "applicants.job_category"=>"non-nurse","applicants.applicant_job_title" => "nonnurse specialist"])
                        ->where('stage','quality_revert');

                    $chef = RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                        ->where([ "applicants.job_category"=>"chef",])
                        ->where('stage','quality_revert');

                    $non_nurse_count = RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                    ->whereIn("applicants.job_category",["non-nurse",'nursery'])
                    ->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist'])
                    ->where('stage','quality_revert');



                    if($range=='daily')
                    {

                        //$non_nurse_count->where('revert_added_date', $range_date);
                    	$non_nurse_count->where('revert_added_date', $range_date);
                        $non_nurse_specialist_count->where('revert_added_date', $range_date);
                        $chef->where('revert_added_date', $range_date);

                    }
                    else if($range=='weekly')
                    {
                        $non_nurse_specialist_count->whereBetween('revert_stages.created_at',$range_date);
                        $non_nurse_count->whereBetween('revert_stages.created_at',$range_date);
                        $chef->whereBetween('revert_stages.created_at',$range_date);
                    }
                    else if($range=='monthly')
                    {
                        $non_nurse_specialist_count->whereMonth('revert_stages.created_at', $current_month)
                            ->whereYear('revert_stages.created_at', $current_year);
                        $non_nurse_count->whereMonth('revert_stages.created_at', $current_month)
                        ->whereYear('revert_stages.created_at', $current_year);
                        $chef->whereMonth('revert_stages.created_at', $current_month)
                            ->whereYear('revert_stages.created_at', $current_year);
                    }

                    $detail_stats_non_nurse_specialist = $non_nurse_specialist_count->get()->count();
                    $detail_stats_non_nurse1 = $non_nurse_count->get()->count();
                    $chef_count = $chef->get()->count();
                    $detail_stats_non_nurse = $detail_stats_non_nurse1 + $chef_count;
                }
            }
            $page_name = 'quality_revert';
            $page_title = 'Quality revert';
            //$source_res='';
            $user_statistics_modal_body = view('administrator.partial.applicants_details_stats',
                compact('detail_stats_nurse', 'detail_stats_non_nurse','page_name','page_title','source_res','detail_stats_non_nurse_specialist','unknown_source_res','stats_date','range','stats_type'))->render();
            return ['user_stats'=>$user_statistics_modal_body,'user_name'=>$user_name];
        }

		 
    }
	
     public function userTypeDetailsStats($applicant_type, $no_of_app, $stats_type_stage, $home,$range, $stats_date,$updateRecord = null,$unknown_src = null)
    {
        //no_of_nurses_update
        if ($updateRecord == "nurse_update"){
				$applicant_type_home =decrypt($applicant_type);

				$user_home='';
				$user_home = ($home == 'Non Nurse') ? ['non-nurse', 'nursery'] : ['nurse'];

				$today = Carbon::today();
				$daily_date='';
				$start_of_week_date='';
				$end_of_week_date='';
				$current_month = '';
				$current_year = '';
				if($range=='daily')
				{
					$daily_date = $stats_date;

				} else if($range=='weekly'){

					$date_weekly = explode("&", $stats_date);
					$conv_date_value = date('Y-m-d H:i:s', strtotime($date_weekly[0]));
					$converted_date=(new Carbon($conv_date_value));

					$start_of_week_date = $converted_date->copy()->startOfWeek(Carbon::MONDAY);
					$end_of_week_date = $converted_date->copy()->endOfWeek(Carbon::SUNDAY);

				}
				else if($range=='monthly')
				{
					//$date_value = Carbon::today()->format('m/Y');
					//$date_monthly = explode("/", $date_value);
					$date_month_year = explode("&", $stats_date);
					$current_month= $date_month_year[0];
					$current_year= $date_month_year[1];

				}
				else if($range=='aggregate')
				{
					$aggregate_date = explode("&", $stats_date);
					$start_date = new Carbon($aggregate_date[0]);
					$end_date = new Carbon($aggregate_date[1]);
				}
				$range_date='';
				$weekly_range_reject_cv='';
				$daily_range='';
				$daily_range_reject_cv='';



				if($range=='daily')
				{
					$range_date=$daily_date;
				}
				else if($range=='weekly')
				{
					$range_date=[$start_of_week_date, $end_of_week_date];
				}
				// $stats_type = $request->input('user_name');
				$formatted_date=$today->format('jS F Y');
				$start_date = new Carbon('2019-06-27 00:00:00');
				$end_date = $today->copy()->endOfDay();

		//
				if($unknown_src != null)
				{
					$source_result = Applicant::with(['cv_notes' => function($query) {
								$query->select('status', 'applicant_id', 'sale_id', 'user_id')
									->with(['user:id,name'])->latest();
							}])->select('id', 'applicant_job_title', 'applicant_name', 'applicant_postcode',  'applicant_phone', 'applicant_homePhone', 'applicant_added_date', 'applicant_added_time', 'job_category', 'applicant_source', 'applicant_notes')
						->where(['status' => 'active'])
						->whereNotIn('applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source','Monster','Jobmedic'])
						->whereIn('job_category' , $user_home);
				}
				else
				{
					$source_result = Applicant::with(['cv_notes' => function($query) {
								$query->select('status', 'applicant_id', 'sale_id', 'user_id')
									->with(['user:id,name'])->latest(); // Eager load the 'user' relationship and only select 'id' and 'name'
							}])->select('id', 'applicant_job_title', 'applicant_name', 'applicant_postcode', 'applicant_phone', 'applicant_homePhone', 'applicant_added_date', 'applicant_added_time', 'job_category', 'applicant_source', 'applicant_notes')
						->where(['status' => 'active', 'applicant_source' => $applicant_type_home])->whereIn('job_category' , $user_home);
				}
				$userData ='';
				if($range=='daily')
				{
					$userData =$source_result->whereDate('updated_at', $range_date)->whereColumn('updated_at', '!=', 'created_at')->get();
				}
				else if($range=='weekly')
				{
		//            $userData =$source_result->whereBetween('created_at',$range_date)->get();
					$userData =$source_result->whereBetween('updated_at',$range_date)->whereColumn('updated_at', '!=', 'created_at')->get();
		//            $userData =$source_result->get();

				}
				else if($range=='monthly')
				{
					$userData = $source_result->whereMonth('updated_at', $current_month)->whereYear('updated_at', $current_year)->whereMonth('updated_at', '!=', 'created_at')->get();

				}
				else if($range=='aggregate')
				{
					$userData = $source_result->whereBetween('updated_at', [$start_date, $end_date])->whereColumn('updated_at', '!=', 'created_at')->get();
				}

				return view('administrator.dashboard.applicants_stats_details', compact('userData','stats_date',
					'no_of_app', 'stats_type_stage','home','range','applicant_type','unknown_src'));
				//end code
        }else{
            $applicant_type_home = decrypt($applicant_type);
            $user_home='';
           	$user_home = ($home == 'Non Nurse') ? ['non-nurse', 'nursery'] : ['nurse'];
            $today = Carbon::today();
            $daily_date='';
            $start_of_week_date='';
            $end_of_week_date='';
            $current_month = '';
            $current_year = '';
            if($range=='daily')
            {
                $daily_date = $stats_date;

            }
            else if($range=='weekly')
            {
                $date_weekly = explode("&", $stats_date);
                $conv_date_value = date('Y-m-d H:i:s', strtotime($date_weekly[0]));
                $converted_date=(new Carbon($conv_date_value));

                $start_of_week_date = $converted_date->copy()->startOfWeek(Carbon::MONDAY);
                $end_of_week_date = $converted_date->copy()->endOfWeek(Carbon::SUNDAY);

            }
            else if($range=='monthly')
            {
                //$date_value = Carbon::today()->format('m/Y');
                //$date_monthly = explode("/", $date_value);
                $date_month_year = explode("&", $stats_date);
                $current_month= $date_month_year[0];
                //echo $date_value;exit();
                $current_year= $date_month_year[1];

            }
            else if($range=='aggregate')
            {
                $aggregate_date = explode("&", $stats_date);
                $start_date = new Carbon($aggregate_date[0]);
                $end_date = new Carbon($aggregate_date[1]);
            }
            $range_date='';
            $weekly_range_reject_cv='';
            $daily_range='';
            $daily_range_reject_cv='';



            if($range=='daily')
            {
                $range_date=$daily_date;
            }
            else if($range=='weekly')
            {
                $range_date=[$start_of_week_date, $end_of_week_date];
            }
            // $stats_type = $request->input('user_name');
            $formatted_date=$today->format('jS F Y');
            $start_date = new Carbon('2019-06-27 00:00:00');
            $end_date = $today->copy()->endOfDay();


            if($unknown_src != null)
            {
                $source_result = Applicant::select('id','applicant_job_title','applicant_name','applicant_postcode',
                    'applicant_phone','applicant_homePhone','applicant_added_date','applicant_added_time','job_category','applicant_source','applicant_notes')
                    ->where(['status' => 'active'])
                    ->whereNotIn('applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source'])
					->whereIn('job_category' , $user_home );
            }
            else
            {
                $source_result = Applicant::select('id','applicant_job_title','applicant_name','applicant_postcode',
                    'applicant_phone','applicant_homePhone','applicant_added_date','applicant_added_time','job_category','applicant_source','applicant_notes')
                    ->where(['status' => 'active', 'applicant_source' => $applicant_type_home])->whereIn('job_category' , $user_home );
            }
            $userData ='';
            if($range=='daily')
            {
                $userData =$source_result->whereDate('created_at', $range_date)->get();
            }
            else if($range=='weekly')
            {
                $userData =$source_result->whereBetween('created_at',$range_date)->get();
//                dd($userData->count());
            }
            else if($range=='monthly')
            {
                $userData = $source_result->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->get();
//               dd($userData->count());

            }
            else if($range=='aggregate')
            {
                $userData = $source_result->whereBetween('created_at', [$start_date, $end_date])->get();
            }
            return view('administrator.dashboard.applicants_stats_details', compact('userData','stats_date',
                'no_of_app', 'stats_type_stage','home','range','applicant_type','unknown_src'));

        }

    }

	
public function appCrmTypeDetailsStats($user_home_type,$stats_date,$range,$stats_type,$unknown_src = null)
    {
        $applicant_type_home ='';
        if($unknown_src != null)
        {
            $applicant_type_home =$unknown_src;
        }
        else
        {
            $applicant_type_home =decrypt($user_home_type);
        }
        
        // echo $stats_type.' applicant_type_home'.$applicant_type_home;exit();

        if(strpos($stats_date, "&") !== false){
            $date_result = explode("&",$stats_date);
        }
        else
        {
            $date_result = $stats_date;

        }
        $today = Carbon::today();
        $daily_date='';
        $start_of_week_date='';
        $end_of_week_date='';
        $current_month = '';
        $current_year = '';
        if($range=='daily')
        {

            $daily_date = (new Carbon($date_result));

        }
        else if($range=='weekly')
        {

            $start_of_week_date = (new Carbon($date_result[0]));
            $end_of_week_date = (new Carbon($date_result[1]));
        }
        else if($range=='monthly')
        {
           //$date_value = (new Carbon($date_result));
						

            //$date_monthly = explode("/", $date_value);
			
            $current_month=(int)$date_result[0];
            $current_year=(int)$date_result[1];

        }
        else if($range=='aggregate')
        {
            $start_date = (new Carbon($date_result[0]));
            $end_date = (new Carbon($date_result[1]));
        }
        $range_date='';
        $weekly_range_reject_cv='';
        $daily_range='';
        $daily_range_reject_cv='';
       
        $source_result ='';
        $formatted_date=$today->format('jS F Y');
        if($range=='daily')
        {
            $range_date=$daily_date;
        }
        else if($range=='weekly')
        {
            $range_date=[$start_of_week_date, $end_of_week_date];
        }
        
            if($unknown_src != null)
            {
                $source_result = Applicant::join('history', 'applicants.id', '=', 'history.applicant_id')	
                ->select('applicants.id','applicants.applicant_job_title','applicants.applicant_name','applicants.applicant_postcode',
                'applicants.applicant_phone','applicants.applicant_homePhone','applicants.applicant_added_date','applicants.applicant_added_time','applicants.job_category','applicants.applicant_source','applicants.applicant_notes')	
                ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source']);
            }
            else
            {
                $source_result = Applicant::join('history', 'applicants.id', '=', 'history.applicant_id')	
                ->select('applicants.id','applicants.applicant_job_title','applicants.applicant_name','applicants.applicant_postcode',
                'applicants.applicant_phone','applicants.applicant_homePhone','applicants.applicant_added_date','applicants.applicant_added_time','applicants.job_category','applicants.applicant_source','applicants.applicant_notes')	
                ->where('applicants.applicant_source',$applicant_type_home);
            }
            if( $stats_type == 'quality_cleared')
            {
                $source_result->where('history.sub_stage', $stats_type);
            }
            else if($stats_type == 'crm_request'){
                $source_result->where('history.sub_stage', 'crm_request')	
                ->whereIn('history.id', function ($query) {
						$query->select(DB::raw('MAX(h.id) FROM history AS h 
						WHERE h.sub_stage = "crm_request" 
						AND history.applicant_id = h.applicant_id 
						AND history.sale_id = h.sale_id 
						AND history.id > (
							SELECT MAX(hh.id) FROM history AS hh 
							WHERE ( hh.sub_stage = "quality_cleared" OR hh.sub_stage = "crm_save" ) 
							AND history.applicant_id = hh.applicant_id AND history.sale_id = hh.sale_id 
						)'));
					});
            }
            else if($stats_type=='crm_confirmation'){
                $source_result->where('history.sub_stage', "crm_request_confirm")
                ->whereIn('history.id', function ($query) use ($formatted_date) {
                    $query->select(DB::raw('MAX(h.id) FROM history AS h 
                    WHERE h.sub_stage = "crm_request_confirm" 
                    AND history.applicant_id = h.applicant_id 
                    AND history.sale_id = h.sale_id 
                    AND history.id > ( 
                        SELECT MAX(hh.id) FROM history AS hh 
                        WHERE hh.sub_stage = "crm_request" 
                        AND history.applicant_id = hh.applicant_id 
                        AND history.sale_id = hh.sale_id 
                    )'));
                });
            }
            else if($stats_type=='crm_prestart_attended'){
                $source_result->where('history.sub_stage', 'crm_interview_attended')	
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_interview_attended" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                });
            }
            else if($stats_type=='crm_start_date'){
                $source_result->whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_back'])
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE ( h.sub_stage="crm_start_date" OR h.sub_stage="crm_start_date_back" )
                     AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                });
            }
            else if($stats_type=='crm_invoice'){
                $source_result->whereIn('history.sub_stage', ['crm_invoice','crm_invoice_sent'])
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage IN ("crm_invoice","crm_invoice_sent") AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                });
            }
            else if($stats_type=='crm_paid'){
                $source_result->where('history.sub_stage', $stats_type);
            }
            else if($stats_type=='crm_rebook'){
                $source_result->where('history.sub_stage', "crm_rebook")
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                    });
            }
            else if($stats_type=='crm_not_attended'){
                $source_result->where(['history.sub_stage' => 'crm_interview_not_attended', 'history.status' => 'active']);
            }
            else if($stats_type=='crm_start_date_hold'){
                $source_result->where(['history.sub_stage' => 'crm_start_date_hold', 'history.status' => 'active']);
            }
            else if($stats_type=='crm_declined'){
                $source_result->where(['history.sub_stage' => 'crm_declined', 'history.status' => 'active']);
            }
            else if($stats_type=='crm_dispute'){
                $source_result->where(['history.sub_stage' => 'crm_dispute', 'history.status' => 'active']);
            }
	
	else if($stats_type=='quality_revert'){
            if($unknown_src != null){

                $source_result=RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                    ->where('stage','quality_revert')
//                ->select('applicants.applicant_source',DB::raw('count(*) as count'))
                    ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source']);
                $userData ='';
                if($range=='daily')
                {
                    $userData =$source_result->whereDate('revert_stages.created_at', $range_date)->get();
                }
                else if($range=='weekly')
                {
                    $userData =$source_result->whereBetween('revert_stages.created_at',$range_date)->get();
                }
                else if($range=='monthly')
                {
                    $userData = $source_result->whereMonth('revert_stages.created_at', $current_month)->whereYear('revert_stages.created_at', $current_year)->get();
                }
                return view('administrator.partial.cv_revert',compact('userData','current_month','current_year','range','range_date'));


            }else{

                $source_result=RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                    ->where('stage','quality_revert')
                        ->where('applicants.applicant_source',$applicant_type_home);
                $userData ='';
                if($range=='daily')
                {
                    $userData =$source_result->whereDate('revert_stages.created_at', $range_date)->get();
                }
                else if($range=='weekly')
                {
                    $userData =$source_result->whereBetween('revert_stages.created_at',$range_date)->get();
                }
                else if($range=='monthly')
                {
                    $userData = $source_result->whereMonth('revert_stages.created_at', $current_month)->whereYear('revert_stages.created_at', $current_year)->get();
                }
                return view('administrator.partial.cv_revert',compact('userData','current_month','current_year','range','range_date'));


            }


        }
	else if($stats_type=='crm_revert'){
            if($unknown_src != null){

                $source_result= RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                    ->where('stage','crm_revert')
                    ->whereNotIn('applicants.applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source']);
                $userData ='';
                if($range=='daily')
                {
                    $userData =$source_result->whereDate('revert_stages.created_at', $range_date)->get();
                }
                else if($range=='weekly')
                {
                    $userData =$source_result->whereBetween('revert_stages.created_at',$range_date)->get();
                }
                else if($range=='monthly')
                {
                    $userData = $source_result->whereMonth('revert_stages.created_at', $current_month)->whereYear('revert_stages.created_at', $current_year)->get();
                }
                return view('administrator.partial.cv_revert',compact('userData','current_month','current_year','range','range_date'));


            }else{

                $source_result= RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                    ->where('stage','crm_revert')->where('applicants.applicant_source',$applicant_type_home);
                $userData ='';
                if($range == 'daily')
                {
                    $userData = $source_result->whereDate('revert_stages.created_at', $range_date)->get();
                }
                else if($range == 'weekly')
                {
                    $userData = $source_result->whereBetween('revert_stages.created_at',$range_date)->get();
                }
                else if($range == 'monthly')
                {
                    $userData = $source_result->whereMonth('revert_stages.created_at', $current_month)->whereYear('revert_stages.created_at', $current_year)->get();
                }

                return view('administrator.partial.cv_revert',compact('userData','current_month','current_year','range','range_date'));


            }


        }
            
            $userData ='';
            if($range=='daily')
            {
                $userData =$source_result->whereDate('history.created_at', $range_date)->get();
            }
            else if($range=='weekly')
            {
                $userData =$source_result->whereBetween('history.created_at',$range_date)->get();
            }
            else if($range=='monthly')
            {
                $userData = $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->get();
            }
            else if($range=='aggregate')
            {
                $userData = $source_result->whereBetween('history.created_at', [$start_date, $end_date])->get();
            }
            return view('administrator.dashboard.app_crm_applicants_stats_details', compact('userData','applicant_type_home'));

    }
	
public function applicantStatsDetailExport(Request $request)
    {
        $applicant_type_home =decrypt( $request->input('applicant_type'));
        // echo ' no_of_app: '.$no_of_app.', $stats_type_stage: '.$stats_type_stage.', $home:'.$home.', $range '.$range.' applicant_type'.$applicant_type_home.' date'.$date;exit();

        $user_home='';
        $home = $request->input('home');
        $date = $request->input('date_val');
        $range = $request->input('range');
        $stats_type_stage = $request->input('stats_type_stage');
        // $applicant_type_home = $request->input('applicant_type');
        $unknown_src = $request->input('unknown_src');
        if($home=='Non Nurse')
        {
            $user_home='non-nurse';
        }
        else
        {
            $user_home='nurse';
        }
        $today = Carbon::today();
        $daily_date='';
        $start_of_week_date='';
        $end_of_week_date='';
        $current_month = '';
        $current_year = '';
        if($range=='daily')
        {
            $daily_date = Carbon::today();

        }
        else if($range=='weekly')
        {
        $date_value = Carbon::now()->startOfWeek()->format('d-m-Y').' - '.Carbon::now()->format('d-m-Y');
            $date_weekly = explode(" ", $date_value);
            $conv_date_value = date('Y-m-d H:i:s', strtotime($date_weekly[0]));
            $converted_date=(new Carbon($conv_date_value));

            $start_of_week_date = $converted_date->copy()->startOfWeek(Carbon::MONDAY);
            $end_of_week_date = $converted_date->copy()->endOfWeek(Carbon::SUNDAY);
        }
        else if($range=='monthly')
        {
            $date_monthly = explode("&", $date);
            $current_month=(int)$date_monthly[0];
            $current_year=(int)$date_monthly[1];
        }
        else if($range=='aggregate')
        {
            $start_date = new Carbon('2019-06-27 00:00:00');
            $end_date = $today->copy()->endOfDay();
        }

        $range_date='';
        $weekly_range_reject_cv='';
        $daily_range='';
        $daily_range_reject_cv='';
       
        

        if($range=='daily')
        {
            $range_date=$daily_date;
        }
        else if($range=='weekly')
        {
            $range_date=[$start_of_week_date, $end_of_week_date];
        }
        // $stats_type = $request->input('user_name');
        $formatted_date=$today->format('jS F Y');
       $start_date = new Carbon('2019-06-27 00:00:00');
       $end_date = $today->copy()->endOfDay();
        
            if($unknown_src != null)
            {
                $source_result = Applicant::select('applicant_added_date','applicant_added_time','applicant_name','applicant_job_title','job_category','applicant_postcode',
                'applicant_phone','applicant_source','applicant_notes')
            ->where(['status' => 'active'])
            ->whereNotIn('applicant_source',['Total Jobs', 'Reed', 'Niche','CV Library','Social Media','Referral','Other Source'])
					->whereIn('job_category' , $user_home);
            
            }
            else
            {
                $source_result = 		Applicant::select('applicant_added_date','applicant_added_time','applicant_name','applicant_job_title','job_category','applicant_postcode',
            'applicant_phone','applicant_source','applicant_notes')
            	->where(['status' => 'active', 'applicant_source' => $applicant_type_home])
					->whereIn('job_category' , $user_home);
            }
            
            $userData ='';
            if($range=='daily')
            {
                $userData =$source_result->whereDate('created_at', $range_date)->get();
            }
            else if($range=='weekly')
            {
                $userData =$source_result->whereBetween('created_at',$range_date)->get();
            }
            else if($range=='monthly')
            {
                $userData = $source_result->whereMonth('created_at', $current_month)->whereYear('created_at', $current_year)->get();
            }
            else if($range=='aggregate')
            {
                $userData = $source_result->whereBetween('created_at', [$start_date, $end_date])->get();
            }
            // print_r($userData);exit();
            // return Excel::download(new ApplicantsMonthlyStatsDetails($userData), 'applicants.csv');
            $myFile =  Excel::raw(new ApplicantsMonthlyStatsDetails($userData), 'Xlsx');
    
    $response =  array(
        'name' => "Applicant-stats-details.xlsx",
        'file' => "data:application/vnd.ms-excel;base64,".base64_encode($myFile)
     );
     return response()->json($response);
    }
	
	  public function openSaleDetails(Request  $request){
        if ($request->app_daily_date!=null){

            $today=Carbon::parse($request->app_daily_date)->format('Y-m-d H:i:s');
        }

        return view('administrator.dashboard.saleDetails',compact('today'));

    }
    public function saleDetails(Request $request, $date){

            $today=Carbon::today();
            $sales= Sale::where(['status' => 'active'])->whereDate('created_at', $date)
                ->orderBy('sales.created_at', 'DESC')->get();

            return datatables()->of($sales)
                ->addIndexColumn()
                ->addColumn('category', function($row){
                    $cat=$row->job_category;
                    return $cat;
                })
               ->addColumn("updated_at", function ($open_sale) {
                    $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                        ->where(['audits.auditable_id' => $open_sale->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                        ->where('audits.message', 'like', '%has been updated%')
                        ->select('users.name')
                        ->orderBy('audits.created_at', 'desc')->first();
                    $updated_by = $updated_by ? $updated_by->name : $open_sale->name;
                    return '<span data-popup="tooltip" title="'.$updated_by.'">'.Carbon::parse($open_sale->updated_at)->toFormattedDateString().'</span>';
                })->addColumn("type", function ($closed_sale) {
                    return ucwords($closed_sale->job_type);
                })
                ->addColumn('job_title', function($row){
                    if($row->job_title_prof!=null)
                    {

                        $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $row->job_title_prof)->first();
                        $job_title_desc = $row->job_title.' ('.$job_prof_res->specialist_prof.')';
                    }
                    else
                    {
                        $job_title_desc = $row->job_title;
                    }
                    return $job_title_desc;
                })
                ->addColumn('created_at', function($row){
                    $created_date=Carbon::parse($row->created_at)->format('M j, Y');
                    return $created_date;
                })
               
                ->addColumn('head_office', function($row){
                    $officeName=Office::where('id',$row->head_office)->first();
                    $name=ucfirst($officeName->office_name);
                    return $name;
                })
                ->addColumn('unit', function($row){
                    $officeName=Unit::where('id',$row->head_office_unit)->first();
                    $name=ucfirst($officeName->unit_name);
                    return $name;
                })
                ->addColumn('sent_cv', function($row){
                    $sent_cv_limit=Cv_note::where('sale_id',$row->id)->where('status','active')->count();
                    $status = $sent_cv_limit==$row->send_cv_limit?'<span class="badge w-100 badge-danger" style="font-size:90%" >'.$sent_cv_limit.'/'.$row->send_cv_limit.' Limit Reached</span>':"<span class='badge w-100 badge-success' style='font-size:90%'>".((int)$row->send_cv_limit - (int)$sent_cv_limit.'/'.(int)$row->send_cv_limit)." Cv's limit remaining</span>";

                    return $status;
                })
                ->rawColumns(['category','updated_at','created_at','type','head_office','unit','sent_cv','job_title'])
                ->make(true);

    }

    //close sale detail
    public function closeSaleDetails(Request  $request){
   if ($request->close_app_daily_date!=null){

            $today=Carbon::parse($request->close_app_daily_date)->format('Y-m-d H:i:s');
        }
        return view('administrator.dashboard.closeSaleDetails',compact('today'));

    }
    public function closeDetailSale(Request $request ,$date){

      
$closeSale = Audit::where(['message' => 'sale-closed', 'auditable_type' => 'Horsefly\\Sale'])->whereDate('created_at', $date)->pluck('auditable_id')->toArray();

        $close= Sale::whereIn('id',$closeSale)
            ->orderBy('sales.updated_at', 'DESC')->get();
        return datatables()->of($close)
            ->addIndexColumn()
            ->addColumn('category', function($row){
                $cat=$row->job_category;
                return $cat;
            })
            ->addColumn("updated_at", function ($closed_sale) {
                $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                    ->where(['audits.auditable_id' => $closed_sale->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                    ->where('audits.message', 'like', '%has been updated%')
                    ->select('users.name')
                    ->orderBy('audits.created_at', 'desc')->first();
                $updated_by = $updated_by ? $updated_by->name : $closed_sale->name;
                return '<span data-popup="tooltip" title="'.$updated_by.'">'.Carbon::parse($closed_sale->updated_at)->toFormattedDateString().'</span>';
            })->addColumn("type", function ($closed_sale) {
                return ucwords($closed_sale->job_type);
            })
            ->addColumn('job_title', function($row){
                if($row->job_title_prof!=null)
                {

                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $row->job_title_prof)->first();
                    $job_title_desc = $row->job_title.' ('.$job_prof_res->specialist_prof.')';
                }
                else
                {
                    $job_title_desc = $row->job_title;
                }
                return $job_title_desc;
            })
            ->addColumn('created_at', function($row){
                $created_date=Carbon::parse($row->created_at)->format('M j, Y');
                return $created_date;
            })
            ->addColumn('head_office', function($row){
                $officeName=Office::where('id',$row->head_office)->first();
                $name=ucfirst($officeName->office_name);
                return $name;
            })
            ->addColumn('unit', function($row){
                $officeName=Unit::where('id',$row->head_office_unit)->first();
                $name=ucfirst($officeName->unit_name);
                return $name;
            })
            ->addColumn('sent_cv', function($closed_sale){
                return "<h5><span class=\"badge badge-danger\">".ucfirst($closed_sale->status)."</span></h5>";

            })
            ->rawColumns(['category','updated_at','created_at','type','head_office','unit','sent_cv','job_title'])
            ->make(true);

    }
	//    weekly sales
    public function openSaleDetailsWeekly(Request  $request){

           $start_date=Carbon::parse($request->open_start_date)->format('Y-m-d H:i:s');
            $end_date=Carbon::parse($request->open_end_date)->format('Y-m-d H:i:s');

        return view('administrator.dashboard.open_sale_weekly',compact('start_date','end_date'));

    }
    public function saleDetailsWeekly($start_date,$end_date){

        $sales= Sale::where(['status' => 'active'])->whereBetween('created_at',[$start_date,$end_date])
            ->orderBy('sales.updated_at', 'DESC')->get();

        return datatables()->of($sales)
            ->addIndexColumn()
            ->addColumn('category', function($row){
                $cat=$row->job_category;
                return $cat;
            })
            ->addColumn("updated_at", function ($open_sale) {
                $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                    ->where(['audits.auditable_id' => $open_sale->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                    ->where('audits.message', 'like', '%has been updated%')
                    ->select('users.name')
                    ->orderBy('audits.created_at', 'desc')->first();
                $updated_by = $updated_by ? $updated_by->name : $open_sale->name;
                return '<span data-popup="tooltip" title="'.$updated_by.'">'.Carbon::parse($open_sale->updated_at)->toFormattedDateString().'</span>';
            })->addColumn("type", function ($closed_sale) {
                return ucwords($closed_sale->job_type);
            })
            ->addColumn('job_title', function($row){
                if($row->job_title_prof!=null)
                {

                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $row->job_title_prof)->first();
                    $job_title_desc = $row->job_title.' ('.$job_prof_res->specialist_prof.')';
                }
                else
                {
                    $job_title_desc = $row->job_title;
                }
                return $job_title_desc;
            })
            ->addColumn('created_at', function($row){
                $created_date=Carbon::parse($row->created_at)->format('M j, Y');
                return $created_date;
            })

            ->addColumn('head_office', function($row){
                $officeName=Office::where('id',$row->head_office)->first();
                $name=ucfirst($officeName->office_name);
                return $name;
            })
            ->addColumn('unit', function($row){
                $officeName=Unit::where('id',$row->head_office_unit)->first();
                $name=ucfirst($officeName->unit_name);
                return $name;
            })
            ->addColumn('sent_cv', function($row){
                $sent_cv_limit=Cv_note::where('sale_id',$row->id)->where('status','active')->count();
                $status = $sent_cv_limit==$row->send_cv_limit?'<span class="badge w-100 badge-danger" style="font-size:90%" >'.$sent_cv_limit.'/'.$row->send_cv_limit.' Limit Reached</span>':"<span class='badge w-100 badge-success' style='font-size:90%'>".((int)$row->send_cv_limit - (int)$sent_cv_limit.'/'.(int)$row->send_cv_limit)." Cv's limit remaining</span>";

                return $status;
            })
            ->rawColumns(['category','updated_at','created_at','type','head_office','unit','sent_cv','job_title'])
            ->make(true);

    }
    public function closeSaleDetailWeekly(Request  $request){
          $sdate = Carbon::parse($request->close_start_date);
        $start_date = $sdate->format('Y-m-d') . " 00:00:00";
        $eDate = Carbon::parse($request->close_end_date);
        $end_date=$eDate->format('Y-m-d') . " 23:59:59";
        return view('administrator.dashboard.close_sale_weekly',compact('start_date','end_date'));

    }
    public function closeDetailSaleWeekly($start_date,$end_date){
        $closeSale = Audit::where(['message' => 'sale-closed', 'auditable_type' => 'Horsefly\\Sale'])
            ->whereBetween('created_at',[$start_date,$end_date])->pluck('auditable_id')->toArray();
		

        $close= Sale::whereIn('id',$closeSale)
            ->orderBy('sales.created_at', 'DESC')->get();
			

        return datatables()->of($close)
            ->addIndexColumn()
            ->addColumn('category', function($row){
                $cat=$row->job_category;
                return $cat;
            })
            ->addColumn("updated_at", function ($closed_sale) {
                $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                    ->where(['audits.auditable_id' => $closed_sale->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                    ->where('audits.message', 'like', '%has been updated%')
                    ->select('users.name')
                    ->orderBy('audits.created_at', 'desc')->first();
                $updated_by = $updated_by ? $updated_by->name : $closed_sale->name;
                return '<span data-popup="tooltip" title="'.$updated_by.'">'.Carbon::parse($closed_sale->updated_at)->toFormattedDateString().'</span>';
            })->addColumn("type", function ($closed_sale) {
                return ucwords($closed_sale->job_type);
            })
            ->addColumn('job_title', function($row){
                if($row->job_title_prof!=null)
                {

                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $row->job_title_prof)->first();
                    $job_title_desc = $row->job_title.' ('.$job_prof_res->specialist_prof.')';
                }
                else
                {
                    $job_title_desc = $row->job_title;
                }
                return $job_title_desc;
            })
            ->addColumn('created_at', function($row){
                $created_date=Carbon::parse($row->created_at)->format('M j, Y');
                return $created_date;
            })
            ->addColumn('head_office', function($row){
                $officeName=Office::where('id',$row->head_office)->first();
                $name=ucfirst($officeName->office_name);
                return $name;
            })
            ->addColumn('unit', function($row){
                $officeName=Unit::where('id',$row->head_office_unit)->first();
                $name=ucfirst($officeName->unit_name);
                return $name;
            })
            ->addColumn('sent_cv', function($closed_sale){
                return "<h5><span class=\"badge badge-danger\">".ucfirst($closed_sale->status)."</span></h5>";

            })
            ->rawColumns(['category','updated_at','created_at','type','head_office','unit','sent_cv','job_title'])
            ->make(true);

    }
    public function openSaleDetailsMonthly(Request  $request){
        $month = $request->monthly_date_sale;
        $year = $request->yearly_date_sale;
	
        return view('administrator.dashboard.open_sale_monthly',compact('month','year'));

    }
    public function openDetailSaleMonthly($month,$year){

        $open= Sale::where(['sales.status' => 'active'])->whereMonth('created_at',$month)->whereYear('created_at',$year)
            ->orderBy('sales.updated_at', 'DESC')->get();
        
        return datatables()->of($open)
            ->addIndexColumn()
            ->addColumn('category', function($row){
                $cat=$row->job_category;
                return $cat;
            })
            ->addColumn("updated_at", function ($open_sale) {
                $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                    ->where(['audits.auditable_id' => $open_sale->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                    ->where('audits.message', 'like', '%has been updated%')
                    ->select('users.name')
                    ->orderBy('audits.created_at', 'desc')->first();
                $updated_by = $updated_by ? $updated_by->name : $open_sale->name;
                return '<span data-popup="tooltip" title="'.$updated_by.'">'.Carbon::parse($open_sale->updated_at)->toFormattedDateString().'</span>';
            })->addColumn("type", function ($closed_sale) {
                return ucwords($closed_sale->job_type);
            })
            ->addColumn('job_title', function($row){
                if($row->job_title_prof!=null)
                {

                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $row->job_title_prof)->first();
                    $job_title_desc = $row->job_title.' ('.$job_prof_res->specialist_prof.')';
                }
                else
                {
                    $job_title_desc = $row->job_title;
                }
                return $job_title_desc;
            })
            ->addColumn('created_at', function($row){
                $created_date=Carbon::parse($row->created_at)->format('M j, Y');
                return $created_date;
            })

            ->addColumn('head_office', function($row){
                $officeName=Office::where('id',$row->head_office)->first();
                $name=ucfirst($officeName->office_name);
                return $name;
            })
            ->addColumn('unit', function($row){
                $officeName=Unit::where('id',$row->head_office_unit)->first();
                $name=ucfirst($officeName->unit_name);
                return $name;
            })
            ->addColumn('sent_cv', function($row){
                $sent_cv_limit=Cv_note::where('sale_id',$row->id)->where('status','active')->count();
                $status = $sent_cv_limit==$row->send_cv_limit?'<span class="badge w-100 badge-danger" style="font-size:90%" >'.$sent_cv_limit.'/'.$row->send_cv_limit.' Limit Reached</span>':"<span class='badge w-100 badge-success' style='font-size:90%'>".((int)$row->send_cv_limit - (int)$sent_cv_limit.'/'.(int)$row->send_cv_limit)." Cv's limit remaining</span>";

                return $status;
            })
            ->rawColumns(['category','updated_at','created_at','type','head_office','unit','sent_cv','job_title'])
            ->make(true);

    }

    public function closeSaleMonthly(Request  $request){
        $month = $request->monthly_close_sale;
        $year = $request->yearly_close_sale;
        return view('administrator.dashboard.close_sale_monthly',compact('month','year'));

    }
    public function closeDetailSaleMonthly($month,$year){
        $closeSale = Audit::where(['message' => 'sale-closed', 'auditable_type' => 'Horsefly\\Sale'])
            ->whereMonth('created_at',$month)->whereYear('created_at',$year)->pluck('auditable_id')->toArray();
        
        $close= Sale::whereIn('id',$closeSale)->where(['sales.status' => 'disable', 'sales.is_on_hold' => '0'])
            ->orderBy('sales.created_at', 'DESC')->get();

        return datatables()->of($close)
            ->addIndexColumn()
            ->addColumn('category', function($row){
                $cat=$row->job_category;
                return $cat;
            })
            ->addColumn("updated_at", function ($closed_sale) {
                $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                    ->where(['audits.auditable_id' => $closed_sale->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                    ->where('audits.message', 'like', '%has been updated%')
                    ->select('users.name')
                    ->orderBy('audits.created_at', 'desc')->first();
                $updated_by = $updated_by ? $updated_by->name : $closed_sale->name;
                return '<span data-popup="tooltip" title="'.$updated_by.'">'.Carbon::parse($closed_sale->updated_at)->toFormattedDateString().'</span>';
            })->addColumn("type", function ($closed_sale) {
                return ucwords($closed_sale->job_type);
            })
            ->addColumn('job_title', function($row){
                if($row->job_title_prof!=null)
                {

                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $row->job_title_prof)->first();
                    $job_title_desc = $row->job_title.' ('.$job_prof_res->specialist_prof.')';
                }
                else
                {
                    $job_title_desc = $row->job_title;
                }
                return $job_title_desc;
            })
            ->addColumn('created_at', function($row){
                $created_date=Carbon::parse($row->created_at)->format('M j, Y');
                return $created_date;
            })
            ->addColumn('head_office', function($row){
                $officeName=Office::where('id',$row->head_office)->first();
                $name=ucfirst($officeName->office_name);
                return $name;
            })
            ->addColumn('unit', function($row){
                $officeName=Unit::where('id',$row->head_office_unit)->first();
                $name=ucfirst($officeName->unit_name);
                return $name;
            })
            ->addColumn('sent_cv', function($closed_sale){
                return "<h5><span class=\"badge badge-danger\">".ucfirst($closed_sale->status)."</span></h5>";

            })
            ->rawColumns(['category','updated_at','created_at','type','head_office','unit','sent_cv','job_title'])
            ->make(true);

    	}
	
	   
	
	   public function statsDetailNurse($stats_date,$range,$stats_type,$job_category)
	   {
			if(strpos($stats_date, "&") !== false){
				$date_result = explode("&",$stats_date);
			}
			else
			{
				$date_result = $stats_date;
			}

			$today = Carbon::today();
			$daily_date='';
			$start_of_week_date='';
			$end_of_week_date='';
			$current_month = '';
			$current_year = '';
			$range_date='';
			$explode='';
		   
			if($range=='daily')
			{
				$daily_date = (new Carbon($date_result));
			}
			else if($range=='weekly')
			{
				$start_of_week_date = (new Carbon($date_result[0]));
				$end_of_week_date = (new Carbon($date_result[1]));
			}
			else if($range=='monthly')
			{
				$current_month=(int)$date_result[0];
				$current_year=(int)$date_result[1];

			}

			if($range=='daily')
			{
				$range_date=$daily_date;
			}
			else if($range=='weekly')
			{
				$range_date=[$start_of_week_date, $end_of_week_date];
			}
			 else if($range=="aggregate"){
				$explode=explode("&",$stats_date);
			}
		   
        	$formatted_date=$today->format('jS F Y');

            $result1= Applicant::join('history', 'applicants.id', '=', 'history.applicant_id')
                ->select('applicants.id','applicants.applicant_job_title','applicants.applicant_name','applicants.applicant_postcode',
                    'applicants.applicant_phone','applicants.applicant_homePhone','applicants.applicant_added_date'
                    ,'applicants.applicant_added_time','applicants.job_category','applicants.applicant_source',
                    'applicants.applicant_notes');
                if ($job_category == "nurse"){
                    $source_result= $result1->where("applicants.job_category", '=',"nurse");
                }elseif ($job_category == "non-nurse"){
                    $source_result= $result1->whereIn(".applicants.job_category", ["non-nurse","chef",'nursery'])
						->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
                }elseif ($job_category =="specialist"){

                    $source_result = $result1->where(["applicants.job_category" => "non-nurse",
                        "applicants.applicant_job_title" => "nonnurse specialist"]);

                }

        if( $stats_type == 'quality_cleared')
        {
            $source_result->where('history.sub_stage', $stats_type);

        }
        else if($stats_type == 'crm_request'){
            $source_result->where('history.sub_stage', 'crm_request')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history AS h
						WHERE h.sub_stage = "crm_request"
						AND history.applicant_id = h.applicant_id
						AND history.sale_id = h.sale_id
						AND history.id > (
							SELECT MAX(hh.id) FROM history AS hh
							WHERE ( hh.sub_stage = "quality_cleared" OR hh.sub_stage = "crm_save" )
							AND history.applicant_id = hh.applicant_id AND history.sale_id = hh.sale_id
						)'));
                });
        }
        else if($stats_type=='crm_confirmation'){
            $source_result->where('history.sub_stage', "crm_request_confirm")
                ->whereIn('history.id', function ($query) use ($formatted_date) {
                    $query->select(DB::raw('MAX(h.id) FROM history AS h 
                    WHERE h.sub_stage = "crm_request_confirm" 
                    AND history.applicant_id = h.applicant_id 
                    AND history.sale_id = h.sale_id 
                    AND history.id > ( 
                        SELECT MAX(hh.id) FROM history AS hh 
                        WHERE hh.sub_stage = "crm_request" 
                        AND history.applicant_id = hh.applicant_id 
                        AND history.sale_id = hh.sale_id 
                    )'));
                });
        }
        else if($stats_type=='crm_prestart_attended'){
            $source_result->where('history.sub_stage', 'crm_interview_attended')
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_interview_attended" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                });
        }
        else if($stats_type=='crm_start_date'){
            $source_result->whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_back'])
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE ( h.sub_stage="crm_start_date" OR h.sub_stage="crm_start_date_back" )
                     AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                });
        }
        else if($stats_type=='crm_invoice'){
            $source_result->whereIn('history.sub_stage', ['crm_invoice','crm_invoice_sent'])
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage IN ("crm_invoice","crm_invoice_sent") AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                });
        }
        else if($stats_type=='crm_paid'){
            $source_result->where('history.sub_stage', $stats_type);
        }
        else if($stats_type=='crm_rebook'){
            $source_result->where('history.sub_stage', "crm_rebook")
                ->whereIn('history.id', function ($query) {
                    $query->select(DB::raw('MAX(h.id) FROM history as h WHERE h.sub_stage="crm_rebook" AND history.applicant_id=h.applicant_id AND history.sale_id=h.sale_id'));
                });
        }
        else if($stats_type=='crm_not_attended'){
            $source_result->where(['history.sub_stage' => 'crm_interview_not_attended', 'history.status' => 'active']);
        }else if($stats_type=='crm_reject'){
            $source_result->where(['history.sub_stage' => 'crm_reject', 'history.status' => 'active']);
        }
        else if($stats_type=='crm_start_date_hold'){
            $source_result->where(['history.sub_stage' => 'crm_start_date_hold', 'history.status' => 'active']);
        }
        else if($stats_type=='crm_declined'){
            $source_result->where(['history.sub_stage' => 'crm_declined', 'history.status' => 'active']);
        }
        else if($stats_type=='crm_dispute'){
            $source_result->where(['history.sub_stage' => 'crm_dispute', 'history.status' => 'active']);
        }else if($stats_type=='crm_req_reject') {
            $source_result->where(['history.sub_stage' => 'crm_request_reject', 'history.status' => 'active']);
        }
        $userData='';
        if($range=='daily')
        {
            $userData = $source_result->whereDate('history.created_at', $range_date)->orderBy('history.id', 'DESC')->get();

        }
        else if($range=='weekly')
        {

            $userData = $source_result->whereBetween('history.created_at', $range_date)->orderBy('history.id', 'DESC')->get();
        }
        else if($range=='monthly')
        {
            $userData = $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->orderBy('history.id', 'DESC')->get();

        }
		   else if($range=="aggregate"){
            $userData = $source_result->whereBetween('history.created_at', [$explode[0],$explode[1]])->orderBy('history.id', 'DESC')->take(800)->get();

        }
//        if($range=="monthly"){
//            $current_month=(int)$date_result[0];
//            $current_year=(int)$date_result[1];
//            $userData = $source_result->whereMonth('history.created_at', $current_month)->whereYear('history.created_at', $current_year)->orderBy('history.id', 'DESC')->get();
//
//        }

        return view('administrator.partial.nurse_detail',compact('userData','stats_type','current_month','current_year','range','range_date','explode'));

    }
    public function positionCheck( $type,$stats_date,$range,$job_category){

            if(strpos($stats_date, "&") !== false){
                $date_result = explode("&",$stats_date);
            }
            else
            {
                $date_result = $stats_date;
            }
            $today = Carbon::today();
            $daily_date='';
            $start_of_week_date='';
            $end_of_week_date='';
            $current_month = '';
            $current_year = '';
            if($range=='daily')
            {

                $daily_date = (new Carbon($date_result));

            }
            else if($range=='weekly')
            {

                $start_of_week_date = (new Carbon($date_result[0]));
                $end_of_week_date = (new Carbon($date_result[1]));
//                dd($start_of_week_date,$end_of_week_date);
            }
            else if($range=='monthly')
            {

                $current_month=(int)$date_result[0];
                $current_year=(int)$date_result[1];

            }
            if($range=='daily')
            {
                $range_date=$daily_date;
            }
            else if($range=='weekly')
            {
                $range_date=[$start_of_week_date, $end_of_week_date];
            }

        if ($job_category=='nurse'){
            $query_nurse=Crm_rejected_cv::join('applicants', 'applicants.id', '=', 'crm_rejected_cv.applicant_id')
                ->select(
                    'applicants.applicant_name as applicant_name',
                    'applicants.applicant_email as applicant_email',
                    'applicants.applicant_phone as applicant_number',
                    'applicants.applicant_job_title as applicant_job_title',
                    'applicants.job_category as job_category',
                    'applicants.applicant_postcode as applicant_postcode',
                    'crm_rejected_cv.reason','crm_rejected_cv.crm_rejected_cv_date','crm_rejected_cv.crm_rejected_cv_time',
                    'crm_rejected_cv.crm_rejected_cv_note',
                    'crm_rejected_cv.user_id',
//                DB::raw('count(*) as count')
                )
//            ->select('crm_rejected_cv.reason',DB::raw('count(*) as count'))
                ->where(['crm_rejected_cv.status' => 'active'])
                ->where(['applicants.job_category' => 'nurse']);

            if($range=='daily')
            {
                $query_nurse->whereDate('crm_rejected_cv.created_at', $range_date)->get();
            }
            else if($range=='weekly')
            {
                $query_nurse->whereBetween('crm_rejected_cv.created_at',$range_date)->get();
            }
            else if($range=='monthly')
            {
                $query_nurse->whereMonth('crm_rejected_cv.created_at', $current_month)->whereYear('crm_rejected_cv.created_at', $current_year)->get();
            }


            if ($type == 'position_filled') {
                $reasons = $query_nurse->where('crm_rejected_cv.reason', 'position_filled')->get();
            }elseif ($type=="agency"){
                $reasons = $query_nurse->where('crm_rejected_cv.reason', 'agency')->get();

            }elseif ($type=="manager"){
                $reasons = $query_nurse->where('crm_rejected_cv.reason', 'manager')->get();

            }elseif ($type=="no_response"){
                $reasons = $query_nurse->where('crm_rejected_cv.reason', 'no_response')->get();

            }
        }else{



            $query_nurse=Crm_rejected_cv::join('applicants', 'applicants.id', '=', 'crm_rejected_cv.applicant_id')
                ->select(
                    'applicants.applicant_name as applicant_name',
                    'applicants.applicant_email as applicant_email',
                    'applicants.applicant_phone as applicant_number',
                    'applicants.applicant_job_title as applicant_job_title',
                    'applicants.job_category as job_category',
                    'applicants.applicant_postcode as applicant_postcode',
                    'crm_rejected_cv.reason','crm_rejected_cv.crm_rejected_cv_date','crm_rejected_cv.crm_rejected_cv_time',
                    'crm_rejected_cv.crm_rejected_cv_note',
                    'crm_rejected_cv.user_id',
//                DB::raw('count(*) as count')
                )
//            ->select('crm_rejected_cv.reason',DB::raw('count(*) as count'))
                ->where(['crm_rejected_cv.status' => 'active'])
//                ->where(['applicants.job_category' => 'non-nurse'])
            ->whereIn('applicants.job_category',['chef','non-nurse','nursery']);

            if($range=='daily')
            {
                $query_nurse->whereDate('crm_rejected_cv.created_at', $range_date)->get();
            }
            else if($range=='weekly')
            {
                $query_nurse->whereBetween('crm_rejected_cv.created_at',$range_date)->get();
            }
            else if($range=='monthly')
            {
                $query_nurse->whereMonth('crm_rejected_cv.created_at', $current_month)->whereYear('crm_rejected_cv.created_at', $current_year)->get();
            }


            if ($type == 'position_filled') {
                $reasons = $query_nurse->where('crm_rejected_cv.reason', 'position_filled')->get();
            }elseif ($type=="agency"){
                $reasons = $query_nurse->where('crm_rejected_cv.reason', 'agency')->get();

            }elseif ($type=="manager"){
                $reasons = $query_nurse->where('crm_rejected_cv.reason', 'manager')->get();

            }elseif ($type=="no_response"){
                $reasons = $query_nurse->where('crm_rejected_cv.reason', 'no_response')->get();

            }
        }




        return view('administrator.partial.postion_check',compact('reasons'));

    }
	
	    public function revertCv($type,$stats_date,$range,$job_category,$page_name){
        if(strpos($stats_date, "&") !== false){
            $date_result = explode("&",$stats_date);
        }
        else
        {
            $date_result = $stats_date;
        }
        $today = Carbon::today();
        $daily_date='';
        $start_of_week_date='';
        $end_of_week_date='';
        $current_month = '';
        $current_year = '';
        $range_date='';
        if($range=='daily')
        {

            $daily_date = (new Carbon($date_result));

        }
        else if($range=='weekly')
        {

            $start_of_week_date = (new Carbon($date_result[0]));
            $end_of_week_date = (new Carbon($date_result[1]));
//                dd($start_of_week_date,$end_of_week_date);
        }
        else if($range=='monthly')
        {

            $current_month=(int)$date_result[0];
            $current_year=(int)$date_result[1];

        }
        if($range=='daily')
        {
            $range_date=$daily_date;
        }
        else if($range=='weekly')
        {
            $range_date=[$start_of_week_date, $end_of_week_date];
        }

        if ($page_name=="crm_revert"){
            $result1 = RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                ->select('applicants.applicant_name','applicants.applicant_phone','applicants.job_category',
                    'applicants.applicant_postcode','applicants.applicant_source','applicants.applicant_job_title',
                    'revert_stages.notes','revert_stages.revert_added_time','revert_stages.revert_added_date','revert_stages.user_id');
            $source_result='';
            if ($job_category == "nurse"){
                $source_result= $result1->where("applicants.job_category", '=',"nurse");
            }elseif ($job_category == "non-nurse"){
                $source_result= $result1->whereIn(".applicants.job_category", ["non-nurse","chef",'nursery'])->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
            }elseif ($job_category =="specialist") {
                $source_result = $result1->where(["applicants.job_category" => "non-nurse",
                    "applicants.applicant_job_title" => "nonnurse specialist"]);

            }
            $crm_revert = $source_result->where('stage', $page_name);
            $userData='';
            if($range=='daily')
            {
                $userData = $crm_revert->whereDate('revert_stages.created_at', $range_date)->orderBy('revert_stages.id', 'DESC')->get();


            }
            else if($range=='weekly')
            {

                $userData = $crm_revert->whereBetween('revert_stages.created_at', $range_date)->orderBy('revert_stages.id', 'DESC')->get();
            }
            else if($range=='monthly')
            {

            $userData = $crm_revert->whereMonth('revert_stages.created_at', $current_month)->whereYear('revert_stages.created_at', $current_year)->orderBy('revert_stages.id', 'DESC')->get();

            }
            return view('administrator.partial.cv_revert',compact('userData','current_month','current_year','range','range_date'));


        }else{
            $result1 = RevertStage::join('applicants', 'applicants.id', '=', 'revert_stages.applicant_id')
                ->select('applicants.applicant_name','applicants.applicant_phone','applicants.job_category',
                    'applicants.applicant_postcode','applicants.applicant_source','applicants.applicant_job_title',
                    'revert_stages.notes','revert_stages.revert_added_time','revert_stages.revert_added_date','revert_stages.user_id');
            $source_result='';
            if ($job_category == "nurse"){
                $source_result= $result1->where("applicants.job_category", '=',"nurse");
            }elseif ($job_category == "non-nurse"){
                $source_result= $result1->whereIn(".applicants.job_category", ["non-nurse","chef",'nursery'])->whereNotIn('applicants.applicant_job_title', ['nonnurse specialist']);
            }elseif ($job_category =="specialist") {
                $source_result = $result1->where(["applicants.job_category" => "non-nurse",
                    "applicants.applicant_job_title" => "nonnurse specialist"]);
//               dd($source_result->count());
            }
            $crm_revert = $source_result->where('stage', $page_name);
            $userData='';
            if($range=='daily')
            {
                $userData = $crm_revert->whereDate('revert_stages.created_at', $range_date)->orderBy('revert_stages.id', 'DESC')->get();


            }
            else if($range=='weekly')
            {

                $userData = $crm_revert->whereBetween('revert_stages.created_at', $range_date)->orderBy('revert_stages.id', 'DESC')->get();
            }
            else if($range=='monthly')
            {

                $userData = $crm_revert->whereMonth('revert_stages.created_at', $current_month)->whereYear('revert_stages.created_at', $current_year)->orderBy('revert_stages.id', 'DESC')->get();

            }
            return view('administrator.partial.cv_revert',compact('userData','current_month','current_year','range','range_date'));


        }


    }
	
	   public function openSaleDetailsUpdate(Request  $request){
        if ($request->open_daily_date_update!=null ){

            $today=Carbon::parse($request->open_daily_date_update)->format('Y-m-d H:i:s');
        }

        return view('administrator.dashboard.open_sale_update',compact('today'));

    }
    public function saleDetailsUpdate(Request $request, $date){

            $sales= Sale::where(['status' => 'active'])->whereDate('created_at', $date)
                ->orderBy('sales.created_at', 'DESC')->get();

            return datatables()->of($sales)
                ->addIndexColumn()
                ->addColumn('category', function($row){
                    $cat=$row->job_category;
                    return $cat;
                })
                ->addColumn("updated_at", function ($open_sale) {
                    $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                        ->where(['audits.auditable_id' => $open_sale->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                        ->where('audits.message', 'like', '%has been updated%')
                        ->select('users.name')
                        ->orderBy('audits.created_at', 'desc')->first();
                    $updated_by = $updated_by ? $updated_by->name : $open_sale->name;
                    return '<span data-popup="tooltip" title="'.$updated_by.'">'.Carbon::parse($open_sale->updated_at)->toFormattedDateString().'</span>';
                })->addColumn("type", function ($closed_sale) {
                    return ucwords($closed_sale->job_type);
                })
                ->addColumn('job_title', function($row){
                    if($row->job_title_prof!=null)
                    {

                        $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $row->job_title_prof)->first();
                        $job_title_desc = $row->job_title.' ('.$job_prof_res->specialist_prof.')';
                    }
                    else
                    {
                        $job_title_desc = $row->job_title;
                    }
                    return $job_title_desc;
                })
                ->addColumn('created_at', function($row){
                    $created_date=Carbon::parse($row->created_at)->format('M j, Y');
                    return $created_date;
                })

                ->addColumn('head_office', function($row){
                    $officeName=Office::where('id',$row->head_office)->first();
                    $name=ucfirst($officeName->office_name);
                    return $name;
                })
                ->addColumn('unit', function($row){
                    $officeName=Unit::where('id',$row->head_office_unit)->first();
                    $name=ucfirst($officeName->unit_name);
                    return $name;
                })
                ->addColumn('sent_cv', function($row){
                    $sent_cv_limit=Cv_note::where('sale_id',$row->id)->where('status','active')->count();
                    $status = $sent_cv_limit==$row->send_cv_limit?'<span class="badge w-100 badge-danger" style="font-size:90%" >'.$sent_cv_limit.'/'.$row->send_cv_limit.' Limit Reached</span>':"<span class='badge w-100 badge-success' style='font-size:90%'>".((int)$row->send_cv_limit - (int)$sent_cv_limit.'/'.(int)$row->send_cv_limit)." Cv's limit remaining</span>";

                    return $status;
                })
                ->rawColumns(['category','updated_at','created_at','type','head_office','unit','sent_cv','job_title'])
                ->make(true);

    }
	
    public function closeSaleDetailsUpdate(Request  $request){
        if ($request->close_daily_date_update!=null){

            $today=Carbon::parse($request->close_daily_date_update)->format('Y-m-d H:i:s');
        }
        return view('administrator.dashboard.close_sale_update',compact('today'));

    }
    public function closeDetailSaleUpdate(Request $request , $date){
        $closeSale = Audit::where(['message' => 'sale-closed', 'auditable_type' => 'Horsefly\\Sale'])->whereDate('created_at', $date)->pluck('auditable_id')->toArray();
       $close= Sale::whereIn('id',$closeSale)
            ->orderBy('sales.updated_at', 'DESC')->get();

        return datatables()->of($close)
            ->addIndexColumn()
            ->addColumn('category', function($row){
                $cat=$row->job_category;
                return $cat;
            })
            ->addColumn("updated_at", function ($closed_sale) {
                $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                    ->where(['audits.auditable_id' => $closed_sale->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                    ->where('audits.message', 'like', '%has been updated%')
                    ->select('users.name')
                    ->orderBy('audits.created_at', 'desc')->first();
                $updated_by = $updated_by ? $updated_by->name : $closed_sale->name;
                return '<span data-popup="tooltip" title="'.$updated_by.'">'.Carbon::parse($closed_sale->updated_at)->toFormattedDateString().'</span>';
            })->addColumn("type", function ($closed_sale) {
                return ucwords($closed_sale->job_type);
            })
            ->addColumn('job_title', function($row){
                if($row->job_title_prof!=null)
                {

                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $row->job_title_prof)->first();
                    $job_title_desc = $row->job_title.' ('.$job_prof_res->specialist_prof.')';
                }
                else
                {
                    $job_title_desc = $row->job_title;
                }
                return $job_title_desc;
            })
            ->addColumn('created_at', function($row){
                $created_date=Carbon::parse($row->created_at)->format('M j, Y');
                return $created_date;
            })
            ->addColumn('head_office', function($row){
                $officeName=Office::where('id',$row->head_office)->first();
                $name=ucfirst($officeName->office_name);
                return $name;
            })
            ->addColumn('unit', function($row){
                $officeName=Unit::where('id',$row->head_office_unit)->first();
                $name=ucfirst($officeName->unit_name);
                return $name;
            })
            ->addColumn('sent_cv', function($closed_sale){
                return "<h5><span class=\"badge badge-danger\">".ucfirst($closed_sale->status)."</span></h5>";

            })
            ->rawColumns(['category','updated_at','created_at','type','head_office','unit','sent_cv','job_title'])
            ->make(true);

    }
	
	public function reOpenSaleDetailsUpdate(Request  $request){
        if ($request->open_daily_date_update!=null ){

            $today=Carbon::parse($request->open_daily_date_update)->format('Y-m-d H:i:s');
        }
//dd('sada');
        return view('administrator.dashboard.re_open_sale_update',compact('today'));

    }

    public function saleReOpenDetailsUpdate(Request $request, $date){

        //$reOpens=Sales_notes::where('status','active')->whereDate('created_at', $date)->pluck('sale_id')->toArray();
 $closeSale = Audit::where(['message' => 'sale-opened', 'auditable_type' => 'Horsefly\\Sale'])->whereDate('created_at', $date)->pluck('auditable_id')->toArray();

        $close= Sale::whereIn('id',$closeSale)
			->where('status','active')
            ->whereRaw('DATE(created_at) != DATE(updated_at)')->orderBy('sales.updated_at', 'DESC')->get();
        return datatables()->of($close)
            ->addIndexColumn()
            ->addColumn('category', function($row){
                $cat=$row->job_category;
                return $cat;
            })
            ->addColumn("updated_at", function ($closed_sale) {
                $updated_by = Audit::join('users', 'users.id', '=', 'audits.user_id')
                    ->where(['audits.auditable_id' => $closed_sale->id, 'audits.auditable_type' => 'Horsefly\Sale'])
                    ->where('audits.message', 'like', '%has been updated%')
                    ->select('users.name')
                    ->orderBy('audits.created_at', 'desc')->first();
                $updated_by = $updated_by ? $updated_by->name : $closed_sale->name;
                return '<span data-popup="tooltip" title="'.$updated_by.'">'.Carbon::parse($closed_sale->updated_at)->toFormattedDateString().'</span>';
            })->addColumn("type", function ($closed_sale) {
                return ucwords($closed_sale->job_type);
            })
            ->addColumn('job_title', function($row){
                if($row->job_title_prof!=null)
                {

                    $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where("id", $row->job_title_prof)->first();
                    $job_title_desc = $row->job_title.' ('.$job_prof_res->specialist_prof.')';
                }
                else
                {
                    $job_title_desc = $row->job_title;
                }
                return $job_title_desc;
            })
            ->addColumn('created_at', function($row){
                $created_date=Carbon::parse($row->created_at)->format('M j, Y');
                return $created_date;
            })
            ->addColumn('head_office', function($row){
                $officeName=Office::where('id',$row->head_office)->first();
                $name=ucfirst($officeName->office_name);
                return $name;
            })
            ->addColumn('unit', function($row){
                $officeName=Unit::where('id',$row->head_office_unit)->first();
                $name=ucfirst($officeName->unit_name);
                return $name;
            })
            ->addColumn('sent_cv', function($closed_sale){
                return "<h5><span class=\"badge badge-success\">".ucfirst($closed_sale->status)."</span></h5>";

            })
            ->rawColumns(['category','updated_at','created_at','type','head_office','unit','sent_cv','job_title'])
            ->make(true);

    }



}
