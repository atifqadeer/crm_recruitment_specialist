<?php

namespace Horsefly\Http\Controllers\Administrator;

use Carbon\Carbon;
use Horsefly\Applicant;
use Horsefly\Applicant_message;
use Horsefly\Crm_note;
use Horsefly\Observers\ActionObserver;
use Horsefly\Sale;
use Horsefly\Crm_rejected_cv;
use Horsefly\Office;
use Horsefly\Quality_notes;
use Horsefly\Specialist_job_titles;
use Horsefly\Sales_notes;
use Horsefly\History;
use Horsefly\Unit;
use Horsefly\Cv_note;
use Horsefly\Interview;
use Horsefly\SentEmail;
use Horsefly\EmailTemplate;
use Illuminate\Http\Request;
use Horsefly\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Horsefly\Exports\CrmEmailExport;
use Illuminate\Support\Facades\Mail;
//use Auth;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Session;

class CrmController extends Controller
{
//    private $action_observer;

    public function __construct()
    {
        $this->middleware('auth');
        /*** CRM - Sent CVs */
        $this->middleware('permission:CRM_Sent-CVs_list|CRM_Sent-CVs_request|CRM_Sent-CVs_save|CRM_Sent-CVs_reject|CRM_Rejected-CV_list|CRM_Request_list|CRM_Rejected-By-Request_list|CRM_Confirmation_list|CRM_Attended_list|CRM_Not-Attended_list|CRM_Start-Date_list|CRM_Start-Date-Hold_list|CRM_Invoice_list|CRM_Dispute_list|CRM_Paid_list', ['only' => ['index','crmSentCv']]);
        $this->middleware('permission:CRM_Sent-CVs_request|CRM_Sent-CVs_save|CRM_Sent-CVs_reject', ['only' => ['sentCvAction']]);
        /*** CRM - Rejected CV */
        $this->middleware('permission:CRM_Rejected-CV_list|CRM_Rejected-CV_revert-sent-cv', ['only' => ['crmRejectCv']]);
        $this->middleware('permission:CRM_Rejected-CV_revert-sent-cv', ['only' => ['revertSentCvAction']]);
        /*** CRM - Request */
        $this->middleware('permission:CRM_Request_list|CRM_Request_reject|CRM_Request_confirm|CRM_Request_save|CRM_Request_schedule-interview', ['only' => ['crmRequest']]);
		        //$this->middleware('permission:CRM_Request_list|CRM_Request_reject|CRM_Request_confirm|CRM_Request_save|CRM_Request_schedule-interview|applicant_chat-box', ['only' => ['crmRequestNurse']]);
        $this->middleware('permission:CRM_Request_reject|CRM_Request_confirm|CRM_Request_save', ['only' => ['requestAction']]);
        $this->middleware('permission:CRM_Request_schedule-interview', ['only' => ['getInterviewSchedule']]);
        /*** CRM - Rejected By Request */
        $this->middleware('permission:CRM_Rejected-By-Request_list|CRM_Rejected-By-Request_revert-sent-cv|CRM_Rejected-By-Request_revert-request', ['only' => ['crmRejectByRequest']]);
        $this->middleware('permission:CRM_Rejected-By-Request_revert-sent-cv|CRM_Rejected-By-Request_revert-request', ['only' => ['rejectByRequestAction']]);
        /*** CRM - Confirmation */
        $this->middleware('permission:CRM_Confirmation_list|CRM_Confirmation_revert-request|CRM_Confirmation_not-attended|CRM_Confirmation_attend|CRM_Confirmation_rebook|CRM_Confirmation_save', ['only' => ['crmConfirmation']]);
        $this->middleware('permission:CRM_Confirmation_revert-request|CRM_Confirmation_not-attended|CRM_Confirmation_attend|CRM_Confirmation_rebook|CRM_Confirmation_save', ['only' => ['afterInterviewAction']]);
        /*** CRM - Rebook */
        $this->middleware('permission:CRM_Rebook_list|CRM_Rebook_not-attended|CRM_Rebook_attend|CRM_Rebook_save', ['only' => ['crmRebook']]);
        $this->middleware('permission:CRM_Rebook_not-attended|CRM_Rebook_attend|CRM_Rebook_save', ['only' => ['rebookAction']]);
        /*** CRM - Pre-Start Date (Attend) */
        $this->middleware('permission:CRM_Attended_list|CRM_Attended_start-date|CRM_Attended_save', ['only' => ['crmPreStartDate']]);
        $this->middleware('permission:CRM_Attended_start-date|CRM_Attended_save', ['only' => ['attendedToPreStartAction']]);
        /*** CRM - Not Attended */
        $this->middleware('permission:CRM_Not-Attended_list|CRM_Not-Attended_revert-to-attended', ['only' => ['crmNotAttended']]);
        $this->middleware('permission:CRM_Not-Attended_revert-to-attended', ['only' => ['notAttendedAction']]);
        /*** CRM - Start Date */
        $this->middleware('permission:CRM_Start-Date_list|CRM_Start-Date_invoice|CRM_Start-Date_start-date-hold|CRM_Start-Date_save', ['only' => ['crmStartDate']]);
        $this->middleware('permission:CRM_Start-Date_invoice|CRM_Start-Date_start-date-hold|CRM_Start-Date_save', ['only' => ['startDateAction']]);
        /*** CRM - Start Date Hold */
        $this->middleware('permission:CRM_Start-Date-Hold_list|CRM_Start-Date-Hold_revert-start-date|CRM_Start-Date-Hold_save', ['only' => ['crmStartDateHold']]);
        $this->middleware('permission:CRM_Start-Date-Hold_revert-start-date|CRM_Start-Date-Hold_save', ['only' => ['startDateHoldAction']]);
        /*** CRM - Invoice */
        $this->middleware('permission:CRM_Invoice_list|CRM_Invoice_paid|CRM_Invoice_dispute|CRM_Invoice_save', ['only' => ['crmInvoice']]);
        $this->middleware('permission:CRM_Invoice_paid|CRM_Invoice_dispute|CRM_Invoice_save', ['only' => ['invoiceAction']]);
        /*** CRM - Dispute */
        $this->middleware('permission:CRM_Dispute_list|CRM_Dispute_revert-invoice', ['only' => ['crmDispute']]);
        $this->middleware('permission:CRM_Dispute_revert-invoice', ['only' => ['disputeAction']]);
        /*** CRM - Paid */
        $this->middleware('permission:CRM_Paid_list|CRM_Paid_open-close-cv', ['only' => ['crmPaid']]);
        $this->middleware('permission:CRM_Paid_open-close-cv', ['only' => ['paidAction']]);

//        $this->action_observer = new ActionObserver();
    }
	
	public function export_email($tab)
    {
        return Excel::download(new CrmEmailExport($tab), 'CRM_'.strtoupper($tab).'_Emails.csv');
    }
    
    public function index()
    {


        /***
        $data['applicant_with_cvs'] = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
            ->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('quality_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('quality_notes.sale_id', '=', 'history.sale_id');
            });
        $data['applicant_with_cvs'] = $data['applicant_with_cvs']->select('quality_notes.details','quality_notes.created_at','quality_notes.quality_added_date','quality_notes.quality_added_time', 'applicants.*', 'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
                'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
                'units.contact_email', 'units.website')
            ->where([
                "applicants.status" => "active",
                "quality_notes.moved_tab_to" => "cleared",
                "quality_notes.status" => "active",
                "history.status" => "active"
            ])->whereIn("history.sub_stage", ["quality_cleared", "crm_save"])
            ->orderBy("quality_notes.created_at","desc")
            ->get();
         */

//        $data['crm_cv_sent_save_note'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
//            ->select('applicants.id as app_id', 'crm_notes.details as crm_note_details', 'crm_added_date', 'crm_added_time')
//            ->where(["applicants.is_interview_confirm" => "yes", "applicants.status" => "active",
//                "crm_notes.moved_tab_to" => "cv_sent_saved"])->orderBy('crm_notes.id', 'DESC')->get();
        /***
        $data['crm_cv_sent_save_note'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            });
        $data['crm_cv_sent_save_note'] = $data['crm_cv_sent_save_note']->whereIn("history.sub_stage", ["quality_cleared", "crm_save"])
            ->whereIn("crm_notes.moved_tab_to", ["cv_sent", "cv_sent_saved"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();
         */

//        $data['crm_request_save_note'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
//            ->select('applicants.id as app_id', 'crm_notes.details as crm_note_details', 'crm_added_date', 'crm_added_time')
//            ->where(["applicants.is_interview_confirm" => "no", "applicants.status" => "active",
//                "crm_notes.moved_tab_to" => "request_save"])->orderBy('crm_notes.id', 'DESC')->get(); //first
        /***
        $data['crm_request_save_note'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            });
        $data['crm_request_save_note'] = $data['crm_request_save_note']->whereIn("history.sub_stage", ["crm_request", "crm_request_save"])
            ->whereIn("crm_notes.moved_tab_to", ["cv_sent_request", "request_save"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();
         */

//        $data['crm_confirm_save_note'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
//            ->select('applicants.id as app_id', 'crm_notes.details as crm_note_details', 'crm_added_date', 'crm_added_time')
//            ->where(["applicants.is_interview_confirm" => "no", "applicants.status" => "active",
//                "crm_notes.moved_tab_to" => "interview_save"])->orderBy('crm_notes.id', 'DESC')->get();
        /***
        $data['crm_confirm_save_note'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            });
        $data['crm_confirm_save_note'] = $data['crm_confirm_save_note']->whereIn("history.sub_stage", ["crm_request_confirm", "crm_interview_save"])
            ->whereIn("crm_notes.moved_tab_to", ["request_confirm", "interview_save"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

//        $data['crm_attend_save_note'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
//            ->select('applicants.id as app_id', 'crm_notes.details as crm_note_details', 'crm_added_date', 'crm_added_time')
//            ->where(["applicants.is_interview_confirm" => "no", "applicants.status" => "active",
//                "crm_notes.moved_tab_to" => "prestart_save"])->orderBy('crm_notes.id', 'DESC')->get();
        $data['crm_attend_save_note'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            });
        $data['crm_attend_save_note'] = $data['crm_attend_save_note']->whereIn("history.sub_stage", ["crm_interview_attended", "crm_prestart_save"])
            ->whereIn("crm_notes.moved_tab_to", ["interview_attended", "prestart_save"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

//        $data['crm_start_date_save_note'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
//            ->select('applicants.id as app_id', 'crm_notes.details as crm_note_details', 'crm_added_date', 'crm_added_time')
//            ->where(["applicants.is_interview_confirm" => "no", "applicants.status" => "active",
//                "crm_notes.moved_tab_to" => "start_date_save"])->orderBy('crm_notes.id', 'DESC')->get();
        $data['crm_start_date_save_note'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            });
        $data['crm_start_date_save_note'] = $data['crm_start_date_save_note']->whereIn("history.sub_stage", ["crm_start_date", "crm_start_date_save", "crm_start_date_back"])
            ->whereIn("crm_notes.moved_tab_to", ["start_date", "start_date_save", "start_date_back"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

//        $data['crm_start_date_back_note'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
//            ->select('applicants.id as app_id', 'crm_notes.details as crm_note_details', 'crm_added_date', 'crm_added_time')
//            ->where(["applicants.is_interview_confirm" => "no", "applicants.status" => "active",
//                "crm_notes.moved_tab_to" => "start_date_back"])->orderBy('crm_notes.id', 'DESC')->get();

//        $data['crm_start_date_hold_save_note'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
//            ->select('applicants.id as app_id', 'crm_notes.details as crm_note_details', 'crm_added_date', 'crm_added_time')
//            ->where(["applicants.is_interview_confirm" => "no", "applicants.status" => "active",
//                "crm_notes.moved_tab_to" => "start_date_hold_save"])->orderBy('crm_notes.id', 'DESC')->get();
        $data['crm_start_date_hold_save_note'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            });
        $data['crm_start_date_hold_save_note'] = $data['crm_start_date_hold_save_note']->whereIn("history.sub_stage", ["crm_start_date_hold", "crm_start_date_hold_save"])
            ->whereIn("crm_notes.moved_tab_to", ["start_date_hold", "start_date_hold_save"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

//        $data['crm_invoice_save_note'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
//            ->select('applicants.id as app_id', 'crm_notes.details as crm_note_details', 'crm_added_date', 'crm_added_time')
//            ->where(["applicants.is_interview_confirm" => "no", "applicants.status" => "active",
//                "crm_notes.moved_tab_to" => "final_save"])->orderBy('crm_notes.id', 'DESC')->get();
        $data['crm_invoice_save_note'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            });
        $data['crm_invoice_save_note'] = $data['crm_invoice_save_note']->whereIn("history.sub_stage", ["crm_invoice", "crm_final_save"])
            ->whereIn("crm_notes.moved_tab_to", ["invoice", "final_save"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

        /***
        $data['applicant_with_rejected_cvs'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            });
        $data['applicant_with_rejected_cvs'] = $data['applicant_with_rejected_cvs']->select('crm_notes.details', 'crm_added_date', 'crm_added_time',
                'applicants.*', 'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
                'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
                'units.contact_email', 'units.website')
            ->where([
                "applicants.status" => "active",
                "crm_notes.moved_tab_to" => "cv_sent_reject",
                "history.sub_stage" => "crm_reject", "history.status" => "active"
            ])->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="cv_sent_reject" and sales_id=sales.id and applicants.id=applicant_id'));
            })->orderBy("crm_notes.created_at","DESC")
            ->get();
         */

        /***
        $data['applicant_cvs_in_request'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->leftJoin('interviews', function ($join) {
                $join->on('applicants.id', '=', 'interviews.applicant_id');
                $join->on('sales.id', '=', 'interviews.sale_id');
                $join->where('interviews.status', '=', 'active');
            });
        $data['applicant_cvs_in_request'] = $data['applicant_cvs_in_request']->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_added_date', 'crm_added_time', 'applicants.*', 'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
                'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
                'units.contact_email', 'units.website',
                'interviews.schedule_time', 'interviews.schedule_date', 'interviews.status as interview_status')
            ->where([
                "applicants.status" => "active",
                "crm_notes.moved_tab_to" => "cv_sent_request", "crm_notes.status" => "active",
                "history.status" => "active"
            ])->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="cv_sent_request" and sales_id=sales.id and applicants.id=applicant_id'));
            })->whereIn("history.sub_stage", ["crm_request", "crm_request_save"])
            ->orderBy("crm_notes.created_at","DESC")
            ->get();
         */

        /***
        $data['reject_by_request'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id');
            /*** interview information is not required
            ->leftJoin('interviews', function ($join) {
                $join->on('applicants.id', '=', 'interviews.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'interviews.sale_id');
                $join->whereIn('interviews.id', function($query){
                    $query->select(DB::raw('MAX(id) FROM interviews WHERE sale_id=sales.id and applicants.id=applicant_id'));
                });
            });
             *** add this in select() : 'interviews.schedule_time', 'interviews.schedule_date', 'interviews.status as interview_status'
            */
        /***
        $data['reject_by_request'] = $data['reject_by_request']->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_added_date', 'crm_added_time',
                'applicants.*', 'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
                'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
                'units.contact_email', 'units.website')
            ->where([
                "applicants.status" => "active",
                "crm_notes.moved_tab_to" => "request_reject",
                "history.sub_stage" => "crm_request_reject", "history.status" => "active"
            ])->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="request_reject" and sales_id=sales.id and applicants.id=applicant_id'));
            })->orderBy("crm_notes.created_at","DESC")
            ->get();

//        $data['is_in_crm_confirm'] = Interview::join('applicants', 'interviews.applicant_id', '=', 'applicants.id')
//            ->select('applicants.*', 'interviews.schedule_date', 'interviews.schedule_time')
//            ->where(array('is_crm_request_confirm' => 'yes', 'is_crm_interview_attended' => 'pending', "interviews.status" => "active"))->get();

        $data['is_in_crm_confirm'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('interviews', function ($join) {
                $join->on('applicants.id', '=', 'interviews.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'interviews.sale_id');
            });
        $data['is_in_crm_confirm'] = $data['is_in_crm_confirm']->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_added_date', 'crm_added_time',
                'applicants.*',
                'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website',
                'interviews.schedule_time', 'interviews.schedule_date')
            ->where([
                "applicants.status" => "active",
                "crm_notes.moved_tab_to" => "request_confirm",
                "interviews.status" => "active",
                "history.status" => "active"
            ])->whereIn('history.sub_stage', ['crm_request_confirm', 'crm_interview_save'])
            ->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="request_confirm" and sales_id=sales.id and applicants.id=applicant_id'));
            })->orderBy("crm_notes.created_at","DESC")
            ->get();

        $data['attended'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            });
        $data['attended'] = $data['attended']->select('crm_notes.details', 'crm_added_date', 'crm_added_time',
                'applicants.*',
                'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'interview_attended',
                'history.status' => 'active'
            ])->whereIn('history.sub_stage', ['crm_interview_attended', 'crm_prestart_save'])
            ->orderBy("crm_notes.created_at","DESC")
            ->get();

        $data['not_attended'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            });
        $data['not_attended'] = $data['not_attended']->select('crm_notes.details', 'crm_added_date', 'crm_added_time',
                'applicants.*',
                'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                "applicants.status" => "active",
                "crm_notes.moved_tab_to" => "interview_not_attended",
                "history.sub_stage" => "crm_interview_not_attended", "history.status" => "active"
            ])->orderBy("crm_notes.created_at","DESC")
            ->get();

        $data['start_date'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            });
        $data['start_date'] = $data['start_date']->select('crm_notes.details', 'crm_added_date', 'crm_added_time',
                'applicants.*',
                'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'start_date',
                'history.status' => 'active'
            ])->whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_save', 'crm_start_date_back'])
            ->orderBy("crm_notes.created_at","DESC")
            ->get();

        $data['start_date_hold'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            });
        $data['start_date_hold'] = $data['start_date_hold']->select('crm_notes.details', 'crm_added_date', 'crm_added_time',
                'applicants.*',
                'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'start_date_hold',
                'history.status' => 'active'
            ])->whereIn('history.sub_stage', ['crm_start_date_hold', 'crm_start_date_hold_save'])
            ->orderBy("crm_notes.created_at","DESC")
            ->get();

        $data['invoices'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            });
        $data['invoices'] = $data['invoices']->select('crm_notes.details', 'crm_added_date', 'crm_added_time',
                'applicants.*',
                'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'invoice',
                'history.status' => 'active'
            ])->whereIn('history.sub_stage', ['crm_invoice', 'crm_final_save'])
            ->orderBy("crm_notes.created_at","DESC")
            ->get();

        $data['dispute'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            });
        $data['dispute'] = $data['dispute']->select('crm_notes.details', 'crm_added_date', 'crm_added_time',
                'applicants.*',
                'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'dispute',
                'history.sub_stage' => 'crm_dispute', 'history.status' => 'active'
            ])
            ->orderBy("crm_notes.created_at","DESC")
            ->get();

        $data['paid'] = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            });
        $data['paid'] = $data['paid']->select('crm_notes.details', 'crm_added_date', 'crm_added_time',
                'applicants.*',
                'sales.id as sale_id', 'sales.job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'paid',
                'history.sub_stage' => 'crm_paid', 'history.status' => 'active'
            ])
            ->orderBy("crm_notes.created_at","DESC")
            ->get();
*/
		$user_info = Applicant_message::join('applicants', 'applicant_messages.applicant_id', '=', 'applicants.id')
        ->select('applicant_messages.*','applicants.applicant_name','applicants.applicant_postcode',DB::raw('count(applicant_messages.applicant_id) as total'))
        ->where('applicant_messages.is_read','0')
        ->where('applicant_messages.status','incoming')
		->orderBy('applicant_messages.created_at', 'desc')
        ->groupBy('applicant_messages.applicant_id')
        ->get();
        return view('administrator.crm.index', compact('user_info'));
//        return view('administrator.crm.index', $data);
    }

    public function crmSentCv()
    {
        $auth_user = Auth::user();
        $applicant_with_cvs = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
            ->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('quality_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('quality_notes.sale_id', '=', 'history.sale_id');
            })->select('quality_notes.details','quality_notes.quality_added_date','quality_notes.quality_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode','applicants.applicant_phone','applicants.applicant_homePhone',  'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
                'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
                'units.contact_email', 'units.website','applicants.is_no_job')
            ->where([
                "applicants.status" => "active",
                
                "quality_notes.status" => "active",
                "history.status" => "active"
            ])->whereIn("quality_notes.moved_tab_to" ,["cleared"])->whereIn("history.sub_stage", ["quality_cleared", "crm_save"])
            ->orderBy("quality_notes.created_at","desc");

        $crm_cv_sent_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->whereIn("crm_notes.moved_tab_to", ["cv_sent", "cv_sent_saved"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

        return datatables()->of($applicant_with_cvs)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? ucwords($sent_by->name) : "";
            })
			
