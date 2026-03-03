@extends('layouts.app')

@if($sent_cv_count < $job['send_cv_limit'])

	@section('style')
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}">

        <style>
            .attachment_card {
                border: 1px solid #ddd;
                padding: 10px;
                margin-bottom: 10px;
                border-radius: 5px;
                background-color: #ededed;
            }
            .attachment_card i{
                font-size: 20px;
            }
        </style>
		 <script>
          var columns = [
			  { "data": "updated_at", "name": "updated_at" },
			  { "data": "applicant_added_time", "name": "applicant_added_time", "orderable": false },
			  { "data": "applicant_name", "name": "applicants.applicant_name", "searchable": true },
			  { "data": "applicant_email", "name": "applicants.applicant_email", "searchable": true },
			  { "data": "applicant_job_title", "name": "applicants.applicant_job_title" },
			  { 
				  "data": "job_category", 
				  "name": "applicants.job_category", 
				  "render": function(data, type, row) {
					  return data ? data.toUpperCase() : '';
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
			{ 
				"data": "applicant_postcode", 
				"name": "applicants.applicant_postcode", 
				"orderable": true, 
				"searchable": true
			},
			{ 
				"data": "applicant_phone", 
				"name": "applicants.applicant_phone", 
				"orderable": false
			},
			{ 
				"data": "download", 
				"name": "applicants.applicant_cv", 
				"orderable": false
			},
			{ 
				"data": "updated_cv", 
				"name": "applicants.updated_cv", 
				"orderable": false
			},
			{ 
				"data": "applicant_homePhone", 
				"name": "applicants.applicant_homePhone", 
				"orderable": false
			},
			{ 
				"data": "applicant_source", 
				"name": "applicants.applicant_source", 
				"searchable": true,
				"orderable": true
			},
			{ 
				"data": "applicant_notes", 
				"name": "applicant_notes", 
				"orderable": false,
				"searchable": true
			},
			{ 
				"data": "status", 
				"name": "status"
			},
			{ 
				"data": "action", 
				"name": "action", 
				"orderable": false, 
				"searchable": false
			}
		];

$(document).ready(function() {
    $.fn.dataTable.ext.errMode = 'none';
    var job = $("#hidden_job_value").val();
    var radius = $("#hidden_radius_value").val();
    
    // Set radius to 0 if it's null, undefined, or empty
    if (radius === null || radius === undefined || radius.trim() === "") {
        radius = 0;
    }
    
    // Initialize DataTables for each table
    var allApplicants = $('#allApplicantsTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ordering": true,
        "ajax": "{!! url('get15kmApplicantsAjax') !!}/" + job + "/" + radius + "/all",
        "order": [[0, 'desc']],
        "columns": columns
    });

    var claimHandlerApplicants = $('#claimHandlerTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ordering": true,
        "ajax": "{!! url('get15kmApplicantsAjax') !!}/" + job + "/" + radius + "/claim-handler",
        "order": [[0, 'desc']],
        "columns": columns,
        "deferRender": true
    });

    var commercialCorporateSolicitorApplicants = $('#commercialCorporateSolicitorTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ordering": true,
        "ajax": "{!! url('get15kmApplicantsAjax') !!}/" + job + "/" + radius + "/commercial-corporate-solicitor",
        "order": [[0, 'desc']],
        "searching": true,
        "columns": columns,
        "deferRender": true
    });

    var conveyancerApplicants = $('#conveyancerTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "{!! url('get15kmApplicantsAjax') !!}/" + job + "/" + radius + "/conveyancer",
        "order": [[0, 'desc']],
        "columns": columns,
        "searching": true,
        "deferRender": true
    });

    var constructionSolicitorApplicants = $('#constructionSolicitorTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "{!! url('get15kmApplicantsAjax') !!}/" + job + "/" + radius + "/construction-solicitor",
        "order": [[0, 'desc']],
        "columns": columns,
        "searching": true,
        "deferRender": true
    });
	
	var criminalSolicitorApplicants = $('#criminalSolicitorTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "{!! url('get15kmApplicantsAjax') !!}/" + job + "/" + radius + "/criminal-solicitor",
        "order": [[0, 'desc']],
        "columns": columns,
        "searching": true,
        "deferRender": true
    });
	
	var employmentApplicants = $('#employmentTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "{!! url('get15kmApplicantsAjax') !!}/" + job + "/" + radius + "/employment",
        "order": [[0, 'desc']],
        "columns": columns,
        "searching": true,
        "deferRender": true
    });
	
	var familySolicitorApplicants = $('#familySolicitorTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "{!! url('get15kmApplicantsAjax') !!}/" + job + "/" + radius + "/family-solicitor",
        "order": [[0, 'desc']],
        "columns": columns,
        "searching": true,
        "deferRender": true
    });

	var immigrationApplicants = $('#immigrationTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "{!! url('get15kmApplicantsAjax') !!}/" + job + "/" + radius + "/immigration",
        "order": [[0, 'desc']],
        "columns": columns,
        "searching": true,
        "deferRender": true
    });

	var litigationApplicants = $('#litigationTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "{!! url('get15kmApplicantsAjax') !!}/" + job + "/" + radius + "/litigation",
        "order": [[0, 'desc']],
        "columns": columns,
        "searching": true,
        "deferRender": true
    });
	
	var medicalNegligenceApplicants = $('#medicalNegligenceTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "{!! url('get15kmApplicantsAjax') !!}/" + job + "/" + radius + "/medical-negligence",
        "order": [[0, 'desc']],
        "columns": columns,
        "searching": true,
        "deferRender": true
    });
	
	var paralegalApplicants = $('#paralegalTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "{!! url('get15kmApplicantsAjax') !!}/" + job + "/" + radius + "/paralegal",
        "order": [[0, 'desc']],
        "columns": columns,
        "searching": true,
        "deferRender": true
    });
	
	var privateClientApplicants = $('#privateClientTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "{!! url('get15kmApplicantsAjax') !!}/" + job + "/" + radius + "/private-client",
        "order": [[0, 'desc']],
        "columns": columns,
        "searching": true,
        "deferRender": true
    });
	
	var propertySolicitorApplicants = $('#propertySolicitorTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": "{!! url('get15kmApplicantsAjax') !!}/" + job + "/" + radius + "/property-solicitor",
        "order": [[0, 'desc']],
        "columns": columns,
        "searching": true,
        "deferRender": true
    });
	
    // Show the initial table (All Applicants)
    $('#all-applicants-table').removeClass('d-none');
    
    // Handle dropdown change
    $('#applicantFilter').change(function() {
        // Hide all tables
        $('.applicant-table').addClass('d-none');
        
        // Show the selected table and reload its DataTable
        const selectedValue = $(this).val();
        const tableElement = $('#' + selectedValue + '-table');
        tableElement.removeClass('d-none');
        
        // Reload the appropriate DataTable based on selection
        switch(selectedValue) {
            case 'all-applicants':
                allApplicants.ajax.reload();
                break;
            case 'claim-handler':
                claimHandlerApplicants.ajax.reload();
                break;
            case 'commercial-corporate-solicitor':
                commercialCorporateSolicitorApplicants.ajax.reload();
                break;
            case 'conveyancer':
                conveyancerApplicants.ajax.reload();
                break;
            case 'construction-solicitor':
                constructionSolicitorApplicants.ajax.reload();
                break;
			case 'criminal-solicitor':
                criminalSolicitorApplicants.ajax.reload();
                break;
			case 'employment':
                employmentApplicants.ajax.reload();
                break;
			case 'family-solicitor':
                familySolicitorApplicants.ajax.reload();
                break;
			case 'immigration':
                immigrationApplicants.ajax.reload();
                break;
			case 'litigation':
                litigationApplicants.ajax.reload();
                break;
			case 'medical-negligence':
                medicalNegligenceApplicants.ajax.reload();
                break;
			case 'paralegal':
                paralegalApplicants.ajax.reload();
                break;
			case 'private-client':
                privateClientApplicants.ajax.reload();
                break;
			case 'property-solicitor':
                propertySolicitorApplicants.ajax.reload();
                break;
        }
    });

    // Optional: Trigger initial load
    allApplicants.ajax.reload();
});
            
         
        </script>
	@endsection
