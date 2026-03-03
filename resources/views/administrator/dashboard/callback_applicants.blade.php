@extends('layouts.app')

@section('style')

    <script>

        $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';
            $('#applicant_sample_1').DataTable({

                "processing": true,
                "serverSide": true,
                "responsive": true,
                "ajax":{
                    "url": "{{ url('get-callback-applicants')  }}",
                    "type": "post",
                    "data": {
                        _token: "{{ csrf_token() }}",
                        app_cb_daily_date: "{{ session('app_cb_daily_date') }}"
                    }
                },
                "order": [[0, "desc"]],
                "columns": [
                    { "data":"created_at", "name": "applicants.updated_at" },
                    { "data":"applicant_added_time", "name": "applicant_added_time" },
                    { "data":"applicant_name", "name": "applicant_name" },
                    { "data":"applicant_job_title", "name": "applicant_job_title" },
                    { "data":"job_category", "name": "job_category" },
                    { "data":"applicant_postcode", "name": "applicant_postcode" },
                    { "data":"applicant_phone", "name": "applicant_phone" },
                    { "data":"applicant_homePhone", "name": "applicant_homePhone" },
                    { "data":"applicant_source", "name": "applicant_source" },
                    { "data":"applicant_notes", "name": "applicant_notes" },
                    { "data":"updated_by", "name": "updated_by" },
                    { "data":"action", "name": "action" }
                ]
            });

        });

    </script>

@endsection

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-dark has-cover" style="border: 1px solid #ddd; border-bottom: 0;">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Applicants</span> - Callback
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Callback Applicants</span>
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
                    <h5 class="card-title">Callback Applicants</h5>
                </div>

                <table class="table table-hover table-striped" id="applicant_sample_1">
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
                        <th>Updated By</th>
                        <th>Action</th>
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

            </script>
@endsection
