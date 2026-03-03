@extends('layouts.app')

@section('style')

    <script>

      $(document).ready(function() {
        $.fn.dataTable.ext.errMode = 'none';
          $('#non_interest_app_sample').DataTable({
               "processing": true,
               "serverSide": true,
               "order": [],
               "ajax":"getNonInterestAppAjax",
               "columns": [
				    { "data": "checkbox", orderable:false, searchable:false},
                   { "data":"interest_added_date", "name": "applicants_pivot_sales.interest_added_date" },
                   { "data":"interest_added_time", "name": "applicants_pivot_sales.interest_added_time" },
                   { "data":"applicant_name", "name": "applicants.applicant_name" },
                   { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                   { "data":"job_category", "name": "applicants.job_category" },
                   { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                   { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                   { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                   { "data":"applicant_source", "name": "applicants.applicant_source" },
                   { "data":"reason", "name": "notes_for_range_applicants.reason" },
                   { "data":"job_details", "name": "job_details" },
                   { "data":"office_name", "name": "offices.office_name" },
                   { "data":"unit_name", "name": "units.unit_name" },
                   { "data":"postcode", "name": "sales.postcode" },
                   { "data":"history", "name": "history" },
               // {"data":"action"}
               ]
          });
      });
    </script>

@endsection 

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
{{--        <div class="page-header page-header-dark has-cover" style="border: 1px solid #ddd; border-bottom: 0;">--}}
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Applicants</span> - Not Interested
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Applicants</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Non Interested</span>
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
                    <h5 class="card-title">Active Not Interested Applicants</h5>
					<div>
                        <button id="submitSelectedButton" class="btn bg-teal legitRipple float-right">
                            <i class="icon-redo"></i>&emsp;
                            Revert
                        </button>
                        @can('applicant_export')
                        <a href="{{ route('export_non_interested_last_applicants_cv') }}" class="btn bg-slate-800 legitRipple mr-2">
                            <i class="icon-cloud-upload"></i>
                            &nbsp;Export</a>
                        @endcan
                        <div id="success-message" class="alert alert-success" style="display: none;"></div>
                        <div id="error-message" class="alert alert-danger" style="display: none;"></div>
                    </div>
                </div>
                <div class="card-body">
                </div>

                <table class="table table-hover table-striped" id="non_interest_app_sample">
                    <thead>
                    <tr>
						 <th><input type="checkbox" id="master-checkbox"></th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Name</th>
                        <th>Job Title</th>
                        <th>Category</th>
                        <th>Postcode</th>
                        <th>Phone#</th>
                        <th>Landline#</th>
                        <th>Source</th>
                        <th>Notes</th>
                        <th>Job Details</th>
                        <th>Office</th>
                        <th>Unit</th>
                        <th>Job Postcode</th>
                        <th>History</th>
                        <!-- <th>Action</th> -->
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
             url: '/non-interested-revert-all', // Update the URL to match your route
            type: 'POST',
            data: { ids: selectedIds,_token: $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    // Reload the DataTable
                    $('#non_interest_app_sample').DataTable().ajax.reload();

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
</script>
@endsection
