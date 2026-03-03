@extends('layouts.app')

@section('style')

    <script>

      $(document).ready(function() {
        $.fn.dataTable.ext.errMode = 'none';
          $('#all_reject_applicant_sample').DataTable({
               "processing": true,
               "serverSide": true,
               "order": [],
               "ajax":"getallCrmRejectedApplicantCvAjax",
               "columns": [
                   { "data":"crm_added_date", "name": "crm_notes.crm_added_date" },
                   { "data":"crm_added_time", "name": "crm_notes.crm_added_time" },
                   { "data":"applicant_name", "name": "applicants.applicant_name" },
                   { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                   { "data":"job_category", "name": "applicants.job_category" },
                   { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
                   { "data":"applicant_phone", "name": "applicants.applicant_phone" },
                   { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                   { "data":"applicant_source", "name": "applicants.applicant_source" },
                   { "data":"details", "name": "crm_notes.details" },
                   { "data":"sub_stage", "name": "history.sub_stage" },
                   { "data":"history", "name": "history" }
               ]
          });

      });

    // $(document).ready(function() {
    //     $.fn.dataTable.ext.errMode = 'none';
    //       $('#all_reject_applicant_sample').DataTable({
    //            "processing": true,
    //            "serverSide": true,
    //            "order": [],
    //            "ajax":"getallCrmRejectedApplicantCvAjax",
    //            "columns": [
    //                { "data":"crm_rejected_cv_date || crm_added_date", "name": "crm_rejected_cv.crm_rejected_cv_date || crm_notes.crm_added_date" },
    //                { "data":"crm_rejected_cv_time || crm_added_time", "name": "crm_rejected_cv.crm_rejected_cv_time || crm_notes.crm_added_time" },
    //                { "data":"applicant_name", "name": "applicants.applicant_name" },
    //                { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
    //                { "data":"job_category", "name": "applicants.job_category" },
    //                { "data":"applicant_postcode", "name": "applicants.applicant_postcode" },
    //                { "data":"applicant_phone", "name": "applicants.applicant_phone" },
    //                { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
    //                { "data":"applicant_source", "name": "applicants.applicant_source" },
    //                { "data":"crm_rejected_cv_note || details", "name": "crm_rejected_cv.crm_rejected_cv_note || crm_notes.details" },
    //                { "data":"sub_stage", "name": "history.sub_stage" },
    //                { "data":"history", "name": "history" }
    //            ]
    //       });

    //   });

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
                        <span class="font-weight-semibold">CRM All Rejected - Applicants</span>
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Resources</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Crm</span>
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
                    <h5 class="card-title">Rejected Applicants
                    </h5>
                </div>
				<div class="card-body">
                   
                    <p></p>
                    
                    @can('applicant_export')
                    <a href="#" class="btn bg-slate-800 legitRipple float-right"
                    data-controls-modal="#export_all_rejected_applicant_action"
                                           data-backdrop="static" data-keyboard="false" data-toggle="modal"
                                           data-target="#export_all_rejected_applicant_action" style="margin-right:20px;">
                        <i class="icon-cloud-upload"></i>
                        &nbsp;Export</a>
                    @endcan
                </div>
                <table class="table table-hover table-striped" id="all_reject_applicant_sample">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Name</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Postcode</th>
                        <th>Phone#</th>
                        <th>Landline#</th>
                        <th>Source</th>
                        <th>Notes</th>
                        <th>Rejection Type</th>
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
    $(document).on('click', '.reject_history', function () {
        // alert('sdfjskj');
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
	$(document).on('focus',".pickadate-year", function(){
        $(this).pickadate({
            selectYears: 4
        });
    });
</script>
@endsection
