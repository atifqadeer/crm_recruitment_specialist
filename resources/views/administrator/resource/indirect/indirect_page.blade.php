@extends('layouts.app')

@section('style')
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}">
<style>
    .custom-input {
    padding-left: 10px;
    border: #303140 solid 1px;
    width: 120px;
    /* border-radius: 5px; */
    box-shadow: 0 0 5px bisque;
    margin: 7px;
}

</style>
    <script>
    var columns = [
        { "data": "updated_at", "name": "applicants.updated_at" },
        { "data": "updated_time", "name": "updated_time", "orderable": false },
        { "data": "applicant_name", "name": "applicants.applicant_name" },
        { "data": "applicant_email", "name": "applicants.applicant_email" },
        { "data": "applicant_job_title", "name": "applicants.applicant_job_title" },
        {
            "data": "job_category",
            "name": "applicants.job_category",
            "render": function(data, type, row) {
                return data.toUpperCase();
            }
        },
        { "data": "applicant_postcode", "name": "applicants.applicant_postcode", "orderable": true },
        { "data": "applicant_phone", "name": "applicants.applicant_phone", "orderable": false  },
        { "data": "download", "name": "download", "orderable": false },
        { "data": "updated_cv", "name": "updated_cv", "orderable": false },
        { "data": "upload", "name": "upload", "orderable": false },
        { "data": "applicant_homePhone", "name": "applicants.applicant_homePhone", "orderable": false  },
        { "data": "applicant_source", "name": "applicants.applicant_source" },
        { "data": "applicant_notes", "name": "applicants.applicant_notes", "orderable": false  },
        { "data": "history", "name": "history", "orderable": false },
        { "data": "status", "name": "applicants.status" }
    ];

    $(document).ready(function() {
    $.fn.dataTable.ext.errMode = 'none';

    var job = $("#hidden_job_value").val();
    var currentDate = new Date().toISOString().split('T')[0];
    $('#filterDate').val(currentDate);

    function showTableLoader(tableId) {
        var loaderRow = `
            <tr class="loader-row">
                <td colspan="100%" style="text-align:center; padding:20px;">
                    <div class="spinner-border text-primary" role="status"></div>
                </td>
            </tr>`;
        $(tableId + ' tbody').html(loaderRow);
    }

   function fetchAndLoadData(selectedDate, isUpdatedFilterEnabled = false) {
	// Show loader inside each table
	showTableLoader('#all_resources_table');
	showTableLoader('#interested_resources_table');
	showTableLoader('#not_interested_resources_table');

	var updatedDateParam = isUpdatedFilterEnabled ? 1 : 0; // Convert boolean to 1 or 0

	$.ajax({
		url: "{!! url('getIndirectResourcesAjax') !!}/" + job + "/all",
		method: "GET",
		data: {
			filterBySaleDate: selectedDate,
			filterByUpdatedSale: updatedDateParam
		},
		success: function(response) {
			var allData = response.data;
			var interestedData = allData.filter(applicant => applicant.temp_not_interested == '0');
			var notInterestedData = allData.filter(applicant => applicant.temp_not_interested == '1');
			$('#showSalesCount').html(response.total_sale_count); // Correct ID

			allApplicants.clear().rows.add(allData).draw();
			interested.clear().rows.add(interestedData).draw();
			notInterested.clear().rows.add(notInterestedData).draw();

			// Hide loader inside checkbox label
			$('#loadingSpinner').hide();
		},
		error: function() {
			$('.loader-row').remove();
			var errorRow = `<tr><td colspan="100%" style="text-align:center;">Error loading data</td></tr>`;
			$('#all_resources_table tbody').html(errorRow);
			$('#interested_resources_table tbody').html(errorRow);
			$('#not_interested_resources_table tbody').html(errorRow);

			// Hide loader inside checkbox label on error
			$('#loadingSpinner').hide();
		}
	});
}


    // Initialize DataTables
    var allApplicants = $('#all_resources_table').DataTable({
        processing: true,
        order: [[0, 'desc']],
        columns: columns,
        deferRender: true,
    });

    var interested = $('#interested_resources_table').DataTable({
        processing: true,
        order: [[0, 'desc']],
        columns: columns,
        deferRender: true,
    });

    var notInterested = $('#not_interested_resources_table').DataTable({
        processing: true,
        order: [[0, 'desc']],
        columns: columns,
        deferRender: true,
    });

    // Initial Data Fetch
    fetchAndLoadData(currentDate);

    // Search Function
    $('#searchInput').on('keyup', function() {
        var value = $(this).val();
        allApplicants.search(value).draw();
        interested.search(value).draw();
        notInterested.search(value).draw();
    });

    // Date Filter Button
    $('#submitDateButton').on('click', function() {
        var selectedDate = $('#filterDate').val();
		var isUpdatedFilterEnabled = $(this).prop('checked'); // Get checkbox state
		fetchAndLoadData(selectedDate, isUpdatedFilterEnabled);
    });

    // Checkbox Event
    $('#filterCheckbox').on('change', function() {
		var selectedDate = $('#filterDate').val();
		var isUpdatedFilterEnabled = $(this).prop('checked'); // Get checkbox state
		$('#loadingSpinner').show(); // Show spinner
		fetchAndLoadData(selectedDate, isUpdatedFilterEnabled);
	});



    // Tab Switching Logic
    $('.nav-tabs a').on('shown.bs.tab', function(e) {
        var targetTab = $(e.target).attr("href");
        if (targetTab === "#all_resources") {
            allApplicants.draw();
        } else if (targetTab === "#interested_resources") {
            interested.draw();
        } else if (targetTab === "#not_interested_resources") {
            notInterested.draw();
        }
    });
});

