@extends('layouts.app')

@section('style')
    <script>
        var columns = [
            {
                "data": null, // No data source; handled in render function
                "name": "serial_number",
                "render": function(data, type, row, meta) {
                    // Calculate the serial number globally, taking into account the current page and page length
                    var page = meta.settings._iDisplayStart; // Starting index of the current page
                    var pageLength = meta.settings._iDisplayLength; // Number of items per page
                    return page + meta.row + 1; // Adding page start index, row index, and +1 for 1-based index
                }
            },
            {
                "data": "applicant_name",
                "name": "applicant_name",
                "render": function(data, type, row, meta) {
                    if (type === 'display' || type === 'filter') {
                        return data
                            .toLowerCase()  // Convert the entire string to lowercase
                            .split(' ')     // Split the string into words
                            .map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
                            .join(' ');    // Join the words back into a single string
                    }
                    return data;
                }
            },
			{ "data":"applicant_email", "name": "applicant_email" },
            { "data":"applicant_job_title", "name": "applicant_job_title" },
			{
                "data": "job_category",
                "name": "job_category",
                "render": function(data, type, row, meta) {
                    return type === 'display' || type === 'filter' ? data.toUpperCase() : data;
                }
            },
			{
                "data": "applicant_postcode",
                "name": "applicant_postcode",
                "render": function(data, type, row, meta) {
                    return type === 'display' || type === 'filter' ? data.toUpperCase() : data;
                }
            },
             { 
                "data":"applicant_phone", 
                "name": "applicant_phone",
                "render": function(data, type, row, meta) {
                    if (row.is_blocked == 1) {
                        return "<span class='badge badge-secondary'>Blocked</span>";
                    }
                    return data;
                }
            },
			 { 
                "data":"applicant_homePhone",
                "name": "applicant_homePhone",
                "render": function(data, type, row, meta) {
                    if (row.is_blocked == 1) {
                        return "";
                    }
                    return data;
                }
             },
			{ "data":"applicant_source", "name": "applicant_source" },
			{ "data":"applicant_notes", "name": "applicant_notes" }
        ];
		
      $(document).ready(function() {
        $.fn.dataTable.ext.errMode = 'none';
          $('#applicant_sample_1').DataTable({
               "processing": true,
               "serverSide": true,
               "responsive": true,
               "ajax":"getTemporaryData",
               "order": [],
               "columns": columns,		
               "createdRow": function(row, data, dataIndex) {
                    // Set the row ID to the applicant ID
                    $(row).attr('id', 'applicant-row-' + data.id);
                }	  
          });
      });

      $(document).ready(function() {
        // Handle "Edit Notes" button click
        $(document).on('click', '.edit-notes-btn', function() {
            // Get the applicant's data from the button's data attributes
            const applicantId = $(this).data('applicant-id');

            // Update the modal's content
            $('#modal_applicant_id').val(applicantId); // Set the applicant ID

            // Open the modal
            $('#updateNotesModal').modal('show');
        });

        // Handle form submission
        $('#app_notes_form').on('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission

            const form = $(this);
            const applicantId = form.find('input[name="applicant_id"]').val();
            const details = form.find('textarea[name="details"]').val();

            // Send AJAX request
            $.ajax({
                url: "{{ route('update_temporary_data_notes') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    applicant_id: applicantId,
                    details: details
                },
                success: function(response) {
                    if (response.success) {
                        // Update the relevant row's notes
                        $(`#applicant-row-${applicantId} #applicant-notes-${applicantId}`).text(response.updated_notes);

                        // Close the modal
                        $('#updateNotesModal').modal('hide');

                        // Reset the form
                        form.trigger("reset");
                    } else {
                        alert('Failed to update notes.');
                    }
                },
                error: function(xhr) {
                    alert('An error occurred. Please try again.');
                }
            });
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
                        <span class="font-weight-semibold">Temporary Data</span>
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Temporary Data</span>
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
                    <h5 class="card-title">Temporary Data</h5>
                    <div> 
                        @if(auth()->user()->id == '66' || auth()->user()->id == '101')
							<a href="#"
							data-controls-modal="#import_applicant_csv"
							data-backdrop="static"
							data-keyboard="false" data-toggle="modal"
							data-target="#import_applicant_csv" class="btn bg-slate-800 legitRipple mr-1">
								<i class="icon-cloud-download"></i>
								&nbsp;Import</a>
                        @endif
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
                <!-- Applicant CSV Import Modal -->
                @can('applicant_import')
                <div id="import_applicant_csv" class="modal fade">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Import Applicant CSV</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
								<p>If you want to download the formatted file. <a style="text-decoration:underline;" href="{{ asset('assets/csv/applicants_format.csv') }}">Click here</a></p>

                                <form action="{{ route('temporary_data_import') }}" method="post" enctype="multipart/form-data">
                                    @csrf()
                                    <div class="form-group row">
                                        <div class="col-lg-12">
                                            <input type="file" name="applicant_csv" class="file-input-advanced" data-fouc>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endcan
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
                                        <input type="hidden" name="applicant_id" id="applicant_id" value=""/>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
				 
                <!-- Applicant CSV Import Modal -->

                <table class="table table-hover table-striped" id="applicant_sample_1">
                    <thead>
                    <tr>
                        <th>Sr#</th>
                        <th>Name</th>
						<th>Email</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Postcode</th>
                        <th>Phone#</th>
						<th>Landline#</th>
                        <th>Source</th>
                        <th>Notes</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <!-- /default ordering -->

        </div>
        <!-- /content area -->

        <!-- Single Modal -->
        <div id="updateNotesModal" class="modal fade" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Notes</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <form id="app_notes_form" class="form-horizontal">
                        @csrf
                        <div class="modal-body">
                            <div class="form-group row">
                                <label class="col-form-label col-sm-3">Details</label>
                                <div class="col-sm-9">
                                    <input type="hidden" name="applicant_id" id="modal_applicant_id" value="">
                                    <textarea name="details" id="modal_sent_cv_details" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>
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
