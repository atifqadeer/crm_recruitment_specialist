/*** global variables */
var crm_table = '';
var table = 'crm_sent_cv_sample';
var route = 'crm-sent-cv';

var columns = [
    { "data":"quality_added_date", "name": "quality_notes.quality_added_date" },
    { "data":"quality_added_time", "name": "quality_notes.quality_added_time", "orderable": false },
    { "data":"name", "name": "name", "orderable": false, "searchable": false },
    { "data":"applicant_name", "name": "applicants.applicant_name" },
    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
    { "data":"office_name", "name": "offices.office_name" },
    { "data":"unit_name", "name": "units.unit_name" },
    { "data":"postcode", "name": "sales.postcode" },
    { "data":"crm_note", "name": "crm_note" },
    { "data":"action", "name": "action", "orderable": false, "searchable": false }
];

function crm_tab_cvs(table, route, columns) {
    $.fn.dataTable.ext.errMode = 'throw';
    if ($.fn.DataTable.isDataTable("#"+table)) {
        $('#'+table).DataTable().clear().destroy();
    }
    crm_table = $('#'+table).DataTable({
        "processing": true,
        "serverSide": true,
        "order": [],
        "ajax": route,
        "columns": columns
    });
}

$(document).ready(function() {
    crm_tab_cvs(table, route, columns);

    /*** shows Reject button in Sent CV popup */
    $(document).on('change', '.crm_select_reason', function () {
        $('.reject_btn').css("display","block");
    });

    /*** Year selector */
    $(document).on('focus',".pickadate-year", function(){
        $(this).pickadate({
            selectYears: 4
        });
    });

    /*** Time picker */
    $(document).on('focus',".time_picker", function(){
        $('#'+$(this).attr('id')).AnyTime_picker({
            format: '%H:%i'
        });
    });

    $(document).on('click', '.crm-refresh', function () {
        crm_table.draw();
    });

    $(document).on('shown.bs.tab', '.nav-tabs a', function (event) {
        var datatable_name = $(this).data('datatable_name');
        var tab_href = $(this).attr('href').substr(1);

        switch (tab_href) {
            case 'CV_sent':
                table = 'crm_sent_cv_sample';
                route = 'crm-sent-cv';
                columns = [
                    { "data":"quality_added_date", "name": "quality_notes.quality_added_date" },
                    { "data":"quality_added_time", "name": "quality_notes.quality_added_time", "orderable": false },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"applicant_name", "name": "applicants.applicant_name" },
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_note" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'reject_CV':
                table = datatable_name;
                route = 'crm-reject-cv';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
					{ "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"applicant_name", "name": "applicants.applicant_name" },
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'request':
                table = datatable_name;
                route = 'crm-request';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
					{ "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"applicant_name", "name": "applicants.applicant_name" },
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'rejectByRequest':
                table = datatable_name;
                route = 'crm-reject-by-request';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
					{ "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"applicant_name", "name": "applicants.applicant_name" },
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'confirmation':
                table = 'crm_confirmation_cv_sample';
                route = 'crm-confirmation';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
					{ "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"interview_schedule", "name": "interviews.schedule_date" },
                    { "data":"applicant_name", "name": "applicants.applicant_name" },
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
			case 'rebook':
                table = 'crm_rebook_cv_sample';
                route = 'crm-rebook';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"applicant_name", "name": "applicants.applicant_name" },
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;	
            case 'pre-start':
                table = 'crm_pre_start_cv_sample';
                route = 'crm-pre-start-date';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
					{ "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"applicant_name", "name": "applicants.applicant_name" },
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
			case 'declined':
                table = 'crm_declined_cv_sample';
                route = 'crm-declined';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                    { "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"applicant_name", "name": "applicants.applicant_name" },
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;	
            case 'not_attended':
                table = 'crm_not_attended_cv_sample';
                route = 'crm-not-attended';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
					{ "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"applicant_name", "name": "applicants.applicant_name" },
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'start':
                table = 'crm_start_date_cv_sample';
                route = 'crm-start-date';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
					{ "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"applicant_name", "name": "applicants.applicant_name" },
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'start_date_hold':
                table = 'crm_start_date_hold_cv_sample';
                route = 'crm-start-date-hold';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
					{ "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"applicant_name", "name": "applicants.applicant_name" },
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'invoice_sent':
                table = 'crm_invoice_cv_sample';
                route = 'crm-invoice';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
					{ "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"applicant_name", "name": "applicants.applicant_name" },
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'dispute':
                table = 'crm_dispute_cv_sample';
                route = 'crm-dispute';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
					{ "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"applicant_name", "name": "applicants.applicant_name" },
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            case 'invoice_pending':
                table = 'crm_paid_cv_sample';
                route = 'crm-paid';
                columns = [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
					{ "data":"name", "name": "name", "orderable": false, "searchable": false },
                    { "data":"applicant_name", "name": "applicants.applicant_name" },
                    { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"job_details", "name": "job_details", "orderable": false, "searchable": false },
                    { "data":"office_name", "name": "offices.office_name" },
                    { "data":"unit_name", "name": "units.unit_name" },
                    { "data":"postcode", "name": "sales.postcode" },
                    { "data":"crm_note", "name": "crm_notes.details" },
                    { "data":"action", "name": "action", "orderable": false, "searchable": false }
                ];
                crm_tab_cvs(table, route, columns);
                break;
            default:
        }
    });

    /*** sent cv tab actions */
    $(document).on('click', '.sent_cv_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $sent_cv_form = $('#sent_cv_form'+app_sale);
        var $sent_cv_alert = $('#sent_cv_alert' + app_sale);
        var details = $.trim($("#sent_cv_details" + app_sale).val());
        if (details) {
            $.ajax({
                // url: "{{ route('sentCvAction') }}",
                url: "sent-cv-action",
                type: "POST",
                data: $sent_cv_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $sent_cv_alert.html(response);
                    setTimeout(function () {
                        $('#clear_cv' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $sent_cv_alert.html(raw_html);
                }
            });
        } else {
            $sent_cv_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $sent_cv_form.trigger('reset');
        setTimeout(function () {
            $sent_cv_alert.html('');
        }, 2000);
        return false;
    });

    /*** rejected cv tab */
    $(document).on('click', '.rejected_cv_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $revert_sent_cv_form = $('#revert_sent_cv_form'+app_sale);
        var $revert_sent_cv_alert = $('#revert_sent_cv_alert' + app_sale);
        var details = $.trim($("#revert_sent_cv_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "revert-sent-cv-action",
                type: "POST",
                data: $revert_sent_cv_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $revert_sent_cv_alert.html(response);
                    setTimeout(function () {
                        $('#revert_sent_cvs' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 2000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $revert_sent_cv_alert.html(raw_html);
                }
            }).then(function (data) {
                setTimeout(function () {
                    $revert_sent_cv_form.trigger('reset');
                    $revert_sent_cv_alert.html('');
                }, 2000);
            });
        } else {
            $revert_sent_cv_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        return false;
    });

    /*** Request tab actions */
    $(document).on('click', '.schedule_interview_submit', function (event) {
        event.preventDefault();
        var app_sale = $(this).data('app_sale');
        var $schedule_interview_form = $('#schedule_interview_form'+app_sale);
        var $schedule_interview_alert = $('#schedule_interview_alert' + app_sale);
        var schedule_date = $.trim($("#schedule_date" + app_sale).val());
        var schedule_time = $.trim($("#schedule_time" + app_sale).val());
        if (schedule_date && schedule_time) {
            $.ajax({
                url: "schedule-interview",
                type: "POST",
                data: $schedule_interview_form.serialize(),
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $schedule_interview_alert.html(response);
                    setTimeout(function () {
                        $('#schedule_interview' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $schedule_interview_alert.html(raw_html);
                }
            });
        } else {
            $schedule_interview_alert.html('<p class="text-danger">Kindly Provide Date and Time</p>');
        }
        $schedule_interview_form.trigger('reset');
        setTimeout(function () {
            $schedule_interview_alert.html('');
        }, 2000);
        return false;
    });

    $(document).on('click', '.request_cv_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $request_cv_form = $('#request_cv_form'+app_sale);
        var $request_cv_alert = $('#request_cv_alert' + app_sale);
        var details = $.trim($("#request_cv_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "request-action",
                type: "POST",
                data: $request_cv_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $request_cv_alert.html(response);
                    setTimeout(function () {
                        $('#confirm_cv' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $request_cv_alert.html(raw_html);
                }
            });
        } else {
            $request_cv_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $request_cv_form.trigger('reset');
        setTimeout(function () {
            $request_cv_alert.html('');
        }, 2000);
        return false;
    });

    /*** rejected by request tab actions */
    $(document).on('click', '.revert_cv_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $revert_cv_form = $('#revert_cv_form'+app_sale);
        var $revert_cv_alert = $('#revert_cv_alert' + app_sale);
        var details = $.trim($("#revert_cv_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "reject-by-request-action",
                type: "POST",
                data: $revert_cv_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $revert_cv_alert.html(response);
                    setTimeout(function () {
                        $('#revert' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 2000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $revert_cv_alert.html(raw_html);
                }
            }).then(function (response) {
                $revert_cv_form.trigger('reset');
                setTimeout(function () {
                    $revert_cv_alert.html('');
                }, 2000);
            });
        } else {
            $revert_cv_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        return false;
    });

    /*** confirmation tab actions */
    $(document).on('click', '.after_interview_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $after_interview_form = $('#after_interview_form'+app_sale);
        var $after_interview_alert = $('#after_interview_alert' + app_sale);
        var details = $.trim($("#after_interview_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "after-interview-action",
                type: "POST",
                data: $after_interview_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $after_interview_alert.html(response);
                    setTimeout(function () {
                        $('#after_interview' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $after_interview_alert.html(raw_html);
                }
            });
        } else {
            $after_interview_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $after_interview_form.trigger('reset');
        setTimeout(function () {
            $after_interview_alert.html('');
        }, 2000);
        return false;
    });

	/*** rebook tab actions */
    $(document).on('click', '.rebook_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $rebook_form = $('#rebook_form'+app_sale);
        var $rebook_alert = $('#rebook_alert' + app_sale);
        var details = $.trim($("#rebook_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "rebook-action",
                type: "POST",
                data: $rebook_form.serialize() + '&form_action' + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $rebook_alert.html(response);
                    setTimeout(function () {
                        $('#rebook' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $rebook_alert.html(raw_html);
                }
            });
        } else {
            $rebook_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $rebook_form.trigger('reset');
        setTimeout(function () {
            $rebook_alert.html('');
        }, 2000);
        return false;
    });

    /*** attended to pre-start date tab actions */
    $(document).on('click', '.accept_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $accept_form = $('#accept_form'+app_sale);
        var $accept_alert = $('#accept_alert' + app_sale);
        var details = $.trim($("#accept_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "attended-to-pre-start-action",
                type: "POST",
                data: $accept_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $accept_alert.html(response);
                    setTimeout(function () {
                        $('#accept' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $accept_alert.html(raw_html);
                }
            });
        } else {
            $accept_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $accept_form.trigger('reset');
        setTimeout(function () {
            $accept_alert.html('');
        }, 2000);
        return false;
    });

	/*** declined tab actions */
    $(document).on('click', '.declined_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $declined_revert_form = $('#declined_revert_form'+app_sale);
        var $declined_revert_alert = $('#declined_revert_alert' + app_sale);
        var details = $.trim($("#declined_revert_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "declined-action",
                type: "POST",
                data: $declined_revert_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $declined_revert_alert.html(response);
                    setTimeout(function () {
                        $('#declined_revert' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $declined_revert_alert.html(raw_html);
                }
            });
        } else {
            $declined_revert_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $declined_revert_form.trigger('reset');
        setTimeout(function () {
            $declined_revert_alert.html('');
        }, 2000);
        return false;
    });
	
    /*** not attended tab actions */
    $(document).on('click', '.revert_attended_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $revert_attended_form = $('#revert_attended_form'+app_sale);
        var $revert_attended_alert = $('#revert_attended_alert' + app_sale);
        var details = $.trim($("#revert_attended_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "not-attended-action",
                type: "POST",
                data: $revert_attended_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $revert_attended_alert.html(response);
                    setTimeout(function () {
                        $('#revert_attended' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 2000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $revert_attended_alert.html(raw_html);
                }
            }).then(function (response) {
                $revert_attended_form.trigger('reset');
                setTimeout(function () {
                    $revert_attended_alert.html('');
                }, 2000);
            });
        } else {
            $revert_attended_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        return false;
    });

    /*** start date tab actions */
    $(document).on('click', '.start_date_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $start_date_form = $('#start_date_form'+app_sale);
        var $start_date_alert = $('#start_date_alert' + app_sale);
        var details = $.trim($("#start_date_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "start-date-action",
                type: "POST",
                data: $start_date_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $start_date_alert.html(response);
                    setTimeout(function () {
                        $('#start_date' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $start_date_alert.html(raw_html);
                }
            });
        } else {
            $start_date_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $start_date_form.trigger('reset');
        setTimeout(function () {
            $start_date_alert.html('');
        }, 2000);
        return false;
    });

    /*** start date hold tab actions */
    $(document).on('click', '.start_date_hold_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $start_date_hold_form = $('#start_date_hold_form'+app_sale);
        var $start_date_hold_alert = $('#start_date_hold_alert' + app_sale);
        var details = $.trim($("#start_date_hold_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "start-date-hold-action",
                type: "POST",
                data: $start_date_hold_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $start_date_hold_alert.html(response);
                    setTimeout(function () {
                        $('#start_date_hold' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 2000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $start_date_hold_alert.html(raw_html);
                }
            }).then(function (response) {
                $start_date_hold_form.trigger('reset');
                setTimeout(function () {
                    $start_date_hold_alert.html('');
                }, 2000);
            });
        } else {
            $start_date_hold_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        return false;
    });

    /*** invoice tab actions */
    $(document).on('click', '.invoice_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $invoice_form = $('#invoice_form'+app_sale);
        var $invoice_alert = $('#invoice_alert' + app_sale);
        var details = $.trim($("#invoice_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "invoice-action",
                type: "POST",
                data: $invoice_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $invoice_alert.html(response);
                    setTimeout(function () {
                        $('#invoice' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $invoice_alert.html(raw_html);
                }
            });
        } else {
            $invoice_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        $invoice_form.trigger('reset');
        setTimeout(function () {
            $invoice_alert.html('');
        }, 2000);
        return false;
    });

    /*** dispute tab actions */
    $(document).on('click', '.revert_invoice_submit', function (event) {
        event.preventDefault();
        var form_action = $(this).val();
        var app_sale = $(this).data('app_sale');
        var $revert_invoice_form = $('#revert_invoice_form'+app_sale);
        var $revert_invoice_alert = $('#revert_invoice_alert' + app_sale);
        var details = $.trim($("#revert_invoice_details" + app_sale).val());
        if (details) {
            $.ajax({
                url: "dispute-action",
                type: "POST",
                data: $revert_invoice_form.serialize() + '&' + form_action + '=' + form_action,
                success: function (response) {
                    $.fn.dataTable.ext.errMode = 'throw';
                    crm_table.draw();
                    $revert_invoice_alert.html(response);
                    setTimeout(function () {
                        $('#revert_invoice' + app_sale).modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 2000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $revert_invoice_alert.html(raw_html);
                }
            }).then(function (response) {
                $revert_invoice_form.trigger('reset');
                setTimeout(function () {
                    $revert_invoice_alert.html('');
                }, 2000);
            });
        } else {
            $revert_invoice_alert.html('<p class="text-danger">Kindly Provide Details</p>');
        }
        return false;
    });

});
