@extends('layouts.app')
@section('style')

    <script>

      $(document).ready(function() {
		   var app_id = $("#hidden_job_value").val();
        $.fn.dataTable.ext.errMode = 'none';
          $('#last_2_months_sample').DataTable({
               "processing": true,
               "serverSide": true,
               //"ajax":"getlast2MonthsApp",
			    "ajax":"{!! url('getlast2MonthsApp') !!}/"+app_id,

      
			 
               "columns": [
                   { "data":"updated_at", "name": "applicants.updated_at","orderable": true },
                   { "data":"applicant_added_time", "name": "applicant_added_time", "orderable": false },
                   { "data":"applicant_name", "name": "applicants.applicant_name" },
				   { "data":"applicant_email", "name": "applicants.applicant_email" },
                   { "data":"applicant_job_title", "name": "applicants.applicant_job_title" },
                   { "data":"job_category", "name": "applicants.job_category" },
                   { "data":"applicant_postcode", "name": "applicants.applicant_postcode", "orderable": true },
                   { "data":"applicant_phone", "name": "applicants.applicant_phone" },
				   { "data":"download", "name": "download", "orderable": false },
                   { "data":"updated_cv", "name": "updated_cv", "orderable": false },
                  { "data":"upload", "name": "upload", "orderable": false },
                   { "data":"applicant_homePhone", "name": "applicants.applicant_homePhone" },
                   { "data":"applicant_source", "name": "applicants.applicant_source" },
                   { "data":"applicant_notes", "name": "applicants.applicant_notes" },
                   { "data":"history", "name": "history", "orderable": false },
                   { "data":"status", "name": "applicants.status", "orderable": true}
               ],
		
			   "rowCallback": function( row, data ) {
                  var dateCell = data.updated_at;
                  var sortedDate = dateSorting (dateCell);
                  $('td:eq(0)', row).html(sortedDate);
              }
          });

      });

    </script>

@endsection 
@section('content')
    <!-- Main content -->
    <div class="content-wrapper">
        <input type="hidden" id="hidden_job_value" value="{{ $id}}">

        <!-- Page header -->
{{--        <div class="page-header page-header-dark has-cover" style="border: 1px solid #ddd; border-bottom: 0;">--}}
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Applicant Added</span> -
                        @if($interval == 7)Last 7 Days @elseif($interval == 21) Last 21 Days @elseif($interval == 60) All @endif
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Resources</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">@if($interval == 7)Last 7 Days @elseif($interval == 21) Last 21 Days @elseif($interval == 60) All @endif</span>
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
                    <h5 class="card-title">Active Applicants Added -
                        @if($interval == 7)
                            Last 7 Days - Nurses
                        @elseif($interval == 21)
                            Last 21 Days
                        @elseif($interval == 60)
                            All Applicants
                        @endif
                    </h5>
                </div>
				<div class="card-body">
                   
                    <p></p>
                    
                    @can('applicant_export')
                 
					 <div class="card-body">
                            {{ Form::open(array('route' => 'export2_months_applicants','method' => 'GET' )) }}
                            <button type="submit" class="btn bg-slate-800 legitRipple float-right" style="margin-right:20px;">
                                <i class="icon-cloud-upload"></i>
                                &nbsp;Export</button>
                            <input type="hidden" id="hidden_job_value" name="hidden_job_value" value="{{$id}}">

                            {{ Form::close() }}

                        </div>
                    @endcan
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
                <table class="table table-hover table-striped" id="last_2_months_sample">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
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
	
	 function dateSorting(date_timestamp) {
     
        var a = new Date(date_timestamp );
        // var a = date_timestamp * 1000;
        console.log(a);
        var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var days = ['1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th', '13th', '14th', '15th', '16th', '17th', '18th', '19th', '20th', '21st', '22nd', '23rd', '24th', '25th', '26th', '27th', '28th', '29th', '30th', '31st'];
        var year = a.getFullYear();
        var month = months[a.getMonth()];
        var date = days[a.getDate()-1];
        var date_time = date + ' ' + month + ' ' + year;

        return date_time;
    }
	
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
	
	$(document).on('click', '.notes_form_submit', function (event) {
        // event.preventDefault();
        // alert('sdfafas');
        var note_key = $(this).data('note_key');
        var detail = $('textarea#sent_cv_details'+note_key).val();
        // var reason =$(#reason option:selected).val();
        var reason = $("#reason"+note_key).val();
        var $notes_form = $('#notes_form'+note_key);
        var $notes_alert = $('#notes_alert' +note_key);
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
    //  alert(app_id);
     $(".modal-body #applicant_id").val(app_id);
});
</script>
@endsection