            ->addColumn("applicant_job_title", function($applicant){
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
            	return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function($applicant){
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_cv_sent_save_note) {
                $content = '';
                if(!empty($crm_cv_sent_save_note)) {
                    foreach ($crm_cv_sent_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time;
                            $content .= '</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_request','CRM_Sent-CVs_save','CRM_Sent-CVs_reject'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option sms_action_sent_cv"
                                 data-controls-modal="#clear_cv' . $applicant->id . '-' . $applicant->sale_id . '" 
								 data-backdrop="static" data-keyboard="false" data-toggle="modal"
								 data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                 data-applicantNameJs="' . $applicant->applicant_name . '" 
                                 data-applicantIdJs="' . $applicant->id . '"
								 data-applicantunitjs="' . $applicant->unit_name . '" 
                                 data-target="#clear_cv' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i>Reject/Request</a>';
					if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_revert'])) {
					 $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#revert_in_qulaity'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#revert_in_qulaity'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Revert In Quality</a>';
					}	
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
				if(!empty($applicant_msgs))
                {
                 if ($applicant_msgs['is_read'] == 0) {
                    $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                    $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                 }
                }
				}
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_request','CRM_Sent-CVs_save','CRM_Sent-CVs_reject'])) {
                    /*** Revert In Quality ***/
                $content .= '<div id="revert_in_qulaity' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Revert In Quality Notes</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $quality_url = '/revert-cv-quality/';
                $content .= '<form action="' . $quality_url . $applicant->id . '" method="POST" id="revert_quality' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                $content .= csrf_field();
                $content .= '<div class="modal-body">';
                $content .= '<div id="revert_quality_cv' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                $content .= '<div class="form-group row">';
                $content .= '<label class="col-form-label col-sm-3">Details</label>';
                $content .= '<div class="col-sm-9">';
				
                $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
				$content .=  '<input type="hidden" name="cv_modal_name" class="model_name" value="sent_cv">';
                $content .= '<textarea name="details" id="revert_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
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
                $content .= '</div>';
					/*** Move CV Modal */
                    $content .= '<div id="clear_cv' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">CRM Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="sent_cv_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
					
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
					$content .=  '<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">';
                    $content .=  '<input type="hidden" name="applicant_name_chat" id="applicant_name_chat">';
                    $content .=  '<input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_reject')) {
                        $content .= '<div class="form-group row">';
                        $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                        $content .= '<div class="col-sm-9">';
                        $content .= '<select name="reject_reason" class="form-control crm_select_reason">';
                        $content .= '<option >Select Reason</option>';
                        $content .= '<option value="position_filled">Position Filled</option>';
                        $content .= '<option value="agency">Sent By Another Agency</option>';
						$content .= '<option value="manager">Rejected By Manager</option>';
						$content .= '<option value="no_response">No Response</option>';
                        $content .= '</select>';
                        $content .= '</div>';
                        $content .= '</div>';
                    }
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_reject')) {
                        $content .= '<button type="submit" name="cv_sent_reject" value="cv_sent_reject" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple reject_btn sent_cv_submit" style="display: none">Reject</button>';
                    }
					   if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_request')) {
                        $content .= '<button type="submit" name="cv_sent_request" value="cv_sent_request" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple cv_sent_request sent_cv_submit">Request</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_save')) {
                        $content .= '<button type="submit" name="cv_sent_save" value="cv_sent_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple sent_cv_submit">Save</button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Move CV Modal */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
               foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0 mt-2">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                /*** Reject CV Modal
                $content .= '<div id="reject_cv'.$applicant_with_cv->id.'-'.$applicant_with_cv->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Reject CV Notes</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<form action="'.route('updateToRejectedCV',['id'=>$applicant_with_cv->id , 'viewString'=>'applicantWithSentCv']).'" method="GET" class="form-horizontal">';
                $content .= csrf_field();
                $content .= '<div class="modal-body">';
                $content .= '<div class="form-group row">';
                $content .= '<label class="col-form-label col-sm-3">Details</label>';
                $content .= '<div class="col-sm-9">';
                $content .= '<input type="hidden" name="job_hidden_id" value="'.$applicant_with_cv->sale_id.'">';
                $content .= '<input type="hidden" name="applicant_hidden_id" value="'.$applicant_with_cv->id.'">';
                $content .= '<textarea name="details" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
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
                 * /Reject CV Modal */

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
               $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
               $row_class = '';
               if(!empty($applicant_msg))
               {
                if ($applicant_msg['is_read'] == 0) {
                    $row_class .= 'blink';
                }
               }
               
                return $row_class;
				}
            })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }
	
	public function crmOpenCv()
    {
        $auth_user = Auth::user();
        $applicant_with_cvs = Applicant::join('cv_notes', 'applicants.id', '=', 'cv_notes.applicant_id')
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
                'applicants.id',
                'applicants.applicant_name',
                'applicants.applicant_postcode',
                'applicants.applicant_job_title',
                'applicants.job_title_prof',
                'applicants.job_category',
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
                'revert_stages.user_id as revert_user_id',
                'revert_stages.notes as revert_notes',
                'revert_stages.stage as revert_stage',
                'revert_stages.updated_at as revert_stage_updated_at'
            )
            ->where([
                'applicants.status' => 'active',
                'cv_notes.status' => 'active',
                'history.sub_stage' => 'quality_cvs_hold',
                'history.status' => 'active'
            ])
            ->orderByRaw('CONCAT(revert_stages.updated_at) DESC');

        return datatables()->of($applicant_with_cvs)
			->editColumn('updated_at', function ($applicant) {
                return Carbon::parse($applicant->revert_stage_updated_at)->toFormattedDateString();
            })
            ->editColumn('updated_time', function ($applicant) {
                return Carbon::parse($applicant->revert_stage_updated_at)->toFormattedTimeString();
            })
            ->addColumn("name", function ($applicant) {
                $sent_by = DB::table('users')
                    ->where('id', $applicant->revert_user_id)->first();
                return $sent_by ? ucwords($sent_by->name) : "";
            })
            ->addColumn("applicant_job_title", function ($applicant) {
                $job_title_desc = '';
                if ($applicant->job_title_prof != null) {
                    $job_prof_res = Specialist_job_titles::select('id', 'specialist_prof')->where('id', $applicant->job_title_prof)->first();
                    $job_title_desc = $applicant->applicant_job_title . ' (' . $job_prof_res->specialist_prof . ')';
                } else {

                    $job_title_desc = $applicant->applicant_job_title;
                }
                return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function ($applicant) {
                return '<a href="' . route('15kmrange', $applicant->id) . '" class="btn-link legitRipple">' . strtoupper($applicant->applicant_postcode) . '</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details' . $applicant->id . '-' . $applicant->sale_id . '"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details' . $applicant->id . '-' . $applicant->sale_id . '">Details</a>';
                $content .= '<div id="job_details' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"' . $applicant->applicant_name . '\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name . ' / ' . $applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category . ', ' . $applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>' . $applicant->job_title . '</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">' . $applicant->postcode . '</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">' . $applicant->job_type . '</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">' . $applicant->timing . '</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">' . $applicant->salary . '</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">' . $applicant->experience . '</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">' . $applicant->qualification . '</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">' . $applicant->benefits . '</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">' . $applicant->posted_date . '</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
                $phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;

                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));

                $mergedArray = [];

                $maxLength = max(count($emails), count($phones), count($landlines), count($names));

                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';

                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
                $applicant_msgs = Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_request', 'CRM_Sent-CVs_save', 'CRM_Sent-CVs_reject'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option sms_action_sent_cv"
                                 data-controls-modal="#clear_cv' . $applicant->id . '-' . $applicant->sale_id . '" 
								 data-backdrop="static" data-keyboard="false" data-toggle="modal"
								 data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                 data-applicantNameJs="' . $applicant->applicant_name . '" 
                                 data-applicantIdJs="' . $applicant->id . '"
								 data-applicantunitjs="' . $applicant->unit_name . '" 
                                 data-target="#clear_cv' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i>Reject/Request</a>';
                    if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_revert'])) {
                        $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#revert_in_qulaity' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#revert_in_qulaity' . $applicant->id . '-' . $applicant->sale_id . '">';
                        $content .= '<i class="icon-file-confirm"></i>Revert In Quality</a>';
                    }
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details' . $applicant->id . '-' . $applicant->sale_id . '">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if (!empty($applicant_msgs)) {
                        if ($applicant_msgs['is_read'] == 0) {
                            $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                            $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                        }
                    }
                }
                $content .= '<a href="' . route('viewAllCrmNotes', ["applicant_id" => $applicant->id, "sale_id" => $applicant->sale_id]) . '" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_request', 'CRM_Sent-CVs_save', 'CRM_Sent-CVs_reject'])) {
                    /*** Revert In Quality ***/
                    $content .= '<div id="revert_in_qulaity' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Revert In Quality Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $quality_url = '/revert-cv-quality/';
                    $content .= '<form action="' . $quality_url . $applicant->id . '" method="POST" id="revert_quality' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="revert_quality_cv' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';

                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .=  '<input type="hidden" name="cv_modal_name" class="model_name" value="sent_cv">';
                    $content .= '<textarea name="details" id="revert_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
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
                    $content .= '</div>';
                    /*** Move CV Modal */
                    $content .= '<div id="clear_cv' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">CRM Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="sent_cv_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="request_from" value="crm_open_cv">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .=  '<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">';
                    $content .=  '<input type="hidden" name="applicant_name_chat" id="applicant_name_chat">';
                    $content .=  '<input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_reject')) {
                        $content .= '<div class="form-group row">';
                        $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                        $content .= '<div class="col-sm-9">';
                        $content .= '<select name="reject_reason" class="form-control crm_select_reason">';
                        $content .= '<option >Select Reason</option>';
                        $content .= '<option value="position_filled">Position Filled</option>';
                        $content .= '<option value="agency">Sent By Another Agency</option>';
                        $content .= '<option value="manager">Rejected By Manager</option>';
                        $content .= '<option value="no_response">No Response</option>';
                        $content .= '</select>';
                        $content .= '</div>';
                        $content .= '</div>';
                    }
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_reject')) {
                        $content .= '<button type="submit" name="cv_sent_reject" value="cv_sent_reject" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple reject_btn sent_cv_submit" style="display: none">Reject</button>';
                    }
                    if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_request')) {
                        $content .= '<button type="submit" name="cv_sent_request" value="cv_sent_request" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple cv_sent_request sent_cv_submit">Request</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_save')) {
                        $content .= '<button type="submit" name="cv_sent_save" value="cv_sent_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple sent_cv_submit">Save</button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Move CV Modal */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                foreach ($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0 mt-2">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - ' . $index . '</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>' . $value['name'] . '</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>' . $value['email'] . '</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>' . $value['phone'] . '</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>' . $value['landline'] . '</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    $applicant_msg = Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                    $row_class = '';
                    if (!empty($applicant_msg)) {
                        if ($applicant_msg['is_read'] == 0) {
                            $row_class .= 'blink';
                        }
                    }

                    return $row_class;
                }
            })
            ->rawColumns(['updated_at', 'updated_time', 'name', 'applicant_job_title', 'applicant_postcode', 'job_details', 'action'])
            ->make(true);
    }
	
	public function crmSentCvNurse()
    {
        $auth_user = Auth::user();
        $applicant_with_cvs = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
            ->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('quality_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('quality_notes.sale_id', '=', 'history.sale_id');
            })->select('quality_notes.details','quality_notes.quality_added_date','quality_notes.quality_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode','applicants.applicant_phone','applicants.applicant_homePhone', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
                'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
                'units.contact_email', 'units.website','applicants.is_no_job')
            ->where([
                "applicants.status" => "active",
                "quality_notes.status" => "active",
                "history.status" => "active"
            ])->whereIn("quality_notes.moved_tab_to" ,["cleared"])
			->whereIn("history.sub_stage", ["quality_cleared", "crm_save"])
			->whereIn("applicants.job_category" ,["nurse","chef"])
            ->orderBy("quality_notes.created_at","desc");

        $crm_cv_sent_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            //->join('history', function ($join) {
                //$join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                //$join->on('crm_notes.sales_id', '=', 'history.sale_id');
            //})
            //->where(["applicants.job_category" => "nurse"])
			->whereIn("applicants.job_category" ,["nurse","chef"])
            //->whereIn("history.sub_stage", ["quality_cleared", "crm_save"])
            ->whereIn("crm_notes.moved_tab_to", ["cv_sent", "cv_sent_saved"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

        return datatables()->of($applicant_with_cvs)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function($applicant){
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
            	return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function($applicant){
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details_nurse'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details_nurse'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details_nurse'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_cv_sent_save_note) {
                $content = '';
                if(!empty($crm_cv_sent_save_note)) {
                    foreach ($crm_cv_sent_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time;
                            $content .= '</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_request','CRM_Sent-CVs_save','CRM_Sent-CVs_reject'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option sms_action_sent_cv"
                                           data-controls-modal="#clear_cv_nurse' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                           data-applicantNameJs="' . $applicant->applicant_name . '" 
                                           data-applicantIdJs="' . $applicant->id . '"
										   data-applicantunitjs="' . $applicant->unit_name . '" 
                                           data-target="#clear_cv_nurse' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i>Reject/Request</a>';
if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_revert'])) {
                    $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#revert_in_qulaity_nurse'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#revert_in_qulaity_nurse'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Revert In Quality</a>';
}
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details_sent_nurse'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details_sent_nurse'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
				if(!empty($applicant_msgs))
                                {
                                 if ($applicant_msgs['is_read'] == 0) {
                                    $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                    $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                 }
                                }
				}
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
               
                if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_request','CRM_Sent-CVs_save','CRM_Sent-CVs_reject'])) {
                     /*** Revert In Quality ***/
                $content .= '<div id="revert_in_qulaity_nurse' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Revert In Quality Notes</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $quality_url = '/revert-cv-quality/';
                $content .= '<form action="' . $quality_url . $applicant->id . '" method="POST" id="revert_quality' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                $content .= csrf_field();
                $content .= '<div class="modal-body">';
                $content .= '<div id="revert_quality_cv' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                $content .= '<div class="form-group row">';
                $content .= '<label class="col-form-label col-sm-3">Details</label>';
                $content .= '<div class="col-sm-9">';
			  
                $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                $content .= '<textarea name="details" id="revert_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
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
                $content .= '</div>';
                    /*** Move CV Modal */
                    $content .= '<div id="clear_cv_nurse' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">CRM Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="sent_cv_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
					
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .=  '<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">';
                    $content .=  '<input type="hidden" name="applicant_name_chat" id="applicant_name_chat">';
                    $content .=  '<input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_reject')) {
                        $content .= '<div class="form-group row">';
                        $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                        $content .= '<div class="col-sm-9">';
                        $content .= '<select name="reject_reason" class="form-control crm_select_reason">';
                        $content .= '<option >Select Reason</option>';
                        $content .= '<option value="position_filled">Position Filled</option>';
                        $content .= '<option value="agency">Sent By Another Agency</option>';
                        $content .= '<option value="manager">Rejected By Manager</option>';
                        $content .= '<option value="no_response">No Response</option>';
                        $content .= '</select>';
                        $content .= '</div>';
                        $content .= '</div>';
                    }
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_reject')) {
                        $content .= '<button type="submit" name="cv_sent_reject" value="cv_sent_reject" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple reject_btn sent_cv_submit" style="display: none">Reject</button>';
                    }
                    if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_request')) {
                        $content .= '<button type="submit" name="cv_sent_request" value="cv_sent_request" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple cv_sent_request sent_cv_submit">Request</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_save')) {
                        $content .= '<button type="submit" name="cv_sent_save" value="cv_sent_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple sent_cv_submit">Save</button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Move CV Modal */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details_sent_nurse'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
               foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                /*** Reject CV Modal
                $content .= '<div id="reject_cv'.$applicant_with_cv->id.'-'.$applicant_with_cv->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Reject CV Notes</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<form action="'.route('updateToRejectedCV',['id'=>$applicant_with_cv->id , 'viewString'=>'applicantWithSentCv']).'" method="GET" class="form-horizontal">';
                $content .= csrf_field();
                $content .= '<div class="modal-body">';
                $content .= '<div class="form-group row">';
                $content .= '<label class="col-form-label col-sm-3">Details</label>';
                $content .= '<div class="col-sm-9">';
                $content .= '<input type="hidden" name="job_hidden_id" value="'.$applicant_with_cv->sale_id.'">';
                $content .= '<input type="hidden" name="applicant_hidden_id" value="'.$applicant_with_cv->id.'">';
                $content .= '<textarea name="details" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
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
                 * /Reject CV Modal */

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
					$applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
					$row_class = '';
					if(!empty($applicant_msg))
					{
						 if ($applicant_msg['is_read'] == 0) {
							 $row_class .= 'blink';
						 }
					}

					 return $row_class;
				}
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }
	
    public function crmSentCvNonNurse()
    {
        $auth_user = Auth::user();
        $applicant_with_cvs = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
            ->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('quality_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('quality_notes.sale_id', '=', 'history.sale_id');
            })->select('quality_notes.details','quality_notes.quality_added_date','quality_notes.quality_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode','applicants.applicant_phone','applicants.applicant_homePhone', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
                'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
                'units.contact_email', 'units.website','applicants.is_no_job')
            ->where([
                "applicants.status" => "active",
                "quality_notes.status" => "active",
                "history.status" => "active"
            ])
			->whereIn("applicants.job_category", ["non-nurse",'nursery'])
			->whereIn("quality_notes.moved_tab_to" ,["cleared"])
            ->whereIn("history.sub_stage", ["quality_cleared", "crm_save"])
            ->orderBy("quality_notes.created_at","desc");

        $crm_cv_sent_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            //->join('history', function ($join) {
                //$join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                //$join->on('crm_notes.sales_id', '=', 'history.sale_id');
            //})
            ->whereIn("applicants.job_category" , ["non-nurse",'nursery'])
            //->whereIn("history.sub_stage", ["quality_cleared", "crm_save"])
            ->whereIn("crm_notes.moved_tab_to", ["cv_sent", "cv_sent_saved"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

        return datatables()->of($applicant_with_cvs)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function($applicant){
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
            return strtoupper($job_title_desc);
                // return strtoupper($applicant->applicant_job_title);
            })
            ->addColumn("applicant_postcode", function($applicant){
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details_non_nurse'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details_non_nurse'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details_non_nurse'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_cv_sent_save_note) {
                $content = '';
                if(!empty($crm_cv_sent_save_note)) {
                    foreach ($crm_cv_sent_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time;
                            $content .= '</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_request','CRM_Sent-CVs_save','CRM_Sent-CVs_reject'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option sms_action_sent_cv"
                                           data-controls-modal="#clear_cv_non_nurse' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                           data-applicantNameJs="' . $applicant->applicant_name . '" 
                                           data-applicantIdJs="' . $applicant->id . '"
										   data-applicantunitjs="' . $applicant->unit_name . '"
										   data-applicantunitjs="' . $applicant->unit_name . '" 
                                           data-target="#clear_cv_non_nurse' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i>Reject/Request</a>';
if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_revert'])) {
                    $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#revert_in_qulaity_non_nurse'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#revert_in_qulaity_non_nurse'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Revert In Quality</a>';
                }
				}
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details_sent_non_nurse'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details_sent_non_nurse'.$applicant->id.'-'.$applicant->sale_id.'">';
				 
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
				if(!empty($applicant_msgs))
                                {
                                 if ($applicant_msgs['is_read'] == 0) {
                                    $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                    $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                 }
                                }
					}
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
               
                if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_request','CRM_Sent-CVs_save','CRM_Sent-CVs_reject'])) {
                     /*** Revert In Quality ***/
                $content .= '<div id="revert_in_qulaity_non_nurse' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Revert In Quality Notes</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $quality_url = '/revert-cv-quality/';
                $content .= '<form action="' . $quality_url . $applicant->id . '" method="POST" id="revert_quality' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                $content .= csrf_field();
                $content .= '<div class="modal-body">';
                $content .= '<div id="revert_quality_cv' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                $content .= '<div class="form-group row">';
                $content .= '<label class="col-form-label col-sm-3">Details</label>';
                $content .= '<div class="col-sm-9">';
			  
                $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                $content .= '<textarea name="details" id="revert_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
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
                $content .= '</div>';
                    /*** Move CV Modal */
                    $content .= '<div id="clear_cv_non_nurse' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">CRM Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="sent_cv_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
			     		
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .=  '<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">';
                    $content .=  '<input type="hidden" name="applicant_name_chat" id="applicant_name_chat">';
                    $content .=  '<input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_reject')) {
                        $content .= '<div class="form-group row">';
                        $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                        $content .= '<div class="col-sm-9">';
                        $content .= '<select name="reject_reason" class="form-control crm_select_reason">';
                        $content .= '<option >Select Reason</option>';
                        $content .= '<option value="position_filled">Position Filled</option>';
                        $content .= '<option value="agency">Sent By Another Agency</option>';
                        $content .= '<option value="manager">Rejected By Manager</option>';
                        $content .= '<option value="no_response">No Response</option>';
                        $content .= '</select>';
                        $content .= '</div>';
                        $content .= '</div>';
                    }
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_reject')) {
                        $content .= '<button type="submit" name="cv_sent_reject" value="cv_sent_reject" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple reject_btn sent_cv_submit" style="display: none">Reject</button>';
                    }
                    if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_request')) {
                        $content .= '<button type="submit" name="cv_sent_request" value="cv_sent_request" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple cv_sent_request sent_cv_submit">Request</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_save')) {
                        $content .= '<button type="submit" name="cv_sent_save" value="cv_sent_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple sent_cv_submit">Save</button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Move CV Modal */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details_sent_non_nurse'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                /*** Reject CV Modal
                $content .= '<div id="reject_cv'.$applicant_with_cv->id.'-'.$applicant_with_cv->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Reject CV Notes</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<form action="'.route('updateToRejectedCV',['id'=>$applicant_with_cv->id , 'viewString'=>'applicantWithSentCv']).'" method="GET" class="form-horizontal">';
                $content .= csrf_field();
                $content .= '<div class="modal-body">';
                $content .= '<div class="form-group row">';
                $content .= '<label class="col-form-label col-sm-3">Details</label>';
                $content .= '<div class="col-sm-9">';
                $content .= '<input type="hidden" name="job_hidden_id" value="'.$applicant_with_cv->sale_id.'">';
                $content .= '<input type="hidden" name="applicant_hidden_id" value="'.$applicant_with_cv->id.'">';
                $content .= '<textarea name="details" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
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
                 * /Reject CV Modal */

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
					}
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }
	
    public function sentCvAction(Request $request)
    {
        date_default_timezone_set('Europe/London');

        /*** Sent CVs tab */
        $cv_sent_reject_value = $request->Input('cv_sent_reject');
        $cv_sent_request_value = $request->Input('cv_sent_request');
        $cv_sent_save_value = $request->Input('cv_sent_save');
		$cv_sent_no_job_value = $request->Input('applicant_no_job_hidden_id');


        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
		
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';
		if (!empty($cv_sent_no_job_value)&&$cv_sent_no_job_value=="no_job"){
			if (!empty($cv_sent_save_value) && ($cv_sent_save_value == 'cv_sent_save')) {
				if(isset($request->request_from) && $request->request_from == 'crm_open_cv'){
                    Cv_note::where([
                        "applicant_id" => $applicant_id,
                        "sale_id" => $sale_id
                    ])->update(["status" => "disable"]);

                    $cv_note = new Cv_note();
                    $cv_note->sale_id = $sale_id;
                    $cv_note->user_id = $auth_user;
                    $cv_note->applicant_id = $applicant_id;
                    $audit_data['detail_note'] = $cv_note->details = $details;
                    $audit_data['added_date'] = $cv_note->send_added_date = date("jS F Y");
                    $audit_data['added_time'] = $cv_note->send_added_time = date("h:i A");
                    $cv_note->save();

                }else{
					$audit_data['action'] = "Save";
					$crm_notes = new Crm_note();
					$crm_notes->applicant_id = $applicant_id;
					$crm_notes->user_id = $auth_user;
					$crm_notes->sales_id = $sale_id;
					$crm_notes->details = $details;
					$audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
					$audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
					$crm_notes->moved_tab_to = "cv_sent_no_job";
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
						$history->user_id = $auth_user;
						$history->sale_id = $sale_id;
						$history->stage = 'crm';
						$history->sub_stage = 'quality_cleared_no_job';
						$history->history_added_date = date("jS F Y");
						$history->history_added_time = date("h:i A");
						$history->save();

						$last_inserted_history = $history->id;
						if($last_inserted_history > 0){
							$history_uid = md5($last_inserted_history);
							History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
							$html = '<div class="alert alert-success border-0 alert-dismissible">
										<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
										<span class="font-weight-semibold">'.$request->input('module').'</span> Note saved Successfully
									</div>';
							echo $html;
						}

					} else {
						echo $html;
					}
				}
				
                $reject_reason = $request->Input('reject_reason');
                if($reject_reason == 'position_filled' || $reject_reason == 'agency' || $reject_reason == 'manager'){
					if($reject_reason == 'position_filled')
					{
						$sale = Sale::find($sale_id);
						$audit = new ActionObserver();
						$audit->changeSaleStatus($sale, ['status' => 'disable']);
						$sale->update(['status' => 'disable', 'is_on_hold' => '0']);
					}else
					{
						$sale = Sale::find($sale_id);
						$sale->status = $sale->status;
						$sale->updated_at = Carbon::now();
						$sale->update();
					}

					$sale_note = new Sales_notes();
					$sale_note->sale_id = $sale_id;
					$sale_note->user_id = $auth_user;
					$sale_note->sales_note_added_date = date("jS F Y");
					$sale_note->sales_note_added_time = date("h:i A");
					$sale_note->sale_note = $details;
					$sale_note->save();

					$last_inserted_sale_note_id = $sale_note->id;
					if ($last_inserted_sale_note_id > 0) {
						$sale_note_uid = md5($last_inserted_sale_note_id);
						Sales_notes::where('id', $last_inserted_sale_note_id)
							->update(['sales_notes_uid' => $sale_note_uid]);
					}
				}
			} elseif (!empty($cv_sent_request_value) && ($cv_sent_request_value == 'cv_sent_request')) {

				$audit_data['action'] = "Request";
				Applicant::where("id", $applicant_id)->update(['is_in_crm_request' => 'yes', 'is_interview_confirm' => 'no']);
				$crm_notes = new Crm_note();
				$crm_notes->applicant_id = $applicant_id;
				$crm_notes->user_id = $auth_user;
				$crm_notes->sales_id = $sale_id;
				$crm_notes->details = $details;
				$audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
				$audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
				$crm_notes->moved_tab_to = "cv_sent_no_job_request";
				$crm_notes->save();
				
				Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id, "moved_tab_to" => "cleared_no_job"])
					->update(["status" => "disable"]);

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
					$history->user_id = $auth_user;
					$history->sale_id = $sale_id;
					$history->stage = 'crm';
					$history->sub_stage = 'crm_no_job_request';
					$history->history_added_date = date("jS F Y");
					$history->history_added_time = date("h:i A");
					$history->save();
					
					if (isset($request->request_from) && $request->request_from == 'crm_open_cv') {
                        Quality_notes::where([
                            "applicant_id" => $applicant_id,
                            "sale_id" => $sale_id,
                            "moved_tab_to" => "cv_hold",
							'status' => 'active'
                        ])
                            ->update(["status" => "disable"]);
                    }
					
					$reject_reason = $request->Input('reject_reason');

					if($reject_reason == 'position_filled' || $reject_reason == 'agency' || $reject_reason == 'manager'){
						if($reject_reason == 'position_filled')
						{
							$sale = Sale::find($sale_id);
							$audit = new ActionObserver();
							$audit->changeSaleStatus($sale, ['status' => 'disable']);
							$sale->update(['status' => 'disable', 'is_on_hold' => '0']);
						}else
						{
							$sale = Sale::find($sale_id);
							$sale->status = $sale->status;
							$sale->updated_at = Carbon::now();
							$sale->update();
						}

						$sale_note = new Sales_notes();
						$sale_note->sale_id = $sale_id;
						$sale_note->user_id = $auth_user;
						$sale_note->sales_note_added_date = date("jS F Y");
						$sale_note->sales_note_added_time = date("h:i A");
						$sale_note->sale_note = $details;
						$sale_note->save();

						$last_inserted_sale_note_id = $sale_note->id;
						if ($last_inserted_sale_note_id > 0) {
							$sale_note_uid = md5($last_inserted_sale_note_id);
							Sales_notes::where('id', $last_inserted_sale_note_id)
								->update(['sales_notes_uid' => $sale_note_uid]);
						}
					}

					$last_inserted_history = $history->id;
					if($last_inserted_history > 0){
						$history_uid = md5($last_inserted_history);
						History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
						// $applicant_numbers='07597019065';
						//$applicant_number = $applicant_phone;

						$html = '<div class="alert alert-success border-0 alert-dismissible">
									<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
									<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Request successfully
								</div>';
						echo $html;
					}
				} else {
					echo $html;
				}
			} elseif (!empty($cv_sent_reject_value) && ($cv_sent_reject_value == 'cv_sent_reject')) {
				$audit_data['action'] = "Reject";
				$audit_data['reject_reason'] = $reject_reason = $request->Input('reject_reason');
				Applicant::where("id", $applicant_id)->update(['is_in_crm_reject' => 'yes',
					'is_interview_confirm' => 'no']);
				
				$crm_notes = new Crm_note();
				$crm_notes->applicant_id = $applicant_id;
				$crm_notes->user_id = $auth_user;
				$crm_notes->sales_id = $sale_id;
				$crm_notes->details = $details;
				$crm_notes->status = 'disable';
				$audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
				$audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
				$crm_notes->moved_tab_to = "cv_sent_reject_no_job";
				$crm_notes->save();

				$last_inserted_note = $crm_notes->id;
				if ($last_inserted_note > 0) {
					$crm_note_uid = md5($last_inserted_note);
					Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
					
					$crm_rejected_cv = new Crm_rejected_cv();
					$crm_rejected_cv->applicant_id = $applicant_id;
					$crm_rejected_cv->sale_id = $sale_id;
					$crm_rejected_cv->user_id = $auth_user;
					$crm_rejected_cv->crm_note_id = $last_inserted_note;
					$crm_rejected_cv->reason = $reject_reason;
					$crm_rejected_cv->crm_rejected_cv_note = $details;
					$crm_rejected_cv->crm_rejected_cv_date = date("jS F Y");
					$crm_rejected_cv->crm_rejected_cv_time = date("h:i A");
					$crm_rejected_cv->save();
					
					$last_crm_reject_id = $crm_rejected_cv->id;
					$crm_last_insert_id = md5($last_crm_reject_id);
					Crm_rejected_cv::where("id", $last_crm_reject_id)->update(['crm_rejected_cv_uid' => $crm_last_insert_id]);
					Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
					Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
					History::where([
						"applicant_id" => $applicant_id,
						"sale_id" => $sale_id
					])->update(["status" => "disable"]);
					
					$history = new History();
					$history->applicant_id = $applicant_id;
					$history->user_id = $auth_user;
					$history->sale_id = $sale_id;
					$history->stage = 'crm';
					$history->sub_stage = 'crm_no_job_reject';
					$history->history_added_date = date("jS F Y");
					$history->history_added_time = date("h:i A");
					$history->save();
					
					if (isset($request->request_from) && $request->request_from == 'crm_open_cv') {
                        Quality_notes::where([
                            "applicant_id" => $applicant_id,
                            "sale_id" => $sale_id,
                            "moved_tab_to" => "cv_hold",
							'status' => 'active'
                        ])
                            ->update(["status" => "disable"]);
                    }
					
					$reject_reason = $request->Input('reject_reason');

                    if($reject_reason == 'position_filled' || $reject_reason == 'agency' || $reject_reason == 'manager'){
						if($reject_reason == 'position_filled')
						{
							$sale = Sale::find($sale_id);
							$audit = new ActionObserver();
							$audit->changeSaleStatus($sale, ['status' => 'disable']);
							$sale->update(['status' => 'disable', 'is_on_hold' => '0']);
						}else
						{
							$sale = Sale::find($sale_id);
							$sale->status = $sale->status;
							$sale->updated_at = Carbon::now();
							$sale->update();
						}

						$sale_note = new Sales_notes();
						$sale_note->sale_id = $sale_id;
						$sale_note->user_id = $auth_user;
						$sale_note->sales_note_added_date = date("jS F Y");
						$sale_note->sales_note_added_time = date("h:i A");
						$sale_note->sale_note = $details;
						$sale_note->save();

						$last_inserted_sale_note_id = $sale_note->id;
						if ($last_inserted_sale_note_id > 0) {
							$sale_note_uid = md5($last_inserted_sale_note_id);
							Sales_notes::where('id', $last_inserted_sale_note_id)
								->update(['sales_notes_uid' => $sale_note_uid]);
						}
					}
					
					$last_inserted_history = $history->id;
					if($last_inserted_history > 0){
						$history_uid = md5($last_inserted_history);
						History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
						$html = '<div class="alert alert-success border-0 alert-dismissible">
									<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
									<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV rejected successfully
								</div>';
						echo $html;
					}
				} else {
					echo $html;
				}
			}
		}else{
			if (!empty($cv_sent_save_value) && ($cv_sent_save_value == 'cv_sent_save')) {
				if(isset($request->request_from) && $request->request_from == 'crm_open_cv'){
                    Cv_note::where([
                        "applicant_id" => $applicant_id,
                        "sale_id" => $sale_id
                    ])->update(["status" => "disable"]);

                    $cv_note = new Cv_note();
                    $cv_note->sale_id = $sale_id;
                    $cv_note->user_id = $auth_user;
                    $cv_note->applicant_id = $applicant_id;
                    $audit_data['detail_note'] = $cv_note->details = $details;
                    $audit_data['added_date'] = $cv_note->send_added_date = date("jS F Y");
                    $audit_data['added_time'] = $cv_note->send_added_time = date("h:i A");
                    $cv_note->save();
                    
                }else{
					$audit_data['action'] = "Save";

					$crm_notes = new Crm_note();
					$crm_notes->applicant_id = $applicant_id;
					$crm_notes->user_id = $auth_user;
					$crm_notes->sales_id = $sale_id;
					$crm_notes->details = $details;
					$audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
					$audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
					$crm_notes->moved_tab_to = "cv_sent_saved";
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
						$history->user_id = $auth_user;
						$history->sale_id = $sale_id;
						$history->stage = 'crm';
						$history->sub_stage = 'crm_save';
						$history->history_added_date = date("jS F Y");
						$history->history_added_time = date("h:i A");
						$history->save();

						$last_inserted_history = $history->id;
						if($last_inserted_history > 0){
							$history_uid = md5($last_inserted_history);
							History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
							$html = '<div class="alert alert-success border-0 alert-dismissible">
									<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
									<span class="font-weight-semibold">'.$request->input('module').'</span> Note saved Successfully
								</div>';
							echo $html;
						}

					} else {
						echo $html;
					}
				}
				
				$reject_reason = $request->Input('reject_reason');

				if($reject_reason == 'position_filled' || $reject_reason == 'agency' || $reject_reason == 'manager'){
					if($reject_reason == 'position_filled')
					{
						$sale = Sale::find($sale_id);
						$audit = new ActionObserver();
						$audit->changeSaleStatus($sale, ['status' => 'disable']);
						$sale->update(['status' => 'disable', 'is_on_hold' => '0']);
					}else
					{
						$sale = Sale::find($sale_id);
						$sale->status = $sale->status;
						$sale->updated_at = Carbon::now();
						$sale->update();
					}

					$sale_note = new Sales_notes();
					$sale_note->sale_id = $sale_id;
					$sale_note->user_id = $auth_user;
					$sale_note->sales_note_added_date = date("jS F Y");
					$sale_note->sales_note_added_time = date("h:i A");
					$sale_note->sale_note = $details;
					$sale_note->save();

					$last_inserted_sale_note_id = $sale_note->id;
					if ($last_inserted_sale_note_id > 0) {
						$sale_note_uid = md5($last_inserted_sale_note_id);
						Sales_notes::where('id', $last_inserted_sale_note_id)
							->update(['sales_notes_uid' => $sale_note_uid]);
					}
				}
        } elseif (!empty($cv_sent_request_value) && ($cv_sent_request_value == 'cv_sent_request')) {
            $audit_data['action'] = "Request";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_request' => 'yes', 'is_interview_confirm' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "cv_sent_request";
            $crm_notes->save();
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id, "moved_tab_to" => "cleared"])->update(["status" => "disable"]);

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Sent CVs');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_request';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
				
				 if (isset($request->request_from) && $request->request_from == 'crm_open_cv') {
                    Quality_notes::where([
                        "applicant_id" => $applicant_id,
                        "sale_id" => $sale_id,
                        "moved_tab_to" => "cv_hold",
						'status' => 'active'
                    ])
					->update(["status" => "disable"]);
					 
					$sale = Sale::find($sale_id);
					$sale->updated_at = Carbon::now();
					$sale->update();
                }
				
				$reject_reason = $request->Input('reject_reason');

				if($reject_reason == 'position_filled' || $reject_reason == 'agency' || $reject_reason == 'manager'){
					if($reject_reason == 'position_filled')
					{
						$sale = Sale::find($sale_id);
						$audit = new ActionObserver();
						$audit->changeSaleStatus($sale, ['status' => 'disable']);
						$sale->update(['status' => 'disable', 'is_on_hold' => '0']);
					}else
					{
						$sale = Sale::find($sale_id);
						$sale->status = $sale->status;
						$sale->updated_at = Carbon::now();
						$sale->update();
					}

					$sale_note = new Sales_notes();
					$sale_note->sale_id = $sale_id;
					$sale_note->user_id = $auth_user;
					$sale_note->sales_note_added_date = date("jS F Y");
					$sale_note->sales_note_added_time = date("h:i A");
					$sale_note->sale_note = $details;
					$sale_note->save();

					$last_inserted_sale_note_id = $sale_note->id;
					if ($last_inserted_sale_note_id > 0) {
						$sale_note_uid = md5($last_inserted_sale_note_id);
						Sales_notes::where('id', $last_inserted_sale_note_id)
							->update(['sales_notes_uid' => $sale_note_uid]);
					}
				}
				
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Request successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif (!empty($cv_sent_reject_value) && ($cv_sent_reject_value == 'cv_sent_reject')) {
            $audit_data['action'] = "Reject";
            $audit_data['reject_reason'] = $reject_reason = $request->Input('reject_reason');
            Applicant::where("id", $applicant_id)->update(['is_in_crm_reject' => 'yes',
                'is_interview_confirm' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->status = 'disable';
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "cv_sent_reject";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Sent CVs');
             */

            $last_inserted_note = $crm_notes->id;
            if ($last_inserted_note > 0) {
                $crm_note_uid = md5($last_inserted_note);
                Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
                $crm_rejected_cv = new Crm_rejected_cv();
                $crm_rejected_cv->applicant_id = $applicant_id;
                $crm_rejected_cv->sale_id = $sale_id;
                $crm_rejected_cv->user_id = $auth_user;
                $crm_rejected_cv->crm_note_id = $last_inserted_note;
                $crm_rejected_cv->reason = $reject_reason;
                $crm_rejected_cv->crm_rejected_cv_note = $details;
                $crm_rejected_cv->crm_rejected_cv_date = date("jS F Y");
                $crm_rejected_cv->crm_rejected_cv_time = date("h:i A");
                $crm_rejected_cv->save();
				
                $last_crm_reject_id = $crm_rejected_cv->id;
                $crm_last_insert_id = md5($last_crm_reject_id);
                Crm_rejected_cv::where("id", $last_crm_reject_id)->update(['crm_rejected_cv_uid' => $crm_last_insert_id]);
                Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
                Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
                History::where([
                    "applicant_id" => $applicant_id,
                    "sale_id" => $sale_id
                ])->update(["status" => "disable"]);
				
                $history = new History();
                $history->applicant_id = $applicant_id;
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_reject';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();				
				
				 if (isset($request->request_from) && $request->request_from == 'crm_open_cv') {
                        Quality_notes::where([
                            "applicant_id" => $applicant_id,
                            "sale_id" => $sale_id,
                            "moved_tab_to" => "cv_hold",
							'status' => 'active'
                        ])
                            ->update(["status" => "disable"]);
                    }

				
				$reject_reason = $request->Input('reject_reason');

				if($reject_reason == 'position_filled' || $reject_reason == 'agency' || $reject_reason == 'manager'){
					if($reject_reason == 'position_filled')
					{
						$sale = Sale::find($sale_id);
						$audit = new ActionObserver();
						$audit->changeSaleStatus($sale, ['status' => 'disable']);
						$sale->update(['status' => 'disable', 'is_on_hold' => '0']);
					}else
					{
						$sale = Sale::find($sale_id);
						$sale->status = $sale->status;
						$sale->updated_at = Carbon::now();
						$sale->update();
					}

					$sale_note = new Sales_notes();
					$sale_note->sale_id = $sale_id;
					$sale_note->user_id = $auth_user;
					$sale_note->sales_note_added_date = date("jS F Y");
					$sale_note->sales_note_added_time = date("h:i A");
					$sale_note->sale_note = $details;
					$sale_note->save();

					$last_inserted_sale_note_id = $sale_note->id;
					if ($last_inserted_sale_note_id > 0) {
						$sale_note_uid = md5($last_inserted_sale_note_id);
						Sales_notes::where('id', $last_inserted_sale_note_id)
							->update(['sales_notes_uid' => $sale_note_uid]);
					}
				}
				
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV rejected successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        }
		}
    }

	
    public function sentCvNoJobAction(Request $request)
    {
        date_default_timezone_set('Europe/London');

        $cv_sent_no_job_value = $request->Input('applicant_no_job_hidden_id');
        $cv_sent_reject_value = $request->Input('cv_sent_reject');
        $cv_sent_request_value = $request->Input('cv_sent_request');
        $cv_sent_save_value = $request->Input('cv_sent_save');
        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
		
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}
		
        $unit_name = Sale::join('units', 'sales.head_office_unit', '=', 'units.id')
        ->where('sales.id','=', $sale_id)
        ->select('units.unit_name')->first();
        $unit_name =  $unit_name->unit_name;
        $app_res = Applicant::select('applicant_phone','applicant_name')->find($applicant_id);
        $applicant_phone = $app_res->applicant_phone;
        $applicant_name = $app_res->applicant_name;

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';

    if (!empty($cv_sent_save_value) && ($cv_sent_save_value == 'cv_sent_save')) {
        $audit_data['action'] = "Save";
        $crm_notes = new Crm_note();
        $crm_notes->applicant_id = $applicant_id;
        $crm_notes->user_id = $auth_user;
        $crm_notes->sales_id = $sale_id;
        $crm_notes->details = $details;
        $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
        $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
        $crm_notes->moved_tab_to = "cv_sent_no_job";
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
            $history->user_id = $auth_user;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'quality_cleared_no_job';
            $history->history_added_date = date("jS F Y");
            $history->history_added_time = date("h:i A");
            $history->save();
			
			$reject_reason = $request->Input('reject_reason');

			if($reject_reason == 'position_filled' || $reject_reason == 'agency' || $reject_reason == 'manager'){
				if($reject_reason == 'position_filled')
				{
					$sale = Sale::find($sale_id);
					$audit = new ActionObserver();
					$audit->changeSaleStatus($sale, ['status' => 'disable']);
					$sale->update(['status' => 'disable', 'is_on_hold' => '0']);
				}else
				{
					$sale = Sale::find($sale_id);
					$sale->status = $sale->status;
					$sale->updated_at = Carbon::now();
					$sale->update();
				}

				$sale_note = new Sales_notes();
				$sale_note->sale_id = $sale_id;
				$sale_note->user_id = $auth_user;
				$sale_note->sales_note_added_date = date("jS F Y");
				$sale_note->sales_note_added_time = date("h:i A");
				$sale_note->sale_note = $details;
				$sale_note->save();

				$last_inserted_sale_note_id = $sale_note->id;
				if ($last_inserted_sale_note_id > 0) {
					$sale_note_uid = md5($last_inserted_sale_note_id);
					Sales_notes::where('id', $last_inserted_sale_note_id)
						->update(['sales_notes_uid' => $sale_note_uid]);
				}
			}
			
            $last_inserted_history = $history->id;
            if($last_inserted_history > 0){
                $history_uid = md5($last_inserted_history);
                History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Note saved Successfully
						</div>';
                echo $html;
            }

        } else {
            echo $html;
        }
    } elseif (!empty($cv_sent_request_value) && ($cv_sent_request_value == 'cv_sent_request')) {

        $audit_data['action'] = "Request";
        Applicant::where("id", $applicant_id)->update(['is_in_crm_request' => 'yes', 'is_interview_confirm' => 'no']);
        $crm_notes = new Crm_note();
        $crm_notes->applicant_id = $applicant_id;
        $crm_notes->user_id = $auth_user;
        $crm_notes->sales_id = $sale_id;
        $crm_notes->details = $details;
        $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
        $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
        $crm_notes->moved_tab_to = "cv_sent_no_job_request";
        $crm_notes->save();
        Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id, "moved_tab_to" => "cleared_no_job"])->update(["status" => "disable"]);

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
            $history->user_id = $auth_user;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_no_job_request';
            $history->history_added_date = date("jS F Y");
            $history->history_added_time = date("h:i A");
            $history->save();
			
			$reject_reason = $request->Input('reject_reason');

			if($reject_reason == 'position_filled' || $reject_reason == 'agency' || $reject_reason == 'manager'){
				if($reject_reason == 'position_filled')
				{
					$sale = Sale::find($sale_id);
					$audit = new ActionObserver();
					$audit->changeSaleStatus($sale, ['status' => 'disable']);
					$sale->update(['status' => 'disable', 'is_on_hold' => '0']);
				}else
				{
					$sale = Sale::find($sale_id);
					$sale->status = $sale->status;
					$sale->updated_at = Carbon::now();
					$sale->update();
				}

				$sale_note = new Sales_notes();
				$sale_note->sale_id = $sale_id;
				$sale_note->user_id = $auth_user;
				$sale_note->sales_note_added_date = date("jS F Y");
				$sale_note->sales_note_added_time = date("h:i A");
				$sale_note->sale_note = $details;
				$sale_note->save();

				$last_inserted_sale_note_id = $sale_note->id;
				if ($last_inserted_sale_note_id > 0) {
					$sale_note_uid = md5($last_inserted_sale_note_id);
					Sales_notes::where('id', $last_inserted_sale_note_id)
						->update(['sales_notes_uid' => $sale_note_uid]);
				}
			}
			
            $last_inserted_history = $history->id;
            if($last_inserted_history > 0){
                $history_uid = md5($last_inserted_history);
                History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                // $applicant_numbers='07597019065';
                $applicant_number = $applicant_phone;
                $applicant_message = 'Hi '.$applicant_name.' Congratulations! '.$unit_name.' would like to invite you to their office for an in-person interview. Are you available next Tues 1-3pm or Fri 10am-12pm? Please do advise a suitable time. You can either reply to this message or contact us on the information given below Thank you for choosing Kingbury to represent you. Best regards, CRM TEAM T: 01494211220 E: crm@kingsburypersonnel.com';
//                      $query_string = 'http://milkyway.tranzcript.com:1008/sendsms?username=admin&password=admin&phonenumber='.$applicant_numbers.'&message='.$applicant_message.'&port=2&report=JSON&timeout=0';
//
//                    $sms_res = $this->sendQualityClearSms($query_string);
                $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Request successfully
						</div>';
                echo $html;
            }
        } else {
            echo $html;
        }
    } elseif (!empty($cv_sent_reject_value) && ($cv_sent_reject_value == 'cv_sent_reject')) {

        $audit_data['action'] = "Reject";
        $audit_data['reject_reason'] = $reject_reason = $request->Input('reject_reason');
        Applicant::where("id", $applicant_id)->update(['is_in_crm_reject' => 'yes',
            'is_interview_confirm' => 'no']);
        $crm_notes = new Crm_note();
        $crm_notes->applicant_id = $applicant_id;
        $crm_notes->user_id = $auth_user;
        $crm_notes->sales_id = $sale_id;
        $crm_notes->details = $details;
        $crm_notes->status = 'disable';
        $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
        $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
        $crm_notes->moved_tab_to = "cv_sent_reject_no_job";
        $crm_notes->save();


        $last_inserted_note = $crm_notes->id;
        if ($last_inserted_note > 0) {
            $crm_note_uid = md5($last_inserted_note);
            Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
			
            $crm_rejected_cv = new Crm_rejected_cv();
            $crm_rejected_cv->applicant_id = $applicant_id;
            $crm_rejected_cv->sale_id = $sale_id;
            $crm_rejected_cv->user_id = $auth_user;
            $crm_rejected_cv->crm_note_id = $last_inserted_note;
            $crm_rejected_cv->reason = $reject_reason;
            $crm_rejected_cv->crm_rejected_cv_note = $details;
            $crm_rejected_cv->crm_rejected_cv_date = date("jS F Y");
            $crm_rejected_cv->crm_rejected_cv_time = date("h:i A");
            $crm_rejected_cv->save();
			
            $last_crm_reject_id = $crm_rejected_cv->id;
            $crm_last_insert_id = md5($last_crm_reject_id);
            Crm_rejected_cv::where("id", $last_crm_reject_id)->update(['crm_rejected_cv_uid' => $crm_last_insert_id]);
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            History::where([
                "applicant_id" => $applicant_id,
                "sale_id" => $sale_id
            ])->update(["status" => "disable"]);
			
            $history = new History();
            $history->applicant_id = $applicant_id;
            $history->user_id = $auth_user;
            $history->sale_id = $sale_id;
            $history->stage = 'crm';
            $history->sub_stage = 'crm_no_job_reject';
            $history->history_added_date = date("jS F Y");
            $history->history_added_time = date("h:i A");
            $history->save();
			
			$reject_reason = $request->Input('reject_reason');

			if($reject_reason == 'position_filled' || $reject_reason == 'agency' || $reject_reason == 'manager'){
				if($reject_reason == 'position_filled')
				{
					$sale = Sale::find($sale_id);
					$audit = new ActionObserver();
					$audit->changeSaleStatus($sale, ['status' => 'disable']);
					$sale->update(['status' => 'disable', 'is_on_hold' => '0']);
				}else
				{
					$sale = Sale::find($sale_id);
					$sale->status = $sale->status;
					$sale->updated_at = Carbon::now();
					$sale->update();
				}

				$sale_note = new Sales_notes();
				$sale_note->sale_id = $sale_id;
				$sale_note->user_id = $auth_user;
				$sale_note->sales_note_added_date = date("jS F Y");
				$sale_note->sales_note_added_time = date("h:i A");
				$sale_note->sale_note = $details;
				$sale_note->save();

				$last_inserted_sale_note_id = $sale_note->id;
				if ($last_inserted_sale_note_id > 0) {
					$sale_note_uid = md5($last_inserted_sale_note_id);
					Sales_notes::where('id', $last_inserted_sale_note_id)
						->update(['sales_notes_uid' => $sale_note_uid]);
				}
			}
			
            $last_inserted_history = $history->id;
            if($last_inserted_history > 0){
                $history_uid = md5($last_inserted_history);
                History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV rejected successfully
						</div>';
                echo $html;
            }
        } else {
            echo $html;
        }

    }
    }

    public function crmRejectCv()
    {
        $auth_user = Auth::user();
        $applicant_with_rejected_cvs = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
                'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date', 'sales.send_cv_limit',
                'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
                'units.contact_email', 'units.website','applicants.is_no_job')
            ->where([
                "applicants.status" => "active",
                //"crm_notes.moved_tab_to" => "cv_sent_reject",
                //"history.sub_stage" => "crm_reject",
				"history.status" => "active"
            ])->whereIn("crm_notes.moved_tab_to",['cv_sent_reject','cv_sent_reject_no_job'])
			->whereIn('history.sub_stage',['crm_reject','crm_no_job_reject'])
			->whereIn('crm_notes.id', function($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('crm_notes')
                    ->whereIn('moved_tab_to', ['cv_sent_reject', 'cv_sent_reject_no_job'])
                    ->where('sales_id', '=', DB::raw('sales.id'))
                    ->where('applicants.id', '=', DB::raw('applicant_id'));
            })->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($applicant_with_rejected_cvs)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function($applicant){
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
            return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function($applicant){
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) {
                $content = '<p><b>DATE: </b>'.$applicant->crm_added_date.'<b> TIME: </b>'.$applicant->crm_added_time.'</p><p><b>NOTE: </b>'.$applicant->details.'</p>';

                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                //if ($auth_user->hasPermissionTo('CRM_Rejected-CV_revert-sent-cv')) {
				if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_revert'])) {
                    $content .= '<a href="#" class="dropdown-item"
                                       data-controls-modal="#revert_sent_cvs' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                       data-keyboard="false" data-toggle="modal"
                                       data-target="#revert_sent_cvs' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i> Revert </a>';
                }
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                if(!empty($applicant_msgs))
                                {
                                 if ($applicant_msgs['is_read'] == 0) {
                                    $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                    $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                 }
                                }
                            }
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                if ($auth_user->hasPermissionTo('CRM_Rejected-CV_revert-sent-cv')) {
                    $sent_cv_count = Cv_note::where(['sale_id' => $applicant->sale_id, 'status' => 'active'])->count();
                    /*** Revert To Sent CVs Modal */
                    $content .= '<div id="revert_sent_cvs' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Rejected CV Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="revert_sent_cv_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="revert_sent_cv_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Sent CV</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<label class="col-form-label font-weight-semibold">'.$sent_cv_count.' out of '.$applicant->send_cv_limit.'</label>';
                    $content .= '</div>';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
					  if ($applicant->is_no_job==1){
                        $content .= '<input type="hidden" name="applicant_no_job_hidden_id" value="no_job">';

                    }
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .= '<textarea name="details" id="revert_sent_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    $content .= '<button type="submit" name="rejected_cv_revert_sent_cvs" value="rejected_cv_revert_sent_cvs" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple rejected_cv_submit">Sent CV</button>';
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Revert To Sent CVs Modal */
                }

                return $content;
            })
			  ->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])
					->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }

    public function revertSentCvAction(Request $request)
    {
        date_default_timezone_set('Europe/London');

        /*** Rejected CV tab */
        $rejected_cv_revert_sent_cvs_value = $request->Input('rejected_cv_revert_sent_cvs');
        $is_no_job = $request->Input('applicant_no_job_hidden_id');

        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
				
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';

		 if (!empty($is_no_job) && $is_no_job == "no_job") {
            $sale = Sale::find($sale_id);
            if ($sale) {
                $sent_cv_count = Cv_note::where(['sale_id' => $sale_id, 'status' => 'active'])->count();
//                if ($sent_cv_count < $sale->send_cv_limit) {
                    if (!empty($rejected_cv_revert_sent_cvs_value) && ($rejected_cv_revert_sent_cvs_value == 'rejected_cv_revert_sent_cvs')) {
                        $crm_note_id = @Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id, 'moved_tab_to' => 'cv_sent_reject'])->select('id')->latest()->first()->id;
                        Crm_rejected_cv::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id, 'crm_note_id' => $crm_note_id])->update(["status" => "disable"]);
                        Cv_note::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id])->update(["status" => "active"]);
                        Quality_notes::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id])->update(["status" => "active"]);
                        Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->whereIn("moved_tab_to", ["cv_sent", "cv_sent_saved","cv_sent_no_job"])->update(["status" => "disable"]);
                        $crm_notes = new Crm_note();
                        $crm_notes->applicant_id = $applicant_id;
                        $crm_notes->user_id = $auth_user;
                        $crm_notes->sales_id = $sale_id;
                        $crm_notes->details = $details;
                        $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                        $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
