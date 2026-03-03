@extends('layouts.app')

@section('style')
    <script>
        var columns = [
            { "data":"created_date", "name": "created_date" },
            { "data":"created_time", "name": "created_time" },
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
                },
				"orderable": true
            },
			{
				"data": "department",
				"name": "department",
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
				"name": "sub_department",
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
			{ "data":"download", "name": "download", "orderable": false },
            { "data":"updated_cv", "name": "updated_cv", "orderable": false },
            { "data":"upload", "name": "upload", "orderable": false },
			{ "data":"applicant_experience", "name": "applicant_experience" },
			{ "data":"applicant_source", "name": "applicant_source" },
			{ "data":"applicant_notes", "name": "applicant_notes" }
        ];
		
		$(document).ready(function() {
			$.fn.dataTable.ext.errMode = 'none';
			<?php if (\Illuminate\Support\Facades\Auth::user()->hasAnyPermission(['applicant_edit', 'applicant_view', 'applicant_history', 'applicant_note-create', 'applicant_note-history'])): ?>
			columns.push({ "data":"action", "name": "action" })
			<?php endif; ?>
			$('#applicant_sample_1').DataTable({
				"processing": true,
				"serverSide": true,
				"responsive": true,
				"ajax":"getApplicants",
				"order": [],
				"columns": columns,
				"rowCallback": function(row, data, index) {
					// Check if isBlocked is 1 and apply red background
					if (data.is_blocked == 1) {
						$(row).css('background-color', 'rgb(22 20 20 / 48%)');
					}else if(data.temp_not_interested){
						$(row).css('background-color','#fde1df');
					}
				}
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
                        <span class="font-weight-semibold">Applicants</span> - All
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Applicants</span>
                    </div>
                </div>
				<div class="d-flex align-items-center pr-3">
                        Blocked: <span class="status-block class_blocked mr-2"></span>
                        Not Interested: <span class="status-block class_danger mr-2"></span>
                    </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">

            <!-- Default ordering -->
            <div class="card">
                <div class="card-header header-elements-inline">
                    <h5 class="card-title">Active Applicants</h5>
                    <div> 
                        @can('applicant_import')
                        <a href="#"
                        data-controls-modal="#import_applicant_csv"
                        data-backdrop="static"
                        data-keyboard="false" data-toggle="modal"
                        data-target="#import_applicant_csv" class="btn bg-slate-800 legitRipple mr-1">
                            <i class="icon-cloud-download"></i>
                            &nbsp;Import</a>
                        @endcan
                        @can('applicant_export')
                        <a href="{{ route('applicants.export_csv') }}" class="btn bg-slate-800 legitRipple mr-1">
                            <i class="icon-cloud-upload"></i>
                            &nbsp;Export</a>
						
						<a href="{{ route('exportApplicantsWithNoLatLng') }}" class="btn bg-slate-800 legitRipple mr-1">
                            <i class="icon-cloud-upload"></i>
                            &nbsp;Export With No LAT & LONG</a>
                        
                        <a href="{{ route('applicants.export_email') }}" class="btn bg-slate-800 legitRipple mr-2">
                                <i class="icon-cloud-upload"></i>
                                &nbsp;Export Email</a>
                        @endcan
                        @can('applicant_create')
                        <a href="{{ route('applicants.create') }}" class="btn bg-teal legitRipple float-right">
                            <i class="icon-plus-circle2"></i>
                            Applicant</a>
                        @endcan
                    </div>
                </div>

                <div class="card-body">
                    <span style="height:15px;width:15px;background-color: #394357;
                    border-radius: 50%;display: inline-block;"></span>
                    <span style="position: relative;bottom: 3px;">Applicant is Rejected on All Stages of Application for 3 Months</span>
                    <p></p>
                    <span style="height:15px;width:15px;background-color: #00796a;
                    border-radius: 50%;display: inline-block;"></span>
                    <span style="position: relative;bottom: 3px;">Applicant is in Positive Stages of CRM</span>
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

                                <form action="{{ route('applicantCsv') }}" method="post" enctype="multipart/form-data">
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
                        <th>Date</th>
                        <th>Time</th>
                        <th>Name</th>
						<th>Email</th>
                        <th>Title</th>
                        <th>Category</th>
						<th>Department</th>
						<th>Sub Department</th>
                        <th>Postcode</th>
                        <th>Phone#</th>
						<th>Landline#</th>
						<th>Applicant CV</th>
                        <th>Updated CV</th>
                       	<th>Upload CV</th>
						<th>Experience</th>
                        <th>Source</th>
                        <th>Notes</th>
                        <!-- <th>Updated By</th> -->
                        @canany(['applicant_edit','applicant_view','applicant_history','applicant_note-create','applicant_note-history'])
                        <th>Action</th>
                        @endcanany
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
	// Update checkbox values dynamically
		$(document).on('change', '#no_job_checkbox', function() {
			$(this).val(this.checked ? '1' : '0');
		});
		
		$(document).on('change', '#interview_availability_checkbox', function() {
			$(this).val(this.checked ? '1' : '0');
		});
		
		$(document).on('change', '#alternate_weekend_checkbox', function() {
			$(this).val(this.checked ? '1' : '0');
		});
		
		$(document).on('change', '#nursing_home_checkbox', function() {
			$(this).val(this.checked ? '1' : '0');
		});
		
		$(document).on('change', '#hangup_call', function() {
			$(this).val(this.checked ? '1' : '0');
		});
	
    // create new note
    $(document).on('click', '.note_form_submit', function (event) {
        event.preventDefault();
        var note_key = $(this).data('note_key');
        var $note_form = $('#note_form'+note_key);
        var $note_alert = $('#note_alert' + note_key);
        var note_details = $.trim($("#note_details" + note_key).val());
        if (note_details) {
            $.ajax({
                url: "{{ route('module_note.store') }}",
                type: "POST",
                data: $note_form.serialize(),
                success: function (response) {
                    // $note_form.trigger('reset');
                    $note_alert.html(response);
                    setTimeout(function () {
                        $('#add_applicant_note' + note_key).modal('hide');
						 window.location.reload();
                    }, 1000);
                },
                error: function (response) {
                    var raw_html = '<p class="text-danger">WHOOPS! Something Went Wrong!!</p>';
                    $note_alert.html(raw_html);
                }
            });
        } else {
            $note_alert.html('<p class="text-danger">Kindly Provide Note Details</p>');
        }
        $note_form.trigger('reset');
        setTimeout(function () {
            $note_alert.html('');
        }, 2000);
        return false;
    });

    // fetch notes history
    $(document).on('click', '.notes_history', function (event) {
        var applicant = $(this).data('applicant');

        $.ajax({
            url: "{{ route('notesHistory') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                module_key: applicant,
                module: "Applicant"
            },
            success: function(response){
                $('#applicants_notes_history'+applicant).html(response);
            },
            error: function(response){
                var raw_html = '<p>WHOOPS! Something Went Wrong!!</p>';
                $('#applicants_notes_history'+applicant).html(raw_html);
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
		 $(".modal-body #applicant_id").val(app_id);
	 });
	
    // JS for hover effect
    const infoIcon = document.querySelector('.fa-info-circle');
    const tooltip = infoIcon.nextElementSibling;

    infoIcon.addEventListener('mouseover', () => {
        tooltip.style.visibility = 'visible';
    });

    infoIcon.addEventListener('mouseout', () => {
        tooltip.style.visibility = 'hidden';
    });
</script>
@endsection
