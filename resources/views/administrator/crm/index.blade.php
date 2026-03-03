@extends('layouts.app')

@section('content')

    <!-- Main content -->
    <div class="content-wrapper">

        {{--<!-- Page header -->--}}
{{--        <div class="page-header page-header-dark has-cover" style="border: 1px solid #ddd; border-bottom: 0;">--}}
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">CRM</span>
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                            <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                            <a href="#" class="breadcrumb-item">Current</a>
                            <span class="breadcrumb-item active">CRM</span>
                           <button type="button" class="btn bg-indigo-400 legitRipple crm-refresh" style="position: absolute; right: 20px;"><i class="fas fa-sync"></i> Refresh </button>
                    </div>
                </div>
            </div>
			<div class="col-md-12">
                        @if ($message = Session::get('error'))
                            <div class="alert alert-danger border-0 alert-dismissible mb-0 p-2">
                                <button type="button" class="close p-2" data-dismiss="alert"><span>×</span></button>
                                <span class="font-weight-semibold">Error!</span> {{ $message }}
                            </div>
                        @endif
                        @if ($message = Session::get('success'))
                            <div class="alert alert-success border-0 alert-dismissible mb-0 p-2">
                                <button type="button" class="close p-2" data-dismiss="alert"><span>×</span></button>
                                <span class="font-weight-semibold">Success!</span> {{ $message }}
                            </div>
                        @endif
                    </div>
			                    <div class="col-md-12" id="notify_alert" ></div>

        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">

            <!-- Default ordering -->
            <div class="card">

                <div class="card-body">

                    <ul class="nav nav-tabs nav-tabs-highlight">
                        <li class="nav-item">
                            <a href="#CV_sent" class="nav-link active legitRipple" data-toggle="tab" data-datatable_name="crm_sent_cv_sample">Sent CVs</a>
                        </li>
						 <li class="nav-item">
                            <a href="#CV_open" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_open_cv_sample">Open CVs</a>
                        </li>
						<li class="nav-item">
                            <a href="#CV_sent_nurse" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_sent_cv_nurse_sample">Sent CVs (Nurse)</a>
                        </li>
                        <li class="nav-item">
                            <a href="#CV_sent_nonnurse" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_sent_cv_nonnurse_sample">Sent CVs (Non Nurse)</a>
                        </li>
						 <li class="nav-item">
                            <a href="#CV_sent_no_job" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_sent_cv_no_job_sample">Sent CVs (No Job)</a>
                        </li>

                        @canany(['CRM_Rejected-CV_list','CRM_Rejected-CV_revert-sent-cv'])
                        <li class="nav-item">
                            <a href="#reject_CV" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_reject_cv_sample">Rejected CV</a>
                        </li>
                        @endcanany

                       <!-- @canany(['CRM_Request_list','CRM_Request_reject','CRM_Request_confirm','CRM_Request_save','CRM_Request_schedule-interview'])
                        <li class="nav-item">
                            <a href="#request" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_request_cv_sample">Request</a>
                        </li>
                        @endcanany -->
						 @canany(['CRM_Request_list','CRM_Request_reject','CRM_Request_confirm','CRM_Request_save','CRM_Request_schedule-interview'])
                        <li class="nav-item">
                            <a href="#request_nurse" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_request_nurse_cv_sample">Request (Nurse)</a>
                        </li>
                        @endcanany
                        @canany(['CRM_Request_list','CRM_Request_reject','CRM_Request_confirm','CRM_Request_save','CRM_Request_schedule-interview'])
                        <li class="nav-item">
                            <a href="#request_nonnurse" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_request_nonnurse_cv_sample">Request (Non Nurse)</a>
                        </li>
                        @endcanany
						@canany(['CRM_Request_list','CRM_Request_reject','CRM_Request_confirm','CRM_Request_save','CRM_Request_schedule-interview'])
                        <li class="nav-item">
                            <a href="#request_no_job" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_request_no_job_cv_sample">Request (No job)</a>
                        </li>
                        @endcanany

                        @canany(['CRM_Rejected-By-Request_list','CRM_Rejected-By-Request_revert-sent-cv','CRM_Rejected-By-Request_revert-request'])
                        <li class="nav-item">
                            <a href="#rejectByRequest" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_rejected_by_request_cv_sample">Rejected By Request</a>
                        </li>
                        @endcanany

                        @canany(['CRM_Confirmation_list','CRM_Confirmation_revert-request','CRM_Confirmation_not-attended','CRM_Confirmation_attend','CRM_Confirmation_rebook','CRM_Confirmation_save'])
                        <li class="nav-item">
                            <a href="#confirmation" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_confirmation_cv_sample">Confirmation</a>
                        </li>
                        @endcanany

                        {{--                    <li class="nav-item">--}}
                        {{--                        <a href="#attended" class="nav-link legitRipple" data-toggle="tab">Attended</a>--}}
                        {{--                    </li>--}}
                        @canany(['CRM_Rebook_list','CRM_Rebook_not-attended','CRM_Rebook_attend','CRM_Rebook_save',])
                            <li class="nav-item">
                                <a href="#rebook" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_rebook_cv_sample">Rebook</a>
                            </li>
                        @endcanany
                        @canany(['CRM_Attended_list','CRM_Attended_start-date','CRM_Attended_save'])
                        <li class="nav-item">
                            <a href="#pre-start" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_pre_start_cv_sample">Attended To Pre-Start Date</a>
                        </li>
                        @endcanany
                        @canany(['CRM_Declined_list','CRM_Declined_revert-to-attended'])
                            <li class="nav-item">
                                <a href="#declined" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_declined_cv_sample">Declined</a>
                            </li>
                        @endcanany
                        @canany(['CRM_Not-Attended_list','CRM_Not-Attended_revert-to-attended'])
                        <li class="nav-item">
                            <a href="#not_attended" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_not_attended_cv_sample">Not Attended</a>
                        </li>
                        @endcanany

                        @canany(['CRM_Start-Date_list','CRM_Start-Date_invoice','CRM_Start-Date_start-date-hold','CRM_Start-Date_save'])
                        <li class="nav-item">
                            <a href="#start" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_start_date_cv_sample">Start date</a>
                        </li>
                        @endcanany

                        @canany(['CRM_Start-Date-Hold_list','CRM_Start-Date-Hold_revert-start-date','CRM_Start-Date-Hold_save'])
                        <li class="nav-item">
                            <a href="#start_date_hold" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_start_date_hold_cv_sample">Start Date Hold</a>
                        </li>
                        @endcanany

                        @canany(['CRM_Invoice_list','CRM_Invoice_paid','CRM_Invoice_dispute','CRM_Invoice_save'])
                        <li class="nav-item">
                            <a href="#invoice_sent" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_invoice_cv_sample">Invoice</a>
                        </li>
                        @endcanany
						
						@canany(['CRM_Invoice_list','CRM_Invoice_paid','CRM_Invoice_dispute','CRM_Invoice_save'])
                        <li class="nav-item">
                            <a href="#invoice_final_sent" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_invoice_final_sent_cv_sample">Invoice Sent</a>
                        </li>
                        @endcanany
						
                        @canany(['CRM_Dispute_list','CRM_Dispute_revert-invoice'])
                        <li class="nav-item">
                            <a href="#dispute" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_dispute_cv_sample">Dispute</a>
                        </li>
                        @endcanany

                        @canany(['CRM_Paid_list','CRM_Paid_open-close-cv'])
                        <li class="nav-item">
                            <a href="#invoice_pending" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="crm_paid_cv_sample">Paid</a>
                        </li>
                        @endcanany

                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="CV_sent">
                            @include('inc/revamp_crm/sent_cv')
                        </div>
						 <div class="tab-pane" id="CV_open">
                            @include('inc/revamp_crm/open_cv')
                        </div>
						<div class="tab-pane" id="CV_sent_nurse">
                            @include('inc/revamp_crm/sent_cv_nurse')
                        </div>
                        <div class="tab-pane" id="CV_sent_nonnurse">
                            @include('inc/revamp_crm/sent_cv_nonnurse')
                        </div>
                       <div class="tab-pane" id="CV_sent_no_job">
                            @include('inc/revamp_crm/sent_cv_job')
                        </div>
                        @canany(['CRM_Rejected-CV_list','CRM_Rejected-CV_revert-sent-cv'])
                        <div class="tab-pane" id="reject_CV">
                            @include('inc/revamp_crm/reject')
                        </div>
                        @endcanany

                        @canany(['CRM_Request_list','CRM_Request_reject','CRM_Request_confirm','CRM_Request_save','CRM_Request_schedule-interview'])
                        <div class="tab-pane" id="request">
                            @include('inc/revamp_crm/request')
                        </div>
                        @endcanany
						@canany(['CRM_Request_list','CRM_Request_reject','CRM_Request_confirm','CRM_Request_save','CRM_Request_schedule-interview'])
                        <div class="tab-pane" id="request_nurse">
                            @include('inc/revamp_crm/request_nurse')
                        </div>
                        @endcanany
                        @canany(['CRM_Request_list','CRM_Request_reject','CRM_Request_confirm','CRM_Request_save','CRM_Request_schedule-interview'])
                        <div class="tab-pane" id="request_nonnurse">
                            @include('inc/revamp_crm/request_nonnurse')
                        </div>
                        @endcanany
						 @canany(['CRM_Request_list','CRM_Request_reject','CRM_Request_confirm','CRM_Request_save','CRM_Request_schedule-interview'])
                            <div class="tab-pane" id="request_no_job">
                                @include('inc/revamp_crm/request_no_job')
                            </div>
                        @endcanany

                        @canany(['CRM_Rejected-By-Request_list','CRM_Rejected-By-Request_revert-sent-cv','CRM_Rejected-By-Request_revert-request'])
                        <div class="tab-pane" id="rejectByRequest">
                            @include('inc/revamp_crm/reject_by_request')
                        </div>
                        @endcanany

                        @canany(['CRM_Confirmation_list','CRM_Confirmation_revert-request','CRM_Confirmation_not-attended','CRM_Confirmation_attend','CRM_Confirmation_rebook','CRM_Confirmation_save'])
                        <div class="tab-pane" id="confirmation">
                            @include('inc/revamp_crm/confirmation')
                        </div>
                        @endcanany

                        @canany(['CRM_Rebook_list','CRM_Rebook_not-attended','CRM_Rebook_attend','CRM_Rebook_save',])
                        <div class="tab-pane" id="rebook">
                            @include('inc/revamp_crm/rebook')
                        </div>
                        @endcanany

                        @canany(['CRM_Attended_list','CRM_Attended_start-date','CRM_Attended_save'])
                        <div class="tab-pane" id="pre-start">
                            @include('inc/revamp_crm/attended_to_pre_start_date')
                        </div>
                        @endcanany

                        @canany(['CRM_Declined_list','CRM_Declined_revert-to-attended'])
                            <div class="tab-pane" id="declined">
                                @include('inc/revamp_crm/declined')
                            </div>
                        @endcanany

                        @canany(['CRM_Not-Attended_list','CRM_Not-Attended_revert-to-attended'])
                        <div class="tab-pane" id="not_attended">
                            @include('inc/revamp_crm/not_attended')
                        </div>
                        @endcanany

                        @canany(['CRM_Start-Date_list','CRM_Start-Date_invoice','CRM_Start-Date_start-date-hold','CRM_Start-Date_save'])
                        <div class="tab-pane" id="start">
                            @include('inc/revamp_crm/start_date')
                        </div>
                        @endcanany

                        @canany(['CRM_Start-Date-Hold_list','CRM_Start-Date-Hold_revert-start-date','CRM_Start-Date-Hold_save'])
                        <div class="tab-pane" id="start_date_hold">
                            @include('inc/revamp_crm/start_date_hold')
                        </div>
                        @endcanany

                        @canany(['CRM_Invoice_list','CRM_Invoice_paid','CRM_Invoice_dispute','CRM_Invoice_save'])
                        <div class="tab-pane" id="invoice_sent">
                            @include('inc/revamp_crm/invoice')
                        </div>
                        @endcanany
						
						 @canany(['CRM_Invoice_list','CRM_Invoice_paid','CRM_Invoice_dispute','CRM_Invoice_save'])
                        <div class="tab-pane" id="invoice_final_sent">
                            @include('inc/revamp_crm/invoice_sent')
                        </div>
                        @endcanany
						
                        @canany(['CRM_Dispute_list','CRM_Dispute_revert-invoice'])
                        <div class="tab-pane" id="dispute">
                            @include('inc/revamp_crm/dispute')
                        </div>
                        @endcanany

                        @canany(['CRM_Paid_list','CRM_Paid_open-close-cv'])
                        <div class="tab-pane" id="invoice_pending">
                            @include('inc/revamp_crm/paid')
                        </div>
                        @endcanany

                    </div>

                </div>
            </div>
            <div class="push"></div>
        </div>		
		<div id="sent_cv_non_nurse_sms" class="modal fade small_msg_modal">
            <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
            <h5 class="modal-title">Send Request Sms To <span id="smsName"></span></h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="#" method="POST" id="send_non_nurse_sms" class="form-horizontal">
            <div class="modal-body">
            <div id="sent_cv_alert_non_nurse"></div>
            <div class="form-group row">
            <label class="col-form-label col-sm-2">Message Text:</label>
            <div class="col-sm-10">
                <input type="hidden" name="applicant_number_sms" id="applicant_number_sms">
                <input type="hidden" name="non_nurse_modal_id" id="non_nurse_modal_id">
            <textarea name="details" id="sent_cv_details_non_nurse_for_sms" class="form-control" cols="40" rows="8" placeholder="TYPE HERE.." required></textarea>
            </div>
            </div>
            </div>
            <div class="modal-footer">
                <button type="button" name="non_nurse_req_sms" value="non_nurse_req_sms" id="non_nurse_req_sms" class="btn bg-teal legitRipple">Send Sms</button>
            </div>
        </form>
        </div>
    </div>
        </div>
		
