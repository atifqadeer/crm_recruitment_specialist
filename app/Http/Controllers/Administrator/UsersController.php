<?php

namespace Horsefly\Http\Controllers\Administrator;

use DateTime;
use Horsefly\Audit;
//use Horsefly\Observers\UserObserver;
use Horsefly\User;
use Horsefly\Crm_note;
use Horsefly\Cv_note;
use Horsefly\History;
use Illuminate\Http\Request;
use Horsefly\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Validator;
use Illuminate\Support\Carbon;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
//        $this->middleware('is_admin');

        $this->middleware('permission:user_list|user_create|user_edit|user_enable-disable|user_activity-log', ['only' => ['index']]);
        $this->middleware('permission:user_create', ['only' => ['create','store']]);
        $this->middleware('permission:user_edit', ['only' => ['edit','update']]);
        $this->middleware('permission:user_enable-disable', ['only' => ['getUserStatusChange']]);
        $this->middleware('permission:user_activity-log', ['only' => ['activityLogs','userLogs']]);
        $this->middleware('permission:role_assign-role', ['only' => ['assignRoleToUsers']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::where(["is_admin" => 0])->get();
        return view('administrator.users.index',compact('users'));
    }
	
	public function usersDailyReport()
    {
        return view('administrator.users.daily_report');
    }
   
    public function resourcesReportAjax(Request $request)
	{
		// Parse dates from the request or default to the current date
		$start_date = $request->input('start_date')
			? Carbon::parse($request->input('start_date'))->startOfDay()->toDateTimeString()
			: Carbon::now()->startOfDay()->toDateTimeString();

		$end_date = $request->input('end_date')
			? Carbon::parse($request->input('end_date'))->endOfDay()->toDateTimeString()
			: Carbon::now()->endOfDay()->toDateTimeString();
		
		// Fetch users excluding specific IDs
		$users = DB::table('users')
			->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
			->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
			->leftJoin('login_details', function ($join) {
				$join->on('users.id', '=', 'login_details.user_id')
					 ->whereDate('login_details.login_date', Carbon::today()); // Filter by today's date
			})
			->whereNotIn('users.id', [1, 101])
			->where('users.is_active', '1')
			->where('roles.name','NOT LIKE','crm%')
			->where('roles.name','NOT LIKE','data entry%')
			->where('roles.name','NOT LIKE','quality%')
			->where('roles.name','NOT LIKE','sales%')
			->select(
				'users.id',
				'users.name',
				'login_details.login_time',
				DB::raw('CASE WHEN login_details.login_date IS NOT NULL THEN "Yes" ELSE "No" END as logged_in_today') // Check if logged in today
			)
			->groupBy('users.id')
			->get();

		// If no users are found, return an empty response
		if ($users->isEmpty()) {
			return datatables()->of([])->toJson();
		}

		// Initialize report array
		$report = [];

		// Fetch all relevant data in bulk
		$userIds = $users->pluck('id')->toArray();

		// Fetch messages in bulk
		$messages = DB::table('applicant_messages')
			->whereIn('user_id', $userIds)
			->where('status', 'outgoing')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as message_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		// Fetch CV notes in bulk for quality cleared
		$crmPaid = DB::table('cv_notes')
			->where('status', 'paid')
			->whereIn('user_id', $userIds)
			->whereBetween(DB::raw("DATE_FORMAT(updated_at, '%Y-%m')"), [
				DB::raw("DATE_FORMAT('$start_date', '%Y-%m')"),
				DB::raw("DATE_FORMAT('$end_date', '%Y-%m')")
			])
			->select('user_id', DB::raw('COUNT(*) as crm_paid_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		// Fetch History in bulk for invoice sent
		$crm_invoice_sent = DB::table('applicants')
			->join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
			->join('history', function ($join) {
				$join->on('crm_notes.applicant_id', '=', 'history.applicant_id')
					 ->on('crm_notes.sales_id', '=', 'history.sale_id');
			})
			->join('cv_notes', function ($join) {
				// Subquery to filter cv_notes rows before joining
				$join->on('cv_notes.applicant_id', '=', 'history.applicant_id')
					 ->on('cv_notes.sale_id', '=', 'history.sale_id')
					 ->whereIn('cv_notes.id', function ($query) {
						 $query->select(DB::raw('MAX(id)'))
							   ->from('cv_notes')
							   ->groupBy('applicant_id', 'sale_id');
					 });
			})
			->where([
				'applicants.status' => 'active',
				'crm_notes.moved_tab_to' => 'invoice_sent',
				'history.status' => 'active',
				'cv_notes.status' => 'active'
			])
			->whereIn('crm_notes.id', function ($query) {
				$query->select(DB::raw('MAX(id)'))
					  ->from('crm_notes')
					  ->where('moved_tab_to', 'invoice_sent')
					  ->groupBy('sales_id', 'applicant_id');
			})
			->whereIn('history.sub_stage', ['crm_invoice_sent', 'crm_final_save'])
			->select(
				'cv_notes.user_id',
				'cv_notes.sale_id',
				'cv_notes.applicant_id',
				DB::raw('COUNT(*) as invoice_sent_count')
			)
			->groupBy('cv_notes.user_id') // Group by these columns
			->get()
			->keyBy('user_id');
		
		// Fetch History in bulk for start date
		$crm_start_date = DB::table('applicants')
			->join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
			->join('history', function ($join) {
				$join->on('crm_notes.applicant_id', '=', 'history.applicant_id')
					 ->on('crm_notes.sales_id', '=', 'history.sale_id');
			})
			->join('cv_notes', function ($join) {
				// Subquery to filter cv_notes rows before joining
				$join->on('cv_notes.applicant_id', '=', 'history.applicant_id')
					 ->on('cv_notes.sale_id', '=', 'history.sale_id')
					 ->whereIn('cv_notes.id', function ($query) {
						 $query->select(DB::raw('id'))
							   ->from('cv_notes')
							   ->groupBy('applicant_id', 'sale_id');
					 });
			})
			->whereBetween(DB::raw("DATE_FORMAT(crm_notes.updated_at, '%Y-%m')"), [
				DB::raw("DATE_FORMAT('$start_date', '%Y-%m')"),
				DB::raw("DATE_FORMAT('$end_date', '%Y-%m')")
			])
			->where([
				'applicants.status' => 'active',
				'crm_notes.moved_tab_to' => 'start_date',
				'history.status' => 'active',
				'cv_notes.status' => 'active'
			])
			->whereIn('crm_notes.id', function ($query) {
				$query->select(DB::raw('MAX(id)'))
					  ->from('crm_notes')
					  ->where('moved_tab_to', 'start_date')
					  ->groupBy('sales_id', 'applicant_id');
			})
			->whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_save', 'crm_start_date_back'])
			->select(
				'cv_notes.user_id',
				'cv_notes.sale_id',
				'cv_notes.applicant_id',
				DB::raw('COUNT(*) as crm_start_date_count')
			)
			->groupBy('cv_notes.user_id') // Group by these columns
			->get()
			->keyBy('user_id');
		
		$applicants_created = DB::table('audits')
			->where('auditable_type', 'Horsefly\Applicant')
			->where('message','LIKE','%has been created%')
			->whereIn('user_id', $userIds)
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as applicants_count_created'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		$applicants_updated = DB::table('audits')
			->where('auditable_type', 'Horsefly\Applicant')
			->where('message','LIKE','%has been updated%')
			->whereIn('user_id', $userIds)
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as applicants_count_updated'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
				
		// Fetch CV notes in bulk for total
		$cvNotes = DB::table('cv_notes')
			->whereIn('user_id', $userIds)
			//->where('status', 'active')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as cv_note_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		// Fetch engaged counts for applicant notes for total
		$applicant_notes = DB::table('applicant_notes')
			->whereIn('user_id', $userIds)
			//->where('status', 'active')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as applicant_notes_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		// Fetch engaged counts in bulk for total
		$module_notes = DB::table('module_notes')
			->where('module_noteable_type', 'Horsefly\Applicant')
			->whereIn('user_id', $userIds)
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as module_notes_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');

		$sumOfEngagedCounts = [];

		// Process each user
		foreach ($users as $user) {
			$user_id = $user->id;
			
			/***engagedTotal*/
			$sumOfEngagedCounts[$user_id] =
				($applicants_created[$user_id]->applicants_count_created ?? 0) + 
				($applicants_updated[$user_id]->applicants_count_updated ?? 0) +
				($applicant_notes[$user_id]->applicant_notes_count ?? 0) + 
				($module_notes[$user_id]->module_notes_count ?? 0) + 
				($cvNotes[$user_id]->cv_note_count ?? 0);
			
			// Initialize user stats
			$user_stats = [
				'engagedTotal' => $sumOfEngagedCounts[$user_id],
				'engagedCreated' => $applicants_created[$user_id]->applicants_count_created ?? 0,
				'engagedUpdated' => $applicants_updated[$user_id]->applicants_count_updated ?? 0,
				'sms' => $messages[$user_id]->message_count ?? 0,
				'calls' => 0, // Add logic for calls if needed
				'cvs_quality_sent' => $cvNotes[$user_id]->cv_note_count ?? 0,
				'cvs_rejected' => 0,
				'cvs_cleared' => 0,
				'crm_sent_cvs' => 0,
				'crm_rejected_cv' => 0,
				'crm_request' => 0,
				'crm_rejected_by_request' => 0,
				'crm_confirmation' => 0,
				'crm_rebook' => 0,
				'crm_attended' => 0,
				'crm_not_attended' => 0,
				'crm_start_date' => $crm_start_date[$user_id]->crm_start_date_count ?? 0,
				'crm_start_date_hold' => 0,
				'crm_declined' => 0,
				'crm_invoice' => 0,
				'crm_invoice_sent' =>  $crm_invoice_sent[$user_id]->invoice_sent_count ?? 0,
				'crm_dispute' => 0,
				'crm_paid' => $crmPaid[$user_id]->crm_paid_count ?? 0,
			];

			// Process CV history
			$this->processCvHistory($user_id, $user_stats, $start_date, $end_date);

			// Add user stats to the report
			$report[] = [
				'user_id' => $user_id,
				'user_name' => ucwords($user->name),
				'logged_in' => $user->logged_in_today,
				'stats' => $user_stats,
			];
		}

		// Sort the report array
		usort($report, function ($a, $b) {
			// Check if 'crm_paid' is greater than 0 for either $a or $b
			$aHasPaid = ($a['stats']['crm_paid'] ?? 0) > 0;
			$bHasPaid = ($b['stats']['crm_paid'] ?? 0) > 0;

			// Prioritize users with 'crm_paid' greater than 0
			if ($aHasPaid !== $bHasPaid) {
				return $bHasPaid <=> $aHasPaid;
			}

			// If both have 'crm_paid' greater than 0 or both have 0, sort by 'crm_paid' in descending order
			if (($a['stats']['crm_paid'] ?? 0) != ($b['stats']['crm_paid'] ?? 0)) {
				return ($b['stats']['crm_paid'] ?? 0) <=> ($a['stats']['crm_paid'] ?? 0);
			}

			// If 'crm_paid' is equal or not available, sort by 'crm_invoice_sent' in descending order
			if (($a['stats']['crm_invoice_sent'] ?? 0) != ($b['stats']['crm_invoice_sent'] ?? 0)) {
				return ($b['stats']['crm_invoice_sent'] ?? 0) <=> ($a['stats']['crm_invoice_sent'] ?? 0);
			}

			// If 'crm_invoice_sent' is equal or not available, sort by 'crm_start_date' in descending order
			if (($a['stats']['crm_start_date'] ?? 0) != ($b['stats']['crm_start_date'] ?? 0)) {
				return ($b['stats']['crm_start_date'] ?? 0) <=> ($a['stats']['crm_start_date'] ?? 0);
			}

			// If all else is equal, sort by 'engagedTotal' in descending order
			return ($b['stats']['engagedTotal'] ?? 0) <=> ($a['stats']['engagedTotal'] ?? 0);
		});

		// Convert the report array to a DataTables-compatible response
		return datatables()->of($report)->toJson();
	}
	
	public function dataEntryReportAjax(Request $request)
	{
		// Parse dates from the request or default to the current date
		$start_date = $request->input('start_date')
			? Carbon::parse($request->input('start_date'))->startOfDay()->toDateTimeString()
			: Carbon::now()->startOfDay()->toDateTimeString();

		$end_date = $request->input('end_date')
			? Carbon::parse($request->input('end_date'))->endOfDay()->toDateTimeString()
			: Carbon::now()->endOfDay()->toDateTimeString();
		
		// Fetch users excluding specific IDs
		$users = DB::table('users')
			->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
			->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
			->leftJoin('login_details', function ($join) {
				$join->on('users.id', '=', 'login_details.user_id')
					 ->whereDate('login_details.login_date', Carbon::today()); // Filter by today's date
			})
			->whereNotIn('users.id', [1, 101])
			->where('users.is_active', '1')
			->where('roles.name', 'LIKE', 'data entry%')
			->select(
				'users.id',
				'users.name',
				'login_details.login_time',
				DB::raw('CASE WHEN login_details.login_date IS NOT NULL THEN "Yes" ELSE "No" END as logged_in_today') // Check if logged in today
			)
			->groupBy('users.id')
			->get();

		// If no users are found, return an empty response
		if ($users->isEmpty()) {
			return datatables()->of([])->toJson();
		}

		// Initialize report array
		$report = [];

		// Fetch all relevant data in bulk
		$userIds = $users->pluck('id')->toArray();
		
		// Fetch CV notes in bulk for total
		$cvNotes = DB::table('cv_notes')
			->whereIn('user_id', $userIds)
			->where('status', 'active')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as cv_note_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		$applicants_created = DB::table('audits')
			->where('auditable_type', 'Horsefly\Applicant')
			->where('message','LIKE','%has been created%')
			->whereIn('user_id', $userIds)
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as applicants_count_created'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		$applicants_updated = DB::table('audits')
			->where('auditable_type', 'Horsefly\Applicant')
			->where('message','LIKE','%has been updated%')
			->whereIn('user_id', $userIds)
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as applicants_count_updated'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');

		// Fetch applicants created within the date range (by created_at)
		$applicants_created2 = DB::table('applicants')
			->whereIn('applicant_user_id', $userIds)
			->whereNotNull('created_at') // Ensure created_at exists
			->whereRaw('DATE(created_at) BETWEEN ? AND ?', [$start_date, $end_date])
			->select('applicant_user_id', DB::raw('COUNT(*) as applicants_count_created'))
			->groupBy('applicant_user_id')
			->get()
			->keyBy('applicant_user_id');

		// Fetch applicants updated within the date range (where created_at != updated_at)
		$applicants_updated2 = DB::table('applicants')
			->whereIn('applicant_user_id', $userIds)
			->whereRaw('DATE(created_at) NOT BETWEEN ? AND ?', [$start_date, $end_date]) // Exclude created_at range
			->whereNotNull('updated_at') // Ensure updated_at exists
			->whereRaw('DATE(updated_at) BETWEEN ? AND ?', [$start_date, $end_date]) // Filter by updated_at range
			->select('applicant_user_id', DB::raw('COUNT(*) as applicants_count_updated'))
			->groupBy('applicant_user_id')
			->get()
			->keyBy('applicant_user_id');
		
		// Fetch engaged counts for applicant notes for total
		$applicant_notes = DB::table('applicant_notes')
			->whereIn('user_id', $userIds)
			//->where('status', 'active')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as applicant_notes_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		$module_notes = DB::table('module_notes')
			->where('module_noteable_type', 'Horsefly\Applicant')
			->whereIn('user_id', $userIds)
			//->where('status', 'active')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as module_notes_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		$sumOfEngagedCounts = [];

		// Process each user
		foreach ($users as $user) {
			$user_id = $user->id;
			
			/***engagedTotal*/
			$sumOfEngagedCounts[$user_id] =
				($applicants_created[$user_id]->applicants_count_created ?? 0) +
				($applicants_updated[$user_id]->applicants_count_updated ?? 0) +
				($applicant_notes[$user_id]->applicant_notes_count ?? 0) +
				($module_notes[$user_id]->module_notes_count ?? 0);
			
			
			$sumOfUpdatesCounts[$user_id] =
				($applicants_updated[$user_id]->applicants_count_updated ?? 0) +
				($applicant_notes[$user_id]->applicant_notes_count ?? 0) +
				($module_notes[$user_id]->module_notes_count ?? 0);
			
			// Initialize user stats
			$user_stats = [
				'engagedTotal' => $sumOfEngagedCounts[$user_id],
				'engagedCreated' => $applicants_created[$user_id]->applicants_count_created ?? 0,
				'engagedUpdated' => $sumOfUpdatesCounts[$user_id]
			];

			// Add user stats to the report
			$report[] = [
				'user_id' => $user_id,
				'user_name' => ucwords($user->name),
				'logged_in' => $user->logged_in_today,
				'stats' => $user_stats,
			];
		}

		// Sort the report array
		usort($report, function ($a, $b) {
			// If 'crm_paid' is zero or equal, sort by 'engaged' (or any other metric)
			return $b['stats']['engagedTotal'] <=> $a['stats']['engagedTotal'];
		});

		// Convert the report array to a DataTables-compatible response
		return datatables()->of($report)->toJson();
	}
	
	public function qualityReportAjax(Request $request)
	{
		// Parse dates from the request or default to the current date
		$start_date = $request->input('start_date')
			? Carbon::parse($request->input('start_date'))->startOfDay()->toDateTimeString()
			: Carbon::now()->startOfDay()->toDateTimeString();

		$end_date = $request->input('end_date')
			? Carbon::parse($request->input('end_date'))->endOfDay()->toDateTimeString()
			: Carbon::now()->endOfDay()->toDateTimeString();
		
		// Fetch users excluding specific IDs
		$users = DB::table('users')
			->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
			->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
			->leftJoin('login_details', function ($join) {
				$join->on('users.id', '=', 'login_details.user_id')
					 ->whereDate('login_details.login_date', Carbon::today()); // Filter by today's date
			})
			->whereNotIn('users.id', [1, 101])
			->where('users.is_active', '1')
			->where('roles.name', 'LIKE', 'quality%')
			->select(
				'users.id',
				'users.name',
				'login_details.login_time',
				DB::raw('CASE WHEN login_details.login_date IS NOT NULL THEN "Yes" ELSE "No" END as logged_in_today') // Check if logged in today
			)
			->groupBy('users.id')
			->get();

		// If no users are found, return an empty response
		if ($users->isEmpty()) {
			return datatables()->of([])->toJson();
		}

		// Initialize report array
		$report = [];

		// Fetch all relevant data in bulk
		$userIds = $users->pluck('id')->toArray();

		// Fetch Quality Cleared notes for total
		$qualityNotesCleared = DB::table('quality_notes')
			->whereIn('user_id', $userIds)
			->whereIn('moved_tab_to', ['cleared'])
			//->where('status', 'active')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as quality_cleared_note_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');

		// Fetch Quality Rejected notes for total
		$qualityNotesRejected = DB::table('quality_notes')
			->whereIn('user_id', $userIds)
			->whereIn('moved_tab_to', ['rejected'])
			//->where('status', 'active')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as quality_rejected_note_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');

		// Fetch Quality Hold notes for total
		$qualityNotesHold = DB::table('quality_notes')
			->whereIn('user_id', $userIds)
			->whereIn('moved_tab_to', ['cv_hold'])
			//->where('status', 'active')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as quality_hold_note_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');

		// Fetch CV notes count in bulk for total
		$cvNotesCount = DB::table('cv_notes')
			->whereIn('user_id', $userIds)
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as cv_note_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');

		// Fetch engaged counts for applicant notes for total
		$applicant_notes = DB::table('applicant_notes')
			->whereIn('user_id', $userIds)
			//->where('status', 'active')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as applicant_notes_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		$module_notes = DB::table('module_notes')
			->where('module_noteable_type', 'Horsefly\Applicant')
			->whereIn('user_id', $userIds)
			//->where('status', 'active')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as module_notes_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');

		// Process each user
		foreach ($users as $user) {
			$user_id = $user->id;

			// Calculate total engaged counts
			$sumOfEngagedCounts =
				($qualityNotesCleared[$user_id]->quality_cleared_note_count ?? 0) +
				($qualityNotesRejected[$user_id]->quality_rejected_note_count ?? 0) +
				($qualityNotesHold[$user_id]->quality_hold_note_count ?? 0) +
				($applicant_notes[$user_id]->applicant_notes_count ?? 0) +
				($module_notes[$user_id]->module_notes_count ?? 0) +
				($cvNotesCount[$user_id]->cv_note_count ?? 0);

			// Initialize user stats
			$user_stats = [
				'engagedTotal' => $sumOfEngagedCounts,
				'cvs_rejected' => $qualityNotesRejected[$user_id]->quality_rejected_note_count ?? 0,
				'cvs_cleared' => $qualityNotesCleared[$user_id]->quality_cleared_note_count ?? 0,
				'cvs_opened' => $qualityNotesHold[$user_id]->quality_hold_note_count ?? 0,
				'crm_rejected_cv' => 0,
				'crm_request' => 0,
				'crm_rejected_by_request' => 0,
			];
			
			$historyDetails = DB::table('history')->where('user_id', $user_id)
				->whereBetween('created_at', [$start_date, $end_date])
				->select('applicant_id', 'sale_id', 'sub_stage')
				->get();

			// Process CV history for the user
			if ($historyDetails) {
				foreach ($historyDetails as $history) {
					// Check for CRM request
					if (in_array($history->sub_stage, ['crm_reject'])) {
						$user_stats['crm_rejected_cv']++;
					}

					// Check for CRM confirmation
					if (in_array($history->sub_stage, ['crm_request'])) {
						$user_stats['crm_request']++;
					}

					// Check for interviews attended
					if (in_array($history->sub_stage, ['crm_request_reject'])) {
						$user_stats['crm_rejected_by_request']++;
					}
				}
			}
			
			// Add user stats to the report
			$report[] = [
				'user_id' => $user_id,
				'user_name' => ucwords($user->name),
				'logged_in' => $user->logged_in_today,
				'stats' => $user_stats,
			];
		}

		// Sort the report array by engagedTotal in descending order
		usort($report, function ($a, $b) {
			return $b['stats']['engagedTotal'] <=> $a['stats']['engagedTotal'];
		});

		// Convert the report array to a DataTables-compatible response
		return datatables()->of($report)->toJson();
	}
	
	public function crmReportAjax(Request $request)
	{
		// Parse dates from the request or default to the current date
		$start_date = $request->input('start_date')
			? Carbon::parse($request->input('start_date'))->startOfDay()->toDateTimeString()
			: Carbon::now()->startOfDay()->toDateTimeString();

		$end_date = $request->input('end_date')
			? Carbon::parse($request->input('end_date'))->endOfDay()->toDateTimeString()
			: Carbon::now()->endOfDay()->toDateTimeString();

		// Fetch users excluding specific IDs
		$users = DB::table('users')
			->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
			->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
			->leftJoin('login_details', function ($join) {
				$join->on('users.id', '=', 'login_details.user_id')
					 ->whereDate('login_details.login_date', Carbon::today()); // Filter by today's date
			})
			->whereNotIn('users.id', [1, 101])
			->where('users.is_active', '1')
			->where('roles.name', 'LIKE', 'CRM%')
			->select(
				'users.id',
				'users.name',
				'login_details.login_time',
				DB::raw('CASE WHEN login_details.login_date IS NOT NULL THEN "Yes" ELSE "No" END as logged_in_today') // Check if logged in today
			)
			->groupBy('users.id')
			->get();


		// If no users are found, return an empty response
		if ($users->isEmpty()) {
			return datatables()->of([])->toJson();
		}

		// Initialize report array
		$report = [];

		// Fetch all relevant data in bulk
		$userIds = $users->pluck('id')->toArray();
		
		// Fetch messages in bulk
		$messages = DB::table('applicant_messages')
			->whereIn('user_id', $userIds)
			->where('status', 'outgoing')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as message_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		// Fetch CV notes in bulk for total
		$cvNotes = DB::table('cv_notes')
			->whereIn('user_id', $userIds)
			//->where('status', 'active')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as cv_note_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');

		// Fetch applicants created within the date range (by created_at)
		$applicants_created2 = DB::table('applicants')
			->whereIn('applicant_user_id', $userIds)
			->whereNotNull('created_at') // Ensure created_at exists
			->whereRaw('DATE(created_at) BETWEEN ? AND ?', [$start_date, $end_date])
			->select('applicant_user_id', DB::raw('COUNT(*) as applicants_count_created'))
			->groupBy('applicant_user_id')
			->get()
			->keyBy('applicant_user_id');

		// Fetch applicants updated within the date range (where created_at != updated_at)
		$applicants_updated2 = DB::table('applicants')
			->whereIn('applicant_user_id', $userIds)
			->whereRaw('DATE(created_at) NOT BETWEEN ? AND ?', [$start_date, $end_date]) // Exclude created_at range
			->whereNotNull('updated_at') // Ensure updated_at exists
			->whereRaw('DATE(updated_at) BETWEEN ? AND ?', [$start_date, $end_date]) // Filter by updated_at range
			->select('applicant_user_id', DB::raw('COUNT(*) as applicants_count_updated'))
			->groupBy('applicant_user_id')
			->get()
			->keyBy('applicant_user_id');
		
		$applicants_created = DB::table('audits')
			->where('auditable_type', 'Horsefly\Applicant')
			->where('message','LIKE','%has been created%')
			->whereIn('user_id', $userIds)
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as applicants_count_created'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		$applicants_updated = DB::table('audits')
			->where('auditable_type', 'Horsefly\Applicant')
			->where('message','LIKE','%has been updated%')
			->whereIn('user_id', $userIds)
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as applicants_count_updated'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		// Fetch applicants created within the date range (by created_at)
		$sales_created = DB::table('sales')
			->leftJoin('audits', 'sales.id', '=', 'audits.auditable_id')
			->whereIn('audits.user_id', $userIds)
			->where('status','<>','pending')
			->where('audits.message', 'like', '%has been created%')
			->whereNotNull('sales.created_at') // Ensure updated_at exists
			->whereBetween('sales.created_at', [$start_date, $end_date]) // Filter by updated_at range
			->whereBetween('audits.created_at', [$start_date, $end_date]) // Filter by updated_at range
			->select(
				'audits.user_id',
				DB::raw('COUNT(DISTINCT CONCAT(audits.auditable_id, "-", DATE_FORMAT(audits.created_at, "%Y-%m-%d %H:%i"))) as sales_count_created'), 
				//DB::raw('COUNT(DISTINCT audits.auditable_id) as sales_count_created'), // Count on-hold sales
				DB::raw('MAX(audits.id) as max_audit_id') // Get the highest audit ID
			)
			->groupBy('audits.user_id')
			->get()
			->keyBy('user_id');
		
		$sales_updated = DB::table('sales')
			->leftJoin('audits', 'sales.id', '=', 'audits.auditable_id')
			->whereIn('audits.user_id', $userIds)
			->where('audits.message', 'like', '%has been updated%')
			->where('status','<>','pending')
			->whereNotBetween('sales.created_at', [$start_date, $end_date]) // Exclude created_at range
			->whereNotNull('sales.updated_at') // Ensure updated_at exists
			->whereBetween('sales.updated_at', [$start_date, $end_date]) // Filter by updated_at range
			->whereBetween('audits.created_at', [$start_date, $end_date]) // Filter by updated_at range
			->select(
				'audits.user_id',
				DB::raw('COUNT(DISTINCT CONCAT(audits.auditable_id, "-", DATE_FORMAT(audits.created_at, "%Y-%m-%d %H:%i"))) as sales_count_updated'), 
				//DB::raw('COUNT(DISTINCT audits.auditable_id) as sales_count_updated'), // Count on-hold sales
				DB::raw('MAX(audits.id) as max_audit_id') // Get the highest audit ID
			)
			->groupBy('audits.user_id')
			->get()
			->keyBy('user_id');
				
		$sales_closed = DB::table('sales')
			->leftJoin('audits', 'sales.id', '=', 'audits.auditable_id')
			->whereIn('audits.user_id', $userIds)
			->where('audits.message', 'like', '%sale-closed%')
			//->where('sales.status', 'disable')
			->whereNotBetween('sales.created_at', [$start_date, $end_date]) // Exclude created_at range
			->whereNotNull('sales.updated_at') // Ensure updated_at exists
			->whereBetween('sales.updated_at', [$start_date, $end_date]) // Filter by updated_at range
			->whereBetween('audits.created_at', [$start_date, $end_date]) // Filter by updated_at range
			->select(
				'audits.user_id',
				DB::raw('COUNT(DISTINCT CONCAT(audits.auditable_id, "-", DATE_FORMAT(audits.created_at, "%Y-%m-%d %H:%i"))) as sales_count_closed'),
				//DB::raw('COUNT(DISTINCT audits.auditable_id) as sales_count_closed'), // Count on-hold sales
				DB::raw('MAX(audits.id) as max_audit_id') // Get the highest audit ID
			)
			->groupBy('audits.user_id')
			->get()
			->keyBy('user_id');
		
		$sales_onHold = DB::table('sales')
			->leftJoin('audits', 'sales.id', '=', 'audits.auditable_id')
			->whereIn('audits.user_id', $userIds)
			->where('sales.is_on_hold', '1')
			//->where('sales.status', 'active')
			->whereNotBetween('sales.created_at', [$start_date, $end_date]) // Exclude created_at range
			->whereNotNull('sales.updated_at') // Ensure updated_at exists
			->whereBetween('sales.updated_at', [$start_date, $end_date]) // Filter by updated_at range
			->whereBetween('audits.created_at', [$start_date, $end_date]) // Filter by updated_at range
			->select(
				'audits.user_id',
				DB::raw('COUNT(DISTINCT CONCAT(audits.auditable_id, "-", DATE_FORMAT(audits.created_at, "%Y-%m-%d %H:%i"))) as sales_count_onhold'),
				//DB::raw('COUNT(DISTINCT audits.auditable_id) as sales_count_onhold'), // Count on-hold sales
				DB::raw('MAX(audits.id) as max_audit_id') // Get the highest audit ID
			)
			->groupBy('audits.user_id')
			->get()
			->keyBy('user_id');
		
		$sales_re_open = DB::table('sales')
			->leftJoin('audits', function ($join) {
				$join->on('audits.auditable_id', '=', 'sales.id')
					->where('audits.auditable_type', '=', 'Horsefly\\Sale')
					->where('audits.message', 'like', '%sale-opened%');
			})
			->whereIn('audits.user_id', $userIds)
			->where('sales.is_on_hold', '0')
			//->where('sales.status', 'active')
			->whereNotBetween('sales.created_at', [$start_date, $end_date]) // Exclude created_at range
			->whereNotNull('sales.updated_at') // Ensure updated_at exists
			->whereBetween('sales.updated_at', [$start_date, $end_date]) // Filter by updated_at range
			->whereBetween('audits.created_at', [$start_date, $end_date]) // Optimized date filter
			->select(
				'audits.user_id',
				DB::raw('COUNT(DISTINCT CONCAT(audits.auditable_id, "-", DATE_FORMAT(audits.created_at, "%Y-%m-%d %H:%i"))) as sales_count_re_open'),
				//DB::raw('COUNT(DISTINCT audits.auditable_id) as sales_count_re_open'), // Count re-opened sales
				DB::raw('MAX(audits.id) as max_audit_id') // Get the maximum audit ID
			)
			->groupBy('audits.user_id')
			->get()
			->keyBy('user_id');
		
		// Fetch engaged counts for applicant notes for total
		$applicant_notes = DB::table('applicant_notes')
			->whereIn('user_id', $userIds)
			//->where('status', 'active')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as applicant_notes_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
				// Fetch engaged counts for applicant notes for total
		$crm_notes = DB::table('crm_notes')
			->whereIn('user_id', $userIds)
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as crm_notes_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		// Fetch engaged counts in bulk for total
		$module_notes_applicant = DB::table('module_notes')
			->where('module_noteable_type', 'Horsefly\Applicant')
			->whereIn('user_id', $userIds)
			//->where('status', 'active')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as module_notes_applicant_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		// Fetch engaged counts in bulk for total
		$module_notes_sale = DB::table('module_notes')
			->where('module_noteable_type', 'Horsefly\Sale')
			->whereIn('user_id', $userIds)
			//->where('status', 'active')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as module_notes_sale_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');

		$sumOfEngagedCounts = [];
		$sumOfSalesCounts = [];
		$sumOfApplicantCounts = [];
		// Process each user
		foreach ($users as $user) {
			$user_id = $user->id;
			
			/***engagedTotal*/
			$sumOfEngagedCounts[$user_id] =
				($applicants_created[$user_id]->applicants_count_created ?? 0) + 
				($applicants_updated[$user_id]->applicants_count_updated ?? 0) +
				($crm_notes[$user_id]->crm_notes_count ?? 0) +
				($sales_created[$user_id]->sales_count_created ?? 0) +
				($sales_updated[$user_id]->sales_count_updated ?? 0) + 
				($sales_closed[$user_id]->sales_count_closed ?? 0) + 
				($sales_onHold[$user_id]->sales_count_onhold ?? 0) +
				($module_notes_applicant[$user_id]->module_notes_applicant_count ?? 0) +
				($module_notes_sale[$user_id]->module_notes_sale_count ?? 0) +
				($sales_re_open[$user_id]->sales_count_re_open ?? 0);
			
			$sumOfSalesCounts[$user_id] =
				($sales_created[$user_id]->sales_count_created ?? 0) +
				($sales_updated[$user_id]->sales_count_updated ?? 0) + 
				($sales_closed[$user_id]->sales_count_closed ?? 0) + 
				($sales_onHold[$user_id]->sales_count_onhold ?? 0) +
				($module_notes_sale[$user_id]->module_notes_sale_count ?? 0) +
				($sales_re_open[$user_id]->sales_count_re_open ?? 0); 
			
			$sumOfApplicantCounts[$user_id] =
				($applicants_created[$user_id]->applicants_count_created ?? 0) + 
				($applicants_updated[$user_id]->applicants_count_updated ?? 0) +
				($module_notes_applicant[$user_id]->module_notes_applicant_count ?? 0) +
				($crm_notes[$user_id]->crm_notes_count ?? 0);
			
			// Initialize user stats
			$user_stats = [
				'engagedTotal' => $sumOfEngagedCounts[$user_id],
				'engagedApplicants' => $sumOfApplicantCounts[$user_id],
				'engagedSales' => $sumOfSalesCounts[$user_id],
				'sms' => $messages[$user_id]->message_count ?? 0,
				'calls' => 0, // Add logic for calls if needed
				'crm_request' => 0,
				'crm_rejected_by_request' => 0,
				'crm_confirmation' => 0,
				'crm_attended' => 0,
				'crm_not_attended' => 0,
			];
			
			// Fetch detailed CV notes for CRM history processing
			$historyDetails = DB::table('history')->where('user_id', $user_id)
				->whereBetween('created_at', [$start_date, $end_date])
				->select('applicant_id', 'sale_id', 'sub_stage')
				->get();

			// Process CV history for the user
			if ($historyDetails) {
				foreach ($historyDetails as $history) {
					// Check for CRM request
					if (in_array($history->sub_stage, ['crm_request'])) {
						$user_stats['crm_request']++;
					}
					
					// Check for CRM request reject
					if (in_array($history->sub_stage, ['crm_request'])) {
						$user_stats['crm_request']++;
					}

					// Check for CRM confirmation
					if (in_array($history->sub_stage, ['crm_request_reject'])) {
						$user_stats['crm_rejected_by_request']++;
					}

					// Check for interviews attended
					if (in_array($history->sub_stage, ['crm_interview_attended'])) {
						$user_stats['crm_attended']++;
					}

					// Check for interviews not attended
					if (in_array($history->sub_stage, ['crm_interview_not_attended'])) {
						$user_stats['crm_not_attended']++;
					}
				}
			}

			// Add user stats to the report
			$report[] = [
				'user_id' => $user_id,
				'user_name' => ucwords($user->name),
				'logged_in' => $user->logged_in_today,
				'stats' => $user_stats,
			];
		}

		// Sort the report array
		usort($report, function ($a, $b) {
			// If 'crm_paid' is zero or equal, sort by 'engaged' (or any other metric)
			return $b['stats']['engagedTotal'] <=> $a['stats']['engagedTotal'];
		});

		// Convert the report array to a DataTables-compatible response
		return datatables()->of($report)->toJson();
	}
	
	public function saleReportAjax(Request $request)
	{
		// Parse dates from the request or default to the current date
		$start_date = $request->input('start_date')
			? Carbon::parse($request->input('start_date'))->startOfDay()->toDateTimeString()
			: Carbon::now()->startOfDay()->toDateTimeString();

		$end_date = $request->input('end_date')
			? Carbon::parse($request->input('end_date'))->endOfDay()->toDateTimeString()
			: Carbon::now()->endOfDay()->toDateTimeString();
	
		// Fetch users excluding specific IDs
		$users = DB::table('users')
			->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
			->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
			->leftJoin('login_details', function ($join) {
				$join->on('users.id', '=', 'login_details.user_id')
					 ->whereDate('login_details.login_date', Carbon::today()); // Filter by today's date
			})
			->whereNotIn('users.id', [1, 101])
			->where('users.is_active', '1')
			->where('roles.name', 'LIKE', 'sales%')
			->select(
				'users.id',
				'users.name',
				'login_details.login_time',
				DB::raw('CASE WHEN login_details.login_date IS NOT NULL THEN "Yes" ELSE "No" END as logged_in_today') // Check if logged in today
			)
			->groupBy('users.id')
			->get();

		// If no users are found, return an empty response
		if ($users->isEmpty()) {
			return datatables()->of([])->toJson();
		}

		// Initialize report array
		$report = [];

		// Fetch all relevant data in bulk
		$userIds = $users->pluck('id')->toArray();
		
		// Fetch messages in bulk
		$messages = DB::table('applicant_messages')
			->whereIn('user_id', $userIds)
			->where('status', 'outgoing')
			->whereBetween('created_at', [$start_date, $end_date])
			->select('user_id', DB::raw('COUNT(*) as message_count'))
			->groupBy('user_id')
			->get()
			->keyBy('user_id');
		
		$sales_created = DB::table('sales')
			->leftJoin('audits', 'sales.id', '=', 'audits.auditable_id')
			->whereIn('audits.user_id', $userIds)
			->where('status','<>','pending')
			->where('audits.message', 'like', '%has been created%')
			->whereNotNull('sales.created_at') // Ensure updated_at exists
			->whereBetween('sales.created_at', [$start_date, $end_date]) // Filter by updated_at range
			->whereBetween('audits.created_at', [$start_date, $end_date]) // Filter by updated_at range
			->select(
				'audits.user_id',
				DB::raw('COUNT(DISTINCT CONCAT(audits.auditable_id, "-", DATE_FORMAT(audits.created_at, "%Y-%m-%d %H:%i"))) as sales_count_created'), 
				//DB::raw('COUNT(DISTINCT audits.auditable_id) as sales_count_created'), // Count on-hold sales
				DB::raw('MAX(audits.id) as max_audit_id') // Get the highest audit ID
			)
			->groupBy('audits.user_id')
			->get()
			->keyBy('user_id');
		
		$sales_updated = DB::table('sales')
			->leftJoin('audits', 'sales.id', '=', 'audits.auditable_id')
			->whereIn('audits.user_id', $userIds)
			->where('audits.message', 'like', '%has been updated%')
			//->where('sales.status', 'active')
			->whereNotBetween('sales.created_at', [$start_date, $end_date]) // Exclude created_at range
			->whereNotNull('sales.updated_at') // Ensure updated_at exists
			->whereBetween('sales.updated_at', [$start_date, $end_date]) // Filter by updated_at range
			->whereBetween('audits.updated_at', [$start_date, $end_date]) // Filter by updated_at range
			->select(
				'audits.user_id',
				DB::raw('COUNT(DISTINCT CONCAT(audits.id, "-", DATE_FORMAT(audits.created_at, "%Y-%m-%d %H:%i"))) as sales_count_updated'), // Ensure unique entries per created_at time with minutes
				DB::raw('MAX(audits.id) as max_audit_id') // Get the maximum audit ID
			)
			->groupBy('audits.user_id')
			->get()
			->keyBy('user_id');
				
		$sales_closed = DB::table('sales')
			->leftJoin('audits', 'sales.id', '=', 'audits.auditable_id')
			->whereIn('audits.user_id', $userIds)
			->where('audits.message', 'like', '%sale-closed%')
			//->where('sales.status', 'disable')
			->whereNotBetween('sales.created_at', [$start_date, $end_date]) // Exclude created_at range
			->whereNotNull('sales.updated_at') // Ensure updated_at exists
			->whereBetween('sales.updated_at', [$start_date, $end_date]) // Filter by updated_at range
			->whereBetween('audits.updated_at', [$start_date, $end_date]) // Filter by updated_at range
			->select(
				'audits.user_id',
				DB::raw('COUNT(DISTINCT CONCAT(audits.id, "-", DATE_FORMAT(audits.created_at, "%Y-%m-%d %H:%i"))) as sales_count_closed'), // Ensure unique entries per created_at time with minutes
				//DB::raw('COUNT(DISTINCT audits.auditable_id) as sales_count_closed'), // Count on-hold sales
				DB::raw('MAX(audits.id) as max_audit_id') // Get the highest audit ID
			)
			->groupBy('audits.user_id')
			->get()
			->keyBy('user_id');
		
		$sales_onHold = DB::table('sales')
			->leftJoin('audits', 'sales.id', '=', 'audits.auditable_id')
			->whereIn('audits.user_id', $userIds)
			->where('sales.is_on_hold', '1')
			//->where('sales.status', 'active')
			->whereNotBetween('sales.created_at', [$start_date, $end_date]) // Exclude created_at range
			->whereNotNull('sales.updated_at') // Ensure updated_at exists
			->whereBetween('sales.updated_at', [$start_date, $end_date]) // Filter by updated_at range
			->whereBetween('audits.updated_at', [$start_date, $end_date]) // Filter by updated_at range
			->select(
				'audits.user_id',
				DB::raw('COUNT(DISTINCT CONCAT(audits.id, "-", DATE_FORMAT(audits.created_at, "%Y-%m-%d %H:%i"))) as sales_count_onhold'), // Ensure unique entries per created_at time with minutes
				//DB::raw('COUNT(DISTINCT audits.auditable_id) as sales_count_onhold'), // Count on-hold sales
				DB::raw('MAX(audits.id) as max_audit_id') // Get the highest audit ID
			)
			->groupBy('audits.user_id')
			->get()
			->keyBy('user_id');
		
		$sales_re_open = DB::table('sales')
			->leftJoin('audits', function ($join) {
				$join->on('audits.auditable_id', '=', 'sales.id')
					->where('audits.auditable_type', '=', 'Horsefly\\Sale')
					->where('audits.message', 'like', '%sale-opened%');
			})
			->whereIn('audits.user_id', $userIds)
			->where('sales.is_on_hold', '0')
			//->where('sales.status', 'active')
			->whereNotBetween('sales.created_at', [$start_date, $end_date]) // Exclude created_at range
			->whereNotNull('sales.updated_at') // Ensure updated_at exists
			->whereBetween('sales.updated_at', [$start_date, $end_date]) // Filter by updated_at range
			->whereBetween('audits.updated_at', [$start_date, $end_date]) // Optimized date filter
			->select(
				'audits.user_id',
				DB::raw('COUNT(DISTINCT CONCAT(audits.id, "-", DATE_FORMAT(audits.created_at, "%Y-%m-%d %H:%i"))) as sales_count_re_open'), // Ensure unique entries per created_at time with minutes
				//DB::raw('COUNT(DISTINCT audits.auditable_id) as sales_count_re_open'), // Count re-opened sales
				DB::raw('MAX(audits.id) as max_audit_id') // Get the maximum audit ID
			)
			->groupBy('audits.user_id')
			->get()
			->keyBy('user_id');

		$sumOfEngagedCounts = [];
		// Process each user
		foreach ($users as $user) {
			$user_id = $user->id;

			/***engagedTotal*/
			$sumOfEngagedCounts[$user_id] =
				($sales_created[$user_id]->sales_count_created ?? 0) +
				($sales_updated[$user_id]->sales_count_updated ?? 0) +
				($sales_re_open[$user_id]->sales_count_re_open ?? 0) +
				($sales_closed[$user_id]->sales_count_closed ?? 0) +
				($sales_onHold[$user_id]->sales_count_onhold ?? 0);
			
			// Initialize user stats
			$user_stats = [
				'calls' => 0, // Add logic for calls if needed
				'engagedTotal' => $sumOfEngagedCounts[$user_id],
				'engagedCreated' => $sales_created[$user_id]->sales_count_created ?? 0,
				'engagedUpdated' => $sales_updated[$user_id]->sales_count_updated ?? 0,
				'engagedReopened' => $sales_re_open[$user_id]->sales_count_re_open ?? 0,
				'engagedOnHold' => $sales_onHold[$user_id]->sales_count_onhold ?? 0,
				'engagedClosed' => $sales_closed[$user_id]->sales_count_closed ?? 0
			];

			// Add user stats to the report
			$report[] = [
				'user_id' => $user_id,
				'user_name' => ucwords($user->name),
				'logged_in' => $user->logged_in_today,
				'stats' => $user_stats,
			];
		}

		// Sort the report array by engagedTotal in descending order
		usort($report, function ($a, $b) {
			return $b['stats']['engagedTotal'] <=> $a['stats']['engagedTotal'];
		});

		// Convert the report array to a DataTables-compatible response
		return datatables()->of($report)->toJson();
	}
    
		private function processCvHistory($user_id, &$user_stats, $start_date, $end_date)
		{
			// Process cleared CVs
			$cvNotes = Cv_note::where('user_id', '=', $user_id)
                ->whereBetween('created_at', [$start_date, $end_date])
                ->select('applicant_id', 'sale_id')
                ->get();

			foreach($cvNotes as $cv){
				$cv_cleared = History::where([
                    'sub_stage' => 'quality_cleared', 
                    'applicant_id' => $cv->applicant_id, 
                    'sale_id' => $cv->sale_id
                    ])
                    ->whereBetween('updated_at', [$start_date, $end_date])
                    ->first();

                if ($cv_cleared) {
                    $user_stats['cvs_cleared']++;
                    /*** Sent CVs */
                    $user_stats['crm_sent_cvs']++;


                    /*** Rejected CV */
                    $crm_rejected_cv = History::where([
                        'sub_stage' => 'crm_reject', 
                        'applicant_id' => $cv->applicant_id, 
                        'sale_id' => $cv->sale_id, 
                        'status' => 'active'
                        ])
                        ->whereBetween('created_at', [$start_date, $end_date])
                        ->first();

                    if ($crm_rejected_cv) {
                        $user_stats['crm_rejected_cv']++;
                        continue;
                    }


                    /*** Request */
                    $crm_request = History::where([
                        'sub_stage' => 'crm_request', 
                        'applicant_id' => $cv->applicant_id, 
                        'sale_id' => $cv->sale_id
                        ])
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
                                    $crm_invoice = History::where([ 
										'applicant_id' => $cv->applicant_id, 
										'sale_id' => $cv->sale_id
									])
										->whereIn('history.sub_stage', ['crm_invoice', 'crm_final_save'])
                                        ->whereIn('id', function ($query) {
                                            $query->select(DB::raw('MAX(id) FROM history h WHERE 
											h.sub_stage IN ("crm_invoice", "crm_final_save") and 
											h.sale_id=history.sale_id and h.applicant_id=history.applicant_id'));
                                        })->whereBetween('created_at', [$start_date, $end_date])->first();

                                    if ($crm_invoice) {
                                        $user_stats['crm_invoice']++;

                                        /*** Dispute */
                                        $crm_dispute = History::where([
											'sub_stage' => 'crm_dispute', 
											'applicant_id' => $cv->applicant_id, 
											'sale_id' => $cv->sale_id, 
											'status' => 'active'
										])
                                            ->whereBetween('created_at', [$start_date, $end_date])
											->orWhereBetween('updated_at', [$start_date, $end_date])->first();
                                        if ($crm_dispute) {
                                            $user_stats['crm_dispute']++;
                                            continue;
                                        }

                                        /*** PAID */
                                        $crm_paid = History::where([
											'sub_stage' => 'crm_paid', 
											'applicant_id' => $cv->applicant_id, 
											'sale_id' => $cv->sale_id
										])
                                            ->whereBetween('created_at', [$start_date, $end_date])->first();
                                        if ($crm_paid) {
                                            $user_stats['crm_paid']++;
                                        }
                                    }
                                }
                            }
                        }
                    }
				} else {
					$cv_rejected = History::where([
                        'sub_stage' => 'quality_reject', 
                        'applicant_id' => $cv->applicant_id, 
                        'sale_id' => $cv->sale_id, 
                        'status' => 'active'
                        ])
                        ->whereBetween('created_at', [$start_date, $end_date])
                        ->first();

                    if ($cv_rejected) {
                        $user_stats['cvs_rejected']++;
                    }
				}
			}
		}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::pluck('name','name')->all();
        return view('administrator.users.create', compact('roles'));
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
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|max:15',
            'roles' => 'required'
        ])->validate();

        $user = new User();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = bcrypt($request->input('password'));
        $user->save();

        $user->assignRole($request->input('roles'));

        $last_inserted_user = $user->id;
        if ($last_inserted_user) {
            DB::table("users")->where('id', $last_inserted_user)->update(['is_admin' => 0]);
            return redirect('users')->with('user_success_msg', 'User Added Successfully');
        } else {
            return redirect('users.create')->with('user_add_error', 'WHOOPS! User Could not Added');
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
        $user = User::find($id);
        $roles = Role::pluck('name','name')->all();
        $userRole = $user->roles->pluck('name','name')->all();

        return view('administrator.users.edit', compact('user','roles','userRole'));
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
        date_default_timezone_set('Europe/London');

        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$id,
            'roles' => 'required'
        ]);

        $update_data  = [ "name" => $request->Input('name'), "email" => $request->Input('email') ];
        if ($request->filled('password'))
            $update_data['password'] = bcrypt($request->Input('password'));
        $user = User::find($id);
        $user->update($update_data);

        DB::table('model_has_roles')->where('model_id',$id)->delete();
        $user->assignRole($request->input('roles'));

        return redirect('users')->with('updateUserSuccessMsg', 'User has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }

    public function assignRoleToUsers(Request $request)
    {
        foreach ($request->input('users') as $user_id) {
            DB::table('model_has_roles')->where('model_id',$user_id)->delete();
            $user = User::find($user_id);
         @$user->assignRole($request->input('role'));
        }
        return redirect()->back()->with('success','Role assigned successfully');
    }

	public function getUserStatusChange($id)
	{
		$user = User::find($id);

		if (!$user) {
			return redirect('users')->with('UserStatusErrMsg', 'User not found!');
		}

		$newStatus = $user->is_active ? 0 : 1; // Toggle status
		$statusMessage = $newStatus ? 'enabled' : 'disabled';

		if ($user->update(['is_active' => $newStatus])) {
			return redirect('users')->with("UserStatusSuccessMsg", "User has been {$statusMessage} successfully.");
		}

		return redirect('users')->with('UserStatusErrMsg', 'Something went wrong!');
	}

    /**
     * Display activity log view
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function activityLogs($id)
    {
        $user = User::find($id);

        return view('administrator.users.activity_logs',
            compact('user'));
    }

    /**
     * Ajax request for data table to fetch user activity logs
     *
     * @param $id
     * @return mixed
     */
    public function userLogs($id)
    {
        $audits = Audit::where('user_id', $id)->orderBy('id', 'DESC')->get();

        return datatables($audits)
            ->addColumn('user', function ($audit) {
                return $audit->user_id;
            })
                         ->addColumn('details', function ($audit) {
                $content = "";
                $content .= '<a href="#" class=""
                                 data-controls-modal="#modal_audit_details'.$audit->id.'"
                                 data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                 data-target="#modal_audit_details'.$audit->id.'">
                                 Details</a>';
                $content .= '<div id="modal_audit_details'.$audit->id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h6 class="modal-title">Action Details</h6>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body" style="max-height: 500px; overflow-y: auto;">';
                if (!empty($audit->data['changes_made'])) {
                    $content .= '<h6 class="font-weight-semibold">Changes</h6>';
                    foreach ($audit->data['changes_made'] as $key_2 => $val_2) {
                        $content .= '<div class="col-1"></div>';

                        if (is_array($val_2)) {
                            $content .= '<p><span class="font-weight-semibold">'.str_replace('_', ' ', $key_2).': </span>'.implode(', ', $val_2).'</p>';
                        } else {
                            $content .= '<p><span class="font-weight-semibold">'.str_replace('_', ' ', $key_2).': </span>'.$val_2.'</p>';
                        }
                    }

                } else {
                    $content .= '<h6 class="font-weight-semibold">Details</h6>';
                    foreach ($audit->data as $key_1 => $val_1) {
                        $content .= '<div class="col-1"></div>';

                        if (is_array($val_1)) {
                            $content .= '<p><span class="font-weight-semibold">'.str_replace('_', ' ', $key_1).': </span>'.implode(', ', $val_1).'</p>';
                        } else {
                            $content .= '<p><span class="font-weight-semibold">'.str_replace('_', ' ', $key_1).': </span>'.$val_1.'</p>';
                        }
                    }
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn('module', function ($audit) {
                $module = explode("\\",$audit->auditable_type);
                return $module[count($module) - 1];
            })
            ->rawColumns(['user', 'details', 'module'])
            ->make(true);
    }
}
