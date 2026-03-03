@extends('layouts.app')
@section('style')

    <script>

      $(document).ready(function() {
        $.fn.dataTable.ext.errMode = 'none';
          $('#last_2_months_blocked_sample').DataTable({
               "processing": true,
               "serverSide": true,
               "ajax":"getlast2MonthsBlockedAppAjax",
                "order": [[ 0, 'desc' ]],
               "columns": [
				   { "data": "checkbox", orderable:false, searchable:false},
                   { "data":"updated_at", "name": "applicants.updated_at" },
                   { "data":"applicant_added_time", "name": "applicant_added_time", "orderable": false },
                   { "data":"applicant_name", "name": "applicants.applicant_name" },
                   { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
				   {
					   "data": "job_category",
					   "name": "applicants.job_category",
					   "render": function(data, type, row) {
						   return data.toUpperCase();
					   }
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
                   { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                   { "data":"applicant_source", "name": "applicants.applicant_source" },
                   { "data":"applicant_notes", "name": "applicants.applicant_notes" },
                   { "data":"history", "name": "history", "orderable": false },
                   { "data":"status", "name": "status", "orderable": false }
               ]
          });

      });

    </script>

@endsection 
@section('content')
    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Applicant Blocked</span> -
                        @if($interval == 7)Last 7 Days @elseif($interval == 21) Last 21 Days @elseif($interval == 60) All @endif
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Resources</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">@if($interval == 7)Last 7 Days @elseif($interval == 21) Last 21 Days @elseif($interval == 60) Blocked @endif</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">

            <!-- Default ordering -->
            <div class="card">
                <div class="card-header header-elements-inline">
                    <h5 class="card-title">Blocked Applicants -
                        @if($interval == 7)
                            Last 7 Days - Nurses
                        @elseif($interval == 21)
                            Last 21 Days
                        @elseif($interval == 60)
                            All Blocked Applicants
                        @endif
                    </h5>
                </div>
				<div class="card-body">
                   
                    <p></p>
                    <button id="submitSelectedButton" class="btn bg-teal legitRipple float-right">
                        Unblock
                    </button>
                    <div id="success-message" class="alert alert-success" style="display: none;"></div>
                    <div id="error-message" class="alert alert-danger" style="display: none;"></div> 
                    @can('applicant_export')
                    <a href="#" class="btn bg-slate-800 legitRipple float-right"  data-controls-modal="#export_applicant_action"
                                           data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                           data-target="#export_applicant_action" style="margin-right:20px;">
                        <i class="icon-cloud-upload"></i>
                        &nbsp;Export</a>
                    @endcan
					
                    
                </div>
                <table class="table table-hover table-striped" id="last_2_months_blocked_sample">
                    <thead>
                    <tr>
						<th><input type="checkbox" id="master-checkbox"></th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Name</th>
                        <th>Job Title</th>
                        <th>Category</th>
						<th>Department</th>
						<th>Sub Department</th>
                        <th>Postcode</th>
                        <th>Phone</th>
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
            <!-- /default ordering -->

        </div>
        <!-- /content area -->

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
	
	// var columns = [
    //     { "data": "checkbox", orderable:false, searchable:false},           
    //     { "data":"updated_at", "name": "applicants.updated_at" },
    //     { "data":"applicant_added_time", "name": "applicant_added_time", "orderable": false },
    //     { "data":"applicant_name", "name": "applicants.applicant_name" },
    //     { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
    //     { "data":"job_category", "name": "applicants.job_category" },
    //     { "data":"applicant_postcode", "name": "applicants.applicant_postcode", "orderable": true },
    //     { "data":"applicant_phone", "name": "applicants.applicant_phone" },
    //     { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
    //     { "data":"applicant_source", "name": "applicants.applicant_source" },
    //     { "data":"applicant_notes", "name": "applicants.applicant_notes" },
    //     { "data":"history", "name": "history", "orderable": false },
    //     { "data":"status", "name": "status", "orderable": false }
    // ];


//    var crm_table = $('#last_2_months_blocked_sample').DataTable({
//         "processing": true,
//         "serverSide": true,
//         "order": [],
//         "ajax": 'getlast2MonthsBlockedAppAjax',
//         "columns": columns
//     });
$('#master-checkbox').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('.applicant_checkbox').prop('checked', isChecked);

        // Manually toggle the DataTables selected class
        $('.applicant_checkbox').each(function() {
            var $row = $(this).closest('tr');
            if (isChecked) {
                $row.addClass('selected');
            } else {
                $row.removeClass('selected');
            }
        });
    });

    // Add a listener to individual checkboxes to update the master checkbox state
    $(document).on('change', '.applicant_checkbox', function() {
        var allCheckboxesChecked = $('.applicant_checkbox:checked').length === $('.applicant_checkbox').length;
        $('#master-checkbox').prop('checked', allCheckboxesChecked);

        // Manually toggle the DataTables selected class
        var $row = $(this).closest('tr');
        if ($(this).prop('checked')) {
            $row.addClass('selected');
        } else {
            $row.removeClass('selected');
        }
    });
    // Add a listener to the "Select All" button for additional actions
    $('#submitSelectedButton').on('click', function() {

        var selectedIds = [];

        // Get selected IDs
        $('.applicant_checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        // Submit AJAX request
        $.ajax({
            url: '/blocked-applicant-revert-all', // Update the URL to match your route
            type: 'POST',
            data: { ids: selectedIds,_token: $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    // Reload the DataTable
                    $('#last_2_months_blocked_sample').DataTable().ajax.reload();

                    // Display a success message
                    $('#success-message').text(response.message).show();

                    // Hide the success message after 5 seconds
                    setTimeout(function() {
                        $('#success-message').hide();
                    }, 5000);

                    // Hide the error message if it was previously shown
                    $('#error-message').hide();
                } else {
                    // Display an error message
                    $('#error-message').text(response.message).show();

                    // Hide the error message after 5 seconds
                    setTimeout(function() {
                        $('#error-message').hide();
                    }, 5000);

                    // Hide the success message if it was previously shown
                    $('#success-message').hide();
                }
            },
            error: function(error) {
                // Handle other errors (e.g., network issues)
                $('#error-message').text('Error: ' + error.statusText).show();

                // Hide the error message after 5 seconds
                setTimeout(function() {
                    $('#error-message').hide();
                }, 5000);

                // Hide the success message if it was previously shown
                $('#success-message').hide();
            }
        });
    });
    $(document).on('click', '.applicant_action_submit', function (event) {
        event.preventDefault();
       
        // var app_sale = $(this).data('app_sale');
        var from_date = $.trim($("#from_date").val());
        var to_date = $.trim($("#to_date").val());
        var applicant_unblock_alert = $('#applicant_unblock_alert');
        var applicant_unblock_form = $('#applicant_unblock_form');
        // var schedule_date = $.trim($("#schedule_date" + app_sale).val());
        // var schedule_time = $.trim($("#schedule_time" + app_sale).val());
        if (from_date && to_date) {
            $.ajax({
                url: "unblock_block_applicants",
                type: "POST",
                data: { 'from_date': from_date, 'to_date' : to_date},
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                success: function (response) {
                    // console.log(response);
                    $.fn.dataTable.ext.errMode = 'throw';
                    // var crm_table = '#last_2_months_blocked_sample';
                    crm_table.draw();
                    applicant_unblock_alert.html(response);
                    // $('#last_2_months_blocked_sample').DataTable().ajax.reload();

                    setTimeout(function () {
                        $('#applicant_action').modal('hide');
                        $('.modal-backdrop').remove();
                        $("body").removeClass("modal-open");
                        $("body").removeAttr("style");
                    }, 1000);

                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    applicant_unblock_alert.html(raw_html);
                }
            });
        } else {
            applicant_unblock_alert.html('<p class="text-danger">Kindly Provide Date and Time</p>');
        }
        applicant_unblock_form.trigger('reset');
        setTimeout(function () {
            applicant_unblock_form.html('');
        }, 2000);
        return false;
    });
	 $(document).on('focus',".pickadate-year", function(){
        $(this).pickadate({
            selectYears: 4
        });
    });
</script>
@endsection
@section('js_file')
    <!-- <script src="{{ asset('js/crm_8102020.js') }}"></script> -->
@endsection
