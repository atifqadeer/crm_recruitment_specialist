@extends('layouts.app')
@section('style')

    <script>
        var columns = [
            { "data":"updated_at", "name": "applicants.updated_at","orderable": true },
            { "data":"applicant_added_time", "name": "applicant_added_time", "orderable": false },
			{ "data":"agent_name", "name": "agent_name"},
            { "data":"applicant_name", "name": "applicants.applicant_name" },
            { "data":"applicant_job_title", "name": "applicants.applicant_job_title","orderable": true },
            {
                "data": "job_category",
                "name": "applicants.job_category",
                "render": function(data, type, row) {
                    return data.toUpperCase();
                },
				"orderable": true
            },
			{
				"data": "department",
				"name": "applicants.department",
				"render": function(data, type, row, meta) {
					if (type === 'display' || type === 'filter') {
						// Return hyphen immediately if data is empty or just a hyphen
						if (!data || data.trim() === '-' || data.trim() === '') {
							return '-';
						}

						// Process the string if it contains actual data
						return data
							.toLowerCase()  // Convert to lowercase
							.replace(/-/g, ' ')  // Replace hyphens with spaces
							.split(' ')     // Split into words
							.filter(word => word.length > 0)  // Remove empty strings
							.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize words
							.join(' ');    // Rejoin into single string
					}
					return data;  // Return original for sorting/other operations
				},
				"orderable": true
			},
			{
				"data": "sub_department",
				"name": "applicants.sub_department",
				"render": function(data, type, row, meta) {
					if (type === 'display' || type === 'filter') {
						if (typeof data === 'string') {
							return data
								.toLowerCase()
								.split(' ')  // Split the string into an array of words
								.map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize each word
								.join(' ');  // Join back into a single string
						}
						return data;
					}
					return data;
				},
				"orderable": true
			},
            { "data":"applicant_postcode", "name": "applicants.applicant_postcode", "orderable": true },
            { "data":"applicant_phone", "name": "applicants.applicant_phone" },
            { "data":"download", "name": "download", "orderable": false },
            { "data":"updated_cv", "name": "updated_cv", "orderable": false },
            { "data":"upload", "name": "upload", "orderable": false },
            { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
            { "data":"applicant_source", "name": "applicants.applicant_source" },
            { "data":"applicant_notes", "name": "applicants.applicant_notes" },
            { "data":"history", "name": "history", "orderable": false },
            { "data":"status", "name": "applicants.status", "orderable": true}
        ];

       var blockedColumns = [{ "data": "checkbox", "orderable": false, "searchable": false }].concat(columns);

      $(document).ready(function() {
           $.fn.dataTable.ext.errMode = 'none';
            var app_id = $("#hidden_job_value").val();
    
            // Initialize DataTables for each tab but only load data for the first tab initially
            var allApplicants = $('#all_resources_table').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": "{!! url('getlast2MonthsApp') !!}/" + app_id,
                "order": [[0, 'desc']],
                "columns": columns
            });

       		var notInterested = $('#not_interested_resources_table').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": "{!! url('getlast2MonthsAppNotInterested') !!}/" + app_id,
                "order": [[0, 'desc']],
                "columns": columns,
                "deferRender": true  // Defer rendering until the tab is shown
            });

            var blockedApplicants = $('#blocked_resources_table').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": "{!! url('getlast2MonthsAppBlocked') !!}/" + app_id,
                "order": [[0, 'desc']],
                "columns": blockedColumns,
                "deferRender": true  // Defer rendering until the tab is shown
            });

            // Add a click event listener to the tab links
            $('.nav-tabs a').on('shown.bs.tab', function(e) {
                var targetTab = $(e.target).attr("href");
                $("#unblockButtonContainer").hide();
                // Check which tab is active and reload DataTable accordingly
                if (targetTab === "#all_resources") {
                    allApplicants.ajax.reload(); // Reload data for the active tab
                } else if (targetTab === "#not_interested_resources") {
                    notInterested.ajax.reload();
                } else if (targetTab === "#blocked_resources") {
                    blockedApplicants.ajax.reload();
                    $("#unblockButtonContainer").show(); // Show the Unblock button
                }
            });
      });

    </script>

