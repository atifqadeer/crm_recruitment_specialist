@extends('layouts.app')
@section('style')

    <script>
 var table;
        $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';
           table= $('#paid_applicant_sample').DataTable({
                "processing": true,
                "serverSide": true,
                "order": [],
                "ajax":"getCrmPaidApplicantsCvAjax",
                "columns": [
                    { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                    { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
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
                    { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                    { "data":"applicant_source", "name": "applicants.applicant_source" },
                    { "data":"details", "name": "crm_notes.details" },
                    { "data":"history", "name": "history", "orderable": false, "searchable": false }
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
                        <span class="font-weight-semibold">Applicants</span> - Paid
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Resources</a>
                        <a href="#" class="breadcrumb-item">CRM</a>
                        <span class="breadcrumb-item active">Paid</span>
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
                    <h5 class="card-title">Paid Applicants
                    </h5>
                </div>
				<div class="card-body">
                   
                    <p></p>
                    
                    @can('applicant_export')
                    <a href="{{ route('export_crm_paid_applicants_cv') }}" class="btn bg-slate-800 legitRipple float-right" style="margin-right:20px;">
                        <i class="icon-cloud-upload"></i>
                        &nbsp;Export</a>
                    @endcan
					
					 <div class="header-elements col-md-12 justify-content-end">
                        <div class="col-md-12">
                            <form class="form-inline" name="sale_filter_form" id="sale_filter_form">
                                <div class="col-md-4">
                                    <select name="job_category" id="job_category_filter" class="form-control select">
                                        <option value="">Job Category</option>
                                        <option value="nurse">Nurse</option>
                                        <option value="non-nurse">Non Nurse</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex">
                                    <button type="submit" class="btn btn-outline bg-teal-400 text-teal-400 border-teal-400 border-2 flex-grow-1 mr-2 search"><i
                                                class="fa fa-search"></i> Search</button>
                                    <button id="refresh_filters" class="btn btn-outline alpha-teal text-teal-400 border-teal-400 border-2 float-right"><i
                                                class="fas fa-sync"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>
					
                </div>
                <table class="table table-hover table-striped" id="paid_applicant_sample">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Name</th>
                        <th>Title</th>
                        <th>Category</th>
						 <th>Department</th>
						 <th>Sub Department</th>
                        <th>Postcode</th>
                        <th>Phone#</th>
                        <th>Landline#</th>
                        <th>Source</th>
                        <th>Notes</th>
                        <th>History</th>
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
	
	  $(document).on('click', '.search', function (event) {
        // alert(1);
        event.preventDefault();
        var form_data = $('#sale_filter_form').serialize();
        table.ajax.url( 'getCrmPaidApplicantsCvAjax?'+form_data ).load();
    });
	
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
</script>
@endsection