@endif

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">

                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Applicants Within</span> - {{ $radius == null ? '10' : $radius }}KMs / 
{{ $radius == null ? 10 * 0.6 : round($radius * 0.6, 2) }}Miles
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Resources</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Direct</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">
            <div class="card-header header-elements-inline">
                <h5 class="card-title">Job Details</h5>
                <input type="hidden" id="hidden_job_value" value="{{ $id}}">
				<input type="hidden" id="hidden_radius_value" value="{{ $radius}}">
            </div>
            @if($job)
            <div class="row">
                <div class="col-lg-6">
                    <div class="card border-left-3 border-left-slate rounded-left-0">
                        <div class="card-body">
                            <div class="d-md-flex align-item-sm-center flex-sm-nowrap">
                                <div class="w-50">
                                    Title: <span class="font-weight-semibold">{{ ucwords($job['job_title']) }}</span>
									@if($net_sent_cv_count == $job['send_cv_limit'])
                                    	<span class="badge badge-danger" style="font-size:90%">0 Limit Reached</span>
									@else
                                    	<span class='badge badge-success' style='font-size:90%'>
											{{$net_sent_cv_count}}/{{ $job['send_cv_limit'] }} Limit Remains</span>
                                    @endif
                                    <ul class="list list-unstyled mb-0">
                                        <li>Postcode: <span class="font-weight-semibold">{{ strtoupper($job['postcode']) }}</span>
                                        </li>
                                        <li>Type: <span class="font-weight-semibold">{{ ucwords($job['job_type']) }}</span></li>
                                        <li>Head Office: <span
                                                    class="font-weight-semibold">{{ ucwords($job['office_name']) }}</span></li>
                                        <li>Qualification: <span
                                                    class="font-weight-semibold">{{ ucfirst($job['qualification']) }}</span></li>
                                    </ul>
                                </div>

                                <div class="text-sm-right mb-0 mt-3 mt-sm-0 ml-auto w-50">
                                    Salary: <span class="font-weight-semibold">{{ $job['salary'] }}</span>
                                    <ul class="list list-unstyled mb-0">
                                        <li>Categroy: <span class="font-weight-semibold">{{ strtoupper($job['job_category']) }}</span>
                                        </li>
                                        <li>Experience: <span class="font-weight-semibold">{{ $job['experience'] }}</span>
                                        </li>
                                        <li>Unit: <span class="font-weight-semibold">{{ $job['unit_name'] }}</span>
                                        </li>
                                        <li class="dropdown">
                                            Status: &nbsp;
                                            <a href="#" class="badge bg-teal align-top">{{ ucwords($job['status']) }}</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer d-md-flex justify-content-sm-between align-items-sm-center">
                            <span>
                                Sent CV: <span class="font-weight-semibold">{{ $net_sent_cv_count }} out of {{ $job['send_cv_limit'] }}</span>
								@if($open_cv_count)
								&nbsp;|&nbsp;Open CV: <span class="font-weight-semibold">{{ $open_cv_count }}</span>
								@endif
                            </span>
                            <!-- Button to open the modal -->
                            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#attachmentsModal" id="viewAttachmentsBtn">
                                <i class="fa fa-file"></i>
                                View Attachments
                            </button>
                            <ul class="list-inline list-inline-condensed mb-0 mt-2 mt-sm-0">
                                <li class="list-inline-item">
                                    Posted On: <span class="font-weight-semibold">{{ $job['sale_added_date'] }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Default ordering -->
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
                <h5 class="card-title">Active Applicants Within {{ $radius == null ? '10' : $radius }}KMs / 
{{ $radius == null ? 10 * 0.6 : round($radius * 0.6, 2) }}Miles</h5>
				 <p></p>
                    
                @can('applicant_export')
                <a href="{{ route('export_15km_applicants',['id' => $sale_export_id]) }}" class="btn bg-slate-800 legitRipple float-right" style="margin-right:20px;">
                    <i class="icon-cloud-upload"></i>
                    &nbsp;Export</a>
                @endcan
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
            <div class="card">
				<!-- Replace nav-tabs with a dropdown filter -->
				<div class="card-header">
					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label for="applicantFilter">Filter By Department:</label>
								<select class="form-control" id="applicantFilter">
									<option value="all-applicants">All Departments</option>
									<option value="claim-handler">Claim Handler</option>
									<option value="commercial-corporate-solicitor">Commercial & Corporate Solicitor</option>
									<option value="conveyancer">Conveyancer</option>
									<option value="construction-solicitor">Construction Solicitor</option>
									<option value="criminal-solicitor">Criminal Solicitor</option>
									<option value="employment">Employment</option>
									<option value="family-solicitor">Family Solicitor</option>
									<option value="immigration">Immigration</option>
									<option value="litigation">Litigation</option>
									<option value="medical-negligence">Medical Negligence</option>
									<option value="paralegal">Paralegal</option>
									<option value="private-client">Private Client</option>
									<option value="property-solicitor">Property Solicitor</option>
								</select>
							</div>
						</div>
					</div>
				</div>
                
                @if($sent_cv_count < $job['send_cv_limit'])
                <div class="tab-content" id="applicantTabsContent">
                    <!-- All Applicants Tab -->
                      <div class="applicant-table" id="all-applicants-table">
            				<table class="table table-hover table-striped" id="allApplicantsTable">
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
                                    <th>Applicant CV</th>
                                    <th>Updated CV</th>
                                    <th>Landline#</th>
                                    <th>Source</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                
                    <!-- Interested Tab -->
                   <div class="applicant-table d-none" id="claim-handler-table">
					   <table class="table table-hover table-striped" id="claimHandlerTable">
                            <!-- Table structure remains the same as the all applicants table -->
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
                                    <th>Applicant CV</th>
                                    <th>Updated CV</th>
                                    <th>Landline#</th>
                                    <th>Source</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                
                    <!-- Not Interested Tab -->
                    <div class="applicant-table d-none" id="commercial-corporate-solicitor-table">
                        <table class="table table-hover table-striped" id="commercialCorporateSolicitorTable">
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
                                    <th>Applicant CV</th>
                                    <th>Updated CV</th>
                                    <th>Landline#</th>
                                    <th>Source</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                
                    <!-- conveyancer Tab -->
                    <div class="applicant-table d-none" id="conveyancer-table">
                        <table class="table table-hover table-striped" id="conveyancerTable">
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
                                    <th>Applicant CV</th>
                                    <th>Updated CV</th>
                                    <th>Landline#</th>
                                    <th>Source</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                
                    <!-- construction-solicitor Tab -->
                    <div class="applicant-table d-none" id="construction-solicitor-table">
                        <table class="table table-hover table-striped" id="constructionSolicitorTable">
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
                                    <th>Applicant CV</th>
                                    <th>Updated CV</th>
                                    <th>Landline#</th>
                                    <th>Source</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
				
					<!-- criminal-solicitor Tab -->
                    <div class="applicant-table d-none" id="criminal-solicitor-table">
                        <table class="table table-hover table-striped" id="criminalSolicitorTable">
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
                                    <th>Applicant CV</th>
                                    <th>Updated CV</th>
                                    <th>Landline#</th>
                                    <th>Source</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
					<!-- employment Tab -->
                    <div class="applicant-table d-none" id="employment-table">
                        <table class="table table-hover table-striped" id="employmentTable">
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
                                    <th>Applicant CV</th>
                                    <th>Updated CV</th>
                                    <th>Landline#</th>
                                    <th>Source</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
					<!-- family-solicitor Tab -->
                    <div class="applicant-table d-none" id="family-solicitor-table">
                        <table class="table table-hover table-striped" id="familySolicitorTable">
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
                                    <th>Applicant CV</th>
                                    <th>Updated CV</th>
                                    <th>Landline#</th>
                                    <th>Source</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
					<!-- immigration Tab -->
                    <div class="applicant-table d-none" id="immigration-table">
                        <table class="table table-hover table-striped" id="immigrationTable">
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
                                    <th>Applicant CV</th>
                                    <th>Updated CV</th>
                                    <th>Landline#</th>
                                    <th>Source</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
					<!-- litigation Tab -->
                    <div class="applicant-table d-none" id="litigation-table">
                        <table class="table table-hover table-striped" id="litigationTable">
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
                                    <th>Applicant CV</th>
                                    <th>Updated CV</th>
                                    <th>Landline#</th>
                                    <th>Source</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
					<!-- medical-negligence Tab -->
                    <div class="applicant-table d-none" id="medical-negligence-table">
                        <table class="table table-hover table-striped" id="medicalNegligenceTable">
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
                                    <th>Applicant CV</th>
                                    <th>Updated CV</th>
                                    <th>Landline#</th>
                                    <th>Source</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
				<!-- paralegal Tab -->
                    <div class="applicant-table d-none" id="paralegal-table">
                        <table class="table table-hover table-striped" id="paralegalTable">
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
                                    <th>Applicant CV</th>
                                    <th>Updated CV</th>
                                    <th>Landline#</th>
                                    <th>Source</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
					<!-- private-client Tab -->
                    <div class="applicant-table d-none" id="private-client-table">
                        <table class="table table-hover table-striped" id="privateClientTable">
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
                                    <th>Applicant CV</th>
                                    <th>Updated CV</th>
                                    <th>Landline#</th>
                                    <th>Source</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
					<!-- property-solicitor Tab -->
                    <div class="applicant-table d-none" id="property-solicitor-table">
                        <table class="table table-hover table-striped" id="propertySolicitorTable">
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
                                    <th>Applicant CV</th>
                                    <th>Updated CV</th>
                                    <th>Landline#</th>
                                    <th>Source</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                @else
                    <h4 class="font-weight-semibold text-center mt-3">Send CV Limit for this Sale has reached maximum. Kindly increase Send CV Limit to send any CV on this Sale. Thank You</h4>
                    @if (!empty($active_applicants))
                        <table class="table table-hover table-striped datatable-sorting">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Name</th>
                                <th>Postcode</th>
                                <th>Stage</th>
                                <th>Sub Stage</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php($history_stages = config('constants.history_all_positive_stages'))
                            @foreach($active_applicants as $applicant)
                                <tr>
                                    <td>{{ $applicant['history_added_date'] }}</td>
                                    <td>{{ $applicant['history_added_time'] }}</td>
                                    <td>{{ $applicant['applicant_name'] }}</td>
                                    <td>{{ $applicant['applicant_postcode'] }}</td>
                                    <td>{{ strtoupper($applicant['stage']) }}</td>
                                    <td>{{ $history_stages[$applicant['sub_stage']] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                @endif
            </div>
            <!-- /default ordering -->
            @else
                <div class="card">
                    <h4 class="text-center mt-2">Following job is either <span class="font-weight-semibold">pending</span> or <span class="font-weight-semibold">rejected</span>. Kindly contact your supervisor to activate this job. Thank You.</h4>
                </div>
            @endif
        </div>
        <!-- /content area -->

        <!-- Modal -->
        <div class="modal fade" id="attachmentsModal" tabindex="-1" role="dialog" aria-labelledby="attachmentsModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="attachmentsModalLabel">Job Attachments</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Attachments will be dynamically loaded here -->
                        <div id="attachmentsList"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="notInterestedModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Enter Not Interested Reason Below:</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="notInterestedForm" method="POST">
                        @csrf
                        <div class="modal-body">
							<input type="hidden" name="requestByAjax" value="yes">
                            <div class="form-group row">
                                <label class="col-form-label col-sm-3">Reason</label>
                                <div class="col-sm-9">
                                    <textarea name="reason" class="form-control" rows="4" placeholder="TYPE HERE.." required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn bg-teal">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="modal fade" id="callBackModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Enter Callback Reason Below:</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="callBackForm" method="POST">
                        @csrf
                        <div class="modal-body">
							<input type="hidden" name="requestByAjax" value="yes">
                            <div class="form-group row">
                                <label class="col-form-label col-sm-3">Reason</label>
                                <div class="col-sm-9">
                                    <textarea name="details" class="form-control" rows="4" placeholder="TYPE HERE.." required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn bg-teal">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SEND CV Modal -->
        <div id="sent_cvModal" class="modal fade" tabindex="-1" aria-labelledby="sent_cvModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="sent_cvModalLabel">Fill form for the sent cv:</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="sentCvForm" class="form-horizontal">
                        <input type="hidden" name="requestByAjax" value="yes">
                        <div class="modal-body">
                            <div id="interested">
                                <div class="form-group row">
                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">1.</strong> Current Employer Name</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="current_employer_name" class="form-control" placeholder="Enter Employer Name">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">2.</strong> PostCode</label>
                                    <div class="col-sm-3">
                                        <input type="text" name="postcode" class="form-control" placeholder="Enter PostCode">
                                    </div>
                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">3.</strong> Current/Expected Salary</label>
                                    <div class="col-sm-3">
                                        <input type="text" name="expected_salary" class="form-control" placeholder="Enter Salary">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">4.</strong> Qualification</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="qualification" class="form-control" placeholder="Enter Qualification">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">5.</strong> Transport Type</label>
                                    <div class="col-sm-9 d-flex align-items-center">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="walk" value="By Walk">
                                            <label class="form-check-label" for="walk">By Walk</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="cycle" value="Cycle">
                                            <label class="form-check-label" for="cycle">Cycle</label>
                                        </div>
                                        <div class="form-check form-check-inline ml-3">
                                            <input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="car" value="Car">
                                            <label class="form-check-label" for="car">Car</label>
                                        </div>
                                        <div class="form-check form-check-inline ml-3">
                                            <input class="form-check-input mt-0" type="checkbox" name="transport_type[]" id="public_transport" value="Public Transport">
                                            <label class="form-check-label" for="public_transport">Public Transport</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">6.</strong> Shift Pattern</label>
                                    <div class="col-sm-9 d-flex align-items-center">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="day" value="Day">
                                            <label class="form-check-label" for="day">Day</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="night" value="Night">
                                            <label class="form-check-label" for="night">Night</label>
                                        </div>
                                        <div class="form-check form-check-inline ml-3">
                                            <input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="full_time" value="Full Time">
                                            <label class="form-check-label" for="full_time">Full Time</label>
                                        </div>
                                        <div class="form-check form-check-inline ml-3">
                                            <input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="part_time" value="Part Time">
                                            <label class="form-check-label" for="part_time">Part Time</label>
                                        </div>
                                        <div class="form-check form-check-inline ml-3">
                                            <input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="twenty_four_hours" value="24 hours">
                                            <label class="form-check-label" for="twenty_four_hours">24 Hours</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input mt-0" type="checkbox" name="shift_pattern[]" id="day_night" value="Day/Night">
                                            <label class="form-check-label" for="day_night">Day/Night</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">7.</strong> Nursing Home</label>
                                    <div class="col-sm-3 d-flex align-items-center">
                                        <div class="form-check mt-0">
                                            <input type="checkbox" name="nursing_home" style="margin-top:-3px" id="nursing_home_checkbox" class="form-check-input" value="0">
                                        </div>
                                    </div>
                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">8.</strong> Alternate Weekend</label>
                                    <div class="col-sm-3 d-flex align-items-center">
                                        <div class="form-check mt-0">
                                            <input type="checkbox" name="alternate_weekend" style="margin-top:-3px" id="alternate_weekend_checkbox" class="form-check-input" value="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">9.</strong> Interview Availability</label>
                                    <div class="col-sm-3 d-flex align-items-center">
                                        <div class="form-check mt-0">
                                            <input type="text" class="form-control" name="interview_availability" id="interview_availability" class="form-check-input">
                                        </div>
                                    </div>

                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">10.</strong> No Job</label>
                                    <div class="col-sm-3 d-flex align-items-center">
                                        <div class="form-check mt-0">
                                            <input type="checkbox" name="no_job" id="no_job_checkbox" style="margin-top:-3px" class="form-check-input" value="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-form-label col-sm-3"><strong style="font-size:18px">11.</strong> Visa Status</label>
                                    <div class="col-sm-3">
                                        <div class="form-check form-check-inline">
                                            <input type="radio" name="visa_status" id="british" class="form-check-input mt-0" value="British">
                                            <label class="form-check-label" for="british">British</label>
                                        </div><br>
                                        <div class="form-check form-check-inline">
                                            <input type="radio" name="visa_status" id="required_sponsorship" class="form-check-input mt-0" value="Required Sponsorship">
                                            <label class="form-check-label" for="required_sponsorship">Required Sponsorship</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-sm-1 d-flex justify-content-center align-items-center">
                                    <input type="checkbox" name="hangup_call" id="hangup_call" class="form-check-input" value="0">
                                </div>
                                <div class="col-sm-11">
                                    <label for="hangup_call" class="col-form-label" style="font-size:16px;">Call Hung up/Not Interested</label>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-form-label col-sm-3">Other Details <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <textarea name="details" id="note_details" class="form-control" cols="30" rows="4" placeholder="TYPE HERE .." required></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn bg-teal legitRipple">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- SEND CV Modal END -->

        <!-- NO NURSING HOME MODAL -->
        <div id="nurseHomeModal" class="modal fade"  tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add No Nursing Home Below:</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <form id="no_nursing_home_form" class="form-horizontal">
                        <input type="hidden" name="requestByAjax" value="yes">
                        <div class="modal-body">
                            <div class="form-group row">
                                <label class="col-form-label col-sm-3">Details</label>
                                <div class="col-sm-9">
                                    <textarea name="details" class="form-control" cols="30" rows="4" placeholder="TYPE HERE.." required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link legitRipple" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn bg-teal legitRipple">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- NO NURSING HOME MODAL END -->

        
@endsection


@section('js_file')
    <script src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}"></script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
@endsection
@if($sent_cv_count < $job['send_cv_limit'])
@section('script')
<script>
    $(document).ready(function() {
        // When the button is clicked to open the modal
        $(document).on('click', '#openCallBackModal', function() {
            var applicantId = $(this).data('applicant-id');
            var jobId = $(this).data('job-id');
            var csrfToken = $(this).data('csrf');

            // Make sure the form exists
            var form = $('#callBackForm');

            // Remove any existing hidden inputs (if any)
            form.find('input[name="applicant_hidden_id"]').remove();
            form.find('input[name="job_hidden_id"]').remove();

            // Append the hidden input fields dynamically
            form.append('<input type="hidden" name="applicant_hidden_id" value="' + applicantId + '">');
            form.append('<input type="hidden" name="job_hidden_id" value="' + jobId + '">');
            // Uncomment if you need to append CSRF token dynamically
            // form.append('<input type="hidden" name="_token" value="' + csrfToken + '">');

            // Open the modal after appending the data
            $('#callBackModal').modal('show'); // Ensure this matches your modal's ID
        });

         // Handle form submission via AJAX
        $('#callBackForm').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            var formData = $(this).serialize(); // Serialize form data
            
            $.ajax({
                url: '/sent-applicant-to-call-back-list', // URL to send the data to
                method: 'GET',
                data: formData,
                success: function(response) {
                    // Handle success (e.g., show success message, close modal, etc.)
                    toastr.success('Reason submitted successfully!');
                    $('#callBackModal').modal('hide'); // Hide the modal
                    window.location.reload();
                },
                error: function(xhr, status, error) {
                    // Handle error
                    toastr.error('Something went wrong. Please try again.');
                }
            });
        });
       
        $(document).on('click', '#openNotInterestedModal', function() {
            var applicantId = $(this).data('applicant-id');
            var jobId = $(this).data('job-id');
            var csrfToken = $(this).data('csrf');

            // Make sure the form exists
            var form = $('#notInterestedForm');

            // Remove any existing hidden inputs (if any)
            form.find('input[name="applicant_hidden_id"]').remove();
            form.find('input[name="job_hidden_id"]').remove();

            // Append the hidden input fields dynamically
            form.append('<input type="hidden" name="applicant_hidden_id" value="' + applicantId + '">');
            form.append('<input type="hidden" name="job_hidden_id" value="' + jobId + '">');
            // Uncomment if you need to append CSRF token dynamically
            // form.append('<input type="hidden" name="_token" value="' + csrfToken + '">');

            // Open the modal after appending the data
            $('#notInterestedModal').modal('show'); // Ensure this matches your modal's ID
        });

        // Handle form submission via AJAX
        $('#notInterestedForm').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            var formData = $(this).serialize(); // Serialize form data
            
            $.ajax({
                url: '/mark-applicant', // URL to send the data to
                method: 'POST',
                data: formData,
                success: function(response) {
                    // Handle success (e.g., show success message, close modal, etc.)
                    toastr.success('Reason submitted successfully!');
                    $('#notInterestedModal').modal('hide'); // Hide the modal
                    window.location.reload();
                },
                error: function(xhr, status, error) {
                    // Handle error
                    toastr.error('Something went wrong. Please try again.');
                }
            });
        });
       
        $(document).on('click', '#openSentCvModal', function() {
            var applicantId = $(this).data('applicant-id');
            var jobId = $(this).data('job-id');
            var csrfToken = $(this).data('csrf');

            // Make sure the form exists
            var form = $('#sentCvForm');

            // Remove any existing hidden inputs (if any)
            form.find('input[name="applicant_hidden_id"]').remove();
            form.find('input[name="sale_hidden_id"]').remove();

            // Append the hidden input fields dynamically
            form.append('<input type="hidden" name="applicant_hidden_id" value="' + applicantId + '">');
            form.append('<input type="hidden" name="sale_hidden_id" value="' + jobId + '">');
            // Uncomment if you need to append CSRF token dynamically
            // form.append('<input type="hidden" name="_token" value="' + csrfToken + '">');

            // Open the modal after appending the data
            $('#sent_cvModal').modal('show'); // Ensure this matches your modal's ID
        });

        // Handle form submission via AJAX
        $('#sentCvForm').on('submit', function(e) {
            e.preventDefault();
            
            var isValid = true;
            $(this).find('input[type="text"], textarea').each(function() {
                if ($(this).prop('required') && $(this).val().trim() === '') {
                    $(this).addClass('is-invalid');
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (!isValid) {
                toastr.warning('Please fill in all required fields.');
                return;
            }

            var formData = $(this).serialize();
            var params = new URLSearchParams(formData);
            var applicant_id = params.get('applicant_hidden_id');

            $.ajax({
                url: '/applicant-cv-to-quality/' + applicant_id,
                type: 'GET',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        toastr.success('CV form submitted successfully!');
                        $('#sent_cvModal').modal('hide');
                        $('#sentCvForm')[0].reset();
                    } else {
                        toastr.error('Failed to submit CV form. Please try again.');
                    }
                },
                error: function(xhr) {
                    toastr.error('An error occurred. Please try again.');
                    console.error(xhr.responseText);
                }
            });
        });

       
        $(document).on('click', '#openNurseHomeModal', function() {
            var applicantId = $(this).data('applicant-id');
            var jobId = $(this).data('job-id');
            var csrfToken = $(this).data('csrf');

            // Make sure the form exists
            var form = $('#no_nursing_home_form');

            // Remove any existing hidden inputs (if any)
            form.find('input[name="applicant_hidden_id"]').remove();
            form.find('input[name="sale_hidden_id"]').remove();

            // Append the hidden input fields dynamically
            form.append('<input type="hidden" name="applicant_hidden_id" value="' + applicantId + '">');
            form.append('<input type="hidden" name="sale_hidden_id" value="' + jobId + '">');
            // Uncomment if you need to append CSRF token dynamically
            // form.append('<input type="hidden" name="_token" value="' + csrfToken + '">');

            // Open the modal after appending the data
            $('#nurseHomeModal').modal('show'); // Ensure this matches your modal's ID
        });

        $('#no_nursing_home_form').on('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission

            var formData = $(this).serialize(); // Serialize the form data

            $.ajax({
                url: '/sent-to-nurse-home', // Your GET route URL
                method: 'GET', // Use GET
                data: formData, // Send serialized form data
                success: function(response) {
                    // Handle success (e.g., show success message, close modal, etc.)
                    toastr.success('Reason submitted successfully!');
                    $('#nurseHomeModal').modal('hide'); // Close the modal
                    window.location.reload(); // Optional: Reload the page after submission
                },
                error: function(xhr, status, error) {
                    // Handle error
                    alert('Something went wrong. Please try again.');
                }
            });
        });

    });

    $(document).ready(function() {
        $('#viewAttachmentsBtn').on('click', function() {
            // Assuming the job id is available as a data attribute in the button or passed via some other way
            let jobId = {{ $job['id'] }};  // Replace with actual dynamic job id
            
            // Make an AJAX request to fetch the files for the selected job
            $.ajax({
                url: '/get-job-attachments/' + jobId, // Modify the URL based on your route
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        let attachments = response.data;
                        let attachmentsHtml = '';
                        
                        // Check if there are attachments
                        if (attachments.length > 0) {
                            attachments.forEach(function(attachment) {
                                // You can customize the icon based on the file type
                                let icon = '';
                                if (attachment.filename.endsWith('.pdf')) {
                                    icon = '<i class="fas fa-file-pdf text-danger"></i>';
                                } else if (attachment.filename.endsWith('.docx')) {
                                    icon = '<i class="fas fa-file-word text-primary"></i>';
                                } else if (attachment.filename.endsWith('.jpg') || attachment.filename.endsWith('.png')) {
                                    icon = '<i class="fas fa-file-image text-success"></i>';
                                } else {
                                    icon = '<i class="fas fa-file-alt"></i>';
                                }

                                attachmentsHtml += `<div class="attachment_card">
                                                        <a href="${attachment.url}" target="_blank" class="d-flex align-items-center">
                                                            ${icon}
                                                            <span class="ml-2">${attachment.filename}</span>
                                                        </a>
                                                    </div>`;
                            });
                        } else {
                            attachmentsHtml = '<p>No attachments available.</p>';
                        }

                        // Insert the generated HTML into the modal
                        $('#attachmentsList').html(attachmentsHtml);
                    } else {
                        // In case of an error or no attachments
                        $('#attachmentsList').html('<p>Error fetching attachments or no attachments available.</p>');
                    }
                },
                error: function(xhr, status, error) {
                    // Handle any errors from the AJAX request
                    $('#attachmentsList').html('<p>An error occurred while fetching attachments.</p>');
                }
            });
        });
    });

    $(document).ready(function(){
        $('[title]').tooltip(); 
    });

    $(document).ready(function() {
        // Update checkbox values dynamically
        $(document).on('change', '#no_job_checkbox', function() {
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

    });

    function dateSorting(date_timestamp) {
        var a = new Date(date_timestamp * 1000);
		console.log(date_timestamp);
        var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var days = ['1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th', '13th', '14th', '15th', '16th', '17th', '18th', '19th', '20th', '21st', '22nd', '23rd', '24th', '25th', '26th', '27th', '28th', '29th', '30th', '31st'];
        var year = a.getFullYear();
        var month = months[a.getMonth()];
        var date = days[a.getDate()-1];
        var date_time = date + ' ' + month + ' ' + year;

        return date_time;
    }

    $(document).on('click', '.app_notes_form_submit', function (event) {
        var note_key = $(this).data('note_key');
        var detail = $('textarea#sent_cv_details'+note_key).val();

        var reason = $("#reason"+note_key).val();

        var $notes_form = $('#app_notes_form'+note_key);
        var $notes_alert = $('#app_notes_alert' +note_key);
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

</script>
@endsection
@endif
