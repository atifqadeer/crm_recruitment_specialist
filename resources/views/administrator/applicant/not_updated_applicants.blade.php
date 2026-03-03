@extends('layouts.app')

@section('style')

    <script>
        var columns = [
            { "data":"created_at", "name": "created_at" },
            { "data":"applicant_added_time", "name": "applicant_added_time" },
            { "data":"applicant_name", "name": "applicant_name" },
            { "data":"applicant_email", "name": "applicant_email" },
            { "data":"applicant_job_title", "name": "applicant_job_title", "orderable": false },
            { "data":"job_category", "name": "job_category" },
            { "data":"applicant_postcode", "name": "applicant_postcode" },
            { "data":"applicant_phone", "name": "applicant_phone" },
            { "data":"download", "name": "download", "orderable": false },
            { "data":"updated_cv", "name": "updated_cv", "orderable": false },
            { "data":"upload", "name": "upload", "orderable": false },
            { "data":"applicant_homePhone", "name": "applicant_homePhone" },
            { "data":"applicant_source", "name": "applicant_source" }
        ];



    // columns.push({ "data":"applicant_source", "name": "applicant_source" });
    // columns.push({ "data":"unit_name", "name": "units.unit_name" });
    // columns.push({ "data":"applicant_notes", "name": "applicant_notes" });
      $(document).ready(function() {
        $.fn.dataTable.ext.errMode = 'none';
          <?php if (\Illuminate\Support\Facades\Auth::user()->hasAnyPermission(['applicant_edit','applicant_view','applicant_history','applicant_note-create','applicant_note-history'])): ?>
          columns.push({ "data":"action", "name": "action" })
          <?php endif; ?>
          $('#applicant_sample_1').DataTable({
              
               "processing": true,
               "serverSide": true,
               "responsive": true,
               "ajax":"get_not_updated_applicants",
               "order": [],
               "columns": columns,
               lengthMenu: [
            [10, 25, 50, 100, 500, -1],
            [10, 25, 50, 100, 500, 'All'],
        ]
          });

      });

    </script>

@endsection
@section('style')
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}">
    <style>
    .datepicker{z-index:1151 !important;}
</style>
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
                        <span class="font-weight-semibold">Applicants</span> - All
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Not Updated Applicants</span>
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
                    <h5 class="card-title">Not Updated Applicants</h5>
                </div>
               
                <div class="card-body">
                    
                    @can('applicant_no-update')
                    <a href="{{ route('applicants.export_not_update_csv') }}" class="btn bg-slate-800 legitRipple float-right" style="margin-right:20px;">
                        <i class="icon-cloud-upload"></i>
                        &nbsp;Export</a>
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


                <table class="table table-hover table-striped" id="applicant_sample_1">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Postcode</th>
                        <th>Phone#</th>
                        <th>Applicant CV</th>
                        <th>Updated CV</th>
                        <th>Upload CV</th>
                        <th>Landline#</th>
                        <th>Source</th>
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

@section('js_file')
    <script src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}"></script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
    <!-- <script src="{{ asset('js/donut_chart.js') }}"></script>s -->
@endsection
@section('script')

<script>
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
    //  alert(app_id);
     $(".modal-body #applicant_id").val(app_id);
});
</script>
@endsection
