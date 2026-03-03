@extends('layouts.app')

@section('style')
<script>
    var columns = [
        { "data":"updated_at", "name": "cv_notes.created_at" },
        { "data":"updated_time", "name": "cv_notes.created_at", "orderable": false},
		{ "data":"name", "name": "users.name"},
        { "data":"applicant_name", "name": "applicants.applicant_name" },
        { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
        { "data":"applicant_postcode", "name": "applicants.applicant_postcode", "orderable": true },
        { "data":"applicant_phone", "name": "applicants.applicant_phone" }
    ];

    <?php if (\Illuminate\Support\Facades\Auth::user()->hasPermissionTo('quality_CVs_cv-download')): ?>
    columns.push({ "data":"download", "name": "download", "orderable": false });
    <?php endif; ?>
	columns.push({ "data":"updated_cv", "name": "updated_cv", "orderable": false });
    columns.push({ "data":"upload", "name": "upload", "orderable": false });
    <?php if (\Illuminate\Support\Facades\Auth::user()->hasPermissionTo('quality_CVs_job-detail')): ?>
    columns.push({ "data":"job_details", "name": "job_details", "orderable": false });
    <?php endif; ?>

    columns.push({ "data":"office_name", "name": "offices.office_name" });
    columns.push({ "data":"unit_name", "name": "units.unit_name" });
    columns.push({ "data":"postcode", "name": "sales.postcode" });
      columns.push({
        data: 'details',
        name: 'cv_notes.details',
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
    columns.push({ "data":"action", "name": "action", "orderable": false });
	
	   var onHoldColumns = [
        { "data":"updated_at", "name": "revert_stages.updated_at" },
        { "data":"updated_time", "name": "revert_stages.updated_at", "orderable": false},
		{ "data":"user_name", "name": "users.name"},
        { "data":"applicant_name", "name": "applicants.applicant_name" },
        { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
        { "data":"applicant_postcode", "name": "applicants.applicant_postcode", "orderable": true },
        { "data":"applicant_phone", "name": "applicants.applicant_phone" }
    ];

    <?php if (\Illuminate\Support\Facades\Auth::user()->hasPermissionTo('quality_CVs_cv-download')): ?>
    onHoldColumns.push({ "data":"download", "name": "download", "orderable": false });
    <?php endif; ?>
	onHoldColumns.push({ "data":"updated_cv", "name": "updated_cv", "orderable": false });
    onHoldColumns.push({ "data":"upload", "name": "upload", "orderable": false });
    <?php if (\Illuminate\Support\Facades\Auth::user()->hasPermissionTo('quality_CVs_job-detail')): ?>
    onHoldColumns.push({ "data":"job_details", "name": "job_details", "orderable": false });
    <?php endif; ?>

    onHoldColumns.push({ "data":"office_name", "name": "offices.office_name" });
    onHoldColumns.push({ "data":"unit_name", "name": "units.unit_name" });
    onHoldColumns.push({ "data":"postcode", "name": "sales.postcode" });
    onHoldColumns.push({
        data: 'revert_notes',
        name: 'details',
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

    onHoldColumns.push({ "data":"action", "name": "action", "orderable": false });

      $(document).ready(function() {
        $.fn.dataTable.ext.errMode = 'none';

        // Initialize DataTable for the active tab
        var activeTable = $('#quality_sent_sample_1').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": "get-quality-cv-applicants",
            "order": [[0, 'desc']],
            "columns": columns
        });

        // Initialize DataTable for the hold tab (but don't load data yet)
        var holdTable = $('#quality_sent_sample_hold_cv').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax":"get-quality-hold-cv-applicants",
            "order": [[0, 'desc']],
            "columns": onHoldColumns,
            "deferRender": true  // Defer rendering until the tab is shown
        });
		  
		    var noJObTable = $('#quality_sent_sample_no_job_cv').DataTable({
            "processing": true,
            "serverSide": true,
            // "ajax":"get-quality-hold-cv-applicants",
            "ajax": {
                "url": "get-quality-no-job-cv-applicants",
                "contentType": "application/json; charset=utf-8", // Add this line
                "type": "GET"
            },
            "order": [[0, 'desc']],
            "columns": columns,
            "deferRender": true  // Defer rendering until the tab is shown
        });

        // Add a click event listener to the tab links
        $('.nav-tabs a').on('shown.bs.tab', function (e) {
            var targetTab = $(e.target).attr("href");

            // Check which tab is active and load DataTable accordingly
            if (targetTab === "#CV_active") {
                activeTable.ajax.reload(); // Reload data for the active tab
            } else if (targetTab === "#CV_hold") {
                // Load data for the hold tab only when it's shown for the first time
                if (holdTable.data().any()) {
                   // console.log('cv_hole');
                    holdTable.ajax.reload();
                }
            }else if (targetTab === "#CV_no_job") {
                noJObTable.ajax.reload();
            }
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
                        <span class="breadcrumb-item active">CVs All</span>
                    </div>
                </div>
				@if(Auth::user()->name=='Super Admin')
                <div class="nav-item avatar dropdown">
                    <a class="nav-link dropdown-toggle waves-effect waves-light" id="navbarDropdownMenuLink-5" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                        <?php $count=0; ?>
                            @foreach($user_info as $info)
                                <?php
                                   $count+=$info->total;
                                ?>
                                
                            @endforeach
                        <span class="badge badge-danger ml-2 total_notifications_new" id="total_notify_count" >{{$count}}</span>
                        <i class="fas fa-bell"></i>
                    </a>
                    <ul class="dropdown-menu divScroll" id="notification_list">
                        <li class="head text-light bg-dark">
                        <div class="row">
                        <div class="col-lg-12 col-sm-12 col-12">
                        <span>Notifications <span class="total_notifications_new">{{$count}}</span></span>
                        <a href="{{route('mark_msg_as_read')}}" class="float-right text-light">Mark all as read</a>
                        </div>
                        </li>
                    <!-- <div class="dropdown-menu dropdown-menu-lg-right dropdown-secondary" aria-labelledby="navbarDropdownMenuLink-5"> -->
                        @foreach($user_info as $info)
                        <?php 
                        // $applicant_notify_name = substr(str_replace(array('{','}','"'),'',$info->applicant_name),5); 
                        $applicant_notify_name = $info->applicant_name.' ('.$info->applicant_postcode.')'; 
                        ?>
                            <a href="#" id="{{$applicant_notify_name}}" data-id="{{$info->applicant_id}}" class="notify_click" >
                            <li class="notification-box">
                                <div class="row">
                                <div class="col-lg-3 col-sm-3 col-3 text-center">
                                <img src="https://bootdey.com/img/Content/avatar/avatar1.png" class="w-50 rounded-circle">
                                </div>
                                <div class="col-lg-8 col-sm-8 col-8">
                                <strong class="text-info">{{$applicant_notify_name}}</strong>
                                <span class="badge badge-danger ml-2 applicant_notifications" style="float: right;">{{$info->total}}</span>
                                <div>
                                {{ str_limit($info->message, $limit = 75, $end = '...') }}
                                    <!-- {{substr($info->message, 0, 70).'...'}} -->
                                </div>
                                <small class="text-warning">{{$info->created_at}}</small>
                                </div>
                                </div>
                            </li>
                            </a>
                            <hr>
                        @endforeach
                    </ul>
                    <!-- </div> -->
                </div>
                @endif
            </div>
        </div>
        <!-- /page header -->

@include('layouts.small_chat_box')
        <!-- Content area -->
        <div class="content">

            <!-- Default ordering -->
            <div class="card">
                <div class="card-header header-elements-inline">
                      <ul class="nav nav-tabs nav-tabs-highlight">
                            <li class="nav-item">
                                <a href="#CV_active" class="nav-link active legitRipple" data-toggle="tab" data-datatable_name="quality_sent_cv_sample">Active CVs</a>
                            </li>
                            <li class="nav-item">
                                <a href="#CV_hold" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="quality_hold_sent_cv_sample">Open CVs</a>
                            </li>
						  <li class="nav-item">
                                <a href="#CV_no_job" class="nav-link legitRipple" data-toggle="tab" data-datatable_name="quality_no_job_sent_cv_sample">No Job CVs</a>
                            </li>
                        </ul>
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
                <div class="card-body">
                </div>
  				<div class="tab-content">
                    <div class="tab-pane active" id="CV_active">
                        <table class="table table-striped table-hover" id="quality_sent_sample_1">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Sent By</th>
                                <th>Name</th>
                                <th>Job Title</th>
                                <th>Postcode</th>
                                <th>Phone#</th>
                                @can('quality_CVs_cv-download')
                                    <th>CV</th>
                                @endcan
                                <th>Updated CV</th>
                                <th>Upload CV</th>
                                @can('quality_CVs_job-detail')
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
                    <div class="tab-pane" id="CV_hold">
                        <table class="table table-striped table-hover" id="quality_sent_sample_hold_cv">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Sent By</th>
                                <th>Name</th>
                                <th>Job Title</th>
                                <th>Postcode</th>
                                <th>Phone#</th>
                                @can('quality_CVs_cv-download')
                                    <th>CV</th>
                                @endcan
                                <th>Updated CV</th>
                                <th>Upload CV</th>
                                @can('quality_CVs_job-detail')
                                    <th>Job Details</th>
                                @endcan
                                <th>Head Office</th>
                                <th>Unit</th>
                                <th>Job Postcode</th>
                                <th>Notes</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>

                    </div>
	  				<div class="tab-pane" id="CV_no_job">
                        <table class="table table-striped table-hover" id="quality_sent_sample_no_job_cv">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Sent By</th>
                                <th>Name</th>
                                <th>Job Title</th>
                                <th>Postcode</th>
                                <th>Phone#</th>
                                @can('quality_CVs_cv-download')
                                    <th>CV</th>
                                @endcan
                                <th>Updated CV</th>
                                <th>Upload CV</th>
                                @can('quality_CVs_job-detail')
                                    <th>Job Details</th>
                                @endcan
                                <th>Head Office</th>
                                <th>Unit</th>
                                <th>Job Postcode</th>
                                <th>Notes</th>
                                <th>Action</th>
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
		
@section('script')
<script>
    $(document).on("click", ".import_cv", function () {
     var app_id = $(this).data('id');
    //  alert(app_id);
     $(".modal-body #applicant_id").val(app_id);
});
	
	  $(document).on('click', '.notes_history', function (event) {
        var applicant = $(this).data('applicant');
        var sale = $(this).data('sale');
        $.ajax({
            url: "{{ route('get-quality-notes') }}",
            type: "GET",
            data: {
                _token: "{{ csrf_token() }}",
                applicant_id: applicant,
                sale_id: sale,
                // module: "Applicant"
            },
            success: function(response){
              var data_notes=response.data;
                $('#applicants_notes_history'+applicant).empty();

                var html = '';
                $.each(data_notes, function(index, value) {
                    html += '<div class="col-1"></div>';
                    html += '<p>';
                    if (value.user && value.user.name) {
                        var capitalizedFirstName = value.user.name.charAt(0).toUpperCase() + value.user.name.slice(1);
                        html += '<span class="font-weight-semibold">' + (index + 1) + '. Created by: </span>' + capitalizedFirstName;
                    } else {
                        html += '<span class="font-weight-semibold">' + (index + 1) + '. Created by: </span>N.A';
                    }
                    // html += '<span class="font-weight-semibold">' + (index + 1) + '. Created by: </span>' + value.user.name.toUpperCase();
                    html += '<span class="font-weight-semibold"> - Created at: </span>' + value.updated_at;
                    html += '</p>';
                    html += '<p>';

                    // Check the stage and append details accordingly
                    if (value.stage === 'quality_note') {
                        html += '<span class="font-weight-semibold">Quality Reject Note : </span>' + value.notes;
                    } else if (value.stage === 'cv_hold') {
                        html += '<span class="font-weight-semibold">CV Hold Note: </span>' + value.notes;
                    }else if (value.stage === 'no_job_quality_cvs') {

                        html += '<span class="font-weight-semibold">CV No job Note: </span>' + value.notes;
                    }

                    html += '</p>';
                    html += '<hr class="w-25 center">';
                });

                // Append the generated HTML to the existing content
                $('#applicants_notes_history'+applicant).append(html);
            },
            error: function(response){
                var raw_html = '<p>WHOOPS! Something Went Wrong!!</p>';
                $('#applicants_notes_history'+applicant).html(raw_html);
            }
        });
    });
</script>
@endsection