@include('layouts.small_chat_box')
		@include('layouts.crm_chat_box')


@endsection

@section('js_file')
		<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
<script>
$(document).ready(function () {
    // Open the first modal
    $(document).on('click', '[data-controls-modal]', function () {
        var emailModalId = $(this).data('controls-modal');
        $(emailModalId).modal('show'); // Show the email modal
    });

    // Handle the email modal submission and open the schedule modal after email submission
    $(document).on('click', '[id^=submit_requested_applicant_email_btn_]', function () {
        var emailModal = $(this).closest('.modal');
        var applicantId = $(this).data('applicant-id');
        var saleId = $(this).data('sale-id');

        // Hide the email modal
        emailModal.modal('hide'); 

        // Construct the schedule interview modal ID dynamically
        var scheduleModalId = `#schedule_interview${applicantId}-${saleId}`;

        // Listen for the 'hidden.bs.modal' event to ensure modal is fully hidden before opening the next one
        emailModal.on('hidden.bs.modal', function () {
            $(scheduleModalId).modal('show');
            emailModal.off('hidden.bs.modal'); // Remove the event listener to prevent multiple bindings
        });
    });

    // Initialize Summernote for the email body textarea in the email modal
    $(document).on('shown.bs.modal', '[id^=send_applicant_email_request_modal]', function () {
        var modalId = $(this).attr('id');
        var idParts = modalId.match(/send_applicant_email_request_modal(\d+)-(\d+)/);
        
        if (idParts) {
            var applicantId = idParts[1];
            var saleId = idParts[2];
            var emailBodyId = `#email_body_requested_${applicantId}_${saleId}`;

            // Initialize Summernote if it hasn't been initialized yet
            if (!$(emailBodyId).data('summernote')) {
                $(emailBodyId).summernote({
                    height: 200,
                    minHeight: 100,
                    maxHeight: 400,
                    focus: true,
                    toolbar: [
                        ["style", ["bold", "italic", "underline", "clear"]],
                        ["font", ["strikethrough", "superscript", "subscript"]],
                        ["fontsize", ["fontsize"]],
                        ["color", ["color"]],
                        ["para", ["ul", "ol", "paragraph"]],
                        ["height", ["height"]],
                        ["insert", ["link", "picture", "video"]],
                        ["view", ["fullscreen", "codeview", "help"]],
                    ],
                });
            }
        }
    });

    // Optional: Destroy Summernote when modal is hidden, to clean up
    $(document).on('hidden.bs.modal', '[id^=send_applicant_email_request_modal]', function () {
        var modalId = $(this).attr('id');
        var idParts = modalId.match(/send_applicant_email_request_modal(\d+)-(\d+)/);
        
        if (idParts) {
            var applicantId = idParts[1];
            var saleId = idParts[2];
            var emailBodyId = `#email_body_requested_${applicantId}_${saleId}`;

            // Destroy Summernote if it has been initialized
            if ($(emailBodyId).data('summernote')) {
                $(emailBodyId).summernote('destroy');
            }
        }
    });
	// Handle the email modal submission and open the schedule modal after email submission
     $(document).on('click', '[id^=submit_applicant_email_attended_btn_]', function () {
        var emailModal = $(this).closest('.modal');
        var applicantId = $(this).data('applicant-id');
        var saleId = $(this).data('sale-id');

        // Hide the email modal
        emailModal.modal('hide'); 

    });

    // Initialize Summernote for the email body textarea in the email modal
    $(document).on('shown.bs.modal', '[id^=send_applicant_email_attended_modal]', function () {
        var modalId = $(this).attr('id');
        var idParts = modalId.match(/send_applicant_email_attended_modal(\d+)-(\d+)/);
        
        if (idParts) {
            var applicantId = idParts[1];
            var saleId = idParts[2];
            var emailBodyId = `#email_body_attended_${applicantId}_${saleId}`;

            // Initialize Summernote if it hasn't been initialized yet
            if (!$(emailBodyId).data('summernote')) {
                $(emailBodyId).summernote({
                    height: 200,
                    minHeight: 100,
                    maxHeight: 400,
                    focus: true,
                    toolbar: [
                        ["style", ["bold", "italic", "underline", "clear"]],
                        ["font", ["strikethrough", "superscript", "subscript"]],
                        ["fontsize", ["fontsize"]],
                        ["color", ["color"]],
                        ["para", ["ul", "ol", "paragraph"]],
                        ["height", ["height"]],
                        ["insert", ["link", "picture", "video"]],
                        ["view", ["fullscreen", "codeview", "help"]],
                    ],
                });
            }
        }
    });

    // Optional: Destroy Summernote when modal is hidden, to clean up
    $(document).on('hidden.bs.modal', '[id^=send_applicant_email_attended_modal]', function () {
        var modalId = $(this).attr('id');
        var idParts = modalId.match(/send_applicant_email_attended_modal(\d+)-(\d+)/);
        
        if (idParts) {
            var applicantId = idParts[1];
            var saleId = idParts[2];
            var emailBodyId = `#email_body_attended_${applicantId}_${saleId}`;

            // Destroy Summernote if it has been initialized
            if ($(emailBodyId).data('summernote')) {
                $(emailBodyId).summernote('destroy');
            }
        }
    });
	
});



</script>
    <script src="{{ asset('js/crm_8102020.js') }}?v={{time()}}"></script>
@endsection