</script>


    	<style>

    /* Hide the checkbox itself */
    .form-check-input {
        display: none;
    }

    /* Style the label as a button */
    .btn-like {
        display: inline-block;
        padding: 8px 16px;
        background-color: #e9ecef; /* Light gray background for unchecked state */
        color: #495057; /* Dark gray text color */
        border: 1px solid #ced4da; /* Light border */
        border-radius: 4px; /* Rounded corners */
        cursor: pointer;
        transition: background-color 0.3s, color 0.3s, border-color 0.3s;
    }

    /* Change the appearance when the checkbox is checked */
    .form-check-input:checked + .btn-like {
        background-color: #007bff; /* Blue background for checked state */
        color: #fff; /* White text color */
        border-color: #007bff; /* Blue border */
    }

    /* Optional: Add hover effect */
    .btn-like:hover {
        background-color: #0056b3; /* Darker blue on hover */
        border-color: #0056b3;
        color: #fff;
    }

    /* Optional: Style for the checkbox label */
    .checkbox-label {
        display: inline-block;
    }
</style>

@endsection 

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">
        <input type="hidden" id="hidden_job_value" value="{{$category}}">

        <!-- Page header -->
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
					@php
                        $jobType = '';
                        if ($category == 'nurse') {
                            $jobType = 'Nurse';
                        } elseif ($category == 'nonnurse') {
                            $jobType = 'Non Nurse';
                        } elseif ($category == 'specialist') {
                            $jobType = 'Specialist';
						} elseif ($category == 'chef') {
                            $jobType = 'Chef';
						} elseif ($category == 'nursery') {
                            $jobType = 'Nursery';
                        }
                    @endphp
                   <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Resources </span> - Indirect - {{ $jobType }} Applicants
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Resources</a>
                        <a href="#" class="breadcrumb-item">{{ $jobType }}</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                    </div>
                </div>
                <div class="d-flex align-items-center pr-3">
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
                              <a href="#all_resources" class="nav-link active legitRipple" data-toggle="tab" data-datatable_name="all_resources_table">All Applicants</a>
                          </li>
                        <li class="nav-item">
                              <a href="#interested_resources" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="interested_resources_table">Interested Applicants</a>
                          </li>
                        <li class="nav-item">
                              <a href="#not_interested_resources" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="not_interested_resources_table">Not Interested Applicants</a>
                          </li> 
                      </ul>
					   <!-- Row to display checkbox and date inline -->
						<div class="row justify-content-end align-items-center">
							<div class="col-auto">
								Sales Count: <span class="badge badge-success" id="showSalesCount">0</span>
							</div>
							<!-- Date Input Field -->
							<div class="col-auto ml-3">
								<div class="form-group">
									<label for="filterDate" class="ml-2 d-none">Select Date:</label>
									<input type="date" class="form-control" value="" id="filterDate">
								</div>
							</div>

							<!-- Submit Button -->
							<div class="col-auto">
								<button id="submitDateButton" class="btn btn-primary">
									Submit Date
								</button>
							</div>

							<!-- Checkbox -->
							<div class="col-auto">
								<div class="form-check">
									<input type="checkbox" class="form-check-input" id="filterCheckbox">
									<label class="form-check-label btn-like" for="filterCheckbox">
										<span class="checkbox-label">Add Updated Sales Filter</span>
										<!-- Loader inside the label -->
										<span id="loadingSpinner" class="spinner-border text-sm" role="status" style="display: none; width: 1rem !important; height: 1rem !important;">
											<span class="sr-only">Loading...</span>
										</span>
									</label>
								</div>
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
                
                <div class="tab-content">
                    <div class="tab-pane active" id="all_resources">
                        <table class="table table-hover table-striped" id="all_resources_table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
									{{-- <th>Sent By</th> --}}
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Job Title</th>
                                    <th>Category</th>
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
                    <div class="tab-pane" id="interested_resources">
                        <table class="table table-hover table-striped" id="interested_resources_table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
									{{-- <th>Sent By</th> --}}
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Job Title</th>
                                    <th>Category</th>
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
									{{-- <th>Sent By</th> --}}
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Job Title</th>
                                    <th>Category</th>
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
@section('js_file')
    <script src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}"></script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
@endsection
		
@section('script')
<script>
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
	
	$(document).on('click', '.app_notes_form_submit', function (event) {
        // event.preventDefault();
        // alert('sdfafas');
        var note_key = $(this).data('note_key');
        var detail = $('textarea#sent_cv_details'+note_key).val();

        // var reason =$(#reason option:selected).val();
        var reason = $("#reason"+note_key).val();

        var $notes_form = $('#app_notes_form'+note_key);
        var $notes_alert = $('#app_notes_alert' +note_key);
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