//                        $crm_notes->moved_tab_to = "cv_sent"; //old status
                        $crm_notes->moved_tab_to = "cv_sent_no_job";
                        $crm_notes->save();

                        /*** activity log
                         * $this->action_observer->action($audit_data, 'CRM > Rejected CV revert to Sent CVs');
                         */

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
                            $history->user_id = $auth_user;
                            $history->sale_id = $sale_id;
                            $history->stage = 'crm';
//                            $history->sub_stage = 'crm_save';// old status
                            $history->sub_stage = 'quality_cleared_no_job';
                            $history->history_added_date = date("jS F Y");
                            $history->history_added_time = date("h:i A");
                            $history->save();
                            $last_inserted_history = $history->id;
                            if($last_inserted_history > 0){
                                $history_uid = md5($last_inserted_history);
                                History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                                $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV reverted Sent CVs Successfully
						</div>';
                                echo $html;
                            }
                        } else {
                            echo $html;
                        }
                    }
//
            } else {
                echo
                '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold"> WHOOPS!</span> Sale not found!!
                </div>';
            }
        }
        else {
        $sale = Sale::find($sale_id);
        if ($sale) {
            $sent_cv_count = Cv_note::where(['sale_id' => $sale_id, 'status' => 'active'])->count();
            if ($sent_cv_count < $sale->send_cv_limit) {
                if (!empty($rejected_cv_revert_sent_cvs_value) && ($rejected_cv_revert_sent_cvs_value == 'rejected_cv_revert_sent_cvs')) {
                    $crm_note_id = @Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id, 'moved_tab_to' => 'cv_sent_reject'])->select('id')->latest()->first()->id;
                    Crm_rejected_cv::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id, 'crm_note_id' => $crm_note_id])->update(["status" => "disable"]);
                    Cv_note::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id])->update(["status" => "active"]);
                    Quality_notes::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id])->update(["status" => "active"]);
                    Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->whereIn("moved_tab_to", ["cv_sent", "cv_sent_saved"])->update(["status" => "disable"]);
                    $crm_notes = new Crm_note();
                    $crm_notes->applicant_id = $applicant_id;
                    $crm_notes->user_id = $auth_user;
                    $crm_notes->sales_id = $sale_id;
                    $crm_notes->details = $details;
                    $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                    $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
                    $crm_notes->moved_tab_to = "cv_sent";
                    $crm_notes->save();

                    /*** activity log
                     * $this->action_observer->action($audit_data, 'CRM > Rejected CV revert to Sent CVs');
                     */

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
                        $history->user_id = $auth_user;
                        $history->sale_id = $sale_id;
                        $history->stage = 'crm';
                        $history->sub_stage = 'crm_save';
                        $history->history_added_date = date("jS F Y");
                        $history->history_added_time = date("h:i A");
                        $history->save();
                        $last_inserted_history = $history->id;
                        if($last_inserted_history > 0){
                            $history_uid = md5($last_inserted_history);
                            History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                            $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV reverted Sent CVs Successfully
						</div>';
                            echo $html;
                        }
                    } else {
                        echo $html;
                    }
                }
            } else {
                echo
                '<div class="alert alert-danger border-0 alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        <span class="font-weight-semibold"> WHOOPS!</span> You cannot perform this action. Send CV Limit for this Sale has reached maximum!!
                    </div>';
            }
        } else {
            echo
            '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold"> WHOOPS!</span> Sale not found!!
                </div>';
        }
		}
    }

    public function crmRequest()
    {
        $auth_user = Auth::user();
        $crm_request_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->whereIn("history.sub_stage", ["crm_request", "crm_request_save"])
            ->whereIn("crm_notes.moved_tab_to", ["cv_sent_request", "request_save"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

        $applicant_cvs_in_request = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->leftJoin('interviews', function ($join) {
                $join->on('applicants.id', '=', 'interviews.applicant_id');
                $join->on('sales.id', '=', 'interviews.sale_id');
                $join->where('interviews.status', '=', 'active');
            })->join('history', function ($join) {
            $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
            $join->on('crm_notes.sales_id', '=', 'history.sale_id');
        })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
            'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone','sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
            'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
            'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
            'units.contact_email', 'units.website',
            'interviews.schedule_time', 'interviews.schedule_date', 'interviews.status as interview_status')
            ->where([
                "applicants.status" => "active",
                "crm_notes.moved_tab_to" => "cv_sent_request", "crm_notes.status" => "active",
                "history.status" => "active"
            ])->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="cv_sent_request" and sales_id=sales.id and applicants.id=applicant_id'));
            })->whereIn("history.sub_stage", ["crm_request", "crm_request_save"])
            ->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($applicant_cvs_in_request)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function($applicant){
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
                return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function($applicant){
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_request_save_note) {
                $content = '';
                if(!empty($crm_request_save_note)) {
                    foreach ($crm_request_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time;
                            $content .= '</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasPermissionTo('CRM_Request_schedule-interview')) {
                    if ($applicant->schedule_time && $applicant->schedule_date && $applicant->interview_status == 'active') {
                        $content .= '<a href="#" class="disabled dropdown-item"><i class="icon-file-confirm"></i>Schedule Interview</a>';
                    } else {
                        $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#schedule_interview' . $applicant->id . '-' . $applicant->sale_id . '"
                                           data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                           data-target="#schedule_interview' . $applicant->id . '-' . $applicant->sale_id . '">';
                        $content .= '<i class="icon-file-confirm"></i>Schedule Interview</a>';
                    }
                }
                if ($auth_user->hasAnyPermission(['CRM_Request_reject','CRM_Request_confirm','CRM_Request_save'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option"
                                 data-controls-modal="#confirm_cv' . $applicant->id . '-' . $applicant->sale_id . '" 
								 data-backdrop="static" data-keyboard="false" data-toggle="modal"
								 data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                 data-applicantNameJs="' . $applicant->applicant_name . '" 
                                 data-applicantIdJs="' . $applicant->id . '"
                                 data-target="#confirm_cv' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i>Move To Confirmation</a>';
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                if(!empty($applicant_msgs))
                                {
                                 if ($applicant_msgs['is_read'] == 0) {
                                    $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                    $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                 }
                                }
                            }
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasPermissionTo('CRM_Request_schedule-interview')) {
                    /*** Schedule Interview Modal */
                    $content .= '<div id="schedule_interview' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-sm">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h3 class="modal-title">' . $applicant->applicant_name . '</h3>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="schedule_interview_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<form action="' . route('scheduleInterview') . '" method="POST" id="schedule_interview_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<input type="hidden" name="applicant_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="sale_id" value="' . $applicant->sale_id . '">';
                    $content .= '<div class="mb-4">';
                    $content .= '<div class="input-group">';
                    $content .= '<span class="input-group-prepend">';
                    $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                    $content .= '</span>';
                    $content .= '<input type="text" class="form-control pickadate-year" name="schedule_date" id="schedule_date' . $applicant->id . '-' . $applicant->sale_id . '" placeholder="Select Schedule Date">';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="mb-4">';
                    $content .= '<div class="input-group">';
                    $content .= '<span class="input-group-prepend">';
                    $content .= '<span class="input-group-text"><i class="icon-watch2"></i></span>';
                    $content .= '</span>';
//                $content .= '<input type="text" class="form-control time_pickerrrr" id="anytime-time'.$applicant->id.'-'.$applicant->sale_id.'" name="schedule_time" placeholder="Select Schedule Time e.g., 00:00">';
                    $content .= '<input type="text" class="form-control" id="schedule_time' . $applicant->id . '-' . $applicant->sale_id . '" name="schedule_time" placeholder="Type Schedule Time e.g., 10:00">';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<button type="submit" class="btn bg-teal legitRipple btn-block schedule_interview_submit" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '">Schedule</button>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Schedule Interview Modal */
                }

                if ($auth_user->hasAnyPermission(['CRM_Request_reject','CRM_Request_confirm','CRM_Request_save'])) {
                    /*** Confirmation CV Modal */
                    $content .= '<div id="confirm_cv' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Confirm CV Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="request_cv_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="request_cv_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
					$content .= '<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">';
                    $content .= '<input type="hidden" name="applicant_name_chat" id="applicant_name_chat">';
                    $content .= '<input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">';
                    $content .= '<textarea name="details" id="request_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    if ($auth_user->hasPermissionTo('CRM_Request_reject')) {
                        $content .= '<button type="submit" name="request_reject" value="request_reject" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple request_cv_submit"> Reject </button>';
                    }
					if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }

                    if ($auth_user->hasPermissionTo('CRM_Request_confirm')) {
                        $disabled = "disabled";
                        if ($applicant->schedule_time && $applicant->schedule_date && $applicant->interview_status == 'active') {
                            $disabled = "";
                        }
                        $content .= '<button type="submit" name="request_to_confirm" value="request_to_confirm" ' . $disabled . ' data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple request_cv_submit"> Confirm </button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Request_save')) {
                        $content .= '<button type="submit" name="request_to_save" value="request_to_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple request_cv_submit"> Save </button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Confirmation CV Modal */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
               foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                return $content;
            })
			 ->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }
	
	public function crmRequestNurse()
    {
        $auth_user = Auth::user();
        $crm_request_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })
            //->where("applicants.job_category","nurse")
			->whereIn("applicants.job_category" ,["nurse"])
            ->whereIn("history.sub_stage", ["crm_request", "crm_request_save"])
            ->whereIn("crm_notes.moved_tab_to", ["cv_sent_request", "request_save"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();
        $applicant_cvs_in_request = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->leftJoin('interviews', function ($join) {
                $join->on('applicants.id', '=', 'interviews.applicant_id');
                $join->on('sales.id', '=', 'interviews.sale_id');
                $join->where('interviews.status', '=', 'active');
            })->join('history', function ($join) {
            $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
            $join->on('crm_notes.sales_id', '=', 'history.sale_id');
        })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
            'applicants.id', 'applicants.applicant_name', 'applicants.applicant_email', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
            'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
            'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
            'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
            'units.contact_email', 'units.website',
            'interviews.schedule_time', 'interviews.schedule_date', 'interviews.status as interview_status')
            ->where([
                "applicants.status" => "active",
                "crm_notes.moved_tab_to" => "cv_sent_request", "crm_notes.status" => "active",
                "history.status" => "active"
            ])->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="cv_sent_request" and sales_id=sales.id and applicants.id=applicant_id'));
            })->whereIn("history.sub_stage", ["crm_request", "crm_request_save"])
			->whereIn("applicants.job_category" ,["nurse"])
            ->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($applicant_cvs_in_request)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function($applicant){
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
                return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function($applicant){
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_request_save_note) {
                $content = '';
                if(!empty($crm_request_save_note)) {
                    foreach ($crm_request_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time;
                            $content .= '</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				
				  $title = 'request_configuration_email';
                $newPhrase = '';
                $newSubject = '';
                $applicant_email = '';
                $request_configuration_email = EmailTemplate::where('title',$title)->first();

                if($request_configuration_email && $request_configuration_email->is_active == 1){
                    // Loop through each attribute of the model
                    foreach ($request_configuration_email->getAttributes() as $key => $value) {
                        if (is_string($value)) {
                            // Convert to UTF-8 if not properly encoded
                            $request_configuration_email->{$key} = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                        }
                    }

                    $applicant_name = $applicant->applicant_name;
                    $salary = $applicant->salary;
                    $job_postcode = $applicant->postcode;
                    $unit_name = $applicant->unit_name;
                    $distance = $job_postcode;
                    $location = $job_postcode;

                    $job_title_desc='';
                    if($applicant->job_title_prof != null)
                    {
                        $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $applicant->job_title_prof)->first();
                        $job_title_desc = strtoupper($applicant->job_title.' ('.$job_prof_res->specialist_prof.')');
                    }
                    else
                    {
                        $job_title_desc = strtoupper($applicant->job_title);
                    }

                    //replace variables into body
                    $data = $request_configuration_email->template;
                    $replace = [$applicant_name, $job_title_desc, $unit_name, $job_postcode, $salary, $distance, $location];
                    $prev_val = ['(applicant_name)', '(job_type)', '(unit_name)', '(postcode)', '(salary)', '(distance)', '(location)' ];
                    $newPhrase = str_replace($prev_val, $replace, $data);

                    // Strip HTML tags from the email body
                    // $newPhrase = strip_tags($newPhrase);
                    
                    //replace variables into subjects
                    $subject = $request_configuration_email->subject;
                    $subjectReplace = [$job_title_desc];
                    $prev_sub = ['(job_type)'];
                    $newSubject = str_replace($prev_sub, $subjectReplace, $subject);

                    $applicant_email = htmlspecialchars($applicant->applicant_email, ENT_QUOTES, 'UTF-8');
                    $newSubject = htmlspecialchars($newSubject, ENT_QUOTES, 'UTF-8');
                    $newPhrase = htmlspecialchars($newPhrase, ENT_QUOTES, 'UTF-8');

                }

                $emailIcon = '<i class="fas fa-envelope text-secondary" title="Not Attached"></i>';
                $sentEmail = SentEmail::where('applicant_id',$applicant->id)->where('sale_id',$applicant->sale_id)->latest()->first();
                if($sentEmail && $sentEmail->status == '1'){
                    $emailIcon = '<i class="fas fa-envelope text-success" title="Sent"></i>';
                }elseif($sentEmail && $sentEmail->status == '2'){
                    $emailIcon = '<i class="fas fa-envelope text-danger" title="Failed"></i>';
                }elseif($sentEmail && $sentEmail->status == '0'){
                    $emailIcon = '<i class="fas fa-envelope text-secondary" title="Pending"></i>';
                }
				
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
				$content .= $emailIcon.'<br>';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasPermissionTo('CRM_Request_schedule-interview')) {
                    if ($applicant->schedule_time && $applicant->schedule_date && $applicant->interview_status == 'active') {
                        $content .= '<a href="#" class="disabled dropdown-item"><i class="icon-file-confirm"></i>Schedule Interview</a>';
                    } else {
                        if ($sentEmail && $sentEmail->status == '1') {
							// If the email is successfully sent (status = '1'), skip the email modal
							$content .= '<a href="#" class="dropdown-item" data-controls-modal="#schedule_interview' . $applicant->id . '-' . $applicant->sale_id . '" data-toggle="modal" data-target="#schedule_interview' . $applicant->id . '-' . $applicant->sale_id . '">';
							$content .= '<i class="icon-file-confirm"></i>Schedule Interview</a>';
						} else {
							// If no email has been sent yet, open the email modal first
							$content .= '<a href="#" class="dropdown-item" data-controls-modal="#send_applicant_email_request_modal' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#send_applicant_email_request_modal' . $applicant->id . '-' . $applicant->sale_id . '">';
							$content .= '<i class="icon-file-confirm"></i>Schedule Interview</a>';
						}
                    }
                }
                if ($auth_user->hasAnyPermission(['CRM_Request_reject','CRM_Request_confirm','CRM_Request_save'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option"
                                           data-controls-modal="#confirm_cv' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                           data-applicantNameJs="' . $applicant->applicant_name . '" 
                                           data-applicantIdJs="' . $applicant->id . '"
                                           data-target="#confirm_cv' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i>Move To Confirmation</a>';
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                                    {
                                     if ($applicant_msgs['is_read'] == 0) {
                                        $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                        $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                     }
                                    }
                                }
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasPermissionTo('CRM_Request_schedule-interview')) {
                    /*** Schedule Interview Modal */
                    $content .= '<div id="schedule_interview' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-sm">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h3 class="modal-title">' . $applicant->applicant_name . '</h3>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="schedule_interview_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<form action="' . route('scheduleInterview') . '" method="POST" id="schedule_interview_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<input type="hidden" name="applicant_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="sale_id" value="' . $applicant->sale_id . '">';
                    $content .= '<div class="mb-4">';
                    $content .= '<div class="input-group">';
                    $content .= '<span class="input-group-prepend">';
                    $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                    $content .= '</span>';
                    $content .= '<input type="text" class="form-control pickadate-year" name="schedule_date" id="schedule_date' . $applicant->id . '-' . $applicant->sale_id . '" placeholder="Select Schedule Date">';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="mb-4">';
                    $content .= '<div class="input-group">';
                    $content .= '<span class="input-group-prepend">';
                    $content .= '<span class="input-group-text"><i class="icon-watch2"></i></span>';
                    $content .= '</span>';
//                $content .= '<input type="text" class="form-control time_pickerrrr" id="anytime-time'.$applicant->id.'-'.$applicant->sale_id.'" name="schedule_time" placeholder="Select Schedule Time e.g., 00:00">';
                    $content .= '<input type="text" class="form-control" id="schedule_time' . $applicant->id . '-' . $applicant->sale_id . '" name="schedule_time" placeholder="Type Schedule Time e.g., 10:00">';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<button type="submit" class="btn bg-teal legitRipple btn-block schedule_interview_submit" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '">Schedule</button>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Schedule Interview Modal */
					
					/*** Reject Modal for Email Input (Child Modal) */
					$content .= '<div id="send_applicant_email_request_modal' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="applicantEmailModalLabel" aria-hidden="true" style="z-index:99999">';
					$content .= '  <div class="modal-dialog" role="document">';
					$content .= '    <div class="modal-content">';
					$content .= '      <div class="modal-header">';
					$content .= '        <h5 class="modal-title" id="applicantEmailModalLabel">Send email to applicant</h5>';
					$content .= '        <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
					$content .= '          <span aria-hidden="true">&times;</span>';
					$content .= '        </button>';
					$content .= '      </div>';
					$content .= '      <div class="modal-body">';
					$content .= '        <!-- Hidden Inputs to Store Applicant and Sale IDs -->';
					$content .= '        <input type="hidden" name="applicant_id" id="requested_applicant_id_' . $applicant->id . '_' . $applicant->sale_id . '" value="' . $applicant->id . '">';
					$content .= '        <input type="hidden" name="sale_id" id="requested_sale_id_' . $applicant->id . '_' . $applicant->sale_id . '" value="' . $applicant->sale_id . '">';
					$content .= '        <!-- Email Subject and Body Inputs -->';
					$content .= '        <div class="form-group">';
					$content .= '          <input type="email" name="email_address" id="email_address_requested_' . $applicant->id . '_' . $applicant->sale_id . '" class="form-control mt-2" placeholder="Enter Email Address" value="' . $applicant_email . '" required>';
					$content .= '        </div>';
					$content .= '        <div class="form-group">';
					$content .= '          <input type="text" name="email_subject" id="email_subject_requested_' . $applicant->id . '_' . $applicant->sale_id . '" class="form-control mt-2" placeholder="Email Subject" value="' . $newSubject . '" required>';
					$content .= '        </div>';
					$content .= '        <div class="form-group">';
					$content .= '          <textarea name="email_body" id="email_body_requested_' . $applicant->id . '_' . $applicant->sale_id . '" rows="5" class="form-control mt-2" placeholder="Email Body" required>' . $newPhrase . '</textarea>';
					$content .= '        </div>';
					$content .= '      </div>';
					$content .= '      <div class="modal-footer">';
$content .= '<button type="button" class="btn btn-danger" id="submit_requested_applicant_email_btn_' . $applicant->id . '_' . $applicant->sale_id . '" data-applicant-id="' . $applicant->id . '" data-sale-id="' . $applicant->sale_id . '">Submit Email</button>';

					$content .= '      </div>';
					$content .= '    </div>';
					$content .= '  </div>';
					$content .= '</div>';
					/*** /Reject Modal for Email Input (Child Modal) */

                }

                if ($auth_user->hasAnyPermission(['CRM_Request_reject','CRM_Request_confirm','CRM_Request_save'])) {
                    /*** Confirmation CV Modal */
                    $content .= '<div id="confirm_cv' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Confirm CV Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="request_cv_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="request_cv_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .= '<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">';
                    $content .= '<input type="hidden" name="applicant_name_chat" id="applicant_name_chat">';
                    $content .= '<input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">';
                    $content .= '<textarea name="details" id="request_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    if ($auth_user->hasPermissionTo('CRM_Request_reject')) {
                      //  $content .= '<button type="submit" name="request_reject" value="request_reject" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple request_cv_submit"> Reject </button>';
						 $content .= '<button type="button" class="btn bg-orange-800 legitRipple reject-btn" data-applicant-id="' . $applicant->id . '" data-sale-id="' . $applicant->sale_id . '" data-toggle="modal" data-target="#reject_email_modal">Reject</button>';
                    }
                        if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }

                    if ($auth_user->hasPermissionTo('CRM_Request_confirm')) {
                        $disabled = "disabled";
                        if ($applicant->schedule_time && $applicant->schedule_date && $applicant->interview_status == 'active') {
                            $disabled = "";
                        }
                        $content .= '<button type="submit" name="request_to_confirm" value="request_to_confirm" ' . $disabled . ' data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple request_cv_submit"> Confirm </button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Request_save')) {
                        $content .= '<button type="submit" name="request_to_save" value="request_to_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple request_cv_submit"> Save </button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Confirmation CV Modal */
					
								
					  /*** Reject Modal for Email Input (Child Modal) */
                    $content .= '<div id="reject_email_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="rejectEmailModalLabel" aria-hidden="true" style="z-index:99999">';
                    $content .= '  <div class="modal-dialog" role="document">';
                    $content .= '    <div class="modal-content">';
                    $content .= '      <div class="modal-header">';
                    $content .= '        <h5 class="modal-title" id="rejectEmailModalLabel">Send Rejection Email</h5>';
                    $content .= '        <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
                    $content .= '          <span aria-hidden="true">&times;</span>';
                    $content .= '        </button>';
                    $content .= '      </div>';
                    $content .= '      <div class="modal-body">';
                    $content .= '        <!-- Hidden Inputs to Store Applicant and Sale IDs -->';
                    $content .= '        <input type="hidden" name="applicant_id" id="applicant_id">';
                    $content .= '        <input type="hidden" name="sale_id" id="sale_id">';
                    $content .= '        <!-- Email Subject and Body Inputs -->';
					 $content .= '        <div class="form-group">';
                  $content .= '          <input type="text" name="email_address" id="email_address" class="form-control mt-2" placeholder="Enter Email Address" required>';
                  $content .= '        </div>';
                    $content .= '        <div class="form-group">';
                    $content .= '          <input type="text" name="email_subject" id="email_subject" class="form-control mt-2" placeholder="Email Subject" required>';
                    $content .= '        </div>';
                    $content .= '        <div class="form-group">';
                    $content .= '          <textarea name="email_body" id="email_body" rows="5" class="form-control mt-2" placeholder="Email Body" required></textarea>';
                    $content .= '        </div>';
                    $content .= '      </div>';
                    $content .= '      <div class="modal-footer">';
                    $content .= '        <button type="button" class="btn btn-danger" id="submit_email_btn">Submit Email</button>';
                    $content .= '      </div>';
                    $content .= '    </div>';
                    $content .= '  </div>';
                    $content .= '</div>';
                    /*** /Reject Modal for Email Input (Child Modal) */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
               foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }
	
    public function crmRequestNonNurse()
    {
        $auth_user = Auth::user();
        $crm_request_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })
            ->whereIn("applicants.job_category",["non-nurse","chef",'nursery'])
            ->whereIn("history.sub_stage", ["crm_request", "crm_request_save"])
            ->whereIn("crm_notes.moved_tab_to", ["cv_sent_request", "request_save"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();
        $applicant_cvs_in_request = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->leftJoin('interviews', function ($join) {
                $join->on('applicants.id', '=', 'interviews.applicant_id');
                $join->on('sales.id', '=', 'interviews.sale_id');
                $join->where('interviews.status', '=', 'active');
            })->join('history', function ($join) {
            $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
            $join->on('crm_notes.sales_id', '=', 'history.sale_id');
        })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
            'applicants.id', 'applicants.applicant_name', 'applicants.applicant_email', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
            'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
            'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
            'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
            'units.contact_email', 'units.website',
            'interviews.schedule_time', 'interviews.schedule_date', 'interviews.status as interview_status')
            ->where([
                "applicants.status" => "active",
                //"applicants.job_category" => "non-nurse",
                "crm_notes.moved_tab_to" => "cv_sent_request", "crm_notes.status" => "active",
                "history.status" => "active"
            ])
          ->whereIn("applicants.job_category",["non-nurse","chef",'nursery'])
			->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="cv_sent_request" and sales_id=sales.id and applicants.id=applicant_id'));
            })->whereIn("history.sub_stage", ["crm_request", "crm_request_save"])
            ->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($applicant_cvs_in_request)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function($applicant){
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
                return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function($applicant){
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_request_save_note) {
                $content = '';
                if(!empty($crm_request_save_note)) {
                    foreach ($crm_request_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time;
                            $content .= '</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				
				$title = 'request_configuration_email';
                $newPhrase = '';
                $newSubject = '';
                $applicant_email = '';
                $request_configuration_email = EmailTemplate::where('title',$title)->first();

                if($request_configuration_email && $request_configuration_email->is_active == 1){
                    // Loop through each attribute of the model
                    foreach ($request_configuration_email->getAttributes() as $key => $value) {
                        if (is_string($value)) {
                            // Convert to UTF-8 if not properly encoded
                            $request_configuration_email->{$key} = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                        }
                    }

                    $applicant_name = $applicant->applicant_name;
                    $salary = $applicant->salary;
                    $job_postcode = $applicant->postcode;
                    $unit_name = $applicant->unit_name;
                    $distance = $job_postcode;
                    $location = $job_postcode;

                    $job_title_desc='';
                    if($applicant->job_title_prof != null)
                    {
                        $job_prof_res = Specialist_job_titles::select('id','specialist_prof')->where('id', $applicant->job_title_prof)->first();
                        $job_title_desc = strtoupper($applicant->job_title.' ('.$job_prof_res->specialist_prof.')');
                    }
                    else
                    {
                        $job_title_desc = strtoupper($applicant->job_title);
                    }

                    //replace variables into body
                    $data = $request_configuration_email->template;
                    $replace = [$applicant_name, $job_title_desc, $unit_name, $job_postcode, $salary, $distance, $location];
                    $prev_val = ['(applicant_name)', '(job_type)', '(unit_name)', '(postcode)', '(salary)', '(distance)', '(location)' ];
                    $newPhrase = str_replace($prev_val, $replace, $data);

                    // Strip HTML tags from the email body
                    // $newPhrase = strip_tags($newPhrase);
                    
                    //replace variables into subjects
                    $subject = $request_configuration_email->subject;
                    $subjectReplace = [$job_title_desc];
                    $prev_sub = ['(job_type)'];
                    $newSubject = str_replace($prev_sub, $subjectReplace, $subject);

                    $applicant_email = htmlspecialchars($applicant->applicant_email, ENT_QUOTES, 'UTF-8');
                    $newSubject = htmlspecialchars($newSubject, ENT_QUOTES, 'UTF-8');
                    $newPhrase = htmlspecialchars($newPhrase, ENT_QUOTES, 'UTF-8');

                }

                $emailIcon = '<i class="fas fa-envelope text-secondary" title="Not Attached"></i>';
                $sentEmail = SentEmail::where('applicant_id',$applicant->id)->where('sale_id',$applicant->sale_id)->latest()->first();
                if($sentEmail && $sentEmail->status == '1'){
                    $emailIcon = '<i class="fas fa-envelope text-success" title="Sent"></i>';
                }elseif($sentEmail && $sentEmail->status == '2'){
                    $emailIcon = '<i class="fas fa-envelope text-danger" title="Failed"></i>';
                }elseif($sentEmail && $sentEmail->status == '0'){
                    $emailIcon = '<i class="fas fa-envelope text-secondary" title="Pending"></i>';
                }
				
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
				$content .= $emailIcon.'<br>';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasPermissionTo('CRM_Request_schedule-interview')) {
                    if ($applicant->schedule_time && $applicant->schedule_date && $applicant->interview_status == 'active') {
                        $content .= '<a href="#" class="disabled dropdown-item"><i class="icon-file-confirm"></i>Schedule Interview</a>';
                    } else {
						 if ($sentEmail && $sentEmail->status == '1') {
							// If the email is successfully sent (status = '1'), skip the email modal
							$content .= '<a href="#" class="dropdown-item" data-controls-modal="#schedule_interview' . $applicant->id . '-' . $applicant->sale_id . '" data-toggle="modal" data-target="#schedule_interview' . $applicant->id . '-' . $applicant->sale_id . '">';
							$content .= '<i class="icon-file-confirm"></i>Schedule Interview</a>';
						} else {
							// If no email has been sent yet, open the email modal first
							$content .= '<a href="#" class="dropdown-item" data-controls-modal="#send_applicant_email_request_modal' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static" data-keyboard="false" data-toggle="modal" data-target="#send_applicant_email_request_modal' . $applicant->id . '-' . $applicant->sale_id . '">';
							$content .= '<i class="icon-file-confirm"></i>Schedule Interview</a>';
						}
                    }
                }
                if ($auth_user->hasAnyPermission(['CRM_Request_reject','CRM_Request_confirm','CRM_Request_save'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option"
                                           data-controls-modal="#confirm_cv' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                           data-applicantNameJs="' . $applicant->applicant_name . '" 
                                           data-applicantIdJs="' . $applicant->id . '"
                                           data-target="#confirm_cv' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i>Move To Confirmation</a>';
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                                    {
                                     if ($applicant_msgs['is_read'] == 0) {
                                        $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                        $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                     }
                                    }
                                }
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasPermissionTo('CRM_Request_schedule-interview')) {
                    /*** Schedule Interview Modal */
                    $content .= '<div id="schedule_interview' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-sm">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h3 class="modal-title">' . $applicant->applicant_name . '</h3>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="schedule_interview_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<form action="' . route('scheduleInterview') . '" method="POST" id="schedule_interview_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<input type="hidden" name="applicant_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="sale_id" value="' . $applicant->sale_id . '">';
                    $content .= '<div class="mb-4">';
                    $content .= '<div class="input-group">';
                    $content .= '<span class="input-group-prepend">';
                    $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                    $content .= '</span>';
                    $content .= '<input type="text" class="form-control pickadate-year" name="schedule_date" id="schedule_date' . $applicant->id . '-' . $applicant->sale_id . '" placeholder="Select Schedule Date">';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="mb-4">';
                    $content .= '<div class="input-group">';
                    $content .= '<span class="input-group-prepend">';
                    $content .= '<span class="input-group-text"><i class="icon-watch2"></i></span>';
                    $content .= '</span>';
//                $content .= '<input type="text" class="form-control time_pickerrrr" id="anytime-time'.$applicant->id.'-'.$applicant->sale_id.'" name="schedule_time" placeholder="Select Schedule Time e.g., 00:00">';
                    $content .= '<input type="text" class="form-control" id="schedule_time' . $applicant->id . '-' . $applicant->sale_id . '" name="schedule_time" placeholder="Type Schedule Time e.g., 10:00">';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<button type="submit" class="btn bg-teal legitRipple btn-block schedule_interview_submit" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '">Schedule</button>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Schedule Interview Modal */
					
					/*** Reject Modal for Email Input (Child Modal) */
					$content .= '<div id="send_applicant_email_request_modal' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="applicantEmailModalLabel" aria-hidden="true" style="z-index:99999">';
					$content .= '  <div class="modal-dialog" role="document">';
					$content .= '    <div class="modal-content">';
					$content .= '      <div class="modal-header">';
					$content .= '        <h5 class="modal-title" id="applicantEmailModalLabel">Send email to applicant</h5>';
					$content .= '        <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
					$content .= '          <span aria-hidden="true">&times;</span>';
					$content .= '        </button>';
					$content .= '      </div>';
					$content .= '      <div class="modal-body">';
					$content .= '        <!-- Hidden Inputs to Store Applicant and Sale IDs -->';
					$content .= '        <input type="hidden" name="applicant_id" id="requested_applicant_id_' . $applicant->id . '_' . $applicant->sale_id . '" value="' . $applicant->id . '">';
					$content .= '        <input type="hidden" name="sale_id" id="requested_sale_id_' . $applicant->id . '_' . $applicant->sale_id . '" value="' . $applicant->sale_id . '">';
					$content .= '        <!-- Email Subject and Body Inputs -->';
					$content .= '        <div class="form-group">';
					$content .= '          <input type="email" name="email_address" id="email_address_requested_' . $applicant->id . '_' . $applicant->sale_id . '" class="form-control mt-2" placeholder="Enter Email Address" value="' . $applicant_email . '" required>';
					$content .= '        </div>';
					$content .= '        <div class="form-group">';
					$content .= '          <input type="text" name="email_subject" id="email_subject_requested_' . $applicant->id . '_' . $applicant->sale_id . '" class="form-control mt-2" placeholder="Email Subject" value="' . $newSubject . '" required>';
					$content .= '        </div>';
					$content .= '        <div class="form-group">';
					$content .= '          <textarea name="email_body" id="email_body_requested_' . $applicant->id . '_' . $applicant->sale_id . '" rows="5" class="form-control mt-2" placeholder="Email Body" required>' . $newPhrase . '</textarea>';
					$content .= '        </div>';
					$content .= '      </div>';
					$content .= '      <div class="modal-footer">';
					$content .= '<button type="button" class="btn btn-danger" id="submit_requested_applicant_email_btn_' . $applicant->id . '_' . $applicant->sale_id . '" data-applicant-id="' . $applicant->id . '" data-sale-id="' . $applicant->sale_id . '">Submit Email</button>';
					$content .= '      </div>';
					$content .= '    </div>';
					$content .= '  </div>';
					$content .= '</div>';
					/*** /Reject Modal for Email Input (Child Modal) */
                }

                if ($auth_user->hasAnyPermission(['CRM_Request_reject','CRM_Request_confirm','CRM_Request_save'])) {
                    /*** Confirmation CV Modal */
                    $content .= '<div id="confirm_cv' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Confirm CV Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="request_cv_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="request_cv_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .= '<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">';
                    $content .= '<input type="hidden" name="applicant_name_chat" id="applicant_name_chat">';
                    $content .= '<input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">';
                    $content .= '<textarea name="details" id="request_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    if ($auth_user->hasPermissionTo('CRM_Request_reject')) {
                      //  $content .= '<button type="submit" name="request_reject" value="request_reject" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple request_cv_submit"> Reject </button>';
						 $content .= '<button type="button" class="btn bg-orange-800 legitRipple reject-btn" data-applicant-id="' . $applicant->id . '" data-sale-id="' . $applicant->sale_id . '" data-toggle="modal" data-target="#reject_email_modal">Reject</button>';
                    }
                    if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }

                    if ($auth_user->hasPermissionTo('CRM_Request_confirm')) {
                        $disabled = "disabled";
                        if ($applicant->schedule_time && $applicant->schedule_date && $applicant->interview_status == 'active') {
                            $disabled = "";
                        }
                        $content .= '<button type="submit" name="request_to_confirm" value="request_to_confirm" ' . $disabled . ' data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple request_cv_submit"> Confirm </button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Request_save')) {
                        $content .= '<button type="submit" name="request_to_save" value="request_to_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple request_cv_submit"> Save </button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Confirmation CV Modal */
					
					 /*** Reject Modal for Email Input (Child Modal) */
                    $content .= '<div id="reject_email_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="rejectEmailModalLabel" aria-hidden="true" style="z-index:99999">';
                    $content .= '  <div class="modal-dialog" role="document">';
                    $content .= '    <div class="modal-content">';
                    $content .= '      <div class="modal-header">';
                    $content .= '        <h5 class="modal-title" id="rejectEmailModalLabel">Send Rejection Email</h5>';
                    $content .= '        <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
                    $content .= '          <span aria-hidden="true">&times;</span>';
                    $content .= '        </button>';
                    $content .= '      </div>';
                    $content .= '      <div class="modal-body">';
                    $content .= '        <!-- Hidden Inputs to Store Applicant and Sale IDs -->';
                    $content .= '        <input type="hidden" name="applicant_id" id="applicant_id">';
                    $content .= '        <input type="hidden" name="sale_id" id="sale_id">';
                    $content .= '        <!-- Email Subject and Body Inputs -->';
					 $content .= '        <div class="form-group">';
                  $content .= '          <input type="text" name="email_address" id="email_address" class="form-control mt-2" placeholder="Enter Email Address" required>';
                  $content .= '        </div>';
                    $content .= '        <div class="form-group">';
                    $content .= '          <input type="text" name="email_subject" id="email_subject" class="form-control mt-2" placeholder="Email Subject" required>';
                    $content .= '        </div>';
                    $content .= '        <div class="form-group">';
                    $content .= '          <textarea name="email_body" id="email_body" rows="5" class="form-control mt-2" placeholder="Email Body" required></textarea>';
                    $content .= '        </div>';
                    $content .= '      </div>';
                    $content .= '      <div class="modal-footer">';
                    $content .= '        <button type="button" class="btn btn-danger" id="submit_email_btn">Submit Email</button>';
                    $content .= '      </div>';
                    $content .= '    </div>';
                    $content .= '  </div>';
                    $content .= '</div>';
                    /*** /Reject Modal for Email Input (Child Modal) */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }

    public function requestAction(Request $request)
    {
        date_default_timezone_set('Europe/London');

        /*** Request tab */
        $request_reject = $request->Input('request_reject');
        $request_to_confirm = $request->Input('request_to_confirm');
        $request_to_save = $request->Input('request_to_save');
		$request_no_job = $request->Input('applicant_hidden_no_job');

        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
		
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';

		 if (!empty($request_no_job)&&$request_no_job=="no_job"){
            if (!empty($request_reject) && ($request_reject == 'request_reject')) {

                $audit_data['action'] = "Reject";
                Applicant::where("id", $applicant_id)->update(['is_in_crm_request_reject' => 'yes', 'is_in_crm_request' => 'no']);
                Interview::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id])->update(['status' => 'disable']);
                Cv_note::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id])->update(["status" => "disable"]);
                Quality_notes::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id])->update(["status" => "disable"]);
                $crm_notes = new Crm_note();
                $crm_notes->applicant_id = $applicant_id;
                $crm_notes->user_id = $auth_user;
                $crm_notes->sales_id = $sale_id;
                $crm_notes->details = $details;
                $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
