@extends('layouts.app')

@section('style')

    <script>
      $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';
          $('#office_sample_1').DataTable({
               "aoColumnDefs": [{"bSortable": false, "aTargets": [0,10]}],
               "bProcessing": true,
               "bServerSide": true,
               "aaSorting": [[0, "desc"]],
               "sPaginationType": "full_numbers",
               "sAjaxSource": "{{ url('getOffices') }}",
               "aLengthMenu": [[10, 50, 100, 500], [10, 50, 100, 500]]
          });

          // table.destroy();

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
                        <span class="font-weight-semibold">Head Offices</span> - All
                    </h5>
                </div>
            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="#" class="breadcrumb-item"><i class="icon-home2 mr-2"></i> Home</a>
                        <a href="#" class="breadcrumb-item">Current</a>
                        <span class="breadcrumb-item active">Head Offices</span>
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
                    <h5 class="card-title">Active Offices</h5>
                    <div>
                    @can('office_import')
                    <a href="#"
                        data-controls-modal="#import_applicant_csv"
                        data-backdrop="static"
                        data-keyboard="false" data-toggle="modal"
                        data-target="#import_applicant_csv" class="btn bg-slate-800 legitRipple mr-1">
                        <i class="icon-cloud-upload"></i>
                        &nbsp;Import</a>
                    @endcan
						<a href="{{ route('exportHeadOfficesWithNoLatLng')}}" class="btn bg-slate-800 mr-2 legitRipple">
                       <i class="icon-cloud-upload"></i>
                       &nbsp;Export With No LAT & LONG</a>
                    @can('office_create')
                     <a href="{{ route('offices.create') }}" class="btn bg-teal legitRipple float-right"><i class="icon-plus-circle2"></i> Office</a>
                    @endcan
                   
                    </div>
                </div>
                <!-- Head Office CSV Import Modal -->
                @can('office_import')
                <div id="import_applicant_csv" class="modal fade">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Import Office CSV</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('officeCsv') }}" method="post" enctype="multipart/form-data">
                                    @csrf()
                                    <div class="form-group row">
                                        <div class="col-lg-12">
                                            <input type="file" name="office_csv" class="file-input-advanced" data-fouc>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endcan
                <!-- Head Office CSV Import Modal -->

                <table class="table table-hover table-striped" id="office_sample_1">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Head Office</th>
                        <th>Postcode</th>
                        <th>Type</th>
                        <th>Phone</th>
                        <th>Landline</th>
                        <th>Notes</th>
                        @can('office_note-history')
                        <th>Notes History</th>
                        @endcan
                        <th>Status</th>
                        @canany(['office_edit','office_view','office_note-create'])
                        <th>Action</th>
                        @endcanany
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
                        $('#add_office_note' + note_key).modal('hide');
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
        var office = $(this).data('office');

        $.ajax({
            url: "{{ route('notesHistory') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                module_key: office,
                module: "Office"
            },
            success: function(response){
                $('#office_notes_history'+office).html(response);
            },
            error: function(response){
                var raw_html = '<p>WHOOPS! Something Went Wrong!!</p>';
                $('#office_notes_history'+office).html(raw_html);
            }
        });
    });
</script>
@endsection
