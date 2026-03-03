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
$(document).ready(function() {
    // Load applicants for the active tab when the page is ready
    loadApplicants($('.nav-link.active').data('datatable_name'), 'active');

    // Load applicants when a tab is activated
    $('.nav-link').on('click', function() {
        const tabId = $(this).data('datatable_name');
        const filterID = $(this).data('filter_id') || 'active'; // Default to 'active'
        loadApplicants(tabId, filterID);
    });

    function loadApplicants(tabId, filterID) {
        $.ajax({
            url: "{!! url('getFollowUpApplicants') !!}/44",
            method: 'GET',
            data: { filter_id: filterID },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log("Data loaded successfully:", response);
                initializeDataTable(`#${tabId}`, response.data); // Use response.data for DataTable
            },
            error: function(xhr, status, error) {
                console.error("Error loading applicants:", status, error);
            }
        });
    }

    function initializeDataTable(tabId, data) {
        if ($.fn.DataTable.isDataTable(tabId)) {
            $(tabId).DataTable().clear().destroy();
        }

        $(tabId).DataTable({
            data: data,
            columns: [
              { data: null, sortable: false, render: function(data, type, row, meta) { return meta.row + 1 + meta.settings._iDisplayStart; } },
				{ 
					data: "applicant_name", 
					render: function(data) { 
						return data.split(' ')
							.map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
							.join(' ');
					} 
				},
                { data: "applicant_email" },
                { data: "applicant_job_title", render: function(data) { return data.toUpperCase(); } },
                { data: "job_category", render: function(data) { return data.toUpperCase(); } },
                { data: "applicant_postcode" },
                { data: "applicant_phone" },
                { data: "download", orderable: false },
                { data: "updated_cv", orderable: false },
                { data: "upload", orderable: false },
                { data: "applicant_homePhone" },
                { data: "applicant_source" },
                { data: "applicant_notes" },
                { data: "status" },
                { data: "action" }
            ]
        });
    }
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
                    <span class="font-weight-semibold">Follow-Up - Nurse</span>
                </h5>
            </div>
        </div>

        <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
            <div class="d-flex">
                <div class="breadcrumb">
                    <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                    <a href="#" class="breadcrumb-item">Follow-Up</a>
                    <a href="#" class="breadcrumb-item">Current</a>
                    <a href="#" class="breadcrumb-item">Nurse</a>
                </div>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <!-- Content area -->
    <div class="content">
        <!-- Default ordering -->
        <div class="card">
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
            
            <div class="card-header header-elements-inline">
                <ul class="nav nav-tabs nav-tabs-highlight">
                    <li class="nav-item">
                        <a href="#tab1" class="nav-link active legitRipple" data-toggle="tab" data-datatable_name="all_resources_table" data-filter_id="active">Active Applicants</a>
                    </li>
                    <li class="nav-item">
                        <a href="#tab2" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="no_responded_applicants_table" data-filter_id="no_responded">No Responded Applicants</a>
                    </li>
                    <li class="nav-item">
                        <a href="#tab3" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="callback_applicants_table" data-filter_id="callback">Callback Applicants</a>
                    </li>
					 <li class="nav-item">
                        <a href="#tab4" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="circuitBusy_applicants_table" data-filter_id="circuit_busy">Circuit Busy Applicants</a>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content">
                    <div id="tab1" class="tab-pane fade show active">
                        <table class="table table-hover table-striped" id="all_resources_table">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
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
                                    <th>Status</th>
                                    <th width="130px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic data loading here -->
                            </tbody>
                        </table>
                    </div>
                    <div id="tab2" class="tab-pane fade">
                        <!-- DataTable for Non-Responded Applicants -->
                        <table class="table table-hover table-striped" id="no_responded_applicants_table">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
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
                                    <th>Status</th>
                                    <th width="130px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic data loading here -->
                            </tbody>
                        </table>
                    </div>
                    <div id="tab3" class="tab-pane fade">
                        <!-- DataTable for Callback Applicants -->
                        <table class="table table-hover table-striped" id="callback_applicants_table">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
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
                                    <th>Status</th>
                                    <th width="130px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic data loading here -->
                            </tbody>
                        </table>
                    </div>
					 <div id="tab4" class="tab-pane fade">
                        <!-- DataTable for Callback Applicants -->
                        <table class="table table-hover table-striped" id="circuitBusy_applicants_table">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
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
                                    <th>Status</th>
                                    <th width="130px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic data loading here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- /default ordering -->
    </div>
    <!-- /content area -->
</div>


    <!-- Modal (one shared modal for all applicants) -->
    <div id="clear_cv" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Notes</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form action="{{ route('block_or_casual_notes') }}" method="POST" id="app_notes_form" class="app-notes-form form-horizontal">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="applicant_hidden_id" id="applicant_hidden_id">
                        <input type="hidden" id="applicant_page" value="follow_up">
                        <div id="app_notes_alert"></div>
                        <div id="sent_cv_alert"></div>
                        <div class="form-group row">
                            <label class="col-form-label col-sm-3">Details</label>
                            <div class="col-sm-9">
                                <textarea name="details" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-form-label col-sm-3">Choose type:</label>
                            <div class="col-sm-9">
                                <select name="reject_reason" class="form-control">
                                    <option value="0">Select Reason</option>
                                    <option value="1">Casual Notes</option>
                                    <option value="2">Block Applicant Notes</option>
                                    <option value="4">No Response</option>
                                    <option value="6">Call Back</option>
                                    <option value="3">Temporary Not Interested Applicants Notes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn bg-dark legitRipple" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn bg-teal legitRipple">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


@endsection
@section('js_file')
    <script src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}"></script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@endsection
		
