@extends('layouts.app')

@section('style')
<script>

    var columns = [
        { "data":"quality_added_date", "name": "quality_notes.created_at" },
        { "data":"quality_added_time", "name": "quality_notes.quality_added_time", "orderable": false},
		{ "data":"name", "name": "users.name"},
        { "data":"applicant_name", "name": "applicants.applicant_name" },
        { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
        { "data":"applicant_postcode", "name": "applicants.applicant_postcode", "orderable": true },
        { "data":"applicant_phone", "name": "applicants.applicant_phone", "orderable": true },
        { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" }
    ];

    <?php if (\Illuminate\Support\Facades\Auth::user()->hasPermissionTo('quality_CVs-Rejected_cv-download')): ?>
    columns.push({ "data":"download", "name": "download", "orderable": false, "searchable": false });
    <?php endif; ?>

    <?php if (\Illuminate\Support\Facades\Auth::user()->hasPermissionTo('quality_CVs-Rejected_job-detail')): ?>
    columns.push({ "data":"job_details", "name": "job_details", "orderable": false, "searchable": false });
    <?php endif; ?>

    columns.push({ "data":"office_name", "name": "offices.office_name" });
    columns.push({ "data":"unit_name", "name": "units.unit_name" });
    columns.push({ "data":"postcode", "name": "sales.postcode" });
    //columns.push({ "data":"details", "name": "quality_notes.details", "orderable": false });
	   columns.push({
        data: 'details',
        name: 'quality_notes.details',
        orderable: false,
        render: function(data, type, row) {
            if (type === 'display' || type === 'filter') {
                var div = document.createElement('div');
                div.innerHTML = data;
                return div.textContent || div.innerText || '';
            }
            return data;
        }
    });
    columns.push({ "data":"action", "name": "action", "orderable": false, "searchable": false });

    $(document).ready(function() {
        $.fn.dataTable.ext.errMode = 'none';
        $('#quality_reject_sample_1').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax":"get-reject-cv-applicants",
            "order": [[ 0, 'desc' ]],
            "columns": columns
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
                        <span class="font-weight-semibold">Quality</span> - Resource
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Quality</a>
						<a href="#" class="breadcrumb-item">Resource</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">CVs Rejected</span>
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
        </div>
        <!-- /page header -->


        <!-- Content area -->
        <div class="content">

            <!-- Default ordering -->
            <div class="card">

                <table class="table table-hover table-striped" id="quality_reject_sample_1">
                    <thead>
                    <tr>
                        <th>Date </th>
                        <th>Time </th>
						<th>Sent By</th>
                        <th>Name</th>
                        <th>Job Title</th>
                        <th>Postcode</th>
                        <th>Phone#</th>
                        <th>Landline#</th>
                        @can('quality_CVs-Rejected_cv-download')
                        <th>CV</th>
                        @endcan
                        @can('quality_CVs-Rejected_job-detail')
                        <th>Job Details</th>
                        @endcan
                        <th>Head Office</th>
                        <th>Unit</th>
                        <th>Job Postcode</th>
                        <th>Notes</th>
                        <th >Action</th>
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