//                $crm_notes->moved_tab_to = "request_reject"; //old status
                $crm_notes->moved_tab_to = "request_no_job_reject";
                $crm_notes->save();
                Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->update(["status" => "disable"]);

                /*** activity log
                 * $this->action_observer->action($audit_data, 'CRM > Request');
                 */

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
                    $history->user_id = $auth_user;
                    $history->sale_id = $sale_id;
                    $history->stage = 'crm';
                    $history->sub_stage = 'crm_request_no_job_reject';
                    $history->history_added_date = date("jS F Y");
                    $history->history_added_time = date("h:i A");
                    $history->save();
					
                    $last_inserted_history = $history->id;
                    if ($last_inserted_history > 0) {
                        $history_uid = md5($last_inserted_history);
                        History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                        $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">' . $request->input('module') . '</span> Applicant CV rejected by request Successfully
						</div>';
                        echo $html;
                    }
                } else {
                    echo $html;
                }
            } elseif (!empty($request_to_confirm) && ($request_to_confirm == 'request_to_confirm')) 
			{

                $audit_data['action'] = "Confirm";
                Applicant::where("id", $applicant_id)->update(['is_crm_request_confirm' => 'yes', 'is_in_crm_request' => 'no', 'is_in_crm_request_reject' => 'no']);
                $crm_notes = new Crm_note();
                $crm_notes->applicant_id = $applicant_id;
                $crm_notes->user_id = $auth_user;
                $crm_notes->sales_id = $sale_id;
                $crm_notes->details = $details;
                $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
//                $crm_notes->moved_tab_to = "request_confirm"; //old status
                $crm_notes->moved_tab_to = "request_no_job_confirm";
                $crm_notes->save();

                /*** activity log
                 * $this->action_observer->action($audit_data, 'CRM > Request');
                 */

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
                    $history->user_id = $auth_user;
                    $history->sale_id = $sale_id;
                    $history->stage = 'crm';
//                    $history->sub_stage = 'crm_request_confirm'; old status
                    $history->sub_stage = 'crm_request_no_job_confirm';
                    $history->history_added_date = date("jS F Y");
                    $history->history_added_time = date("h:i A");
                    $history->save();
                    $last_inserted_history = $history->id;
                    if ($last_inserted_history > 0) {
                        $history_uid = md5($last_inserted_history);
                        History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                        $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">' . $request->input('module') . '</span> Applicant CV moved to Confirmation Successfully
						</div>';
                        echo $html;
                    }
                } else {
                    echo $html;
                }
            } elseif (!empty($request_to_save) && ($request_to_save == 'request_to_save')) {
                $audit_data['action'] = "Save";
                $crm_notes = new Crm_note();
                $crm_notes->applicant_id = $applicant_id;
                $crm_notes->user_id = $auth_user;
                $crm_notes->sales_id = $sale_id;
                $crm_notes->details = $details;
                $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
//                $crm_notes->moved_tab_to = "request_save";old status
                $crm_notes->moved_tab_to = "request_no_job_save";
                $crm_notes->save();

                /*** activity log
                 * $this->action_observer->action($audit_data, 'CRM > Request');
                 */

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
                    $history->user_id = $auth_user;
                    $history->sale_id = $sale_id;
                    $history->stage = 'crm';
//                    $history->sub_stage = 'crm_request_save';
                    $history->sub_stage = 'crm_request_no_job_save';
                    $history->history_added_date = date("jS F Y");
                    $history->history_added_time = date("h:i A");
                    $history->save();
                    $last_inserted_history = $history->id;
                    if ($last_inserted_history > 0) {
                        $history_uid = md5($last_inserted_history);
                        History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                        $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">' . $request->input('module') . '</span> Note saved Successfully
						</div>';
                        echo $html;
                    }
                } else {
                    echo $html;
                }
            }

        }
        else {
        if (!empty($request_reject) && ($request_reject == 'request_reject')) {
            $audit_data['action'] = "Reject";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_request_reject' => 'yes', 'is_in_crm_request' => 'no']);
            Interview::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id])->update(['status' => 'disable']);
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "request_reject";
            $crm_notes->save();
            Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->update(["status" => "disable"]);

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Request');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_request_reject';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
				
				if($history){
					//new working to send email to applicant on request reject
					$sale = Sale::where('id',$sale_id)->select('head_office_unit')->first();
					$unit_id = $sale ? $sale->head_office_unit : 0;

					$unit_data = Unit::where('id',$unit_id)->select('unit_name','contact_email')->first();
					$unit_name = $unit_data ? ucwords(strtolower($unit_data->unit_name)) : '';
					$unit_email = $unit_data ? $unit_data->contact_email : '';

					$applicantRecord = Applicant::where("id", $applicant_id)->select('applicant_name')->first();
					$applicant_name = $applicantRecord ? ucwords(strtolower($applicantRecord->applicant_name)) : '';
					
				//	$this->sendEmailToUnit($applicant_name, $unit_name, $unit_email);
				}
				
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV rejected by request Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif (!empty($request_to_confirm) && ($request_to_confirm == 'request_to_confirm')) {
            $audit_data['action'] = "Confirm";
            Applicant::where("id", $applicant_id)->update(['is_crm_request_confirm' => 'yes', 'is_in_crm_request' => 'no', 'is_in_crm_request_reject' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "request_confirm";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Request');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_request_confirm';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Confirmation Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif (!empty($request_to_save) && ($request_to_save == 'request_to_save')) {
            $audit_data['action'] = "Save";
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "request_save";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Request');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_request_save';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Note saved Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        }
		}
    }

    public function crmRejectByRequest()
    {
        $auth_user = Auth::user();
        /*** interview information is not required
        ->leftJoin('interviews', function ($join) {
        $join->on('applicants.id', '=', 'interviews.applicant_id');
        $join->on('crm_notes.sales_id', '=', 'interviews.sale_id');
        $join->whereIn('interviews.id', function($query){
        $query->select(DB::raw('MAX(id) FROM interviews WHERE sale_id=sales.id and applicants.id=applicant_id'));
        });
        });
         *** add this in select() : 'interviews.schedule_time', 'interviews.schedule_date', 'interviews.status as interview_status'
         */

        $reject_by_request = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title', 'applicants.job_category',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.send_cv_limit',
                'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
                'units.contact_email', 'units.website','applicants.is_no_job')
            ->where([
                "applicants.status" => "active",
                //"crm_notes.moved_tab_to" => "request_reject",
                //"history.sub_stage" => "crm_request_reject", 
				"history.status" => "active"
            ])->whereIn("crm_notes.moved_tab_to" ,["request_reject","request_no_job_reject"])
            ->whereIn("history.sub_stage" ,["crm_request_reject","crm_request_no_job_reject"])
            ->whereIn('crm_notes.id', function($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('crm_notes')
                    ->whereIn('moved_tab_to',["request_reject","request_no_job_reject"])
                    ->where('sales_id', DB::raw('sales.id'))
                    ->where('applicant_id', DB::raw('applicants.id'));
            })->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($reject_by_request)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function($applicant){
                return strtoupper($applicant->applicant_job_title);
            })
            ->addColumn("applicant_postcode", function($applicant){
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) {
                $content = '';
                $content .= '<p><b>DATE: </b>';
                $content .= $applicant->crm_added_date;
                $content .= '<b> TIME: </b>';
                $content .= $applicant->crm_added_time;
                $content .= '</p>';
                $content .= '<p><b>NOTE: </b>';
                $content .= $applicant->details;
                $content .= '</p>';

                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                if ($auth_user->hasAnyPermission(['CRM_Rejected-By-Request_revert-sent-cv','CRM_Rejected-By-Request_revert-request'])) {
                    $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#revert' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#revert' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i> Revert </a>';
                }
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                                    {
                                     if ($applicant_msgs['is_read'] == 0) {
                                        $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                        $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                     }
                                    }
                                }

                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                if ($auth_user->hasAnyPermission(['CRM_Rejected-By-Request_revert-sent-cv','CRM_Rejected-By-Request_revert-request'])) {
                    $sent_cv_count = Cv_note::where(['sale_id' => $applicant->sale_id, 'status' => 'active'])->count();
                    /*** Revert To Sent CVs Modal */
                    $content .= '<div id="revert' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Rejected CV Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="revert_cv_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="revert_cv_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Sent CV</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<label class="col-form-label font-weight-semibold">'.$sent_cv_count.' out of '.$applicant->send_cv_limit.'</label>';
                    $content .= '</div>';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
					 if ($applicant->is_no_job==1){
                        $content .= '<input type="hidden" name="applicant_hidden_no_job" value="no_job">';

                    }
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .= '<textarea name="details" id="revert_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    if ($auth_user->hasPermissionTo('CRM_Rejected-By-Request_revert-sent-cv')) {
                        $content .= '<button type="submit" name="rejected_request_revert_sent_cvs" value="rejected_request_revert_sent_cvs" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple revert_cv_submit"> Sent CV </button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Rejected-By-Request_revert-request')) {
                        $content .= '<button type="submit" name="rejected_request_revert_request" value="rejected_request_revert_request" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple revert_cv_submit"> Request </button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Revert To Sent CVs Modal */
                }

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }

    public function rejectByRequestAction(Request $request)
    {
        date_default_timezone_set('Europe/London');

        /*** Rejected By Request tab */
        $rejected_request_revert_sent_cvs = $request->input('rejected_request_revert_sent_cvs');
        $rejected_request_revert_request = $request->input('rejected_request_revert_request');
		$rejected_request_no_job_revert_request = $request->input('applicant_hidden_no_job');

        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
		
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';

		 if(!empty($rejected_request_no_job_revert_request)&&$rejected_request_no_job_revert_request=="no_job"){

            $sale = Sale::find($sale_id);
            if ($sale) {
                $sent_cv_count = Cv_note::where(['sale_id' => $sale_id, 'status' => 'active'])->count();
//                if ($sent_cv_count < $sale->send_cv_limit) {
                    if (!empty($rejected_request_revert_sent_cvs) && ($rejected_request_revert_sent_cvs == 'rejected_request_revert_sent_cvs')) {
//                        Cv_note::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id])->update(["status" => "active"]);
                        Quality_notes::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id, "moved_tab_to" => "cleared_no_job"])->update(["status" => "active"]);
                        Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->whereIn("moved_tab_to", ["cv_sent", "cv_sent_saved", "cv_sent_request","cv_sent_no_job"])->update(["status" => "disable"]);
                        $crm_notes = new Crm_note();
                        $crm_notes->applicant_id = $applicant_id;
                        $crm_notes->user_id = $auth_user;
                        $crm_notes->sales_id = $sale_id;
                        $crm_notes->details = $details;
                        $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                        $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
//                        $crm_notes->moved_tab_to = "cv_sent";//old status
                        $crm_notes->moved_tab_to = "cv_sent_no_job";
                        $crm_notes->save();

                        /*** activity log
                         * $this->action_observer->action($audit_data, 'CRM > Rejected CV revert to Sent CVs');
                         */

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
                            $history->user_id = $auth_user;
                            $history->sale_id = $sale_id;
                            $history->stage = 'crm';
                            $history->sub_stage = 'quality_cleared_no_job';
                            $history->history_added_date = date("jS F Y");
                            $history->history_added_time = date("h:i A");
                            $history->save();
							
                            $last_inserted_history = $history->id;
                            if ($last_inserted_history > 0) {
                                $history_uid = md5($last_inserted_history);
                                History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                                $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">' . $request->input('module') . '</span> Applicant CV reverted Sent CVs Successfully
						</div>';
                                echo $html;
                            }
                        } else {
                            echo $html;
                        }
                    } elseif (!empty($rejected_request_revert_request) && ($rejected_request_revert_request == 'rejected_request_revert_request')) {
//                        Cv_note::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id])->update(["status" => "active"]);

                        /*** get latest sent cv record */
                        $latest_sent_cv = Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->where("moved_tab_to", "cv_sent_no_job_request")->latest()->first();
                        $all_cv_sent_saved = Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->where("moved_tab_to", "cv_sent_saved")
                            ->where('created_at', '>=', $latest_sent_cv->created_at)->get();
                        $crm_notes_ids[0] = $latest_sent_cv->id;
                        foreach ($all_cv_sent_saved as $cv) {
                            $crm_notes_ids[] = $cv->id;
                        }
                        Crm_note::whereIn('id', $crm_notes_ids)->update(["status" => "active"]);
//                        Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->whereIn("moved_tab_to", ["cv_sent", "cv_sent_saved"])->update(["status" => "active"]);

                        $crm_notes = new Crm_note();
                        $crm_notes->applicant_id = $applicant_id;
                        $crm_notes->user_id = $auth_user;
                        $crm_notes->sales_id = $sale_id;
                        $crm_notes->details = $details;
                        $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                        $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
                        $crm_notes->moved_tab_to = "cv_sent_no_job_request";
                        $crm_notes->save();

                        /*** activity log
                         * $this->action_observer->action($audit_data, 'CRM > Request');
                         */

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
                            $history->user_id = $auth_user;
                            $history->sale_id = $sale_id;
                            $history->stage = 'crm';
                            $history->sub_stage = 'crm_no_job_request';
                            $history->history_added_date = date("jS F Y");
                            $history->history_added_time = date("h:i A");
                            $history->save();
                            $last_inserted_history = $history->id;
                            if ($last_inserted_history > 0) {
                                $history_uid = md5($last_inserted_history);
                                History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                                $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">' . $request->input('module') . '</span> Applicant CV reverted Request Successfully
						</div>';
                                echo $html;
                            }
                        } else {
                            echo $html;
                        }
                    }
//                } else {
//                    echo
//                    '<div class="alert alert-danger border-0 alert-dismissible">
//                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
//                        <span class="font-weight-semibold"> WHOOPS!</span> You cannot perform this action. Send CV Limit for this Sale has reached maximum!!
//                    </div>';
//                }
            } else {
                echo
                '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold"> WHOOPS!</span> Sale not found!!
                </div>';
            }
        }

        else {
		
        $sale = Sale::find($sale_id);
        if ($sale) {    
            $sent_cv_count = Cv_note::where(['sale_id' => $sale_id, 'status' => 'active'])->count();
            if ($sent_cv_count < $sale->send_cv_limit) {
                if (!empty($rejected_request_revert_sent_cvs) && ($rejected_request_revert_sent_cvs == 'rejected_request_revert_sent_cvs')) {
                    Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "active"]);
                    Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id, "moved_tab_to" => "cleared"])->update(["status" => "active"]);
                    Crm_note::where(["applicant_id" => $applicant_id,"sales_id" => $sale_id])->whereIn("moved_tab_to", ["cv_sent", "cv_sent_saved", "cv_sent_request"])->update(["status" => "disable"]);
                    $crm_notes = new Crm_note();
                    $crm_notes->applicant_id = $applicant_id;
                    $crm_notes->user_id = $auth_user;
                    $crm_notes->sales_id = $sale_id;
                    $crm_notes->details = $details;
                    $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                    $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
                    $crm_notes->moved_tab_to = "cv_sent";
                    $crm_notes->save();

                    /*** activity log
                     * $this->action_observer->action($audit_data, 'CRM > Rejected CV revert to Sent CVs');
                     */

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
                        $history->user_id = $auth_user;
                        $history->sale_id = $sale_id;
                        $history->stage = 'crm';
                        $history->sub_stage = 'crm_save';
                        $history->history_added_date = date("jS F Y");
                        $history->history_added_time = date("h:i A");
                        $history->save();
                        $last_inserted_history = $history->id;
                        if($last_inserted_history > 0){
                            $history_uid = md5($last_inserted_history);
                            History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                            $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV reverted Sent CVs Successfully
						</div>';
                            echo $html;
                        }
                    } else {
                        echo $html;
                    }
                } elseif (!empty($rejected_request_revert_request) && ($rejected_request_revert_request == 'rejected_request_revert_request')) {
                    Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "active"]);

                    /*** get latest sent cv record */
                    $latest_sent_cv = Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->where("moved_tab_to", "cv_sent")->latest()->first();
                    $all_cv_sent_saved = Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->where("moved_tab_to", "cv_sent_saved")
                        ->where('created_at', '>=', $latest_sent_cv->created_at)->get();
                    $crm_notes_ids[0] = $latest_sent_cv->id;
                    foreach ($all_cv_sent_saved as $cv) {
                        $crm_notes_ids[] = $cv->id;
                    }
                    Crm_note::whereIn('id', $crm_notes_ids)->update(["status" => "active"]);
