@extends('layouts.app')

@section('style')

    <script>
        var columns = [
            { "data":"created_at", "name": "created_at" },
            { "data":"updated_at", "name": "updated_at" },
            { "data":"job_category", "name": "job_category" },
            { "data":"job_title", "name": "job_title" },
            { "data":"head_office", "name": "head_office" },
            { "data":"unit", "name": "unit" },
            { "data":"postcode", "name": "postcode"},
            { "data":"type", "name": "type" },
            { "data":"experience", "name": "experience" },
            { "data":"qualification", "name": "qualification"},
            { "data":"salary", "name": "salary" },
            { "data":"sent_cv", "name": "sent_cv" },

        ];
        var table;
        $(document).ready(function() {
			var date=$('#app_daily_date').val();
            var url = "close_sale_detail" + '/' + date;
            // $("#sale_filter_office").select2({ width: '99%' });
            $.fn.dataTable.ext.errMode = 'none';
            table = $('#sale_sample_1').DataTable({
              "processing": true,
                "serverSide": true,
                 "ajax":url,
                "order": [[ 1, 'desc' ]],
                "columns": columns,
                "drawCallback": function( row, data ) {
                    $('[data-popup="tooltip"]').tooltip();
                }
            });

            // table.destroy();

        });
    </script>

@endsection

@section('content')
    <!-- Main content -->
    <div class="content-wrapper">
        <input type="hidden" name="app_daily_date" id="app_daily_date" value="{{$today}}">

        <!-- Page header -->
        {{--        <div class="page-header page-header-dark has-cover" style="border: 1px solid #ddd; border-bottom: 0;">--}}
        <div class="page-header page-header-dark has-cover">
            <div class="page-header-content header-elements-inline">
                <div class="page-title">
                    <h5>
                        <i class="icon-arrow-left52 mr-2"></i>
                        <span class="font-weight-semibold">Sales</span> - All
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Sales</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Close</span>
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
                    <div class="col-md-2">
                        <h5 class="card-title">Close Sales</h5>
                    </div>
                    <div class="header-elements col-md-10 justify-content-end">
                        <div class="col-md-12" style="padding-right: 0 !important;">

                        </div>
                    </div>
                </div>

                <div class="card-body row pb-0">
                    <div class="col-md-6">
                        @if ($message = Session::get('error'))
                            <div class="alert alert-danger border-0 alert-dismissible mb-0 p-2">
                                <button type="button" class="close p-2" data-dismiss="alert"><span>×</span></button>
                                <span class="font-weight-semibold">Error!</span> {{ $messsage }}
                            </div>
                        @endif
                        @if ($message = Session::get('success'))
                            <div class="alert alert-success border-0 alert-dismissible mb-0 p-2">
                                <button type="button" class="close p-2" data-dismiss="alert"><span>×</span></button>
                                <span class="font-weight-semibold">Success!</span> {{ $message }}
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">

                    </div>
                </div>


                <table class="table table-striped" id="sale_sample_1">
                    <thead>
                    <tr>
                        <th>Created Date</th>
                        <th>Updated Date</th>
                        <th>Category</th>
                        <th>Job Title</th>
                        <th>Head Office</th>
                        <th>Unit</th>
                        <th>Postcode</th>
                        <th>Type</th>
                        <th>Experience</th>
                        <th>Qualification</th>
                        <th>Salary</th>
                        <th>Status</th>
                        {{--                        <th>Action</th>--}}
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
                    alert('sdfsf');
                    event.preventDefault();
                    var note_key = $(this).data('note_key');
                    var note_form = $('#note_form'+note_key);
                    var note_alert = $('#note_alert' + note_key);
                    console.log(note_key);
                    var note_details = $.trim($("#note_details" + note_key).val());
                    if (note_details) {
                        $.ajax({
                            url: "{{ route('module_note.store') }}",
                            type: "POST",
                            data: note_form.serialize(),
                            success: function (response) {
                                // $note_form.trigger('reset');
                                note_alert.html(response);
                                setTimeout(function () {
                                    $('#add_sale_note' + note_key).modal('hide');
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
                    var sale = $(this).data('sale');

                    $.ajax({
                        url: "{{ route('notesHistory') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            module_key: sale,
                            module: "Sale"
                        },
                        success: function(response){
                            $('#sales_notes_history'+sale).html(response);
                        },
                        error: function(response){
                            var raw_html = '<p>WHOOPS! Something Went Wrong!!</p>';
                            $('#sales_notes_history'+sale).html(raw_html);
                        }
                    });
                });

                // fetch head offices by selected user
                $(document).on('change', '#sale_filter_user', function (event) {
                    var user_key = $(this).val();
                    $(this).select({ width: 'resolve' });
                    $.ajax({
                        url: "{{ route('userOffices') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            user_key: user_key
                        },
                        success: function (response) {
                            $("#sale_filter_office").html(response)
                            $("#sale_filter_office").select2({ width: '99%' });
                        },
                        error: function (response) {
                            alert("WHOOPS! Something went Wrong!!");
                        }
                    });
                });

                // reload table with search options
                $(document).on('click', '.search', function (event) {
                    event.preventDefault();
                    var form_data = $('#sale_filter_form').serialize();
                    table.ajax.url( 'getSales?'+form_data ).load();
                });

                $(document).on('click', '#refresh_filters', function (event) {
                    event.preventDefault();

                    $('#job_category_filter, #cv_sent_option, #sale_filter_user').prop('selectedIndex',0);
                    $('#job_category_filter, #cv_sent_option, #sale_filter_user').select2();
                    $('#sale_filter_office').empty().append('<option value="">Select Head Office</option>');
                    table.ajax.url( 'getSales' ).load();
                });
                $("#job_category_filter").change(function(){
                    var job=$(this).val();
                    if(job=="nurse"){
                        var options = "";
                        options += "<label>Select Job Profession</label><select name='job_specialist' class='form-control form-control-select2' id='job_specialist_filter' required><option value=''>Select Specialist</option>";
                        options += "<option value='nurse specialist'>Nurse Specialist</option>";
                        options += "</select>";
                        $("#job_specialist_filter").html(options);
                        // alert("nurse");
                    }
                    else if(job=="nonnurse"){
                        var options = "";
                        options += "<label>Select Job Profession</label><select name='job_specialist' class='form-control form-control-select2' id='job_specialist_filter' required><option value=''>Select Specialist</option>";
                        options += "<option value='nonnurse specialist'>Non Nurse Specialist</option>";
                        options += "</select>";
                        $("#job_specialist_filter").html(options);


                    }
                    else{
                        var options = "";
                        options += "<label>Job Specialist Title</label><select name='job_specialist' class='form-control form-control-select2' id='job_specialist_filter' required><option value=''>Select Specialist</option>";
                        options += "<option value='nurse specialist'>Nurse Specialist</option>";
                        options += "<option value='nonnurse specialist'>Non Nurse Specialist</option>";
                        options += "</select>";
                        $("#job_specialist_filter").html(options);
                    }
                });
            </script>
@endsection