@section('script')
<script>
	$(document).ready(function() {
        // Event listener for 'No Answer' button click
        $(document).on('click', '.btn_no_answer', function(e) {
            e.preventDefault(); // Prevent the default action

            var applicantId = $(this).data('id'); // Get the applicant ID from data-id
            var staticParam = 'no answer'; // Static parameter for this action

            // Log to verify correct button click
            console.log("No Answer button clicked for applicant ID: " + applicantId);

            // AJAX request for 'No Answer' action
            $.ajax({
                type: 'POST',
                url: "{{ route('block_or_casual_notes') }}", // Define the appropriate backend route here
                data: {
                    applicant_hidden_id: applicantId,
                    details: staticParam,
                    ['applicant_page' + applicantId]: 'follow_up',
                    reject_reason: '4',
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    // Find the row with the specific applicant ID
                    var row = $('#applicant_' + applicantId);
                    row.addClass('class_noJob'); // Apply the background class
                    toastr.success('No Answer action completed successfully.');
                },
                error: function(xhr, status, error) {
                    console.log(error);
                    toastr.error('An error occurred. Please try again.');
                }
            });
        });

        // Event listener for 'Busy' button click
        $(document).on('click', '.btn_busy', function(e) {
            e.preventDefault(); // Prevent the default action

            var applicantId = $(this).data('id'); // Get the applicant ID from data-id
            var staticParam = 'circuit busy or direct voice mail'; // Static parameter for this action

            // Log to verify correct button click
            console.log("Busy button clicked for applicant ID: " + applicantId);

            // AJAX request for 'Busy' action
            $.ajax({
                type: 'POST',
                url: "{{ route('block_or_casual_notes') }}", // Define the appropriate backend route here
                data: {
                    applicant_hidden_id: applicantId,
                    details: staticParam,
                    reject_reason: '7',
                    ['applicant_page' + applicantId]: 'follow_up',
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    // Find the row with the specific applicant ID
                    var row = $('#applicant_' + applicantId);
                    row.addClass('class_noJob'); // Apply the background class
                    toastr.success('Busy action completed successfully.');
                },
                error: function(xhr, status, error) {
                    console.log(error);
                    toastr.error('An error occurred. Please try again.');
                }
            });
        });
    });

	$(document).ready(function () {
        // Event listener for the modal open button
        $(document).on('click', '.reject_history', function () {
            // Get the applicant ID from the button using data method
            var applicantId = $(this).data('applicant'); // Use data() method

            // Reset the form or fields before populating with new data
            $('#app_notes_form')[0].reset(); // This will reset the form fields
            $('#applicant_hidden_id').val(''); // Clear the hidden field
            $('#applicant_page').attr('name', ''); // Reset name attribute if needed

            // Optionally clear any other content within the modal
            $('#app_notes_alert').html(''); // Clear alert message
            $('#sent_cv_alert').html('');   // Clear sent CV alert message

            // Set the applicant ID in the modal form's hidden field
            $('#applicant_hidden_id').val(applicantId);
            $('#applicant_page').attr('name', 'applicant_page' + applicantId);

            // If needed, dynamically change the form's ID or any other field
            $('#app_notes_form').attr('data-applicant', applicantId);
        });

        // Submit form via AJAX
        $('.app-notes-form').on('submit', function (e) {
            e.preventDefault(); // Prevent the default form submission
            
            var form = $(this); // Get the form element
            var formData = form.serialize(); // Serialize the form data
            var applicantId = $('#applicant_hidden_id').val(); // Get the applicant ID

            // Send the form data via AJAX
            $.ajax({
                type: 'POST',
                url: form.attr('action'), // The action route defined in the form
                data: formData,
                success: function (response) {
                    console.log(response);
                    // Hide the modal after success
                    $('#clear_cv').modal('hide');
                    
                    // Get the selected reject reason
                    var rejectReason = form.find('select[name="reject_reason"]').val();
                    
                    // Find the row with the specific applicant ID
                    var row = $('#applicant_' + applicantId);
                    
                    // Check the reason and apply/remove the background class
                    if (rejectReason == '4') { // If the reason is "No Response"
                        row.addClass('class_noJob'); // Apply the background class
                    } else {
                        row.fadeOut(); // Remove the background class if any other reason
                    }

                },
                error: function (xhr, status, error) {
                    // Handle error (optional)
                    console.log(error);
                    toastr.error('An error occurred. Please try again.');
                }
            });
        });
            // Function to count rows with class_noJob
        function countRowsWithClassNoJob() {
            var count = $('.class_noJob').length;
            var title = "Number of rows with No Responsed: " + count;
            $('#titleElement').text(title);
        }
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

    $('#submitSelectedButton').on('click', function() {

        var selectedIds = [];

        // Get selected IDs
        $('.applicant_checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            toastr.error('Please select at least one applicant to unblock.');
            return; // Exit the function if no checkboxes are selected
        }


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
	
    $(document).on("click", ".import_cv", function () {
        var app_id = $(this).data('id');

        $(".modal-body #applicant_id").val(app_id);
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
	
	$(document).ready(function () {
		$('#app_notes_form').on('submit', function (e) {
			e.preventDefault(); // Prevent default form submission

			// Clear any previous alerts
			$('#app_notes_alert').html('');

			var formData = $(this).serialize(); // Serialize form data
			var url = "{{ route('block_or_casual_notes') }}"; // Set the correct route

			$.ajax({
				type: 'POST',
				url: url,
				data: formData,
				success: function (response) {
					// Log the response for debugging
					console.log(response);

					if (response.success) {
						// Show success message using SweetAlert or Bootstrap Alert
						Swal.fire('Success', response.message, 'success');
						$('#app_notes_modal').modal('hide'); // Close the modal
					} else {
						// Display error message
						$('#app_notes_alert').html('<div class="alert alert-danger">' + response.message + '</div>');
					}
				},
				error: function (xhr) {
					// Log the error response for debugging
					console.log(xhr.responseText);
					var errorMessage = xhr.responseText ? JSON.parse(xhr.responseText).message : 'An error occurred';
					$('#app_notes_alert').html('<div class="alert alert-danger">' + errorMessage + '</div>');
				}
			});
		});
	});

</script>
@endsection