@endsection 
@section('content')
    <!-- Main content -->
    <div class="content-wrapper">
        <input type="hidden" id="hidden_job_value" value="{{ $id}}">

        <!-- Page header -->
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
					@php
                        use Illuminate\Support\Facades\Request;
                    
                        $jobType = '';
                        $lastSegment = Request::segment(count(Request::segments()));
                    
                        if ($lastSegment == 44) {
                            $jobType = 'Nurse';
                        } elseif ($lastSegment == 45) {
                            $jobType = 'Non Nurse';
                        } elseif ($lastSegment == 46) {
                            $jobType = 'Specialist';
						} elseif ($lastSegment == 47) {
                            $jobType = 'Chef';
						} elseif ($lastSegment == 48) {
                            $jobType = 'Nursery';
                        }
                    @endphp
                   <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Resources </span> - {{ $jobType }} Applicants (
                        @if($interval == 7)Last 7 Days @elseif($interval == 21) Last 21 Days @elseif($interval == 60) All @endif )
                    </h5>
                </div>
            </div>

             <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Resources</a>
                        <a href="#" class="breadcrumb-item">{{ $jobType }}</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">@if($interval == 7)Last 7 Days @elseif($interval == 21) Last 21 Days @elseif($interval == 60) All @endif</span>
                    </div>
                </div>
                <div class="d-flex align-items-center float-right pr-3">
                    Sent: <span class="status-block class_success mr-2"></span>
                    Reject: <span class="status-block class_danger mr-2"></span>
                    No Job: <span class="status-block class_noJob mr-2"></span>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">

            <!-- Default ordering -->
            <div class="card">
               <div class="card-header header-elements-inline">
                    <ul class="nav nav-tabs nav-tabs-highlight">
                        <li class="nav-item">
                            <a href="#all_resources" class="nav-link active legitRipple" data-toggle="tab" data-datatable_name="all_resources_table">Active Applicants</a>
                        </li>
                        <li class="nav-item">
                            <a href="#not_interested_resources" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="not_interested_resources_table">Not Interested Applicants</a>
                        </li>
                      <li class="nav-item">
                            <a href="#blocked_resources" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="blocked_resources_table">Blocked Applicants</a>
                        </li>
                    </ul>
                </div>
				<div class="card-body">
					 <div id="unblockButtonContainer" style="display:none;">
                        <button id="submitSelectedButton" class="btn bg-teal legitRipple float-right">
                            Unblock
                        </button>
                    </div>
                    @can('applicant_export')
                            {{ Form::open(array('route' => 'export2_months_applicants','method' => 'GET' )) }}
                            <button type="submit" class="btn bg-slate-800 legitRipple float-right mr-2">
                                <i class="icon-cloud-upload"></i>
                                &nbsp;Export</button>
                            <input type="hidden" id="hidden_job_value" name="hidden_job_value" value="{{$id}}">
                            {{ Form::close() }}
                    @endcan
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
                <div id="import_applicant_cv" class="modal fade">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Import CV</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('import_applicantCv') }}" method="post" enctype="multipart/form-data">
                                    @csrf()
                                    <div class="form-group row">
                                        <div class="col-lg-12">
                                            <input type="file" name="applicant_cv" class="file-input-advanced" data-fouc>
                                        </div>
                                    </div>
                                   
                                   <div class="modal-body-id">
                                        <input type="hidden" name="page_url" id="page_url" value="{{url()->current()}}"/>
                                    </div>
                                    <div class="modal-body-id">
                                        <input type="hidden" name="applicant_id" id="applicant_id" value=""/>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active" id="all_resources">
                        <table class="table table-hover table-striped" id="all_resources_table">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
								<th>Sent By</th>
                                <th>Name</th>
                                <th>Job Title</th>
                                <th>Category</th>
								<th>Department</th>
								<th>Sub Department</th>
                                <th>Postcode</th>
                                <th>Phone</th>
                                <th>Applicant CV</th>
                                <th>Updated CV</th>
                                <th>Upload CV</th>
                                <th>Landline</th>
                                <th>Source</th>
                                <th>Notes</th>
                                <th>History</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-pane" id="not_interested_resources">
                        <table class="table table-hover table-striped" id="not_interested_resources_table">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
								<th>Sent By</th>
                                <th>Name</th>
                                <th>Job Title</th>
                                <th>Category</th>
								<th>Department</th>
								<th>Sub Department</th>
                                <th>Postcode</th>
                                <th>Phone</th>
                                <th>Applicant CV</th>
                                <th>Updated CV</th>
                                <th>Upload CV</th>
                                <th>Landline</th>
                                <th>Source</th>
                                <th>Notes</th>
                                <th>History</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <div class="tab-pane" id="blocked_resources">
                        <table class="table table-hover table-striped" id="blocked_resources_table">
                            <thead>
                            <tr>
								<th><input type="checkbox" id="master-checkbox"></th>
                                <th>Date</th>
                                <th>Time</th>
								<th>Sent By</th>
                                <th>Name</th>
                                <th>Job Title</th>
                                <th>Category</th>
								<th>Department</th>
								<th>Sub Department</th>
                                <th>Postcode</th>
                                <th>Phone</th>
                                <th>Applicant CV</th>
                                <th>Updated CV</th>
                                <th>Upload CV</th>
                                <th>Landline</th>
                                <th>Source</th>
                                <th>Notes</th>
                                <th>History</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /default ordering -->

        </div>
        <!-- /content area -->