//                        Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->whereIn("moved_tab_to", ["cv_sent", "cv_sent_saved"])->update(["status" => "active"]);

                    $crm_notes = new Crm_note();
                    $crm_notes->applicant_id = $applicant_id;
                    $crm_notes->user_id = $auth_user;
                    $crm_notes->sales_id = $sale_id;
                    $crm_notes->details = $details;
                    $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                    $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
                    $crm_notes->moved_tab_to = "cv_sent_request";
                    $crm_notes->save();

                    /*** activity log
                     * $this->action_observer->action($audit_data, 'CRM > Request');
                     */

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
                        $history->user_id = $auth_user;
                        $history->sale_id = $sale_id;
                        $history->stage = 'crm';
                        $history->sub_stage = 'crm_request';
                        $history->history_added_date = date("jS F Y");
                        $history->history_added_time = date("h:i A");
                        $history->save();
                        $last_inserted_history = $history->id;
                        if($last_inserted_history > 0){
                            $history_uid = md5($last_inserted_history);
                            History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                            $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV reverted Request Successfully
						</div>';
                            echo $html;
                        }
                    } else {
                        echo $html;
                    }
                }
            } else {
                echo
                '<div class="alert alert-danger border-0 alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        <span class="font-weight-semibold"> WHOOPS!</span> You cannot perform this action. Send CV Limit for this Sale has reached maximum!!
                    </div>';
            }
        } else {
            echo
            '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold"> WHOOPS!</span> Sale not found!!
                </div>';
        }
		}
    }

    public function crmConfirmation()
    {
        $auth_user = Auth::user();
        $crm_confirm_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->whereIn("history.sub_stage", ["crm_request_confirm", "crm_interview_save","crm_request_no_job_confirm"])
            ->whereIn("crm_notes.moved_tab_to", ["request_confirm", "interview_save","request_no_job_confirm"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

        $is_in_crm_confirm = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('interviews', function ($join) {
                $join->on('applicants.id', '=', 'interviews.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'interviews.sale_id');
            })->join('history', function ($join) {
            $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
            $join->on('crm_notes.sales_id', '=', 'history.sale_id');
        })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
            'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
            'offices.office_name',
            'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website',
            'interviews.schedule_time', 'interviews.schedule_date','applicants.is_no_job')
            ->where([
                "applicants.status" => "active",
                //"crm_notes.moved_tab_to" => "request_confirm",
                "interviews.status" => "active",
                "history.status" => "active"
            ])->whereIn("crm_notes.moved_tab_to" ,["request_confirm","request_no_job_confirm"])
            ->whereIn('history.sub_stage', ['crm_request_confirm', 'crm_interview_save',"crm_request_no_job_confirm"])
            ->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id)'))
                    ->from('crm_notes')
                    ->where(function ($subquery) {
                        $subquery->where('moved_tab_to', 'request_confirm')
                            ->orWhere('moved_tab_to', 'request_no_job_confirm');
                    })
                    ->whereRaw('sales_id = sales.id')
                    ->whereRaw('applicants.id = applicant_id');
            })->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($is_in_crm_confirm)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("interview_schedule", function ($applicant) {
                return $applicant->schedule_date.'<br><a href="#" style="margin-left: 15px;">'.$applicant->schedule_time.'</a>';
            })
            ->addColumn("applicant_job_title", function ($applicant) {
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
            return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function ($applicant) {
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';

                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_confirm_save_note) {
                $content = '';
                if(!empty($crm_confirm_save_note)) {
                    foreach ($crm_confirm_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time;
                            $content .= '</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasAnyPermission(['CRM_Confirmation_revert-request','CRM_Confirmation_not-attended','CRM_Confirmation_attend','CRM_Confirmation_rebook','CRM_Confirmation_save'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option"
                               	data-controls-modal="#confirm_cv' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                data-keyboard="false" data-toggle="modal"
								data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                data-applicantNameJs="' . $applicant->applicant_name . '" 
                                data-applicantIdJs="' . $applicant->id . '"
								data-target="#after_interview' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i> Accept </a>';
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                                    {
                                     if ($applicant_msgs['is_read'] == 0) {
                                        $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                        $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                     }
                                    }
                                }
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasAnyPermission(['CRM_Confirmation_revert-request','CRM_Confirmation_not-attended','CRM_Confirmation_attend','CRM_Confirmation_rebook','CRM_Confirmation_save'])) {
                    /*** After Interview Note Modal */
                    $content .= '<div id="after_interview' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal" >';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Interview Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="after_interview_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="after_interview_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
					 if ($applicant->is_no_job==1){
                        $content .= '<input type="hidden" name="applicant_hidden_no_job" value="no_job">';

                    }
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
					$content .= '<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">';
                    $content .= '<input type="hidden" name="applicant_name_chat" id="applicant_name_chat">';
                    $content .= '<input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">';
                    $content .= '<textarea name="details" id="after_interview_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
					if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }

                    if ($auth_user->hasPermissionTo('CRM_Confirmation_revert-request')) {
                        $content .= '<button type="submit" name="confirm_revert_request" value="confirm_revert_request" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-success-400 legitRipple after_interview_submit">Revert Request</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Confirmation_not-attended')) {
                        $content .= '<button type="submit" name="interview_not_attend" value="interview_not_attend" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple after_interview_submit">Not Attend</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Confirmation_attend')) {
                        $content .= '<button type="submit" name="interview_attend" value="interview_attend" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple after_interview_submit"> Attend </button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Confirmation_rebook')) {
                        $content .= '<button type="submit" name="interview_rebook" value="interview_rebook" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-primary-600 legitRipple after_interview_submit"> Rebook </button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Confirmation_save')) {
                        $content .= '<button type="submit" name="interview_save" value="interview_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple after_interview_submit"> Save </button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /After Interview Note Modal */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                return $content;
            })->editColumn('schedule_search', function($applicant)
            { 
                $date_new=strtotime($applicant->schedule_date);
                return date('d-m-Y', $date_new);
            })
            ->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
->rawColumns(['name','interview_schedule','applicant_job_title','applicant_postcode','job_details','crm_note','action','schedule_search'])
            ->make(true);
    }
	
