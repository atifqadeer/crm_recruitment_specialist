@extends('layouts.app')

@section('style')

    <script>
        var new_columns = [
            { "data":"created_at", "name": "created_at" },
            { "data":"applicant_added_time", "name": "applicant_added_time" },
            { "data":"applicant_name", "name": "applicant_name" },
            { "data":"applicant_job_title", "name": "applicant_job_title", "orderable": false },
            { "data":"job_category", "name": "job_category" },
            { "data":"applicant_postcode", "name": "applicant_postcode" },
            { "data":"applicant_phone", "name": "applicant_phone" },
            { "data":"applicant_homePhone", "name": "applicant_homePhone" },
            { "data":"applicant_source", "name": "applicant_source" },
            { "data":"applicant_notes", "name": "applicant_notes" }
        ];



    // columns.push({ "data":"applicant_source", "name": "applicant_source" });
    // columns.push({ "data":"unit_name", "name": "units.unit_name" });
    // columns.push({ "data":"applicant_notes", "name": "applicant_notes" });
      $(document).ready(function() {
        $.fn.dataTable.ext.errMode = 'none';
          <?php if (\Illuminate\Support\Facades\Auth::user()->hasAnyPermission(['applicant_edit','applicant_view','applicant_history','applicant_note-create','applicant_note-history'])): ?>
            new_columns.push({ "data":"action", "name": "action" })
          <?php endif; ?>
          $('#applicant_sample_2').DataTable({
              
               "processing": true,
               "serverSide": true,
               "responsive": true,
               "ajax":"getIdleApplicantsNonNurse",
               "order": [],
               "columns": new_columns
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
                        <span class="font-weight-semibold">Idle Applicants</span> - All
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Idle Applicants</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="content">
            <div class="card">

               
                <div class="card-body">
                    <span style="height:15px;width:15px;background-color: #394357;
                    border-radius: 50%;display: inline-block;"></span>
                    <span style="position: relative;bottom: 3px;">Applicant is Rejected on All Stages of Application for 3 Months</span>
                    <p></p>
                    <span style="height:15px;width:15px;background-color: #00796a;
                    border-radius: 50%;display: inline-block;"></span>
                    <span style="position: relative;bottom: 3px;">Applicant is in Positive Stages of CRM</span>
                    <!-- @can('applicant_import')
                    <a href="#"
                       data-controls-modal="#import_applicant_csv"
                       data-backdrop="static"
                       data-keyboard="false" data-toggle="modal"
                       data-target="#import_applicant_csv" class="btn bg-slate-800 legitRipple float-right">
                        <i class="icon-cloud-download"></i>
                        &nbsp;Import</a>
                    @endcan -->
                    @can('applicant_export')
                    <a href="{{ route('export_idel_applicants') }}" class="btn bg-slate-800 legitRipple float-right" style="margin-right:20px;">
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

                <table class="table table-hover table-striped" id="applicant_sample_2">
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
                       
                        <!-- <th>Updated By</th> -->
                        <!-- @canany(['applicant_edit','applicant_view','applicant_history','applicant_note-create','applicant_note-history'])
                        <th>Action</th>
                        @endcanany -->
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

//     $(document).on('click', '.user-statistics', function (event) {
//         // $("#user_stats_end_date_value").dialog({ modal: true, title: event.title, width:350});
//         var user_key = $(this).data('user_key');
//         var user_name = $(this).data('user_name');
//         var start_date = $('#user_stats_start_date_value').val();
//         var end_date = $('#user_stats_end_date_value').val();

//         $('#user_name').html(user_name);

//         $.ajax({
//             url: "{{ route('userStatistics') }}",
//             type: "POST",
//             data: {
//                 _token: "{{ csrf_token() }}",
//                 user_key: user_key,
//                 user_name: user_name,
//                 start_date: start_date,
//                 end_date: end_date
//             },
//             success: function(response){
//                 $('#user_stats_details').html(response);
//                 $('#user_s_date').html(start_date);
//                 $('#user_e_date').html(end_date);
//             },
//             error: function(response){
//                 let raw_html = '<p>WHOOPS! Something Went Wrong!!</p>';
//                 $('#user_stats_details').html(raw_html);
//             }
//         });
//     });
//     var zIndexModal = $(modal).css('z-index');
// $(datePicker).css('z-index', zIndexModal + 1);
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