@endsection
@section('script')
<script>
	
	 function dateSorting(date_timestamp) {
     
        var a = new Date(date_timestamp );
        // var a = date_timestamp * 1000;
        console.log(a);
        var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var days = ['1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th', '13th', '14th', '15th', '16th', '17th', '18th', '19th', '20th', '21st', '22nd', '23rd', '24th', '25th', '26th', '27th', '28th', '29th', '30th', '31st'];
        var year = a.getFullYear();
        var month = months[a.getMonth()];
        var date = days[a.getDate()-1];
        var date_time = date + ' ' + month + ' ' + year;

        return date_time;
    }
	
    $(document).on('click', '.reject_history', function () {
        var applicant = $(this).data('applicant');
        $.ajax({
            url: "{{ route('rejectedHistory') }}",
            type: "post",
            data: {
                _token: "{{ csrf_token() }}",
                applicant: applicant
            },
            success: function(response){
                $('#applicant_rejected_history'+applicant).html(response);
            },
            error: function(response){
                var raw_html = '<p>WHOOPS! Something Went Wrong!!</p>';
                $('#applicant_rejected_history'+applicant).html(raw_html);
            }
        });
    });
	
	$(document).on('click', '.notes_form_submit', function (event) {
        // event.preventDefault();
        // alert('sdfafas');
        var note_key = $(this).data('note_key');
        var detail = $('textarea#sent_cv_details'+note_key).val();
        // var reason =$(#reason option:selected).val();
        var reason = $("#reason"+note_key).val();
        var $notes_form = $('#notes_form'+note_key);
        var $notes_alert = $('#notes_alert' +note_key);
        // var note_details = $.trim($("#sent_cv_details"+note_key).val());
        // alert(reason);
        if (detail=='' || reason==0) {
            $notes_alert.html('<p class="text-danger">Please Fill Out All The Fields!</p>');
            $notes_form.trigger('reset');
        setTimeout(function () {
            $notes_alert.html('');
        }, 2000);
        return false;
        } 
        return true;
       
    });
	$(document).on("click", ".import_cv", function () {
     var app_id = $(this).data('id');
    //  alert(app_id);
     $(".modal-body #applicant_id").val(app_id);
});
</script>
@endsection