public function crmConfirmationSearch(Request $request)
    {


              $auth_user = Auth::user();
        $crm_confirm_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->whereIn("history.sub_stage", ["crm_request_confirm", "crm_interview_save"])
            ->whereIn("crm_notes.moved_tab_to", ["request_confirm", "interview_save"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

        $is_in_crm_confirm = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('interviews', function ($join) {
                $join->on('applicants.id', '=', 'interviews.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'interviews.sale_id');
            })->join('history', function ($join) {
            $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
            $join->on('crm_notes.sales_id', '=', 'history.sale_id');
        })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
            'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
            'offices.office_name',
            'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website',
            'interviews.schedule_time', 'interviews.schedule_date')
            ->where([
                "applicants.status" => "active",
                "crm_notes.moved_tab_to" => "request_confirm",
                "interviews.status" => "active",
                "history.status" => "active"
            ])
            ->where('interviews.schedule_date', 'like', '%' . $request->get('email') . '%')
            ->whereIn('history.sub_stage', ['crm_request_confirm', 'crm_interview_save'])
            ->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="request_confirm" and sales_id=sales.id and applicants.id=applicant_id'));
            })->orderBy("crm_notes.created_at","DESC");
            return datatables()->of($is_in_crm_confirm)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("interview_schedule", function ($applicant) {
                return $applicant->schedule_date.'<br><a href="#" style="margin-left: 15px;">'.$applicant->schedule_time.'</a>';
            })
            ->addColumn("applicant_job_title", function ($applicant) {
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
            ->addColumn("applicant_postcode", function ($applicant) {
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';

                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_confirm_save_note) {
                $content = '';
                if(!empty($crm_confirm_save_note)) {
                    foreach ($crm_confirm_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time;
                            $content .= '</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasAnyPermission(['CRM_Confirmation_revert-request','CRM_Confirmation_not-attended','CRM_Confirmation_attend','CRM_Confirmation_rebook','CRM_Confirmation_save'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option"
                                           data-controls-modal="#confirm_cv' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                           data-applicantNameJs="' . $applicant->applicant_name . '" 
                                           data-applicantIdJs="' . $applicant->id . '"
                                           data-target="#after_interview' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i> Accept </a>';
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
				 if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                                    {
                                     if ($applicant_msgs['is_read'] == 0) {
                                        $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                        $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                     }
                                    }
                                }
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasAnyPermission(['CRM_Confirmation_revert-request','CRM_Confirmation_not-attended','CRM_Confirmation_attend','CRM_Confirmation_rebook','CRM_Confirmation_save'])) {
                    /*** After Interview Note Modal */
                    $content .= '<div id="after_interview' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Interview Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="after_interview_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="after_interview_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .= '<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">';
                    $content .= '<input type="hidden" name="applicant_name_chat" id="applicant_name_chat">';
                    $content .= '<input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">';
                    $content .= '<textarea name="details" id="after_interview_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }

                    if ($auth_user->hasPermissionTo('CRM_Confirmation_revert-request')) {
                        $content .= '<button type="submit" name="confirm_revert_request" value="confirm_revert_request" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-success-400 legitRipple after_interview_submit">Revert Request</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Confirmation_not-attended')) {
                        $content .= '<button type="submit" name="interview_not_attend" value="interview_not_attend" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple after_interview_submit">Not Attend</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Confirmation_attend')) {
                        $content .= '<button type="submit" name="interview_attend" value="interview_attend" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple after_interview_submit"> Attend </button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Confirmation_rebook')) {
                        $content .= '<button type="submit" name="interview_rebook" value="interview_rebook" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-primary-600 legitRipple after_interview_submit"> Rebook </button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Confirmation_save')) {
                        $content .= '<button type="submit" name="interview_save" value="interview_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple after_interview_submit"> Save </button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /After Interview Note Modal */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                return $content;
            })
				->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
            ->rawColumns(['name','interview_schedule','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }

    public function afterInterviewAction(Request $request)
    {
        date_default_timezone_set('Europe/London');

        /*** Interview Confirmation tab */
        $interview_not_attend = $request->Input('interview_not_attend');
        $interview_attend = $request->Input('interview_attend');
        $interview_save = $request->Input('interview_save');
        $confirm_revert_request = $request->Input('confirm_revert_request');
        $interview_rebook = $request->Input('interview_rebook');
		$interview_revert_no_job = $request->Input('applicant_hidden_no_job');

        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
		
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';

        if (!empty($interview_not_attend) && ($interview_not_attend == 'interview_not_attend')) {
            $audit_data['action'] = "Not Attend";
            Applicant::where("id", $applicant_id)->update(['is_crm_interview_attended' => 'no', 'is_crm_request_confirm' => 'no']);
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "interview_not_attended";
            $crm_notes->save();
            Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->update(["status" => "disable"]);

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Confirmation');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_interview_not_attended';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Not Attended Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif (!empty($interview_attend) && ($interview_attend == 'interview_attend')) {
            $audit_data['action'] = "Attend";
            Applicant::where("id", $applicant_id)->update(['is_crm_interview_attended' => 'yes', 'is_crm_request_confirm' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "interview_attended";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Confirmation');
             */

            $last_inserted_note = $crm_notes->id;
            if ($last_inserted_note > 0) {
                $crm_note_uid = md5($last_inserted_note);
                Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
                History::where([
                    "applicant_id" => $applicant_id,
//                    "user_id" => $auth_user,
                    "sale_id" => $sale_id
                ])->update(["status" => "disable"]);
                $history = new History();
                $history->applicant_id = $applicant_id;
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_interview_attended';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Attended Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif (!empty($interview_rebook) && ($interview_rebook == 'interview_rebook')) {
            $audit_data['action'] = "Rebook";
            Applicant::where("id", $applicant_id)->update(['is_crm_interview_attended' => 'yes', 'is_crm_request_confirm' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "rebook";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Confirmation');
             */

            $last_inserted_note = $crm_notes->id;
            if ($last_inserted_note > 0) {
                $crm_note_uid = md5($last_inserted_note);
                Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
                History::where([
                    "applicant_id" => $applicant_id,
//                    "user_id" => $auth_user,
                    "sale_id" => $sale_id
                ])->update(["status" => "disable"]);
                $history = new History();
                $history->applicant_id = $applicant_id;
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_rebook';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Rebook Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif (!empty($interview_save) && ($interview_save == 'interview_save')) {
            $audit_data['action'] = "Save";
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "interview_save";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Confirmation');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_interview_save';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Note saved Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif(!empty($confirm_revert_request) && ($confirm_revert_request == 'confirm_revert_request')) {
			   if (!empty($interview_revert_no_job) && $interview_revert_no_job == "no_job") {

                $audit_data['action'] = "Confirmation Revert Request";
                Interview::where(['applicant_id' => $applicant_id, 'sale_id' => $sale_id, 'status' => 'active'])->update(['status' => 'disable']);
                Crm_note::where(['applicant_id' => $applicant_id, 'sales_id' => $sale_id])
                    ->whereIn('moved_tab_to', ['cv_sent_request', 'request_to_save', 'request_to_confirm', 'interview_save','request_no_job_confirm'])
                    ->update(['status' => 'disable']);
                $crm_notes = new Crm_note();
                $crm_notes->applicant_id = $applicant_id;
                $crm_notes->user_id = $auth_user;
                $crm_notes->sales_id = $sale_id;
                $crm_notes->details = $details;
                $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
                $crm_notes->moved_tab_to = "cv_sent_no_job_request";
                $crm_notes->save();

                /*** activity log
                $this->action_observer->action($audit_data, 'CRM > Confirmation Revert Request');
                 */

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
                    $history->user_id = $auth_user;
                    $history->sale_id = $sale_id;
                    $history->stage = 'crm';
                    $history->sub_stage = 'crm_no_job_request';
                    $history->history_added_date = date("jS F Y");
                    $history->history_added_time = date("h:i A");
                    $history->save();
                    $last_inserted_history = $history->id;
                    if($last_inserted_history > 0){
                        $history_uid = md5($last_inserted_history);
                        History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                        $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV reverted Request Successfully
						</div>';
                        echo $html;
                    }
                }
                else {
                    echo $html;
                }
            } else {
			
            $audit_data['action'] = "Confirmation Revert Request";
            Interview::where(['applicant_id' => $applicant_id, 'sale_id' => $sale_id, 'status' => 'active'])->update(['status' => 'disable']);
            Crm_note::where(['applicant_id' => $applicant_id, 'sales_id' => $sale_id])
                ->whereIn('moved_tab_to', ['cv_sent_request', 'request_to_save', 'request_to_confirm', 'interview_save'])
                ->update(['status' => 'disable']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "cv_sent_request";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Confirmation Revert Request');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_request';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV reverted Request Successfully
						</div>';
                    echo $html;
                }
            } 
			else {
                echo $html;
            }
			   }
        }
    }

    public function crmRebook()
    {
        $auth_user = Auth::user();
        $crm_rebook_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->whereIn("history.sub_stage", ["crm_rebook", "crm_rebook_save"])
            ->whereIn("crm_notes.moved_tab_to", ["rebook", "rebook_save"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

        $is_in_crm_rebook = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                "applicants.status" => "active",
                "crm_notes.moved_tab_to" => "rebook",
                "history.status" => "active"
            ])->whereIn('history.sub_stage', ['crm_rebook', 'crm_rebook_save'])
            ->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="rebook" and sales_id=sales.id and applicants.id=applicant_id'));
            })
            ->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($is_in_crm_rebook)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function ($applicant) {
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
                return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function ($applicant) {
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';

                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_rebook_save_note) {
                $content = '';
                if(!empty($crm_rebook_save_note)) {
                    foreach ($crm_rebook_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time;
                            $content .= '</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();

                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasAnyPermission(['CRM_Rebook_not-attended','CRM_Rebook_attend','CRM_Rebook_save'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option testing_href"
                                           data-controls-modal="#rebook' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal" data-name="' . $applicant->applicant_name . '" 
										   data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                data-applicantNameJs="' . $applicant->applicant_name . '" 
                                data-applicantIdJs="' . $applicant->id . '" 
                                           data-target="#rebook' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i> Accept </a>';
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                                    {
                                     if ($applicant_msgs['is_read'] == 0) {
                                        $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                        $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                     }
                                    }
                                }
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasAnyPermission(['CRM_Rebook_not-attended','CRM_Rebook_attend','CRM_Rebook_save'])) {
                    /*** Rebook Note Modal */
                    $content .= '<div id="rebook' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade reebok_confirm" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Rebook Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="rebook_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="rebook_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
					$content .= '<input type="hidden" name="test_val" id="test_val">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .= '<textarea name="details" id="rebook_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
					if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }
					if ($auth_user->hasPermissionTo('CRM_Request_confirm')) {
                        $content .= '<button type="submit" name="rebook_confirm" value="rebook_confirm" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" id="rebook_confirm" class="btn bg-blue legitRipple">Confirmation</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Rebook_not-attended')) {
                        $content .= '<button type="submit" name="rebook_not_attend" value="rebook_not_attend" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple rebook_submit">Not Attend</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Rebook_attend')) {
                        $content .= '<button type="submit" name="rebook_attend" value="rebook_attend" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple rebook_submit"> Attend </button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Rebook_save')) {
                        $content .= '<button type="submit" name="rebook_save" value="rebook_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple rebook_submit"> Save </button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Rebook Note Modal */
					
					/*** /Rebook Note Modal */
                    $content .= '<div id="schedule_interviewww" class="modal fade" >';
                    $content .= '<div class="modal-dialog modal-sm">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h3 class="modal-title" id="schdule_applicant_name"></h3>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="schedule_interview_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<form action="' . route('scheduleInterview') . '" method="POST" id="schedule_interview_form_reebok' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<input type="hidden" name="detail_value" id="detail_value" >';
                    $content .= '<input type="hidden" name="rebook_applicant_id" id="rebook_applicant_id" >';
                    $content .= '<input type="hidden" name="rebook_sale_id" id="rebook_sale_id" >';
                    $content .= '<input type="hidden" name="applicant_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="sale_id" value="' . $applicant->sale_id . '">';
                    $content .= '<div class="mb-4">';
                    $content .= '<div class="input-group">';
                    $content .= '<span class="input-group-prepend">';
                    $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                    $content .= '</span>';
                    $content .= '<input type="text" class="form-control pickadate-year" name="schedule_date_reebok" id="schedule_date_reebok' . $applicant->id . '-' . $applicant->sale_id . '" placeholder="Select Schedule Date">';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="mb-4">';
                    $content .= '<div class="input-group">';
                    $content .= '<span class="input-group-prepend">';
                    $content .= '<span class="input-group-text"><i class="icon-watch2"></i></span>';
                    $content .= '</span>';
//                $content .= '<input type="text" class="form-control time_pickerrrr" id="anytime-time'.$applicant->id.'-'.$applicant->sale_id.'" name="schedule_time" placeholder="Select Schedule Time e.g., 00:00">';
                    $content .= '<input type="text" class="form-control" id="schedule_time_reebok' . $applicant->id . '-' . $applicant->sale_id . '" name="schedule_time_reebok" placeholder="Type Schedule Time e.g., 10:00">';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<button type="button" class="btn bg-teal" id="schedule_rebook" >Schedule Confirmation</button>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                    $row_class = '';
                    if(!empty($applicant_msg))
                    {
                    if ($applicant_msg['is_read'] == 0) {
                        $row_class .= 'blink';
                    }
                    }
                    
                    return $row_class;
                }
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }

    public function rebookAction(Request $request)
    {
        date_default_timezone_set('Europe/London');

        /*** Interview Confirmation tab */
        $form_action = $request->Input('form_action');

        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
				
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';

        if (!empty($form_action) && ($form_action == 'rebook_confirm')) {
                    $audit_data['action'] = "Confirm";
                    Applicant::where("id", $applicant_id)->update(['is_crm_request_confirm' => 'yes', 'is_in_crm_request' => 'no', 'is_in_crm_request_reject' => 'no']);
                    $crm_notes = new Crm_note();
                    $crm_notes->applicant_id = $applicant_id;
                    $crm_notes->user_id = $auth_user;
                    $crm_notes->sales_id = $sale_id;
                    $crm_notes->details = $details;
                    $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                    $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
                    $crm_notes->moved_tab_to = "request_confirm";
                    $crm_notes->save();
        
                    /*** activity log
                    $this->action_observer->action($audit_data, 'CRM > Request');
                     */
        
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
                        $history->user_id = $auth_user;
                        $history->sale_id = $sale_id;
                        $history->stage = 'crm';
                        $history->sub_stage = 'crm_request_confirm';
                        $history->history_added_date = date("jS F Y");
                        $history->history_added_time = date("h:i A");
                        $history->save();
                        $last_inserted_history = $history->id;
                        if($last_inserted_history > 0){
                            $history_uid = md5($last_inserted_history);
                            History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                            $html = '<div class="alert alert-success border-0 alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                    <span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV Revert In Confirmation Successfully
                                </div>';
                            echo $html;
                        }
                    } else {
                        echo $html;
                    }
                }elseif (!empty($form_action) && ($form_action == 'rebook_not_attend')) {
            $audit_data['action'] = "Not Attend";
            Applicant::where("id", $applicant_id)->update(['is_crm_interview_attended' => 'no', 'is_crm_request_confirm' => 'no']);
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "interview_not_attended";
            $crm_notes->save();
            Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->update(["status" => "disable"]);

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Confirmation');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_interview_not_attended';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Not Attended Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif (!empty($form_action) && ($form_action == 'rebook_attend')) {
            $audit_data['action'] = "Attend";
            Applicant::where("id", $applicant_id)->update(['is_crm_interview_attended' => 'yes', 'is_crm_request_confirm' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "interview_attended";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Confirmation');
             */

            $last_inserted_note = $crm_notes->id;
            if ($last_inserted_note > 0) {
                $crm_note_uid = md5($last_inserted_note);
                Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
                History::where([
                    "applicant_id" => $applicant_id,
//                    "user_id" => $auth_user,
                    "sale_id" => $sale_id
                ])->update(["status" => "disable"]);
                $history = new History();
                $history->applicant_id = $applicant_id;
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_interview_attended';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Attended Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif (!empty($form_action) && ($form_action == 'rebook_save')) {
            $audit_data['action'] = "Save";
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "rebook_save";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Confirmation');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_rebook_save';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Note saved Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        }
    }

    public function crmPreStartDate()
    {
        $auth_user = Auth::user();
        $crm_attend_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->whereIn("history.sub_stage", ["crm_interview_attended", "crm_prestart_save"])
            ->whereIn("crm_notes.moved_tab_to", ["interview_attended", "prestart_save"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

        $attended = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_email', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'interview_attended',
                'history.status' => 'active'
            ])->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="interview_attended" and sales_id=sales.id and applicants.id=applicant_id'));
            })->whereIn('history.sub_stage', ['crm_interview_attended', 'crm_prestart_save'])
            ->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($attended)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function ($applicant) {
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
            return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function ($applicant) {
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_attend_save_note) {
                $content = '';
                if(!empty($crm_attend_save_note)) {
                    foreach ($crm_attend_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time;
                            $content .= '</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasAnyPermission(['CRM_Attended_start-date','CRM_Attended_save'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option"
                                 data-controls-modal="#accept' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                 data-keyboard="false" data-toggle="modal"
								 data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                 data-applicantNameJs="' . $applicant->applicant_name . '" 
                                 data-applicantIdJs="' . $applicant->id . '"
                                 data-target="#accept' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i> Accept </a>';
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                                    {
                                     if ($applicant_msgs['is_read'] == 0) {
                                        $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                        $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                     }
                                    }
                                }
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasAnyPermission(['CRM_Attended_start-date','CRM_Attended_save','CRM_Attended_decline'])) {
                    /*** Accept Modal */
                    $content .= '<div id="accept' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Start Date Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="accept_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="accept_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
					$content .= '<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">';
                    $content .= '<input type="hidden" name="applicant_name_chat" id="applicant_name_chat">';
                    $content .= '<input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">';
                    $content .= '<textarea name="details" id="accept_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
					 // Send Email button
                    $content .= '<button type="button" class="btn btn-primary" 
                        data-controls-modal="#send_applicant_email_attended_modal' . $applicant->id . '-' . $applicant->sale_id . '"
                        data-backdrop="static" data-toggle="modal" 
                        data-target="#send_applicant_email_attended_modal' . $applicant->id . '-' . $applicant->sale_id . '">Send Email</button>';
					if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }
					if ($auth_user->hasPermissionTo('CRM_Confirmation_rebook')) {
                        $content .= '<button type="submit" name="Confirmation_rebook" value="Confirmation_rebook" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-blue legitRipple accept_submit"> Rebook</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Attended_decline')) {
                        $content .= '<button type="submit" name="decline" value="decline" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple accept_submit"> Decline</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Attended_start-date')) {
                        $content .= '<button type="submit" name="start_date" value="start_date" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple accept_submit">Start Date</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Attended_save')) {
                        $content .= '<button type="submit" name="prestart_save" value="prestart_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple accept_submit"> Save </button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Accept Modal */
					
					 /*** Attended Modal for Email Input (Child Modal) */
                    $content .= '<div id="send_applicant_email_attended_modal' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="applicantEmailAttendedModalLabel" aria-hidden="true" style="z-index:99999">';
                    $content .= '  <div class="modal-dialog" role="document">';
                    $content .= '    <div class="modal-content">';
                    $content .= '      <div class="modal-header">';
                    $content .= '        <h5 class="modal-title" id="applicantEmailAttendedModalLabel">Send email to applicant</h5>';
                    $content .= '        <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
                    $content .= '          <span aria-hidden="true">&times;</span>';
                    $content .= '        </button>';
                    $content .= '      </div>';
                    $content .= '      <div class="modal-body">';
                    $content .= '        <!-- Hidden Inputs to Store Applicant and Sale IDs -->';
                    $content .= '        <input type="hidden" name="applicant_id" id="attended_applicant_id_' . $applicant->id . '_' . $applicant->sale_id . '" value="' . $applicant->id . '">';
                    $content .= '        <input type="hidden" name="sale_id" id="attended_sale_id_' . $applicant->id . '_' . $applicant->sale_id . '" value="' . $applicant->sale_id . '">';
                    $content .= '        <!-- Email Subject and Body Inputs -->';
                    $content .= '        <div class="form-group">';
                    $content .= '          <input type="email" name="email_address" id="email_address_attended_' . $applicant->id . '_' . $applicant->sale_id . '" class="form-control mt-2" placeholder="Enter Email Address" value="' . $applicant->applicant_email . '" required>';
                    $content .= '        </div>';
                    $content .= '        <div class="form-group">';
                    $content .= '          <input type="text" name="email_subject" id="email_subject_attended_' . $applicant->id . '_' . $applicant->sale_id . '" class="form-control mt-2" placeholder="Email Subject" value="" required>';
                    $content .= '        </div>';
                    $content .= '        <div class="form-group">';
                    $content .= '          <textarea name="email_body" id="email_body_attended_' . $applicant->id . '_' . $applicant->sale_id . '" rows="5" class="form-control mt-2" placeholder="Email Body" required></textarea>';
                    $content .= '        </div>';
                    $content .= '      </div>';
                    $content .= '      <div class="modal-footer">';
                    $content .= '<button type="button" class="btn btn-danger" id="submit_applicant_email_attended_btn_' . $applicant->id . '_' . $applicant->sale_id . '" data-applicant-id="' . $applicant->id . '" data-sale-id="' . $applicant->sale_id . '">Submit Email</button>';

                    $content .= '      </div>';
                    $content .= '    </div>';
                    $content .= '  </div>';
                    $content .= '</div>';
                    /*** /Attended Modal for Email Input (Child Modal) */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }

    public function attendedToPreStartAction(Request $request)
    {
        date_default_timezone_set('Europe/London');

        /*** Attended tab */
        $start_date = $request->Input('start_date');
        $prestart_save = $request->Input('prestart_save');
        $decline = $request->Input('decline');
		$interview_rebook = $request->Input('Confirmation_rebook');

        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
				
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';

        if (!empty($interview_rebook) && ($interview_rebook == 'Confirmation_rebook')) {
                    $audit_data['action'] = "Rebook";
                    Applicant::where("id", $applicant_id)->update(['is_crm_interview_attended' => 'yes', 'is_crm_request_confirm' => 'no']);
					Crm_note::where([
                                "applicant_id" => $applicant_id,
                                "sales_id" => $sale_id,
                                "moved_tab_to"=> "rebook"
                            ])->delete();
                    $crm_notes = new Crm_note();
                    $crm_notes->applicant_id = $applicant_id;
                    $crm_notes->user_id = $auth_user;
                    $crm_notes->sales_id = $sale_id;
                    $crm_notes->details = $details;
                    $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                    $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
                    $crm_notes->moved_tab_to = "rebook";
                    $crm_notes->save();
        
                    /*** activity log
                    $this->action_observer->action($audit_data, 'CRM > Confirmation');
                     */
        
                    $last_inserted_note = $crm_notes->id;
                    if ($last_inserted_note > 0) {
                        $crm_note_uid = md5($last_inserted_note);
                        Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
                        History::where([
                            "applicant_id" => $applicant_id,
        //                    "user_id" => $auth_user,
                            "sale_id" => $sale_id
                        ])->update(["status" => "disable"]);
                        $history = new History();
                        $history->applicant_id = $applicant_id;
                        $history->user_id = $auth_user;
                        $history->sale_id = $sale_id;
                        $history->stage = 'crm';
                        $history->sub_stage = 'crm_rebook';
                        $history->history_added_date = date("jS F Y");
                        $history->history_added_time = date("h:i A");
                        $history->save();
                        $last_inserted_history = $history->id;
                        if($last_inserted_history > 0){
                            $history_uid = md5($last_inserted_history);
                            History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                            $html = '<div class="alert alert-success border-0 alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                    <span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Rebook Successfully
                                </div>';
                            echo $html;
                        }
                    } else {
                        echo $html;
                    }
                } elseif (!empty($start_date) && ($start_date == 'start_date')) {
            $audit_data['action'] = "Start Date";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_start_date' => 'yes', 'is_crm_interview_attended' => 'pending']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "start_date";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Attended To Start Date');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_start_date';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Start Date Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif (!empty($prestart_save) && ($prestart_save == 'prestart_save')) {
            $audit_data['action'] = "Save";
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "prestart_save";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Attended To Pre-Start Date');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_prestart_save';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Note saved Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif (!empty($decline) && ($decline == 'decline')) {
            $audit_data['action'] = "Declined";
            Applicant::where("id", $applicant_id)->update(['is_crm_interview_attended' => 'no', 'is_crm_request_confirm' => 'no']);
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "declined";
            $crm_notes->save();
            Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->update(["status" => "disable"]);

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Confirmation');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_declined';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Declined Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        }
    }

    public function crmDeclined()
    {
        $auth_user = Auth::user();
        $crm_attend_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->where("history.sub_stage", "=", "crm_declined")
            ->where("crm_notes.moved_tab_to", "=", "declined")
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

        $attended = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'declined',
                'history.status' => 'active'
            ])->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="declined" and sales_id=sales.id and applicants.id=applicant_id'));
            })->where('history.sub_stage', '=', 'crm_declined')
            ->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($attended)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function ($applicant) {
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
            return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function ($applicant) {
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_attend_save_note) {
                $content = '';
                if(!empty($crm_attend_save_note)) {
                    foreach ($crm_attend_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time;
                            $content .= '</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasPermissionTo('CRM_Declined_revert-to-attended')) {
                    $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#declined_revert' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#declined_revert' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i> Revert </a>';
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                                    {
                                     if ($applicant_msgs['is_read'] == 0) {
                                        $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                        $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                     }
                                    }
                                }

                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasPermissionTo('CRM_Declined_revert-to-attended')) {
                    /*** Revert Modal */
                    $content .= '<div id="declined_revert' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Decline Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="declined_revert_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="declined_revert_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .= '<textarea name="details" id="declined_revert_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    if ($auth_user->hasPermissionTo('CRM_Declined_revert-to-attended')) {
                        $content .= '<button type="submit" name="declined_revert_attended" value="declined_revert_attended" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple declined_submit"> Attended </button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Revert Modal */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }

    public function declinedAction(Request $request)
    {
        date_default_timezone_set('Europe/London');

        /*** Declined tab */
        $declined_revert_attended = $request->Input('declined_revert_attended');

        $audit_data['action'] = 'Declined revert Attended';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
				
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';

        $sale = Sale::find($sale_id);
        if ($sale) {
            $sent_cv_count = Cv_note::where(['sale_id' => $sale_id, 'status' => 'active'])->count();
            if ($sent_cv_count < $sale->send_cv_limit) {
                if (!empty($declined_revert_attended) && ($declined_revert_attended == 'declined_revert_attended')) {
                    $audit_data['action'] = "Revert To Attend";
                    Applicant::where("id", $applicant_id)->update(['is_crm_interview_attended' => 'yes']);
                    Cv_note::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id])->update(["status" => "active"]);

                    /*** latest sent cv records */
                    $crm_notes_index = 0;
                    $latest_sent_cv = Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->where("moved_tab_to", "cv_sent")->latest()->first();
                    $all_cv_sent_saved = Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->where("moved_tab_to", "cv_sent_saved")
                        ->where('created_at', '>=', $latest_sent_cv->created_at)->get();
                    $crm_notes_ids[$crm_notes_index++] = $latest_sent_cv->id;
                    foreach ($all_cv_sent_saved as $cv) {
                        $crm_notes_ids[$crm_notes_index++] = $cv->id;
                    }
                    /*** latest request records */
                    $latest_request = Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->where("moved_tab_to", "cv_sent_request")->latest()->first();
                    $all_request_saved = Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->where("moved_tab_to", "request_save")
                        ->where('created_at', '>=', $latest_request->created_at)->get();
                    $crm_notes_ids[$crm_notes_index++] = $latest_request->id;
                    foreach ($all_request_saved as $cv) {
                        $crm_notes_ids[$crm_notes_index++] = $cv->id;
                    }
                    /*** latest confirmation records */
                    $latest_confirmation = Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->where("moved_tab_to", "request_confirm")->latest()->first();
                    $all_confirmation_saved = Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->where("moved_tab_to", "interview_save")
                        ->where('created_at', '>=', $latest_confirmation->created_at)->get();
                    $crm_notes_ids[$crm_notes_index++] = $latest_confirmation->id;
                    foreach ($all_confirmation_saved as $cv) {
                        $crm_notes_ids[$crm_notes_index++] = $cv->id;
                    }
                    /*** latest rebook records */
                    $latest_rebook = Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->where("moved_tab_to", "rebook")->latest()->first();
                    if ($latest_rebook) {
                        $all_rebook_saved = Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->where("moved_tab_to", "rebook_save")
                            ->where('created_at', '>=', $latest_rebook->created_at)->get();
                        $crm_notes_ids[$crm_notes_index++] = $latest_rebook->id;
                        foreach ($all_rebook_saved as $cv) {
                            $crm_notes_ids[$crm_notes_index++] = $cv->id;
                        }
                    }
                    Crm_note::whereIn('id', $crm_notes_ids)->update(["status" => "active"]);
//                        Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->whereIn('moved_tab_to', ['cv_sent', 'cv_sent_saved', 'cv_sent_request', 'request_save', 'request_confirm', 'prestart_save', 'interview_attended', 'interview_save', 'rebook'])->update(["status" => "active"]);
                    $crm_notes = new Crm_note();
                    $crm_notes->applicant_id = $applicant_id;
                    $crm_notes->user_id = $auth_user;
                    $crm_notes->sales_id = $sale_id;
                    $crm_notes->details = $details;
                    $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                    $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
                    $crm_notes->moved_tab_to = "interview_attended";
                    $crm_notes->save();

                    /*** activity log
                     * $this->action_observer->action($audit_data, 'CRM > Attendd');
                     */

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
                        $history->user_id = $auth_user;
                        $history->sale_id = $sale_id;
                        $history->stage = 'crm';
                        $history->sub_stage = 'crm_interview_attended';
                        $history->history_added_date = date("jS F Y");
                        $history->history_added_time = date("h:i A");
                        $history->save();
                        $last_inserted_history = $history->id;
                        if ($last_inserted_history > 0) {
                            $history_uid = md5($last_inserted_history);
                            History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                            $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">' . $request->input('module') . '</span> Applicant CV reverted Attended to Pre-Start Date Successfully
						</div>';
                            echo $html;
                        }
                    } else {
                        echo $html;
                    }
                }
            } else {
                echo
                '<div class="alert alert-danger border-0 alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        <span class="font-weight-semibold"> WHOOPS!</span> You cannot perform this action. Send CV Limit for this Sale has reached maximum!!
                    </div>';
            }
        } else {
            echo
            '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold"> WHOOPS!</span> Sale not found!!
                </div>';
        }
    }

    public function crmNotAttended()
    {
        $auth_user = Auth::user();
        $not_attended = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title', 'applicants.job_title_prof','applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date', 'sales.send_cv_limit',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                "applicants.status" => "active",
                "crm_notes.moved_tab_to" => "interview_not_attended",
                "history.sub_stage" => "crm_interview_not_attended", "history.status" => "active"
            ])->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="interview_not_attended" and sales_id=sales.id and applicants.id=applicant_id'));
            })->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($not_attended)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function($applicant){
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
            return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function($applicant){
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) {
                $content = '';
                $content .= '<p><b>DATE: </b>';
                $content .= $applicant->crm_added_date;
                $content .= '<b> TIME: </b>';
                $content .= $applicant->crm_added_time;
                $content .= '</p>';
                $content .= '<p><b>NOTE: </b>';
                $content .= $applicant->details;
                $content .= '</p>';

                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                if ($auth_user->hasPermissionTo('CRM_Not-Attended_revert-to-attended')) {
                    $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#revert_attended' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#revert_attended' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i>Revert </a>';
                }
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                                    {
                                     if ($applicant_msgs['is_read'] == 0) {
                                        $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                        $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                     }
                                    }
                                }

                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                if ($auth_user->hasPermissionTo('CRM_Not-Attended_revert-to-attended')) {
                    $sent_cv_count = Cv_note::where(['sale_id' => $applicant->sale_id, 'status' => 'active'])->count();
                    /*** Revert To Attend Modal */
                    $content .= '<div id="revert_attended' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Not Attended Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="revert_attended_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="revert_attended_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Sent CV</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<label class="col-form-label font-weight-semibold">'.$sent_cv_count.' out of '.$applicant->send_cv_limit.'</label>';
                    $content .= '</div>';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .= '<textarea name="details" id="revert_attended_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    $content .= '<button type="submit" name="back_to_attended" value="back_to_attended" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple revert_attended_submit"> Attended </button>';
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Revert To Attend Modal */
                }

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }

    public function notAttendedAction(Request $request)
    {
        date_default_timezone_set('Europe/London');

        /*** Not Attended tab */
        $revert_to_attend = $request->Input('back_to_attended');

        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
				
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';

        $sale = Sale::find($sale_id);
        if ($sale) {
            $sent_cv_count = Cv_note::where(['sale_id' => $sale_id, 'status' => 'active'])->count();
            if ($sent_cv_count < $sale->send_cv_limit) {
                if (!empty($revert_to_attend) && ($revert_to_attend == 'back_to_attended')) {
                    $audit_data['action'] = "Revert To Attend";
                    Applicant::where("id", $applicant_id)->update(['is_crm_interview_attended' => 'yes']);
                    Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "active"]);
                    $crm_notes = new Crm_note();
                    $crm_notes->applicant_id = $applicant_id;
                    $crm_notes->user_id = $auth_user;
                    $crm_notes->sales_id = $sale_id;
                    $crm_notes->details = $details;
                    $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                    $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
                    $crm_notes->moved_tab_to = "interview_attended";
                    $crm_notes->save();

                    /*** activity log
                     * $this->action_observer->action($audit_data, 'CRM > Not Attended');
                     */

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
                        $history->user_id = $auth_user;
                        $history->sale_id = $sale_id;
                        $history->stage = 'crm';
                        $history->sub_stage = 'crm_interview_attended';
                        $history->history_added_date = date("jS F Y");
                        $history->history_added_time = date("h:i A");
                        $history->save();
                        $last_inserted_history = $history->id;
                        if($last_inserted_history > 0){
                            $history_uid = md5($last_inserted_history);
                            History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                            $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV reverted Attended to Pre-Start Date Successfully
						</div>';
                            echo $html;
                        }
                    } else {
                        echo $html;
                    }
                }
            } else {
                echo
                '<div class="alert alert-danger border-0 alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        <span class="font-weight-semibold"> WHOOPS!</span> You cannot perform this action. Send CV Limit for this Sale has reached maximum!!
                    </div>';
            }
        } else {
            echo
            '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold"> WHOOPS!</span> Sale not found!!
                </div>';
        }
    }

    public function crmStartDate()
    {
        $auth_user = Auth::user();
        $crm_start_date_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->whereIn("history.sub_stage", ["crm_start_date", "crm_start_date_save", "crm_start_date_back"])
            ->whereIn("crm_notes.moved_tab_to", ["start_date", "start_date_save", "start_date_back"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

        $start_date = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'start_date',
                'history.status' => 'active'
            ])->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="start_date" and sales_id=sales.id and applicants.id=applicant_id'));
            })->whereIn('history.sub_stage', ['crm_start_date', 'crm_start_date_save', 'crm_start_date_back'])
            ->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($start_date)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function ($applicant) {
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
            return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function ($applicant) {
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_start_date_save_note) {
                $content = '';
                if(!empty($crm_start_date_save_note)) {
                    foreach ($crm_start_date_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time;
                            $content .= '</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasAnyPermission(['CRM_Start-Date_invoice','CRM_Start-Date_start-date-hold','CRM_Start-Date_save'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option"
                                 data-controls-modal="#start_date' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                 data-keyboard="false" data-toggle="modal"
								 data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                 data-applicantNameJs="' . $applicant->applicant_name . '" 
                                 data-applicantIdJs="' . $applicant->id . '"
                                 data-target="#start_date' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i> Accept </a>';
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                                    {
                                     if ($applicant_msgs['is_read'] == 0) {
                                        $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                        $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                     }
                                    }
                                }
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasAnyPermission(['CRM_Start-Date_invoice','CRM_Start-Date_start-date-hold','CRM_Start-Date_save'])) {
                    /*** Accept Modal */
                    $content .= '<div id="start_date' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Interview Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="start_date_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="start_date_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
					$content .= '<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">';
                    $content .= '<input type="hidden" name="applicant_name_chat" id="applicant_name_chat">';
                    $content .= '<input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">';
                    $content .= '<textarea name="details" id="start_date_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
					if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }
					if ($auth_user->hasPermissionTo('CRM_Rebook_attend')) {
                        $content .= '<button type="submit" name="rebook_attend" value="rebook_attend" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-blue legitRipple start_date_submit"> Attend </button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Start-Date_invoice')) {
                        $content .= '<button type="submit" name="start_date_invoice" value="start_date_invoice" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple start_date_submit"> Invoice </button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Start-Date_start-date-hold')) {
                        $content .= '<button type="submit" name="start_date_hold" value="start_date_hold" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple start_date_submit">Start Date Hold</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Start-Date_save')) {
                        $content .= '<button type="submit" name="start_date_save" value="start_date_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple start_date_submit"> Save </button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Accept Modal */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
               foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }

    public function startDateAction(Request $request)
    {
        date_default_timezone_set('Europe/London');

        /*** Start Date tab */
        $start_date_invoice = $request->Input('start_date_invoice');
        $start_date_hold = $request->Input('start_date_hold');
        $start_date_save = $request->Input('start_date_save');
		$attended = $request->Input('rebook_attend');

        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
				
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';

        if (!empty($attended) && ($attended == 'rebook_attend')) {
                    $audit_data['action'] = "Attend";
			
					//if applicant revert from start date for purpose of send cv save or quality
					$lstHistory = History::where([
						'applicant_id' => $applicant_id,
						'sale_id' => $sale_id,
						'sub_stage' => 'crm_start_date'
					])
						->whereDate('created_at', Carbon::now()->format('Y-m-d'))
						->latest()
						->first();
                    
                    // Check if last history exists and was created within the last 10 minutes
                    if ($lstHistory) {
                        $timeDifference = Carbon::now()->diffInMinutes($lstHistory->created_at);
                        
                        if ($timeDifference <= 10) {
                            // The last entry was made within 10 minutes, delete it
                            $lstHistory->delete();
                        }
                    }
			
                    Applicant::where("id", $applicant_id)->update(['is_crm_interview_attended' => 'yes', 'is_crm_request_confirm' => 'no']);
                    $crm_notes = new Crm_note();
                    $crm_notes->applicant_id = $applicant_id;
                    $crm_notes->user_id = $auth_user;
                    $crm_notes->sales_id = $sale_id;
                    $crm_notes->details = $details;
                    $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                    $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
                    $crm_notes->moved_tab_to = "interview_attended";
                    $crm_notes->save();
        
                    /*** activity log
                    $this->action_observer->action($audit_data, 'CRM > Confirmation');
                     */
        
                    $last_inserted_note = $crm_notes->id;
                    if ($last_inserted_note > 0) {
                        $crm_note_uid = md5($last_inserted_note);
                        Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
                        History::where([
                            "applicant_id" => $applicant_id,
        //                    "user_id" => $auth_user,
                            "sale_id" => $sale_id
                        ])->update(["status" => "disable"]);
                        $history = new History();
                        $history->applicant_id = $applicant_id;
                        $history->user_id = $auth_user;
                        $history->sale_id = $sale_id;
                        $history->stage = 'crm';
                        $history->sub_stage = 'crm_interview_attended';
                        $history->history_added_date = date("jS F Y");
                        $history->history_added_time = date("h:i A");
                        $history->save();
                        $last_inserted_history = $history->id;
                        if($last_inserted_history > 0){
                            $history_uid = md5($last_inserted_history);
                            History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                            $html = '<div class="alert alert-success border-0 alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                                    <span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Attended Successfully
                                </div>';
                            echo $html;
                        }
                    } else {
                        echo $html;
                    }
                } elseif (!empty($start_date_invoice) && ($start_date_invoice == 'start_date_invoice')) {
            $audit_data['action'] = "Invoice";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_invoice' => 'yes', 'is_in_crm_start_date' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "invoice";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Start Date');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_invoice';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Invoice Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif (!empty($start_date_hold) && ($start_date_hold == 'start_date_hold')) {
            $audit_data['action'] = "Start Date Hold";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_start_date_hold' => 'yes', 'is_in_crm_start_date' => 'no']);
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "start_date_hold";
            $crm_notes->save();
            Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->update(["status" => "disable"]);

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Start Date');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_start_date_hold';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Start Date Hold Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif (!empty($start_date_save) && ($start_date_save == 'start_date_save')) {
            $audit_data['action'] = "Save";
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "start_date_save";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Start Date');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_start_date_save';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span>Note saved Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        }
    }

    public function crmStartDateHold()
    {
        $auth_user = Auth::user();
        $crm_start_date_hold_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->whereIn("history.sub_stage", ["crm_start_date_hold", "crm_start_date_hold_save"])
            ->whereIn("crm_notes.moved_tab_to", ["start_date_hold", "start_date_hold_save"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

        $start_date_hold = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date', 'sales.send_cv_limit',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'start_date_hold',
                'history.status' => 'active'
            ])->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="start_date_hold" and sales_id=sales.id and applicants.id=applicant_id'));
            })->whereIn('history.sub_stage', ['crm_start_date_hold', 'crm_start_date_hold_save'])
            ->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($start_date_hold)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function ($applicant) {
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
            return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function ($applicant) {
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_start_date_hold_save_note) {
                $content = '';
                if(!empty($crm_start_date_hold_save_note)) {
                    foreach ($crm_start_date_hold_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time;
                            $content .= '</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasAnyPermission(['CRM_Start-Date-Hold_revert-start-date','CRM_Start-Date-Hold_save'])) {
                    $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#start_date_hold' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#start_date_hold' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i> Accept </a>';
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
				 if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                                    {
                                     if ($applicant_msgs['is_read'] == 0) {
                                        $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                        $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                     }
                                    }
                                }
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasAnyPermission(['CRM_Start-Date-Hold_revert-start-date','CRM_Start-Date-Hold_save'])) {
                    $sent_cv_count = Cv_note::where(['sale_id' => $applicant->sale_id, 'status' => 'active'])->count();
                    /*** Accept Modal */
                    $content .= '<div id="start_date_hold' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Interview Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="start_date_hold_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="start_date_hold_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Sent CV</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<label class="col-form-label font-weight-semibold">'.$sent_cv_count.' out of '.$applicant->send_cv_limit.'</label>';
                    $content .= '</div>';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .= '<textarea name="details" id="start_date_hold_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    if ($auth_user->hasPermissionTo('CRM_Start-Date-Hold_revert-start-date')) {
                        $content .= '<button type="submit" name="start_date_hold_to_start_date" value="start_date_hold_to_start_date" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple start_date_hold_submit">Start Date</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Start-Date-Hold_save')) {
                        $content .= '<button type="submit" name="start_date_hold_save" value="start_date_hold_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple start_date_hold_submit"> Save </button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Accept Modal */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }

    public function startDateHoldAction(Request $request)
    {
        date_default_timezone_set('Europe/London');

        /*** Start Date Hold tab */
        $start_date_hold_to_start_date = $request->Input('start_date_hold_to_start_date');
        $start_date_hold_save = $request->Input('start_date_hold_save');

        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
				
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';

        $sale = Sale::find($sale_id);
        if ($sale) {
            if (!empty($start_date_hold_to_start_date) && ($start_date_hold_to_start_date == 'start_date_hold_to_start_date')) {
                $sent_cv_count = Cv_note::where(['sale_id' => $sale_id, 'status' => 'active'])->count();
                if ($sent_cv_count < $sale->send_cv_limit) {
                    $audit_data['action'] = "Start Date";
                    Applicant::where("id", $applicant_id)->update(['is_in_crm_start_date_hold' => 'no', 'is_in_crm_start_date' => 'yes']);
                    Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "active"]);
//            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "active"]);
                    $crm_notes = new Crm_note();
                    $crm_notes->applicant_id = $applicant_id;
                    $crm_notes->user_id = $auth_user;
                    $crm_notes->sales_id = $sale_id;
                    $crm_notes->details = $details;
                    $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                    $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
                    $crm_notes->moved_tab_to = "start_date_back";
                    $crm_notes->save();

                    /*** activity log
                     * $this->action_observer->action($audit_data, 'CRM > Start Date Hold');
                     */

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
                        $history->user_id = $auth_user;
                        $history->sale_id = $sale_id;
                        $history->stage = 'crm';
                        $history->sub_stage = 'crm_start_date_back';
                        $history->history_added_date = date("jS F Y");
                        $history->history_added_time = date("h:i A");
                        $history->save();
                        $last_inserted_history = $history->id;
                        if($last_inserted_history > 0){
                            $history_uid = md5($last_inserted_history);
                            History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                            $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV reverted Start Date Successfully
						</div>';
                            echo $html;
                        }
                    } else {
                        echo $html;
                    }
                } else {
                    echo
                    '<div class="alert alert-danger border-0 alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        <span class="font-weight-semibold"> WHOOPS!</span> You cannot perform this action. Send CV Limit for this Sale has reached maximum!!
                    </div>';
                }
            } elseif (!empty($start_date_hold_save) && ($start_date_hold_save == 'start_date_hold_save')) {
                $audit_data['action'] = "Save";
                $crm_notes = new Crm_note();
                $crm_notes->applicant_id = $applicant_id;
                $crm_notes->user_id = $auth_user;
                $crm_notes->sales_id = $sale_id;
                $crm_notes->details = $details;
                $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
                $crm_notes->moved_tab_to = "start_date_hold_save";
                $crm_notes->save();

                /*** activity log
                 * $this->action_observer->action($audit_data, 'CRM > Start Date Hold');
                 */

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
                    $history->user_id = $auth_user;
                    $history->sale_id = $sale_id;
                    $history->stage = 'crm';
                    $history->sub_stage = 'crm_start_date_hold_save';
                    $history->history_added_date = date("jS F Y");
                    $history->history_added_time = date("h:i A");
                    $history->save();
                    $last_inserted_history = $history->id;
                    if($last_inserted_history > 0){
                        $history_uid = md5($last_inserted_history);
                        History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                        $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Note saved Successfully
						</div>';
                        echo $html;
                    }
                } else {
                    echo $html;
                }
            }
        } else {
            echo
            '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold"> WHOOPS!</span> Sale not found!!
                </div>';
        }
    }

    public function crmInvoice()
    {
        $auth_user = Auth::user();
        $crm_invoice_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->whereIn("history.sub_stage", ["crm_invoice", "crm_final_save"])
            ->whereIn("crm_notes.moved_tab_to", ["invoice", "final_save"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

        $invoices = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title','applicants.job_title_prof', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'invoice',
                'history.status' => 'active'
            ])->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="invoice" and sales_id=sales.id and applicants.id=applicant_id'));
            })->whereIn('history.sub_stage', ['crm_invoice', 'crm_final_save'])
            ->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($invoices)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function ($applicant) {
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
            return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function ($applicant) {
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_invoice_save_note) {
                $content = '';
                if(!empty($crm_invoice_save_note)) {
                    foreach ($crm_invoice_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time.'</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();

                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasAnyPermission(['CRM_Invoice_paid','CRM_Invoice_dispute','CRM_Invoice_save'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option"
                                 data-controls-modal="#invoice' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                 data-keyboard="false" data-toggle="modal"
								 data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                 data-applicantNameJs="' . $applicant->applicant_name . '"
                                 data-applicantIdJs="' . $applicant->id . '"
                                 data-target="#invoice' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i> Accept </a>';
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                                    {
                                     if ($applicant_msgs['is_read'] == 0) {
                                        $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                        $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                     }
                                    }
                                }

                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasAnyPermission(['CRM_Invoice_paid','CRM_Invoice_dispute','CRM_Invoice_save'])) {
                    /*** Accept Modal */
                    $content .= '<div id="invoice' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Interview Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="invoice_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="invoice_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
					$content .=  '<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">';
                    $content .=  '<input type="hidden" name="applicant_name_chat" id="applicant_name_chat">';
                    $content .=  '<input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">';
                    $content .= '<textarea name="details" id="invoice_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
					if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Invoice_paid')) {
                        //$content .= '<button type="submit" name="paid" value="paid" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple invoice_submit"> Paid </button>';
						                        $content .= '<button type="submit" name="invoice_sent" value="invoice_sent" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple invoice_submit"> Send Invoice </button>';

                    }
					 if ($auth_user->hasPermissionTo('CRM_Invoice_revert')) {
                        $content .= '<button type="submit" name="revert_invoice" value="revert_invoice" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple invoice_submit"> Revert Invoice </button>';
                    }

                    if ($auth_user->hasPermissionTo('CRM_Invoice_dispute')) {
                        $content .= '<button type="submit" name="dispute" value="dispute" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple invoice_submit"> Dispute </button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Invoice_save')) {
                        $content .= '<button type="submit" name="final_save" value="final_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple invoice_submit"> Save </button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Accept Modal */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }
	

	public function crmInvoiceFinalSent()
    {
        $auth_user = Auth::user();
        $crm_invoice_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->whereIn("history.sub_stage", ["crm_invoice_sent", "crm_final_save"])
            ->whereIn("crm_notes.moved_tab_to", ["invoice_sent", "final_save"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

        $invoices = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'invoice_sent',
                'history.status' => 'active'
            ])->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="invoice_sent" and sales_id=sales.id and applicants.id=applicant_id'));
            })->whereIn('history.sub_stage', ['crm_invoice_sent', 'crm_final_save'])
            ->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($invoices)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function ($applicant) {
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
            return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function ($applicant) {
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details_invoice_sent'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details_invoice_sent'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details_invoice_sent'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_invoice_save_note) {
                $content = '';
                if(!empty($crm_invoice_save_note)) {
                    foreach ($crm_invoice_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time.'</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();

                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasAnyPermission(['CRM_Invoice_paid','CRM_Invoice_dispute','CRM_Invoice_save'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option"
                                           data-controls-modal="#invoice_sent' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                           data-applicantNameJs="' . $applicant->applicant_name . '"
                                           data-applicantIdJs="' . $applicant->id . '"
                                           data-target="#invoice_sent' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i> Accept </a>';
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                                    {
                                     if ($applicant_msgs['is_read'] == 0) {
                                        $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                        $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                     }
                                    }
                                }
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasAnyPermission(['CRM_Invoice_paid','CRM_Invoice_dispute','CRM_Invoice_save'])) {
                    /*** Accept Modal */
                    $content .= '<div id="invoice_sent' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Interview Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="invoice_form_sent' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="invoice_alert_sent' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .=  '<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">';
                    $content .=  '<input type="hidden" name="applicant_name_chat" id="applicant_name_chat">';
                    $content .=  '<input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">';
                    $content .= '<textarea name="details" id="invoice_details_sent' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Invoice_paid')) {
                        $content .= '<button type="submit" name="paid" value="paid" data-app_sale_sent="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple invoice_submit_sent"> Paid </button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Invoice_dispute')) {
                        $content .= '<button type="submit" name="dispute" value="dispute" data-app_sale_sent="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple invoice_submit_sent"> Dispute </button>';
                    }
                    // if ($auth_user->hasPermissionTo('CRM_Invoice_save')) {
                    //     $content .= '<button type="submit" name="final_save" value="final_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple invoice_submit"> Save </button>';
                    // }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Accept Modal */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
               foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }

    public function invoiceAction(Request $request)
    {
        date_default_timezone_set('Europe/London');

        /*** Invoice tab */
        $paid = $request->Input('paid');
		$invoice_sent = $request->Input('invoice_sent');
		$invoice_revert = $request->Input('revert_invoice');
        $dispute = $request->Input('dispute');
        $final_save = $request->Input('final_save');

        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
				
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';
		


        if (!empty($paid) && ($paid == 'paid')) {
            $audit_data['action'] = "Paid";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_paid' => 'yes', 'is_in_crm_invoice' => 'no','is_in_crm_invoice_sent' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "paid";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Invoice');
             */

            $last_inserted_note = $crm_notes->id;
            if ($last_inserted_note > 0) {
                $crm_note_uid = md5($last_inserted_note);
                Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
                Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "paid"]);
                History::where([
                    "applicant_id" => $applicant_id,
                    "sale_id" => $sale_id
                ])->update(["status" => "disable"]);
                $history = new History();
                $history->applicant_id = $applicant_id;
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_paid';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();

//                $dispatcher = Applicant::getEventDispatcher();
//                Applicant::unsetEventDispatcher();
                $update_columns = ['paid_status' => 'close', 'paid_timestamp' => Carbon::now()];
                $update_applicant = Applicant::where('id', '=', $applicant_id)->update($update_columns);
//                Applicant::setEventDispatcher($dispatcher);

                if ($update_applicant) {
                    /*** activity log */
                    $action_observer = new ActionObserver();
                    $action_observer->changeCvStatus($applicant_id, $update_columns, 'Closed');
                }

                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Paid Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        }elseif (!empty($invoice_sent) && ($invoice_sent == 'invoice_sent')) {
            $audit_data['action'] = "invoice_sent";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_invoice' => 'no', 'is_in_crm_invoice_sent' => 'yes']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "invoice_sent";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Invoice');
             */

            $last_inserted_note = $crm_notes->id;
            if ($last_inserted_note > 0) {
                $crm_note_uid = md5($last_inserted_note);
                Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
                // Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "paid"]);
                History::where([
                    "applicant_id" => $applicant_id,
                    "sale_id" => $sale_id
                ])->update(["status" => "disable"]);
                $history = new History();
                $history->applicant_id = $applicant_id;
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_invoice_sent';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();

                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to invoice sent Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        }elseif (!empty($invoice_revert) && ($invoice_revert == 'revert_invoice')) {
            $audit_data['action'] = "Start Date";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_start_date' => 'yes', 'is_crm_interview_attended' => 'pending', 'is_in_crm_invoice' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "start_date";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Attended To Start Date');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_start_date';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Start Date Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        }elseif (!empty($dispute) && ($dispute == 'dispute')) {
            $audit_data['action'] = "Dispute";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_dispute' => 'yes', 'is_in_crm_invoice' => 'no','is_in_crm_invoice_sent' => 'no']);
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "dispute";
            $crm_notes->save();
            Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->update(["status" => "disable"]);

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Invoice');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_dispute';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Dispute Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif (!empty($final_save) && ($final_save == 'final_save')) {
            $audit_data['action'] = "Save";
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "final_save";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Invoice');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_final_save';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Note saved Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        }
    }
	

	public function invoiceActionSent(Request $request)
    {
        date_default_timezone_set('Europe/London');

        /*** Invoice tab */
        $paid = $request->Input('paid');
        // $invoice_sent = $request->Input('invoice_sent');
        $dispute = $request->Input('dispute');
        $final_save = $request->Input('final_save');

        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
				
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';
  

        if (!empty($paid) && ($paid == 'paid')) {
            $audit_data['action'] = "Paid";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_paid' => 'yes', 'is_in_crm_invoice' => 'no','is_in_crm_invoice_sent' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "paid";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Invoice');
             */

            $last_inserted_note = $crm_notes->id;
            if ($last_inserted_note > 0) {
                $crm_note_uid = md5($last_inserted_note);
                Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
                Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "paid"]);
                History::where([
                    "applicant_id" => $applicant_id,
                    "sale_id" => $sale_id
                ])->update(["status" => "disable"]);
                $history = new History();
                $history->applicant_id = $applicant_id;
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_paid';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();

//                $dispatcher = Applicant::getEventDispatcher();
//                Applicant::unsetEventDispatcher();
                $update_columns = ['paid_status' => 'close', 'paid_timestamp' => Carbon::now()];
                $update_applicant = Applicant::where('id', '=', $applicant_id)->update($update_columns);
//                Applicant::setEventDispatcher($dispatcher);

                if ($update_applicant) {
                    /*** activity log */
                    $action_observer = new ActionObserver();
                    $action_observer->changeCvStatus($applicant_id, $update_columns, 'Closed');
                }

                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Paid Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        }elseif (!empty($dispute) && ($dispute == 'dispute')) {
            $audit_data['action'] = "Dispute";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_dispute' => 'yes', 'is_in_crm_invoice' => 'no','is_in_crm_invoice_sent' => 'no']);
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "dispute";
            $crm_notes->save();
            Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->update(["status" => "disable"]);

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Invoice');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_dispute';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Dispute Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif (!empty($final_save) && ($final_save == 'final_save')) {
            $audit_data['action'] = "Save";
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "final_save";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Invoice');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_final_save';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Note saved Successfully
						</div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        }
    }

    public function crmDispute()
    {
        $auth_user = Auth::user();
        $dispute = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date', 'sales.send_cv_limit',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'dispute',
                'history.sub_stage' => 'crm_dispute', 'history.status' => 'active'
            ])->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="dispute" and sales_id=sales.id and applicants.id=applicant_id'));
            })->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($dispute)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function ($applicant) {
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
            return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function ($applicant) {
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) {
                $content = '';
                $content .= '<p><b>DATE: </b>';
                $content .= $applicant->crm_added_date;
                $content .= ' <b> TIME: </b>';
                $content .= $applicant->crm_added_time.'</p>';
                $content .= '<p><b>NOTE: </b>';
                $content .= $applicant->details.'</p>';
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                if ($auth_user->hasPermissionTo('CRM_Dispute_revert-invoice')) {
                    $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#revert_invoice' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#revert_invoice' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i> Revert </a>';
                }
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                                    {
                                     if ($applicant_msgs['is_read'] == 0) {
                                        $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                        $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                     }
                                    }
                                }
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
               foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                if ($auth_user->hasPermissionTo('CRM_Dispute_revert-invoice')) {
                    $sent_cv_count = Cv_note::where(['sale_id' => $applicant->sale_id, 'status' => 'active'])->count();
                    /*** Revert Invoice Modal */
                    $content .= '<div id="revert_invoice' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Invoice Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="revert_invoice_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="revert_invoice_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Sent CV</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<label class="col-form-label font-weight-semibold">'.$sent_cv_count.' out of '.$applicant->send_cv_limit.'</label>';
                    $content .= '</div>';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .= '<textarea name="details" id="revert_invoice_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    $content .= '<button type="submit" name="dispute_revert_invoice" value="dispute_revert_invoice" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple revert_invoice_submit"> Invoice </button>';
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Revert Invoice Modal */
                }

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }

    public function disputeAction(Request $request)
    {
        date_default_timezone_set('Europe/London');

        /*** Invoice tab */
        $dispute_revert_invoice = $request->Input('dispute_revert_invoice');

        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
				
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';

        $sale = Sale::find($sale_id);
        if ($sale) {
            $sent_cv_count = Cv_note::where(['sale_id' => $sale_id, 'status' => 'active'])->count();
            if ($sent_cv_count < $sale->send_cv_limit) {
                if (!empty($dispute_revert_invoice) && ($dispute_revert_invoice == 'dispute_revert_invoice')) {
                    $audit_data['action'] = "Invoice";
                    Cv_note::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id])->update(["status" => "active"]);
                    Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->whereIn('moved_tab_to', ['cv_sent', 'cv_sent_saved', 'cv_sent_request', 'request_save', 'request_confirm', 'prestart_save', 'start_date', 'start_date_save', 'start_date_back', 'interview_attended', 'interview_save'])->update(["status" => "active"]);
                    $crm_notes = new Crm_note();
                    $crm_notes->applicant_id = $applicant_id;
                    $crm_notes->user_id = $auth_user;
                    $crm_notes->sales_id = $sale_id;
                    $crm_notes->details = $details;
                    $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                    $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
                    $crm_notes->moved_tab_to = "invoice";
                    $crm_notes->save();

                    /*** activity log
                     * $this->action_observer->action($audit_data, 'CRM > Invoice');
                     */

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
                        $history->user_id = $auth_user;
                        $history->sale_id = $sale_id;
                        $history->stage = 'crm';
                        $history->sub_stage = 'crm_invoice';
                        $history->history_added_date = date("jS F Y");
                        $history->history_added_time = date("h:i A");
                        $history->save();
                        $last_inserted_history = $history->id;
                        if($last_inserted_history > 0){
                            $history_uid = md5($last_inserted_history);
                            History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                            $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV reverted Invoice Successfully
						</div>';
                            echo $html;
                        } else {
                            echo $html;
                        }
                    } else {
                        echo $html;
                    }
                }
            } else {
                echo
                '<div class="alert alert-danger border-0 alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        <span class="font-weight-semibold"> WHOOPS!</span> You cannot perform this action. Send CV Limit for this Sale has reached maximum!!
                    </div>';
            }
        } else {
            echo
            '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold"> WHOOPS!</span> Sale not found!!
                </div>';
        }
    }

    public function crmPaid()
    {
        $auth_user = Auth::user();
        $paid = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_added_date', 'crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone', 'applicants.paid_status', 'applicants.paid_timestamp',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                'applicants.status' => 'active',
                'crm_notes.moved_tab_to' => 'paid',
                'history.sub_stage' => 'crm_paid', 'history.status' => 'active'
            ])->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="paid" and sales_id=sales.id and applicants.id=applicant_id'));
            })
            ->orderBy("crm_notes.created_at","DESC");

        return datatables()->of($paid)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function ($applicant) {
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
            return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function ($applicant) {
                if ($applicant->paid_status == 'close')
                     return strtoupper($applicant->applicant_postcode);
                else
                    return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) {
                $content = '';
                $content .= '<p><b>DATE: </b>';
                $content .= $applicant->crm_added_date;
                $content .= ' <b> TIME: </b>';
                $content .= $applicant->crm_added_time.'</p>';
                $content .= '<p><b>NOTE: </b>';
                $content .= $applicant->details.'</p>';
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
				$applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasPermissionTo('CRM_Paid_open-close-cv')) {
                    $paid_status_button = ($applicant->paid_status == 'close') ? 'Open' : 'Close';
                    $content .= '<a href="#" class="dropdown-item"
                       data-controls-modal="#paid_status' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                       data-keyboard="false" data-toggle="modal"
                       data-target="#paid_status' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i>' . $paid_status_button . '</a>';
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
				if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                                    {
                                     if ($applicant_msgs['is_read'] == 0) {
                                        $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                                        $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                                     }
                                    }
                                }
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasPermissionTo('CRM_Paid_open-close-cv')) {
                    /*** Paid Status Modal */
                    $content .= '<div id="paid_status' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">' . $paid_status_button . ' ' . $applicant->applicant_name . '\'s CV</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form method="POST" id="paid_status_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="paid_status_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $current_paid_status = ($paid_status_button == 'Open') ? 'closed' : 'opened';
                    $paid_status_timestamp = Carbon::parse($applicant->paid_timestamp);
                    $content .= '<label class="col-form-label col-sm-12">Applicant CV has been ' . $current_paid_status . ' since ' . $paid_status_timestamp->format('jS F Y') . ' (' . $paid_status_timestamp->diff(Carbon::now())->format('%y years, %m months and %d days') . '). Are you sure you want to ' . $paid_status_button . ' it?</label>';
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    $content .= '<button type="button" class="btn bg-orange-800 legitRipple" data-dismiss="modal">Cancel</button>';
                    $content .= '<button type="submit" name="paid_status" value="' . $paid_status_button . '" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple paid_status_submit"> ' . $paid_status_button . ' </button>';
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Paid Status Modal */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                return $content;
            })
			->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $row_class = '';
                if(!empty($applicant_msg))
                {
                 if ($applicant_msg['is_read'] == 0) {
                     $row_class .= 'blink';
                 }
                }
                
                 return $row_class;
            }
             })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }

    public function paidAction(Request $request)
    {
        date_default_timezone_set('Europe/London');

        /*** Paid tab */
        $paid_status = $request->Input('paid_status');

        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <span class="font-weight-semibold"> WHOOPS!</span> Something Went Wrong!!
        </div>';
        $update_paid_status = NULL;
        $msg = '';
        if (!empty($paid_status)) {
            if ($paid_status == 'Open') {
                $audit_data['action'] = "Open Applicant CV";
                $update_paid_status = 'open';
                $msg = 'Opened';
            } elseif ($paid_status == 'Close') {
                $audit_data['action'] = "Close Applicant CV";
                $update_paid_status = 'close';
                $msg = 'Closed';
            }
            $update_columns = ['paid_status' => $update_paid_status, 'paid_timestamp' => Carbon::now()];
            $updated = Applicant::where('id', $applicant_id)->update($update_columns);
            if ($updated) {

                /*** activity log */
                $action_observer = new ActionObserver();
                $action_observer->changeCvStatus($applicant_id, $update_columns, $msg);

                $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">Success!</span> Applicant CV ' . $msg . ' Successfully
						</div>';
            }
            echo $html;
        } else {
            echo $html;
        }
    }
	
	public function openToPaidApplicants(){
        $applicants = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_added_date', 'crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone', 'applicants.paid_status', 'applicants.paid_timestamp',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type', 'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name',
                'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline', 'units.contact_email', 'units.website')
            ->where([
                'applicants.status' => 'active',
                'applicants.paid_status' => 'close',
                'crm_notes.moved_tab_to' => 'paid',
                'history.sub_stage' => 'crm_paid',
                'history.status' => 'active'
            ])
            ->whereDate('applicants.paid_timestamp', '<', Carbon::now()->subMonths(5))
            ->whereDate('crm_notes.updated_at', '<', Carbon::now()->subMonths(5))
            ->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="paid" and sales_id=sales.id and applicants.id=applicant_id'));
            })
            ->orderBy("crm_notes.updated_at","DESC")
            ->get();

        $updated = 0;
        foreach($applicants as $applicant){
            $update_paid_status = 'open';
            $msg = 'Opened';
            $update_columns = ['paid_status' => $update_paid_status, 'paid_timestamp' => Carbon::now()];
            $updated = Applicant::where('id', $applicant->id)->update($update_columns);
            if($updated) {
                $action_observer = new ActionObserver();
                $action_observer->changeCvStatus($applicant->id, $update_columns, $msg);
            }
        }
        if(count($applicants) > 0) {
            if ($updated) {
                $response = ['success' => true, 'message' => 'Applicants open successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Something went wrong, Please try again'];
            }
        }else{
            $response = ['success' => false, 'message' => 'No record found'];
        }
        return json_encode($response);
    }

    public function getRevertToCvSent($revert_id, $current_tab)
    {
        if ($current_tab == "applicantWithSentCv") {
            Applicant::where("id", $revert_id)->update(['is_cv_in_quality' => 'yes', 'is_interview_confirm' => 'no']);
            return redirect()->route('index');
        } elseif ($current_tab == "applicantWithRequestCv") {
            Applicant::where("id", $revert_id)->update(['is_in_crm_request' => 'no', 'is_interview_confirm' => 'yes']);
            return redirect()->route('index');
        } elseif ($current_tab == "applicantWithRejectCv") {
            Applicant::where("id", $revert_id)->update(['is_in_crm_reject' => 'no', 'is_interview_confirm' => 'yes']);
            return redirect()->route('index');
        } elseif ($current_tab == "applicantWithCvToAttend") {
            Applicant::where("id", $revert_id)->update(['is_crm_interview_attended' => 'yes']);
            return redirect()->route('index');
        } elseif ($current_tab == "applicantWithAttendPreStartCv") {
            Applicant::where("id", $revert_id)->update(['is_crm_interview_attended' => 'pending', 'is_crm_request_confirm' => 'yes']);
            return redirect()->route('index');
        } elseif ($current_tab == "applicantWithConfirmCv") {
            Applicant::where("id", $revert_id)->update(['is_in_crm_request' => 'yes', 'is_crm_request_confirm' => 'no']);
            Interview::where("applicant_id", $revert_id)->update(['status' => 'disable']);
            return redirect()->route('index');
        } elseif ($current_tab == "applicantWithDisputeCv") {
            Applicant::where("id", $revert_id)->update(['is_in_crm_dispute' => 'yes', 'is_in_crm_invoice' => 'no']);
            return redirect()->route('index');
        } elseif ($current_tab == "applicantWithInvoicePendingCv") {
            Applicant::where("id", $revert_id)->update(['is_in_crm_paid' => 'no', 'is_in_crm_invoice' => 'yes']);
            return redirect()->route('index');
        } elseif ($current_tab == "applicantWithInvoiceCv") {
            Applicant::where("id", $revert_id)->update(['is_in_crm_invoice' => 'no', 'is_in_crm_start_date' => 'yes']);
            return redirect()->route('index');
        } elseif ($current_tab == "applicantWithStartHoldCv") {
            Applicant::where("id", $revert_id)->update(['is_in_crm_start_date_hold' => 'no', 'is_in_crm_start_date' => 'yes']);
            return redirect()->route('index');
        } elseif ($current_tab == "applicantWithStartDateCv") {
            Applicant::where("id", $revert_id)->update(['is_in_crm_start_date' => 'no', 'is_crm_interview_attended' => 'yes']);
            return redirect()->route('index');
        } elseif ($current_tab == "applicantWithRejectByRequestCv") {
            Applicant::where("id", $revert_id)->update(['is_in_crm_request_reject' => 'no', 'is_in_crm_request' => 'yes']);
            return redirect()->route('index');
        }

    }
	 public function store_applicant_revert_manual()
    {
		 echo 'can not process';exit();
        $applicant_id = 94440;
        $sale_id = 10658;
        $auth_user = Auth::user()->id;
        $details = 'Applicant Revert';
            $audit_data['action'] = "Reject";
            $audit_data['reject_reason'] = $reject_reason = 'position_filled';
            Applicant::where("id", $applicant_id)->update(['is_in_crm_reject' => 'yes',
                'is_interview_confirm' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->status = 'disable';
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "cv_sent_reject";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Sent CVs');
             */

            $last_inserted_note = $crm_notes->id;
            if ($last_inserted_note > 0) {
                $crm_note_uid = md5($last_inserted_note);
                Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
                $crm_rejected_cv = new Crm_rejected_cv();
                $crm_rejected_cv->applicant_id = $applicant_id;
                $crm_rejected_cv->sale_id = $sale_id;
                $crm_rejected_cv->user_id = $auth_user;
                $crm_rejected_cv->crm_note_id = $last_inserted_note;
                $crm_rejected_cv->reason = $reject_reason;
                $crm_rejected_cv->crm_rejected_cv_note = $details;
                $crm_rejected_cv->crm_rejected_cv_date = date("jS F Y");
                $crm_rejected_cv->crm_rejected_cv_time = date("h:i A");
                $crm_rejected_cv->save();
                $last_crm_reject_id = $crm_rejected_cv->id;
                $crm_last_insert_id = md5($last_crm_reject_id);
                Crm_rejected_cv::where("id", $last_crm_reject_id)->update(['crm_rejected_cv_uid' => $crm_last_insert_id]);
                Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
                Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
                History::where([
                    "applicant_id" => $applicant_id,
                    "sale_id" => $sale_id
                ])->update(["status" => "disable"]);
                $history = new History();
                $history->applicant_id = $applicant_id;
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_reject';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                            <span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV rejected successfully
                        </div>';
                    echo 'success';
                }
            } else {
                echo 'error';
            }
    }

    public function store(Request $request)
    {
        date_default_timezone_set('Europe/London');
        
        $cv_sent_reject_value = $request->Input('cv_sent_reject');
        $cv_sent_request_value = $request->Input('cv_sent_request');
        $cv_sent_save_value = $request->Input('cv_sent_save');
        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_hidden_id');

        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('job_hidden_id');
		
		$dateTime = Carbon::now();
		$current_date =  $dateTime->format('d-m-Y');
		$current_time = $dateTime->format('h:i A');
				
		if(auth()->user()->id == '101'){
        	$audit_data['details'] = $details = $request->Input('details');
		}else{
			$audit_data['details'] = $details = $request->Input('details') . ' --- By: ' . auth()->user()->name;
		}

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';

        if (!empty($cv_sent_save_value) && ($cv_sent_save_value == 'cv_sent_save')) {
			if(isset($request->request_from) && $request->request_from == 'crm_open_cv'){
                Cv_note::where([
                    "applicant_id" => $applicant_id, 
                    "sale_id" => $sale_id])
                    ->update(["status" => "disable"]);

                $cv_note = new Cv_note();
                $cv_note->sale_id = $sale_id;
                $cv_note->user_id = $auth_user;
                $cv_note->applicant_id = $applicant_id;
                $audit_data['detail_note'] = $cv_note->details = $details;
                $audit_data['added_date'] = $cv_note->send_added_date = date("jS F Y");
                $audit_data['added_time'] = $cv_note->send_added_time = date("h:i A");
                $cv_note->save();
                
            }else{
				$audit_data['action'] = "Save";
				$crm_notes = new Crm_note();
				$crm_notes->applicant_id = $applicant_id;
				$crm_notes->user_id = $auth_user;
				$crm_notes->sales_id = $sale_id;
				$crm_notes->details = $details;
				$audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
				$audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
				$crm_notes->moved_tab_to = "cv_sent_saved";
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
					$history->user_id = $auth_user;
					$history->sale_id = $sale_id;
					$history->stage = 'crm';
					$history->sub_stage = 'crm_save';
					$history->history_added_date = date("jS F Y");
					$history->history_added_time = date("h:i A");
					$history->save();

					$last_inserted_history = $history->id;
					if($last_inserted_history > 0){
						$history_uid = md5($last_inserted_history);
						History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
						$html = '<div class="alert alert-success border-0 alert-dismissible">
								<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
								<span class="font-weight-semibold">'.$request->input('module').'</span> Note saved Successfully
							</div>';
						echo $html;
					}

				} else {
					echo $html;
				}
			}
			

        } elseif (!empty($cv_sent_request_value) && ($cv_sent_request_value == 'cv_sent_request')) {
            $audit_data['action'] = "Request";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_request' => 'yes', 'is_interview_confirm' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "cv_sent_request";
            $crm_notes->save();
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id, "moved_tab_to" => "cleared"])->update(["status" => "disable"]);

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Sent CVs');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_request';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
				
				if (isset($request->request_from) && $request->request_from == 'crm_open_cv') {
                    Quality_notes::where([
                        "applicant_id" => $applicant_id,
                        "sale_id" => $sale_id,
                        "moved_tab_to" => "cv_hold",
						'status' => 'active'
                    ])
                        ->update(["status" => "disable"]);
                }
				
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                            <span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV moved to Request successfully
                        </div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        } elseif (!empty($cv_sent_reject_value) && ($cv_sent_reject_value == 'cv_sent_reject')) {
            $audit_data['action'] = "Reject";
            $audit_data['reject_reason'] = $reject_reason = $request->Input('reject_reason');
            Applicant::where("id", $applicant_id)->update(['is_in_crm_reject' => 'yes',
                'is_interview_confirm' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->status = 'disable';
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "cv_sent_reject";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Sent CVs');
             */

            $last_inserted_note = $crm_notes->id;
            if ($last_inserted_note > 0) {
                $crm_note_uid = md5($last_inserted_note);
                Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
				
                $crm_rejected_cv = new Crm_rejected_cv();
                $crm_rejected_cv->applicant_id = $applicant_id;
                $crm_rejected_cv->sale_id = $sale_id;
                $crm_rejected_cv->user_id = $auth_user;
                $crm_rejected_cv->crm_note_id = $last_inserted_note;
                $crm_rejected_cv->reason = $reject_reason;
                $crm_rejected_cv->crm_rejected_cv_note = $details;
                $crm_rejected_cv->crm_rejected_cv_date = date("jS F Y");
                $crm_rejected_cv->crm_rejected_cv_time = date("h:i A");
                $crm_rejected_cv->save();
				
                $last_crm_reject_id = $crm_rejected_cv->id;
                $crm_last_insert_id = md5($last_crm_reject_id);
                Crm_rejected_cv::where("id", $last_crm_reject_id)->update(['crm_rejected_cv_uid' => $crm_last_insert_id]);
                Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
                Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
                History::where([
                    "applicant_id" => $applicant_id,
                    "sale_id" => $sale_id
                ])->update(["status" => "disable"]);
				
                $history = new History();
                $history->applicant_id = $applicant_id;
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_reject';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
				
				if (isset($request->request_from) && $request->request_from == 'crm_open_cv') {
                    Quality_notes::where([
                        "applicant_id" => $applicant_id,
                        "sale_id" => $sale_id,
                        "moved_tab_to" => "cv_hold",
						'status' => 'active'
                    ])
                        ->update(["status" => "disable"]);
                }
				
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    $html = '<div class="alert alert-success border-0 alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                            <span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV rejected successfully
                        </div>';
                    echo $html;
                }
            } else {
                echo $html;
            }
        }

      	// simple call code
        /*** Sent CVs tab */
        $cv_sent_reject_value = $request->Input('cv_sent_reject');
        $cv_sent_request_value = $request->Input('cv_sent_request');
        $cv_sent_save_value = $request->Input('cv_sent_save');

        /*** Rejected CV tab */
        $rejected_cv_revert_sent_cvs_value = $request->Input('rejected_cv_revert_sent_cvs');

        /*** Request tab */
        $request_reject = $request->Input('request_reject');
        $request_to_confirm = $request->Input('request_to_confirm');
        $request_to_save = $request->Input('request_to_save');

        /*** Rejected By Request tab */
        $rejected_request_revert_to_sent_cvs = $request->input('rejected_request_revert_to_sent_cvs');
        $rejected_request_revert_to_request = $request->input('rejected_request_revert_to_request');

        /*** Interview Confirmation tab */
        $interview_not_attend = $request->Input('interview_not_attend');
        $interview_attend = $request->Input('interview_attend');
        $interview_save = $request->Input('interview_save');
        $confirm_revert_request = $request->Input('confirm_revert_request');

        /*** Not Attended tab */
        $revert_to_attend = $request->Input('back_to_attended');

        /*** Attended tab */
        $start_date = $request->Input('start_date');
        $prestart_save = $request->Input('prestart_save');

        /*** Start Date tab */
        $start_date_invoice = $request->Input('start_date_invoice');
        $start_date_hold = $request->Input('start_date_hold');
        $start_date_save = $request->Input('start_date_save');

        /*** Start Date Hold tab */
        $start_date_hold_to_start_date = $request->Input('start_date_hold_to_start_date');
        $start_date_hold_save = $request->Input('start_date_hold_save');

        /*** Invoice tab */
        $paid = $request->Input('paid');
        $dispute = $request->Input('dispute');
        $final_save = $request->Input('final_save');

        if (!empty($cv_sent_save_value)) {
			if(isset($request->request_from) && $request->request_from == 'crm_open_cv'){
                Cv_note::where([
                    "applicant_id" => $applicant_id, 
                    "sale_id" => $sale_id])
                    ->update(["status" => "disable"]);
                    
                $cv_note = new Cv_note();
                $cv_note->sale_id = $sale_id;
                $cv_note->user_id = $auth_user;
                $cv_note->applicant_id = $applicant_id;
                $audit_data['detail_note'] = $cv_note->details = $details;
                $audit_data['added_date'] = $cv_note->send_added_date = date("jS F Y");
                $audit_data['added_time'] = $cv_note->send_added_time = date("h:i A");
                $cv_note->save();
                
            }else{
				$audit_data['action'] = "Save";
				$crm_notes = new Crm_note();
				$crm_notes->applicant_id = $applicant_id;
				$crm_notes->user_id = $auth_user;
				$crm_notes->sales_id = $sale_id;
				$crm_notes->details = $details;
				$audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
				$audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
				$crm_notes->moved_tab_to = "cv_sent_saved";
				$crm_notes->save();

				/*** activity log
				$this->action_observer->action($audit_data, 'CRM > Sent CVs');
				 */

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
					$history->user_id = $auth_user;
					$history->sale_id = $sale_id;
					$history->stage = 'crm';
					$history->sub_stage = 'crm_save';
					$history->history_added_date = date("jS F Y");
					$history->history_added_time = date("h:i A");
					$history->save();

					$last_inserted_history = $history->id;
					if($last_inserted_history > 0){
						$history_uid = md5($last_inserted_history);
						History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
						return redirect()->route('index');
					}

				} else {
					return redirect()->route('index');
				}
			}
        } elseif (!empty($cv_sent_request_value)) {
            $audit_data['action'] = "Request";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_request' => 'yes', 'is_interview_confirm' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "cv_sent_request";
            $crm_notes->save();
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id, "moved_tab_to" => "cleared"])->update(["status" => "disable"]);

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Sent CVs');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_request';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
				
				if (isset($request->request_from) && $request->request_from == 'crm_open_cv') {
                    Quality_notes::where([
                        "applicant_id" => $applicant_id,
                        "sale_id" => $sale_id,
                        "moved_tab_to" => "cv_hold",
						'status' => 'active'
                    ])
                        ->update(["status" => "disable"]);
                }
				
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        } elseif (!empty($cv_sent_reject_value)) {
            $audit_data['action'] = "Reject";
            $audit_data['reject_reason'] = $reject_reason = $request->Input('reject_reason');
            Applicant::where("id", $applicant_id)->update(['is_in_crm_reject' => 'yes',
                'is_interview_confirm' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $crm_notes->status = 'disable';
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "cv_sent_reject";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Sent CVs');
             */

            $last_inserted_note = $crm_notes->id;
            if ($last_inserted_note > 0) {
                $crm_note_uid = md5($last_inserted_note);
                Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
                $crm_rejected_cv = new Crm_rejected_cv();
                $crm_rejected_cv->applicant_id = $applicant_id;
                $crm_rejected_cv->sale_id = $sale_id;
                $crm_rejected_cv->user_id = $auth_user;
                $crm_rejected_cv->crm_note_id = $last_inserted_note;
                $crm_rejected_cv->reason = $reject_reason;
                $crm_rejected_cv->crm_rejected_cv_note = $details;
                $crm_rejected_cv->crm_rejected_cv_date = date("jS F Y");
                $crm_rejected_cv->crm_rejected_cv_time = date("h:i A");
                $crm_rejected_cv->save();
				
                $last_crm_reject_id = $crm_rejected_cv->id;
                $crm_last_insert_id = md5($last_crm_reject_id);
                Crm_rejected_cv::where("id", $last_crm_reject_id)->update(['crm_rejected_cv_uid' => $crm_last_insert_id]);
                Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
                Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
                History::where([
                    "applicant_id" => $applicant_id,
                    "sale_id" => $sale_id
                ])->update(["status" => "disable"]);
				
                $history = new History();
                $history->applicant_id = $applicant_id;
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_reject';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
				
				 if (isset($request->request_from) && $request->request_from == 'crm_open_cv') {
                    Quality_notes::where([
                        "applicant_id" => $applicant_id,
                        "sale_id" => $sale_id,
                        "moved_tab_to" => "cv_hold",
						'status' => 'active'
                    ])
                        ->update(["status" => "disable"]);
                }
				
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        } elseif (!empty($rejected_cv_revert_sent_cvs_value)) {
            $crm_note_id = @Crm_note::where(["applicant_id" => $applicant_id,"sales_id" => $sale_id, 'moved_tab_to' => 'cv_sent_reject'])
				->select('id')->latest()->first()->id;
			
            Crm_rejected_cv::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id, 'crm_note_id' => $crm_note_id])
				->update(["status" => "disable"]);
			
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])
				->update(["status" => "active"]);
			
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])
				->update(["status" => "active"]);
			
            Crm_note::where(["applicant_id" => $applicant_id,"sales_id" => $sale_id])
				->whereIn("moved_tab_to", ["cv_sent", "cv_sent_saved"])
				->update(["status" => "disable"]);
			
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "cv_sent";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Rejected CV revert to Sent CVs');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_save';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        } elseif (!empty($request_reject)) {
            $audit_data['action'] = "Reject";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_request_reject' => 'yes', 'is_in_crm_request' => 'no']);
            Interview::where(["applicant_id" => $applicant_id, "sale_id" => $sale_id])->update(['status' => 'disable']);
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "request_reject";
            $crm_notes->save();
            Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->update(["status" => "disable"]);

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Request');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_request_reject';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
				
				$email_to = $request->input('email_address');
  				$email_subject = $request->input('email_subject');
                $email_body = $request->input('email_body');

                $sale = Sale::where('id',$sale_id)
                            ->select('head_office_unit')
                            ->first();

              

                $applicantRecord = Applicant::where("id", $applicant_id)
                    ->select('applicant_name')
                    ->first();

                $applicant_name = $applicantRecord ? ucwords(strtolower($applicantRecord->applicant_name)) : '';

                Mail::send([],[], function($message) use ($email_body, $email_subject ,$email_to) {
                    $message->from('customerservice@kingsburypersonnel.com', 'Kingsbury Personnel Ltd');
                    $message->to($email_to);
                    $message->subject($email_subject);
                    $message->setBody($email_body, 'text/html');
                });
                if (Mail::failures()) {
                    return 'error';
                }
                else
                {
                    $email_from = 'customerservice@kingsburypersonnel.com';
                    $email_sent_to_cc ='';
                    $subject = $email_subject;
                    $action_name = 'Request Reject';
                    $dbSaveEmail = $this->saveSentEmails($email_to, $email_sent_to_cc, $email_from, $subject, $email_body, $action_name);
                  //  return 'success';
                }
			
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
				
				
            } else {
                return redirect()->route('index');
            }
			
        } elseif (!empty($request_to_confirm)) {
            $audit_data['action'] = "Confirm";
            Applicant::where("id", $applicant_id)->update(['is_crm_request_confirm' => 'yes', 'is_in_crm_request' => 'no', 'is_in_crm_request_reject' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "request_confirm";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Request');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_request_confirm';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        } elseif (!empty($request_to_save)) {
            $audit_data['action'] = "Save";
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "request_save";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Request');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_request_save';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        } elseif (!empty($rejected_request_revert_to_sent_cvs)) {
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "active"]);
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id, "moved_tab_to" => "cleared"])->update(["status" => "active"]);
            Crm_note::where(["applicant_id" => $applicant_id,"sales_id" => $sale_id])->whereIn("moved_tab_to", ["cv_sent", "cv_sent_saved", "cv_sent_request"])->update(["status" => "disable"]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "cv_sent";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Rejected CV revert to Sent CVs');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_save';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        } elseif (!empty($rejected_request_revert_to_request)) {
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "active"]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "cv_sent_request";
            $crm_notes->save();
            Crm_note::where(["applicant_id" => $applicant_id,"sales_id" => $sale_id])->whereIn("moved_tab_to", ["cv_sent", "cv_sent_saved"])->update(["status" => "active"]);

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Request');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_request';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        } elseif (!empty($interview_save)) {
            $audit_data['action'] = "Save";
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "interview_save";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Confirmation');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_interview_save';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        } elseif(!empty($confirm_revert_request)) {
            $audit_data['action'] = "Confirmation Revert Request";
            Interview::where(['applicant_id' => $applicant_id, 'sale_id' => $sale_id, 'status' => 'active'])->update(['status' => 'disable']);
            Crm_note::where(['applicant_id' => $applicant_id, 'sales_id' => $sale_id])
                ->whereIn('moved_tab_to', ['cv_sent_request', 'request_to_save', 'request_to_confirm', 'interview_save'])
                ->update(['status' => 'disable']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "cv_sent_request";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Confirmation Revert Request');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_request';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        } elseif (!empty($interview_attend)) {
            $audit_data['action'] = "Attend";
            Applicant::where("id", $applicant_id)->update(['is_crm_interview_attended' => 'yes', 'is_crm_request_confirm' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "interview_attended";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Confirmation');
             */

            $last_inserted_note = $crm_notes->id;
            if ($last_inserted_note > 0) {
                $crm_note_uid = md5($last_inserted_note);
                Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
                History::where([
                    "applicant_id" => $applicant_id,
//                    "user_id" => $auth_user,
                    "sale_id" => $sale_id
                ])->update(["status" => "disable"]);
                $history = new History();
                $history->applicant_id = $applicant_id;
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_interview_attended';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        }
        elseif (!empty($interview_not_attend)) {
            $audit_data['action'] = "Not Attend";
            Applicant::where("id", $applicant_id)->update(['is_crm_interview_attended' => 'no', 'is_crm_request_confirm' => 'no']);
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "interview_not_attended";
            $crm_notes->save();
            Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->update(["status" => "disable"]);

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Confirmation');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_interview_not_attended';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        }
        elseif (!empty($revert_to_attend)) {
            $audit_data['action'] = "Revert To Attend";
            Applicant::where("id", $applicant_id)->update(['is_crm_interview_attended' => 'yes']);
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "active"]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "interview_attended";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Not Attended');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_interview_attended';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        }
        elseif (!empty($start_date)) {
            $audit_data['action'] = "Start Date";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_start_date' => 'yes', 'is_crm_interview_attended' => 'pending']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "start_date";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Attended To Pre-Start Date');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_start_date';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        }
        elseif (!empty($prestart_save)) {
            $audit_data['action'] = "Save";
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "prestart_save";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Attended To Pre-Start Date');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_prestart_save';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        }
        elseif (!empty($start_date_invoice)) {
            $audit_data['action'] = "Invoice";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_invoice' => 'yes', 'is_in_crm_start_date' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "invoice";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Start Date');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_invoice';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        }
        elseif (!empty($start_date_hold)) {
            $audit_data['action'] = "Start Date Hold";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_start_date_hold' => 'yes', 'is_in_crm_start_date' => 'no']);
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "start_date_hold";
            $crm_notes->save();
            Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->update(["status" => "disable"]);

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Start Date');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_start_date_hold';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        }
        elseif (!empty($start_date_save)) {
            $audit_data['action'] = "Save";
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "start_date_save";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Start Date');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_start_date_save';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        }
        elseif (!empty($start_date_hold_to_start_date)) {
            $audit_data['action'] = "Start Date";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_start_date_hold' => 'no', 'is_in_crm_start_date' => 'yes']);
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "active"]);
//            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "active"]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "start_date_back";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Start Date Hold');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_start_date_back';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        }
        elseif (!empty($start_date_hold_save)) {
            $audit_data['action'] = "Save";
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "start_date_hold_save";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Start Date Hold');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_start_date_hold_save';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        }
        elseif (!empty($paid)) {
            $audit_data['action'] = "Paid";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_paid' => 'yes', 'is_in_crm_invoice' => 'no']);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "paid";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Invoice');
             */

            $last_inserted_note = $crm_notes->id;
            if ($last_inserted_note > 0) {
                $crm_note_uid = md5($last_inserted_note);
                Crm_note::where('id', $last_inserted_note)->update(['crm_notes_uid' => $crm_note_uid]);
                Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "paid"]);
                History::where([
                    "applicant_id" => $applicant_id,
                    "sale_id" => $sale_id
                ])->update(["status" => "disable"]);
                $history = new History();
                $history->applicant_id = $applicant_id;
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_paid';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        }
        elseif (!empty($dispute)) {
            $audit_data['action'] = "Dispute";
            Applicant::where("id", $applicant_id)->update(['is_in_crm_dispute' => 'yes', 'is_in_crm_invoice' => 'no']);
            Cv_note::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            Quality_notes::where(["applicant_id" => $applicant_id,"sale_id" => $sale_id])->update(["status" => "disable"]);
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "dispute";
            $crm_notes->save();
            Crm_note::where(["applicant_id" => $applicant_id, "sales_id" => $sale_id])->update(["status" => "disable"]);

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Invoice');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_dispute';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        }
        elseif (!empty($final_save)) {
            $audit_data['action'] = "Save";
            $crm_notes = new Crm_note();
            $crm_notes->applicant_id = $applicant_id;
            $crm_notes->user_id = $auth_user;
            $crm_notes->sales_id = $sale_id;
            $crm_notes->details = $details;
            $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
            $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
            $crm_notes->moved_tab_to = "final_save";
            $crm_notes->save();

            /*** activity log
            $this->action_observer->action($audit_data, 'CRM > Invoice');
             */

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
                $history->user_id = $auth_user;
                $history->sale_id = $sale_id;
                $history->stage = 'crm';
                $history->sub_stage = 'crm_final_save';
                $history->history_added_date = date("jS F Y");
                $history->history_added_time = date("h:i A");
                $history->save();
                $last_inserted_history = $history->id;
                if($last_inserted_history > 0){
                    $history_uid = md5($last_inserted_history);
                    History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                    return redirect()->route('index');
                }
            } else {
                return redirect()->route('index');
            }
        }
    }

    public function getInterviewSchedule(Request $request)
    {
        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('module').'</span> WHOOPS! Something Went Wrong!!
                </div>';
        $audit_data['action'] = "Schedule Interview";
        $interview_scheduled = 0;
        $user = Auth::user()->id;
        $interview = new Interview();
        $interview->user_id = $user;
        $audit_data['sale'] = $interview->sale_id = $request->Input('sale_id');
        $audit_data['applicant'] = $interview->applicant_id = $request->Input('applicant_id');
        $audit_data['schedule_date'] = $interview->schedule_date = $request->Input('schedule_date');
        $audit_data['schedule_time'] = $interview->schedule_time = $request->Input('schedule_time');
        $interview->save();

        /*** activity log
        $this->action_observer->action($audit_data, 'CRM > Request');
         */

        $last_inserted_interview = $interview->id;
        if ($last_inserted_interview > 0) {
            $interview_uid = md5($last_inserted_interview);
            Interview::where('id', $last_inserted_interview)->update(['interview_uid' => $interview_uid]);
            $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Interview scheduled successfully
						</div>';
            echo $html;
        } else {
            echo $html;
        }
    }

    public function getCrmNotesDetails($applicant_id,$sale_id){
        $auth_user = Auth::user()->id;
        $applicant = $applicant_id;
        $sale = $sale_id;

        //CV SENT Notes
        $cv_send_in_quality_notes = Cv_note::where(array('applicant_id' => $applicant, 'sale_id' => $sale))
			->orderBy('created_at','desc')->first();
        // ./CV SENT Notes

        // Quality Notes
        $applicant_in_quality = Quality_notes::where(array('applicant_id' => $applicant,'sale_id' => $sale))
			->orderBy('created_at','desc')->first();
        // ./Quality Notes

        //CRM Notes
        $applicant_in_crm = Crm_note::join('applicants', 'crm_notes.applicant_id', '=', 'applicants.id')
            ->select("applicants.applicant_job_title","applicants.applicant_name","applicants.applicant_postcode","crm_notes.*")
            ->where(array('crm_notes.applicant_id' => $applicant, 'crm_notes.sales_id' => $sale))
			->orderBy('crm_notes.created_at', 'DESC')
			->get();
        // ./CRM Notes

        return view('inc.crm_templates.notes.index',
            compact('cv_send_in_quality_notes','applicant_in_quality','applicant_in_crm'));
    }
	
 public function reebokConfirmRevert(Request $request)
    {
        //  echo $request->Input('sale_id').' applicant id: '.$request->Input('applicant_id').' and details: '.$request->Input('detail_value') ;exit();
        date_default_timezone_set('Europe/London');

        /*** Interview Confirmation tab */
        $form_action = $request->Input('form_action');

        $audit_data['action'] = '';
        $audit_data['applicant'] = $applicant_id = $request->Input('applicant_id');
        $auth_user = Auth::user()->id;
        $audit_data['sale'] = $sale_id = $request->Input('sale_id');
        $audit_data['details'] = $details = $request->Input('detail_value');

        $html = '<div class="alert alert-danger border-0 alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    <span class="font-weight-semibold">'.$request->input('detail_value').'</span> WHOOPS! Something Went Wrong!!
                </div>';

                if (!empty($form_action) && ($form_action == 'rebook_confirm')) {
                    $audit_data['action'] = "Confirm";
                    Applicant::where("id", $applicant_id)->update(['is_crm_request_confirm' => 'yes', 'is_in_crm_request' => 'no', 'is_in_crm_request_reject' => 'no']);
                    $crm_notes = new Crm_note();
                    $crm_notes->applicant_id = $applicant_id;
                    $crm_notes->user_id = $auth_user;
                    $crm_notes->sales_id = $sale_id;
                    $crm_notes->details = $details;
                    $audit_data['added_date'] = $crm_notes->crm_added_date = date("jS F Y");
                    $audit_data['added_time'] = $crm_notes->crm_added_time = date("h:i A");
                    $crm_notes->moved_tab_to = "request_confirm";
                    $crm_notes->save();
        
                    /*** activity log
                    $this->action_observer->action($audit_data, 'CRM > Request');
                     */
        
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
                        $history->user_id = $auth_user;
                        $history->sale_id = $sale_id;
                        $history->stage = 'crm';
                        $history->sub_stage = 'crm_request_confirm';
                        $history->history_added_date = date("jS F Y");
                        $history->history_added_time = date("h:i A");
                        $history->save();
                        $last_inserted_history = $history->id;
                        if($last_inserted_history > 0){
                            $history_uid = md5($last_inserted_history);
                            History::where('id', $last_inserted_history)->update(['history_uid' => $history_uid]);
                            // $html = '<div class="alert alert-success border-0 alert-dismissible">
                            //         <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                            //         <span class="font-weight-semibold">'.$request->input('module').'</span> Applicant CV Revert In Confirmation Successfully
                            //     </div>';
                            // echo $html;
                        }
                    } 

                   
        $audit_data['action'] = "Schedule Interview";
        $interview_scheduled = 0;
        $user = Auth::user()->id;
        $result = Interview::where(['applicant_id' => $applicant_id, 'sale_id' => $sale_id ])->update(['schedule_date' => $request->Input('schedule_date')
        , 'schedule_time' => $request->Input('schedule_time')]);
        if($result)
        {
               $html = '<div class="alert alert-success border-0 alert-dismissible">
							<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
							<span class="font-weight-semibold">'.$request->input('module').'</span> Interview revert back to confirmation.
						</div>';
            echo $html;

        }
        else
        {
            $html = '<div class="alert alert-danger border-0 alert-dismissible">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <span class="font-weight-semibold"></span> WHOOPS! Something Went Wrong!!
        </div>';
        echo $html;

        }


                }
    }
	
	public function crmRequestChef()
    {

        $auth_user = Auth::user();
        $crm_request_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })
            //->where("applicants.job_category","chef")
           ->whereIn("history.sub_stage", ["crm_no_job_request","crm_request_no_job_save"])
            ->whereIn("crm_notes.moved_tab_to", ["cv_sent_no_job_request","request_no_job_save"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();
        $applicant_cvs_in_request = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            ->join('sales', 'crm_notes.sales_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->leftJoin('interviews', function ($join) {
                $join->on('applicants.id', '=', 'interviews.applicant_id');
                $join->on('sales.id', '=', 'interviews.sale_id');
                $join->where('interviews.status', '=', 'active');
            })->join('history', function ($join) {
                $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            })->select('crm_notes.details', 'crm_notes.crm_added_date', 'crm_notes.crm_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category', 'applicants.applicant_phone', 'applicants.applicant_homePhone',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
                'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date',
                'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
                'units.contact_email', 'units.website',
                'interviews.schedule_time', 'interviews.schedule_date', 'interviews.status as interview_status','applicants.is_no_job')
            ->where([
                "applicants.status" => "active",
                //"applicants.job_category" => "chef",
                //"crm_notes.moved_tab_to" => "cv_sent_request",
				"crm_notes.status" => "active",
                "history.status" => "active"
            ])->whereIn('crm_notes.moved_tab_to',['cv_sent_no_job_request',"request_no_job_save"])
			->whereIn('crm_notes.id', function($query){
                $query->select(DB::raw('MAX(id) FROM crm_notes WHERE moved_tab_to="cv_sent_no_job_request" and sales_id=sales.id and applicants.id=applicant_id'));
            })->whereIn("history.sub_stage", ["crm_no_job_request","crm_request_no_job_save"])
            ->orderBy("crm_notes.created_at","DESC");
//        dd($applicant_cvs_in_request->count());

        return datatables()->of($applicant_cvs_in_request)
            ->addColumn("name", function ($applicant) {
                $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                    ->where('cv_notes.applicant_id', '=', $applicant->id)
                    ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                    ->first();
                return $sent_by ? $sent_by->name : "";
            })
            ->addColumn("applicant_job_title", function($applicant){
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
                return strtoupper($job_title_desc);
            })
            ->addColumn("applicant_postcode", function($applicant){
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_request_save_note) {
                $content = '';
                if(!empty($crm_request_save_note)) {
                    foreach ($crm_request_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time;
                            $content .= '</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasPermissionTo('CRM_Request_schedule-interview')) {
                    if ($applicant->schedule_time && $applicant->schedule_date && $applicant->interview_status == 'active') {
                        $content .= '<a href="#" class="disabled dropdown-item"><i class="icon-file-confirm"></i>Schedule Interview</a>';
                    } else {
                        $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#schedule_interview' . $applicant->id . '-' . $applicant->sale_id . '"
                                           data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                           data-target="#schedule_interview' . $applicant->id . '-' . $applicant->sale_id . '">';
                        $content .= '<i class="icon-file-confirm"></i>Schedule Interview</a>';
                    }
                }
                if ($auth_user->hasAnyPermission(['CRM_Request_reject','CRM_Request_confirm','CRM_Request_save'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option"
                                           data-controls-modal="#confirm_cv' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                           data-applicantNameJs="' . $applicant->applicant_name . '" 
                                           data-applicantIdJs="' . $applicant->id . '"
                                           data-target="#confirm_cv' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i>Move To Confirmation</a>';
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasPermissionTo('CRM_Request_schedule-interview')) {
                    /*** Schedule Interview Modal */
                    $content .= '<div id="schedule_interview' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-sm">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h3 class="modal-title">' . $applicant->applicant_name . '</h3>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="schedule_interview_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<form action="' . route('scheduleInterview') . '" method="POST" id="schedule_interview_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<input type="hidden" name="applicant_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="sale_id" value="' . $applicant->sale_id . '">';
                    $content .= '<div class="mb-4">';
                    $content .= '<div class="input-group">';
                    $content .= '<span class="input-group-prepend">';
                    $content .= '<span class="input-group-text"><i class="icon-calendar5"></i></span>';
                    $content .= '</span>';
                    $content .= '<input type="text" class="form-control pickadate-year" name="schedule_date" id="schedule_date' . $applicant->id . '-' . $applicant->sale_id . '" placeholder="Select Schedule Date">';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="mb-4">';
                    $content .= '<div class="input-group">';
                    $content .= '<span class="input-group-prepend">';
                    $content .= '<span class="input-group-text"><i class="icon-watch2"></i></span>';
                    $content .= '</span>';
//                $content .= '<input type="text" class="form-control time_pickerrrr" id="anytime-time'.$applicant->id.'-'.$applicant->sale_id.'" name="schedule_time" placeholder="Select Schedule Time e.g., 00:00">';
                    $content .= '<input type="text" class="form-control" id="schedule_time' . $applicant->id . '-' . $applicant->sale_id . '" name="schedule_time" placeholder="Type Schedule Time e.g., 10:00">';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<button type="submit" class="btn bg-teal legitRipple btn-block schedule_interview_submit" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '">Schedule</button>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Schedule Interview Modal */
                }

                if ($auth_user->hasAnyPermission(['CRM_Request_reject','CRM_Request_confirm','CRM_Request_save'])) {
                    /*** Confirmation CV Modal */
                    $content .= '<div id="confirm_cv' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Confirm CV Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="request_cv_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="request_cv_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
					 if ($applicant->is_no_job==1){
                        $content .= '<input type="hidden" name="applicant_hidden_no_job" value="no_job">';
                    }
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .= '<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">';
                    $content .= '<input type="hidden" name="applicant_name_chat" id="applicant_name_chat">';
                    $content .= '<input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">';
                    $content .= '<textarea name="details" id="request_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    if ($auth_user->hasPermissionTo('CRM_Request_reject')) {
                      //  $content .= '<button type="submit" name="request_reject" value="request_reject" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple request_cv_submit"> Reject </button>';
						$content .= '<button type="button" class="btn bg-orange-800 legitRipple reject-btn" data-applicant-id="' . $applicant->id . '" data-sale-id="' . $applicant->sale_id . '" data-toggle="modal" data-target="#reject_email_modal">Reject</button>';
                    }
                    if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }

                    if ($auth_user->hasPermissionTo('CRM_Request_confirm')) {
                        $disabled = "disabled";
                        if ($applicant->schedule_time && $applicant->schedule_date && $applicant->interview_status == 'active') {
                            $disabled = "";
                        }
                        $content .= '<button type="submit" name="request_to_confirm" value="request_to_confirm" ' . $disabled . ' data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple request_cv_submit"> Confirm </button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Request_save')) {
                        $content .= '<button type="submit" name="request_to_save" value="request_to_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple request_cv_submit"> Save </button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Confirmation CV Modal */
					
  /*** Reject Modal for Email Input (Child Modal) */
                    $content .= '<div id="reject_email_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="rejectEmailModalLabel" aria-hidden="true" style="z-index:99999">';
                    $content .= '  <div class="modal-dialog" role="document">';
                    $content .= '    <div class="modal-content">';
                    $content .= '      <div class="modal-header">';
                    $content .= '        <h5 class="modal-title" id="rejectEmailModalLabel">Send Rejection Email</h5>';
                    $content .= '        <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
                    $content .= '          <span aria-hidden="true">&times;</span>';
                    $content .= '        </button>';
                    $content .= '      </div>';
                    $content .= '      <div class="modal-body">';
                    $content .= '        <!-- Hidden Inputs to Store Applicant and Sale IDs -->';
                    $content .= '        <input type="hidden" name="applicant_id" id="applicant_id">';
                    $content .= '        <input type="hidden" name="sale_id" id="sale_id">';
                    $content .= '        <!-- Email Subject and Body Inputs -->';
					 $content .= '        <div class="form-group">';
                  $content .= '          <input type="text" name="email_address" id="email_address" class="form-control mt-2" placeholder="Enter Email Address" required>';
                  $content .= '        </div>';
                    $content .= '        <div class="form-group">';
                    $content .= '          <input type="text" name="email_subject" id="email_subject" class="form-control mt-2" placeholder="Email Subject" required>';
                    $content .= '        </div>';
                    $content .= '        <div class="form-group">';
                    $content .= '          <textarea name="email_body" id="email_body" rows="5" class="form-control mt-2" placeholder="Type message here..." required></textarea>';
                    $content .= '        </div>';
                    $content .= '      </div>';
                    $content .= '      <div class="modal-footer">';
                    $content .= '        <button type="button" class="btn btn-danger" id="submit_email_btn">Submit Email</button>';
                    $content .= '      </div>';
                    $content .= '    </div>';
                    $content .= '  </div>';
                    $content .= '</div>';
                    /*** /Reject Modal for Email Input (Child Modal) */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                /*** /Manager Details Modal */

                return $content;
            })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }
	 
	    public function crmSentCvChef()
    {
        $auth_user = Auth::user();

        $applicant_with_cvs = Applicant::join('quality_notes', 'applicants.id', '=', 'quality_notes.applicant_id')
            ->join('sales', 'quality_notes.sale_id', '=', 'sales.id')
            ->join('offices', 'sales.head_office', '=', 'offices.id')
            ->join('units', 'sales.head_office_unit', '=', 'units.id')
            ->join('history', function ($join) {
                $join->on('quality_notes.applicant_id', '=', 'history.applicant_id');
                $join->on('quality_notes.sale_id', '=', 'history.sale_id');
            })->select('quality_notes.details','quality_notes.quality_added_date','quality_notes.quality_added_time',
                'applicants.id', 'applicants.applicant_name', 'applicants.applicant_postcode','applicants.applicant_phone','applicants.applicant_homePhone', 'applicants.applicant_job_title','applicants.job_title_prof', 'applicants.job_category','applicants.is_no_job',
                'sales.id as sale_id', 'sales.job_category as sales_job_category', 'sales.job_title', 'sales.postcode', 'sales.job_type',
                'sales.timing', 'sales.salary', 'sales.experience', 'sales.qualification', 'sales.benefits', 'sales.posted_date','sales.job_description',
                'offices.office_name', 'units.unit_name', 'units.unit_postcode', 'units.contact_name', 'units.contact_phone_number', 'units.contact_landline',
                'units.contact_email', 'units.website',DB::raw("(SELECT users.name from cv_notes INNER JOIN users on users.id = cv_notes.user_id
                WHERE cv_notes.applicant_id=applicants.id AND cv_notes.sale_id=sales.id limit 1) as sent_by"))
            ->where([
                "applicants.status" => "active",
//                "quality_notes.moved_tab_to" => "cleared",
                "quality_notes.status" => "active",
                "history.status" => "active"
            ])->whereIn("quality_notes.moved_tab_to" ,["cleared", "cleared_no_job"])
			->whereIn("history.sub_stage", ["quality_cleared_no_job"])
            ->orderBy("quality_notes.created_at","desc");

        // $crm_cv_sent_save_note = ViewSentcvData::select("*")
        //             ->get();

        $crm_cv_sent_save_note = Applicant::join('crm_notes', 'applicants.id', '=', 'crm_notes.applicant_id')
            // ->join('history', function ($join) {
            //     $join->on('crm_notes.applicant_id', '=', 'history.applicant_id');
            //     $join->on('crm_notes.sales_id', '=', 'history.sale_id');
            // })
            // ->whereIn("history.sub_stage", ["quality_cleared", "crm_save"])
            ->whereIn("crm_notes.moved_tab_to", ["cv_sent_no_job"])
            ->select("crm_notes.sales_id", "crm_notes.applicant_id as app_id", "crm_notes.details as crm_note_details", "crm_notes.crm_added_date", "crm_notes.crm_added_time")
            ->orderBy('crm_notes.id', 'DESC')->get();

        return datatables()->of($applicant_with_cvs)
            ->addColumn("name", function ($applicant) {
                // $sent_by = Cv_note::join('users', 'users.id', '=', 'cv_notes.user_id')
                //     ->where('cv_notes.applicant_id', '=', $applicant->id)
                //     ->where('cv_notes.sale_id', '=', $applicant->sale_id)
                //     ->select('users.name')
                //     ->first();
                // return $sent_by ? $sent_by->name : "";
                return $applicant->sent_by;
            })
            ->addColumn("applicant_job_title", function($applicant){
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
                return strtoupper($job_title_desc);
                // return strtoupper($applicant->applicant_job_title);
            })
            ->addColumn("applicant_postcode", function($applicant){
                return '<a href="'.route('15kmrange', $applicant->id).'" class="btn-link legitRipple">'.strtoupper($applicant->applicant_postcode).'</a>';
            })
            ->addColumn("job_details", function ($applicant) {
                $content = '';
                $content .= '<a href="#" data-controls-modal="#job_details'.$applicant->id.'-'.$applicant->sale_id.'"
                                                data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                                data-target="#job_details'.$applicant->id.'-'.$applicant->sale_id.'">Details</a>';
                $content .= '<div id="job_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-lg">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">"'.$applicant->applicant_name.'\'s Job Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
                $content .= '<div class="media flex-column flex-md-row mb-4">';
                $content .= '<div class="media-body">';
                $content .= '<h5 class="media-title font-weight-semibold">';
                $content .= $applicant->office_name.' / '.$applicant->unit_name;
                $content .= '</h5>';
                $content .= '<ul class="list-inline list-inline-dotted text-muted mb-0">';
                $content .= '<li class="list-inline-item">';
                $content .= $applicant->sales_job_category.', '.$applicant->job_title;
                $content .= '</li>';
                $content .= '</ul>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="row">';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Title: </h6>';
                $content .= '<p>'.$applicant->job_title.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Postcode: </h6>';
                $content .= '<p class="mb-3">'.$applicant->postcode.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Job Type: </h6>';
                $content .= '<p class="mb-3">'.$applicant->job_type.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Timings: </h6>';
                $content .= '<p class="mb-3">'.$applicant->timing.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Salary: </h6>';
                $content .= '<p class="mb-3">'.$applicant->salary.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Experience: </h6>';
                $content .= '<p class="mb-3">'.$applicant->experience.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Qualification: </h6>';
                $content .= '<p class="mb-3">'.$applicant->qualification.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Benefits: </h6>';
                $content .= '<p class="mb-3">'.$applicant->benefits.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div class="col-3">';
                $content .= '<h6 class="font-weight-semibold">Posted Date: </h6>';
                $content .= '<p class="mb-3">'.$applicant->posted_date.'</p>';
                $content .= '</div>';
                $content .= '<div class="col-3"></div>';
                $content .= '<div  class="col-3">';
                $content .= '<h6 class="font-weight-semibold">JOB Description : </h6>';
                $content .='<p class="mb-3">'.$applicant->job_description.'</p>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">Close</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->addColumn("crm_note", function ($applicant) use ($crm_cv_sent_save_note) {
                $content = '';
                if(!empty($crm_cv_sent_save_note)) {
                    foreach ($crm_cv_sent_save_note as $crm_save) {
                        if (($crm_save->app_id == $applicant->id) && ($crm_save->sales_id == $applicant->sale_id)) {
                            $content .= '<p><b>DATE: </b>';
                            $content .= $crm_save->crm_added_date;
                            $content .= '<b> TIME: </b>';
                            $content .= $crm_save->crm_added_time;
                            $content .= '</p>';
                            $content .= '<p><b>NOTE: </b>';
                            $content .= $crm_save->crm_note_details;
                            $content .= '</p>';
                            break;
                        }
                    }
                }
                return $content;
            })
            ->addColumn("action", function ($applicant) use ($auth_user) {
				$phoneArray = $applicant->contact_phone_number;
                $landlineArray = $applicant->contact_landline;
                $emailArray = $applicant->contact_email;
                $nameArray = $applicant->contact_name;
            
                $emails = array_filter(explode(',', $emailArray));
                $phones = array_filter(explode(',', $phoneArray));
                $landlines = array_filter(explode(',', $landlineArray));
                $names = array_filter(explode(',', $nameArray));
    
                $mergedArray = [];
            
                $maxLength = max(count($emails), count($phones), count($landlines), count($names));
            
                for ($i = 0; $i < $maxLength; $i++) {
                    $email = $emails[$i] ?? '';
                    $phone = $phones[$i] ?? '';
                    $landline = $landlines[$i] ?? '';
                    $name = $names[$i] ?? '';
            
                    if ($email || $phone || $landline || $name) {
                        $mergedArray[] = [
                            'email' => $email,
                            'phone' => $phone,
                            'landline' => $landline,
                            'name' => $name
                        ];
                    }
                }
                $applicant_msgs =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                $content = '';
                /*** action menu */
                $content .= '<div class="list-icons">';
                $content .= '<div class="dropdown">';
                $content .= '<a href="#" class="list-icons-item" data-toggle="dropdown">';
                $content .= '<i class="icon-menu9"></i>';
                $content .= '</a>';
                $content .= '<div class="dropdown-menu dropdown-menu-right">';
                if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_request','CRM_Sent-CVs_save','CRM_Sent-CVs_reject'])) {
//                if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_request','CRM_Sent-CVs_save','CRM_Sent-CVs_reject'])) {
                    $content .= '<a href="#" class="dropdown-item sms_action_option sms_action_sent_cv"
                                           data-controls-modal="#clear_cv' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-applicantPhoneJs="' . $applicant->applicant_phone . '" 
                                           data-applicantNameJs="' . $applicant->applicant_name . '" 
                                           data-applicantIdJs="' . $applicant->id . '"
                                           data-applicantunitjs="' . $applicant->unit_name . '" 
                                           data-target="#clear_cv' . $applicant->id . '-' . $applicant->sale_id . '">';
                    $content .= '<i class="icon-file-confirm"></i>Reject/Request</a>';
                    if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_revert'])) {
                        $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#revert_in_qulaity' . $applicant->id . '-' . $applicant->sale_id . '" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#revert_in_qulaity' . $applicant->id . '-' . $applicant->sale_id . '">';
                        $content .= '<i class="icon-file-confirm"></i>Revert In Quality</a>';
                    }
                }
                $content .= '<a href="#" class="dropdown-item"
                                           data-controls-modal="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'" data-backdrop="static"
                                           data-keyboard="false" data-toggle="modal"
                                           data-target="#manager_details'.$applicant->id.'-'.$applicant->sale_id.'">';
                $content .= '<i class="icon-file-confirm"></i>Manager Details</a>';
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    if(!empty($applicant_msgs))
                    {
                        if ($applicant_msgs['is_read'] == 0) {
                            $content .= '<a href="#" class="dropdown-item crm_chat" data-applicantPhoneJs="' . $applicant->applicant_phone . '" data-applicantIdJs="' . $applicant->id . '" data-applicantNameJs="' . $applicant->applicant_name . '">';
                            $content .= '<i class="icon-file-confirm"></i>Reply Sms</a>';
                        }
                    }
                }
                $content .= '<a href="'.route('viewAllCrmNotes',["applicant_id" => $applicant->id,"sale_id" => $applicant->sale_id]).'" class="dropdown-item">';
                $content .= '<i class="icon-file-confirm"></i>View All Notes</a>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                if ($auth_user->hasAnyPermission(['CRM_Sent-CVs_request','CRM_Sent-CVs_save','CRM_Sent-CVs_reject'])) {
                    /*** Revert In Quality ***/
                    $content .= '<div id="revert_in_qulaity' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade" tabindex="-1">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">Revert In Quality Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $quality_url = '/revert-cv-quality/';
                    $content .= '<form action="' . $quality_url . $applicant->id . '" method="POST" id="revert_quality' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="revert_quality_cv' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                  
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
					$content .=  '<input type="hidden" name="cv_modal_name" class="model_name" value="sent_cv_no_job">';
                    $content .= '<textarea name="details" id="revert_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
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
                    $content .= '</div>';
                    /*** Move CV Modal */
                    $content .= '<div id="clear_cv' . $applicant->id . '-' . $applicant->sale_id . '" class="modal fade small_msg_modal">';
                    $content .= '<div class="modal-dialog modal-lg">';
                    $content .= '<div class="modal-content">';
                    $content .= '<div class="modal-header">';
                    $content .= '<h5 class="modal-title">CRM Notes</h5>';
                    $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $content .= '</div>';
                    $content .= '<form action="' . route('processCv') . '" method="POST" id="sent_cv_form' . $applicant->id . '-' . $applicant->sale_id . '" class="form-horizontal">';
                    $content .= csrf_field();
                    $content .= '<div class="modal-body">';
                    $content .= '<div id="sent_cv_alert' . $applicant->id . '-' . $applicant->sale_id . '"></div>';
                    $content .= '<div class="form-group row">';
                    $content .= '<label class="col-form-label col-sm-3">Details</label>';
                    $content .= '<div class="col-sm-9">';
                   
                    $content .= '<input type="hidden" name="applicant_hidden_id" value="' . $applicant->id . '">';
                    $content .= '<input type="hidden" name="job_hidden_id" value="' . $applicant->sale_id . '">';
                    $content .=  '<input type="hidden" name="applicant_id_chat" id="applicant_id_chat">';
                    $content .=  '<input type="hidden" name="applicant_name_chat" id="applicant_name_chat">';
                    $content .=  '<input type="hidden" name="applicant_phone_chat" id="applicant_phone_chat">';
                    $content .=  '<input type="hidden" name="cv_modal_name" class="model_name" value="sent_cv">';
                    $content .= '<textarea name="details" id="sent_cv_details' . $applicant->id . '-' . $applicant->sale_id . '" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>';
                    $content .= '</div>';
                    $content .= '</div>';
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_reject')) {
                        $content .= '<div class="form-group row">';
                        $content .= '<label class="col-form-label col-sm-3">Choose type:</label>';
                        $content .= '<div class="col-sm-9">';
                        $content .= '<select name="reject_reason" id="reject_reason" class="form-control crm_select_reason">';
                        $content .= '<option >Select Reason</option>';
                        $content .= '<option value="position_filled">Position Filled</option>';
                        $content .= '<option value="agency">Sent By Another Agency</option>';
                        $content .= '<option value="manager">Rejected By Manager</option>';
                        $content .= '<option value="no_response">No Response</option>';
                        $content .= '</select>';
                        $content .= '</div>';
                        $content .= '</div>';
                    }
                    $content .= '</div>';
                    $content .= '<div class="modal-footer">';
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_reject')) {
                        $content .= '<button type="submit" name="cv_sent_reject" value="cv_sent_reject" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-orange-800 legitRipple reject_btn sent_no_job_cv_submit" style="display: none">Reject</button>';
                    }
                    if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                        $content .= '<button type="button" class="btn btn-primary" id="show_chat">Send Sms</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_request')) {
                        $content .= '<button type="submit" name="cv_sent_request" value="cv_sent_request" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-dark legitRipple cv_sent_request sent_no_job_cv_submit sanat">Request</button>';
                    }
                    if ($auth_user->hasPermissionTo('CRM_Sent-CVs_save')) {
                        $content .= '<button type="submit" name="cv_sent_save" value="cv_sent_save" data-app_sale="' . $applicant->id . '-' . $applicant->sale_id . '" class="btn bg-teal legitRipple cv_sent_notes_save sent_no_job_cv_submit">Save</button>';
                    }
                    $content .= '</div>';
                    $content .= '</form>';
                    $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                    /*** /Move CV Modal */
                }

                /*** Manager Details Modal */
                $content .= '<div id="manager_details'.$applicant->id.'-'.$applicant->sale_id.'" class="modal fade" tabindex="-1">';
                $content .= '<div class="modal-dialog modal-md">';
                $content .= '<div class="modal-content">';
                $content .= '<div class="modal-header">';
                $content .= '<h5 class="modal-title">Manager Details</h5>';
                $content .= '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                $content .= '</div>';
                $content .= '<div class="modal-body">';
               foreach($mergedArray as $index => $value) {
                    $index = $index + 1;
                    $content .= '<div><ul class="list-group pt-0">';
                    $content .= '<li class="list-group-item active" style="padding: .35rem 1.25rem;"><p class="mb-0"><b><em>Person - '. $index .'</em></b></p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Name: </b>'.$value['name'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Email: </b>'.$value['email'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Phone: </b>'.$value['phone'].'</p></li>';
                    $content .= '<li class="list-group-item" style="padding: .35rem 1.25rem;"><p class="mb-0"><b>Landline: </b>'.$value['landline'].'</p></li>';
                    $content .= '</ul></div>';
                }
                $content .= '</div>';
                $content .= '<div class="modal-footer">';
                $content .= '<button type="button" class="btn bg-teal legitRipple" data-dismiss="modal">CLOSE</button>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';
                $content .= '</div>';

                return $content;
            })
            ->setRowClass(function ($applicant) use ($auth_user) {
                if ($auth_user->hasPermissionTo('applicant_chat-box')) {
                    $applicant_msg =Applicant_message::where(['phone_number' => $applicant->applicant_phone, 'status' => 'incoming'])->orderBy('created_at', 'desc')->first();
                    $row_class = '';
                    if(!empty($applicant_msg))
                    {
                        if ($applicant_msg['is_read'] == 0) {
                            $row_class .= 'blink';
                        }
                    }

                    return $row_class;
                }
            })
            ->rawColumns(['name','applicant_job_title','applicant_postcode','job_details','crm_note','action'])
            ->make(true);
    }

    public function sendEmailToUnit($applicant_name, $service_name, $service_email)
    {
        $template = EmailTemplate::where('title','request_reject')->first();
        $data = $template->template;
        $replace = [$service_name,$applicant_name];
        $prev_val = ['(Service name)', '(xyz)'];

        $newPhrase = str_replace($prev_val, $replace, $data);
        
        Mail::send([],[], function($message) use ($newPhrase, $service_email) {
            $message->from('customerservice@kingsburypersonnel.com', 'Kingsbury Personnel Ltd');
            $message->to($service_email);
            $message->subject('Application has been Withdraw');
            $message->setBody($newPhrase, 'text/html');
        });
        if (Mail::failures()) {
            return 'error';
        }
        else
        {
			$email_from = 'customerservice@kingsburypersonnel.com';
            $email_sent_to_cc ='';
            $subject = 'Application has been Withdraw';
            $action_name = 'Request Reject';
            $dbSaveEmail = $this->saveSentEmails($service_email, $email_sent_to_cc, $email_from, $subject, $newPhrase, $action_name);
            return 'success';
        }
    }
	 
    public function saveSentEmails($email_to, $email_cc, $email_from, $email_title, $email_body, $action_name)
    {
        $sent_email = new SentEmail();
        $sent_email->action_name = $action_name;
        $sent_email->sent_from = $email_from;
        $sent_email->sent_to = $email_to;
        $sent_email->cc_emails = $email_cc;
        $sent_email->subject = $email_title;
        $sent_email->template = $email_body;
        $sent_email->email_added_date = date("jS F Y");
        $sent_email->email_added_time = date("h:i A");
        if($action_name == 'Random Email'){
            $sent_email->status = '0';
        }
        $sent_email->save();
		
        if($sent_email)
        {
            return 'success';
        }
        else
        {
            return 'error';
        }
    }
}
